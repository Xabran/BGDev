<?php

class ITSEC_User_Security_Check {
	public function run() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'manage_toplevel_page_itsec_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_users_custom_column', array( $this, 'column_content' ), null, 3);
		add_action( 'wp_ajax_itsec-user-security-check-user-search', array( $this, 'user_search' ) );
		add_action( 'wp_ajax_itsec-set-user-role', array( $this, 'set_role' ) );
		add_action( 'wp_ajax_itsec-destroy-sessions', array( $this, 'destroy_sessions' ) );
	}

	public function admin_init() {
	}

	function add_columns( $columns ) {
		$columns = array(
			'username'         => __( 'Username' ), // Uses core translation
		);

		if ( class_exists( 'ITSEC_Two_Factor' ) && class_exists( 'ITSEC_Two_Factor_Helper' ) ) {
			$two_factor_helper = ITSEC_Two_Factor_Helper::get_instance();
			if ( $two_factor_helper->get_enabled_providers() ) {
				$columns['itsec-two-factor'] = __( 'Two-Factor', 'it-l10n-ithemes-security-pro' );
			}
		}
		if ( class_exists( 'ITSEC_Lib_User_Activity' ) ) {
			$columns['itsec-last-active'] = __( 'Last Active', 'it-l10n-ithemes-security-pro' );
		}
		$columns['itsec-user-sessions'] = __( 'Sessions', 'it-l10n-ithemes-security-pro' );
		if ( current_user_can( 'promote_users' ) ) {
			$columns['itsec-user-role'] = __( 'Role', 'it-l10n-ithemes-security-pro' );
		}
		return $columns;
	}

	function column_content( $value, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'itsec-last-active':
				$ITSEC_Lib_User_Activity = ITSEC_Lib_User_Activity::get_instance();

				$time = intval( $ITSEC_Lib_User_Activity->get_last_seen( $user_id ) );

				$time_diff = time() - $time;
				if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					return sprintf( __( '%s ago' ), human_time_diff( $time ) ); // Uses core translation
				} elseif ( empty( $time ) ) {
					return __( 'Unknown', 'it-l10n-ithemes-security-pro' );
				} else {
					return mysql2date( __( 'Y/m/d' ), date( 'Y-m-d H:i:s', $time ) ); // Uses core translation
				}
			case 'itsec-two-factor':
				$itsec_two_factor = new ITSEC_Two_Factor();
				if ( $itsec_two_factor->is_user_using_two_factor( $user_id ) ) {
					return '<span class="dashicons dashicons-lock" title="' . esc_attr__( 'Two Factor Enabled', 'it-l10n-ithemes-security-pro' ) . '"></span>';
				} else {
					return '<span class="dashicons dashicons-unlock" title="' . esc_attr__( 'Two Factor Not Enabled', 'it-l10n-ithemes-security-pro' ) . '"></span>';
				}
			case 'itsec-user-sessions':
				return $this->get_user_session_cell_contents( $user_id );
			case 'itsec-user-role':
				if ( ! current_user_can( 'promote_users' ) ) {
					return '';
				}
				$user = get_userdata( $user_id );
				ob_start();
				?>
				<label class="screen-reader-text" for="<?php echo esc_attr( 'change_role-' . $user_id ); ?>"><?php _e( 'Change role to&hellip;' ) ?></label>
				<select name="<?php echo esc_attr( 'change_role-' . $user_id ); ?>" id="<?php echo esc_attr( 'change_role-' . $user_id ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?> " data-nonce="<?php echo esc_attr( wp_create_nonce( 'itsec-user-security-check-set-role-' . $user_id ) ); ?>">
					<option value="" disabled><?php _e( 'Change role to&hellip;' ) ?></option>
					<?php wp_dropdown_roles($user->roles[0]); ?>
				</select>
				<?php
				return ob_get_clean();
		}
		return $value;
	}

	private function get_user_session_cell_contents( $user_id ) {
		$wp_sessions = WP_Session_Tokens::get_instance( $user_id );
		$sessions = $wp_sessions->get_all();
		if ( empty( $sessions ) ) {
			return __( 'Not currently logged in anywhere.', 'it-l10n-ithemes-security-pro' );
		} elseif( $user_id === get_current_user_id() && 1 === count( $sessions ) ) {
			return __( 'You are only logged in at this location.' );
		} else {
			$label = ( $user_id === get_current_user_id() )? __( 'Log Out Everywhere Else' ) : __( 'Log Out Everywhere' );// Uses code translation
			$return = sprintf( _n( 'Currently logged in at one location.', 'Currently logged in at %d locations.', count( $sessions ), 'it-l10n-ithemes-security-pro' ), count( $sessions ) );
			$return .= '<p><button type="button" class="destroy-sessions button button-secondary" data-nonce="' . esc_attr( wp_create_nonce( 'update-user_' . $user_id ) ) . '" data-user_id="' . esc_attr( $user_id ) . '">' . $label . '</button></p>';
			return $return;
		}
	}

	public function user_search() {
		if ( wp_verify_nonce( $_POST['_nonce'], 'itsec-user-security-check-user-search' ) ) {
			$return = new stdClass();
			require_once( 'class-itsec-wp-users-list-table.php' );
			$wp_list_table = new ITSEC_WP_Users_List_Table( array( 'screen' => 'toplevel_page_itsec' ) );

			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->views();
			$return->views = ob_get_clean();
			ob_start();
			$wp_list_table->search_box( __( 'Search Users' ), 'user' );
			$return->search_box = ob_get_clean();
			$return->search_nonce = wp_create_nonce( 'itsec-user-security-check-user-search' );
			ob_start();
			$wp_list_table->display();
			$return->users_table = ob_get_clean();
			wp_send_json_success( $return );
		}
		wp_send_json_error( array( 'message' => __( 'There was a problem searching.', 'it-l10n-ithemes-security-pro' ) ) );
	}

	public function set_role() {
		$user_id = absint( $_POST['user_id'] );
		if ( wp_verify_nonce( $_POST['_nonce'], 'itsec-user-security-check-set-role-' . $user_id ) && ! empty( $_REQUEST['new_role'] ) ) {
			$user = get_userdata( $user_id );
			$user->set_role( $_REQUEST['new_role'] );

			$return = new stdClass();
			require_once( 'class-itsec-wp-users-list-table.php' );
			$wp_list_table = new ITSEC_WP_Users_List_Table( array( 'screen' => 'toplevel_page_itsec' ) );

			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->views();
			$return->views = ob_get_clean();
			$return->message = __( 'Successfully updated role.', 'it-l10n-ithemes-security-pro' );

			wp_send_json_success( $return );
		} else {
			wp_send_json_error( array( 'message' => __( 'There was a problem updaing the user role.', 'it-l10n-ithemes-security-pro' ) ) );
		}
	}

	/**
	 * Ajax handler for destroying multiple open sessions for a user.
	 *
	 * Based on wp_ajax_destroy_sessions()
	 */
	public function destroy_sessions() {
		$user = get_userdata( (int) $_POST['user_id'] );
		if ( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) ) {
				$user = false;
			} elseif ( ! wp_verify_nonce( $_POST['nonce'], 'update-user_' . $user->ID ) ) {
				$user = false;
			}
		}

		if ( ! $user ) {
			wp_send_json_error( array(
				'message' => __( 'Could not log out user sessions. Please try again.' ),
			) );
		}

		$sessions = WP_Session_Tokens::get_instance( $user->ID );

		if ( $user->ID === get_current_user_id() ) {
			$sessions->destroy_others( wp_get_session_token() );
			$message = __( 'You are now logged out everywhere else.' );
		} else {
			$sessions->destroy_all();
			/* translators: 1: User's display name. */
			$message = sprintf( __( '%s has been logged out.' ), $user->display_name );
		}

		wp_send_json_success( array( 'message' => $message, 'session_cell_contents' => $this->get_user_session_cell_contents( $user->ID ) ) );
	}
}
