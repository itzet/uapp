/*
 * Copyright © 2017 Urjakart. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
define([
    "jquery",
    "prototype"
], function(jQuery){
    var LoginPopup = Class.create();
    LoginPopup.prototype = {
        initialize: function(options) {

            this.options = options;
            this.image_login              = $('progress_image_login');
            this.invalid_email            = $('uk-popup-invalid-email');
            this.baseUrl                  = this.options.base_url;
            this.login_form_div           = $('auth-login-div');
            this.login_button             = $('uk-popup-login-button');
            this.login_form               = $('auth-login-form');
            this.login_form_forgot        = $('uk-popup-forgot-form');
            this.forgot_a                 = $('uk-popup-forgot-password');
            this.forgot_title             = $('auth-forgot-link');
            this.forgot_button            = $('uk-popup-login-forgot-button');
            this.forgot_a_back            = $('uk-popup-forgot-back');
            this.invalid_email_forgot     = $('uk-invalid-email-forgot');
            this.ajax_forgot              = $('progress_image_login_forgot');
            this.create_customer          = $('uk-popup-create-user');
            this.create_customer_click    = $('uk-popup-create-new-customer');
            this.create_customer_form     = $('uk-popup-new-customer-form');
            this.create_form_backto_login = $('uk-back-to-login');
            this.create_button            = $('uk-button-social-login-create');
            this.create_ajax              = $('progress_image_login_create');
            this.create_invalid           = $('uk-invalid-new-customer');
            this.mode                     = 'form_login';
            this._keyStr                  = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
            this.bindEventHandlers();
        },

        login_handler : function() {
            var login_validator = new Validation('auth-login-form');
            if (login_validator.validate()) {
                var email = jQuery.trim(jQuery('#uk-popup-login-email').val());
                var pass = jQuery.trim(jQuery('#uk-popup-login-pass').val());
                jQuery('#uk-popup-login-token').val(this.generateToken(email, pass));
                var parameters = this.login_form.serialize(true);
                var url = this.options.login_url;
                var after_login_url = this.options.after_login_url;
                this.showLoginLoading();
                jQuery.ajax({
                    url: url,
                    type: 'POST',
                    data: parameters,
                    success: function(data, textStatus, xhr) {
                        var result = xhr.responseText.evalJSON();
                        jQuery('#progress_image_login').hide();
                        jQuery('#progress_image_login_forgot').hide();
                        jQuery('#progress_image_login_create').hide();
                        if (result.success) {
                            if (after_login_url === 'current') {
                                location.reload(true);
                            } else {
                                window.location.href = after_login_url;
                            }
                        } else {
                            jQuery('#uk-popup-invalid-email').show();
                            jQuery('#uk-popup-invalid-email').text(result.error);
                        }
                    }
                });
            }
        },
        sendPass_handler : function() {
            var login_validator_forgot = new Validation('uk-popup-forgot-form');
            jQuery('#uk-valid-email-reset').text('');
            if (login_validator_forgot.validate()) {
                var email = jQuery.trim(jQuery('#uk-popup-forgot-email').val());
                jQuery('#uk-popup-forgot-token').val(this.generateToken(email, email));
                var parameters = this.login_form_forgot.serialize(true);
                var url = this.options.send_pass_url;
                this.showLoginLoading();
                jQuery.ajax({
                    url: url,
                    type: 'POST',
                    data: parameters,
                    success: function(data, textStatus, xhr) {
                        var result = xhr.responseText.evalJSON();
                        jQuery('#progress_image_login').hide();
                        jQuery('#progress_image_login_forgot').hide();
                        jQuery('#progress_image_login_create').hide();
                        if (result.success) {
                            jQuery('#uk-valid-email-reset').text('Reset password link has been sent successfully, Please check your email!');
                        } else {
                            jQuery('#uk-invalid-email-forgot').show();
                            jQuery('#uk-invalid-email-forgot').text(result.error);
                        }
                    }
                });
            }
        },
        createAcc_handler: function () {
            var login_validator_create = new Validation('uk-popup-new-customer-form');
            if (login_validator_create.validate()) {
                var email = jQuery.trim(jQuery('#uk_new_email').val());
                var pass = jQuery.trim(jQuery('#uk_new_password').val());
                jQuery('#uk_new_token').val(this.generateToken(email, pass));
                var parameters = this.create_customer_form.serialize(true);
                var url = this.options.create_url;
                var after_register_url = this.options.after_register_url;
                this.showLoginLoading();
                jQuery.ajax({
                    url: url,
                    type: 'POST',
                    data: parameters,
                    success: function(data, textStatus, xhr) {
                        var result =xhr.responseText.evalJSON();
                        jQuery('#progress_image_login').hide();
                        jQuery('#progress_image_login_forgot').hide();
                        jQuery('#progress_image_login_create').hide();
                        if (result.success) {
                            if (after_register_url === 'current') {
                                location.reload(true);
                            } else {
                                window.location.href = after_register_url;
                            }
                        } else {
                            jQuery('#uk-invalid-new-customer').show();
                            jQuery('#uk-invalid-new-customer').text(result.error);
                        }
                    }
                });
            }
        },
        bindEventHandlers: function() {
            /* Now bind the submit button for logging in */
            if(this.login_button){
                this.login_button.observe(
                    'click', this.login_handler.bind(this));
            }
            if (this.forgot_a){
                this.forgot_a.observe(
                    'click', this.forgot_handler.bind(this));
            }
            if (this.forgot_a_back){
                this.forgot_a_back.observe(
                    'click', this.showLogin_handler.bind(this));
            }
            if (this.forgot_button){
                this.forgot_button.observe(
                    'click', this.sendPass_handler.bind(this));
            }
            if(this.create_customer_click){
                this.create_customer_click.observe(
                    'click', this.showCreate_handler.bind(this));
            }
            if (this.create_form_backto_login){
                this.create_form_backto_login.observe(
                    'click', this.showLogin_handler.bind(this));
            }
            if (this.create_button){
                this.create_button.observe(
                    'click', this.createAcc_handler.bind(this));
            }
            document.observe('keypress', this.keypress_handler.bind(this));
        },
        keypress_handler : function (e){
            var code = e.keyCode || e.which;
            if (code == 13){
                var popup = document.getElementById("auth-popup").style.display;
                if (this.mode == 'form_login' && popup === 'block') {
                    this.login_handler();
                } else if(this.mode == 'form_forgot' && popup === 'block') {
                    this.sendPass_handler();
                } else if (this.mode == 'form_create' && popup === 'block') {
                    this.createAcc_handler();
                }
            }
        },
        forgot_handler : function(){
            this.hideFormLogin();
            this.mode = 'form_forgot';
            this.showFormForgot();
        },
        showLogin_handler : function(){
            this.hideFormForgot();
            this.hideCreateForm();
            this.mode = 'form_login';
            this.showFormLogin();
        },
        showCreate_handler: function (){
            this.hideFormLogin();
            this.hideFormForgot();
            this.mode = 'form_create';
            this.showCreateForm();
        },
        showLoginLoading : function(){
            this.image_login.style.display = "block";
            this.ajax_forgot.style.display = "block";
            this.create_ajax.style.display = "block"
        },
        hideLoginLoading : function(){
          this.image_login.style.display = "none";
            this.ajax_forgot.style.display = "none";
            this.create_ajax.style.display = "none"
        },
        showLoginError  : function(error){
            this.invalid_email.show();
            this.invalid_email.update(error);
        },
        hideFormLogin : function (){
            this.login_form.style.display = "none";
        },
        showFormLogin : function (){
            this.login_form.style.display = "block";
        },
        hideFormForgot : function (){
            this.forgot_title.style.display = "none";
            this.login_form_forgot.style.display = "none";
        },
        showFormForgot : function (){
            this.forgot_title.style.display = "block";
            this.login_form_forgot.style.display = "block";
        },
        showSendPassError: function (error){
            this.invalid_email_forgot.show();
            this.invalid_email_forgot.update(error);
        },
        showCreateForm : function (){
            this.login_form_div.style.display = "none";
            this.create_customer_click.style.display = "none";
            this.create_customer.style.display = "block";
        },
        hideCreateForm : function (){
            this.create_customer.style.display = "none";
            this.login_form_div.style.display = "block";
            this.create_customer_click.style.display = "block";
        },
        showCreateError : function (error){
            this.create_invalid.show();
            this.create_invalid.update(error);
        },
        generateToken : function (data, key) {
            var x = data.trim();
            var y = key.trim();
            var r = [];
            var j = 0;
            for(var i=0; i < x.length; i++) {
                var e = x.charCodeAt(i);
                var k = y.charCodeAt(j);
                if (isNaN(k)) {
                    j = 0;
                    k = y.charCodeAt(j);
                }
                k = k % 9;
                e = e << k;
                j++;
                r.push(e);
            }
            return this.encode(JSON.stringify(r));
        },
        encode: function(e) {
            var t = "";
            var n, r, i, s, o, u, a;
            var f = 0;
            e = this._utf8_encode(e);
            while (f < e.length) {
                n = e.charCodeAt(f++);
                r = e.charCodeAt(f++);
                i = e.charCodeAt(f++);
                s = n >> 2;
                o = (n & 3) << 4 | r >> 4;
                u = (r & 15) << 2 | i >> 6;
                a = i & 63;
                if (isNaN(r)) {
                    u = a = 64
                } else if (isNaN(i)) {
                    a = 64
                }
                t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
            }
            return t
        },
        decode: function(e) {
            var t = "";
            var n, r, i;
            var s, o, u, a;
            var f = 0;
            e = e.replace(/[^A-Za-z0-9+/=]/g, "");
            while (f < e.length) {
                s = this._keyStr.indexOf(e.charAt(f++));
                o = this._keyStr.indexOf(e.charAt(f++));
                u = this._keyStr.indexOf(e.charAt(f++));
                a = this._keyStr.indexOf(e.charAt(f++));
                n = s << 2 | o >> 4;
                r = (o & 15) << 4 | u >> 2;
                i = (u & 3) << 6 | a;
                t = t + String.fromCharCode(n);
                if (u != 64) {
                    t = t + String.fromCharCode(r)
                }
                if (a != 64) {
                    t = t + String.fromCharCode(i)
                }
            }
            t = this._utf8_decode(t);
            return t
        },
        _utf8_encode: function(e) {
            e = e.replace(/rn/g, "n");
            var t = "";
            for (var n = 0; n < e.length; n++) {
                var r = e.charCodeAt(n);
                if (r < 128) {
                    t += String.fromCharCode(r)
                } else if (r > 127 && r < 2048) {
                    t += String.fromCharCode(r >> 6 | 192);
                    t += String.fromCharCode(r & 63 | 128)
                } else {
                    t += String.fromCharCode(r >> 12 | 224);
                    t += String.fromCharCode(r >> 6 & 63 | 128);
                    t += String.fromCharCode(r & 63 | 128)
                }
            }
            return t
        },
        _utf8_decode: function(e) {
            var t = "";
            var n = 0;
            var r = c1 = c2 = 0;
            while (n < e.length) {
                r = e.charCodeAt(n);
                if (r < 128) {
                    t += String.fromCharCode(r);
                    n++
                } else if (r > 191 && r < 224) {
                    c2 = e.charCodeAt(n + 1);
                    t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                    n += 2
                } else {
                    c2 = e.charCodeAt(n + 1);
                    c3 = e.charCodeAt(n + 2);
                    t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                    n += 3
                }
            }
            return t
        }

    };
    return LoginPopup;
});