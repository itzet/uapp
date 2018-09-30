<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use \Urjakart\Onlinepayments\Controller\OnlinePaymentAbstract;
use Magento\Framework\App\ObjectManager;

class Response extends OnlinePaymentAbstract {

    const MSG_API_URL = 'https://api.urjakart.com/sms.php';

    private $_curl;

    public function execute() {
        $returnUrl = $this->getCheckoutHelper()->getUrl('checkout');
        $auth = $this->isAuthorized();
        if ($auth) {
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
                    'data' => serialize($params)
                ];
                $db_conn->update($tableName, $rowResponse, ['order_id' => $order->getIncrementId()]);
                $db_conn->closeConnection();
                // end
                if ($paymentMethod->validateResponse($params)) {
                    if ($paymentMethod->validateHash($params)) {
                        $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                        $payment = $order->getPayment();
                        $paymentMethod->postProcessing($order, $payment, $params);
                        $orderSender = $objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
                        $orderSender->send($order, true, true);
                        $this->sendSms($params);
                    } else {
                        $this->messageManager->addErrorMessage(__('Fraud detected, try to make a valid transaction.'));
                        $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/cart');
                        $payment = $order->getPayment();
                        $params['flag'] = 'fraud';
                        $paymentMethod->postProcessing($order, $payment, $params);
                        $this->sendSms($params);
                        $this->_checkoutHelper->restoreQuote();
                        $this->getCustomerSession()->setOrderId($order->getRealOrderId());
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method'));
                    $returnUrl = $this->getCheckoutHelper()->getUrl('checkout/cart');
                    $payment = $order->getPayment();
                    $paymentMethod->postProcessing($order, $payment, $params);
                    $this->sendSms($params);
                    $this->_checkoutHelper->restoreQuote();
                    $this->getCustomerSession()->setOrderId($order->getRealOrderId());
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('We can\'t place the order.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Your are not authorize!'));
        }

        $this->getResponse()->setRedirect($returnUrl);
    }

    private function sendSms($response) {

        $orderId = $response['productinfo'];
        $grandTotal = $response['net_amount_debit'];
        $firstName = $response['firstname'];
        if (!empty($response['flag']) && $response['flag'] === 'fraud') {
            $mobile = '9555483332,7042154089,8285860138,7065443131,7834980993';
            $email = $response['email'];
            date_default_timezone_set("Asia/Kolkata");
            $msg = urlencode('Fraud of Order No: ' . $orderId . ' performed By ' . $firstName . ', email is ' . $email . ', amount of Rs.' . $grandTotal . ' at ' . date("Y-m-d H:i:s"));

        } else {
            $mobile = $response['phone'];
            $status = $response['status'];
            if ($response['status'] === 'success') {
                $msg = 'Confirmation%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20we%20have%20received%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . number_format($grandTotal, 0) . ',%20and%20it%20is%20being%20processed.';
            } else {
                $msg = 'Cancellation%20of%20Order%20No%20' . $orderId . ':%20Dear%20' . $firstName . ',%20your%20order%20no.%20' . $orderId . ',%20amounting%20to%20Rs.' . $grandTotal . '%20has%20been%20cancelled%20due%20to%20payment%20' . $status;
            }
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

    private function isAuthorized() {
        $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $isRefererProd = strpos($referer, 'secure.payu.in') ? true : false;
        $isRefererTest = strpos($referer, 'test.payu.in') ? true : false;

        return $isRefererProd || $isRefererTest;
    }
}
