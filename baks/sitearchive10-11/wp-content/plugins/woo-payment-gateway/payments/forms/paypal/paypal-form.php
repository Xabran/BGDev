<div class="paypalContainer">
<?php
if (BT_Manager ()->getEnvironment () === 'sandbox') {
	?>
	<div class="gateway-environment"><?php echo __('Sandbox Mode', 'braintree')?></div>
<?php
}
?>
	<div id="paypal_container">
	<?php
	$paypal_design = BT_Manager ()->get_paypal_design();
	include_once $paypal_design ['html'];
	?>
	</div>
	<div id="paypal_vaulted_method" style="display: none"></div>
</div>
<?php if(BT_Manager()->isActive('paypal_only')){?>
<input type="hidden" id="client_token" value="<?php echo BT_Manager ()->getClientToken ()?>">
<?php }?>