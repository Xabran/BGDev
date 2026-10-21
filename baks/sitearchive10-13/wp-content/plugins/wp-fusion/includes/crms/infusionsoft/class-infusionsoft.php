<?php

class WPF_Infusionsoft_iSDK {

	/**
	 * Allows for direct access to the API, bypassing WP Fusion
	 */

	public $app;

	/**
	 * Lets pluggable functions know which features are supported by the CRM
	 */

	public $supports;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/

	public function __construct() {

		$this->slug = 'infusionsoft';
		$this->name = 'Infusionsoft';
		$this->menu_name = 'Infusionsoft (iSDK)';
		$this->supports = array();

		// Set up admin options
		if(is_admin()) {
			require_once dirname(__FILE__) . '/admin/class-admin.php';
			new WPF_Infusionsoft_iSDK_Admin($this->slug, $this->name, $this);
		}

	}


	/**
	 * Sets up hooks specific to this CRM
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		add_action( 'wpf_contact_updated', array( $this, 'send_api_call' ) );
		add_filter( 'wpf_format_field_value', array( $this, 'format_field_value' ), 10, 3 );
		add_filter( 'wpf_crm_post_data', array( $this, 'format_post_data' ));

	}

	/**
	 * Formats POST data received from HTTP Posts into standard format
	 *
	 * @access public
	 * @return array
	 */

	public function format_post_data( $post_data ) {

		if(isset($post_data['contactId']))
			$post_data['contact_id'] = $post_data['contactId'];

		return $post_data;

	}


	/**
	 * Formats user entered data to match Infusionsoft field formats
	 *
	 * @access public
	 * @return mixed
	 */

	public function format_field_value( $value, $field_type, $field ) {

		if ($field_type == 'datepicker' || $field_type == 'date') {

			// Adjust formatting for date fields
			$date = date("Ymd\T00:00:00", strtotime($value));
			return $date;

		} elseif ($field_type == 'checkbox' || $field_type == 'checkbox-full') {

			if(empty($value)) {
				//If checkbox is unselected
				return 0;
			} else {
				// If checkbox is selected
				return 1;
			}

		} elseif ($field_type == 'multiselect') {

			$value = implode(",", $value);
			return $value;

		/*} elseif ($field == 'user_pass') {

			@TODO: IS is cutting off the end of the string

			// Get the encrypted password from the database
			global $wpdb;
			$user_pass = $wpdb->get_var( "SELECT user_pass FROM $wpdb->users WHERE ID = $user_id" );
			$update_data[$field_data['crm_field']] = $user_pass;*/

		} elseif ($field == 'user_pass') {

			// Don't update password if it's empty
			if(!empty($value))
				return $value;

		} else {

			return $value;

		}

	}


	/**
	 * Maps local field types to IS field types
	 *
	 * @access public
	 * @return string
	 */

	public function map_field_types( $field_type ) {

		switch ($field_type) {
			case 'text':
				return 'Text';

			case 'select':
				return 'Select';

			case 'multiselect':
				return 'MultiSelect';

			case 'textarea':
				return 'TextArea';

			case 'datepicker':
				return 'Date';

			case 'checkbox':
				return 'YesNo';

			case 'checkbox-full':
				return 'YesNo';

			case 'radio':
				return 'Radio';

			case 'radio-full':
				return 'Radio';

			default:
				// In case no matching datatype is found, fall back to plain text
				return "Text";
		}

	}



	/*
	 * Connect
	 *
	 * Initialize connection to Infusionsoft
	 *
	 * @return mixed
	 */

	public function connect($app_name = null, $api_key = null, $test = false) {

		// If app is already running, don't try and restart it.
		if(is_object($this->app))
			return true;

		require dirname(__FILE__) . '/includes/isdk.php';
		$app = new iSDK;

		// Get saved data from DB
		if(empty($app_name) || empty($api_key)) {
			$app_name = wp_fusion()->settings->get('app_name');
			$api_key = wp_fusion()->settings->get('api_key');
		}

		try {

			$result = $app->cfgCon($app_name, $api_key, 'off');

			if($result == true) {
				$this->app = $app;
				return true;
			}

		} catch (Exception $e) {

			return new WP_Error( 'connection', __($e->getMessage() . '. Please verify your connection details are correct.', 'wp-fusion' ) );

		}
	}

	/**
	 * Performs initial sync once connection is configured
	 *
	 * @access public
	 * @return bool
	 */

	public function sync() {

		if(is_wp_error( $this->connect() ) )
			return false;

		$this->sync_tags();
		$this->sync_crm_fields();

		do_action('wpf_sync');

		return true;

	}


	/**
	 * Loads all available tags and categories from the CRM and saves them locally
	 *
	 * @access public
	 * @return array Tags
	 */

	public function sync_tags() {

		if(is_wp_error( $this->connect() ) )
			return false;

		// Retrieve tag categories

		$fields = array('CategoryName', 'Id');
		$query = array('Id' => '%');

		$tags = array();

		$categories = $this->app->dsQuery( 'ContactGroupCategory', 1000, 0, $query, $fields );
		$fields = array('Id','GroupName','GroupCategoryId');

		foreach($categories as $category) {

			// Retrieve tags
			$query = array('GroupCategoryId' => $category['Id']);
			$result = $this->app->dsQuery('ContactGroup',1000,0,$query,$fields);

			foreach($result as $tag) {
				$tags[$tag['Id']]['label'] = sanitize_text_field($tag['GroupName']);
				$tags[$tag['Id']]['category'] = sanitize_text_field($category['CategoryName']);
			}

		}

		// For tags with no category
		$query = array('GroupCategoryId' => '');
		$result = $this->app->dsQuery( 'ContactGroup', 1000, 0, $query, $fields);

		foreach($result as $tag) {
			$tags[$tag['Id']]['label'] = sanitize_text_field($tag['GroupName']);
			$tags[$tag['Id']]['category'] = 'No Category';
		}

		wp_fusion()->settings->set('available_tags', $tags);

		return $tags;

	}


	/**
	 * Loads all custom fields from CRM and merges with local list
	 *
	 * @access public
	 * @return array CRM Fields
	 */

	public function sync_crm_fields() {

		if(is_wp_error( $this->connect() ) )
			return false;

		// Load built in fields first
		require dirname(__FILE__) . '/admin/infusionsoft-fields.php';

		$built_in_fields = array();

		foreach( $infusionsoft_fields as $index => $data ) {
			$built_in_fields[$data['crm_field']] = $data['crm_label'];
		}

		asort($built_in_fields);

		// Get custom fields

		$custom_fields = array();

		$fields = array( 'Name', 'Label' );
		$query = array( 'FormId' => '-1' );

		$result = $this->app->dsQuery('DataFormField', 1000, 0, $query, $fields);

		foreach( $result as $key => $data ) {
			$custom_fields['_' . $data['Name']] = $data['Label'];
		}

		asort($custom_fields);

		$crm_fields = array('Standard Fields' => $built_in_fields, 'Custom Fields' => $custom_fields);
		wp_fusion()->settings->set('crm_fields', $crm_fields);

		return $crm_fields;

	}


	/**
	 * Gets contact ID for a user based on email address
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function get_contact_id( $email_address ) {

		if(is_wp_error( $this->connect() ))
			return false;

		// Pull the remote contact record based on local user's email address
		$return_fields = array('Id');
		$contact = $this->app->findByEmail( $email_address, $return_fields );

		if(isset($contact[0]['Id'])) {

			return $contact[0]['Id'];

		 } else {

		 	return false;

		 }

	}


	/**
	 * Gets all tags currently applied to the user
	 *
	 * @access public
	 * @return array Tags
	 */

	public function get_tags( $contact_id ) {

		if(is_wp_error( $this->connect() ))
			return false;

		$fields = array('Groups');
		$query = array('Id' => $contact_id);
		$tag_ids = $this->app->dsQuery( 'Contact', 1000, 0, $query, $fields);

		if(isset($tag_ids[0]['Groups'])) {

			// If user has tags applied
			$tag_ids = explode( ',', $tag_ids[0]['Groups'] );
			return $tag_ids;

		} else {

			return false;

		}

	}


	/**
	 * Applies tags to a contact
	 *
	 * @access public
	 * @return bool
	 */

	public function apply_tags( $tags, $contact_id ) {

		if(is_wp_error( $this->connect() ))
			return false;

		foreach($tags as $tag) {

			try {

				$this->app->grpAssign( $contact_id, $tag );

			} catch (Exception $e) {

				// If contact record is not found, or field not available, return false
				return false;

			}

		}

		return true;

	}


	/**
	 * Removes tags from a contact
	 *
	 * @access public
	 * @return bool
	 */

	public function remove_tags( $tags, $contact_id ) {

		if(is_wp_error( $this->connect() ))
			return false;

		foreach($tags as $tag) {

			try {

				$this->app->grpRemove($contact_id, $tag);

			} catch (Exception $e) {

				// If contact record is not found, or field not available, return false
				return false;

			}
		}

	}


	/**
	 * Adds a new contact
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function add_contact( $data, $map_meta_fields = true ) {

		if(is_wp_error( $this->connect() ))
			return false;

		// Allow functions to pass in pre-mapped data
		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		$contact_id = $this->app->addWithDupCheck( $data, 'Email' );
		$this->app->optIn($data['Email']);

		return $contact_id;

	}


	/**
	 * Update contact, with error handling
	 *
	 * @access public
	 * @return bool
	 */

	public function update_contact( $contact_id, $data, $map_meta_fields = true ) {

		if(is_wp_error( $this->connect() ))
			return false;

		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		if(empty($data))
			return true;

		try {

			$result = $this->app->updateCon($contact_id, $data);

		} catch (Exception $e) {

			// If contact ID has changed, try to find new contact ID
			if(strpos($e->getMessage(), 'RecordNotFound') !== FALSE) {

				$user_query = get_users(array( 'meta_key' => wp_fusion()->crm->slug . '_contact_id', 'meta_value' => $contact_id, 'fields' => 'ID' ) );
				$contact_id = wp_fusion()->user->get_contact_id($user_query[0]->ID, true);

				if($contact_id !== false) {

					$this->update_contact($contact_id, $data);
					return true;

				} else {

					// If contact has been deleted, re-add
					$this->add_contact($data, false);
					return true;

				}

			} else {
				return false;
			}

		}

		return true;

	}


	/**
	 * Loads a contact and updates local user meta
	 *
	 * @access public
	 * @return array User meta data that was returned
	 */

	public function load_contact( $contact_id ) {

		$return_fields = array();
		$field_map = array();

		foreach(wp_fusion()->settings->get('contact_fields') as $field_id => $field_data) {

			if( $field_data['active'] == true && !empty( $field_data['crm_field'] ) ) {

				$return_fields[] = $field_data['crm_field'];
				$field_map[$field_id] = $field_data['crm_field'];

			}

		}

		if( empty($return_fields) )
			return false;

		if(is_wp_error( $this->connect() ))
			return false;

		// Quit if not contact record found
		try {
			$result = $this->app->loadCon($contact_id, $return_fields);
		} catch (Exception $e) {
			return false;
		}

		$user_meta = array();

		foreach($field_map as $user_meta_key => $infusionsoft_key) {

			if(isset($result[$infusionsoft_key])) {
				$field_data = $result[$infusionsoft_key];
			} else {
				continue;
			}

			// Check if result is a date field
			if (DateTime::createFromFormat('Ymd\TG:i:s', $field_data) !== FALSE) {
					// Set to default WP date format
					$date_format = get_option( 'date_format' );
					$field_data = date($date_format, strtotime($field_data));
			}

			$user_meta[$user_meta_key] = $field_data;

		}

		return $user_meta;


	}


	/**
	 * Loads a list of contacts based on params
	 *
	 * @access public
	 * @return array Contacts returned
	 */

	public function load_contacts( $params ) {

		if(is_wp_error( $this->connect() ))
			return false;

		$contacts = array();
		$contact_fields = wp_fusion()->settings->get('contact_fields');
		$return_fields = array('Contact.Id');
		$field_map = array();

		foreach($contact_fields as $meta_key => $data) {

			// Can't get custom fields via the contactgroupassign table (IS doesn't keep Username or Password in GroupAssign)
			if($data['active'] == 1 && 0 !== strpos($data['crm_field'], '_') && $data['crm_field'] != 'Username' && $data['crm_field'] != 'Password' && !empty($data['crm_field'])) {
				$return_fields[] = 'Contact.' . $data['crm_field'];
				$field_map[] = array('meta_key' => $meta_key, 'crm_key' => 'Contact.' . $data['crm_field']);
			}

		}

		// If there's no data to get, don't bother
		if(empty($return_fields) || !in_array('Contact.Email', $return_fields))
			return false;

		// If we're not importing by tag, set it to a wildcard
		if(!is_array($params['tags']))
			$params['tags'] = array('%');


		foreach($params['tags'] as $tag) {

			$results = $this->app->dsQuery("ContactGroupAssign", 1000, 0, array('GroupId' => $tag), $return_fields);

			foreach($results as $id => $result) {

				$contacts[$result['Contact.Id']] = array();

				foreach($field_map as $map_data) {

					if(isset($result[$map_data['crm_key']]))
						$contacts[$result['Contact.Id']][$map_data['meta_key']] = $result[$map_data['crm_key']];

				}

			}

		}

		return $contacts;

	}

	/**
	 * Optionally sends an API call after a contact has been updated
	 *
	 * @access public
	 * @return void
	 */

	public function send_api_call( $contact_id ) {

		if( wp_fusion()->settings->get('api_call') == true ) {

			$integration = wp_fusion()->settings->get('api_call_integration');
			$call_name = wp_fusion()->settings->get('api_call_name');

			if(is_wp_error( $this->connect() ))
				return false;

			$this->app->achieveGoal($integration, $call_name, $contact_id);

		}

	}
	
}
