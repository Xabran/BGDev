<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_TradeGecko_AJAX class
 *
 * Handles all AJAX actions
 *
 */
class WC_TradeGecko_AJAX {

	/**
	 * Add wp_ajax_* hooks
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		$ajax_events = array(
			// Clear logs call
			'clear_sync_logs'             => false,
			// Manual orders sync call
			'manual_sync'                 => false,
			// Manual orders sync call
			'manual_order_update_sync'    => false,
			// Manual inventory sync call
			'inventory_manual_sync'       => false,
			// Manual update order call
			'update_order'                => false,
			// Manual export customer call
			'export_customer'             => false,
			// Manual single product inventory sync
			'single_product_sync'         => false,
			// Clear the rinning process to allow another one to run
			'allow_running_process_again' => false,
			// Export products to CSV
			'export_products_csv'         => false,
			// Clear synced products mapping
			'clear_synced_products'       => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_wc_tradegecko_' . $ajax_event, array( $this, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_wc_tradegecko_' . $ajax_event, array( $this, $ajax_event ) );
			}
		}
	}

	/**
	 * Perform Orders Synchronization
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function manual_sync() {

		$this->verify_request( 'wc_tradegecko_manual_sync_nonce' );

		do_action( 'wc_tradegecko_synchronization' );

		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Perform Orders Synchronization
	 *
	 * @access public
	 * @since  1.5
	 * @return void
	 */
	public function manual_order_update_sync() {

		$this->verify_request( 'wc_tradegecko_manual_order_update_sync_nonce' );

		do_action( 'wc_tradegecko_order_update_synchronization' );

		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Perform Inventory Synchronization
	 *
	 * @access public
	 * @since  1.1
	 * @return void
	 */
	public function inventory_manual_sync() {

		$this->verify_request( 'wc_tradegecko_manual_inventory_sync_nonce' );

		do_action( 'wc_tradegecko_inventory_synchronization' );

		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Clear sync logs
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function clear_sync_logs() {

		$this->verify_request( WC_TradeGecko_Init::$prefix . 'clear_sync_logs' );

		update_option( WC_TradeGecko_Init::$prefix . 'sync_log', array() );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Update a single order. If order is not exported, export it.
	 */
	public function update_order() {

		// Verify nonce
		$this->verify_request( 'wc_tradegecko_sync_order' );

		try {
			if ( ! class_exists( 'TG_Mutex' ) ) {
				require_once( 'mutex/class-tg-mutex.php' );
			}

			// Init the mutex
			$this->single_order_update = new TG_Mutex( 'tradegecko-ajax-single-order-update-mutex' );

			// Lock the process
			if ( $this->single_order_update->lock() ) {

				$order_id    = WC_TradeGecko_Init::get_get( 'order_id' );
				$tg_order_id = WC_TradeGecko_Init::get_post_meta_direct( $order_id, 'synced_order_id', true );

				if ( $tg_order_id ) {
					do_action( 'wc_tradegecko_update_order', WC_TradeGecko_Init::get_get( 'order_id' ) );
				} else {
					do_action( 'wc_tradegecko_export_new_orders', WC_TradeGecko_Init::get_get( 'order_id' ) );
				}

				// Unlock the process when done
				$this->single_order_update->unlock();
			}
		} catch( Exception $e ) {

			// Unlock the process, if there was an error
			$this->single_order_update->unlock();

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', 'Ajax Update/Export Order: '. $order_id .' '. $e->getMessage() );

		}

		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Update a single order. If order is not exported, export it.
	 */
	public function export_customer() {

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_export_customer' );

		$user_id = WC_TradeGecko_Init::get_get( 'user_id' );

		try {

			do_action( 'wc_tradegecko_export_customer', $user_id );

		}
		catch ( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}
		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Sync a single product with TG
	 *
	 * @since 1.2
	 */
	function single_product_sync() {

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_single_product_sync' );

		$product_id = (int) WC_TradeGecko_Init::get_get( 'product_id' );

		try {
			// If we have a variant ID, attempt to sync the product information
			do_action( 'wc_tradegecko_single_product_inventory_sync', $product_id );

		}
		catch ( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}
		wp_safe_redirect( wp_get_referer() );
		exit;

	}

	/**
	 * Clear the rinning process to allow another one to run.
	 *
	 * @since  1.6
	 * @global object $wc_tg_sync
	 */
	function allow_running_process_again() {
		global $wc_tg_sync;

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_allow_running_process_again' );

		$process = WC_TradeGecko_Init::get_get( 'process' );

		// inventory_sync, orders_export, orders_update
		if ( 'order_export' == $process ) {
			$wc_tg_sync->update_is_order_sync_running( 'end' );
		} elseif ( 'order_update' == $process ) {
			$wc_tg_sync->update_is_order_update_sync_running( 'end' );
		} elseif ( 'inventory_sync' == $process ) {
			$wc_tg_sync->update_is_inventory_sync_running( 'end' );
		} else {
			wp_die( __( 'No Applicable Process.', WC_TradeGecko_Init::$text_domain ) );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Export all products into CSV
	 *
	 * @since  1.6
	 */
	function export_products_csv() {
		// Verify nonces
		$this->verify_request( 'wc_tradegecko_export_products_csv' );

		// Try to set the execution time to unlimitted.
		@set_time_limit( 0 );

		$args = array(
			'fields'      => 'ids',
			'post_type'   => 'product',
			'post_status' => 'publish',
			'nopaging'    => true,
		);

		$query = new WP_Query( $args );

		if ( 0 < $query->post_count ) {

			$start_time = microtime( true );

			$export = new WC_TradeGecko_Export_Products_CSV( $query->posts );

			// Get the CSV string
			$csv = $export->get_products_csv();

			$end_time = microtime( true );

			$total_time = $end_time - $start_time;

			// Add log
			WC_TradeGecko_Init::add_log( 'Products were exported to CSV. Time in seconds: ' . $total_time );
			WC_TradeGecko_Init::add_sync_log( 'Message', 'Products were successfully exported to CSV.' );

			// Output the csv file
			$export->get_csv_file( $csv );

		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Clear the variant ids from all synced products.
	 * Allows the products to be synced to TG from the ground up.
	 *
	 * @since  1.6
	 * @global object $wpdb
	 */
	function clear_synced_products() {

		// Verify nonces
		$this->verify_request( 'wc_tradegecko_clear_synced_products' );

		global $wpdb;

		$query = "UPDATE $wpdb->postmeta as meta
			SET meta.meta_value = ''
			WHERE meta.meta_key = '_wc_tradegecko_variant_id'";

		$wpdb->query( $query );

		// Add log
		WC_TradeGecko_Init::add_log( 'All Products have been unsynced.' );
		WC_TradeGecko_Init::add_sync_log( 'Message', 'All Products have been unsynced.' );

		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	/**
	 * Check if the request is from an admin or a user that with suffitient rights. <br/>
	 * Varify the _wpnonce.
	 *
	 * @access private
	 * @since  1.0
	 *
	 * @param string $action - Nonce the ajax action is performed with
	 *
	 * @return void
	 */
	private function verify_request( $action ) {

		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', WC_TradeGecko_Init::$text_domain ) );
		}

		if ( ! wp_verify_nonce( ( WC_TradeGecko_Init::get_get( '_wpnonce' ) ), $action ) ) {
			wp_die( __( 'Cannot verify the request, please go back and try again.', WC_TradeGecko_Init::$text_domain ) );
		}
	}

}

new WC_TradeGecko_Ajax();