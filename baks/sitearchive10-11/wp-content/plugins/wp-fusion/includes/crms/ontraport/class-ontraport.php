<?php

class WPF_Ontraport {

	/**
	 * (deprecated)
	 */

	public $app;

	/**
	 * Contains API params
	 */

	public $params;

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

		$this->slug = 'ontraport';
		$this->name = 'Ontraport';
		$this->supports = array();

		// Set up admin options
		if(is_admin()) {
			require_once dirname(__FILE__) . '/admin/class-admin.php';
			new WPF_Ontraport_Admin($this->slug, $this->name, $this);
		}

	}

	/**
	 * Sets up hooks specific to this CRM
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		//add_filter( 'wpf_format_field_value', array( $this, 'format_field_value' ), 10, 3 );

	}


	/**
	 * Gets params for API calls
	 *
	 * @access  public
	 * @return  bool
	*/
	
	public function get_params($api_url = null, $api_key = null) {
		
		// Get saved data from DB
		if(empty($api_url) || empty($api_key)) {
			$api_url = wp_fusion()->settings->get('op_url');
			$api_key = wp_fusion()->settings->get('op_key');
		}

		$this->params = array(
				'timeout' => 120,
				'httpversion' => '1.1',
				'headers' => array(
						"Api-Appid" => $api_url,
						"Api-Key" 	=> $api_key
						)
				);

		return true;
	}
	
	
	/**
	 * Initialize connection
	 *
	 * @access  public
	 * @return  bool
	*/

	public function connect( $api_url = null, $api_key = null, $test = false ) {
		
		if($test == false)
			return true;
		
		if( ! $this->params) $this->get_params($api_url, $api_key);
		
		$request = "https://api.ontraport.com/1/objects/meta?format=byId&objectID=0";
		$response = wp_remote_get($request, $this->params);

		if (wp_remote_retrieve_response_code($response) != 200)
			return new WP_Error( 'connection', wp_remote_retrieve_body( $response ));
		
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
		$this->sync_crm_fields();

		do_action('wpf_sync');

		return true;

	}


	/**
	 * Gets all available tags and saves them to options
	 *
	 * @access public
	 * @return array Lists
	 */

	public function sync_tags() {
		
		if( ! $this->params)$this->get_params();
		
		$available_tags = array();
		$offset = 0;
		$continue = true;
		
		while ($continue == true) {
		
			$request = "https://api.ontraport.com/1/objects?objectID=14&start=".$offset;
			$response = wp_remote_get($request, $this->params);	

			if(is_wp_error( $response )) continue;

			$body_json = json_decode($response['body'], true);
			
			foreach ($body_json['data'] as $row){						
				$available_tags[$row['tag_id']] = $row['tag_name'];
			}
	
			if(count($body_json['data']) < 50)$continue = false;
		
			$offset = $offset + 50;
		}

		wp_fusion()->settings->set( 'available_tags', $available_tags );

		return $available_tags;
	}


	/**
	 * Loads all custom fields from CRM and merges with local list
	 *
	 * @access public
	 * @return array CRM Fields
	 */

	public function sync_crm_fields() {
	
		if( ! $this->params) $this->get_params();

		$crm_fields = array();
		$request = "https://api.ontraport.com/1/objects/meta?format=byId&objectID=0";	
		$response = wp_remote_get($request, $this->params);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code($response) != 200)
			return $crm_fields;

		$body_json = json_decode($response['body'], true);
		
		foreach($body_json['data'][0]['fields'] as $key => $field_data) {
			$crm_fields[$key] = $field_data['alias'];
		}
		
		asort($crm_fields);
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
	
		if( ! $this->params)$this->get_params();

		$contact_info = array();
		$request = "https://api.ontraport.com/1/objects?objectID=0&condition=email%3D'" . urlencode($email_address) . "'&listFields=id";
		$response = wp_remote_get($request, $this->params);
		$body_json = json_decode($response['body'], true);
		
		if( empty($body_json['data'] ))
			return false;
		
		return $body_json['data'][0]['id'];
	}


	/**
	 * Gets all tags currently applied to the user, also update the list of available tags
	 *
	 * @access public
	 * @return void
	 */

	public function get_tags( $contact_id ) {
		
		if( ! $this->params) $this->get_params();

		$contact_info = array();
		$request = "https://api.ontraport.com/1/objects?objectID=0&&condition=id%3D'". $contact_id . "'&listFields=contact_cat";
		$response = wp_remote_get($request, $this->params);
		$body_json = json_decode($response['body'], true);
		
		if(empty($body_json['data'][0]['contact_cat']))
			return false;
		
		$cat = array_filter(explode ('*/*',$body_json['data'][0]['contact_cat']));
		
		return $cat;
	}

	/**
	 * Applies tags to a contact
	 *
	 * @access public
	 * @return bool
	 */

	public function apply_tags( $tags, $contact_id ) {

		if( ! $this->params) $this->get_params();
		
		$url = "https://api.ontraport.com/1/objects/tag";
		$nparams = $this->params;
		$alist = implode(",", $tags);
		$post_data = array (
				'objectID' => 0,
				'add_list' => $alist,
				'ids' => $contact_id
		);
		$nparams['method'] = 'PUT';
		$nparams['body'] = json_encode($post_data);

		$post = wp_remote_post($url, $nparams);

		if (wp_remote_retrieve_response_code($post) != 200)
			return new WP_Error( 'connection', wp_remote_retrieve_body( $post ));

		return true;
	}

	/**
	 * Removes tags from a contact
	 *
	 * @access public
	 * @return bool
	 */

	public function remove_tags( $tags, $contact_id ) {

		if( ! $this->params) $this->get_params();
		
		$url = "https://api.ontraport.com/1/objects/tag";
		$nparams = $this->params;
		$alist = implode(",", $tags);
		$post_data = array (
				'objectID' => 0,
				'add_list' => $alist,
				'ids' => $contact_id
		);
		$nparams['method'] = 'DELETE';
		$nparams['body'] = json_encode($post_data);
		$post = wp_remote_post($url, $nparams);

		if (wp_remote_retrieve_response_code($post) != 200)
			return new WP_Error( 'connection', wp_remote_retrieve_body( $post ));

		return true;

	}


	/**
	 * Adds a new contact
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function add_contact( $data, $map_meta_fields = true ) {
		
		if( ! $this->params) $this->get_params();

		if($map_meta_fields == true )
			$data = wp_fusion()->crm_base->map_meta_fields($data);
		
		$urlp = "https://api.ontraport.com/1/objects";
		$data['objectID'] = 0;
		$nparams = $this->params;
		$nparams['method'] = 'POST';
		$nparams['body'] = json_encode($data);

		$post = wp_remote_post($urlp, $nparams);

		if (wp_remote_retrieve_response_code($post) != 200)
			return false;

		$body = json_decode(wp_remote_retrieve_body( $post ));

		return $body->id;

	}

	/**
	 * Update contact
	 *
	 * @access public
	 * @return bool
	 */

	public function update_contact( $contact_id, $data, $map_meta_fields = true ) {

		if( ! $this->params)
			$this->get_params();

		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		if(empty($data))
			return true;
		
		$urlp = "https://api.ontraport.com/1/objects";
		$data['objectID'] = 0;
		$data['id'] = $contact_id;
		$nparams = $this->params;
		$nparams['method'] = 'PUT';
		$nparams['body'] = json_encode($data);
		
		$post = wp_remote_post($urlp, $nparams);

		if (wp_remote_retrieve_response_code($post) != 200)
			return new WP_Error( 'connection', wp_remote_retrieve_body( $post ));

		return true;
	}

	/**
	 * Loads a contact and updates local user meta
	 *
	 * @access public
	 * @return array User meta data that was returned
	 */

	public function load_contact( $contact_id ) {

		if( ! $this->params)
			$this->get_params();
		
		$url = "https://api.ontraport.com/1/objects?objectID=0&ids=" . $contact_id;
		$response = wp_remote_get($url, $this->params);

		if (wp_remote_retrieve_response_code($response) != 200)
			return new WP_Error( 'connection', wp_remote_retrieve_body( $response ));

		$user_meta = array();
		$contact_fields = wp_fusion()->settings->get('contact_fields');
		$body_json = json_decode($response['body'], true);

		foreach($contact_fields as $field_id => $field_data) {

			if($field_data['active'] == true && isset($body_json['data'][0][$field_data['crm_field']]))
				$user_meta[$field_id] = $body_json['data'][0][$field_data['crm_field']];

		}

		return $user_meta;
	}
	
	
	/**
	 * Loads a contact and updates local user meta
	 *
	 * @access public
	 * @return array User meta data that was returned
	 */
	
	
	public function load_contacts ( $params ){
		
		$contacts = array();
		$contact_fields = wp_fusion()->settings->get('contact_fields');
		$return_fields = array('Contact.Id');
		$field_map = array();
		
		foreach ($contact_fields as $meta_key => $data ){
			
			if($data['active'] == 1 && 0 !== strpos($data['crm_field'], '_') && $data['crm_field'] != 'Username' && $data['crm_field'] != 'Password') {
				$return_fields[] = 'Contact.' . $data['crm_field'];
				$field_map[] = array('meta_key' => $meta_key, 'crm_key' => 'Contact.' . $data['crm_field']);
			}
			
		}
		
		// If there's no data to get, don't bother
		if(empty($return_fields) || !in_array('Contact.email', $return_fields))	return false;
		
		//
		if( ! is_array($params['tags']))
			$params['tags'] = array('%');
		
		//	
		foreach ($params['tags'] as  $tag){
			
			if( ! $this->params)$this->get_params();
			
			$url = "https://api.ontraport.com/1/objects?objectID=0&performAll=true&sortDir=asc&condition=contact_cat%3D'*%2F*" . $tag . "*%2F*'&searchNotes=true";
			$results = wp_remote_get($url, $this->params);
			
			if(wp_remote_retrieve_response_code($results) != 200 ){
				return new WP_Error( 'connection', wp_remote_retrieve_response_code($results));
			}
			
			$body_json = json_decode($results['body'], true);

			foreach($body_json['data'] as $row => $contact) {
				$contacts[$contact['id']] = array();
				foreach($contact_fields as $field_id => $field_data) {
					if($field_data['active'] == true && isset($body_json['data'][$row][$field_data['crm_field']]))
						$contacts[$contact['id']][$field_id] = $body_json['data'][$row][$field_data['crm_field']];
				}
			}
		}		
		return $contacts;
		
	}

}