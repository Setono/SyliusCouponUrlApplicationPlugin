(function () {
    'use strict';

    const STORAGE_KEY = 'setono_sylius_coupon_url_application.base_url';
    const MODAL_ID = 'setono-coupon-url-modal';

    function buildUrl(baseUrl, couponCode) {
        if (!baseUrl) {
            return '';
        }

        try {
            const url = new URL(baseUrl);
            url.searchParams.set('coupon', couponCode);
            return url.toString();
        } catch (_) {
            const separator = baseUrl.includes('?') ? '&' : '?';
            return baseUrl + separator + 'coupon=' + encodeURIComponent(couponCode);
        }
    }

    function init() {
        const modal = document.getElementById(MODAL_ID);
        if (!modal) {
            return;
        }

        const baseUrlInput = modal.querySelector('#setono-coupon-url-modal-base-url');
        const urlInput = modal.querySelector('#setono-coupon-url-modal-url');
        const copyButton = modal.querySelector('#setono-coupon-url-modal-copy');
        const copyFeedback = modal.querySelector('#setono-coupon-url-modal-copy-feedback');
        const defaultBaseUrl = modal.dataset.defaultBaseUrl || '';

        let currentCouponCode = '';

        function syncUrl() {
            urlInput.value = buildUrl(baseUrlInput.value, currentCouponCode);
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            currentCouponCode = trigger && trigger.dataset.couponCode ? trigger.dataset.couponCode : '';

            const storedBaseUrl = window.localStorage.getItem(STORAGE_KEY);
            baseUrlInput.value = storedBaseUrl !== null ? storedBaseUrl : defaultBaseUrl;

            copyFeedback.classList.add('d-none');
            syncUrl();
        });

        baseUrlInput.addEventListener('input', function () {
            window.localStorage.setItem(STORAGE_KEY, baseUrlInput.value);
            syncUrl();
        });

        copyButton.addEventListener('click', function () {
            const value = urlInput.value;
            if (!value) {
                return;
            }

            const showFeedback = function () {
                copyFeedback.classList.remove('d-none');
                window.setTimeout(function () {
                    copyFeedback.classList.add('d-none');
                }, 2000);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(showFeedback, function () {
                    urlInput.select();
                    document.execCommand('copy');
                    showFeedback();
                });
                return;
            }

            urlInput.select();
            document.execCommand('copy');
            showFeedback();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
