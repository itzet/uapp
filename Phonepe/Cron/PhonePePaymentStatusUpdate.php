<?php
/**
 * Copyright © Urjakart. All rights reserved.
 */
namespace Urjakart\Phonepe\Cron;

use Magento\Framework\App\ObjectManager;

/**
 * Class PhonePePaymentStatusUpdate
 *
 * @package Urjakart\Phonepe\Cron
 */
class PhonePePaymentStatusUpdate
{
    private $connection;

    /*
     * @description This is cron method executing every 30 to check
     * the payment status in case of pending for bank side.
     * and update the current order status.
     * */
    public function updateStatus()
    {
        $objectManager = ObjectManager::getInstance();
        $this->connection = $objectManager->get('Magento\Framework\App\ResourceConnection')
            ->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $sales_order = $this->connection->getTableName('sales_order');
        $sales_order_payment = $this->connection->getTableName('sales_order_payment');
        $query = 'select so.increment_id, sop.last_trans_id from ' . $sales_order . ' so inner join ';
        $query .= $sales_order_payment . ' sop on so.entity_id=sop.parent_id where ';
        $query .= 'sop.method="phonepe" and so.status="pending_payment" and sop.last_trans_id is not null';
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $transaction = $statement->fetchAll();
        $phonepeModel = $objectManager->get('Urjakart\Phonepe\Model\PhonePe');
        $orderFactory = $objectManager->get('Magento\Sales\Model\OrderFactory');
        $orderSender = $objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
        foreach ($transaction as $payment) {
            $response = $this->checkStatus($phonepeModel, $payment['last_trans_id']);
            $order = $orderFactory->create()->loadByIncrementId($payment['increment_id']);
            $payment = $order->getPayment();
            if ($this->updateOrderStatus($order, $payment, $response)) {
                $orderSender->send($order, true, true);
            }
        }
    }

    /*
     * @description Call api for check payment status from phonepe side
     * @param object $phonepeModel Urjakart\Phonepe\Model\PhonePe
     * @param string $txn_id transaction id of phonepe payment
     * @return array response of api
     * */
    private function checkStatus($phonepeModel, $txn_id) {

        $merchant_id = $phonepeModel->getConfigData("merchant_key");
        $salt_key = $phonepeModel->getConfigData('salt');
        $salt_index = $phonepeModel->getConfigData('salt_index');
        $request = '/' . $phonepeModel->getApiVersion() . '/transaction/' . $merchant_id;
        $request .= '/' . $txn_id . '/status';
        $header = hash('sha256', $request . $salt_key) . "###" . $salt_index;
        $wsUrl = $phonepeModel->getCgiUrl() . $request;
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $wsUrl);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'X-VERIFY:' . $header
        ]);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_TIMEOUT, 30);
        $status_res = curl_exec($c);
        curl_close($c);

        return json_decode($status_res, 1);
    }

    /*
     * @description Update the order status if payment status
     * has changed at phonepe side.
     *
     * @param object \Magento\Sales\Model\Order $order
     * @param object \Magento\Sales\Model\Order\Payment $payment
     * @param array verify api response data
     * @return boolean
     * */
    private function updateOrderStatus(
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\DataObject $payment,
        $response
    ) {
        if (!empty($response['success']) && $response['success']) {
            if (!empty($response['code']) && $response['code'] === 'PAYMENT_SUCCESS') {
                $payment->setAdditionalInformation('phone_pe_payment_status', $response['data']['paymentState']);
                $payment->setAdditionalInformation('pay_response_code', $response['data']['payResponseCode']);
                $payment->setIsTransactionClosed(0);
                $payment->place();
                $order->setStatus('prepaidverify');
                $order->save();

                return true;
            }
        }

        return false;
    }
}
?>