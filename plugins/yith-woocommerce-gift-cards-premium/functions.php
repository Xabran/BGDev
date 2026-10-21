<?php

/**
 * Include files
 */
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards.php' );
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-premium.php' );
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-backend.php' );
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-backend-premium.php' );
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-frontend.php' );
require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-frontend-premium.php' );

require_once( YITH_YWGC_DIR . 'lib/class.ywgc-gift-card.php' );
require_once( YITH_YWGC_DIR . 'lib/class.ywgc-gift-card-premium.php' );
require_once( YITH_YWGC_DIR . 'lib/class.ywgc-plugin-fw-loader.php' );

/** Define constant values */
defined( 'YWGC_CUSTOM_POST_TYPE_NAME' ) || define( 'YWGC_CUSTOM_POST_TYPE_NAME', 'gift_card' );
defined( 'YWGC_PHYSICAL_PLACEHOLDER' ) || define( 'YWGC_PHYSICAL_PLACEHOLDER', 'XXXX-XXXX-XXXX-XXXX' );
defined( 'YWGC_GIFT_CARD_PRODUCT_TYPE' ) || define( 'YWGC_GIFT_CARD_PRODUCT_TYPE', 'gift-card' );
defined( 'YWGC_GIFT_CARD_LAST_VIEWED_ID' ) || define( 'YWGC_GIFT_CARD_LAST_VIEWED_ID', 'ywgc_last_viewed' );
defined( 'YWGC_AMOUNTS' ) || define( 'YWGC_AMOUNTS', '_gift_card_amounts' );
defined( 'YWGC_PRODUCT_PLACEHOLDER' ) || define( 'YWGC_PRODUCT_PLACEHOLDER', '_ywgc_placeholder' );
defined( 'YWGC_MANUAL_AMOUNT_MODE' ) || define( 'YWGC_MANUAL_AMOUNT_MODE', '_ywgc_manual_amount_mode' );
defined( 'YWGC_PHYSICAL_GIFT_CARD' ) || define( 'YWGC_PHYSICAL_GIFT_CARD', '_ywgc_physical_gift_card' );
defined( 'YWGC_DB_VERSION_OPTION' ) || define( 'YWGC_DB_VERSION_OPTION', 'yith_gift_cards_db_version' );
defined( 'YWGC_CATEGORY_TAXONOMY' ) || define( 'YWGC_CATEGORY_TAXONOMY', 'giftcard-category' );
defined( 'YWGC_PRODUCT_IMAGE' ) || define( 'YWGC_PRODUCT_IMAGE', '_ywgc_product_image' );
defined( 'YWGC_PRODUCT_TEMPLATE_DESIGN' ) || define( 'YWGC_PRODUCT_TEMPLATE_DESIGN', '_ywgc_show_product_template_design' );

/*  plugin actions */
defined( 'YWGC_ACTION_RETRY_SENDING' ) || define( 'YWGC_ACTION_RETRY_SENDING', 'retry-sending' );
defined( 'YWGC_ACTION_ENABLE_CARD' ) || define( 'YWGC_ACTION_ENABLE_CARD', 'enable-gift-card' );
defined( 'YWGC_ACTION_DISABLE_CARD' ) || define( 'YWGC_ACTION_DISABLE_CARD', 'disable-gift-card' );
defined( 'YWGC_ACTION_ADD_DISCOUNT_TO_CART' ) || define( 'YWGC_ACTION_ADD_DISCOUNT_TO_CART', 'add-discount' );
defined( 'YWGC_ACTION_VERIFY_CODE' ) || define( 'YWGC_ACTION_VERIFY_CODE', 'verify-code' );

/*  gift card post_metas */
defined( 'YWGC_META_GIFT_CARD_AMOUNT' ) || define( 'YWGC_META_GIFT_CARD_AMOUNT', '_ywgc_amount' );
defined( 'YWGC_META_GIFT_CARD_AMOUNT_TAX' ) || define( 'YWGC_META_GIFT_CARD_AMOUNT_TAX', '_ywgc_amount_tax' );
defined( 'YWGC_META_GIFT_CARD_AMOUNT_BALANCE' ) || define( 'YWGC_META_GIFT_CARD_AMOUNT_BALANCE', '_ywgc_amount_balance' );
defined( 'YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX' ) || define( 'YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX', '_ywgc_amount_balance_tax' );
defined( 'YWGC_META_GIFT_CARD_SENT' ) || define( 'YWGC_META_GIFT_CARD_SENT', '_ywgc_email_sent' );
defined( 'YWGC_META_GIFT_CARD_ORDER_ID' ) || define( 'YWGC_META_GIFT_CARD_ORDER_ID', '_ywgc_order_id' );
defined( 'YWGC_META_GIFT_CARD_ORDERS' ) || define( 'YWGC_META_GIFT_CARD_ORDERS', '_ywgc_orders' );
defined( 'YWGC_META_GIFT_CARD_CUSTOMER_USER' ) || define( 'YWGC_META_GIFT_CARD_CUSTOMER_USER', '_ywgc_customer_user' );
defined( 'YWGC_ORDER_ITEM_DATA' ) || define( 'YWGC_ORDER_ITEM_DATA', '_ywgc_order_item_data' );

/*  order item metas    */
defined( 'YWGC_META_GIFT_CARD_USER_DATA' ) || define( 'YWGC_META_GIFT_CARD_USER_DATA', '_ywgc_gift_card_user_data' );
defined( 'YWGC_META_GIFT_CARD_POST_ID' ) || define( 'YWGC_META_GIFT_CARD_POST_ID', '_ywgc_gift_card_post_id' );
defined( 'YWGC_META_GIFT_CARD_DELIVERY_DATE' ) || define( 'YWGC_META_GIFT_CARD_DELIVERY_DATE', '_ywgc_gift_card_delivery_date' );
defined( 'YWGC_META_GIFT_CARD_STATUS' ) || define( 'YWGC_META_GIFT_CARD_STATUS', '_ywgc_gift_card_status' );

/* Gift card status */
defined( 'GIFT_CARD_STATUS_DISABLED' ) || define( 'GIFT_CARD_STATUS_DISABLED', 'ywgc-disabled' );
defined( 'GIFT_CARD_STATUS_DISMISSED' ) || define( 'GIFT_CARD_STATUS_DISMISSED', 'ywgc-dismissed' );
defined( 'GIFT_CARD_STATUS_ENABLED' ) || define( 'GIFT_CARD_STATUS_ENABLED', 'publish' );
defined( 'GIFT_CARD_STATUS_PRE_PRINTED' ) || define( 'GIFT_CARD_STATUS_PRE_PRINTED', 'ywgc-pre-printed' );

/* Gift card table columns */
defined( 'YWGC_TABLE_COLUMN_ORDER' ) || define( 'YWGC_TABLE_COLUMN_ORDER', 'purchase_order' );
defined( 'YWGC_TABLE_COLUMN_INFORMATION' ) || define( 'YWGC_TABLE_COLUMN_INFORMATION', 'information' );
defined( 'YWGC_TABLE_COLUMN_AMOUNT' ) || define( 'YWGC_TABLE_COLUMN_AMOUNT', 'amount' );
defined( 'YWGC_TABLE_COLUMN_BALANCE' ) || define( 'YWGC_TABLE_COLUMN_BALANCE', 'balance' );
defined( 'YWGC_TABLE_COLUMN_DEST_ORDERS' ) || define( 'YWGC_TABLE_COLUMN_DEST_ORDERS', 'dest_orders' );
defined( 'YWGC_TABLE_COLUMN_DEST_ORDERS_TOTAL' ) || define( 'YWGC_TABLE_COLUMN_DEST_ORDERS_TOTAL', 'dest_order_total' );
defined( 'YWGC_TABLE_COLUMN_ACTIONS' ) || define( 'YWGC_TABLE_COLUMN_ACTIONS', 'gift_card_actions' );

if ( ! function_exists( 'ywgc_required' ) ) {
	/**
	 * Give the "required" attribute, if in use
	 *
	 * @param           $value
	 * @param bool|true $compare
	 * @param bool|true $echo
	 *
	 * @return string
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function ywgc_required( $value, $compare = true, $echo = true ) {
		$required = ( $value === $compare ) || ( $value instanceof $compare );
		$result   = $required ? "required" : '';
		if ( $echo ) {
			echo $result;
		} else {
			return $result;
		}
	}
}

if ( ! function_exists( 'ywgc_can_create_gift_card' ) ) {
	/**
	 * Verify if current user can create product of type gift card
	 */
	function ywgc_can_create_gift_card() {
		return apply_filters( 'ywgc_can_create_gift_card', true );
	}
}

if ( ! function_exists( 'ywgc_get_status_label' ) ) {
	/**
	 * Retrieve the status label for every gift card status
	 *
	 * @param YWGC_Gift_Card $gift_card
	 *
	 * @return string
	 */
	function ywgc_get_status_label( $gift_card ) {
		$label = '';

		switch ( $gift_card->status ) {
			case GIFT_CARD_STATUS_DISABLED:
				$label = __( "The gift card has been disabled", 'yith-woocommerce-gift-cards' );
				break;
			case GIFT_CARD_STATUS_ENABLED:
				$label = __( "Valid", 'yith-woocommerce-gift-cards' );
				break;
			case GIFT_CARD_STATUS_DISMISSED:
				$label = __( "No longer valid, replaced by another code", 'yith-woocommerce-gift-cards' );
				break;
		}

		return $label;
	}
}

if ( ! function_exists( 'ywgc_get_order_item_giftcards' ) ) {
	/**
	 * Retrieve the gift card ids associated to an order item
	 *
	 * @param int $order_item_id
	 *
	 * @return string|void
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function ywgc_get_order_item_giftcards( $order_item_id ) {

		/*
		 * Let third party plugin to change the $order_item_id
		 * 
		 * @since 1.3.7
		 */
		$order_item_id = apply_filters( 'yith_get_order_item_gift_cards', $order_item_id );
		$gift_ids      = wc_get_order_item_meta( $order_item_id, YWGC_META_GIFT_CARD_POST_ID );

		if ( is_numeric( $gift_ids ) ) {
			$gift_ids = array( $gift_ids );
		}

		if ( ! is_array( $gift_ids ) ) {
			$gift_ids = array();
		}

		return $gift_ids;
	}
}

if ( ! function_exists( 'ywgc_set_order_item_giftcards' ) ) {
	/**
	 * Retrieve the gift card ids associated to an order item
	 *
	 * @param int   $order_item_id the order item
	 * @param array $ids           the array of gift card ids associated to the order item
	 *
	 * @return string|void
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function ywgc_set_order_item_giftcards( $order_item_id, $ids ) {

		$ids = apply_filters( 'yith_ywgc_set_order_item_meta_gift_card_ids', $ids, $order_item_id );

		wc_update_order_item_meta( $order_item_id, YWGC_META_GIFT_CARD_POST_ID, $ids );

		do_action( 'yith_ywgc_set_order_item_meta_gift_card_ids_updated', $order_item_id, $ids );
	}
}

if ( ! function_exists( 'wc_help_tip' ) && function_exists('WC') && version_compare( WC()->version, '2.5.0', '<' ) ) {

	/**
	 * Display a WooCommerce help tip. (Added for compatibility with WC 2.4)
	 *
	 * @since  2.5.0
	 *
	 * @param  string $tip        Help tip text
	 * @param  bool   $allow_html Allow sanitized HTML if true or escape
	 *
	 * @return string
	 */
	function wc_help_tip( $tip, $allow_html = false ) {
		if ( $allow_html ) {
			$tip = wc_sanitize_tooltip( $tip );
		} else {
			$tip = esc_attr( $tip );
		}

		return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
	}

}