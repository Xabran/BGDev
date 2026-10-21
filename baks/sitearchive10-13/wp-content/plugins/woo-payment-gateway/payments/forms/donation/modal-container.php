<div class="overlay-payment-processing">
		  <?php self::getLoader()?>
		</div>
<div id="donation_overlay" class="modal-overlay">
	<div class="modal-container">
		<div id="donation_container">
			<div class="errorMessages" id="error_messages"></div>
			<div class="modal-button">
				<span id="cancel_donation"><?php echo __('Cancel', 'braintree')?></span>
			</div>
			<form name="donation-form" id="donation-form">
				  <?php
						$fields = array ();
						if (self::$_captureName) {
							$fields [] = array (
									'type' => 'text',
									'placeholder' => __ ( 'Full Name', 'braintree' ),
									'class' => 'modal-input',
									'value' => '',
									'name' => 'donor_name',
									'id' => 'donor_name' 
							);
						}
						if (self::$_captureAddress) {
							$fields [] = array (
									'type' => 'text',
									'placeholder' => __ ( 'Street Address', 'braintree' ),
									'class' => 'modal-input',
									'name' => 'street_address',
									'id' => 'street_address' 
							);
							$fields [] = array (
									'type' => 'text',
									'placeholder' => __ ( 'Postal Code', 'braintree' ),
									'class' => 'modal-input',
									'name' => 'postal_code',
									'id' => 'postal_code' 
							);
							$fields [] = array (
									'type' => 'select',
									'placeholder' => __ ( 'Country', 'braintree' ),
									'class' => 'modal-input',
									'options' => self::getCountryOptions ( self::getOption ( 'donation_default_country' ) ),
									'name' => 'billing_country',
									'id' => 'billing_country' 
							);
						}
						if (self::$_captureEmail) {
							$fields [] = array (
									'type' => 'text',
									'placeholder' => __ ( 'Email Address', 'braintree' ),
									'class' => 'modal-input',
									'name' => 'billing_email',
									'id' => 'billing_email' 
							);
						}
						$fields = apply_filters ( 'braintree_for_woocommerce_donation_fields', $fields );
						foreach ( $fields as $field ) {
							switch ($field ['type']) {
								case 'text' :
									echo self::buildInputField ( $field );
									break;
								case 'select' :
									echo self::buildSelectField ( $field );
									break;
							}
						}
						switch (self::$_donationType) {
							case self::TRANSACTION :
								echo self::buildAmountField ( $atts );
								break;
							case self::RECURRING :
								echo self::buildRecurringPaymentsSelectField ( array (
										'class' => 'modal-input' 
								) );
								break;
						};
						BT_Manager ()->getDonationDropinContainer ();
						?>
					<div class="modal-submit-donation">
					<input type="submit" id="donation_submit" style="<?php echo self::getDonationButtonStyles()?>" value="<?php echo self::getOption('donation_button_text')?>"/>
				</div>
			</form>
		</div>
	</div>
</div>
