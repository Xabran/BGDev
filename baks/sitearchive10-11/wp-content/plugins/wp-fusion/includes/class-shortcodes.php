<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_Shortcodes {

    public function __construct() {

		add_shortcode( 'wpf' , array( $this, 'shortcodes'));
		add_shortcode( 'wpf_update_tags' , array( $this, 'shortcode_update_tags'));
		add_shortcode( 'wpf_update_meta' , array( $this, 'shortcode_update_meta'));

    }


	/**
	 * Handles content restriction shortcodes
	 *
	 * @access public
	 * @return mixed
	 */

	public function shortcodes($atts, $content = '') {

		$atts = shortcode_atts( array(
			'tag' 		=> '',
			'not' 		=> '',
			'method'	=> ''
		), $atts, 'wpf' );

		global $current_user;

		// If admins are excluded from restrictions
		if(is_user_logged_in() && wp_fusion()->settings->get('exclude_admins') == true) {

			$user = get_userdata( $current_user->ID );
			if(in_array('administrator', $user->roles ))
				return apply_filters( 'the_content', $content );

		}

		if($current_filter = get_query_var( 'wpf_tag' )) {

			if($current_filter == 'unlock-all') {
				return apply_filters( 'the_content', $content );
			} elseif($current_filter == 'lock-all') {
				return false;
			}
			
		}

		$user_tags = wp_fusion()->user->get_tags( get_current_user_id() );

		if(empty($user_tags))
			$user_tags = array();
		
		$proceed_tag = false;
		$proceed_not = false;

		if(!empty($atts['tag'])) {

			$tags = array();
			$tags_split = explode(',', $atts['tag']);

			// Get tag IDs where needed
			foreach($tags_split as $tag) {
				if(is_numeric($tag)) {
					$tags[] = $tag;
				} else {
					$tags[] = wp_fusion()->user->get_tag_id(trim($tag));
				}
			}

			foreach($tags as $tag) {

				if(in_array($tag, $user_tags)){
					$proceed_tag = true;

					if($atts['method'] == 'any')
						break;

				} else {
					$proceed_tag = false;

					if($atts['method'] != 'any')
						break;
				}
			}

			// If we're overriding
			if($current_filter = get_query_var( 'wpf_tag' )) {
				if($current_filter == $atts['tag']){
					$proceed_tag = true;
				}
			}

		} else {
			$proceed_tag = true;
		}


		if (!empty($atts['not'])) {

			$tags = array();
			$tags_split = explode(',', $atts['not']);

			// Get tag IDs where needed
			foreach($tags_split as $tag) {
				if(is_numeric($tag)) {
					$tags[] = $tag;
				} else {
					$tags[] = wp_fusion()->user->get_tag_id(trim($tag));
				}
			}

			foreach($tags as $tag) {
				if(in_array($tag, $user_tags)){
					$proceed_not = false;
					break;
				} else {
					$proceed_not = true;
				}
			}

			// If we're overriding
			if($current_filter = get_query_var( 'wpf_tag' )) {
				if($current_filter == $atts['not']) {
					return false;
				}
			}

		} else {
			$proceed_not = true;
		}

		if($proceed_tag == true && $proceed_not == true)
			return do_shortcode( $content );

	}


	/**
	 * Update tags shortcode
	 *
	 * @access public
	 * @return null
	 */

	public function shortcode_update_tags($atts) {

		if(is_user_logged_in())
			wp_fusion()->user->get_tags( get_current_user_id(), true );

	}

	/**
	 * Update meta data shortcode
	 *
	 * @access public
	 * @return null
	 */

	public function shortcode_update_meta($atts) {

		if(is_user_logged_in())
			wp_fusion()->user->pull_user_meta( get_current_user_id() );

	}

}

new WPF_Shortcodes;