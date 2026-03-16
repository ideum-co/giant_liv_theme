/**
 * Giant Checkout — main JavaScript controller
 *
 * Handles the 4-step checkout accordion:
 *  Step 1 – Personal info (login / guest)
 *  Step 2 – Shipping method selection
 *  Step 3 – Distributor selection (only when cart contains a bicycle)
 *  Step 4 – Payment & place order
 *
 * Communicates with Magento REST API and the custom Giant endpoint.
 */
(function () {
    'use strict';

    /* ─────────────────────────────────────────────
       State
    ───────────────────────────────────────────── */
    const cfg = window.GIANT_CHECKOUT_CONFIG || {};

    const state = {
        currentStep    : 1,
        guestEmail     : null,
        guestData      : null,
        cartId         : null,         // guest masked cart ID or null for logged-in
        selectedMethod : null,         // shipping method code
        selectedDist   : null,         // distributor entity_id
        selectedPayment: null,         // payment method code
        hasBicycle     : cfg.hasBicycle     || false,
        isLoggedIn     : cfg.isLoggedIn     || false,
        department     : null,         // user's selected department for address/distributor
    };

    /* Max step considering the bicycle conditional step */
    const MAX_STEP = state.hasBicycle ? 4 : 3;

    /* Map logical step number to DOM element id */
    function stepId(n) {
        if (n === MAX_STEP) return 'giant-step-payment';
        if (state.hasBicycle && n === 3) return 'giant-step-3';
        return 'giant-step-' + n;
    }

    /* ─────────────────────────────────────────────
       Utilities
    ───────────────────────────────────────────── */
    function $(selector, ctx) {
        return (ctx || document).querySelector(selector);
    }

    function $$(selector, ctx) {
        return Array.from((ctx || document).querySelectorAll(selector));
    }

    function show(el) { if (el) el.style.display = ''; }
    function hide(el) { if (el) el.style.display = 'none'; }

    function showError(elId, msg) {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = msg;
        show(el);
    }

    function clearError(elId) {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = '';
        hide(el);
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.classList.toggle('giant-btn--loading', loading);
    }

    /**
     * Generic AJAX helper wrapping fetch with JSON support.
     */
    async function ajax(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type' : 'application/json',
                'Accept'       : 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        };
        const response = await fetch(url, Object.assign({}, defaults, options, {
            headers: Object.assign({}, defaults.headers, options.headers || {}),
        }));
        if (!response.ok) {
            const text = await response.text();
            throw new Error(text || response.statusText);
        }
        const text = await response.text();
        if (!text) return null;
        try { return JSON.parse(text); } catch (e) { return text; }
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /* ─────────────────────────────────────────────
       Step accordion logic
    ───────────────────────────────────────────── */

    /**
     * Open a step: expand its body, mark as active.
     * Close all other steps.
     */
    function openStep(n) {
        $$('.giant-step').forEach(el => {
            const s    = parseInt(el.dataset.step, 10);
            const body = el.querySelector('.giant-step__body');
            const hdr  = el.querySelector('.giant-step__header');

            if (s === n) {
                el.classList.add('giant-step--active');
                el.classList.remove('giant-step--done');
                hdr.setAttribute('aria-expanded', 'true');
                show(body);
            } else if (s < n) {
                // already completed
                el.classList.remove('giant-step--active');
                el.classList.add('giant-step--done');
                hdr.setAttribute('aria-expanded', 'false');
                hide(body);
                const editLink = el.querySelector('.giant-step__edit-link');
                show(editLink);
            } else {
                // future step
                el.classList.remove('giant-step--active', 'giant-step--done');
                hdr.setAttribute('aria-expanded', 'false');
                hide(body);
            }
        });

        state.currentStep = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /** Allow clicking a completed step header to go back */
    function bindStepHeaderClicks() {
        $$('.giant-step__header').forEach(hdr => {
            hdr.addEventListener('click', () => {
                const step = parseInt(hdr.closest('.giant-step').dataset.step, 10);
                if (step < state.currentStep) {
                    openStep(step);
                }
            });

            hdr.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    hdr.click();
                }
            });
        });
    }

    /* ─────────────────────────────────────────────
       Step 1 – Personal info / Login / Guest
    ───────────────────────────────────────────── */

    function initStep1() {
        const loginForm       = document.getElementById('giant-login-form');
        const guestBtn        = document.getElementById('giant-guest-btn');
        const guestContainer  = document.getElementById('giant-guest-form-container');
        const guestForm       = document.getElementById('giant-guest-contact-form');
        const loginBtn        = loginForm ? loginForm.querySelector('.giant-login-btn') : null;

        // If already logged in, skip step 1
        if (state.isLoggedIn) {
            markStep1Done('logged-in');
            openStep(2);
            loadShippingMethods();
            return;
        }

        // Show guest form
        if (guestBtn) {
            guestBtn.addEventListener('click', () => {
                hide(loginForm.closest('.giant-login-grid'));
                show(guestContainer);
            });
        }

        // Login submit
        if (loginForm) {
            loginForm.addEventListener('submit', async e => {
                e.preventDefault();
                clearError('giant-login-error');
                setLoading(loginBtn, true);

                const email    = loginForm.querySelector('[name="username"]').value.trim();
                const password = loginForm.querySelector('[name="password"]').value;

                try {
                    await ajax(cfg.urls.login, {
                        method: 'POST',
                        body  : JSON.stringify({ username: email, password }),
                    });

                    state.isLoggedIn = true;
                    state.guestEmail = email;
                    markStep1Done(email);
                    openStep(2);
                    loadShippingMethods();
                } catch (err) {
                    showError('giant-login-error', __t('Correo o contraseña incorrectos. Por favor, inténtalo de nuevo.'));
                } finally {
                    setLoading(loginBtn, false);
                }
            });
        }

        // Guest contact form submit
        if (guestForm) {
            const submitBtn = guestForm.querySelector('button[type="submit"]');
            guestForm.addEventListener('submit', async e => {
                e.preventDefault();
                clearError('giant-guest-error');

                if (!validateGuestForm(guestForm)) return;

                setLoading(submitBtn, true);

                const firstname  = guestForm.querySelector('[name="firstname"]').value.trim();
                const lastname   = guestForm.querySelector('[name="lastname"]').value.trim();
                const email      = guestForm.querySelector('[name="email"]').value.trim();
                const phone      = guestForm.querySelector('[name="telephone"]')?.value.trim() || '';
                const street     = guestForm.querySelector('[name="street"]')?.value.trim() || '';
                const city       = guestForm.querySelector('[name="city"]')?.value.trim() || '';
                const department = guestForm.querySelector('[name="department"]')?.value || '';
                const postcode   = guestForm.querySelector('[name="postcode"]')?.value.trim() || '';

                state.guestData  = { firstname, lastname, email, phone, street, city, department, postcode };
                state.guestEmail = email;
                state.department = department;

                try {
                    await initGuestCart();
                    markStep1Done(email);
                    openStep(2);
                    loadShippingMethods();
                } catch (err) {
                    showError('giant-guest-error', __t('Error al procesar tus datos. Por favor inténtalo de nuevo.'));
                    console.error(err);
                } finally {
                    setLoading(submitBtn, false);
                }
            });
        }
    }

    function validateGuestForm(form) {
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('giant-input--error');
                valid = false;
            } else {
                field.classList.remove('giant-input--error');
            }
        });

        const emailField = form.querySelector('[name="email"]');
        if (emailField && emailField.value && !/.+@.+\..+/.test(emailField.value)) {
            emailField.classList.add('giant-input--error');
            valid = false;
        }

        if (!valid) {
            showError('giant-guest-error', __t('Por favor completa todos los campos requeridos.'));
        }

        return valid;
    }

    async function initGuestCart() {
        if (state.cartId) return state.cartId;

        if (cfg.maskedCartId) {
            state.cartId = cfg.maskedCartId;
            return state.cartId;
        }

        throw new Error('No active cart found. Please add items to your cart first.');
    }

    function markStep1Done(identifier) {
        const stepEl   = document.getElementById('giant-step-1');
        const titleEl  = stepEl?.querySelector('.giant-step__title');
        if (titleEl && identifier !== 'logged-in') {
            titleEl.textContent = __t('Información personal') + ' — ' + identifier;
        }
    }

    /* ─────────────────────────────────────────────
       Step 2 – Shipping methods
    ───────────────────────────────────────────── */

    async function loadShippingMethods() {
        const container = document.getElementById('giant-shipping-methods-container');
        const continueBtn = document.getElementById('giant-shipping-continue');
        if (!container) return;

        try {
            let methods = [];

            if (state.isLoggedIn) {
                methods = await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/shipping-methods');
            } else if (state.cartId) {
                methods = await ajax(
                    cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/shipping-methods'
                );
            } else {
                // Fallback: show a default "Click & Collect" option
                methods = [{
                    carrier_code: 'freeshipping',
                    method_code : 'freeshipping',
                    carrier_title: __t('Envío estándar'),
                    method_title : __t('Estándar'),
                    amount       : 0,
                    available    : true,
                }];
            }

            renderShippingMethods(container, methods, continueBtn);
        } catch (err) {
            console.error('Error loading shipping methods:', err);
            renderShippingMethods(container, [{
                carrier_code : 'freeshipping',
                method_code  : 'freeshipping',
                carrier_title: __t('Recoger en tienda'),
                method_title : __t('Click & Collect'),
                amount       : 0,
                available    : true,
            }], continueBtn);
        }
    }

    function renderShippingMethods(container, methods, continueBtn) {
        if (!methods.length) {
            container.innerHTML = '<p class="giant-no-options">' + __t('No hay opciones de envío disponibles.') + '</p>';
            return;
        }

        let html = '<div class="giant-shipping-methods" role="radiogroup" aria-label="' + __t('Opciones de envío') + '">';

        methods.forEach((method, i) => {
            if (!method.available) return;

            const code  = method.carrier_code + '_' + method.method_code;
            const price = method.amount === 0
                ? __t('Gratis')
                : formatCurrency(method.amount);

            const title = method.carrier_title === method.method_title
                ? method.carrier_title
                : method.carrier_title + ' - ' + method.method_title;

            html += `
                <label class="giant-method-option" for="shipping-${i}">
                    <input type="radio"
                           name="shipping_method"
                           id="shipping-${i}"
                           value="${escHtml(code)}"
                           class="giant-radio"
                           ${i === 0 ? 'checked' : ''}>
                    <span class="giant-method-option__label">
                        <span class="giant-method-option__title">${escHtml(title)}</span>
                    </span>
                    <span class="giant-method-option__price">${price}</span>
                </label>`;
        });

        html += '</div>';
        container.innerHTML = html;

        // Pre-select first method
        const firstRadio = container.querySelector('input[type="radio"]');
        if (firstRadio) {
            state.selectedMethod = firstRadio.value;
            continueBtn.disabled = false;
        }

        // Listen for selection changes
        container.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                state.selectedMethod = radio.value;
                continueBtn.disabled = false;

                // Update shipping price in summary
                const method = methods.find(m =>
                    m.carrier_code + '_' + m.method_code === radio.value
                );
                if (method) updateSummaryShipping(method);
            });
        });

        // Pre-update summary
        if (methods.length > 0 && methods[0].available) {
            updateSummaryShipping(methods[0]);
        }
    }

    function updateSummaryShipping(method) {
        const label = document.getElementById('giant-summary-shipping-label');
        const price = document.getElementById('giant-summary-shipping-price');
        if (label) {
            label.textContent = method.carrier_title === method.method_title
                ? method.carrier_title
                : method.carrier_title + ' - ' + method.method_title;
        }
        if (price) {
            price.textContent = method.amount === 0
                ? formatCurrency(0)
                : formatCurrency(method.amount);
        }
    }

    function initStep2() {
        const continueBtn = document.getElementById('giant-shipping-continue');
        if (!continueBtn) return;

        continueBtn.addEventListener('click', async () => {
            if (!state.selectedMethod) return;

            setLoading(continueBtn, true);

            try {
                await saveShippingMethod();

                if (state.hasBicycle) {
                    openStep(3);
                    prefillDepartmentAndLoad();
                } else {
                    openStep(MAX_STEP);
                    loadPaymentMethods();
                }
            } catch (err) {
                console.error('Error saving shipping method:', err);
                openStep(state.hasBicycle ? 3 : MAX_STEP);
                if (state.hasBicycle) prefillDepartmentAndLoad();
                else loadPaymentMethods();
            } finally {
                setLoading(continueBtn, false);
            }
        });
    }

    async function saveShippingMethod() {
        if (!state.selectedMethod) return;

        const [carrier, method] = state.selectedMethod.split('_');
        const address = buildAddress();

        const payload = {
            addressInformation: {
                shipping_address: address,
                billing_address : address,
                shipping_carrier_code: carrier,
                shipping_method_code : method,
            },
        };

        if (state.isLoggedIn) {
            await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/shipping-information', {
                method: 'POST',
                body  : JSON.stringify(payload),
            });
        } else if (state.cartId) {
            await ajax(
                cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/shipping-information',
                { method: 'POST', body: JSON.stringify(payload) }
            );
        }
    }

    function buildAddress() {
        if (state.guestData) {
            return {
                firstname : state.guestData.firstname,
                lastname  : state.guestData.lastname,
                email     : state.guestData.email,
                telephone : state.guestData.phone || '',
                street    : [state.guestData.street || ''],
                city      : state.guestData.city || '',
                region    : { region: state.guestData.department || '' },
                country_id: 'CO',
                postcode  : state.guestData.postcode || '000000',
            };
        }
        return {
            firstname : '',
            lastname  : '',
            email     : state.guestEmail || '',
            street    : [''],
            city      : '',
            country_id: 'CO',
            postcode  : '000000',
        };
    }

    /* ─────────────────────────────────────────────
       Step 3 – Distributor selection
    ───────────────────────────────────────────── */

    async function loadDistributors(department) {
        const container   = document.getElementById('giant-distributors-container');
        const continueBtn = document.getElementById('giant-distributor-continue');
        if (!container) return;

        container.innerHTML = '<div class="giant-loading"><span class="giant-loading__spinner" aria-hidden="true"></span> ' +
            __t('Cargando distribuidores…') + '</div>';

        try {
            let url = cfg.urls.distributors;
            if (department) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'department=' + encodeURIComponent(department);
            }
            const data = await ajax(url);
            renderDistributors(container, data.distributors || [], continueBtn, department);
        } catch (err) {
            console.error('Error loading distributors:', err);
            container.innerHTML = '<p class="giant-no-options">' +
                __t('No se pudieron cargar los distribuidores. Por favor recarga la página.') +
                '</p>';
        }
    }

    function renderDistributors(container, distributors, continueBtn, department) {
        if (!distributors.length) {
            container.innerHTML = '<p class="giant-no-options">' +
                __t('No hay distribuidores disponibles en tu área.') +
                '</p>';
            continueBtn.disabled = false;
            return;
        }

        let matchesLocal = department && distributors.some(function(d) { return d.department === department; });
        let infoHtml = '';
        if (department && !matchesLocal) {
            infoHtml = '<p class="giant-dept-info">' +
                __t('No encontramos distribuidores en tu departamento. Mostrando todos los distribuidores disponibles.') +
                '</p>';
        }

        let html = infoHtml + '<div class="giant-distributors" role="radiogroup" aria-label="' +
            __t('Selecciona un distribuidor') + '">';

        distributors.forEach((dist, i) => {
            const addressParts = [dist.address, dist.city, dist.department].filter(Boolean);
            const addressText  = addressParts.join(', ');
            const phoneText = dist.phone
                ? '<span class="giant-dist__phone">' + escHtml(dist.phone) + '</span>'
                : '';

            html += `
                <label class="giant-dist-option" for="dist-${dist.id}">
                    <input type="radio"
                           name="distributor_id"
                           id="dist-${dist.id}"
                           value="${dist.id}"
                           class="giant-radio">
                    <div class="giant-dist-option__info">
                        <span class="giant-dist-option__name">${escHtml(dist.name)}</span>
                        ${addressText ? '<span class="giant-dist-option__address">' + escHtml(addressText) + '</span>' : ''}
                        ${phoneText}
                    </div>
                </label>`;
        });

        html += '</div>';
        container.innerHTML = html;

        state.selectedDist = null;
        continueBtn.disabled = true;

        container.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                state.selectedDist = parseInt(radio.value, 10);
                continueBtn.disabled = false;
                clearError('giant-distributor-error');

                container.querySelectorAll('.giant-dist-option').forEach(opt => {
                    opt.classList.toggle('giant-dist-option--selected', opt.contains(radio));
                });
            });
        });
    }

    function prefillDepartmentAndLoad() {
        if (!state.department) return;
        const deptSelect = document.getElementById('giant-department-select');
        if (deptSelect && state.department) {
            deptSelect.value = state.department;
            loadDistributors(state.department);
        }
    }

    function initStep3() {
        if (!state.hasBicycle) return;

        const continueBtn  = document.getElementById('giant-distributor-continue');
        const deptSelect   = document.getElementById('giant-department-select');
        if (!continueBtn) return;

        if (deptSelect) {
            deptSelect.addEventListener('change', function () {
                var dept = deptSelect.value;
                state.selectedDist = null;
                continueBtn.disabled = true;
                if (dept) {
                    loadDistributors(dept);
                } else {
                    var container = document.getElementById('giant-distributors-container');
                    if (container) {
                        container.innerHTML = '<p class="giant-no-options giant-dept-hint">' +
                            __t('Selecciona tu departamento para ver los distribuidores disponibles.') + '</p>';
                    }
                }
            });
        }

        continueBtn.addEventListener('click', () => {
            if (!state.selectedDist) {
                showError(
                    'giant-distributor-error',
                    __t('Por favor selecciona un distribuidor para continuar.')
                );
                return;
            }

            openStep(MAX_STEP);
            loadPaymentMethods();
        });
    }

    /* ─────────────────────────────────────────────
       Step 4 – Payment
    ───────────────────────────────────────────── */

    async function loadPaymentMethods() {
        const container  = document.getElementById('giant-payment-methods-container');
        const placeOrder = document.getElementById('giant-place-order');
        if (!container) return;

        try {
            let methods = [];

            if (state.isLoggedIn) {
                methods = await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/payment-methods');
            } else if (state.cartId) {
                methods = await ajax(
                    cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/payment-methods'
                );
            } else {
                methods = [{ code: 'checkmo', title: __t('Cheque / Transferencia') }];
            }

            renderPaymentMethods(container, methods, placeOrder);
        } catch (err) {
            console.error('Error loading payment methods:', err);
            renderPaymentMethods(container, [
                { code: 'checkmo', title: __t('Cheque / Transferencia') },
            ], placeOrder);
        }
    }

    function renderPaymentMethods(container, methods, placeOrder) {
        if (!methods.length) {
            container.innerHTML = '<p class="giant-no-options">' +
                __t('No hay métodos de pago disponibles.') + '</p>';
            return;
        }

        let html = '<div class="giant-payment-methods" role="radiogroup" aria-label="' +
            __t('Forma de pago') + '">';

        methods.forEach((method, i) => {
            html += `
                <label class="giant-method-option" for="payment-${i}">
                    <input type="radio"
                           name="payment_method"
                           id="payment-${i}"
                           value="${escHtml(method.code)}"
                           class="giant-radio"
                           ${i === 0 ? 'checked' : ''}>
                    <span class="giant-method-option__label">
                        <span class="giant-method-option__title">${escHtml(method.title)}</span>
                    </span>
                </label>`;
        });

        html += '</div>';
        container.innerHTML = html;

        // Pre-select first
        const firstRadio = container.querySelector('input[type="radio"]');
        if (firstRadio) {
            state.selectedPayment = firstRadio.value;
            placeOrder.disabled   = false;
        }

        container.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                state.selectedPayment = radio.value;
                placeOrder.disabled   = false;
                clearError('giant-payment-error');
            });
        });
    }

    function initStep4() {
        const placeOrderBtn = document.getElementById('giant-place-order');
        if (!placeOrderBtn) return;

        placeOrderBtn.addEventListener('click', async () => {
            if (!state.selectedPayment) {
                showError('giant-payment-error', __t('Por favor selecciona un método de pago.'));
                return;
            }

            clearError('giant-payment-error');
            setLoading(placeOrderBtn, true);

            try {
                const orderId = await placeOrder();
                handleOrderSuccess(orderId);
            } catch (err) {
                const msg = tryParseErrorMessage(err.message);
                showError('giant-payment-error', msg || __t('Error al procesar el pedido. Por favor inténtalo de nuevo.'));
                console.error('Place order error:', err);
            } finally {
                setLoading(placeOrderBtn, false);
            }
        });
    }

    async function placeOrder() {
        const payload = {
            paymentMethod: {
                method          : state.selectedPayment,
                additional_data : {},
                extension_attributes: {
                    giant_distributor_id: state.selectedDist || 0,
                },
            },
        };

        if (state.guestData) {
            payload.email = state.guestData.email;
        }

        let orderId;

        if (state.isLoggedIn) {
            orderId = await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/payment-information', {
                method: 'POST',
                body  : JSON.stringify(payload),
            });
        } else {
            orderId = await ajax(
                cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/payment-information',
                { method: 'POST', body: JSON.stringify(payload) }
            );
        }

        return orderId;
    }

    function handleOrderSuccess(orderId) {
        // Redirect to success page
        window.location.href = cfg.urls.baseUrl + 'checkout/onepage/success';
    }

    /* ─────────────────────────────────────────────
       Order summary – coupon
    ───────────────────────────────────────────── */

    function initCoupon() {
        const toggle     = document.getElementById('giant-coupon-toggle');
        const form       = document.getElementById('giant-coupon-form');
        const applyBtn   = document.getElementById('giant-apply-coupon');
        const codeInput  = document.getElementById('giant-coupon-code');

        if (!toggle || !form) return;

        toggle.addEventListener('click', () => {
            const visible = form.style.display !== 'none';
            form.style.display = visible ? 'none' : '';
            toggle.setAttribute('aria-expanded', String(!visible));
        });

        if (applyBtn) {
            applyBtn.addEventListener('click', async () => {
                clearError('giant-coupon-error');
                const code = codeInput?.value.trim();
                if (!code) {
                    showError('giant-coupon-error', __t('Introduce un código de cupón.'));
                    return;
                }

                setLoading(applyBtn, true);
                try {
                    await applyCoupon(code);
                    document.getElementById('giant-coupon-success').textContent =
                        __t('Cupón aplicado correctamente.');
                    show(document.getElementById('giant-coupon-success'));
                    // Refresh totals
                    await refreshTotals();
                } catch (err) {
                    showError('giant-coupon-error', __t('Código de cupón no válido.'));
                } finally {
                    setLoading(applyBtn, false);
                }
            });
        }
    }

    async function applyCoupon(code) {
        if (state.isLoggedIn) {
            await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/coupons/' + encodeURIComponent(code), {
                method: 'PUT',
            });
        } else if (state.cartId) {
            await ajax(
                cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/coupons/' + encodeURIComponent(code),
                { method: 'PUT' }
            );
        }
    }

    async function refreshTotals() {
        try {
            let totals;
            if (state.isLoggedIn) {
                totals = await ajax(cfg.urls.baseUrl + 'rest/V1/carts/mine/totals');
            } else if (state.cartId) {
                totals = await ajax(cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/totals');
            } else {
                return;
            }

            const totalEl = document.getElementById('giant-summary-total');
            if (totalEl && totals.grand_total !== undefined) {
                totalEl.textContent = formatCurrency(totals.grand_total);
            }
        } catch (err) {
            console.error('Error refreshing totals:', err);
        }
    }

    /* ─────────────────────────────────────────────
       Recheck bicycle status after item removal
    ───────────────────────────────────────────── */

    function recheckBicycleStatus() {
        var bicycleItems = document.querySelectorAll('.giant-summary__item[data-is-bicycle="1"]');
        var hadBicycle   = state.hasBicycle;
        var hasBicycleNow = bicycleItems.length > 0;

        if (hadBicycle && !hasBicycleNow) {
            state.hasBicycle = false;
            state.selectedDist = null;

            var step3 = document.getElementById('giant-step-3');
            if (step3) {
                step3.style.display = 'none';
            }

            var paymentStep = document.getElementById('giant-step-payment');
            if (paymentStep) {
                paymentStep.dataset.step = '3';
                var numEl = paymentStep.querySelector('.giant-step__num');
                if (numEl) numEl.textContent = '3';
            }

            if (state.currentStep === 3) {
                openStep(3);
                loadPaymentMethods();
            }
        }
    }

    /* ─────────────────────────────────────────────
       Order summary – remove item
    ───────────────────────────────────────────── */

    function initRemoveItems() {
        document.querySelectorAll('.giant-summary__item-remove').forEach(btn => {
            btn.addEventListener('click', async () => {
                const itemId = btn.dataset.itemId;
                if (!itemId) return;

                if (!confirm(__t('¿Eliminar este artículo del carrito?'))) return;

                try {
                    let deleteUrl;
                    if (state.isLoggedIn) {
                        deleteUrl = cfg.urls.baseUrl + 'rest/V1/carts/mine/items/' + itemId;
                    } else if (state.cartId) {
                        deleteUrl = cfg.urls.baseUrl + 'rest/V1/guest-carts/' + state.cartId + '/items/' + itemId;
                    } else {
                        deleteUrl = cfg.urls.baseUrl + 'rest/V1/guest-carts/' + (cfg.maskedCartId || '') + '/items/' + itemId;
                    }

                    await ajax(deleteUrl, { method: 'DELETE' });
                    btn.closest('.giant-summary__item')?.remove();

                    var remaining = document.querySelectorAll('.giant-summary__item');
                    if (remaining.length === 0) {
                        window.location.href = cfg.urls.baseUrl + 'checkout/cart/';
                        return;
                    }

                    recheckBicycleStatus();
                    await refreshTotals();
                } catch (err) {
                    console.error('Error removing item:', err);
                }
            });
        });
    }

    /* ─────────────────────────────────────────────
       Helpers
    ───────────────────────────────────────────── */

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatCurrency(amount) {
        if (typeof Intl !== 'undefined') {
            try {
                return new Intl.NumberFormat(document.documentElement.lang || 'es-ES', {
                    style   : 'currency',
                    currency: cfg.currencyCode || 'COP',
                }).format(amount);
            } catch (e) { /* fallback */ }
        }
        return parseFloat(amount).toFixed(2);
    }

    function getCookieValue(name) {
        const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function tryParseErrorMessage(raw) {
        try {
            const obj = JSON.parse(raw);
            return obj.message || obj.error || raw;
        } catch (e) {
            return raw;
        }
    }

    /** Simple translation stub — replace with actual i18n if needed */
    function __t(str) {
        return str;
    }

    /* ─────────────────────────────────────────────
       Bootstrap
    ───────────────────────────────────────────── */

    function init() {
        bindStepHeaderClicks();
        initStep1();
        initStep2();
        initStep3();
        initStep4();
        initCoupon();
        initRemoveItems();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
