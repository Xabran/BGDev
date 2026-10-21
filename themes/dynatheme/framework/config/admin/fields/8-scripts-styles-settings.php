<?php 
$sections = array(
	'title'  => __( 'Scripts and Styles', 'cmk' ),
	'desc'   => __( '', 'cmk' ),
	'icon'   => 'fa fa-connectdevelop',
	'class'  => '', 
	'fields' => array(
		array(
			'id'=>'cmk-activate-font-awesome',
			'type' => 'switch', 
			'title' => __('Font Awesome', 'cmk'),
			'desc'=> __(''),
			'subtitle' => __('Enable Font Awesome'),
			'on' => 'ON',
			'off' => 'OFF',
			'default'  => '1',
		),
		array(
			'id'=>'cmk-font-awesome-source',
			'type' => 'switch', 
			'required' => array('cmk-activate-font-awesome','=','1'),
			'title' => __('Font Awesome Source', 'cmk'),
			'subtitle'=> __('Use CDN or Local File (bundled with theme).', 'cmk'),
			'on' => 'CDN',
			'off' => 'Local',
			'default'  => '1',
		),
		array(
			'id'=>'cmk-activate-elusive-icon',
			'type' => 'switch', 
			'title' => __('Elusive Icon', 'cmk'),
			'desc'=> __(''),
			'subtitle' => __('Enable Elusive Icon'),
			'on' => 'ON',
			'off' => 'OFF',
			'default'  => '0',
		),
		array(
			'id'=>'cmk-elusive-icon-source',
			'type' => 'switch', 
			'required' => array('cmk-activate-elusive-icon','=','1'),
			'title' => __('Elusive Icon Source', 'cmk'),
			'subtitle'=> __('Use CDN or Local File (bundled with theme).', 'cmk'),
			'on' => 'CDN',
			'off' => 'Local',
			'default'  => '1',
		),
		array(
			'id'		=> 'cmk-enque-scripts',
			'type'		=> 'select',
			'multi'		=> true, 
			'sortable'	=> true,
			'title'		=> __( 'Load Javascript', 'cmk' ),
			'subtitle'	=> __( 'Only load javascript that you need', 'cmk' ),
			'options'	=> DynaTheme::get_cmk_unenqueue_lists(),
			//'desc'     => __( 'Do not load all javascript if you don\'t need them.', 'cmk' ),
			'default'  => 'cmk-dynatheme-js',
		),
	)
);
Redux::setSection( $opt_name, $sections);

