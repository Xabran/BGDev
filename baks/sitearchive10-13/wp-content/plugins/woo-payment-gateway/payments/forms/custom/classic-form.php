<div class="classic-form-container">
	<div class="card-number-wrapper">
		<span class="field-label"><?php echo BT_Manager()->get_option('card_number_label')?></span>
		<div id="card-number" class="hosted-field"></div>
		<span class="card-type"></span>
	</div>
	<div class="form-group-wrapper multi-fields">
		<div class="exp-date-field">
			<span class="field-label"><?php echo BT_Manager()->get_option('card_expiration_date_label')?></span>
			<div id="expiration-date" class="hosted-field"></div>
		</div>
		<?php if(BT_Manager()->isActive('cvv_field_enabled')){?>
			<div class="cvv-field">
				<span class="field-label"><?php echo BT_Manager()->get_option('card_cvv_label')?></span>
				<div id="cvv" class="hosted-field"></div>
				<span class="cvv-image"></span>
			</div>
		<?php }?>
	</div>
		<?php if(BT_Manager()->isActive('postal_field_enabled')){?>
			<div class="form-group-wrapper">
				<div class="postal-field">
					<span class="field-label"><?php echo BT_Manager()->get_option('card_postal_label')?></span>
					<div id="postal-code" class="hosted-field"></div>
				</div>
			</div>
		<?php }?>
	<label class="save-payment-method-label"><span class="save-cc-helper"><?php echo __('Save', 'braintree')?></span>
		<div class="save-payment-method">
			<input type="checkbox" id="save_payment_method"
				name="save_payment_method"><label for="save_payment_method"></label>
		</div>
	</label>
</div>