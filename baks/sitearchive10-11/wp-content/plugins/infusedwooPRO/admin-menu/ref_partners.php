<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h1>Setting up Affiliate Links / Referral Partner Tracking in InfusedWoo</h1>
<hr>

Setting up an affiliate link pointing to your woocommerce site is easy. It is actually similar to how you setup affiliate links to your existing products except that we need to do a simple tweak on the destination url of the affiliate link so that it will be tracked by InfusedWoo.
<br><br>

To set this up, go to Infusionsoft → CRM → Referral Partners → Referral Tracking Links. Then click "Add a Referral Tracking Link".
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-21-06-38/Screen-Shot-2014-09-25-21-06-38.png" />
<br><br>

Then enter a descriptive name and code you desire for the tracking link. In the website address, we prepend this to the destination url: <br>
<b>http://<?php echo $iwpro->machine_name; ?>.infusionsoft.com/aff.html?to=</b>
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-21-16-47/Screen-Shot-2014-09-25-21-16-47.png" style="max-width: 100%" />
<br><br>
<b>Example:</b><br><br>
So for example, We'd like our affiliates to advertise a product with URL:
<a href="<?php bloginfo('url');?>/sample-product">
	<?php bloginfo('url'); ?>/sample-product/</a>
<br><br>
In the website address, I put the following (following the format above):<br><br>
http://<?php echo $iwpro->machine_name; ?>.infusionsoft.com/aff.html?to=<?php bloginfo('url'); ?>/sample-product
<br><br>
Hit "Save" and your referral tracking link is setup and should appear in the referral partner center. And affiliates should get commission credit every time a sale is made from the their affiliate link.
