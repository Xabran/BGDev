<?php 
$sections = array(
	'title'  => __( 'Custom CSS', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-css3',
	//'class'  => 'cmk_custom_code css', 
	'fields' => array(
		array(
			'id'       => 'cmk-custom-css',
			'type'     => 'ace_editor',
			'title'    => __( '', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'mode'     => 'css',
			'theme'    => 'monokai',
			'desc'     => '',
			'default'  => ".sample-class{\n\tmargin: 0 auto;\n}",
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