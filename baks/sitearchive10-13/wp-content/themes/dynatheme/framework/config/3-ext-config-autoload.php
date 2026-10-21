<?php
if ( !defined('INCLUDE_EXT') ) {

	$ext_uri = Dynatheme::$cmk_dir . '/framework/ext/';

	// List PHP file Extension to be loaded
	$ext_file = array(
		'htmlgen/htmlgen.php',
		'meta/meta-functions.php',
		'layout-shortcode/layout-shortcode.php',
	);
	
	// Load Defined Extension
	foreach ( $ext_file as $ext ) {
		$file = $ext_uri.$ext;
		if ( file_exists ( $file ) ) {
			include ( $file );
		}
	}

	define('INCLUDE_EXT', 132);
} 