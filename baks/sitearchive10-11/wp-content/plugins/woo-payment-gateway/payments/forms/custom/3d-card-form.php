<div class="card-form">
	<div class="card-form__inner cf">
		<div class="card-form__element" data-input-text="<?php echo BT_Manager()->get_option('card_number_placeholder')?>">
			<ul class="card-form__layers">
				<li class="card-form__layer">
					<form action="">
						<div id="card-number" class="card-form__input card-form__hosted-field" />
					</form>
				</li>
			</ul>
		</div>
		<?php if(BT_Manager()->isActive('cvv_field_enabled')){?>
			<div class="card-form__element half" data-input-text="<?php echo BT_Manager()->get_option('card_cvv_placeholder')?>">
				<ul class="card-form__layers">
					<li class="card-form__layer">
						<form action="">
							<div id="cvv" class="card-form__input card-form__hosted-field" />
						</form>
					</li>
				</ul>
			</div>
		<?php }?>
		<div class="card-form__element half" data-input-text="<?php echo BT_Manager()->get_option('card_expiration_date_placeholder')?>">
			<ul class="card-form__layers">
				<li class="card-form__layer">
					<form action="">
						<div id="expiration-date"
							class="card-form__input card-form__hosted-field" />
					</form>
				</li>
			</ul>
		</div>
		<?php if(BT_Manager()->isActive('postal_field_enabled')){?>
			<div class="card-form__element" data-input-text="<?php echo BT_Manager()->get_option('card_postal_placeholder')?>">
				<ul class="card-form__layers">
					<li class="card-form__layer">
						<form action="">
							<div id="postal-code" class="card-form__input card-form__hosted-field" />
						</form>
					</li>
				</ul>
			</div>
		<?php }?>
	</div>
</div>
