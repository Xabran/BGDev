<?php 
$sections = array(
	'title'  => __( 'Footer Settings', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-tasks',
	'class'  => '', 
	'fields' => array(
		array(
			'id'      => 'cmk-footerwidgets-count',
			'type'    => 'select',
			'title'   => __( 'Number of Footer Widgets', 'cmk' ),
			'desc'    => __( 'You can assign as many footer widgets as you want, default is 3', 'cmk' ),
			'default' => '3',
			'options' => array(
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6',
			),
		),
		array(
			'id'      => 'cmk-footerwidgets-position',
			'type'    => 'select',
			'title'   => __( 'Footer Widgets Position', 'cmk' ),
			'subtitle'    => __( 'Display Footer Widgets before or after footer.', 'cmk' ),
			'options'  => array(
				'before-foot' => 'Before Footer',
				'after-foot' => 'After Footer',
				),
			'default'  => 'before-foot'
		),
		array(
			'id'       => 'cmk-footer-content',
			'type'     => 'ace_editor',
			'title'    => __( 'Footer Content', 'cmk' ),
			'subtitle' => __( 'You can place HTML in here. It also supports shorcode.', 'cmk' ),
			'mode'     => 'html',
			'theme'    => 'monokai',
			'desc'     => '',
			'default'  => "<p class='copyright leftalign'>Copyright &copy; | <a href='[site-url]'>[site-name]</a> | <span class='fright'>Wordpress Theme by: <a href='http://dynamized.dev/'>DynaTheme</a>.</span><p>",
			'options'  => array(
			'wrap' => true,
				'minLines'=> 10, 
				'maxLines' => 45,
				'fontFamily' =>  'Consolas',
  				'fontSize' => '13pt',
			),
			'full_width' => true, 
		),
		/*array(
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
			'title'    => __( 'Menus Select Option', 'cmk' ),
			'subtitle' => __( 'No validation can be done on this field type', 'cmk' ),
			'desc'     => __( 'This is the description field, again good for additional info.', 'cmk' ),
		),*/
	)
);
Redux::setSection( $opt_name, $sections);