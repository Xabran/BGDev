<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YWGC_AeliaCS_Module' ) ) {

	/**
	 *
	 * @class   YWGC_AeliaCS_Module
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YWGC_AeliaCS_Module {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Shop's base currency. Used for caching.
		 *
		 * @var string
		 * @since 1.0.6
		 */
		protected static $base_currency;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			/**
			 * Aelia  Multi-currency support
			 */
			add_filter( 'wc_aelia_currencyswitcher_product_convert_callback', array(
				$this,
				'wc_aelia_currencyswitcher_product_convert_callback'
			), 10, 2 );

			/**
			 * Retrieve the array data key for the subtotal in the current currency
			 */
			add_filter( 'yith_ywgc_line_subtotal_key', array( $this, 'wc_aelia_line_subtotal_key' ), 10, 2 );

			/**
			 * Retrieve the array data key for the subtotal tax in the  current currency
			 */
			add_filter( 'yith_ywgc_line_subtotal_tax_key', array( $this, 'wc_aelia_line_subtotal_tax_key' ), 10, 2 );

			///**
			// * Set the amount from base currency to current currency
			// */
			//add_filter ( 'yith_ywgc_gift_card_coupon_amount', array ( $this, 'convert_base_currency_amount_to_user_currency' ), 10, 2 );
//
			/**
			 * Set the amount from customer currency to base currency
			 */
			add_filter( 'yith_ywgc_set_gift_card_coupon_amount_before_deduct',
				array(
					$this,
					'convert_user_currency_amount_to_base_currency'
				) );
//
			///**
			// * Set the tax amount from customer currency to base currency
			// */
			//add_filter ( 'yith_ywgc_set_gift_card_coupon_amount_tax_before_deduct', array ( $this, 'convert_user_currency_amount_to_base_currency' ) );

			//add_filter ( 'ywgc_gift_card_creation_amount', array ( $this, 'convert_manual_amount_to_base_currency' ) );

			//add_filter ( 'yith_ywgc_get_gift_card_price', array ( $this, 'convert_to_user_currency' ) );
//
			add_filter( 'yith_ywgc_gift_card_template_amount', array(
				$this,
				'get_amount_in_gift_card_currency'
			), 10, 2 );
		}

		/**
		 * @param YWGC_Gift_Card_Premium $gift_card
		 *
		 * @return float
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function get_amount_in_gift_card_currency( $amount, $gift_card ) {

			if ( ! empty( $gift_card->currency ) ) {

				$amount = self::get_amount_in_currency( $gift_card->get_amount( true ), $gift_card->currency, null );
			}

			return wc_price( $amount, array( 'currency' => $gift_card->currency ) );
		}

		public function convert_to_user_currency( $amount ) {

			return self::get_amount_in_currency( $amount );
		}

		public function convert_manual_amount_to_base_currency( $amount ) {

			if ( isset( $_POST['ywgc-manual-amount'] ) ) {
				$amount = $this->convert_user_currency_amount_to_base_currency( $amount );
			}

			return $amount;
		}

		/**
		 * Callback to support currency conversion of Gift Card products.
		 *
		 * @param callable   $callback The original callback passed by the Currency
		 *                             Switcher.
		 * @param WC_Product $product  The product to convers.
		 *
		 * @return callable The callback that will perform the conversion.
		 * @since  1.0.6
		 * @author Aelia <support@aelia.co>
		 */
		public function wc_aelia_currencyswitcher_product_convert_callback( $callback, $product ) {

			if ( $product instanceof WC_Product_Gift_Card ) {
				$callback = array( $this, 'convert_gift_card_prices' );
			}

			return $callback;
		}

		/**
		 * Converts the prices of a gift card product to the specified currency.
		 *
		 * @param WC_Product_Gift_Card $product  A variable product.
		 * @param string               $currency A currency code.
		 *
		 * @return WC_Product_Gift_Card The product with converted prices.
		 * @since  1.0.6
		 * @author Aelia <support@aelia.co>
		 */
		public function convert_gift_card_prices( $product, $currency ) {

			$product->min_price = $this->get_amount_in_currency( $product->min_price );
			$product->max_price = $this->get_amount_in_currency( $product->max_price );

			foreach ( $product->amounts as $idx => $amount ) {
				$product->amounts[ $idx ] = $this->get_amount_in_currency( $product->amounts[ $idx ] );
			}

			if ( ! empty( $product->price ) ) {

				$product->price = $this->get_amount_in_currency( $product->price );
			}

			return $product;
		}

		/**
		 * Retrieve the array data key for the subtotal in the current currency
		 */
		public function wc_aelia_line_subtotal_key() {
			return "line_subtotal_base_currency";
		}

		/**
		 * Retrieve the array data key for the subtotal in the current currency
		 */
		public function wc_aelia_line_subtotal_tax_key() {
			return "line_subtotal_tax_base_currency";
		}

		/**
		 * Convert the amount from base currency to current currency
		 *
		 * @param float                  $amount
		 * @param YWGC_Gift_Card_Premium $gift_card
		 *
		 * @return float
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function convert_base_currency_amount_to_user_currency( $amount, $gift_card ) {

			if ( ! empty( $gift_card->currency ) ) {

				return self::get_amount_in_currency( $amount, null, $gift_card->currency );
			}

			return self::get_amount_in_currency( $amount );
		}

		/**
		 * Convert the amount from current currency to base currency
		 *
		 * @param float $amount
		 *
		 * @return float
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function convert_user_currency_amount_to_base_currency( $amount ) {

			return self::get_amount_in_currency( $amount, self::base_currency(), get_woocommerce_currency() );
		}

		/**
		 * Convenience method. Returns WooCommerce base currency.
		 *
		 * @return string
		 * @since 1.0.6
		 */
		public static function base_currency() {

			if ( empty( self::$base_currency ) ) {
				self::$base_currency = get_option( 'woocommerce_currency' );
			}

			return self::$base_currency;
		}

		/**
		 * Basic integration with WooCommerce Currency Switcher, developed by Aelia
		 * (https://aelia.co). This method can be used by any 3rd party plugin to
		 * return prices converted to the active currency.
		 *
		 * @param double $amount        The source price.
		 * @param string $to_currency   The target currency. If empty, the active currency
		 *                              will be taken.
		 * @param string $from_currency The source currency. If empty, WooCommerce base
		 *                              currency will be taken.
		 *
		 * @return double The price converted from source to destination currency.
		 * @author Aelia <support@aelia.co>
		 * @link   https://aelia.co
		 * @since  1.0.6
		 */
		public static function get_amount_in_currency( $amount, $to_currency = null, $from_currency = null ) {

			if ( empty( $from_currency ) ) {
				$from_currency = self::base_currency();
			}
			if ( empty( $to_currency ) ) {
				$to_currency = get_woocommerce_currency();
			}

			return apply_filters( 'wc_aelia_cs_convert', $amount, $from_currency, $to_currency );
		}
	}
}

YWGC_AeliaCS_Module::get_instance();