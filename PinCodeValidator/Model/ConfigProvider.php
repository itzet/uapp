<?php

namespace Urjakart\PinCodeValidator\Model;

use \Magento\Framework\App\ObjectManager;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE;

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod
     */
    protected $method;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(\Magento\Payment\Helper\Data $paymenthelper) {
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {

        $objectManager = ObjectManager::getInstance();
        $url = $objectManager->get('Magento\Framework\Url');

        return $this->method->isAvailable() ? [
            'payment' => [
                    'cod' => [
                        'url' => $url->getUrl('codvalidator/standard/validator')
                    ]
            ]
        ]:[];
    }
}