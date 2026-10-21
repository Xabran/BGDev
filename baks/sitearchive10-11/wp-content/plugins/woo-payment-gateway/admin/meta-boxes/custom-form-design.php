<?php $options = BT_Manager()->get_custom_form_options()?>

<span class="customFormDesignLink"><?php echo __('Custom form design options', 'braintree')?></span>
<div class="customFormDesignModal">
	<div class="customFormDesignContainer">
		<h3><?php echo __('Custom Form Design', 'braintree')?></h3>
		<div class="instructions">
			<p>
			<?php echo __('The plugin comes with several forms already built and ready to use. If these do not suite your needs you can customize the existing forms or create your own custom form by following the 
					<a target="_blank" href="'.admin_url().'admin.php?page=braintree-payments-tutorials">tutorial</a>.', 'braintree')?>
			</p>
			<div><strong><?php echo __('Note:', 'braintree')?></strong>&nbsp <?php echo __('Some forms may render differently than designed because of how a theme\'s css can override the default styling of the 
					forms. If you find the form is not rendering properly, you can always adjust the css via your theme\'s custom.css file.', 'braintree')?></div>
		</div>
		<span class="closeCustomFormDesignModal"><?php echo __('Close', 'braintree')?></span>
		<table class="braintree-woocommerce-settings">
			<tbody>
				<?php foreach ($options as $key => $option){
					echo HTML_Helper::buildSettings($key, $option);
				} ?>
			</tbody>
		</table>
	</div>
</div>