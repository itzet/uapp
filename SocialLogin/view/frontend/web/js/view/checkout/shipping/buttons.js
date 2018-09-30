define([
    'jquery',
    'uiComponent',
    'ko'
], function ($, Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Urjakart_SocialLogin/checkout/shipping/buttons'
        },
        initialize: function() {
            if (typeof $('#bt_fb_social_login_checkout').html() !== 'undefined') {
                this.facebookButton = ko.observable($('#bt_fb_social_login_checkout').html());
                $('#bt_fb_social_login_checkout').html('');
            } else {
                this.facebookButton = ko.observable('');
            }
            if (typeof $('#bt_go_social_login_checkout').html() !== 'undefined') {
                this.googleButton = ko.observable($('#bt_go_social_login_checkout').html());
                $('#bt_go_social_login_checkout').html('');
            } else {
                this.googleButton = ko.observable('');
            }
            if (typeof $('#bt_link_social_login_checkout').html() !== 'undefined') {
                this.linkedButton = ko.observable($('#bt_link_social_login_checkout').html());
                $('#bt_link_social_login_checkout').html('');
            } else {
                this.linkedButton = ko.observable('');
            }

            this._super();
        },

        isSocialEnabled: function () {

            var isEnabled = false;
            if (window.isSocialEnabledOnCheckout > 0)
                isEnabled = true;

            return isEnabled;
        }
    });
});