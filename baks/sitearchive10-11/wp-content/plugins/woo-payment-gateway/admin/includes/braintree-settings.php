<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}
/**
 * Returns an array contain the settings used for configuring the plugin.
 */
return array (
		'apisettings_title' => array (
				'type' => 'title',
				'title' => __ ( 'API Settings', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'If you have enabled sandbox mode and want to test transactions, you must use <a target="_blank" href="https://developers.braintreepayments.com/reference/general/testing/php#credit-card-numbers">Braintree Test Cards</a>.<div class="braintree-plugin-questions"><strong>Have Questions?</strong> Our detailed <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payments-tutorials">tutorials</a> and <a target="_blank" href="https://wordpress.paymentplugins.com/braintree-documentation/">documentation</a> can help answer your questions on how to configure the plugin.</div>', 'braintree' ) 
		),
		'woocommerce_title' => array (
				'type' => 'title',
				'title' => __ ( 'WooCommerce Settings', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'If you have enabled sandbox mode and want to test transactions, you must use <a target="_blank" href="https://developers.braintreepayments.com/reference/general/testing/php#credit-card-numbers">Braintree Test Cards</a>.<div class="braintree-plugin-questions"><strong>Have Questions?</strong> Our detailed <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payments-tutorials">tutorials</a> and <a target="_blank" href="https://wordpress.paymentplugins.com/braintree-documentation/">documentation</a> can help answer your questions on how to configure the plugin.</div> ' ) 
		),
		'debug_title' => array (
				'type' => 'title',
				'title' => __ ( 'Debug Log', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'You can ebable debug mode from this page. By enabling debug mode, you can view messages related to transactions within the plugin. 
						This can be helpful when troublshooting payment issues.', 'braintree' ) 
		),
		'license_title' => array (
				'type' => 'title',
				'title' => __ ( 'License Activation', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'You can activate your license from this page. Once you have purchased a license from <a target="_blank" href="https://wordpress.paymentplugins.com">Payment Plugins</a>
						 you can activate it here. Enter the license key you receive in your order in the license field and click save. If the license activation is not successful, check the debug log for a detailed error message.', 'braintree' ) 
		),
		'subscriptions_title' => array (
				'type' => 'title',
				'title' => __ ( 'Subscriptions', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'There are two configurable subscription modes with this plugin. You must have WooCoomerce subscriptions enabled in order to accept subscription payments. 
						<div class="div--subscriptionExplanation"><span>Option 1. Braintree Subscriptions</span></div>
						<div>If this mode is enabled, a subscription will be created within Braintree\'s system during checkout. The subscription payment will be charged on the cycle that you configure within the Braintree admin panel. This option is best suited for 
						merchants that want Braintree to handle the subscription payment automatically. If you choose this option, you must ensure you have created a Recurring Billing Plan inside the Braintree admin panel. Once the Recurring Billing Plan has been created, 
						you can assign the plan to your WooCommerce products.</div>
						<div class="div--subscriptionExplanation"><span>Option 2. WooCommerce Subscriptions</span></div>
						<div>If this mode is enabled, subscription payments will be handled by the WooCommerce Subscription plugin. During checkout, the customer\'s payment method will be tokenized, and the token will be saved in your database. When it is time for the subscription 
						payment to be charged, the WooCommerce subscription plugin will use the payment token to run the transaction. This options is best suited for merchants that have complex subscription options such as daily, weekly, or bi-weekly subscription plans.</div>
						<div class="braintree-plugin-questions"><strong>Have Questions?</strong> Our detailed <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payments-tutorials">tutorials</a> and <a target="_blank" href="https://wordpress.paymentplugins.com/braintree-documentation/">documentation</a> can help answer your questions on how to configure the plugin', 'braintree' ) 
		),
		'braintree_subscriptions_title' => array (
				'type' => 'title',
				'title' => __ ( 'Subscriptions', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'On this page, you can configure the settings for Subscriptions. In order to sell a subscription, you must have enabled the WooCommerce plugin. Once you create a PlanId in Braintree\'s system, you will be able to assign that planId to your
						product. On the <a target="_blank" href="' . admin_url () . 'admin.php?page=braintree-payments-tutorials' . '">Tutorials Page</a> you can find detailed steps on how to create a PlanId in Braintree\'s system and assign it to your product.', 'braintree' ) 
		),
		'donations_title' => array (
				'type' => 'title',
				'title' => __ ( 'Donations Settings', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'On this page you can configure the settings for your donation form. By using short code <span>[braintree_donations]</span> on any of your pages, you can accept 
						donations. If you would like to set pre-configured donation amounts, you can add them to the short code. <strong>Example:</strong> <strong>[braintree_donations 1="5" 2="10" 3="15"]</strong> will add a drop down on the payment form and allow the donor to select from those three amounts. If you only want
						one amount to be possible then simply write the shortcode as <strong>[braintree_donations 1="50"]</strong>. By doing this, you can actually sell products using the donation functionality.
						<div>To setup recurring donations, you need to create a Braintree Plan and then add the name of each donation plan that you want to be available. <strong>Example:</strong> <strong>[braintree_recurring_donation 1="my_plan_id" 2="my_2nd_plan"]</strong></div>', 'braintree' ) 
		),
		'webhooks_title' => array (
				'type' => 'title',
				'title' => __ ( 'Braintree Webhooks', 'braintree' ),
				'class' => array (),
				'value' => '',
				'description' => __ ( 'If you have enabled Braintree Webhooks, then your site will be configured to receive messages from Braintree pertaining to subscriptions. For example,
						if webhooks are enabled and a customer\'s subscription payment fails, Braintree will send a message to your site indicating the subscription that failed. The plugin will then perform 
						the necessary actions based on Braintree\'s message. All webhooks are based on the Braintree Subscription option being enabled. If you have enabled WooCommerce Subscriptions, then there is no need for webhooks. 
						In order for webhooks to work, you must add a webhook url within your braintree account. Login to your <a target="_blank" href="https://braintreegateway.com/login">Braintree Account</a> and navigate to 
						<span>Settings > Webhooks</span> and click <span>Create a New Webhook</span>. Copy and paste the following url into the <span>Destination URL*</span> field. 
						<input class="input--webhookUrl" type="text" value="' . WC_Braintree_Admin::getWebhooksUrl () . '"/>', 'braintree' ) 
		),
		'license_status' => array (
				'type' => 'custom',
				'title' => __ ( 'License Status', 'braintree' ),
				'value' => '',
				'default' => 'Inactive',
				'class' => array (),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::getLicenseStatus',
				'description' => __ ( 'Once you have purchased a license from <a href="https://wordpress.paymentplugins.com">Payment Plugins</a> you will
						be provided with a license key. You can active the license on the license activate page and this will allow you to configure the 
						production mode of the plugin. ', 'braintree' ) 
		),
		'license_status_notice' => array (
				'type' => 'custom',
				'title' => __ ( 'License Status', 'braintree' ),
				'value' => '',
				'default' => 'Inactive',
				'class' => array (),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::getLicenseStatus',
				'description' => __ ( 'Once you have purchased a license from <a href="https://wordpress.paymentplugins.com">Payment Plugins</a> you will
						be provided with a license key. You can active the license on the license activate page and this will allow you to configure the
						production mode of the plugin. ', 'braintree' ) 
		),
		'license' => array (
				'type' => 'text',
				'value' => '',
				'default' => '',
				'title' => __ ( 'License Number', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'This field contains the value of your license key if you have purchased one. It will appear once you have 
						activated your license.', 'braintree' ) 
		),
		'enabled' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => 'yes',
				'title' => __ ( 'Enable Braintree Payments', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If Braintree Payments is enabled, you can process credit cards and PayPal payments.', 'braintree' ) 
		),
		'production_environment' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Enable Production Mode', 'braintre' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'This setting will enable the prodution mode. You must purchase a license and
						activate your key to maintain this setting. You can purchase a license at 
						<a target="_blank" href="https://wordpress.paymentplugins.com/product-category/braintree-plugins/">Payment Plugins</a>.', 'braintree' ) 
		),
		'sandbox_environment' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Enable Sandbox Mode', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'This setting will enable sandbox mode. In sandbox mode you can test your integration. If enabled, your sandbox
						API keys will be used to communicate with the <a href="https://sandbox.braintreegateway.com">Braintree Sandbox Environment</a>.', 'braintree' ) 
		),
		'production_merchant_id' => array (
				'type' => 'text',
				'title' => __ ( 'Production Merchant ID', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'Your Merchant ID is used to identify you within Braintree\'s environment. You can find your merchant Id by logging 
						into the <a target="_blank" href="https://braintreegateway.com/login">Production Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						> <span>View Authorizations</span> button.', 'braintree' ) 
		),
		'production_private_key' => array (
				'type' => 'password',
				'title' => __ ( 'Production Private Key', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'privateKey' 
				),
				'tool_tip' => true,
				'description' => __ ( 'The private key is used by Braintree to authenticate each request. You should not share this key with anyone. You can find your merchant Id by logging
						into the <a target="_blank" href="https://braintreegateway.com/login">Production Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						<span>View Authorizations</span>', 'braintree' ) 
		),
		'production_public_key' => array (
				'type' => 'text',
				'title' => __ ( 'Production Public Key', 'braintree' ),
				'value' => '',
				'default' => '',
				// 'disabled'=>true,
				'class' => array (
						'publicKey' 
				),
				'tool_tip' => true,
				'description' => __ ( 'The public key is used by Braintree to authenticate each request. You can find your merchant Id by logging
						into the <a target="_blank" href="https://braintreegateway.com/login">Production Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						> <span>View Authorizations</span>', 'braintree' ) 
		),
		'sandbox_merchant_id' => array (
				'type' => 'text',
				'title' => __ ( 'Sandbox Merchant ID', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'Your Merchant ID is used to identify you within Braintree\'s environment. You can find your merchant Id by logging
						into the <a target="_blank" href="https://sandbox.braintreegateway.com/login">Sandbox Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						> <span>View Authorizations</span>', 'braintree' ) 
		),
		'sandbox_private_key' => array (
				'type' => 'password',
				'title' => __ ( 'Sandbox Private Key', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'privateKey' 
				),
				'tool_tip' => true,
				'description' => __ ( 'The private key is used by Braintree to authenticate each request. You should not share this key with anyone. You can find your merchant Id by logging
						into the <a target="_blank" href="https://sandbox.braintreegateway.com/login">Sandbox Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						> <span>View Authorizations</span>', 'braintree' ) 
		),
		'sandbox_public_key' => array (
				'type' => 'text',
				'title' => __ ( 'Sandbox Public Key', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'publicKey' 
				),
				'tool_tip' => true,
				'description' => __ ( 'The public key is used by Braintree to authenticate each request. You can find your merchant Id by logging
						into the <a target="_blank" href="https://sandbox.braintreegateway.com/login">Sandbox Environment</a> and navigating to <span>Acount</span> > <span>My User</span>
						> <span>View Authorizations</span>', 'braintree' ) 
		),
		'title_text' => array (
				'type' => 'text',
				'title' => __ ( 'Title Text', 'braintree' ),
				'value' => '',
				'default' => 'Braintree Payments',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The title text is the text that will be displayed on the checkout page. Common values are Credit Card / PayPal.', 'braintree' ) 
		),
		'order_status' => array (
				'type' => 'select',
				'title' => __ ( 'Order Status', 'braintree' ),
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-completed',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'This is the order status assigned to the order once the payment has been processed by Braintree. Most merchants
						use processing or complete.', 'braintree' ) 
		),
		'order_prefix' => array (
				'type' => 'text',
				'title' => __ ( 'Order Prefix', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The order prefix is prepended to the WooCommerce order id and will appear within Braintree as the Order ID. This settings can be helpful if you want to distinguish
						orders that came from this particular site or plugin.', 'braintree' ) 
		),
		'payment_methods' => array (
				'type' => 'checkbox',
				'value' => Braintree_PaymentMethods::paymentMethods (),
				'default' => '',
				'title' => __ ( 'Display Payment Methods', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If you want to display an image of the payment methods on the
						checkout page that your Braintree account accepts, select the checkboxes.', 'braintree' ) 
		),
		'paypal_only' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Only Allow PayPal', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If you only want to use this plugin to accept payments, enable it here. In addition to this setting, you 
						must make sure you enabled PayPal from within the Braintree Control Panel.', 'braintree' ) 
		),
		'enable_debug' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => 'yes',
				'title' => __ ( 'Enable Debug Mode', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If you need to troubleshoot payment transactions, enable debug mode. You can view the debug messages on this page.', 'braintree' ) 
		),
		'woocommerce_subscriptions' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'WooCommerce Subscriptions', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'Enable this mode if you want the WooCommerce Subscriptions plugin to handle the subscription payment.', 'braintree' ) 
		),
		'braintree_subscriptions' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Braintree Subscriptions', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'Enable this mode if you want the subscription to be creaetd in the Braintree system and handled automatically by Braintree. When this setting is enabled,
						you must create a planId in Braintree and assign the planId to your subscription products.', 'braintree' ) 
		),
		'braintree_subscriptions_charge_failed' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-pending-cancel',
				'title' => __ ( 'Status - Payment Failed', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription charge is unsuccesful. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_charge_success' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-active',
				'title' => __ ( 'Status - Payment Success', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription charge is successful. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a target="_blank" href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_active' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-active',
				'title' => __ ( 'Status - Subscription Active', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription has gone active. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a target="_blank" href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_expired' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-expired',
				'title' => __ ( 'Status - Subscription Expired', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription has expired. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a target="_blank" href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_past_due' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-pending-cancel',
				'title' => __ ( 'Status - Subscription Past Due', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription has gone past due. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a target="_blank" href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_cancelled' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-cancelled',
				'title' => __ ( 'Status - Subscription Cancelled', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the WooCommerce Subscription object when the subscription has been cancelled. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a target="_blank" href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_subscriptions_mixed_cart' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Mixed Shopping Cart', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'Enable this setting if you want to allow products and subscriptions to be added to the shopping cart at the same time when there is a 
						Braintree Subscription in the cart.', 'braintree' ) 
		),
		'subscriptions_payment_success_status' => array (
				'type' => 'select',
				'options' => array (
						'wc-pending' => _x ( 'Pending', 'Subscription status', 'woocommerce-subscriptions' ),
						'wc-active' => _x ( 'Active', 'Subscription status', 'woocommerce-subscriptions' ),
						'wc-on-hold' => _x ( 'On hold', 'Subscription status', 'woocommerce-subscriptions' ) 
				),
				'default' => 'wc-active',
				'title' => __ ( 'Subscription Status - Payment Success', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'This setting determines the status of the subscription when the payment is processed during checkout.', 'braintree' ) 
		),
		'subscriptions_payment_failed_status' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getSubscriptionStatuses (),
				'default' => 'wc-on-hold',
				'title' => __ ( 'Subscription Status - Payment Failed', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'This setting determines the status of the subscription when the payment fails during a recurring payment.
						<span class="example">Example:If you want the customer\'s subscription to be cancelled if their payment fails during a recurring payment, set the status to cancelled.</span>', 'braintree' ) 
		),
		'woocommerce_subscriptions_prefix' => array (
				'type' => 'text',
				'value' => '',
				'default' => '',
				'title' => __ ( 'Subscription Prefix', 'braintree' ),
				'class' => array (
						'subItem',
						'prefixInput' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you would like the subscription orderId to contain an order prefix, you can add one here. If left blank, the order id within Braintree
						will match the WooCommerce order id.', 'braintree' ) 
		),
		'donation_button_design' => array (
				'type' => 'title',
				'title' => __ ( 'Button Design Settings', 'braintree' ),
				'class' => array (
						'h1--DonationButtonDesign' 
				),
				'value' => '',
				'description' => __ ( 'You can configure the look and feel of your donation button with the following settings.', 'braintree' ) 
		),
		'donation_form_layout' => array (
				'type' => 'select',
				'value' => array (
						'modal',
						'inline' 
				),
				'default' => 'modal',
				'title' => __ ( 'Form Layout', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The form layout has two options. If modal is selected, the form will appear as a popup when the donator
						clicks the donation button. If inline is selected, the donation form will appear inside the html of the page.', 'braintree' ) 
		),
		'donation_button_text' => array (
				'type' => 'text',
				'value' => 'Donate',
				'default' => __ ( 'Donate', 'braintree' ),
				'title' => __ ( 'Button Text', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the text that appears on the donation button by entering the text here.', 'braintree' ) 
		),
		'donation_button_background' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#61D395',
				'title' => __ ( 'Background Color', 'braintree' ),
				'class' => array (
						'donationColor' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the background color of the donation button by selecting a color from the color picker.', 'braintree' ) 
		),
		'donation_button_border' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#61D395',
				'title' => __ ( 'Border Color', 'braintree' ),
				'class' => array (
						'donationColor' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the border color of the donation button by selecting a color from the color picker.', 'braintree' ) 
		),
		'donation_button_text_color' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#ffffff',
				'title' => __ ( 'Text Color', 'braintree' ),
				'class' => array (
						'donationColor' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the text color of the button.', 'braintree' ) 
		),
		'donation_modal_button_text' => array (
				'type' => 'text',
				'value' => 'Some Text',
				'default' => __ ( 'Donate', 'braintree' ),
				'title' => __ ( 'Modal Button Text', 'braintree' ),
				'class' => array (
						'subItem',
						'modalOption' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled the modal donation form, you can control the text that is displayed for the button that when clicked displays
						the modal donation form.', 'braintree' ) 
		),
		'donation_modal_button_background' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#61D395',
				'title' => __ ( 'Background Color', 'braintree' ),
				'class' => array (
						'donationColor',
						'subItem',
						'modalOption' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the the background color of the modal donation button by selecting a color from the color picker.', 'braintree' ) 
		),
		'donation_modal_button_border' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#61D395',
				'title' => __ ( 'Border Color', 'braintree' ),
				'class' => array (
						'donationColor',
						'subItem',
						'modalOption' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the border color of the modal donation button by selecting a color from the color picker.', 'braintree' ) 
		),
		'donation_modal_button_text_color' => array (
				'type' => 'text',
				'value' => '#61D395',
				'default' => '#ffffff',
				'title' => __ ( 'Text Color', 'braintree' ),
				'class' => array (
						'donationColor',
						'subItem',
						'modalOption' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can customize the color of the text with this option.', 'braintree' ) 
		),
		'donation_address' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Capture Billing Address', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, the donation form will have address fields for capturing the donor\'s billing address.', 'braintree' ) 
		),
		'donation_email' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Capture Email', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, the donation form will have a field for capturing the donor\'s email address. If you have enabled email receipts from within Braintree,
						then this is a required setting.', 'braintree' ) 
		),
		'donation_name' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Capture Donor Name', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, the donation form will have fields for capturing the donor\'s name.', 'braintree' ) 
		),
		'donation_success_url' => array (
				'type' => 'text',
				'value' => '',
				'default' => '',
				'title' => __ ( 'Success URL', 'braintree' ),
				'class' => array (
						'donation-url' 
				),
				'tool_tip' => true,
				'description' => __ ( 'Enter the url of the page/site you would like the customer to be redirected to after the donation.', 'braintree' ) 
		),
		'donation_currency' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::getCountriesOptions',
				'default' => 'USD',
				'title' => __ ( 'Donation Currency', 'braintree' ),
				'class' => array (
						'' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can set the currency that will display on the amount field. Ensure this currency matches the currency for your Braintree Account. If you have set a merchant account for donations, the donation currency will automatically use that currency.', 'braintree' ) 
		),
		'donation_default_country' => array (
				'type' => 'select',
				'options' => Braintree_Countries::$countries,
				'title' => __ ( 'Donation Country', 'braintree' ),
				'class' => array (
						'subItem',
						'addressOption' 
				),
				'tool_tip' => true,
				'description' => __ ( 'You can set the default country that will appear on the donation form.', 'braintree' ) 
		),
		'donation_merchant_account_id' => array (
				'type' => 'text',
				'value' => '',
				'default' => '',
				'title' => __ ( 'Merchant Account Id', 'braintree' ),
				'class' => array (
						'' 
				),
				'tool_tip' => true,
				'description' => __ ( 'NOTE: Not to be confused with the API key Merchant ID. The Merchant Account ID determines the currency that the donation is settled in. You can find your Merchant Account Id by logging into Braintree,
						and clicking Settings > Processing and scrolling to the bottom of the page.', 'braintree' ) 
		),
		'donation_payment_methods' => array (
				'type' => 'checkbox',
				'value' => Braintree_PaymentMethods::paymentMethods (),
				'default' => '',
				'title' => __ ( 'Display Payment Methods', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If you want to display an image of the payment methods on the
						checkout page that your Braintree account accepts, select the checkboxes.', 'braintree' ) 
		),
		'enable_webhooks' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Enable Webhooks', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If Webhooks are enabled, you must enable Braintree Subscriptions on the Subscriptions page.', 'braintree' ) 
		),
		'webhook_subscription_charged_unsuccessfully' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Payment Failed', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the failed subscription payment. The status of the subscription will be set to on hold.', 'braintree' ) 
		),
		'webhook_subscription_expired' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Expired', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the expired subscription. The status of the subscription will be set to expired.', 'braintree' ) 
		),
		'webhook_subscription_past_due' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Payment Past Due', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the past due subscription. The status of the subscription will be set to expired.', 'braintree' ) 
		),
		'webhook_subscription_went_active' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Active', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the active subscription. The status of the subscription will be set to active.', 'braintree' ) 
		),
		'webhook_subscription_charged_successfully' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Charge Success', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the successful subscription charge. The transaction id will be added to the order note for future reference.', 'braintree' ) 
		),
		'webhook_subscription_cancelled' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Subscription Cancelled', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, Braintree will send a message containing the information of the cancelled subscription. The transaction id will be added to the order note for future reference.', 'braintree' ) 
		),
		'woocommerce_braintree_merchant_acccounts' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::displayMerchantAccounts',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Merchant Account ID\'s', 'braintree' ),
				'class' => array (
						'' 
				),
				'tool_tip' => true,
				'description' => __ ( 'Not to be confused with your Merchant Id. The Merchant Account ID determines
						the currency of your transactions. If you are using a currency switcher, this will allow you to add multiple merchant accounts so that you can charge in multiple currencies. First select the currency from the drop down and then click the Add Merchant Account button. Enter your merchant account and save.
						If you entered any incorrect information you will receive an error message.', 'braintree' ) 
		),
		'braintree_only_subscriptions_charge_failed' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-failed',
				'title' => __ ( 'Status - Payment Failed', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to theSubscription object when the subscription charge is unsuccesful. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_only_subscriptions_charge_success' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-completed',
				'title' => __ ( 'Status - Payment Success', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the Subscription object when the subscription charge is successful. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_only_subscriptions_active' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-completed',
				'title' => __ ( 'Status - Subscription Active', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the Subscription object when the subscription has gone active. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_only_subscriptions_expired' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-cancelled',
				'title' => __ ( 'Status - Subscription Expired', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the Subscription object when the subscription has expired. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_only_subscriptions_past_due' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-failed',
				'title' => __ ( 'Status - Subscription Past Due', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the Subscription object when the subscription has gone past due. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'braintree_only_subscriptions_cancelled' => array (
				'type' => 'select',
				'options' => Braintree_Manager::getOrderStatuses (),
				'default' => 'wc-cancelled',
				'title' => __ ( 'Status - Subscription Cancelled', 'braintree' ),
				'class' => array (
						'subItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled webhooks, this is the status assigned to the Subscription object when the subscription has been cancelled. You must
						enable webhooks from within the Braintree Admin Panel. Instructions can be found on <a href="https://braintreegateway.com">Braintree</a> on how to setup a webhook.', 'braintree' ) 
		),
		'fail_on_duplicate' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Fail On Duplicate Payment Methods', 'braintree' ),
				'class' => array (
						'' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, duplicate credit cards cannot be added to the vault. For instance, if the VISA card <strong>4111111111111111</strong> already exists in the vault and
						another customer tries to add that card, there will be an error message presented to the customer informing them that that card already exists in the vault.', 'braintree' ) 
		),
		'sandbox_connection_test' => array (
				'type' => 'custom',
				'title' => __ ( 'Sandbox Connection Test', 'braintree' ),
				'value' => '',
				'default' => 'Inactive',
				'class' => array (),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::testSandboxConnection',
				'description' => __ ( 'Once you have entered and saved your API keys, you can perform a connection test to ensure you have entered them correctly.', 'braintree' ) 
		),
		'production_connection_test' => array (
				'type' => 'custom',
				'title' => __ ( 'Production Connection Test', 'braintree' ),
				'value' => '',
				'default' => 'Inactive',
				'class' => array (),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::testProductionConnection',
				'description' => __ ( 'Once you have entered and saved your API keys, you can perform a connection test to ensure you have entered them correctly.', 'braintree' ) 
		),
		'dynamic_descriptors' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Dynamic Descriptors', 'braintree' ),
				'class' => array (
						'' 
				),
				'tool_tip' => true,
				'description' => __ ( 'Dynamic descriptors are the text that appears on your customer\'s credit card statement. Descriptors can help prevent charge backs as they provide details on the transaction such as the company name and product being sold.
						If enabled, you will be able to customize the descriptor name, phone (U.S. only) and url. If you use subscriptions, each subscription product can have its own descripor name for more detail.', 'braintree' ) 
		),
		'dynamic_descriptor_name' => array (
				'type' => 'custom',
				'title' => __ ( 'Name', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'subItem',
						'descriptorSubItem' 
				),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::getDynamicDescriptorName',
				'description' => __ ( 'Dynamic descriptors can be used to customize what appears on your customer\'s credit card statement. They can help to prevent charge backs because the customer will recognize the charge from your web site. ', 'braintree' ) 
		),
		'dynamic_descriptor_phone' => array (
				'type' => 'custom',
				'title' => __ ( 'Phone', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'subItem',
						'descriptorSubItem' 
				),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::getDynamicDescriptorPhone',
				'description' => __ ( 'Dynamic descriptors can be used to customize what appears on your customer\'s credit card statement. By providing a number, your customer\'s will have a way of contacting you with any questions. ', 'braintree' ) 
		),
		'dynamic_descriptor_url' => array (
				'type' => 'custom',
				'title' => __ ( 'URL', 'braintree' ),
				'value' => '',
				'default' => '',
				'class' => array (
						'subItem',
						'descriptorSubItem' 
				),
				'tool_tip' => true,
				'function' => 'WC_Braintree_Admin::getDynamicDescriptorUrl',
				'description' => __ ( 'For US Merchants only. Dynamic descriptors can be used to customize what appears on your customer\'s credit card statement. By providing a url, your customer\'s will have an additional way of validating their purchase. ', 'braintree' ) 
		),
		'save_payment_methods' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Save Payment Methods', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'Note: not all forms have a save payment feature. If enabled, the payment form on the checkout page will have a checkbox which will give customers the option to save their payment method. The payment methods are stored securely in Braintree\'s vault and never touch your server.', 'braintree' ) 
		),
		'custom_form' => array (
				'type' => 'checkbox',
				'value' => 'yes',
				'default' => '',
				'title' => __ ( 'Custom Form', 'braintree' ),
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, you can select from a range of custom payment forms instead of relying on the dropin form. If left unchecked, the plugin will use the Dropin Form automatically', 'braintree' ) 
		),
		'custom_form_design' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::display_custom_form_design',
				'title' => __ ( 'Form Design', 'braintree' ),
				'default' => 3,
				'class' => array (
						'subItem',
						'customFormSubItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If enabled, you can configure the type of form you want to use on the checkout page. If you leave this option unchecked, the plugin will use the Dropin form.', 'braintree' ) 
		),
		'paypal_options' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::displayPayPalOptions',
				'title' => __ ( 'PayPal Options', 'braintree' ),
				'default' => 1,
				'class' => array (
						'subItem',
						'customFormSubItem' 
				),
				'tool_tip' => true,
				'description' => __ ( 'If you have enabled the custom form, you can setup PayPal as it\'s own gateway. Click the options link to configure the different settings.', 'braintree' ) 
		),
		'authorize_transaction' => array (
				'type' => 'checkbox',
				'title' => __ ( 'Authorize Amount Only', 'braintree' ),
				'default' => '',
				'class' => array (),
				'value' => 'yes',
				'tool_tip' => true,
				'description' => __ ( 'If enable, your orders will be authorized for the order amount but not automatically settled. You will need to capture the amount for the order by going to the admin 
						order page. This setting is not recommend for merchants that want their orders to be settled automatically.', 'braintree' ) 
		),
		'3ds_enabled'=>array(
				'type'=>'checkbox',
				'title'=>__('3D Secure Enabled', 'braintree'),
				'class'=>array(),
				'default'=>'',
				'value'=>'yes',
				'tool_tip'=>true,
				'description'=>__('You must have Braintree enable 3D Secure in your Production and/or Sandbox account in order for this setting to take affect.', 'braintree')
		)
		 
);