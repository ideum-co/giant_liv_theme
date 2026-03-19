(function() {
    'use strict';

    function waitForJQuery(callback) {
        if (typeof window.jQuery !== 'undefined') {
            callback(window.jQuery);
        } else if (typeof require === 'function') {
            require(['jquery'], function($) {
                callback($);
            });
        } else {
            var attempts = 0;
            var interval = setInterval(function() {
                attempts++;
                if (typeof window.jQuery !== 'undefined') {
                    clearInterval(interval);
                    callback(window.jQuery);
                } else if (attempts > 100) {
                    clearInterval(interval);
                }
            }, 100);
        }
    }

    waitForJQuery(function($) {
        initGiantInteractive($);
    });

    function initGiantInteractive($) {
        var $body = $('body');
        var isHomepage = $body.hasClass('cms-index-index');
        var panelOpen = false;
        var activeCategory = null;

        function buildSlideMenu() {
            if ($('#giant-slide-menu').length) return;

            var $overlay = $('<div id="giant-slide-overlay"></div>');
            var $panel = $('<div id="giant-slide-menu"></div>');
            var $closeBtn = $('<button class="giant-slide-close" aria-label="Cerrar">&times;</button>');
            var $backBtn = $('<button class="giant-slide-back" aria-label="Volver"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>');
            var $colContainer = $('<div class="giant-slide-columns"></div>');

            $panel.append($closeBtn).append($backBtn).append($colContainer);
            $body.append($overlay).append($panel);

            var categories = [];
            $('.ox-megamenu-navigation > li.level-top').each(function() {
                var $li = $(this);
                var $a = $li.find('> a.level-top');
                var name = $a.find('.name').text().trim();
                var href = $a.attr('href') || '#';
                var subs = [];

                var $tabs = $li.find('.tabs-navigation .tab-header');
                if ($tabs.length) {
                    $tabs.each(function() {
                        var $tab = $(this);
                        var tabName = $tab.find('span.tab-title').text().trim() || $tab.find('.tab-title').first().text().trim();
                        var tabHref = $tab.find('a').attr('href') || '';
                        var tabId = tabHref.replace('#', '');
                        var children = [];

                        var $tabContent = $li.find('#' + tabId);
                        if ($tabContent.length) {
                            var $showAll = $tabContent.find('a.toggle-product-subcategory');
                            if ($showAll.length) {
                                var showAllHref = $showAll.attr('href') || '#';
                                children.push({name: 'Mostrar todo ' + tabName, href: showAllHref, isShowAll: true});
                            }

                            $tabContent.find('.subcategory a, .two-column-list a, .circles a').each(function() {
                                var $link = $(this);
                                var childName = $link.find('.text').text().trim() || $link.text().trim();
                                var childHref = $link.attr('href') || '#';
                                if (childName && !$link.hasClass('toggle-product-subcategory') && childName !== tabName) {
                                    children.push({name: childName, href: childHref});
                                }
                            });

                            if (children.length === 0) {
                                $tabContent.find('a').each(function() {
                                    var $link = $(this);
                                    if ($link.hasClass('toggle-product-subcategory')) return;
                                    var childName = $link.text().trim();
                                    var childHref = $link.attr('href') || '#';
                                    if (childName && childName.length < 80) {
                                        children.push({name: childName, href: childHref});
                                    }
                                });
                            }
                        }

                        subs.push({name: tabName, href: '#', children: children});
                    });
                }

                if (name) {
                    categories.push({name: name, href: href, subs: subs});
                }
            });

            var extraLinks = [
                {name: 'Ofertas', href: '/ofertas.html', subs: []},
                {name: 'Distribuidores', href: '/distribuidores', subs: []}
            ];
            var existingNames = categories.map(function(c) { return c.name.toLowerCase(); });
            extraLinks.forEach(function(link) {
                if (existingNames.indexOf(link.name.toLowerCase()) === -1) {
                    categories.push(link);
                }
            });

            function renderMainColumn() {
                $colContainer.empty();
                activeCategory = null;
                $backBtn.removeClass('visible');

                var $col = $('<div class="giant-slide-col giant-slide-col-main"></div>');
                var $title = $('<div class="giant-slide-title">Menú</div>');
                $col.append($title);

                var $list = $('<ul class="giant-slide-list"></ul>');
                categories.forEach(function(cat, idx) {
                    var $item = $('<li></li>');
                    var $link = $('<a href="' + cat.href + '">' + cat.name + '</a>');
                    if (cat.subs.length > 0) {
                        var $arrow = $('<span class="giant-slide-arrow"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 1L7 7L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>');
                        $link.append($arrow);
                        $link.on('click', function(e) {
                            e.preventDefault();
                            renderSubColumn(cat, idx);
                        });
                    }
                    $item.append($link);
                    $list.append($item);
                });
                $col.append($list);
                $colContainer.append($col);
            }

            function renderSubColumn(cat) {
                $colContainer.empty();
                activeCategory = cat;
                $backBtn.addClass('visible');

                var $mainCol = $('<div class="giant-slide-col giant-slide-col-main"></div>');
                var $title = $('<div class="giant-slide-title">' + cat.name + '</div>');
                $mainCol.append($title);

                var $list = $('<ul class="giant-slide-list"></ul>');
                cat.subs.forEach(function(sub) {
                    var $item = $('<li></li>');
                    var $link = $('<a href="#">' + sub.name + '</a>');
                    if (sub.children.length > 0) {
                        var $arrow = $('<span class="giant-slide-arrow"><svg width="8" height="14" viewBox="0 0 8 14" fill="none"><path d="M1 1L7 7L1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>');
                        $link.append($arrow);
                        $link.on('click', function(e) {
                            e.preventDefault();
                            $list.find('li').removeClass('active');
                            $item.addClass('active');
                            renderChildColumn(sub);
                        });
                    }
                    $item.append($link);
                    $list.append($item);
                });
                $mainCol.append($list);
                $colContainer.append($mainCol);
            }

            function renderChildColumn(sub) {
                $colContainer.find('.giant-slide-col-child').remove();

                var $childCol = $('<div class="giant-slide-col giant-slide-col-child"></div>');
                var $title = $('<div class="giant-slide-title">' + sub.name + '</div>');
                $childCol.append($title);

                var $list = $('<ul class="giant-slide-list"></ul>');
                sub.children.forEach(function(child) {
                    var $item = $('<li></li>');
                    var cls = child.isShowAll ? ' class="giant-slide-showall"' : '';
                    var $link = $('<a href="' + child.href + '"' + cls + '>' + child.name + '</a>');
                    $item.append($link);
                    $list.append($item);
                });
                $childCol.append($list);
                $colContainer.append($childCol);
            }

            $closeBtn.on('click', function() {
                closePanel();
            });

            $overlay.on('click', function() {
                closePanel();
            });

            $backBtn.on('click', function() {
                if (activeCategory) {
                    renderMainColumn();
                }
            });

            window.giantSlideMenu = {
                open: function(catName) {
                    renderMainColumn();
                    if (catName) {
                        categories.forEach(function(cat) {
                            if (cat.name.toLowerCase() === catName.toLowerCase() && cat.subs.length > 0) {
                                renderSubColumn(cat);
                            }
                        });
                    }
                    $panel.addClass('open');
                    $overlay.addClass('open');
                    $body.addClass('giant-menu-open');
                    panelOpen = true;
                },
                close: closePanel
            };
        }

        function closePanel() {
            $('#giant-slide-menu').removeClass('open');
            $('#giant-slide-overlay').removeClass('open');
            $body.removeClass('giant-menu-open');
            panelOpen = false;
        }

        buildSlideMenu();

        $(document).off('click.gianthamburger').on('click.gianthamburger', '.giant-hamburger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (panelOpen) {
                closePanel();
            } else if (window.giantSlideMenu) {
                window.giantSlideMenu.open();
            }
        });

        $(document).off('click.giantnav').on('click.giantnav', '.giant-nav-links a', function(e) {
            var catName = $(this).text().trim();
            var hasSubmenu = false;
            $('.ox-megamenu-navigation > li.level-top').each(function() {
                var name = $(this).find('> a.level-top .name').text().trim();
                if (name.toLowerCase() === catName.toLowerCase() && $(this).find('.tabs-navigation .tab-header').length > 0) {
                    hasSubmenu = true;
                }
            });
            if (hasSubmenu) {
                e.preventDefault();
                window.giantSlideMenu.open(catName);
            }
        });

        $(document).on('keydown.giantmenu', function(e) {
            if (e.key === 'Escape' && panelOpen) {
                closePanel();
            }
        });


        var $pageHeader = $('.page-header');
        var scrollThreshold = 50;
        var ticking = false;
        var wasScrolled = false;

        function onScroll() {
            if (!ticking) {
                var raf = window.requestAnimationFrame || function(cb) { setTimeout(cb, 16); };
                raf(function() {
                    var isScrolled = window.scrollY > scrollThreshold;
                    if (isScrolled !== wasScrolled) {
                        wasScrolled = isScrolled;
                        if (isScrolled) {
                            $pageHeader.addClass('giant-scrolled');
                        } else {
                            $pageHeader.removeClass('giant-scrolled');
                        }
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', onScroll, {passive: true});
        onScroll();

        if (isHomepage) {
            var hasHero = $('.rev_slider, .tp-revslider-mainul, [class*="banner-slider"], .hero-slider, .cms-index-index .widget-banner').length > 0;
            if (hasHero) {
                $body.addClass('giant-has-hero');
            }
            setTimeout(function() {
                if ($('.rev_slider, .tp-revslider-mainul, [class*="banner-slider"], .hero-slider, .cms-index-index .widget-banner').length > 0) {
                    $body.addClass('giant-has-hero');
                }
            }, 2000);
        }

        function triggerLazyImages(container) {
            var $scope = container ? $(container) : $(document);
            $scope.find('img.lazy[data-original]').each(function() {
                var $img = $(this);
                var src = $img.attr('data-original');
                if (src && $img.attr('src') !== src) {
                    $img.attr('src', src).removeClass('lazy');
                    $img.closest('.product-image-wrapper').removeClass('lazy-loader');
                    $img.closest('.product-item-photo').removeClass('lazy-loader');
                }
            });
        }

        $(document).on('init afterChange', '.similares .slick-slider, .widget-product-carousel .slick-slider', function() {
            setTimeout(function() { triggerLazyImages(this); }.bind(this), 200);
        });

        if (window.MutationObserver) {
            var lazyObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    if (m.addedNodes.length) {
                        Array.prototype.forEach.call(m.addedNodes, function(node) {
                            if (node.nodeType === 1 && (node.classList.contains('slick-slide') || node.querySelector && node.querySelector('img.lazy[data-original]'))) {
                                triggerLazyImages(node);
                            }
                        });
                    }
                });
            });
            var similaresEl = document.querySelector('.similares');
            if (similaresEl) {
                lazyObserver.observe(similaresEl, { childList: true, subtree: true });
            }
            var widgetCarousels = document.querySelectorAll('.widget-product-carousel');
            widgetCarousels.forEach(function(el) {
                lazyObserver.observe(el, { childList: true, subtree: true });
            });
        }

        setTimeout(function() { triggerLazyImages(); }, 4000);

        if (typeof require === 'function') {
            require(['Magento_Customer/js/customer-data'], function(customerData) {
                try {
                    var syncDone = window.sessionStorage.getItem('giant_cart_synced');
                    if (syncDone) return;

                    var cart = customerData.get('cart');
                    var cartData = cart();
                    var mci = document.cookie.match(/mage-cache-invalidate=([^;]+)/);
                    var needsReload = false;

                    if (mci && mci[1] === '1') {
                        customerData.invalidate(['cart']);
                        needsReload = true;
                    }

                    if (!cartData || !cartData.data_id) {
                        needsReload = true;
                    }

                    if (needsReload) {
                        customerData.reload(['cart'], true);
                    }

                    window.sessionStorage.setItem('giant_cart_synced', '1');
                } catch (e) {}
            });
        }
    }
})();
