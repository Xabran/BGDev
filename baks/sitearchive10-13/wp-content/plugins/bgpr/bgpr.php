<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://boardgames.com
 * @since             1.0.0
 * @package           Bgpr
 *
 * @wordpress-plugin
 * Plugin Name:       BGPR
 * Plugin URI:        bgpr
 * Description:       This plugin is used for BGPR Reward System.
 * Version:           1.0.0
 * Author:            BoardGames
 * Author URI:        http://boardgames.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bgpr
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bgpr-activator.php
 */
function activate_bgpr() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bgpr-activator.php';
	Bgpr_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bgpr-deactivator.php
 */
function deactivate_bgpr() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bgpr-deactivator.php';
	Bgpr_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_bgpr' );
register_deactivation_hook( __FILE__, 'deactivate_bgpr' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bgpr.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bgpr() {


	$plugin = new Bgpr();
	$plugin->run();

	if(!session_id()) {
        session_start();
    }

}
define( 'BGPR_ROOT_PATH', plugin_dir_path( __FILE__ ) );
run_bgpr();
