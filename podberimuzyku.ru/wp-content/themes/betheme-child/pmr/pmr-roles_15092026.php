<?php
/**
 * PMR Roles
 *
 * Пользовательские роли PMR.
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация ролей PMR.
 *
 * pmr_user — пользователь без активной подписки.
 * pmr_api  — технический пользователь для Laravel → WordPress API.
 */
function pmr_register_roles() {

    /**
     * Обычный пользователь PMR
     */
    if (!get_role('pmr_user')) {

        add_role(
            'pmr_user',
            'PMR User',
            [
                'read' => false,
            ]
        );

    }

    /**
     * Технический пользователь API
     */
    if (!get_role('pmr_api')) {

        add_role(
            'pmr_api',
            'PMR API',
            [
                'read' => false,
            ]
        );

    }
}

add_action(
    'init',
    'pmr_register_roles'
);
