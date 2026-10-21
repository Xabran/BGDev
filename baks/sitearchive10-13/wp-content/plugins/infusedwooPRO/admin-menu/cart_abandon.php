<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h1>Cart Abandon Campaign</h1>
<hr>

This is a free campaign blueprint that comes with InfusedWoo Plugin. 
<br><br>

According to a <a href="http://baymard.com/lists/cart-abandonment-rate" target="_blank">research</a> from Baymard Institute, an average of 67.9% of your site visitor abandon their shopping cart. 
As an example on how to effectively use 
the <?php echo infusedwoo_sub_menu_link('campaign_goals', 'available campaign API goals'); ?> that is built-in inside InfusedWoo, we will be building a Cart Abandon Campaign in Infusionsoft.
<br><br>

<h2>Campaign Blueprint</h2>

<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Test_Campaign_2016-05-24_15-32-13.png" style="width:100%;" />
<br><br>
This campaign blueprint is based from a very good blog post:
<a href="http://blog.marketo.com/2013/12/how-to-send-perfectly-time-abandoned-cart-emails.html" target="_blank">
<i>How to Send Perfectly Timed “Abandoned Cart” Emails</i>
</a><br><br>
We will be building a campaign that sends three emails when they abandon their cart.
First email will be sent 1 hour after purchase, Second Email 24 hours thereafter. And third email after 48 hours.
<br><br>
<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Test_Campaign_2016-05-24_15-36-09.png" style="float:left; margin-right: 5px; margin-bottom: 5px; margin-right: 10px; width: 210px" />

<b>1. Add an API Goal: </b> From the Campaign tools, drag the Goal tool to the campaign area. Enter the Goal name (something descriptive)
Then configure the goal as API goal. See below:<br><br>
<br><br><br><br><br>
<center>
<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Test_Campaign_2016-05-24_15-42-54.png" />
</center>
<br><br>
<b>2. Enter API Settings to the Goal</b>: Double click on the newly created API goal to enter the settings.
Then set the integration name to "wooevent" and call name "reachedcheckout" then hit "save".
<br><br>
<center>
<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Test_Campaign_2016-05-24_15-45-59.png"  />
</center>

<br><br>
<b>3. Cart Abandon Sequence</b>
<br><br>
<center>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-23-20-37-42/Screen-Shot-2014-09-23-20-37-42.png" style="width: 100%;" /><br>
</center>
<br>Next we add a new sequence and connect the "reached checkout" goal to this sequence. In this example, we sent out three emails.<br>
<br>
First email is more focused on helping the customer, e.g. asking them if they have some technical difficulties or issues with payments.
This is sent 1 hour after they have reached the checkout page.<br><br>

Second email is sent 24 hours after the first email and this is a more urgent email, e.g. telling them that their cart will expire or 
some of their cart items will soon get out of stock.<br><br>

Last email is sent 48 hours after the last email. In this email you may give a discount or a coupon code.
<br><br>
<b>TIP:</b> If you have enabled Advanced Cart Tracking in <?php echo infusedwoo_sub_menu_link('campaign_goals', 'Available campaign API goals section'); ?>,
You can embed this link on your emails so your customers can restore their cart automatically when they click the link:<br><br>
<?php 
	$iw_cart_uri = WC_Cart::get_cart_url(); 
	if(strpos($iw_cart_uri, '?') !== false) {
		$iw_cart_uri .= "&ia_saved_cart=~Contact.Email~";
	} else {
		$iw_cart_uri .= "?ia_saved_cart=~Contact.Email~";
	}

	echo $iw_cart_uri;
?>


<br><br>
<b>4. Ending Goals: </b> Last but not the least, we add ending goals that will stop the campaign when two events happen: when the customer purchases and when the 
customer empties their cart.<br><br><center>
<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Test_Campaign_2016-05-24_15-48-57.png" style=""/>
</center>
<br><br>
This is a very important step. Without this goal, all customers going to your checkout page will receive all cart abandon emails even if they have successfully purchased. 
<br><br>
To do this, create two API goals (repeat #1 and #2) and place the goals next to the sequence you created from #3. This time set the API goal values to the following:<br>
Purchase Goal = Integration: <i>woopurchase</i>, Call Name: <i>any</i><br>
Emptied Cart Goal = Integration: <i>wooevent</i>, Call Name: <i>emptiedcart</i><br>
<br>
<hr>

