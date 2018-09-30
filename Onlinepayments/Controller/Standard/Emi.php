<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use \Urjakart\Onlinepayments\Controller\OnlinePaymentAbstract;
use \Magento\Framework\App\ObjectManager;

class Emi extends OnlinePaymentAbstract {

    public function execute() {
        $emiData = ['data' => [], 'error' => true];
        if ($this->getRequest()->isAjax()) {
            $objectManager = ObjectManager::getInstance();
            $curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
            $key = $this->_paymentMethod->getConfigData('merchant_key');
            $salt = $this->_paymentMethod->getConfigData('salt');
            $wsUrl = $this->_paymentMethod->getInfoUrl();
            $command = "getEmiAmountAccordingToInterest";
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
            $var1 = $cart->getQuote()->getGrandTotal();
            $hash_str = $key  . '|' . $command . '|' . $var1 . '|' . $salt ;
            $hash = strtolower(hash('sha512', $hash_str));
            $data = array('key' => $key , 'hash' => $hash , 'var1' => $var1, 'command' => $command);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
            $curl->post($wsUrl, $data);
            $res = json_decode($curl->getBody(),1);
            if (count($res) > 0) {
                $emiData['data'] = $res;
                $emiData['error'] = false;
            }
        }

        return $this->resultJsonFactory->create()->setData($emiData);
    }
}
