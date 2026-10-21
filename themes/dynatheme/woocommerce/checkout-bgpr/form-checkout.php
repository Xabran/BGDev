<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) );
	return;
}

$bgprPublic = new Bgpr_Public;
$productClub = $bgprPublic::check_if_PlayersClubInCart();
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<?php if ( sizeof( $checkout->checkout_fields ) > 0 ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<?php
		$bucksNeeded = 0;
		$wcsubtotal 	= preg_replace('/&#36;/', '', WC()->cart->get_cart_subtotal());

		$wcsubtotal = filter_var($wcsubtotal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$wcsubtotal = preg_replace("/[^0-9\.]/", "",$wcsubtotal);
		if ($wcsubtotal <= 75):
			$bucksNeeded = 75 - $wcsubtotal;
		endif;
                //NO LONGER IN USE
		//if (!$productClub && $bucksNeeded > 0):
                if(1 == 0):
		?>

		<div class="alert alert-bgbucks">
			<p>Add $<?php echo $bucksNeeded;?> more to get free shipping. 
				<a href="/discover/sale/discount-board-games" class="button keep_shopping last">Keep Shopping</a></p>
		</div>

		<?php
		endif;
		
		if (is_user_logged_in()) {
			//print_r($_SESSION);
			//unset($_SESSION['cart_reward_bucks_used']);
			//unset($_SESSION['cart_reward_coupon_used']);
			setlocale(LC_MONETARY, 'en_US');
			$user = wp_get_current_user();

			$myBGBuck = get_user_meta($user->ID, 'bg_earned_bucks',true);

			if ($myBGBuck > 0 && !$_SESSION['cart_reward_bucks_used']){
		?>
		<div class="alert alert-bgbucks">
			<p>Yeah! You have $<?php echo $myBGBuck;?> in Board Games Bucks! Would you like to redeem? 
				<a href="/redeem" data-redirect="checkout" class="button bgreward_redeem last">Redeem Your BG Bucks!</a></p>
		</div>


		<?php  } //endif user has bucks
		 } ?>

		 <br/><br/>
		<div class="col2-set" id="customer_details">
			<div class="col-1">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

	

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<br/><br/>
	<h3 id="order_review_heading"><?php _e( 'STEP 3: PAYMENT INFORMATION', 'woocommerce' ); ?></h3>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

	<!-- start submit button -->
		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<input type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( 'COMPLETE ORDER' ) . '" />' ); ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

	<!-- end submit button -->
	

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
