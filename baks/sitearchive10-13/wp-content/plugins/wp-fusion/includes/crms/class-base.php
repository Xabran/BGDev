<?php 

class WPF_CRM_Base {

	/**
	 * Contains an array of installed APIs and their details
	 *
	 * @var available_crms
	 */

	public $available_crms = array();


	/**
	 * Contains the class object for the currently active CRM
	 *
	 * @var crm
	 */

	public $crm;


	public function __construct() {

		$configured_crms = wp_fusion()->get_crms();

		foreach( $configured_crms as $slug => $classname ) {

			if(class_exists($classname)) {

				$crm = new $classname();

				if(wp_fusion()->settings->get('crm') == $slug) {
					$this->crm = $crm;
					$this->crm->init();
				}

				$this->available_crms[$slug] = array( 'name' => $crm->name );

				if(isset($crm->menu_name)) {
					$this->available_crms[$slug]['menu_name'] = $crm->menu_name;
				} else {
					$this->available_crms[$slug]['menu_name'] = $crm->name;
				}

			}

		}

		add_filter( 'wpf_configure_settings', array( $this, 'configure_settings' ) );

		// AJAX CRM connection and sync
		add_action( 'wp_ajax_wpf_sync' , array( $this, 'sync' ) );
		add_action( 'wp_ajax_wpf_sync_users', array( $this, 'sync_users' ) );

		// API debugging class
		if (defined('WPF_DEBUG') && WPF_DEBUG == true)
			$this->debug();
		
		// Sets up "turbo" mode
		$this->turbo();

	}

	/**
	 * Adds the available CRMs to the select dropdown on the setup page
	 *
	 * @access  public
	 * @since   1.0
	 * @return  array
	 */

	public function configure_settings( $settings ) {

		$settings['crm']['choices'] = $this->get_crms_for_select();

		return $settings;

	}

	/**
	 * Returns the slug and menu name of each CRM for select fields
	 *
	 * @access  public
	 * @since   1.0
	 * @return  array
	 */

	public function get_crms_for_select() {

		foreach( $this->available_crms as $slug => $data ) {
			$select_array[$slug] = $data['menu_name'];
		}

		return $select_array;

	}

	/**
	 * Perform initial app sync
	 *
	 * @access public
	 * @return JSON response
	 */

	public function sync() {

		if(wp_fusion()->crm->sync() == true) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}

	}

	/**
	 * Perform initial app sync
	 *
	 * @access public
	 * @return JSON response
	 */

	public function sync_users() {

		$offset = $_POST['offset'];
		$number = $_POST['number'];

		if(wp_fusion()->user->sync_users($offset, $number) == true) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}

	}

	/**
	 * Maps local fields to CRM field names
	 *
	 * @access public
	 * @return array
	 */

	public function map_meta_fields( $user_data ) {

		$update_data = array();

		foreach (wp_fusion()->settings->get('contact_fields') as $field => $field_data) {

			// If field exists in form and sync is active
			if(isset($user_data[$field]) && $field_data['active'] == true && !empty($field_data['crm_field'])) {

				$value = apply_filters( 'wpf_format_field_value', $user_data[$field], $field_data['type'], $field_data['crm_field'] );

				if(!empty($value))
					$update_data[$field_data['crm_field']] = $value;

			}
		}


		return $update_data;

	}

	/**
	 * Enables debugging class
	 *
	 * @access public
	 */

	public function debug() {

		require_once WPF_DIR_PATH . 'includes/crms/class-debug.php';
		$this->crm = new WPF_CRM_Debug($this->crm);

	}

	/**
	 * Enables turbo mode
	 *
	 * @access public
	 */

	public function turbo() {

		require_once WPF_DIR_PATH . 'includes/crms/class-queue.php';
		$this->crm = new WPF_CRM_Queue($this->crm);

	}



}