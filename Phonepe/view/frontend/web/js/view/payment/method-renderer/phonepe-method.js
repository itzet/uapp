define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Urjakart_Phonepe/js/action/set-payment-method',
        'Magento_Catalog/js/price-utils'
    ],
    function(ko, Component, quote, setPaymentMethod, priceUtils){
    'use strict';

    return Component.extend({
        defaults:{
            'template':'Urjakart_Phonepe/payment/phonepe'
        },
        redirectAfterPlaceOrder: false,
        
        afterPlaceOrder: function () {
            setPaymentMethod();    
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
            return window.checkoutConfig.payment.phonepe.instructions;
        }

    });
});
