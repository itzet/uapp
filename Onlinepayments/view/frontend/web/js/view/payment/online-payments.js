/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'creditcard',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/creditcard-method'
            },
            {
                type: 'debitcard',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/debitcard-method'
            },
            {
                type: 'netbanking',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/netbanking-method'
            },
            {
                type: 'emi',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/emi-method'
            },
            {
                type: 'payumoney',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/payumoney-method'
            },
            {
                type: 'wallets',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/wallets-method'
            },
            {
                type: 'emigroup',
                component: 'Urjakart_Onlinepayments/js/view/payment/method-renderer/emigroup-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
