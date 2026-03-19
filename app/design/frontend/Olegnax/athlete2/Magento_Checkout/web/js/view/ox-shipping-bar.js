define([
    'ko',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
], function (ko, Component, customerData, priceUtils) {
    'use strict';

    return Component.extend({
        displaySubtotal: ko.observable(true),
        cssClass: ko.observable(''),
        freeShippingRemaining: ko.observable(''),
        isSuccess: ko.observable(false),
        defaults: {
            template: 'Magento_Checkout/ox-shipping-bar/content'
        },

        initialize: function () {
            this._super();
            this.cart = customerData.get('cart');
            this.calculateRemaining();
            this.cart.subscribe(this.calculateRemaining.bind(this));
        },
        
        calculateRemaining: function () {
            var subtotalAmount = this.cart().subtotalAmount;
            var remaining = Math.max(0, this.freeShippingPrice - Math.min(this.freeShippingPrice, subtotalAmount));
            this.freeShippingRemaining(remaining);
        },

        getPercentage: function () {
            var subtotalAmount = Math.min(this.freeShippingPrice, this.cart().subtotalAmount);
            return (subtotalAmount * 100) / this.freeShippingPrice;
        },

        getShowWhenEmpty: function () {
            if(this.hideWhenEmpty){
                return this.cart().summary_count > 0;
            }
            return true;
        },

        getContent: function () {
            var output = this.getOptionsText();
            output = output.replace('{{free_shipping_price}}', this.getFormattedPrice(this.freeShippingPrice));
            output = output.replace('{{free_shipping_remaining_price}}', this.getFormattedPrice(this.freeShippingRemaining()));
            return output;
        },

        getOptionsText: function () {

            if (this.freeShippingRemaining() <= 0) {
                this.isSuccess(true);
                this.cssClass('success');
                return this.freeShippingTextSuccess;
            } else if (this.freeShippingRemaining() < this.freeShippingPrice) {
                this.cssClass('progress');
                return this.freeShippingTextProgress;
            } else{
                this.cssClass('');
                return this.freeShippingText;
            }
        },

        getFormattedPrice: function (value) {
            if (value === undefined || value === null) {
                return '';
            }
            var price = (Math.round(value * 100) / 100).toFixed(2);
            return "<span class=\"price\">" + priceUtils.formatPrice(price, this.priceFormat) + "</span>";
        },
    });
});
