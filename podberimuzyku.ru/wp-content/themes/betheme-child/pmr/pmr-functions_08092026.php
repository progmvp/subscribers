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
