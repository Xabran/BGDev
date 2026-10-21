=== WP Fusion ===
Contributors: verygoodplugins
Tags: infusionsoft, user meta, sync, woocommerce
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 2.7.2
WC tested up to: 2.6.4

The only plugin you need for integrating your WordPress site with your CRM.

== Description ==

WP Fusion is a WordPress plugin that connects what happens on your website to your CRM. Using WP Fusion you can build a membership site, keep your customers’ information in sync, capture new leads, record ecommerce transactions, and much more.

= Features =

* Automaticaly create new contacts in your CRM when new users are added in WordPress
	* Can limit user creation to specified user roles
	* Assign tags to newly-created users
* Restrict access to content based on a user's CRM tags
	* Option to redirect to alternate page if requested page is locked
	* Shortcodes to selectively hide/show content within posts
* Apply tags when a user visits a certain page (with configurable delay)
* Configurable synchronization of user meta fields with contact fields
	* Update a contact record in your CRM when a user's profile is updated
* LearnDash and Sensei integration for managing online courses
* Integration with numerous membership and ecommerce plugins

== Installation ==

Upload and activate the plugin, then go to Settings >> WP Fusion. Select your desired CRM, enter your API credentials and click "Test Connection" to verify the connection and perform the first synchronization. This may take some time if you have many user accounts on your site. See our [Getting Started Guide](https://wpfusionplugin.com/documentation/#getting-started-guide) for more information on setting up your application.

== Frequently Asked Questions ==

See our [FAQ](https://wpfusionplugin.com/documentation/).

== Changelog ==

= 2.7.2 =
* Added s2Member integration
* Added support for applying tags when WooCommerce coupons are used
* Added support for syncing AffiliateWP affiliate information
* Fixed returning passwords for imported contacts
* Updates for compatibility with plugin integrations

= 2.7.1 =
* Added LifterLMS support
* Fix for password updates not syncing from UM Account page

= 2.7 =
* Added Restrict Content Pro Integration
* Tag mapping for LearnDash Groups
* Can now sync user password from Ultimate Member reset password page

= 2.6.8 =
* Fix for contact fields not getting correct defaults on first install
* Fixed wrong lists getting assigned when updating AC contacts
* Significant API performance optimizations

= 2.6.7 =
* Enabled webhooks from Ontraport

= 2.6.6 =
* Fixed error in GForms integration

= 2.6.5 =
* Added support for syncing PMPro membership level name
* Fixed tags not applying when WooCommerce orders refunded
* Bugfixes and performance optimizations

= 2.6.4 =
* Batch processing tweaks

= 2.6.3 =
* Admin performance optimizations
* Batch processing / export tool

= 2.6.2 =
* Fix for tag select not appearing under Woo variations
* Formatting filters for date fields in ActiveCampaign
* Added quiz support to Gravity Forms
* Optimizations and performance tweaks

= 2.6.1 =
* Drip bugfixes
* Fix for restricted WooCommerce products not being hidden on some themes

= 2.6 =
* Added Drip CRM support
* Option to run Woo checkout actions asynchronously

= 2.5.5 =
* Updates to support Media Tools Addon

= 2.5.4 =
* Added option to push generated passwords back to CRM
* Added ability to apply tags in LearnDash when a quiz is marked complete
* Added ability to link a tag with an Ultimate Member role for automatic role assignment

= 2.5.3 =
* Fixed bug with WooCommerce variations and user-entered tags
* Fixed BuddyPress error when XProfile was disabled

= 2.5.2 =
* Fix for license activations / updates on hosts with outdated CURL
* Updates to support WPF addons
* Re-introduced import tool for ActiveCampaign users
* PHP 7 optimizations

= 2.5.1 =
* Improvements to initial ActiveCampaign sync
* Added instructions for AC import

= 2.5 =
* Added Paid Memberships Pro support
* Added course / tag relationship mapping for LearnDash courses
* Added automatic detection and mapping for BuddyPress profile fields
* Added "Apply tags when refunded" option for WooCommerce products
* Updated HTTP status codes on HTTP Post responses
* Tweaks to Import function for Ontraport users
* Fix for duplicate contacts being created on email address change with ActiveCampaign
* Fix for resyncing contacts with + symbol in email address

= 2.4.1 =
* Bugfixes for Ontraport integration
* Added Contact Type field mapping for Infusionsoft

= 2.4 =
* Added Ontraport CRM integration

= 2.3.2 =
* MemberMouse beta integration
* Fix for license activation for users on outdated versions of CURL / SSL
* Fix for BuddyPress pages not locking properly

= 2.3.1 =
* Fixed error in bbPress integration on old PHP versions

= 2.3 =
* Added Contact Form 7 support
* All bbPress topics now inherit permissions from their forum
* Added ability to lock bbPress forums archive
* Fixed bug with importing users by tag
* Fixed error with shortcodes using Thrive Content Builder
* Removed Add to Cart links for restricted products on the Woo store page
* Added option to hide restricted products from Woo store page entirely
* Added support for applying tags based on EDD variations

= 2.2.2 =
* Fix for tag shortcodes on AC
* Improvements to tag selection on Woo subscriptions / variations
* Woo Subscription fields now show on variable subscriptions as well
* Updated included Select2 libraries
* Restricted content with no tags specified will now be restricted for non-logged-in-users

= 2.2.1 =
* Fixed fatal error with GForms integration on lower PHP versions

= 2.2 =
* Added support for re-syncing contacts in batches for sites with large numbers of users
* Added support for ActiveCampaign webhooks
* Added support for EDD Recurring Payments
* Simplified URL structure for HTTP POST actions and added debugging output
* Fix for "0" tag appearing with ActiveCampaign tags

= 2.1.2 =
* Fixed bug where AC profiles wouldn't update if email address wasn't present in the form
* Fix for redirect rules not being respected for admins
* Fix for user_email and display_name not updating via HTTP Post

= 2.1.1 =
* Fixed bug affecting [wpf] shortcodes with users who had no tags applied

= 2.1 =
* Added support for applying tags in Woo when a subscription expires, is cancelled, or is put on hold
* Added "Push All" option for incompatible plugins and "user_meta" updates triggered via functions
* Fix for ActiveCampaign accounts with no tags
* Isolated AC API to prevent conflicts with plugins using outdated versions of the same API

= 2.0.10 =
* Bugfix when using tag label in shortcode

= 2.0.9 =
* Fix for tag checking logic with shortcode

= 2.0.8 =
* Fix for has_tag() function when using tag label
* Fixes for conflicts with other plugins using older versions of Infusionsoft API
* Support for re-adding contacts if they've been deleted in the CRM

= 2.0.7 =
* Resync contact now deletes local data if contact was deleted in the CRM
* Update license handler to latest version
* Resynchronize now force resets all tags
* Moved upgrade hook to later in the admin load process

= 2.0.6 =
* Support for manually marking WooCommerce payments as completed
* Improved support for servers with limited API tools
* Fixed wp_fusion()->user->get_tag_id() function to work with ActiveCampaign
* Bugfixes to shortcode content restriction system
* Fix for fields with subfields occasionally not showing up in GForms mapping
* Fix for new Ultimate Member field formats

= 2.0.5 =
* Fix for user accounts not created properly when WooCommerce and WooSubscriptions were both installed
* Added "apply to related lessons" feature to Sensei integration
* WooCommerce will now track leadsources and save them to a customer's contact record

= 2.0.4 =
* Bugfix for PHP notices appearing when shortcodes were in use and current user had no CRM tags
* Added SQL escaping for imported tag labels and categories
* Fix for contact address not updating existing contacts on guest checkout
* Fix for ACF not pulling / pushing field data properly

= 2.0.3 =
* Bugfix for importing users where CRM fields were mapped to multiple local fields
* Bugfix for Setup tab not appearing on initial install

= 2.0.2 =
* Bugfix for notices appearing for admins when admin bar was in use

= 2.0.1 =
* Bugfix for "update" action in HTTP Posts

= 2.0 =
* Complete rewrite and refactoring of core code
* Integration with ActiveCampaign, supporting all of the same features as Infusionsoft
* Custom fields are now available as a dynamic dropdown
* Ability to re-sync tags and custom fields within the plugin
* Integration with Sensei LMS
* Infusionsoft integration upgraded to use XMLRPC 4.0
* 100's of bug fixes, performance enhancements, and other improvements

= 1.6.4 =
* Improved compatibility with other plugins that use the iSDK class
* Changes to options framework to support 3rd party addons
* Added backwards compatibility for PHP versions less than 5.3

= 1.6.3 =
* Fix for registering contacts that already exist in Infusionsoft

= 1.6.2 =
* Fix for saving WooCommerce variation configuration
* Added automatic detection for when contacts are merged
* Improvements to wpf_template_redirect filter
* Added ability to apply tags per Ultimate Member registration form
* Ability to defer adding the contact until after the UM account has been activated
* Fixed bug with tags not appearing on admin user profile page
* Added filters for unsetting post types
* Added wpf_tags_applied and wpf_tags_removed actions

= 1.6.1 =
* Added has_tag function
* Added wpf_template_redirect filter
* Improved detection of registration form fields
* Fixed PHP notices appearing when using ACF
* Updates for compatibility with WP 4.3.1

= 1.6 =
* Can feed Gravity Forms data to Infusionsoft even if the user isn't logged in on your site
* Added support for Easy Digital Downloads
* Fixed bug with pulling date fields into Ultimate Member

= 1.5.2 =
* Fixed a bug with the "any" shortcode method
* More robust handling for user creation

= 1.5.1 =
* Fixed bug with account creation and Ultimate Member user roles

= 1.5 =
* LearnDash integration: can now apply tags on course/lesson/topic completion
* Content restrictions can now apply to child content
* New Ultimate Member fields are detected automatically
* Added ability to set user role via HTTP Post 'add'
* Added 'any' option to shortcodes

= 1.4.5 =
* Fixed global redirects not working properly
* Fixed issue with Preview As in admin bar
* Added 'wpf_create_user' filter
* Allowed for creating / updating users manually
* API improvements

= 1.4.4 =
* Misc. bugfixes with last release

= 1.4.3 =
* Improved compatibility of WooCommerce checkout with caching plugins
* Fixed bug with static page redirects
* Improved Ultimate Member integration
* Added support for combining "tag" and "not" in the WPF shortcode
* Added support for separating multiple shortcode tags with a comma
* Reduced API calls when profiles are updated
* Fixed bugs with guest checkout in WooCommerce

= 1.4.2 =
* Fixed bug with Ultimate Member integration in last release

= 1.4.1 =
* "Resync Contact" now pulls meta data as well
* Can now validate custom fields by name as well as label
* Added warning messages for WP Engine users
* Improved support for Ultimate Member membership plugin
* Fixed bug with redirects on Blog page / archive pages

= 1.4 =
* Added support for locking bbPress forums based on tags
* Added wpf_update_tags and wpf_update_meta shortcodes
* Support for overriding the new user welcome email with plugins
* Fixed bug with API Key generation
* Fixed bug with tags not applying after the specified delay
* Improved integration with WooCommerce checkout

= 1.3.5 =
* Added integration with Ultimate Member plugin

= 1.3.4 =
* Added "User Role" selection to import tool
* Added actions for user added and user updated
* Added "lock all" button to preview bar dropdown
* Fixed bug where tag preview wouldn't work on a static home page
* Fixed bug where shortcodes within the `[wpf]` shortcode wouldn't execute

= 1.3.3 =
* Improved integration support for user meta / profile plugins

= 1.3.2 =
* Tags will be removed when a payment is refunded
* Added support for applying tags with product variations
* Fixed bug with pushing ACF meta data on profile save
* Added support for pulling ACF meta data on profile load

= 1.3.1 =
* Added wpf_woocommerce_payment_complete action
* Added search filter to redirect page select dropdown
* Fixed "Class 'WPF_WooCommerce_Integration'" not found bug

= 1.3 =
* Added ability to import contacts from Infusionsoft as new WordPress users
* Added new plugin API methods for updating meta data and creating new users (see the documentation for more information)
* Added "unlock all" option to frontend admin toolbar
* Tags applied by a WooCommerce subscription can be removed when the subscription fails to charge, a trial period ends, or the subscription is put on hold
* Added support for syncing password and username fields
* Fixed a bug with applying tags at WooCommerce checkout when the user isn't logged in

= 1.2.1 =
* Added pull_user_meta() template tag
* Fixed bug with pushing user meta when no contact ID is found

= 1.2 =
* Added support for syncing multiselect fields with a contact record
* Added ability to trigger a campaign goal when a user profile is updated
* Added ability to manually resync a user profile if a contact record is deleted / recreated
* Now supports syncing with Infusionsoft built in fields. See the Infusionsoft "Table Documentation" for field name reference
* Users registered through a UserPro registration form will now have their password saved in Infusionsoft
* Fixed several bugs with user account creation using a UserPro registration form
* Fixed bug where tag categories with over 1,000 tags wouldn't import fully
* Fixed a bug that would cause checkout to fail with WooCommerce if a user is in guest checkout mode
* Numerous other bugfixes, optimizations, and improvements

= 1.1.5 =
* Fixed bug that would cause a user profile to fail to load when an IS contact wasn't found
* "Preview with tag" dropdown now groups tags by category and sorts alphabetically
* Fixed a bug with applying tags at WooCommerce checkout
* Notices for inactive / expired licenses

= 1.1.4 =
* Check for UserPro header on initial sync bug fixed
* Removed PHP notices on meta box when no tags are present
* "Preview with tag" has been removed from admin screens

= 1.1.3 =
* Automatic update bug fixed

= 1.1.2 =
* Fixed bug where users without email address would kill initial sync

= 1.1.1 =
* Changed name to WP Fusion

= 1.1 =
* EDD software licensing added

= 1.0.3 =
* Cleaned up apply_tags function

= 1.0.2 =
* Misc. bugfixes
* Added ability to apply tags to contact on WooCommerce purchase

= 1.0.1 =
* Misc. bugfixes
* Added content selection dropdown on post meta box

= 1.0 =
* Initial release


== Shortcodes ==

To restrict content based on a user's Infusionsoft tags, wrap the desired content in the WP Fusion shortcode, like so:

`[wpf tag=45] Restricted Content [/wpf]`

You can also specify the tag name, like so:

`[wpf tag="New Customer"] Restricted Content [/wpf]`

To show content only if a user _doesn't_ have a certain tag, use the following syntax:

`[wpf not=45] Restricted Content [/wpf]`

To force an update of the current user's tags before loading the rest of the page, use:

`[wpf_update_tags]`

To force an update of the current user's meta data before loading the rest of the page, use:

`[wpf_update_meta]`
