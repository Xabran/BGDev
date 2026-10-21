<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'LifterLMS' )) {

	class WPF_LifterLMS extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @since   1.0
		 * @return  void
		 */

		public function init() {

			// Course stuff
			add_action( 'llms_user_enrolled_in_course', array( $this, 'course_begin' ), 10, 2);
			add_action( 'lifterlms_course_completed', array( $this, 'course_lesson_complete' ), 10, 2);
			add_action( 'lifterlms_lesson_completed', array( $this, 'course_lesson_complete' ), 10, 2);

			add_action( 'add_meta_boxes', 		array( $this, 'configure_meta_box' ));
			add_action( 'wpf_meta_box_content', array( $this, 'meta_box_content' ), 40, 2 );

			// Registration / checkout stuff
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
			add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
			
		}

		/**
		 * Triggered when user is enrolled in / begins course
		 *
		 * @access public
		 * @return void
		 */

		public function course_begin( $user_id, $course_id ) {

			$wpf_settings = get_post_meta($course_id, 'wpf-settings', true);

			if(!empty($wpf_settings) && !empty($wpf_settings['apply_tags_start']))
				wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_start'], $user_id );

		}

		/**
		 * Triggered when course / lesson marked complete
		 *
		 * @access public
		 * @return void
		 */

		public function course_lesson_complete( $user_id, $post_id ) {

			$wpf_settings = get_post_meta($post_id, 'wpf-settings', true);

			if(!empty($wpf_settings) && !empty($wpf_settings['apply_tags_complete']))
				wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_complete'], $user_id );

		}

		/**
		 * Remove "apply on view" fields from meta box
		 *
		 * @access public
		 * @return void
		 */

		public function configure_meta_box() {

			global $post;

			if($post->post_type == 'course' || $post->post_type == 'lesson')
				remove_action( 'wpf_meta_box_content', array( wp_fusion()->meta_boxes, 'apply_tags_select'), 30 );

		}


		/**
		 * Adds learndash fields to WPF meta box
		 *
		 * @access public
		 * @return void
		 */

		public function meta_box_content( $post, $settings ) {

			if($post->post_type != 'course' && $post->post_type != 'lesson')
				return;

			if($post->post_type == 'course') {
				echo '<p><label for="wpf-apply-tags-start"><small>Apply these tags when user enrolled in course:</small></label>';
				wpf_render_tag_multiselect( $settings['apply_tags_start'], 'wpf-settings', 'apply_tags_start' );
			}

			echo '<p><label for="wpf-apply-tags-complete"><small>Apply these tags when ' . $post->post_type . ' marked complete:</small></label>';
			wpf_render_tag_multiselect( $settings['apply_tags_complete'], 'wpf-settings', 'apply_tags_complete' );

		}


		/**
		 * Adds LifterLMS meta fields to WPF contact fields list
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$meta_fields['billing_address_1'] 	= array('label' => 'Billing Address 1', 'type' => 'text');
			$meta_fields['billing_address_2'] 	= array('label' => 'Billing Address 2', 'type' => 'text');
			$meta_fields['billing_city'] 		= array('label' => 'Billing City', 'type' => 'text');
			$meta_fields['billing_state'] 		= array('label' => 'Billing State', 'type' => 'text');
			$meta_fields['billing_country'] 	= array('label' => 'Billing Country', 'type' => 'text');
			$meta_fields['billing_postcode'] 	= array('label' => 'Billing Postcode', 'type' => 'text');
			$meta_fields['phone_number'] 		= array('label' => 'Phone Number', 'type' => 'text');

			return $meta_fields;

		}


		/**
		 * Filters user meta on registration
		 *
		 * @access  public
		 * @return  array Post Data
		 */

		public function user_register( $post_data, $user_id ) {

			// Quit if not a LifterLMS registration
			if(!isset($post_data['firstname']))
				return $post_data;

			$post_data['user_pass']			= $post_data['password'];
			$post_data['first_name']		= $post_data['firstname'];
			$post_data['last_name']			= $post_data['lastname'];
			$post_data['billing_postcode']	= $post_data['billing_zip'];
			$post_data['phone_number']		= $post_data['phone'];

			return $post_data;

		}
			
	}

	new WPF_LifterLMS;

}