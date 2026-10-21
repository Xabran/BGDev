<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

/* Auto Load all classes. */
spl_autoload_register ( function ($className = '') {
	$class = $className . '.php';
	if (file_exists ( WC_BRAINTREE_ADMIN_CLASSES . $class )) {
		require_once WC_BRAINTREE_ADMIN_CLASSES . $class;
	} else if (file_exists ( WC_BRAINTREE_CLASSES . $class )) {
		require_once WC_BRAINTREE_CLASSES . $class;
	} else if (file_exists ( WC_BRAINTREE_PLUGIN . 'services/' . $class )) {
		require_once WC_BRAINTREE_PLUGIN . 'services/' . $class;
	}
} );

/**
 * ****Admin Classes*****
 */
require_once (WC_BRAINTREE_ADMIN_CLASSES . 'Braintree_DebugLog.php');
require_once (WC_BRAINTREE_ADMIN_CLASSES . 'Braintree_Manager.php');
include_once (WC_BRAINTREE_ADMIN_CLASSES . 'HTML_Helper.php');
require_once (WC_BRAINTREE_ADMIN_CLASSES . 'WC_Braintree_Admin.php');
require_once (WC_BRAINTREE_ADMIN_CLASSES . 'WCS_BT_Meta_Box.php');
require_once (WC_BRAINTREE_ADMIN_CLASSES . 'Braintree_Install.php');
require_once (WC_BRAINTREE_PLUGIN . 'payments/functions/wc-braintree-functions.php');
/**
 * ****Payment Classes*****
 */
require_once (WC_BRAINTREE_CLASSES . 'Braintree_PaymentMethods.php');
require_once (WC_BRAINTREE_CLASSES . 'Braintree_Donations.php');
require_once (WC_BRAINTREE_PLUGIN . 'services/Braintree_Webhooks.php');
add_action ( 'plugins_loaded', function () {
	if (class_exists ( 'WC_Payment_Gateway' )) {
		require_once (WC_BRAINTREE_CLASSES . 'WC_Braintree_Payments.php');
		require_once (WC_BRAINTREE_CLASSES . 'WC_Braintree_Subscriptions.php');
		require_once (WC_BRAINTREE_CLASSES . 'WC_PayPal_Payments.php');
	}
} );

	
