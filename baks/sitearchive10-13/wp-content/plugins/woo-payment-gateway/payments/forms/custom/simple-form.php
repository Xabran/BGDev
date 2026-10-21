<div class="simple-form">
	<div class="form-group">
		<label><?php echo __(BT_Manager()->get_option('card_number_label'))?></label>
		<div id="card-number" class="hosted-field"></div>
	</div>
	<div class="form-group">
		<label><?php echo __(BT_Manager()->get_option('card_expiration_date_label'))?></label>
		<div id="expiration-date" class="hosted-field"></div>
	</div>
	<?php if(BT_Manager()->isActive('cvv_field_enabled')){?>
		<div class="form-group">
			<label><?php echo __(BT_Manager()->get_option('card_cvv_label'))?></label>
			<div id="cvv" class="hosted-field"></div>
		</div>
	<?php }?>
	<?php if(BT_Manager()->isActive('postal_field_enabled')){?>
		<div class="form-group">
			<label><?php echo __(BT_Manager()->get_option('card_postal_label'))?></label>
			<div id="postal-code" class="hosted-field"></div>
		</div>
	<?php }?>
</div>