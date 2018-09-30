<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Onlinepayments\Cron;

use Magento\Framework\App\ObjectManager;

/**
 * Class PayUPaymentVerify
 *
 * @package Urjakart\Onlinepayments\Cron
 */
class PayUPaymentVerify
{
    private $connection;

    /**
     * @description This is cron method executing every 10 minutes to check
     * the payment status in case of pending for bank side or in case of tampering.
     * and update the current order status.
     * */
    public function verify()
    {
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')
            ->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $sales_order = $this->connection->getTableName('sales_order');
        $sales_order_payment = $this->connection->getTableName('sales_order_payment');
        date_default_timezone_set("Asia/Kolkata");
        $failed_allowed = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") .' -2 days'));
        $query = 'select so.increment_id, sop.last_trans_id from ' . $sales_order . ' so inner join ';
        $query .= $sales_order_payment . ' sop on so.entity_id=sop.parent_id where ';
        $query .= '(sop.method="creditcard" or sop.method="debitcard" or sop.method="netbanking" or ';
        $query .= 'sop.method="emi") and (so.status="pending" or (so.status="failed" and so.created_at >="';
        $query .= $failed_allowed . '")) and sop.last_trans_id is not null';
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $transaction = $statement->fetchAll();
        $transaction = array_column($transaction, 'last_trans_id');
        $txnArr = array_chunk($transaction,10);
        foreach ($txnArr as $k => $v) {
            $txnArr[$k] = implode('|', $v);
        }
        try {
            $curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
            $onlinePaymentModel = $objectManager->get('Urjakart\Onlinepayments\Model\OnlinePayment');
            $orderFactory = $objectManager->get('Magento\Sales\Model\OrderFactory');
            $orderSender = $objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
            foreach ($txnArr as $txns) {
                $txnRes = $this->checkStatus($onlinePaymentModel, $curl, $txns);
                $this->verifyApiResponse($txnRes, $orderFactory, $orderSender);
            }
        } catch (\Exception $e) {
            $to = "ashishg@urjakart.com";
            $subject = "Exception In Payu verify API";
            $message = '<div style="color:red;font-size:20px;padding:30px;border:2px solid #4285f4">';
            $message .= $e->getMessage() . '</div>';
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Exception<exception@urjakart.com>' . "\r\n";
            @mail($to,$subject,$message,$headers);
        }
    }

    /**
     * @description Call api for check payment status from payu side
     * @param object $onlinePayment \Urjakart\Onlinepayments\Model\OnlinePayment
     * @param object $curl \Magento\Framework\HTTP\Client\Curl
     * @param string $txn_id transaction id of payu payment
     * @return array response of api
     * @throws object
     * */
    private function checkStatus($onlinePaymentModel, $curl, $txnIds) {

        try {
            $key = $onlinePaymentModel->getConfigData("merchant_key");
            $salt = $onlinePaymentModel->getConfigData('salt');
            $wsUrl = $onlinePaymentModel->getInfoUrl();
            $command = "verify_payment";
            $hash_str = $key  . '|' . $command . '|' . $txnIds . '|' . $salt ;
            $hash = strtolower(hash('sha512', $hash_str));
            $data = array('key' => $key, 'hash' => $hash, 'var1' => $txnIds, 'command' => $command);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
            $curl->post($wsUrl, $data);
            $res = json_decode($curl->getBody(),1);
            if (!empty($res['status']) && $res['status'] > 0) {
                return !empty($res['transaction_details']) ? $res['transaction_details'] : [];
            } else {
                return [];
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @description Verify API response
     *
     * @param array $txnRes api response data
     * @param object \Magento\Sales\Model\OrderFactory $orderFactory
     * @param object \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @return boolean
     * @throws object
     * */
    private function verifyApiResponse($txnRes, $orderFactory, $orderSender) {
        try {
            foreach ($txnRes as $txn) {
                $order = $orderFactory->create()->loadByIncrementId($txn['productinfo']);
                if (trim($txn['error_code']) === 'E000' &&
                    (trim($txn['unmappedstatus']) === 'captured' || trim($txn['unmappedstatus']) === 'auth') &&
                    round($txn['transaction_amount']) === round($order->getGrandTotal())
                ) {
                    $payment = $order->getPayment();
                    if ($this->updateOrderStatus($order, $payment, $txn)) {
                        $orderSender->send($order, true, true);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @description Update the order status if payment status
     * has changed at payu side
     *
     * @param object \Magento\Sales\Model\Order $order
     * @param object \Magento\Sales\Model\Order\Payment $payment
     * @param array $response api response data
     * @return boolean
     * @throws object
     * */
    private function updateOrderStatus(
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DataObject $payment,
        $response
    ) {
        try {
            $tableName = $this->connection->getTableName('sales_payment_transaction');
            $query = 'select additional_information from ' . $tableName . ' where txn_id=:txnId';
            $statement = $this->connection->prepare($query);
            $statement->bindParam(':txnId', $response['txnid']);
            $statement->execute();
            $txnInfo = $statement->fetchAll();
            $txnInfo = !empty($txnInfo[0]['additional_information']) ? unserialize($txnInfo[0]['additional_information']) : [];
            $txnInfo['payu_verify_mihpayid'] = $response['mihpayid'];
            $data = [
                'additional_information' => serialize($txnInfo)
            ];
            $this->connection->update($tableName, $data, ['txn_id' => $response['txnid']]);
            $payment->setAdditionalInformation('bank_ref_num', $response['bank_ref_num']);
            $payment->setIsTransactionClosed(0);
            $payment->place();
            $order->setStatus('prepaidverify');
            $order->addStatusHistoryComment($response['field9']);
            $order->save();

            return true;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
?>
