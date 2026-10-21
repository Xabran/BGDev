<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action("init","ia_ls_save", 10);

function ia_ls_save() {
	global $iwpro;
	$siteurl = $_SERVER['HTTP_HOST'];
	$siteurl = str_replace("http://","",$siteurl);
	$siteurl = str_replace("https://","",$siteurl);
	$siteurl = str_replace("www.","",$siteurl);

	if(!empty($_GET['leadsource'])) {
		setcookie("ia_leadsource", $_GET['leadsource'], (time()+31*24*3600), "/", $siteurl, 0); 
		$_SESSION['leadsource'] = $_GET['leadsource'];
	} else if(!empty($_GET['utm_source'])) {
		setcookie("ia_leadsource", $_GET['utm_source'], (time()+31*24*3600), "/", $siteurl, 0); 
		$_SESSION['leadsource'] = $_COOKIE['utm_source'];				
	} else if(!empty($_GET['utm_campaign'])) {
		setcookie("ia_leadsource", $_GET['utm_campaign'], (time()+31*24*3600), "/", $siteurl, 0); 
		$_SESSION['leadsource'] = $_COOKIE['utm_campaign'];				
	} else if(!empty($_COOKIE['ia_leadsource'])) {
		$_SESSION['leadsource'] = $_COOKIE['ia_leadsource'];				
	}
	
	if(!empty($_GET['affiliate'])) {
		setcookie("is_aff", $_GET['affiliate'], (time()+365*24*3600), "/", $siteurl, 0); 
	}

	if(!empty($_GET['aff'])) {
		setcookie("is_affcode", $_GET['aff'], (time()+365*24*3600), "/", $siteurl, 0); 
	}
}

?>