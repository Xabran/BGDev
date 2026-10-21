<?php

/*
Plugin Name: WP Fusion
Description: WP Fusion connects your website to your CRM, with support for several CRMs and dozens of plugins.
Plugin URI: https://wpfusionplugin.com/
Version: 2.7.2
Author: Very Good Plugins
Author URI: http://verygoodplugins.com/
Text Domain: wp-fusion

*/

/**
 * @copyright Copyright (c) 2016. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 *
 */

define( 'WP_FUSION_VERSION', '2.7.2' );

// deny direct access
if(!function_exists('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


final class WP_Fusion {

	/** Singleton *************************************************************/

	/**
	 * @var WP_Fusion The one true WP_Fusion
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * The integrations handler instance variable
	 *
	 * @var WPF_Integrations
	 * @since 2.0
	 */
	public $integrations;


	/**
	 * Manages configured CRMs
	 *
	 * @var WPF_CRMS
	 * @since 2.0
	 */
	public $crm_base;


	/**
	 * Access to the currently selected CRM
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $crm;


	/**
	 * Handler for AJAX and and asynchronous functions
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $ajax;


	/**
	 * User handler - registration, sync, and updates
	 *
	 * @var WPF_User
	 * @since 2.0
	 */
	public $user;


	/**
	 * Stores configured admin meta boxes and settings
	 *
	 * @var WPF_Meta_Boxes
	 * @since 2.0
	 */
	public $meta_boxes;


	/**
	 * The settings instance variable
	 *
	 * @var WP_Fusion_Settings
	 * @since 1.0
	 */
	public $settings;


	/**
	 * Main Wp_Fusion Instance
	 *
	 * Insures that only one instance of WP_Fusion exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @return The one true WP_Fusion
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Fusion ) ) {

			self::$instance = new WP_Fusion;
			self::$instance->setup_constants();
			self::$instance->includes();

			// Create settings
			self::$instance->settings 		= new WPF_Settings;

			// Load active CRM
			self::$instance->crm_base 		= new WPF_CRM_Base;
			self::$instance->crm 			= self::$instance->crm_base->crm;

			// Only useful if a CRM is selected and valid
			if(!empty(self::$instance->crm)) {

				self::$instance->setup_crm_constants();
				self::$instance->integrations_includes();

				self::$instance->user 		= new WPF_User;
				self::$instance->access 	= new WPF_Access_Control;
				self::$instance->ajax 		= new WPF_AJAX;
				
				self::$instance->updater();

			}

		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_constants() {

		if(!defined('WPF_MIN_WP_VERSION')) {
			define('WPF_MIN_WP_VERSION', '4.0');
		}

		if(!defined('WPF_DIR_PATH')) {
			define('WPF_DIR_PATH', plugin_dir_path(__FILE__));
		}

		if(!defined('WPF_PLUGIN_PATH')) {
			define('WPF_PLUGIN_PATH', plugin_basename(__FILE__));
		}

		if(!defined('WPF_DIR_URL')) {
			define('WPF_DIR_URL', plugin_dir_url(__FILE__));
		}

		if(!defined('WPF_STORE_URL')) {
			define( 'WPF_STORE_URL', 'http://wpfusionplugin.com' );
		}

	}

	/**
	 * Setup CRM related constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_crm_constants() {

		if(!defined('WPF_CRM_NAME')) {
			define('WPF_CRM_NAME', self::$instance->crm->name);
		}

	}


	/**
	 * Defines default supported plugin integrations
	 *
	 * @access private
	 * @return array Integrations
	 */

	public function get_integrations() {

		return apply_filters( 'wpf_integrations', array(
			'edd'          		=> 'Easy Digital Downloads',
			'edd-recurring'		=> 'EDD Recurring Payments',
			'gforms' 			=> 'Gravity Forms',
			'woocommerce'  		=> 'WooCommerce',
			'woo-subscriptions' => 'WooCommerce Subscriptions',
			'um'				=> 'Ultimate Member',
			'userpro'			=> 'UserPro',
			'acf'				=> 'Advanced Custom Fields',
			'learndash'			=> 'LearnDash',
			'sensei'			=> 'Sensei',
			'bbpress'			=> 'bbPress',
			'cf7' 				=> 'Contact form 7',
			'membermouse' 		=> 'MemberMouse',
			'buddypress' 		=> 'BuddyPress',
			'pmp'				=> 'Paid Memberships Pro',
			'restrict-content-pro' => 'Restrict Content Pro',
			'lifterlms'			=> 'LifterLMS',
			's2member'			=> 'S2Member',
			'affiliate-wp'		=> 'AffiliateWP'
		) );

	}

	/**
	 * Defines supported CRMs
	 *
	 * @access private
	 * @return array CRMS
	 */

	public function get_crms() {

		return apply_filters( 'wpf_crms', array(
			'infusionsoft'				=> 'WPF_Infusionsoft_iSDK',
			'activecampaign'			=> 'WPF_ActiveCampaign', 
			'ontraport'					=> 'WPF_Ontraport',
			'drip'						=> 'WPF_Drip'
		) );

	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @return void
	 */

	private function includes() {

		require_once WPF_DIR_PATH .'includes/admin/class-settings.php';

		if(is_admin()) {

			require_once WPF_DIR_PATH .'includes/admin/class-notices.php';
			require_once WPF_DIR_PATH .'includes/admin/admin-functions.php';

		}

		// Autoload CRMs
		require_once WPF_DIR_PATH .'includes/crms/class-base.php';

		foreach( $this->get_crms() as $filename => $integration ) {
			if( file_exists( WPF_DIR_PATH . 'includes/crms/' . $filename . '/class-' . $filename . '.php' ) ) {
				require_once WPF_DIR_PATH . 'includes/crms/' . $filename . '/class-' . $filename . '.php';
			}
		}

	}

	/**
	 * Includes classes applicable for after the connection is configured
	 *
	 * @access private
	 * @return void
	 */

	private function integrations_includes() {

		require_once WPF_DIR_PATH . 'includes/class-user.php';
		require_once WPF_DIR_PATH . 'includes/class-api.php';
		require_once WPF_DIR_PATH . 'includes/class-ajax.php';
		require_once WPF_DIR_PATH . 'includes/class-access-control.php';

		if(is_admin()) {

			require_once WPF_DIR_PATH . 'includes/admin/class-updater.php';
			require_once WPF_DIR_PATH . 'includes/admin/class-meta-boxes.php';
			require_once WPF_DIR_PATH . 'includes/admin/class-user-profile.php';

			self::$instance->meta_boxes = new WPF_Meta_Boxes;

		} else {

			require_once WPF_DIR_PATH . 'includes/admin/class-admin-bar.php';
			require_once WPF_DIR_PATH . 'includes/class-shortcodes.php';

		}

		// Autoload integrations
		require_once WPF_DIR_PATH . 'includes/integrations/class-base.php';

		foreach( $this->get_integrations() as $filename => $integration ) {
			if( file_exists( WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
				require_once WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
			}
		}


	}

	/**
	 * Set up EDD updater
	 *
	 * @access public
	 * @return void
	 */

	public function updater() {

		if( ! is_admin() )
			return;

		$license_key = $this->settings->get( 'license_key' );
		$license_status = $this->settings->edd_check_license($license_key);

		if($license_status == 'valid') {
			
			// setup the updater
			$edd_updater = new WPF_Plugin_Updater( WPF_STORE_URL, __FILE__, array( 
					'version' 	=> WP_FUSION_VERSION, 		// current version number
					'license' 	=> $license_key, 			// license key (used get_option above to retrieve from DB)
					'item_name' => 'WP Fusion', 			// name of this plugin
					'author' 	=> 'Very Good Plugins'  	// author of this plugin
				)
			);

		} else {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {
				add_action("after_plugin_row_" . WPF_PLUGIN_PATH, array( self::$instance, 'wpf_update_message' ), 10, 3);
			}
		}

	}

	/**
	 * Display update message
	 *
	 * @access public
	 * @return void
	 */

	public function wpf_update_message( $plugin_file, $plugin_data, $status ) {

		echo '<tr class="plugin-update-tr">';
			echo '<td colspan="3" class="plugin-update colspanchange">';
				echo '<div class="update-message notice inline notice-warning notice-alt">';
					echo '<p>Your WP Fusion License key is currently inactive or expired. <a href="' . get_admin_url() . './options-general.php?page=wpf-settings#setup">Activate your license key</a> or <a href="http://wpfusionplugin.com/" target="_blank">purchase a license</a> to enable automatic updates and support.</p>';
				echo '</div>';
			echo '</td>';
		echo '</tr>';

	}


	/*
	//
	//
	// DEPRECATED FUNCTIONS
	//
	//
	*/

	/**
	 * Logs error, to PHP error log or screen if WP_DEBUG is on
	 *
	 * @access private
	 * @return mixed
	 */

	private function log_deprecated( $function, $new_function ) {

		if ( WP_DEBUG ) {
			trigger_error( sprintf( __( 'Error: The function wp_fusion()->%1$s() was <strong>deprecated</strong> in WP Fusion 2.0. Please use %2$s instead.' ), $function, $new_function ) );
		} else {
			error_log( 'Error: The function wp_fusion()->' . $function . '() was deprecated in WP Fusion 2.0. Please use ' . $new_function . ' instead.');
		}

	}


	/**
	 * Connects to CRM (deprecated)
	 *
	 * @access public
	 * @return bool Whether or not connection succeeded
	 */

	public function connect() {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->crm->connect()');
		return wp_fusion()->crm->connect();

	}

	/**
	 * Get contact ID (deprecated)
	 *
	 * @access public
	 * @return int Contact ID for user
	 */

	public function get_contact_id( $user_id, $force_update = false) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->get_contact_id()');
		return wp_fusion()->user->get_contact_id( $user_id, $force_update );

	}


	/**
	 * Updates a contact based on ID (deprecated)
	 *
	 * @access public
	 * @return bool Success or failure
	 */

	public function update_contact( $contact_id, $update_data ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->crm->update_contact()');
		return wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

	}


	/**
	 * Updates a single meta field (deprecated)
	 *
	 * @access public
	 * @return bool Success or failure
	 */

	public function push_meta_data_single($meta_id, $user_id, $meta_key, $_meta_value ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->push_user_meta()');
		return wp_fusion()->crm->push_user_meta( $user_id, array( $meta_key => $_meta_value ) );

	}

	/**
	 * Gets tags for a contact (deprecated)
	 *
	 * @access public
	 * @return array Applied tags
	 */

	public function get_tags( $user_id, $force_update = false) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->get_tags()');
		return wp_fusion()->user->get_tags( $user_id, $force_update );

	}

	/**
	 * Applies tags to a contact (deprecated)
	 *
	 * @access public
	 * @return bool Success or failure
	 */

	public function apply_tags( $tags, $user_id ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->apply_tags()');
		return wp_fusion()->user->apply_tags( $tags, $user_id );

	}

	/**
	 * Gets tags for a contact (deprecated)
	 *
	 * @access public
	 * @return bool Whether or not the user has the tag
	 */

	public function has_tag( $tag_id, $user_id ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->has_tag()');
		return wp_fusion()->user->has_tag( $tag_id, $user_id );

	}

	/**
	 * Create user (deprecated)
	 *
	 * @access public
	 * @return int Contact ID for new user
	 */

	public function create_user( $user_id ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->user_register()');
		return wp_fusion()->user->user_register( $user_id );

	}

	/**
	 * Import user (deprecated)
	 *
	 * @access public
	 * @return int Contact ID for new user
	 */

	public function import_user( $contact_id, $send_notification ) {

		self::$instance->log_deprecated(__FUNCTION__, 'wp_fusion()->user->import_user()');
		return wp_fusion()->user->import_user( $contact_id, $send_notification );

	}

}


/**
 * The main function responsible for returning the one true WP Fusion
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $wpf = wp_fusion(); ?>
 *
 * @return object The one true WP Fusion Instance
 */

function wp_fusion() {
	return WP_Fusion::instance();
}

// Get WP Fusion Running
wp_fusion();