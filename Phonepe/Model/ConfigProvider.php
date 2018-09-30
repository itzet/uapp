<?php

namespace Urjakart\Phonepe\Model;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $methodCode = \Urjakart\Phonepe\Model\PhonePe::PAYMENT_PHONEPE_CODE;

    protected $method;

    public function __construct(\Magento\Payment\Helper\Data $paymenthelper){
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
    }

    public function getConfig() {

        return $this->method->isAvailable() ? [
            'payment'=> [ 'phonepe' => [
                'redirectUrl'  => $this->method->getRedirectUrl(),
                'instructions' => $this->method->getInstructions()
            ]
        ]
        ]:[];
    }
}
