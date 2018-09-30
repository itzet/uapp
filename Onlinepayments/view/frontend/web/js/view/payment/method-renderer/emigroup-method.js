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
                template: 'Urjakart_Onlinepayments/payment/emigroup'
            },
            initialize: function () {
                this._super();
            },
            redirectAfterPlaceOrder: false,

            selectPaymentMethod: function () {
                this._super();
                var methods = ['emi', 'kisshtpay'];
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

            activeEmi: function () {
                var data = JSON.parse(window.checkoutConfig.payment.onlinepayment.emigroup);
                var price = quote.totals().base_grand_total;
                if (data.emi != null) {
                    if (parseInt(data.emi.active)) {
                        if (data.emi.min_order || data.emi.max_order) {
                            if ( price >= parseInt(data.emi.min_order) && price <= parseInt(data.emi.max_order)) {
                                return data.emi.title;
                            } else {
                                return false;
                            }
                        } else {
                            return data.emi.title;
                        }
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            },

            activeKissht: function () {
                var data = JSON.parse(window.checkoutConfig.payment.onlinepayment.emigroup);
                var price = quote.totals().base_grand_total;
                if (data.kisshtpay != null) {
                    if (parseInt(data.kisshtpay.active)) {
                        if (data.kisshtpay.min_order || data.kisshtpay.max_order) {
                            if ( price >= parseInt(data.kisshtpay.min_order) && price <= parseInt(data.kisshtpay.max_order)) {
                                return data.kisshtpay.title;
                            } else {
                                return false;
                            }
                        } else {
                            return data.kisshtpay.title;
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