<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

return array (
		'custom_form_design' => array (
				'type' => 'custom',
				'function' => 'WC_Braintree_Admin::output_custom_forms',
				'title' => __ ( 'Custom Form', 'braintree' ),
				'default' => 'bootstrap_form',
				'value'=>'',
				'class'=>array(),
				'tool_tip' => true,
				'description' => __ ( 'This option allows you to select the payment form which will be displayed on the checkout page.', 'braintree' ) 
		),
		'custom_form_styles' => array (
				'type' => 'textarea',
				'title' => __ ( 'Styles', 'braintree' ),
				'default' => '',
				'placeholder'=>'{"input":{  "font-size":"14px","font-family":"helvetica, tahoma, calibri, sans-serif","color":"#3a3a3a"}}',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('You can customize the css of the hosted payment fields using this setting. All css must be in json format or it will cause errors. If the styles are left blank, the default styles will be applied automatically. Please reference
						<a target="_blank" href="https://developers.braintreepayments.com/guides/hosted-fields/styling/javascript/v3">Braintree Examples</a> if you have questions on how to format the custom css.', 'braintree')
		),
		'postal_field_enabled'=>array(
				'type'=>'custom',
				'title'=>__('Display Postal Field', 'braintree'),
				'function'=>'WC_Braintree_Admin::display_postal_field_option',
				'default'=>'yes',
				'value'=>'yes',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('If enabled, the custom form that you select will display a field for the customer to enter the postal code associated with their payment method.', 'braintree')
		),
		'cvv_field_enabled'=>array(
				'type'=>'custom',
				'title'=>__('Display CVV Field', 'braintree'),
				'function'=>'WC_Braintree_Admin::display_cvv_field_option',
				'default'=>'yes',
				'value'=>'yes',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('If enabled, the custom form that you select will display a field for the customer to enter their card security code (CVV).', 'braintree')
		
		),
		'card_number_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'Number Placeholder', 'braintree' ),
				'default' => __ ( 'Card Number', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value entered here will display as a placeholder on the credit card number field.', 'braintree' ) 
		),
		'card_cvv_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'CVV Placeholder', 'braintree' ),
				'default' => __ ( 'CVV', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value entered here will display as a placeholder on the credit card security code field.', 'braintree' ) 
		),
		'card_expiration_date_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'Expiration Date Placeholder', 'braintree' ),
				'default' => __ ( 'MM/YY', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'For forms that contain a combined expiration date and year, this will be the placeholder.', 'braintree' ) 
		),
		'card_expiration_month_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'Expiration Month Placeholder', 'braintree' ),
				'default' => __ ( 'MM', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value entered here will display as a placeholder on the expiration month.', 'braintree' )
		),
		'card_expiration_year_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'Expiration Year Placeholder', 'braintree' ),
				'default' => __ ( 'YY', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value entered here will display as a placeholder on the expiration year.', 'braintree' )
		),
		'card_postal_placeholder' => array (
				'type' => 'text',
				'title' => __ ( 'Card Postal Placeholder', 'braintree' ),
				'default' => __ ( 'Postal Code', 'braintree' ),
				'value' => '',
				'class' => array (),
				'tool_tip' => true,
				'description' => __ ( 'The value entered here will display as a placeholder on the credit card postal code field.', 'braintree' ) 
		),
		'card_number_label'=>array(
				'type'=>'text',
				'title'=>__('Card Number Label', 'braintree'),
				'default'=>__('Card Number', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card number input field for forms that contain a label.', 'braintree')
		),
		'card_cvv_label'=>array(
				'type'=>'text',
				'title'=>__('CVV Label', 'braintree'),
				'default'=>__('CVV', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card cvv input field for forms that contain a label.', 'braintree')
		),
		'card_postal_label'=>array(
				'type'=>'text',
				'title'=>__('Postal Label', 'braintree'),
				'default'=>__('Postal Code', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card postal input field for forms that contain a label.', 'braintree')
		),
		'card_expiration_date_label'=>array(
				'type'=>'text',
				'title'=>__('Exp Date Label', 'braintree'),
				'default'=>__('Exp Date', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card expiration date input field for forms that contain a label.', 'braintree')
		),
		'card_expiration_month_label'=>array(
				'type'=>'text',
				'title'=>__('Exp Month Label', 'braintree'),
				'default'=>__('Month', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card expiration month input field for forms that contain a label.', 'braintree')
		),
		'card_expiration_year_label'=>array(
				'type'=>'text',
				'title'=>__('Exp Year Label', 'braintree'),
				'default'=>__('Year', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card expiration year input field for forms that contain a label.', 'braintree')
		),
		'card_save_label'=>array(
				'type'=>'text',
				'title'=>__('Save Card Label', 'braintree'),
				'default'=>__('Save', 'braintree'),
				'value'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This text will display above or next to the card save field for forms that contain a label.', 'braintree')
		),
		'enable_loader'=>array(
				'type'=>'checkbox',
				'title'=>__('Checkout Loader', 'braintree'),
				'default'=>'',
				'value'=>'yes',
				'tool_tip'=>true,
				'class'=>array(),
				'description'=>__('If enabled, a loader will appear around the payment form to let the customer know that the payment is processing.', 'braintree')
		),
		'custom_form_loader_message'=>array(
				'type'=>'textarea',
				'title'=>__('Loader HTML', 'braintree'),
				'default'=>__('<div class="custom-form-checkout-loader"><h1>Processing...</h1></div>', 'braintree'),
				'value'=>'',
				'tool_tip'=>true,
				'description'=>__('This is the html that will appear inside the loader overaly. You can add your own animation here or custom html. The customizations are endless.', 'braintree')
		),
		'custom_form_loader_css'=>array(
				'type' => 'textarea',
				'title' => __ ( 'Loader CSS', 'braintree' ),
				'default' => '{"border":"none","width":"80%","max-width":"300px"}',
				'placeholder'=>'',
				'class'=>array(),
				'tool_tip'=>true,
				'description'=>__('This option allows you to enter custom css for the loader. The css must be entered in json format.')
		)
);