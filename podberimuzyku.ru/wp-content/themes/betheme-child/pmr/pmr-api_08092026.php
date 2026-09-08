<?php
/**
 * PMR API
 *
 * Laravel → WordPress integration.
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
 * Access is allowed only to the technical
 * WordPress user with the pmr_api role.
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

    if (!in_array('pmr_api', (array) $user->roles, true)) {
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
 * Synchronize subscription status with WordPress user.
 *
 * Laravel sends:
 *
 * email  = user email
 * status = active|expired
 *
 * active:
 *     user must have subscriber role.
 *
 * expired:
 *     user must have pmr_user role.
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
     * Find WordPress user by email.
     */
    $user = get_user_by(
        'email',
        $email
    );

    /**
     * ACTIVE subscription.
     *
     * If the user does not exist, create it.
     */
    if ($status === 'active') {

        if (!$user) {

            $username_base = sanitize_user(
                current(
                    explode('@', $email)
                ),
                true
            );

            if (!$username_base) {
                $username_base = 'pmr-user';
            }

            $username = $username_base;
            $counter  = 1;

            while (username_exists($username)) {

                $username = $username_base . '-' . $counter;

                $counter++;
            }

            /**
             * Generate random password.
             *
             * Laravel password is NOT transferred to WordPress.
             */
            $password = wp_generate_password(
                32,
                true,
                true
            );

            $user_id = wp_insert_user(
                [
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass'  => $password,
                    'role'       => 'pmr_user',
                ]
            );

            if (is_wp_error($user_id)) {
                return new WP_Error(
                    'pmr_api_user_create_failed',
                    $user_id->get_error_message(),
                    [
                        'status' => 500,
                    ]
                );
            }

            $user = get_user_by(
                'id',
                $user_id
            );
        }

        /**
         * Never change administrator role.
         */
        if (in_array('administrator', (array) $user->roles, true)) {
            return new WP_Error(
                'pmr_api_admin_protected',
                'Administrator account cannot be modified by PMR API.',
                [
                    'status' => 403,
                ]
            );
        }

        /**
         * Activate premium access.
         */
        $user->set_role('subscriber');

        return rest_ensure_response(
            [
                'success' => true,
                'status'  => 'active',
                'user'    => [
                    'id'    => $user->ID,
                    'email' => $user->user_email,
                    'role'  => 'subscriber',
                ],
            ]
        );
    }

    /**
     * EXPIRED subscription.
     *
     * If the user does not exist, there is nothing to deactivate.
     * We do NOT create a WordPress account just because a subscription
     * has expired.
     */
    if ($status === 'expired') {

        if (!$user) {
            return rest_ensure_response(
                [
                    'success' => true,
                    'status'  => 'expired',
                    'user'    => null,
                    'message' => 'WordPress user does not exist. Nothing to deactivate.',
                ]
            );
        }

        /**
         * Never change administrator role.
         */
        if (in_array('administrator', (array) $user->roles, true)) {
            return new WP_Error(
                'pmr_api_admin_protected',
                'Administrator account cannot be modified by PMR API.',
                [
                    'status' => 403,
                ]
            );
        }

        /**
         * Remove premium access.
         */
        $user->set_role('pmr_user');

        return rest_ensure_response(
            [
                'success' => true,
                'status'  => 'expired',
                'user'    => [
                    'id'    => $user->ID,
                    'email' => $user->user_email,
                    'role'  => 'pmr_user',
                ],
            ]
        );
    }

    /**
     * Should never be reached.
     */
    return new WP_Error(
        'pmr_api_unknown_error',
        'Unknown API error.',
        [
            'status' => 500,
        ]
    );
}
