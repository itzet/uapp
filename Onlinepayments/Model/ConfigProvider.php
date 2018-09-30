<?php

namespace Urjakart\Onlinepayments\Model;
use Magento\Framework\Escaper;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        \Urjakart\Onlinepayments\Model\CreditCard::PAYMENT_METHOD_CREDITCARD_CODE,
        \Urjakart\Onlinepayments\Model\DebitCard::PAYMENT_METHOD_DEBITCARD_CODE,
        \Urjakart\Onlinepayments\Model\NetBanking::PAYMENT_METHOD_NETBANKING_CODE,
        \Urjakart\Onlinepayments\Model\Emi::PAYMENT_METHOD_EMI_CODE,
        \Urjakart\Onlinepayments\Model\PayuMoney::PAYMENT_METHOD_PAYUMONEY_CODE,
        \Urjakart\Onlinepayments\Model\EmiGroup::PAYMENT_METHOD_EMIGROUP_CODE,
        \Urjakart\Onlinepayments\Model\Wallet::PAYMENT_METHOD_WALLET_CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(\Magento\Payment\Helper\Data $paymentHelper){
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['onlinepayment']['redirectUrl'] = $this->methods['creditcard']->getRedirectUrl();
                $config['payment']['onlinepayment']['emiUrl'] = $this->methods['creditcard']->getEmiUrl();
                $config['payment']['onlinepayment']['cardUrl'] = $this->methods['creditcard']->getCardUrl();
                $config['payment']['onlinepayment']['wallets'] = $this->methods['wallets']->getWalletList();
                $config['payment']['onlinepayment']['emigroup'] = $this->methods['emigroup']->getEMIList();
                $config['payment']['instructions'][$code] = $this->methods[$code]->getInstructions($code);

            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }
}