<?php
if (! defined ( 'ABSPATH' )) {
	exit ();
}

/**
 * Installation class used for processing updates.
 *
 * @author Clayton Rogers
 * @since 3/20/2016
 */
class Braintree_Install {
	private static function getUpdates() {
		return array (
				'2.3.0' => WC_BRAINTREE_PLUGIN . 'admin/updates/braintree-update-2.3.0.php',
				'2.3.3' => WC_BRAINTREE_PLUGIN . 'admin/updates/braintree-update-2.3.3.php',
				'2.3.5' => WC_BRAINTREE_PLUGIN . 'admin/updates/braintree-update-2.3.5.php',
				'2.3.8' => WC_BRAINTREE_PLUGIN . 'admin/updates/braintree-update-2.3.8.php' 
		);
	}
	public static function init() {
		
		/* Run updates when admin_init action is called. */
		add_action ( 'admin_init', __CLASS__ . '::checkVersion' );
		
		add_action ( 'in_plugin_update_message-woo-payment-gateway/braintree-payments.php', __CLASS__ . '::getAdminUpdateNotice' );
	}
	
	/**
	 * Check the version of the current installation.
	 */
	public static function checkVersion() {
		$version = get_option ( 'braintree_for_woocommerce_version' );
		if (! $version || ($version ['currentVersion'] < BT_Manager ()->version)) {
			self::update ();
			add_action ( 'admin_notices', __CLASS__ . '::updateNotice' );
		}
	}
	public static function update() {
		if (! get_option ( 'braintree_for_woocommerce_version' )) {
			$previousVersions = array ();
			foreach ( self::getUpdates () as $version => $update ) {
				include_once ($update);
				$previousVersions [$version] = $version;
			}
		} else {
			$versions = get_option ( 'braintree_for_woocommerce_version' );
			if (! is_array ( $versions )) {
				delete_option ( 'braintree_for_woocommerce_version' );
				$versions = array (
						'previousVersions' => array () 
				);
			}
			$previousVersions = $versions ['previousVersions'];
			foreach ( self::getUpdates () as $version => $update ) {
				if (! array_key_exists ( $version, $previousVersions )) {
					include_once $update;
					$previousVersions [$version] = $version;
				}
			}
		}
		update_option ( 'braintree_for_woocommerce_version', array (
				'currentVersion' => BT_Manager ()->version,
				'previousVersions' => $previousVersions 
		) );
	}
	public static function updateNotice() {
		BT_Manager ()->addAdminNotice ( array (
				'type' => 'success',
				'text' => sprintf ( __ ( 'Thank you for updating Braintree For WooCommerce to version %s.', 'braintree' ), BT_Manager ()->version ) 
		) );
	}
	public static function getAdminUpdateNotice($args) {
		$response = wp_safe_remote_get ( 'https://plugins.svn.wordpress.org/woo-payment-gateway/trunk/readme.txt' );
		if ($response instanceof WP_Error) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'There was an error retrieving the update notices. %s', print_r ( $response, true ) ) );
		} else {
			$content = ! empty ( $response ['body'] ) ? $response ['body'] : '';
			self::parseUpdateNoticeContent ( $content );
		}
	}
	
	/**
	 * Parse the content for the update notice.
	 *
	 * @param string $content
	 *        	The content retrieved from the readme.txt file.
	 */
	public static function parseUpdateNoticeContent($content) {
		$pattern = '/==\s*Upgrade Notice\s*==\s*=\s*([0-9.]*)\s*=\s*(.*)/';
		if (preg_match ( $pattern, $content, $matches )) {
			$version = $matches [1];
			$notice = $matches [2];
			if (version_compare ( $version, BT_Manager ()->version, '>' )) {
				echo '<div class="wc_plugin_upgrade_notice">' . $notice . '</div>';
			}
		}
	}
}
Braintree_Install::init ();