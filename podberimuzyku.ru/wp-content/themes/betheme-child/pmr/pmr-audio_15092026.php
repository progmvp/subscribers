<?php
/**
 * PMR Audio
 *
 * Логика вывода аудио PMR
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Срок действия гостевой ссылки на аудио.
 *
 * 10 минут.
 *
 * @return int
 */
function pmr_audio_guest_ttl()
{
    return 10 * MINUTE_IN_SECONDS;
}


/**
 * Секрет для подписи гостевых аудио-ссылок.
 *
 * Используем стандартный секрет WordPress.
 *
 * @return string
 */
function pmr_audio_guest_secret()
{
    return wp_salt('auth');
}


/**
 * Генерация подписи гостевой аудио-ссылки.
 *
 * @param int $post_id
 * @param int $expires
 *
 * @return string
 */
function pmr_audio_guest_signature($post_id, $expires)
{
    return hash_hmac(
        'sha256',
        $post_id . '|' . $expires,
        pmr_audio_guest_secret()
    );
}


/**
 * Генерация временной защищённой ссылки
 * для гостевого аудио.
 *
 * @param int $post_id
 *
 * @return string
 */
function pmr_get_guest_audio_url($post_id)
{
    $post_id = absint($post_id);

    if (!$post_id) {
        return '';
    }

    $expires = time() + pmr_audio_guest_ttl();

    $signature = pmr_audio_guest_signature(
        $post_id,
        $expires
    );

    return home_url(
        '/audio-guest/' . $post_id .
        '?expires=' . $expires .
        '&signature=' . $signature
    );
}


/**
 * Регистрируем endpoint:
 *
 * /audio-guest/{post_id}
 */
add_action('init', function () {

    add_rewrite_rule(
        '^audio-guest/([0-9]+)/?$',
        'index.php?pmr_audio_guest=$matches[1]',
        'top'
    );

});


/**
 * Разрешаем собственный query var.
 */
add_filter('query_vars', function ($vars) {

    $vars[] = 'pmr_audio_guest';

    return $vars;

});


/**
 * Обработка защищённого гостевого аудио.
 */
add_action('template_redirect', function () {

    $post_id = absint(
        get_query_var('pmr_audio_guest')
    );

    if (!$post_id) {
        return;
    }


    /**
     * Получаем параметры защищённой ссылки.
     */
    $expires = isset($_GET['expires'])
        ? absint($_GET['expires'])
        : 0;

    $signature = isset($_GET['signature'])
        ? sanitize_text_field(
            wp_unslash($_GET['signature'])
        )
        : '';


    /**
     * Проверяем срок действия ссылки.
     */
    if (!$expires || time() > $expires) {

        status_header(403);

        exit('Audio link expired');
    }


    /**
     * Проверяем подпись.
     */
    $expected = pmr_audio_guest_signature(
        $post_id,
        $expires
    );

    if (
        !$signature ||
        !hash_equals($expected, $signature)
    ) {

        status_header(403);

        exit('Invalid audio signature');
    }


    /**
     * Получаем исходную гостевую ссылку.
     */
    $audio = get_post_meta(
        $post_id,
        'dzsap_audio_link_guest',
        true
    );

    if (empty($audio)) {

        status_header(404);

        exit('Audio not found');
    }


    /**
     * Передаём запрос существующей ссылке.
     */
    wp_safe_redirect($audio, 302);

    exit;

});


/**
 * Получение ссылки на аудио PMR
 *
 * Администратор:
 *   dzsap_audio_link-first
 *
 * Подписчик:
 *   dzsap_audio_link-first
 *
 * Гость:
 *   dzsap_audio_link_guest
 *   → временная защищённая ссылка
 *
 * Если dzsap_audio_link_guest отсутствует:
 *   dzsap_audio_link-first
 *
 * @param int|null $post_id
 *
 * @return string
 */
function pmr_get_audio($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }


    /**
     * Подписчик получает оригинальное аудио.
     */
    if (pmr_is_subscriber()) {

        return get_post_meta(
            $post_id,
            'dzsap_audio_link-first',
            true
        );
    }


    /**
     * Гость получает значение
     * dzsap_audio_link_guest.
     */
    $audio = get_post_meta(
        $post_id,
        'dzsap_audio_link_guest',
        true
    );


    /**
     * Если guest-ссылка существует,
     * вместо неё отдаём временную
     * защищённую ссылку.
     */
    if (!empty($audio)) {

        return pmr_get_guest_audio_url($post_id);
    }


    /**
     * Если guest-ссылка отсутствует,
     * сохраняем существующий fallback.
     */
    return get_post_meta(
        $post_id,
        'dzsap_audio_link-first',
        true
    );
}
