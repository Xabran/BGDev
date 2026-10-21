<?php
/*
Plugin Name: zBoardGames responsive improvement plugin
Description: 
Version: 1.0.0
Author: Denis Bakaev

*/


if (!class_exists('zBg'))
{
	class zBg
	{
		
		function __construct()
		{
			add_action('wp_head', array($this,'add_scripts_and_styles'));
		}
		

	
		function add_scripts_and_styles()
		{
			//$siteurl = get_option('siteurl');
			$siteurl = get_site_url();
			$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/denis.css';
			echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
			
			$url2 = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/denis.js';
			echo "<script src='$url2'></script>\n";
			
		}

	
	}
}

$sm=new zBg();

//add_action('wp_head', array('zBg','add_scripts_and_styles'));


