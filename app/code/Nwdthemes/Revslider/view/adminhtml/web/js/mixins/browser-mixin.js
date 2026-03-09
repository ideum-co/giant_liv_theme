define([
    'jquery'
], function ($) {
    'use strict';

    var mixin = {

            /**
             * @param {*} url
             * @param {*} width
             * @param {*} height
             * @param {*} title
             * @param {Object} options
             * @param {Function} options
             */
            openDialog: function(url, width, height, title, options, onInsert) {
                var windowId = this.windowId,
                    content = '<div class="popup-window" id="' + windowId + '"></div>';

                if (this.modalLoaded) {

                    if (!_.isUndefined(options)) {
                        this.modal.modal('option', 'closed', options.closed);
                    }

                    this.modal.modal('openModal');
                    $(window).trigger('reload.MediaGallery');

                    return;
                }

                this.modal = $(content).modal($.extend({
                    title:  title || 'Insert File...',
                    modalClass: 'magento',
                    type: 'slide',
                    buttons: []
                }, options));

                this.modal.modal('openModal');

                $.ajax({
                    url: url,
                    type: 'get',
                    context: $(this),
                    showLoader: true

                }).done(function (data) {
                    this.modal.html(data).trigger('contentUpdated');
                    this.modal.find('#insert_files').data('onInsert', onInsert);
                    this.modalLoaded = true;
                }.bind(this));

            }

        },

        widgetMixin = {

            /**
             * @param {jQuery.Event} event
             * @return {Boolean}
             */
            insert: function (event) {
                var fileRow = $(event.currentTarget),
                    onInsert = this.element.find('#insert_files').data('onInsert');

                if (!fileRow.prop('id')) {
                    return false;
                }

                return $.ajax({
                    url: this.options.onInsertUrl,
                    data: {
                        filename: fileRow.attr('id'),
                        node: this.activeNode.id,
                        store: this.options.storeId,
                        'as_is': 0,
                        'force_static_path': 0,
                        'form_key': FORM_KEY
                    },
                    context: this,
                    showLoader: true
                }).done($.proxy(function (data) {
                    if (typeof data == 'string') {
                        data = JSON.parse(data.replace(/(\r\n|\n|\r)/gm, "").trim());
                    }
                    if (data.image) {
                        onInsert(data.image, Base64.encode(data.image), data.width, data.height);
                        MediabrowserUtility.closeDialog();
                    } else if (data.ajaxRedirect) {
                        document.location = data.ajaxRedirect;
                    }
                }, this));
            }

        }

    return function (widget) {
        if (typeof widget == 'undefined') {
            // 2.2.6 and below
            widget = window.MediabrowserUtility;
            require.config({
                paths: {
                    'jquery/file-uploader': 'jquery/fileUploader/jquery.fileupload-fp',
                    'prototype': 'legacy-build.min'
                },
                shim: {
                    'jquery/file-uploader': {
                        deps: ['prototype']
                    }
                }
            });
        }
        if (typeof revMageImageUploadUrl != 'undefined') {
            $.extend(widget, mixin);
            $.widget('mage.mediabrowser', $.mage.mediabrowser, widgetMixin);
        }
        return widget;
    };

});