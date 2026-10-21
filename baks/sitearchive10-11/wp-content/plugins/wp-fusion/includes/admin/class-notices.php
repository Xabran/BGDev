<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_Admin_Notices {

	public function __construct() {

		add_action( 'admin_notices', array( $this, 'plugin_activation' ));

	}

	/**
	 * Shows a notice on first activation
	 *
	 * @access public
	 * @return mixed
	 */

	public function plugin_activation() {

		if(wp_fusion()->settings->get('connection_configured') == false) {

			$out = '<div class="updated">';
			$out .= '<p>';
			$out .= 'To finish setting up WP Fusion, please go to the <a href="' . get_admin_url() . '/options-general.php?page=wpf-settings#setup">WP Fusion settings page</a>.';
			$out .= '</p>';
			$out .= '</div>';

			echo $out;

		}

	}

}

new WPF_Admin_Notices;