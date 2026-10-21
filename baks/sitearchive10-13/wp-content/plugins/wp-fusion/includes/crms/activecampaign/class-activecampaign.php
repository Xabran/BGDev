<?php

class WPF_ActiveCampaign {

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
	 * @since   2.0
	*/

	public function __construct() {

		$this->slug = 'activecampaign';
		$this->name = 'ActiveCampaign';
		$this->supports = array( 'add_tags', 'add_lists' );

		// Set up admin options
		if(is_admin()) {
			require_once dirname(__FILE__) . '/admin/class-admin.php';
			new WPF_ActiveCampaign_Admin($this->slug, $this->name, $this);
		}

	}

	/**
	 * Sets up hooks specific to this CRM
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		add_filter( 'wpf_format_field_value', array( $this, 'format_field_value' ), 10, 3 );
		add_filter( 'wpf_crm_post_data', array( $this, 'format_post_data' ));

		add_action( 'wpf_user_created', array( $this, 'save_lists' ), 10, 2 );

	}

	/**
	 * Formats POST data received from HTTP Posts into standard format
	 *
	 * @access public
	 * @return array
	 */

	public function format_post_data( $post_data ) {

		$post_data['contact_id'] = $post_data['contact']['id'];

		return $post_data;

	}


	/**
	 * Formats user entered data to match AC field formats
	 *
	 * @access public
	 * @return mixed
	 */

	public function format_field_value( $value, $field_type, $field ) {

		if ($field_type == 'datepicker' || $field_type == 'date') {

			// Adjust formatting for date fields
			$date = date('m/d/Y', strtotime($value));
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

		} elseif ($field == 'user_pass') {

			// Don't update password if it's empty
			if(!empty($value))
				return $value;

		} else {

			return $value;

		}

		return false;

	}


	/**
	 * ActiveCampaign requires an email to be submitted when contacts are updated
	 *
	 * @access private
	 * @return string Email
	 */

	private function get_email_from_cid( $contact_id ) {
  	
		$users = get_users(array('meta_key' => 'activecampaign_contact_id', 'meta_value' => $contact_id, 'fields' => array('user_email')));

		if(!empty($users)) {
			return $users[0]->user_email;
		} else {
			return false;
		}


	}


	/**
	 * Initialize connection
	 *
	 * @access  public
	 * @return  bool
	*/

	public function connect( $api_url = null, $api_key = null, $test = false ) {

		if( isset($this->app) )
			return true;

		// Get saved data from DB
		if(empty($api_url) || empty($api_key)) {
			$api_url = wp_fusion()->settings->get('ac_url');
			$api_key = wp_fusion()->settings->get('ac_key');
		}

		require dirname(__FILE__) . '/includes/ActiveCampaign.class.php';

		$app = new WPF_ActiveCampaign_API($api_url, $api_key);

		if($test == true) {
			if (!(int)$app->credentials_test())
				return new WP_Error( 'connection', __( 'Access denied: Invalid credentials (URL and/or API key).', 'wp-fusion' ) );
		}

		// Connection was a success
		$this->app = $app;

		return true;

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
		$this->sync_lists();
		$this->sync_crm_fields();

		do_action('wpf_sync');

		return true;

	}


	/**
	 * Gets all available tags and saves them to options
	 *
	 * @access public
	 * @return array Tags
	 */

	public function sync_tags() {

		$api = wp_fusion()->settings->get('ac_url') . '/api/3/tags&api_key=' . wp_fusion()->settings->get('ac_key') . '&limit=100';

		$request = curl_init($api);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

		$response = (string)curl_exec($request);
		curl_close($request);

		$result = json_decode($response);

		$available_tags = array();

		foreach($result->tags as $tag) {
			$available_tags[$tag->tag] = $tag->tag;
		}

		wp_fusion()->settings->set('available_tags', $available_tags);

		return $available_tags;

	}

	/**
	 * Gets all available lists and saves them to options
	 *
	 * @access public
	 * @return array Lists
	 */

	public function sync_lists() {

		if(is_wp_error( $this->connect() ) )
			return false;

		$lists = $this->app->api( 'list/list', array('ids' => 'all') );

		$available_lists = array();

		foreach( $lists as $list ) {
			if(is_object( $list )) {
				$available_lists[$list->id] = $list->name;
			}
		}

		wp_fusion()->settings->set( 'available_lists', $available_lists );

		return $available_lists;

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
		require dirname(__FILE__) . '/admin/activecampaign-fields.php';

		$built_in_fields = array();

		foreach( $activecampaign_fields as $index => $data ) {
			$built_in_fields[$data['crm_field']] = $data['crm_label'];
		}

		asort($built_in_fields);

		// Get custom fields

		$custom_fields = array();
		$result = $this->app->api( 'list/field_view?ids=all');

		if( $result->success == 1 ) {
			foreach( $result as $field ) {
				if( is_object($field) ) {
					$custom_fields['field[' . $field->id . ',0]'] = $field->title;
				}
			}
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

		$result = $this->app->api( 'contact/view?email=' . urlencode($email_address) );

		if( $result->success == 1 ) {
			return $result->id;
		} else {
			return false;
		}

	}


	/**
	 * Gets all tags currently applied to the user, also update the list of available tags
	 *
	 * @access public
	 * @return void
	 */

	public function get_tags( $contact_id ) {

		if(is_wp_error( $this->connect() ))
			return false;

		$result = $this->app->api( 'contact/view?id=' . $contact_id );

		if( $result->success == 1 ) {

			// Merge tags with available ones
			$available_tags = wp_fusion()->settings->get('available_tags');

			if(!is_array($available_tags))
				$available_tags = array();

			foreach($result->tags as $i => $tag) {
				$available_tags[$tag] = $tag;
			}

			wp_fusion()->settings->set( 'available_tags', $available_tags );

			return $result->tags;

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

		$result = $this->app->api( 'contact/tag_add', array('id' => $contact_id, 'tags' => $tags) );

		if( $result->success == 1 ) {

			// Possibly update available tags if it's a newly created one
			$available_tags = wp_fusion()->settings->get('available_tags');

			foreach($tags as $tag) {
				if(!isset($available_tags[$tag])) {
					$available_tags[$tag] = $tag;
					$needs_update = true;
				}
			}

			if(isset($needs_update))
				wp_fusion()->settings->set( 'available_tags', $available_tags );

			return true;

		} else {

			return false;

		}

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

		$result = $this->app->api( 'contact/tag_remove', array('id' => $contact_id, 'tags' => $tags) );

		if( $result->success == 1 ) {

			return true;

		} else {

			return false;

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

		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		// Set lists
		$lists = wp_fusion()->settings->get('ac_lists');

		if(!empty($lists)) {
			foreach($lists as $list_id) {
				$data['p[' . $list_id . ']'] = $list_id;
			}
		}

		$result = $this->app->api( 'contact/sync', $data );

		if( $result->success == 1 ) {

			return $result->subscriber_id;

		} else {

			return false;

		}


	}

	/**
	 * Stores lists to user meta after contact creation
	 *
	 * @access public
	 * @return void
	 */

	public function save_lists( $user_id, $contact_id ) {

		$lists = wp_fusion()->settings->get('ac_lists');

		if(empty($lists))
			return;

		update_user_meta( $user_id, 'activecampaign_lists', $lists );

	}


	/**
	 * Update contact
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

		if(!isset($data['email']))
			$data['email'] = $this->get_email_from_cid($contact_id);

		$user_id = wp_fusion()->user->get_user_id( $contact_id );
		$lists = get_user_meta( $user_id, 'activecampaign_lists', true );

		if(!empty($lists)) {

			foreach((array)$lists as $list_id) {
				$data['p[' . $list_id . ']'] = $list_id;
			}

		} else {

			// In case contact has no lists, assume the first list
			$data['p[1]'] = 1;

		}

		$data['id'] = $contact_id;

		$result = $this->app->api( 'contact/edit', $data );

		if( $result->success == 1 ) {

			return true;

		} else {

			return false;

		}

	}

	/**
	 * Loads a contact and updates local user meta
	 *
	 * @access public
	 * @return array User meta data that was returned
	 */

	public function load_contact( $contact_id ) {

		if(is_wp_error( $this->connect() ))
			return false;

		$result = $this->app->api( 'contact/view?id=' . $contact_id );

		if( $result->success == 1 ) {

			$user_meta = array();

			// Save user's lists
			$lists = array();
			foreach($result->lists as $list_object) {
				$lists[] = $list_object->listid;
			}

			$user_meta['activecampaign_lists'] = $lists;

			// Map contact fields
			$contact_fields = wp_fusion()->settings->get('contact_fields');

			// Standard fields
			foreach($result as $field_name => $value) {

				foreach($contact_fields as $meta_key => $field_data) {

					if($field_data['crm_field'] == $field_name && $field_data['active'] == true)
						$user_meta[$meta_key] = $value;

				}

			}

			// Custom fields
			foreach($result->fields as $field_object) {

				foreach($contact_fields as $meta_key => $field_data) {

					if($field_data['active'] == true) {

						// Get field ID from stored CRM field value
						$field_array = explode(',', str_replace('field[', '', str_replace(']', '', $field_data['crm_field'])));
						 
						if($field_object->id == $field_array[0])
							$user_meta[$meta_key] = $field_object->val;
					}

				}

			}

			return $user_meta;

		} else {

			return false;

		}

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

		$results = $this->app->api( 'contact/list?filters[tagname]=' . urlencode($params['tags'][0]) );

		if( $results->success == 1 ) {

			$contacts = array();
			$contact_fields = wp_fusion()->settings->get('contact_fields');

			foreach($results as $result) {

				if(!is_object($result))
					continue;

				$contacts[$result->id] = array();

				// Standard fields
				foreach($result as $field_name => $value) {

					foreach($contact_fields as $meta_key => $field_data) {

						if($field_data['crm_field'] == $field_name && $field_data['active'] == true)
							$contacts[$result->id][$meta_key] = $value;

					}

				}

				// Custom fields
				foreach($result->fields as $field_object) {

					foreach($contact_fields as $meta_key => $field_data) {

						if($field_data['active'] == true) {

							// Get field ID from stored CRM field value
							$field_array = explode(',', str_replace('field[', '', str_replace(']', '', $field_data['crm_field'])));
							 
							if($field_object->id == $field_array[0])
								$contacts[$result->id][$meta_key] = $field_object->val;
						}

					}

				}
			}

			return $contacts;

		} else {

			return false;

		}

	}



}


