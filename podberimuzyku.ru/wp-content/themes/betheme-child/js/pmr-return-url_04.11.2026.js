(function () {
    'use strict';

    var LARAVEL_URL = 'https://lk.podberimuzyku.ru/member-subscribers/';

    /**
     * Проверяем, ведёт ли ссылка на страницу оформления подписки.
     */
    function isSubscriptionLink(url) {
        try {
            var parsed = new URL(url, window.location.href);

            return (
                (
                    parsed.hostname === 'podberimuzyku.ru' ||
                    parsed.hostname === 'www.podberimuzyku.ru' ||
                    parsed.hostname === 'lk.podberimuzyku.ru'
                ) &&
                /^\/member-subscribers\/?$/.test(parsed.pathname)
            );

        } catch (error) {
            return false;
        }
    }

    /**
     * Формируем ссылку с адресом исходной страницы.
     */
    function updateSubscriptionLink(link) {

        var href = link.getAttribute('href');

        if (!href || !isSubscriptionLink(href)) {
            return;
        }

        var target = new URL(LARAVEL_URL);

        target.searchParams.set(
            'return_url',
            window.location.href
        );

        link.setAttribute(
            'href',
            target.toString()
        );
    }

    /**
     * Обрабатываем существующие ссылки.
     */
    function init() {

        document.querySelectorAll('a[href]').forEach(function (link) {
            updateSubscriptionLink(link);
        });

    }

    if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', init);

    } else {

        init();

    }

    /**
     * Обрабатываем ссылки, которые могут появиться позднее.
     */
    document.addEventListener('click', function (event) {

        var link = event.target.closest('a[href]');

        if (link) {
            updateSubscriptionLink(link);
        }

    }, true);

})();
