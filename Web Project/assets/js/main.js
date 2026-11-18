/* ==================================================================
   Threadline — client-side behaviour
   - cart badge & live cart totals
   - AJAX add-to-bag
   - form validation (register, login, checkout, contact, admin)
   - UI helpers (toasts, back to top, reveal on scroll)
   ================================================================== */
(function () {
    'use strict';

    const money = (value) =>
        '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    /* ------------------------------------------------------------------
       Toasts
       ------------------------------------------------------------------ */
    function showToast(message, variant) {
        const container = document.getElementById('toastContainer');
        if (!container) {
            alert(message);
            return;
        }
        const el = document.createElement('div');
        el.className = 'toast toast-threadline align-items-center border-0';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.setAttribute('aria-atomic', 'true');
        el.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body">' +
            '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5"/></svg>' +
            '<span></span>' +
            '</div>' +
            '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';
        el.querySelector('span').textContent = message;
        if (variant === 'danger') {
            el.style.background = '#7f2f1d';
        }
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3600 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }
    window.showToast = showToast;

    /* ------------------------------------------------------------------
       Cart badge
       ------------------------------------------------------------------ */
    function updateCartBadge(count) {
        const badge = document.getElementById('cartCount');
        if (!badge) return;
        badge.textContent = count;
        badge.classList.remove('is-bumping');
        void badge.offsetWidth; // restart animation
        badge.classList.add('is-bumping');
    }
    window.updateCartBadge = updateCartBadge;

    /* ------------------------------------------------------------------
       Live cart totals (used on cart.php)
       ------------------------------------------------------------------ */
    function calculateCartTotals() {
        const rows = document.querySelectorAll('.cart-row[data-price]');
        if (!rows.length) return null;

        let subtotal = 0;
        rows.forEach((row) => {
            const price = parseFloat(row.dataset.price) || 0;
            const input = row.querySelector('.cart-qty');
            const qty = Math.max(1, parseInt(input ? input.value : '1', 10) || 1);
            const lineTotal = price * qty;
            subtotal += lineTotal;

            const out = row.querySelector('.line-total');
            if (out) out.textContent = money(lineTotal);
        });

        const threshold = parseFloat(document.body.dataset.freeShipping || '100');
        const flatRate = parseFloat(document.body.dataset.shippingRate || '7.95');
        const shipping = subtotal <= 0 || subtotal >= threshold ? 0 : flatRate;
        const total = subtotal + shipping;

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = money(value);
        };
        set('cartSubtotal', subtotal);
        set('cartShipping', shipping);
        set('cartTotal', total);

        const bar = document.getElementById('freeProgress');
        if (bar) {
            const pct = Math.min(100, Math.round((subtotal / threshold) * 100));
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', String(pct));
        }
        const note = document.getElementById('freeMessage');
        if (note) {
            const remaining = Math.max(0, threshold - subtotal);
            note.textContent = remaining > 0
                ? 'You are ' + money(remaining) + ' away from free delivery.'
                : 'Free delivery unlocked — nice one.';
        }

        return { subtotal: subtotal, shipping: shipping, total: total };
    }
    window.calculateCartTotals = calculateCartTotals;

    /* ------------------------------------------------------------------
       Quantity steppers
       ------------------------------------------------------------------ */
    function initSteppers() {
        document.querySelectorAll('.qty-stepper').forEach((stepper) => {
            const input = stepper.querySelector('input');
            if (!input) return;
            stepper.querySelectorAll('[data-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    const min = parseInt(input.min || '1', 10);
                    const max = parseInt(input.max || '20', 10);
                    const next = (parseInt(input.value, 10) || min) + parseInt(button.dataset.step, 10);
                    input.value = Math.min(max, Math.max(min, next));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });
    }

    /* ------------------------------------------------------------------
       AJAX add to bag
       ------------------------------------------------------------------ */
    function initAddToCart() {
        document.querySelectorAll('form.js-add-to-cart').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                if (!window.fetch) return; // progressive enhancement: normal POST
                event.preventDefault();

                const button = form.querySelector('[type="submit"]');
                const original = button ? button.innerHTML : '';
                if (button) {
                    button.disabled = true;
                    button.innerHTML = 'Adding…';
                }

                try {
                    const data = new FormData(form);
                    data.append('ajax', '1');
                    const response = await fetch(form.getAttribute('action') || 'cart.php', {
                        method: 'POST',
                        body: data,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!result.ok) {
                        showToast(result.message || 'Sorry, that item could not be added.', 'danger');
                    } else {
                        updateCartBadge(result.count);
                        showToast(result.message || 'Added to your bag.');
                    }
                } catch (error) {
                    form.submit(); // fall back to a standard POST
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                }
            });
        });
    }

    /* ------------------------------------------------------------------
       Form validation helpers
       ------------------------------------------------------------------ */
    function setError(input, message) {
        clearError(input);
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        input.insertAdjacentElement('afterend', feedback);
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
        const next = input.nextElementSibling;
        if (next && next.classList.contains('invalid-feedback')) next.remove();
    }

    const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim());
    const isPhone = (value) => /^[0-9+\-\s()]{7,20}$/.test(value.trim());

    function requireValue(form, name, label) {
        const field = form.elements[name];
        if (!field) return true;
        if (!String(field.value).trim()) {
            setError(field, label + ' is required.');
            return false;
        }
        clearError(field);
        return true;
    }

    function validateEmailField(form, name) {
        const field = form.elements[name];
        if (!field) return true;
        if (!String(field.value).trim()) {
            setError(field, 'Email address is required.');
            return false;
        }
        if (!isEmail(field.value)) {
            setError(field, 'Enter a valid email address, e.g. you@example.com.');
            return false;
        }
        clearError(field);
        return true;
    }

    /* ------------------------------------------------------------------
       Login form
       ------------------------------------------------------------------ */
    function initLoginValidation() {
        const form = document.getElementById('loginForm');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            let ok = validateEmailField(form, 'email');
            ok = requireValue(form, 'password', 'Password') && ok;
            if (!ok) event.preventDefault();
        });
    }

    /* ------------------------------------------------------------------
       Register form — name, email, phone, password, confirmation
       ------------------------------------------------------------------ */
    function validateRegisterForm(form) {
        let ok = true;

        const name = form.elements.full_name;
        if (!name.value.trim()) {
            setError(name, 'Please tell us your full name.');
            ok = false;
        } else if (name.value.trim().length < 3) {
            setError(name, 'Your name must be at least 3 characters.');
            ok = false;
        } else {
            clearError(name);
        }

        ok = validateEmailField(form, 'email') && ok;

        const phone = form.elements.phone;
        if (phone.value.trim() && !isPhone(phone.value)) {
            setError(phone, 'Enter a valid phone number (7–20 digits).');
            ok = false;
        } else {
            clearError(phone);
        }

        const password = form.elements.password;
        if (!password.value) {
            setError(password, 'Choose a password.');
            ok = false;
        } else if (password.value.length < 8) {
            setError(password, 'Use at least 8 characters.');
            ok = false;
        } else {
            clearError(password);
        }

        const confirm = form.elements.confirm_password;
        if (!confirm.value) {
            setError(confirm, 'Please confirm your password.');
            ok = false;
        } else if (confirm.value !== password.value) {
            setError(confirm, 'Passwords do not match.');
            ok = false;
        } else {
            clearError(confirm);
        }

        const terms = form.elements.terms;
        if (terms && !terms.checked) {
            setError(terms, 'Please accept the terms to create an account.');
            ok = false;
        } else if (terms) {
            clearError(terms);
        }

        return ok;
    }

    function initRegisterValidation() {
        const form = document.getElementById('registerForm');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            if (!validateRegisterForm(form)) event.preventDefault();
        });
    }

    /* ------------------------------------------------------------------
       Checkout — contact, address, payment fields
       ------------------------------------------------------------------ */
    function initCheckoutValidation() {
        const form = document.getElementById('checkoutForm');
        if (!form) return;

        form.addEventListener('submit', (event) => {
            let ok = true;

            ['full_name', 'address', 'city', 'postcode'].forEach((name) => {
                ok = requireValue(form, name, name.replace('_', ' ')) && ok;
            });
            ok = validateEmailField(form, 'email') && ok;

            const phone = form.elements.phone;
            if (!phone.value.trim() || !isPhone(phone.value)) {
                setError(phone, 'Enter a valid phone number.');
                ok = false;
            } else {
                clearError(phone);
            }

            // Payment fields (demo only — card data is never stored)
            const card = form.elements.card_number;
            const digits = card.value.replace(/\s+/g, '');
            if (!/^\d{13,19}$/.test(digits)) {
                setError(card, 'Enter a valid card number (13–19 digits).');
                ok = false;
            } else {
                clearError(card);
            }

            const expiry = form.elements.card_expiry;
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry.value.trim())) {
                setError(expiry, 'Use MM/YY, e.g. 08/29.');
                ok = false;
            } else {
                clearError(expiry);
            }

            const cvc = form.elements.card_cvc;
            if (!/^\d{3,4}$/.test(cvc.value.trim())) {
                setError(cvc, 'CVC must be 3 or 4 digits.');
                ok = false;
            } else {
                clearError(cvc);
            }

            if (!ok) {
                event.preventDefault();
                const first = form.querySelector('.is-invalid');
                if (first) {
                    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    first.focus({ preventScroll: true });
                }
            }
        });
    }

    /* ------------------------------------------------------------------
       Contact form
       ------------------------------------------------------------------ */
    function initContactValidation() {
        const form = document.getElementById('contactForm');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            let ok = requireValue(form, 'name', 'Name');
            ok = validateEmailField(form, 'email') && ok;
            ok = requireValue(form, 'subject', 'Subject') && ok;

            const message = form.elements.message;
            if (!message.value.trim() || message.value.trim().length < 10) {
                setError(message, 'Please write at least 10 characters.');
                ok = false;
            } else {
                clearError(message);
            }

            if (!ok) event.preventDefault();
        });
    }

    /* ------------------------------------------------------------------
       Admin product form
       ------------------------------------------------------------------ */
    function initAdminProductValidation() {
        const form = document.getElementById('productForm');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            let ok = requireValue(form, 'name', 'Product name');
            ok = requireValue(form, 'price', 'Price') && ok;
            ok = requireValue(form, 'stock', 'Stock') && ok;

            const price = parseFloat(form.elements.price.value);
            if (isNaN(price) || price <= 0) {
                setError(form.elements.price, 'Price must be greater than 0.');
                ok = false;
            }
            const stock = parseInt(form.elements.stock.value, 10);
            if (isNaN(stock) || stock < 0) {
                setError(form.elements.stock, 'Stock cannot be negative.');
                ok = false;
            }
            if (!ok) event.preventDefault();
        });
    }

    /* ------------------------------------------------------------------
       Newsletter (demo interaction)
       ------------------------------------------------------------------ */
    function initNewsletter() {
        const form = document.getElementById('newsletterForm');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const email = form.querySelector('input[type="email"]');
            const feedback = document.getElementById('newsletterFeedback');
            feedback.classList.remove('d-none', 'text-danger', 'text-success');

            if (!isEmail(email.value)) {
                feedback.textContent = 'Please enter a valid email address.';
                feedback.classList.add('text-danger');
                email.focus();
                return;
            }
            feedback.textContent = 'Welcome to the list — check your inbox for a hello.';
            feedback.classList.add('text-success');
            form.reset();
        });
    }

    /* ------------------------------------------------------------------
       Shop filter auto-submit (onchange)
       ------------------------------------------------------------------ */
    function initFilters() {
        document.querySelectorAll('[data-autosubmit]').forEach((field) => {
            field.addEventListener('change', () => field.form.submit());
        });

        // Price range label updates while dragging
        const range = document.getElementById('maxPrice');
        const label = document.getElementById('maxPriceLabel');
        if (range && label) {
            const sync = () => { label.textContent = money(range.value); };
            range.addEventListener('input', sync);
            sync();
        }
    }

    /* ------------------------------------------------------------------
       Welcome toast after login
       ------------------------------------------------------------------ */
    function initWelcome() {
        const el = document.getElementById('welcomeToast');
        if (!el) return;
        showToast(el.dataset.message || 'Welcome back.');
    }

    /* ------------------------------------------------------------------
       Admin — image preview
       ------------------------------------------------------------------ */
    function initImagePreview() {
        const select = document.querySelector('[data-image-preview]');
        const preview = document.getElementById('imagePreview');
        if (!select || !preview) return;
        select.addEventListener('change', () => {
            preview.src = '../assets/img/products/' + encodeURIComponent(select.value);
        });
    }

    /* ------------------------------------------------------------------
       Misc UI
       ------------------------------------------------------------------ */
    function initMisc() {
        const top = document.getElementById('backToTop');
        if (top) {
            const toggle = () => top.classList.toggle('is-visible', window.scrollY > 500);
            window.addEventListener('scroll', toggle, { passive: true });
            top.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
            toggle();
        }

        const revealItems = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window && revealItems.length) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            revealItems.forEach((item) => observer.observe(item));
        } else {
            revealItems.forEach((item) => item.classList.add('is-visible'));
        }

        // Hover feedback on quick-view buttons (onmouseover / onmouseout)
        document.querySelectorAll('.product-quick .btn').forEach((button) => {
            button.addEventListener('mouseover', () => button.classList.add('is-hover'));
            button.addEventListener('mouseout', () => button.classList.remove('is-hover'));
        });

        // Live cart recalculation on quantity change (onchange)
        document.querySelectorAll('.cart-qty').forEach((input) => {
            input.addEventListener('change', calculateCartTotals);
            input.addEventListener('input', calculateCartTotals);
        });

        // Card number formatting
        const card = document.querySelector('input[name="card_number"]');
        if (card) {
            card.addEventListener('input', () => {
                const digits = card.value.replace(/\D/g, '').slice(0, 19);
                card.value = digits.replace(/(.{4})/g, '$1 ').trim();
            });
        }

        // Expiry formatting MM/YY
        const expiry = document.querySelector('input[name="card_expiry"]');
        if (expiry) {
            expiry.addEventListener('input', () => {
                let value = expiry.value.replace(/\D/g, '').slice(0, 4);
                if (value.length > 2) value = value.slice(0, 2) + '/' + value.slice(2);
                expiry.value = value;
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSteppers();
        initAddToCart();
        initLoginValidation();
        initRegisterValidation();
        initCheckoutValidation();
        initContactValidation();
        initAdminProductValidation();
        initNewsletter();
        initFilters();
        initImagePreview();
        initWelcome();
        initMisc();
        calculateCartTotals();
    });
})();
