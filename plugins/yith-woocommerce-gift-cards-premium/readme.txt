=== YITH WooCommerce Gift Cards Premium ===

Contributors: yithemes
Tags: gift card, gift cards, coupon, gift, discount
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 1.4.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Changelog ==

= Version 1.4.11 - Released: Nov 03, 2016 =

* Fixed: when using the 'gift this product' feature, the amount is not correctly set.

= Version 1.4.10 - Released: Nov 02, 2016 =

* Added: gift card amounts managed through the accounting.js script
* Updated: gift card product page layout, removing default colors and style to let the page being rendered by the theme
* Fixed: gift card amount set to 0 when in manual mode only.

= Version 1.4.9 - Released: Oct 20, 2016 =

* Fixed: backward compatibility with WooCommerce version 2.6.0 or sooner: wrong product title shown when a gift card is added to the cart

= Version 1.4.8 - Released: Oct 11, 2016 =

* Updated: removed duplicated 'Add to cart' button
* Updated: the amounts dropdown is now selected on first item by default
* Updated: the preview layout on single product page
* Fixed: empty product title shown after adding a gift card to cart
* Added: spanish translation files

= Version 1.4.7 - Released: Jul 27, 2016 =

* Updated: Aelia Currency Switcher compatibility to latest plugin version
* Added: multiple BCC recipients for gift cards sold
* Added: template for gift card footer
* Added: template for gift card suggestion section

= Version 1.4.6 - Released: Jul 12, 2016 =

* Fixed: manual amount in physical product do not work
* Fixed: total not updated correctly in mini-cart

= Version 1.4.5 - Released: Jul 05, 2016 =

* Fixed: tab 'general' not visible in gift cards products after the update to WooCommerce 2.6.2

= Version 1.4.4 - Released: Jun 30, 2016 =

* Fixed: the amount shown on mini cart was not converted in current currency when using the Aelia Currency Switcher plugin

= Version 1.4.3 - Released: Jun 30, 2016 =

* Fixed: mini cart amounts not updated when a gift card is added to the cart

= Version 1.4.2 - Released: Jun 28, 2016 =

* Fixed: email footer and header not shown when using the "send now" feature

= Version 1.4.1 - Released: Jun 27, 2016 =

* Updated: do not show the amount dropdown if only manual amount is enabled

= Version 1.4.0 - Released: Jun 14, 2016 =

* Added: WooCommerce 2.6 ready
* Added: set the gift cards product as downloadable to let the payment gateway to set the order as completed when paid
* Fixed: issue that would prevent to edit a gift card when the order was in processing status
* Fixed: a warning was shown in product of type other than the gift cards due to a conflict with YITH Dynamic Pricing
* Fixed: wrong gift card object retrieved then using a numeric gift card code

= Version 1.3.8 - Released: May 18, 2016 =

* Updated: the form-gift-cards.php template file
* Fixed: the discount code was not applied correctly clicking on the email received

= Version 1.3.7 - Released: May 09, 2016 =

* Added: allow manual entered amounts for physical products
* Added: support to WPML Multiple Currency
* Added: let the vendor to manage his own gift cards when YITH Multi Vendor is active
* Added: gift card code fields could be removed from checkout page via a filter
* Fixed: wrong amount value retrieved when reading old gift cards

= Version 1.3.6 - Released: May 05, 2016 =

* Fixed: gift cards generated twice when used within YITH Multi Vendor plugin

= Version 1.3.5 - Released: Apr 28, 2016 =

* Added: support to WooCommerce 2.6.0 for edit product page
* Fixed: out of date get_status() function call removed from the /templates/myaccount/my-giftcards.php file
* Fixed: conflict on emails sent by the YITH WooCommerce Points and Rewards plugin

= Version 1.3.4 - Released: Apr 26, 2016 =

* Fixed: the 'alt' and 'title' attribute of the gift cards template were not localizable
* Updated: yith-woocommerce-gift-cards.pot file

= Version 1.3.3 - Released: Apr 21, 2016 =

* Fixed: the custom image chosen while purchasing a gift card was not used in the email
* Fixed: resetting the custom image from the edit product page, the featured image was not used anymore

= Version 1.3.2 - Released: Apr 13, 2016 =

* Fixed: customize gift card button not shown if 'show template' is set to false

= Version 1.3.1 - Released: Apr 12, 2016 =

* Updated: pre-printed gift cards are not sent automatically when the code is filled
* Fixed: gallery items do not load properly
* Fixed: option for show the shop logo on the gift card template not visible on plugin settings

= Version 1.3.0 - Released: Apr 11, 2016 =

* Added: create a gallery of standard design from which the customer can choose the one that best fits the festivity or recurrence for which the gift card is being purchased
* Added: new feature for shops selling pre-printed physical gift cards, you can add the code manually instead of being auto generated
* Added: new option let you use the product featured image can be used as the gift card header image
* Added: from the product edit page you can set any image from the media gallery as the gift card header image
* Added: new option let you choose if shop logo should be shown on the gift card template
* Added: you can choose between two layouts for the gift card template
* Fixed: email header not visible when using the bulk action "Order actions" from the order page

= Version 1.2.12 - Released: Mar 22, 2016 =

* Fixed: gift cards table filter fails after "send now" button pressed
* Fixed: Aelia Currency Switcher add-on, wrong currency shown on emails
* Fixed: unwanted edit link shown on gift cards email
* Fixed: standard coupon not accepted when used together with a gift card

= Version 1.2.11 - Released: Mar 15, 2016 =

* Fixed: wrong gift card value shown on gift this product for variable product

= Version 1.2.10 - Released: Mar 14, 2016 =

* Updated: on back end gift cards table page, show the sum of order totals instead of subtotals
* Fixed: duplicated orders shown on back end gift cards table page

= Version 1.2.9 - Released: Mar 11, 2016 =

* Added: new gift card status: "Dismissed" is for gift card not valid and no more usable.
* Added: Syncronization between gift cards status and order status
* Fixed: wrong calculation on gift card when a manual amount is entered
* Updated: YITH Plugin FW

= Version 1.2.8 - Released: Mar 09, 2016 =

* Added: Rich snippets for the gift card product
* Added: automatic cart discount clicking from the email received
* Deleted: yith-status-options.php file no more used

= Version 1.2.7 - Released: Mar 07, 2016 =

* Fixed: ywgc-frontend.min.js not updated to the latest version
* Added: let the customer to change the recipient of gift card, crating a new gift card with update balance
* Added: in my-account page show the order where a gift card was used
* Updated: yith-woocommerce-gift-cards.pot in /languages folder

= Version 1.2.6 - Released: Mar 01, 2016 =

* Updated: all the gift cards used by a customer are now shown on my-account page
* Added: Aelia Currency Switcher compatibility let you use gift cards in multiple currency environment

= Version 1.2.5 - Released: Feb 26, 2016 =

* Added: gift cards can be set as disabled and no discount will be applied
* Added: template myaccount/my-giftcards.php for showing gift cards balance
* Added: show balance of used gift cards in my-account page
* Fixed: coupon code section shown twice on cart page based on the theme used
* Updated: removed filter yith_woocommerce_gift_cards_empty_price_html

= Version 1.2.4 - Released: Feb 11, 2016 =

* Fixed: in cart page the "Coupon" text was not localizable.
* Fixed: the class selector for datepicker conflict with other datepicker in the page
* Fixed: adding to cart of product with "sold individually" flag setted fails
* Fixed: require_once of class.ywgc-product-gift-card.php lead sometimes to "the Class 'WC_Product' not found" fatal error
* Added: compatibility with the YITH WooCommerce Points and Rewards plugin

= Version 1.2.3 - Released: Jan 18, 2016 =

* Fixed: notification email on gift card code used not delivered to the customer

= Version 1.2.2 - Released: Jan 15, 2016 =

* Added: compatibility with YITH WooCommerce Dynamic Pricing

= Version 1.2.1 - Released: Jan 14, 2016 =

* Fixed: missing parameter 2 on emails

= Version 1.2.0 - Released: Jan 13, 2016 =

* Updated: gift card code is generated only one time, even if the order status changes to 'completed' several time
* Updated: plugin ready for WooCommerce 2.5
* Updated: removed action ywgc_gift_cards_email_footer for woocommerce_email_footer on email template
* Fixed: prevent gift card message containing HTML or scripts to be rendered
* Added: resend gift card email on the resend order emails dropdown on order page

= Version 1.1.6 - Released: Dec 28, 2015 =

* Added: digital gift cards content shown on gift cards table in admin dashboard
* Added: option to force gift card code sending when automatic sending fails

= Version 1.1.5 - Released: Dec 14, 2015 =

* Fixed: YITH Plugin Framework breaks updates on WordPress multisite

= Version 1.1.4 - Released: Dec 11, 2015 =

* Fixed: manual entered text not used in emails

= Version 1.1.3 - Released: Dec 08, 2015 =

* Fixed: YIT panel script not enqueued in admin

= Version 1.1.2 - Released: Dec 07, 2015 =

* Fixed: temporary gift card tax calculation
* Updated: temporary gift card is visible on dashboard so it can be set the title and the image

= Version 1.1.1 - Released: Nov 30 2015 =

* Fixed: Emogrifier warning caused by typo in CSS
* Fixed: problem that prevent the gift card email from being sent
* Fixed: ask for a valid date when postdated delivery is checked

= Version 1.1.0 - Released: Nov 26 2015 =

* Added: optionally redirect to cart after a gift cards is added to cart
* Fixed: postdated gift cards was sent on wrong date

= Version 1.0.9 - Released: Nov 25 2015 =

* Fixed: missing function on YIT Plugin Framework
* Updated: gift cards sender and recipient details added on emails

= Version 1.0.8 - Released: Nov 24 2015 =

* Fixed: wrong gift card values generated when in WooCommerce Tax Options, prices are set as entered without tax and displayd inclusiding taxes

= Version 1.0.7 - Released: Nov 20 2015 =

* Updated: gift card price support price including or excluding taxes

= Version 1.0.6 - Released: Nov 19 2015 =

* Updated: Gift Cards object cast to array for third party compatibility

= Version 1.0.5 - Released: Nov 17 2015 =

* Fixed: tax not deducted when gift card code was used

= Version 1.0.4 - Released: Nov 13 2015 =

* Fixed: multiple gift cards code not generated

= Version 1.0.3 - Released: Nov 12 2015 =

* Added: tax class on gift card product type
* Updated: changed action used for YITH Plugin FW loading
* Updated: gift card full amount(product price plus taxes) used for cart discount

= Version 1.0.2 - Released: Nov 06, 2015 =

* Fixed: coupon conflicts at checkout

= Version 1.0.1 - Released: Oct 29, 2015 =

* Update: YITH plugin framework

= Version 1.0.0 - Released: Oct 22, 2015 =

* Initial release