<?php
$paymentMethods = BT_Manager ()->get_option ( 'payment_methods' );
if (! empty ( $paymentMethods )) {
	?>
	<div class="accepted-payment-methods">
	<?php
	foreach ( $paymentMethods as $key => $method ) {
		if (! empty ( BT_Manager ()->settings ['payment_methods'] [$key] )) {
			?>
		<div class="payment-method">
		  <img src="<?php echo Braintree_PaymentMethods::paymentMethods()[$key]['src']?>" />
		</div>
		<?php
		}
	}
	?>
	</div>
<?php
}