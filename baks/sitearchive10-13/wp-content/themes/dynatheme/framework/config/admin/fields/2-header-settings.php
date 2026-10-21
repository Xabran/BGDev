<?php 

$sections = array(
	'title'  => __( 'Header Settings', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-th-large',
	'class'  => '', 
	'fields' => array(
		array(
			'id'=>'cmk-header-logo',
			'type' => 'media', 
			'title' => __('Website Logo', 'cmk'),
			'mode' => false, // Can be set to false to allow any media type, or can also be set to any mime type.
			//'default'=>array('url' => CMK_IMG_URL .'/twitter-nav.png'),
			'desc'=> __(''),
			'subtitle' => __('Add your website logo here'),
		),
		array(
			'id'       => 'cmk-header-layout',
			'type'     => 'image_select',
			'title'    => __( 'Header Layout', 'cmk' ),
			'subtitle' => __( 'Select the layout of header you want.', 'cmk' ),
			'options'  => array(
				'1' => array(
					'alt' => '1 Column',
					'img' => ReduxFramework::$_url . 'assets/img/1col.png'
				),
				'2' => array(
					'alt' => '2 Column Left',
					'img' => ReduxFramework::$_url . 'assets/img/2cl.png'
				),
				'3' => array(
					'alt' => '2 Column Right',
					'img' => ReduxFramework::$_url . 'assets/img/2cr.png'
				),
				'4' => array(
					'alt' => '3 Column Middle',
					'img' => ReduxFramework::$_url . 'assets/img/3cm.png'
				),
				'5' => array(
					'alt' => '3 Column Left',
					'img' => ReduxFramework::$_url . 'assets/img/3cl.png'
				),
				'6' => array(
					'alt' => '3 Column Right',
					'img' => ReduxFramework::$_url . 'assets/img/3cr.png'
				)
			),
			'default'  => '2',
			'customizer' => true			
		),
		array(
			'id'       => 'cmk-nav-position',
			'type'     => 'select',
			'title'    => __( 'Navigation Position', 'cmk' ),
			'subtitle' => __( '', 'cmk' ),
			'desc'     => __( '', 'cmk' ),
			//'data' => 'elusive-icons',
			'options'  => array(
				'a' => 'Before Header',
				'b' => 'Inside Header',
				'c' => 'After Header',
				),
			'default'  => 'c'
		),
		array(
			'id'       => 'cmk-assined_nav',
			'type'     => 'select',
			'data'     => 'menus',
			'title'    => __( 'Select Navigation', 'cmk' ),
			'subtitle' => __( 'Select the Menu you want to display.', 'cmk' ),
			'desc'     => __( '', 'cmk' ),
		),
	)
);
Redux::setSection( $opt_name, $sections);