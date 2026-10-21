<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(defined('WS_PLUGIN__S2MEMBER_VERSION')) {
	
	class WPF_S2Member extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			add_filter( 'wpf_user_register', array( $this, 'user_meta_filter' ), 10, 2 );
			add_filter( 'wpf_update_user', array( $this, 'user_meta_filter' ), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

			add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
			add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

			add_action( 'ws_plugin__s2member_after_configure_user_registration', array( $this, 'post_s2_registration' ));
			add_action( 'wpf_user_created', array( $this, 'after_registration' ), 10, 3 );
				
		}

		/**
		 * Stores member level in POST so we can use it to apply tags after registration
		 *
		 * @access public
		 * @return void
		 */

		public function post_s2_registration( $defined_vars ) {

			$_POST['s2_level'] = $defined_vars['level'];

		}

		/**
		 * Triggered after registration, gets user membership level and triggers configured tags
		 *
		 * @access public
		 * @return void
		 */

		public function after_registration( $user_id, $contact_id, $post_data ) {

			$level = $_POST['s2_level'];
			$tags = wp_fusion()->settings->get('s2m_level_' . $level . '_apply_tags');
			wp_fusion()->user->apply_tags($tags, $user_id);

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
		 * Registers additional s2member settings
		 *
		 * @access  public
		 * @return  array Settings
		 */

		public function register_settings( $settings, $options ) {

			$settings['s2m_header'] = array(
					'title'   => __('s2Member Integration', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'heading',
					'section' => 'integrations'
			);

			$settings['s2m_desc'] = array(
					'std'     => 0,
					'type'    => 'paragraph',
					'section' => 'integrations',
					'desc'	  => __('For each membership level you can configure tags to be applied to the user at registration.', 'wp-fusion'),
			);

			for($n = 0; $n <= $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["levels"]; $n++) {

				$settings['s2m_level_' . $n . '_apply_tags'] = array(
						'title'   => __('Level ' . $n . ': ' . format_to_edit($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level".$n."_label"]), 'wp-fusion' ),
						'desc'    => __('', 'wp-fusion' ),
						'std'     => 0,
						'type'    => 'assign_tags',
						'section' => 'integrations'
				);

			}

			return $settings;

		}

		/**
		 * Filters user meta at registration / or on profile updates
		 *
		 * @access  public
		 * @return  array Post Data
		 */

		public function user_meta_filter( $post_data, $user_id ) {

			foreach( $post_data as $key => $value ) {
				if (strpos($key, 'ws_plugin__s2member_custom_reg_field_') !== false) {
					$key = str_replace('ws_plugin__s2member_custom_reg_field_', '', $key);
					$post_data[$key] = $value;
				} elseif (strpos($key, 'ws_plugin__s2member_profile_') !== false) {
					$key = str_replace('ws_plugin__s2member_profile_', '', $key);
					$post_data[$key] = $value;
				}
			}

			return $post_data;

		}

		/**
		 * Adds S2M meta fields to WPF contact fields list
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$fields = json_decode($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["custom_reg_fields"]);

			foreach( $fields as $field ) {
				$meta_fields[$field->id] = array( 'label' => $field->label, 'type' => $field->type );
			}

			$meta_fields['wp_s2member_subscr_id'] 		= array( 'label' => 's2Member Subscriber ID', 'type' => 'text' );
			$meta_fields['wp_s2member_subscr_gateway'] 	= array( 'label' => 's2Member Subscriber Gateway', 'type' => 'text' );
			$meta_fields['wp_s2member_subscr_notes'] 	= array( 'label' => 's2Member Subscriber Notes', 'type' => 'text' );

			return $meta_fields;

		}
		
	}

	new WPF_S2Member;

}