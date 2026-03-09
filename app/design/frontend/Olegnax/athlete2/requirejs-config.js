/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            "jquery/hoverintent": "lib/jquery-hoverintent/jquery.hoverIntent.min",
            "OXjquery-zoom": "lib/jquery-zoom/jquery.zoom.min",
            "OXmodal": "js/modal",
            "OXmodalMinicart": "js/modal-minicart",
            "OXmodalWishlist": "js/modal-wishlist",
            "OXmodalPhotoswipe": "js/modal-photoswipe",
            "Athlete2/modal": "js/modal",
            'AtloopOwlAddtocart': 'js/loopaddtocart-owl.carousel',
            "AtProductValidate": 'js/validate-product',            
            "OXExpand": "js/expand",
            "ox-thumb-carousel": "js/ox-thumb-carousel",
            "sticky-sidebar": "js/sticky-sidebar",
            "ox-video": "js/ox-video",
            "OXmobileNoSlider": "js/mobile-noslider",
            "OXcountdown": "js/ox-countdown",
            "photoswipe": "lib/photoswipe/photoswipe",
            "photoswipe-ui": "lib/photoswipe/photoswipe-ui-default",
            "photoswipe-init":  "js/photoswipe"
        }
    },
    paths:{},
    shim: {
        'OXjquery-zoom': {
            deps: ['jquery']
         }
      },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'js/mixins/catalog-add-to-cart': true
            },
            'Cynoinfotech_FreeShippingMessage/js/catalog-add-to-cart': {
                'js/mixins/catalog-add-to-cart-CFSM': true
            },
            'Magento_Paypal/js/order-review': {
                'js/mixins/order-review': true
            }
        }
    }
};
if (!OX_CATALOG_AJAX) {
    delete config.config.mixins['Magento_Catalog/js/catalog-add-to-cart'];
    delete config.config.mixins['Cynoinfotech_FreeShippingMessage/js/catalog-add-to-cart'];
}

if(OX_OWL_DISABLE){
    delete config.map['*']['AtloopOwlAddtocart'];
}
if(OX_WAYPOINTS){
    config.paths['waypoints'] = "js/waypoints";
    config.shim['js/waypoints'] = ["jquery"];
    config.map['*']['waypoints'] = "js/waypoints";
    config.map['*']['ox-waypoints-init'] = "js/waypoints-init";
}
