/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/grid/columns/column',
], function ($, Column) {
    'use strict';

    return Column.extend({
        isAllowed: function (show) {
            $('body').trigger('contentUpdated');
            return !!show;
        }
    });
});
