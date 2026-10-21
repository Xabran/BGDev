<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'YITH_WooCommerce_Gift_Cards_Premium' ) ) {

	/**
	 *
	 * @class   YITH_WooCommerce_Gift_Cards_Premium
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YITH_WooCommerce_Gift_Cards_Premium extends YITH_WooCommerce_Gift_Cards {
		/**
		 * @var int The default product of type gift card
		 */
		public $default_gift_card = - 1;

		/**
		 * @var bool Let the user to enter manually the amount of the gift card
		 */
		public $allow_manual_amount = false;

		/**
		 * @var bool allow the customer to choose a product from the shop to be used as a present for the gift card
		 */
		public $allow_product_as_present = false;

		/**
		 * @var bool allow the customer to edit the content of a gift card
		 */
		public $allow_modification = false;

		/**
		 * @var bool let your customer to buy a digital card and send it later
		 */
		public $allow_send_later = false;

		/**
		 * @var bool notify the customer when a gift card he bought is used
		 */
		public $notify_customer = false;

		/**
		 * @var string the shop name
		 */
		public $shop_name;

		/**
		 * @var int limit the maximum size of custom image uploaded by the customer
		 */
		public $custom_image_max_size;

		/**
		 * @var string  the logo to be used on the gift card
		 */
		public $shop_logo_url;

		/**
		 * @var bool set it the shop logo should be shown inside the gift card template
		 */
		public $shop_logo_on_template = false;

		/**
		 * @var string the image url used as gift card header
		 */
		public $default_header_image_url;

		/**
		 * @var string the style to be used for the email
		 *
		 */
		public $template_style = 'style1';
		/**
		 * @var bool set if the admin should receive the email containing the gift card code in BCC
		 */
		public $blind_carbon_copy;

		/**
		 * @var bool enable the automatic discount when the customer click on the link in the email received
		 */
		public $automatic_discount = false;

		/**
		 * @var bool restrict the usage of the gift card to the recipient
		 */
		public $restricted_usage = false;

		/**
		 * @var bool allow to use a user picture as custom gift card design
		 */
		public $allow_custom_design = true;

		/**
		 * @var bool let the user to choose from some gift cards templates
		 */
		public $allow_template_design = false;

		/**
		 * @var bool
		 */
		public $allow_multiple_recipients = true;

		/**
		 * @var string action to perform on order cancelled
		 */
		public $order_cancelled_action = '';

		/**
		 * @var string action to perform on order refunded
		 */
		public $order_refunded_action = '';

		/**
		 * @var bool choose if the pre-printed mode is enabled for physical gift cards
		 */
		public $enable_pre_printed = false;

		/**
		 * @var bool activate this option for forcing the update of totals when the cart is retrieved from the session
		 */
		public $mini_cart_fix = false;

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

			if ( ! class_exists( 'Emogrifier' ) ) {

				$path = WC()->plugin_path() . '/includes/libraries/class-emogrifier.php';
				require_once( WC()->plugin_path() . '/includes/libraries/class-emogrifier.php' );
			}

			$this->register_custom_post_statuses();


			/*
			 * Save additional product attribute when a gift card product is saved
			 */
			add_action( 'yith_gift_cards_after_product_save', array( $this, 'save_gift_card_product' ), 10, 3 );

			/*
			 * Customize a gift card with data entered by the customer on product page
			 */
			add_filter( 'yith_gift_cards_before_add_to_cart', array( $this, 'customize_card_before_add_to_cart' ) );

			/**
			 * Permit gift card to be purchasable even without price
			 */
			add_filter( 'woocommerce_is_purchasable', array( $this, 'gift_card_is_purchasable' ), 10, 2 );

			/**
			 * Add an option to let the admin set the gift card as a physical good or digital goods
			 */
			add_filter( 'product_type_options', array( $this, 'add_type_option' ) );

			/**
			 * Add CSS style to gift card emails header
			 */
			add_action( 'woocommerce_email_header', array( $this, 'include_css_for_emails' ), 10, 2 );

			/**
			 * Add the customer product suggestion is there is one
			 */
			add_action( 'ywgc_gift_card_email_after_preview', array( $this, 'show_email_additional_data' ) );

			/**
			 * When the default gift card image is changed from the plugin setting, update the product image
			 * of the default gift card
			 */
			add_action( 'yit_panel_wc_after_update', array( $this, 'update_default_gift_card' ) );

			/**
			 * Add plugin compatibility with YITH WooCommerce Multi Vendor
			 */
			add_filter( 'ywgc_can_create_gift_card', array( $this, 'user_can_create_gift_cards' ) );

			/**
			 * Show an introductory text before the gift cards editor
			 */
			add_action( 'ywgc_gift_cards_email_before_preview', array( $this, 'show_introductory_text' ), 10, 2 );

			/**
			 * Append CSS for the email being sent to the customer
			 */
			add_action( 'yith_gift_cards_template_before_add_to_cart_form', array( $this, 'append_css_files' ) );

			/**
			 * Add information to the email footer
			 */
			add_action( 'woocommerce_email_footer', array( $this, 'add_footer_information' ) );

			/**
			 * Add the admin email as recipient in BCC for every gift card code sent
			 */
			add_filter( 'ywgc_gift_card_code_email_bcc', array( $this, 'add_admin_to_email_bcc' ) );

			add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'resend_gift_card_code' ) );

			/**
			 * YITH WooCommerce Dynamic Pricing and Discount Premium compatibility.
			 * Single product template. Manage the price exclusion when used with the YITH WooCommerce Dynamic Pricing
			 */
			add_filter( 'ywdpd_get_price_exclusion', array(
				$this,
				'exclude_price_for_yith_dynamic_discount_product_page'
			), 10, 3 );

			/**
			 * YITH WooCommerce Dynamic Pricing and Discount Premium compatibility.
			 * Cart template. Manage the price exclusion
			 */
			add_filter( 'ywdpd_replace_cart_item_price', array(
				$this,
				'set_price_for_yith_dynamic_discount_cart_page'
			), 10, 4 );

			/**
			 * YITH WooCommerce Dynamic Pricing and Discount Premium compatibility.
			 * Show the table with pricing discount
			 */
			add_filter( 'ywdpd_show_price_on_table_pricing', array( $this, 'show_price_on_table_pricing' ), 10, 3 );

			/**
			 * YITH WooCommerce Points and Rewards Premium compatibility.
			 * Set the points earned for a gift card product
			 */
			add_filter( 'ywpar_get_product_point_earned', array( $this, 'set_points_rewards_earning' ), 10, 2 );

			/**
			 * We'll add additional information and link near the product name, but we need to show it
			 * only on view order page, while the same action (woocommerce_order_item_meta_start) is used for emails where we do not want
			 * to show it.
			 * Hack it enabling and disabling the feature using the action/filter available at the moment
			 *
			 * @since WooCommerce 2.5.5
			 *
			 * Remove some action/filter that cause unwanted data to be shown on emails
			 */
			add_filter( 'woocommerce_order_item_quantity_html', array( $this, 'enable_edit_hooks_for_emails' ) );

			/**
			 * Add the previously removed action/filter that cause unwanted data to be shown on emails
			 */
			add_action( 'woocommerce_order_item_meta_end', array( $this, 'disable_edit_hooks_for_emails' ) );

			/**
			 * Add taxonomy and assign it to gift card products
			 */
			add_action( 'init', array( $this, 'create_gift_cards_category' ) );

			/**
			 * Convert gift card amounts shown on product page according to current WPML currency
			 */
			add_filter( 'yith_ywgc_gift_card_amounts', array( $this, 'get_wpml_multi_currency' ), 10, 2 );
		}

		/**
		 * Convert gift card amounts shown on product page according to current WPML currency
		 *
		 * @param array                $amounts amounts to be shown
		 * @param WC_Product_Gift_Card $product the gift card product
		 *
		 * @return array
		 */
		public function get_wpml_multi_currency( $amounts, $product ) {

			if ( $amounts ) {
				$multi_currency_amounts = array();
				foreach ( $amounts as $amount ) {
					$multi_currency_amounts[] = apply_filters( 'wcml_raw_price_amount', $amount );
				}

				return $multi_currency_amounts;
			}

			return $amounts;
		}

		// register new taxonomy which applies to attachments
		public function create_gift_cards_category() {

			$labels = array(
				'name'              => 'Gift Cards Categories',
				'singular_name'     => 'Gift Card Category',
				'search_items'      => 'Search Gift Card Categories',
				'all_items'         => 'All Gift Card Categories',
				'parent_item'       => 'Parent Gift Card Category',
				'parent_item_colon' => 'Parent Gift Card Category:',
				'edit_item'         => 'Edit Gift Card Category',
				'update_item'       => 'Update Gift Card Category',
				'add_new_item'      => 'Add New Gift Card Category',
				'new_item_name'     => 'New Gift Card Category Name',
				'menu_name'         => 'Gift Card Category',
			);

			$args = array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'query_var'         => true,
				'rewrite'           => true,
				'show_admin_column' => true,
				'show_ui'           => true,
				'public'            => true,
			);

			register_taxonomy( YWGC_CATEGORY_TAXONOMY, 'attachment', $args );
		}

		/**
		 * Remove some action/filter that cause unwante data to be shown on emails
		 */
		public function disable_edit_hooks_for_emails() {
			remove_action( 'woocommerce_order_item_meta_start', array(
				$this->frontend,
				'edit_gift_card',
			), 10 );
		}

		/**
		 * Add the previously removed action/filter that cause unwanted data to be shown on emails
		 *
		 * @param string $title the text being shown
		 *
		 * @return string
		 */
		public function enable_edit_hooks_for_emails( $title ) {
			add_action( 'woocommerce_order_item_meta_start', array(
				$this->frontend,
				'edit_gift_card',
			), 10, 3 );

			return $title;
		}

		/**
		 * Register all the custom post statuses of gift cards
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function register_custom_post_statuses() {

			register_post_status( GIFT_CARD_STATUS_DISABLED, array(
					'label'                     => __( 'Disabled', 'yith-woocommerce-gift-cards' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( __( 'Disabled', 'yith-woocommerce-gift-cards' ) . '<span class="count"> (%s)</span>', __( 'Disabled', 'yith-woocommerce-gift-cards' ) . ' <span class="count"> (%s)</span>' ),
				)
			);

			register_post_status( GIFT_CARD_STATUS_DISMISSED, array(
					'label'                     => __( 'Dismissed', 'yith-woocommerce-gift-cards' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( __( 'Dismissed', 'yith-woocommerce-gift-cards' ) . '<span class="count"> (%s)</span>', __( 'Dismissed', 'yith-woocommerce-gift-cards' ) . ' <span class="count"> (%s)</span>' ),
				)
			);
		}

		/**
		 * Set the points earned while used within YITH Points and Rewards plugin.
		 *
		 * @param float      $points
		 * @param WC_Product $product
		 *
		 * @return float
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_points_rewards_earning( $points, $product ) {

			//  Gift card products are not eligible for earning points!

			if ( YWGC_GIFT_CARD_PRODUCT_TYPE == $product->product_type ) {
				return 0.00;
			}

			return $points;
		}

		/**
		 * Show discounted price in the YITH WooCommerce Dynamic Pricing table
		 *
		 * @param string     $html    current value being shown
		 * @param array      $rule    rule to be applied
		 * @param WC_Product $product current product
		 *
		 * @return string
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_price_on_table_pricing( $html, $rule, $product ) {
			if ( YWGC_GIFT_CARD_PRODUCT_TYPE != $product->product_type ) {
				return $html;
			}
			/** @var WC_Product_Gift_Card $product */
			$prices = $product->amounts;
			if ( $prices ) {
				$min_price          = current( $prices );
				$discount_min_price = ywdpd_get_discounted_price_table( $min_price, $rule );
				$max_price          = end( $prices );
				$discount_max_price = ywdpd_get_discounted_price_table( $max_price, $rule );

				$html = $discount_min_price !== $discount_max_price ? sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), wc_price( $discount_min_price ), wc_price( $discount_max_price ) ) : wc_price( $discount_min_price );

			}

			return $html;
		}

		/**
		 * Single product template. Manage the price exclusion when used with the YITH WooCommerce Dynamic Pricing
		 *
		 * @param bool       $status  current visibility status
		 * @param float      $price   the price to be shown
		 * @param WC_Product $product the product in use
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function exclude_price_for_yith_dynamic_discount_product_page( $status, $price, $product ) {

			if ( YWGC_GIFT_CARD_PRODUCT_TYPE == $product->product_type ) {
				return true;
			}

			return $status;
		}

		/**
		 * Cart template. Manage the price exclusion when used with the YITH WooCommerce Dynamic Pricing
		 *
		 * @param float $price     the formatted price that will be shown in place of the real price
		 * @param float $old_price the real price
		 * @param array $cart_item
		 * @param array $cart_item_key
		 *
		 * @return mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_price_for_yith_dynamic_discount_cart_page( $price, $old_price, $cart_item, $cart_item_key ) {

			$_product = $cart_item['data'];

			if ( isset( $_product ) && ( $_product instanceof WC_Product_Gift_Card ) ) {
				if ( isset( $cart_item['amount'] ) ) {

					$original_price = $cart_item['amount'];
					$price          = '<del>' . wc_price( $original_price ) . '</del> ' . WC()->cart->get_product_price( $_product );
				}
			}

			return $price;
		}

		/**
		 * Add gift card email to the available email on resend order email feature
		 *
		 * @param array $emails current emails
		 *
		 * @return array
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function resend_gift_card_code( $emails ) {
			$emails[] = 'ywgc-email-send-gift-card';

			return $emails;
		}

		/**
		 * Add the BCC header to the email that send gift card code to the users
		 *
		 * @param string $headers current email headers
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function add_admin_to_email_bcc( $headers ) {
			//  Check if the option is set
			if ( ! $this->blind_carbon_copy ) {
				return $headers;
			}

			$recipients = apply_filters( 'yith_ywgc_bcc_additional_recipients', array( get_option( 'admin_email' ) ) );

			return $headers . "\nBcc: " . implode( ',', $recipients ) . "\r\n";
		}

		/**
		 * Append CSS for the email being sent to the customer
		 *
		 * @param WC_Email $email the email content
		 */
		public function add_footer_information( $email = null ) {
			if ( $email == null ) {
				return;
			}

			if ( ! isset( $email->object ) ) {
				return;
			}

			if ( ! $this->instanceof_giftcard( $email->object ) ) {
				return;
			}

			wc_get_template( 'emails/gift-card-footer.php',
				array(
					'email'     => $email,
					'shop_name' => $this->shop_name,
				),
				'',
				YITH_YWGC_TEMPLATES_DIR );
		}

		/**
		 * Add CSS style to gift card emails header
		 */
		public function include_css_for_emails( $email_heading, $email = null ) {
			if ( $email == null ) {
				return;
			}

			if ( ! isset( $email->object ) ) {
				return;
			}

			if ( ! $this->instanceof_giftcard( $email->object ) ) {
				return;
			}
			?>
			<style type="text/css">
				/* Put your CSS here */
				<?php include(YITH_YWGC_ASSETS_DIR . "/css/ywgc-frontend.css"); ?>

				h2,
				.center-email,
				.ywgc-footer {
					margin: 0 auto;
					margin-bottom: 15px;
					text-align: center;
				}

				.ywgc-product-image {
					float: left;
					display: inline-block;
					width: 30%;
					margin-right: 20px;
				}

				.ywgc-suggested-text {
					padding: 0 20px;
					display: inline-block;
					font-weight: 700;
					font-size: 13px;
					color: #484848;
					text-align: center;
					margin-bottom: 20px;
					opacity: 0.8;
				}

				.ywgc-product-description {
					float: left;
					width: 60%;
				}

				.ywgc-product-title {
					display: block;
					font-weight: 700;
					text-transform: uppercase;
					color: #484848;
					margin-bottom: 10px;
					opacity: 0.8;
				}

				.ywgc-product-excerpt {
					margin-bottom: 15px;
				}

				.ywgc-product-link {
					display: inline-block;
					padding: 8px 15px;
					background-color: #afafaf;
					border-radius: 5px;
					color: white;
					text-decoration: none;
					text-transform: uppercase;
					font-size: 13px;
					font-weight: 700;
				}

				div.ywgc-gift-card-content input,
				div.ywgc-gift-card-content textarea,
				div.ywgc-gift-card-content button {
					display: none;
				}

				div.ywgc-gift-card-content fieldset {
					border: none;
				}

				img.ywgc-logo-shop-image {
					max-width: 50px;
				}

				.ywgc-logo-style2 {
					text-align: center;
				}

				.ywgc-add-cart-discount {
					text-align: justify;
					clear: both;
					max-width: 500px;
					margin: 0 auto;
					margin-bottom: 15px;
				}

				.ywgc-add-cart-discount a {
					text-decoration: none;
				}

				.ywgc-discount-link {
					color: white;
					font-weight: 700;
					text-decoration: none;
					background-color: #557da1;
					display: inline-block;
					padding: 8px 15px;
					border-radius: 5px;
					text-transform: uppercase;
					font-size: 13px;
				}

				.ywgc-discount-message {
					display: block;
					margin-bottom: 25px;
					text-align: justify;
				}

				.ywgc-product-suggested {
					background-color: #f0f0f0;
					padding: 30px 0;
					margin-bottom: 30px;
				}

				.ywgc-discount-link-section {
					text-align: center;
					margin-bottom: 30px;
				}

				.ywgc-generated-code {
					font-weight: bold;
					display: block;
					font-size: 18px;
				}

			</style>
			<?php
		}

		/**
		 * Append CSS for the email being sent to the customer
		 */
		public function append_css_files() {
			$this->frontend->enqueue_frontend_style();
		}

		/**
		 * Show the introductory message on the email being sent
		 *
		 * @param string         $text
		 * @param YWGC_Gift_Card $gift_card
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_introductory_text( $text, $gift_card ) {
			?>
			<p class="center-email"><?php echo apply_filters( 'ywgc_gift_cards_email_before_preview_text', $text, $gift_card ); ?></p>
			<?php
		}

		/**
		 * Deny all vendors from creating gift cards
		 *
		 * @param $enable_user bool current enable status
		 *
		 * @return bool
		 */
		public function user_can_create_gift_cards( $enable_user ) {
			//  if YITH Multivendor is active, check if the user can
			if ( defined( 'YITH_WPV_PREMIUM' ) ) {
				$vendor = yith_get_vendor( 'current', 'user' );

				return $vendor->is_super_user();
			}

			return $enable_user;
		}

		/**
		 * When the default gift card image is changed from the plugin setting, update the product image
		 * of the default gift card
		 */
		public function update_default_gift_card() {
			if ( isset( $_POST["ywgc_gift_card_header_url-yith-attachment-id"] ) ) {
				update_post_meta( $this->default_gift_card, "_thumbnail_id", $_POST["ywgc_gift_card_header_url-yith-attachment-id"] );
			}
		}

		/**
		 * Hash the gift card code so it could be used for security checks
		 *
		 * @param YWGC_Gift_Card_Premium $gift_card
		 *
		 * @return string
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function hash_gift_card( $gift_card ) {

			return hash( 'md5', $gift_card->gift_card_number . $gift_card->ID );
		}

		/**
		 * Show a link that let the customer to go to the website, adding the discount to the cart
		 *
		 * @param YWGC_Gift_Card_Premium $gift_card
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_link_for_cart_discount( $gift_card ) {

			if ( ! $this->automatic_discount ) {
				return;
			}
			$shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );

			$args = array(
				YWGC_ACTION_ADD_DISCOUNT_TO_CART => $gift_card->gift_card_number,
				YWGC_ACTION_VERIFY_CODE          => $this->hash_gift_card( $gift_card ),
			);

			$apply_discount_url = esc_url( add_query_arg( $args, $shop_page_url ) );
			?>
			<div class="ywgc-add-cart-discount">
                <span
	                class="ywgc-discount-message"><?php _e( "In order to use this gift card you can enter the gift card code in the appropriate field of the cart page or you can click the following link to obtain the discount automatically.", 'yith-woocommerce-gift-cards' ); ?></span>

				<div class="ywgc-discount-link-section">
					<a class="ywgc-discount-link"
					   href="<?php echo $apply_discount_url; ?>"><?php _e( 'Click here for the discount', 'yith-woocommerce-gift-cards' ); ?></a>
				</div>
			</div>
			<?php
		}

		/**
		 * Show the product suggestion associated to the gift card
		 *
		 * @param YWGC_Gift_Card_Premium $gift_card
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_product_suggestion( $gift_card ) {

			if ( ! $gift_card->product_as_present ) {
				return;
			}

			//  The customer has suggested a product when he bought the gift card
			if ( $gift_card->present_variation_id ) {
				$product = wc_get_product( $gift_card->present_variation_id );
			} else {
				$product = wc_get_product( $gift_card->present_product_id );
			}

			wc_get_template( 'emails/product-suggestion.php',
				array(
					'gift_card' => $gift_card,
					'product'   => $product,
				),
				'',
				YITH_YWGC_TEMPLATES_DIR );
		}

		/**
		 * Add the customer product suggestion is there is one
		 *
		 * @param  YWGC_Gift_Card_Premium $gift_card
		 */
		public function show_email_additional_data( $gift_card ) {
			$this->show_link_for_cart_discount( $gift_card );

			$this->show_product_suggestion( $gift_card );
		}

		/**
		 * Add an option to let the admin set the gift card as a physical good or digital goods.
		 *
		 * @param array $array
		 *
		 * @return mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function add_type_option( $array ) {
			if ( isset( $array["virtual"] ) ) {
				$array["virtual"]["wrapper_class"] = add_cssclass( 'show_if_gift-card', $array["virtual"]["wrapper_class"] );
			}

			return $array;
		}

		/**
		 * Permit gift card to be purchasable even without price
		 *
		 * @param bool       $purchasable
		 * @param WC_Product $product
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function gift_card_is_purchasable( $purchasable, $product ) {

			if ( ! ( $product instanceof WC_Product_Gift_Card ) ) {
				return $purchasable;
			}

			return true;
		}

		/**
		 * Create a product of type gift card to be used as placeholder. Should not be visible on shop page.
		 */
		public function initialize_products() {
			//  Search for a product with meta YWGC_PRODUCT_PLACEHOLDER
			$this->default_gift_card = get_option( YWGC_PRODUCT_PLACEHOLDER, - 1 );

			if ( - 1 == $this->default_gift_card ) {

				//  Create a default gift card product
				$args = array(
					'post_title'   => __( 'Gift card', 'yith-woocommerce-gift-cards' ),
					'post_name'    => __( 'gift_card', 'yith-woocommerce-gift-cards' ),
					'post_content' => __( 'This product has been automatically created by the plugin YITH Gift Cards.You must not edit it, or the plugin could not work properly', 'yith-woocommerce-gift-cards' ),
					'post_status'  => 'publish',
					'post_date'    => date( 'Y-m-d H:i:s' ),
					'post_author'  => 0,
					'post_type'    => 'product',
				);

				$this->default_gift_card = wp_insert_post( $args );
				update_option( YWGC_PRODUCT_PLACEHOLDER, $this->default_gift_card );

				//  Create a taxonomy for products of type YWGC_GIFT_CARD_PRODUCT_TYPE and
				//  set the product created to the new taxonomy
				//  Create product type
				$term = wp_insert_term( YWGC_GIFT_CARD_PRODUCT_TYPE, 'product_type' );

				$term_id = - 1;
				if ( $term instanceof WP_Error ) {
					$error_code = $term->get_error_code();
					if ( "term_exists" == $error_code ) {
						$term_id = $term->get_error_data( $error_code );
					}
				} else {
					$term_id = $term["term_id"];
				}

				if ( $term_id != - 1 ) {
					wp_set_object_terms( $this->default_gift_card, $term_id, 'product_type' );
				} else {
					wp_die( __( "An error occurred, you cannot use the plugin", 'yith-woocommerce-gift-cards' ) );
				}
			}
			//  set this default gift card product as virtual
			update_post_meta( $this->default_gift_card, "_virtual", 'yes' );
		}

		public function init_plugin() {
			$this->allow_manual_amount       = "yes" == get_option( 'ywgc_permit_free_amount' );
			$this->allow_product_as_present  = "yes" == get_option( 'ywgc_permit_its_a_present' );
			$this->allow_modification        = "yes" == get_option( 'ywgc_permit_modification' );
			$this->allow_send_later          = "yes" == get_option( 'ywgc_enable_send_later' );
			$this->notify_customer           = "yes" == get_option( 'ywgc_notify_customer' );
			$this->blind_carbon_copy         = "yes" == get_option( "ywgc_blind_carbon_copy" );
			$this->automatic_discount        = "yes" == get_option( "ywgc_auto_discount" );
			$this->restricted_usage          = "yes" == get_option( "ywgc_restricted_usage" );
			$this->allow_custom_design       = "yes" == get_option( "ywgc_custom_design" );
			$this->allow_template_design     = "yes" == get_option( "ywgc_template_design" );
			$this->allow_multiple_recipients = "yes" == get_option( "ywgc_allow_multi_recipients" );

			$this->order_cancelled_action = get_option( "ywgc_order_cancelled_action", 'nothing' );
			$this->order_refunded_action  = get_option( "ywgc_order_refunded_action", 'nothing' );
			$this->enable_pre_printed     = "yes" == get_option( "ywgc_enable_pre_printed" );
			$this->mini_cart_fix          = "yes" == get_option( "ywgc_fix_mini_cart" );

			$this->shop_name             = get_option( 'ywgc_shop_name', '' );
			$this->custom_image_max_size = get_option( 'ywgc_custom_image_max_size', 1 );
			$this->shop_logo_url         = get_option( "ywgc_shop_logo_url", YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.png' );
			$this->shop_logo_on_template = "yes" == get_option( "ywgc_shop_logo_on_gift_card" );

			$this->default_header_image_url = get_option( "ywgc_gift_card_header_url", YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.png' );
			$this->template_style           = get_option( "ywgc_template_style", 'style1' );

			$this->initialize_products();
		}

		/**
		 * Retrieve if the gift cards should be updated on order refunded
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function change_status_on_refund() {
			return $this->disable_on_refund() || $this->dismiss_on_refund();
		}

		/**
		 * Retrieve if the gift cards should be updated on order cancelled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function change_status_on_cancelled() {
			return $this->disable_on_cancelled() || $this->dismiss_on_cancelled();
		}

		/**
		 * Retrieve if a gift card should be set as dismissed if an order change its status
		 * to refunded
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function dismiss_on_refund() {
			return 'dismiss' == $this->order_refunded_action;
		}

		/**
		 * Retrieve if a gift card should be set as disabled if an order change its status
		 * to refunded
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function disable_on_refund() {
			return 'disable' == $this->order_refunded_action;
		}

		/**
		 * Retrieve if a gift card should be set as dismissed if an order change its status
		 * to cancelled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function dismiss_on_cancelled() {
			return 'dismiss' == $this->order_cancelled_action;
		}

		/**
		 * Retrieve if a gift card should be set as disabled if an order change its status
		 * to cancelled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function disable_on_cancelled() {
			return 'disable' == $this->order_cancelled_action;
		}

		/**
		 * Register the custom post type
		 */
		public function init_post_type() {
			$args = array(
				'label'               => __( 'Gift Cards', 'yith-woocommerce-gift-cards' ),
				'description'         => __( 'Gift Cards', 'yith-woocommerce-gift-cards' ),
				// Features this CPT supports in Post Editor
				'supports'            => array(
					'title',
				),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'menu_position'       => 9,
				'can_export'          => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-clipboard',
				'query_var'           => false,
			);

			// Registering your Custom Post Type
			register_post_type( YWGC_CUSTOM_POST_TYPE_NAME, $args );
		}

		/**
		 * Checks for YWGC_Gift_Card_Premium instance
		 *
		 * @param object $obj the object to check
		 *
		 * @return bool obj is an instance of YWGC_Gift_Card_Premium
		 */
		public function instanceof_giftcard( $obj ) {
			return $obj instanceof YWGC_Gift_Card_Premium;
		}

		/**
		 * Retrieve a gift card product instance from the gift card code
		 *
		 * @param $code string the card code to search for
		 *
		 * @return YWGC_Gift_Card_Premium
		 */
		public function get_gift_card_by_code( $code ) {
			/*if ( ! is_string ( $code ) ) {
				return null;
			}*/

			$args = array(
				'gift_card_number' => $code
			);

			return new YWGC_Gift_Card_Premium( $args );
		}

		public function set_template_design_allowed_status( $product_id, $status ) {
			update_post_meta( $product_id, YWGC_PRODUCT_TEMPLATE_DESIGN, $status );
		}

		public function get_template_design_status( $product_id ) {
			return get_post_meta( $product_id, YWGC_PRODUCT_TEMPLATE_DESIGN, true );
		}

		public function set_manual_amount_allowed_status( $product_id, $status ) {
			update_post_meta( $product_id, YWGC_MANUAL_AMOUNT_MODE, $status );
		}

		public function get_manual_amount_allowed_status( $product_id ) {
			return get_post_meta( $product_id, YWGC_MANUAL_AMOUNT_MODE, true );

		}


		/**
		 * Save additional product attribute when a gift card product is saved
		 *
		 * @param $post_id int current product id
		 */
		public function save_gift_card_product( $post_id, $post, $product ) {
			//	Save the flag for manual amounts when the product is saved
			if ( isset( $_POST["manual_amount_mode"] ) ) {
				$this->set_manual_amount_allowed_status( $post_id, $_POST["manual_amount_mode"] );
			}
		}

		/**
		 * Create a new gift card object from POST data so it can be added to the cart with all the
		 * settings needed.
		 */
		public function create_gift_card() {

			$product_as_present   = isset( $_POST["ywgc-as-present"] ) && ( 1 == $_POST["ywgc-as-present"] );
			$present_variation_id = 0;
			$present_product_id   = 0;

			if ( $product_as_present ) {
				$product_id = $this->default_gift_card;

				$present_product_id   = $_POST["add-to-cart"];
				$present_variation_id = 0;

				if ( isset( $_POST["variation_id"] ) ) {
					$present_variation_id = $_POST["variation_id"];
				}

				if ( $present_variation_id ) {
					$product = new WC_Product( $present_variation_id );
				} else {
					$product = new WC_Product( $present_product_id );
				}

				$amount = $product->get_price();
			} else {
				$product_id = absint( $_POST['add-to-cart'] );

				$amount = ! isset( $_POST['gift_amounts'] ) || ( "-1" == $_POST['gift_amounts'] ) ?
					number_format( (float) $_POST['ywgc-manual-amount'], wc_get_price_decimals(), '.', '' ) :
					$_POST['gift_amounts'];
			}

			//$amount    = apply_filters( 'ywgc_gift_card_creation_amount', $amount );
			$gift_card = new YWGC_Gift_Card_Premium();

			$gift_card->set_amount( $amount, 0 );

			$gift_card->product_id = $product_id;

			/** @var YWGC_Gift_Card_Premium $gift_card */
			$gift_card->product_as_present   = $product_as_present;
			$gift_card->present_variation_id = $present_variation_id;
			$gift_card->present_product_id   = $present_product_id;

			/**
			 * Sanitize the gift card message, leaving the newline
			 */
			$gift_card->message            = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['ywgc-edit-message'] ) ) );
			$gift_card->sender             = sanitize_text_field( $_POST["ywgc-sender-name"] );
			$gift_card->postdated_delivery = isset( $_POST["ywgc-postdate"] );

			if ( $gift_card->postdated_delivery ) {
				$gift_card->delivery_date = sanitize_text_field( $_POST["ywgc-delivery-date"] );
			}

			/* Check is a custom picture or a template is chosen by the customer*/
			if ( isset( $_POST['ywgc-design-type'] ) ) {
				$design_type = $_POST['ywgc-design-type'];
				switch ( $design_type ) {
					case 'default' :
						$gift_card->use_default_image = true;

						break;

					case 'custom':

						if ( isset( $_FILES["ywgc-upload-picture"] ) ) {
							$custom_image = $_FILES["ywgc-upload-picture"];
							if ( isset( $custom_image["tmp_name"] ) && ( 0 == $custom_image["error"] ) ) {
								$gift_card->custom_image      = $this->save_uploaded_file( $custom_image );
								$gift_card->use_default_image = false;
							}
						}
						break;

					case 'template':
						if ( isset( $_POST['ywgc-template-design'] ) && is_numeric( $_POST['ywgc-template-design'] ) ) {
							$template_id                  = intval( $_POST['ywgc-template-design'] );
							$gift_card->custom_image      = $template_id;
							$gift_card->use_default_image = false;
						}
						break;
				}
			}

			return $gift_card;
		}

		/**
		 * move an uploaded file into a persistent folder
		 *
		 * @param string $image uploaded image
		 *
		 * @return string   real path of the uploaded image
		 */
		public
		function save_uploaded_file(
			$image
		) {
			// Create folders for storing documents
			$date     = getdate();
			$folder   = sprintf( "%s/%s", $date["year"], $date["mon"] );
			$filename = $image["name"];

			while ( true ) {

				$relative_path = sprintf( "%s/%s", $folder, $filename );
				$dir_path      = sprintf( "%s/%s", YITH_YWGC_SAVE_DIR, $folder );
				$full_path     = sprintf( "%s/%s", YITH_YWGC_SAVE_DIR, $relative_path );

				if ( ! file_exists( $full_path ) ) {
					if ( ! file_exists( $dir_path ) ) {
						wp_mkdir_p( $dir_path );
					}

					move_uploaded_file( $image["tmp_name"], $full_path );

					return $relative_path;
				} else {

					$unique_id = rand();

					$name_without_ext = pathinfo( $filename, PATHINFO_FILENAME );
					$ext              = pathinfo( $filename, PATHINFO_EXTENSION );

					$filename = $name_without_ext . $unique_id . '.' . $ext;
				}
			}
		}

		public function get_postdated_gift_cards( $send_date ) {
			$args = array(
				'meta_query' => array(
					array(
						'key'     => YWGC_META_GIFT_CARD_DELIVERY_DATE,
						'value'   => $send_date,
						'compare' => '<=',
					),
					array(
						'key'     => YWGC_META_GIFT_CARD_SENT,
						'value'   => '1',
						'compare' => 'NOT EXISTS',
					),
				),

				'post_type'      => YWGC_CUSTOM_POST_TYPE_NAME,
				'fields'         => 'ids',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
			);

			$ids = get_posts( $args );

			return $ids;
		}

		/**
		 * Retrieve the real picture to be used on the gift card preview
		 *
		 * @param YWGC_Gift_Card_Premium $object
		 *
		 * @return string
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 *
		 */
		public function get_gift_card_header_url( $object ) {
			//  Choose a valid gift card image header
			if ( ! $object->use_default_image ) {
				//  There is a custom header image or a template chosen by the customer?
				if ( is_numeric( $object->custom_image ) ) {
					//  a template was chosen, retrieve the picture associated
					$header_image_url = wp_get_attachment_image_url( $object->custom_image, 'full' );
				} else {
					$header_image_url = YITH_YWGC_SAVE_URL . $object->custom_image;
				}
			} else {
				if ( ! empty( $this->gift_card_header_url ) ) {
					$header_image_url = $this->gift_card_header_url;
				} else {
					$header_image_url = YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.png';
				}
			}

			return $header_image_url;
		}

		/**
		 * Retrieve the image to be used as a main image for the gift card
		 *
		 * @param WC_product $product
		 *
		 * @return string
		 */
		public function get_header_image_for_product( $product ) {

			$header_image_url = '';

			if ( $product && ( $product instanceof WC_Product ) ) {
				$custom_header = $this->admin->get_manual_header_image( $product->id );
				if ( $custom_header ) {
					$image = wp_get_attachment_image_src( $custom_header, 'full' );

					$header_image_url = $image[0];

				} elseif ( has_post_thumbnail( $product->id ) ) {
					$image            = wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'full' );
					$header_image_url = $image[0];
				}
			}

			return $header_image_url;
		}

		/**
		 * Retrieve the default image, configured from the plugin settings, to be used as gift card header image
		 *
		 * @param YWGC_Gift_Card_Premium|WC_Product $obj
		 *
		 * @return mixed|string|void
		 */
		public function get_header_image( $obj = null ) {


			$header_image_url = $this->default_header_image_url;

			if ( $obj instanceof WC_Product ) {

				$header_image_url = $this->get_header_image_for_product( $obj );
			} elseif ( $obj instanceof YWGC_Gift_Card_Premium ) {

				if ( ! $obj->use_default_image ) {
					//  There is a custom header image or a template chosen by the customer?
					if ( is_numeric( $obj->custom_image ) ) {
						//  a template was chosen, retrieve the picture associated
						$header_image_url = wp_get_attachment_image_url( $obj->custom_image, 'full' );

					} else {
						$header_image_url = YITH_YWGC_SAVE_URL . $obj->custom_image;
					}
				} else {
					$product          = wc_get_product( $obj->product_id );
					$header_image_url = $this->get_header_image_for_product( $product );
				}
			}

			if ( ! $header_image_url ) {
				$header_image_url = YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.png';
			}

			return $header_image_url;
		}

		/**
		 * Output a gift cards template filled with real data or with sample data to start editing it
		 * on product page
		 *
		 * @param        $object WC_Product|YWGC_Gift_Card_Premium
		 * @param string $context
		 */
		public function preview_digital_gift_cards( $object, $context = 'shop' ) {

			$header_image_url = '';

			if ( $object instanceof WC_Product ) {

				// check if the admin set a default image for gift card
				$header_image_url = $this->get_header_image( $object );
				$amount           = ( $object instanceof WC_Product_Simple ) ? $object->get_display_price() : 0;
				$formatted_price  = wc_price( $amount );
				$gift_card_code   = "xxxx-xxxx-xxxx-xxxx";
				$message          = __( "Your message...", 'yith-woocommerce-gift-cards' );
			} else if ( $object instanceof YWGC_Gift_Card_Premium ) {

				$header_image_url = $this->get_header_image( $object );

				$amount          = $object->get_amount( true );
				$formatted_price = apply_filters( 'yith_ywgc_gift_card_template_amount', wc_price( $amount ), $object );

				$gift_card_code = $object->gift_card_number;
				$message        = $object->message;
			}
			?>
			<div class="ywgc-template <?php echo $this->template_style; ?>">
				<?php if ( $this->shop_logo_on_template && $this->shop_logo_url ) : ?>
					<div class="ywgc-top-header">
						<img src="<?php echo $this->shop_logo_url; ?>"
						     class="ywgc-logo-shop-image"
						     alt="<?php _e( "The shop logo for the gift card", 'yith-woocommerce-gift-cards' ); ?>"
						     title="<?php _e( "The shop logo for the gift card", 'yith-woocommerce-gift-cards' ); ?>">
					</div>
				<?php endif; ?>

				<div class="ywgc-preview">
					<div class="ywgc-main-image">
						<?php if ( $header_image_url ): ?>
							<img src="<?php echo $header_image_url; ?>"
							     id="ywgc-main-image" class="ywgc-main-image"
							     alt="<?php _e( "The main image for the gift card", 'yith-woocommerce-gift-cards' ); ?>"
							     title="<?php _e( "The main image for the gift card", 'yith-woocommerce-gift-cards' ); ?>">
						<?php endif; ?>

					</div>
					<div class="ywgc-card-values">
						<?php if ( $this->shop_logo_on_template && $this->shop_logo_url ) : ?>
							<div class="ywgc-logo-shop">
								<img src="<?php echo $this->shop_logo_url; ?>"
								     class="ywgc-logo-shop-image"
								     alt="<?php _e( "The shop logo for the gift card", 'yith-woocommerce-gift-cards' ); ?>"
								     title="<?php _e( "The shop logo for the gift card", 'yith-woocommerce-gift-cards' ); ?>">
							</div>
						<?php endif; ?>
						<div class="ywgc-card-amount">
							<?php echo $formatted_price; ?>
						</div>

					</div>
					<div class="ywgc-card-code">
						<?php _e( "Your Gift Card code:", 'yith-woocommerce-gift-cards' ); ?>
						<span class="ywgc-generated-code"><?php echo $gift_card_code; ?></span>
					</div>
					<div class="ywgc-card-message"><?php echo $message; ?></div>
				</div>
			</div>
			<?php
		}


	}
}