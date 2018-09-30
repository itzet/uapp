/*
 * This method use to get the EMI data.
 */
function selectEmiBank(bankName) {
    document.getElementById('emi-card-form').style.display = 'none';
    document.getElementById('cardtype_emi').style.border = '1px solid #CCC';
    document.getElementById('emiRateTable').style.border = '1px solid #CCC';
    document.getElementById('ccnum_emi').style.border = '1px solid #337ab7';
    document.getElementById('ccnum_emi').value = '';
    document.getElementById('ccvv_emi').value = '';
    document.getElementById('ccexpmon_emi').value = '';
    document.getElementById('ccexpyr_emi').value = '';
    document.getElementById('error-ccnum_emi').style.display = 'none';
    document.getElementById('error_ccexpmon_emi').style.display = 'none';
    document.getElementById('error_ccexpyr_emi').style.display = 'none';
    document.getElementById('error_ccvv_emi').style.display = 'none';
    document.getElementById('emi_card_date').style.border = '1px solid #ccc';
    document.getElementById('emi_card_cvv').style.border = '1px solid #ccc';
    document.getElementById('checkoutprocess_emi').disabled = false;
    document.getElementById('amex_emi').style.display = 'none';
    document.getElementById('visa_emi').style.display = 'none';
    document.getElementById('master_emi').style.display = 'none';
    if (bankName === '') {
        document.getElementById('error-cardtype-emi').style.display = 'block';
        document.getElementById('emiRateTable').innerHTML = '';
        return false;
    }
    document.getElementById('error-cardtype-emi').style.display = 'none';
    var table = '<tr><th></th><th>Month</th><th>Rate of Interest</th>';
    table += '<th>Monthly Installments</th><th>Interest paid to Bank</th></tr>';
    var emiData = window.checkoutConfig.payment.emiData;
    emiData = emiData[bankName];
    var arrData = Object.keys(emiData);
    arrData.sort(function(a, b) {
        return parseInt(a.replace(/[^0-9\.]/g, '0')) - parseInt(b.replace(/[^0-9\.]/g, '0'));
    });
    var arrEmi = [];
    for (var i = 0; i < arrData.length; i++) {
        if (arrData[i].search("P") > 0)
            continue;
        arrEmi.push(arrData[i]);
    }
    for (var i = 0; i < arrEmi.length; i++) {
        var emi = arrEmi[i];
        var data = emiData[emi];
        table += '<tr><td><input type="radio" name="emimonth" value="';
        table += emi + '" onchange="showEmiCardForm()"></td>';
        table += '<td>' + Math.round(data.loanAmount/data.emiAmount) + '</td>';
        table += '<td>' + data.emiBankInterest + '%</td>';
        table += '<td>' + Math.round(data.emi_value).toFixed(2) + '</td>';
        table += '<td>' + Math.round(data.emi_interest_paid).toFixed(2) + '</td></tr>';
    }
    document.getElementById('emiRateTable').innerHTML = table;
}

/*
 * This method use to show the card form for emi payment.
 */
function showEmiCardForm()
{
    document.getElementById('emi-card-form').style.display = 'block';
    document.getElementById('emiRateTable').style.border = '1px solid #6cbe42';
    document.getElementById('error-emitype-emi').style.display = 'none';
    var cb = document.getElementById('ccnum_emi').style.border;
    var db = document.getElementById('emi_card_date').style.border;
    var cvb = document.getElementById('emi_card_cvv').style.border;
    if (cb.search('255') > 0) {
        document.getElementById('ccnum_emi').style.border = '1px solid #337ab7';
        document.getElementById('ccnum_emi').value = '';
        document.getElementById('error-ccnum_emi').style.display = 'none';
        document.getElementById('amex_emi').style.display = 'none';
        document.getElementById('visa_emi').style.display = 'none';
        document.getElementById('master_emi').style.display = 'none';
        document.getElementById('checkoutprocess_emi').disabled = false;
    }
    if (db.search('255') > 0) {
        document.getElementById('ccexpmon_emi').value = '';
        document.getElementById('ccexpyr_emi').value = '';
        document.getElementById('error_ccexpmon_emi').style.display = 'none';
        document.getElementById('error_ccexpyr_emi').style.display = 'none';
        document.getElementById('emi_card_date').style.border = '1px solid #ccc';
    }
    if (cvb.search('255') > 0) {
        document.getElementById('ccvv_emi').value = '';
        document.getElementById('error_ccvv_emi').style.display = 'none';
        document.getElementById('emi_card_cvv').style.border = '1px solid #ccc';
    }
}

/*
 * This method use to validate credit card .
 */
function validateCreditCard(self, type, charCode) {
    if (!(typeof charCode === 'boolean' || (charCode >= 48 && charCode <= 57))) {
        return false;
    } else if (self.value.length === 6 || !charCode) {
        var cardtype = document.getElementById('cardtype_cc').value;
        if (!charCode) {
            if (cardtype === '') {
                return false;
            } else if (inValidType(self.value.length, type.toLowerCase())) {
                return false;
            }
        }
        document.getElementById('ccnum_cc').style.border = '1px solid #337ab7';
        document.getElementById('error-ccnum_cc').style.display = 'none';
        document.getElementById('amex').style.display = 'none';
        document.getElementById('visa').style.display = 'none';
        document.getElementById('master').style.display = 'none';

        var data = {
            "bin" : self.value.substring(0,6),
            "errorBlock" : document.getElementById('error-ccnum_cc'),
            "ccnum" : document.getElementById('ccnum_cc'),
            "ccvv" : document.getElementById('ccvv_cc'),
            "btn" : document.getElementById('checkoutprocess_cc'),
            "amex" : document.getElementById('amex'),
            "visa" : document.getElementById('visa'),
            "master" : document.getElementById('master'),
            "processing" : document.getElementById('processing'),
            "cardtype" : document.getElementById('cardtype_cc'),
            "errorMsg" : 'Invalid credit card type!'
        };
        processAjaxRequest(data, type);

        return true;
    } else {
        if (self.value.length < 6) {
            document.getElementById('ccnum_cc').style.border = '1px solid #337ab7';
            document.getElementById('error-ccnum_cc').style.display = 'none';
            document.getElementById('amex').style.display = 'none';
            document.getElementById('visa').style.display = 'none';
            document.getElementById('master').style.display = 'none';
        }

        return false;
    }
}

/*
 * This method use to validate debit card .
 */
function validateDebitCard(self, type, charCode) {
    if (!(typeof charCode === 'boolean' || (charCode >= 48 && charCode <= 57))) {
        return false;
    } else if (self.value.length === 6 || !charCode) {
        var cardtype = document.getElementById('cardtype_dc').value;
        if (!charCode) {
            if (cardtype === '') {
                return false;
            } else if (inValidType(self.value.length, type.toLowerCase())) {
                return false;
            }
        }
        document.getElementById('ccnum_dc').style.border = '1px solid #337ab7';
        document.getElementById('error-ccnum_dc').style.display = 'none';
        document.getElementById('amex_dc').style.display = 'none';
        document.getElementById('visa_dc').style.display = 'none';
        document.getElementById('master_dc').style.display = 'none';

        var data = {
            "bin" : self.value.substring(0,6),
            "errorBlock" : document.getElementById('error-ccnum_dc'),
            "ccnum" : document.getElementById('ccnum_dc'),
            "ccvv" : document.getElementById('ccvv_dc'),
            "btn" : document.getElementById('checkoutprocess_dc'),
            "amex" : document.getElementById('amex_dc'),
            "visa" : document.getElementById('visa_dc'),
            "master" : document.getElementById('master_dc'),
            "processing" : document.getElementById('processing_dc'),
            "cardtype" : document.getElementById('cardtype_dc'),
            "errorMsg" : 'Invalid debit card type!'
        };
        processAjaxRequest(data, type);

        return true;

    } else {
        if (self.value.length < 6) {
            document.getElementById('ccnum_dc').style.border = '1px solid #337ab7';
            document.getElementById('error-ccnum_dc').style.display = 'none';
            document.getElementById('amex_dc').style.display = 'none';
            document.getElementById('visa_dc').style.display = 'none';
            document.getElementById('master_dc').style.display = 'none';
        }

        return false;
    }
}

/*
 * This method use to validate credit card for emi.
 */
function validateEmiCard(self, type, charCode) {
    if (!(typeof charCode === 'boolean' || (charCode >= 48 && charCode <= 57))) {
        return false;
    } else if (self.value.length === 6 || !charCode) {
        var cardtype = document.getElementById('cardtype_em').value;
        if (!charCode) {
            if (cardtype === '') {
                return false;
            } else if (inValidType(self.value.length, 'em')) {
                return false;
            }
        }
        document.getElementById('ccnum_emi').style.border = '1px solid #337ab7';
        document.getElementById('error-ccnum_emi').style.display = 'none';
        document.getElementById('amex_emi').style.display = 'none';
        document.getElementById('visa_emi').style.display = 'none';
        document.getElementById('master_emi').style.display = 'none';

        var data = {
            "bin" : self.value.substring(0,6),
            "errorBlock" : document.getElementById('error-ccnum_emi'),
            "ccnum" : document.getElementById('ccnum_emi'),
            "ccvv" : document.getElementById('ccvv_emi'),
            "btn" : document.getElementById('checkoutprocess_emi'),
            "amex" : document.getElementById('amex_emi'),
            "visa" : document.getElementById('visa_emi'),
            "master" : document.getElementById('master_emi'),
            "processing" : document.getElementById('processing_emi'),
            "cardtype" : document.getElementById('cardtype_em'),
            "errorMsg" : 'Invalid credit card type!'
        };
        processAjaxRequest(data, type);

        return true;
    } else {
        if (self.value.length < 6) {
            document.getElementById('ccnum_emi').style.border = '1px solid #337ab7';
            document.getElementById('error-ccnum_emi').style.display = 'none';
            document.getElementById('amex_emi').style.display = 'none';
            document.getElementById('visa_emi').style.display = 'none';
            document.getElementById('master_emi').style.display = 'none';
        }

        return false;
    }
}

/*
 * This method use to send the validation request via ajax.
 */
function processAjaxRequest(data, type) {

    if (type === 'DC') {
        var infoMastro = document.getElementById('info_mastro');
        infoMastro.innerHTML = '';
        infoMastro.style.display = 'none';
    }
    var xhttp = new XMLHttpRequest();
    var url = window.checkoutConfig.payment.onlinepayment.cardUrl + '?bin=' + data.bin;
    xhttp.onreadystatechange = function() {
        if (this.readyState === 1 || this.readyState === 2 || this.readyState === 3) {
            data.btn.disabled = true;
            data.ccvv.disabled = true;
	        data.ccvv.maxLength = 3;
            data.ccnum.maxLength = 16;
        } else if (this.readyState === 4 && this.status === 200) {
            var response = JSON.parse(this.responseText);
            data.ccvv.disabled = false;
            if (response.error) {
                data.errorBlock.innerHTML = response.msg;
                data.errorBlock.style.display = 'block';
                data.ccnum.style.border = '1px solid #FF0000';
                data.btn.disabled = true;
            } else {
                data.btn.disabled = false;
                data.amex.style.display = 'none';
                data.processing.style.display = 'none';
                data.visa.style.display = 'none';
                data.master.style.display = 'none';
                data.errorBlock.innerHTML = '';
                data.errorBlock.style.display = 'none';
                data.ccnum.style.border = '1px solid #6cbe42';
                data.cardtype.value = response.data.cardType;
                if (type !== response.data.cardCategory) {
                    data.errorBlock.innerHTML = data.errorMsg;
                    data.errorBlock.style.display = 'block';
                    data.ccnum.style.border = '1px solid #FF0000';
                } else if (response.data.cardType === 'unknown') {
                    data.errorBlock.innerHTML = 'Unknown error with this card number!';
                    data.errorBlock.style.display = 'block';
                    data.ccnum.style.border = '1px solid #FF0000';
                } else if (response.data.cardType === 'AMEX') {
                    data.ccvv.maxLength = 4;
                    data.ccnum.maxLength = 15;
                    data.amex.style.display = 'block';
                } else if (response.data.cardType === 'DINR') {
                    data.ccnum.maxLength = 14;
                } else if (response.data.cardType === 'MAES') {
                    data.ccnum.maxLength = 19;
                    infoMastro.innerHTML = '<b>Note:</b> expiry and cvv optional for mastro card!';
                    infoMastro.style.display = 'block';
                } else {
                    if (response.data.cardType === 'MAST') {
                        data.master.style.display = 'block';
                    } else if (response.data.cardType === 'VISA'){
                        data.visa.style.display = 'block';
                    }
                }
            }
        }
    };
    xhttp.open("GET", url, true);
    xhttp.send();
}

/*
 * This method use to remove error of expiry from credit, debit & emi.
 */
function removeError(self) {
    var str = document.getElementById(self.parentNode.id).style.border;
    if (self.value !== '' && str.search("255") > -1) {
        var id = self.id;
        var date = new Date();
        var month = date.getMonth();
        var year = date.getFullYear();
        var selector = id.split('_');
        var expmon, expyr, errSel;
        if (selector[0] === 'ccexpyr') {
            selector = 'ccexpmon_' + selector[1];
            errSel = 'error_' + selector;
            expmon = document.getElementById(selector).value;
            expyr = document.getElementById(id).value;
        } else if (selector[0] === 'ccexpmon') {
            selector = 'ccexpyr_' + selector[1];
            errSel = 'error_' + id;
            expmon = document.getElementById(id).value;
            expyr = document.getElementById(selector).value;
        }
        if (expmon !== '' && expyr !== '') {
            if (year == expyr && expmon < month + 1) {
                document.getElementById(errSel).innerHTML = 'Invalid card expiry!';
                document.getElementById(errSel).style.display = 'block';
                document.getElementById(self.parentNode.id).style.border = '1px solid #FF0000';
            } else {
                document.getElementById(errSel).innerHTML = '';
                document.getElementById(errSel).style.display = 'none';
                document.getElementById(self.parentNode.id).style.border = '1px solid #ccc';
            }
        }
    }
}

/*
 * This method use to remove error of cvv from credit, debit & emi.
 */
function removeErrorCvv(self) {
    if (self.value.length === 3 || self.value.length === 4) {
        document.getElementById(self.parentNode.id).style.border = '1px solid #CCC';
        document.getElementById('error_' + self.id).style.display = 'none';
    } else if (self.value.length > 0 && self.value.length < 3) {
        document.getElementById(self.parentNode.id).style.border = '1px solid #FF0000';
        document.getElementById('error_' + self.id).style.display = 'block';
        document.getElementById('error_' + self.id).innerHTML = 'invalid CVV!';
    } else if (self.value.length === 0) {
        document.getElementById(self.parentNode.id).style.border = '1px solid #FF0000';
        document.getElementById('error_' + self.id).style.display = 'none';
    }
}

/*
 * This method use to show error of card number from credit, debit & emi.
 */
function showErr(self, type) {
    var len = false;
    if (self.value.length > 0 && (len = inValidType(self.value.length, type))) {
        document.getElementById(self.id).style.border = '1px solid #FF0000';
        document.getElementById('error-' + self.id).style.display = 'block';
        if (len === 'less')
            document.getElementById('error-' + self.id).innerHTML = 'The actual card length is less than the required length';
        else if (len === 'greater')
            document.getElementById('error-' + self.id).innerHTML = 'The actual card length is greater than the required length';
    } else if (self.value.length === 0) {
        document.getElementById(self.id).style.border = '1px solid #FF0000';
    }
}

/*
 * This method only check the card type with card length.
 */
function inValidType(len, type) {
    var cardType = document.getElementById('cardtype_' + type).value;
    if ((cardType === 'MAST' || cardType === 'VISA') && len < 16) {
        return 'less';
    } else if ((cardType === 'MAST' || cardType === 'VISA') && len > 16) {
        return 'greater';
    } else if (cardType === 'AMEX' && len < 15) {
        return 'less';
    } else if (cardType === 'AMEX' && len > 15) {
        return 'greater';
    } else if (cardType === 'DINR' && len < 14) {
        return 'less';
    } else if (cardType === 'DINR' && len > 14) {
        return 'greater';
    } else if (cardType === 'MAES' && len < 16) {
        return 'less';
    } else if (cardType === 'MAES' && len > 19) {
        return 'greater';
    } else if (cardType === 'MAES' && len > 16 && len < 19) {
        return 'greater';
    }

    return false;
}

/*
 * This method use to hide error.
 */
function changeSelection($id) {
    var eID = document.getElementById("bankcode_nb");
    eID.options[$id].selected=true;
    eID.style.border = '1px solid #CCC';
    document.getElementById('error-bankcode-nb').style.display = 'none';
}

/*
 * This method use to select netbanking option.
 */
function changeBank() {
    var eID = document.getElementById("bankcode_nb");
    var colorVal = eID.options[eID.selectedIndex].value;
    if (colorVal) {
        eID.style.border = '1px solid #CCC';
        document.getElementById('error-bankcode-nb').style.display = 'none';
    }
    if (colorVal === 'SBIB') {
        document.getElementById("SBIB").checked=true;
    } else if (colorVal === 'ICIB') {
        document.getElementById("ICIB").checked=true;
    } else if (colorVal === 'HDFB') {
        document.getElementById("HDFB").checked=true;
    } else if (colorVal === 'AXIB') {
        document.getElementById("AXIB").checked=true;
    } else {
        var radList = document.getElementsByName('bankname');
        for (var i = 0; i < radList.length  ; i++) {
            if (radList[i].checked)
                radList[i].checked = false;
        }
    }
}

function addFaq() {
   var items = window.checkoutConfig.quoteItemData;
   var contents = [];
   for (var i = 0; i < items.length; i++) {
       contents.push({
               "id": items[i].sku.toString(),
               "quantity" : items[i].qty.toString(),
               "item_price" : items[i].price.toString()
       });
   }
   var paymentInfo = {
        contents: contents,
        content_type: 'product',
        value: window.checkoutConfig.quoteData.base_grand_total,
        currency: window.checkoutConfig.totalsData.base_currency_code
   };
   if (paymentInfo) {
        fbq('track', 'AddPaymentInfo', paymentInfo);
   }

   /*
   * Mixpanel for payment entered event code added below
   * */
    var cartData = JSON.parse(sessionStorage.getItem('cartDataMP'));
    var cartDataSQ = JSON.parse(sessionStorage.getItem('cartDataSQ'));
    var categories = [];
    var categoriesl1 = [];
    var categoriesl2 = [];
    var categoriesl3 = [];
    var brands = [];
    var sku = [];
    var x= Object.keys(cartData);
    var totalQty = parseInt(window.checkoutConfig.totalsData.items_qty);
    var cartTotal = Math.round(window.checkoutConfig.totalsData.subtotal_incl_tax);
    var grandTotal = Math.round(window.checkoutConfig.totalsData.base_grand_total);
    var discountTotal = parseInt(window.checkoutConfig.totalsData.discount_amount);
    var codFee = 0;
    var deliveryFee = 0;
    var eccategory = [];
    if(window.checkoutConfig.selectedShippingMethod !== null ) {
        if (window.checkoutConfig.selectedShippingMethod.amount !== null)
        {
            deliveryFee = parseInt(window.checkoutConfig.selectedShippingMethod.amount);
        }
    }

    if (typeof window.payment.method === 'undefined') {
        window.payment.method = 'creditcard';
    }
    if (typeof window.checkoutConfig.validCod !== 'undefined' &&
        typeof window.checkoutConfig.validCod.codFee !== 'undefined' &&
        window.checkoutConfig.validCod.codFee > 0 &&
        window.payment.method === 'cashondelivery') {
        codFee = parseInt(window.checkoutConfig.validCod.codFee);
        grandTotal += codFee;
    }
    for(var i=0; i < x.length; i++){
        var itemId = x[i];
        var product = cartData[itemId];
        var eccat = '';
        categories.push(product.category);
        if(i==0) {
            categoriesl1.push(product.categoryl1);
            categoriesl2.push(product.categoryl2);
            categoriesl3.push(product.categoryl3);
            if(product.categoryl1) {eccat = product.categoryl1;}
            if(product.categoryl2) {eccat += "/"+product.categoryl2;}
            if(product.categoryl3) {eccat += "/"+product.categoryl3;}
        }
        eccategory.push(eccat);
        brands.push(product.brand);
        sku.push(product.sku);
    }
    var paymentmethod = {
        checkmo:"Cheque",
        free: "Free",
        cashondelivery: "Cash On Delivery",
        banktransfer:"NEFT/RTGS",
        purchaseorder:"Purchase Order",
        authorizenet: "Authorize Net",
        paytm:"Paytm Wallet",
        creditcard:"Credit Card",
        debitcard:"Debit Card",
        netbanking:"Netbanking",
        emi: "PayU EMI",
        payumoney: "PayU Money Wallet",
        phonepe: "PhonePe UPI",
        kisshtpay:"Kissht EMI"
    };
    var coupon = window.checkoutConfig.totalsData.coupon_code;
    if(coupon === null) {
        coupon = '';
    }
    var code = window.payment.method;
    var flag = true;
    if (code === 'creditcard') {
        var x = document.getElementById('ccnum_cc').style.border;
        var y = document.getElementById('name_cc').style.border;
        var z = document.getElementById('card_date').style.border;
        var u = document.getElementById('card_cvv').style.border;
        var v = document.getElementById('ccnum_cc').value;
        x = x.search('255');
        y = y.search('255');
        z = z.search('255');
        u = u.search('255');
        flag = !((x > -1) || (y > -1) || (z > -1) || (u > -1) || v == '');

        var x = document.getElementById('ccnum_emi').style.border;
        var y = document.getElementById('name_emi').style.border;
        var z = document.getElementById('emi_card_date').style.border;
        var u = document.getElementById('emi_card_cvv').style.border;
        var v = document.getElementById('ccnum_emi').value;
        var t = document.getElementById('cardtype_emi').value;
        x = x.search('255');
        y = y.search('255');
        z = z.search('255');
        u = u.search('255');
        flag = flag || !((x > -1) || (y > -1) || (z > -1) || (u > -1) || v == '' || t == '');
    } else if (code === 'debitcard') {
        var x = document.getElementById('ccnum_dc').style.border;
        var y = document.getElementById('name_dc').style.border;
        var z = document.getElementById('card_date_dc').style.border;
        var u = document.getElementById('card_cvv_dc').style.border;
        var v = document.getElementById('ccnum_dc').value;
        x = x.search('255');
        y = y.search('255');
        z = z.search('255');
        u = u.search('255');
        flag = !((x > -1) || (y > -1) || (z > -1) || (u > -1) || v == '');
    } else if (code === 'netbanking') {
        var v = document.getElementById('bankcode_nb').value;
        flag = !(v == '');
    } else if (code === 'emi') {
        var t = document.getElementById('cardtype_emi').value;
        var v = document.getElementById('ccnum_emi').value;
        flag = !(v == '' || t == '');
    }

    if (typeof flag != 'undefined' && flag) {
        mixpanel.track(
            "Payment Info Entered",
            {
                "Payment Method" : paymentmethod[window.payment.method],
                "Cart Size" : cartDataSQ.cart_size,
                "Cart Items Qty" : cartDataSQ.qty_list,
                "Cart Value" : cartTotal,
                "Cart Items" : sku,
                "Total Amount" : grandTotal,
                "COD Fees" : codFee,
                "Cart Category L1" : categoriesl1,
                "Cart Category L2" : categoriesl2,
                "Cart Category L3" : categoriesl3,
                "Cart Brands" : brands,
                "Delivery Fees" : deliveryFee,
                "Coupon" : coupon,
                "Discount": discountTotal
            }
        );
        //EC code
        for(var i = 0; i < window.checkoutConfig.quoteItemData.length; i++) {
            var product = window.checkoutConfig.quoteItemData[i];
            ga('ec:addProduct', {
              'id': sku[i],
              'name': product.name,
              'category': eccategory[i],
              'brand': brands[i],
              'price': parseInt(product.base_price_incl_tax),
              'quantity': parseInt(product.qty)
            });
        }
        ga("ec:setAction", "checkout_option", {
          "step": 3,
          "option": paymentmethod[window.payment.method]
        });
        ga('send', 'event', 'Checkout', 'Option');
        //End of EC code
    }
}

/*
 * Validate the card holder name.
 */
function  validateName(self, flag) {
    var exp = /^[A-Za-z ]+$/;
    if (self.value === '' && flag) {
        self.style.border = '1px solid #FF0000';
    } else if (!exp.test(self.value) && (self.value || flag)) {
        self.style.border = '1px solid #FF0000';
        document.getElementById('error-' + self.id).style.display = 'block';
        document.getElementById('error-' + self.id).innerHTML = 'Name must be a-z, A-Z and space only!';
    } else {
        self.style.border = '1px solid #ccc';
        document.getElementById('error-' + self.id).style.display = 'none';
        document.getElementById('error-' + self.id).innerHTML = '';
    }
}

/*
 * Convert it into upper case.
 */
function convertUpperCase(self) {
    self.value = self.value.toUpperCase();
}

/*
 * This custom jquery use for make disabled false for
 * other payment option after selection.
 */
require(['jquery'],function($) {
    $(document).ready(function () {
        $("#co-payment-form").click(function () {
            $('input[name="payment[method]"]').click(function() {
                $(":radio[name='payment[method]']").each(function () {
                    if (!$(this).parent().parent().parent().hasClass('_active')) {
                        $(this).attr('disabled', false);
                    }
                });
                $(this).attr('disabled', true);
                var methods = ['payumoney', 'paytm', 'emi', 'kisshtpay'];
                for(var i=0; i < methods.length; i++) {
                    if (methods[i] === this.id) {
                        $('#' + methods[i]).parent().find('i').addClass('active-wlt');
                        $('#' + methods[i] + '-block').css('display', 'block');
                        $('#' + methods[i] + '-block').find('button').attr('disabled', false);
                    } else {
                        $('#' + methods[i]).parent().find('i').removeClass('active-wlt');
                        $('#' + methods[i] + '-block').css('display', 'none');
                    }
                }
            });
            $('input[name="emimonth"]').click(function() {
                $('#emi-block').css('display', 'block');
            });
        });
    });
});
