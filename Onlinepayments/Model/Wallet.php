<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\Onlinepayments\Model;

/**
 * Wallet payment method model model to grouped all EMI
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Wallet extends \Urjakart\Onlinepayments\Model\OnlinePayment
{
    const PAYMENT_METHOD_WALLET_CODE = 'wallets';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_WALLET_CODE;

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
