<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'wc_tradegecko_add_admin_settings_page' );
/**
 * Add admin setting page
 *
 * @since 1.0
 */
function wc_tradegecko_add_admin_settings_page() {
	add_submenu_page('woocommerce', __('TradeGecko', WC_TradeGecko_Init::$text_domain ), __('TradeGecko', WC_TradeGecko_Init::$text_domain ), 'manage_woocommerce', WC_TradeGecko_Init::$settings_page, 'wc_tradegecko_options_page');
}

/**
 * Output the settings page content
 *
 * @since 1.0
 */
function  wc_tradegecko_options_page() {

	$active_tab = WC_TradeGecko_Init::get_get( 'tab' ) ? WC_TradeGecko_Init::get_get( 'tab' ) : 'general';

	// Try to re-authenticate when the API tab is saved
	wc_tradegecko_authentication_recheck( $active_tab );

	$remove_args = array( 'settings-updated', 'new-auth-code-obtained' );

	$error_count = get_option( WC_TradeGecko_Init::$prefix . 'error_count', 0 );

	ob_start(); ?>

	<div class="wrap">

                <div id="tradegecko-header">
                        <img class="icon-tradegecko" width="32" height="32" src="<?php echo WC_TradeGecko_Init::$plugin_url .  "/assets/images/woo-tg-32x32.png" ?>" ></img>
                        <h3 class="tradegecko-header"><?php echo sprintf( __( 'TradeGecko - Woocommerce add-on. %sGo To Tradegecko%s', WC_TradeGecko_Init::$text_domain ), '<a href="http://go.tradegecko.com" target="_blank">', '</a>' );  ?></h3>
                </div>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg('tab', 'general', remove_query_arg( $remove_args ) ) ); ?>"
			   class="nav-tab tab_general <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
				<?php _e('General', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo esc_url( add_query_arg('tab', 'api', remove_query_arg( $remove_args ) ) ); ?>"
			   class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
				<?php _e('API', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo esc_url( add_query_arg('tab', 'sync', remove_query_arg( $remove_args ) ) ); ?>"
			   class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Sync', WC_TradeGecko_Init::$text_domain); ?>
			</a><a href="<?php echo esc_url( add_query_arg('tab', 'sync_log', remove_query_arg( $remove_args ) ) ); ?>"
			   class="nav-tab tab_errors <?php echo $active_tab == 'sync_log' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Sync Logs', WC_TradeGecko_Init::$text_domain); if ( 0 < $error_count ) { ?>
				<mark class="error_mark" style=""><?php echo $error_count; ?></mark><?php } ?>
			</a><a href="<?php echo esc_url( add_query_arg('tab', 'import_export', remove_query_arg( $remove_args ) ) ); ?>"
			   class="nav-tab <?php echo $active_tab == 'import_export' ? 'nav-tab-active' : ''; ?>">
				<?php _e('Export/Import', WC_TradeGecko_Init::$text_domain); ?>
			</a>
		</h2>

		<div id="tab_container">

			<?php if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated') ) { ?>
				<div class="updated settings-error">
					<p><strong><?php _e('Settings Updated', WC_TradeGecko_Init::$text_domain); ?></strong></p>
				</div>
			<?php } elseif ( 'true' == WC_TradeGecko_Init::get_get( 'new-auth-code-obtained' ) ) { ?>
				<div class="updated settings-error">
					<p><strong><?php _e('New Authorization Code Obtained', WC_TradeGecko_Init::$text_domain); ?></strong></p>
				</div>
			<?php } ?>

                        <?php
				if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated' ) && 'sync' == $active_tab ) {
                                        // Remove the scheduled hook in case new time and date were set
                                        wp_clear_scheduled_hook( 'wc_tradegecko_synchronization' );
				}

			?><form method="post" action="options.php"><?php

				settings_fields(WC_TradeGecko_Init::$prefix .'settings_'. $active_tab);
				do_settings_sections(WC_TradeGecko_Init::$prefix .'settings_'. $active_tab);

				// No need of save button on export and sync log tabs
				if ( 'import_export' != $active_tab && 'sync_log' != $active_tab ) {
					submit_button();
				}
				?>

			</form>
		</div><!--end #tab_container-->
		<script type="text/javascript">
			jQuery(document).ready(function(){
				// Chosen selects
				jQuery("select.chosen_select").chosen();
			});
		</script>
	</div>
	<?php
	if ( 'sync_log' == $active_tab ) {
		update_option( WC_TradeGecko_Init::$prefix . 'error_count', 0 );
	}

	echo ob_get_clean();
}

/**
 * Try to validate the saved token right after the settings are saved.
 *
 * @since 1.7
 * @param type $active_tab Current tab we are on
 */
function wc_tradegecko_authentication_recheck( $active_tab ) {
	if ( 'true' == WC_TradeGecko_Init::get_get( 'new-auth-code-obtained' ) && 'api' == $active_tab ) {
		$old_auth_code = get_option( 'wc_tradegecko_old_auth_code', '' );
		$new_auth_code = WC_TradeGecko_Init::get_setting('auth_code');

		// We have a new Authorization Code
		if ( $old_auth_code !== $new_auth_code ) {
			update_option( 'wc_tradegecko_old_auth_code', $new_auth_code );

			// Remove the Refresh token
			update_option( 'wc_tradegecko_api_refresh_token', '' );

			// Remove the stored transient
			delete_transient( 'wc_tradegecko_api_access_token' );

			// Update the Auth Error option
			update_option( 'wc_tg_auth_error', '' );

			try {
				// Obtain a new access token right away
				$token = WC_TradeGecko_Init::$api->check_valid_access_token();

				WC_TradeGecko_Init::add_log( 'Successfully obtained first access token with the new Auth Code.' );
			} catch ( Exception $e ) {
				$message = sprintf( __( 'Obtaining first access token with the new Auth Code failed. %s', WC_TradeGecko_Init::$text_domain ), $e->getMessage() );
				WC_TradeGecko_Init::add_sync_log( 'error', $message );
				WC_TradeGecko_Init::add_log( $message );
			}
		}
	}

	// If we saved the API setting
	if ( 'true' == WC_TradeGecko_Init::get_get( 'settings-updated' ) && 'api' == $active_tab ) {

		if ( '' != WC_TradeGecko_Init::get_setting( 'privileged_access_token' ) ) {
			delete_transient( 'validate_privileged_token' );

			update_option( 'wc_tg_auth_error', '' );

			try {
				// Obtain a new access token right away
				$token = WC_TradeGecko_Init::$api->check_valid_access_token();
			} catch ( Exception $e ) {
				// If the error is one of the error codes, show message that credentials need to be double checked
				if ( in_array( $e->getCode(), WC_TradeGecko_Init::auth_error_codes() ) ) {
					$message = sprintf( __( 'The Privileged Access Token entered is not valid. Please double check the token. %s', WC_TradeGecko_Init::$text_domain ), $e->getMessage() );
					WC_TradeGecko_Init::add_sync_log( 'error', $message );
					WC_TradeGecko_Init::add_log( $message );
				} else {
					$message = sprintf( __( 'Privileged Access Token: %s', WC_TradeGecko_Init::$text_domain ), $e->getMessage() );
					WC_TradeGecko_Init::add_sync_log( 'error', $message );
					WC_TradeGecko_Init::add_log( $message );
				}
			}
		} else {
			// Remove the stored transient to force the request for new access token
			delete_transient( 'wc_tradegecko_api_access_token' );
		}
	}
}