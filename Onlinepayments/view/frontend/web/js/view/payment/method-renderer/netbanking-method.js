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
                template: 'Urjakart_Onlinepayments/payment/netbanking'
            },
            initialize: function () {
                this.bankNames = ko.observableArray([
                    { label: 'Select Bank', value: '' },
                    { label: 'Airtel Payments Bank', value: 'AIRNB' },
                    { label: 'Andhra Bank', value: 'ADBB' },
                    { label: 'AXIS Bank NetBanking', value: 'AXIB' },//3
                    { label: 'Bank of India', value: 'BOIB' },
                    { label: 'Bank of Maharashtra', value: 'BOMB' },
                    { label: 'Canara Bank', value: 'CABB' },
                    { label: 'Catholic Syrian Bank', value: 'CSBN' },
                    { label: 'Central Bank Of India', value: 'CBIB' },
                    { label: 'Citibank Netbanking', value: 'CITNB' },
                    { label: 'City Union Bank', value: 'CUBB' },
                    { label: 'Corporation Bank', value: 'CRPB' },
                    { label: 'Cosmos Bank', value: 'CSMSNB' },
                    { label: 'DCB Bank', value: 'DCBB' },
                    { label: 'Dena Bank', value: 'DENN' },
                    { label: 'Deutsche Bank', value: 'DSHB' },
                    { label: 'Dhanlaxmi Bank', value: 'DLSB' },
                    { label: 'Federal Bank', value: 'FEDB' },
                    { label: 'HDFC Bank', value: 'HDFB' }, //18
                    { label: 'ICICI Netbanking', value: 'ICIB' }, //19
                    { label: 'IDBI Bank', value: 'IDBB' },
                    { label: 'IDFC Netbanking', value: 'IDFCNB' },
                    { label: 'Indian Bank', value: 'INDB' },
                    { label: 'Indian Overseas Bank', value: 'INOB' },
                    { label: 'IndusInd Bank', value: 'INIB' },
                    { label: 'Jammu and Kashmir Bank', value: 'JAKB' },
                    { label: 'Janata Sahakari Bank Pune', value: 'JSBNB' },
                    { label: 'Karnataka Bank', value: 'KRKB' },
                    { label: 'Karur Vysya - Corporate Netbanking', value: 'KRVBC' },
                    { label: 'Karur Vysya - Retail Netbanking', value: 'KRVB' },
                    { label: 'Kotak Mahindra Bank', value: '162B' },
                    { label: 'Lakshmi Vilas Bank - Corporate Netbanking', value: 'LVCB' },
                    { label: 'Lakshmi Vilas Bank - Retail Netbanking', value: 'LVRB' },
                    { label: 'Oriental Bank of Commerce', value: 'OBCB' },
                    { label: 'Punjab And Maharashtra Co-operative Bank Limited', value: 'PMNB' },
                    { label: 'Punjab And Sind Bank', value: 'PSBNB' },
                    { label: 'Punjab National Bank - Corporate Banking', value: 'CPNB' },
                    { label: 'Punjab National Bank - Retail Banking', value: 'PNBB' },
                    { label: 'Saraswat Bank', value: 'SRSWT' },
                    { label: 'Shamrao Vithal Co-operative Bank Ltd.', value: 'SVCNB' },
                    { label: 'South Indian Bank', value: 'SOIB' },
                    { label: 'State Bank of India', value: 'SBIB' },//41
                    { label: 'Syndicate Bank', value: 'SYNDB' },
                    { label: 'Tamilnad Mercantile Bank', value: 'TMBB' },
                    { label: 'The Bharat Co-op. Bank Ltd', value: 'BHNB' },
                    { label: 'The Nainital Bank', value: 'TBON' },
                    { label: 'UCO Bank', value: 'UCOB' },
                    { label: 'Union Bank - Corporate Netbanking', value: 'UBIBC' },
                    { label: 'Union Bank - Retail Netbanking', value: 'UBIB' },
                    { label: 'United Bank Of India', value: 'UNIB' },
                    { label: 'Vijaya Bank', value: 'VJYB' },
                    { label: 'Yes Bank', value: 'YESB' }
                ]);
                this._super();
            },
            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                setPaymentMethod(this.item.method);
            },

            selectPaymentMethod: function () {
                this._super();
                $('#bankcode_nb').val('');
                $('#bankcode_nb').css('border', '1px solid #CCC');
                $('input[name="bankname"]').attr('checked', false);
                $('#error-bankcode-nb').css('display', 'none');

                return this;
            },

            validate: function () {
                var isValidated = true;
                var errorBlock = $('#error-bankcode-nb');
                var bank = $('#bankcode_nb');
                if (!bank.val()) {
                    isValidated = false;
                    errorBlock.text('Please choose bank!');
                    errorBlock.show();
                    bank.css('border', '1px solid #FF0000');
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
