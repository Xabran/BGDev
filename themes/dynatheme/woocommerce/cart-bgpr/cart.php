<?php
session_start();
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
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
 * @version 2.3.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


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
		<a href="/redeem" data-redirect="cart" class="button bgreward_redeem last">Redeem Your BG Bucks!</a></p>
</div>


<?php  } //endif user has bucks
 } //if user is loggedin ?>

<?php wc_print_notices();

		
		$bgprPublic = new Bgpr_Public;
		$productClub = $bgprPublic::check_if_PlayersClubInCart();

		$bucksNeeded = 0;
		$wcsubtotal 	= preg_replace('/&#36;/', '', WC()->cart->get_cart_subtotal());

		$wcsubtotal = filter_var($wcsubtotal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$wcsubtotal = preg_replace("/[^0-9\.]/", "",$wcsubtotal);
		if ($wcsubtotal <= 75):
			$bucksNeeded = 75 - $wcsubtotal;
		endif;
                //NO LONGER IN USE
		//if (!$productClub && $bucksNeeded > 0):
                if (1 == 0):
			?>

			<div class="alert alert-bgbucks" id="addmorebgbox">
				<p>You will get free shipping for any orders with at least $75 worth of games!
					<a href="/discover/sale/discount-board-games" class="button keep_shopping last">Keep Shopping</a></p>
			</div>

		<?php
		endif;

if (!is_user_logged_in()) { 
    $info_message = ' <a href="#" class="showlogin">' . __( 'Login', 'woocommerce' ) . '</a> to earn or redeem <b>Board Game Bucks</b>';
wc_print_notice( $info_message, 'notice' );

woocommerce_login_form(
        array(
                'message'  => __( 'Welcome back!  Enter your login credentials below.  New customers can create an account during the checkout.', 'woocommerce' ),
                'redirect' => wc_get_page_permalink( 'cart' ),
                'hidden'   => true
        )
);
?>

<div class="alert alert-info">
	<p><a href="/account" ><b>Login</b></a> to earn or redeem <b>Board Game Bucks</b></p>
</div>

<?php } //end if not loggedin  


?>

<a href="<?php echo esc_url( wc_get_checkout_url() ) ;?>" class="checkout-button button alt wc-forward last" style="margin:10px 20px 10px 0px !important;">
	<?php echo __( 'Proceed to Checkout', 'woocommerce' ); ?> &raquo;
</a>

<?php do_action( 'woocommerce_before_cart' );  ?>

<form action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

<?php do_action( 'woocommerce_before_cart_table' ); ?>




<table class="shop_table shop_table_responsive cart" cellspacing="0">
	<thead>
		<tr>
			<th class="product-remove">&nbsp;</th>
			<th class="product-thumbnail">&nbsp;</th>
			<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-price"><?php _e( 'Price', 'woocommerce' ); ?></th>
			<th class="product-quantity"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
			<th class="product-subtotal"><?php _e( 'Total', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

					<td class="product-remove">
						<?php
							echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
								'<a href="%s" class="remove" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
								esc_url( WC()->cart->get_remove_url( $cart_item_key ) ),
								__( 'Remove this item', 'woocommerce' ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							), $cart_item_key );
						?>
					</td>

					<td class="product-thumbnail">
						<?php
							$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

							if ( ! $product_permalink ) {
								echo $thumbnail;
							} else {
								printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
							}
						?>
					</td>

					<td class="product-name" data-title="<?php _e( 'Product', 'woocommerce' ); ?>">
						<?php
							if ( ! $product_permalink ) {
								echo apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;';
							} else {
								echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title() ), $cart_item, $cart_item_key );
							}

							// Meta data
							echo WC()->cart->get_item_data( $cart_item );

							// Backorder notification
							if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
								echo '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>';
							}
						?>
					</td>

					<td class="product-price" data-title="<?php _e( 'Price', 'woocommerce' ); ?>">
						<?php
							echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
						?>
					</td>

					<td class="product-quantity" data-title="<?php _e( 'Quantity', 'woocommerce' ); ?>">
						<?php
							if ( $_product->is_sold_individually() ) {
								$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
							} else {
								$product_quantity = woocommerce_quantity_input( array(
									'input_name'  => "cart[{$cart_item_key}][qty]",
									'input_value' => $cart_item['quantity'],
									'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
									'min_value'   => '0'
								), $_product, false );
							}

							echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
						?>
					</td>

					<td class="product-subtotal" data-title="<?php _e( 'Total', 'woocommerce' ); ?>">
						<?php
							echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
						?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_cart_contents' );
		?>
		<tr>
			<td colspan="6" class="actions">

				<?php if ( wc_coupons_enabled() ) { ?>
					<div class="coupon"><a href="" id="coupon_link">Have a coupon or gift card?</a></div>
					<br/>
					<div class="coupon" id="couponbox" style="display:none;">

						<label for="coupon_code"><?php _e( 'Coupon', 'woocommerce' ); ?>:</label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <input type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply Code', 'woocommerce' ); ?>" />

						<?php do_action( 'woocommerce_cart_coupon' ); ?>
					</div>
				<?php } ?>

				<input type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update Cart', 'woocommerce' ); ?>" />

				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart' ); ?>
			</td>
		</tr>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_after_cart_table' ); ?>

</form>

<?php
//global $bgpr_public;
$itemsCount = WC()->cart->get_cart_contents_count();
$bg_action_type = $itemsCount . '_game_purchase';


	$ClubProduct_inCart = false;
	$totalBeforePlayersClub = 0;
	foreach( WC()->cart->get_cart() as $cart_item_key => $values ) :
		$_product = $values['data'];
		
	
		if( 2244 == $_product->id ) :
			$ClubProduct_inCart = true;
		else:
			$totalBeforePlayersClub = $_product->price + $totalBeforePlayersClub;
		endif;
	endforeach;
if ($ClubProduct_inCart){
	$itemsCount = $itemsCount - 1;
}
$bgBuck = new BGBuck;
$bgReward = $bgBuck->getDataByType($itemsCount);

$wctotal 	= preg_replace('/&#36;/', '', WC()->cart->get_total());


$instantBGBucks = 20;

$wctotal = filter_var($wctotal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
$wctotal = preg_replace("/[^0-9\.]/", "",$wctotal);
//echo $wctotal;
if ($wctotal < 75){
	add_filter( 'woocommerce_shipping_chosen_method', '__return_false', 99);
}

//print_r($bgReward);
?>
<div class="cart-collaterals">

	<div class="cart_playersclub">
		<div class="alert alert-green">
			<p>Complete your order to earn $<?=$bgReward->buck_reward?> in Board Games! 
				<?php if (!is_user_logged_in()): ?>
				<br/>(You will be able to create an account on the next page)
				<?php endif; ?>
			</p>
		</div>
		<div class="clearfix"></div>

		<?php if (empty(WC()->cart->get_cart())): ?>
			<img src="<?php echo get_option('home').'/wp-content/plugins/bgpr/public/images/checkout-image-playersclub.jpg';?>"/>
		<?php else: 

				$ClubProduct_inCart = false;
				$totalBeforePlayersClub = 0;
				foreach( WC()->cart->get_cart() as $cart_item_key => $values ) :
					$_product = $values['data'];
					/*echo '<pre>';
					print_r( $values);
					echo '</pre>';*/
				
					if( 2244 == $_product->id ) :
						$ClubProduct_inCart = true;
						$totalBeforePlayersClub = 0 + $totalBeforePlayersClub;
					else:
						$totalBeforePlayersClub = ($_product->price * $values['quantity'])  + $totalBeforePlayersClub;
					endif;
				endforeach;

				$memberAlready = Bgpr_Public::has_woocommerce_subscription('', 2244, 'active');


				if (!$ClubProduct_inCart && !$memberAlready):
					echo '<a href="" id="playersclubimg"><img src="'.get_option('home').'/wp-content/plugins/bgpr/public/images/checkout-image-playersclub.jpg"/></a>';
				else:

					if (!$memberAlready):
			?>
						<div class="cart_playersclub_graphic">
							<img src="<?php echo get_option('home').'/wp-content/plugins/bgpr/public/images/players-club.jpg';?>"/>
						</div>

						<div class="cart_playersclub_compute">
							<?php
							

							if ($totalBeforePlayersClub < 20){
								$instantBGBucksToBeUsed = $totalBeforePlayersClub;

							} else {
								$instantBGBucksToBeUsed = 20;

							}
							
							$leftOfInstantBG = 20 - $instantBGBucksToBeUsed;

							$withClub 	= $totalBeforePlayersClub + 19.95;
							$withClub = (floatval($withClub) - $instantBGBucksToBeUsed);
							$immediateSavings = $instantBGBucksToBeUsed;

							//echo $instantBGBucksToBeUsed;
							?>
							<p>Smart move! You are already saving money!</p>
				 
							<p>We automatically used $<?=$instantBGBucksToBeUsed?> out of 20 of your Players Club BG Bucks on this order!</p>
							
							<?php
							if ($leftOfInstantBG < 20 && $leftOfInstantBG !=0){ ?>
							<p>The remaining $<?=$leftOfInstantBG?> BG Bucks will be added to your account to use later!</p>
							<?php } ?>
				 
							
						</div>

			<?php 
					else:
					endif;
				endif; 
			endif;?>
	</div>
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>

</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
