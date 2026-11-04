<?php
/**
 * PMR Cache Tools
 *
 * Admin tools for server cache management
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| ADMIN MENU
|--------------------------------------------------------------------------
*/

function pmr_add_cache_tools_menu() {

    add_management_page(
        'Очистка FastCGI Cache',
        'Очистить FastCGI Cache',
        'manage_options',
        'pmr-clear-fastcgi-cache',
        'pmr_cache_tools_page'
    );

}

add_action('admin_menu', 'pmr_add_cache_tools_menu');


/*
|--------------------------------------------------------------------------
| ADMIN BAR
|--------------------------------------------------------------------------
*/

function pmr_add_cache_tools_to_admin_bar($wp_admin_bar) {

    if (!current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_node([
        'id'    => 'pmr-clear-fastcgi-cache',
        'title' => 'Очистить FastCGI Cache',
        'href'  => '#',
        'meta'  => [
            'title' => 'Очистить Nginx FastCGI Cache',
        ],
    ]);

}

add_action(
    'admin_bar_menu',
    'pmr_add_cache_tools_to_admin_bar',
    100
);


/*
|--------------------------------------------------------------------------
| AJAX CLEAR CACHE
|--------------------------------------------------------------------------
*/

function pmr_ajax_clear_fastcgi_cache() {

    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Недостаточно прав.'
        ]);
    }

    check_ajax_referer(
        'pmr_clear_fastcgi_cache',
        'nonce'
    );

    $output = shell_exec(
        'sudo /usr/local/sbin/clear-fastcgi-cache.sh 2>&1'
    );

    wp_send_json_success([
        'message' => 'FastCGI Cache успешно очищен.',
        'output'  => trim($output),
    ]);
}

add_action(
    'wp_ajax_pmr_clear_fastcgi_cache',
    'pmr_ajax_clear_fastcgi_cache'
);


/*
|--------------------------------------------------------------------------
| ADMIN BAR JAVASCRIPT
|--------------------------------------------------------------------------
*/

function pmr_fastcgi_cache_admin_bar_script() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $nonce = wp_create_nonce(
        'pmr_clear_fastcgi_cache'
    );

    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const cacheButton = document.querySelector(
            '#wp-admin-bar-pmr-clear-fastcgi-cache > a'
        );

        if (!cacheButton) {
            return;
        }

        cacheButton.addEventListener('click', function (e) {

            e.preventDefault();

            if (cacheButton.dataset.loading === '1') {
                return;
            }

            cacheButton.dataset.loading = '1';

            const originalText = cacheButton.textContent;

            cacheButton.textContent = 'Очистка...';

            const formData = new FormData();

            formData.append(
                'action',
                'pmr_clear_fastcgi_cache'
            );

            formData.append(
                'nonce',
                '<?php echo esc_js($nonce); ?>'
            );

            fetch(
                '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                }
            )
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                if (data.success) {

                    cacheButton.textContent =
                        '✓ Cache очищен';

                    setTimeout(function () {
                        cacheButton.textContent =
                            originalText;
                    }, 2000);

                } else {

                    cacheButton.textContent =
                        '✕ Ошибка';

                    alert(
                        data.data && data.data.message
                            ? data.data.message
                            : 'Не удалось очистить FastCGI Cache.'
                    );

                    setTimeout(function () {
                        cacheButton.textContent =
                            originalText;
                    }, 2000);
                }

            })
            .catch(function () {

                cacheButton.textContent =
                    '✕ Ошибка';

                alert(
                    'Ошибка соединения при очистке FastCGI Cache.'
                );

                setTimeout(function () {
                    cacheButton.textContent =
                        originalText;
                }, 2000);

            })
            .finally(function () {

                cacheButton.dataset.loading = '0';

            });

        });

    });
    </script>

    <?php
}

add_action(
    'admin_footer',
    'pmr_fastcgi_cache_admin_bar_script'
);


/*
|--------------------------------------------------------------------------
| ADMIN PAGE
|--------------------------------------------------------------------------
*/

function pmr_cache_tools_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';

    if (
        isset($_POST['pmr_clear_cache']) &&
        check_admin_referer('pmr_clear_fastcgi_cache')
    ) {

        $output = shell_exec(
            'sudo /usr/local/sbin/clear-fastcgi-cache.sh 2>&1'
        );

        $message = nl2br(
            esc_html($output)
        );

    }

    ?>

    <div class="wrap">

        <h1>Очистка FastCGI Cache</h1>

        <?php if ($message): ?>

            <div class="notice notice-success">

                <p>
                    <strong>
                        Результат выполнения:
                    </strong>
                </p>

                <p>
                    <?php echo $message; ?>
                </p>

            </div>

        <?php endif; ?>

        <form method="post">

            <?php
            wp_nonce_field(
                'pmr_clear_fastcgi_cache'
            );
            ?>

            <p>
                Нажмите кнопку для очистки
                Nginx FastCGI Cache.
            </p>

            <p>

                <button
                    type="submit"
                    name="pmr_clear_cache"
                    class="button button-primary"
                >
                    Очистить FastCGI Cache
                </button>

            </p>

        </form>

    </div>

    <?php
}