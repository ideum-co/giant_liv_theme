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
        getLabel: function (row) {
            return row.name;
        },
        /**
         * Get Product name for link.
         *
         * @param {String} label
         * @returns {String}
         */
        getNameUnsanitizedHtml: function (label) {
            return escaper.escapeHtml(label, this.allowedTags);
        }
    });
});
