<?php

class WPF_Drip {

	/**
	 * Allows for direct access to the API, bypassing WP Fusion
	 */

	public $app;

	/**
	 * Account ID (used for API queries)
	 */

	private $account_id;

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

		$this->slug = 'drip';
		$this->name = 'Drip';
		$this->supports = array( 'add_tags', 'add_fields' );

		// Set up admin options
		if(is_admin()) {
			require_once dirname(__FILE__) . '/admin/class-admin.php';
			new WPF_Drip_Admin($this->slug, $this->name, $this);
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
		add_filter( 'wpf_crm_post_data', array( $this, 'format_post_data' ));

	}

	/**
	 * Formats POST data received from HTTP Posts into standard format
	 *
	 * @access public
	 * @return array
	 */

	public function format_post_data( $post_data ) {

		//@todo for drip

		$post_data['contact_id'] = $post_data['contact']['id'];

		return $post_data;

	}


	/**
	 * Drip requires an email to be submitted when contacts are updated
	 *
	 * @access private
	 * @return string Email
	 */

	private function get_email_from_cid( $contact_id ) {
  	
		$users = get_users(array('meta_key' => 'drip_contact_id', 'meta_value' => $contact_id, 'fields' => array('user_email')));

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

	public function connect( $api_token = null, $account_id = null, $test = false ) {

		// If app is already running, don't try and restart it.
		if(is_object($this->app))
			return true;

		if(empty($api_token) || empty($account_id)) {
			$api_token = wp_fusion()->settings->get('drip_token');
			$account_id = wp_fusion()->settings->get('drip_account');
		}
		
		require dirname(__FILE__) . '/includes/Drip_API.class.php';
		$app = new WPF_Drip_API($api_token);

		if($test == true) {

			$accounts = $app->get_accounts();
			
			if(empty($accounts))
				return new WP_Error( 'connection', __( 'Access denied: Invalid API token.', 'wp-fusion' ) );

			$valid_id = false;
			foreach($accounts as $account) {
				if($account['id'] == $account_id)
					$valid_id = true;
			}

			if($valid_id == false)
				return new WP_Error( 'connection', __( 'Access denied: Your API token doesn\'t have access to this account.', 'wp-fusion' ) );

		}

		$this->account_id = $account_id;
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

		$this->sync_tags_crm_fields();
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
		
		$available_tags = $this->sync_tags_crm_fields('tags');

		return $available_tags;
	}


	/**
	 * Loads all custom fields from CRM and merges with local list
	 *
	 * @access public
	 * @return array CRM Fields
	 */

	public function sync_crm_fields() {
	
		$crm_fields = $this->sync_tags_crm_fields('crm_fields');

		return $crm_fields;
	}


	/**
	 * Samples a group of subscribers to get available tags and custom fields
	 *
	 * @access public
	 * @return array CRM Fields
	 */

	public function sync_tags_crm_fields( $return = false ) {

		$this->connect();

        $url = 'https://api.getdrip.com/v2/' . $this->account_id . '/subscribers/';
        $response = $this->app->make_request($url);
        $response = json_decode($response['buffer']);

        if(empty($response->subscribers))
        	return true;

        $available_tags = array();
        $crm_fields = array('email' => 'Email');

        foreach($response->subscribers as $subscriber) {

			foreach($subscriber->tags as $tag) {

				if(!isset($available_tags[$tag]))
					$available_tags[$tag] = $tag;

			}

			foreach($subscriber->custom_fields as $field_id => $field_label) {

				if(!isset($crm_fields[$field_id]))
					$crm_fields[$field_id] = $field_id;

			}

        }

        wp_fusion()->settings->set( 'available_tags', $available_tags );
        wp_fusion()->settings->set( 'crm_fields', $crm_fields );

        if($return == 'tags') {
        	return $available_tags;
        } elseif( $return == 'crm_fields' ) {
        	return $crm_fields;
        } else {
        	return true;
        }

	}

	/**
	 * Gets contact ID for a user based on email address
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function get_contact_id( $email_address ) {
	
		$this->connect();

		$result = $this->app->fetch_subscriber( array( 'account_id' => $this->account_id, 'email' => $email_address ) );

		if(empty($result) || !isset($result['id']))
			return false;

		return $result['id'];

	}


	/**
	 * Gets all tags currently applied to the user, also update the list of available tags
	 *
	 * @access public
	 * @return void
	 */

	public function get_tags( $contact_id ) {
		
		$this->connect();
		
		$result = $this->app->fetch_subscriber( array( 'account_id' => $this->account_id, 'subscriber_id' => $contact_id ) );

		if(empty($result) || empty($result['tags']))
			return false;

		// Set available tags
		$available_tags = wp_fusion()->settings->get('available_tags');

		if(!is_array($available_tags))
			$available_tags = array();

		foreach($result['tags'] as $tag) {

			if(!isset($available_tags[$tag]))
				$available_tags[$tag] = $tag;

		}

		wp_fusion()->settings->set('available_tags', $available_tags);

		return $result['tags'];

	}

	/**
	 * Applies tags to a contact
	 *
	 * @access public
	 * @return bool
	 */

	public function apply_tags( $tags, $contact_id ) {

		$this->connect();

		foreach($tags as $tag) {
			$this->app->tag_subscriber( array('account_id' => $this->account_id, 'email' => $this->get_email_from_cid( $contact_id ), 'tag' => $tag) );
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

		$this->connect();

		foreach($tags as $tag) {
			$this->app->untag_subscriber( array('account_id' => $this->account_id, 'email' => $this->get_email_from_cid( $contact_id ), 'tag' => $tag) );
		}

		return true;

	}


	/**
	 * Adds a new contact
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function add_contact( $data, $map_meta_fields = true ) {
		
		$this->connect();

		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		$email = $data['email'];
		unset($data['email']);

		$params = array(
			'account_id' 	=> $this->account_id,
			'email'			=> $email,
			'custom_fields'	=> $data
			);

		$response = $this->app->create_or_update_subscriber($params);

		if(!empty($response)) {
			return $response['id'];
		} else {
			return false;
		}

	}

	/**
	 * Update contact
	 *
	 * @access public
	 * @return bool
	 */

	public function update_contact( $contact_id, $data, $map_meta_fields = true ) {

		$this->connect();

		if($map_meta_fields == true)
			$data = wp_fusion()->crm_base->map_meta_fields( $data );

		if(empty($data))
			return true;

		$params = array(
			'account_id' 	=> $this->account_id,
			'id'			=> $contact_id,
			'custom_fields'	=> $data
			);

		// Handle email address changes
		if(isset($params['custom_fields']['email'])) {
			$params['new_email'] = $params['custom_fields']['email'];
			unset($params['custom_fields']['email']);
		}
		
		$response = $this->app->create_or_update_subscriber($params);

		if(!empty($response)) {
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

		$this->connect();

		$result = $this->app->fetch_subscriber( array( 'account_id' => $this->account_id, 'subscriber_id' => $contact_id ) );

		if(empty($result))
			return false;

		$contact_fields = wp_fusion()->settings->get('contact_fields');
		$user_meta = array();

		foreach($contact_fields as $field_id => $field_data) {

			if($field_data['active'] == true && isset($result['custom_fields'][$field_data['crm_field']]))
				$user_meta[$field_id] = $result['custom_fields'][$field_data['crm_field']];

			if($field_id == 'user_email')
				$user_meta['user_email'] = $result['email'];

		}

		return $user_meta;
	}
	
	
	/**
	 * Loads a list of contacts based on params
	 *
	 * @access public
	 * @return array Contacts returned
	 */
	
	public function load_contacts( $params ){
		
		$this->connect();

		$contacts = array();
		$contact_fields = wp_fusion()->settings->get('contact_fields');

		$tag = $params['tags'][0];

		// Load all subscribers
        $url = 'https://api.getdrip.com/v2/' . $this->account_id . '/subscribers/';
        $response = $this->app->make_request($url);
        $response = json_decode($response['buffer']);

        if(empty($response->subscribers))
        	return false;

        // Filter results by tag
        foreach( $response->subscribers as $subscriber ) {

        	if(!in_array($tag, $subscriber->tags))
        		continue;

        	$contacts[$subscriber->id] = array('user_email' => $subscriber->email);

        	foreach($subscriber->custom_fields as $field_id => $field_value) {

				foreach($contact_fields as $meta_key => $field_data) {

					if($field_data['active'] == true && $field_data['crm_field'] == $field_id)
						$contacts[$subscriber->id][$meta_key] = $field_value;

				}

        	}

        }

		return $contacts;
		
	}

}