/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals',
        'Urjakart_PinCodeValidator/js/view/unselect'
    ],
    function ($, Component, quote, priceUtils, totals) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magento_OfflinePayments/payment/cashondelivery'
            },

            initialize: function () {

                this._super();
                var pin = quote.shippingAddress().postcode;
                if (pin != '' && window.isBackendCodAvailable === 'yes') {
                    $.ajax({
                        url: window.checkoutConfig.payment.cod.url + '?pincode=' + pin,
                        type: 'post',
                        context: this,
                        data: {isAjax: 1},
                        dataType: 'json',
                        async: false,
                        success: function (response) {
                            if (response.head.status) {
                                window.checkoutConfig.validCod = {
                                    "isValidCod" : true,
                                    "msg" : response.body.msg,
                                    "codFee" : response.body.data.cod_fees
                                };
                            } else {
                                window.checkoutConfig.validCod = {
                                    "isValidCod" : false,
                                    "msg" : response.body.msg,
                                    "codFee" : response.body.data.cod_fees
                                };
                            }
                        },
                        error: function (error) {
                            window.checkoutConfig.validCod = {
                                "isValidCod" : false,
                                "msg" : "unknown error",
                                "codFee" : 0
                            };
                        }
                    });
                }
                return this;
            },

            selectPaymentMethod: function () {
                this._super();
                window.payment.method = this.item.method;
                if (this.isCodEnabled()) {
                    $('#cod-fee-block').attr('style', '');
                    var fee = window.checkoutConfig.validCod.codFee;
                    var price = priceUtils.formatPrice(fee, quote.getPriceFormat());;
                    $('#cod-fee-amount').text(price);
                } else {
                    $('#btn_cod').css('display', 'none');
                }

                return this;
            },

            isCodEnabled : function () {
                if (window.isBackendCodAvailable === 'yes')
                    return window.checkoutConfig.validCod.isValidCod;
                else
                    return false;
            },

            getCodMsg : function () {
                if (window.isBackendCodAvailable === 'yes')
                    return window.checkoutConfig.validCod.msg;
                else
                    return 'COD is not available for current item(s)';
                    //return 'COD is not available for ' + window.CodNoSku;
            },

            validate : function () {
                return this.isCodEnabled();
            },

            /** Returns payment method instructions */
            getInstructions: function() {
                if (this.isCodEnabled())
                    return window.checkoutConfig.payment.instructions[this.item.method];
                else
                    return false;
            }
        });
    }
);
