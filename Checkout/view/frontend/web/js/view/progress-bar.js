/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        "underscore",
        'ko',
        'uiComponent',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/step-navigator',
        'jquery/jquery.hashchange'
    ],
    function ($, _, ko, Component, customer, stepNavigator) {
        var steps = stepNavigator.steps;

        return Component.extend({
            defaults: {
                template: 'Urjakart_Checkout/progress-bar',
                visible: true
            },
            steps: steps,

            initialize: function() {
                this._super();
                $(window).hashchange(_.bind(stepNavigator.handleHash, stepNavigator));
                stepNavigator.handleHash();
            },

            sortItems: function(itemOne, itemTwo) {
                return stepNavigator.sortItems(itemOne, itemTwo);
            },

            navigateTo: function(step) {
                if (step.sortOrder == 10) {
                    $('#shipping-rate-main-id').show();
                }
                if (step.sortOrder == 9) {
                    var checkoutUrl = window.location.href;
                    var baseUrl = checkoutUrl.split("checkout");
                    window.location = baseUrl[0] + 'checkout/cart/';
                } else {
                    stepNavigator.navigateTo(step.code);
                }
            },

            isProcessed: function(item) {
                return stepNavigator.isProcessed(item.code);
            }
        });
    }
);
