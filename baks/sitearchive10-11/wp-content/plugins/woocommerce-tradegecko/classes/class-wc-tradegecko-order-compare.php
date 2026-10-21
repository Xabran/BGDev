<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class to compare WC order to the TG order
 *
 * @param WC_Order $order WC Order object
 */
class WC_TradeGecko_Order_Compare {

	public function __construct( \WC_Order $order ) {
		$this->order = $order;
	}

	public function compare_order_items( $tg_order_items ) {
		global $wc_tg_sync;

		// Add the line items to the query
		$items = $this->order->get_items();
		$diff = false;
		$matched_variants = array();

		$tg_count = 0;
		foreach ( $tg_order_items->order_line_items as $line_item ) {
			if ( false == $line_item->freeform ) {
				$tg_count += 1;
			}
		}

		// Compare the order items count
		if ( $tg_count != count( $items ) ) {
			WC_TradeGecko_Init::add_log( 'Items count is different: '. print_r( $tg_count, true ) .' '. print_r( count( $items ), true ) );
			$diff = true;
		}

		// We only want to check each item individually,
		// if the items count passes
		if ( ! $diff ) {
			foreach ( $items as $item ) {

				$_product = $this->order->get_product_from_item( $item );
				$prod_id = $wc_tg_sync->get_product_id( $_product );
				$qty = $item['qty'];
				$variant_id = WC_TradeGecko_Init::get_post_meta( $prod_id, 'variant_id', true );
				$item_price = $wc_tg_sync->get_order_item_cost_per_unit( $this->order, $item );

				// We will loop through each order item
				// and try to find changes in the order.
				foreach ( $tg_order_items->order_line_items as $line_item ) {
					// Only real products, no freeforms
					if ( true == $line_item->freeform ) {
						continue;
					}

					// Make sure we are comparing synced products
					if ( $variant_id == $line_item->variant_id ) {
						$matched_variants[] = $variant_id;

						if ( $qty != $line_item->quantity ) {
							WC_TradeGecko_Init::add_log( 'Item qty different: '. print_r( $qty, true ) );
							$diff = true;
							// Bail, if we found a change. No need to continue looping.
							break;
						}

						if ( $item_price != $line_item->price ) {
							WC_TradeGecko_Init::add_log( 'Item price different: '. print_r( $item_price, true ) );
							$diff = true;
							// Bail, if we found a change. No need to continue looping.
							break;
						}
					}
				}
			}
		}

		// If there is still not difference found.
		if ( ! $diff ) {
			// Check to see, if we found a variant match for all WC items.
			// If we found a match for all items,
			// the count of $matched_variants will be the same as the order items count
			if ( count( $matched_variants ) != count( $items ) ) {
				WC_TradeGecko_Init::add_log( 'Found variants count is different: '. print_r( count( $matched_variants ), true ) .' to '. count( $items ) );
				$diff = true;
			}
		}

		WC_TradeGecko_Init::add_log( 'Are items different: '. print_r( $diff, true ) );

		return $diff;
	}

	/**
	 * Compare the Shipping line item.<br/>
	 * Check the $point_of_view and routes the check to the appropriate method
	 *
	 * @since 1.7
	 * @param object $tg_order_items TG order line items
	 * @param string(optional) $point_of_view From which point of view are we comparing the shipping, wc or tg?
	 * @return string|bool The action we have to take according to the compare result. FALSE, if no differences found.
	 */
	public function compare_order_shipping( $tg_order_items, $point_of_view = 'wc' ) {
		$shipping = false;

		// First find the shipping line item
		foreach ( $tg_order_items->order_line_items as $line_item ) {
			if ( true == $line_item->freeform ) {
				if ( 'Shipping' == $line_item->line_type ) {
					$shipping = $line_item;
					break;
				}
			}
		}

		if ( 'tg' == $point_of_view ) {
			$diff = $this->check_shipping_tg_to_wc( $shipping );
		} else {
			$diff = $this->check_shipping_wc_to_tg( $shipping );
		}

		WC_TradeGecko_Init::add_log( 'Shipping differences: '. print_r( $diff, true ) );

		return $diff;
	}

	/**
	 * Compare Shipping line item from WC to TG point of view.
	 *
	 * @since 1.7
	 * @global object $wc_tg_sync
	 * @param object $shipping The TG shipping line item
	 * @return string|bool The action we have to take according to the compare result. FALSE, if no differences found.
	 */
	private function check_shipping_wc_to_tg( $shipping ) {
		global $wc_tg_sync;
		$diff = false;
		$ship_price = $wc_tg_sync->get_order_shipping_price( $this->order );

		// If we have no TG Shipping line item
		if ( ! $shipping ) {
			if ( 0 < $ship_price ) {
				$diff = 'create';
			}
		} else {
			if ( 0 >= $ship_price ) {
				$diff = 'delete';
			} else {
				// Uniform the prices we are going to check
				$wc_tax_rate_ship = number_format( $wc_tg_sync->get_order_shipping_tax_rate( $this->order, $ship_price ), 2, '.', '' );
				$tg_tax_rate_ship = number_format( $shipping->tax_rate, 2, '.', '' );
				$wc_ship_price = number_format( $ship_price, 2, '.', '' );
				$tg_ship_price = number_format( $shipping->price, 2, '.', '' );

				if ( $wc_ship_price != $tg_ship_price ) {
					$diff = 'update';
				}

				if ( $wc_tax_rate_ship != $tg_tax_rate_ship ) {
					$diff = 'update';
				}
			}
		}

		return $diff;
	}

	/**
	 * Compare Discount line item
	 *
	 * @since 1.7
	 * @param type $tg_order_items
	 * @param type $point_of_view
	 * @return type
	 */
	public function compare_order_discount( $tg_order_items, $point_of_view = 'wc' ) {
		$discount = false;

		// First find the shipping line item
		foreach ( $tg_order_items->order_line_items as $line_item ) {
			if ( true == $line_item->freeform ) {
				if ( 'Discount' == $line_item->line_type ) {
					$discount = $line_item;
					break;
				}
			}
		}

		if ( 'tg' == $point_of_view ) {
			$diff = $this->check_discount_tg_to_wc( $discount );
		} else {
			$diff = $this->check_discount_wc_to_tg( $discount );
		}

		WC_TradeGecko_Init::add_log( 'Discount differences: '. print_r( $diff, true ) );

		return $diff;
	}

	/**
	 * Check the discount line item WC to TG
	 *
	 * @since 1.7
	 * @param type $discount
	 * @return string
	 */
	private function check_discount_wc_to_tg( $discount ) {
		$wc_order_discount = $this->order->get_total_discount();
		$diff = false;

		if ( ! $discount ) {
			if ( 0 < $wc_order_discount ) {
				$diff = 'create';
			}
		} else {
			if ( 0 >= $wc_order_discount ) {
				$diff = 'delete';
			} else {
				$tg_discount = number_format( abs( $discount->price ), 2, '.', '' );
				$wc_discount = number_format( $wc_order_discount, 2, '.', '' );
				if ( $wc_discount != $tg_discount ) {
					$diff = 'update';
				}
			}
		}

		return $diff;
	}

	/**
	 * Check the WC shipping address fields against TG address fields
	 *
	 * @since 1.7
	 * @global type $wc_tg_sync
	 * @param type $address
	 * @return boolean
	 */
	public function check_shipping_address_fields( $address ) {
		global $wc_tg_sync;

		// Get the fields from the WC order
		$shipping = $wc_tg_sync->build_customer_shipping_info( $this->order );

		// If any of the address fields are different, then we will update the address
		if (	$shipping['shipping_address']['address1'] != $address->address1 ||
			$shipping['shipping_address']['address2'] != $address->address2 ||
			$shipping['shipping_address']['city'] != $address->city ||
			$shipping['shipping_address']['country'] != $address->country ||
			$shipping['shipping_address']['state'] != $address->state ||
			$shipping['shipping_address']['zip_code'] != $address->zip_code ||
			$shipping['shipping_address']['company_name'] != $address->company_name ||
			$shipping['shipping_address']['first_name'] != $address->first_name ||
			$shipping['shipping_address']['last_name'] != $address->last_name )
		{
			return true;
		}

		return false;
	}

	/**
	 * Check the WC billing address fields against TG address fields
	 *
	 * @since 1.7
	 * @global type $wc_tg_sync
	 * @param type $order
	 * @param type $address
	 * @return boolean
	 */
	public function check_billing_address_fields( $address ) {
		global $wc_tg_sync;

		// Get the fields from the WC order
		$billing = $wc_tg_sync->build_customer_billing_info( $this->order );

		// If any of the address fields are different, then we will update the address
		if (	$billing['billing_address']['address1'] != $address->address1 ||
			$billing['billing_address']['address2'] != $address->address2 ||
			$billing['billing_address']['city'] != $address->city ||
			$billing['billing_address']['country'] != $address->country ||
			$billing['billing_address']['state'] != $address->state ||
			$billing['billing_address']['zip_code'] != $address->zip_code ||
			$billing['billing_address']['company_name'] != $address->company_name ||
			$billing['billing_address']['first_name'] != $address->first_name ||
			$billing['billing_address']['last_name'] != $address->last_name ||
			$billing['billing_address']['email'] != $address->email ||
			$billing['billing_address']['phone_number'] != $address->phone_number )
		{
			return true;
		}

		return false;
	}
}

