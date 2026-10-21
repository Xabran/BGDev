<?php
$order = wc_get_order ( $post->ID );
?>
<div>
	<h3><?php echo __('Settlement Amount')?></h3>
	<p>
		<input type="text" name="braintree_order_settlement_amount"
			value="<?php echo $order->get_total()?>">
	</p>
</div>