/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define( [
    'jquery',
    'jquery-ui-modules/widget',
    'jquery-ui-modules/core',
], function ( $ ) {
    'use strict';
    function debouncer( func, timeout ) {
        var timeoutID, timeout = timeout || 500;
        return function () {
            var scope = this,
                args = arguments;
            clearTimeout( timeoutID );
            timeoutID = setTimeout( function () {
                func.apply( scope, Array.prototype.slice.call( args ) );
            }, timeout );
        }
    };
    $.widget( 'mage.OXstickySimple', {
        options: {
            stickyTarget: null,
            scrollStart: null,
            defaultClass: 'ox-sticky',
            stickyClass: 'sticky',
            addMargin: false,
            margin: 0,
            marginTarget: null,
            interval: 10,
            mediaBreakpoint: 1025,
        },
        _create: function () {
            this.status = null;
            if ( this.options.stickyTarget ) {
                this.target = this.element.find( this.options.stickyTarget );
            } else {
                this.target = this.element;
            }
            if ( !this.target.length ) {
                this.target = this.element;
            }
            this.element.trigger( 'initsticky.ox' );
            this._updateHeight();
            if ( this.options.addMargin ) {
                if ( this.options.marginTarget ) {
                    this.marginTarget = $( this.options.marginTarget );
                } else {
                    this.marginTarget = this.element.next();
                }
                this.marginTarget.data( 'margin-top', this.marginTarget.css( 'margin-top' ) );
                this.margin_outerHeight = this.target.outerHeight();
            }
            this.margin__outerHeight = 0;
            if ( this.options.defaultClass ) {
                this.target.addClass( this.options.defaultClass );
            }

            var _self = this;    
                 
            var win_size = $( window ).innerWidth();
            $( window ).on( 'resize', function () {
                var new_size = $( window ).innerWidth();
                if ( win_size == new_size ) {
                    return;
                }
                debouncer( _self._updateHeight() );
                setTimeout( function () {
                    debouncer( _self._updateHeight() );
                }, 310 );
            } );
            $( '.page-header' ).on( 'ox.header.refresh', function () {
                console.log('ox.header.refresh');
                debouncer( _self._updateHeight() );
            } );
        },

        _initScroll: function(){
            let _self = this;
            this.options.scrollStart = $( '.page-header' ).offset().top;
            if( this.options.scrollStart > 0 ){
                $( window ).on( 'scroll.oxsticky', function(){
                    debouncer(_self.refresh(), _self.options.interval );
                });
            }else{
                $( window ).off( 'scroll.oxsticky');
                this.target.addClass( this.options.stickyClass );
                this.addMargin( true );
            }
        },
        refresh: function () {
            var status = window.pageYOffset > this.options.scrollStart;
            // Margin
            this.addMargin( status );
            // Sticky Class
           this.target.toggleClass( this.options.stickyClass, status );

           this.status = status;
        },
        addMargin: function ( status ) {
            if ( !this.options.addMargin )
                return;
            var margin = 0;
            if ( status ) {
                if ( this.options.margin ) {
                    margin = this.options.margin;
                } else {
                    if ( !this.margin__outerHeight && !status ) {
                        this.margin__outerHeight = this.target.outerHeight();
                    }
                    margin = this.margin__outerHeight ? this.margin__outerHeight : this.margin_outerHeight;
                }
                this.marginTarget.css( 'margin-top', margin );
            } else {
                margin = this.marginTarget.data( 'margin-top' );
                if ( margin ) {
                    this.marginTarget.css( 'margin-top', margin );
                } else {
                    this.marginTarget.css( 'margin-top', null );
                }
            }
        },
        _updateHeight: function () {
            this.target.css( 'min-height', '' );
            var height = this.target.children( '.sticky-wrapper' ).height();
            if ( 0 < height ) {
                this.target.css( 'min-height', height );
                window.a2header_sticky_height = height;
                requestAnimationFrame(() => $('body').css('--ox-header-height', height + 'px' ));
            }
            this._initScroll();
        },
    } );
    return $.mage.OXstickySimple;
} );