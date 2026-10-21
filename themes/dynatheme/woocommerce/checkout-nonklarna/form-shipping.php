<?php
/**
 * Checkout shipping information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$themeURL = get_stylesheet_directory_uri();
?>
<ul class="checkout_info">
	<li><img src="<?php echo $themeURL; ?>/images/icon-100-satisfication.png" alt="100% Satisfaction Guaranteed" width="" height="" class="alignleft" /> <h3>100% SATISFACTION GUARANTEED</h3>
		<p>If you are not completely satisfied with your game for any reason, you have a full 30 days to return for a prompt refund, no questions asked. We will do everything we can to insure you get the perfect game for you.</p>
	</li>
	<li><img src="<?php echo $themeURL; ?>/images/icon-return-policy.png" alt="Return Policy" width="" height="" class="alignleft"/><h3>RETURN POLICY</h3>
		<p>We accept all returns within 90 days of unopened games. If for any reason you need to return  or exchange we are here to help you.</p>
	</li>
	<li><img src="<?php echo $themeURL; ?>/images/icon-info-safe.png" alt="Your Information is Safe" width="" height="" class="alignleft"/><h3>YOUR INFORMATION IS SAFE</h3>
		<p>We will not give out your personal information for any purpose whatsoever without your permission.</p>
	</li>
	<li>
		<img src="<?php echo $themeURL; ?>/images/icon-secured.png" alt="Secure Checkout" width="" height="" class="alignleft"/><h3>SECURE CHECKOUT</h3>
		<p>All information is encrypted and transmitted without risk using a Secure Sockets Layer protocol.</p>
	</li>
	<li><img src="<?php echo $themeURL; ?>/images/icon-need-help.png" alt="Need Help?" width="" height="" class="alignleft"/><h3>NEED HELP?</h3>
		<p>Call US @ 1-800-313-PLAY<br/>9AM - 5PM PST <br/>Monday - Friday</p>
	</li>
	
</ul>

