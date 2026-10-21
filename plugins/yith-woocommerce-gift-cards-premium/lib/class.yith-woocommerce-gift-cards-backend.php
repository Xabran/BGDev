<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'YITH_WooCommerce_Gift_Cards_Backend' ) ) {

	/**
	 *
	 * @class   YITH_WooCommerce_Gift_Cards_Backend
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YITH_WooCommerce_Gift_Cards_Backend {
		/** @var YITH_WooCommerce_Gift_Cards|YITH_WooCommerce_Gift_Cards_Premium main */
		public $main;

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

			/**
			 * Remove unwanted WordPress submenu item
			 */
			add_action( 'admin_menu', array( $this, 'remove_unwanted_custom_post_type_features' ), 5 );

			/**
			 * show a bubble with the number of new gift cards from the last visit
			 */
			add_action( 'admin_menu', array( $this, 'show_number_of_new_gift_cards' ), 99 );

			/**
			 * Enqueue scripts and styles
			 */
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_files' ) );

			/**
			 * Add the "Gift card" type to product type list
			 */
			add_filter( 'product_type_selector', array( $this, 'add_gift_card_product_type' ) );

			/**
			 * * Save gift card data when a product of type "gift card" is saved
			 */
			add_action( 'save_post', array( $this, 'save_gift_card' ), 1, 2 );

			/**
			 * * Save gift card data when a product of type "gift card" is saved
			 */
			add_action( 'save_post', array( $this, 'save_pre_printed_gift_card_code' ), 1, 2 );

			/**
			 * Ajax call for adding and removing gift card amounts on product edit page
			 */
			add_action( 'wp_ajax_add_gift_card_amount', array( $this, 'add_gift_card_amount_callback' ) );
			add_action( 'wp_ajax_remove_gift_card_amount', array( $this, 'remove_gift_card_amount_callback' ) );

			/**
			 * Hide some item meta from product edit page
			 */
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_item_meta' ) );


			if ( version_compare( WC()->version, '2.6.0', '<' ) ) {

				/**
				 * Append gift card amount generation controls to general tab of product page, below the SKU element
				 */
				add_action( 'woocommerce_product_options_sku', array( $this, 'show_gift_card_product_settings' ) );

			} else {
				/**
				 * Append gift card amount generation controls to general tab on product page
				 */
				add_action( 'woocommerce_product_options_general_product_data', array(
					$this,
					'show_gift_card_product_settings'
				) );
			}
			/**
			 * Generate a valid card number for every gift card product in the order
			 */
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );

			/**
			 * Check if a gift card discount code was used and deduct the amount from the gift card.
			 */
			add_action( 'woocommerce_order_add_coupon', array( $this, 'deduct_amount_from_gift_card' ), 10, 5 );
		}

		/**
		 * show a bubble with the number of new gift cards from the last visit
		 */
		public function show_number_of_new_gift_cards() {
			global $menu;
			foreach ( $menu as $key => $value ) {
				if ( isset( $value[5] ) && ( $value[5] == 'menu-posts-' . YWGC_CUSTOM_POST_TYPE_NAME ) ) {
					//  Add a bubble with the new gift card created since the last time
					$last_viewed = get_option( YWGC_GIFT_CARD_LAST_VIEWED_ID, 0 );
					global $wpdb;
					$new_ids = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}posts WHERE post_type = %s and ID > %d", YWGC_CUSTOM_POST_TYPE_NAME, $last_viewed ) );
					$bubble  = "<span class='awaiting-mod count-{$new_ids}'><span class='pending-count'>{$new_ids}</span></span>";
					$menu[ $key ][0] .= $bubble;

					return;
				}
			}
		}

		/*
		 * Remove features for the review custom post type
		 */
		public function remove_unwanted_custom_post_type_features() {
			global $submenu;

			if ( isset( $submenu[ "edit.php?post_type=" . YWGC_CUSTOM_POST_TYPE_NAME ] ) ) {
				$gift_card_menu = $submenu[ 'edit.php?post_type=' . YWGC_CUSTOM_POST_TYPE_NAME ];

				foreach ( $gift_card_menu as $key => $value ) {
					if ( $value[2] == 'post-new.php?post_type=' . YWGC_CUSTOM_POST_TYPE_NAME ) {
						//  it's the add-new submenu item, we want to remove it
						unset( $submenu[ "edit.php?post_type=" . YWGC_CUSTOM_POST_TYPE_NAME ][ $key ] );
						break;
					}
				}
			}
		}

		/**
		 * Check if a gift card discount code was used and deduct the amount from the gift card.
		 *
		 * @param int    $order_id
		 * @param int    $item_id
		 * @param string $code
		 * @param float  $discount_amount
		 * @param float  $discount_amount_tax
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function deduct_amount_from_gift_card( $order_id, $item_id, $code, $discount_amount, $discount_amount_tax = 0.00 ) {

			$discount_amount     = apply_filters( 'yith_ywgc_set_gift_card_coupon_amount_before_deduct', $discount_amount );
			$discount_amount_tax = apply_filters( 'yith_ywgc_set_gift_card_coupon_amount_tax_before_deduct', $discount_amount_tax );

			$gift = $this->main->get_gift_card_by_code( $code );

			if ( $gift->exists() ) {
				$gift->deduct_amount( $discount_amount, $discount_amount_tax );
				$gift->register_order( $order_id );
			}
		}

		/**
		 * Enqueue scripts on administration comment page
		 *
		 * @param $hook
		 */
		function enqueue_backend_files( $hook ) {
			global $post_type, $wp_version;

			$screen = get_current_screen();
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			//  Enqueue style and script for the edit-gift_card screen id
			if ( "edit-gift_card" == $screen->id ) {

				//  When viewing the gift card page, store the max id so all new gift cards will be notified next time
				global $wpdb;
				$last_id = $wpdb->get_var( $wpdb->prepare( "SELECT max(id) FROM {$wpdb->prefix}posts WHERE post_type = %s", YWGC_CUSTOM_POST_TYPE_NAME ) );
				update_option( YWGC_GIFT_CARD_LAST_VIEWED_ID, $last_id );
			}

			if ( ( 'product' == $post_type ) || ( 'gift_card' == $post_type ) ) {

				//  Add style and scripts
				wp_enqueue_style( 'ywgc-backend-css',
					YITH_YWGC_ASSETS_URL . '/css/ywgc-backend.css',
					array(),
					YITH_YWGC_VERSION );

				wp_register_script( "ywgc-backend",

					YITH_YWGC_SCRIPT_URL . yit_load_js_file( 'ywgc-backend.js' ),
					array(
						'jquery',
						'jquery-blockui',
					),
					YITH_YWGC_VERSION,
					true );

				wp_localize_script( 'ywgc-backend',
					'ywgc_data', array(
						'loader'            => apply_filters( 'yith_gift_cards_loader', YITH_YWGC_ASSETS_URL . '/images/loading.gif' ),
						'ajax_url'          => admin_url( 'admin-ajax.php' ),
						'choose_image_text' => __( 'Choose Image', 'yith-woocommerce-gift-cards' ),
					)
				);

				wp_enqueue_script( "ywgc-backend" );

				//  Hide some unused elements on the screen that should not be shown
				if ( YWGC_CUSTOM_POST_TYPE_NAME == $post_type ) {

					if ( version_compare( $wp_version, '4.3-RC1', '>=' ) ) {
						$rule = ".post-type-{$post_type} .wrap .page-title-action{ display: none; }";
					} else {
						$rule = ".post-type-{$post_type} .add-new-h2{ display: none; }";
					}
					wp_add_inline_style( 'ywgc-backend-css', $rule );
				}
			}

			if ( "upload" == $screen->id ) {

				wp_register_script( "ywgc-categories",
					YITH_YWGC_SCRIPT_URL . yit_load_js_file( 'ywgc-categories.js' ),
					array(
						'jquery',
						'jquery-blockui',
					),
					YITH_YWGC_VERSION,
					true );

				$categories1_id = 'categories1_id';
				$categories2_id = 'categories2_id';

				wp_localize_script( 'ywgc-categories', 'ywgc_data', array(
					'loader'                => apply_filters( 'yith_gift_cards_loader', YITH_YWGC_ASSETS_URL . '/images/loading.gif' ),
					'ajax_url'              => admin_url( 'admin-ajax.php' ),
					'set_category_action'   => __( "Set gift card category", 'yith-woocommerce-gift-cards' ),
					'unset_category_action' => __( "Unset gift card category", 'yith-woocommerce-gift-cards' ),
					'categories1'           => $this->get_category_select( $categories1_id ),
					'categories1_id'        => $categories1_id,
					'categories2'           => $this->get_category_select( $categories2_id ),
					'categories2_id'        => $categories2_id,
				) );

				wp_enqueue_script( "ywgc-categories" );
			}
		}

		public function get_category_select( $select_id ) {
			$media_terms = get_terms( YWGC_CATEGORY_TAXONOMY, 'hide_empty=0' );

			$select = '<select id="' . $select_id . '" name="' . $select_id . '">';
			foreach ( $media_terms as $entry ) {
				$select .= '<option value="' . $entry->term_id . '">' . $entry->name . '</option>';
			}
			$select .= '</select>';

			return $select;

		}

		/**
		 * Add the "Gift card" type to product type list
		 *
		 * @param array $types current type array
		 *
		 * @return mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function add_gift_card_product_type( $types ) {
			if ( ywgc_can_create_gift_card() ) {
				$types[ YWGC_GIFT_CARD_PRODUCT_TYPE ] = __( "Gift card", 'yith-woocommerce-gift-cards' );
			}

			return $types;
		}

		public function save_gift_card_data( $product_id ) {


			/**
			 * Save custom gift card header image, if exists
			 */
			if ( isset( $_REQUEST['ywgc_product_image_id'] ) ) {
				if ( intval( $_REQUEST['ywgc_product_image_id'] ) ) {

					$this->set_header_image( $product_id, $_REQUEST['ywgc_product_image_id'] );
				} else {

					$this->unset_header_image( $product_id );
				}
			}

			/**
			 * Save gift card amounts
			 */
			$amounts = isset( $_POST["gift-card-amounts"] ) ? $_POST["gift-card-amounts"] : array();
			$this->save_gift_card_amounts( $product_id, $amounts );

			/**
			 * Save gift card settings about template design
			 */
			if ( isset( $_POST['template-design-mode'] ) ) {
				$this->main->set_template_design_allowed_status( $product_id, $_POST['template-design-mode'] );
			}
		}

		public function save_gift_card_amounts( $product_id, $amounts = array() ) {
			update_post_meta( $product_id, YWGC_AMOUNTS, $amounts );
		}


		/**
		 * Set the header image for a gift card product
		 *
		 * @param $product_id
		 * @param $attachment_id
		 */
		public function set_header_image( $product_id, $attachment_id ) {

			update_post_meta( $product_id, YWGC_PRODUCT_IMAGE, $attachment_id );
		}

		/**
		 * Unset the header image for a gift card product
		 *
		 * @param $product_id
		 */
		public function unset_header_image( $product_id ) {

			delete_post_meta( $product_id, YWGC_PRODUCT_IMAGE );
		}

		/**
		 * Retrieve the custom image set from the edit product page for a specific gift card product
		 *
		 * @param $product_id
		 *
		 * @return mixed
		 */
		public function get_manual_header_image( $product_id ) {
			return get_post_meta( $product_id, YWGC_PRODUCT_IMAGE, true );
		}

		/**
		 * Check if there are pre-printed gift cards that were filled and need to be updated
		 *
		 * @param $post_id
		 * @param $post
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function save_pre_printed_gift_card_code( $post_id, $post ) {

			if ( 'shop_order' != $post->post_type ) {
				return;
			}

			if ( ! isset( $_POST["ywgc-pre-printed-code"] ) ) {
				return;
			}

			$codes = $_POST["ywgc-pre-printed-code"];

			foreach ( $codes as $gift_id => $gift_code ) {
				if ( ! empty( $gift_code ) ) {
					$args                 = array( 'ID' => $gift_id );
					$gc                   = new YWGC_Gift_Card_Premium( $args );
					$gc->gift_card_number = $gift_code;
					$gc->set_enabled_status( true );
					$gc->save();
				}
			}
		}


		/**
		 * Save gift card amount when a product is saved
		 *
		 * @param $post_id int
		 * @param $post    object
		 *
		 * @return mixed
		 */
		function save_gift_card( $post_id, $post ) {

			$product = wc_get_product( $post_id );

			if ( null == $product ) {
				return;
			}

			if ( ! isset( $_POST["product-type"] ) || ( YWGC_GIFT_CARD_PRODUCT_TYPE != $_POST["product-type"] ) ) {

				return;
			}

			// verify this is not an auto save routine.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			/**
			 * Update gift card amounts
			 */
			$this->save_gift_card_data( $post_id );


			do_action( 'yith_gift_cards_after_product_save', $post_id, $post, $product );
		}

		private function get_gift_card_product_amounts( $product_id ) {
			$metas = get_post_meta( $product_id, YWGC_AMOUNTS, true );

			return is_array( $metas ) ? $metas : array();
		}

		/**
		 * Add a new amount to the available gift card amounts
		 *
		 * @param $product_id int   the gift card product id
		 * @param $amount     int       the amount to add
		 *
		 * @return bool amount added, false if the same value still exists
		 */
		public function add_amount_to_gift_card( $product_id, $amount ) {
			$amounts = $this->get_gift_card_product_amounts( $product_id );

			if ( ! in_array( $amount, $amounts ) ) {

				$amounts[] = $amount;
				sort( $amounts, SORT_NUMERIC );
				$this->save_gift_card_amounts( $product_id, $amounts );

				return true;
			}

			return false;
		}

		/**
		 * Remove an amount to a gift card
		 *
		 * @param $product_id int   the gift card product id
		 * @param $amount     int       the amount to remove
		 *
		 * @return bool amount added, false if the same value still exists
		 */
		public function remove_amount_to_gift_card( $product_id, $amount ) {
			$amounts = $this->get_gift_card_product_amounts( $product_id );

			if ( in_array( $amount, $amounts ) ) {
				if ( ( $key = array_search( $amount, $amounts ) ) !== false ) {
					unset( $amounts[ $key ] );
				}


				$this->save_gift_card_amounts( $product_id, $amounts );

				return true;
			}

			return false;
		}

		/**
		 * Add a new amount to a gift card prdduct
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function add_gift_card_amount_callback() {
			$amount = number_format( $_POST['amount'], 2, wc_get_price_decimal_separator(), '' );

			$product_id = intval( $_POST['product_id'] );

			$res = $this->add_amount_to_gift_card( $product_id, $amount );

			wp_send_json( array( "code" => $res, "value" => $this->gift_card_amount_list_html( $product_id ) ) );
		}

		/**
		 * Retrieve the html content that shows the gift card amounts list
		 *
		 * @param $product_id int gift card product id
		 *
		 * @return string
		 */
		private function gift_card_amount_list_html( $product_id ) {
			ob_start();
			$this->show_gift_card_amount_list( $product_id );
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}

		/**
		 * Remove amount to a gift card prdduct
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function remove_gift_card_amount_callback() {
			$amount     = number_format( $_POST['amount'], 2, wc_get_price_decimal_separator(), '' );
			$product_id = intval( $_POST['product_id'] );

			$res = $this->remove_amount_to_gift_card( $product_id, $amount );

			wp_send_json( array( "code" => $res ) );
		}

		/**
		 * Hide some item meta from order edit page
		 */
		public function hide_item_meta( $args ) {
			$args[] = YWGC_META_GIFT_CARD_POST_ID;

			return $args;
		}

		/**
		 * Show checkbox enabling the product to avoid use of free amount
		 */
		public function show_manual_amount_settings( $product_id ) {
			$manual_mode    = $this->main->get_manual_amount_allowed_status( $product_id );
			$global_checked = ( $manual_mode == "global" ) || ( ( $manual_mode != "accept" ) && ( $manual_mode != "reject" ) );
			?>

			<p class="form-field permit_free_amount">
				<label><?php _e( "Variable amount mode", 'yith-woocommerce-gift-cards' ); ?></label>
                <span class="wrap">
                    <input type="radio" class="ywgc-manual-amount-mode global-manual-mode" name="manual_amount_mode"
                           value="global" <?php checked( $global_checked, true ); ?>>
                    <span><?php _e( "Default", 'yith-woocommerce-gift-cards' ); ?></span>
                    <input type="radio" class="ywgc-manual-amount-mode accept-manual-mode" name="manual_amount_mode"
                           value="accept" <?php checked( $manual_mode, "accept" ); ?>>
                    <span><?php _e( "Enabled", 'yith-woocommerce-gift-cards' ); ?></span>
                    <input type="radio" class="ywgc-manual-amount-mode deny-manual-mode" name="manual_amount_mode"
                           value="reject" <?php checked( $manual_mode, "reject" ); ?>>
                    <span><?php _e( "Disabled", 'yith-woocommerce-gift-cards' ); ?></span>
                </span>
			</p>

			<?php
		}

		/**
		 * Show the settings to let the admin choose if for the product is available the custom design
		 *
		 * @param int $product_id
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_template_design_settings( $product_id ) {
			$allow_template = $this->main->get_template_design_status( $product_id );
			$global_checked = ( $allow_template == "global" ) || ( ( $allow_template != "enabled" ) && ( $allow_template != "disabled" ) );
			?>

			<p class="form-field permit_template_design show_if_virtual">
				<label><?php _e( "Show template design", 'yith-woocommerce-gift-cards' ); ?></label>
                <span class="wrap">
                    <input type="radio" class="ywgc-template-design-mode" name="template-design-mode"
                           value="global" <?php checked( $global_checked, true ); ?>>
                    <span><?php _e( "Default", 'yith-woocommerce-gift-cards' ); ?></span>
                    <input type="radio" class="ywgc-template-design-mode" name="template-design-mode"
                           value="enabled" <?php checked( $allow_template, "enabled" ); ?>>
                    <span><?php _e( "Enabled", 'yith-woocommerce-gift-cards' ); ?></span>
                    <input type="radio" class="ywgc-template-design-mode" name="template-design-mode"
                           value="disabled" <?php checked( $allow_template, "disabled" ); ?>>
                    <span><?php _e( "Disabled", 'yith-woocommerce-gift-cards' ); ?></span>
                </span>
			</p>

			<?php
		}

		/**
		 * Show checkbox enabling the product to avoid use of free amount
		 */
		public function show_custom_header_image_settings( $product_id ) {
			$image_id = $this->get_manual_header_image( $product_id );
			?>
			<p id="ywgc_header_image" class="form-field">
				<label><?php _e( "Gift card image", 'yith-woocommerce-gift-cards' ); ?></label>
                    <span id="ywgc-card-header-image" class="wrap">
                        <?php if ( $image_id ) {
	                        echo '<a target="_blank" href="' . wp_get_attachment_image_url( $image_id, "full" ) . '">';
	                        echo wp_get_attachment_image( $image_id, array( 80, 80 ) );
	                        echo '</a>';
                        } else {
	                        _e( 'No image selected, the featured image will be used', 'yith-woocommerce-gift-cards' );
                        }
                        ?>
	                    <input type="button"
	                           name="ywgc_product_image"
	                           value="<?php _e( 'Choose image', 'yith-woocommerce-gift-cards' ) ?>"
	                           class="image-gallery-chosen button" />

                        <input type="button"
                               name="ywgc_reset_product_image"
                               value="<?php _e( 'Reset image', 'yith-woocommerce-gift-cards' ) ?>"
                               class="image-gallery-reset button" />

                        <input type="hidden"
                               id="ywgc_product_image_id"
                               name="ywgc_product_image_id"
                               value="<?php echo esc_attr( $image_id ); ?>" />

	                    <?php echo wc_help_tip( 'Choose the image to be used as the gift card main image. Leave it blank if you want to use the featured image instead.' ); ?>
                    </span>
			</p>
			<?php
		}

		/**
		 * Show controls on backend product page to let create the gift card price
		 */
		public function show_gift_card_product_settings() {

			if ( ! ywgc_can_create_gift_card() ) {
				return;
			}

			global $post, $thepostid;
			?>
			<div class="options_group show_if_gift-card">
				<p class="form-field">
					<label><?php _e( "Gift card amount", 'yith-woocommerce-gift-cards' ); ?></label>
                <span class="wrap add-new-amount-section">
                    <input type="text" id="gift_card-amount" name="gift_card-amount" class="short" style=""
                           placeholder="">
                    <a href="#" class="add-new-amount"><?php _e( "Add", 'yith-woocommerce-gift-cards' ); ?></a>
                </span>
				</p>

				<?php
				$this->show_gift_card_amount_list( $thepostid );
				$this->show_manual_amount_settings( $thepostid );
				$this->show_custom_header_image_settings( $thepostid );
				$this->show_template_design_settings( $thepostid );
				?>
			</div>
			<?php
		}

		/**
		 * Show the gift card amounts list
		 *
		 * @param $product_id int gift card product id
		 */
		private function show_gift_card_amount_list( $product_id ) {
			$amounts = $this->get_gift_card_product_amounts( $product_id );
			apply_filters( 'yith_gift_cards_before_amount_list', $product_id, $amounts );
			?>

			<p class="form-field _gift_card_amount_field">
				<?php if ( $amounts ): ?>
					<?php foreach ( $amounts as $amount ) : ?>
						<span class="variation-amount"><?php echo wc_price( $amount ); ?>
							<input type="hidden" name="gift-card-amounts[]" value="<?php _e( $amount ); ?>">
                        <a href="#" class="remove-amount"></a></span>
					<?php endforeach; ?>
				<?php else: ?>
					<span
						class="no-amounts"><?php _e( "You don't have configured any gift card yet", 'yith-woocommerce-gift-cards' ); ?></span>
				<?php endif; ?>
			</p>
			<?php
		}

		/**
		 * Create the gift cards object for the order
		 *
		 * @param WC_Order $order
		 */
		public function create_gift_cards_for_order( $order ) {

			foreach ( $order->get_items( 'line_item' ) as $order_item_id => $order_item_data ) {

				$product_id = $order_item_data["product_id"];
				$product    = wc_get_product( $product_id );

				//  skip all item that belong to product other than the gift card type
				if ( ! $product instanceof WC_Product_Gift_Card ) {
					continue;
				}
				//  Check if current product, of type gift card, has a previous gift card
				// code before creating another
				if ( $gift_ids = ywgc_get_order_item_giftcards( $order_item_id ) ) {
					return;
				}
				if ( ! apply_filters( 'yith_ywgc_create_gift_card_for_order_item', true, $order, $order_item_id, $order_item_data ) ) {
					return;
				}
				$meta_order_item_data = wc_get_order_item_meta( $order_item_id, YWGC_ORDER_ITEM_DATA );

				$is_postdated  = false;
				$delivery_date = current_time( 'Y-m-d', 0 );

				//  Set a delivery date (today or the gift card delivery date)
				if ( ! empty( $meta_order_item_data["postdated_delivery"] ) ) {
					if ( ! empty ( $meta_order_item_data["delivery_date"] ) ) {
						$delivery_date = $meta_order_item_data["delivery_date"];
						$is_postdated  = true;
					}
				}

				$line_subtotal_key = apply_filters( 'yith_ywgc_line_subtotal_key', "line_subtotal" );
				$line_subtotal     = isset( $order_item_data[ $line_subtotal_key ] ) ? $order_item_data[ $line_subtotal_key ] : 0.00;

				$line_subtotal_tax_key = apply_filters( 'yith_ywgc_line_subtotal_tax_key', "line_subtotal_tax" );
				$line_subtotal_tax     = isset( $order_item_data[ $line_subtotal_tax_key ] ) ? $order_item_data[ $line_subtotal_tax_key ] : 0.00;

				//  Generate as many gift card code as the quantity bought
				$quantity = $order_item_data["qty"];
				$new_ids  = array();

				for ( $i = 0; $i < $quantity; $i ++ ) {

					$single_amount = (float) ( $line_subtotal / $quantity );
					$single_tax    = (float) ( $line_subtotal_tax / $quantity );

					//  Generate a gift card post type and save it
					$gift_card = new YWGC_Gift_Card_Premium();

					if ( $this->main->enable_pre_printed && ! $product->is_virtual() ) {
						$gift_card->set_as_pre_printed();
					}

					$gift_card->set_amount( $single_amount, $single_tax );
					$gift_card->product_id = $product_id;
					$gift_card->order_id   = $order->id;

					if ( $gift_card->is_pre_printed() ) {
						$gift_card->gift_card_number = YWGC_PHYSICAL_PLACEHOLDER;
					} else {
						$gift_card->gift_card_number = $this->main->generate_gift_card_code();
					}

					$gift_card->save();

					//  Save the gift card id
					$new_ids[] = $gift_card->ID;

					//  Attach gift card user contents from order item meta to post meta
					update_post_meta( $gift_card->ID, YWGC_META_GIFT_CARD_USER_DATA, $meta_order_item_data );

					//  set the delivery date...
					update_post_meta( $gift_card->ID, YWGC_META_GIFT_CARD_DELIVERY_DATE, $delivery_date );

					//  ...and send it now if it's not postdated
					if ( ! $is_postdated && $gift_card->is_virtual() ) {
						do_action( 'ywgc-email-send-gift-card_notification', $gift_card->ID );
					}
				}

				// save gift card Post ids on order item
				ywgc_set_order_item_giftcards( $order_item_id, $new_ids );
			}

		}

		/**
		 * Notify the customer if a gift cards he bought is used
		 *
		 * @param WC_Order $order
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function notify_customer_if_gift_cards_used( $order ) {
			//  Check if the customer notification is set...
			if ( $this->main->notify_customer ) {

				foreach ( $order->get_used_coupons() as $coupon_code ) {
					// if the code belong to a gift card, notify the customer

					$gift_card = $this->main->get_gift_card_by_code( $coupon_code );

					if ( $gift_card->exists() && $gift_card->is_virtual() ) {
						WC()->mailer();
						do_action( 'ywgc-email-notify-customer_notification', $gift_card->ID );
					}
				}
			}
		}

		/**
		 * When the order is completed, generate a card number for every gift card product
		 *
		 * @param int|WC_Order $order      The order which status is changing
		 * @param string       $old_status Current order status
		 * @param string       $new_status New order status
		 *
		 */
		public function order_status_changed( $order, $old_status, $new_status ) {

			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}


			if ( 'completed' == $new_status ) {
				$this->manage_order_completed( $order );
			} elseif ( 'refunded' == $new_status ) {
				$this->change_gift_cards_status_on_order( $order, $this->main->order_refunded_action );
			} elseif ( 'cancelled' == $new_status ) {
				$this->change_gift_cards_status_on_order( $order, $this->main->order_cancelled_action );
			}
		}

		/**
		 * The order is set to completed
		 *
		 * @param WC_Order $order
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function manage_order_completed( $order ) {
			if ( is_numeric( $order ) ) {
				$order = new WC_Order( $order );
			}

			if ( apply_filters( 'yith_gift_cards_generate_on_order_completed', true, $order ) ) {

				$this->create_gift_cards_for_order( $order );
				$this->notify_customer_if_gift_cards_used( $order );
			}
		}

		/**
		 * The order is set to completed
		 *
		 * @param WC_Order $order
		 * @param string   $action
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function change_gift_cards_status_on_order( $order, $action ) {

			if ( 'nothing' == $action ) {
				return;
			}

			foreach ( $order->get_items() as $item_id => $item ) {
				$ids = ywgc_get_order_item_giftcards( $item_id );

				if ( $ids ) {
					foreach ( $ids as $gift_id ) {
						$args      = array( 'ID' => $gift_id );
						$gift_card = new YWGC_Gift_Card_Premium( $args );

						if ( ! $gift_card->exists() ) {
							continue;
						}

						if ( 'dismiss' == $action ) {
							$gift_card->set_dismissed_status();
						} elseif ( 'disable' == $action ) {

							$gift_card->set_enabled_status( false );
						}
					}
				}
			}
		}
	}
}