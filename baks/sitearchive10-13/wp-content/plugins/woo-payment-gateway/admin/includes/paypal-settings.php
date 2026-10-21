<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

return array (
		'enable_paypal' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => 'yes',
				'title' => __ ( 'PayPal Enabled', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled custom forms and want to use PayPal as a payment option you must select this option. PayPal will be treated as a separate gateway. You must ensure that you have linked your PayPal and Braintree accounts.', 'braintree' ) 
		),
		'paypal_gateway_title' => array (
				'type' => 'text',
				'value' => '',
				'default' => __ ( 'PayPal', 'braintree' ),
				'title' => __ ( 'PayPal Title', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value here determines the text that appears on the checkout page next to the PayPal gateway.', 'braintree' ) 
		),
		'paypal_checkout_flow' => array (
				'type' => 'select',
				'value' => '',
				'default' => 'checkout',
				'title' => __ ( 'Checkout With PayPal', 'braintree' ),
				'options' => array (
						'checkout' => __ ( 'Checkout Flow', 'braintree' ),
						'vault' => __ ( 'Vault Flow', 'braintree' ) 
				),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'This options allows you to set the checkout flow for PayPal. Vault flow is a shorter version of the checkout flow and will require your customerst to grant your merchant site authorization.
						The checkout flow is the standard checkout options for PayPal. If you are using subscriptions, the vault flow will default regardless of this setting as subscriptions require a billing agreement which
						the vault flow grants.', 'braintree' ) 
		),
		'paypal_display_name'=>array(
				'type'=>'text',
				'default'=>get_option('blogname'),
				'title'=>__('PayPal Display Name', 'braintree'),
				'class'=>array(),
				'value'=>'',
				'tool_tip'=>true,
				'description'=>__('This is the name that will appear on the PayPal checkout screen if the customer chooses to pay with PayPal.', 'braintree')
		),
		'paypal_button_design' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::display_paypal_designs',
				'title' => __ ( 'PayPal Button Design', 'braintree' ),
				'class' => array (),
				'value' => '',
				'default' => 2,
				'tool_tip' => true,
				'description' => __ ( 'This option allows you to select the PayPal button design that you want to be displayed on the checkout page. If you want to
						create your own PayPal button, follow the tutorials.', 'braintree' ) 
		) 
);