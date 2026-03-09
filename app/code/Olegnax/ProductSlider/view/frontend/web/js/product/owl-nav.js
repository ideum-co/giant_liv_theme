/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/column',
], function (Column) {
    'use strict';

    return Column.extend({
        /**
         * Depends on this option, "owl nav" can be shown or hide. Depends on  backend configuration
         *
         * @returns {Boolean}
         */
        isAllowed: function (show) {
            return !!show;
        }
    });
});
