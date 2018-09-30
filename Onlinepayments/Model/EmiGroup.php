<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\Onlinepayments\Model;

/**
 * EMI Group payment method model to grouped all EMI
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class EmiGroup extends \Urjakart\Onlinepayments\Model\OnlinePayment
{
    const PAYMENT_METHOD_EMIGROUP_CODE = 'emigroup';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_EMIGROUP_CODE;

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
