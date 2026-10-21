<?php
/**
 * Show a section with a product suggestion if the gift card was purchased as a gift for a product in the shop
 *
 * @author  Yithemes
 * @package yith-woocommerce-gift-cards-premium\templates\emails
 */

?>
<div class="ywgc-product-suggested">
            <span
	            class="ywgc-suggested-text"><?php echo sprintf( __( "%s would like to suggest you to use this gift card to purchase the following product:", 'yith-woocommerce-gift-cards' ), $gift_card->sender ); ?>
</span>

	<div style="overflow: hidden">
		<img class="ywgc-product-image"
		     src="<?php echo $product->get_image_id() ? current( wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' ) ) : wc_placeholder_img_src(); ?>" />

		<div class="ywgc-product-description">
			<span class="ywgc-product-title"><?php echo $product->post->post_title; ?></span>

			<div
				class="ywgc-product-excerpt"><?php echo wp_trim_words( $product->post->post_excerpt, 20 ); ?></div>
			<a class="ywgc-product-link" href="<?php echo $product_url; ?>">
				<?php _e( "Go to the product", 'yith-woocommerce-gift-cards' ); ?></a>
		</div>
	</div>
</div>
