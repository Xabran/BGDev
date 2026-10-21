<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'BuddyPress' )) {
	
	class WPF_BuddyPress extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			add_filter( 'wpf_redirect_post_id', 	array( $this, 'get_bb_page_id' ) );
			add_filter( 'wpf_meta_fields', 			array( $this, 'set_contact_field_names' ), 30 );
			add_filter( 'wpf_pulled_user_meta', 	array( $this, 'pulled_user_meta' ), 10, 2 );

			add_action( 'xprofile_updated_profile', array( $this, 'push_profile_data'), 10, 3);
			add_filter( 'wpf_user_register', 		array( $this, 'user_register' ), 10, 2 );

		}

		/**
		 * Gets page ID for BBP core pages
		 *
		 * @access  public
		 * @return  int Post ID
		 */

		public function get_bb_page_id( $post_id ) {

			if($post_id != 0)
				return $post_id;

			global $bp;
			$post = get_page_by_path( $bp->unfiltered_uri[0] );

			if(!empty($post))
				return $post->ID;

			return $post_id;

		}

		/**
		 * Loads XProfile fields for inclusion in Contact Fields table
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function set_contact_field_names( $meta_fields ) {

			if(!class_exists('BP_XProfile_Data_Template'))
				return $meta_fields;

			$r = array(
				'user_id'                => get_current_user_id(),
				'member_type'            => 'any',
				'fetch_fields'           => true,
				'fetch_field_data'       => true,
				);

			$profile_template = new BP_XProfile_Data_Template( $r );

			foreach($profile_template->groups as $group) {

				if(!empty($group->fields)) {

					foreach($group->fields as $field) {

						$meta_fields['bbp_field_' . $field->id] = array('label' => $field->name, 'type' => 'text');

					}

				}

			}

			return $meta_fields;

		}


		/**
		 * Triggered when BBP profile is updated
		 *
		 * @access  public
		 * @return  void
		 */

		public function push_profile_data( $user_id, $posted_field_ids, $errors ) {

			$update_data = array();

			foreach($_POST as $field_id => $field_data) {

				$update_data['bbp_' . $field_id] = $field_data;

			}

			wp_fusion()->user->push_user_meta( $user_id, $update_data );

		}


		/**
		 * Triggered when new user registered through BBP
		 *
		 * @access  public
		 * @return  array Post Data
		 */

		public function user_register( $post_data, $user_id ) {

			$post_data['user_login'] = $post_data['signup_username'];
			$post_data['user_pass'] = $post_data['signup_password'];
			$post_data['user_email'] = $post_data['signup_email'];

			// Xprofile fields
			foreach($post_data as $field_id => $value) {

				if (strpos($field_id, 'field_') !== false)
					$post_data['bbp_' . $field_id] = $value;

			}

			return $post_data;

		}

		/**
		 * Triggered when user meta is loaded from the CRM
		 *
		 * @access  public
		 * @return  array User Meta
		 */

		public function pulled_user_meta( $user_meta, $user_id ) {

			if(!class_exists('BP_XProfile_ProfileData'))
				return $user_meta;

			foreach($user_meta as $key => $value) {

				if (strpos($key, 'bbp_field_') !== false) {

					$field_id = str_replace('bbp_field_', '', $key);

					$field = new BP_XProfile_ProfileData( $field_id, $user_id );
					$field->value = $value;
					$field->save();

					unset($user_meta[$key]);
				}

			}

			return $user_meta;

		}


	}

	new WPF_BuddyPress;

}