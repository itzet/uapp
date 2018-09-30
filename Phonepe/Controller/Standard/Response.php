<?php

namespace Urjakart\Phonepe\Controller\Standard;

use Urjakart\Phonepe\Controller\PhonePeAbstract;
use Magento\Framework\App\ObjectManager;

class Response extends PhonePeAbstract {

    const MSG_API_URL = 'https://api.urjakart.com/sms.php';

    private $_curl;

    public function execute() {
        $returnUrl = $this->getCheckoutHelper()->getUrl('checkout');

        $objectManager = ObjectManager::getInstance();
        $this->_curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
        try {
            $paymentMethod = $this->getPaymentMethod();
            $params = $this->getRequest()->getParams();
            $order = $this->getOrder();
            // This is only to save row response data in uk_payment_response table.
            $db_conn = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $tableName = $db_conn->getTableName('uk_payment_response');
            $rowResponse = [
                'order_id' => $order->getIncrementId(),
                'type_name' => $paymentMethod->getCode(),
                'data' => serialize($params)
            ];
            $db_conn->insert($tableName, $rowResponse);
            $db_conn->closeConnection();
            // end
            $code = !empty($params['code']) ? $params['code'] : '';
            if ($paymentMethod->validateResponse($params)) {
                $payment = $order->getPayment();
                $paymentMethod->postProcessing($order, $payment, $params);
                if ($code === 'PAYMENT_PENDING') {
                    $this->messageManager->addNoticeMessage(__('Your payment status is pending from your bank side, please wait and check after 30 minute'));
                    $entity_id = $order->getEntityId();
                    $returnUrl = $this->getCheckoutHelper()->getUrl('sales/order/view/order_id/' . $entity_id);
                } else {
                    $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                    $orderSender = $objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
                    $orderSender->send($order, true, true);
                }
                $this->sendSms($order, $code);
            } else { //$paymentMethod->responseError($code, $params)
                $this->messageManager->addErrorMessage(__('Transaction failed please try again'));
                $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/cart');
                $payment = $order->getPayment();
                $paymentMethod->postProcessing($order, $payment, $params);
                $this->sendSms($order, $code);
                $this->_checkoutHelper->restoreQuote();
                $this->getCustomerSession()->setOrderId($order->getRealOrderId());
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t place the order.'));
        }

        $this->getResponse()->setRedirect($returnUrl);
    }

    private function sendSms($order, $code) {

        $orderId = $order->getRealOrderId();
        $grandTotal = $order->getGrandTotal();
        $firstName = $order->getCustomerName();
        $billing_address = $order->getBillingAddress();
        $mobile = $billing_address->getTelephone();
        $status = $code;
        if ($code === 'PAYMENT_SUCCESS') {
            $msg = 'Confirmation%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20we%20have%20received%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . number_format($grandTotal, 0) . ',%20and%20it%20is%20being%20processed.';
        } elseif ($code === 'PAYMENT_PENDING') {
            $msg = 'Notification%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20we%20have%20received%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . number_format($grandTotal, 0) . ',%20but%20payment%20is%20pending.';
        } else {
            $msg = 'Cancellation%20of%20Order%20No%20'.$orderId.':%20Dear%20' .$firstName. ',%20your%20order%20no.%20'.$orderId.',%20amounting%20to%20Rs.'.$grandTotal.'%20has%20been%20cancelled%20due%20to%20payment%20' . $status;
        }
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