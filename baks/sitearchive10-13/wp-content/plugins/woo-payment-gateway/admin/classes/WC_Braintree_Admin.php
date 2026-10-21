<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

/**
 * Main admin class for the braintree plugin.
 * Controls admin screens for plugin configuration.
 *
 * @author Clayton Rogers
 * @since 3/12/16
 */
class WC_Braintree_Admin {
	private static $api_view = array (
			'apisettings_title',
			'license_status_notice',
			'production_environment',
			'production_merchant_id',
			'production_private_key',
			'production_public_key',
			'production_connection_test',
			'sandbox_environment',
			'sandbox_merchant_id',
			'sandbox_private_key',
			'sandbox_public_key',
			'sandbox_connection_test' 
	);
	private static $wooCommerce_view = array (
			'woocommerce_title',
			'enabled',
			'custom_form',
			'custom_form_design',
			'paypal_options',
			'dynamic_descriptors',
			'dynamic_descriptor_name',
			'dynamic_descriptor_phone',
			'dynamic_descriptor_url',
			'title_text',
			//'order_status', removed in version 2.4.8
			'order_prefix',
			'payment_methods',
			'payment_method_location',
			'fail_on_duplicate',
			'authorize_transaction',
			'3ds_enabled',
			'save_payment_methods',
			'paypal_only',
			'woocommerce_braintree_merchant_acccounts' 
	);
	private static $debugLog_view = array (
			'debug_title',
			'enable_debug' 
	);
	private static $license_view = array (
			'license_title',
			'license_status',
			'license' 
	);
	private static $subscriptions_view = array (
			'subscriptions_title',
			'woocommerce_subscriptions',
			'woocommerce_subscriptions_prefix',
			//'subscriptions_payment_success_status', removed in version 2.4.8
			//'subscriptions_payment_failed_status', removed in version 2.4.8
			'braintree_subscriptions',
			'braintree_subscriptions_charge_success',
			'braintree_subscriptions_charge_failed',
			'braintree_subscriptions_active',
			'braintree_subscriptions_expired',
			'braintree_subscriptions_past_due',
			'braintree_subscriptions_cancelled',
	);
	private static $braintree_subscriptions_view = array (
			'braintree_subscriptions_title',
			'braintree_subscriptions',
			'braintree_only_subscriptions_charge_success',
			'braintree_only_subscriptions_charge_failed',
			'braintree_only_subscriptions_active',
			'braintree_only_subscriptions_expired',
			'braintree_only_subscriptions_past_due',
			'braintree_only_subscriptions_cancelled' 
	);
	private static $donations_view = array (
			'donations_title',
			'donation_text_exempt',
			'donation_form_layout',
			'donation_modal_button_text',
			'donation_modal_button_background',
			'donation_modal_button_border',
			'donation_modal_button_text_color',
			'donation_button_text',
			'donation_button_background',
			'donation_button_border',
			'donation_button_text_color',
			'donation_address',
			'donation_default_country',
			'donation_email',
			'donation_merchant_account_id',
			'donation_name',
			'donation_currency',
			'donation_success_url',
			'donation_payment_methods' 
	);
	private static $webhooks_view = array (
			'webhooks_title',
			'enable_webhooks',
			'webhook_subscription_charged_successfully',
			'webhook_subscription_charged_unsuccessfully',
			'webhook_subscription_went_active',
			'webhook_subscription_past_due',
			'webhook_subscription_expired',
			'webhook_subscription_cancelled' 
	);
	
	/**
	 * Set initial values required by the class to function.
	 */
	public static function init() {
		add_action ( 'admin_enqueue_scripts', __CLASS__ . '::loadAdminScripts' );
		add_action ( 'admin_menu', __CLASS__ . '::braintreeAdminMeu' );
		add_action ( 'admin_init', __CLASS__ . '::saveBraintreeSettings' );
		add_action ( 'wp_ajax_braintree_for_woocommerce_delete_item', __CLASS__ . '::deleteSetting' );
		add_action ( 'admin_notices', __CLASS__ . '::displayAdminNotices' );
		if (BT_Manager ()->isActive ( 'dynamic_descriptors' ) || BT_Manager ()->getRequestParameter ( 'dynamic_descriptors' ) === 'yes') {
			add_action ( 'save_post', __CLASS__ . '::saveSubscriptionDynamicDescriptor' );
		}
		add_action ( 'add_meta_boxes', __CLASS__ . '::add_meta_boxes' );
	}
	
	/**
	 * Add necessary meta boxes used in admin functionality.
	 */
	public static function add_meta_boxes() {
		add_meta_box ( 'braintree-order-settlement-box', __ ( 'Submit For Settlement' ), __CLASS__ . '::output_wc_meta_box_settlement', 'shop_order', 'side' );
	}
	public static function braintreeAdminMeu() {
		add_menu_page ( 'Braintree Payments', 'Braintree Payments', 'manage_options', 'braintree-payments-menu', null, null, '9.134' );
		add_submenu_page ( 'braintree-payments-menu', 'Braintree Settings', 'Braintree Settings', 'manage_options', 'braintree-payment-settings', 'WC_Braintree_Admin::showBraintreePaymentsView' );
		add_submenu_page ( 'braintree-payments-menu', 'Woocommerce Settings', 'Woocommerce Settings', 'manage_options', 'braintree-woocommerce-settings', 'WC_Braintree_Admin::showWoocommerceView' );
		add_submenu_page ( 'braintree-payments-menu', 'Activate License', 'Activate License', 'manage_options', 'braintree-license-page', 'WC_Braintree_Admin::showLicenseView' );
		add_submenu_page ( 'braintree-payments-menu', 'Subscriptions', 'Subscriptions', 'manage_options', 'braintree-subscriptions-page', 'WC_Braintree_Admin::showSubscriptionView' );
		add_submenu_page ( 'braintree-payments-menu', 'Webhooks', 'Webhooks', 'manage_options', 'braintree-webhooks-page', 'WC_Braintree_Admin::showWebhookView' );
		add_submenu_page ( 'braintree-payments-menu', 'Donations', 'Donations', 'manage_options', 'braintree-donations-page', 'WC_Braintree_Admin::showDonationsView' );
		add_submenu_page ( 'braintree-payments-menu', 'Debug Log', 'Debug Log', 'manage_options', 'braintree-debug-log', 'WC_Braintree_Admin::showDebugView' );
		add_submenu_page ( 'braintree-payments-menu', 'Tutorials', 'Tutorials', 'manage_options', 'braintree-payments-tutorials', 'Braintree_Admin_Pages::showTutorialsView' );
		remove_submenu_page ( 'braintree-payments-menu', 'braintree-payments-menu' );
	}
	public static function loadAdminScripts() {
		wp_enqueue_style ( 'braintree-admin-style', WC_BRAINTREE_ASSETS . 'css/admin-style.css', array (), BT_Manager ()->version );
		wp_enqueue_script ( 'braintree-admin-script', WC_BRAINTREE_ASSETS . 'js/admin-script.js', array (
				'jquery' 
		), BT_Manager ()->version, true );
		wp_localize_script ( 'braintree-admin-script', 'braintree_admin_settings', self::localize_admin_settings () );
		
		if (self::isPage ( array (
				'braintree-payment-settings',
				'braintree-woocommerce-settings',
				'braintree-license-page',
				'braintree-subscriptions-page',
				'braintree-webhooks-page',
				'braintree-debug-log',
				'braintree-payments-tutorials',
				'braintree-donations-page' 
		) )) {
			wp_enqueue_script ( 'color-picker-colors-script', WC_BRAINTREE_ASSETS . 'js/tinyColorPicker-master/colors.js' );
			wp_enqueue_script ( 'color-picker-script', WC_BRAINTREE_ASSETS . 'js/tinyColorPicker-master/jqColorPicker.js', array (
					'color-picker-colors-script' 
			) );
			foreach ( BT_Manager ()->get_paypal_design_options () as $index => $option ) {
				wp_enqueue_style ( 'paypal-button-style-' . $index, $option ['css'], array (), BT_Manager ()->version );
			}
		}
		if (self::isPage ( 'braintree-payments-tutorials' )) {
			wp_enqueue_script ( 'braintree-admin-tutorials', WC_BRAINTREE_ASSETS . 'js/admin-tutorial.js', array (
					'jquery' 
			) );
		}
	}
	public static function saveBraintreeSettings() {
		if (isset ( $_POST ['save_braintree_apisettings'] )) {
			self::saveSettings ( self::$api_settings, 'braintree-payment-settings' );
		} elseif (isset ( $_POST ['save_braintree_payment_settings'] )) {
			self::saveSettings ( self::$api_view, 'braintree-payment-settings' );
		} elseif (isset ( $_POST ['save_braintree_woocommerce_settings'] )) {
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::saveMerchantAccounts' );
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::validateDynamicDescriptorName' );
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::validateDynamicDescriptorPhone' );
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::validateDynamicDescriptorUrl' );
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::save_paypal_options' );
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::save_custom_form_settings' );
			self::saveSettings ( self::$wooCommerce_view, 'braintree-woocommerce-settings' );
		} elseif (isset ( $_POST ['save_woocommerce_subscription_settings'] )) {
			self::saveSettings ( self::$subscriptions_view, 'braintree-subscriptions-page' );
		} elseif (isset ( $_POST ['save_braintree_subscription_settings'] )) {
			self::saveSettings ( self::$braintree_subscriptions_view, 'braintree-subscriptions-page' );
		} elseif (isset ( $_POST ['save_braintree_donation_settings'] )) {
			add_filter ( 'braintree_for_woocommerce_save_settings', __CLASS__ . '::validateDonationsMerchantAccount' );
			self::saveSettings ( self::$donations_view, 'braintree-donations-page' );
		} elseif (isset ( $_POST ['activate_braintree_license'] )) {
			$license_key = isset ( $_POST ['license'] ) ? $_POST ['license'] : '';
			BT_Manager ()->activateLicense ( $license_key );
		} elseif (isset ( $_POST ['braintree_save_debug_settings'] )) {
			self::saveSettings ( self::$debugLog_view, 'braintree-debug-log' );
		} elseif (isset ( $_POST ['save_braintree_webhooks'] )) {
			self::saveSettings ( self::$webhooks_view, 'braintree-webhooks-page' );
		} elseif (isset ( $_POST ['braintree_delete_debug_log'] )) {
			BT_Manager ()->deleteDebugLog ();
		} elseif (isset ( $_POST ['test_braintree_connection'] )) {
			self::saveSettings ( self::$api_view, 'braintree-payment-settings' );
			BT_Manager ()->testBraintreeConnection ( $_POST ['test_braintree_connection'] );
		}
	}
	public static function saveSettings($fields, $page) {
		$defaults = array (
				'title' => '',
				'type' => '',
				'value' => '',
				'type' => '',
				'class' => array (),
				'default' => '' 
		);
		$settings = BT_Manager ()->settings;
		$required_settings = BT_Manager ()->get_required_settings ();
		
		foreach ( $fields as $field ) {
			$value = isset ( $required_settings [$field] ) ? $required_settings [$field] : $defaults;
			$value = wp_parse_args ( $value, $defaults );
			if (is_array ( $value ['value'] ) && $value ['type'] === 'checkbox') {
				foreach ( $value ['value'] as $k => $v ) {
					$settings [$field] [$k] = isset ( $_POST [$k] ) ? $_POST [$k] : '';
				}
			} else {
				$settings [$field] = isset ( $_POST [$field] ) ? trim ( $_POST [$field] ) : '';
			}
		}
		$settings = apply_filters ( 'braintree_for_woocommerce_save_settings', $settings );
		BT_Manager ()->update_settings ( $settings );
	}
	public static function getAdminHeader() {
		include WC_BRAINTREE_PLUGIN . 'admin/meta-boxes/admin-header.php';
	}
	public static function displaySettingsPage($fields_to_display, $page, $button) {
		$form_fields = BT_Manager ()->get_required_settings ();
		$html = '<div><form method="POST" action="' . get_admin_url () . 'admin.php?page=' . $page . '">';
		$html .= '<table class="braintree-woocommerce-settings"><tbody>';
		foreach ( $fields_to_display as $key ) {
			$value = BT_Manager ()->get_required_settings () [$key];
			$value = ! empty ( $value ) ? BT_Manager ()->get_required_settings () [$key] : array ();
			$html .= HTML_Helper::buildSettings ( $key, $value, BT_Manager ()->settings );
		}
		$html .= '</tbody></table>';
		if ($button != null) {
			$html .= '<div><input name="' . $button . '" class="braintree-payments-save" type="submit" value="Save"></div>';
		}
		$html .= '</form></div>';
		echo $html;
	}
	public static function showBraintreePaymentsView() {
		self::getAdminHeader ();
		self::displaySettingsPage ( self::$api_view, 'braintree-payment-settings', 'save_braintree_payment_settings' );
	}
	public static function showWoocommerceView() {
		self::getAdminHeader ();
		if (BT_Manager ()->woocommerceActive ()) {
			if (! preg_match ( '/^US/i', get_option ( 'woocommerce_default_country' ) )) {
				$key = array_search ( 'dynamic_descriptor_phone', self::$wooCommerce_view );
				unset ( self::$wooCommerce_view [$key] );
			}
			self::displaySettingsPage ( self::$wooCommerce_view, 'braintree-woocommerce-settings', 'save_braintree_woocommerce_settings' );
		} else {
			?>
<div>
	<h2 class="braintree-warning"><?php echo __('You must download and activate the <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> Plugin before this screen is available. You can download WooCommerce <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">here</a>.', 'braintree')?></h1>

</div>
<?php
		}
	}
	public static function showDonationsView() {
		self::getAdminHeader ();
		self::displaySettingsPage ( self::$donations_view, 'braintree-donations-page', 'save_braintree_donation_settings' );
	}
	public static function showDebugView() {
		self::getAdminHeader ();
		self::displaySettingsPage ( self::$debugLog_view, 'braintree-debug-log', 'braintree_save_debug_settings' );
		?>
<form class="braintree-deletelog-form" name="braintree_woocommerce_form"
	method="post"
	action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'].'?page=braintree-debug-log') ?>">
	<button name="braintree_delete_debug_log"
		class="braintree-payments-save" type="submit">Delete Log</button>
</form>
<div class="config-separator"></div>
<div class="braintree-debug-log-container">
				<?php echo BT_Manager()->display_debugLog()?>
			</div>
<?php
	}
	public static function showLicenseView() {
		self::getAdminHeader ();
		self::displaySettingsPage ( self::$license_view, 'braintree-license-page', 'activate_braintree_license' );
	}
	public static function showSubscriptionView() {
		self::getAdminHeader ();
		if (BT_Manager ()->subscriptionsActive ()) {
			self::displaySettingsPage ( self::$subscriptions_view, 'braintree-subscriptions-page', 'save_woocommerce_subscription_settings' );
		} else {
			self::displaySettingsPage ( self::$braintree_subscriptions_view, 'braintree-subscriptions-page', 'save_braintree_subscription_settings' );
		}
	}
	public static function showWebhookView() {
		self::getAdminHeader ();
		self::displaySettingsPage ( self::$webhooks_view, 'braintree-webhooks-page', 'save_braintree_webhooks' );
	}
	public static function getLicenseStatus() {
		$html = '';
		$license_status = BT_Manager ()->get_option ( 'license_status' );
		$license_status = $license_status === 'active' ? 'Active' : 'Inactive';
		$html .= '<div class="license--' . $license_status . '"><span>' . $license_status . '</span>';
		if ($license_status === 'Inactive') {
			$html .= '<span class="braintree-purchase-license"><a target="_blank" href="https://wordpress.paymentplugins.com/product-category/braintree-plugins/">' . __ ( 'Purchase a license', 'braintree' ) . '</a></span>';
		}
		return $html;
	}
	public static function getWebhooksUrl() {
		return get_site_url () . '/braintreegateway/webhooks/notifications/';
		// return get_site_url().'/webhooks/braintree?woocommerce_braintree_subscription_hook=process';
	}
	
	/**
	 * Generate html selection element based on countries and currency.
	 *
	 * @param string $defaultCountry        	
	 */
	public static function getCountriesOptions($value) {
		$defaultCountry = BT_Manager ()->get_option ( 'donation_currency' );
		$html = '<select name="donation_currency" id="donation_currency">';
		foreach ( Braintree_Currencies::getCurrencies () as $prefix => $description ) {
			$selected = $prefix === $defaultCountry ? 'selected' : '';
			$html .= '<option value="' . $prefix . '" ' . $selected . '>' . $description . ' (' . Braintree_Currencies::getCurrencySymbol ( $prefix ) . ')</option>';
		}
		$html .= '</select>';
		return $html;
	}
	
	/**
	 * Display a list of country currencies that can be associated with the Merchant AccountID's.
	 */
	public static function displayMerchantAccounts() {
		$json = json_encode ( array (
				'merchant_text' => BT_Manager ()->getEnvironment () === 'sandbox' ? __ ( 'Sandbox Merchant Account ID (%s)', 'braintree' ) : __ ( 'Merchant Account ID (%s)', 'braintree' ),
				'merchant_account_input' => 'woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id' 
		) );
		ob_start ();
		?>
<div class="braintree-merchant-account-note">
		<?php
		echo __ ( '<strong>Note:</strong>This setting is optional. To add a merchant account, select the currency for the merchant account and then click the Add Merchant Account button. Paste the merchant account id into the 
				text field then save. If Sandbox mode is enabled, add your sandbox merchant accounts. If Production mode is enabled, add your production merchant accounts. If you use a currency switcher and sell in multiple currencies you will want to maintain this setting.', 'braintree' )?></div>
<script> var merchantParams = <?php echo $json?></script>
<table class="merchant-account-add">
	<tr>
		<th>
			<div>
				<select id="merchant_account_currency">
		        		<?php foreach (get_woocommerce_currencies() as $currency=>$description){?>
		      	  			<option value="<?php echo $currency?>"><?php echo $description .' ( '. Braintree_Currencies::getCurrencySymbol( $currency ).' )'?></option>
		        		<?php }?>
		        		</select>
			</div>
		</th>
	</tr>
	<tr>
		<td><a class="braintree-admin-button" href="#"
			id="add_merchant_account"><?php echo __('Add Merchant Account','braintree')?></a>
		</td>
	</tr>
	<table id="merchant_accounts">
		    <?php
		foreach ( get_woocommerce_currencies () as $currency => $description ) {
			$merchantAccount = BT_Manager ()->get_option ( 'woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id[' . $currency . ']' );
			if (! empty ( $merchantAccount )) {
				?>
		    		<tr>
			<th><span><?php echo BT_Manager()->getEnvironment() === 'sandbox' ? __('Sandbox Merchant Account ID', 'braintree').' ('.$currency.')' : __('Merchant Account ID', 'braintree').' ('.$currency.')'?></span>
			</th>
			<td>
				<div>
					<input
						id="woocommerce_braintree_<?php echo BT_Manager()->getEnvironment()?>_merchant_account_id[<?php echo $currency?>]"
						name="woocommerce_braintree_<?php echo BT_Manager()->getEnvironment()?>_merchant_account_id[<?php echo $currency?>]"
						type="text" value="<?php echo $merchantAccount?>" /> <span
						class="dashicons dashicons-trash"></span>
				</div>
			</td>
		</tr>
		    		<?php
			}
		}
		?>
		  </table>
</table>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	public static function saveMerchantAccounts($settings) {
		if (isset ( $_POST ['woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id'] )) {
			$array = $_POST ['woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id'];
			foreach ( $array as $prefix => $value ) {
				$messages = array ();
				$value = trim ( $value );
				
				if (! $merchantAccount = BT_Manager ()->getBraintreeMerchantAccount ( $value )) {
					BT_Manager ()->addAdminNotice ( array (
							'type' => 'error',
							'text' => sprintf ( __ ( '%s is not a valid Merchant Account ID for your Braintree %s environment. Please login to your Braintree Account and verify your Merchant Account(s).
									If you have enabled production mode in the <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payment-settings">API Settings</a> page but have not purchased a license yet, you will only be able to add 
									sandbox merchant accounts.', 'braintree' ), $value, BT_Manager ()->getEnvironment () ) 
					) );
				} else if (($merchantAccount->currencyIsoCode) && $prefix !== $merchantAccount->currencyIsoCode) {
					BT_Manager ()->addAdminNotice ( array (
							'type' => 'error',
							'text' => sprintf ( __ ( 'You have selected %s as the merchant account currency. The default currency for Merchant Account <strong>%s</strong> is <strong>%s</strong>. Please select the correct currency.', 'braintree' ), $prefix, $value, $merchantAccount->currencyIsoCode ) 
					) );
				} else
					$settings ['woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id[' . $prefix . ']'] = $value;
			}
		}
		return $settings;
	}
	public static function validateDonationsMerchantAccount($settings) {
		if (isset ( $_POST ['donation_merchant_account_id'] ) && ! empty ( $_POST ['donation_merchant_account_id'] )) {
			$value = trim ( $_POST ['donation_merchant_account_id'] );
			
			if (! $merchantAccount = BT_Manager ()->getBraintreeMerchantAccount ( $value )) {
				
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '%s is not a valid Merchant Account ID as it does not match any of your Braintree Merchant Accounts. 
						Please read our <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payments-tutorials">Tutorial</a> on how to locate your Merchant Account(s).', 'braintree' ), $value ) 
				) );
				$settings ['donation_merchant_account_id'] = null;
			} else {
				$settings ['donation_merchant_account_id'] = $value;
				if ($merchantAccount->currencyIsoCode) {
					$settings ['donation_currency'] = $merchantAccount->currencyIsoCode;
				}
			}
		}
		return $settings;
	}
	public static function testProductionConnection() {
		ob_start ();
		?>
<div class="braintree-connectionTest">
	<button class="braintree-admin-button" name="test_braintree_connection"
		value="production"><?php echo __('Test Connection', 'braintree')?></button>
</div>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	public static function testSandboxConnection() {
		ob_start ();
		?>
<div class="braintree-connectionTest">
	<button class="braintree-admin-button" name="test_braintree_connection"
		value="sandbox"><?php echo __('Test Connection', 'braintree')?></button>
</div>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	public static function deleteSetting() {
		$name = $_POST ['setting'];
		unset ( BT_Manager ()->settings [$name] );
		BT_Manager ()->update_settings ();
		wp_send_json ( array (
				'result' => 'success' 
		) );
	}
	public static function isPage($page = '') {
		$requestPage = BT_Manager ()->getRequestParameter ( 'page' );
		$page = is_array ( $page ) ? $page : array (
				$page 
		);
		return in_array ( $requestPage, $page ) || array_key_exists ( $requestPage, $page );
	}
	public static function validateDynamicDescriptorName($settings = null) {
		$value = BT_Manager ()->getRequestParameter ( 'dynamic_descriptor_name' );
		
		if (! empty ( $value )) {
			$fail = false;
			$length = strlen ( $value );
			$messages = array ();
			if ($number = preg_match_all ( '/[^\w.+\-*\s]+/', $value, $matches )) { // look for illegal characters
				$chars = '';
				foreach ( $matches [0] as $match ) {
					$chars .= $match;
				}
				$fail = true;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>The following illegal characters were used for your descriptor name: %s', 'braintree' ), $chars ) 
				) );
			}
			if (! preg_match ( '/^[^\s].{2}\*|^[^\s].{6}\*|^[^\s].{11}\*/', $value )) { // look for incorrect company name length.
				$fail = true;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an incorrect company name length. Valid values are 3, 7, and 12 characters long' ) ) 
				) );
			}
			if ($length > 22) {
				$fail = true;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>The descriptor length cannot be greater than 22 characters.', 'braintree' ) ) 
				) );
			}
			if ($fail) {
				$settings ['dynamic_descriptor_name'] = BT_Manager ()->get_option ( 'dynamic_descriptor_name' ); // Use old value since new one is no good.
				return $settings;
			} else if ($length < 22) { // add spaces to make length 22 characters.
				$diff = 22 - $length;
				for($i = 0; $i < $diff; $i ++) {
					$value .= ' ';
				}
			}
		}
		$settings ['dynamic_descriptor_name'] = $value;
		
		return $settings;
	}
	public static function saveSubscriptionDynamicDescriptor($post_id) {
		if (! isset ( $_POST ['product-type'] )) {
			return;
		}
		$value = BT_Manager ()->getRequestParameter ( 'dynamic_descriptor_name' );
		
		if (! empty ( $value )) {
			$fail = false;
			$length = strlen ( $value );
			$messages = array ();
			if ($number = preg_match_all ( '/[^\w.+\-*\s]+/', $value, $matches )) { // look for illegal characters
				$chars = '';
				foreach ( $matches [0] as $match ) {
					$chars .= $match;
				}
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>The following illegal characters were used for your descriptor name: %s', 'braintree' ), $chars ) 
				) );
				$fail = true;
			}
			if (! preg_match ( '/^[^\s].{2}\*|^[^\s].{6}\*|^[^\s].{11}\*/', $value )) { // look for incorrect company name length.
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an incorrect company name length. Valid values are 3, 7, and 12 characters long' ) ) 
				) );
				$fail = true;
			}
			if ($length > 22) {
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => sprintf ( __ ( '<strong>Dynamic Descriptor Error - </strong>The descriptor length cannot be greater than 22 characters.', 'braintree' ) ) 
				) );
				$fail = true;
			}
			if ($fail) {
				return false;
			} else if ($length < 22) { // add spaces to make length 22 characters.
				$diff = 22 - $length;
				for($i = 0; $i < $diff; $i ++) {
					$value .= ' ';
				}
			}
		}
		
		update_post_meta ( $post_id, 'dynamic_descriptor_name', $value );
	}
	
	/**
	 * Validate the dynamic descripor number entered on the WooCommerce settings page.
	 *
	 * @param unknown $settings        	
	 */
	public static function validateDynamicDescriptorPhone($settings) {
		$value = BT_Manager ()->getRequestParameter ( 'dynamic_descriptor_phone' );
		$valid = true;
		
		if (! empty ( $value )) { // validate the $value.
			$value = preg_replace ( '/\s+/', '', $value ); // Get rid of all white space as it's not allowed by Braintree.
			$messages = array ();
			
			if (preg_match ( '/[^\d(\).\-]+/', $value )) { // 10-14 characters, only digits, ().-\s
				$messages [] = __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an invalid phone number. Please click the help link for examples on valid values', 'braintree' );
				$valid = false;
			}
			if (strlen ( $value ) > 14) {
				$valid = false;
				$messages [] = __ ( '<strong>Dynamic Descriptor Error - </strong>The phone number can have a maximum of 14 characters.', 'braintree' );
			}
			if (preg_match_all ( '/[\d]+/', $value, $matches )) { // make sure there are 10 digits.
				$length = 0;
				foreach ( $matches [0] as $match ) {
					$length = $length + strlen ( $match );
				}
				if ($length != 10) {
					$settings ['dynamic_descriptor_phone'] = BT_Manager ()->get_option ( 'dynamic_descriptor_phone' );
					BT_Manager ()->addAdminNotice ( array (
							'type' => 'error',
							'text' => __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an invalid phone number. The phone number must be exactly 10 digits for US based numbers.', 'braintree' ) 
					) );
					$valid = false;
				}
			} else {
				$valid = false;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an invalid phone number. The phone number must be exactly 10 digits for US based numbers.', 'braintree' ) 
				) );
			}
			if ($valid) {
				$settings ['dynamic_descriptor_phone'] = $value;
			} else {
				$settings ['dynamic_descriptor_phone'] = BT_Manager ()->get_option ( 'dynamic_descriptor_phone' );
			}
		}
		
		return $settings;
	}
	public static function validateDynamicDescriptorUrl($settings) {
		$value = BT_Manager ()->getRequestParameter ( 'dynamic_descriptor_url' );
		$valid = true;
		if (! empty ( $value )) {
			$messages = array ();
			$value = preg_replace ( '/\s/', '', $value ); // replace any white space.
			if (preg_match ( '/[^\w.-_~:]+/', $value )) {
				$valid = false;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an invalid url. The URL can contain up to 13 characters.', 'braintree' ) 
				) );
			}
			if (strlen ( $value > 13 )) {
				$valid = false;
				BT_Manager ()->addAdminNotice ( array (
						'type' => 'error',
						'text' => __ ( '<strong>Dynamic Descriptor Error - </strong>You have entered an invalid url. The URL can contain up to 13 characters.', 'braintree' ) 
				) );
			}
			if ($valid) {
				$settings ['dynamic_descriptor_url'] = $value;
			} else {
				$settings ['dynamic_descriptor_url'] = BT_Manager ()->get_option ( 'dynamic_descriptor_url' );
			}
		}
		return $settings;
	}
	public static function displayDescriptorValidationErrors() {
		$messages = get_transient ( 'braintree_for_woocommerce_descriptor_errors' );
		if ($messages) {
			echo '<div class="notice notice-error">';
			foreach ( $messages as $message ) {
				echo '<p>' . $message . '</p>';
			}
			echo '</div>';
			
			delete_transient ( 'braintree_for_woocommerce_descriptor_errors' );
		}
	}
	public static function getDynamicDescriptorName() {
		ob_start ();
		
		$tip = __ ( 'Dynamic descriptors allow you to control what appears on a customers credit card statement on a per transaction basis. The following rules must be followed when adding a descriptor:
						<div><h4>' . __ ( 'Descriptor Name', 'braintree' ) . '</h4><ul><li>Comprised of a business name and product name, separated by an asterisk (*).
						<li>Business name must be either 3, 7, or 12 characters exactly; product descriptor can be up to 18, 14, or 9 characters respectively (with an * in between for a total of 22 characters) If the company name plus the product name and asterick does not add up to
						22 characters, that is fine. The plugin will automatically add spaces for a total length of 22 characters.</li>
						<li>Can contain special characters . + - </li>
						<li>Can contain lower and upper case</li>
						<li>Can contain spaces, but cannot start with a space</li></ul></div>', 'braintree' );
		$examples = '<div><h4>' . __ ( 'Examples:', 'braintree' ) . '</h4><div>' . __ ( 'cool company*towels   ', 'braintree' ) . '</div><div>' . __ ( 'PaymntPlugin*braintree', 'braintree' ) . '</div></h4></div>';
		
		echo '<div id="dynamic_descriptor_tutorial_name" class="dynamic_descriptor_tutorial"><div class="explanation"><h4>' . __ ( 'Dynamic Descriptors Explanation', 'braintree' ) . '</h4><span class="close">' . __ ( 'Close', 'braintree' ) . '</span>' . $tip . $examples . '</div></div>';
		?>
<div class="dynamic_descriptor">
	<input type="text" id="dynamic_descriptor_name"
		name="dynamic_descriptor_name" maxlength="22"
		value="<?php echo BT_Manager()->get_option( 'dynamic_descriptor_name' )?>" />
	<span tutorial="_name" class="dynamic_descriptor_help"><?php echo __('help', 'braintree')?></span>
</div>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	
	/**
	 * Return the html for the dynamic descriptor number field.
	 */
	public static function getDynamicDescriptorPhone() {
		ob_start ();
		
		$tip = __ ( 'Dynamic descriptors allow you to control what appears on a customers credit card statement on a per transaction basis. The following rules must be follwed when adding a descriptor:
						<div><h4>' . __ ( 'Descriptor Phone', 'braintree' ) . '</h4><ul><li>Must contain exactly 10 digits.</li>
						<li>Can contain up to 14 characters total, including special characters.</li>
						<li>Can contain special characters . ( ) -</li>
						</ul></div>', 'braintree' );
		$examples = '<div><h4>' . __ ( 'Examples:', 'braintree' ) . '</h4><div>' . __ ( '(512)567-4545', 'braintree' ) . '</div><div>' . __ ( '877-434-2894', 'braintree' ) . '</div></h4></div>';
		
		echo '<div id="dynamic_descriptor_tutorial_phone" class="dynamic_descriptor_tutorial"><div class="explanation"><h4>' . __ ( 'Dynamic Descriptors Explanation', 'braintree' ) . '</h4><span class="close">' . __ ( 'Close', 'braintree' ) . '</span>' . $tip . $examples . '</div></div>';
		?>
<div class="dynamic_descriptor">
	<input type="text" id="dynamic_descriptor_phone"
		name="dynamic_descriptor_phone" maxlength="14"
		value="<?php echo BT_Manager()->get_option( 'dynamic_descriptor_phone')?>" />
	<span tutorial="_phone" class="dynamic_descriptor_help"><?php echo __('help', 'braintree')?></span>
</div>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	
	/**
	 * Return the html for the dynamic descriptor number field.
	 */
	public static function getDynamicDescriptorUrl() {
		ob_start ();
		
		$tip = __ ( 'Dynamic descriptors allow you to control what appears on a customers credit card statement on a per transaction basis. The following rules must be follwed when adding a descriptor:
						<div><h4>' . __ ( 'Descriptor URL', 'braintree' ) . '</h4>
						<ul><li>The Url can be 13 or fewer characters.</li></ul></div>', 'braintree' );
		
		$examples = '<div><h4>' . __ ( 'Examples:', 'braintree' ) . '</h4><div>' . __ ( 'example.com', 'braintree' ) . '</div><div>' . __ ( 'mywebsite.com', 'braintree' ) . '</div></h4></div>';
		
		echo '<div id="dynamic_descriptor_tutorial_url" class="dynamic_descriptor_tutorial"><div class="explanation"><h4>' . __ ( 'Dynamic Descriptors Explanation', 'braintree' ) . '</h4><span class="close">' . __ ( 'Close', 'braintree' ) . '</span>' . $tip . $examples . '</div></div>';
		?>
<div class="dynamic_descriptor">
	<input type="text" id="dynamic_descriptor_url"
		name="dynamic_descriptor_url" maxlength="13"
		value="<?php echo BT_Manager()->get_option( 'dynamic_descriptor_url')?>" />
	<span tutorial="_url" class="dynamic_descriptor_help"><?php echo __('help', 'braintree')?></span>
</div>
<?php
		$html = ob_get_clean ();
		return $html;
	}
	public static function displayAdminNotices() {
		$messages = BT_Manager ()->getAdminNotices ();
		if ($messages) {
			foreach ( $messages as $message ) {
				?>
<div class="notice notice-<?php echo $message['type']?>">
	<p><?php echo $message['text']?></p>
</div>
<?php
			}
		}
		BT_Manager ()->deleteAdminNotices ();
	}
	public static function displayPayPalOptions() {
		ob_start ();
		include WC_BRAINTREE_PLUGIN . 'admin/meta-boxes/paypal-design-options.php';
		return ob_get_clean ();
	}
	public static function display_paypal_designs() {
		ob_start ();
		$options = BT_Manager ()->get_paypal_design_options ();
		foreach ( $options as $index => $option ) {
			echo '<div class="paypalDesignOption">';
			echo '<input type="radio" name="paypal_button_design" value="' . $index . '"' . checked ( $index, BT_Manager ()->get_option ( 'paypal_button_design' ), false ) . '/>';
			include $option ['html'];
			echo '</div>';
		}
		return ob_get_clean ();
	}
	public static function save_paypal_options($settings) {
		$options = BT_Manager ()->get_paypal_options ();
		foreach ( $options as $key => $option ) {
			$settings [$key] = isset ( $_POST [$key] ) ? trim ( $_POST [$key] ) : '';
		}
		return $settings;
	}
	public static function display_custom_form_design() {
		ob_start ();
		include WC_BRAINTREE_PLUGIN . 'admin/meta-boxes/custom-form-design.php';
		return ob_get_clean ();
	}
	public static function output_custom_forms() {
		ob_start ();
		$forms = BT_Manager ()->get_custom_forms ();
		echo '<select name="custom_form_design" id="custom_form_design">';
		foreach ( $forms as $name => $form ) {
			echo '<option value="' . $name . '"' . selected ( BT_Manager ()->get_option ( 'custom_form_design' ), $name ) . '>' . $form ['description'] . '</option>';
		}
		echo '</select>';
		return ob_get_clean ();
	}
	
	/**
	 * Save the custom form options and validate the entries.
	 *
	 * @param array $settings        	
	 */
	public static function save_custom_form_settings($settings) {
		$options = BT_Manager ()->get_custom_form_options ();
		foreach ( $options as $key => $option ) {
			$value = isset ( $_POST [$key] ) ? stripslashes ( $_POST [$key] ) : '';
			if ($key === 'custom_form_styles') {
				if (! empty ( $value )) {
					$array = json_decode ( $value, true );
					if (! $array) {
						BT_Manager ()->addAdminNotice ( array (
								'type' => 'error',
								'text' => sprintf ( __ ( 'The field %s is not formatted properly for JSON. Reason: %s.', 'braintree' ), $key, json_last_error_msg () ) 
						) );
					} else {
						$settings ['custom_form_' . $_POST ['custom_form_design'] . '_styles'] = $value;
					}
				} else {
					$settings ['custom_form_' . $_POST ['custom_form_design'] . '_styles'] = '';
				}
			} else if ($key === 'custom_form_loader_css') {
				if (! empty ( $value )) {
					$array = json_decode ( $value, true );
					if (! $array) {
						BT_Manager ()->addAdminNotice ( array (
								'type' => 'error',
								'text' => sprintf ( __ ( 'The field %s is not formatted properly for JSON. Reason: %s.', 'braintree' ), $key, json_last_error_msg () ) 
						) );
					} else {
						$settings [$key] = $value;
					}
				} else {
					$settings [$key] = '';
				}
			} else {
				$settings [$key] = $value;
			}
		}
		return $settings;
	}
	
	/**
	 * Output the html for the postal code custom form field setting.
	 */
	public static function display_postal_field_option() {
		ob_start ();
		echo '<div class="braintree-admin-settings-note">';
		echo __ ( '<strong>Note:</strong> If you have enabled postal code verification within your Braintree account AVS settings, 
				be sure that you enable the postal code field for the custom form.', 'braintree' );
		echo '</div>';
		echo '<div class="braintree-settings-div"><input type="checkbox" id="postal_field_enabled" name="postal_field_enabled" value="yes" ' . checked ( BT_Manager ()->get_option ( 'postal_field_enabled' ), 'yes', false ) . '/></div>';
		return ob_get_clean ();
	}
	
	/**
	 * output the html for the cvv custom form field setting.
	 */
	public static function display_cvv_field_option() {
		ob_start ();
		echo '<div class="braintree-admin-settings-note">';
		echo __ ( '<strong>Note:</strong> If you have enabled cvv validations within your Braintree account settings,
				be sure that you enable the cvv field for the custom form.', 'braintree' );
		echo '</div>';
		echo '<div class="braintree-settings-div"><input type="checkbox" id="cvv_field_enabled" name="cvv_field_enabled" value="yes" ' . checked ( BT_Manager ()->get_option ( 'cvv_field_enabled' ), 'yes', false ) . '/></div>';
		return ob_get_clean ();
	}
	public static function localize_admin_settings() {
		$settings = array (
				'environment' => BT_Manager ()->getEnvironment (),
				'license_status' => strtolower ( BT_Manager ()->get_option ( 'license_status' ) ),
				'license_required_message' => __ ( 'License is required', 'braintree' ) 
		);
		$settings ['custom_forms'] = array (
				'forms' => array () 
		);
		foreach ( BT_Manager ()->get_custom_forms () as $form_id => $form ) {
			$value = BT_Manager ()->get_option ( 'custom_form_' . $form_id . '_styles' );
			$settings ['custom_forms'] [$form_id] = array (
					'styles' => ! empty ( $value ) ? $value : $form ['default_styles'] 
			);
		}
		return $settings;
	}
	
	/**
	 * Output the html for the meta box that contains tehe order settlement fields.
	 */
	public static function output_wc_meta_box_settlement($post) {
		include WC_BRAINTREE_PLUGIN . 'admin/meta-boxes/wc-admin-order-settlement.php';
	}
}
WC_Braintree_Admin::init ();