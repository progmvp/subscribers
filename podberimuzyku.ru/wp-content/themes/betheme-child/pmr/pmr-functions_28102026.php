<?php
/**
 * PMR Core Functions
 *
 * @package Betheme Child
 */

if (!defined('ABSPATH')) {
	exit;
}


/**
 * PMR paths
 */

define('PMR_PATH', get_stylesheet_directory() . '/pmr/');
define('PMR_URL', get_stylesheet_directory_uri() . '/pmr/');


/**
 * Load PMR modules
 */

require_once PMR_PATH . 'pmr-roles.php';
require_once PMR_PATH . 'pmr-api.php';
require_once PMR_PATH . 'pmr-token.php';
require_once PMR_PATH . 'pmr-access.php';
require_once PMR_PATH . 'pmr-title.php';
require_once PMR_PATH . 'pmr-thumbnail.php';
require_once PMR_PATH . 'pmr-audio.php';


/**
 * PMR Login Widget
 */

require_once get_stylesheet_directory() . '/pmr/class-pmr-widget-login.php';


function pmr_register_login_widget() {

    register_widget(
        'PMR_Widget_Login'
    );

}

add_action(
    'widgets_init',
    'pmr_register_login_widget'
);

/**
 * DZSAP: общий скрипт случайных аудиофрагментов
 * Подключение в футере сайта.
 */
function pmr_enqueue_dzsap_related_random() {

    wp_enqueue_script(
        'pmr-dzsap-related-random',
        get_stylesheet_directory_uri() . '/js/dzsap-related-random.js',
        array(),
        '20261028',
        true
    );

}
add_action('wp_enqueue_scripts', 'pmr_enqueue_dzsap_related_random');
