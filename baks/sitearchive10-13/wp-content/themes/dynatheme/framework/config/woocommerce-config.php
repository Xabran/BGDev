<?php

/**
 * Package Included for DynaTheme
 * 
 * Do not Modify this under any circumtances
 *
 * @package     DynaTheme WooCommerce Configuration
 * @subpackage  Core Config
 * @author      SilverKenn
 * @version     0.1.9
 */

 
## support woocommerce if its active
add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
	add_theme_support( 'woocommerce' );
}

## remove woocommerce sidebar on product page
add_action('wp_head', 'remove_sidebars_on_woocommerce', 12);
function remove_sidebars_on_woocommerce() {
	if( is_product() || is_woocommerce() ) {
		remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
		#remove_action( 'genesis_sidebar_alt', 'genesis_do_sidebar_alt' );
	}
}

## remove woocommerce script that for non woocommerce pages
#add_action( 'wp_enqueue_scripts', '_cmk_clean_woocommerce_scripts_styles', 99 );
function _cmk_clean_woocommerce_scripts_styles() {
    if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) { 
        wp_dequeue_style( 'woocommerce_frontend_styles' );
		wp_dequeue_style( 'woocommerce-general');
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'woocommerce_fancybox_styles' );
		wp_dequeue_style( 'woocommerce_chosen_styles' );
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
		wp_dequeue_style( 'select2' );
        wp_dequeue_script( 'wc-add-payment-method' );
        wp_dequeue_script( 'wc-lost-password' );
        wp_dequeue_script( 'wc_price_slider' );
        wp_dequeue_script( 'wc-single-product' );
        wp_dequeue_script( 'wc-add-to-cart' );
        wp_dequeue_script( 'wc-cart-fragments' );
        wp_dequeue_script( 'wc-credit-card-form' );
        wp_dequeue_script( 'wc-checkout' );
        wp_dequeue_script( 'wc-add-to-cart-variation' );
        wp_dequeue_script( 'wc-single-product' );
        wp_dequeue_script( 'wc-cart' );
        wp_dequeue_script( 'wc-chosen' );
        wp_dequeue_script( 'woocommerce' );
        wp_dequeue_script( 'prettyPhoto' );
        wp_dequeue_script( 'prettyPhoto-init' );
        wp_dequeue_script( 'jquery-blockui' );
        wp_dequeue_script( 'jquery-placeholder' );
        wp_dequeue_script( 'jquery-payment' );
        wp_dequeue_script( 'fancybox' );
        wp_dequeue_script( 'jqueryui' );
    }
}
