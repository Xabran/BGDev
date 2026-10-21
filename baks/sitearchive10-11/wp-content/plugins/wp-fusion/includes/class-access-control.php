<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_Access_Control {

	public function __construct() {

		add_filter( 'post_link', array( $this, 'rewrite_permalinks'), 10, 3 );
		add_filter( 'page_link', array( $this, 'rewrite_permalinks'), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'rewrite_permalinks'), 10, 3 );
		add_filter( 'wp_get_nav_menu_items', array($this, 'hide_menu_items'), 10, 3 );

		add_action( 'template_redirect', array( $this, 'template_redirect') );
		add_filter( 'wpf_begin_redirect', array($this, 'begin_redirect'), 5 );

	}


	/**
	 * Checks if a user can access a given post
	 *
	 * @access public
	 * @return bool Whether or not a user can access specified content.
	 */

	public function user_can_access($post_id, $user_id = false) {

		if($post_id == null)
			return false;

		$post_restrictions = get_post_meta( $post_id, 'wpf-settings', true );

		// If content isn't locked
		if(empty($post_restrictions) || !isset($post_restrictions['lock_content']) || $post_restrictions['lock_content'] != true)
			return true;

		// If not logged in
		if( !is_user_logged_in() )
			return false;

		if($user_id == false)
			$user_id = get_current_user_id();

		// If no tags specified for restriction, but user is logged in, allow access
		if(!isset($post_restrictions['allow_tags']) || empty($post_restrictions['allow_tags']))
			return apply_filters( 'wpf_user_can_access', true, $user_id, $post_id );

		// If admins are excluded from restrictions
		if(wp_fusion()->settings->get('exclude_admins') == true) {

			$user = get_userdata( $user_id );
			if(in_array('administrator', $user->roles ))
				return true;

		}

		$user_tags = wp_fusion()->user->get_tags( $user_id );

		// If user has no valid tags
		if($user_tags == false)
			return apply_filters( 'wpf_user_can_access', false, $user_id, $post_id );

		$can_access = false;

		foreach( (array) $post_restrictions['allow_tags'] as $tag) {

			if(in_array($tag, $user_tags)) 
				$can_access = true;
		}

		// If no tags matched
		return apply_filters( 'wpf_user_can_access', $can_access, $user_id, $post_id );

	}

	/**
	 * Rewrites permalinks for restricted content
	 *
	 * @access public
	 * @return string URL of permalink
	 */

	public function rewrite_permalinks($url, $post, $leavename) {

		// Don't run on admin
		if(is_admin())
			return $url;

		if(is_object($post))
			$post = $post->ID;

		if($this->user_can_access($post) == true)
			return $url;

		$redirect = $this->get_redirect($post);

		if(!empty($redirect)) {

			return $redirect;

		} else {

			return $url;

		}

	}


	/**
	 * Finds redirect for a given post
	 *
	 * @access public
	 * @return mixed
	 */

	public function get_redirect( $post_id ) {

		$defaults = array(
			'redirect_url' 	=> '',
			'redirect' 		=> ''
		);

		// Get settings
		$post_restrictions = wp_parse_args( get_post_meta($post_id, 'wpf-settings', true), $defaults );

		$default_redirect = wp_fusion()->settings->get('default_redirect');

		// Get redirect URL if one is set
		if(!empty($post_restrictions['redirect_url'])) {

			$redirect = $post_restrictions['redirect_url'];

		} else if(!empty($post_restrictions['redirect'])) {

			// Fix for pre 1.6.2 when redirects were stored as full URLs and not IDs
			if(is_numeric($post_restrictions['redirect'])) {
				$redirect = get_permalink( $post_restrictions['redirect'] );
			} else {
				$redirect = $post_restrictions['redirect'];
			}

		} else if( !empty($default_redirect) ) {

			$redirect = $default_redirect;

		}

		$redirect = apply_filters('wpf_template_redirect', $redirect, $post_id);

		return $redirect;

	}


	/**
	 * Excludes archives from redirects
	 *
	 * @access public
	 * @return bool
	 */

	public function begin_redirect( $bypass ) {

		if(is_archive() || is_home() || is_admin())
			$bypass = true;

		return $bypass;

	}


	/**
	 * Handles redirects for locked content
	 *
	 * @access public
	 * @return mixed
	 */

	public function template_redirect() {

		// Allow bypassing redirect
		$bypass = apply_filters( 'wpf_begin_redirect', false );

		if($bypass == true)
			return;

		global $post;

		if(empty($post))
			return;

		// For inheriting restrictions from another post
		$post_id = apply_filters( 'wpf_redirect_post_id', $post->ID );

		// If user can access, return without doing anything
		if($this->user_can_access($post_id) == true)
			return;

		// Get redirect URL for the post
		$redirect = $this->get_redirect( $post_id );

		// Allow integrations to exlude pages, archives, etc from redirect @todo maybe not necessary
		if($redirect === false)
			return;

		if(!empty($redirect)) {

			wp_redirect($redirect);
			exit();

		} else {

			add_filter( 'the_content', array($this, 'restricted_content_filter') );
			return;

		}

		return;
	}


	/**
	 * Outputs "restricted" message on restricted content
	 *
	 * @access public
	 * @return mixed
	 */

	public function restricted_content_filter( $content ) {

		return stripslashes(wp_fusion()->settings->get('restricted_message'));

	}


	/**
	 * Removes restricted pages from menu
	 *
	 * @access public
	 * @return array Menu items
	 */

	public function hide_menu_items($items, $menu, $args) {

		if(wp_fusion()->settings->get('hide_restricted') == true) {

			foreach ( $items as $key => $item ) {

				if($this->user_can_access($item->object_id) === false)
					unset($items[$key]);

			}

		}

		return $items;

	}

}