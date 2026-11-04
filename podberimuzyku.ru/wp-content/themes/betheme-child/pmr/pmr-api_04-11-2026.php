<?php
/**
 * PMR API
 *
 * Laravel → WordPress integration.
 *
 * The API uses the existing technical WordPress user pmr-api.
 * Laravel remains the source of truth for subscription status.
 *
 * No Laravel subscriber users are created in WordPress.
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PMR_API_NAMESPACE', 'pmr/v1');

/**
 * Register PMR REST API routes.
 */
add_action(
    'rest_api_init',
    function () {

        register_rest_route(
            PMR_API_NAMESPACE,
            '/subscription',
            [
                'methods'             => 'POST',
                'callback'            => 'pmr_api_subscription',
                'permission_callback' => 'pmr_api_permission',
            ]
        );

    }
);

/**
 * Check PMR API authentication.
 *
 * Only the existing technical WordPress user pmr-api
 * may call the subscription API.
 *
 * Authentication is based on the WordPress user identity,
 * not on a custom pmr_api role.
 *
 * @param WP_REST_Request $request REST request.
 *
 * @return true|WP_Error
 */
function pmr_api_permission(WP_REST_Request $request) {

    if (!is_user_logged_in()) {
        return new WP_Error(
            'pmr_api_unauthorized',
            'Authentication required.',
            [
                'status' => 401,
            ]
        );
    }

    $user = wp_get_current_user();

    if (!$user || !$user->exists()) {
        return new WP_Error(
            'pmr_api_unauthorized',
            'Invalid user.',
            [
                'status' => 401,
            ]
        );
    }

    if ($user->user_login !== 'pmr-api') {
        return new WP_Error(
            'pmr_api_forbidden',
            'API access denied.',
            [
                'status' => 403,
            ]
        );
    }

    return true;
}

/**
 * Receive subscription status from Laravel.
 *
 * Laravel is the source of truth.
 *
 * IMPORTANT:
 * No WordPress subscriber account is created or modified here.
 *
 * Laravel sends:
 *
 * email  = Laravel subscriber email
 * status = active|expired
 *
 * The email is accepted for diagnostics and future integration,
 * but is not used to create a WordPress user.
 *
 * @param WP_REST_Request $request REST request.
 *
 * @return WP_REST_Response|WP_Error
 */
function pmr_api_subscription(WP_REST_Request $request) {

    $email = sanitize_email(
        $request->get_param('email')
    );

    $status = sanitize_key(
        $request->get_param('status')
    );

    /**
     * Validate email.
     */
    if (!$email || !is_email($email)) {
        return new WP_Error(
            'pmr_api_invalid_email',
            'Valid email is required.',
            [
                'status' => 400,
            ]
        );
    }

    /**
     * Validate subscription status.
     */
    if (!in_array($status, ['active', 'expired'], true)) {
        return new WP_Error(
            'pmr_api_invalid_status',
            'Status must be active or expired.',
            [
                'status' => 400,
            ]
        );
    }

    /**
     * The Laravel user must NOT be created in WordPress.
     *
     * WordPress access is handled through the existing technical
     * pmr-api session and the standard subscriber role.
     */
    return rest_ensure_response(
        [
            'success' => true,
            'status'  => $status,
            'user'    => [
                'login' => 'pmr-api',
                'role'  => 'subscriber',
            ],
            'email'   => $email,
            'message' => 'Subscription status accepted. No WordPress subscriber user was created or modified.',
        ]
    );
}
