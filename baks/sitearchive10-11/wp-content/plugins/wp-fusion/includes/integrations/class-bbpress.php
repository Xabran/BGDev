<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'bbPress' )) {
	
	class WPF_bbPress extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			// Settings
			add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
			add_filter( 'wpf_configure_sections', array($this, 'configure_sections'), 10, 2 );
			add_action( 'show_field_bbp_allow_tags', array($this, 'show_field_bbp_allow_tags'), 10, 2);

			add_filter( 'bbp_get_forum_class', array( $this, 'get_forum_class' ), 10, 2 );
			add_filter( 'wpf_template_redirect', array( $this, 'template_redirect'), 10, 2 );

			add_filter( 'wpf_begin_redirect', array($this, 'begin_redirect') );
			add_filter( 'wpf_redirect_post_id', array( $this, 'redirect_post_id') );
			add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_meta_boxes' ));


		}

		/**
		 * Registers bbPress settings
		 *
		 * @access  public
		 * @return  array Settings
		 */

		public function register_settings( $settings, $options ) {

			$settings['bbp_header'] = array(
					'title'   => __('bbPress Integration', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'heading',
					'section' => 'integrations'
			);

			$settings['bbp_lock'] = array(
					'title'   => __('Restrict Access', 'wp-fusion' ),
					'desc'    => __('Restrict access to forums archive (' . home_url() . '/forums/)', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'checkbox',
					'section' => 'integrations',
					'unlock'  => array('bbp_allow_tags', 'bbp_redirect')
			);

			$settings['bbp_allow_tags'] = array(
					'title'   => __('Required tags (any)', 'wp-fusion' ),
					'desc'    => __('If the user doesn\'t have any of the tags specified, they will be redirected to the URL below.', 'wp-fusion' ),
					'type'    => 'multi_select',
					'choices' => $options['available_tags'],
					'section' => 'integrations'
			);

			$settings['bbp_redirect'] = array(
					'title'   => __('Redirect URL', 'wp-fusion' ),
					'type'    => 'text',
					'section' => 'integrations'
			);

			if(!isset($options['bbp_lock']) || $options['bbp_lock'] != 1) {
				$settings['bbp_allow_tags']['disabled'] = true;
				$settings['bbp_redirect']['disabled'] = true;
			}

			return $settings;

		}

		/**
		 * Shows assign tags field
		 *
		 * @access public
		 * @return mixed
		 */

		public function show_field_bbp_allow_tags($id, $field) {

			$settings = wp_fusion()->settings->get($id);

			if(empty($settings))
				$settings = array();

			wpf_render_tag_multiselect( $settings, 'wpf_options', $id, null, $field['disabled'] );

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
		 * Removes standard WPF meta boxes from topic post types
		 *
		 * @access  public
		 * @return  array Post Types
		 */

		public function unset_meta_boxes( $post_types ) {

			unset($post_types['topic']);

			return $post_types;

		}

		/**
		 * Sets topics to inherit permissions from their forums
		 *
		 * @access  public
		 * @return  int Post ID
		 */

		public function redirect_post_id( $post_id ) {

			$forum_id = get_post_meta( $post_id, '_bbp_forum_id', true );

			if(!empty($forum_id))
				$post_id = $forum_id;

			// Remove post ID on archives
			if(bbp_is_forum_archive())
				$post_id = null;

			return $post_id;

		}

		/**
		 * Enables redirects for bbP forum archives
		 *
		 * @access public
		 * @return bool
		 */

		public function begin_redirect( $bypass ) {

			if(bbp_is_forum_archive())
				$bypass = false;

			return $bypass;

		}

		/**
		 * Disables redirects on bbP forum archives
		 *
		 * @access  public
		 * @return  array Classes
		 */

		public function template_redirect( $redirect, $post_id ) {

			if( bbp_is_forum_archive() ) {

				// Check if forum archive is locked
				if(wp_fusion()->settings->get('bbp_lock') == true) {
					
					$redirect = wp_fusion()->settings->get('bbp_redirect');

					if( !is_user_logged_in() )
						return $redirect;

					$user_id = get_current_user_id();

					// If admins are excluded from restrictions
					if(wp_fusion()->settings->get('exclude_admins') == true) {

						$user = get_userdata( $user_id );
						if(in_array('administrator', $user->roles ))
							return false;

					}

					$user_tags = wp_fusion()->user->get_tags( $user_id );

					// If user has no valid tags
					if($user_tags == false)
						return $redirect;

					$allow_tags = wp_fusion()->settings->get('bbp_allow_tags');

					foreach( (array) $allow_tags as $tag) {

						if(in_array($tag, $user_tags))
							return false;
					}

					return $redirect;

				} else {

					// If forum index isn't locked
					return false;

				}


			} else {

				return $redirect;

			}

		}

		/**
		 * Applies a class to bbPress forums if they're locked
		 *
		 * @access  public
		 * @return  array Classes
		 */

		public function get_forum_class( $classes, $forum_id ) {

			if( !wp_fusion()->access->user_can_access( $forum_id )) {
				$classes[] = 'wpf-locked';
			}

			return $classes;

		}


	}

	new WPF_bbPress;

}