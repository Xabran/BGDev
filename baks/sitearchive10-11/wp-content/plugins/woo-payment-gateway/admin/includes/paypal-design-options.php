<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

return apply_filters ( 'braintree_paypal_design_options', array (
		0 => array (
				'css' => WC_BRAINTREE_ASSETS . 'css/paypal/paypal-button-pill.css',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/paypal/buttons/paypal-button-pill.php' 
		),
		1 => array (
				'css' => WC_BRAINTREE_ASSETS . 'css/paypal/paypal-button-standard.css',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/paypal/buttons/paypal-button-standard.php' 
		),
		2 => array (
				'css' => WC_BRAINTREE_ASSETS . 'css/paypal/paypal-button-yellow.css',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/paypal/buttons/paypal-button-yellow.php' 
		),
		3 => array (
				'css' => WC_BRAINTREE_ASSETS . 'css/paypal/paypal-button-logo_h.css',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/paypal/buttons/paypal-button-logo_h.php' 
		) 
) );