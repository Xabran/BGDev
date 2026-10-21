<?php
/**
 * File that is used for Version 2.3.3 update.
 */

$users = get_users(array('role'=>'', 'who'=>''));
foreach($users as $user){
	$customerId = get_user_meta($user->ID, 'braintree_vault_id', true);
	update_user_meta($user->ID, 'braintree_production_vault_id', $customerId);
	delete_user_meta($user->ID, 'braintree_vault_id');
}
BT_Manager()->log->writeToLog(sprintf('Version 2.3.3 update. Total users updated: %s', count($users)));

$statuses = array('wc-pending'=>'wc-pending', 'wc-processing'=>'wc-processing', 'wc-on-hold'=>'wc-on-hold', 'wc-completed'=>'wc-completed', 'wc-cancelled'=>'wc-cancelled', 
		'wc-refunded'=>'wc-refunded', 'wc-failed'=>'wc-failed', 'wc-active'=>'wc-active', 'wc-switched'=>'wc-switched', 'wc-expired'=>'wc-expired', 'wc-pending-cancel'=>'wc-pending-cancel');

$orders = get_posts(array(
		'posts_per_page'=>-1, 
		'post_type'=>array('shop_order', 'shop_subscription'), 
		'post_status'=>array_keys($statuses),
		'date_query'=>array(
				array(
						'after'=>array(
								'year'=>2016,
								'month'=>3,
								'day'=>01
						),
						'inclusive'=>true
				)
		)
));
foreach($orders as $order){
	$paymentToken = get_post_meta($order->ID, '_payment_method_token', true);
	if( strpos($paymentToken, 'PayPal - ') !== false){
		$index = strpos($paymentToken, '-') + 2;
		$paymentToken = substr($paymentToken, $index);
		update_post_meta($order->ID, '_payment_method_token', $paymentToken);
	}
}

BT_Manager()->log->writeToLog(sprintf('Version 2.3.3 update. Total orders updated: %s', count($orders)));
?>