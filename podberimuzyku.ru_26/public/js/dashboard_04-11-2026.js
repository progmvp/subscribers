
/* /var/www/podberimuzyku.ru_26/public/js/dashboard.js */

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('lk-sidebar');
    const menuToggle = document.getElementById('lk-menu-toggle');
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

    if (!sidebar || !menuToggle) {
        return;
    }

    function closeMenu() {
        sidebar.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Îòêðûòü ìåíþ');
    }

    function toggleMenu() {
        const isOpen = sidebar.classList.toggle('is-open');

        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.setAttribute(
            'aria-label',
            isOpen ? 'Çàêðûòü ìåíþ' : 'Îòêðûòü ìåíþ'
        );
    }

    menuToggle.addEventListener('click', toggleMenu);

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            navLinks.forEach(function (item) {
                item.classList.remove('is-active');
            });

            link.classList.add('is-active');
            closeMenu();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 760) {
            closeMenu();
        }
    });
});
