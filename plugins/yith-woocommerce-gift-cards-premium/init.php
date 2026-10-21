<?php
/*
Plugin Name: YITH WooCommerce Gift Cards Premium
Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-gift-cards/
Description: Allow your users to purchase and give gift cards, an easy and direct way to encourage new sales.
Author: YITHEMES
Text Domain: yith-woocommerce-gift-cards
Version: 1.4.11
Author URI: http://yithemes.com/
*/

/*  Copyright 2013-2015  Your Inspiration Themes  (email : plugins@yithemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'yith_ywgc_install_woocommerce_admin_notice' ) ) {

	function yith_ywgc_install_woocommerce_admin_notice() {
		?>
		<div class="error">
			<p><?php _e( 'YITH WooCommerce Gift Cards is enabled but not effective. It requires WooCommerce in order to work.', 'yit' ); ?></p>
		</div>
		<?php
	}
}

/**
 * Check if a free version is currently active and try disabling before activating this one
 */
if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YITH_YWGC_FREE_INIT', plugin_basename( __FILE__ ) );

if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

//region    ****    Define constants

defined( 'YITH_YWGC_PREMIUM' ) || define( 'YITH_YWGC_PREMIUM', '1' );
defined( 'YITH_YWGC_SLUG' ) || define( 'YITH_YWGC_SLUG', 'yith-woocommerce-gift-cards' );
defined( 'YITH_YWGC_SECRET_KEY' ) || define( 'YITH_YWGC_SECRET_KEY', 'GcGTnx2i0Qdavxe9b9by' );

defined( 'YITH_YWGC_PLUGIN_NAME' ) || define( 'YITH_YWGC_PLUGIN_NAME', 'YITH WooCommerce Gift Cards' );
defined( 'YITH_YWGC_INIT' ) || define( 'YITH_YWGC_INIT', plugin_basename( __FILE__ ) );
defined( 'YITH_YWGC_VERSION' ) || define( 'YITH_YWGC_VERSION', '1.4.11' );
defined( 'YITH_YWGC_DB_CURRENT_VERSION' ) || define( 'YITH_YWGC_DB_CURRENT_VERSION', '1.0.2' );
defined( 'YITH_YWGC_FILE' ) || define( 'YITH_YWGC_FILE', __FILE__ );
defined( 'YITH_YWGC_DIR' ) || define( 'YITH_YWGC_DIR', plugin_dir_path( __FILE__ ) );
defined( 'YITH_YWGC_URL' ) || define( 'YITH_YWGC_URL', plugins_url( '/', __FILE__ ) );
defined( 'YITH_YWGC_ASSETS_URL' ) || define( 'YITH_YWGC_ASSETS_URL', YITH_YWGC_URL . 'assets' );
defined( 'YITH_YWGC_ASSETS_DIR' ) || define( 'YITH_YWGC_ASSETS_DIR', YITH_YWGC_DIR . 'assets' );
defined( 'YITH_YWGC_SCRIPT_URL' ) || define( 'YITH_YWGC_SCRIPT_URL', YITH_YWGC_ASSETS_URL . '/js/' );
defined( 'YITH_YWGC_TEMPLATES_DIR' ) || define( 'YITH_YWGC_TEMPLATES_DIR', YITH_YWGC_DIR . 'templates/' );
defined( 'YITH_YWGC_ASSETS_IMAGES_URL' ) || define( 'YITH_YWGC_ASSETS_IMAGES_URL', YITH_YWGC_ASSETS_URL . '/images/' );

$wp_upload_dir = wp_upload_dir();

defined( 'YITH_YWGC_SAVE_DIR' ) || define( 'YITH_YWGC_SAVE_DIR', $wp_upload_dir['basedir'] . '/yith-gift-cards/' );
defined( 'YITH_YWGC_SAVE_URL' ) || define( 'YITH_YWGC_SAVE_URL', $wp_upload_dir['baseurl'] . '/yith-gift-cards/' );

//endregion

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YITH_YWGC_DIR . 'plugin-fw/init.php' ) ) {
	require_once( YITH_YWGC_DIR . 'plugin-fw/init.php' );
}
yit_maybe_plugin_fw_loader( YITH_YWGC_DIR );

require_once( YITH_YWGC_DIR . 'functions.php' );

if ( ! function_exists( 'yith_ywgc_premium_init' ) ) {
	/**
	 * Init the plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_premium_init() {

		/**
		 * Load text domain and start plugin
		 */
		load_plugin_textdomain( 'yith-woocommerce-gift-cards', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		YWGC_Plugin_FW_Loader::get_instance();

		//  Star the plugin
		yith_gift_cards_start();

		//  Load third-party modules
		yith_ywgc_load_third_party_modules();

	}
}
add_action( 'yith_ywgc_premium_init', 'yith_ywgc_premium_init' );

if ( ! function_exists( 'YITH_YWGC' ) ) {
	/**
	 * Get the main plugin class
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function YITH_YWGC() {
		return YITH_WooCommerce_Gift_Cards_Premium::get_instance();
	}
}

if ( ! function_exists( 'yith_ywgc_load_third_party_modules' ) ) {
	/**
	 * Start the plugin main class, backend and frontend
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_load_third_party_modules() {

		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			require_once( YITH_YWGC_DIR . 'lib/third-party/class.ywgc-AeliaCS-module.php' );
		}
		if ( defined( 'YITH_WPV_PREMIUM' ) ) {
			require_once( YITH_YWGC_DIR . 'lib/third-party/class.ywgc-multi-vendor-module.php' );
		}
	}
}

if ( ! function_exists( 'yith_gift_cards_start' ) ) {
	/**
	 * Start the plugin main class, backend and frontend
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_gift_cards_start() {
		//  Start main class
		$main = YITH_YWGC();

		//  Init the backend
		$backend           = YITH_WooCommerce_Gift_Cards_Backend_Premium::get_instance();
		$main->admin       = $backend;
		$main->admin->main = $main;

		//  Init the frontend
		$frontend             = YITH_WooCommerce_Gift_Cards_Frontend_Premium::get_instance();
		$main->frontend       = $frontend;
		$main->frontend->main = $main;
	}
}

if ( ! function_exists( 'yith_ywgc_premium_install' ) ) {
	/**
	 * Install the premium plugin
	 *
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function yith_ywgc_premium_install() {

		if ( ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', 'yith_ywgc_premium_install_woocommerce_admin_notice' );
		} else {
			do_action( 'yith_ywgc_premium_init' );
		}
	}
}

add_action( 'plugins_loaded', 'yith_ywgc_premium_install', 11 );

//  start the scheduling of gift cards
register_activation_hook( YITH_YWGC_FILE, 'YITH_WooCommerce_Gift_Cards_Backend_Premium::start_gift_cards_scheduling' );
register_deactivation_hook( YITH_YWGC_FILE, 'YITH_WooCommerce_Gift_Cards_Backend_Premium::end_gift_cards_scheduling' );