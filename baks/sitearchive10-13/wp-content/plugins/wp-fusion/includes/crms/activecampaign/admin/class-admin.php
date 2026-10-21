<?php

class WPF_ActiveCampaign_Admin {

	private $slug;
	private $name;
	private $crm;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function __construct($slug, $name, $crm) {

		$this->slug = $slug;
		$this->name = $name;
		$this->crm = $crm;

		// Settings
		add_filter( 'wpf_configure_settings', array( $this, 'register_connection_settings' ), 15, 2 );
		add_action( 'show_field_activecampaign_header_begin', array($this, 'show_field_activecampaign_header_begin' ), 10, 2);
		add_action( 'show_field_ac_key_end', array($this, 'show_field_ac_key_end' ), 10, 2);

		// AJAX
		add_action( 'wp_ajax_wpf_test_connection_' . $this->slug, array($this, 'test_connection') );

		if(wp_fusion()->settings->get('crm') == $this->slug)
			$this->init();

	}

	/**
	 * Hooks to run when this CRM is selected as active
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function init() {

		add_filter( 'wpf_initialize_options', array( $this, 'add_default_fields' ), 10 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 10, 2 );
		add_action( 'wpf_resync_contact', array( $this, 'resync_lists' ));

	}


	/**
	 * Loads ActiveCampaign connection information on settings page
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function register_connection_settings( $settings, $options ) {

		$new_settings = array();

		$new_settings['activecampaign_header'] = array(
			'title'   => __('ActiveCampaign Configuration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'setup'
		);

		$new_settings['ac_url'] = array(
			'title'   => __('API URL', 'wp-fusion' ),
			'desc'    => __('Enter the API URL for your ActiveCampaign account (find it under Settings >> Developer in your account).', 'wp-fusion' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'setup'
		);

		$new_settings['ac_key'] = array(
			'title'   => __('API Key', 'wp-fusion' ),
			'desc'    => __('The API key will appear beneath the API URL on the Developer settings page.', 'wp-fusion' ),
			'type'    => 'api_validate',
			'section' => 'setup',
			'class'	  => 'api_key',
			'post_fields' => array('ac_url', 'ac_key')
		);

		$settings = wp_fusion()->settings->insert_setting_after('crm', $settings, $new_settings);

		return $settings;

	}

	/**
	 * Loads ActiveCampaign specific settings fields
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function register_settings( $settings, $options ) {

		$new_settings['ac_lists'] = array(
				'title'   => __('Lists', 'wp-fusion' ),
				'desc'    => __('New users will be automatically added to the selected lists.', 'wp-fusion' ),
				'type'    => 'multi_select',
				'placeholder' => 'Select lists',
				'section' => 'main',
				'choices' => $options['available_lists']
		);

		$settings = wp_fusion()->settings->insert_setting_after('assign_tags', $settings, $new_settings);

		if(!isset($settings['create_users']['unlock']['ac_lists']))
			$settings['create_users']['unlock'][] = 'ac_lists';
		
		$settings['ac_lists']['disabled'] = (wp_fusion()->settings->get('create_users') == 0 ? true : false );

		// Remove import settings and replace with notice
		// unset($settings['import_users']);
		// unset($settings['email_notifications']);
		// unset($settings['import_groups']);

		// $settings['import_users_p']['desc'] = '<div class="alert alert-info"><strong>Note:</strong> We\'re currently waiting on improvements to the ActiveCampaign API that will allow us to fetch users by tag. In the meantime, please follow the steps below to import users:</div>';

		// $settings['import_users_p']['desc'] .= '<ul style="font-size: 14px; line-height: 26px;">';
		// $settings['import_users_p']['desc'] .= '<li><strong>1.</strong> Go to the Automations tab in your ActiveCampaign account and create a new blank automation.</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>2.</strong> When the "How will a contact enter your automation?" popup appears, click on "Or don\'t have a trigger for your automation" a the bottom of the window.</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>3.</strong> Add a new action, and choose <strong>Webhook</strong> from from the <strong>Conditions and Workflow</strong> menu.</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>4.</strong> In the URL field, put <strong>' . site_url() . '/?wpf_action=add&access_key=' . wp_fusion()->settings->get('access_key') . '</strong>. (For more information on webhooks <a href="https://wpfusionplugin.com/documentation/tutorials/activecampaign-webhooks/" target="_blank">see our documentation</a>)</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>5.</strong> Make the automation active by clicking <strong>Active</strong> at the top right.</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>6.</strong> Go to the Contacts tab and select the contacts you\'d like to import (either by tag, list, or other criteria), and click <strong>Edit >> All Selected</strong>.</li>';
		// $settings['import_users_p']['desc'] .= '<li><strong>7.</strong> Click <strong>Add To Automation</strong> and select the automation you just created. Click Apply Changes.</li>';
		// $settings['import_users_p']['desc'] .= '</ul>';

		// $settings['import_users_p']['desc'] .= '<br /><p style="font-size: 14px;">ActiveCampaign will now trigger WP Fusion via Webhook to import all of the selected contacts as new users on your site.<br />It\'s recommended to test this with your own contact record first to make sure everything works properly.</p>';

		return $settings;

	}


	/**
	 * Loads standard ActiveCampaign field names and attempts to match them up with standard local ones
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function add_default_fields( $options ) {

		if($options['connection_configured'] == true) {

			require_once dirname(__FILE__) . '/activecampaign-fields.php';

			foreach($options['contact_fields'] as $field => $data) {

				if(isset($activecampaign_fields[$field])) {
					$options['contact_fields'][$field] = array_merge($options['contact_fields'][$field], $activecampaign_fields[$field]);
				}

			}

		}

		return $options;

	}


	/**
	 * Puts a div around the Infusionsoft configuration section so it can be toggled
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function show_field_activecampaign_header_begin( $id, $field ) {

		echo '</table>';
		$crm = wp_fusion()->settings->get('crm');
		echo '<div id="' . $this->slug . '" class="crm-config ' . ($crm == false || $crm != $this->slug ? 'hidden' : 'crm-active') . '" data-name="' . $this->name .'" data-crm="' . $this->slug .'">';

	}

	/**
	 * Close out Active Campaign section
	 *
	 * @access  public
	 * @since   1.0
	*/


	public function show_field_ac_key_end($id, $field) {

		if($field['desc'] != '') {
			echo '<span class="description">'.$field['desc'].'</span>';
		}
		echo '</td>';
		echo '</tr>';

		echo '</table><div id="connection-output"></div>';
		echo '</div>'; // close #activecampaign div
		echo '<table class="form-table">';
		
	}

	/**
	 * Verify connection credentials
	 *
	 * @access public
	 * @return bool
	 */

	public function test_connection() {

		$api_url = $_POST['ac_url'];
		$api_key = $_POST['ac_key'];

		$connection = $this->crm->connect($api_url, $api_key, true);

		if( is_wp_error( $connection ) ) {

			wp_send_json_error( $connection->get_error_message() );
			
		} else {

			$options = wp_fusion()->settings->get_all();
			$options['ac_url'] = $api_url;
			$options['ac_key'] = $api_key;
			$options['crm'] = $this->slug;
			$options['connection_configured'] = true;
			wp_fusion()->settings->set_all( $options );

			wp_send_json_success();

		}

		die();
		
	}

	/**
	 * Triggered by Resync Contact button, loads lists for contact and saves to user meta
	 *
	 * @access public
	 * @return void
	 */

	public function resync_lists( $user_id ) {

		if(is_wp_error( $this->crm->connect() ))
			return false;

		$contact_id = wp_fusion()->user->get_contact_id( $user_id );

		$result = $this->crm->app->api( 'contact/view?id=' . $contact_id );

		$lists = array();

		foreach($result->lists as $list_object) {

			$lists[] = $list_object->listid;

		}

		update_user_meta( $user_id, 'activecampaign_lists', $lists );

	}


}