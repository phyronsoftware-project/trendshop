import TomSelect from 'tom-select';
import MicroModal from 'micromodal';
import { initializeChat } from './chat';
import chinese from './locales/zh.json';
import english from './locales/en.json';
import khmer from './locales/km.json';

const translations = { km: khmer, en: english, zh: chinese };
const localeNames = { km: 'ខ្មែរ', en: 'English', zh: '中文' };

// Fall back to Khmer when an old or unsupported locale is stored.
const resolveLocale = (locale) => (translations[locale] ? locale : 'km');

// Resolve nested translation keys from the selected JSON dictionary.
const translate = (locale, key) => key.split('.').reduce((value, segment) => value?.[segment], translations[resolveLocale(locale)]) ?? key;

// Apply translated text and accessibility labels without reloading the page.
const applyLocale = (locale) => {
    const activeLocale = resolveLocale(locale);

    document.documentElement.lang = activeLocale;
    document.body.classList.toggle('locale-km', activeLocale === 'km');
    document.querySelectorAll('[data-i18n]').forEach((element) => {
        element.textContent = translate(activeLocale, element.dataset.i18n);
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        element.placeholder = translate(activeLocale, element.dataset.i18nPlaceholder);
    });
    document.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
        element.setAttribute('aria-label', translate(activeLocale, element.dataset.i18nAriaLabel));
    });
    document.querySelectorAll('[data-current-locale-flag]').forEach((element) => {
        element.src = element.dataset[`${activeLocale}Src`];
        element.alt = localeNames[activeLocale];
    });
    document.querySelectorAll('[data-current-locale-name]').forEach((element) => {
        element.textContent = localeNames[activeLocale];
    });

    localStorage.setItem('trendshop-locale', activeLocale);
    document.dispatchEvent(new CustomEvent('storefront:locale-changed', { detail: activeLocale }));
};

// Restore the saved appearance before binding theme controls.
const savedTheme = localStorage.getItem('trendshop-theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
document.documentElement.classList.toggle('dark', savedTheme ? savedTheme === 'dark' : prefersDark);

document.addEventListener('DOMContentLoaded', () => {
    initializeChat();
    const storefrontHeader = document.querySelector('[data-storefront-header]');
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const localeDropdown = document.querySelector('[data-locale-dropdown]');
    const localeMenuToggle = document.querySelector('[data-locale-menu-toggle]');
    const localeMenu = document.querySelector('[data-locale-menu]');
    const localeChevron = document.querySelector('[data-locale-chevron]');
    const accountDropdown = document.querySelector('[data-account-dropdown]');
    const accountMenuToggle = document.querySelector('[data-account-menu-toggle]');
    const accountMenu = document.querySelector('[data-account-menu]');
    const accountChevron = document.querySelector('[data-account-chevron]');
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const categorySelect = document.querySelector('#category-filter');
    const productSearch = document.querySelector('#product-search');
    const productFilterForm = document.querySelector('[data-product-filter-form]');
    const productCards = [...document.querySelectorAll('[data-product-card]')];
    const emptyState = document.querySelector('[data-empty-state]');
    const productGrid = document.querySelector('[data-product-grid]');
    const pagination = document.querySelector('[data-pagination]');
    const pageNumbers = document.querySelector('[data-page-numbers]');
    const previousPage = document.querySelector('[data-page-previous]');
    const nextPage = document.querySelector('[data-page-next]');
    const bestSellerSlider = document.querySelector('[data-best-seller-slider]');
    const bestSellerSlides = [...document.querySelectorAll('[data-best-seller-slide]')];
    const bestSellerDots = [...document.querySelectorAll('[data-best-seller-dot]')];
    const bestSellerPrevious = document.querySelector('[data-best-seller-previous]');
    const bestSellerNext = document.querySelector('[data-best-seller-next]');
    const productMainImage = document.querySelector('[data-product-main-image]');
    const productImageOpen = document.querySelector('[data-product-image-open]');
    const productModalImage = document.querySelector('[data-product-modal-image]');
    const productImageModal = document.querySelector('#product-image-modal');
    const productModalPrevious = document.querySelector('[data-product-modal-previous]');
    const productModalNext = document.querySelector('[data-product-modal-next]');
    const productThumbnails = [...document.querySelectorAll('[data-product-thumbnail]')];
    const productQuantity = document.querySelector('[data-product-quantity]');
    const checkoutQuantity = document.querySelector('[data-checkout-quantity]');
    const quantityDecrease = document.querySelector('[data-quantity-decrease]');
    const quantityIncrease = document.querySelector('[data-quantity-increase]');
    const galleryModal = document.querySelector('[data-gallery-modal]');
    const galleryMain = document.querySelector('[data-gallery-main]');
    const galleryModalImage = document.querySelector('[data-gallery-modal-image]');
    const galleryThumbnails = [...document.querySelectorAll('[data-gallery-thumbnail]')];
    const deliveryAddress = document.querySelector('[data-delivery-address]');
    const deliveryAddressPreview = document.querySelector('[data-delivery-address-preview]');
    const alert = document.querySelector('[data-alert]');
    const alertIcon = document.querySelector('[data-alert-icon]');
    const alertMessage = document.querySelector('[data-alert-text]');
    const wishlistAjaxForms = [...document.querySelectorAll('[data-wishlist-ajax]')];
    const cartAjaxForms = [...document.querySelectorAll('[data-cart-ajax]')];
    const validationForms = [...document.querySelectorAll('[data-validate-form]')];
    const scrollTopButton = document.querySelector('[data-scroll-top]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let selectedCategories = [];
    let currentPage = 1;
    let activeAlertKey = null;
    let alertTimeout = null;
    let activeBestSellerSlide = 0;
    let bestSellerInterval = null;
    let activeModalImageIndex = 0;
    // Show three complete five-card rows on desktop before changing page.
    const productsPerPage = 15;
    const staticLoadingDuration = 3000;
    const bestSellerSlideDuration = 3000;
    let previousScrollPosition = window.scrollY;
    let scrollDirection = null;
    let scrollDirectionDistance = 0;

    // Animate the reusable alert and automatically close it after three seconds.
    const hideAlert = () => {
        alert?.classList.add('pointer-events-none', 'translate-y-[-0.75rem]', 'opacity-0');
        alert?.classList.remove('pointer-events-auto', 'translate-y-0', 'opacity-100');
    };

    const showAlert = (status, message, shouldTranslate = true) => {
        if (!alert || !alertMessage || !alertIcon) {
            return;
        }

        const icons = { success: '✓', error: '!', warning: '!', info: 'i' };
        const activeStatus = icons[status] ? status : 'info';
        const activeLocale = resolveLocale(localStorage.getItem('trendshop-locale'));

        activeAlertKey = shouldTranslate ? message : null;
        alert.dataset.status = activeStatus;
        alertIcon.textContent = icons[activeStatus];
        alertMessage.textContent = shouldTranslate ? translate(activeLocale, message) : message;
        alert.classList.remove('pointer-events-none', 'translate-y-[-0.75rem]', 'opacity-0');
        alert.classList.add('pointer-events-auto', 'translate-y-0', 'opacity-100');

        window.clearTimeout(alertTimeout);
        alertTimeout = window.setTimeout(hideAlert, 3000);
    };

    // Replace native browser validation popups with compact inline field feedback.
    const validationErrorFor = (field) => [...field.form.querySelectorAll('[data-validation-error]')]
        .find((error) => error.dataset.validationError === field.name);

    const setFieldValidation = (field, message = '', isClientMessage = false) => {
        const error = validationErrorFor(field);
        const isInvalid = message !== '';
        field.setAttribute('aria-invalid', String(isInvalid));
        field.classList.toggle('!border-red-500', isInvalid);
        field.classList.toggle('!ring-1', isInvalid);
        field.classList.toggle('!ring-red-500/20', isInvalid);
        if (!error) return;

        error.textContent = message;
        error.classList.toggle('hidden', !isInvalid);
        error.dataset.clientValidationError = String(isClientMessage && isInvalid);
    };

    const fieldValidationMessage = (field) => {
        const value = field.value.trim();
        const activeLocale = resolveLocale(localStorage.getItem('trendshop-locale'));
        for (const rule of (field.dataset.validation ?? '').split('|').filter(Boolean)) {
            const [name, parameter] = rule.split(':');
            if (name === 'required' && value === '') return translate(activeLocale, 'validation.required');
            if (name === 'accepted' && !field.checked) return translate(activeLocale, 'validation.accepted');
            if (name === 'email' && value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return translate(activeLocale, 'validation.email');
            if (name === 'number' && value !== '' && !Number.isFinite(Number(value))) return translate(activeLocale, 'validation.number');
            if (name === 'min' && value !== '' && value.length < Number(parameter)) return translate(activeLocale, 'validation.min').replace(':min', parameter);
            if (name === 'max' && value.length > Number(parameter)) return translate(activeLocale, 'validation.max').replace(':max', parameter);
            if (name === 'min-value' && value !== '' && Number(value) < Number(parameter)) return translate(activeLocale, 'validation.minValue').replace(':min', parameter);
            if (name === 'max-value' && value !== '' && Number(value) > Number(parameter)) return translate(activeLocale, 'validation.maxValue').replace(':max', parameter);
            if (name === 'same' && value !== (field.form.elements.namedItem(parameter)?.value ?? '')) return translate(activeLocale, 'validation.confirmed');
            if (name === 'image' && field.files?.[0] && !field.files[0].type.startsWith('image/')) return translate(activeLocale, 'validation.image');
            if (name === 'max-file' && field.files?.[0] && field.files[0].size > Number(parameter) * 1024) return translate(activeLocale, 'validation.maxFile').replace(':max', parameter);
        }

        return '';
    };

    validationForms.forEach((form) => {
        const fields = [...form.querySelectorAll('[data-validation]')];
        fields.forEach((field) => {
            const serverError = validationErrorFor(field);
            if (serverError && !serverError.classList.contains('hidden') && serverError.textContent.trim() !== '') {
                setFieldValidation(field, serverError.textContent.trim());
            }

            const refreshField = () => setFieldValidation(field, fieldValidationMessage(field), true);
            field.addEventListener(field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input', refreshField);
            field.addEventListener('blur', refreshField);
            if (field.hasAttribute('data-submit-on-change')) {
                field.addEventListener('change', () => {
                    if (fieldValidationMessage(field) === '') field.form.requestSubmit();
                });
            }
        });

        form.addEventListener('submit', (event) => {
            const invalidFields = fields.filter((field) => {
                const message = fieldValidationMessage(field);
                setFieldValidation(field, message, true);

                return message !== '';
            });
            if (invalidFields.length === 0) return;

            event.preventDefault();
            invalidFields[0].focus();
        });
    });

    // Keep visible client validation messages synchronized with the selected storefront language.
    document.addEventListener('storefront:locale-changed', () => {
        validationForms.forEach((form) => {
            form.querySelectorAll('[data-validation]').forEach((field) => {
                const error = validationErrorFor(field);
                if (error?.dataset.clientValidationError === 'true') {
                    setFieldValidation(field, fieldValidationMessage(field), true);
                }
            });
        });
    });

    // Save or remove a favorite without reloading the product catalogue.
    wishlistAjaxForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('[data-wishlist-toggle]');
            if (!button || button.disabled) return;

            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: new FormData(form),
                });

                if (response.status === 401 || response.redirected) {
                    window.location.assign(response.redirected ? response.url : form.dataset.loginUrl);
                    return;
                }

                const payload = await response.json();
                if (!response.ok) {
                    showAlert('error', payload.message ?? 'Unable to update wishlist.', false);
                    return;
                }

                button.setAttribute('aria-pressed', String(payload.wishlisted));
                button.setAttribute('aria-label', payload.wishlisted ? 'Remove from wishlist' : 'Add to wishlist');
                const wishlistCount = document.querySelector('[data-wishlist-count]');
                if (wishlistCount) {
                    wishlistCount.textContent = String(payload.wishlist_count);
                    wishlistCount.classList.toggle('hidden', payload.wishlist_count === 0);
                }
                if (form.hasAttribute('data-wishlist-remove-card') && !payload.wishlisted) {
                    form.closest('[data-product-card]')?.remove();
                    const wishlistGrid = document.querySelector('[data-wishlist-grid]');
                    if (!wishlistGrid?.querySelector('[data-product-card]')) {
                        wishlistGrid?.querySelector('[data-wishlist-empty]')?.classList.remove('hidden');
                    }
                }
                showAlert('success', payload.message, false);
            } catch {
                showAlert('error', 'Unable to update wishlist. Please try again.', false);
            } finally {
                button.disabled = false;
            }
        });
    });

    // Add or remove a product card from the cart and update its filled state without reloading.
    cartAjaxForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('[data-cart-toggle]');
            if (!button || button.disabled) return;

            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: new FormData(form),
                });

                if (response.status === 401 || response.redirected) {
                    window.location.assign(response.redirected ? response.url : form.dataset.loginUrl);
                    return;
                }

                const payload = await response.json();
                if (!response.ok) {
                    showAlert('error', payload.message ?? 'Unable to update cart.', false);
                    return;
                }

                const translationKey = payload.in_cart ? 'actions.removeCart' : 'actions.addCart';
                const activeLocale = resolveLocale(localStorage.getItem('trendshop-locale'));
                button.setAttribute('aria-pressed', String(payload.in_cart));
                button.dataset.i18n = translationKey;
                button.textContent = translate(activeLocale, translationKey);
                const cartCount = document.querySelector('[data-cart-count]');
                if (cartCount) {
                    cartCount.textContent = String(payload.cart_count);
                    cartCount.classList.toggle('hidden', payload.cart_count === 0);
                }
                showAlert('success', payload.message, false);
            } catch {
                showAlert('error', 'Unable to update cart. Please try again.', false);
            } finally {
                button.disabled = false;
            }
        });
    });

    // Simulate backend-bound actions for three seconds during the static UI phase.
    const startStaticLoading = (action) => {
        if (!action || action.getAttribute('aria-busy') === 'true') {
            return;
        }

        const originalContent = action.innerHTML;
        const showLoadingLabel = action.dataset.loadingLabel !== 'false';
        const activeLocale = resolveLocale(localStorage.getItem('trendshop-locale'));
        const loadingLabel = showLoadingLabel ? `<span>${translate(activeLocale, 'actions.loading')}</span>` : '';

        action.disabled = true;
        action.setAttribute('aria-busy', 'true');
        action.innerHTML = `<span class="inline-block size-4 shrink-0 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>${loadingLabel}`;

        window.setTimeout(() => {
            action.innerHTML = originalContent;
            action.disabled = false;
            action.removeAttribute('aria-busy');
            showAlert(action.dataset.alertStatus, action.dataset.alertMessage);
        }, staticLoadingDuration);
    };

    // Auto-slide through the best seller gallery with optional manual controls.
    const showBestSellerSlide = (slideIndex) => {
        if (bestSellerSlides.length === 0) {
            return;
        }

        activeBestSellerSlide = (slideIndex + bestSellerSlides.length) % bestSellerSlides.length;
        bestSellerSlides.forEach((slide, index) => {
            const isActive = index === activeBestSellerSlide;
            slide.classList.toggle('translate-x-0', isActive);
            slide.classList.toggle('opacity-100', isActive);
            slide.classList.toggle('-translate-x-full', index < activeBestSellerSlide);
            slide.classList.toggle('translate-x-full', index > activeBestSellerSlide);
            slide.classList.toggle('opacity-0', !isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });

        bestSellerDots.forEach((dot, index) => {
            const isActive = index === activeBestSellerSlide;
            dot.classList.toggle('w-6', isActive);
            dot.classList.toggle('w-2', !isActive);
            dot.classList.toggle('opacity-100', isActive);
            dot.classList.toggle('opacity-60', !isActive);
            dot.setAttribute('aria-current', String(isActive));
        });
    };

    const stopBestSellerSlider = () => window.clearInterval(bestSellerInterval);
    const startBestSellerSlider = () => {
        stopBestSellerSlider();

        if (bestSellerSlides.length > 1) {
            bestSellerInterval = window.setInterval(() => showBestSellerSlide(activeBestSellerSlide + 1), bestSellerSlideDuration);
        }
    };

    if (bestSellerSlider) {
        startBestSellerSlider();
        bestSellerSlider.addEventListener('mouseenter', stopBestSellerSlider);
        bestSellerSlider.addEventListener('mouseleave', startBestSellerSlider);
        bestSellerSlider.addEventListener('focusin', stopBestSellerSlider);
        bestSellerSlider.addEventListener('focusout', startBestSellerSlider);
    }

    bestSellerPrevious?.addEventListener('click', () => {
        showBestSellerSlide(activeBestSellerSlide - 1);
        startBestSellerSlider();
    });

    bestSellerNext?.addEventListener('click', () => {
        showBestSellerSlide(activeBestSellerSlide + 1);
        startBestSellerSlider();
    });

    bestSellerDots.forEach((dot) => {
        dot.addEventListener('click', () => {
            showBestSellerSlide(Number(dot.dataset.slideIndex));
            startBestSellerSlider();
        });
    });

    // Render small client-side pagination for the filtered static catalogue.
    const renderPagination = (totalPages) => {
        if (!pagination || !pageNumbers || !previousPage || !nextPage) {
            return;
        }

        pageNumbers.replaceChildren();

        const visiblePages = totalPages <= 7
            ? Array.from({ length: totalPages }, (_, index) => index + 1)
            : [...new Set([
                1,
                ...(currentPage <= 4 ? [2, 3, 4, 5] : []),
                currentPage - 1,
                currentPage,
                currentPage + 1,
                ...(currentPage >= totalPages - 3 ? [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1] : []),
                totalPages,
            ])].filter((page) => page >= 1 && page <= totalPages).sort((first, second) => first - second);

        visiblePages.forEach((page, index) => {
            if (index > 0 && page - visiblePages[index - 1] > 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'grid size-8 place-items-center text-xs font-bold text-slate-500';
                ellipsis.textContent = '…';
                pageNumbers.append(ellipsis);
            }

            const pageButton = document.createElement('button');
            const isCurrent = page === currentPage;

            pageButton.type = 'button';
            pageButton.dataset.page = String(page);
            pageButton.textContent = String(page);
            pageButton.setAttribute('aria-current', isCurrent ? 'page' : 'false');
            pageButton.className = `grid size-8 place-items-center border text-xs font-bold transition-all duration-300 ${isCurrent ? 'border-[#173f88] bg-[#173f88] text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-[#173f88] hover:text-[#173f88] dark:border-slate-700 dark:bg-[#0e1113] dark:text-slate-300'}`;
            pageNumbers.append(pageButton);
        });

        previousPage.disabled = currentPage === 1;
        nextPage.disabled = currentPage === totalPages;
    };

    // Filter static product cards by category and entered keywords.
    const filterProducts = () => {
        const keyword = productSearch?.value.trim().toLowerCase() ?? '';
        const filteredProducts = productCards.filter((card) => {
            const matchesCategory = selectedCategories.length === 0 || selectedCategories.includes(card.dataset.category);
            return matchesCategory && card.dataset.search.includes(keyword);
        });
        const totalPages = Math.max(1, Math.ceil(filteredProducts.length / productsPerPage));

        currentPage = Math.min(currentPage, totalPages);
        const firstProduct = (currentPage - 1) * productsPerPage;
        productCards.forEach((card) => card.classList.add('hidden'));
        filteredProducts.slice(firstProduct, firstProduct + productsPerPage).forEach((card) => card.classList.remove('hidden'));

        emptyState?.classList.toggle('hidden', filteredProducts.length > 0);
        pagination?.classList.toggle('hidden', filteredProducts.length === 0);
        renderPagination(totalPages);
    };

    let categoryControl = null;
    let previousCategoryValues = [...(categorySelect?.selectedOptions ?? [])]
        .map((option) => option.value)
        .sort()
        .join('|');
    if (categorySelect) {
        categoryControl = new TomSelect(categorySelect, {
            plugins: { remove_button: { title: 'Remove' } },
            placeholder: translate(localStorage.getItem('trendshop-locale') ?? 'km', 'filter.categoryPlaceholder'),
            hideSelected: true,
            onChange(values) {
                const normalizedValues = Array.isArray(values) ? values : (values ? [values] : []);
                selectedCategories = normalizedValues;
                const currentCategoryValues = [...normalizedValues].sort().join('|');

                // Ignore initialization and translation refreshes that do not change selection.
                if (currentCategoryValues === previousCategoryValues) return;
                previousCategoryValues = currentCategoryValues;
                if (productFilterForm) {
                    productFilterForm.requestSubmit();
                    return;
                }
                currentPage = 1;
                filterProducts();
            },
        });
    }

    // Update controls whose text is rendered by JavaScript.
    document.addEventListener('storefront:locale-changed', ({ detail: locale }) => {
        if (!categoryControl) {
            return;
        }

        categoryControl.settings.placeholder = translate(locale, 'filter.categoryPlaceholder');
        Object.entries(translations[locale].categories).forEach(([value, label]) => {
            categoryControl.updateOption(value, { value, text: label });
        });
        categoryControl.refreshOptions(false);
        categoryControl.refreshItems();
        categoryControl.inputState();
        if (activeAlertKey && alertMessage) {
            alertMessage.textContent = translate(locale, activeAlertKey);
        }
    });

    themeToggle?.addEventListener('click', () => {
        const darkModeEnabled = document.documentElement.classList.toggle('dark');
        localStorage.setItem('trendshop-theme', darkModeEnabled ? 'dark' : 'light');
    });

    // Reveal the back-to-top icon only after the page has moved away from the top.
    const updateScrollTopButton = () => {
        const shouldShow = window.scrollY > 120;
        scrollTopButton?.classList.toggle('pointer-events-none', !shouldShow);
        scrollTopButton?.classList.toggle('translate-y-3', !shouldShow);
        scrollTopButton?.classList.toggle('opacity-0', !shouldShow);
        scrollTopButton?.setAttribute('aria-hidden', String(!shouldShow));
        scrollTopButton?.setAttribute('tabindex', shouldShow ? '0' : '-1');
    };

    updateScrollTopButton();

    // Hide the header while scrolling down and reveal it after a small upward movement.
    window.addEventListener('scroll', () => {
        updateScrollTopButton();
        if (!storefrontHeader) {
            return;
        }

        const currentScrollPosition = Math.max(window.scrollY, 0);
        const scrollDelta = currentScrollPosition - previousScrollPosition;
        const nextDirection = scrollDelta > 0 ? 'down' : 'up';

        if (nextDirection !== scrollDirection) {
            scrollDirection = nextDirection;
            scrollDirectionDistance = 0;
        }

        scrollDirectionDistance += Math.abs(scrollDelta);

        if (currentScrollPosition <= 16 || menuToggle?.getAttribute('aria-expanded') === 'true') {
            storefrontHeader.classList.remove('-translate-y-full');
        } else if (scrollDirection === 'down' && currentScrollPosition > 96 && scrollDirectionDistance >= 16) {
            storefrontHeader.classList.add('-translate-y-full');
        } else if (scrollDirection === 'up' && scrollDirectionDistance >= 8) {
            storefrontHeader.classList.remove('-translate-y-full');
        }

        previousScrollPosition = currentScrollPosition;
    }, { passive: true });

    // Animate the language menu while keeping all three choices visible.
    localeMenuToggle?.addEventListener('click', () => {
        const isOpen = localeMenuToggle.getAttribute('aria-expanded') === 'true';
        localeMenuToggle.setAttribute('aria-expanded', String(!isOpen));
        localeMenu?.classList.toggle('invisible', isOpen);
        localeMenu?.classList.toggle('scale-95', isOpen);
        localeMenu?.classList.toggle('opacity-0', isOpen);
        localeMenu?.classList.toggle('visible', !isOpen);
        localeMenu?.classList.toggle('scale-100', !isOpen);
        localeMenu?.classList.toggle('opacity-100', !isOpen);
        localeChevron?.classList.toggle('rotate-180', !isOpen);
    });

    // Reveal profile and logout actions from the signed-in email button.
    accountMenuToggle?.addEventListener('click', () => {
        const isOpen = accountMenuToggle.getAttribute('aria-expanded') === 'true';
        accountMenuToggle.setAttribute('aria-expanded', String(!isOpen));
        accountMenu?.classList.toggle('invisible', isOpen);
        accountMenu?.classList.toggle('scale-95', isOpen);
        accountMenu?.classList.toggle('opacity-0', isOpen);
        accountMenu?.classList.toggle('visible', !isOpen);
        accountMenu?.classList.toggle('scale-100', !isOpen);
        accountMenu?.classList.toggle('opacity-100', !isOpen);
        accountChevron?.classList.toggle('rotate-180', !isOpen);
    });

    document.querySelectorAll('[data-locale-option]').forEach((option) => {
        option.addEventListener('click', () => {
            applyLocale(option.dataset.localeOption);
            localeMenuToggle?.setAttribute('aria-expanded', 'false');
            localeMenu?.classList.add('invisible', 'scale-95', 'opacity-0');
            localeMenu?.classList.remove('visible', 'scale-100', 'opacity-100');
            localeChevron?.classList.remove('rotate-180');
        });
    });

    document.addEventListener('click', (event) => {
        if (localeDropdown && !localeDropdown.contains(event.target)) {
            localeMenuToggle?.setAttribute('aria-expanded', 'false');
            localeMenu?.classList.add('invisible', 'scale-95', 'opacity-0');
            localeMenu?.classList.remove('visible', 'scale-100', 'opacity-100');
            localeChevron?.classList.remove('rotate-180');
        }

        if (accountDropdown && !accountDropdown.contains(event.target)) {
            accountMenuToggle?.setAttribute('aria-expanded', 'false');
            accountMenu?.classList.add('invisible', 'scale-95', 'opacity-0');
            accountMenu?.classList.remove('visible', 'scale-100', 'opacity-100');
            accountChevron?.classList.remove('rotate-180');
        }
    });

    menuToggle?.addEventListener('click', () => {
        const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isExpanded));
        mobileMenu?.classList.toggle('hidden', isExpanded);
    });

    productSearch?.addEventListener('input', () => {
        if (productFilterForm) return;
        currentPage = 1;
        filterProducts();
    });

    pageNumbers?.addEventListener('click', (event) => {
        const pageButton = event.target.closest('[data-page]');
        if (!pageButton) {
            return;
        }

        currentPage = Number(pageButton.dataset.page);
        filterProducts();
        productGrid?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    previousPage?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        filterProducts();
        productGrid?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    nextPage?.addEventListener('click', () => {
        currentPage += 1;
        filterProducts();
        productGrid?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Switch the product preview while keeping one visible active thumbnail.
    productThumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            if (!productMainImage || productMainImage.src === thumbnail.dataset.image) {
                return;
            }

            productThumbnails.forEach((item) => {
                item.classList.remove('border-[#173f88]', 'ring-2', 'ring-blue-100', 'dark:ring-blue-950');
                item.classList.add('border-slate-200', 'dark:border-slate-700');
            });
            thumbnail.classList.remove('border-slate-200', 'dark:border-slate-700');
            thumbnail.classList.add('border-[#173f88]', 'ring-2', 'ring-blue-100', 'dark:ring-blue-950');
            productMainImage.classList.add('opacity-30');

            window.setTimeout(() => {
                productMainImage.src = thumbnail.dataset.image;
                productMainImage.classList.remove('opacity-30');
            }, 150);
        });
    });

    // Cycle through every gallery image inside the zoomed preview.
    const showModalGalleryImage = (imageIndex) => {
        if (!productModalImage || productThumbnails.length === 0) {
            return;
        }

        activeModalImageIndex = (imageIndex + productThumbnails.length) % productThumbnails.length;
        productModalImage.classList.add('opacity-25');

        window.setTimeout(() => {
            productModalImage.src = productThumbnails[activeModalImageIndex].dataset.image;
            productModalImage.classList.remove('opacity-25');
        }, 120);
    };

    // Open the currently selected product image in an accessible zoomed preview.
    if (productImageOpen && productMainImage && productModalImage) {
        MicroModal.init({
            awaitOpenAnimation: true,
            awaitCloseAnimation: true,
            disableScroll: true,
        });

        productImageOpen.addEventListener('click', () => {
            const selectedImageIndex = productThumbnails.findIndex((thumbnail) => new URL(thumbnail.dataset.image, window.location.href).href === productMainImage.src);
            activeModalImageIndex = Math.max(selectedImageIndex, 0);
            productModalImage.src = productThumbnails[activeModalImageIndex]?.dataset.image ?? productMainImage.src;
            productModalImage.alt = productMainImage.alt;
            MicroModal.show('product-image-modal', {
                awaitOpenAnimation: true,
                awaitCloseAnimation: true,
                disableScroll: true,
            });
        });
    }

    productModalPrevious?.addEventListener('click', () => {
        showModalGalleryImage(activeModalImageIndex - 1);
    });

    productModalNext?.addEventListener('click', () => {
        showModalGalleryImage(activeModalImageIndex + 1);
    });

    // Support keyboard arrows while the image preview is open.
    document.addEventListener('keydown', (event) => {
        if (productImageModal?.getAttribute('aria-hidden') !== 'false') {
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            showModalGalleryImage(activeModalImageIndex - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            showModalGalleryImage(activeModalImageIndex + 1);
        }
    });

    // Keep the static product quantity within the displayed stock limit.
    quantityDecrease?.addEventListener('click', () => {
        productQuantity.value = String(Math.max(1, Number(productQuantity.value) - 1));
        if (checkoutQuantity) checkoutQuantity.value = productQuantity.value;
    });

    quantityIncrease?.addEventListener('click', () => {
        const maximumQuantity = Number(quantityIncrease.dataset.max ?? 1);
        productQuantity.value = String(Math.min(maximumQuantity, Number(productQuantity.value) + 1));
        if (checkoutQuantity) checkoutQuantity.value = productQuantity.value;
    });
    productQuantity?.addEventListener('input', () => {
        if (checkoutQuantity) checkoutQuantity.value = productQuantity.value;
    });

    // Open the dynamic product gallery and navigate every sub-image.
    let galleryIndex = 0;
    const showGalleryImage = (index) => {
        if (!galleryModalImage || galleryThumbnails.length === 0) return;
        galleryIndex = (index + galleryThumbnails.length) % galleryThumbnails.length;
        galleryModalImage.src = galleryThumbnails[galleryIndex].dataset.image;
        if (galleryMain) galleryMain.src = galleryModalImage.src;
        galleryThumbnails.forEach((thumbnail, thumbnailIndex) => {
            const isActive = thumbnailIndex === galleryIndex;
            thumbnail.classList.toggle('border-[#173f88]', isActive);
            thumbnail.classList.toggle('border-slate-200', !isActive);
            thumbnail.setAttribute('aria-current', String(isActive));
        });
    };
    galleryThumbnails.forEach((thumbnail, index) => thumbnail.addEventListener('click', () => showGalleryImage(index)));
    document.querySelector('[data-gallery-open]')?.addEventListener('click', () => {
        const selected = galleryThumbnails.findIndex((thumbnail) => new URL(thumbnail.dataset.image, window.location.href).href === galleryMain?.src);
        showGalleryImage(Math.max(selected, 0));
        galleryModal?.classList.remove('hidden');
        galleryModal?.classList.add('flex');
        document.body.style.overflow = 'hidden';
    });
    document.querySelector('[data-gallery-close]')?.addEventListener('click', () => {
        galleryModal?.classList.add('hidden'); galleryModal?.classList.remove('flex'); document.body.style.overflow = '';
    });
    document.querySelector('[data-gallery-previous]')?.addEventListener('click', () => showGalleryImage(galleryIndex - 1));
    document.querySelector('[data-gallery-next]')?.addEventListener('click', () => showGalleryImage(galleryIndex + 1));

    // Mirror the selected saved address so the customer can verify it before buying.
    deliveryAddress?.addEventListener('change', () => {
        const address = deliveryAddress.selectedOptions[0];
        if (!address || !deliveryAddressPreview) return;
        deliveryAddressPreview.querySelector('[data-address-recipient]').textContent = address.dataset.recipient;
        deliveryAddressPreview.querySelector('[data-address-phone]').textContent = address.dataset.phone;
        deliveryAddressPreview.querySelector('[data-address-summary]').textContent = address.dataset.summary;
        deliveryAddressPreview.querySelector('[data-address-fee]').textContent = address.dataset.fee;
    });

    // Delay only actions that will require backend or database work later.
    document.querySelectorAll('[data-static-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            startStaticLoading(form.querySelector('[type="submit"][data-static-action]'));
        });
    });

    document.querySelectorAll('[data-static-action]:not([type="submit"])').forEach((action) => {
        action.addEventListener('click', () => startStaticLoading(action));
    });

    // Connect immediate static actions to translated feedback without business logic.
    document.querySelectorAll('[data-alert-message][data-alert-status]').forEach((action) => {
        action.addEventListener('click', () => {
            if (action.hasAttribute('data-static-action')) {
                return;
            }

            showAlert(action.dataset.alertStatus, action.dataset.alertMessage);
        });
    });

    document.querySelector('[data-alert-close]')?.addEventListener('click', hideAlert);

    // Display server flash feedback once and dismiss it after three seconds.
    if (alert?.dataset.initialMessage) {
        showAlert(alert.dataset.status, alert.dataset.initialMessage, false);
    }

    scrollTopButton?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    applyLocale(localStorage.getItem('trendshop-locale') ?? 'km');
    if (!productFilterForm) filterProducts();
});
