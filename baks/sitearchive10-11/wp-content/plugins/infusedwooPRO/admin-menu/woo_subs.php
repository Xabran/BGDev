<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h1>Woocommerce Subscriptions</h1>
<hr>
InfusedWoo is also designed to work with Woocommerce Subscriptions. <br><br>
Once you have set up your subscriptions using Woocommerce subscriptions, you can then do necessary automation from these subscriptions
through actions sets or campaign builder.
<br><br>
If you haven't yet set up your subscriptions, you may follow this guide from woocommerce:
<a href="http://docs.woothemes.com/document/subscriptions/store-manager-guide/" target="_blank">http://docs.woothemes.com/document/subscriptions/store-manager-guide/</a>


<h2>Automation Through Action Sets</h2>


When editing or adding a new subscription, go to Product Data → Infusionsoft Tab. 
<br><br>
Here, you can setup actions to run when a subscription is activated, cancelled, suspended and expired.
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-20-42-17/Screen-Shot-2014-09-25-20-42-17.png" style="max-width: 100%;" />



<h2>Automation Through Campaign Builder</h2>


If you want to use Infusionsoft's Campaign Builder instead, you can use the API goal to trigger actions within the campaign.
Below is a table showing the API goals you can use for woocommerce subscriptions.

<br><br>

<table class="bluetable" cellspacing=0>
	<thead>
		<tr>
			<th>Goal</th>
			<th>Integration Name</th>
			<th>Call Name</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Payment is made on a specific Subscription</td>
			<td>woosubpayment</td>
			<td><i>{SKU of the the Subscription}</i></td>
		</tr>
		<tr>
			<td>Payment is made on any Subscription</td>
			<td>woosubpayment</td>
			<td><i>any</i></td>
		</tr>
		<tr>
			<td>Specific Subscription is activated</td>
			<td>woosubactivated</td>
			<td><i>{SKU of the the Subscription}</i></td>
		</tr>
		<tr>
			<td>Any Subscription is activated</td>
			<td>woosubactivated</td>
			<td>any</td>
		</tr>
		<tr>
			<td>Specific Subscription is cancelled</td>
			<td>woosubcancelled</td>
			<td><i>{SKU of the the Subscription}</i></td>
		</tr>
		<tr>
			<td>Any Subscription is cancelled</td>
			<td>woosubcancelled</td>
			<td>any</td>
		</tr>
		<tr>
			<td>Specific Subscription is suspended</td>
			<td>woosubsuspended</td>
			<td><i>{SKU of the the Subscription}</i></td>
		</tr>
		<tr>
			<td>Any Subscription is suspended</td>
			<td>woosubsuspended</td>
			<td>any</td>
		</tr>
		<tr>
			<td>Specific Subscription is expired</td>
			<td>woosubexpired</td>
			<td><i>{SKU of the the Subscription}</i></td>
		</tr>
		<tr>
			<td>Any Subscription is expired</td>
			<td>woosubexpired</td>
			<td>any</td>
		</tr>
	</tbody>
</table>