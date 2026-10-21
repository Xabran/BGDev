<?php
/**
 * Class that displays all of the tutorial sections for the plugin.
 * @author Clayton Rogers
 *
 */
class Braintree_Admin_Pages{

	
	/**
	 * Display the tutorials page. 
	 */
	public static function showTutorialsView(){
		WC_Braintree_Admin::getAdminHeader();
		self::tutorialsHeader();
		self::merchantAccounts();
		self::apiKeys();
		self::subscriptions();
		self::webhooks();
		self::dynamicDescriptors();
		self::dropinForm();
		self::paypal_button();
		self::custom_form();
	}
	
	public static function tutorialsHeader(){
		?>
		<div class="div--tutorialHeader">
		  <ul>
		    <li><a tutorial-container="merchant_accounts" href="#"><?php echo __('Merchant Account ID', 'braintree')?></a></li>
		  	<li><a tutorial-container="api_keys" href="#"><?php echo __('API Keys', 'braintree')?></a></li>
		  	<li><a tutorial-container="subscriptions" href="#"><?php echo __('Subscriptions', 'braintree')?></a></li>
		  	<li><a tutorial-container="webhooks" href="#"><?php echo __('Webhooks', 'braintree')?></a></li>
		    <li><a tutorial-container="dynamic_descriptors" href="#"><?php echo __('Dynamic Descriptors', 'braintree')?></a></li>
		    <li><a tutorial-container="dropin_form" href="#"><?php echo __('Dropin Form', 'braintree')?></a></li>
		    <li><a tutorial-container="paypal_button" href="#"><?php echo __('PayPal Button', 'braintree')?></a></li>
		    <li><a tutorial-container="custom_form" href="#"><?php echo __('Custom Form', 'braintree')?></a></li>
		  </ul>
		</div>
		<?php
	}
	
	public static function merchantAccounts(){
		?>
		<div id="merchant_accounts" class="braintree-explanation-container display">
		  <div class="div--title">
		    <h2><?php echo __('Merchant Account ID Configuration', 'braintree')?></h2>
		  </div>
		  <div class="explanation">
		    <div><strong><?php echo __('Merchant Account ID: ', 'braintree')?></strong>
		      <?php echo __('The Merchant Account ID is used during transactions to determine the settlement currency.
		      		Within the <a href="'.admin_url().'admin.php?page=braintree-woocommerce-settings'.'">WooCommerce Settings</a> page, you
		      		can add all of the Merchant Accounts that are associated with your Braintree Account.', 'braintree')?>
		    </div>
		    <div class="explanation">
		      <?php echo __('To find your merchant accounts, login to your <a target="_blank" href="https://sandbox.braintreegateway.com/login">Braintree Sandbox</a> or <a target="_blank" href="https://braintreegateway.com/login">Braintree Production</a> account. 
		      		Once logged in, Click the <strong>Settings</strong> link, then <strong>Processing</strong>. At the bottom of the page, you will find your Merchant Accounts. ', 'braintree')?>
		    </div>
		    <div>
		      <p><?php echo __('Login and click the Processing link located under the Settings menu item.', 'braintree')?></p>
		      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/settings-processing.png"/></div>
		    </div>
		    <div>
		      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/merchant-accounts.png"/></div>
		    </div>
		     <div>
		      <p><?php echo __('Navigate to the <a href="'.admin_url().'admin.php?page=braintree-woocommerce-settings'.'">WooCommerce Settings</a> page and add the your merchant accounts. If you are using a currency switcher on your site, you will want to add all of the 
		      		merchant accounts associatd with your Braintree account. If you leave this setting blank, the default merchant account will be used.', 'braintree')?></p>
		      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/merchant-accounts-assign.png"/></div>
		    </div>
		  </div>
		</div>
		<?php 
	}
	
	public static function apiKeys(){
		?>
		<div id="api_keys" class="braintree-explanation-container">
	      <div class="div--title">
			<h2><?php echo __('API Keys Configuration', 'braintree')?></h2>
			  </div>
				 <div class="explanation">
				    <div><strong><?php echo __('API Keys: ', 'braintree')?></strong>
				      <?php echo __('The API Keys are used to communicate securely with Braintree from your Wordpress site. In order for the plugin to send and receive data to and from Braintree, it is required that you add your API keys.
				      		The Merchant ID identifies your merchant account during each request and the Public and Private Key are used for authentication.', 'braintree')?>
				    </div>
				 </div>
				 <div class="explanation">
				      <?php echo __('To access your API Keys, login to your <a target="_blank" href="https://braintreegateway.com/login">Braintree Sandbox</a> or <a target="_blank" href="https://sandbox.braintreegateway.com/login">Braintree Production</a> account. Hover over the 
				      		<strong>Account</strong> link and click <strong>My User</strong>. Scroll to the bottom of the page and click <strong>View Authorizations</strong>. If you have not generated your API keys, do so now. Copy and paste the 
				      		Merchant ID, Private Key, and Public Key into the field located on the <a target="_blank" href="'.admin_url().'admin.php?page=braintree-payment-settings'.'">API Settings Page.</a>', 'braintree')?>
				 </div>
				<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/account-myuser.png"/></div>
				<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/api-keys.png"/></div>
				<div>
				  <p><?php echo __('Copy and paste the API keys from Braintree into the settings located on the <a target="_blank" href="'.admin_url().'admin.php?page=braintree-payment-settings'.'">API Settings</a> page. Once you have saved your API keys, you can perform a connection
				  		test to ensure you have entered the keys correctly.', 'braintree')?></p>
				  <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/api-keys-setup.png"/></div>
				</div>
		</div>
		<?php 
	}
	
	public static function subscriptions(){
		?>
		<div id="subscriptions" class="braintree-explanation-container">
		  <div class="div--title">
			 <h2><?php echo __('Subscription Configuration', 'braintree')?></h2>
		  </div>
		    <h3><a type="#braintreeonly_subscriptions" class="span-subscription_type"><?php echo __('Use This Plugin for Subscriptions', 'braintree')?></a></h3>
		    <h3><a type="#woocommerce_subscriptions" class="span-subscription_type"><?php echo __('I Use The WooCommerce Subscriptions Plugin', 'braintree') ?></a></h3>
		    <div id="woocommerce_subscriptions" class="tutorials_subscription_type">
		      <div class="explanation">
			    <div>
		        <?php echo __('<a href="https://www.woothemes.com/products/woocommerce-subscriptions">WooCommerce Subscriptions</a> can be configured in one of two ways.From within the <a target="_blank" href="'.admin_url().'admin.php?page=braintree-subscriptions-page'.'">Subscriptions Page</a> you will have the option to either have the WooCommerce Subscriptions Plugin manage your subscription payments, 
		      		or create subscriptions directly in Braintree when a customer makes a subscription purchase. Braintree subscriptions are limited to monthly intervals
		      		but they are great for automatically charging your customers and sending webhook notifications directly to your site. We recommend the WooCommerce Subscriptions setting if you have complex subscriptions consisting of daily or weekly intervals', 'braintree')?>
		        </div>
			  <div>
			  	<p><?php echo __('You can choose to create a subscription within Braintree for the customer or have the WooCommerce Subscriptions plugin handle the subscription payments. If you select WooCommerce Subscriptions, there are no additional configuration settings to maintain beyond creating a subscription product.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/subscriptions-settings-page.png"/></div>
			  </div>
		      </div>
		      
		      <div><h3><?php echo __('Create A Braintree Subscription', 'braintree')?></h3></div>
			  <div class="explanation">
			      <?php echo __('To configure Braintree Subscriptions, login to your <a target="_blank" href="https://sandbox.braintreegateway.com/login">Braintree Sandbox</a> or <a target="_blank" href="https://braintreegateway.com/login">Braintree Production</a> account. 
			      		Once logged in, Click the <strong>Plans</strong> link located on the left navigation bar. To add a plan click <strong>New</strong>.', 'braintree')?>
			  </div>
			  <div>
			  	<p><?php echo __('Login to your Braintree Sandbox or Production account and navigate to the Plans link on the left hand side.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/plans.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Click the Plans link and then click Add New to create a new Plan. Give your plan a name, Id, and description. The price that you set for the plan can be overwridden from within the WooCommerce edit product pages. The plugin will use the product price that has ben configured.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Assign the Plan currency, billing period, and whether or not the subscription starts immediately.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId2.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Set the end date if there is one and configure any add ons that you want the plan to have. An example of an add on would be a one time fee
			    		to be charged when the subscription is created to serve as a signup fee.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId3.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Once you have saved the Plan, navigate to your wordpress site and click on the subscription product you want to edit. 
			    		If you have configured your API keys correctly, the planId\'s associated with your merchant account will appear with a drop down. You can assign a plan on a per currency basis. This will allow you 
			    		to sell your subscriptions using a currency switcher. Be sure and select the checkbox Sell As Subscription so that the plugin knows that a Braintree Subscription will be created during checkout. Select the plan and click Add Plan. You can always remove unwanted plans from the product too. 
			    		While the checkbox Sell As subscription is checked, the subscription intervals will match the defalt Braintree plan to prevent confusion. The default plan is fetched using the woocommerce currency that is set for your shop.
			    		To test, simply purchase the subscription in the currency you would like and verify that it is created in the Braintree environment.', 'braintree')?></p>
			    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/subscriptions-edit-product.png"/></div>
			  </div>
			   <div>
			    <p><?php echo __('You don\'t have to have to have the WooCommerce Subscriptions plugin to charge for subscriptions. This plugin allows you to convert a regular WooCommerce product into a subscription. Select the 
			    		product that you want to charge as a subscription then select "Sell As Subscription" and select the plan Id.', 'braintree')?></p>
			    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/subscription-braintree-only.png"/></div>
			  </div>
		  </div>
		  
		   <div id="braintreeonly_subscriptions" class="tutorials_subscription_type">
		     <div><h3><?php echo __('Create A Braintree Subscription', 'braintree')?></h3></div>
			  <div class="explanation">
			      <?php echo __('This plugin has the ability to convert your WooCommerce Product(s) into a subscription. In order to sell your product as a subscription, you must maintain some configurations first. To configure Braintree Subscriptions, login to your <a target="_blank" href="https://sandbox.braintreegateway.com/login">Braintree Sandbox</a> or <a target="_blank" href="https://braintreegateway.com/login">Braintree Production</a> account. 
			      		Once logged in, Click the <strong>Plans</strong> link located on the left navigation bar. To add a plan click <strong>New</strong>.', 'braintree')?>
			  </div>
			  <div>
			  	<p><?php echo __('Login to your Braintree Sandbox or Production account and navigate to the Plans link on the left hand side.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/plans.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Click the Plans link and then click Add New to create a new Plan. Give your plan a name, Id, and description. The price that you set for the plan can be overwridden from within the WooCommerce edit product pages. The plugin will use the product price that has ben configured.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Assign the Plan currency, billing period, and whether or not the subscription starts immediately.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId2.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Set the end date if there is one and configure any add ons that you want the plan to have. An example of an add on would be a one time fee
			    		to be charged when the subscription is created to serve as a signup fee.', 'braintree')?></p>
			  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/planId3.png"/></div>
			  </div>
			  <div>
			    <p><?php echo __('Once you have saved the Plan, navigate to your wordpress site and click on the product you want to sell as a subscription. 
			    		If you have configured your API keys correctly, the planId\'s associated with your merchant account will appear with a drop down. You can assign a plan on a per currency basis. This will allow you 
			    		to sell your subscriptions using a currency switcher. Be sure and select the checkbox Sell As Subscription so that the plugin knows that a Braintree Subscription will be created during checkout. Select the plan and click Add Plan. You can always remove unwanted plans from the product too.
			    		To test, simply purchase the subscription in the currency you would like and verify that it is created in the Braintree environment.', 'braintree')?></p>
			    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/subscriptions-edit-braintree-product.png"/></div>
			  </div>
		   </div>
		</div>
		<?php 
	}
	
	public static function webhooks(){
		?>
		<div id="webhooks" class="braintree-explanation-container">
		  <div class="div--title">
			 <h2><?php echo __('Webhook Configuration', 'braintree')?></h2>
		  </div>
		  <div class="explanation">
		    <div><strong><?php echo __('Webhooks: ', 'braintree')?></strong>
		    <?php echo __('Braintree has the ability to send notices to your wordpress site when certain events occur. For example, you can tell Braintree to send you messages
		    		anytime a Braintree subscription payment fails. This allows you to handle the failed payment method in an automated way.', 'braintree')?>
		    </div>
		  </div>
		  <div>
		  	<p><?php echo __('Login to your Braintree <a target="_blank" href="https://sandbox.braintreegateway.com/login">Sandbox</a> or <a target="_blank" href="https://braintreegateway.com/login">Production</a> account and navigate to the Webhooks page by clicking <strong>Webhooks</strong> located under the <strong>Settings</strong>
		  			menu item.', 'braintree')?></p>
		  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/webhooks.png"/></div>
		  </div>
		  <div>
		    <p><?php echo __('Click the <strong>Create new webhook</strong> button.', 'braintree')?></p>
		    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/webhook-create.png"/></div>
		  </div>
		  <div>
		    <p><?php echo __('Paste this url <textarea>'.WC_Braintree_Admin::getWebhooksUrl().'</textarea> into the Destination URL field. Switch http for https if you have an SSL cert enabled. 
		    		Assign all of the notification types that you want Braintree to trigger. Save the webhook.', 'braintree')?></p>
		    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/webhook-create2.png"/></div>
		  </div>
		  <div>
		    <p><?php echo __('Enable the webhooks option on the <a href="'.admin_url().'admin.php?page=braintree-webhooks-page'.'">Webhook Settings</a> page and enable all the notification types that 
		    		you want to accept from Braintree.', 'braintree')?></p>
		    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/webhook-create3.png"/></div>
		  </div>
		  <div>
		    <p><?php echo __('From within Braintree, navigate to the Webhooks page. Under the actions header, there will be a link next to your URL that says <strong>Check URL</strong>. Click this link to 
		    		test your webhook integration. If you have configured everything correctly, you will receive a 200 response code.', 'braintree')?></p>
		    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/webhook-create4.png"/></div>
		  </div>
		  <div>
		    <p><?php echo __('You can verify that the notification sent from Braintree was received by checking the <a target="_blank" href="'.admin_url().'admin.php?page=braintree-debug-log'.'">Debug Log</a>.', 'braintree')?></p>
		    <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/debug-log.png"/></div>
		  </div>
		</div>
		<?php 
	}
	
	public static function dynamicDescriptors(){
		?>
		<div id="dynamic_descriptors" class="braintree-explanation-container">
	      <div class="div--title">
			<h2><?php echo __('Dynamic Descriptors', 'braintree')?></h2>
		  </div>
			 <div class="explanation">
			    <div>
			      <?php echo __('Dynamic descriptors can be enabled from within the <a target="_blank" href="' .admin_url() .'admin.php?page=braintree-woocommerce-settings">WooCommerce Settings</a> page. Dynamic descriptors are
			      		a way to affect the text that appears on the customer\'s credit card statement. ', 'braintree')?>
			    </div>
			 </div>
			 <div class="explanation">
		      <?php echo __('When enabled, three fields can be configured for dynamic descriptors from within the <a target="_blank" href="' .admin_url() .'admin.php?page=braintree-woocommerce-settings">WooCommerce Settings</a> page. ', 'braintree')?>
			   <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dynamic-descriptors.png"/></div>
			   <div>
			     <h4><?php echo __('Name', 'braintree')?></h4>
			     <ul>
			       <li><?php echo __('Comprised of a business name and product name, separated by an asterisk (*)', 'braintree')?></li>
			       <li><?php echo __('Business name must be either 3, 7, or 12 characters; product descriptor can be up to 18, 14, or 9 characters respectively (with an * in between for a total of 22 characters).', 'braintree')?></li>
			       <li><?php echo __('Can contain special characters . + -', 'braintree')?></li>
			       <li><?php echo __('Can contain lower and upper case', 'braintree')?></li>
			       <li><?php echo __('Can contain spaces, but cannot start with a space', 'braintree')?></li>
			     </ul>
			   </div>
			   <div>
			     <h4><?php echo __('Phone (US Based Merchants Only)', 'braintree')?></h4>
			     <ul>
			       <li><?php echo __('Must contain exactly 10 digits', 'braintree')?></li>
			       <li><?php echo __('Can contain up to 14 characters total, including special characters', 'braintree')?></li>
			       <li><?php echo __('Can contain special characters . ( ) -', 'braintree')?></li>
			     </ul>
			   </div>
			   <div>
			     <h4><?php echo __('Url', 'braintree')?></h4>
			     <ul>
			       <li><?php echo __('The value in the URL/web address field of a customer\'s statement. The URL must be 13 characters or shorter.', 'braintree')?></li>
			     </ul>
			   </div>
			 </div>
			 <div>
			   <h3><?php echo __('Braintree Subscription Descriptor', 'braintree')?></h3>
			   <div>
			     <p><?php echo __('If Dynamic descriptors have been enabled and you are selling subscriptions using the Braintree Subscriptions, then you can create name descriptors for each of your products. This
			     		will allow you to provide more detail to your customer\'s on the purchase they made from your site.', 'braintree')?></p>
			   	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dynamic-descriptors-subscription.png"/></div>
			   </div>
			 </div>
			 
		</div>
		<?php 
	}
	
	public static function dropinForm(){
		?>
		<div id="dropin_form" class="braintree-explanation-container">
	      <div class="div--title">
			<h2><?php echo __('Dropin Form', 'braintree')?></h2>
		  </div>
			 <div class="explanation">
			    <div>
			      <?php echo __('The dropin form is the payment form that customers will enter their payment information into during checkout. The form is hosted by Braintree which means your server never touches the customers payment data. 
			      		There are several configurations you can make to customize the dropin form to suite your business needs. ', 'braintree')?>
			    </div>
			    <div>
			      <p><?php echo __('Without any settings being maintained, the standard dropin form contains only fields for the credit card number and the expiration date. ', 'braitnree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-basic.png"/></div>
			    </div>
			    <div>
			      <p><strong><?php echo __('Postal Code Field: ', 'braintree')?></strong></span><?php echo __('In order for the postal code field to become visible on the dropin form, you must enable it from within
			      		the braintree control panel.', 'braintree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-postalcode.png"/></div>
			    </div>
			    <div>
			      <p><?php echo __('By selecting to verify the postal code, the dropin form will display a field for the postal code. Select all of the checkboxes that apply.', 'braintree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-postalcode2.png"/></div>
			       <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-postalcode3.png"/></div>
			    </div>
			    <div>
			      <p><strong><?php echo __('CVV Field: ', 'braintree')?></strong><?php echo __('In order to display the CVV field on the dropin form, you must enable CVV verification within
			      		the Braintree control panel. Login to your Braintree account and navigate to <strong>Settings</strong> > <strong>Processing</strong> and click the Edit button under CVV.', 'braintree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-cvv.png"/></div>
			    </div>
			    <div>
			      <p><?php echo __('Select the checkboxes that apply. The CVV field will now appear on the dropin form.', 'braintree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-cvv2.png"/></div>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-cvv3.png"/></div>
			    </div>
			    <div>
			      <p><strong><?php echo __('PayPal: ', 'braintree')?></strong><?php echo __('You can integrated PayPal with your Braintree account. You will need to follow their instructions which can be found within your Braintree
			      		control panel.', 'braintree')?></p>
			      <div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/dropin-form-paypal.png"/></div>
			    </div>
			 </div>
		</div>
		<?php
	}
	
	public static function paypal_button(){
		?>
		<div id="paypal_button" class="braintree-explanation-container">
	      <div class="div--title">
			<h2><?php echo __('PayPal Button', 'braintree')?></h2>
		  </div>
		  <div class="explanation">
		  	<div>
		  		<?php echo __('If you have enabled the Custom Form option in the WooCommerce settings, then you have the option to enable PayPal as its own gateway.
		  				This requires that you select a PayPal button design that will display on the checkout page. If you are not happy with the plugin provided buttons, you can add your own.', 'braintree')?>
		  	</div>
		  	<div>
		  		<div><label><strong><?php echo __('Custom PayPal Button', 'braintree')?></strong></label></div>
		  		<p><?php echo __('To add a custom PayPal button you must do the following. In your theme\'s <strong>functions.php</strong> file add a function that hooks into the PayPal Options filter.')?></p>
		  		<div>
		  			<style>code { white-space: pre; }</style>
<code>
function add_paypal_design_button($options) { 
	$options [] = array (
			'css' => get_template_directory_uri() . '/braintree/css/paypal/paypal-button-pill.css',
			'html' => get_template_directory() . '/braintree/forms/paypal/buttons/paypal-button-pill.php' 
	);
	return $options;
}

add_filter ( 'braintree_paypal_design_options', 'add_paypal_design_button' );
</code>
<p><?php echo __('The options array accepts two indexes, one for the url path to your custom css file and the other for the file path to your .php file that contains the html code for your PayPal button.
		Once you have completed this step, you can then select your custom button on the <a target="_blank" href="'.admin_url() . 'admin.php?page=braintree-woocommerce-settings">WooCommerce Settings</a> page of the plugin.', 'braintree')?></p>
		  		</div>
		  	</div>
		  </div>
		</div>
		<?php
	}
	
	public static function custom_form(){
		?>
		<div id="custom_form" class="braintree-explanation-container">
	      <div class="div--title">
			<h2><?php echo __('Custom Form', 'braintree')?></h2>
		  </div>
		  <div class="explanation">
		  	<div>
		  		<?php echo __('If you have enabled the Custom Form option in the <a target="_blank" href="'.admin_url().'admin.php?page=braintree-woocommerce-settings">WooCommerce Settings</a>, then you have the option to either customize an existing form or design and provide
		  				your own custom form. In order for your own form to show up in the drop down field, you must register your form.', 'braintree')?>
		  	</div>
		  </div>
		  <div class="explanation">
		  	<h3><strong><?php echo __('Step 1: Create Your Form', 'braintree')?></strong></h3>
		  	<div>
		  		<?php echo __('In order to register a form, you must create the form as a .php file. Inside your themes folder, create a new folder named braintree (or whatever you want). Then, create your form {my-form.php}. Use the existing forms
		  				provided with the plugin as a guide.', 'braintree')?>
		  	</div>
		  	<div>
		  		<?php echo __('In order for your form to be rendered correctly, you must name your container divs with specific Id\'s. Please see the example below.', 'braintree')?>
		  	</div>
		  	<style>code { white-space: pre; }</style>
<code>
<?php echo esc_html('<div class="my-custom-form">
	<div id="card-number"></div>
	<div id="expiration-date"></div>
	<div id="cvv"></div>
	<div id="postal-code"></div>
</div>')?>
</code>
			<div><h4><?php echo __('Naming Requirements', 'braintree')?></h4></div>
				<div><?php echo __('Your field containers must use these Id\'s or your custom form will not render correctly.', 'braintree')?></div>
				<div><h5><?php echo __('Credit Card: #card-number')?></h5></div>
				<div><h5><?php echo __('Expiration Date: #expiration-date')?></h5></div>
				<div><h5><?php echo __('Expiration Month: #expiration-month')?></h5></div>
				<div><h5><?php echo __('Expiration Year: #expiration-year')?></h5></div>
				<div><h5><?php echo __('Security Code: #cvv')?></h5></div>
				<div><h5><?php echo __('Postal Code: #postal-code')?></h5></div>
		  </div>
		  <div class="explanation">
		  	<h3><strong><?php echo __('Step 2: Create your CSS file.', 'braintree')?></strong></h3>
		  	<div>
		  		<?php echo __('Your custom form can be styled any way that you like. This will require that you either create a css file or write
		  				the css in your theme\'s custom.css file.', 'braintree')?>
		  	</div>
		  </div>
		  <div class="explanation">
		  	<h3><strong><?php echo __('Ste 3: Create Javascript File', 'braintree')?></strong></h3>
		  	<div>
		  		<?php echo __('When events occur such as invalid card entries, or valid cards, these events can be hooked in to. These events allow you 
		  				to change the styling of your form accordingly, such as when a field is focussed. The following examples demonstrate how to hook into these events.', 'braintree')?>
		  	</div>
		  	<div>
<code>
jQuery(document).ready(function(){
	var MyCustomForm = function(){};
	
	/*Add the field-focus class to the container when the user clicks on the field*/
	MyCustomForm.onFocus = function(e, event){
		var field = event.fields[event.emittedBy];
		jQuery(field.container).addClass('field-focussed');
	}
	
	/*Add card type class for detecting type of card user is entering*/
	MyCustomForm.cardTypeChange = function(e, event){
		var field = event.fields[event.emittedBy];
		if(event.cards.length === 1){
			jQuery(field.container).addClass(event.cards[0].type);
		}
	}
	
	/*Register your functions with the events*/
	jQuery(document.body).on('braintree_field_focus', MyCustomForm.onFocus);
	jQuery(document.body).on('braintree_card_type_change', MyCustomForm.cardTypeChange);
});
</code>

		  	</div>
		  	<div><h4><?php echo __('Events', 'braintree')?></h4></div>
				<div><h5><?php echo __('braintree_field_empty: Emitted when a field transitions from having data to being empty.')?></h5></div>
				<div><h5><?php echo __('braintree_field_not_empty: Emitted when a field transitions from being empty to having data.')?></h5></div>
				<div><h5><?php echo __('braintree_field_blur: Emitted when a field loses focus.')?></h5></div>
				<div><h5><?php echo __('braintree_field_focus: Emitted when a field gains focus.')?></h5></div>
				<div><h5><?php echo __('braintree_field_validity_change: Emitted when the validity of a field changes.')?></h5></div>
				<div><h5><?php echo __('braintree_card_type_change: Emitted when the possible card type has changed. Only emitted from a change in the number field.')?></h5></div>
		  </div>
		  <div class="explanation">
		  	<h3><strong><?php echo __('Step 4: Register Your Form', 'braintree')?></strong></h3>
		  	<div>
		  		<?php echo __('In order for your custom form to be available as an option, you must register it with the plugin. To register your plugin you must hook into the custom form filter. We suggest
		  				creating a function in your theme\'s functions.php file and adding it to the filter.', 'braintree')?>
		  	</div>
		  	<div>
<code>
function add_custom_braintree_form($forms){
	$forms['my_custom_form'] = array(
			'description'=>'My Custom Form',
			'html'=>get_template_directory() . '/braintree/my-form.php',
			'css'=>get_template_directory_uri() . '/braintree/my-form.css',
			'javascript'=>get_template_directory_uri() . '/braintree/my-form.js',
			'externa_css'=>'If external css file is needed',
			'default_styles'=>'{"input":{"font-size":"16px", "font-family":"helvetica, tahoma, calibri, sans-serif"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
	);
	return $forms;
}

add_filter('braintree_custom_forms', 'add_custom_braintree_form');
</code>
		  	</div>
		  	<div><?php echo __('Once you have registered your custom form, it will be available to select on the custom form settings page. Once selected, your form will appear on
		  			the checkout page.')?></div>
		  	<div class="explanation-img"><img src="https://tutorials.paymentplugins.com/woo-payment-gateway/images/custom-form.png"/></div>
		  </div>
		 </div>
		 <?php
	}
}