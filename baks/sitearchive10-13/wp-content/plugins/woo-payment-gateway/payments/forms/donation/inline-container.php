<div class="overlay-payment-processing">
		  <?php self::getLoader()?>
</div>
<div class="inline-donation-container">
	<div class="errorMessages" id="error_messages"></div>
	<div class="inline-donation-form">
		<form name="donation-form" id="donation-form">
		      <?php
								
								if (self::$_captureName) {
									echo self::buildInputField ( array (
											'type' => 'text',
											'placeholder' => __ ( 'Full Name', 'braintree' ),
											'class' => 'inline-input',
											'value' => '',
											'name' => 'donor_name',
											'id' => 'donor_name' 
									) );
								}
								if (self::$_captureAddress) {
									echo self::buildInputField ( array (
											'type' => 'text',
											'placeholder' => __ ( 'Street Address', 'braintree' ),
											'class' => 'inline-input',
											'name' => 'street_address',
											'id' => 'street_address' 
									) );
									echo self::buildInputField ( array (
											'type' => 'text',
											'placeholder' => __ ( 'Postal Code', 'braintree' ),
											'class' => 'inline-input',
											'name' => 'postal_code',
											'id' => 'postal_code' 
									) );
									echo self::buildSelectField ( array (
											'type' => 'select',
											'placeholder' => __ ( 'Country', 'braintree' ),
											'class' => 'inline-input',
											'options' => self::getCountryOptions ( self::getOption ( 'donation_default_country' ) ),
											'name' => 'billing_country',
											'id' => 'billing_country' 
									) );
								}
								if (self::$_captureEmail) {
									echo self::buildInputField ( array (
											'type' => 'text',
											'placeholder' => __ ( 'Email Address', 'braintree' ),
											'class' => 'inline-input',
											'name' => 'billing_email',
											'id' => 'billing_email' 
									) );
								}
								switch (self::$_donationType) {
									case self::TRANSACTION :
										echo self::buildAmountField ( $atts );
										break;
									case self::RECURRING :
										echo self::buildRecurringPaymentsSelectField ( array (
												'class' => 'inline-input' 
										) );
										break;
								}
								BT_Manager ()->getDonationDropinContainer ();
								?>
			  <div class="inline-submit-donation">
				<input type="submit" id="donation_submit" style="<?php echo self::getDonationButtonStyles()?>" value="<?php echo self::getOption('donation_button_text')?>"/>
			</div>
		</form>
	</div>
</div>