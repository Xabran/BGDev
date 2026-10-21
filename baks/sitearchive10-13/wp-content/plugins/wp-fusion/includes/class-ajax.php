<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_AJAX {

	public function __construct() {

		add_action( 'wp_ajax_apply_tags', array( $this, 'apply_tags' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_async_script' ) );

		// AJAX handlers
		add_action( 'wp_ajax_wpf_async', array( $this, 'process_async' ) );
		add_action( 'wp_ajax_nopriv_wpf_async', array( $this, 'process_async' ) );

	}

	/**
	 * Applies tags to a given user via AJAX call
	 *
	 * @access public
	 * @return void
	 */

	public function apply_tags() {

		$tags = $_POST['tags'];
		$user_id = $_POST['user_id'];

		wp_fusion()->user->apply_tags($tags, $user_id);

		die();

	}

	/**
	 * Adds an asynchronous callback to the async queue
	 *
	 * @access public
	 * @return void
	 */

	public function async_add( $id, $args ) {

		$wpf_async = get_option( 'wpf_async' );

		if(empty($wpf_async))
			$wpf_async = array();

		$wpf_async[$id] = $args;

		update_option( 'wpf_async', $wpf_async );

	}

	/**
	 * Enqueues async scripts if async actions detected
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_async_script() {

		if( !get_option( 'wpf_async' ) )
			return;

		$wpf_async = get_option( 'wpf_async' );

		wp_register_script( 'wpf-async', WPF_DIR_URL . '/assets/js/wpf-async.js', array('jquery'), WP_FUSION_VERSION );

		wp_localize_script( 'wpf-async', 'wpf_async', array('ajaxurl' => admin_url( 'admin-ajax.php' ), 'hooks' => $wpf_async) );

		wp_enqueue_script( 'wpf-async' );

		delete_option( 'wpf_async' );

	}

	/**
	 * Processes async actions
	 *
	 * @access public
	 * @return void
	 */

	public function process_async() {

		$callback = $_POST['data'];

		// Add "doing async" override
		$callback['args'][] = true;

		$class = new $callback['class'];
		call_user_func_array( array($class, $callback['function']), $callback['args'] );

		die();

	}

}