define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],function(Component,renderList){
    'use strict';
    renderList.push({
        type : 'phonepe',
        component : 'Urjakart_Phonepe/js/view/payment/method-renderer/phonepe-method'
    });

    return Component.extend({});
})
