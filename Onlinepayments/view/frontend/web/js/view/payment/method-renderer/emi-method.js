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
                template: 'Urjakart_Onlinepayments/payment/emi'
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
                $.ajax({
                    url: window.checkoutConfig.payment.onlinepayment.emiUrl,
                    type: 'post',
                    context: this,
                    data: {isAjax: 1},
                    dataType: 'json',
                    async: false,
                    success: function (response) {
                        if (!response.error) {
                            var validData = {
                                "HSBC": "HSBC Credit Card",
                                "KOTAK": "KOTAK Credit Card",
                                "21": "ICICI Credit Card",
                                "INDUS": "INDUSIND Credit Card",
                                "7": "AXIS Credit Card",
                                "15": "HDFC Credit Card",
                                "20": "CITIBANK Credit Card",
                                "SBI": "SBI Credit Card"
                            };
                            var emiArr = [];
                            for (var val in response.data) {
                                emiArr.push({
                                    label: validData[val],
                                    value: val
                                });
                            }
                            emiArr.sort(function(a, b) {
                                return a.label < b.label ? -1 : 1;
                            });
                            emiArr[0] = { label: 'Select Bank', value: '' };
                            this.emiOptions = ko.observableArray(emiArr);
                            window.checkoutConfig.payment.emiData = response.data;
                            window.checkoutConfig.payment.emiEnabled = true;
                        } else {
                            this.emiOptions = ko.observableArray([{ label: 'Select Bank', value: '' }]);
                            window.checkoutConfig.payment.emiEnabled = false;
                            window.checkoutConfig.payment.errMsg = 'EMI not available for this amount!';
                        }
                    },
                    error: function (error) {
                        this.emiOptions = ko.observableArray([{ label: 'Select Bank', value: '' }]);
                        window.checkoutConfig.payment.emiEnabled = false;
                        window.checkoutConfig.payment.errMsg = 'Unknown error with emi!';
                    }
                });

                return this;
            },
            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                setPaymentMethod(this.item.method);
            },

            selectPaymentMethod: function () {
                this._super();
                if (!window.checkoutConfig.payment.emiEnabled) {
                    $('#emi-not-err').text(window.checkoutConfig.payment.errMsg);
                    $('#emi-not-err').css('display', 'block');
                    $('#cardtype_emi').css('display', 'none');
                }
                $('#cardtype_emi').val('');
                $('#emiRateTable').html('');
                $('#ccnum_emi').val('');
                $('#ccvv_emi').val('');
                $('#ccexpmon_emi').val('');
                $('#ccexpyr_emi').val('');
                $('#cardtype_emi').css('border', '1px solid #CCC');
                $('#ccnum_emi').css('border', '1px solid #CCC');
                $('#card_date_emi').css('border', '1px solid #CCC');
                $('#card_cvv_emi').css('border', '1px solid #CCC');
                $('#error-cardtype-emi').css('display', 'none');
                $('#error-emitype-emi').css('display', 'none');
                $('#error-ccnum_emi').css('display', 'none');
                $('#error_ccexpmon_emi').css('display', 'none');
                $('#error_ccexpyr_emi').css('display', 'none');
                $('#error_ccvv_emi').css('display', 'none');
                $('#emi-card-form').css('display', 'none');

                return this;
            },

            validate: function () {

                var isValidated = true;
                var banks = [
                    "FIRST", "HSBC", "KOTAK", "21", "INDUS", "7", "15",
                    "20", "AXIS", "SBI"
                ];
                var errorBankEmi = $('#error-cardtype-emi');
                var errorEmiType = $('#error-emitype-emi');
                var emiBank = $('#cardtype_emi');
                var emiType = $.trim($('input[name="emimonth"]:checked').val());
                if ($.trim(emiBank.val()) === '') {
                    errorBankEmi.text('Please choose bank!');
                    errorBankEmi.css('display', 'block');
                    emiBank.css('border', '1px solid #FF0000');
                    return false;
                } else if (!$.inArray($.trim(emiBank.val()), banks)) {
                    errorBankEmi.text('please choose other bank!');
                    errorBankEmi.css('display', 'block');
                    emiBank.css('border', '1px solid #FF0000');
                    return false;
                }
                if (emiType === '') {
                    errorEmiType.text('Please select emi option!');
                    errorEmiType.css('display', 'block');
                    $('#emiRateTable').css('border', '1px solid #FF0000');
                    return false;
                }

                var ccnum = $.trim($('#ccnum_emi').val());
                var ccname = $.trim($('#name_emi').val());
                var exp = /^[A-Za-z ]+$/;
                var cccvv = $.trim($('#ccvv_emi').val());
                var ccexpm = $.trim($('#ccexpmon_emi').val());
                var ccexpy = $.trim($('#ccexpyr_emi').val());
                var cardtype = $.trim($('#cardtype_em').val());
                var ccnum_err = $('#error-ccnum_emi');
                var errornameinput = $('#name_emi');
                var errorBlockName = $('#error-name_emi');
                var cexpmon_err = $('#error_ccexpmon_emi');
                var cexpyr_err = $('#error_ccexpyr_emi');
                var cvv_err = $('#error_ccvv_emi');
                var exp_err = $('#emi_card_date');
                var ecvv_err = $('#emi_card_cvv');
                var date = new Date();
                var month = date.getMonth();
                var year = date.getFullYear();
                var arrMonth = ['00','01','02','03','04','05','06','07','08','09','10','11','12'];
                var arrYear = [];
                for (var i = 0; i < 20; i++) {
                    arrYear.push((year + i - 1).toString());
                }
                var len;

                if (ccnum === '') {
                    $('#ccnum_emi').css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (len = this.validateLength(ccnum.length, cardtype)) {
                    ccnum_err.css('display', 'block');
                    if (len === 'less')
                        ccnum_err.text('The actual card length is less than the required length');
                    else if (len === 'greater')
                        ccnum_err.text('The actual card length is greater than the required length');
                    $('#ccnum_emi').css('border', '1px solid #FF0000');
                    isValidated = false;
                }
                if (ccname === '') {
                    errornameinput.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!exp.test(ccname)) {
                    errornameinput.css('border', '1px solid #FF0000');
                    errorBlockName.css('display', 'block');
                    errorBlockName.text('Name must be a-z, A-Z and space only!');
                    isValidated = false;
                }
                if (ccexpm === '') {
                    exp_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                }
                if (!$.inArray(ccexpm, arrMonth) ||
                    (year == ccexpy && ccexpm < month + 1)
                ) {
                    cexpmon_err.css('display', 'block');
                    cexpmon_err.text('Invalid card expiry month!');
                    exp_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                }
                if (ccexpy === '') {
                    exp_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!$.inArray(ccexpy, arrYear) || ccexpy < year) {
                    cexpyr_err.css('display', 'block');
                    cexpyr_err.text('Invalid card expiry year!');
                    exp_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                }
                if (ccexpm !== '' && ccexpy === '') {
                    cexpmon_err.css('display', 'block');
                    cexpmon_err.text('Please select card expiry year!');
                }
                if (ccexpm === '' && ccexpy !== '') {
                    cexpmon_err.css('display', 'block');
                    cexpmon_err.text('Please select card expiry month!');
                }
                if (cccvv === '') {
                    ecvv_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (!((cccvv.length === 4 && cardtype === 'AMEX') || (cccvv.length === 3 && cardtype !== 'AMEX'))) {
                    cvv_err.css('display', 'block');
                    cvv_err.text('Invalid CVV!');
                    ecvv_err.css('border', '1px solid #FF0000');
                    isValidated = false;
                }

                return isValidated;
            },

            validateLength: function (len, type) {
                if ((type === 'MAST' || type === 'VISA') && len < 16) {
                    return 'less';
                } else if ((type === 'MAST' || type === 'VISA') && len > 16) {
                    return 'greater';
                } else if (type === 'AMEX' && len < 15) {
                    return 'less';
                } else if (type === 'AMEX' && len > 15) {
                    return 'greater';
                } else if (type === 'DINR' && len < 14) {
                    return 'less';
                } else if (type === 'DINR' && len > 14) {
                    return 'greater';
                }

                return false;
            },

            btnTxt: ko.computed(function () {
                var price = quote.totals().base_grand_total;
                var btnTxt = 'Continue Checkout ' + priceUtils.formatPrice(price, quote.getPriceFormat());
                return btnTxt;
            }),

            isEmiEnabled : function () {
                return window.checkoutConfig.payment.emiEnabled;
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