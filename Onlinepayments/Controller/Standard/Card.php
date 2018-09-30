<?php

namespace Urjakart\Onlinepayments\Controller\Standard;

use \Urjakart\Onlinepayments\Controller\OnlinePaymentAbstract;
use \Magento\Framework\App\ObjectManager;

class Card extends OnlinePaymentAbstract {

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    public function execute()
    {
        $response = [];
        $bin = $this->getRequest()->getParam('bin');
        if ($bin) {
            $this->objectManager = ObjectManager::getInstance();
            $binData = $this->processRequest($bin);
            $response = $this->validateResponse($binData);
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

    private function processRequest($bin)
    {
        $curl = $this->objectManager->get('Magento\Framework\HTTP\Client\Curl');
        $key = $this->_paymentMethod->getConfigData('merchant_key');
        $salt = $this->_paymentMethod->getConfigData('salt');
        $wsUrl = $this->_paymentMethod->getInfoUrl();
        $command = "check_isDomestic";
        $hash_str = $key  . '|' . $command . '|' . $bin . '|' . $salt ;
        $hash = strtolower(hash('sha512', $hash_str));
        $data = array('key' => $key , 'hash' =>$hash , 'var1' => $bin, 'command' => $command);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
        $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->post($wsUrl, $data);

        return json_decode($curl->getBody(),1);
    }

    private function validateResponse($binData)
    {
        $response = [];
        $isDomestic = !empty($binData['isDomestic']) ? strtolower($binData['isDomestic']) : false;
        $issuingBank = !empty($binData['issuingBank']) ? strtolower($binData['issuingBank']) : false;
        $cardType = !empty($binData['cardType']) ? $binData['cardType'] : false;
        $cardCategory = !empty($binData['cardCategory']) ? $binData['cardCategory'] : false;
        $isValid = (
            $isDomestic !== 'unknown' &&
            $issuingBank !== 'unknown' &&
            $cardType !== 'unknown' &&
            $cardCategory !== 'unknown'
        );
        if ($isValid) {
            $response['error'] = false;
            $response['msg'] = '';
            $response['data'] = $binData;
        } else {
            $response['error'] = true;
            $response['msg'] = 'Invalid card number!';
            $response['data'] = '';
        }

        return $response;
    }
}
