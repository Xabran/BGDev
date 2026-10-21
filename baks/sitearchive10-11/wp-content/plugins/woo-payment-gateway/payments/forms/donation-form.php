<?php
if (BT_Manager ()->getEnvironment () === 'sandbox') {
	?>
<div class="gateway-environment"><?php echo __('Sandbox Mode', 'braintree')?></div>
<?php
}
$paymentMethods = BT_Manager ()->get_option ( 'donation_payment_methods' );
if (! empty ( $paymentMethods )) {
	?>
<div class="accepted-payment-methods">
<?php
	foreach ( $paymentMethods as $key => $method ) {
		if (! empty ( BT_Manager ()->settings ['donation_payment_methods'] [$key] )) {
			?>
	<div class="payment-method">
		<img
			src="<?php echo Braintree_PaymentMethods::paymentMethods()[$key]['src']?>" />
	</div>
	<?php
		}
	}
	?>
</div>
<?php
}
?>
<div id="dropin-container"></div>
