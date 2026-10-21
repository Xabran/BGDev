<?php

/**
 * Runs batch processing jobs (like imports, exports, etc)
 */

class WPF_Batch {

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/

	public function __construct() {

		// Global handlers
		add_action( 'wp_ajax_wpf_batch_init', array( $this, 'batch_init' ) );
		add_action( 'wp_ajax_wpf_batch_step', array( $this, 'batch_step' ) );

		// Built in batch processes
		add_action( 'wpf_batch_users_init', array( $this, 'users_init' ) );
		add_action( 'wpf_batch_users', array( $this, 'users_step' ) );

	}

	/**
	 * Initialize batch process and return count of objects to be processed
	 *
	 * @since 1.0
	 * @return int Count
	*/

	public function batch_init() {

		$hook = $_POST['hook'];
		do_action( 'wpf_batch_' . $hook . '_init' );

		die();

	}

	/**
	 * Runs a single batch step
	 *
	 * @since 1.0
	 * @return void
	*/

	public function batch_step() {

		$hook = $_POST['hook'];

		$args = array(
			'offset'	=> $_POST['offset'],
			'number'	=> $_POST['number']
			);

		do_action( 'wpf_batch_' . $hook, $args );

		die();

	}

	/**
	 * Users batch process init
	 *
	 * @since 1.0
	 * @return int Count
	*/

	public function users_init() {

		$args = array(
			'fields'		=> 'ID',
			'meta_query' 	=> array( array(
					'key' 		=> wp_fusion()->crm->slug . '_contact_id',
					'compare'	=> 'NOT EXISTS'
				) )
			);

		$user_query = new WP_User_Query( $args );
		$total = $user_query->get_total();

		echo $total;

	}

	/**
	 * Users batch process - single step
	 *
	 * @since 1.0
	 * @return void
	*/

	public function users_step( $args ) {

		$args = array(
			'fields'		=> 'ID',
			'orderby'		=> 'ID',
			'number'		=> $args['number'],
			'meta_query' 	=> array( array(
					'key' 		=> wp_fusion()->crm->slug . '_contact_id',
					'compare'	=> 'NOT EXISTS'
				) )
			);

		$users = get_users( $args );

		$_POST = array();
		foreach( $users as $user_id ) {
			wp_fusion()->user->user_register( $user_id, null, true );
		}


	}


}