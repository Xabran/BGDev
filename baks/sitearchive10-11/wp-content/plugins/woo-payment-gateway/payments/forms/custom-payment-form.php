<?php
$user_id = wp_get_current_user ()->ID;
$has_saved_methods = BT_Manager()->customerHasPaymentMethods();
if (BT_Manager ()->getEnvironment () === 'sandbox') {
	?>
	<div class="gateway-environment"><?php echo __('Sandbox Mode', 'braintree')?></div>
<?php
}
include WC_BRAINTREE_PLUGIN . 'payments/forms/accepted-payment-methods.php';

echo WC_Braintree_Payments::getSavedPaymentMethodForm ( $user_id );

$custom_form_key = BT_Manager ()->get_option ( 'custom_form_design' );
$custom_form = BT_Manager ()->get_custom_forms () [$custom_form_key];

?>
<div id="braintree_custom_form" style="<?php echo $has_saved_methods == true ? 'display: none' : ''?>">
	<?php if($has_saved_methods){?>
	<div class="payment-method-button">
		<span id="cancel_add_new"><?php echo __('Cancel', 'braintree')?></span>
	</div>
	<?php
	}
	include $custom_form ['html'];
	?>
</div>
<input type="hidden" id="client_token" value="<?php echo BT_Manager ()->getClientToken ( BT_Manager()->isActive('3ds_enabled') ? BT_Manager ()->getMerchantAccountId () : [] )?>"/>
<?php
if(BT_Manager()->isActive('3ds_enabled')){
	echo '<div id="braintree_3ds_container"></div>';
}