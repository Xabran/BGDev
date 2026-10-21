<?php
$sections = array(
	'title'  => __( 'Custom PHP', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-user-secret',
	'class'  => 'cmk_custom_code php', 
	'fields' => array(
		array(
			'id'       => 'custom_php_field_options',
			'type'     => 'button_set',
			'title'    => __( '', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'desc'     => __( '', 'cmk' ),
			'options'  => array(
				'1' => 'Front End PHP Code',
				'2' => 'Back End/Admin PHP Code',
			),
			'default'  => '1',
			'customizer' => false
		),
		array(
			'id'       => 'cmk-custom-php',
			'required' => array('custom_php_field_options','=','1'),
			'type'     => 'ace_editor',
			'title'    => __( '', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'mode'     => 'php',
			'theme'    => 'monokai',
			'desc'     => 'Use this editor for adding custom functionality on the front-end.',
			'default'  => "<?php\n##Start your code below this line\nadd_action('my_action','my_function');\nfunction my_function() {\n\techo 'Hello';\n}",
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
		array(
			'id'       => 'admin-cmk-custom-php',
			'required' => array('custom_php_field_options','=','2'),
			'type'     => 'ace_editor',
			'title'    => __( '', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'mode'     => 'php',
			'theme'    => 'monokai',
			'desc'     => 'Please be careful when using this editor. If you make a single php error, your site will be down and you have to delete or edit this file from wp-uploads folder.',
			'default'  => "<?php\n##Use this editor only if you need PHP admin functionality.",
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