<?php
/**
 * PMR Access
 *
 * Премиум-доступ:
 * - администратор WordPress;
 * - технический пользователь pmr-api с действующей
 *   подписанной SSO-сессией Laravel.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Проверяет подпись и срок действия SSO cookie.
 *
 * Возвращает данные сессии или false.
 */
function pmr_sso_get_access_context() {
    if (
        !defined('PMR_SSO_SHARED_SECRET')
        || !is_string(PMR_SSO_SHARED_SECRET)
        || PMR_SSO_SHARED_SECRET === ''
    ) {
        return false;
    }

    $cookie = $_COOKIE['pmr_sso_access'] ?? '';

    if (!is_string($cookie) || $cookie === '') {
        return false;
    }

    $parts = explode('|', $cookie);

    if (count($parts) !== 3) {
        return false;
    }

    $laravel_user_id = absint($parts[0]);
    $expiry = absint($parts[1]);
    $received_signature = (string) $parts[2];

    if (
        $laravel_user_id < 1
        || $expiry <= time()
        || !preg_match('/^[a-f0-9]{64}$/', $received_signature)
    ) {
        return false;
    }

    $payload = $laravel_user_id . '|' . $expiry;

    $expected_signature = hash_hmac(
        'sha256',
        $payload,
        PMR_SSO_SHARED_SECRET
    );

    if (!hash_equals($expected_signature, $received_signature)) {
        return false;
    }

    return [
        'laravel_user_id' => $laravel_user_id,
        'expires_at'      => $expiry,
    ];
}

/**
 * Проверяет, имеет ли текущий пользователь премиум-доступ.
 */
function pmr_is_subscriber() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();

    if (empty($user->roles)) {
        return false;
    }

    /*
     * Администратор сохраняет полный доступ.
     */
    if (in_array('administrator', $user->roles, true)) {
        return true;
    }

    /*
     * Обычная роль subscriber сама по себе премиум-доступ не даёт.
     */
    if ($user->user_login !== 'pmr-api') {
        return false;
    }

    return pmr_sso_get_access_context() !== false;
}

/**
 * Возвращает тип пользователя PMR.
 */
function pmr_user_type() {
    if (!is_user_logged_in()) {
        return 'guest';
    }

    $user = wp_get_current_user();

    if (in_array('administrator', $user->roles, true)) {
        return 'administrator';
    }

    if (
        $user->user_login === 'pmr-api'
        && pmr_sso_get_access_context() !== false
    ) {
        return 'subscriber';
    }

    return 'guest';
}
