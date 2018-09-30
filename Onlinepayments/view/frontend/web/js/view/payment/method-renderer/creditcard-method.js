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
                template: 'Urjakart_Onlinepayments/payment/creditcard'
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
                // This condition added by ashish for stop loading default payment method
                if (!(typeof window.isBillingAddressUpdate != 'undefined' && window.isBillingAddressUpdate === 1)) {
                    this.selectPaymentMethod(); // for default selected payment method
                    //$('#creditcard').attr('disabled', 'disabled');
                }
                // end

                /* mixPanel */
                if(typeof window.xyz == 'undefined'){
                    mixpanel.track(
                        "Billing Info Entered",
                        {
                            "New Shipping Info": 0,
                            "Cart Size": size,
                            "Cart Items Qty": qty,
                            "Cart Value": totalsValue,
                            "Cart Items": sku
                        }
                    );
                    window.xyz=1;
                }
                /* mixPanel */
                return this;
            },
            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                setPaymentMethod(this.item.method);
            },

            selectPaymentMethod: function () {
                this._super();
                $('#ccnum_cc').val('');
                $('#name_cc').val('');
                $('#ccvv_cc').val('');
                $('#ccexpmon_cc').val('');
                $('#ccexpyr_cc').val('');
                $('#ccnum_cc').css('border', '1px solid #337ab7');
                $('#name_cc').css('border', '1px solid #CCC');
                $('#card_date').css('border', '1px solid #CCC');
                $('#card_cvv').css('border', '1px solid #CCC');
                $('#error-ccnum_cc').css('display', 'none');
                $('#error-name_cc').css('display', 'none');
                $('#error_ccexpmon_cc').css('display', 'none');
                $('#error_ccexpyr_cc').css('display', 'none');
                $('#error_ccvv_cc').css('display', 'none');

                return this;
            },

            validate: function () {

                var isValidated = true;
                var ccnum = $.trim($('#ccnum_cc').val());
                var ccname = $.trim($('#name_cc').val());
                var exp = /^[A-Za-z ]+$/;
                var cccvv = $.trim($('#ccvv_cc').val());
                var ccexpm = $.trim($('#ccexpmon_cc').val());
                var ccexpy = $.trim($('#ccexpyr_cc').val());
                var cardtype = $.trim($('#cardtype_cc').val());
                var date = new Date();
                var month = date.getMonth();
                var year = date.getFullYear();
                var arrMonth = ['00','01','02','03','04','05','06','07','08','09','10','11','12'];
                var arrYear = [];
                for (var i = 0; i < 20; i++) {
                    arrYear.push((year + i - 1).toString());
                }
                var errorinput = $('#ccnum_cc');
                var errornameinput = $('#name_cc');
                var errorExp = $('#card_date');
                var errorCvv = $('#card_cvv');
                var errorBlockNum = $('#error-ccnum_cc');
                var errorBlockName = $('#error-name_cc');
                var errorBlockMonth = $('#error_ccexpmon_cc');
                var errorBlockYear = $('#error_ccexpyr_cc');
                var errorBlockCVV = $('#error_ccvv_cc');
                var len;
                if (ccnum === '') {
                    errorinput.css('border', '1px solid #FF0000');
                    isValidated = false;
                } else if (len = this.validateLength(ccnum.length, cardtype)) {
                    errorinput.css('border', '1px solid #FF0000');
                    errorBlockNum.css('display', 'block');
                    if (len === 'less')
                        errorBlockNum.text('The actual card length is less than the required length');
                    else if (len === 'greater')
                        errorBlockNum.text('The actual card length is greater than the required length');
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
                    errorBlockCVV.text('Invalid cvv!');
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
