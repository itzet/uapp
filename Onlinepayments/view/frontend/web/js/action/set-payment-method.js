define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Urjakart_Onlinepayments/js/form/form-builder',
        'Magento_Ui/js/modal/alert',
        'Urjakart_Onlinepayments/js/form/tabs'
    ],
    function ($, quote, customerData, customer, fullScreenLoader, formBuilder, alert) {
        'use strict';

        return function (code) {

            var serviceUrl,
                email,
                form;

            if (!customer.isLoggedIn()) {
                email = quote.guestEmail;
            } else {
                email = customer.customerData.email;
            }

            serviceUrl = window.checkoutConfig.payment.onlinepayment.redirectUrl+'?email='+email;
            fullScreenLoader.startLoader();
            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: {isAjax: 1},
                dataType: 'json',
                success: function (response) {
                    if ($.type(response) === 'object' && !$.isEmptyObject(response)) {
                        $('#online_payment_form').remove();
                        /*
                         * Here we added the credit card, debit card or net banking
                         * payment detail for seam less transaction.
                         */
                        if (code === 'creditcard') {
                            response.fields.ccnum = $('#ccnum_cc').val();
                            response.fields.ccname = $('#name_cc').val();
                            response.fields.ccvv = $('#ccvv_cc').val();
                            response.fields.ccexpmon = $('#ccexpmon_cc').val();
                            response.fields.ccexpyr = $('#ccexpyr_cc').val();
                            response.fields.pg = 'CC';
                            response.fields.bankcode = $.trim($("#cardtype_cc").val());
                        } else if (code === 'debitcard') {
                            response.fields.ccnum = $('#ccnum_dc').val();
                            if ($('#name_dc').val())
                                response.fields.ccname = $('#name_dc').val();
                            response.fields.ccvv = $('#ccvv_dc').val();
                            response.fields.ccexpmon = $('#ccexpmon_dc').val();
                            response.fields.ccexpyr = $('#ccexpyr_dc').val();
                            response.fields.pg = 'DC';
                            response.fields.bankcode = $.trim($("#cardtype_dc").val());
                        } else if (code === 'netbanking') {
                            response.fields.pg = 'NB';
                            response.fields.bankcode = $.trim($('#bankcode_nb').val());
                        } else if (code === 'emi') {
                            response.fields.ccnum = $('#ccnum_emi').val();
                            response.fields.ccname = $('#name_emi').val();
                            response.fields.ccvv = $('#ccvv_emi').val();
                            response.fields.ccexpmon = $('#ccexpmon_emi').val();
                            response.fields.ccexpyr = $('#ccexpyr_emi').val();
                            response.fields.pg = 'EMI';
                            response.fields.bankcode = $.trim($('input[name="emimonth"]:checked').val());
                        } else if (code === 'payumoney') {
                            response.fields.pg = 'WALLET';
                            response.fields.bankcode = 'payuw';
                        }

                        form = formBuilder.build(
                            {
                                action: response.url,
                                fields: response.fields
                            }
                        );
                        customerData.invalidate(['cart']);
                        form.submit();
                    } else {
                        fullScreenLoader.stopLoader();
                        alert({
                            content: $.mage.__('Sorry, something went wrong. Please try again.')
                        });
                    }
                },
                error: function (response) {
                    fullScreenLoader.stopLoader();
                    alert({
                        content: $.mage.__('Sorry, something went wrong. Please try again later.')
                    });
                }
            });
        };
    }
);