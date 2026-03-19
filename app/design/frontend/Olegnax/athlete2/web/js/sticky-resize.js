/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define( [
    'jquery',
    'matchMedia',
    'jquery-ui-modules/widget',
    'jquery-ui-modules/core',
], function ( $, mediaCheck ) {
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
    $.widget( 'mage.OXstickyResize', {
        options: {
            stickyTarget: null,
            scrollStart: null,
            defaultClass: 'ox-sticky',
            stickyClass: 'sticky',
            maxHeight: null,
            addMargin: false,
            margin: 0,
            marginTarget: null,
            timeout: 0,
            interval: 10,
            mediaBreakpoint: 1025,
            searchOver: false,
            resizedHeight: 64
        },
        _create: function () {
            this.status = null;
            mediaCheck( {
                media: '(min-width: ' + this.options.mediaBreakpoint + 'px)',
                entry: $.proxy( function () {
                    this.minimize = true
                }, this ),
                exit: $.proxy( function () {
                    this.minimize = false
                }, this ),
            } );

            if ( this.options.stickyTarget ) {
                this.target = this.element.find( this.options.stickyTarget );
            } else {
                this.target = this.element;
            }
            if ( !this.target.length ) {
                this.target = this.element;
            }
            this.stickyWrapper = this.target.children( '.sticky-wrapper' );
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
            if ( 'height' == this.options.scrollStart ) {
                this.options.scrollStart = this.target.outerHeight();
            }
            if (this.element.offset().top) {
                this.options.scrollStart += this.target.offset().top;
            }
            this.lastPosition = $( window ).scrollTop();
            this.delt_minimize = Math.max((this.stickyWrapper.height() - this.options.resizedHeight), 0);
            this.search_mini_form = this.target.find( '.block-search--type-panel' ).find( '#search_mini_form' ).find('.search_form_wrap');
            var _self = this;    
            $( window ).on( 'scroll', function () {
                debouncer(_self.refresh(), _self.options.interval );
            } );        
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
            this._toggleSearch();
            this.element.trigger( 'initedsticky.ox' );

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
        refresh: function () {
            var scrollPosition = $( window ).scrollTop();
            var direction = scrollPosition - this.lastPosition;
            this.lastPosition = scrollPosition;
            var scroll_start = this.options.scrollStart;
            var status = scroll_start < scrollPosition;
            if ( 0 > direction ) {
                scroll_start = this.target.offset().top;
                status = scroll_start < scrollPosition;
            }    
            // Margin
            this.addMargin( status );
            // Sticky Class
           this.target.toggleClass( this.options.stickyClass, status );

            if ( !window.matchMedia( '(max-width: ' + (this.options.mediaBreakpoint - 1) + 'px)' ).matches ) {
                this._hideSearch( status );
            }

            if ( this.status != status ) {
                this._updateHeight_logo();
            }
            this.status = status;
            var status_minimize = this.options.scrollStart + this.delt_minimize < scrollPosition;
            if ( this.minimize ) {
                if ( status_minimize ) {
                    this._stickyMinimize();
                } else {
                    this._stickyMaximize();
                }
            }

            this.target.toggleClass( 'resize', status_minimize );
            if(status_minimize){
                this._setHeight();               
            } 
        },
        _setHeightVar: function (){
            var _offset = this.stickyWrapper.height() - this.logo_height;
            if(this.offset != _offset && _offset > 0) {
                this.offset = _offset;
                window.a2header_sticky_height = this.offset;
                this.element.trigger( 'heightsticky.ox' );
            }
        },
        _setHeight: function (){
            this._setHeightVar();
            const that = this;
            let getHeaderHeightTimeout;
            clearTimeout( getHeaderHeightTimeout );
            getHeaderHeightTimeout = setTimeout( function () {
                that._setHeightVar();
            }, 220 ); //css animation speed
        },
        _updateHeight: function () {
            var topbar = this.stickyWrapper.find( '.top-bar' );
            var topbar_height = topbar.height();
            topbar.css( 'height', topbar_height );
            this.target.css( 'min-height', '' );
            var height = this.stickyWrapper.height();
            if ( 0 < height ) {
                this.target.css( 'min-height', height );
                window.a2header_height = height;
            }
            this._setHeight();

        },
        _updateHeight_logo: function ( _status ) {
            if ( window.matchMedia( '(max-width: ' + this.options.mediaBreakpoint + 'px)' ).matches && $( 'body.sticky-minimized.mobile-header--layout-2' ).length && this.target.hasClass( this.options.stickyClass ) ) {
                var height = this.stickyWrapper.height();
                var $logo = this.target.find( '.logo__container' );
                if ( $logo.length ) {
                    var logo_height = $logo.outerHeight();
                    height -= logo_height;
                    this.logo_height = logo_height;
                    this.stickyWrapper.css( { 'transform': 'translateY(-' + logo_height + 'px)' } );
                }
                if ( 0 < height && !_status ) {
                    this.target.css( 'min-height', height );
                }
                this._setHeight();
            } else {
                this.logo_height = 0;
                this.stickyWrapper.css( { 'transform': '' } );
            }

        },
        _itemMove: function ($item, _info) {
            $item.each( $.proxy( function ( index, item ) {
                var $this = $( item ),
                    _class = ( ( $this.attr( 'class' ) || '' ).match( /ox-move-sticky-([^ ]{1,})/i ) || [ '', '' ] )[1],
                    $sticky_parent = $( '[data-move-sticky="' + _class + '"]' ).eq( 0 );
                if ( !_class || !$sticky_parent.length || $this.parent().is( $sticky_parent ) ) {
                    return;
                }
                if ( !$( '[data-move-back="' + _class + '"]' ).length ) {
                    $this.parent().attr( 'data-move-back', _class );
                }
                $this.data( 'moveBackPosition', $this.parent().children().index( $this ) );
                var element = $this.detach();
                $sticky_parent.append( element );
                if(_info){
                    $('html').addClass(_info + '-moved');
                }
            }, this ) );
        },
        _itemMoveBack: function ($item, _info) {
            $item.each( $.proxy( function ( index, item ) {
                var $this = $( item ),
                    _class = ( ( $this.attr( 'class' ) || '' ).match( /ox-move-sticky-([^ ]{1,})/i ) || [ '', '' ] )[1],
                    $back_parent = $( '[data-move-back="' + _class + '"]' ).eq( 0 ),
                    position = $this.data( 'moveBackPosition' ) || 0;
                if ( !_class || !$back_parent.length || $this.parent().is( $back_parent ) ) {
                    return;
                }
                var element = $this.detach();
                if ( 0 < position ) {
                    var prev = $back_parent.children().eq( position - 1 );

                    if ( prev.length ) {
                        prev.after( element );
                    } else {
                        $back_parent.prepend( element );
                    }
                } else {
                    $back_parent.prepend( element );
                }
                 if(_info){
                    $('html').removeClass(_info + '-moved');
                }
            }, this ) );
        },
        _stickyMinimize: function () {
            this._itemMove($( '.ox-move-sticky' ));
            this._itemMove($( '.ox-move-search' ), 'search');
            if (this._searchInOverlay()) {
                var $searchModal = $('.ox-move-search').parent(),
                    searchModal = $searchModal.data('mageOXmodal') || $searchModal.data('mage-OXmodal');
                if (searchModal) {
                    $searchModal.find(searchModal.options.closeButtonTrigger)
                    .off('click.moveSearchOX')
                }
            }
        },
        /**
         * @private
         */
        _stickyMaximize: function () {
            this._itemMoveBack($( '.ox-move-sticky' ));
            if (this._searchInOverlay()) {
                var $searchModal = $('.ox-move-search').parent(),
                    searchModal = $searchModal.data('mageOXmodal') || $searchModal.data('mage-OXmodal');
                if (searchModal) {
                    $searchModal.find(searchModal.options.closeButtonTrigger)
                    .off('click.moveSearchOX')
                    .one('click.moveSearchOX', $.proxy(function () {
                        this._itemMoveBack($('.ox-move-search'), 'search');
                    }, this));
                }
            } else {
                this._itemMoveBack($('.ox-move-search'), 'search');
            }
            
        },
        _searchInOverlay: function () {
            return $('html').hasClass('ox-fixed') && 0 < $('.ox-dialog .ox-move-search').length;
        },

        _hideSearch: function ( status ) {
            this.options.searchOver = false;
            if ( status ) {
                this.search_mini_form.find( '#search' ).css( { 'opacity': 0 } );
                this.search_mini_form.find( '#search' ).removeClass( 'animate' );
                // $( 'body' ).removeClass('ox-search-opened form-search-over');
            } else {
                this.search_mini_form.find( '#search' ).removeAttr( 'style' );
            }
        },
        _toggleSearch: function () {
            var _this = this;
           
            if ( _this.search_mini_form.length ) {
                
                this.target.on( 'click', '.block-search--type-panel #search_mini_form .search_form_wrap', function ( event ) {
                    if ( window.matchMedia( '(max-width: ' + _this.options.mediaBreakpoint + 'px)' ).matches || $( 'body.ox-slideout-active' ).length ) {
                        return
                    }
                    event.stopPropagation();
                    if ( _this.options.searchOver ) {
                        return true;
                    }
                    var $this = $( this );
                    $( 'body' ).addClass('ox-search-opened form-search-over');
                    $this.find( '#search' ).addClass( 'animate' );
                    $this.find( '#search' ).stop( true, false ).css( 'opacity', 0 ).animate( { opacity: 1 }, 400, 'easeOutExpo', function () {
                        _this.options.searchOver = true;
                    } );
                    return false;

                } );
                //Hide search if visible
                $( 'body' ).on( 'click', function ( event ) {
                    if ( window.matchMedia( '(max-width: ' + _this.options.mediaBreakpoint + 'px)' ).matches || $( 'body.ox-slideout-active' ).length || _this.options.scrollStart + _this.delt_minimize >= $( window ).scrollTop() ) {
                        return
                    }
                    if ( _this.options.searchOver ) {
                        _this.options.searchOver = false;
                        _this.search_mini_form.find( '#search' ).removeClass( 'animate' );
                        _this.search_mini_form.find( '#search' ).stop( true, false ).animate( { opacity: 0 }, 400, 'easeInExpo', function () {
                            $( 'body' ).removeClass('ox-search-opened form-search-over');
                        } );
                    }

                } );
                _this.search_mini_form.find( '#search' ).on( "touchend", function ( e ) {
                    e.stopPropagation();
                } );
            }
        }
    } );
    return $.mage.OXstickyResize;
} );