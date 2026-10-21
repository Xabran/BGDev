<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Product_Gift_Card' ) ) {

	/**
	 *
	 * @class   YWGC_Gift_Card
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class WC_Product_Gift_Card extends WC_Product {

		/**
		 * @var array the amounts set for the product
		 */
		public $amounts;

		/**
		 * Initialize a gift card product.
		 *
		 * @param mixed $product
		 */
		public function __construct( $product ) {
			$this->product_type = YWGC_GIFT_CARD_PRODUCT_TYPE;

			parent::__construct( $product );
			$this->amounts = $this->get_amounts_post_meta();
		}

		/**
		 * A virtual gift card is also downloadable
		 *
		 * @return bool
		 */
		public function is_downloadable() {
			return $this->is_virtual();
		}

		/**
		 * Retrieve the number of current amounts for this product
		 *
		 * @return int
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		private function get_amounts_count() {
			return count( $this->amounts );
		}

		/**
		 * Retrieve a list of amounts for the current product
		 *
		 * @return array
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		private function get_amounts_post_meta() {
			$result = array();

			if ( $this->id ) {
				$result = get_post_meta( $this->id, YWGC_AMOUNTS, true );
			}

			$result = is_array( $result ) ? $result : array();

			return apply_filters( 'yith_ywgc_gift_card_amounts', $result, $this );
		}

		/**
		 * Returns false if the product cannot be bought.
		 *
		 * @return bool
		 */
		public function is_purchasable() {

			$purchasable = $this->get_amounts_count();

			return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
		}

		/**
		 * Returns the price in html format
		 *
		 * @access public
		 *
		 * @param string $price (default: '')
		 *
		 * @return string
		 */
		public function get_price_html( $price = '' ) {
			$amounts = $this->get_amounts_post_meta();

			// No price for current gift card
			if ( ! count( $amounts ) ) {
				$price = apply_filters( 'yith_woocommerce_gift_cards_empty_price_html', '', $this );
			} else {
				$prices    = $this->get_amounts_to_be_shown();
				$min_price = current( $prices );
				$max_price = end( $prices );

				$price = $min_price !== $max_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'yith-woocommerce-gift-cards' ), wc_price( $min_price ), wc_price( $max_price ) ) : wc_price( $min_price );
				$price = apply_filters( 'yith_woocommerce_gift_cards_amount_range', $price, $this );
			}

			return apply_filters( 'woocommerce_get_price_html', $price, $this );
		}

		/*
		public function get_price() {

			if ( is_product() && $this->amounts ) {
				return current( $this->amounts );
			}

			return parent::get_price();
		}
		*/

		/**
		 * Retrieve an array of gift cards amounts with the corrected value to be shown(inclusive or not inclusive taxes)
		 *
		 * @return array
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function get_amounts_to_be_shown() {
			$amounts          = $this->amounts;
			$amounts_to_show  = array();
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

			$original_amounts = $this->get_amounts_post_meta();
			$index            = 0;

			foreach ( $amounts as $amount ) {
				if ( 'incl' === $tax_display_mode ) {
					$price = $this->get_price_including_tax( 1, $amount );
				} else {
					$price = $this->get_price_excluding_tax( 1, $amount );
				}

				$original_amount                     = $original_amounts[ $index ];
				$amounts_to_show[ $original_amount ] = $price;
				$index ++;
			}

			return $amounts_to_show;
		}

		/**
		 * Get the add to cart button text
		 *
		 * @return string
		 */
		public function add_to_cart_text() {

			return apply_filters( 'yith_woocommerce_gift_cards_add_to_cart_text', __( 'Select amount', 'yith-woocommerce-gift-cards' ), $this );
		}

		/**
		 * Add a new amount to the gift cards
		 *
		 * @param float $amount
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function add_amount( $amount ) {

			$amounts = $this->get_amounts_post_meta();

			if ( ! in_array( $amount, $amounts ) ) {

				$amounts[] = $amount;
				sort( $amounts, SORT_NUMERIC );
				$this->save_amounts( $amounts );
			}

			return false;
		}

		/**
		 * Remove an amount from the amounts list
		 *
		 * @param float $amount
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function remove_amount( $amount ) {

			$amounts = $this->get_amounts_post_meta();

			if ( in_array( $amount, $amounts ) ) {
				if ( ( $key = array_search( $amount, $amounts ) ) !== false ) {
					unset( $amounts[ $key ] );
				}

				$this->save_amounts( $amounts );

				return true;
			}

			return false;
		}


		/**
		 * Save the gift card product amounts
		 *
		 * @param array $amounts current amount list
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		private function save_amounts( $amounts = array() ) {
			update_post_meta( $this->id, YWGC_AMOUNTS, $amounts );
		}
	}
}