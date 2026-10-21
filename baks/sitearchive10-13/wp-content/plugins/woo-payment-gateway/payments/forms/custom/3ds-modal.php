<?php ob_start()?>
<div class="threeDS-hosted-modal">
	<div class="threeDS-container">
		<div class="threeDS-modal-header"><?php echo __('Authentication', 'braintree')?></div>
		<div class="threeDS-frame-body"></div>
		<div class="threeDS-cancel"><?php echo __('Cancel', 'braintree')?></div>
	</div>
</div>
<?php return ob_get_clean()?>