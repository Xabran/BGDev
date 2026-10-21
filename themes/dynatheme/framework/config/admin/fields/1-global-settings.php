<?php
$sections = array(
	'title'  => __( 'Global Settings', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'el el-cogs',
	// 'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
	'fields' => array(
	
		array(
			'id'=>'cmk-favico',
			'type' => 'media', 
			'title' => __('Favicon', 'cmk'),
			'mode' => false, // Can be set to false to allow any media type, or can also be set to any mime type.
			'default'=>array('url' => Dynatheme::$cmk_url .'/images/favicon.png'),
			'desc'=> __(''),
			#'subtitle' => __('Add/Upload Your Website Favicon here'),
		),
		array(
			'id'       => 'cmk-site-build-mode',
			'type'     => 'switch',
			'title'    => __( 'Site Environment', 'cmk' ),
			'subtitle' => __( 'This option is for debugging purposes.', 'cmk' ),
			'desc'     => __( '', 'cmk' ),
			'on' => 'Live',
			'off' => 'Development',
			'default'  => '0',
			'compiler' => true,
		),
		array(
			'id'       => 'cmk-active-css',
			'type'     => 'select',
			'title'    => __( 'Load Style', 'cmk' ),
			'subtitle' => __( 'Select Dynatheme Style', 'cmk' ),
			'desc'     => __( 'Please select a style you wanted to use, You can check the demo for reference.', 'cmk' ),
			'options'  => array(
				Dynatheme::$cmk_url.'/css/animate.min.css' => 'DTDS S-1',
				#Dynatheme::$cmk_url.'/css/custom.css' => 'Style 2',
				#Dynatheme::$cmk_url.'/css/main.css' => 'Style 3',
			),
			'default'  => Dynatheme::$cmk_url.'/css/main.css'
		),
		array(
			'id'       => 'cmk-site-layout',
			'type'     => 'switch',
			'title'    => __( 'Site Layout', 'cmk' ),
			'subtitle' => __( 'Select the layout of want to use.', 'cmk' ),
			'desc'     => __( 'If you are not sure about this option,Please check the demo to understand the difference between Box & Wide Layout', 'cmk' ),
			'on' => 'Wide',
			'off' => 'Box',
			'default'  => '1'
		),
		array(
			'id'=>'cmk-box-max-width',
			'type' => 'spinner', 
			'required' => array('cmk-site-layout','=','0'),
			'title' => __('Maximum Width', 'cmk'),
			'desc'=> __('Set maximum width for the box layout. Default is 1366px ', 'cmk'),
			'default' 	=> '1366',
			'min' 		=> '0',
			'step'		=> '1',
			'max'		=> '3000',
		),
		array(
			'id'=>'cmk-mobile-responsive',
			'type' => 'switch', 
			'title' => __('Mobile Responsive', 'cmk'),
			'subtitle' => __('Enable Mobile Responsive', 'cmk'),
			'on' => 'Enable',
			'off' => 'Disable',
			'default'  => '1'
		),
		array(
			'id'=>'cmk-force-page-fullwidth',
			'type' => 'switch', 
			'title' => __('Full Width Page', 'cmk'),
			'subtitle' => __('Force Page to be full width (No Sidebar)', 'cmk'),
			'on' => 'Enable',
			'off' => 'Disable',
			'default'  => '1'
		),
		/*array(
			'id'       => 'cmk-font-awesome',
			'type'     => 'select',
			'title'    => __( 'Select Option', 'cmk' ),
			'subtitle' => __( 'Default Dynatheme Style', 'cmk' ),
			'desc'     => __( 'Please select a style you wanted to use, You can check the demo for reference.', 'cmk' ),
			'data' => 'elusive-icons',
			'options'  => array(
				'fa fa-facebook' => '&nbsp;&nbsp;Facebook',
				'fa fa-facebook-square' => '&nbsp;&nbsp;Facebook Square',
				'fa fa-twitter' => '&nbsp;&nbsp;Twitter',
				'fa fa-twitter-square' => '&nbsp;&nbsp;Twitter Square',
				'fa fa-google-plus' => '&nbsp;&nbsp;Google+',
				'fa fa-google-plus-square' => '&nbsp;&nbsp;Google+ Square',
			),
			'default'  => ''
		),*/
	)
);
Redux::setSection( $opt_name, $sections);