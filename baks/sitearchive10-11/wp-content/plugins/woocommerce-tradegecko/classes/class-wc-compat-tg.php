<?php
/*
 * Class to ensure compatibility in the transition of WC 2.0, 2.1, 2.2 and 2.3
 *
 * Version 1.3
 * Author: VanboDevelops
 * Author URI: http://www.vanbodevelops.com
 *
 *	Copyright: (c) 2012 - 2015 VanboDevelops
 *	License: GNU General Public License v3.0
 *	License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Compat_TG {

	/**
	 * Is WC 2.1+
	 * @var bool
	 */
	public static $is_wc_2_1;

	/**
	 * Is WC 2.0+
	 * @var type
	 */
	public static $is_wc_2;

	/**
	 * Is WC 2.2+
	 * @var type
	 */
	public static $is_wc_2_2;

	/**
	 * Is WC 2.3+
	 * @var type
	 */
	public static $is_wc_2_3;

	/**
	 * Detect, if we are using WC 2.1+
	 *
	 * @return bool
	 */
	public static function is_wc_2_1() {
		if ( is_bool( self::$is_wc_2_1 ) ) {
			return self::$is_wc_2_1;
		}

		return self::$is_wc_2_1 = ( version_compare( self::get_wc_version_constant(), '2.1', '>=') );
	}

	/**
	 * Detect, if we are using WC 2.0+ up to 2.1
	 *
	 * @return bool
	 */
	public static function is_wc_2() {
		if ( is_bool( self::$is_wc_2 ) ) {
			return self::$is_wc_2;
		}

		return self::$is_wc_2 = ( version_compare( self::get_wc_version_constant(), '2.0', '>=') && version_compare( self::get_wc_version_constant(), '2.1', '<') );
	}

	/**
	 * Detect, if we are using WC 2.2+
	 *
	 * @since 1.1
	 * @return bool
	 */
	public static function is_wc_2_2() {
		if ( is_bool( self::$is_wc_2_2 ) ) {
			return self::$is_wc_2_2;
		}

		return self::$is_wc_2_2 = ( version_compare( self::get_wc_version_constant(), '2.2', '>=') );
	}

	/**
	 * Detect, if we are using WC 2.3+
	 *
	 * @since 1.1.2
	 * @return bool
	 */
	public static function is_wc_2_3() {
		if ( is_bool( self::$is_wc_2_3 ) ) {
			return self::$is_wc_2_3;
		}

		return self::$is_wc_2_3 = ( version_compare( self::get_wc_version_constant(), '2.3', '>=') );
	}

	/**
	 * Get the Gateway settings page
	 *
	 * @param string $class_name
	 * @return string Formated URL
	 */
	public static function gateway_settings_page( $class_name ) {
		$page = 'woocommerce_settings';
		$tab = 'payment_gateways';
		$section = $class_name;
		if ( self::is_wc_2_1() ) {
			$page = 'wc-settings';
			$tab = 'checkout';
			$section = strtolower( $class_name );
		}

		return admin_url( 'admin.php?page='. $page .'&tab='. $tab .'&section='. $section );

	}

	/**
	 * Get the WC logger object
	 *
	 * @global object $woocommerce
	 * @return \WC_Logger
	 */
	public static function get_wc_logger() {
		if ( self::is_wc_2_1() ) {
			return new WC_Logger();
		} else {
			global $woocommerce;
			return $woocommerce->logger();
		}
	}

	/**
	 * Get the stacked site notices
	 *
	 * @global object $woocommerce
	 * @param string $notice_type
	 * @return int
	 */
	public static function wc_notice_count( $notice_type = '' ) {
		if ( self::is_wc_2_1() ) {
			return wc_notice_count( $notice_type );
		} else {
			global $woocommerce;

			if ( 'message' == $notice_type ) {
				return $woocommerce->message_count();
			} else {
				return $woocommerce->error_count();
			}
		}
	}

	/**
	 * Add site notices
	 *
	 * @global object $woocommerce
	 * @param string $message The message to be logged.
	 * @param string $notice_type (Optional) Name of the notice type. Can be success, message, error, notice.
	 * @return void
	 */
	public static function wc_add_notice( $message, $notice_type = 'success' ) {
		if ( self::is_wc_2_1() ) {
			wc_add_notice( $message, $notice_type );
		} else {
			global $woocommerce;

			if ( 'message' == $notice_type || 'success' == $notice_type ) {
				$woocommerce->add_message( $message );
			} else {
				$woocommerce->add_error( $message );
			}
		}
	}

	/**
	 * Get the global WC object
	 *
	 * @global object $woocommerce
	 * @return object
	 */
	public static function get_wc_global() {
		if ( self::is_wc_2_1() && function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}

	}

	/**
	 * Force SSL on a URL
	 *
	 * @global object $woocommerce
	 * @param string $url The URL to format
	 * @return string
	 */
	public static function force_https( $url ) {
		if ( self::is_wc_2_1() ) {
			return WC_HTTPS::force_https_url( $url );
		} else {
			global $woocommerce;
			return $woocommerce->force_ssl( $url );
		}
	}

	/**
	 * Empty the user cart session
	 *
	 * @global object $woocommerce
	 */
	public static function empty_cart() {
		if ( self::is_wc_2_1() ) {
			WC()->cart->empty_cart();
		} else {
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}
	}

	/**
	 * Get other templates (e.g. product attributes) passing attributes and including the file.
	 *
	 * @param mixed $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 * @return void
	 */
	public static function wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( self::is_wc_2_1() ) {
			wc_get_template( $template_name, $args, $template_path, $default_path );
		} else {
			woocommerce_get_template( $template_name, $args, $template_path, $default_path );
		}
	}

	/**
	 * Get Order shipping total
	 *
	 * @param WC_Order $order
	 * @return double
	 */
	public static function get_total_shipping( WC_Order $order ) {
		if ( self::is_wc_2_1() ) {
			return $order->get_total_shipping();
		} else {
			return $order->get_shipping();
		}
	}

	/**
	 * Get My Account URL
	 *
	 * @return string Formatted URL string
	 */
	public static function get_myaccount_url() {
		if ( self::is_wc_2_1() ) {
			return wc_customer_edit_account_url();
		} else {
			return get_permalink( woocommerce_get_page_id( 'myaccount' ) );
		}
	}

	/**
	 * Get Order meta object. WC 2.0 compatibility
	 * Not needed as we don't support WC under 2.0 anymore
	 *
	 * @param array $item
	 * @return \order_item_meta|\WC_Order_Item_Meta
	 */
	public static function get_order_item_meta( $item ) {
		$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

		return $item_meta;
	}

	/**
	 * Get WC version constant.
	 *
	 * @return string|null
	 */
	public static function get_wc_version_constant() {
		if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
			return WC_VERSION;
		}

		if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) {
			return WOOCOMMERCE_VERSION;
		}

		return null;
	}

	/**
	 * Include inline JS
	 *
	 * @since 1.0.1
	 * @global object $woocommerce
	 * @param type $script
	 */
	public static function wc_include_js( $script ) {
		if ( self::is_wc_2_1() ) {
			wc_enqueue_js( $script );
		} else {
			global $woocommerce;
			$woocommerce->add_inline_js( $script );
		}
	}

	/**
	 * Get WC page ID
	 *
	 * @since 1.0.1
	 * @param string $page
	 * @return int
	 */
	public static function wc_get_page_id( $page ) {
		if ( self::is_wc_2_1() ) {
			return wc_get_page_id( $page );
		} else {
			return woocommerce_get_page_id( $page );
		}
	}

	/**
	 * Clean variables
	 *
	 * @since 1.0.2
	 * @param string $var
	 * @return string
	 */
	public static function wc_clean( $var ) {
		if ( self::is_wc_2_1() ) {
			return wc_clean( $var );
		} else {
			return woocommerce_clean( $var );
		}
	}

	/**
	 * Clear all transients cache for product data.
	 *
	 * @since 1.0.3
	 * @param int $product_id
	 */
	public static function clear_product_transients( $product_id ) {
		if ( self::is_wc_2_1() ) {
			wc_delete_product_transients( $product_id );
		} else {
			global $woocommerce;
			$woocommerce->clear_product_transients( $product_id );
		}
	}

	/**
	 * Format date
	 *
	 * @return string
	 */
	public static function wc_date_format() {
		if ( self::is_wc_2_1() ) {
			return wc_date_format();
		} else {
			return woocommerce_date_format();
		}
	}

	/**
	 * Format decimal numbers ready for DB storage
	 *
	 * @param float $number
	 * @param mixed $dp
	 * @param bool $trim_zeros
	 * @return float
	 */
	public static function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
		if ( self::is_wc_2_1() ) {
			return wc_format_decimal( $number, $dp, $trim_zeros );
		} else {
			return woocommerce_format_decimal( $number, $dp, $trim_zeros );
		}
	}

	/**
	 * Get WC page ID
	 *
	 * @since 1.0.3
	 * @param WC_Order $order
	 * @return int
	 */
	public static function get_order_currency( WC_Order $order ) {
		if ( self::is_wc_2_1() ) {
			return $order->get_order_currency();
		} else {
			$currency = get_post_meta( $order->id, '_order_currency', true );
			return $currency;
		}
	}

	/**
	 * Get order object
	 *
	 * @since 1.1
	 * @param int $order_id
	 * @return WC_Order
	 */
	public static function wc_get_order( $order_id ) {
		if ( self::is_wc_2_2() ) {
			return wc_get_order( (int) $order_id );
		} else {
			return new WC_Order( (int) $order_id );
		}
	}

	/**
	 * Get debug file path
	 *
	 * @since 1.1
	 * @param string $name
	 * @return string
	 */
	public static function wc_get_debug_file_path( $name ) {
		if ( self::is_wc_2_2() ) {
			$file = wc_get_log_file_path( $name );
		} else {
			$file = 'woocommerce/logs/'. $name .'-'. sanitize_file_name( wp_hash( $name ) ). '.txt';
		}

		return $file;
	}

	/**
	 * Get available order status ids.<br/>
	 * Formatted in WC <= 2.1 = term_id or term_slug -> name<br/>
	 * Formatted in WC >= 2.2 = status slug -> name
	 *
	 * @since 1.1
	 * @global type $wpdb
	 * @param string $format_by
	 * @return array
	 */
	public static function wc_get_order_statuses( $format_by = 'term_id' ) {
		if ( self::is_wc_2_2() ) {
			return wc_get_order_statuses();
		} else {
			global $wpdb;

			// Ensure we can only use slug or id
			if ( 'term_id' != $format_by ) {
				$format_by = 'slug';
			}

			$order_statuses = get_terms( 'shop_order_status', array( 'hide_empty' => false ) );
			if ( is_array( $order_statuses ) ) {
				foreach ( $order_statuses as $order_status ) {
					$statuses[] = $order_status->slug;
				}
			}

			$count = count($statuses);
			$placeholders = array_fill(0, $count, '%s');
			$status_places = implode(', ', $placeholders);

			$query = "SELECT *
				 FROM   $wpdb->terms
				 WHERE  slug IN ( $status_places )";
			$terms = $wpdb->get_results( $wpdb->prepare( $query, $statuses ) );

			$status = array();
			foreach( $terms as $term ) {
				$status[ $term->$format_by ] = ucfirst( $term->name );
			}

			return $status;
		}
	}

	/**
	 * Get product object
	 *
	 * @since 1.1
	 * @param int $id
	 * @return type
	 */
	public static function wc_get_product( $id ) {
		if ( self::is_wc_2_2() ) {
			return wc_get_product( (int) $id );
		} else {
			return get_product( (int) $id );
		}
	}

	/**
	 * Get the count of all processing orders
	 *
	 * @since 1.1.1
	 * @return type
	 */
	public static function get_processing_order_count() {
		if ( self::is_wc_2_2() ) {
			return wc_processing_order_count();
		} else {
			return get_term_by( 'slug', 'processing', 'shop_order_status' )->count;
		}
	}

	/**
	 * Save transaction ID to the order
	 *
	 * @since 1.1.2
	 * @param WC_Order $order
	 * @param string $transaction_id
	 * @return type
	 */
	public static function payment_complete( $order, $transaction_id = '' ) {
		if ( self::is_wc_2_2() ) {
			return $order->payment_complete( $transaction_id );
		} else {
			return $order->payment_complete();
		}
	}

	/**
	 * Get order total discount.
	 * Since WC 2.3, with or without tax
	 *
	 * @since 1.3
	 * @param WC_Order $order
	 * @param bool $ex_tax
	 *
	 * @return mixed
	 */
	public static function get_total_discount( $order, $ex_tax = true ) {
		if ( self::is_wc_2_3() ) {
			return $order->get_total_discount( $ex_tax );
		} else {
			return $order->get_total_discount();
		}
	}
}