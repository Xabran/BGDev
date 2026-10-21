<?php
return apply_filters ( 'braintree_custom_forms', array (
		'bootstrap_form' => array (
				'description'=>'Bootstrap Form',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/bootstrap-form.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/bootstrap-form.css',
				'external_css' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/bootstrap-form.js',
				'default_styles'=>'{"input":{"font-size":"16px", "font-family":"helvetica, tahoma, calibri, sans-serif"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		),
		'google_material_design' => array (
				'description'=>'Google Material Design',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/google-material-design.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/google-material-design.css',
				'external_css' => '',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/google-material-design.js',
				'default_styles'=>'{"input":{"font-size":"16px", "font-family":"roboto, verdana, sans-serif"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		),
		'dynamic_card_form' => array (
				'description'=>'Dynamic Card Form',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/dynamic-card-form.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/dynamic-card-form.css',
				'external_css' => '',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/dynamic-card-form.js',
				'default_styles'=>'{"input":{"font-size":"16px"}, "input.invalid":{"color":"#E53A40"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		),
		'3d_card_form' => array (
				'description'=>'3D Card Form',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/3d-card-form.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/3d-card-form.css',
				'external_css' => '',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/3d-card-form.js',
				'default_styles'=>'{"input":{"font-size":"16px"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		),
		'classic_form' => array (
				'description'=>'Classic Form',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/classic-form.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/classic-form.css',
				'external_css' => '',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/classic-form.js',
				'default_styles'=>'{"input":{"font-size":"16px"}, ".invalid":{"color":"#D0021B"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		),
		'simple_form'=>array(
				'description'=>'Simple Form',
				'html' => WC_BRAINTREE_PLUGIN . 'payments/forms/custom/simple-form.php',
				'css' => WC_BRAINTREE_ASSETS . 'css/forms/simple-form.css',
				'external_css' => '',
				'javascript' => WC_BRAINTREE_ASSETS . 'js/forms/simple-form.js',
				'default_styles'=>'{"input":{"font-size":"16px", "font-family":"courier, monospace"}, ".valid":{"color":"rgb(94, 189, 128)"}, "@media screen and (max-width: 375px)":{"input":{"font-size":"14px"}}}'
		)
) );