/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/grid/columns/column',
    'escaper'
], function (Column, escaper) {
    'use strict';

    return Column.extend({
        defaults: {
            allowedTags: ['div', 'span', 'b', 'strong', 'i', 'em', 'u', 'a'],
        },
        /**
         * Title column.
         *
         * @param {String} label
         * @returns {String}
         */
        getNameUnsanitizedHtml: function (label, tag) {
            return '<' + tag + ' role="heading" aria-level="2">' + escaper.escapeHtml(label, this.allowedTags) + '</' + tag + '>';
        }
    });
});
