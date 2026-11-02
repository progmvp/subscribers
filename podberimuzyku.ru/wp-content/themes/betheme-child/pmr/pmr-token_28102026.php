<?php
/**
 * PMR SSO Token Authentication
 *
 * Laravel one-time token -> existing technical WordPress user pmr-api.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMR_TOKEN_NAMESPACE', 'pmr/v1');

add_action('rest_api_init', function () {
    register_rest_route(
        PMR_TOKEN_NAMESPACE,
        '/token',
        [
            'methods'             => 'GET',
            'callback'            => 'pmr_token_authenticate',
            'permission_callback' => '__return_true',
        ]
    );
});

/**
 * Проверка URL возврата.
 */
function pmr_sso_valid_return_url($url) {

    if (!is_string($url) || $url === '') {
        return false;
    }

    $parts = wp_parse_url($url);

    if (!is_array($parts)) {
        return false;
    }

    $scheme = strtolower($parts['scheme'] ?? '');
    $host = strtolower($parts['host'] ?? '');
    $port = $parts['port'] ?? null;

    return $scheme === 'https'
        && in_array(
            $host,
            ['podberimuzyku.ru', 'www.podberimuzyku.ru'],
            true
        )
        && ($port === null || $port === 443)
        && !isset($parts['user'])
        && !isset($parts['pass']);
}

/**
 * Установка подписанного cookie с контекстом подписки Laravel.
 *
 * Значение cookie подписано серверным секретом.
 * Срок действия не может превышать срок подписки.
 */
function pmr_sso_set_access_cookie($laravel_user_id, $subscription_expires_at) {

    if (
        !defined('PMR_SSO_SHARED_SECRET')
        || !is_string(PMR_SSO_SHARED_SECRET)
        || PMR_SSO_SHARED_SECRET === ''
    ) {
        return false;
    }

    $expiry = strtotime($subscription_expires_at);

    if (!$expiry || $expiry <= time()) {
        return false;
    }

    $payload = absint($laravel_user_id) . '|' . $expiry;

    $signature = hash_hmac(
        'sha256',
        $payload,
        PMR_SSO_SHARED_SECRET
    );

    $cookie_value = $payload . '|' . $signature;

    $options = [
        'expires'  => $expiry,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (!setcookie('pmr_sso_access', $cookie_value, $options)) {
        return false;
    }

    $_COOKIE['pmr_sso_access'] = $cookie_value;

    return true;
}

/**
 * Проверка Laravel токена и вход под существующим пользователем pmr-api.
 */
function pmr_token_authenticate(WP_REST_Request $request) {

    if (
        !defined('PMR_SSO_SHARED_SECRET')
        || !is_string(PMR_SSO_SHARED_SECRET)
        || PMR_SSO_SHARED_SECRET === ''
    ) {
        return new WP_Error(
            'pmr_sso_not_configured',
            'SSO is not configured.',
            ['status' => 500]
        );
    }

    $token = (string) $request->get_param('token');

    if (
        $token === ''
        || strlen($token) < 32
        || strlen($token) > 200
    ) {
        return new WP_Error(
            'pmr_token_invalid',
            'Invalid token.',
            ['status' => 400]
        );
    }

    /*
     * Проверяем и погашаем токен на Laravel сервере.
     * Секрет передаётся только в серверном запросе.
     */
    $response = wp_remote_post(
        'https://lk.podberimuzyku.ru/api/sso/wordpress/validate',
        [
            'timeout'     => 10,
            'redirection' => 0,
            'headers'     => [
                'X-PMR-SSO-Secret' => PMR_SSO_SHARED_SECRET,
                'Accept'           => 'application/json',
            ],
            'body' => [
                'token' => $token,
            ],
        ]
    );

    if (is_wp_error($response)) {
        return new WP_Error(
            'pmr_sso_laravel_unavailable',
            'Could not validate SSO token.',
            ['status' => 502]
        );
    }

    $status = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (
        $status !== 200
        || !is_array($data)
        || empty($data['success'])
        || empty($data['user_id'])
        || empty($data['subscription_expires_at'])
        || empty($data['return_url'])
    ) {
        return new WP_Error(
            'pmr_token_rejected',
            'Token is invalid, expired or already used.',
            ['status' => 401]
        );
    }

    /*
     * Проверяем URL возврата, полученный от Laravel.
     */
    $return_url = (string) $data['return_url'];

    if (!pmr_sso_valid_return_url($return_url)) {
        return new WP_Error(
            'pmr_sso_return_url_invalid',
            'Invalid return URL.',
            ['status' => 400]
        );
    }

    /*
     * Используем существующего технического пользователя.
     */
    $user = get_user_by('login', 'pmr-api');

    if (!$user || !$user->exists()) {
        return new WP_Error(
            'pmr_api_user_not_found',
            'Technical WordPress user pmr-api not found.',
            ['status' => 500]
        );
    }

    /*
     * Создаём подписанный cookie.
     */
    if (
        !pmr_sso_set_access_cookie(
            (int) $data['user_id'],
            (string) $data['subscription_expires_at']
        )
    ) {
        return new WP_Error(
            'pmr_sso_cookie_failed',
            'Could not establish subscription session.',
            ['status' => 500]
        );
    }

    /*
     * Авторизуем технического пользователя WordPress.
     */
    wp_set_current_user($user->ID);

    wp_set_auth_cookie(
        $user->ID,
        false,
        is_ssl()
    );

    /*
     * Возвращаем пользователя на исходную страницу.
     */
    wp_redirect($return_url, 302, 'PMR SSO');
    exit;
}
