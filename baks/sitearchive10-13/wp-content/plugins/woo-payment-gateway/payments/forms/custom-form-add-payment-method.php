<input type="hidden" id="client_token"
	value="<?php echo BT_Manager ()->getClientToken ()?>" />
<?php
include WC_BRAINTREE_PLUGIN . 'payments/forms/accepted-payment-methods.php';

$custom_form_key = BT_Manager ()->get_option ( 'custom_form_design' );
$custom_form = BT_Manager ()->get_custom_forms () [$custom_form_key];
?>
<div id="braintree_custom_form">
	<?php
	include $custom_form ['html'];
	?>
</div>