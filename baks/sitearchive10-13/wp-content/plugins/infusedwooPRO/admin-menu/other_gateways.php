<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h1>Other Payment Gateways</h1>
<hr>

You can integrate other payment gateways to Infusionsoft. 
By default, all payment gateways are already connected to Infusionsoft's CRM and Marketing modules.
<br><br>
If you want integrate the payment gateways to Infusionsoft's Ecommerce module as well, you may enable this below. 
<br><br>
<div class="big-row">
	
		<div class="ui-toggle<?php echo isset($iwpro->saveOrders) && $iwpro->saveOrders == "yes" ? " checked" : ""; ?>" name="ia_saveOrders">
						<div class="slider"></div>
						<div class="check"><i class="fa fa-check"></i></div>
						<div class="ex"><i class="fa fa-times"></i></div>
					</div>
	
	&nbsp;&nbsp;Create invoices in infusionsoft for all payment gateways
	<br><br>
	<i style="color: #777">If checked, invoices will be generated in infusionsoft for all payment methods. (Orders will be marked as paid if they pay using payment gateways other than infusionsoft.)
	</i>
<br>