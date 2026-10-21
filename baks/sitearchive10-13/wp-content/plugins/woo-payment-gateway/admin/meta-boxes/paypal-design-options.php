<?php
$paypal_options = BT_Manager()->get_paypal_options();
?>
<span class="paypalDesignLink"><?php echo __('PayPal options and settings', 'braintree')?></span>
<div class="paypalDesignModal">
	<div class="paypalDesignContainer">
		<h3><?php echo __('PayPal Button Design', 'braintree')?></h3>
		<div class="instructions">
			<p>
				<?php echo __('On this page you can select the type of PayPal button that you want to be dispalyed on the checkout page.
				Once you select the design, close this page and then click the Save button. If you want to add your own custom button as an option, 
						then please follow the <a target="_blank" href="'.admin_url() . 'admin.php?page=braintree-payments-tutorials">tutorials</a>.', 'braintree')?>
			</p>
			<p><strong><?php echo __('Note: ')?></strong><?php echo __('Because of how a theme\'s styling can affect how elements look, the buttons may appear differently than intended. If 
					the sizing, margin, padding, etc of the buttons appears off you may want to adjst the styling in your theme\'s custom.css file.', 'braintree')?></p>
		</div>
		<span class="closePayPalDesignModal"><?php echo __('Close', 'braintree')?></span>
		<table class="braintree-woocommerce-settings">
			<tbody>
				<?php foreach ( $paypal_options as $key=>$option ){?>
					<?php echo HTML_Helper::buildSettings ( $key, BT_Manager()->get_required_settings()[$key], BT_Manager ()->settings )?>
				<?php }?>
			</tbody>
		</table>
	</div>
</div>
