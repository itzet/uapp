<?php
namespace Urjakart\Smsnotifications\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\App\ObjectManager;

class SmsNotificationsObserver implements ObserverInterface {

    const MSG_API_URL = 'https://api.urjakart.com/sms.php';

    private $_curl;

    public function execute(Observer $observer) {
        $objectManager = ObjectManager::getInstance();
        $this->_curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
        try {
            $order = $observer->getEvent()->getOrder();
            $OldStatus = $order->getOrigData('status');
            $NewStatus = $order->getStatus();
            $mobile = '';
            /* Fire sms when New Status  and old Status are not same */
            if ($OldStatus != $NewStatus) {
                $realOrderId = $order->getRealOrderId();
                $firstName = $order->getShippingAddress()->getFirstname();
                $mobile = $order->getShippingAddress()->getTelephone();
                $grandTotal = number_format($order->getGrandTotal(), 0);

                /*if ($order->getStatus() == "pending") {
                  if ($mobile != '') {
                      $msg ='Confirmation%20of%20Order%20No%20'.$realOrderId.':%20Dear%20'.$firstName.',%20we%20have%20received%20your%20order%20no.%20'.$realOrderId.',%20amounting%20to%20Rs.'.$grandTotal.',%20and%20it%20is%20being%20processed.';
                      $url = self::MSG_API_URL;
                      $url .= '?msg=' . $msg;
                      $url .= '&rec=' . $mobile;
                      $url .= '&from=URJKRT';
                      $this->_curl->get($url);
                  }
                }*/

                if ($order->getStatus() == "canceled") {
                    if ($mobile != '') {
                        $msg = 'Cancellation%20of%20Order%20No%20'.$realOrderId.':%20Dear%20' .$firstName. ',%20your%20order%20no.%20'.$realOrderId.',%20amounting%20to%20Rs.'.$grandTotal.'%20has%20been%20cancelled.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }

                if ($order->getStatus() == "prepaidverify") {
                    if ($mobile != '') {
                        $msg = 'Payment%20received%20for%20Order%20No%20'. $realOrderId . '.%20It%20is%20being%20processed%20and%20soon%20will%20be%20dispatched.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }

                if ($order->getStatus() == "codverify") {
                    if ($mobile != '') {
                        $msg = 'Order%20No%20'. $realOrderId .'%20is%20confirmed%20after%20COD%20verification.%20It%20is%20being%20processed%20and%20soon%20will%20be%20disptached.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }

                if ($order->getStatus() == "holded") {
                    if ($mobile != '') {
                        $msg = 'Order%20No%20'. $realOrderId .'%20is%20on%20hold%20due%20to%20verification%20fail.%20Please%20contact%20our%20support%20team%20at%20the%20earliest.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }

                if ($order->getStatus() == "ordshp") {
                    if ($mobile != '') {
                        $msg = 'Dispatched%20Order%20No%20'. $realOrderId .':%20Your%20package%20has%20been%20dispatched%20from%20our%20warehouse.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }

                if ($order->getStatus() == "complete") {
                    if ($mobile != '') {
                        $msg = 'Delivered%20Order%20No%20'. $realOrderId .':%20Dear%20'. $firstName .',%20you%20order%20no.%20'. $realOrderId .'%20has%20been%20delivered.';
                        $url = self::MSG_API_URL;
                        $url .= '?msg=' . $msg;
                        $url .= '&rec=' . $mobile;
                        $url .= '&from=URJKRT';
                        $this->_curl->get($url);
                    }
                }
            }
        } catch (\Exception $e) {

        }
    }
}