<?php
/**
 * Variable product add to cart
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.4.0
 */
if ( ! defined ( 'ABSPATH' ) ) {
    exit;
}

do_action ( 'yith_gift_cards_template_before_add_to_cart_form' );
?>
    <button id="give-as-present"
            class="btn btn-ghost give-as-present"><?php _e ( "Gift this product", 'yith-woocommerce-gift-cards' ); ?></button>
<?php
YITH_YWGC ()->frontend->show_gift_card_generator ();
