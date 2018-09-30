<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\BackendPayment\Model;

/**
 * Cash fos payment method model
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Cashfos extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CASHFOS_CODE = 'cashfos';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CASHFOS_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

}
