<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use \Urjakart\Onlinepayments\Controller\OnlinePaymentAbstract;
use Magento\Framework\App\ObjectManager;

class Cancel extends OnlinePaymentAbstract {

    const MSG_API_URL = 'https://api.urjakart.com/sms.php';

    private $_curl;

    public function execute() {

        try {
            $objectManager = ObjectManager::getInstance();
            $this->_curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
            $paymentMethod = $this->getPaymentMethod();
            $params = $this->getRequest()->getParams();
            $order = $this->getOrder();
            // This is only to save row response data in uk_payment_response table.
            $db_conn = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $tableName = $db_conn->getTableName('uk_payment_response');
            $rowResponse = [
                'data' => serialize($params)
            ];
            $db_conn->update($tableName, $rowResponse, ['order_id' => $order->getIncrementId()]);
            $db_conn->closeConnection();
            // end
            $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method'));
            $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/cart');
            $payment = $order->getPayment();
            $paymentMethod->postProcessing($order, $payment, $params);
            $this->sendSms($params);
            $this->_checkoutHelper->restoreQuote();
            $this->getCustomerSession()->setOrderId($order->getRealOrderId());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t place the order.'));
        }

        $this->getResponse()->setRedirect($returnUrl);
    }

    private function sendSms($response) {

        $orderId = $response['productinfo'];
        $grandTotal = $response['net_amount_debit'];
        $firstName = $response['firstname'];
        $mobile = $response['phone'];
        $status = $response['status'];
        $msg = 'Cancellation%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . $grandTotal . '%20has%20been%20cancelled%20due%20to%20payment%20' . $status;
        $this->callSmsApi($msg, $mobile);
    }

    private function callSmsApi($msg, $mobile) {
        try {
            $url = self::MSG_API_URL;
            $url .= '?msg=' . $msg;
            $url .= '&rec=' . $mobile;
            $url .= '&from=URJKRT';
            $this->_curl->get($url);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

}