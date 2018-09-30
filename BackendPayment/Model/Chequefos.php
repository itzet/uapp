<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Urjakart\BackendPayment\Model;

/**
 * Class Chequefos
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 */
class Chequefos extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CHEQUEFOS_CODE = 'chequefos';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CHEQUEFOS_CODE;


    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
