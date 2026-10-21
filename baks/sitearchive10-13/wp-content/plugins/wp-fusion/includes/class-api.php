<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_API {

	public function __construct() {

		add_action( 'init', array( $this, 'get_actions' ) );

		add_action( 'wpf_update', array( $this, 'update_user' ) );
		add_action( 'wpf_add', array( $this, 'add_user' ) );

		// Import / update user actions
		add_action( 'wp_ajax_nopriv_wpf_update_user', array( $this, 'update_user' ) );
		add_action( 'wp_ajax_nopriv_wpf_add_user', array( $this, 'add_user' ) );

	}


	/**
	 * Gets actions passed as query params
	 *
	 * @access public
	 * @return void
	 */

	public function get_actions() {

		if ( isset( $_REQUEST['wpf_action'] ) ) {

			if( !isset($_REQUEST['access_key']) || $_REQUEST['access_key'] != wp_fusion()->settings->get('access_key') )
				wp_die('Invalid Access Key');

			if( has_action('wp_ajax_nopriv_wpf_' . $_REQUEST['wpf_action'] ) ) {
				do_action( 'wp_ajax_nopriv_wpf_' . $_REQUEST['wpf_action'] );
			} else {
				do_action( 'wpf_' . $_REQUEST['wpf_action'] );
			}

		}

	}


	/**
	 * Called by CRM HTTP Posts to update a user
	 *
	 * @access public
	 * @return null
	 */

	public function update_user() {

		$post_data = apply_filters( 'wpf_crm_post_data', $_REQUEST );

		$args = array(
				'meta_key'		=> wp_fusion()->crm->slug . '_contact_id',
				'meta_value'	=> $post_data['contact_id'],
				'fields'		=> array('ID')
			);

		$users = get_users($args);

		// If user is found
		if(isset($users[0])) {

			$user_meta = wp_fusion()->user->pull_user_meta($users[0]->ID);
			$tags = wp_fusion()->user->get_tags( $users[0]->ID, true );

			wp_die('<h3>Success</h3>Updated user meta:<pre>' . print_r($user_meta, true) . '</pre><br />Updated tags:<pre>' . print_r($tags, true) . '</pre>', 'Success', 200);

		} else {
			wp_die('No matching user found');
		}
	}


	/**
	 * Called by CRM HTTP Posts to add a user
	 *
	 * @access public
	 * @return null
	 */

	public function add_user() {

		$post_data = apply_filters( 'wpf_crm_post_data', $_REQUEST );

		if(!isset($post_data['send_notification']))
			$post_data['send_notification'] = false;

		if(!isset($post_data['role']))
			$post_data['role'] = 'subscriber';

		$user_id = wp_fusion()->user->import_user($post_data['contact_id'], $post_data['send_notification'], $post_data['role']);

		if ( is_multisite() )
			$result = add_user_to_blog(get_current_blog_id(), $user_id, $post_data['role']);

		wp_die('<h3>Success</h3>User imported with ID ' . $user_id, 'Success', 200);

	}

}


new WPF_API;