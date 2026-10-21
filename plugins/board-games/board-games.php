<?php
/*
 * Plugin Name: Board Games
 * Version: 1.0
 * Plugin URI: http://localhost/
 * Description: Add Woocommerce List, Facebook Integration and Additional Functionality
 * Author: SilverKenn
 * Author URI: http://www.symmetricsweb.com
 * Requires at least: 2.7
 * Tested up to: 4.5
 *
 * Text Domain: brgm
 * Domain Path: /lang/
 *
 * @package Board
 * @author SilverKenn
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('BR_DIR', plugin_dir_path( __FILE__ ) );
define('BR_URL', plugin_dir_url( __FILE__ ) );

// Load plugin class files
require_once( 'includes/class-bg-helpers.php' );
require_once( 'includes/class-bg-facebook.php' );
require_once( 'includes/class-bg-woocommerce-list.php' );
require_once( 'includes/function-bg-filters.php' );
require_once( 'includes/function-bg-setup.php' );

function _initialize_br_list() {
	global $br_wclist;
	$br_wclist->init();
}
add_action('init', '_initialize_br_list');
