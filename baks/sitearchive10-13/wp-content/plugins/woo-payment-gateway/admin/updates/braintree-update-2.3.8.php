<?php
/**
 * In this update, subscriptions that used the braintree_payments_gateway are updated with the _merchant_account_id information. This is 
 * an important update because WooCommerce Subscriptions that were created in a certain currency, need to be processed using the correct
 * merchant account id.
 * @author Clayton Rogers
 * @since 2.3.8
 */
global $wpdb;

$query = $wpdb->prepare ( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = %s ", 'shop_subscription' );
$orders = $wpdb->get_results ( $query, OBJECT );
$count = 0;
if ($orders) {
	foreach ( $orders as $order ) {
		$payment_method = get_post_meta ( $order->ID, '_payment_method', true );
		if ($payment_method === 'braintree_payment_gateway') {
			$transaction_id = get_post_meta ( $order->ID, '_transaction_id', true );
			$merchant_account_id = get_post_meta ( $order->ID, '_merchant_account_id', true );
			if (! $merchant_account_id) {
				if ($transaction_id) {
					$transaction = BT_Manager ()->getBraintreeTransaction ( $transaction_id );
					if ($transaction) {
						update_post_meta ( $order->ID, '_merchant_account_id', $transaction->merchantAccountId );
						$count ++;
					}
				}
			}
		}
	}
	BT_Manager ()->log->writeToLog ( sprintf ( __ ( 'Braintree For WooCommerce: %s orders were updated with merchant account id\'s during upgrade to version 2.3.8', 'braintree' ), $count ) );
	BT_Manager ()->addAdminNotice ( array (
			'type' => 'success',
			'text' => sprintf ( __ ( 'Braintree For WooCommerce: %s orders were updated with merchant account id\'s during upgrade to version 2.3.8', 'braintree' ), $count ) 
	) );
}