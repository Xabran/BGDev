<?php
/**
 * My gift cards
 *
 * @package yith-woocommerce-gift-cards-premium\templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$my_orders_columns = array(
	'code'    => __( 'Code', 'yith-woocommerce-gift-cards' ),
	'value'   => __( 'Value', 'yith-woocommerce-gift-cards' ),
	'balance' => __( 'Balance', 'yith-woocommerce-gift-cards' ),
	'usage'   => __( "Usage", 'yith-woocommerce-gift-cards' ),
	'status'  => __( 'Status', 'yith-woocommerce-gift-cards' ),
);

$gift_cards_args = apply_filters( 'yith_ywgc_woocommerce_my_account_my_orders_query', array(
	'numberposts' => - 1,
	'meta_key'    => YWGC_META_GIFT_CARD_CUSTOMER_USER,
	'meta_value'  => get_current_user_id(),
	'post_type'   => YWGC_CUSTOM_POST_TYPE_NAME,
	'post_status' => 'any',
) );

//  Retrieve the gift cards matching the criteria
$posts = get_posts( $gift_cards_args );

if ( $posts ) : ?>

	<h2><?php echo apply_filters( 'yith_ywgc_my_account_my_giftcards', __( 'My gift cards', 'yith-woocommerce-gift-cards' ) ); ?></h2>

	<table class="shop_table shop_table_responsive my_account_giftcards">
		<thead>
		<tr>
			<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
				<th class="<?php echo esc_attr( $column_id ); ?>"><span
						class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
			<?php endforeach; ?>
		</tr>
		</thead>

		<tbody>
		<?php foreach ( $posts as $single_post ) :
			$args = array( 'ID' => $single_post->ID );
			$gift_card = new YWGC_Gift_Card_Premium( $args );

			if ( ! $gift_card->exists() ) {
				continue;
			}
			?>
			<tr class="ywgc-gift-card status-<?php echo esc_attr( $gift_card->status ); ?>">
				<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
					<td class="<?php echo esc_attr( $column_id ); ?> "
					    data-title="<?php echo esc_attr( $column_name ); ?>">

						<?php
						$value = '';
						switch ( $column_id ) {
							case 'code' :
								$value = $gift_card->get_code();
								break;

							case 'value' :
								$value = wc_price( apply_filters( 'yith_ywgc_get_gift_card_price', $gift_card->get_amount( true ) ) );
								break;

							case 'balance' :
								$value = wc_price( apply_filters( 'yith_ywgc_get_gift_card_price', $gift_card->get_balance( true ) ) );
								break;

							case 'status' :
								$value = ywgc_get_status_label( $gift_card );
								break;

							case 'usage' :
								$orders = $gift_card->get_registered_orders();

								if ( $orders ) {
									foreach ( $orders as $order_id ) {
										?>
										<a href="<?php echo wc_get_endpoint_url( 'view-order', $order_id ); ?>"
										   class="ywgc-view-order button">
											<?php printf( __( "Order %s", 'yith-woocommerce-gift-cards' ), $order_id ); ?>
										</a><br>
										<?php
									}
								} else {
									_e( "The code has not been used yet", 'yith-woocommerce-gift-cards' );
								}
								break;
							default:
								$value = apply_filters( 'yith_ywgc_my_account_column', '', $column_id, $gift_card );
						}

						if ( $value ) {
							echo '<span>' . $value . '</span>';
						}
						?>

					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
