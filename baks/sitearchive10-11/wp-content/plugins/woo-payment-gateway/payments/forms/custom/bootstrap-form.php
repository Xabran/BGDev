
<div class="row">
	<div class="form-group col-xs-8">
		<label class="control-label"><?php echo BT_Manager()->get_option('card_number_label')?></label>
		<!--  Hosted Fields div container -->
		<div class="form-control" id="card-number"></div>
		<span class="helper-text"></span>
	</div>
	<div class="form-group col-xs-4">
		<div class="row">
			<label class="control-label col-xs-12"><?php echo BT_Manager()->get_option('card_expiration_date_label')?></label>
			<div class="col-xs-6">
				<!--  Hosted Fields div container -->
				<div class="form-control" id="expiration-month"></div>
			</div>
			<div class="col-xs-6">
				<!--  Hosted Fields div container -->
				<div class="form-control" id="expiration-year"></div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<?php if(BT_Manager()->isActive('cvv_field_enabled')){?>
		<div class="form-group col-xs-6">
			<label class="control-label"><?php echo BT_Manager()->get_option('card_cvv_label')?></label>
			<!--  Hosted Fields div container -->
			<div class="form-control" id="cvv"></div>
		</div>
	<?php }?>
	<?php if(BT_Manager()->isActive('postal_field_enabled')){?>
		<div class="form-group col-xs-6">
			<label class="control-label"><?php echo BT_Manager()->get_option('card_postal_label')?></label>
			<!--  Hosted Fields div container -->
			<div class="form-control" id="postal-code"></div>
		</div>
	<?php }?>
</div>
