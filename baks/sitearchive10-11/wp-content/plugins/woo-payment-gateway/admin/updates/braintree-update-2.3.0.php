<?php 
/**
 * File that is used for Version 2.3.0 update.
 */

$environments = array('sandbox', 'production');

$license_status = BT_Manager()->get_payments_config('braintree_payments_license_status');

$license = BT_Manager()->get_payments_config('braintree_payments_license');

BT_Manager()->settings['license_status'] = ! empty($license_status) ? $license_status : BT_Manager()->settings['license_status'];

BT_Manager()->settings['license'] = ! empty($license) ? $license : BT_Manager()->settings['license'];

$api_keys = BT_Manager()->get_payments_config('braintree_api_keys_config');

$woocommerceConfig = BT_Manager()->get_payments_config('braintree_payments_woocommerce_config');

$subscriptionConfig = BT_Manager()->get_payments_config('braintree_subscription_config');

if(! empty($api_keys)){
	foreach($environments as $environment){
		$merchantId = $api_keys[$environment]['merchantId'];
		$publicKey = $api_keys[$environment]['public_key'];
		$privateKey = $api_keys[$environment]['private_key'];
		BT_Manager()->settings[$environment.'_merchant_id'] = $merchantId;
		BT_Manager()->settings[$environment.'_public_key'] = $publicKey;
		BT_Manager()->settings[$environment.'_private_key'] = $privateKey;
	}
	$activeEnvironment = $api_keys['environment'];
	BT_Manager()->settings[$activeEnvironment.'_environment'] = 'yes';
}
if(! empty($woocommerceConfig)){
	BT_Manager()->settings['order_status'] = !empty($woocommerceConfig['order_status']) ? $woocommerceConfig['order_status'] : BT_Manager()->settings['order_status'];
	BT_Manager()->settings['order_prefix'] = !empty($woocommerceConfig['order_prefix']) ? $woocommerceConfig['order_prefix'] : BT_Manager()->settings['order_prefix'];
	BT_Manager()->settings['title_text'] = !empty($woocommerceConfig['payment_title']) ? $woocommerceConfig['payment_title'] : BT_Manager()->settings['title_text'];
	BT_Manager()->settings['paypal_only'] = !empty($woocommerceConfig['only_paypal']) && $woocommerceConfig['only_paypal'] === 'true' ? 'yes' : BT_Manager()->settings['paypal_only'];
}

if(! empty($subscriptionConfig)){
	BT_Manager()->settings['woocommerce_subscriptions_prefix'] = !empty($subscriptionConfig['order_prefix']) ? $subscriptionConfig['order_prefix'] : BT_Manager()->settings['woocommerce_subscriptions_prefix'];
	BT_Manager()->settings['braintree_subscriptions'] = !empty($subscriptionConfig['recurring_billing_type']) && $subscriptionConfig['recurring_billing_type'] === 'braintree' ? 'yes' : '';
	BT_Manager()->settings['woocommerce_subscriptions'] = !empty($subscriptionConfig['recurring_billing_type']) && $subscriptionConfig['recurring_billing_type'] !== 'braintree' ? 'yes' : '';
}
BT_Manager()->settings['enabled'] = 'yes';

BT_Manager()->update_settings();

BT_Manager()->delete_payments_config('braintree_payments_license_status');

BT_Manager()->delete_payments_config('braintree_payments_license');

BT_Manager()->delete_payments_config('braintree_api_keys_config');

BT_Manager()->delete_payments_config('woocommerce_braintree_payment_gateway_settings');

BT_Manager()->delete_payments_config('braintree_payments_woocommerce_config');

BT_Manager()->delete_payments_config('braintree_subscription_config');

delete_option('braintree_payments_next_activation');

add_action('admin_notices', function(){
	?>
	<div class="updated notice notice-success braintree-update-message">
	  <p><?php echo __('Version 2.3.0 is a major update. Not all of the data could be migrated properly due to a reorganization of how data is stored. 
	  		Please check all of your configuration settings as a precaution. If you have Braintree subscriptions configured, you will need to reassign planId\'s to your products. Please follow the tutorials located on the
	  		the <a target="_blank" href="'.get_admin_url().'admin.php?page=braintree-payments-tutorials">tutorials page</a>', 'braintree')?></p>
	</div>
	<?php 

});

?>