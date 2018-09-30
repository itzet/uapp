<?php

namespace Urjakart\PinCodeValidator\Controller\Standard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ObjectManager;

class Validator extends Action {

    const API_URL = 'https://api.urjakart.com/pincheck.php';
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute() {
        $params = [];
        if ($this->getRequest()->isAjax()) {
            $pinCode = $this->getRequest()->getParam('pincode');
            $objectManager = ObjectManager::getInstance();
            $curl = $objectManager->get('Magento\Framework\HTTP\Client\Curl');
            $wsUrl = self::API_URL . '?token=' . $this->getToken() . '&pin=' . trim($pinCode);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, 30);
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 1);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, 1);
          //  $curl->setOption(CURLOPT_HTTPHEADER, ['token : ' . $this->getToken()]);
            $curl->get($wsUrl);
            $pinData = json_decode($curl->getBody(),1);

            $params = $pinData;
        }

        return $this->resultJsonFactory->create()->setData($params);
    }

    private function getToken() {
        $objectManager = ObjectManager::getInstance();
        $connection = $objectManager
            ->get('Magento\Framework\App\ResourceConnection')
            ->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $tableName = $connection->getTableName('access_list');
        $token = $connection->fetchRow("select token from $tableName");

        return !empty($token['token']) ? $token['token'] : $token;
    }
}
