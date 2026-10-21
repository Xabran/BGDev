<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_Sync
 * Class to handle all sync actions.
 *
 * @since 1.0
 */
class WC_TradeGecko_Sync {

	/** TradeGecko order IDs */
	public $tg_order_ids = array();

	/** Holds WC order IDs of not exported orders */
	public $not_exported_order_ids = array();

	/** TradeGecko order IDs to WC order IDs mapping  */
	public $tg_id_to_order_id_mapping = array();

	public function __construct() {

		$this->inventory_sync		= WC_TradeGecko_Init::get_setting( 'inventory_sync' );
		$this->orders_sync		= WC_TradeGecko_Init::get_setting( 'orders_sync' );
		$this->enable			= WC_TradeGecko_Init::get_setting( 'enable' );
		$this->product_price_sync	= WC_TradeGecko_Init::get_setting( 'product_price_sync' );
		$this->product_title_sync	= WC_TradeGecko_Init::get_setting( 'product_title_sync' );
		$this->sync_fulfillments	= WC_TradeGecko_Init::get_setting( 'order_fulfillment_sync' );
		$this->allow_sale_price_mapping	= WC_TradeGecko_Init::get_setting( 'allow_sale_price_mapping' );
		$this->regular_price_id		= WC_TradeGecko_Init::get_setting( 'regular_price_id' );
		$this->sale_price_id		= WC_TradeGecko_Init::get_setting( 'sale_price_id' );
		$this->available_currency_id	= WC_TradeGecko_Init::get_setting( 'available_currency_id' );
		$this->stock_location_id	= WC_TradeGecko_Init::get_setting( 'stock_location_id' );
		$this->sync_products_in_cart	= WC_TradeGecko_Init::get_setting( 'sync_products_in_cart' );
		$this->order_line_items_sync	= WC_TradeGecko_Init::get_setting( 'order_line_items_sync' );
		$this->order_line_items_update_direction = WC_TradeGecko_Init::get_setting( 'order_line_items_update_direction' );

		// Order Export
		add_action( 'wc_tradegecko_synchronization', array( $this, 'wc_tradegecko_automatic_order_export_sync' ) );

		// Order Update
		add_action( 'wc_tradegecko_order_update_synchronization', array( $this, 'wc_tradegecko_automatic_order_update_sync' ) );

		// Inventory Sync
		add_action( 'wc_tradegecko_inventory_synchronization', array( $this, 'wc_tradegecko_automatic_inventory_sync' ) );

		// Single Product Inventory Sync
		add_action( 'wc_tradegecko_single_product_inventory_sync', array( $this, 'wc_tradegecko_single_product_inventory_sync' ) );

		if ( $this->orders_sync ) {
			// Action on creating new order
			add_action( 'wc_tradegecko_export_new_orders', array( $this, 'process_new_order_export' ) );

			// Export customer
			add_action( 'wc_tradegecko_export_customer', array( $this, 'export_customer' ) );

			// Action on successful payment
			add_action( 'woocommerce_payment_complete', array( $this, 'process_order_update' ) );
			add_action( 'wc_tradegecko_update_order', array( $this, 'process_order_update' ) );

			add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed',  array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_failed_to_processing', array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_failed_to_completed',  array( $this, 'process_order_update' ) );
		}

		if ( $this->inventory_sync && $this->sync_products_in_cart ) {
			add_action('woocommerce_check_cart_items', array( $this, 'update_cart_item_stock' ), 0 );
		}

		if ( ! class_exists( 'TG_Mutex' ) ) {
			require_once( 'mutex/class-tg-mutex.php' );
		}

		$this->update_orders_mutex = new TG_Mutex( 'tradegecko-update-orders-mutex' );
		$this->export_orders_mutex = new TG_Mutex( 'tradegecko-export-orders-mutex' );
		$this->inventory_mutex = new TG_Mutex( 'tradegecko-inventory-mutex' );

		// Only add the hook, when the feature is enabled and update is wc to tg
		if ( $this->order_line_items_sync && 'wc_to_tg' == $this->order_line_items_update_direction ) {
			add_action( 'woocommerce_saved_order_items', array( $this, 'update_changed_order_items' ), 10, 2 );
		}
	}

	/*=============================
	 * Action Hook Functions
	 ==============================*/

	/**
	 * Process single order update or export.
	 * Function will check, if order was exported and export it, if it's not.
	 * If it is exported, it will perform an order update.
	 *
	 * @since 1.0
	 * @access public
	 * @param type $order_id
	 */
	public function process_order_update( $order_id ) {

		try {

			if ( $this->orders_sync ) {

				// Init the mutex
				$this->export_single_order_mutex = new TG_Mutex( 'tradegecko-export-single-order-mutex' );

				// Lock the process
				if ( $this->export_single_order_mutex->lock() ) {
					// Filter the ids to be updated
					$this->filter_order_ids_to_update( $order_id );

					if ( ! empty ( $this->tg_order_ids ) ) {
						$this->update_orders();
					} elseif ( ! empty( $this->not_exported_order_ids ) ) {
						// Add log
						WC_TradeGecko_Init::add_log(
							'Single Order Export ID: ' .
							print_r( $this->not_exported_order_ids, true )
						);

						foreach ( $this->not_exported_order_ids as $id ) {
							do_action( 'wc_tradegecko_export_new_orders', $id );
						}
					}

					// Set to default values, just in case
					$this->tg_order_ids              = array();
					$this->not_exported_order_ids    = array();
					$this->tg_id_to_order_id_mapping = array();

					WC_TradeGecko_Init::add_sync_log(
						'Message',
						__( 'Single Order Sync Completed', WC_TradeGecko_Init::$text_domain )
					);

					// Add log
					WC_TradeGecko_Init::add_log( 'Single Order Sync Completed' );

					// Unlock the process when done
					$this->export_single_order_mutex->unlock();
				}
			}

		} catch( Exception $e ) {

			// Unlock the process, if there was an error
			$this->export_single_order_mutex->unlock();

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', 'Order: '. $order_id .' '. $e->getMessage() );

		}
	}

	/**
	 * Process the export of the new order to TG.<br />
	 * It will process the customer info and the order export
	 *
	 * @access public
	 * @since 1.0
	 * @param int $order_id The new order ID
	 * @param array $posted Array of the posted order form data
	 */
	public function process_new_order_export( $order_id ) {

		$order = WC_Compat_TG::wc_get_order( $order_id );

		try {

			$this->export_order( $order->id );

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', 'Order: '. $order->get_order_number() .' '. $e->getMessage() );

		}
	}

	/**
	 * Sync the orders. The process will export any new orders.
	 *
	 * @since 1.1
	 * @access public
	 */
	public function wc_tradegecko_automatic_order_export_sync() {

		try {
			// Make sure another order sync is not running
			if ( $this->update_is_order_sync_running( 'begin' ) ) {

				// Attempt to set unlimited time execution
				@set_time_limit( 0 );

				$start_time = microtime(true);

				if ( $this->orders_sync ) {

					// Filter the ids to be updated
					$this->filter_order_ids_to_update();

					// We only need the not exported orders
					if ( ! empty( $this->not_exported_order_ids ) ) {
						// Add log
						WC_TradeGecko_Init::add_log( 'Order Export IDs: ' .
							print_r( $this->not_exported_order_ids, true ) );

						foreach ( $this->not_exported_order_ids as $id ) {
							do_action( 'wc_tradegecko_export_new_orders', $id );
						}

					}

					// Set to default values, just in case
					$this->tg_order_ids = array();
					$this->not_exported_order_ids = array();
					$this->tg_id_to_order_id_mapping = array();

					WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Order Export Sync Completed',
						WC_TradeGecko_Init::$text_domain ) );

					// Add log
					WC_TradeGecko_Init::add_log( 'Order Export Sync Completed' );

				}

				$end_time = microtime(true);

				$total_time = $end_time - $start_time;
				WC_TradeGecko_Init::add_log( 'Orders export sync execution in seconds: '. $total_time );

				$this->update_is_order_sync_running( 'end' );
			}
		} catch( Exception $e ) {

			// Mark the sync process as done
			$this->update_is_order_sync_running( 'end' );

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );
		}
	}

	/**
	 * Sync the orders. The process will update all already exported orders.
	 *
	 * @since 1.5
	 * @access public
	 */
	public function wc_tradegecko_automatic_order_update_sync() {

		try {
			// Make sure another order sync is not running
			if ( $this->update_is_order_update_sync_running( 'begin' ) ) {

				// Attempt to set unlimited time execution
				@set_time_limit( 0 );

				$start_time = microtime(true);

				if ( $this->orders_sync ) {
					// Filter the ids to be updated
					$this->filter_order_ids_to_update();

					// We only need the to update orders
					if ( ! empty ( $this->tg_order_ids ) ) {
						$this->update_orders();
					}

					// Set to default values, just in case
					$this->tg_order_ids = array();
					$this->not_exported_order_ids = array();
					$this->tg_id_to_order_id_mapping = array();

					WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Order Update Sync Completed',
						WC_TradeGecko_Init::$text_domain ) );

					// Add log
					WC_TradeGecko_Init::add_log( 'Order Update Sync Completed' );
				}

				$end_time = microtime(true);

				$total_time = $end_time - $start_time;
				WC_TradeGecko_Init::add_log( 'Orders update sync execution in seconds: '. $total_time );

				$this->update_is_order_update_sync_running( 'end' );
			}
		} catch( Exception $e ) {

			// Mark the sync process as done
			$this->update_is_order_update_sync_running( 'end' );

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );
		}
	}

	/**
	 * Sync the inventory.
	 *
	 * @since 1.1
	 * @access public
	 */
	public function wc_tradegecko_automatic_inventory_sync() {

		try{
			// If the process is marked as running, we will start the sync
			if ( $this->update_is_inventory_sync_running( 'begin' ) ) {


				$start_time = microtime(true);

				// Sync product inventory
				if ( $this->inventory_sync ) {
					// Attempt to set unlimited time execution
					@set_time_limit( 0 );

					$this->sync_inventory();
				}

				$end_time = microtime(true);

				$total_time = $end_time - $start_time;
				WC_TradeGecko_Init::add_log( 'Inventory sync execution in seconds: '. $total_time );

				$this->update_is_inventory_sync_running( 'end' );
			}
		} catch( Exception $e ) {

			// Mark the sync process as done
			$this->update_is_inventory_sync_running( 'end' );

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}

	}

	/**
	 * Sync one product with TG
	 *
	 * @param int $product_id
	 */
	public function wc_tradegecko_single_product_inventory_sync( $product_id ) {

		try{
			$start_time = microtime(true);
			if ( $this->inventory_sync ) {
				$this->sync_single_product_inventory( $product_id );
			}
			$end_time = microtime(true);

			$total_time = $end_time - $start_time;
			WC_TradeGecko_Init::add_log( 'Single product inventory sync execution in seconds: '. $total_time );
		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_log( $e->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );
		}
	}

	/**
	 * Make sure we update the order items, when the merchant saves changes
	 *
	 * To update changes to order items right after they are made,
	 * this method is hooked into the woocommerce_saved_order_items hook.
	 * However, this hook is fired every time order status is updated,
	 * so we can reach a case of infinite loop, where order status is changed back and
	 * forth ( mainly Any Status to Completed and back ) and the method will never end.
	 * To prevent this from happening, we placed a few checks to make sure Completed status
	 * orders are not processed and are the end of the update monitoring.
	 *
	 * @since 1.7
	 * @param type $order_id
	 * @param type $items
	 */
	public function update_changed_order_items( $order_id ) {

		// Skip update, if we are saving the order from edit screen
		if (	'editpost' == WC_TradeGecko_Init::get_post( 'action' ) &&
			'shop_order' == WC_TradeGecko_Init::get_post( 'post_type' ) ) {
			return;
		}

		$order = WC_Compat_TG::wc_get_order( (int) $order_id );

		// We don't update completed orders
		if ( 'completed' == $order->status ) {
			return;
		}

		// Filter the ids to be updated
		$this->filter_order_ids_to_update( $order->id );

		try {
			if ( ! empty ( $this->tg_order_ids ) ) {
				$this->update_orders();

				WC_TradeGecko_Init::add_sync_log( 'Message', sprintf( __( 'Order %s, changes synced with TradeGecko.',
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number() ) );

				// Add log
				WC_TradeGecko_Init::add_log( sprintf( __( 'Order %s, changes synced with TradeGecko.',
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number() ) );
			}

			// Set to default values, just in case
			$this->tg_order_ids = array();
			$this->not_exported_order_ids = array();
			$this->tg_id_to_order_id_mapping = array();
		} catch (Exception $ex) {
			WC_TradeGecko_Init::add_log( $ex->getMessage() );

			WC_TradeGecko_Init::add_sync_log( 'Error', $ex->getMessage() );
		}
	}

	/**
	 * Perform a product inventory sync.
	 *
	 * @since 1.0
	 * @access private
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function sync_inventory() {

		// Pagination request limit
		$limit = apply_filters( 'wc_tradegecko_request_variants_limit', 100 );

		// The page to start the requests from
		$page = 1;
		$is_last_page = false;

		// We need to request all products and update them,
		// but because there is a limit to how many products TG can safely return,
		// we will paginate the requests and do them until we reach the last page.
		while ( ! $is_last_page ) {
			// Pull all variants
			$all_variants_response = WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', '', '', array( 'limit' => $limit, 'page' => $page ) );

			// The response body is the data we need
			$all_variants = WC_TradeGecko_Init::get_decoded_response_body( $all_variants_response );

			if ( isset( $all_variants->error ) ) {
				throw new Exception( sprintf( __( 'Inventory variants could not be pulled from the TradeGecko system.'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$all_variants->error,
					$all_variants->error_description ) );
			}

			if ( 0 < count( $all_variants->variants ) ) {
				$processed_product = array();
				$processed_skus = array();

				foreach ( $all_variants->variants as $variant ) {
					// Process SKU only once
					if ( in_array( $variant->sku, $processed_skus ) ) {
						continue;
					}

					WC_TradeGecko_Init::add_log( 'Find the Product by SKU: '. $variant->sku );

					// Check if we can find the product id from the sku
					$valid_product_ids = $this->get_product_by_sku( $variant->sku );

					WC_TradeGecko_Init::add_log( 'Product is: '. print_r( $valid_product_ids, true ) );

					$processed_skus[] = $variant->sku;

					// If we found the product in the system, we can now update its info
					if ( $valid_product_ids ) {

						$updated_at_least_one = false;
						foreach ( $valid_product_ids as $valid_product_id ) {

							// Get the product object
							if ( function_exists( 'get_product' ) || function_exists( 'wc_get_product' ) ) {

								$product = $this->get_product_to_update( $valid_product_id );

								// Check if we have a product object after we checked all matched IDs
								if ( false == $product ) {
									WC_TradeGecko_Init::add_log( 'Skipping Sync for IDs: '. print_r( $valid_product_id, true ) .' It did not return a proper product object.' );
									continue;
								}

								$product_id = $this->get_product_id( $product );
							} else {
								throw new Exception( sprintf( __( 'WooCommerce essential function "get_product" is missing. '
									. 'You are either using a version older than WooCommerce 2.0 '
									. 'or modified the WooCommerce plugin in someway.', WC_TradeGecko_Init::$text_domain ) ) );
							}

							if ( false == $variant->is_online ) {
								// Unsync product, if it should not be online
								$this->unsync_product( $product );
								continue;
							}

							// Add log
							WC_TradeGecko_Init::add_log( 'Updating product #' . $product_id );
							WC_TradeGecko_Init::add_log( 'Product data from TG: ' . print_r( $variant, true ) );

							// Update all different parts of the product
							$this->update_product_data( $product, $variant );

							$updated_at_least_one = true;
						}

						// Skip the title sync, if we did not update at least one matched product
						if ( ! $updated_at_least_one ) {
							continue;
						}

						// Update product info, if enabled and the product is not precessed already
						if ( $this->product_title_sync && ! in_array( $variant->product_id, $processed_product ) ) {

							// Get the info parent product
							$product_data = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'products', null, $variant->product_id ) );

							WC_TradeGecko_Init::add_log( 'The Parent Product Info Data: ' . print_r( $product_data, true ) );

							if ( isset( $product_data->error ) ) {
								throw new Exception( sprintf( __( 'Could not retrieve main product info of %s.'
									. ' Error Code: %s.'
									. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
									$product_id,
									$product_data->error,
									$product_data->error_description ) );
							} else{
								$this->update_product_title( $product, $variant );

								$processed_product[] = $variant->product_id;
							}
						}
					}
				}
			}

			// Check, if we reached the last page
			if ( ! empty( $all_variants_response['headers']['x-pagination'] ) ) {
				$pagination = json_decode( $all_variants_response['headers']['x-pagination'] );

				$is_last_page = $pagination->last_page;
				$page++;

			} else {
				$is_last_page = true;
			}
		}

		WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Inventory Sync Completed Successfully', WC_TradeGecko_Init::$text_domain ) );

	}

	/**
	 * Sync a single product with Tradegecko
	 *
	 * @since 1.2
	 * @param int $product_id
	 * @throws Exception
	 */
	private function sync_single_product_inventory( $product_id ) {

		$product = WC_Compat_TG::wc_get_product( (int) $product_id );

		// Add log
		WC_TradeGecko_Init::add_log( 'Updating single product #' . $product_id );

		// Get the variant from TG system by SKU
		$variants = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', null, null, array( 'sku' => $product->get_sku() ) ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Product search by SKU. Response: ' . print_r( $variants, true ) );

		if ( isset( $variants->error ) ) {
			throw new Exception( sprintf( __( 'The product SKU(%s) cannot be found in Tradegecko system.'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$product->get_sku(),
				$variants->error,
				$variants->error_description ) );
		}

		$variants = isset( $variants->variants ) ? $variants->variants : $variants->variant;
		if ( 0 < count( $variants ) ) {
			foreach ( $variants as $variant ) {
				// Double check that the product should be synced with the online store
				if ( false == $variant->is_online ) {
					$this->unsync_product( $product );
					continue;
				}

				// Still check that the SKU returned matches out product's
				if ( $product->get_sku() == $variant->sku ) {

					// Update all different parts of the product
					$this->update_product_data( $product, $variant );

					// Update product info, if enabled and the product is not precessed already
					if ( $this->product_title_sync ) {

						// Get the info parent product
						$product_data = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'products', null, $variant->product_id ) );

						WC_TradeGecko_Init::add_log( 'Single Product Parent Product Info Data: ' . print_r( $product_data, true ) );

						if ( isset( $product_data->error ) ) {
							throw new Exception( sprintf( __( 'Could not retrieve main product info of %s.'
								. ' Error Code: %s.'
								. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
								$product_id,
								$product_data->error,
								$product_data->error_description ) );
						} else{
							$this->update_product_title( $product, $variant );
						}
					}
					// In case there are multiple variants, we only need the first found
					break;
				}
			}
		} else {
			throw new Exception( sprintf( __( 'A product with a matching SKU(%s) should be present in '
				. 'Tradegecko system in order to sync the product.', WC_TradeGecko_Init::$text_domain ), $product->get_sku() ) );
		}
	}

	/**
	 * Filter the matched IDs we have and
	 * make sure we have a product that is in publish status and can be updated
	 *
	 * @since 1.5.3
	 * @param object $valid_product_id
	 * @return WC_Product|boolean
	 */
	function get_product_to_update( $valid_product_id ) {
		$found = false;

		$product = WC_Compat_TG::wc_get_product( $valid_product_id->post_id );

		// Break if we have the object and the post(for simple products) or the post parent(for variations) is published
		if ( is_object( $product ) && ( 'publish' == $product->post->post_status ||
						'pending' == $product->post->post_status ||
						'draft' == $product->post->post_status ) ) {
			$found = true;
		}

		return $found ? $product : false;
	}

	/**
	 * Update all different parts of the product in a sync
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param object $variant Tradegecko variant information
	 */
	private function update_product_data( \WC_Product $product, $variant ) {
		// Set product stock
		$this->set_product_stock( $product, $variant );

		// Add the TG variant id to the product meta
		$this->update_product_tg_variant_id( $product, $variant->id );

		// Update stock of the product respecting the max_online parameter
		$this->set_product_manage_stock( $product, $variant );

		// Allow backorders if the 'keep_selling' is enabled
		$this->set_product_keep_selling( $product, $variant );

		// Update price of the product
		$this->update_product_price( $product, $variant );
	}

	/**
	 * Update the product title
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param object $product_data Tradegecko product information
	 */
	private function update_product_title( \WC_Product $product, $product_data ) {
		$product_id = $this->get_product_id( $product );
		$parent_id = $this->get_product_parent_id( $product );

		// Update only if the product title is different
		if( ! empty( $product_data->product ) && $product->get_title() != $product_data->product->name ) {

			WC_TradeGecko_Init::add_log( 'Updating product title to '. $product_data->product->name );

			// Update the product name
			$post_data = array();
			$post_data['ID'] = '' != $parent_id ? $parent_id : $product_id;
			$post_data['post_title'] = $product_data->product->name;

			wp_update_post( $post_data );

		}

	}

	/**
	 * Update product price
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param type $variant Tradegecko variant information
	 */
	private function update_product_price( \WC_Product $product, $variant ) {
		$product_id = $this->get_product_id( $product );

		// Do we sync the prices
		if ( $this->product_price_sync ) {

			// Do we sync Sale Prices
			if ( $this->allow_sale_price_mapping ) {
				$sale_price_id = $this->sale_price_id;

				// Get the sale price
				$sale_price = $this->get_variant_price( $variant, $sale_price_id );

				// Update the sale price
				update_post_meta( $product_id, '_sale_price', $sale_price );

				// We want to update the Sale Price of the product only when the admin has set it.
				// Otherwise, don't update it, leave it as it is currently in WC.
				// If we have a sale price update the main price, too
				if ( '' != $sale_price ) {
					update_post_meta( $product_id, '_price', $sale_price );
				}

			} else {
				$sale_price = get_post_meta( $product_id, '_sale_price', true );
			}

			$regular_price_id = $this->regular_price_id;

			// Get the regular price
			$regular_price = $this->get_variant_price( $variant, $regular_price_id );

			// If we don't have the price mapped by ID, we will not update anything!!!
			if ( '' === $regular_price ) {
				return;
			}

			// Update the Regular Price
			if ( '' != $sale_price ) {
				update_post_meta( $product_id, '_regular_price', $regular_price );
			} else {
				update_post_meta( $product_id, '_regular_price', $regular_price );
				update_post_meta( $product_id, '_price', $regular_price );
			}

		}
	}

	/**
	 * Get the variant price by price_id
	 *
	 * @since 1.4.2
	 * @param object $variant The TG variant object
	 * @param int $price_id The price ID
	 * @return decimal|string
	 */
	public function get_variant_price( $variant, $price_id ){
		$price = '';

		// Get the price from the variant_prices
		// Match the price by the set ID
		foreach( $variant->variant_prices as $variant_price ) {
			if ( $variant_price->price_list_id == $price_id ) {
				$price = $variant_price->value;
				break;
			}
		}

		if ( '' !== $price ) {
			$price = WC_Compat_TG::wc_format_decimal( $price );
		}

		return $price;
	}

	/**
	 * Set the product backorders allowed option
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param type $variant Tradegecko variant information
	 */
	private function set_product_keep_selling( \WC_Product $product, $variant ) {
		$product_id = $this->get_product_id( $product );

		if ( false == $variant->keep_selling && $product->backorders_allowed() ) {
			update_post_meta($product_id, '_backorders', 'no');
		} elseif( true == $variant->keep_selling && ! $product->backorders_allowed() ) {
			if ( 'notify' == WC_TradeGecko_Init::get_setting( 'product_allow_backorders' ) ) {
				update_post_meta($product_id, '_backorders', 'notify');
			} else {
				update_post_meta($product_id, '_backorders', 'yes');
			}
		}
	}

	/**
	 * Update the synced variant ID
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param type $variant_id Tradegecko variant ID
	 */
	private function update_product_tg_variant_id( \WC_Product $product, $variant_id ) {
		$product_id = $this->get_product_id( $product );

		WC_TradeGecko_Init::update_post_meta( $product_id, 'variant_id', $variant_id );
	}

	/**
	 * Set the product manage stock option
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @param type $variant Tradegecko variant information
	 */
	private function set_product_manage_stock( \WC_Product $product, $variant ) {
		$product_id = $this->get_product_id( $product );

		if ( false == $variant->manage_stock && $product->managing_stock() ) {
			update_post_meta( $product_id, '_manage_stock', 'no' );
		} elseif( true == $variant->manage_stock && ! $product->managing_stock() ) {
			update_post_meta( $product_id, '_manage_stock', 'yes' );
		}
	}

	/**
	 * Get the product ID. Looks if the product is variation or not
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @return int The product ID
	 */
	public function get_product_id( \WC_Product $product ) {
		if ( $product instanceof WC_Product_Variation ) {
			$product_id = $product->get_variation_id();
		} else {
			$product_id = $product->id;
		}

		return $product_id;
	}

	/**
	 * Get the product parent ID, if variable
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product
	 * @return int The parent of the given product, if variable.
	 */
	private function get_product_parent_id( \WC_Product $product ) {
		$parent_id = '';
		if ( $product instanceof WC_Product_Variation ) {
			$parent_id = $product->get_variation_id();
		}

		return $parent_id;
	}

	/**
	 * Update the product stock
	 *
	 * @since 1.2
	 * @param \WC_Product $product WC product object
	 * @param object $variant TG variant information
	 * @return void
	 */
	private function set_product_stock( \WC_Product $product, $variant )
	{
		$available_stock = '';

		// Only update stock, if we manage stock
		if ( false == $variant->manage_stock ) {
			return;
		}

		if ( '' != $this->stock_location_id ) {
			$location_id = $this->stock_location_id;

			// Get the product stock
			$available_stock = $this->get_variant_stock_level( $variant, $location_id );
		}

		// If we did not have a match for the stock location,
		// we will not update the product stock.
		if ( '' === $available_stock ) {
			return;
		}

		// Can't have negative stock
		if ( 0 > $available_stock ) {
			$available_stock = 0;
		}

		// Skip if no update is needed
		if( $product->stock === $available_stock ) {
			return;
		}

		if ( empty( $variant->max_online ) ) {
			$product->set_stock ( $available_stock );
		} elseif ( $variant->max_online < $available_stock ) {
			$product->set_stock ( $variant->max_online );
		} else {
			$product->set_stock ( $available_stock );
		}
	}

	/**
	 * Get the product stock by location ID.
	 *
	 * @since 1.4.2
	 * @param object $variant
	 * @param mixed $location_id
	 * @return mixed
	 */
	public function get_variant_stock_level( $variant, $location_id ) {
		$available_stock = '';

		foreach ( $variant->locations as $location ) {
			if ( $location->location_id == $location_id ) {
				$available_stock = (int) $location->stock_on_hand - (int) $location->committed;
				break;
			}
		}

		return $available_stock;
	}

	/**
	 * Save the time when the product was last synced
	 *
	 * @since 1.2
	 * @param \WC_Product $product
	 */
	public function update_last_synced_at( \WC_Product $product )
	{
		$product_id = $this->get_product_id( $product );

		WC_TradeGecko_Init::update_post_meta( $product_id, 'last_syned_at', current_time( 'mysql' ) );
	}

	/**
	 * Retrieve the time when the product was last synced
	 *
	 * @since 1.2
	 * @param \WC_Product $product
	 * @return string
	 */
	private function get_last_synced_at( \WC_Product $product )
	{
		$product_id = $this->get_product_id( $product );

		return WC_TradeGecko_Init::get_post_meta( $product_id, 'last_syned_at', true );
	}

	/**
	 * Update the customer/company main information.
	 *
	 * @since 1.0
	 * @param object $customer_data TG customer data
	 * @param int $tg_customer_id TG customer ID
	 * @param object $customer WP user object
	 * @access private
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function update_customer_main_info( $customer_data, $tg_customer_id, $customer ) {

		$user_meta = array_map( array( $this, 'map_user_array' ), get_user_meta( $customer->ID ) );

		// Check if the main info is different than the one we have
		$main_info_to_update = array();

		if ( ! empty( $customer->user_email ) && $customer->user_email != $customer_data->email ) {
			$main_info_to_update['company']['email'] = $customer->user_email;
		}

		if ( ! empty( $customer->user_url ) && $customer->user_url != $customer_data->website ) {
			$main_info_to_update['company']['website'] = $customer->user_url;
		}

		$found_match = false;

		// Update company name only if it is different than the TG one and it is not empty.
		if ( false == $found_match && ! empty( $user_meta['billing_company'] ) ) {
			if ( $user_meta['billing_company'] != $customer_data->name ) {
				$main_info_to_update['company']['name'] = $user_meta['billing_company'];
			}
			$found_match = true;
		}

		if ( false == $found_match && ( ! empty( $user_meta['billing_first_name'] ) || ! empty( $user_meta['billing_last_name'] ) ) ) {
			if ( $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'] != $customer_data->name ) {
				$main_info_to_update['company']['name'] = $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'];
			}
			$found_match = true;
		}

		if ( false == $found_match && ( ! empty( $user_meta['first_name'] ) || ! empty( $user_meta['last_name'] ) ) ) {
			if ( $user_meta['first_name'] .' '. $user_meta['last_name'] != $customer_data->name ) {
				$main_info_to_update['company']['name'] = $user_meta['first_name'] .' '. $user_meta['last_name'];
			}
			$found_match = true;
		}
		if ( false == $found_match ) {
			$main_info_to_update['company']['name'] = $customer->user_email; // Field is required for each customer
		}

		// Add log
		WC_TradeGecko_Init::add_log( 'Update Main Info for:'. $customer->ID .' Data: '. print_r( $main_info_to_update, true ) );

		// Update the main info only if needed
		if ( ! empty( $main_info_to_update ) ) {

			$update_company = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'companies', $main_info_to_update, $tg_customer_id ) );
			// If error occurred end the process and log the error
			if ( isset( $update_company->error ) ) {
				throw new Exception( sprintf( __( 'Customer info for user "%s" could not be updated.'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$customer->user_login,
					$update_company->error,
					$update_company->error_description ) );
			}

		}

	}

	/**
	 * Export and create customers in TG system.
	 * Customers Shipping and Billing addresses will be created as well, if present.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $customer_id
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	public function export_customer( $customer_id ) {

		$customer_data = get_userdata( (int) $customer_id );

		// Before we even attempt to export this user,
		// make sure he/she does not exist in TG
		$is_tg_customer = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'companies', null, null, array( 'term' => $customer_data->user_email ) ) );

		// If error occurred end the process and log the error
		if ( isset( $is_tg_customer->error ) ) {

			// We don't want to do anything, if the process errors, because we will export the customer anyway.

		} else {
			// If we have a match, then save it to the customer
			$tg_company = isset( $is_tg_customer->company ) ? $is_tg_customer->company : $is_tg_customer->companies;
			if ( 0 < count( $tg_company ) ) {
				// We found a match in TG
				foreach ( $tg_company as $company ) {

					if ( $customer_data->user_email == $company->email ) {

						// Save the TG ID to the User Meta
						$this->save_company_id( $company->id, null, $customer_data->ID  );
						return;

					}
				}
			}
		}

		// Build the customer info
		$customer = $this->build_customer_info_array( $customer_id );

		// Add log
		$exp_cus = ( ! empty( $customer['customer_id'] ) ) ? $customer['customer_id'] : $customer['name'];
		WC_TradeGecko_Init::add_log( 'Export customer ' . $exp_cus );

		$main_info['company'] = array(
			'name'		=> $customer['name'],
			'email'		=> $customer['email'],
			'company_type'	=> 'consumer',
		);

		// Add URL, if needed
		if ( ! empty( $customer['website'] ) ) {
			$main_info['company']['website'] = $customer['website'];
		}

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data: ' . print_r( $main_info, true ) );

		$create_customer = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'companies', $main_info ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data response: ' . print_r( $create_customer, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_customer->error ) ) {
			throw new Exception( sprintf( __( 'Customer "%s" could not be exported.'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$customer['customer_login'],
				$create_customer->error,
				$create_customer->error_description ) );
		}

		// Save customer to the
		$this->save_company_id( $create_customer->company->id, null, $customer['customer_id']  );

		// Build the billing address export
		$billing_address['address'] = $customer['billing_address'];
		$billing_address['address']['company_id'] = $create_customer->company->id;
		$billing_address['address']['label'] = 'Address-'. rand( 00001, 9999999 );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export billing address data: ' . print_r( $billing_address, true ) );

		// Export the billing address
		$create_billing_address = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $billing_address ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export billing address data response: ' . print_r( $create_billing_address, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_billing_address->error ) ) {
			throw new Exception( sprintf( __( 'Could not create Billing address for user "%s".'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$customer['customer_login'],
				$create_billing_address->error,
				$create_billing_address->error_description ) );
		}

		// If the shipping address is the same as the billing address, don't export it.
		if ( $customer['billing_address']['address1'] == $customer['shipping_address']['address1'] ) {
			return;
		}

		// Build the Shipping address for export
		$shipping_address['address'] = $customer['shipping_address'];
		$shipping_address['address']['company_id'] = $create_customer->company->id;
		$shipping_address['address']['label'] = 'Address-'. rand( 00001, 9999999 );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export shipping address data: ' . print_r( $shipping_address, true ) );

		// Export the shipping address
		$create_shipping_address = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $shipping_address ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export shipping address data response: ' . print_r( $create_shipping_address, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_shipping_address->error ) ) {
			throw new Exception( sprintf( __( 'Could not create Shipping address for user "%s".'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$customer['customer_login'],
				$create_shipping_address->error,
				$create_shipping_address->error_description ) );
		}

	}

	/**
	 * Generate the export customer info. Main info and addresses.
	 *
	 * @since 1.0
	 * @access private
	 * @param type $customer_id
	 * @return type
	 */
	private function build_customer_info_array( $customer_id ) {

		$user_data = array();

		$customer = get_userdata( (int) $customer_id );

		$user_meta = array_map( array( $this, 'map_user_array' ), get_user_meta( $customer->ID ) );

		if ( ! empty( $user_meta['billing_company'] ) ) {
			$name = $user_meta['billing_company'];
		} elseif ( ! empty( $user_meta['billing_first_name'] ) || ! empty( $user_meta['billing_last_name'] ) ) {
			$name = $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'];
		} elseif ( ! empty( $user_meta['first_name'] ) || ! empty( $user_meta['last_name'] ) ) {
			$name = $user_meta['first_name'] .' '. $user_meta['last_name'];
		} else {
			$name = $customer->user_email; // field is required for each customer
		}

		$user_data['customer_id'] = $customer->ID;
		$user_data['customer_login'] = $customer->user_login;
		$user_data['name'] = $name;
		$user_data['email'] = $customer->user_email;

		// Add URL, if needed
		if ( ! empty( $customer->user_url ) ) {
			$user_data['website'] = $customer->user_url;
		}

		// Create the customer addresses
		if ( ! empty( $user_meta['billing_address_1'] ) ) {
			$user_data['billing_address'] = array(
				'first_name'	=> $user_meta['billing_first_name'],
				'last_name'	=> $user_meta['billing_last_name'],
				'company_name'	=> $user_meta['billing_company'],
				'address1'	=> $user_meta['billing_address_1'],
				'address2'	=> $user_meta['billing_address_2'],
				'city'		=> $user_meta['billing_city'],
				'country'	=> $user_meta['billing_country'],
				'zip_code'	=> $user_meta['billing_postcode'],
				'state'		=> $user_meta['billing_state'],
				'phone_number'	=> $user_meta['billing_phone'],
				'email'		=> $user_meta['billing_email'],
			);

		} else {
			$user_data['billing_address'] = array(
				'address1'	=> 'empty',
			);
		}

		if ( ! empty( $user_meta['shipping_address_1'] ) ) {
			$user_data['shipping_address'] = array(
				'first_name'	=> $user_meta['shipping_first_name'],
				'last_name'	=> $user_meta['shipping_last_name'],
				'company_name'	=> $user_meta['shipping_company'],
				'address1'	=> $user_meta['shipping_address_1'],
				'address2'	=> $user_meta['shipping_address_2'],
				'city'		=> $user_meta['shipping_city'],
				'country'	=> $user_meta['shipping_country'],
				'zip_code'	=> $user_meta['shipping_postcode'],
				'state'		=> $user_meta['shipping_state'],
			);

		} elseif ( ! empty( $user_meta['billing_address_1'] ) ) {
			// Add the billing address as shipping
			$user_data['shipping_address'] = array(
				'first_name'	=> $user_meta['billing_first_name'],
				'last_name'	=> $user_meta['billing_last_name'],
				'company_name'	=> $user_meta['billing_company'],
				'address1'	=> $user_meta['billing_address_1'],
				'address2'	=> $user_meta['billing_address_2'],
				'city'		=> $user_meta['billing_city'],
				'country'	=> $user_meta['billing_country'],
				'zip_code'	=> $user_meta['billing_postcode'],
				'state'		=> $user_meta['billing_state'],
			);

		} else {
			$user_data['shipping_address'] = array(
				'address1'	=> 'empty',
			);
		}

		return apply_filters( 'wc_tradegecko_customer_info' , $user_data, $customer_id );
	}

	/**
	 * Check and generate the customer info for the order export.
	 *
	 * @since 1.1
	 * @access private
	 * @param object $order The order object
	 * @throws Exception
	 */
	private function get_customer_tg_information( $order ) {

		// Is customer synced with TG
		$tg_customer_id = get_user_meta( $order->user_id, 'wc_tradegecko_customer_id', true );

		// If the customer was exported and has a TG ID
		if ( '' != $tg_customer_id ) {

			$customer = get_userdata( $order->user_id );

			$tg_customer_data = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'companies', null, $tg_customer_id ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'UserID: '. $customer->ID .' TG information: ' . print_r( $tg_customer_data, true ) );

			// If error occurred end the process and log the error
			if ( isset( $tg_customer_data->error ) ) {
				// We could not find the customer by TG ID, so unsync him from WC.
				if ( '404' == $tg_customer_data->error ) {
					update_user_meta( $order->user_id, 'wc_tradegecko_customer_id', '' );

					// Call this functions again, so the customer will be exported.
					$this->get_customer_tg_information( $order );
				} else {
					throw new Exception( sprintf( __( 'Could not retrieve the customer info for user "%s".'
						. ' Error Code: %s.'
						. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
						$customer->user_login,
						$tg_customer_data->error,
						$tg_customer_data->error_description ) );
				}
			}



			// Update main customer info
			$tg_company = isset( $tg_customer_data->company ) ? $tg_customer_data->company : $tg_customer_data->companies;

			// If the user was archived, unsync and export it again.
			if ( 'archived' == $tg_company->status ) {
				update_user_meta( $order->user_id, 'wc_tradegecko_customer_id', '' );

				// Call this functions again, so the customer will be exported.
				$this->get_customer_tg_information( $order );
			}

			$this->update_customer_main_info( $tg_company, $tg_customer_id, $customer );

			// Add customer TG ID to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_id', $tg_customer_id);

			// May be export the customer shipping and billing addresses
			$this->maybe_export_customer_addresses( $tg_customer_id, $order );

		} else {

			// Try to find the customer in TG by email.
			$is_tg_customer = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'companies', null, null, array( 'term' => $order->billing_email ) ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Search for user email: '. $order->billing_email .' Data: '. print_r( $is_tg_customer, true ) );

			// If error occurred end the process and log the error
			if ( isset( $is_tg_customer->error ) ) {
				throw new Exception( sprintf( __( 'Customer look search in TradeGecko system failed. Order: %s.'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$order->get_order_number(),
					$is_tg_customer->error,
					$is_tg_customer->error_description ) );
			}

			$tg_company = isset( $is_tg_customer->company ) ? $is_tg_customer->company : $is_tg_customer->companies;

			$customer_id = '';
			if ( 0 < count( $tg_company ) ) {
				// We have a matched company
				foreach ( $tg_company as $company ) {
					// Double check, the email is the same. Case-insensitive
					if ( strtolower( $order->billing_email ) == strtolower( $company->email ) ) {
						// Save Company ID to the order
						$this->save_company_id( $company->id, $order );

						// Save the company ID to a variable
						$customer_id = $company->id;
						break;
					}
				}
			}

			// Did we have find match?
			if ( '' == $customer_id ) {
				// We did not find a company, so we will export/create a new one
				$customer_id = $this->export_customer_main_info( $order );

				// Save the exported Company ID to the order
				$this->save_company_id( $customer_id, $order );
			}

			// Export company addresses, if needed
			$this->maybe_export_customer_addresses( $customer_id, $order );
		}

	}

	/**
	 * Save the company ID to the order and may be to the user.
	 *
	 * @since 1.1
	 * @access private
	 * @param string 	$company_id
	 * @param mixed 	$order
	 * @param int 		$user_id
	 */
	private function save_company_id( $company_id, $order = null, $user_id = null  ) {
		if ( null != $order ) {
			// Add customer TG ID to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_id', $company_id );

			if ( 0 != $order->user_id ) {
				// Add the TG ID to the customer, too.
				update_user_meta( $order->user_id, 'wc_tradegecko_customer_id', $company_id );
			}
		} elseif ( null != $user_id ) {
			update_user_meta( $user_id, 'wc_tradegecko_customer_id', $company_id );
		}
	}

	/**
	 * Export the customer main information
	 *
	 * @since 1.1
	 * @access private
	 * @param object $order
	 * @return int TG customer ID
	 * @throws Exception
	 */
	private function export_customer_main_info( $order ) {
		$name = '';

		if ( ! empty( $order->billing_company ) ) {
			$name = $order->billing_company;
		} elseif ( ! empty( $order->billing_first_name ) || ! empty( $order->billing_last_name ) ) {
			$name = $order->billing_first_name .' '. $order->billing_last_name;
		} else {
			$name = $order->billing_email; // Field should required
		}

		$main_info['company'] = array(
			'name'		=> $name,
			'email'		=> $order->billing_email,
			'company_type'	=> 'consumer',
		);

		// If we are dealing with a registered user
		if ( 0 != $order->user_id ) {
			$customer = get_userdata( (int) $order->user_id );

			$customer_name = $customer->user_login;

			// Add URL, if needed
			if ( '' != $customer->user_url ) {
				$main_info['company']['website'] = $customer->user_url;
			}
		} else {
			$customer_name = $name;
		}

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data: ' . print_r( $main_info, true ) );

		$create_customer = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'companies', $main_info ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data response: ' . print_r( $create_customer, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_customer->error ) ) {
			throw new Exception( sprintf( __( 'Customer "%s" could not be exported.'
				. ' Order: %s.'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$customer_name,
				$order->get_order_number(),
				$create_customer->error,
				$create_customer->error_description ) );
		}

		// return the company ID
		return $create_customer->company->id;

	}

	/**
	 * Try to find the customer billing and shipping addresses in TG system.
	 * Export the addresses, if needed.
	 *
	 * @since 1.1
	 * @access private
	 * @param int $tg_customer_id
	 * @param object $order
	 * @throws Exception
	 */
	public function maybe_export_customer_addresses( $tg_customer_id, $order ) {

		// Add log
		WC_TradeGecko_Init::add_log( 'Updating/Exporting addresses for order ' . $order->get_order_number() );

		// Gather the customer addresses
		$address_data = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'addresses', null, null, array( 'company_id' => $tg_customer_id ) ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Customer address data response: ' . print_r( $address_data, true ) );

		// If error occurred end the process and log the error
		if ( isset( $address_data->error ) ) {
			throw new Exception( sprintf( __( 'Could not retieve the addresses for the customer of order "%s".'
				. ' Error Code: %s.'
				. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
				$order->get_order_number(),
				$address_data->error,
				$address_data->error_description ) );
		}

		$shipping_id = '';
		$billing_id = '';

		// Look through the addresses to find the correct address
		$addresses = isset( $address_data->addresses ) ? $address_data->addresses : $address_data->address;
		foreach ( $addresses as $address ) {

			// Search the shipping address
			if ( $address->address1 == $order->billing_address_1 ) {
				$billing_id = $address->id;
			}

			// Search the shipping address
			if ( $address->address1 == $order->shipping_address_1 ) {
				$shipping_id = $address->id;
			}

			if ( '' != $billing_id && '' != $shipping_id ) {
				break;
			}

		}

		/*==================
		 * Billing Address
		 ===================*/
		if ( '' != $billing_id ) {
			// Add billing address id to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_billing_id', $billing_id);
		} else {
			$customer_billing_info = $this->build_customer_billing_info( $order );

			// Build the billing address export
			$billing_address['address'] = $customer_billing_info['billing_address'];
			$billing_address['address']['company_id'] = $tg_customer_id;
			$billing_address['address']['label'] = 'Address-'. rand( 00001, 9999999 );

			// Add log
			WC_TradeGecko_Init::add_log( 'Export billing address data: ' . print_r( $billing_address, true ) );

			// Export the billing address
			$create_billing_address = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $billing_address ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Export billing address data response: ' . print_r( $create_billing_address, true ) );

			// If error occurred end the process and log the error
			if ( isset( $create_billing_address->error ) ) {
				throw new Exception( sprintf( __( 'Could not create Billing address for customer of order "%s".'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$order->get_order_number(),
					$create_billing_address->error,
					$create_billing_address->error_description ) );
			}

			// Add the billing address ID to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_billing_id', $create_billing_address->address->id );

		}

		/*==================
		 * Shipping Address
		 ===================*/
		if ( '' != $shipping_id ) {
			// Add s address id to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_shipping_id', $shipping_id);
		} elseif( empty( $order->shipping_address_1 ) || $order->billing_address_1 == $order->shipping_address_1 ) {
			// If we don't have a shipping address to export
			// or the billing address is the same as the shipping address,
			// just add the billing_id to the shipping and don't export a duplicate address
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_shipping_id', $create_billing_address->address->id );
		} else {
			$customer_shipping_info = $this->build_customer_shipping_info( $order );

			// Build the Shipping address for export
			$shipping_address['address'] = $customer_shipping_info['shipping_address'];
			$shipping_address['address']['company_id'] = $tg_customer_id;
			$shipping_address['address']['label'] = 'Address-'. rand( 00001, 9999999 );

			// Add log
			WC_TradeGecko_Init::add_log( 'Export shipping address data: ' . print_r( $shipping_address, true ) );

			// Export the shipping address
			$create_shipping_address = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $shipping_address ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Export shipping address data response: ' . print_r( $create_shipping_address, true ) );

			// If error occurred end the process and log the error
			if ( isset( $create_shipping_address->error ) ) {
				throw new Exception( sprintf( __( 'Could not create Shipping address for customer of order "%s".'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$order->get_order_number(),
					$create_shipping_address->error,
					$create_shipping_address->error_description ) );
			}

			// Add shiping address ID to the order
			WC_TradeGecko_Init::update_post_meta( $order->id, 'customer_shipping_id', $create_shipping_address->address->id);

		}

	}

	/**
	 * Build the billing address info to export
	 *
	 * @since 1.1
	 * @access private
	 * @param object $order
	 * @return array
	 */
	public function build_customer_billing_info( $order ) {

		if ( ! empty( $order->billing_address_1 ) ) {
			$user_data['billing_address'] = array(
				'first_name' => $order->billing_first_name,
				'last_name'	=> $order->billing_last_name,
				'company_name'	=> $order->billing_company,
				'address1'	=> $order->billing_address_1,
				'address2'	=> $order->billing_address_2,
				'city'		=> $order->billing_city,
				'country'	=> $order->billing_country,
				'zip_code'	=> $order->billing_postcode,
				'state'		=> $order->billing_state,
				'phone_number'	=> $order->billing_phone,
				'email'		=> $order->billing_email,
			);
		} else {
			$user_data['billing_address'] = array(
				'address1'	=> 'empty',
			);
		}

		return $user_data;
	}

	/**
	 * Build the shipping address info to export
	 *
	 * @since 1.1
	 * @access private
	 * @param type $order
	 * @return array
	 */
	public function build_customer_shipping_info( $order ) {

		$user_data['shipping_address'] = array(
			'first_name'	=> $order->shipping_first_name,
			'last_name'	=> $order->shipping_last_name,
			'company_name'	=> $order->shipping_company,
			'address1'	=> $order->shipping_address_1,
			'address2'	=> $order->shipping_address_2,
			'city'		=> $order->shipping_city,
			'country'	=> $order->shipping_country,
			'zip_code'	=> $order->shipping_postcode,
			'state'		=> $order->shipping_state,
		);

		return $user_data;

	}

	/**
	 * Export order to TradeGecko
	 *
	 * @since 1.0
	 * @access private
	 * @global object $wpdb
	 * @param type $order_id
	 * @throws Exception
	 */
	private function export_order( $order_id ) {

		// Get order object
		$order = WC_Compat_TG::wc_get_order( (int) $order_id );

		$order_number = str_replace( '#', '', $order->get_order_number() );

		// Add log
		WC_TradeGecko_Init::add_log( 'Exporting order #' . $order_number );

		// Update/Export customer info and attach the needed information to the order
		$this->get_customer_tg_information( $order );

		$billing_address_id = WC_TradeGecko_Init::get_post_meta( $order->id, 'customer_billing_id', true );
		$shipping_address_id = WC_TradeGecko_Init::get_post_meta( $order->id, 'customer_shipping_id', true );
		$company_id = WC_TradeGecko_Init::get_post_meta( $order->id, 'customer_id', true );

		// Bail with an exception, if one of the three parameters are missing, as we can't export the order
		if ( '' == $billing_address_id || '' == $shipping_address_id || '' == $company_id ) {
			throw new Exception(
				sprintf(
					__( 'Could not export order# "%s".'
					. ' One or all of the required parameters are missing.'
					, WC_TradeGecko_Init::$text_domain ),
					$order_number
				)
			);
		}

		// Build the new order query
		$order_info = array(
			'order' => array(
				'billing_address_id'	=> $billing_address_id,
				'company_id'		=> $company_id,
				'ship_at'		=> $order->order_date,
				'email'			=> $order->billing_email,
				'fulfillment_status'	=> 'unshipped',
				'issued_at'		=> $order->order_date,
				'notes'			=> $order->customer_note,
				'order_number'		=> WC_TradeGecko_Init::get_setting( 'order_number_prefix' ) . $order_number,
				'payment_status'	=> ( 'completed' == $order->status || 'processing' == $order->status ) ? 'paid' : 'unpaid',
				'phone_number'		=> $order->billing_phone,
				'shipping_address_id'	=> $shipping_address_id,
				'status'		=> 'active',
				'tax_type'		=> ( $this->prices_include_tax( $order ) ) ? 'inclusive' : 'exclusive',
				'source_url'		=> admin_url( 'post.php?post=' . absint( $order->id ) . '&action=edit' ),
				'stock_location_id'	=> $this->stock_location_id,
			)
		);

		if ( '' != $this->available_currency_id && '0' != $this->available_currency_id ) {
			$order_info['order']['currency_id'] = $this->available_currency_id;
		}

		// Add the line items to the query
		$items = $order->get_items();

		foreach ( $items as $item ) {

			$_product = $order->get_product_from_item( $item );

			if ( false === $_product ) {
				// If the product of the order line item returns false,
				// it means it is deleted from the database, so we can't export the order
				throw new Exception(
					sprintf( __( "Order export failed for order with order number #%s and ID: %s.
						Can't export orders with products, deleted from your store database.
						The Deleted product is: %s",
						WC_TradeGecko_Init::$text_domain ),
						$order_number,
						$order->id,
						$item['name']
					)
				 );
			}

			$prod_id = $this->get_product_id( $_product );
			$item_tax_rate = $this->get_order_item_tax_rate( $item );
			$cost_per_unit = $this->get_order_item_cost_per_unit( $order, $item );

			// Get the variant ID from the exported and synced items
			$variant_id = WC_TradeGecko_Init::get_post_meta( $prod_id, 'variant_id', true );

			if ( '' == $variant_id ) {

				// Search for the variant in TG by SKU.
				$variant_data = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', null, null, array( 'sku' => $_product->get_sku() ) ) );

				if ( isset( $variant_data->error ) ) {
					// Fail order export, we do not have variant id and could not retrieve one
					throw new Exception( sprintf( __( 'Order export failed for order with order number #%s and ID: %s.'
						. ' Cannot export orders with products, which do not exist in TradeGecko.'
						. ' Product with ID: %s and SKU: %s, does not exist in TradeGecko.', WC_TradeGecko_Init::$text_domain ),
						$order_number,
						$order->id,
						$prod_id,
						$_product->get_sku() ) );
				}

				$variants = isset( $variant_data->variants ) ? $variant_data->variants : $variant_data->variant;
				if ( 0 < count( $variants ) ) {

					foreach( $variants as $variant ) {
						// Make sure the SKU matches
						if ( $_product->get_sku() == $variant->sku ) {
							// Add the first variant we found
							$variant_id = $variant->id;

							// Add the variant ID to the product for future use.
							WC_TradeGecko_Init::update_post_meta( $prod_id, 'variant_id', $variant->id );
							break;
						}
					}

				} else {
					// Fail order export if product is not synced with TG
					throw new Exception( sprintf( __( 'Order export failed for order with order number #%s and ID: %s.'
						. ' Cannot export orders with products, which do not exist in TradeGecko.'
						. ' Product with ID: %s and SKU: %s, does not exist in TradeGecko.', WC_TradeGecko_Init::$text_domain ),
						$order_number,
						$order->id,
						$prod_id,
						$_product->get_sku() ) );

				}
			}

			$order_info['order']['order_line_items'][] = array(
				'quantity'	=> (int) $item['qty'],
				'discount'	=> '',
				'price'		=> $cost_per_unit,
				'tax_rate'	=> $item_tax_rate,
				'variant_id'	=> $variant_id,
			);

		}

		// Add the Shipping as freeform
		if ( 0 < WC_Compat_TG::get_total_shipping( $order ) ) {
			$order_info['order']['order_line_items'][] = $this->get_order_shipping_line_item( $order );
		}

		// Add another item for the discount as freeform
		if ( 0 < $order->get_total_discount() ) {
			$order_info['order']['order_line_items'] = array_merge($order_info['order']['order_line_items'], $this->get_order_discount_line_items( $order ));
		}

		// Allow for the parameters to be changed or added to
		$order_info = apply_filters( 'wc_tradegecko_new_order_query', $order_info, $order->id );

		// Add log
		WC_TradeGecko_Init::add_log( 'Order Info to export: ' . print_r( $order_info, true ) );

		$export_order = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'orders', $order_info ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Response for the exported order: ' . print_r( $export_order, true ) );

		// If error occurred end the process and log the error
		if ( isset( $export_order->error ) ) {

			$is_exported = false;

			// If error is higher than 400 and lower than 600, double check the order export
			if ( 400 <= $export_order->error && 599 >= $export_order->error ) {
				// Re-query the order to make sure it is not exported
				$is_exported = $this->requery_exported_order( $order );
			}

			if ( false == $is_exported ) {
				throw new Exception( sprintf( __( 'Could not export order# "%s".'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$order_number,
					$export_order->error,
					$export_order->error_description ) );
			}
		} else {
			WC_TradeGecko_Init::update_post_meta( $order->id, 'synced_order_id', $export_order->order->id );
		}

	}

	/**
	 * Re-query the TG order by order_number, to see if the order was exported successfully.
	 *
	 * @since 1.5
	 * @param WC_Order $order
	 * @return boolean TRUE, if the order was exported. FALSE, if the order was not.
	 */
	public function requery_exported_order( WC_Order $order ) {

		$order_number = str_replace( '#', '', $order->get_order_number() );

		// Query the TG order by order number
		$exported_order = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'orders', null, null, array( 'order_number' => WC_TradeGecko_Init::get_setting( 'order_number_prefix' ) . $order_number ) ) );

		if ( isset( $exported_order->error ) ) {
			return false;
		}

		$order_node = isset( $exported_order->order ) ? $exported_order->order : $exported_order->orders ;
		if ( ! empty( $order_node ) ) {
			foreach ( $order_node as $tg_order ) {
				// Make sure the order_number is not partially matched
				if ( $tg_order->order_number == WC_TradeGecko_Init::get_setting( 'order_number_prefix' ) . $order_number ) {
					// Add the first order id to the WC order meta.
					WC_TradeGecko_Init::update_post_meta( $order->id, 'synced_order_id', $tg_order->id );
					break;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * Calculate a single tax rate percentage value using the total price with tax and the total price without tax.
	 *
	 * We need to send a single tax percentage to TG, but in WC there can be multiple tax rates applied to each order item.<br/>
	 * We can also have compound and not compound tax applied.<br/>
	 * This simple calculation will give us the tax rate no matter how many or what kind of taxes were applied to the order item.
	 *
	 * @since 1.6.2
	 * @param float $total_with_tax Total price with tax
	 * @param float $total_without_tax Total price without tax
	 * @return float
	 */
	private function get_tax_rate( $total_with_tax, $total_without_tax ) {
		$item_rate = ( ( $total_with_tax - $total_without_tax ) / $total_without_tax ) * 100;

		return $item_rate;
	}

	/**
	 * Sync product inventory
	 *
	 * @param WC_Product $product
	 */
	public function sync_product_inventory( \WC_Product $product )
	{
		$prod_id = $this->get_product_id( $product );

		$tradegecko_variant_id = WC_TradeGecko_Init::get_post_meta( $prod_id, 'variant_id', true );

		// Only continue if we have a variant ID, or else we will pull all TG variants,
		// we won't be able to update the product stock anyway
		if ( '' == $tradegecko_variant_id ) {
			return;
		}

		// Pull variant data from TradeGecko
		$result = WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', null,  $tradegecko_variant_id);
		$result = WC_TradeGecko_Init::get_decoded_response_body( $result );

		// Check for errors
		if ( isset( $result->error ) ) {
			WC_TradeGecko_Init::add_log( sprintf( __( 'Inventory variants could not be pulled from the TradeGecko system. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $result->error, $result->error_description ) );
		} else {
			// Check which key we have
			$variant = isset ( $result->variant ) ? $result->variant : $result->variants;

			// We want to set the product stock, if there is no error.
			$this->set_product_stock( $product, $variant );
		}
	}

	/**
	 * Update the stock level of items in cart
	 */
	public function update_cart_item_stock()
	{
		try {
			foreach ( WC_Compat_TG::get_wc_global()->cart->get_cart() as $cart_item_key => $values ) {
				$product = $values['data'];
				$last_synced_at = $this->get_last_synced_at( $product );

				// Only sync once every 5 minutes
				if( strtotime( current_time( 'mysql' ) ) - (60*5) > strtotime($last_synced_at) ) {
					$this->sync_product_inventory( $product );
					$this->update_last_synced_at( $product );
				}
			}
		 } catch( Exception $e ) {
			 // Do nothing we could not connect to TG or something else went wrong.
			 // Don't disrupt the customer purchase.
			 WC_TradeGecko_Init::add_log( $e->getMessage() );
		}
	}

	/**
	 * Sync orders that are already exported to TG.
	 *
	 * @since 1.0
	 * @access private
	 * @param array $order_ids
	 */
	private function update_orders() {

		// Add log
		WC_TradeGecko_Init::add_log( 'Update Orders IDs: ' . print_r( $this->tg_order_ids, true ) );

		if ( ! empty ( $this->tg_order_ids ) ) {

			if ( ! class_exists( 'WC_TradeGecko_Update_Orders' ) ) {
				include_once( 'class-wc-tradegecko-update-orders.php' );
			}

			$tg_update_orders = new WC_TradeGecko_Update_Orders();

			$tg_update_orders->process_update_orders( $this->tg_order_ids, $this->tg_id_to_order_id_mapping );

		}
	}

	/**
	 * Filter and order ids and put them as exported or unexported.
	 *
	 * @since 1.0
	 * @access private
	 * @param array $order_ids
	 */
	private function filter_order_ids_to_update( $order_ids = array() ) {

		// We want to sync specific orders
		if ( ! empty( $order_ids ) ) {

			// Add log
			WC_TradeGecko_Init::add_log( 'Filter order IDs given specific IDs: ' . print_r( $order_ids, true ) );

			// Make sure the parameter is an array
			if ( ! is_array( $order_ids ) ) {
				$order_ids = array( (int) $order_ids );
			}

			// Get the TG order ids for each order. Skip the order that a TG order id is not found
			foreach( $order_ids as $id ) {
				if ( $tg_id = WC_TradeGecko_Init::get_post_meta_direct( $id, 'synced_order_id', true ) ) {
					$this->tg_order_ids[] = $tg_id;
					$this->tg_id_to_order_id_mapping[ $tg_id ] = $id;
				} else {
					$this->not_exported_order_ids[] = $id;
				}
			}
		} else {
			$tradegecko_query_orders_status = $this->get_order_sync_statuses();

			$results = $this->get_orders_to_be_updated( $tradegecko_query_orders_status );

			// Filter each order and get the TG order id
			// If order is not exported save it and export it later
			foreach($results as $row) {
				if($row->tradegecko_order_id) {
					$this->tg_order_ids[] = $row->tradegecko_order_id;
					$this->tg_id_to_order_id_mapping[ $row->tradegecko_order_id ] = $row->order_id;
				} else {
					$this->not_exported_order_ids[] = $row->order_id;
				}
			}
		}
	}

	/**
	 * Get the product ID from the SKU
	 *
	 * @since 1.0
	 * @access private
	 * @global object $wpdb DB object
	 * @param string $sku The product SKU
	 * @return boolean|int The product ID or False
	 */
	private function get_product_by_sku( $sku ) {
		global $wpdb;
		$product =
			"SELECT post_id FROM $wpdb->posts post
			LEFT OUTER JOIN $wpdb->postmeta meta ON post.ID = meta.post_id
			WHERE  meta.meta_key = '_sku'
			AND meta.meta_value = '%s'
			AND post.post_status IN ( 'publish', 'pending', 'draft' )";
		$product_id = $wpdb->get_results( $wpdb->prepare( $product, $sku ) );

		if ( $product_id ) {
			return $product_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the tax rate for the Order line item
	 *
	 * @since 1.7
	 * @param array $item
	 * @return float
	 */
	public function get_order_item_tax_rate( $item ) {
		$item_tax_rate = 0;

		// If we have tax for the item
		// Calculate a single percentage rate
		if ( $item['line_subtotal_tax'] > 0 ) {
			$item_tax_rate = ($item['line_subtotal_tax'] / $item['line_subtotal']) * 100;
		}

		return $item_tax_rate;
	}

	/**
	 * Calculate the cost per unit for a line item
	 *
	 * @since 1.7
	 * @param \WC_Order $order
	 * @param array $item
	 * @return float
	 */
	public function get_order_item_cost_per_unit( \WC_Order $order, $item ) {
		// Cost of the item before discount
		if ( $this->prices_include_tax( $order ) ) {
			$cost_per_unit = number_format( ( $item['line_subtotal'] + $item['line_subtotal_tax'] ) / $item['qty'], 2, '.', '');
		} else {
			$cost_per_unit = number_format( $item['line_subtotal'] / $item['qty'], 2, '.', '');
		}

		return $cost_per_unit;
	}

	/**
	 * Get the total discount of a given set of line items
	 *
	 * @since 1.7.8
	 * @param \WC_Order $order
	 * @param $items
	 * @return float
	 */
	public function get_items_discount_total( \WC_Order $order, $items ) {
		$items_discount_total = 0;

		foreach ( $items as $item ) {
			$items_discount_total = $items_discount_total + $this->get_item_discount( $order, $item );
		}

		return $items_discount_total;
  }

  /**
	 * Get the total discount of a given line item
	 *
	 * @since 1.7.9
	 * @param \WC_Order $order
	 * @param $items
	 * @return float
	 */
	public function get_item_discount( \WC_Order $order, $item ) {
		if ( ($item['line_subtotal_tax'] != 0) && $this->prices_include_tax( $order ) ) {
			$item_tax_rate = $this->get_order_item_tax_rate( $item );
			$discount_without_tax = $item['line_subtotal'] - $item['line_total'];
			$discount_tax = ($item_tax_rate / 100) * $discount_without_tax;
			return number_format( ($discount_without_tax + $discount_tax), 2, '.', '');
		}
		else {
			return ($item['line_subtotal'] - $item['line_total']);
		}
	}

	/**
	 * Get the discount line item block to add to the TG order
	 *
	 * @since 1.7
	 * @param \WC_Order $order
	 * @return array
	 */
	public function get_order_discount_line_items( \WC_Order $order ) {

		$items = $order->get_items();

		$taxable_items = array_filter($items, function( $item ) {
			return $item['line_subtotal_tax'] != 0;
		});

		$non_taxable_items = array_filter($items, function( $item ) {
			return $item['line_subtotal_tax'] == 0;
		});

		$discount_line_items = array();

		if (count($taxable_items) > 0) {
			$item_tax_rate = $this->get_order_item_tax_rate( array_values($taxable_items)[0] );

			array_push( $discount_line_items, array(
				'quantity'	=> 1,
				'price'	=> '-'.number_format( $this->get_items_discount_total( $order, $taxable_items ), 2, '.', ''),
				'freeform'	=> 'true',
				'tax_rate'	=> $item_tax_rate,
				'line_type'	=> 'Discount',
				'label'	 => 'Discount (Taxable)'
			));
		}

		if (count($non_taxable_items) > 0) {
			array_push( $discount_line_items, array(
				'quantity'	=> 1,
				'price'	=> '-'.number_format( $this->get_items_discount_total( $order, $non_taxable_items ), 2, '.', ''),
				'freeform'	=> 'true',
				'tax_rate'	=> '',
				'line_type'	=> 'Discount',
				'label'	=> 'Discount (Non-taxable)'
			));
		}

		return $discount_line_items;
	}

	/**
	 * Get the shipping line item block to add to the TG order
	 *
	 * @since 1.7
	 * @param \WC_Order $order
	 * @return array
	 */
	public function get_order_shipping_line_item( \WC_Order $order ) {
		$ship_price = $this->get_order_shipping_price( $order );
		$tax_rate_ship = $this->get_order_shipping_tax_rate( $order, $ship_price );
		$shipping_method = $order->get_shipping_method();

		return array(
			'quantity'	=> 1,
			'price'		=> number_format( $ship_price, 2, '.', ''),
			'freeform'	=> 'true',
			'tax_rate'	=> $tax_rate_ship,
			'line_type'	=> 'Shipping',
			'label'		=> 'Shipping - ' . $shipping_method,
		);
	}

	/**
	 * Calculates order shipping total, with or without tax.
	 *
	 * @since 1.7
	 * @param \WC_Order $order
	 * @return float
	 */
	public function get_order_shipping_price( \WC_Order $order ) {
		// Take out this to the sync class
		$ship_price = WC_Compat_TG::get_total_shipping( $order );

		if ( $this->prices_include_tax( $order ) ) {
			$ship_price += $order->get_shipping_tax();
		}

		return $ship_price;
	}

	/**
	 * Calculate the tax rate(%) for the shipping line item
	 *
	 * @since 1.7
	 * @param \WC_Order $order
	 * @param type $ship_price
	 * @return type
	 */
	public function get_order_shipping_tax_rate( \WC_Order $order, $ship_price ) {
		$is_taxable = 0 < $order->get_shipping_tax() ? true : false;
		$tax_rate_ship = 0;

		if ( $is_taxable ) {
			$shipping_total_with_tax = $this->prices_include_tax( $order ) ? $ship_price : $ship_price + $order->get_shipping_tax();
			$shipping_total_without_tax = $this->prices_include_tax( $order ) ? $ship_price - $order->get_shipping_tax() : $ship_price;

			$tax_rate_ship = $this->get_tax_rate( $shipping_total_with_tax, $shipping_total_without_tax );
		}

		return $tax_rate_ship;
	}

	/**
	 * Map User meta array and return the first of each field.
	 *
	 * @since 1.0
	 * @access public
	 * @param array $a
	 * @return mixed
	 */
	public function map_user_array( $a ) {
		if ( is_array($a) ) {
			return isset( $a[0] ) ? $a[0] : current( $a );
		}

		return $a;
	}

	/**
	 * Check if order sync process is currently in progress.
	 *
	 * @since 1.4.1
	 * @return boolean TRUE, if sync is running. FALSE, if sync is not running.
	 */
	public function check_is_order_sync_running() {
		$is_running = get_transient( 'wc_tradegecko_is_order_sync_running' );

		if ( false !== $is_running ) {
			return true;
		}

		return false;
	}

	/**
	 * Update the wc_tradegecko_is_order_sync_running option. Signifying if an order sync is currently running or not.
	 *
	 * @since 1.4.1
	 * @param string $action The update action.
	 *		<br><b>begin</b>: if the is_running process will begin.
	 *		<br><b>end</b>: if the is_running process will end.
	 */
	public function update_is_order_sync_running( $action ) {

		if ( 'begin' == $action ) {
			if ( ! $this->export_orders_mutex->lock() ) {
				throw new Exception( __( 'Update Orders Process lock could not be initiated. '
					. 'Please try again.', WC_TradeGecko_Init::$text_domain ) );
			}

			// Check if the same process is already running
			if ( ! $this->check_is_order_sync_running() ) {
				$time_in_seconds = $this->get_elapse_time_string( 'orders' );

				set_transient( 'wc_tradegecko_is_order_sync_running', true, $time_in_seconds );

				$this->export_orders_mutex->unlock();
				return true;
			}

			$this->export_orders_mutex->unlock();
			return false;
		} elseif ( 'end' == $action ) {
			delete_transient( 'wc_tradegecko_is_order_sync_running' );
		}

		return false;
	}

	/**
	 * Check if inventory sync process is currently in progress.
	 * It check if the inventory sync is running and if it is, it will make sure it will not run longer than 3 hours
	 *
	 * @since 1.4.1
	 * @return boolean
	 */
	public function check_is_inventory_sync_running() {

		$is_running = get_transient( 'wc_tradegecko_is_inventory_sync_running' );

		if ( false !== $is_running ) {
			return true;
		}

		return false;
	}

	/**
	 * Update the wc_tradegecko_is_inventory_sync_running option. Signifying, if an inventory sync is currently running or not.
	 *
	 * @param string $action The update action.
	 *		<br><b>begin</b>: if the is_running process will begin.
	 *		<br><b>end</b>: if the is_running process will end.
	 */
	public function update_is_inventory_sync_running( $action ) {

		if ( 'begin' == $action ) {
			if ( ! $this->inventory_mutex->lock() ) {
				throw new Exception( __( 'Could not execute Inventory Sync request. '
					. 'Process lock was unsuccessful.', WC_TradeGecko_Init::$text_domain ) );
			}
			// Check if the same process is already running
			if ( ! $this->check_is_inventory_sync_running() ) {
				$time_in_seconds = $this->get_elapse_time_string( 'products' );

				set_transient( 'wc_tradegecko_is_inventory_sync_running', true, $time_in_seconds );

				$this->inventory_mutex->unlock();
				return true;
			}

			$this->inventory_mutex->unlock();
			return false;
		} elseif ( 'end' == $action ) {
			delete_transient( 'wc_tradegecko_is_inventory_sync_running' );
		}

		return false;
	}

	/**
	 * Check if order update sync process is currently in progress.
	 *
	 * @since 1.5
	 * @return boolean TRUE, if sync is running. FALSE, if sync is not running.
	 */
	public function check_is_order_update_sync_running() {

		$is_running = get_transient( 'wc_tradegecko_is_order_update_sync_running' );

		if ( false !== $is_running ) {
			return true;
		}

		return false;
	}

	/**
	 * Update the wc_tradegecko_is_order_update_sync_running option. Signifying if an order sync is currently running or not.
	 *
	 * @since 1.5
	 * @param string $action The update action.
	 *		<br><b>begin</b>: if the is_running process will begin.
	 *		<br><b>end</b>: if the is_running process will end.
	 */
	public function update_is_order_update_sync_running( $action ) {

		if ( 'begin' == $action ) {
			if ( ! $this->update_orders_mutex->lock() ) {
				throw new Exception( __( 'Update Orders Process lock could not be initiated. '
					. 'Please try again.', WC_TradeGecko_Init::$text_domain ) );
			}

			// Check if the same process is already running
			if ( ! $this->check_is_order_update_sync_running() ) {
				$time_in_seconds = $this->get_elapse_time_string( 'orders' );

				set_transient( 'wc_tradegecko_is_order_update_sync_running', true, $time_in_seconds );

				$this->update_orders_mutex->unlock();
				return true;
			}

			$this->update_orders_mutex->unlock();
			return false;
		} elseif ( 'end' == $action ) {
			delete_transient( 'wc_tradegecko_is_order_update_sync_running' );
		}

		return false;
	}

	/**
	 * Get the time we want an order or inventory sync to last.
	 * The time is based on products or orders count.
	 *
	 * @since 1.5
	 * @param type $based_on What do we base the time on 'products' or 'orders'
	 * @return string The time string
	 */
	public function get_elapse_time_string( $based_on ) {
		$seconds = 10800; // 3 hours

		if ( 'orders' == $based_on ) {
			$order_count = WC_TradeGecko_Init::get_processing_orders_count();

			if ( 1 <= $order_count && 200 >= $order_count ) {
				$seconds = 2700; // 45 minutes
			} elseif ( 201 <= $order_count && 400 >= $order_count ) {
				$seconds = 4800; // 80 minutes
			} elseif ( 401 <= $order_count && 800 >= $order_count ) {
				$seconds = 7200; // 2 hours
			} else {
				// The most time we will elapse is 3 hours
				$seconds = '10800'; // 3 hours
			}
		} elseif ( 'products' == $based_on ) {
			$product_count = WC_TradeGecko_Init::get_products_count();

			if ( 1 <= $product_count && 200 >= $product_count ) {
				$seconds = 1800; // 30 minutes
			} elseif ( 201 <= $product_count && 400 >= $product_count ) {
				$seconds = 3600; // 1 hour
			} elseif ( 401 <= $product_count && 800 >= $product_count ) {
				$seconds = 7200; // 2 hours
			} else {
				// The most time we will elapse is 3 hours
				$seconds = 10800; // 3 hours
			}
		} elseif ( 'minute' == $based_on ) {
			$seconds = 60;
		} elseif ( 'hour' == $based_on ) {
			$seconds = 3600;
		}

		return $seconds;
	}

	/**
	 * Get all orders in processing status waiting to be updated
	 *
	 * @since 1.6.3
	 * @global object $wpdb
	 * @param array $order_statuses
	 * @return object
	 */
	private function get_orders_to_be_updated( array $order_statuses ) {
		global $wpdb;

		$count = count( $order_statuses );
		$placeholders = array_fill( 0, $count, '%s' );
		$status_places = implode( ', ', $placeholders );

		if ( WC_Compat_TG::is_wc_2_2() ) {
			$query = $wpdb->prepare("
			SELECT post.ID AS order_id, meta.meta_value AS tradegecko_order_id
				FROM $wpdb->posts post
				LEFT  JOIN $wpdb->postmeta meta ON post.ID = meta.post_id AND meta.meta_key = '_wc_tradegecko_synced_order_id'
				WHERE post.post_type = 'shop_order'
				AND post.post_status IN (" . $status_places . ");
			", $order_statuses);

		} else {
			$query = $wpdb->prepare("
			SELECT post.`ID` AS 'order_id', meta.`meta_value` AS 'tradegecko_order_id', t.`slug` AS 'status'
				FROM $wpdb->posts post
				LEFT  JOIN $wpdb->postmeta meta ON post.ID = meta.post_id AND meta.meta_key = '_wc_tradegecko_synced_order_id'
				INNER JOIN $wpdb->term_relationships tr ON tr.object_id = post.ID
				INNER JOIN $wpdb->term_taxonomy tx ON tr.term_taxonomy_id = tx.term_taxonomy_id AND tx.`taxonomy` = 'shop_order_status'
				INNER JOIN $wpdb->terms t ON tx.term_id = t.term_id
				WHERE `post_type` = 'shop_order' AND
				`post_status` = 'publish' AND
				t.`slug` IN (" . $status_places . ");
			", $order_statuses);
		}

		$results = $wpdb->get_results( $query );

		return $results;
	}

	/**
	 * Return the WC order statuses that will be monitored and constantly synced
	 *
	 * @since 1.7
	 * @return array An array of the order statuses
	 */
	public function get_order_sync_statuses() {

		if ( WC_Compat_TG::is_wc_2_2() ) {
			$statuses = array( 'wc-processing' );
		} else {
			$statuses = array( 'processing' );
		}

		return apply_filters( 'wc_tradegecko_query_orders_status', $statuses );
	}

	/**
	 * Unsync the product from the TG variant.
	 * Clears the TG Variant ID from the store database
	 *
	 * @since 1.7.5
	 * @param WC_Product $product
	 */
	private function unsync_product( \WC_Product $product ) {
		$product_id = $this->get_product_id( $product );
		$variant_id = WC_TradeGecko_Init::get_post_meta( $product_id, 'variant_id', true );

		// Unsync the product in the store, if it should not be online
		if ( '' != $variant_id ) {
			WC_TradeGecko_Init::update_post_meta( $product_id, 'variant_id', '' );
		}
	}

	/**
	 * Returns true, if prices do include tax
	 *
	 * @since 1.7.5
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function prices_include_tax( \WC_Order $order ) {
		return ( 'yes' == $order->prices_include_tax );
	}

}
$GLOBALS['wc_tg_sync'] = new WC_TradeGecko_Sync();
