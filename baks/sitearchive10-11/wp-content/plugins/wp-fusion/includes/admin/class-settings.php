<?php

class WPF_Settings {

	/**
	 * Contains all plugin settings
	*/
	
	public $options;


	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/

	public function __construct() {

		$this->options = get_option( 'wpf_options', array() );

		if(is_admin())
			$this->init();

	}

	/**
	 * Fires up settings in admin
	 *
	 * @since 1.0
	 * @return void
	*/

	private function init() {

		$this->includes();

		// Custom fields 
		add_action( 'show_field_api_validate', array($this, 'show_field_api_validate'), 10, 2 );
		add_action( 'show_field_contact_fields', array($this, 'show_field_contact_fields'), 10, 2 );
		add_action( 'show_field_contact_fields_begin', array($this, 'show_field_contact_fields_begin'), 10, 2);
		add_action( 'show_field_assign_tags', array($this, 'show_field_assign_tags'), 10, 2);
		add_action( 'show_field_import_users', array($this, 'show_field_import_users'), 10, 2);
		add_action( 'show_field_import_users_end', array($this, 'show_field_import_users_end'), 10, 2);
		add_action( 'show_field_import_groups', array($this, 'show_field_import_groups'), 10, 2);
		add_action( 'show_field_export_options', array($this, 'show_field_export_options'), 10, 2);
		add_action( 'show_field_edd_license_begin', array($this, 'show_field_edd_license_begin'), 10, 2);
		add_action( 'show_field_edd_license', array($this, 'show_field_edd_license'), 10, 2);
		add_action( 'show_field_crm_field', array($this, 'show_field_crm_field'), 10, 2);

		// AJAX
		add_action( 'wp_ajax_sync_tags', array($this, 'sync_tags') );
		add_action( 'wp_ajax_sync_custom_fields', array($this, 'sync_custom_fields') );
		add_action( 'wp_ajax_import_users', array($this, 'import_users') );
		add_action( 'wp_ajax_delete_import_group', array($this, 'delete_import_group') );
		add_action( 'wp_ajax_edd_activate', array($this, 'edd_activate') );
		add_action( 'wp_ajax_edd_deactivate', array($this, 'edd_deactivate') );

		// Setup scripts and initialize
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );
		add_filter( 'wpf_configure_settings', array($this, 'configure_settings'), 10, 2 );
		add_filter( 'wpf_configure_sections', array($this, 'configure_sections'), 30, 2 );
		add_filter( 'wpf_initialize_options', array($this, 'initialize_options'));
		add_action( 'wpf_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Validation
		add_filter( 'validate_field_contact_fields', array($this, 'validate_field_contact_fields'), 10, 2);

		// Plugin action links and messages
		add_filter( 'plugin_action_links_' . WPF_PLUGIN_PATH, array($this, 'add_action_links') );

		// Fire up the options framework
		new WPF_Options( $this->get_setup(), $this->get_settings(), $this->get_sections() );

		// Load batch processing class
		new WPF_Batch();

	}

	/**
	 * Include options library
	 *
	 * @since 1.0
	 * @return void
	*/


	private function includes() {

		require_once WPF_DIR_PATH . 'includes/admin/options/class-options.php';
		require_once WPF_DIR_PATH . 'includes/admin/class-batch.php';

	}


	/**
	 * Get the value of a specific setting
	 *
	 * @since 1.0
	 * @return mixed
	*/
	public function get( $key, $default = false ) {
		$value = ! empty( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		return $value;
	}


	/**
	 * Set the value of a specific setting
	 *
	 * @since 1.0
	 * @return voic
	*/
	public function set( $key, $value ) {

		$this->options[$key] = $value;
		update_option( 'wpf_options', $this->options );

	}

	/**
	 * Get all settings
	 *
	 * @since 1.0
	 * @return array
	*/
	public function get_all() {
		return $this->options;
	}

	/**
	 * Allows setting global settings during runtime
	 *
	 * @since 1.0
	 * @return array
	*/
	public function set_all( $options ) {
		$this->options = $options;
		update_option( 'wpf_options', $options );
	}

	/**
	 * Utility function for adding a setting before an existing setting
	 *
	 * @since 1.0
	 * @return array
	*/

	public function insert_setting_before($key, array $array, $new_key, $new_value = null) {
		
	    if (array_key_exists($key, $array)) {
	        $new = array();
	        foreach($array as $k => $value) {
	            if ($k === $key) {
	                if (is_array($new_key) && count($new_key) > 0) {
	                    $new = array_merge($new, $new_key);
	                } else {
	                    $new[$new_key] = $new_value;
	                }
	            }
	            $new[$k] = $value;
	        }
	        return $new;
	    }

	    return false;
	}


	/**
	 * Utility function for adding a setting after an existing setting
	 *
	 * @since 1.0
	 * @return array
	*/

	public function insert_setting_after($key, array $array, $new_key, $new_value = null) {
	 
	    if (array_key_exists($key, $array)) {
	        $new = array();
	 
	        foreach($array as $k => $value) {
	            $new[$k] = $value;
	            if ($k === $key) {
	                if (is_array($new_key) && count($new_key) > 0) {
	                    $new = array_merge($new, $new_key);
	                } else {
	                    $new[$new_key] = $new_value;
	                }
	           }
	        }
	 
	        return $new;
	    }

	    return false;
	}

	/**
	 * Add settings link to plugin page
	 *
	 * @access public
	 * @return array Links
	 */

	public function add_action_links($links) {
		$links[] = '<a href="'. get_admin_url(null, 'options-general.php?page=wpf-settings') .'">Settings</a>';
		return $links;
	}

	/**
	 * Enqueue options page scripts
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_scripts() {

		// Style
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css', array(), WP_FUSION_VERSION);
		wp_enqueue_style( 'wpf-admin', WPF_DIR_URL . 'assets/css/wpf-admin.css', array(), WP_FUSION_VERSION);

		// Scripts
		wp_enqueue_script( 'wpf-options', WPF_DIR_URL . 'assets/js/wpf-options.js', array( 'jquery', 'select4' ), WP_FUSION_VERSION, true );
		wp_localize_script( 'wpf-options', 'wpf_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_script( 'wpf-admin', WPF_DIR_URL . 'assets/js/wpf-admin.js', array( 'jquery', 'select4' ), WP_FUSION_VERSION, true );
		wp_localize_script( 'wpf-admin', 'wpf', array( 'crm_supports' => wp_fusion()->crm->supports ) );

	}


	/**
	 * Filters out internal WordPress fields from showing up in syncable meta fields list and sets labels and types for built in fields
	 *
	 * @since 1.0
	 * @return array
	*/

	public function prepare_meta_fields( $meta_fields ) {

		$exclude_fields = array(
			'contact_id',
			'wpf_tags',
			'rich_editing',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'wp_capabilities',
			'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'session_tokens',
			'wp_dashboard_quick_press_last_post_id',
			'nav_menu_recently_edited',
			'managenav-menuscolumnshidden',
			'metaboxhidden_nav-menus',
			'unique_id',
			'profilepicture',
			'action',
			'group',
			'shortcode',
			'up_username',
			'meta-box-order_post',
			'screen_layout_post',
			'closedpostboxes_post',
			'metaboxhidden_post'
		);

		foreach( $exclude_fields as $field ) {
			if( isset( $meta_fields[$field] ) )
				unset( $meta_fields[$field] );
		}

		// Load the reference of standard WP field names and types
		include dirname(__FILE__) . '/wordpress-fields.php';

		// Sets field types and labels for all built in fields
		foreach( $wp_fields as $key => $data ) {

			$meta_fields[$key] = array(
				'label'	=> $data['label'],
				'type'	=> $data['type']
				);

		}

		return $meta_fields;

	}

	/**
	 * Sync tags
	 *
	 * @access public
	 * @return array New tags loaded from CRM
	 */

	public function sync_tags() {

		$available_tags = wp_fusion()->settings->get('available_tags');
		$new_tags = wp_fusion()->crm->sync_tags();

		foreach($new_tags as $id => $data) {
			if(!isset($available_tags[$id])) {
				echo '<option value="' . $id . '">' . wp_fusion()->user->get_tag_label( $id ) .'</option>';
			}
		}

		die();
	}


	/**
	 * Sync custom fields
	 *
	 * @access public
	 * @return array New custom fields loaded from CRM
	 */

	public function sync_custom_fields() {

		$crm_fields = wp_fusion()->settings->get('crm_fields');
		$new_fields = wp_fusion()->crm->sync_crm_fields();

		foreach($new_fields['Custom Fields'] as $id => $label) {
			if(!isset($crm_fields[$id])) {
				echo '<option value="' . $id . '">' . $label .'</option>';
			}
		}

		die();
	}


	/**
	 * Import contacts as new users based on params
	 *
	 * @access public
	 * @param params Criteria for import
	 * @param send_notification Whether or not to send welcome email
	 * @param role Role of the imported users
	 *
	 * @return contacts Count of contacts imported
	 */

	public function import_users() {

		$inputs = json_decode(stripslashes($_POST['params']));
		$send_notification = ($_POST['notify'] === 'true');
		$role = $_POST['role'];

		$params = array('tags' => $inputs->{'import_users'});
		$params = apply_filters( 'wpf_import_users_params', $params, $inputs );


		$imported_contacts = wp_fusion()->crm->load_contacts( $params );

		// If no contacts matched
		if(count($imported_contacts) == 0) {
			echo '0';
			die();
		}

		// Keep track of import groups so they can be removed later

		$import_groups = get_option('wpf_import_groups', array());

		$time = current_time( 'timestamp' );
		$import_groups[$time] = array(
				'params'	=> $inputs,
				'user_ids'	=> array(),
				'role'		=> $role
			);

		foreach($imported_contacts as $contact_id => $user_meta) {

			// Check if email already exists
			$user = get_user_by( 'email', $user_meta['user_email'] );

			// Don't count existing users towards our total import count
			if(is_object($user)) {
				unset($imported_contacts[$contact_id]);
				wp_fusion()->user->get_tags($user->ID, true);
				wp_fusion()->user->pull_user_meta($user->ID);
				continue;
			}

			if(!isset($user_meta['user_login']))
				$user_meta['user_login'] = $user_meta['user_email'];

			// Generate a password if one hasn't been supplied
			if(!isset($user_meta['user_pass']) || empty($user_meta['user_pass']))
				$user_meta['user_pass'] = wp_generate_password( $length = 12, $include_standard_special_chars = false );

			// Set contact ID
			$user_meta[wp_fusion()->crm->slug . '_contact_id'] = $contact_id;

			$user_meta = apply_filters('wpf_import_user', $user_meta );

			// Prevent the default registration hook from running
			remove_action( 'user_register', array(wp_fusion()->user, 'user_register') );

			// Insert user and store meta
			$user_id = wp_insert_user( $user_meta );
			wp_fusion()->user->get_tags($user_id, true);
			wp_fusion()->user->pull_user_meta($user_id);

			// Save user id to import group
			$import_groups[$time]['user_ids'][] = $user_id;

			if($send_notification == true)
				wp_new_user_notification( $user_id, null, 'both' );

			do_action( 'wpf_user_imported', $user_id, $user_meta );

		}

		if(count($imported_contacts) > 0)
			update_option('wpf_import_groups', $import_groups);

		// Send back a count of imported contacts
		echo count($imported_contacts);

		die();

	}


	/**
	 * Deletes a previously imported group of contacts
	 *
	 * @access public
	 * @return bool
	 */

	public function delete_import_group() {

		$import_group = $_POST['group_id'];
		$import_groups = get_option('wpf_import_groups');

		global $current_user;

		foreach($import_groups[$import_group]['user_ids'] as $user_id) {

			wp_delete_user($user_id, $current_user->ID);

		}

		unset($import_groups[$import_group]);
		update_option('wpf_import_groups', $import_groups);
		wp_send_json_success();

		die();

	}


	/**
	 * Check EDD license
	 *
	 * @access public
	 * @return bool
	 */

	public function edd_check_license($license_key) {

		$status = get_transient( 'wpf_license_check' );

		// Run the license check a maximum of once per day
		if( false === $status ) {

			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'check_license',
				'license' 	=> $license_key,
				'item_name' => urlencode( 'WP Fusion' ),
				'url'       => home_url()
			);
			// Call the custom API.
			$response = wp_remote_post( WPF_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$this->set('license_status', $license_data->license);

			set_transient( 'wpf_license_check', true, 60*60*24*3 );

			return $license_data->license;

		} else {

			// Return stored license data
			return $this->get('license_status');

		}

	}


	/**
	 * Activate EDD license
	 *
	 * @access public
	 * @return bool
	 */

	public function edd_activate() {

		$license_key = trim($_POST['key']);

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'activate_license', 
			'license' 	=> $license_key, 
			'item_name' => urlencode( 'WP Fusion' ), // the name of our product in EDD
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( WPF_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() . '&ndash; Please <a href="https://wpfusionplugin.com/support/contact/" target="_blank">contact support</a> for further assistance.' );
			die();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// $license_data->license will be either "valid" or "invalid"

		// Store the options locally
		$this->set('license_status', $license_data->license );
		$this->set('license_key', $license_key );

		if($license_data->license == 'valid') {
			wp_send_json_success('activated');
		} else {
			wp_send_json_error( '<pre>' . print_r($license_data, true) . '</pre>' );
		}

		die();
	}


	/**
	 * Deactivate EDD license
	 *
	 * @access public
	 * @return bool
	 */

	public function edd_deactivate() {

		$license_key = trim($_POST['key']);

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'deactivate_license', 
			'license' 	=> $license_key, 
			'item_name' => urlencode( 'WP Fusion' ), // the name of our product in EDD
			'url'       => home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_post( WPF_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
			die();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if($license_data->license == 'deactivated') {
			$this->set('license_status', 'invalid');
			wp_send_json_success('deactivated');
		} else {
			wp_send_json_error( '<pre>' . print_r($license_data, true) . '</pre>' );
		}

		wp_die();
	}


	/**
	 * Set up options page
	 *
	 * @access public
	 * @return void
	 */

	private function get_setup() {
		return array(
			 'project_name' => 'WP Fusion',
			 'project_slug' => 'wpf',
			 'menu'         => 'settings',
			 'page_title'   => "WP Fusion Settings",
			 'menu_title'   => "WP Fusion",
			 'capability'   => 'manage_options',
			 'option_group' => 'wpf_options',
			 'slug'         => 'wpf-settings',
			 'page_icon'	=> 'tools'
		);
	}


	/**
	 * Get options page sections
	 *
	 * @access private
	 * @return void
	 */

	private function get_sections() {

		$sections = array();

		$sections['wpf-settings']['main'] = __('General Settings', 'wp-fusion' );
		$sections['wpf-settings']['contact-fields'] = __('Contact Fields', 'wp-fusion' );

		$sections['wpf-settings']['import'] = __('Import Users', 'wp-fusion' );
		$sections['wpf-settings']['batch-process'] = __('Batch Process', 'wp-fusion' );
		$sections['wpf-settings']['setup'] = __('Setup', 'wp-fusion' );
		$sections['wpf-settings']['reset'] = __('Reset to Defaults', 'wp-fusion' );


		return $sections;

	}


	/**
	 * Configure visible sections
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections($page, $options) {

		// If no connection configured, only show setup page
		// if($options['connection_configured'] == false) {
		// 	$page['sections'] = array('setup' => 'Setup');
		// }

		return $page;

	}


	/**
	 * Initialize settings to default values
	 *
	 * @access public
	 * @return array
	 */

	public function initialize_options( $options ) {

		// Access Key
		if(empty($options['access_key']))
			$options['access_key'] = substr(md5(microtime() . rand()), 0, 8);

		// Contact fields
		global $current_user;
		$meta_fields = get_user_meta($current_user->ID);

		// Manually add password/username/email fields
		$meta_fields["user_pass"] = "";
		$meta_fields["user_login"] = "";
		$meta_fields["user_email"] = "";

		// Remove hidden fields
		foreach($meta_fields as $key => $value) {
			if(substr( $key, 0, 1 ) === "_" || substr( $key, 0, 5 ) === "hide_" || substr( $key, 0, 3 ) === "wp_")
				unset($meta_fields[$key]);		
		}

		// These fields should be turned on by default
		if(!isset($options['contact_fields']['first_name']['active']))
			$options['contact_fields']['first_name']['active'] = true;

		if(!isset($options['contact_fields']['last_name']['active']))
			$options['contact_fields']['last_name']['active'] = true;

		if(!isset($options['contact_fields']['user_email']['active']))
			$options['contact_fields']['user_email']['active'] = true;

		// Set defaults for other meta fields
		foreach($meta_fields as $key => $value){

			// If key is new
			if(!isset($options['contact_fields'][$key]))
				$options['contact_fields'][$key] = array();

			// If active is empty
			if(!isset($options['contact_fields'][$key]['active']))
				$options['contact_fields'][$key]['active'] = 0;

		}

		return $options;

	}


	/**
	 * Define all available settings
	 *
	 * @access private
	 * @return void
	 */

	private function get_settings() {

		$settings = array();


		$settings['users_header'] = array(
				'title'   => __('Automatically Create Contact Records for New Users', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'main'
		);

		$settings['create_users'] = array(
				'title'   => __('Create Users', 'wp-fusion' ),
				'desc'    => __('Create new contacts when users are added in WordPress.', 'wp-fusion' ),
				'std'     => 1,
				'type'    => 'checkbox',
				'section' => 'main',
				'unlock'  => array('user_roles', 'assign_tags', 'opportunity_state')
		);


		$settings['user_roles'] = array(
				'title'   => __('User Roles', 'wp-fusion' ),
				'desc'    => __('You can choose to create new contacts only when users are added to a certain role. Leave blank for all roles.', 'wp-fusion' ),
				'type'    => 'multi_select',
				'choices' => array(),
				'placeholder' => 'Select user roles',
				'section' => 'main'
		);

		$settings['assign_tags'] = array(
				'title'   => __('Assign Tags', 'wp-fusion' ),
				'desc'    => __('The selected tags will be applied to new contacts.', 'wp-fusion' ),
				'type'    => 'multi_select',
				'choices' => array(),
				'section' => 'main'
		);


		/*
		// CONTACT DATA SYNC
		*/


		$settings['contact_sync_header'] = array(
				'title'   => __('Synchronize Contact Data', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'main'
		);

		$settings['push'] = array(
				'title'   => __('Push', 'wp-fusion' ),
				'desc'    => __('When a user profile is modified, update their contact record in your CRM to match.', 'wp-fusion' ),
				'std'     => 1,
				'type'    => 'checkbox',
				'section' => 'main'
		);

		$settings['push_all_meta'] = array(
				'title'   => __('Push All', 'wp-fusion' ),
				'desc'    => __('Push meta data whenever a single "user_meta" entry is added or modified.<br /><small>(Useful if using non-supported plugins or manual user_meta updates, but may result in duplicate API calls and slower performance)</small>', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'main'
		);

		/*
		// RESTRICT PAGE ACCESS
		*/

		$settings['restrict_access_header'] = array(
				'title'   => __('Restrict Page Access', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'main'
		);

		$settings['hide_restricted'] = array(
				'title'   => __('Hide Restricted Content', 'wp-fusion' ),
				'desc'    => __('Content that the user cannot access will be removed from menus.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'main'
		);

		$settings['exclude_admins'] = array(
				'title'   => __('Exclude Administrators', 'wp-fusion' ),
				'desc'    => __('Users with Administrator accounts will be able to view all content, regardless of restrictions.', 'wp-fusion' ),
				'std'     => 1,
				'type'    => 'checkbox',
				'section' => 'main'
		);

		$settings['default_redirect'] = array(
				'title'   => __('Default Redirect', 'wp-fusion' ),
				'desc'    => __('Default redirect URL for when access is denied. This can be overridden on a per-page basis. Leave blank to display error message below.', 'wp-fusion' ),
				'std'     => '',
				'type'    => 'text',
				'section' => 'main'
		);

		$settings['restricted_message'] = array(
				'title'   => __('Restricted Content Message', 'wp-fusion' ),
				'desc'    => __('Restricted content message for when a redirect hasn\'t been specified.', 'wp-fusion' ),
				'std'     => "<h2 style='text-align:center'>Oops!</h2><p style='text-align:center'>You don't have permission to view this page! Make sure you're logged in and try again, or contact support.</p>",
				'type'    => 'editor',
				'section' => 'main',
				'textarea_rows'	=> 10
		);

		/*
		// ACCESS KEY
		*/

		$settings['access_key_header'] = array(
				'title'   => __('Webhooks', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'main'
		);

		$settings['access_key_desc'] = array(
				'std'     => 0,
				'type'    => 'paragraph',
				'section' => 'main',
				'desc'	  => __('Webhooks allow you to send from your CRM back to your website. See <a href="http://wpfusionplugin.com/documentation/" target="_blank">our documentation</a> for more information on creating webhooks.', 'wp-fusion'),
		);

		$settings['access_key'] = array(
				'title' 	=> __('Access Key', 'wp-fusion' ),
				'desc'		=> __('You must use this key when sending data back to WP Fusion.', 'wp-fusion' ),
				'type'  	=> 'text',
				'section'	=> 'main'
		);

		$settings['return_password'] = array(
				'title' 	=> __('Return Password', 'wp-fusion' ),
				'desc'		=> __('Send new users\' passwords back to your CRM after import (field must be mapped in the <a href="#contact-fields">Contact Fields</a> settings).', 'wp-fusion' ),
				'type'  	=> 'checkbox',
				'section'	=> 'main'
		);

		/*
		// CONTACT FIELDS
		*/

		$settings['contact_fields'] = array(
				'title'   => __('Contact Fields', 'wp-fusion' ),
				'std'     => array(),
				'type'    => 'contact-fields',
				'section' => 'contact-fields',
				'choices' => array()
		);

		/*
		// IMPORT USERS
		*/

		$settings['import_users_p'] = array(
				'desc'		=> __('<div class="alert alert-info">This feature allows you to import your CRM contacts as new WordPress users. Any fields configured on the <strong>Contact Fields</strong> tab will be imported.</div>', 'wp-fusion' ),
				'type'		=> 'paragraph',
				'section'	=> 'import'
		);

		$settings['import_users'] = array(
				'title' 	=> __('Import Users', 'wp-fusion' ),
				'desc'  	=> __('Contacts with the selected tags will be imported as new users.', 'wp-fusion' ),
				'type'  	=> 'multi_select',
				'choices'	=> array(),
				'section'	=> 'import'
		);

		$settings['email_notifications'] = array(
				'title'   => __('Enable Notifications', 'wp-fusion' ),
				'desc'    => __('Send a <a href="https://wpfusionplugin.com/documentation/tutorials/import-users/" target="_blank">welcome email</a> to new users containing their username, password, and login link.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'import'
		);

		$settings['import_groups'] = array(
				'title'   => __('Import Groups', 'wp-fusion' ),
				'type'    => 'import_groups',
				'section' => 'import'
		);

		/*
		// EXPORT / BATCH PROCESS
		*/

		// $settings['export_p'] = array(
		// 		'desc'		=> __('<div class="alert alert-info">This feature allows you to import your CRM contacts as new WordPress users. Any fields configured on the <strong>Contact Fields</strong> tab will be imported.</div>', 'wp-fusion' ),
		// 		'type'		=> 'paragraph',
		// 		'section'	=> 'export'
		// );

		$settings['export_options'] = array(
				'title' 	=> __('Data to Batch Process', 'wp-fusion' ),
				'type'  	=> 'export_options',
				'choices'	=> array(),
				'section'	=> 'batch-process'
		);

		/*
		// API CONFIGURATION
		*/

		$settings['api_heading'] = array(
				'title'	  => __('API Configuration', 'wp-fusion' ),
				'section' => 'setup',
				'type'	  => 'heading'
			);

		$settings['crm'] = array(
				'title'	  => __('Select CRM', 'wp-fusion' ),
				'section' => 'setup',
				'type'	  => 'select',
				'placeholder' => 'Select your CRM',
				'allow_null' => false,
				'choices' => array()
			);

		$settings['connection_configured'] = array(
				'std'     => false,
				'type'    => 'hidden',
				'section' => 'setup'
		);

		$settings['license_heading'] = array(
				'title'	  => 'WP Fusion License',
				'section' => 'setup',
				'type'	  => 'heading'
			);

		$settings['license_key'] = array(
				'title'   => __('License Key', 'wp-fusion' ),
				'std'     => '',
				'type'    => 'edd_license',
				'section' => 'setup',
				'license_status' => 'invalid'
		);

		$settings['license_status'] = array(
				'type'    => 'hidden',
				'section' => 'setup',
				'std'	  => 'invalid'
		);

		/*
		// RESET
		*/

		$settings['reset_options'] = array(
				'title'   => __('Reset', 'wp-fusion' ),
				'desc'    => __('Check this box and click "Save Changes" below to reset all options to their defaults.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'reset',
				'section' => 'reset'
		);

		return $settings;

	}



	/**
	 * Configure available settings
	 *
	 * @access public
	 * @return array
	 */

	public function configure_settings( $settings, $options ) {

		// Lock the license field if the license is valid
		if(isset($options['license_status']) && $options['license_status'] == 'valid' && !empty($options['license_key'])) {
			$settings['license_key']['license_status'] = 'valid';
			$settings['license_key']['disabled'] = true;
		}

		// Everything else can be done after the API connection is set up
		if(isset($options['connection_configured']) && $options['connection_configured'] == true) {

			global $wp_roles;

			$settings['user_roles']['choices'] = $wp_roles->role_names;
			$settings['user_roles']['disabled'] = ($options['create_users'] == 0 ? true : false );

			$settings['assign_tags']['choices'] = $options['available_tags'];
			$settings['assign_tags']['disabled'] = ($options['create_users'] == 0 ? true : false );

			$settings['import_users']['choices'] = $options['available_tags'];

			if(get_option('wpf_import_groups') == false)
				$settings['import_groups']['type'] = 'hidden';

			// Disable CRM change after initial connection is configured
			$settings['crm']['disabled'] = true;
			$settings['crm']['desc'] = 'To change CRMs, please do a full reset of WP Fusion from the Reset tab.';

			$settings['contact_fields']['choices'] = apply_filters( 'wpf_meta_fields', $options['contact_fields'] );

		} else {

			// Enable CRM change after reset
			$settings['crm']['disabled'] = false;
			$settings['crm']['desc'] = '';

		}

		return $settings;

	}

	/**
	 * Shows API key field
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function show_field_api_validate($id, $field) {

		echo '<input id="' . $id . '" class="form-control '. $field['class'] .'" type="text" name="wpf_options[' . $id . ']" placeholder="'.$field['std'].'" value="' . esc_attr($this->options[$id]) . '">';
		
		$total_users = count_users();
		$total_users = $total_users['total_users'];
		$batch_size = apply_filters( 'wpf_sync_batch_size', 100 );

		if($this->options['connection_configured'] == true) {

			echo '<a id="test-connection" data-total-users="' . $total_users . '" data-batch-size="' . $batch_size . '" data-post-fields="' . implode(',', $field['post_fields']) . '" class="btn btn-success" data-toggle="tooltip" data-placement="right" title="Reload all custom fields and user tags from ' . wp_fusion()->crm->name . '">Resynchronize</a>';

		} else {

			echo '<a id="test-connection" data-total-users="' . $total_users . '" data-batch-size="' . $batch_size . '" data-post-fields="' . implode(',', $field['post_fields']) . '" class="btn btn-default">Test Connection</a>';

		}

	}

	/**
	 * Opens EDD license field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_edd_license_begin($id, $field) {
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="'.$id.'">'.$field['title'].'</label></th>';
		echo '<td>';
	}


	/**
	 * Displays EDD license field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_edd_license($id, $field) {

		echo '<input id="' . $id . '" class="form-control" type="text" name="wpf_options['.$id.']" placeholder="'.$field['std'].'" value="'.esc_attr($this->options[$id]).'" '.($field['disabled'] ? 'disabled="true"' : '').'>';
			
		if($field['license_status'] == "invalid") {
			echo '<a id="edd-license" data-action="edd_activate" class="btn btn-default">Activate License</a>';
		} else {
			echo '<a id="edd-license" data-action="edd_deactivate" class="btn btn-default">Deactivate License</a>';
		}
		echo '<span class="description">Enter your license key for automatic updates and support.</span>';
		echo '<div id="connection-output-edd"></div>';
	}

	/**
	 * Displays import users field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_users($id, $field) {

		if(empty($this->options[$id]))
			$this->options[$id] = array();

		wpf_render_tag_multiselect( $this->options[$id], 'wpf_options', $id, null, false, 'Select Tag', 1 );

		global $wp_roles;

		echo '<select class="select4" id="import_role" data-placeholder="Select a user role">';

			echo '<option></option>';

			foreach($wp_roles->role_names as $role => $name) {

				// Set subscriber as default
				echo '<option ' . selected( 'Subscriber', $name, false ) . ' value="' . $role . '">' . $name . '</option>';

			}

		echo '</select>';

		echo '<a id="import-users-btn" class="btn btn-default">Import</a>';

	}

	/**
	 * Close import users field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_users_end() {

		echo '</td></tr></table>';
		echo '<div id="import-output"></div>';
		echo '<table class="form-table">';

	}

	/**
	 * Shows assign tags field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_assign_tags($id, $field) {

		if(empty($this->options[$id]))
			$this->options[$id] = array();

		wpf_render_tag_multiselect( $this->options[$id], 'wpf_options', $id, null, $field['disabled'] );

	}


	/**
	 * Displays a single CRM field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_crm_field($id, $field) {

		if(empty($this->options[$id]))
			$this->options[$id] = array();

		wpf_render_crm_field_select( $this->options[$id]['crm_field'], 'wpf_options', $id );

	}

	/**
	 * Opening for contact fields table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_contact_fields_begin($id, $field) {
		echo '<tr valign="top"' . ($field['disabled'] ? ' class="disabled"' : '') . '>';
		echo '<td>';
	}


	/**
	 * Displays contact fields table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_contact_fields($id, $field) {

		echo '<table id="contact-fields" class="table table-hover">';

			echo '<thead>';
				echo '<tr>';
					echo '<th class="sync">Sync</th>';
					echo '<th>Name</th>';
					echo '<th>Meta Field</th>';
					echo '<th>Type</th>';
					echo '<th>' . wp_fusion()->crm->name . ' Field</th>';
				echo '</tr>';
			echo '</thead>';

			foreach($field['choices'] as $user_meta => $data) {

				// Allow hiding for internal fields
				if(isset($data['hidden']))
					continue;

				echo '<tr' . ($this->options[$id][$user_meta]['active'] == true ? ' class="success" ' : '') . '>';
					echo '<td><input class="checkbox contact-fields-checkbox"' . (empty($this->options[$id][$user_meta]['crm_field']) ? ' disabled' : '') . ' type="checkbox" id="wpf_cb_'.$user_meta.'" name="wpf_options['.$id.']['.$user_meta.'][active]" value="1" '.checked($this->options[$id][$user_meta]['active'], 1, FALSE).'/></td>';
					echo '<td class="wp_field_label">'. (isset($data['label']) ? $data['label'] : '') . '</td>';
					echo '<td><span class="label label-default">' . $user_meta . '</span></td>';
					echo '<td class="wp_field_type"><input id="wpf_is_'.$user_meta.'_type"class="form-control wpf_type disabled" disabled type="text" name="wpf_options['.$id.']['.$user_meta.'][type]" placeholder="" value="'.esc_attr((isset($data['type']) ? $data['type'] : 'text')).'"></td>';
					echo '<td>';
						wpf_render_crm_field_select( $this->options[$id][$user_meta]['crm_field'], 'wpf_options', 'contact_fields', $user_meta );
					echo '</td>';

				echo '</tr>';

			}

		echo '</table>';
	}


	/**
	 * Displays import groups table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_groups($id, $field) {

		$import_groups = get_option('wpf_import_groups');

		if($import_groups == false)
			return;

		echo '<table id="import-groups" class="table table-hover">';

			echo '<thead>';
				echo '<tr">';
					echo '<th class="import-date">Date</th>';
					echo '<th>Tag(s)</th>';
					echo '<th>Role</th>';
					echo '<th>Contacts</th>';
					echo '<th>Delete</th>';

				echo '</tr>';
			echo '</thead>';	

			foreach($import_groups as $date => $data) {

				// Fix for old import groups with dash in name
				if(isset($data['params']->{'import_users-tags'})) {
					$tags = $data['params']->{'import_users-tags'};
				} elseif(isset($data['params']->{'import_users'})) {
					$tags = $data['params']->{'import_users'};
				}

				if(isset($tags)) {

					$tag_labels = array();					

					foreach($tags as $id) {
						$tag_labels[] = wp_fusion()->user->get_tag_label($id);
					}

				} else {
					$tags = 'All Contacts';
				}

				global $wp_roles;

				echo '<tr class="import-group-row">';
					echo '<td class="import-date">' . date('n/j/Y g:ia', $date) . '<div class="progress-bar progress-bar-striped progress-bar-danger active"></div></td>';
					echo '<td>' . implode(', ', $tag_labels) .'</td>';
					echo '<td>' . (isset($data['role']) ? $wp_roles->roles[$data['role']]['name'] : '') . '</td>';
					echo '<td>' . count($data['user_ids']) . '</td>';
					echo '<td>';
						echo '<a class="delete-import-group btn btn-default btn-sm" data-delete="' . $date .'">Delete</a>';
					echo '</td>';
				echo '</tr>';

			}

		echo '</table>';
	}


	/**
	 * Displays export options
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_export_options($id, $field) {

		$options = array(
						'users' => array( 
							'label' 	=> 'Users and User Meta',
							'title' 	=> 'Users',
							'batch_size' => 10
						),
					);

		$options = apply_filters( 'wpf_export_options', $options );

		foreach($options as $key => $data) {

			echo '<input class="checkbox export-options" data-batch-size="' . $data['batch_size'] . '" data-title="' . $data['title'] . '" type="checkbox" id="export_' . $key . '" name="export_options[]" value="' . $key . '" />';
			echo '<label for="export_' . $key . '">' . $data['label'] . '</label><br />';

		}

		echo '<br /><br /><a id="export-btn" class="btn btn-default">Run Batch Operations</a>';

	}


	/**
	 * Validation for contact field data
	 *
	 * @access public
	 * @return mixed
	 */

	public function validate_field_contact_fields($input, $setting) {
		
		foreach($input as $field => $data) {

			if(!isset($data['active']))
				$input[$field]['active'] = 0;
		}

		return $input;

	}

}