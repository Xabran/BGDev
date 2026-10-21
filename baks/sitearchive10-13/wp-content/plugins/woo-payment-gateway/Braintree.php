<?php
/**
 * Braintree PHP Library
 * Creates class_aliases for old class names replaced by PSR-4 Namespaces
 *
 * @copyright  2015 Braintree, a division of PayPal, Inc.
 */

require_once( WC_BRAINTREE_PLUGIN . 'autoload.php');

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Braintree_Exception('PHP version >= 5.4.0 required');
}

if(! function_exists('requireDependencies')){
	function requireDependencies() {
	    $requiredExtensions = array( 'xmlwriter', 'openssl', 'dom', 'hash', 'curl' );
	    foreach ($requiredExtensions AS $ext) {
	        if (!extension_loaded($ext)) {
	            throw new Braintree_Exception('The Braintree library requires the ' . $ext . ' extension.');
	        }
	    }
	}
}

requireDependencies();
