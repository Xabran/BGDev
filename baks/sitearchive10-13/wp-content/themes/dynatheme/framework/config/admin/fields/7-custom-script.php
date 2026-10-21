<?php 
$sections = array(
	'title'  => __( 'Custom Script', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-code',
	'class'  => 'cmk_custom_script',
	'fields' => array(
		array(
			'id'       => 'cmk-minify-script',
			'type'     => 'checkbox',
			'title'    => __( 'Minify Script', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'desc'     => '',
			'compiler' => true,
			'default'  => '0',

		),
		array(
			'id'       => 'cmk-custom-script',
			'type'     => 'ace_editor',
			'title'    => __( '', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'mode'     => 'javascript',
			'theme'    => 'monokai',
			'desc'     => '',
			'default'  => "\n( function($) {\n\t$('.genesis-nav-menu').cmknav({\n\t\tlabel: '<i class=\"fa fa-navicon\"></i>',\n\t});//This code is required for Mobile Menu\n\t//Start your code below this line\n\t\n})(jQuery);",
			'options'  => array(
			'wrap' => true,
				'minLines'=> 20, 
				'maxLines' => 45,
				'fontFamily' =>  'Consolas',
  				'fontSize' => '11pt',
			),
			'compiler' => true,
			'full_width' => true, 
		),
	)
);
Redux::setSection( $opt_name, $sections);
