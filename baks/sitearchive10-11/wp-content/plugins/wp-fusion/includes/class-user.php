<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_User {
	

	public function __construct() {

		add_action( 'user_register', array( $this, 'user_register'), 20);
		add_action( 'password_reset', array( $this, 'password_reset'), 10, 2);

		add_action( 'wp_enqueue_scripts', array( $this, 'apply_tags_on_view'));

		add_action( 'updated_user_meta', array( $this, 'push_user_meta_single'), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'push_user_meta_single'), 10, 4 );

		add_action( 'wpf_user_imported', array( $this, 'return_password' ), 10, 2 );

	}


	/**
	 * Used by create user to map post data for PHP versions less than 5.3
	 *
	 * @access public
	 * @return mixed
	 */

	public function map_user_meta( $a ) {
		return $a[0];
	}


	/**
	 * Syncs user tags in batches, with option to force-reload
	 *
	 * @access public
	 * @return bool
	 */

	public function sync_users($offset = 0, $number = 100, $force_reload = true) {

		$number = apply_filters( 'wpf_sync_batch_size', $number );

		// Set a local contact_id field for every user and import applied tags
		$args = array(
				'fields' 	=> array('ID'),
				'number' 	=> $number,
				'offset'	=> $offset,
				'orderby' 	=> 'ID',
				'order' 	=> 'DESC'
			);

		$users = get_users($args);

		foreach($users as $user) {
			$this->get_tags( $user->ID, $force_reload );
		}

		return true;

	}


	/**
	 * Triggered when a new user is registered. Creates the user in the CRM and stores contact ID
	 *
	 * @access public
	 * @return void
	 */

	public function user_register( $user_id, $post_data = null, $force = false ) {

		if(wp_fusion()->settings->get('create_users') != true && $force == false)
			return;

		if(empty($post_data) && !empty($_POST)) {

			$post_data = $_POST;

		} elseif(empty($post_data)) {

			// If nothing's been supplied, get meta data from DB
			$post_data = array_map( array($this, 'map_user_meta'), get_user_meta( $user_id ) );
		}

		// Get as much meta as we can from the DB
		$userdata = get_userdata( $user_id );
		$post_data['role'] = $userdata->roles[0];
		$post_data['first_name'] = $userdata->first_name;
		$post_data['last_name'] = $userdata->last_name;
		$post_data['user_email'] = $userdata->user_email;
		$post_data['user_url'] = $userdata->user_url;

		// See if user role is elligible for being created as a contact
		$valid_roles = wp_fusion()->settings->get( 'user_roles' );
		if( !empty( $valid_roles ) && !in_array( $post_data['role'], $valid_roles ) && $force == false)
				return;

		// If we're syncing passwords
		if(isset($post_data['pass1']) && !empty($post_data['pass1'])) {
			$post_data['user_pass'] = $post_data['pass1'];
			unset($post_data['pass1']);
		}

		// Allow outside modification of this data
		$post_data = apply_filters( 'wpf_user_register', $post_data, $user_id );

		// Allows for cancelling of registration via filter
		if($post_data == null)
			return;

		// Check if contact already exists in CRM
		$contact_id = $this->get_contact_id( $user_id );

		if($contact_id == false) {

			$contact_id = wp_fusion()->crm->add_contact( $post_data );
			update_user_meta($user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id);

		} else {

			// If contact exists, update data and pull down anything new from the CRM
			wp_fusion()->crm->update_contact( $contact_id, $post_data );
			$this->pull_user_meta( $user_id );
			$this->get_tags( $user_id, true );

		}

		// Assign any tags specified in the WPF settings page
		$assign_tags = wp_fusion()->settings->get('assign_tags');
		if(!empty($assign_tags))
			$this->apply_tags( $assign_tags, $user_id );


		do_action('wpf_user_created', $user_id, $contact_id, $post_data);

		return $contact_id;


	}
	

	/**
	 * Triggered when a password is reset
	 *
	 * @access public
	 * @return void
	 */

	public function password_reset( $user, $new_pass ) {

		wp_fusion()->user->push_user_meta( $user->ID, array( 'user_pass' => $new_pass ) );

	}


	/**
	 * Gets contact ID from user ID
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function get_contact_id( $user_id, $force_update = false ) {

		if( $user_id == false )
			$user_id = get_current_user_id();

		$contact_id = get_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', true );

		if( $force_update == true )
			$contact_id = null;

		// If contact ID is already set
		if( !empty( $contact_id ) )
			return $contact_id;

		$user = get_user_by( 'id', $user_id );

		// If no user email set, don't bother with an API call
		if(empty($user->user_email))
			return false;

		$contact_id = wp_fusion()->crm->get_contact_id( $user->user_email );

		if( $contact_id == false ) {
			delete_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id' );
			delete_user_meta( $user_id, wp_fusion()->crm->slug . '_tags' );
			return false;
		}

		update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );

		return $contact_id;

	}

	/**
	 * Gets user ID from contact ID
	 *
	 * @access public
	 * @return int User ID
	 */

	public function get_user_id( $contact_id ) {

		$users = get_users(array('meta_key' => wp_fusion()->crm->slug . '_contact_id', 'meta_value' => $contact_id, 'fields' => array('ID')));

		if(!empty($users)) {
			return $users[0]->ID;
		} else {
			return false;
		}

	}

	/**
	 * Checks to see if a user has a given tag
	 *
	 * @access public
	 * @return bool
	 */

	public function has_tag($tag, $user_id = false) {

		if( $user_id == false )
			$user_id = get_current_user_id();

		$user_tags = $this->get_tags($user_id);

		// Allow overrides by admin bar
		if(is_user_logged_in() && current_user_can( 'manage_options' ) && get_query_var( 'wpf_tag' )) {

			if(get_query_var( 'wpf_tag' ) == 'unlock-all')
				return true;

			if(get_query_var( 'wpf_tag' ) == 'lock-all')
				return false;

			$user_tags[] = get_query_var( 'wpf_tag' );

		}

		if(!is_array($user_tags))
			return false;

		// Get tag ID if label passed
		if(!is_numeric($tag))
			$tag = $this->get_tag_id( $tag );

		if(in_array($tag, $user_tags))
			return true;


		return false;

	}

	/**
	 * Gets all tags currently applied to the user
	 *
	 * @access public
	 * @return array Tags applied to the user
	 */

	public function get_tags( $user_id = false, $force_update = false ) {

		if( $user_id == false )
			$user_id = get_current_user_id();

		$user_tags = get_user_meta($user_id, wp_fusion()->crm->slug . '_tags', true);

		if(is_array($user_tags) && $force_update == false)
			return $user_tags;

		// If no tags
		if(empty($user_tags) && $force_update == false)
			return false;

		$contact_id = $this->get_contact_id( $user_id );

		// If contact doesn't exist in CRM
		if($contact_id == false) {
			update_user_meta($user_id, wp_fusion()->crm->slug . '_tags', false);
			return false;
		}

		$user_tags = wp_fusion()->crm->get_tags( $contact_id );

		update_user_meta($user_id, wp_fusion()->crm->slug . '_tags', $user_tags);

		do_action( 'wpf_tags_modified', $user_id, $user_tags );

		return $user_tags;

	}


	/**
	 * Gets the display label for a given user's tag
	 *
	 * @access public
	 * @return string Label for given tag
	 */

	public function get_tag_label( $tag_id ) {

		$available_tags = wp_fusion()->settings->get('available_tags');

		if(is_array($available_tags[$tag_id])) {

			return $available_tags[$tag_id]['label'];

		} else {

			return $available_tags[$tag_id];

		}

	}

	/**
	 * Gets tag ID from tag name
	 *
	 * @access public
	 * @return int ID
	 */

	public function get_tag_id($tag_name) {

		foreach(wp_fusion()->settings->get('available_tags') as $id => $data) {

			if(isset($data['label']) && $data['label'] == $tag_name) {
				return $id;
			} elseif ($id == $tag_name) {
				return $tag_name;
			}
			
		}

		return false;

	}


	/**
	 * Applies an array of tags to a given contact ID
	 *
	 * @access public
	 * @return bool
	 */

	public function apply_tags( $tags, $user_id = false ) {

		if( $user_id == false )
			$user_id = get_current_user_id();

		$contact_id = $this->get_contact_id( $user_id );

		// If no contact ID, don't try applying tags
		if($contact_id == false)
			return false;

		// Save locally before trying to update CRM
		$user_tags = $this->get_tags( $user_id );

		if(empty($user_tags))
			$user_tags = array();

		$user_tags = array_unique(array_merge($user_tags, $tags));

		update_user_meta($user_id, wp_fusion()->crm->slug . '_tags', $user_tags);

		wp_fusion()->crm->apply_tags( $tags, $contact_id );

		do_action('wpf_tags_applied', $user_id, $tags);
		do_action('wpf_tags_modified', $user_id, $user_tags );

		return true;

	}


	/**
	 * Removes an array of tags from a given contact ID
	 *
	 * @access public
	 * @return bool
	 */

	public function remove_tags( $tags, $user_id = false ) {

		if( $user_id == false )
			$user_id = get_current_user_id();

		$contact_id = $this->get_contact_id( $user_id );

		// If no contact ID, don't try applying tags
		if($contact_id == false)
			return false;

		// Save locally before trying to update CRM
		$user_tags = $this->get_tags( $user_id );

		$user_tags = array_diff($user_tags, $tags);

		update_user_meta($user_id, wp_fusion()->crm->slug . '_tags', $user_tags);

		wp_fusion()->crm->remove_tags( $tags, $contact_id );

		do_action('wpf_tags_removed', $user_id, $tags);
		do_action('wpf_tags_modified', $user_id, $user_tags );

		return true;

	}


	/**
	 * Gets and saves updated user meta from the CRM
	 *
	 * @access public
	 * @return void
	 */

	public function pull_user_meta( $user_id ) {

		$contact_id = $this->get_contact_id( $user_id );

		$user_meta = wp_fusion()->crm->load_contact( $contact_id );

		$user_meta = apply_filters( 'wpf_pulled_user_meta', $user_meta, $user_id );

		// If there was an error or no elligible fields
		if( $user_meta == false )
			return false;

		$this->set_user_meta( $user_id, $user_meta );

		do_action( 'wpf_user_updated', $user_id );

		return $user_meta;

	}

	/**
	 * Sets an array of meta data for the user
	 *
	 * @access public
	 * @return void
	 */

	public function set_user_meta( $user_id, $user_meta ) {

		foreach( $user_meta as $key => $value ) {

			if($key == 'user_pass' && !empty($value)) {
				wp_set_password( $value, $user_id );
			} elseif( $key == 'display_name' ) {
				wp_update_user( array( 'ID' => $user_id, 'display_name' => $value ) );
			} elseif( $key == 'user_email' ) {
				wp_update_user( array( 'ID' => $user_id, 'user_email' => $value ) );
				update_user_meta( $user_id, $key, $value );
			} else {
				update_user_meta( $user_id, $key, $value );
			}

			do_action( 'wpf_user_meta_updated', $user_id, $key, $value );

		}

	}


	/**
	 * Pushes a single user meta field to the CRM
	 *
	 * @access public
	 * @return void
	 */

	public function push_user_meta_single( $meta_id, $object_id, $meta_key, $_meta_value ) {

		if(wp_fusion()->settings->get('push_all_meta') != true)
			return;

		$this->push_user_meta($object_id, array($meta_key => $_meta_value));

	}


	/**
	 * Sends updated user meta to CRM
	 *
	 * @access public
	 * @return void
	 */

	public function push_user_meta( $user_id, $user_meta = false ) {

		if(wp_fusion()->settings->get('push') != true)
			return;

		// If we're submitting from a built in profile edit page
		if(isset($_POST['action']) && ($_POST['action'] == 'createuser' || $_POST['action'] == 'update') && $user_meta == false) {

			$user_meta = $_POST;

		} elseif (empty($user_meta)) {

			// If nothing's been supplied, get the latest from the DB
			$user_meta = array_map( array($this, 'map_user_meta'), get_user_meta( $user_id ) );
			$userdata = get_userdata( $user_id );
			$user_meta['role'] = $userdata->roles[0];

		}

		$user_meta = apply_filters( 'wpf_user_update', $user_meta, $user_id );

		$contact_id = $this->get_contact_id( $user_id );

		if(empty($contact_id))
			return;

		$result = wp_fusion()->crm->update_contact( $contact_id, $user_meta );

		if( $result == true )
			do_action( 'wpf_user_updated', $user_id );


	}

	/**
	 * Applies tags when a page is viewed
	 *
	 * @access public
	 * @return void
	 */

	public function apply_tags_on_view() {

		if( is_admin() || !is_singular() || !is_user_logged_in() )
			return;

		global $post;
		$wpf_settings = get_post_meta($post->ID, 'wpf-settings', true);

		if(isset($wpf_settings['apply_tags'])) {

			if(empty($wpf_settings['apply_delay'])) {

				$this->apply_tags($wpf_settings['apply_tags'], get_current_user_id());

			} else {

				wp_enqueue_script( 'wpf-apply-tags', WPF_DIR_URL . '/assets/js/wpf-apply-tags.js', array('jquery'), WP_FUSION_VERSION, true );

				$localize_data = array(
						'ajaxurl' 	 => admin_url( 'admin-ajax.php' ),
						'user_id' 	 => get_current_user_id(),
						'tags'		 => $wpf_settings['apply_tags'],
						'delay'		 => $wpf_settings['apply_delay']
					);

				wp_localize_script( 'wpf-apply-tags', 'wpf_ajax', $localize_data );

			}

		}


	}

	/**
	 * Imports a user
	 *
	 * @access public
	 * @return int User ID of newly created user
	 */

	public function import_user( $contact_id, $send_notification = false, $role = 'subscriber' ) {

		// First see if user already exists
		$users = get_users( array( 'meta_key' => wp_fusion()->crm->slug . '_contact_id', 'meta_value' => $contact_id, 'fields' => array('ID') ) );

		if(!empty($users)) {

			$this->pull_user_meta( $users[0]->ID );
			$this->get_tags( $users[0]->ID, true );

			return $users[0]->ID;

		}

		$user_meta = wp_fusion()->crm->load_contact( $contact_id );

		// See if user with matching email exists
		$user = get_user_by( 'email', $user_meta['user_email'] );

		if(is_object($user)) {

			$user_meta = apply_filters( 'wpf_pulled_user_meta', $user_meta, $user->ID );

			update_user_meta( $user->ID, wp_fusion()->crm->slug . '_contact_id', $contact_id );
			$this->set_user_meta( $user->ID, $user_meta );
			$this->get_tags( $user->ID, true );

			return $user->ID;

		}

		// Generate a password if one hasn't been supplied
		if(!isset($user_meta['user_pass']) || empty($user_meta['user_pass']))
			$user_meta['user_pass'] = wp_generate_password( $length = 12, $include_standard_special_chars = false );

		// If user name is set
		if(!isset($user_meta['user_login']))
			$user_meta['user_login'] = $user_meta['user_email'];

		if(!empty($role))
			$user_meta['role'] = $role;

		// Set contact ID
		$user_meta[ wp_fusion()->crm->slug . '_contact_id'] = $contact_id;

		// Apply filters
		$user_meta = apply_filters('wpf_import_user', $user_meta );

		// Prevent the default registration hook from running
		remove_action( 'user_register', array($this, 'user_register'), 20 );

		// Insert user and store meta
		$user_id = wp_insert_user( $user_meta );

		// Save any custom fields (wp insert user ignores them)
		$this->set_user_meta( $user_id, $user_meta );

		// Get tags
		$this->get_tags($user_id, true);

		// Send notification
		if($send_notification == true)
			wp_new_user_notification( $user_id, null, 'both' );

		do_action( 'wpf_user_imported', $user_id, $user_meta );

		return $user_id;


	}


	/**
	 * Returns generated password to CRM
	 *
	 * @access public
	 * @return void
	 */

	public function return_password( $user_id, $user_meta ) {

		if(wp_fusion()->settings->get('return_password') != true)
			return;

		wp_fusion()->crm->update_contact( $user_meta[wp_fusion()->crm->slug . '_contact_id'], array('user_pass' => $user_meta['user_pass']) );

	}


}