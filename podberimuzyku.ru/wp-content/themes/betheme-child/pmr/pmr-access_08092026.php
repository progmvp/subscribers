<?php
/**
 * PMR Access
 *
 * Проверка доступа пользователей
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Проверка: пользователь имеет премиум доступ
 *
 * Администраторы и подписчики считаются премиум пользователями
 *
 * @return bool
 */
function pmr_is_subscriber() {

    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();

    if (empty($user->roles)) {
        return false;
    }


    if (in_array('administrator', $user->roles, true)) {
        return true;
    }


    if (in_array('subscriber', $user->roles, true)) {
        return true;
    }


    return false;
}


/**
 * Получение типа пользователя PMR
 *
 * Используется для отладки и будущей логики
 *
 * @return string
 */
function pmr_user_type() {

    if (!is_user_logged_in()) {
        return 'guest';
    }

    $user = wp_get_current_user();


    if (in_array('administrator', $user->roles, true)) {
        return 'administrator';
    }


    if (in_array('subscriber', $user->roles, true)) {
        return 'subscriber';
    }


    return 'guest';
}
