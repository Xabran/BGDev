<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class handles the Update orders process of order synchronization
 *
 * @since 1.5
 */
class WC_TradeGecko_Update_Orders {

	public function __construct() {

		$this->tg_order_ids		= array();
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
		$this->order_number_prefix	= WC_TradeGecko_Init::get_setting( 'order_number_prefix' );
		$this->order_line_items_sync	= WC_TradeGecko_Init::get_setting( 'order_line_items_sync' );
		$this->order_line_items_update_direction = WC_TradeGecko_Init::get_setting( 'order_line_items_update_direction' );


		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'process_tg_order_update_request' ) );

	}

	/**
	 * Process update order request from TG.
	 *
	 * NOTE: Method is not in use, but it is added to prepare
	 * implementation of TG -> WC request for orders updating.
	 */
	function process_tg_order_update_request() {

	}

	/**
	 * Process and initiate the orders update.
	 *
	 * @since 1.5
	 * @param array $order_ids The TG Order IDs of the orders that need updating.
	 * @param array $tg_id_to_order_id_mapping TG to WC order IDs mapping
	 */
	function process_update_orders( array $order_ids, array $tg_id_to_order_id_mapping ) {
		// Split the order ids to smaller batches
		$update_batches = $this->split_tg_order_ids_into_batches( $order_ids );

		foreach ( $update_batches as $tg_order_ids ) {
			// Update each batch of orders
			$this->update_orders_batch( $tg_order_ids, $tg_id_to_order_id_mapping );
		}
	}

	/**
	 * Update the orders in the order batches
	 *
	 * @since 1.5
	 * @param array $order_batch The IDs to update in the batch
	 * @param array $tg_id_to_order_id_mapping TG to WC order IDs mapping
	 * @throws Exception
	 */
	function update_orders_batch( array $order_batch, $tg_id_to_order_id_mapping ) {

		// Get the orders info from TG
		$tg_orders = $this->get_tg_orders_update_info( $order_batch );
		$unsuccessful_updates = array();

		foreach ( $tg_orders as $tg_open_order ) {
			try {
				$this->update_order( $tg_open_order, $tg_id_to_order_id_mapping );
			} catch (Exception $ex) {
				// Because in the update calls to TG anything can happen
				// and there could be errors returned. We will give the process some padding of errors
				// before we end the Update process itself.
				$unsuccessful_updates[] = $ex->getMessage();

				// Log the messages to debug
				WC_TradeGecko_Init::add_log( 'Hit exception: ' . $ex->getMessage() .' Code: '. $ex->getCode() );

				// We will allow up to ten errors per batch of 300 orders
				// After which we will end the Update Orders process.
				if ( 10 <= count( $unsuccessful_updates ) || '100' < $ex->getCode() ) {
					// Implode all messages, so we can show them to customer
					$error_message = implode( '<br/>', $unsuccessful_updates );

					// Log the messages to debug
					WC_TradeGecko_Init::add_log( 'Update Orders process ended. Messages: ' . $error_message );

					throw new Exception( sprintf( __( 'Update Orders process ended. Error Messages: %s.',
						WC_TradeGecko_Init::$text_domain ), $error_message ) );
				}
			}
		}
	}

	/**
	 * Update Order for fulfillments or order changes
	 *
	 * @since 1.7
	 * @global object $wc_tg_sync
	 * @param object $tg_open_order
	 * @param array $tg_id_to_order_id_mapping
	 * @return type
	 */
	private function update_order( $tg_open_order, $tg_id_to_order_id_mapping ) {
		// Get the WC order ID from the mapping
		$order_id = $tg_id_to_order_id_mapping[ $tg_open_order->id ];

		// If we don't have matching order ID, go to the next order in line
		if ( empty( $order_id ) ) {
			return;
		}

		$order = WC_Compat_TG::wc_get_order( (int) $order_id );

		// We don't update Completed orders
		if ( 'completed' == $order->status ) {
			return;
		}

		// Add order fulfillments
		$this->maybe_update_order_fulfillments( $order, $tg_open_order );

		// Update TG order payment status.
		$this->maybe_update_tg_unpaid_order_status( $order, $tg_open_order );

		// Change the order status to match the appropriate TG process
		$order_status_changed = $this->update_order_status( $order, $tg_open_order );

		// Re-sync order line items stock level
		if ( $order_status_changed && $this->inventory_sync ) {
			$updated_product = array();
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product = $order->get_product_from_item( $item );

				// Prevent syncing the same product over and over again
				if( isset($updated_product[ (string) $product->get_sku() ]) && $updated_product[ (string) $product->get_sku() ] === true ) {
					continue;
				} else {
					global $wc_tg_sync;
					$wc_tg_sync->sync_product_inventory( $product );
					$wc_tg_sync->update_last_synced_at( $product );

					// Log updated product
					$updated_product[ (string) $product->get_sku() ] = true;
				}
			}
		}

		// Check and sync any changes made to the order, so WC and TG orders are the same at all times
		$this->maybe_sync_order_changes( $order, $tg_open_order );
	}

	/**
	 * Check and run update for TG order fulfillments
	 *
	 * @since 1.7
	 * @param WC_Order $order
	 * @param object $tg_open_order TradeGecko order object
	 * @return bool
	 */
	private function maybe_update_order_fulfillments( \WC_Order $order, $tg_open_order ) {
		// Bail, if we don't sync fulfillments
		if ( 'no' == $this->sync_fulfillments ) {
			return;
		}

		// Bail, if we don't have fulfillments for the order
		if ( empty( $tg_open_order->fulfillment_ids ) ) {
			return;
		}

		// We don't update Completed orders
		if ( 'completed' == $order->status ) {
			return;
		}

		// Update the order fulfillments
		$this->update_order_fulfillments( $order, $tg_open_order );
	}

	/**
	 * Update the TG order status
	 *
	 * @since 1.5
	 * @param WC_Order $order
	 * @param type $tg_open_order
	 */
	function maybe_update_tg_unpaid_order_status( WC_Order $order, $tg_open_order ) {
		// This should be skipped all the time, but do it just in case
		if ( 'unpaid' == $tg_open_order->payment_status &&
			( 'completed' == $order->status || 'processing' == $order->status ) ) {
			// Update TG order to paid
			$update_info = array(
				'order' => array(
					'payment_status' => 'paid'
				)
			);

			// Add log
			WC_TradeGecko_Init::add_log( 'Updating order payment status. Request: ' . print_r( $update_info, true ) );

			$tg_update_order = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'orders', $update_info, $tg_open_order->id ) );
		}
	}

	/**
	 * Update the WC orders with the appropriate TG fulfillment information
	 *
	 * @since 1.5
	 * @param WC_Order $order
	 * @param object $tg_open_order TG order data
	 * @throws Exception
	 */
	function update_order_fulfillments( WC_Order $order, $tg_open_order ) {
		// Sync fulfillments, if full order is shipped.
		if ( ( 'full' == $this->sync_fulfillments && 'shipped' == $tg_open_order->fulfillment_status ) ||
			'partial' == $this->sync_fulfillments ) {
			// Get the fulfillment info and update with shipping time and tracking info
			$ff_data = array();
			foreach ( $tg_open_order->fulfillment_ids as $fulfillment_id ) {
				$tg_fulfillment = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'fulfillments', null, $fulfillment_id ) );

				if ( isset( $tg_fulfillment->error ) ) {
					throw new Exception( sprintf( __( 'Could not retrieve fulfillments. Error Message: %s.',
						WC_TradeGecko_Init::$text_domain ), $tg_fulfillment->error_description ), 100 );
				}

				// Add log
				WC_TradeGecko_Init::add_log( 'Doing Fulfillment ID: ' . $fulfillment_id .'. Data: '.print_r( $tg_fulfillment, true ) );

				// Make sure we have the correct node
				$fulfillment = isset( $tg_fulfillment->fulfillment ) ? $tg_fulfillment->fulfillment : $tg_fulfillment->fulfillments;

				// Ignore the fulfillment, if it is not completed
				if ( 'packed' == $fulfillment->status ) {
					continue;
				}

				// Get the fulfillment line items node
				$fulfillment_line_items = isset( $tg_fulfillment->fulfillment_line_items ) ? $tg_fulfillment->fulfillment_line_items : $tg_fulfillment->fulfillment_line_item;

				// Run through the line items and get their ids
				$order_line_items = array();
				foreach( $fulfillment_line_items as $fulfillment_line_item ) {
					$order_line_items[] = $fulfillment_line_item->order_line_item_id;
				}

				$ff_data[ $fulfillment->id ] = array(
					'shipped_at'		=> $fulfillment->shipped_at,
					'received_at'		=> $fulfillment->received_at,
					'delivery_type'		=> $fulfillment->delivery_type,
					'tracking_number'	=> $fulfillment->tracking_number,
					'tracking_message'	=> ( ! empty( $fulfillment->notes ) ) ? $fulfillment->notes : '',
					'tracking_url'		=> $fulfillment->tracking_url,
					'line_item_ids'		=> $order_line_items,
				);

				WC_TradeGecko_Init::update_post_meta( $order->id, 'order_fulfillment', $ff_data );
			}
		}
	}

	/**
	 * Update WC Order status according to TG Order status
	 *
	 * @since 1.5
	 * @param WC_Order $order WC Order
	 * @param type $tg_order TG Order
	 * @return boolean TRUE, if status is updated. FALSE, if status is not updated.
	 */
	function update_order_status( \WC_Order $order, $tg_order ) {
		// After order info is added and updated
		// Complete the order, if the fulfillment status is shipped
		// This will trigger all emails and notification to the customer

		$order_status_changed = false;

		if ( 'completed' != $order->status && 'shipped' == $tg_order->fulfillment_status ) {
			$order->update_status( 'completed', __( 'Order shipped in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		// Cancel voided order
		if ( 'cancelled' != $order->status && 'void' == $tg_order->status ) {
			$order->update_status( 'cancelled', __( 'Order voided in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		// Cancel deleted order
		if ( 'cancelled' != $order->status && 'deleted' == $tg_order->status ) {
			$order->update_status( 'cancelled', __( 'Order deleted in TradeGecko.', WC_TradeGecko_Init::$text_domain ) );
			$order_status_changed = true;
		}

		return $order_status_changed;
	}



	/**
	 * Make an API call an get the order info for the orders we want to update
	 *
	 * @since 1.5
	 * @param array $tg_order_ids
	 * @return array
	 * @throws Exception
	 */
	private function get_tg_orders_update_info( $tg_order_ids ) {

		// Now that we filtered all open orders, sync the information with TG
		$tg_open_orders = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'orders', null, null, array( 'ids' => $tg_order_ids ) ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Update orders data response: ' . print_r( $tg_open_orders, true ) );

		// If error occurred end the process and log the error
		if ( isset( $tg_open_orders->error ) ) {
			throw new Exception( sprintf( __( 'Could not retrieve the open orders from TradeGecko. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $tg_open_orders->error, $tg_open_orders->error_description ) );
		}

		$tg_orders =  isset( $tg_open_orders->order ) ? $tg_open_orders->order : $tg_open_orders->orders;

		return $tg_orders;

	}

	/**
	 * Check and sync any changes made to the order, so WC and TG orders are the same at all times
	 *
	 * @since 1.7
	 * @param WC_Order $order
	 * @param object $tg_open_order
	 */
	private function maybe_sync_order_changes( \WC_Order $order, $tg_open_order ) {
		// No update, if not enabled
		if ( ! $this->order_line_items_sync ) {
			return;
		}

		// No updates for WC Completed orders.
		if ( 'completed' == $order->status ) {
			return;
		}

		// No update for TG shipped orders
		if ( 'shipped' == $tg_open_order->fulfillment_status ) {
			return;
		}

		// Perform the line items sync in the chosen direction
		if ( 'wc_to_tg' == $this->order_line_items_update_direction ) {
			$this->sync_wc_to_tg_order_changes( $order, $tg_open_order );
		} elseif ( 'tg_to_wc' == $this->order_line_items_update_direction ) {
			$this->sync_tg_to_wc_order_changes( $order, $tg_open_order );
		}
	}

	/**
	 * Sync line items in WC to TG direction
	 *
	 * @since 1.7
	 * @param \WC_Order $order WC order
	 * @param type $tg_open_order TG order
	 */
	private function sync_wc_to_tg_order_changes( \WC_Order $order, $tg_open_order ) {

		// Get the TG order line items
		$tg_order_items = $this->get_tg_order_line_items( $tg_open_order );

		// Check for the Compare class and include it, if not included
		if ( ! class_exists( 'WC_TradeGecko_Order_Compare' ) ) {
			include_once 'class-wc-tradegecko-order-compare.php';
		}

		// Init the Compare class
		$compare = new WC_TradeGecko_Order_Compare( $order );

		// Check order items, TG non freeform items
		$are_items_different = $compare->compare_order_items( $tg_order_items );

		// Check Shipping
		$shipping_action = $compare->compare_order_shipping( $tg_order_items );

		// Check Discount
		$discount_action = $compare->compare_order_discount( $tg_order_items );

		// Update the TG order items
		if ( $are_items_different ) {
			$this->update_tg_order_items( $order, $tg_open_order, $tg_order_items );
		}

		// Update the TG order freeform items
		if ( $shipping_action ) {
			$this->update_tg_order_shipping( $order, $tg_open_order, $tg_order_items, $shipping_action );
		}

		// Update the TG order freeform items
		if ( $discount_action ) {
			$this->update_tg_order_discount( $order, $tg_open_order, $tg_order_items, $discount_action );
		}

		// Check and update order addresses
		$this->update_tg_order_addresses( $order, $tg_open_order );
	}

	/**
	 * Update TG order line items. Only the real product items, without the freeform items.
	 * Because we don't have a way to track each items and its changes,<br/>
	 * we have to create the new line items and then delete the old line items
	 *
	 * @since 1.7
	 * @global object $wc_tg_sync The TG Sync class object
	 * @param \WC_Order $order WC order
	 * @param object $tg_open_order TG order
	 * @param object $tg_order_items TG order items
	 * @return void
	 * @throws Exception
	 */
	private function update_tg_order_items( \WC_Order $order, $tg_open_order, $tg_order_items ) {
		global $wc_tg_sync;

		// Add the line items to the query
		$items = $order->get_items();
		$order_items = array();

		foreach ( $items as $item ) {

			$_product = $order->get_product_from_item( $item );
			$prod_id = $wc_tg_sync->get_product_id( $_product );
			$item_tax_rate = $wc_tg_sync->get_order_item_tax_rate( $item );
			$cost_per_unit = $wc_tg_sync->get_order_item_cost_per_unit( $order, $item );
			$variant_id = WC_TradeGecko_Init::get_post_meta( $prod_id, 'variant_id', true );

			if ( false == $variant_id || '' == $variant_id ) {
				throw new Exception( sprintf( __( "Can't update order %s. At least one of the items is not synced and does not have valid TradeGecko variant ID.",
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number() ), 100 );
			}

			$order_items['order_line_items'][] = array(
				'quantity'	=> (int) $item['qty'],
				'discount'	=> '',
				'price'		=> $cost_per_unit,
				'tax_rate'	=> $item_tax_rate,
				'variant_id'	=> $variant_id,
				'order_id'	=> $tg_open_order->id
			);
		}

		// No updates, if we did not build the order items
		if ( empty( $order_items ) ) {
			return;
		}

		// Create the new line items in TG
		$results = array();
		foreach ( $order_items['order_line_items'] as $line_item ) {
			$update = $this->create_tg_order_line_item( $line_item );

			if ( isset( $update->error ) ) {
				throw new Exception( sprintf( __( "Could not do the order items update. Order: %s. Error message: %s",
						WC_TradeGecko_Init::$text_domain ), $order->get_order_number(), $update->error_description ), 100 );
			}
			$results[] = $update;
		}

		WC_TradeGecko_Init::add_log( 'Create items results: '. print_r( $results, true ) );

		// Now that we have the new items created,
		// Delete the old items, but only the real product items(no freeforms)
		$delete_results = array();
		foreach ( $tg_order_items->order_line_items as $delete_line_item ) {
			if ( false == $delete_line_item->freeform ) {
				$delete = $this->delete_tg_order_line_items( $delete_line_item->id );

				if ( isset( $delete->error ) ) {
					throw new Exception( sprintf( __( "Could not delete the order items. Order: %s. Error message: %s",
						WC_TradeGecko_Init::$text_domain ), $order->get_order_number(), $delete->error_description ), 100 );
				}

				$delete_results[] = $delete;
			}
		}

		WC_TradeGecko_Init::add_log( 'Delete items results: '. print_r( $delete_results, true ) );
	}

	/**
	 * Update the TG order Shipping line item
	 *
	 * @since 1.7
	 * @param \WC_Order $order WC order
	 * @param object $tg_open_order TG order
	 * @param object $tg_order_items TG order line items
	 * @param string $action The action we have to perform
	 * @throws Exception
	 * @return void
	 */
	private function update_tg_order_shipping( \WC_Order $order, $tg_open_order, $tg_order_items, $action ) {
		$action_performed = false;
		if ( 'delete' == $action ) {
			$shipping_line_item_id = $this->get_freeform_line_item_id_by_type( $tg_order_items, 'Shipping' );
			$action_results = $this->delete_tg_order_line_items( $shipping_line_item_id );
			$action_performed = true;
		} elseif ( 'update' == $action ) {
			$order_items = $this->generate_shipping_item_data( $order, $tg_open_order );
			$shipping_line_item_id = $this->get_freeform_line_item_id_by_type( $tg_order_items, 'Shipping' );

			$action_results = $this->update_tg_order_line_item( $shipping_line_item_id, $order_items );
			$action_performed = true;
		} elseif ( 'create' == $action ) {
			$order_items = $this->generate_shipping_item_data( $order, $tg_open_order );

			$action_results = $this->create_tg_order_line_item( $order_items );
			$action_performed = true;
		}

		if ( $action_performed ) {
			WC_TradeGecko_Init::add_log( sprintf( '%s Shipping items results: %s', ucfirst( $action ), print_r( $action_results, true ) ) );

			if ( isset( $action_results->error ) ) {
				throw new Exception( sprintf( __( "Could not perform order shipping line item update. Order: %s. Error message: %s",
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number(), $action_results->error_description ), 100 );
			}
		}
	}

	/**
	 * Get the TG Freeform line item id from its type ( Shipping, Discount )
	 *
	 * @since 1.7
	 * @param object $tg_order_items TG Line Items
	 * @param string $line_type The item type
	 * @return int
	 */
	private function get_freeform_line_item_id_by_type( $tg_order_items, $line_type = 'Shipping' ) {
		foreach ( $tg_order_items->order_line_items as $line_item ) {
			if ( true == $line_item->freeform && $line_type == $line_item->line_type ) {
				return $line_item->id;
			}
		}
	}

	/**
	 * Generate the TG order shipping item data to be updated on created in the TG database.
	 *
	 * @since 1.7
	 * @global object $wc_tg_sync
	 * @param object $tg_open_order The TG order object
	 * @return array The shipping item data
	 */
	private function generate_shipping_item_data( \WC_Order $order, $tg_open_order ) {
		global $wc_tg_sync;

		$ship_price = $wc_tg_sync->get_order_shipping_price( $order );
		$tax_rate_ship = $wc_tg_sync->get_order_shipping_tax_rate( $order, $ship_price );
		$shipping_method = $order->get_shipping_method();

		$order_items['order_line_item'] = array(
			'quantity'	=> 1,
			'price'		=> number_format( $ship_price, 2, '.', ''),
			'freeform'	=> 'true',
			'tax_rate'	=> $tax_rate_ship,
			'line_type'	=> 'Shipping',
			'label'		=> 'Shipping - ' . $shipping_method,
			'order_id'	=> $tg_open_order->id,
		);

		return $order_items;
	}

	/**
	 * Update the TG order Discount line item
	 *
	 * @since 1.7
	 * @param \WC_Order $order WC order
	 * @param object $tg_open_order TG order
	 * @param object $tg_order_items TG order line items
	 * @param string $action The action we have to perform
	 * @throws Exception
	 * @return void
	 */
	private function update_tg_order_discount( \WC_Order $order, $tg_open_order, $tg_order_items, $action ) {
		$action_performed = false;
		if ( 'delete' == $action ) {
			$discount_line_item_id = $this->get_freeform_line_item_id_by_type( $tg_order_items, 'Discount' );
			$action_results = $this->delete_tg_order_line_items( $discount_line_item_id );
			$action_performed = true;
		} elseif ( 'update' == $action ) {
			$order_items = $this->generate_discount_item_data( $order, $tg_open_order );
			$discount_line_item_id = $this->get_freeform_line_item_id_by_type( $tg_order_items, 'Discount' );

			$action_results = $this->update_tg_order_line_item( $discount_line_item_id, $order_items );
			$action_performed = true;
		} elseif ( 'create' == $action ) {
			$order_items = $this->generate_discount_item_data( $order, $tg_open_order );

			$action_results = $this->create_tg_order_line_item( $order_items );
			$action_performed = true;
		}

		if ( $action_performed ) {
			WC_TradeGecko_Init::add_log( sprintf( '%s Discount items results: %s', ucfirst( $action ), print_r( $action_results, true ) ) );

			if ( isset( $action_results->error ) ) {
				throw new Exception( sprintf( __( "Could not perform order discount line item update. Order: %s. Error message: %s",
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number(), $action_results->error_description ), 100 );
			}
		}
	}

	/**
	 * Generate the Discount line item data. It is to be used in update or create requests
	 *
	 * @since 1.7
	 * @param \WC_Order $order WC order
	 * @param object $tg_open_order TG order
	 * @return array
	 */
	private function generate_discount_item_data( \WC_Order $order, $tg_open_order ) {
		return $order_items['order_line_item'] = array(
			'quantity'	=> 1,
			'price'		=> '-'.number_format( $order->get_total_discount(), 2, '.', ''),
			'freeform'	=> 'true',
			'line_type'	=> 'Discount',
			'label'		=> 'Discount',
			'order_id'	=> $tg_open_order->id
		);
	}

	/**
	 * Perform a check and update/export on the TG order addresses
	 *
	 * @since 1.7
	 * @global type $wc_tg_sync
	 * @param \WC_Order $order WC order
	 * @param object $tg_open_order TG order
	 */
	public function update_tg_order_addresses( \WC_Order $order, $tg_open_order ) {
		global $wc_tg_sync;

		// Check the order addresses and export a new address, if the address changed.
		// The address ids will be saved to the order.
		$wc_tg_sync->maybe_export_customer_addresses( $tg_open_order->company_id, $order );

		$billing_address = WC_TradeGecko_Init::get_post_meta( $order->id, 'customer_billing_id', true );
		$shipping_address = WC_TradeGecko_Init::get_post_meta( $order->id, 'customer_shipping_id', true );
		$update = false;
		$data = array();

		WC_TradeGecko_Init::add_log( sprintf( 'Shipping old address: %s . Billing old address: %s', $tg_open_order->shipping_address_id, $tg_open_order->billing_address_id ) );
		WC_TradeGecko_Init::add_log( sprintf( 'Shipping new address: %s . Billing new address: %s', $shipping_address, $billing_address ) );

		if ( $billing_address != $tg_open_order->billing_address_id ) {
			$data['order']['billing_address_id'] = $billing_address;
			$update = true;
		}

		if ( $shipping_address != $tg_open_order->shipping_address_id ) {
			$data['order']['shipping_address_id'] = $shipping_address;
			$update = true;
		}

		if ( $update ) {
			$results = $this->update_tg_order( $tg_open_order->id, $data );

			WC_TradeGecko_Init::add_log( sprintf( 'Update address results: %s', print_r( $results, true ) ) );

			if ( isset( $results->error ) ) {
				throw new Exception( sprintf( __( "Could not perform order address update. Order: %s. Error message: %s",
					WC_TradeGecko_Init::$text_domain ), $order->get_order_number(), $results->error_description ), 100 );
			}
		}
	}

	/**
	 * Update order line items in TG to WC direction
	 *
	 * Note: Still in development.
	 *
	 * @since 1.7
	 * @param \WC_Order $order WC order
	 * @param type $tg_open_order TG order
	 */
	private function sync_tg_to_wc_order_changes( \WC_Order $order, $tg_open_order ) {
		// The feature is still in development,
		// so nothing will happen here.
	}

	/**
	 * Query the TG database to retrieve the order line items
	 *
	 * @since 1.7
	 * @param object $tg_order TG Order object
	 * @return object All TradeGecko order line items
	 */
	public function get_tg_order_line_items( $tg_order ) {
		WC_TradeGecko_Init::add_log( 'Order items IDs: '. print_r( $tg_order->order_line_item_ids, true ) );

		$tg_order_items = WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'order_line_items', null, null, array( 'ids' => $tg_order->order_line_item_ids ) ) );

		WC_TradeGecko_Init::add_log( 'Order items Response: '. print_r( $tg_order_items, true ) );

		if ( isset( $tg_order_items->error ) ) {
			throw new Exception( sprintf( __( 'Could not retrieve order items for order %s. Error Message: %s.',
				WC_TradeGecko_Init::$text_domain ), str_replace( $this->order_number_prefix, '', $tg_order->order_number ),
				$tg_order_items->error_description ), 200 );
		}

		return $tg_order_items;
	}

	/**
	 * Create an order line item in the TG database.
	 *
	 * @since 1.7
	 * @param array $item_data The line item data
	 * @return mixed The result of the line item creation as returned by the TG server
	 */
	private function create_tg_order_line_item( $item_data ) {
		return WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'order_line_items', $item_data ) );
	}

	/**
	 * Update TG order line item
	 *
	 * @since 1.7
	 * @param int $update_id The ID of the line item we want to update
	 * @param array $item_data The updated data of the line item
	 * @return mixed The result of the update as returned by the TG server
	 */
	private function update_tg_order_line_item( $update_id, $item_data ) {
		return WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'order_line_items', $item_data, $update_id ) );
	}

	/**
	 * Delete an order line item from a TG order
	 *
	 * @since 1.7
	 * @param int $item_id The ID of the line item to be deleted
	 * @return mixed Result of deletion
	 */
	private function delete_tg_order_line_items( $item_id ) {
		return WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'DELETE', 'order_line_items', null, $item_id ) );
	}

	/**
	 * Update TG Address
	 *
	 * @since 1.7
	 * @param type $address_id
	 * @param type $address_data
	 * @return type
	 */
	private function update_tg_customer_address( $address_id, $address_data ) {
		return WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'addresses', $address_data, $address_id ) );
	}

	/**
	 * Update TG order object
	 *
	 * @since 1.7
	 * @param int $order_id The TG order ID
	 * @param array $data The order data to be updated
	 * @return type
	 */
	private function update_tg_order( $order_id, $data ) {
		return WC_TradeGecko_Init::get_decoded_response_body( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'orders', $data, $order_id ) );
	}

	/**
	 * Split the order ids into smaller batches of 100 or less.
	 *
	 * Filter 'update_orders_chunks' can be used to have more or less orders in batches.
	 *
	 * @since 1.4.1
	 * @param array $order_ids An array of each tg order id
	 * @return array Order ids into 300 or less
	 */
	private function split_tg_order_ids_into_batches( array $order_ids ) {
		$batches = array();
		$chunks_size = apply_filters( 'update_orders_chunks', 300 );

		if ( $chunks_size >= count( $order_ids ) ) {
			$batches[] = $order_ids;
		} else {
			$batches = array_chunk( $order_ids, (int) $chunks_size );
		}

		return $batches;
	}

}