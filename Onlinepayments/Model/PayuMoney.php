<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\Onlinepayments\Model;

/**
 * Cash on delivery payment method model
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class PayuMoney extends \Urjakart\Onlinepayments\Model\OnlinePayment
{
    const PAYMENT_METHOD_PAYUMONEY_CODE = 'payumoney';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_PAYUMONEY_CODE;

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
