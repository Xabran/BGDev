<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Bgpr
 * @subpackage Bgpr/includes
 * @author     BoardGames <jetogonon@gmail.com>
 */
class Bgpr {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Bgpr_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'bgpr';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Bgpr_Loader. Orchestrates the hooks of the plugin.
	 * - Bgpr_i18n. Defines internationalization functionality.
	 * - Bgpr_Admin. Defines all hooks for the admin area.
	 * - Bgpr_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bgpr-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bgpr-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-bgpr-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-bgpr-public.php';

		$this->loader = new Bgpr_Loader();

		require plugin_dir_path( __FILE__ ) . '../includes/class-bgpr-points.php';

		require plugin_dir_path( __FILE__ ) . '../includes/class-bgpr-points-log.php';

		require plugin_dir_path( __FILE__ ) . '../includes/class-bgpr-levels.php';

		require plugin_dir_path( __FILE__ ) . '../includes/class-bgpr-bucks.php';

		require plugin_dir_path( __FILE__ ) . '../includes/class-bgpr-bucks-log.php';

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Bgpr_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Bgpr_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Bgpr_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		//catch POSTs from paypal IPN
		$this->loader->add_action( 'valid-paypal-standard-ipn-request', $plugin_admin, 'bgpr_after_validIPN' );

		/*when user is created by admin*/
		//$this->loader->add_action( 'user_register', $plugin_admin, 'bgpr_registration_byadmin');

		/*when user is deleted by admin*/



		// menu item
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Settings link to the plugin
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

		//set admin AJAX functions
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'bgpr_admin_js' ); 

		$this->loader->add_action( 'wp_ajax_bgpr_point_actions', $plugin_admin ,'bgpr_point_actions_callback' );

		$this->loader->add_action( 'wp_ajax_bgpr_edit_member_meta', $plugin_admin, 'bgpr_edit_member_meta_callback');

		

	}

	

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Bgpr_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		/*when user sign up*/
		$this->loader->add_action( 'user_register', $plugin_public, 'bgpr_registration_event');


		/*when user adds a review*/
		$this->loader->add_action('comment_post', $plugin_public, 'bgpr_add_review_event');

		/*when user clicks button for want_it, love_it, and have_it*/
		$this->loader->add_action('brgm/after/wclist', $plugin_public, 'bgpr_action_list_event');

		/*user clicks Redeem Reward button in Cart*/
		$this->loader->add_action('wp_ajax_bgpr_claim_reward', $plugin_public, 'bgpr_claim_reward_callback');

		/* ajax call to update bottom left*/
		$this->loader->add_action('wp_ajax_bgpr_update_left_bottom', $plugin_public, 'bgpr_update_bottom_left_callback');
		$this->loader->add_action('wp_ajax_nopriv_bgpr_update_left_bottom', $plugin_public, 'bgpr_update_bottom_left_callback');

		/*ajax call to update amount needed more for free shipping*/
		//$this->loader->add_action('wp_ajax_bgpr_update_amountforfreeshipping', $plugin_public, 'bgpr_update_amountforfreeshipping_callback');


		$this->loader->add_action( 'woocommerce_removed_coupon', $plugin_public, 'bgpr_coupon_removed' );

		/*when user removed the players club from the cart items*/
		$this->loader->add_action( 'woocommerce_cart_item_removed', $plugin_public, 'bgpr_playersclub_removed');


		/*when user changes shipping method*/
		//$this->loader->add_action( 'woocommerce_shipping_method_chosen', $plugin_public, 'action_check_if_bgpr_shipping');

		$this->loader->add_action( 'wp_ajax_bgpr_free_shipping_selected', $plugin_public ,'bgpr_free_shipping_selected_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_bgpr_free_shipping_selected', $plugin_public ,'bgpr_free_shipping_selected_callback' );
		//$this->loader->add_action( 'wp_ajax_bgpr_free_shipping_deselected', $plugin_public ,'bgpr_free_shipping_deselected_callback' );

		$this->loader->add_action( 'bgpr_create_instantbucks_coupon', $plugin_public, 'bgpr_create_instantbucks_coupon_callback');

		$this->loader->add_action( 'woocommerce_checkout_fields', $plugin_public,'bgpr_custom_field_checkout' );

		/*after pre-payment completed, but actually only IPN from paypal can truly validate a successful payment*/
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $plugin_public, 'bgpr_pre_payment_complete');


		/*on user logout*/
		$this->loader->add_action( 'wp_logout', $plugin_public, 'bgpr_user_logout');

		/*thank you page after user made a payment using CC*/
		$this->loader->add_action('woocommerce_thankyou_success', $plugin_public, 'bgpr_woocommerce_thankyou' );

		//$this->loader->add_action( 'woocommerce_cart_totals_after_order_total', $plugin_public, 'my_new_carttotals_after' );


	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public  function get_plugin_name() 
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Bgpr_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}