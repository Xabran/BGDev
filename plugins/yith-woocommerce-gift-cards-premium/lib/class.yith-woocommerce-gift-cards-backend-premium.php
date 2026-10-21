<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'YITH_WooCommerce_Gift_Cards_Backend_Premium' ) ) {

	/**
	 *
	 * @class   YITH_WooCommerce_Gift_Cards_Backend_Premium
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YITH_WooCommerce_Gift_Cards_Backend_Premium extends YITH_WooCommerce_Gift_Cards_Backend {
		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		protected function __construct() {

			parent::__construct();

			// Add to admin_init function
			add_filter( 'manage_edit-gift_card_columns', array( $this, 'add_custom_columns_title' ) );

			// Add to admin_init function
			add_action( 'manage_gift_card_posts_custom_column', array(
				$this,
				'add_custom_columns_content',
			), 10, 2 );

			add_action( 'woocommerce_before_order_itemmeta', array(
				$this,
				'woocommerce_before_order_itemmeta'
			), 10, 3 );

			/**
			 * Add an email action for sending the digital gift card
			 */
			add_filter( 'woocommerce_email_actions', array( $this, 'add_gift_cards_trigger_action' ) );

			/**
			 * Locate the plugin email templates
			 */
			add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_core_template' ), 10, 3 );

			/**
			 * Add the email used to send digital gift card to woocommerce email tab
			 */
			add_filter( 'woocommerce_email_classes', array( $this, 'add_woocommerce_email_classes' ) );

			add_action( 'ywgc_start_gift_cards_sending', array( $this, 'send_delayed_gift_cards' ) );

			/**
			 * Set the class rate and class tax as visible for product of  type "gift cards"
			 */
			add_action( 'woocommerce_product_options_general_product_data', array(
				$this,
				'show_tax_class_for_gift_cards'
			) );

			/**
			 * manage CSS class for the gift cards table rows
			 */
			add_filter( 'post_class', array( $this, 'add_cpt_table_class' ), 10, 3 );

			add_action( 'init', array( $this, 'redirect_gift_cards_link' ) );
			add_action( 'woocommerce_before_cart', array( $this, 'manage_gift_card_email' ) );

			add_action( 'load-upload.php', array( $this, 'set_gift_card_category_to_media' ) );

			add_action( 'edited_term_taxonomy', array( $this, 'update_taxonomy_count' ), 10, 2 );

			/**
			 * Show icon that prompt the admin for a pre-printed gift cards buyed and whose code is not entered
			 */
			add_action( 'manage_shop_order_posts_custom_column', array(
				$this,
				'show_warning_for_pre_printed_gift_cards'
			) );

		}

		/**
		 * Show icon on backend page "orders" for order where there is file uploaded and waiting to be confirmed.
		 *
		 * @param string $column current column being shown
		 */
		public function show_warning_for_pre_printed_gift_cards( $column ) {
			//  If column is not of type order_status, skip it
			if ( 'order_status' !== $column ) {
				return;
			}

			global $the_order;
			$count = $this->pre_printed_cards_waiting_count( $the_order );
			if ( $count ) {
				$message = _n( "This order contains one pre-printed gift card that needs to be filled", sprintf( "This order contains %d pre-printed gift cards that needs to be filled", $count ), $count, 'yith-woocommerce-gift-cards' );
				echo '<img class="ywgc-pre-printed-waiting" src="' . YITH_YWGC_ASSETS_IMAGES_URL . 'waiting.png" title="' . $message . '" />';
			}
		}

		/**
		 * Retrieve the number of pre-printed gift cards that are not filled
		 *
		 * @param WC_Order $order
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 * @return int
		 */
		private function pre_printed_cards_waiting_count( $order ) {
			$order_items = $order->get_items( 'line_item' );
			$count       = 0;

			foreach ( $order_items as $order_item_id => $order_data ) {
				$gift_ids = ywgc_get_order_item_giftcards( $order_item_id );

				if ( empty( $gift_ids ) ) {
					return;
				}

				foreach ( $gift_ids as $gift_id ) {

					$args = array( 'ID' => $gift_id );
					$gc   = new YWGC_Gift_Card_Premium( $args );

					if ( $gc->is_pre_printed() ) {
						$count ++;
					}
				}
			}

			return $count;
		}

		/**
		 * Fix the taxonomy count of items
		 *
		 * @param $term_id
		 * @param $taxonomy_name
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function update_taxonomy_count( $term_id, $taxonomy_name ) {
			//  Update the count of terms for attachment taxonomy
			if ( YWGC_CATEGORY_TAXONOMY != $taxonomy_name ) {
				return;
			}

			//  update now
			global $wpdb;
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND (post_parent = 0 OR (post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term_id ) );

			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term_id ) );
		}


		public function set_gift_card_category_to_media() {

			//  Skip all request without an action
			if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['action2'] ) ) {
				return;
			}

			//  Skip all request without a valid action
			if ( ( '-1' == $_REQUEST['action'] ) && ( '-1' == $_REQUEST['action2'] ) ) {
				return;
			}

			$action = '-1' != $_REQUEST['action'] ? $_REQUEST['action'] : $_REQUEST['action2'];

			//  Skip all request that do not belong to gift card categories
			if ( ( 'ywgc-set-category' != $action ) && ( 'ywgc-unset-category' != $action ) ) {
				return;
			}

			//  Skip all request without a media list
			if ( ! isset( $_REQUEST['media'] ) ) {
				return;
			}

			$media_ids = $_REQUEST['media'];

			//  Check if the request if for set or unset the selected category to the selected media
			$action_set_category = ( 'ywgc-set-category' == $action ) ? true : false;

			//  Retrieve the category to be applied to the selected media
			$category_id = '-1' != $_REQUEST['action'] ? intval( $_REQUEST['categories1_id'] ) : intval( $_REQUEST['categories2_id'] );

			foreach ( $media_ids as $media_id ) {

				// Check whether this user can edit this post
				//if ( ! current_user_can ( 'edit_post', $media_id ) ) continue;

				if ( $action_set_category ) {
					$result = wp_set_object_terms( $media_id, $category_id, YWGC_CATEGORY_TAXONOMY, true );
				} else {
					$result = wp_remove_object_terms( $media_id, $category_id, YWGC_CATEGORY_TAXONOMY );
				}

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		/**
		 * manage CSS class for the gift cards table rows
		 *
		 * @param array  $classes
		 * @param string $class
		 * @param int    $post_id
		 *
		 * @return array|mixed|void
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function add_cpt_table_class( $classes, $class, $post_id ) {

			if ( YWGC_CUSTOM_POST_TYPE_NAME != get_post_type( $post_id ) ) {
				return $classes;
			}

			$args      = array( 'ID' => $post_id );
			$gift_card = new YWGC_Gift_Card_Premium( $args );

			if ( ! $gift_card->exists() ) {
				return $class;
			}

			$classes[] = $gift_card->status;

			return apply_filters( 'yith_gift_cards_table_class', $classes, $post_id );
		}

		/**
		 * send the gift card code email
		 *
		 * @param int $gift_card_id the gift card id
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function send_gift_card_email( $gift_card_id ) {

			$args      = array( 'ID' => $gift_card_id );
			$gift_card = new YWGC_Gift_Card_Premium( $args );

			if ( ! $gift_card->exists() ) {
				//  it isn't a gift card
				return;
			}

			if ( ! $gift_card->is_virtual() || empty( $gift_card->recipient ) ) {
				// not a digital gift card or missing recipient
				return;
			}

			//include( 'emails/class.ywgc-email-send-gift-card.php' );
			WC()->mailer();
			do_action( 'ywgc-email-send-gift-card_notification', $gift_card );
		}

		/**
		 * Manage the request from an email for a gift card code to be applied to the cart
		 *
		 */
		public function manage_gift_card_email() {

			if ( isset( $_GET[ YWGC_ACTION_ADD_DISCOUNT_TO_CART ] ) &&
			     isset( $_GET[ YWGC_ACTION_VERIFY_CODE ] )
			) {
				$gift_card = $this->main->get_gift_card_by_code( $_GET[ YWGC_ACTION_ADD_DISCOUNT_TO_CART ] );

				if ( $gift_card->exists() && $gift_card->is_enabled() ) {

					//  Check the hash value
					$hash_value = $this->main->hash_gift_card( $gift_card );

					if ( $hash_value == $_GET[ YWGC_ACTION_VERIFY_CODE ] ) {
						//  can add the discount to the cart

						WC()->cart->add_discount( $gift_card->get_code() );
					}
				}
			}
		}

		/**
		 * Make some redirect based on the current action being performed
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function redirect_gift_cards_link() {

			/**
			 * Check if a gift card discount should be added to the cart
			 */
			if ( isset( $_GET[ YWGC_ACTION_ADD_DISCOUNT_TO_CART ] ) &&
			     isset( $_GET[ YWGC_ACTION_VERIFY_CODE ] )
			) {
				//  if not currently applied, add the discount to the cart
				if ( ! isset( $_GET['apply_discount_code'] ) ) {

					wp_redirect( add_query_arg(
						array(
							YWGC_ACTION_ADD_DISCOUNT_TO_CART => $_GET[ YWGC_ACTION_ADD_DISCOUNT_TO_CART ],
							YWGC_ACTION_VERIFY_CODE          => $_GET[ YWGC_ACTION_VERIFY_CODE ],
							'apply_discount_code'            => 1,
						),
						wc_get_cart_url() ) );
					exit;
				}
			}

			/**
			 * Check if the user ask for retrying sending the gift card email that are not shipped yet
			 */
			if ( isset( $_GET[ YWGC_ACTION_RETRY_SENDING ] ) ) {
				$post_id = $_GET['id'];
				$this->send_gift_card_email( $post_id );


				wp_redirect( remove_query_arg( array( YWGC_ACTION_RETRY_SENDING, 'id' ) ) );
				exit;
			}

			/**
			 * Check if the user ask for enabling/disabling a specific gift cards
			 */
			if ( isset( $_GET[ YWGC_ACTION_ENABLE_CARD ] ) || isset( $_GET[ YWGC_ACTION_DISABLE_CARD ] ) ) {
				$gift_card_id = $_GET['id'];
				$enabled      = isset( $_GET[ YWGC_ACTION_ENABLE_CARD ] );

				$args      = array( 'ID' => $gift_card_id );
				$gift_card = new YWGC_Gift_Card_Premium( $args );

				if ( ! $gift_card->is_dismissed() ) {

					$current_status = $gift_card->is_enabled();

					if ( $current_status != $enabled ) {

						$gift_card->set_enabled_status( $enabled );
						do_action( 'yith_gift_cards_status_changed', $gift_card, $enabled );
					}

					wp_redirect( remove_query_arg( array( YWGC_ACTION_ENABLE_CARD, YWGC_ACTION_DISABLE_CARD, 'id' ) ) );
					die();
				}
			}


			if ( ! isset( $_GET["post_type"] ) || ! isset( $_GET["s"] ) ) {
				return;
			}

			if ( 'shop_coupon' != ( $_GET["post_type"] ) ) {
				return;
			}

			if ( preg_match( "/(\w{4}-\w{4}-\w{4}-\w{4})(.*)/i", $_GET["s"], $matches ) ) {
				wp_redirect( admin_url( 'edit.php?s=' . $matches[1] . '&post_type=gift_card' ) );
				die();
			}
		}

		public function show_tax_class_for_gift_cards() {
			echo '<script>
                jQuery("select#_tax_status").closest("div.options_group").addClass("show_if_gift-card");
            </script>';
		}

		/**
		 * Locate the plugin email templates
		 *
		 * @param $core_file
		 * @param $template
		 * @param $template_base
		 *
		 * @return string
		 */
		public function locate_core_template( $core_file, $template, $template_base ) {
			$custom_template = array(
				'emails/send-gift-card.php',
				'emails/plain/send-gift-card.php',
				'emails/notify-customer.php',
				'emails/plain/notify-customer.php',
			);

			if ( in_array( $template, $custom_template ) ) {
				$core_file = YITH_YWGC_TEMPLATES_DIR . $template;
			}

			return $core_file;
		}

		/**
		 * Add an email action for sending the digital gift card
		 *
		 * @param array $actions list of current actions
		 *
		 * @return array
		 */
		function add_gift_cards_trigger_action( $actions ) {
			//  Add trigger action for sending digital gift card
			$actions[] = 'ywgc-email-send-gift-card';
			$actions[] = 'ywgc-email-notify-customer';

			return $actions;
		}

		/**
		 * Add the email used to send digital gift card to woocommerce email tab
		 *
		 * @param string $email_classes current email classes
		 *
		 * @return mixed
		 */
		public function add_woocommerce_email_classes( $email_classes ) {
			// add the email class to the list of email classes that WooCommerce loads
			$email_classes['ywgc-email-send-gift-card']  = include( 'emails/class.ywgc-email-send-gift-card.php' );
			$email_classes['ywgc-email-notify-customer'] = include( 'emails/class.ywgc-email-notify-customer.php' );

			return $email_classes;
		}

		/**
		 * Show the gift card code under the order item, in the order admin page
		 *
		 * @param int        $item_id
		 * @param array      $item
		 * @param WC_product $_product
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function woocommerce_before_order_itemmeta( $item_id, $item, $_product ) {

			global $theorder;
			$gift_ids = ywgc_get_order_item_giftcards( $item_id );


			if ( empty( $gift_ids ) ) {
				return;
			}

			foreach ( $gift_ids as $gift_id ) {
				$args = array( 'ID' => $gift_id );
				$gc   = new YWGC_Gift_Card_Premium( $args );

				if ( ! $gc->is_pre_printed() ):
					?>
					<div>
					<span
						class="ywgc-gift-code-label"><?php _e( "Gift card code: ", 'yith-woocommerce-gift-cards' ); ?></span>

						<a href="<?php echo admin_url( 'edit.php?s=' . $gc->get_code() . '&post_type=gift_card&mode=list' ); ?>"
						   class="ywgc-card-code"><?php echo $gc->get_code(); ?></a>
					</div>
				<?php elseif ( apply_filters( 'yith_ywgc_enter_pre_printed_gift_card_code', true, $theorder, $_product ) ): ?>
					<div>
					<span
						class="ywgc-gift-code-label"><?php _e( "Enter the pre-printed code: ", 'yith-woocommerce-gift-cards' ); ?></span>
						<input type="text" name="ywgc-pre-printed-code[<?php echo $gc->ID; ?>]"
						       class="ywgc-pre-printed-code">
					</div>
				<?php endif;
			}
		}


		/**
		 * Add custom columns to custom post type table
		 *
		 * @param array $defaults current columns
		 *
		 * @return array new columns
		 */
		function add_custom_columns_title( $defaults ) {
			$columns = array_slice( $defaults, 0, 2 );

			$columns[ YWGC_TABLE_COLUMN_ORDER ]             = __( "Order", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_AMOUNT ]            = __( "Amount", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_BALANCE ]           = __( "Balance", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_DEST_ORDERS ]       = __( "Orders", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_DEST_ORDERS_TOTAL ] = __( "Order total", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_INFORMATION ]       = __( "Information", 'yith-woocommerce-gift-cards' );
			$columns[ YWGC_TABLE_COLUMN_ACTIONS ]           = '';

			return array_merge( $columns, array_slice( $defaults, 1 ) );
		}

		/**
		 * @param WC_Order|int $order
		 *
		 * @return int
		 */
		private
		function get_order_number_and_details(
			$order
		) {

			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order instanceof WC_Order ) {
				return '';
			}
			$order_id = $order->id;

			if ( $order->user_id ) {
				$user_info = get_userdata( $order->user_id );
			}

			if ( ! empty( $user_info ) ) {
				$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

				if ( $user_info->first_name || $user_info->last_name ) {
					$username .= esc_html( ucfirst( $user_info->first_name ) . ' ' . ucfirst( $user_info->last_name ) );
				} else {
					$username .= esc_html( ucfirst( $user_info->display_name ) );
				}

				$username .= '</a>';

			} else {

				if ( $order->billing_first_name || $order->billing_last_name ) {
					$username = trim( $order->billing_first_name . ' ' . $order->billing_last_name );
				} else {
					$username = __( 'Guest', 'yith-woocommerce-gift-cards' );
				}
			}

			return sprintf( _x( '%s by %s', 'Order number by X', 'yith-woocommerce-gift-cards' ),
				'<a href="' . admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' ) . '" class="row-title"><strong>#' .
				esc_attr( $order->get_order_number() ) . '</strong></a>',
				$username );
		}


		/**
		 * show content for custom columns
		 *
		 * @param $column_name string column shown
		 * @param $post_ID     int     post to use
		 */
		function add_custom_columns_content( $column_name, $post_ID ) {

			$args              = array( 'ID' => $post_ID );
			$gift_card         = new YWGC_Gift_Card_Premium( $args );

			if ( ! $gift_card->exists() ) {
				return;
			}

			switch ( $column_name ) {
				case YWGC_TABLE_COLUMN_ORDER :
					$order_id = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_ORDER_ID, true );
					echo $this->get_order_number_and_details( $order_id );

					break;

				case YWGC_TABLE_COLUMN_AMOUNT :
					$_amount = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_AMOUNT, true );
					$_amount = empty( $_amount ) ? 0.00 : $_amount;

					$_amount_tax = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_AMOUNT_TAX, true );
					$_amount_tax = empty( $_amount_tax ) ? 0.00 : $_amount_tax;

					echo wc_price( $_amount + $_amount_tax );
					break;

				case YWGC_TABLE_COLUMN_BALANCE:
					$_amount = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE, true );
					$_amount = empty( $_amount ) ? 0.00 : $_amount;

					$_amount_tax = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX, true );
					$_amount_tax = empty( $_amount_tax ) ? 0.00 : $_amount_tax;

					echo wc_price( $_amount + $_amount_tax );
					break;

				case YWGC_TABLE_COLUMN_DEST_ORDERS:
					$orders = $gift_card->get_registered_orders();
					if ( $orders ) {
						foreach ( $orders as $order_id ) {
							echo $this->get_order_number_and_details( $order_id );
							echo "<br>";
						}
					} else {
						_e( "The code has not been used yet", 'yith-woocommerce-gift-cards' );
					}

					break;

				case YWGC_TABLE_COLUMN_INFORMATION:
					$content = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_USER_DATA, true );
					$recipient = isset( $content["recipient"] ) ? $content["recipient"] : '';

					if ( empty( $recipient ) ) {
						?>
						<div>
							<span><?php echo __( "Physical product", 'yith-woocommerce-gift-cards' ); ?></span>
						</div>
						<?php
					} else {

						$delivery_date = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_DELIVERY_DATE, true );
						$email_date    = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_SENT, true );

						if ( $email_date ) {
							$status_class = "sent";
							$message      = sprintf( __( "Sent on %s", 'yith-woocommerce-gift-cards' ), $email_date );
						} else if ( $delivery_date >= current_time( 'Y-m-d' ) ) {
							$status_class = "scheduled";
							$message      = __( "Scheduled", 'yith-woocommerce-gift-cards' );
						} else {
							$status_class = "failed";
							$message      = __( "Failed", 'yith-woocommerce-gift-cards' );
						}
						?>

						<div>
							<span><?php echo sprintf( __( "Recipient: %s", 'yith-woocommerce-gift-cards' ), $recipient ); ?></span>
						</div>
						<div>
							<span><?php echo sprintf( __( "Delivery date: %s", 'yith-woocommerce-gift-cards' ), $delivery_date ); ?></span>
							<br>
                            <span
	                            class="ywgc-delivery-status <?php echo $status_class; ?>"><?php echo $message; ?></span>

						</div>
						<?php
					}

					break;

				case YWGC_TABLE_COLUMN_DEST_ORDERS_TOTAL:

					$orders = $gift_card->get_registered_orders();
					$total  = 0.00;

					if ( $orders ) {
						foreach ( $orders as $order_id ) {

							$the_order = wc_get_order( $order_id );
							if ( $the_order ) {
								//  From version 1.2.10, show the order totals instead of subtotals
								//  $order_total = floatval(preg_replace('#[^\d.]#', '', $the_order->get_subtotal_to_display()));
								$total += $the_order->order_total;
							}
						}
					}
					echo wc_price( $total );

					$_amount = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_AMOUNT, true );
					$_amount = empty( $_amount ) ? 0.00 : $_amount;

					if ( $_amount && ( $total > $_amount ) ) {
						$percent = (float) ( $total - $_amount ) / $_amount * 100;
						echo '<br><span class="ywgc-percent">' . sprintf( __( '(+ %.2f%%)', 'yith-woocommerce-gift-cards' ), $percent ) . '</span>';
					}

					break;

				case YWGC_TABLE_COLUMN_ACTIONS:

					$status_class = "";
					$message   = "";
					$action    = '';

					//  Print some action button based on the gift card status, if the gift card is not dismissed
					if ( $gift_card->can_be_enabled() ) {
						$status_class = "gift-cards disabled";
						$message      = __( "Enable", 'yith-woocommerce-gift-cards' );
						$action       = YWGC_ACTION_ENABLE_CARD;
					} elseif ( $gift_card->can_be_disabled() ) {
						$status_class = "gift-cards enabled";
						$message      = __( "Disable", 'yith-woocommerce-gift-cards' );
						$action       = YWGC_ACTION_DISABLE_CARD;
					}

					if ( $action ) {
						echo sprintf( '<a class="ywgc-actions %s" href="%s" title="%s">%s</a>',
							$status_class,
							esc_url( add_query_arg( array( $action => 1, 'id' => $post_ID ) ) ),
							$message,
							$message );
					}

					if ( $gift_card->is_dismissed() ) {
						?>
						<span
							class="ywgc-dismissed-text"><?php _e( "This card is dismissed", 'yith-woocommerce-gift-cards' ); ?></span>
						<?php

					} else {
						$content   = get_post_meta( $post_ID, YWGC_META_GIFT_CARD_USER_DATA, true );
						$recipient = isset( $content["recipient"] ) ? $content["recipient"] : '';
						if ( ! empty( $recipient ) ) {

							$send_now_link = sprintf( '<a class="ywgc-actions %s" href="%s" title="%s">%s</a>',
								'gift-cards send-now',
								esc_url_raw( add_query_arg( array(
									YWGC_ACTION_RETRY_SENDING => 1,
									'id'                      => $post_ID
								) ) ),
								__( "Send now", 'yith-woocommerce-gift-cards' ),
								__( "Send now", 'yith-woocommerce-gift-cards' ) );

							echo $send_now_link;
						}
					}

					break;
			}
		}

		/**
		 * Send the digital gift cards that should be received on specific date.
		 *
		 * @param string $send_date
		 */
		public function send_delayed_gift_cards( $send_date = null ) {
			if ( ! class_exists( "YWGC_Email_Send_Gift_Card" ) ) {
				include( 'emails/class.ywgc-email-send-gift-card.php' );
			}

			if ( null == $send_date ) {
				$send_date = current_time( 'Y-m-d', 0 );
			}

			// retrieve gift card to be sent for specific date
			$gift_cards_ids = $this->main->get_postdated_gift_cards( $send_date );

			foreach ( $gift_cards_ids as $gift_card_id ) {
				// send digital single gift card to recipient
				$args      = array( 'ID' => $gift_card_id );
				$gift_card = new YWGC_Gift_Card_Premium( $args );

				if ( ! $gift_card->exists() ) {
					continue;
				}

				if ( ! $gift_card->is_virtual() || empty( $gift_card->recipient ) ) {
					// not a digital gift card or missing recipient
					continue;
				}

				if ( $gift_card->has_been_sent() ) {
					//  avoid sending emails more than one time
					continue;
				}

				do_action( 'ywgc-email-send-gift-card_notification', $gift_card );
			}
		}

		/**
		 * Start the scheduling that let gift cards to be sent on expected date
		 */
		public static function start_gift_cards_scheduling() {
			wp_schedule_event( time(), 'daily', 'ywgc_start_gift_cards_sending' );
		}

		/**
		 * Stop the scheduling that let gift cards to be sent on expected date
		 */
		public static function end_gift_cards_scheduling() {
			wp_clear_scheduled_hook( 'ywgc_start_gift_cards_sending' );
		}
	}
}