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
                template: 'Urjakart_Onlinepayments/payment/debitcard'
            },
            initialize: function () {
                this.expMonths = ko.observableArray([
                    { label: 'MM', value: '' },
                    { label: '01', value: '01' }, { label: '02', value: '02' },
                    { label: '03', value: '03' }, { label: '04', value: '04' },
                    { label: '05', value: '05' }, { label: '06', value: '06' },
                    { label: '07', value: '07' }, { label: '08', value: '08' },
                    { label: '09', value: '09' }, { label: '10', value: '10' },
                    { label: '11', value: '11' }, { label: '12', value: '12' }
                ]);
                var date = new Date();
                var year = date.getFullYear();
                var jsonArr = [{ label: 'YY', value: '' }];
                for (var i = 0; i < 20; i++) {
                    var val = year + i;
                    jsonArr.push({
                        label: val.toString(),
                        value: val.toString()
                    });
                }
                this.expYears = ko.observableArray(jsonArr);
                this._super();
            },
            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                setPaymentMethod(this.item.method);
            },

            selectPaymentMethod: function () {
                this._super();
                $('#ccnum_dc').val('');
                $('#ccvv_dc').val('');
                $('#ccexpmon_dc').val('');
                $('#ccexpyr_dc').val('');
                $('#ccnum_dc').css('border', '1px solid #337ab7');
                $('#card_date_dc').css('border', '1px solid #CCC');
                $('#card_cvv_dc').css('border', '1px solid #CCC');
                $('#error-ccnum_dc').css('display', 'none');
                $('#error_ccexpmon_dc').css('display', 'none');
                $('#error_ccexpyr_dc').css('display', 'none');
                $('#error_ccvv_dc').css('display', 'none');

                return this;
            },

            validate: function () {

                var isValidated = true;
                var ccnum = $.trim($('#ccnum_dc').val());
                var ccname = $.trim($('#name_cc').val());
                var exp = /^[A-Za-z ]+$/;
                var cccvv = $.trim($('#ccvv_dc').val());
                var ccexpm = $.trim($('#ccexpmon_dc').val());
                var ccexpy = $.trim($('#ccexpyr_dc').val());
                var cardtype = $.trim($('#cardtype_dc').val());
                var date = new Date();
                var month = date.getMonth();
                var year = date.getFullYear();
                var arrMonth = ['00','01','02','03','04','05','06','07','08','09','10','11','12'];
                var arrYear = [];
                for (var i = 0; i < 20; i++) {
                    arrYear.push((year + i - 1).toString());
                }
                var errorinput = $('#ccnum_dc');
                var errornameinput = $('#name_dc');
                var errorExp = $('#card_date_dc');
                var errorCvv = $('#card_cvv_dc');
                var errorBlockNum = $('#error-ccnum_dc');
                var errorBlockName = $('#error-name_dc');
                var errorBlockMonth = $('#error_ccexpmon_dc');
                var errorBlockYear = $('#error_ccexpyr_dc');
                var errorBlockCVV = $('#error_ccvv_dc');

                if (ccnum === '') {
                    errorinput.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!(ccnum.length === 16 || (ccnum.length === 19 && cardtype === 'MAES'))) {
                    errorinput.css('border', '1px solid #FF0000');
                    errorBlockNum.css('display', 'block');
                    errorBlockNum.text('Invalid card number!');
                    isValidated = false;
                }

                if (ccname === '') {
                    isValidated = true;
                } else if (!exp.test(ccname)) {
                    errornameinput.css('border', '1px solid #FF0000');
                    errorBlockName.css('display', 'block');
                    errorBlockName.text('Name must be a-z, A-Z and space only!');
                    isValidated = false;
                }

                if (cardtype === 'MAES' && cccvv === '' && ccexpm === '' && ccexpy === '') {
                    errorBlockMonth.css('display', 'none');
                    errorBlockMonth.text('');
                    errorExp.css('border', '1px solid #ccc');
                    errorBlockYear.css('display', 'none');
                    errorBlockYear.text('');
                    errorCvv.css('border', '1px solid #ccc');
                    errorBlockCVV.css('display', 'none');
                    errorBlockCVV.text('');
                    return isValidated;
                }

                if (ccexpm === '') {
                    errorExp.css('border', '1px solid #FF0000');
                    isValidated = false;
                }
                if (!$.inArray(ccexpm, arrMonth) ||
                    (year == ccexpy && ccexpm < month + 1)
                ) {
                    errorExp.css('border', '1px solid #FF0000');
                    errorBlockMonth.css('display', 'block');
                    errorBlockMonth.text('Invalid card expiry month!');
                    isValidated = false;
                }
                if (ccexpy === '') {
                    errorExp.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!$.inArray(ccexpy, arrYear) || ccexpy < year) {
                    errorExp.css('border', '1px solid #FF0000');
                    errorBlockYear.css('display', 'block');
                    errorBlockYear.text('Invalid card expiry year!');
                    isValidated = false;
                }
                if (ccexpm !== '' && ccexpy === '') {
                    errorBlockMonth.css('display', 'block');
                    errorBlockMonth.text('Please select card expiry year!');
                }
                if (ccexpm === '' && ccexpy !== '') {
                    errorBlockMonth.css('display', 'block');
                    errorBlockMonth.text('Please select card expiry month!');
                }
                if (cccvv === '') {
                    errorCvv.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!((cccvv.length === 4 && cardtype === 'AMEX') || (cccvv.length === 3 && cardtype !== 'AMEX'))) {
                    errorCvv.css('border', '1px solid #FF0000');
                    errorBlockCVV.css('display', 'block');
                    errorBlockCVV.text('Invalid CVV!');
                    isValidated = false;
                }
                return isValidated;
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