<?php
/**
 * Checkout gift cards form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-gift-cards.php.
 *
 * @author  YIThemes
 * @package yith-woocommerce-gift-cards-premium/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'YITH_YWGC_INIT' ) ) {
	return;
}

$info_message = apply_filters( 'yith_gift_cards_enter_code_message', __( 'Have a gift card?', 'yith-woocommerce-gift-cards' ) . ' <a href="#" class="ywgc-show-giftcard">' . __( 'Click here to enter your code', 'yith-woocommerce-gift-cards' ) . '</a>' );
if ( empty( $info_message ) ) {
	return;
}
wc_print_notice( $info_message, 'notice' );
?>

<form class="ywgc-enter-code" method="post" style="display:none">

	<p class="form-row">
		<input type="text" name="coupon_code" class="input-text"
		       placeholder="<?php esc_attr_e( 'Gift card code', 'yith-woocommerce-gift-cards' ); ?>" id="giftcard_code"
		       value="" />
		<input type="submit" class="button" name="apply_coupon"
		       value="<?php esc_attr_e( 'Apply gift card', 'yith-woocommerce-gift-cards' ); ?>" />
		<input type="hidden" name="is_gift_card"
		       value="1" />
	</p>

	<p class="form-row form-row-last">

	</p>

	<div class="clear"></div>
</form>
