<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'wc_tradegecko_register_settings' );

/**
 * Register the settings
 *
 * Settings options are as follows: <br />
 *
 * text, textarea, select, multiselect, password, radio, hook, upload, rich_editor <br />
 *
 * Options for the settings types are: <br />
 *
 * id                - option unique ID. It will be used as id attribute and as name
 * class        - additional class for the option
 * label        - the label
 * size                - size type - regular, small, large
 * css                - any css styles to add to the option
 * desc                - option description
 * desc_style        - tip, text - How the description should be visualized
 * options        - for Select and multicheck. The options to be vizualized.
 * before_option - Output to visualize before the option
 * after_option        - Output to visualize after the option
 *
 * @return void
 */
function wc_tradegecko_get_settings() {
	$wc_tradegecko_settings = array(
		'general'       => apply_filters(
			'wc_tradegecko_settings_general',
			array(
				array(
					'id'         => 'enable',
					'name'       => '',
					'label'      => __( 'Activate TradeGecko Integration', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( 'Enable this to take advantage of the TradeGecko integration features.', WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'std'        => '1',
					'type'       => 'checkbox',
				),

				array(
					'id'         => 'enable_debug',
					'name'       => '',
					'label'      => __( 'Enable Debug Logging', WC_TradeGecko_Init::$text_domain ),
					'desc'       => sprintf(
						__(
							'This option will provide you with a step by step
 								log of all manipulations done during a synchronization.
 								Please enable, if needed ONLY. The debug log can get
 								quite big, depending on amount of orders and products.<br />
 								The debug log will be inside %s.', WC_TradeGecko_Init::$text_domain
						),
						'<code>' . WC_Compat_TG::wc_get_debug_file_path( 'tradegecko' ) .
						'</code>'
					),
					'desc_style' => 'text',
					'std'        => '0',
					'type'       => 'checkbox',
				),

				array(
					'id'   => 'inventory_section',
					'name' => '<strong>' . __( 'Product Inventory Settings', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you setup the inventory and product synchronization settings.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'         => 'inventory_sync',
					'name'       => '',
					'label'      => __( 'Stock Synchronization', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __(
						'Enable, to sync your WooCommerce products inventory with
								TradeGecko. The WooCommerce inventory for each
								product will be synchronized with the TradeGecko
								inventory.', WC_TradeGecko_Init::$text_domain
					),
					'desc_style' => 'text',
					'std'        => '1',
					'type'       => 'checkbox',
				),
				array(
					'id'         => 'product_price_sync',
					'name'       => '',
					'label'      => __( 'Price Synchronization', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( "Enable, to sync your WooCommerce products prices with TradeGecko. The WooCommerce products prices will be synchronized with the products prices in TradeGecko.", WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'std'        => '1',
					'type'       => 'checkbox',
				),
				array(
					'id'         => 'product_title_sync',
					'name'       => '',
					'label'      => __( 'Title Synchronization', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( "Enable, to sync your WooCommerce products title with TradeGecko products title. Sync the product titles only, if they are going to be changing often. Otherwise, good practice would be to sync them once and then disable it.", WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'std'        => '1',
					'type'       => 'checkbox',
				),

				array(
					'id'      => 'product_allow_backorders',
					'name'    => __( 'Set Allow Backorders', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Choose the way backorders are allowed for your products.', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'product_allow_backorders',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => array( 'allow' => 'Allow', 'notify' => 'Allow, but notify the customer' ),
				),

				array(
					'id'      => 'available_currency_id',
					'name'    => __( 'TradeGecko Currency', WC_TradeGecko_Init::$text_domain ),
					'desc'    => sprintf( __( 'Choose the TradeGecko currency you will use for your store. Your store currency is: %s', WC_TradeGecko_Init::$text_domain ), get_woocommerce_currency() ),
					'class'   => WC_TradeGecko_Init::$prefix . 'available_currency_id',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => WC_TradeGecko_Init::get_tradegecko_currencies(),
				),

				array(
					'id'      => 'regular_price_id',
					'name'    => __( 'Map Product Regular Price', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Choose the TradeGecko Price List you want to use, to update the products Regular Price.', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'regular_price_id',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => WC_TradeGecko_Init::get_tradegecko_price_lists(),
				),

				array(
					'id'         => 'allow_sale_price_mapping',
					'name'       => '',
					'label'      => __( 'Allow Sale Price Mapping', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( "Enable to allow TradeGecko to modify your products Sale Price. Sale prices will be controlled by the TradeGecko Price List you choose for <strong>'Map Product Sale Price'<strong>", WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'type'       => 'checkbox',
				),

				array(
					'id'      => 'sale_price_id',
					'name'    => __( 'Map Product Sale Price', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( "Choose the TradeGecko Price List you want to use, to update the products Sale Price.<br/>If the price in the Price List is 0, then the Sale Price will also be removed leaving only the Regular Price.", WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'sale_price_id',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => WC_TradeGecko_Init::get_tradegecko_price_lists(),
				),

				array(
					'id'      => 'stock_location_id',
					'name'    => __( 'Stock Location', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( "Choose the Stock Location your store stock will be synced to.", WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'stock_location_id',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => WC_TradeGecko_Init::get_tradegecko_stock_locations(),
				),

				array(
					'id'         => 'sync_products_in_cart',
					'name'       => '',
					'label'      => __( 'Sync The Stock Of The Products In Cart', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( "Sync the stock of each product in the cart, when the customer loads the Cart Page. It is a double check, ensuring that each product has enough stock, before you sell it. However, it can also affect your Cart Page loading time.", WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'type'       => 'checkbox',
				),

				array(
					'id'   => 'orders_section',
					'name' => '<strong>' . __( 'Orders Settings', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you setup the orders synchronization settings.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),

				array(
					'id'         => 'orders_sync',
					'name'       => '',
					'label'      => __( 'Orders Synchronization', WC_TradeGecko_Init::$text_domain ),
					'desc'       => __( 'Enable, to sync your WooCommerce orders with TradeGecko. The WooCommerce orders will be created send to TradeGecko and orders status will be updated as it is updated in TradeGecko. Your Customers info will be synced together with the orders.', WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'std'        => '1',
					'type'       => 'checkbox',
				),

				array(
					'id'    => 'order_line_items_sync',
					'desc'  => sprintf(
						__(
							"Enable to sync any changes made to already exported order.%s"
							. "%sIMPORTANT:%s This feature is useful for merchants, who often change aspects of their orders(e.g. line items, shipping charges). "
							. "Don't enable, if you don't make changes to your orders, as this feature can greatly increase your Order Update Sync times.",
							WC_TradeGecko_Init::$text_domain
						), '<br/>', '<strong>', '</strong>'
					),
					'class' => WC_TradeGecko_Init::$prefix . 'order_line_items_sync',
					'label' => __( 'Sync Order Line Items', WC_TradeGecko_Init::$text_domain ),
					'type'  => 'checkbox',
					'std'   => '0',
				),

				array(
					'id'      => 'order_line_items_update_direction',
					'name'    => __( 'Order Line Items Update Direction', WC_TradeGecko_Init::$text_domain ),
					'desc'    => sprintf(
						__(
							'If you enable "Sync Order Line Items", choose the direction you are going to make changes to your orders.%s'
							. 'WooCommerce to TrageGecko: you make changes to WooCommerce orders and TrageGecko will be updated.%s'
							. 'TrageGecko to WooCommerce: you make changes to TrageGecko orders and WooCommerce will be updated.',
							WC_TradeGecko_Init::$text_domain
						), '<br/>', '<br/>'
					),
					'class'   => WC_TradeGecko_Init::$prefix . 'order_line_items_update_direction',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => array( 'wc_to_tg' => 'WooCommerce to TrageGecko', /*'tg_to_wc' => 'TrageGecko to WooCommerce'*/ ),
					'std'     => 'wc_to_tg',
				),

				array(
					'id'         => 'order_number_prefix',
					'name'       => __( 'Order Number Prefix', WC_TradeGecko_Init::$text_domain ),
					'label'      => '',
					'desc'       => __( 'Enter an prefix word that will identify the store orders from your other channel orders.', WC_TradeGecko_Init::$text_domain ),
					'desc_style' => 'text',
					'std'        => 'WooCommerce-',
					'type'       => 'text',
				),

				array(
					'id'      => 'order_fulfillment_sync',
					'name'    => __( 'Sync Order Fulfillments', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __(
						'Do you want to pull the shipping information from TG and
							display it to the customers? The fulfillment information will be
							displayed at the View Order page and Order Completed emails.
							You will also be able to edit it in WooCommerce from an
							order panel meta box.', WC_TradeGecko_Init::$text_domain
					),
					'class'   => WC_TradeGecko_Init::$prefix . 'order_fulfillment_sync',
					'css'     => 'width: 300px;',
					'type'    => 'select',
					'options' => array( 'full' => 'Sync, but only when full order is shipped', 'partial' => 'Sync, even partial shipments', 'no' => 'Do not sync' ),
				),
			)
		),
		'api'           => apply_filters(
			'wc_tradegecko_settings_api',
			array(
				array(
					'id'   => 'get_api_credentials',
					'name' => '',
					'desc' => '',
					'type' => 'hook',
				),
				array(
					'id'   => 'api_section',
					'name' => '<strong>' . __( 'Standard Token Settings', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you setup the TradeGecko API Standard Refresh Token settings.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'    => 'client_id',
					'name'  => __( 'API Application Id', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enter here the your API Application Id', WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'client_id',
					'type'  => 'text',
				),
				array(
					'id'    => 'client_secret',
					'name'  => __( 'API Secret', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enter here the your API Secret. You will obtain that after you register your API Application.', WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'client_secret',
					'type'  => 'password',
				),
				array(
					'id'    => 'redirect_uri',
					'name'  => __( 'Redirect URI', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enter here your API Redirect URI. This is the redirect uri you entered when you registered your API Application.<br/> The Redirect URI should be: ' . admin_url( '/admin.php' ), WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'redirect_uri',
					'type'  => 'text',
				),
				array(
					'id'    => 'auth_code',
					'name'  => __( 'Authorization Code', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Here you will see the Authorization Code given to you when you Authorize the TradeGecko Application.', WC_TradeGecko_Init::$text_domain ) . '<br/>' . __( '<strong>IMPORTANT:</strong> If you change any of the above credentials, you need to obtain a new Authorization Code as well.', WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'auth_code',
					'type'  => 'text',
				),
				array(
					'id'   => 'get_authorization_code',
					'name' => '',
					'desc' => __( 'Pressing the button will lead you to a TradeGecko page, where you will be asked to grant access and give Authorization to the application.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
				array(
					'id'   => 'api_privileged_token_section',
					'name' => '<strong>' . __( 'Privileged Token Settings', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => sprintf( __( 'This section will help you setup the TradeGecko API %sPrivileged Access Token%s settings.' ), '<strong>', '</strong>' ),
					'type' => 'header',
				),
				array(
					'id'    => 'privileged_access_token',
					'name'  => __( 'Privileged Access Token', WC_TradeGecko_Init::$text_domain ),
					'desc'  => sprintf(
						__(
							'Enter here the Privileged Access Token. You can create/revoke Privileged tokens at any time | %sManage Tokens%s',
							WC_TradeGecko_Init::$text_domain
						), '<a href="' . wc_tradegecko_manage_api_credentials_url( array( 'privileged' => '1' ) ) . '" target="_blank">', '</a>'
					),
					'class' => WC_TradeGecko_Init::$prefix . 'privileged_access_token',
					'type'  => 'password',
				),
			)
		),

		'sync'          => apply_filters(
			'wc_tradegecko_settings_sync',
			array(
				array(
					'id'   => 'auto_sync_section',
					'name' => '<strong>' . __( 'Automatic Orders Sync', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you schedule automatic order synchronizations.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'    => 'automatic_sync',
					'name'  => __( 'Order Export', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enable, to be able to setup automatic order export sync schedule.<br />Next scheduled order export sync: ' . WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_synchronization' ) ), WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'automatic_sync',
					'label' => __( 'Turn on Automatic Orders Export Synchronization', WC_TradeGecko_Init::$text_domain ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => 'sync_time_interval',
					'name'    => __( 'Sync Time Interval', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the time interval you want the automatic order synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'sync_time_interval',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => wc_tradegecko_get_intervals(),
				),
				array(
					'id'      => 'sync_time_period',
					'name'    => __( 'Sync Time Period', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'sync_time_period',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'    => 'automatic_order_update_sync',
					'name'  => __( 'Order Update', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enable, to be able to setup automatic order update sync schedule.<br />Next scheduled order update sync: ' . WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_order_update_synchronization' ) ), WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'automatic_sync',
					'label' => __( 'Turn on Automatic Orders Update Synchronization', WC_TradeGecko_Init::$text_domain ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => 'order_update_sync_time_interval',
					'name'    => __( 'Sync Time Interval', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the time interval you want the automatic order synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'order_update_sync_time_interval',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => wc_tradegecko_get_intervals(),
				),
				array(
					'id'      => 'order_update_sync_time_period',
					'name'    => __( 'Sync Time Period', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'order_update_sync_time_period',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'   => 'auto_inventory_sync_section',
					'name' => '<strong>' . __( 'Automatic Inventory Sync', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you schedule automatic inventory synchronizations.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'    => 'automatic_inventory_sync',
					'name'  => __( 'Inventory Synchronization', WC_TradeGecko_Init::$text_domain ),
					'desc'  => __( 'Enable, to be able to setup automatic inventory sync schedule.<br />Next scheduled inventory sync: ' . WC_TradeGecko_Init::get_formatted_datetime( wp_next_scheduled( 'wc_tradegecko_inventory_synchronization' ) ), WC_TradeGecko_Init::$text_domain ),
					'class' => WC_TradeGecko_Init::$prefix . 'automatic_inventory_sync',
					'label' => __( 'Turn on Automatic Inventory Synchronization', WC_TradeGecko_Init::$text_domain ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => 'sync_inventory_time_interval',
					'name'    => __( 'Sync Time Interval', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the time interval you want the automatic inventory synchronization to be in.<br/><strong>IMPORTANT:</strong> Please make sure the three sync schedules are set at least <strong>10 minutes</strong> apart from each other.', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'sync_inventory_time_interval',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => wc_tradegecko_get_intervals(),
				),
				array(
					'id'      => 'sync_inventory_time_period',
					'name'    => __( 'Sync Time Period', WC_TradeGecko_Init::$text_domain ),
					'desc'    => __( 'Select the period you want the above interval to be in. For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every 5 Days', WC_TradeGecko_Init::$text_domain ),
					'class'   => WC_TradeGecko_Init::$prefix . 'sync_inventory_time_period',
					'css'     => 'width: 100px;',
					'type'    => 'select',
					'options' => array( 'MINUTE_IN_SECONDS' => 'Minutes', 'HOUR_IN_SECONDS' => 'Hours', 'DAY_IN_SECONDS' => 'Days' ),
				),

				array(
					'id'   => 'manual_sync_section',
					'name' => '<strong>' . __( 'Manual Sync', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you perform manual synchronizations.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'   => 'manual_sync',
					'name' => '',
					'desc' => __( 'Pressing the button will perform a manual orders export synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 order to update and export. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
				array(
					'id'   => 'manual_order_update_sync',
					'name' => '',
					'desc' => __( 'Pressing the button will perform a manual orders update synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 order to update and export. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
				array(
					'id'   => 'manual_inventory_sync',
					'name' => '',
					'desc' => __( 'Pressing the button will perform a manual inventory synchronization.<br/><strong>IMPORTANT: </strong>Do not click the button, if you have more than 100 products to update and sync. Please schedule an automatic synchronization event and wait for it to run instead.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
				array(
					'id'   => 'debug_sync_section',
					'name' => '<strong>' . __( 'Running Processes Visual', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'Visual section of the sync processes.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'   => 'running_orders_export',
					'name' => __( 'Order Export', WC_TradeGecko_Init::$text_domain ),
					'desc' => '',
					'type' => 'hook',
				),
				array(
					'id'   => 'running_orders_update',
					'name' => __( 'Order Update', WC_TradeGecko_Init::$text_domain ),
					'desc' => '',
					'type' => 'hook',
				),
				array(
					'id'   => 'running_inventory',
					'name' => __( 'Inventory', WC_TradeGecko_Init::$text_domain ),
					'desc' => '',
					'type' => 'hook',
				),
				array(
					'id'   => 'clear_synced_products',
					'name' => __( 'Clear Synced Products', WC_TradeGecko_Init::$text_domain ),
					'desc' => __( 'This option will the mapping of all your products to the TG variants, allowing you to sync them again in the next sceduled Inventory Sync.<br/>It is useful when changing TradeGecko accounts.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
			)
		),
		'sync_log'      => apply_filters(
			'wc_tradegecko_settings_sync_log',
			array(
				array(
					'id'   => 'clear_sync',
					'name' => '',
					'desc' => __( 'Press the button to clear the synchronization logs.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),

				array(
					'id'   => 'sync_log_table',
					'name' => '',
					'desc' => __( 'Table to dispay the synchronization logs.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
			)
		),
		'import_export' => apply_filters(
			'wc_tradegecko_settings_import_export',
			array(
				array(
					'id'   => 'export_section',
					'name' => '<strong>' . __( 'Products Export', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you Export your products to TradeGecko CSV format.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'   => 'export_products',
					'desc' => __( 'Click the button above to export your store products into TradeGecko compatible CSV file.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
				array(
					'id'   => 'import_section',
					'name' => '<strong>' . __( 'Products Import', WC_TradeGecko_Init::$text_domain ) . '</strong>',
					'desc' => __( 'This section will help you Import your products from TradeGecko CSV file.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'header',
				),
				array(
					'id'   => 'import_products',
					'desc' => __( 'Import your TradeGecko products CSV into WooCommerce.', WC_TradeGecko_Init::$text_domain ),
					'type' => 'hook',
				),
			)
		),
	);

	return $wc_tradegecko_settings;
}

/**
 * Register the Settings and put them into tabs
 */
function wc_tradegecko_register_settings() {
	$tg_settings = wc_tradegecko_get_settings();

	foreach ( $tg_settings as $tab => $settings ) {

		if ( false == get_option( WC_TradeGecko_Init::$prefix . 'settings_' . $tab ) ) {
			add_option( WC_TradeGecko_Init::$prefix . 'settings_' . $tab );
		}

		add_settings_section(
			WC_TradeGecko_Init::$prefix . 'settings_' . $tab,
			__return_null(),
			'__return_false',
			WC_TradeGecko_Init::$prefix . 'settings_' . $tab
		);

		foreach ( $tg_settings[$tab] as $option ) {
			add_settings_field(
				WC_TradeGecko_Init::$prefix . 'settings_' . $tab . '[' . $option['id'] . ']',
				isset( $option['name'] ) ? $option['name'] : '',
				function_exists( WC_TradeGecko_Init::$prefix . '' . $option['type'] . '_callback' ) ? WC_TradeGecko_Init::$prefix . '' . $option['type'] . '_callback' : 'wc_tradegecko_missing_callback',
				WC_TradeGecko_Init::$prefix . 'settings_' . $tab,
				WC_TradeGecko_Init::$prefix . 'settings_' . $tab,
				array(
					'id'            => isset( $option['id'] ) ? $option['id'] : '',
					'desc'          => isset( $option['desc'] ) ? $option['desc'] : '',
					'desc_style'    => isset( $option['desc_style'] ) ? $option['desc_style'] : '',
					'css'           => isset( $option['css'] ) ? $option['css'] : '',
					'class'         => isset( $option['class'] ) ? $option['class'] : '',
					'name'          => isset( $option['name'] ) ? $option['name'] : '',
					'label'         => isset( $option['label'] ) ? $option['label'] : '',
					'section'       => $tab,
					'size'          => isset( $option['size'] ) ? $option['size'] : null,
					'options'       => isset( $option['options'] ) ? $option['options'] : '',
					'std'           => isset( $option['std'] ) ? $option['std'] : '',
					'before_option' => isset( $option['before_option'] ) ? $option['before_option'] : '',
					'after_option'  => isset( $option['after_option'] ) ? $option['after_option'] : '',
				)
			);
		}

		register_setting( WC_TradeGecko_Init::$prefix . 'settings_' . $tab, WC_TradeGecko_Init::$prefix . 'settings_' . $tab, WC_TradeGecko_Init::$prefix . 'settings_sanitize' );
	}
}

/**
 * Show settings description
 *
 * @since 1.0
 *
 * @param type $args
 *
 * @return string
 */
function wc_tradegecko_get_setting_description( $args ) {
	if ( $args['desc_style'] == 'tip' ) {
		$description = '<img class="help_tip" width="16" height="16" data-tip="' . esc_attr( $args['desc'] ) . '" src="' . WC_TradeGecko_Init::$plugin_url . 'assets/images/help.png" />';
	} elseif ( $args['desc_style'] == 'text' ) {
		$description = '<br /><span class="description">' . $args['desc'] . '</span>';
	} else {
		$description = '<br /><span class="description">' . $args['desc'] . '</span>';
	}

	return $description;
}

/**
 * Header Callback
 *
 * Renders the header. <br />
 * Header is currently not shown. May not be needed.
 * REVIEW
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_header_callback( $args ) {
	$html = '';
	if ( '' != $args['desc'] ) {
		$html .= '<div class="settings_header">';
		$html .= '<hr/>';
		$html .= '<span class="description">';
		$html .= $args['desc'];
		$html .= '</span>';
		$html .= '<hr/>';
		$html .= '</div>';
	}

	echo $html;
}

/**
 * Checkbox Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_checkbox_callback( $args ) {

	$checked = isset( WC_TradeGecko_Init::$settings[$args['id']] ) ? checked( 1, WC_TradeGecko_Init::$settings[$args['id']], false ) : '';
	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';
	$label = esc_html( $args['label'] );

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<legend class="screen-reader-text"><span>' . $label . '</span></legend>';
	$html .= '<label for="' . $id . '" > ';
	$html .= '<input name="' . $name . '" class="' . $class . ' " id="' . $id . '" type="checkbox" value="1" style="' . $css . '" ' . $checked . ' /> ';

	$html .= $label . '</label>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Multicheck Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_multicheck_callback( $args ) {

	$html = '<fieldset>';
	$html .= $args['before_option'];
	foreach ( $args['options'] as $key => $option ):
		if ( isset( WC_TradeGecko_Init::$settings[$args['id']][$key] ) ) {
			$enabled = $option;
		} else {
			$enabled = null;
		}

		$id =  esc_attr( $args['id'] );
		$section = esc_attr( $args['section'] );
		$class = esc_attr( $args['class'] );
		$css = esc_attr( $args['css'] );
		$key = esc_attr( $key );
		$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . '][' . $key . ']';
		$label = esc_html( $option );

		$html .= '<input
			name="' . $name . '"
			class="' . $class . ' "
			id="' . $name .'"
			type="checkbox"
			style="' . $css . '"
			value="' . $option . '" ' . checked( $option, $enabled, false ) . '/>';

		$html .= '&nbsp;';

		$html .= '<label
			for="' . $name .'">' . $label . '
			</label><br/>';
	endforeach;
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Radio Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_radio_callback( $args ) {


	$html = '<fieldset>';
	$html .= $args['before_option'];
	foreach ( $args['options'] as $key => $option ) :
		$checked = false;
		if ( isset( WC_TradeGecko_Init::$settings[$args['id']] ) && WC_TradeGecko_Init::$settings[$args['id']] == $key ) {
			$checked = true;
		} elseif ( $args['std'] == $key ) {
			$checked = true;
		}

		$id =  esc_attr( $args['id'] );
		$section = esc_attr( $args['section'] );
		$class = esc_attr( $args['class'] );
		$css = esc_attr( $args['css'] );
		$esc_key = esc_attr( $key );
		$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';
		$label = esc_html( $option );

		$html .= '<input
			name="' . $name .'"
			style="' . $css . '"
			class="' . $class . ' "
			id="' . $esc_key . '"
			type="radio"
			value="' . $esc_key . '" ' . checked( true, $checked, false ) . '/>';

		$html .= '&nbsp;';

		$html .= '<label for="' . $esc_key . '">' . $label . '</label>';

		$keys = array_keys( $args['options'] );
		$last = end( $keys );

		$html .= ( $key == $last ) ? '' : '<br/>';
	endforeach;
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;

}

/**
 * Text Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_text_callback( $args ) {

	if ( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) {
		$value = esc_attr( WC_TradeGecko_Init::$settings[ $args['id'] ] );
	} else {
		$value = esc_attr( isset( $args['std'] ) ? $args['std'] : '' );
	}
	$size = esc_attr( isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular' );
	$attr = esc_attr( isset( $args['attr'] ) ? $args['attr'] : '' );
	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']" ' . $attr;

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<input
		type="text"
		class="' . $size . '-text ' . $class . '"
		style="' . $css . '"
		id="' . $id . '"
		name="' . $name .'"
		value="' . $value . '"/>';
	$html .= '<label for="' . $id . '"></label>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_textarea_callback( $args ) {

	if ( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) {
		$value = esc_textarea( WC_TradeGecko_Init::$settings[ $args['id'] ] );
	} else {
		$value = esc_textarea( isset( $args['std'] ) ? $args['std'] : '' );
	}
	$size = esc_attr( isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular' );
	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<textarea
		class="' . $size . '-text ' . $class . ' "
		style="' . $css . '"
		cols="50"
		rows="5"
		id="' . $id . '"
		name="' . $name .'">' . $value . '</textarea>';
	$html .= '<label for="' . $id . '"></label>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Password Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_password_callback( $args ) {

	if ( isset( WC_TradeGecko_Init::$settings[ $args['id'] ] ) ) {
		$value = esc_attr( WC_TradeGecko_Init::$settings[ $args['id'] ] );
	} else {
		$value = esc_attr( isset( $args['std'] ) ? $args['std'] : '' );
	}
	$size = esc_attr( isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular' );
	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';


	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<input
		type="password"
		class="' . $size . '-text ' . $class . ' "
		style="' . $css . '"
		id="' . $id . '"
		name="' . $name .'"
		value="' . $value . '"/>';
	$html .= '<label for="' . $id . '"></label>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Missing Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_missing_callback( $args ) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', WC_TradeGecko_Init::$text_domain ), $args['id'] );
}

/**
 * Select Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_select_callback( $args ) {

	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';

	$html = '<fieldset>';
	$html .= $args['before_option'];
	$html .= '<select
		id="' . $id . '"
		style="' . $css . '"
		class="chosen_select ' . $class . ' "
		name="' . $name .'"/>';

	foreach ( $args['options'] as $option => $option_name ) {
		$selected = isset( WC_TradeGecko_Init::$settings[$args['id']] ) ? selected( $option, WC_TradeGecko_Init::$settings[$args['id']], false ) : '';
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $option_name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="' . $id . '"></label>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_rich_editor_callback( $args ) {
	global $wp_version;

	if ( isset( WC_TradeGecko_Init::$settings[$args['id']] ) ) {
		$value = esc_textarea( WC_TradeGecko_Init::$settings[$args['id']] );
	} else {
		$value = esc_textarea( isset( $args['std'] ) ? $args['std'] : '' );
	}

	$id =  esc_attr( $args['id'] );
	$section = esc_attr( $args['section'] );
	$class = esc_attr( $args['class'] );
	$css = esc_attr( $args['css'] );
	$name = WC_TradeGecko_Init::$prefix . 'settings_' . $section . '[' . $id . ']';

	$html = '<fieldset>';
	$html .= $args['before_option'];
	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		$html .= wp_editor( $value, $name, array( 'textarea_name' => $name, 'editor_class' => $class, 'textarea_rows' => 5 ) );
	} else {
		$html .= '<textarea
			class="large-text  ' . $class . '"
			rows="5"
			cols="10"
			id="' . $name .'"
			name="' . $name .'">' . $value . '</textarea>';
		$html .= '<br/><label for="' . $name . '"></label>';
	}
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= $args['after_option'];
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_hook_callback( $args ) {
	do_action( 'callback_hook_' . $args['id'], $args );
}

/**
 * Settings Sanitization
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_settings_sanitize( $input ) {
	return $input;
}

add_action( 'callback_hook_get_authorization_code', 'wc_tradegecko_get_authorization_code' );
/**
 * Get Auth Code
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_get_authorization_code( $args ) {

	$html = '<fieldset>';
	$html .= '<input type="button" id="wc_tradegecko_get_authorization_code" class="button" value="Get Authorization Code" />';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';
	$html .= do_action( 'wc_tradegecko_get_authorization_code_script' );

	echo $html;
}

add_action( 'wc_tradegecko_get_authorization_code_script', 'wc_tradegecko_get_authorization_code_script' );
/**
 * Output the Get Auth Code Script
 *
 * @since 1.0
 */
function wc_tradegecko_get_authorization_code_script() {
	$url = http_build_query(
		array(
			'response_type' => 'code',
			'redirect_uri'  => WC_TradeGecko_Init::$settings['redirect_uri'],
			'client_id'     => WC_TradeGecko_Init::$settings['client_id']
		)
	);

	?>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('#wc_tradegecko_get_authorization_code').click(function () {
				var url = "https://api.tradegecko.com/oauth/authorize?" + "<?php echo $url; ?>";
				var client_id;
				var redirect_url;
				client_id = jQuery('input.<?php echo WC_TradeGecko_Init::$prefix .'client_id' ?>').val();
				redirect_url = jQuery('input.<?php echo WC_TradeGecko_Init::$prefix .'redirect_uri' ?>').val()

				if (0 >= client_id.length) {

					alert('Please enter "API Application Id" first.');
					jQuery('input.<?php echo WC_TradeGecko_Init::$prefix .'client_id' ?>').focus();

				} else if (0 >= redirect_url.length) {

					alert('Please enter "Redirect URI" first.');
					jQuery('input.<?php echo WC_TradeGecko_Init::$prefix .'redirect_uri' ?>').focus();

				} else {
					window.location.replace(url);
				}

				return false;

			});
		});
	</script>
<?php

}

add_action( 'callback_hook_manual_sync', 'wc_tradegecko_manual_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_sync( $args ) {

	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_manual_sync', 'wc_tradegecko_manual_sync_nonce' );

	$count = WC_TradeGecko_Init::get_processing_orders_count();

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Manual Orders Export Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count;
		$html .= ( 1 == $count ) ? ' Order' : ' Orders';
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_manual_order_update_sync', 'wc_tradegecko_manual_order_update_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_order_update_sync( $args ) {

	$url   = wc_tragedego_get_ajax_url( 'wc_tradegecko_manual_order_update_sync', 'wc_tradegecko_manual_order_update_sync_nonce' );
	$count = WC_TradeGecko_Init::get_processing_orders_count();

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Manual Orders Update Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count;
		$html .= ( 1 == $count ) ? ' Order' : ' Orders';
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_manual_inventory_sync', 'wc_tradegecko_manual_inventory_sync' );
/**
 * Manual sync
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_manual_inventory_sync( $args ) {

	$url   = wc_tragedego_get_ajax_url( 'wc_tradegecko_inventory_manual_sync', 'wc_tradegecko_manual_inventory_sync_nonce' );
	$count = WC_TradeGecko_Init::get_products_count();

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Manual Inventory Sync', WC_TradeGecko_Init::$text_domain );
	if ( 0 < $count ) {
		$html .= ' - ' . $count . ' ' . _n( 'Product', 'Products', intval( $count ), WC_TradeGecko_Init::$text_domain );
	}

	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_clear_sync', 'wc_tradegecko_clear_sync' );
/**
 * Clear Sync Logs
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_clear_sync( $args ) {

	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_clear_sync_logs', 'wc_tradegecko_clear_sync_logs' );

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Clear Sync Logs', WC_TradeGecko_Init::$text_domain ) . '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_sync_log_table', 'wc_tradegecko_sync_log_table' );
/**
 * Sync Logs Table
 *
 * @since 1.0
 * @return void
 */
function wc_tradegecko_sync_log_table( $args ) {

	$html = do_action( 'wc_tradegecko_get_sync_log_table' );

	echo $html;
}

add_action( 'wc_tradegecko_get_sync_log_table', 'wc_tradegecko_get_sync_log_table' );
/**
 * Output the Sync Log Table
 *
 * @since 1.0
 */
function wc_tradegecko_get_sync_log_table() {
	// Get the log
	$log      = get_option( WC_TradeGecko_Init::$prefix . 'sync_log', array() );
	$log_data = array();

	$i = 1;
	foreach ( array_reverse( $log ) as $log_key => $log_value ) {

		$log_data[] = array(
			'ID'        => $log_key,
			'order_num' => $i,
			'datetime'  => WC_TradeGecko_Init::get_formatted_datetime( $log_value['timestamp'] ),
			'log_type'  => sprintf( '<mark class="%s">%s</mark>', strtolower( $log_value['log_type'] ), $log_value['log_type'] ),
			'action'    => $log_value['action']
		);

		$i ++;
	}

	$log_table = new WC_TradeGecko_List_Table( $log_data );

	$log_table->prepare_items();
	$log_table->display();

}

add_action( 'callback_hook_running_orders_update', 'wc_tradegecko_running_orders_update' );
/**
 * Order Update process visual
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_running_orders_update( $args ) {
	global $wc_tg_sync;

	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_allow_running_process_again', null, array( 'process' => 'order_update' ) );

	$is_running = $wc_tg_sync->check_is_order_update_sync_running();

	$desc = sprintf( __( 'Order Update process %s running at the moment.' ), $is_running ? 'is' : 'is not' );
	if ( $is_running ) {
		$desc .= '<br/>' . __( 'Click on the button below, if you want to allow another process to run before the first one has ended.' );
	}

	$html = '<fieldset>';
	$html .= '<p>' . $desc . '</p>';
	if ( $is_running ) {
		$html .= '<a href="' . $url . '" class="button">' . __( 'Force Allow Orders Update Process', WC_TradeGecko_Init::$text_domain );
	}
	$html .= '</a>';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_running_orders_export', 'wc_tradegecko_running_orders_export' );
/**
 * Order Export process visual
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_running_orders_export( $args ) {
	global $wc_tg_sync;

	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_allow_running_process_again', null, array( 'process' => 'order_export' ) );

	$is_running = $wc_tg_sync->check_is_order_sync_running();

	$desc = sprintf( __( 'Order Export process %s running at the moment.' ), $is_running ? 'is' : 'is not' );
	if ( $is_running ) {
		$desc .= '<br/>' . __( 'Click on the button below, if you want to allow another process to run before the first one has ended.' );
	}

	$html = '<fieldset>';
	$html .= '<p>' . $desc . '</p>';
	if ( $is_running ) {
		$html .= '<a href="' . $url . '" class="button">' . __( 'Force Allow Orders Export Process', WC_TradeGecko_Init::$text_domain );
	}
	$html .= '</a>';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_running_inventory', 'wc_tradegecko_running_inventory_sync' );
/**
 * Inventory sync process visual
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_running_inventory_sync( $args ) {
	global $wc_tg_sync;

	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_allow_running_process_again', null, array( 'process' => 'inventory_sync' ) );

	$is_running = $wc_tg_sync->check_is_inventory_sync_running();

	$desc = sprintf( __( 'Inventory Sync process %s running at the moment.' ), $is_running ? 'is' : 'is not' );
	if ( $is_running ) {
		$desc .= '<br/>' . __( 'Click on the button below, if you want to allow another process to run before the first one has ended.' );
	}

	$html = '<fieldset>';
	$html .= '<p>' . $desc . '</p>';
	if ( $is_running ) {
		$html .= '<a href="' . $url . '" class="button">' . __( 'Force Allow Inventory Sync Process', WC_TradeGecko_Init::$text_domain );
	}
	$html .= '</a>';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_export_products', 'wc_tradegecko_export_products_csv' );
/**
 * Export Products to CSV
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_export_products_csv( $args ) {
	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_export_products_csv' );

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Export Products to CSV', WC_TradeGecko_Init::$text_domain );
	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_clear_synced_products', 'wc_tradegecko_clear_synced_products' );
/**
 * Clear synced products mapping
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_clear_synced_products( $args ) {
	$url = wc_tragedego_get_ajax_url( 'wc_tradegecko_clear_synced_products' );

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Clear Synced Products Mapping', WC_TradeGecko_Init::$text_domain );
	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

add_action( 'callback_hook_import_products', 'wc_tradegecko_import_products_csv' );
/**
 * Link to import products page
 *
 * @since 1.6
 * @return void
 */
function wc_tradegecko_import_products_csv( $args ) {
	$url = admin_url( 'admin.php?import=wc_tradegecko_product_importer' );

	$html = '<fieldset>';
	$html .= '<a href="' . $url . '" class="button">' . __( 'Import Products from CSV', WC_TradeGecko_Init::$text_domain );
	$html .= '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

/**
 * Generate an ajax url to use for the force allow the running sync processes.
 *
 * Possible values for $process: inventory_sync, orders_export, orders_update
 *
 * @since 1.6
 *
 * @param type $process
 *
 * @return string
 */
function wc_tragedego_get_ajax_url( $action, $nonce_name = null, array $query_args = array() ) {
	$nonce = wp_create_nonce( $nonce_name ? $nonce_name : $action );

	$query = array(
		'action'   => $action,
		'_wpnonce' => $nonce,
	);

	if ( ! empty( $query_args ) ) {
		$query = array_merge( $query, $query_args );
	}

	$url_query = http_build_query(
		$query, 'arg-'
	);

	$url = admin_url( 'admin-ajax.php?' ) . $url_query;

	return $url;
}

/**
 * Return the time interval array, used for the sync schedule
 * Intervals are from 1 to 120
 *
 * @return int
 */
function wc_tradegecko_get_intervals() {
	$intervals = array();

	for ( $i = 1; $i <= 120; $i ++ ) {
		$intervals[$i] = $i;
	}

	return $intervals;
}

add_action( 'callback_hook_get_api_credentials', 'wc_tradegecko_get_api_credentials' );
/**
 * Get API Credentials Button
 *
 * @since 1.3.2
 * @return void
 */
function wc_tradegecko_get_api_credentials( $args ) {

	$html = '<fieldset>';
	$html .= '<a href="' . wc_tradegecko_manage_api_credentials_url() . '" target="_blank" class="button">' . __( 'Retrieve TradeGecko API credentials', WC_TradeGecko_Init::$text_domain ) . '</a>';
	$html .= wc_tradegecko_get_setting_description( $args ) . '<br />';
	$html .= '</fieldset>';

	echo $html;
}

function wc_tradegecko_manage_api_credentials_url( $query_args = array() ) {
	$query = array(
		'redirect_uri' => admin_url( '/admin.php' ),
	);

	if ( ! empty( $query_args ) ) {
		$query = array_merge( $query, $query_args );
	}

	$url_query = http_build_query(
		$query, 'arg-'
	);

	// privileged
	$url = 'https://go.tradegecko.com/oauth/applications/woocommerce?' . $url_query;

	return $url;
}