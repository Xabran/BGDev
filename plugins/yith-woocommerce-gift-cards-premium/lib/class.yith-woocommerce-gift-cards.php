<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


if (!class_exists('YITH_WooCommerce_Gift_Cards')) {

    /**
     *
     * @class   YITH_WooCommerce_Gift_Cards
     * @package Yithemes
     * @since   1.0.0
     * @author  Your Inspiration Themes
     */
    class YITH_WooCommerce_Gift_Cards
    {
        /**
         * @var YITH_WooCommerce_Gift_Cards_Backend|YITH_WooCommerce_Gift_Cards_Backend_Premium The instance for backend features and methods
         */
        public $admin;

        /**
         * @var YITH_WooCommerce_Gift_Cards_Frontend|YITH_WooCommerce_Gift_Cards_Frontend_Premium instance for frontend features and methods
         */
        public $frontend;

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
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
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
        protected function __construct()
        {

            require_once(YITH_YWGC_DIR . 'lib/class.ywgc-product-gift-card.php');

            /**
             * Do some stuff on plugin init
             */
            add_action('init', array($this, 'on_plugin_init'));

            /**
             * Hide the temporary gift card product from being shown on shop page
             */
            add_action('woocommerce_product_query', array($this, 'hide_from_shop_page'), 10, 1);

            add_filter('yith_plugin_status_sections', array($this, 'set_plugin_status'));
        }

        /**
         * Hide the temporary gift card product from being shown on shop page
         *
         * @param WP_Query $query The current query
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function hide_from_shop_page($query)
        {
            $default_gift_card = get_option(YWGC_PRODUCT_PLACEHOLDER, -1);

            if ($default_gift_card > 0) {
                $query->set('post__not_in', array($default_gift_card));
            }
        }

        /**
         * Execute update on data used by the plugin that has been changed passing
         * from a DB version to another
         */
        public function update_database()
        {

            /**
             * Init DB version if not exists
             */
            $db_version = get_option(YWGC_DB_VERSION_OPTION);

            if (!$db_version) {
                //  Update from previous version where the DB option was not set
                global $wpdb;

                //  Update metakey from YITH Gift Cards 1.0.0
                $query = "Update {$wpdb->prefix}woocommerce_order_itemmeta
                        set meta_key = '" . YWGC_META_GIFT_CARD_POST_ID . "'
                        where meta_key = 'gift_card_post_id'";
                $wpdb->query($query);

                $db_version = '1.0.0';
            }

            /**
             * Start the database update step by step
             */
            if (version_compare($db_version, '1.0.0', '<=')) {

                //  Set gift card placeholder with catalog visibility equal to "hidden"
                $placeholder_id = get_option(YWGC_PRODUCT_PLACEHOLDER);

                update_post_meta($placeholder_id, '_visibility', 'hidden');

                $db_version = '1.0.1';
            }

            if (version_compare($db_version, '1.0.1', '<=')) {

                //  extract the user_id from the order where a gift card is applied and register
                //  it so the gift card will be shown on my-account

                $args = array(
                    'numberposts' => -1,
                    'meta_key' => YWGC_META_GIFT_CARD_ORDERS,
                    'post_type' => YWGC_CUSTOM_POST_TYPE_NAME,
                    'post_status' => 'any',
                );

                //  Retrieve the gift cards matching the criteria
                $posts = get_posts($args);

                foreach ($posts as $post) {
                    $args = array('ID' => $post->ID);
                    $gift_card = new YWGC_Gift_Card_Premium($args);

                    if (!$gift_card->exists()) {
                        continue;
                    }

                    /** @var WC_Order $order */
                    $orders = $gift_card->get_registered_orders();
                    foreach ($orders as $order_id) {
                        $order = wc_get_order($order_id);
                        if ($order) {
                            $gift_card->register_user($order->customer_user);
                        }
                    }
                }

                $db_version = '1.0.2';  //  Continue to next step...
            }

            //  Update the current DB version
            update_option(YWGC_DB_VERSION_OPTION, YITH_YWGC_DB_CURRENT_VERSION);
        }

        /**
         *  Execute all the operation need when the plugin init
         */
        public function on_plugin_init()
        {
            $this->init_post_type();

            $this->init_plugin();

            $this->update_database();
        }

        /**
         * Initialize plugin data and shard instances
         */
        public function init_plugin()
        {
            //nothing to do
        }

        /**
         * Register the custom post type
         */
        public function init_post_type()
        {
            $args = array(
                'label' => __('Gift Cards', 'yith-woocommerce-gift-cards'),
                'description' => __('Gift Cards', 'yith-woocommerce-gift-cards'),
                //'labels' => $labels,
                // Features this CPT supports in Post Editor
                'supports' => array(
                    //'title',
                    'editor',
                    //'author',
                ),
                'hierarchical' => false,
                'public' => false,
                'show_ui' => false,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'menu_position' => 9,
                'can_export' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'menu_icon' => 'dashicons-clipboard',
                'query_var' => false,
            );

            // Registering your Custom Post Type
            register_post_type(YWGC_CUSTOM_POST_TYPE_NAME, $args);
        }

        /**
         * Checks for YWGC_Gift_Card instance
         *
         * @param object $obj the object to check
         *
         * @return bool obj is an instance of YWGC_Gift_Card
         */
        public function instanceof_giftcard($obj)
        {
            return $obj instanceof YWGC_Gift_Card;
        }

        /**
         * Retrieve a gift card product instance from the gift card code
         *
         * @param $code string the card code to search for
         *
         * @return YWGC_Gift_Card
         */
        public function get_gift_card_by_code($code)
        {
            /*if ( ! is_string ( $code ) ) {
                return null;
            }
*/
            $args = array('gift_card_number' => $code);

            return new YWGC_Gift_Card($args);
        }

        /**
         * Generate a new gift card code
         *
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function generate_gift_card_code()
        {

            //  Create a new gift card number

            //http://stackoverflow.com/questions/3521621/php-code-for-generating-decent-looking-coupon-codes-mix-of-alphabets-and-number
            $code = strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 16));

            $code = sprintf("%s-%s-%s-%s",
                substr($code, 0, 4),
                substr($code, 4, 4),
                substr($code, 8, 4),
                substr($code, 12, 4)
            );

            return $code;
        }

        /**
         * Populate a new  a new YWGC_Gift_Card object from current $_POST data
         */
        public function populate_gift_card()
        {

	        if (!isset($_POST['add-to-cart'])) {
                return null;
            }

            $product_id = absint($_POST['add-to-cart']);

            $amount = 0.00;
            if (isset($_POST['gift_amounts']) && ($_POST['gift_amounts'] > -1)) {
                $amount = $_POST['gift_amounts'];
            } elseif (isset($_POST['ywgc-manual-amount'])) {
                $amount =  number_format( (float) $_POST['ywgc-manual-amount'], wc_get_price_decimals(), '.', '' );
            }

            $gift = new YWGC_Gift_Card();

            $gift->set_amount($amount, 0);
            $gift->product_id = $product_id;
            $gift->order_id = 0;

            return $gift;
        }
    }
}