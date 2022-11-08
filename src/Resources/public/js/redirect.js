window.addEventListener('DOMContentLoaded', (event) => {
    const urlHeader = document.querySelector('.sylius-table-column-url');
    if(null === urlHeader) {
        return;
    }

    urlHeader.innerHTML = urlHeader.innerHTML + ' <div class="ui input"><input type="text" placeholder="Add redirect to URL..." id="coupon-url-redirect"></div>';

    document.querySelector('#coupon-url-redirect').addEventListener('keyup', (e) => {
        document.querySelectorAll('.coupon-url').forEach((item) => {
            item.innerHTML = item.dataset.url + '&redirect=' + encodeURIComponent(e.currentTarget.value);
        });
    });
});
