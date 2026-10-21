<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if ( function_exists( 'userpro_init' ) ) {
	
	class WPF_UserPro extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @since   1.0
		 * @return  void
		 */

		public function init() {

			add_filter( 'userpro_form_validation', array($this, 'form_submitted'), 50, 2);
			add_filter( 'userpro_before_value_is_displayed', array( $this, 'load_new_value' ), 99, 4);

			add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 10, 2 );
			add_filter( 'wpf_configure_sections', array($this, 'configure_sections'), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ), 30 );
			add_filter( 'wpf_user_register', array( $this, 'filter_form_fields'), 10, 2 );
			add_filter( 'wpf_user_update', array( $this, 'filter_form_fields'), 10, 2 );

		}

		/**
		 * Add fields to settings page
		 *
		 * @access public
		 * @return array Settings
		 */

		public function register_settings( $settings, $options ) {

			$settings['userpro_header'] = array(
					'title'   => __('UserPro Integration', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'heading',
					'section' => 'integrations'
			);

			$settings['userpro_pull'] = array(
					'title'   => __('Pull', 'wp-fusion' ),
					'desc'    => __('Update the local profile data for a given user from the CRM before displaying. May slow down profile load times.', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'checkbox',
					'section' => 'integrations'
			);

			return $settings;

		}

		/**
		 * Adds Integrations tab if not already present
		 *
		 * @access public
		 * @return void
		 */

		public function configure_sections($page, $options) {

			if(!isset($page['sections']['integrations']))
				$page['sections'] = wp_fusion()->settings->insert_setting_after('contact-fields', $page['sections'], array('integrations' => __('Integrations', 'wp-fusion' )));

			return $page;

		}

		/**
		 * Set field labels from UM field labels
		 *
		 * @access public
		 * @return array Settings
		 */

		public function set_contact_field_names( $meta_fields ) {

			$userpro_fields = get_option('userpro_fields');

			foreach ((array)$userpro_fields as $key => $field) {

				if(!is_array($field))
					$field = array('label' => '', 'type' => 'text');

				if(!isset($field['label']))
					$field['label'] = '';

				if(!isset($field['type']))
					$field['type'] = 'text';

				$meta_fields[$key] = array(
					'label'	=> $field['label'],
					'type'	=> $field['type']
					);

			}

			return $meta_fields;

		}

		/**
		 * Filters registration data before sending to the CRM
		 *
		 * @access public
		 * @return array Registration data
		 */

		public function filter_form_fields( $post_data, $user_id ) {

			$unique_id = '-' . $post_data['unique_id'];
			
			$registration_data = array();

			foreach($post_data as $key => $value) {

				if(substr( $key, -strlen( $unique_id ) ) == $unique_id) {

					// Trim the unique ID from the end of the string
					$key = substr($key, 0, -strlen($unique_id));

					$registration_data[$key] = $value;

				}

			}

			return $registration_data;

		}


		/**
		 * Sends UserPro submitted form data to the CRM
		 *
		 * @access public
		 * @return array Errors
		 */

		public function form_submitted( $errors, $form ) {

			if(empty($errors) && wp_fusion()->settings->get('push') == true )
				wp_fusion()->user->push_user_meta( get_current_user_id(), $form);


			return $errors;

		}


		/**
		 * Update UserPro form fields before displaying
		 *
		 * @access public
		 * @return void
		 */

		public function load_new_value( $value, $key, $array, $user_id ) {

			if(wp_fusion()->settings->get('userpro_pull') == true) {

				$new_value = wp_fusion()->user->pull_user_meta( $user_id, $key );

				// If there's an error, still display the meta data.
				if($new_value != false)
					$value = $new_value;

			}

			return $value;

		}

	}

	new WPF_UserPro;

}