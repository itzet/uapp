/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Urjakart_Onlinepayments/js/action/set-payment-method',
        'Magento_Catalog/js/price-utils'
    ],
    function ($, ko, Component, quote, setPaymentMethod, priceUtils) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Urjakart_Onlinepayments/payment/payumoney'
            },
            initialize: function () {
                this._super();
            },
            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                setPaymentMethod(this.item.method);
            },

            selectPaymentMethod: function () {
                this._super();

                return this;
            },

            validate: function () {
                return true;
            },

            btnTxt: ko.computed(function () {
                var price = quote.totals().base_grand_total;
                var btnTxt = 'Pay ' + priceUtils.formatPrice(price, quote.getPriceFormat());
                return btnTxt;
            }),
            /**
             * Get value of instruction field.
             * @returns {String}
             */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            }
        });
    }
);