<?php
/*
 * Plugin Name: Braintree For WooCommerce
 * Plugin URI: https://wordpress.paymentplugins.com
 * Description: Sell your WooCommerce products and subscriptions or accept donations using your Braintree Account. SAQ A compliant.
 * Version: 2.4.8
 * Author: Clayton Rogers, mr.clayton@paymentplugins.com
 * Author URI: https://wordpress.paymentplugins.com/braintree-documentation/
 * Tested up to: 4.6.1
 */
if (version_compare ( PHP_VERSION, '5.4', '<' )) {
	add_action ( 'admin_notices', function () {
		?>
		<div class="notice notice-error">
			<p style="font-size: 16px"><?php
		
				echo sprintf ( __ ( 'Your PHP Version is %s but Braintree requires PHP Version 5.4 or greater. Please update
				your PHP Version to use Braintree For WooCommerce.', 'braintree' ), PHP_VERSION );
			?>
			</p>
		</div>
<?php
	} );
	return;
}

define ( 'WC_BRAINTREE_CLASSES', plugin_dir_path ( __FILE__ ) . 'payments/classes/' );
define ( 'WC_BRAINTREE_ASSETS', plugin_dir_url ( __FILE__ ) . 'assets/' );
define ( 'WC_BRAINTREE_PLUGIN', plugin_dir_path ( __FILE__ ) );
define ( 'WC_BRAINTREE_ADMIN_CLASSES', plugin_dir_path ( __FILE__ ) . 'admin/classes/' );
define ( 'BRAINTREE_LICENSE_ACTIVATION_URL', 'https://wordpress.paymentplugins.com/' );
define ( 'BRAINTREE_LICENSE_VERIFICATION_KEY', 'gTys$hsjeScg63dDs35JlWqbx7h' );
define ( 'BRAINTREE_DROPIN_JS', 'https://js.braintreegateway.com/js/braintree-2.28.0.min.js' );
define ( 'BRAINTREE_3DS_JS', 'https://js.braintreegateway.com/web/3.0.2/js/three-d-secure.min.js' );
define ( 'BRAINTREE_V3_JS', 'https://js.braintreegateway.com/web/3.0.2/js/client.min.js' );
define ( 'BRAINTREE_V3_HOSTED_JS', 'https://js.braintreegateway.com/web/3.0.2/js/hosted-fields.min.js' );
define ( 'BRAINTREE_PAYPAL_JS', 'https://js.braintreegateway.com/web/3.0.2/js/paypal.min.js' );
require_once (WC_BRAINTREE_PLUGIN . 'Braintree.php');
require_once (WC_BRAINTREE_PLUGIN . 'class-loader.php');
