/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Urjakart_Onlinepayments/payment/wallets'
            },
            initialize: function () {
                this._super();
            },
            redirectAfterPlaceOrder: false,

            selectPaymentMethod: function () {
                this._super();
                var methods = ['payumoney', 'paytm'];
                var method = $('input[type="radio"]:checked').val();
                $('#' + method + '-block').css('display', 'block');
                $('#' + method + '-block').find('button').attr('disabled', false);
                for(var i=0; i < methods.length; i++) {
                    if (methods[i] === method) {
                        $('#' + method).parent().find('i').addClass('active-wlt');
                    } else {
                        $('#' + methods[i] + '-block').css('display', 'none');
                        $('#' + methods[i]).parent().find('i').removeClass('active-wlt');
                        $("#emiRateTable").html('');
                        $("#emi-card-form").css('display', 'none');
                    }
                }
                return this;
            },

            activePayu: function () {
                var data = JSON.parse(window.checkoutConfig.payment.onlinepayment.wallets);
                var price = quote.totals().base_grand_total;
                if (data.payumoney != null) {
                    if (parseInt(data.payumoney.active)) {
                        if (data.payumoney.min_order || data.payumoney.max_order) {
                            if ( price >= parseInt(data.payumoney.min_order) && price <= parseInt(data.payumoney.max_order)) {
                                return data.payumoney.title;
                            } else {
                                return false;
                            }
                        } else {
                            return data.payumoney.title;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            },

            activePaytm: function () {
                var data = JSON.parse(window.checkoutConfig.payment.onlinepayment.wallets);
                var price = quote.totals().base_grand_total;
                if (data.paytm != null) {
                    if (parseInt(data.paytm.active)) {
                        if (data.paytm.min_order || data.paytm.max_order) {
                            if ( price >= parseInt(data.paytm.min_order) && price <= parseInt(data.paytm.max_order)) {
                                return data.paytm.title;
                            } else {
                                return false;
                            }
                        } else {
                            return data.paytm.title;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            },

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