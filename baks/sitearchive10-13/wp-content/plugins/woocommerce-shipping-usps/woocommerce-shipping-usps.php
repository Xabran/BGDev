<?php
/**
 * Plugin Name: WooCommerce USPS Shipping
 * Plugin URI: https://woocommerce.com/products/usps-shipping-method/
 * Description: Obtain shipping rates dynamically via the USPS Shipping API for your orders.
 * Version: 4.4.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Copyright: 2009-2016 Automattic
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * https://www.usps.com/webtools/htm/Rate-Calculators-v1-5.htm
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '83d1524e8f5f1913e58889f83d442c32', '18657' );

/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	/**
	 * WC_USPS class
	 */
	class WC_USPS {

		/**
		 * Plugin's version.
		 *
		 * @since 4.4.0
		 *
		 * @var string
		 */
		public $version = '4.4.0';

		/**
		 * Constructor
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'activation_check' ) );
			add_action( 'admin_init', array( $this, 'maybe_install' ), 5 );
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_action( 'woocommerce_shipping_init', array( $this, 'init' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_method' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'admin_notices', array( $this, 'environment_check' ) );
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
			add_action( 'wp_ajax_usps_dismiss_upgrade_notice', array( $this, 'dismiss_upgrade_notice' ) );
		}

		/**
		 * Check plugin can run
		 */
		public function activation_check() {
			if ( ! function_exists( 'simplexml_load_string' ) ) {
				deactivate_plugins( basename( __FILE__ ) );
				wp_die( "Sorry, but you cannot run this plugin, it requires the SimpleXML library installed on your server/hosting to function." );
			}
		}

		/**
		 * environment_check function.
		 *
		 * @access public
		 * @return void
		 */
		public function environment_check() {
			if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
				return;
			}

			$admin_page = 'wc-settings';

			if ( get_woocommerce_currency() !== 'USD' ) {
				echo '<div class="error">
					<p>' . sprintf( __( 'USPS requires that the <a href="%s">currency</a> is set to US Dollars.', 'woocommerce-shipping-usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
				</div>';
			}

			elseif ( ! in_array( WC()->countries->get_base_country(), array( 'US', 'PR', 'VI', 'MH', 'FM' ) ) ) {
				echo '<div class="error">
					<p>' . sprintf( __( 'USPS requires that the <a href="%s">base country/region</a> is the United States.', 'woocommerce-shipping-usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
				</div>';
			}
		}

		/**
		 * Localisation
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-shipping-usps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Plugin page links
		 */
		public function plugin_action_links( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_shipping_usps' ) . '">' . __( 'Settings', 'woocommerce-shipping-usps' ) . '</a>',
				'<a href="http://support.woothemes.com/">' . __( 'Support', 'woocommerce-shipping-usps' ) . '</a>',
				'<a href="http://docs.woothemes.com/document/usps-shipping/">' . __( 'Docs', 'woocommerce-shipping-usps' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}

		/**
		 * Load gateway class
		 */
		public function init() {
			if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
				include_once( dirname( __FILE__ ) . '/includes/class-wc-shipping-usps-deprecated.php' );
			} else {
				include_once( dirname( __FILE__ ) . '/includes/class-wc-shipping-usps.php' );
			}
		}

		/**
		 * Add method to WC
		 */
		public function add_method( $methods ) {
			if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
				$methods[] = 'WC_Shipping_USPS';
			} else {
				$methods['usps'] = 'WC_Shipping_USPS';
			}

			return $methods;
		}

		/**
		 * Enqueue scripts
		 */
		public function scripts() {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}

		/**
		 * Checks the plugin version
		 *
		 * @access public
		 * @since 4.4.0
		 * @version 4.4.0
		 * @return bool
		 */
		public function maybe_install() {
			// only need to do this for versions less than 4.4.0 to migrate
			// settings to shipping zone instance
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
			if ( ! $doing_ajax
			     && ! defined( 'IFRAME_REQUEST' )
			     && version_compare( WC_VERSION, '2.6.0', '>=' )
			     && version_compare( get_option( 'wc_usps_version' ), '4.4.0', '<' ) ) {

				$this->install();

			}

			return true;
		}

		/**
		 * Update/migration script
		 *
		 * @since 4.4.0
		 * @version 4.4.0
		 * @access public
		 * @return bool
		 */
		public function install() {
			// get all saved settings and cache it
			$usps_settings = get_option( 'woocommerce_usps_settings', false );

			// settings exists
			if ( $usps_settings ) {
				global $wpdb;

				// unset un-needed settings
				unset( $usps_settings['enabled'] );
				unset( $usps_settings['availability'] );
				unset( $usps_settings['countries'] );

				// first add it to the "rest of the world" zone when no usps
				// instance.
				if ( ! $this->is_zone_has_usps( 0 ) ) {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}woocommerce_shipping_zone_methods ( zone_id, method_id, method_order, is_enabled ) VALUES ( %d, %s, %d, %d )", 0, 'usps', 1, 1 ) );
					// add settings to the newly created instance to options table
					$instance = $wpdb->insert_id;
					add_option( 'woocommerce_usps_' . $instance . '_settings', $usps_settings );
				}

				update_option( 'woocommerce_usps_show_upgrade_notice', 'yes' );
			}

			update_option( 'wc_usps_version', $this->version );
		}

		/**
		 * Show the user a notice for plugin updates
		 *
		 * @since 4.4.0
		 */
		public function upgrade_notice() {
			$show_notice = get_option( 'woocommerce_usps_show_upgrade_notice' );

			if ( 'yes' !== $show_notice ) {
				return;
			}

			$query_args = array( 'page' => 'wc-settings', 'tab' => 'shipping' );
			$zones_admin_url = add_query_arg( $query_args, get_admin_url() . 'admin.php' );
			?>
			<div class="notice notice-success is-dismissible wc-usps-notice">
				<p><?php echo sprintf( __( 'USPS now supports shipping zones. The zone settings were added to a new USPS method on the "Rest of the World" Zone. See the zones %shere%s ', 'woocommerce-shipping-usps' ),'<a href="' .$zones_admin_url. '">','</a>' ); ?></p>
			</div>

			<script type="application/javascript">
				jQuery( '.notice.wc-usps-notice' ).on( 'click', '.notice-dismiss', function () {
					wp.ajax.post('usps_dismiss_upgrade_notice');
				});
			</script>
			<?php
		}

		/**
		 * Turn of the dismisable upgrade notice.
		 * @since 4.4.0
		 */
		public function dismiss_upgrade_notice() {
			update_option( 'woocommerce_usps_show_upgrade_notice', 'no' );
		}

		/**
		 * Helper method to check whether given zone_id has usps method instance.
		 *
		 * @since 4.4.0
		 *
		 * @param int $zone_id Zone ID
		 *
		 * @return bool True if given zone_id has usps method instance
		 */
		public function is_zone_has_usps( $zone_id ) {
			global $wpdb;
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(instance_id) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'usps' AND zone_id = %d", $zone_id ) ) > 0;
		}
	}

	new WC_USPS();
}
