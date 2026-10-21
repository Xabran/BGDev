<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'acf' )) {
	
	class WPF_ACF extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			$this->context 	= 'acf';

			add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 10, 2 );
			add_filter( 'wpf_configure_sections', array($this, 'configure_sections'), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ), 10 );
			add_action( 'wpf_user_meta_updated', array( $this, 'user_meta_updated' ), 10, 3 );
			add_action( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );
			add_action( 'wpf_user_register', array( $this, 'user_update' ), 10, 2 );

			add_filter( 'acf/load_value', array( $this, 'pull_field_value' ), 10, 3);
			add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ));

		}

		/**
		 * Add fields to settings page
		 *
		 * @access public
		 * @return array Settings
		 */

		public function register_settings( $settings, $options ) {

			$settings['acf_header'] = array(
					'title'   => __('Advanced Custom Fields Integration', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'heading',
					'section' => 'integrations'
			);

			$settings['acf_pull'] = array(
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
		 * Set field labels from ACF field labels
		 *
		 * @access public
		 * @return array Settings
		 */

		public function set_contact_field_names( $meta_fields ) {

			// Only works with ACF pro
			if(!function_exists('acf_get_field_groups'))
				return $meta_fields;

			// Query ACF for field groups registered on the user edit page
			$field_groups = acf_get_field_groups( array('user_id' => 'new', 'user_form' => 'edit') );

			if(empty($field_groups))
				return $meta_fields;

			foreach( $field_groups as $field_group ) {
				
				$fields = acf_get_fields( $field_group );

				foreach( (array)$fields as $field => $data ) {

					$meta_fields[$data['name']] = array(
						'label'	=> $data['label'],
						'type'	=> $data['type']
						);

				}

			}

			return $meta_fields;


		}

		/**
		 * Removes standard WPF meta boxes from ACF related post types
		 *
		 * @access  public
		 * @return  array Post Types
		 */

		public function unset_wpf_meta_boxes( $post_types ) {

			unset($post_types['acf-field']);
			unset($post_types['acf']);
			unset($post_types['acf-field-group']);
			
			return $post_types;

		}


		/**
		 * Updates an ACF field value from the CRM before display
		 *
		 * @access  public
		 * @return  void
		 */

		public function pull_field_value( $value, $post_id, $field ) {

			// Only run on user edit pages
			if( wp_fusion()->settings->get('acf_pull') == true && strpos( $post_id, 'user_') !== false ) {

				$user_id = substr($post_id, 5);
				$new_value = wp_fusion()->user->pull_user_meta($user_id, $field['name']);

				if($new_value != false && isset($new_value[$field['name']])) {
					$value = $new_value[$field['name']];
				}

			}

			return $value;

		}


		/**
		 * Formats ACF fields from internal forms before sending update to CRM
		 *
		 * @access  public
		 * @return  void
		 */

		public function user_update( $post_data, $user_id ) {

			if(isset($post_data['acf'])) {

				foreach((array)$post_data['acf'] as $field_id => $field_data) {

					$field_object = get_field_object($field_id);
					$post_data[$field_object['name']] = $field_data;
					unset($post_data['acf'][$field_id]);

				}

			}

			return $post_data;

		}


		/**
		 * Updates ACF fields when user meta is loaded from the CRM
		 *
		 * @access  public
		 * @return  void
		 */

		public function user_meta_updated( $user_id, $key, $value ) {

			remove_filter( 'acf/load_value', array($this, 'pull_field_value'), 10 );

			if( get_field_object($key, 'user_' . $user_id) ) {

				$field_object = get_field_object($key, 'user_' . $user_id);
				update_field($field_object['key'], $value, 'user_' . $user_id);

			}

			add_filter( 'acf/load_value', array( $this, 'pull_field_value' ), 10, 3);

		}

	}

	new WPF_ACF;

}