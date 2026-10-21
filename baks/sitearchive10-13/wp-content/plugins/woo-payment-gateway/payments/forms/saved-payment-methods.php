<?php
$paymentMethods = BT_Manager()->getSavedPaymentMethods($user_id);
if (! $paymentMethods) {
	return;
}
?>
<div class="payment-method-form" id="saved_payment_methods">
	<div class="payment-method-button">
		<span id="add_new_method"><?php echo __ ( 'Add New', 'braintree' ) ?></span>
		<input type="hidden" id="selected_payment_method"
			name="selected_payment_method" />
	</div>
<?php
foreach ( $paymentMethods as $index => $paymentMethod ) {
	?>
	<div class="payment-method-item card-label <?php echo $paymentMethod['default'] === true ? 'selected' : ''?>"
		payment-token="<?php echo $index ?>">
		<span
			class="payment-method-type <?php echo str_replace ( ' ', '', $paymentMethod ['type'] ) ?>"></span>
		<span class="payment-method-description"><?php echo $paymentMethod['description']?></span>
	</div>
<?php
}
?>
</div>