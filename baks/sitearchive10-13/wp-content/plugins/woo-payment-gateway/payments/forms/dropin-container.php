<?php
if (BT_Manager ()->getEnvironment () === 'sandbox') {
?>
	<div class="gateway-environment"><?php echo __('Sandbox Mode', 'braintree')?></div>
<?php
}

include WC_BRAINTREE_PLUGIN . 'payments/forms/accepted-payment-methods.php';

?>
<div id="braintree_dropin_container" <?php echo BT_Manager()->customerHasPaymentMethods() ? 'style="display: none"' : ''?>>
	<div id="dropin-container">
	<?php if(BT_Manager()->customerHasPaymentMethods()){?>
		<div class="payment-method-button">
			<span id="cancel_add_new"><?php echo __('Cancel', 'braintree')?></span>
		</div>
		<?php
	}
	?>
	</div>

<?php
if (wp_get_current_user ()->ID) {
	?>
<label class="save-payment-method-label"><span class="save-cc-helper"><?php echo __('Save', 'braintree')?></span>
	<div class="save-payment-method">
		<input type="checkbox" id="save_payment_method"
			name="save_payment_method"><label for="save_payment_method"></label>
	</div></label>
<?php
}
?>
</div>