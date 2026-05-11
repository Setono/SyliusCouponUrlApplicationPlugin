window.addEventListener('DOMContentLoaded', (event) => {
    const urlHeader = document.querySelector('.sylius-table-column-url');
    if(null === urlHeader) {
        return;
    }

    urlHeader.innerHTML = '<div style="display:flex; flex-wrap: nowrap; align-items: center; gap: 10px">' +
        '<div>' + urlHeader.innerHTML + '</div>' +
        '<div class="ui input" style="flex-grow: 1"><input type="text" placeholder="' + setono_sylius_coupon_url_application.translations.use_other_base_url + '" id="coupon-base-url"></div>' +
    '</div>';

    document.querySelector('#coupon-base-url').addEventListener('keyup', (e) => {
        document.querySelectorAll('.coupon-url').forEach((item) => {
            const url = '' === e.currentTarget.value ? item.dataset.baseUrl : e.currentTarget.value;

            item.innerHTML = url + '?coupon=' + item.dataset.coupon;
        });
    });
});
