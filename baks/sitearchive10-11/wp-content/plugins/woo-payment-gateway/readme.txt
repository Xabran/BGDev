=== Braintree For Woocommerce ===
Contributors: mr.clayton@paymentplugins.com
Donate link: 
Tags: braintree, braintree payments, braintreepayments, braintree plugin, braintree gateway, payment processing, woocommerce, payment gateway, woocommerce subscriptions, payment gateways, paypal, donations, v.zero, saq a, subscriptions, braintree subscriptions, payment forms, wordpress payments, donation, recurring donations
Requires at least: 3.0.1
Tested up to: 4.6.1
Stable tag: 2.4.8
Copyright: Clayton Rogers, mr.clayton@paymentplugins.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Braintree For Woocommerce integrates your Braintree merchant account with your wordpress site all while keeping you SAQ A compliant. Merchants can accept credit card and Paypal payments for their goods and services via WooCommerce. Merchants can also accept donations via credit cards and Paypal. Merchants can sell Subscriptions using the WooCommerce Subscriptions plugin or sell stand alone subscriptions using the built in plugin functionality.
Merchants have the ability to select from several delivered form designs or they can create their own form.

= Features =
- SAQ A PCI Compliant
- Beautifully designed forms out of the box
- Create your own custom form
- Integrates with Woocommerce
- Integrates with Woocommerce Subscriptions 2.0.0+
- Offer subscriptions without the WooCommerce Subscription plugin
- Integrations with WooCommerce currency switchers
- Webhooks for automated order statuses
- Accept credit cards and Paypal via your Braintree account
- Accept donations via your Braintree account
- Void transactions
- Automatic settlement or authorize transactions
- Issue refunds
- Dynamic descriptors
- Customers can save payment data to the Braintree payment vault
- Change subscription payment method


Braintree For WooCommerce supports many features. In addition to working with the WooCommerce plugin, merchants can also charge for subscriptions using the WooCommerce Subscriptions plugin. If you do not have the WooCommerce Subscription plugin, this plugin supports a feature where a WooCommerce product can be converted to a subscription. This plugin has a donation feature that allows you to capture donation payments by placing short code [braintree_donations] on any page. There are many options that can be customized so that this plugin can best server your business needs. For Braintree Subscriptions, this plugin has support for Webhooks, which takes out the manual process of having to monitor your subscription payments and statuses. You can configure the subscription status that will be applied when the Webhook message is received from Braintree. New in Version 2.3.0 is support for email receipts. By configuring your email settings from within Braintree, you can start sending automatic email receipts to your customers. If you accept multiple currencies, this plugin integrates with the WooCommerce Currency plugin, allowing you to settle your transactions in multiple currencies. 
For more information or questions, please email <a href=”mailto:support@paymentplugins.com”>support@paymentplugins.com</a> or read through our detail <a target="_blank" href="https://wordpress.paymentplugins.com/braintree-documentation/">documentation</a>.


== Frequently Asked Questions ==

= How can I found out more? = 
Please read our <a target="_blank" href="https://wordpress.paymentplugins.com/braintree-documentation/">documentation</a> which covers all the plugin functionality. 

= Do I have to have the WooCommerce Plugin? = 
While we suggest that you use WooCommerce when selling products for tracking and report purposes, it is not required. You can use the donation functionality to sell products as well.
All you need to do is customize the donation form to your liking via the plugin settings page and add the shortcode <strong>[braintree_donations]</strong> to the page that you wish to sell an item. If you want
the form to contain a list of payment amounts, simply add the following shortcode and include the index and amount. <strong>[braintree_donations 1="1" 2="2" 3="3" 4="5" 5="10"]</strong>. If you only want one price to be allowed, only inlcude one index <strong>[braintree_donations 1="55"]</strong>.

= Where can I access my public and private keys? =

Login to your Braintree account and select Account->My User->View Authorizations

= What is a Merchant Account ID? = 

The Merchant Account ID determines the currecency that your transactions are settled in. You can have multiple Merchant Account ID's if your Braintree account has been
configured for multiple currencies. 

= How do I test this plugin? = 
All of the plugin functionality can be tested in sandbox mode. If you haven't already, sign up for a <a target="_blank" href="https://www.braintreepayments.com/get-started">Braintree Sandbox Account</a>
and download this plugin. Set the mode to Sandbox and configure your API keys by following the tutorial located within the plugin. You can use <a target="_blank" href="https://developers.braintreepayments.com/reference/general/testing/php">Test Cards</a> for all of your sandbox transcations. 

= Can I customize the look of the plugin? =

Yes, you can select between several options when customizing the look and feel.

= Who can I contact for information on this plugin? =

Please email support@paymentplugins.com

= Do I have to have the WooCommerce Subscriptions Plugin? = 

No, this plugin allows you to convert a regular WooCommerce product into a subscription.

= I don't know where to start, what should I do? = 

Our plugin has a detailed tutorials page that shows you step by step how to configure the different settings. Once you install and activate the plugin,
navigate to the tutorials page. 

== Screenshots ==
1. Google material form.
2. Simple card form.
3. API settings for communicating with Braintree. 
4. WooCommerce Settings.
5. Subscription Settings. 
6. Donation Settings.
7. Debug Log.
8. Braintree Webhooks.
9. Convert WooCommerce product into a subscription.
10. Configure the custom forms.

== Changelog ==
= 2.4.8 = 
* Fixed - iFrame already exists resolved. Occured with some themes that have heavy ajax use.
* Added - Recurring donations available now.
* Removed - Order status settings. WooCommerce determines order status.
* Added - filters for process_payment call on Gateway.
= 2.4.7 = 
* Fixed - PayPal using dropin form and 3DS.
* Added - Custom form loader for checkout.
= 2.4.6 = 
* Fixed - Non braintree related orders were not rendering in admin panel.
= 2.4.5 = 
* Added - Merchants can now select between PayPal checkout or vault flow.
* Added - Transactions can be authorized or settled automatically.
* Added - Transactions can now be voided.
= 2.4.4 = 
* Added - Simple form added.
* Improved - Theme support for custom forms.
* Improved - PayPal gateway user experience.
= 2.4.3 = 
* Added - Support for hosted forms now available. Merchants can create their own custom forms or customize the provided form designs.
* Added - PayPal can now be setup as a separate gateway.
= 2.4.2 =
* Added - Call to payment_complete() of WC_Order after transaction success in order for stock to be reduced.
= 2.4.1 = 
* Added - Customer's can add and delete payment methods from the my acount page.
= 2.4.0 = 
* Added - Tax amount now reported on transactions.
* Fixed - Merchant account associated with Braintree Subscription.
* Added - $0 amount transactions for synchronized subscriptions.
= 2.3.9 = 
* Added - Improved subscription logic for WooCommerce Subscriptions. A new order is created now when a subscription payment is processed.
= 2.3.8 = 
* Changed - font size for admin notices so they are more visible.
* Changed - API keys now save during connection test to prevent confusion.
* Fixed - Some themes caused multiple dropin forms to show on checkout page.
* Added - Default method selected on checkout page for users with saved payment methods.
= 2.3.7 = 
* Fixed - Webhooks now send response code 200 to Braintree.
* Added - Improved admin notifications.
* Added - Pretty url for webhook service added. Old url is still accepted.
= 2.3.6 = 
* Added - Improvements made to payment method save functionality. 
= 2.3.5 = 
* Added - Shipping address has been added to sale transactions.
* Added - Dynamic descriptors
* Added - Merchant account validations
= 2.3.4 = 
* Fixed - Order prefix was showing up as suffix in versions after 2.3.0.
* Added - Validation to compare merchant account Id with merchant Id. This prevents customer's from confusing the two values.
* Added - Connection test for API keys.
= 2.3.3 = 
* Added - Transactions now capture the billing state for tax purposes. Donation form can now be used to sell products.
* Fixed - Data update for recurring payments using Paypal.
= 2.3.2 =
* Fixed - Refunds were displaying unsuccessful message when refunds were processing correctly. 
= 2.3.1 = 
* Added - Fail on duplicate payment method option.
* Fixed - Pay for order screen. 
* Fixed - JQuery issue with certain themes and plugins.

= 2.3.0 = 
* Fixed - Issues with standard PayPal plugin.
* Added - Webhooks for Subscriptions.
* Added - New admin ui for configurations.
= 2.1.10 = 
* Added
= 2.2.09 =
* Fixed - Dropin UI showed error message cannot use paymentmethodnonce more than once when incorrect card data was entered. 
= 2.2.08 =
* Change - License activation url updated. 
= 2.2.07 = 
* Fixed - Donations error.
= 2.2.06 = 
* Fixed - Dependency on WooCommerce within Donation page. 
= 2.2.05 = 
* Fized - During checkout, if country was changed, states were not updating properly. Resolved.
= 2.2.04 = 
* Added - Support added for custom orders. 
= 2.2.03 = 
* Added - Support for Woocommerce Currency Switcher. Merchants can now configure as many Merchant Account Id's as they want. 
= 2.2.02 = 
* Fixed - Warning messages on the donation page when WP_DEBUG set to true. 
= 2.2.01 = 
* Fixed - When deleting multiple subscriptions, exception was thrown due to a null object. Deprecated methods replaced for Woocommerce Subscriptions. 
= 2.2.00 = 
* Resolved - PHP version 5.5 and lower displayed warning messages in teh admin panel when WP_DEBUG set to true. Issue has been resolved now.
= 2.1.99 = 
* Deprecated - Form 2 has been deprecated in favor of form 0 and 1. 
* Changed - Paypal only checkout no longer auto submits after Paypal login. For user experience improvement, customer must
click "Place Order" to process the payment. 
= 2.1.98 = 
* Added - site url and admin email sent to paymentplugins.com during plugin activation in order to help with technical issues and trouble shooting questions.
= 2.1.97 = 
* Fixed - Deprecated calls to checkout.js file updated.
= 2.1.96 = 
* Fixed - Duplicate dropin forms resolved when ajax refresh triggerd by Woocommerce. 
= 2.1.95 = 
* Fixed - Token for subscription payment method added to subscription meta. 
= 2.1.94 = 
* Added - The donation form can now be configured to be inline or modal and the donatin button text can be customized. 
* Added - Improved log messages for failed payments. 
= 2.1.93 =
* Update - Website changed from wordpresspayments.co to paymentplugins.com for trademark reasons. 
= 2.1.92 = 
* Fixed - Transaction log was overwritten during upgrades. Transaction log is now saved in a new folder located in wp-content to avoid this issue. 
= 2.1.91 = 
* Fixed - Fatal Error resolved for class Braintree_For_Woocommerce
= 2.1.9 = 
* Fixed - If Woocommerce was not enabled, the plugin would error out. Now, Woocommerec is not necessary to run the plugin for donation payments. 
= 2.1.8 =
* Added - Admins now have the ability to select payment method images to display on the checkout page. 
* Fixed - Potential error if exception thrown during subscription creation.
* Added - Additional transaction log message for enhanced tracking implemented. 
= 2.1.7 = 
* Added - Transaction log that records all transactions related to orders, subscriptions, and donations. The transaction log can be used to trouble shoot payment errors or verify processed payments.
* Added - Support for Woocommerce Subscriptions Version 2.0.0+. Integration with Braintree monthly subscriptions or Woocommerce Subscription's automatic billing.
* Added - Support for multiple subscriptions in shopping cart. 
* Added - Support for mixed shopping cart. 
* Added - Customer can now change payment method on subscriptions.
* Added - Admins can change the recurring payment method on a subscription.
* Added - Ability to validation of postal code and billing address for orders and subscriptions.
* Added - Ability to validation of postal code and billing address for donations. 
* Fixed - Transactions now pass the countryCode for validation within Braintree.
* Fixed - Ajax integration with checkout page to ensure nonce is not replaced during Woocommerce ajax calls on checkout page. 
= 2.1.6 = 
* Fixed - Use of saved payment methods was failing due to false positive check on credit card token.
= 2.1.5 = 
* Fixed - Fixed issue Cannot use object of type WP_Error as array. 
* Fixed - Correct payment type used now displayed on order review page and in Admin order page.
= 2.1.4 = 
* Added - Enhanced Paypal checkout. Admins can now select if they want to only display Paypal as a checkout payment option. 
= 2.1.3 =
* Added - Admins can now see when their license will expire by navigating to the "Activate License" page.
* Added - Improved UX and UI for donation screen. 
* Added - Custom forms can now be used to validate AVS settings for woocommerce checkout and donations. 
= 2.1.2 = 
* Added - Non logged in users can now make donations. 
= 2.1.1 = 
* Fixed - Error message added on donations form indicating failed transaction.
= 2.1.0 = 
* Changed - On the Woocommerce Order Admin page, Admins can now see the Payment type and Masked credit card number that was used for the order. Example. Visa - 401288******1881; Paypal - john.doe@example.com. 
This is compliant with PCI standards. 

= 2.0.9 =
* Fixed - Woocommerce was not detected if plugin loaded before Braintree for Woocommerce. Plugin is now detected regardless of plugin loading order. 
= 2.0.8 =
* Fixed - Updated the payment method title to show "Credit Card" instead of Braintree For Woocommerce. Included check for Woocommerce_Subscriptions activation to prevent any conflicts. 
= 2.0.7 = 
* Fixed - Conflict with folder capitilaization. Wordpress issues fatal error during plugin activation when folders from previous version are lowercase. If you 
receive an error message that Currencies.php and Braintree_Donations.php cannot be found, simply delete the plugin folder and files and reinstall version 2.0.7. 
= 2.0.6 =
* Fixed - Possible issues with checkout if Woocommerce Subscriptions was not activated fixed. 
= 2.0.5 = 
* Added - Integration with Woocommerce Subscriptions. Merchants can now charge for subscription products and services using their Braintree and Woocommerce Subscriptions install.

= 2.0.4 =
* Added - Additional security checks added for transactions when customers using payment method tokens. 

= 2.0.3 = 
* Fix - Bug found in checkout.js. There is the possibility that the response object from braintree does not indicate the payment type. Because of this, additional checks have to be 
made to determine of the payment type is CreditCard or Paypal. 

= 2.0.2 =
* Some themes might of had a conflict with the function "load_admin_scripts" so it was renamed to prevent any future clashes. 
= 2.0.1 =
* Additional setting made in the Woocommerce Payments settings page. Merchants can now control the status that is assigned to orders
when a payment is succesfully processed. 
= 2.0.0 =
* Major update.
* This plugin is now a paid plugin. For those that have already downloaded the plugin, please email mr.clayton@wordpresspayments.co
 to receive a free 20 activation license in order to keep processing payments. The 20 day license should provide merchants with enought time
 to evaluate the new functionality and either pay for a full year, or find a suitable replacement. Please email the developer if you need more than 20 days to either
 find a replacement plugin or evaluate this plugin.
* Fix - when customers opt to save payment data, a random id is generated in the Braintree vault 
 and saved in the merchants wordpress database as user meta. This ensures that no two customers can ever have
 the same id, even across different domains that share a Braintree gateway.
* Fix - woocommerce status is changed to complete upon succesful payment transaction.
* Added - Seperate config screen where merchants can maintain all of their API keys & UX settings.
* Added - Donations functionality. 


= 1.1.7 =
* Updated PHP Braintree library to version 3.5.

= 1.1.6 =
* Update to method that sends the client token to the browser.

= 1.1.5 =
* Removed add_action call in braintree-payment-gateeway.php file as it was a placeholder for later releases. 

= 0.5 =
* List versions from most recent at top to oldest at bottom.


= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Upgrade Notice ==
= 2.4.8 = 
Version 2.4.8 adds support for recurring donations. For more information visit the donation settings page of the plugin. Some merchants received an error message on the checkout page related to an existing iFrame; this issue has been resolved.
= 2.4.7 = 
Version 2.4.7 adds custom form loader functionality. You can add your own html such as a processing message or an animation which will appear over the payment form when checkout takes place. 
= 2.4.6 = 
Some merchants experienced an issue where non Braintree related orders were not rendering on the admin screen correctly. Version 2.4.6 resolves that issue.
= 2.4.5 = 
Merchants can now authorize payments or submit for settlement automatically. 3DS is now supported for card payments. Merchants can now configure the type of PayPal checkout flow that they want.
= 2.4.4 =
2.4.4 improves the PayPal Gateway UX in addition to providing better theme support for the custom forms.

= 2.4.3 = 
2.4.3 comes with new payment forms available. If the provided forms are not enough, users can customize the existing forms or create their own custom forms by following the instructions on the tutorials page.

= 2.3.0 =
2.3.0 is a major update to the plugin. Once updated, you will need to re-enter some of your configuration settings such as enabled payment method icons, donation settings, and subscription settings. 
