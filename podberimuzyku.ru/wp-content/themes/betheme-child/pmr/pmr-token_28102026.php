<?php
/**
 * PMR Token Authentication
 *
 * Laravel subscription bridge:
 * token -> existing technical WordPress user pmr-api session.
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMR_TOKEN_NAMESPACE', 'pmr/v1');

/**
 * Register token authentication endpoint.
 */
add_action(
    'rest_api_init',
    function () {

        register_rest_route(
            PMR_TOKEN_NAMESPACE,
            '/token',
            [
                'methods'             => 'GET',
                'callback'            => 'pmr_token_authenticate',
                'permission_callback' => '__return_true',
            ]
        );

    }
);

/**
 * Temporary test token.
 *
 * ONLY FOR INITIAL TEST.
 */
function pmr_token_test_value() {

    return 'PMR-TEST-2026-09-08';

}

/**
 * Authenticate existing technical WordPress user pmr-api.
 *
 * The technical user is identified by login, not by a custom role.
 */
function pmr_token_authenticate(WP_REST_Request $request) {

    $token = sanitize_text_field(
        $request->get_param('token')
    );

    if (!$token) {
        return new WP_Error(
            'pmr_token_missing',
            'Token is required.',
            [
                'status' => 400,
            ]
        );
    }

    if (!hash_equals(
        pmr_token_test_value(),
        $token
    )) {
        return new WP_Error(
            'pmr_token_invalid',
            'Invalid token.',
            [
                'status' => 401,
            ]
        );
    }

    /**
     * Find the existing technical WordPress user.
     */
    $user = get_user_by(
        'login',
        'pmr-api'
    );

    if (!$user || !$user->exists()) {
        return new WP_Error(
            'pmr_api_user_not_found',
            'Technical WordPress user pmr-api not found.',
            [
                'status' => 500,
            ]
        );
    }

    /**
     * Establish WordPress authentication.
     *
     * No custom pmr_api role is required.
     * The existing pmr-api user may use the standard subscriber role.
     */
    wp_set_current_user(
        $user->ID
    );

    wp_set_auth_cookie(
        $user->ID,
        false,
        is_ssl() ? 'https' : 'http'
    );

    /**
     * Return diagnostic information.
     */
    return rest_ensure_response(
        [
            'success' => true,
            'message' => 'pmr-api authenticated.',
            'user'    => [
                'id'    => $user->ID,
                'login' => $user->user_login,
                'roles' => $user->roles,
            ],
        ]
    );

}
