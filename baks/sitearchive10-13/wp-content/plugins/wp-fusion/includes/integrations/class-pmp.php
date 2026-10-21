<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(defined('PMPRO_VERSION')) {
	
	class WPF_PMP extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			add_action( 'pmpro_membership_level_after_other_settings', array( $this, 'membership_level_settings' ));
			add_action( 'pmpro_save_membership_level', array( $this, 'save_level_settings' ));
			add_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 2 );
			add_action( 'pmpro_after_change_membership_level', array( $this, 'change_membership_level' ), 10, 2 );

			add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
				
		}

		/**
		 * Adds options to PMP membership level settings
		 *
		 * @access  public
		 * @return  mixed
		 */

		public function membership_level_settings() { ?>

			<?php

			$edit = $_GET['edit'];
			global $wpdb;

			$level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$edit' LIMIT 1", OBJECT);

			$settings = array('remove_tags' => 0, 'apply_tags' => array(), 'apply_tags_expired' => array());

			if(get_option('wpf_pmp_' . $edit)) 
				$settings = array_merge($settings, get_option('wpf_pmp_' . $edit));
			?>
			
			<h3 class="topborder">WP Fusion <?php _e('Settings', 'wpfusion');?></h3>
			<table class="form-table" id="wp_fusion_tab">
				<tbody>
					<tr>
						<th scope="row" valign="top"><label><?php _e('Apply Tags', 'wp-fusion');?>:</label></th>
						<td>
							<?php wpf_render_tag_multiselect( $settings['apply_tags'], 'wpf-settings', 'apply_tags' ); ?><br />
							<small>The selected tags will be applied to the contact in <?php echo wp_fusion()->crm->name; ?> at registration.</small>
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top"><label><?php _e('Remove Tags', 'wp-fusion');?>:</label></th>
						<td>
							<input class="checkbox" type="checkbox" id="wpf-remove-tags" name="wpf-settings[remove_tags]" value="1" <?php echo checked($settings['remove_tags'], 1, FALSE) ?> />
							<label for="wpf-remove-tags">Remove tags if membership is cancelled or changed.</label>
						</td>
					</tr>
					<tr class="expiration_info" <?php if(!pmpro_isLevelExpiring($level)) echo 'style="display: none;"'; ?>>
						<th scope="row" valign="top"><label><?php _e('Apply Tags - Expired', 'wp-fusion');?>:</label></th>
						<td>
							<?php wpf_render_tag_multiselect( $settings['apply_tags_expired'], 'wpf-settings', 'apply_tags_expired' ); ?><br />
							<small>Apply the selected tags when a membership expires.</small>
						</td>
					</tr>
				</tbody>
			</table>

		<?php

		}

		/**
		 * Saves changes to membership level settings
		 *
		 * @access  public
		 * @return  void
		 */

		public function save_level_settings( $saveid ) {

			update_option( 'wpf_pmp_' . $saveid, $_POST['wpf-settings'] );

		}

		/**
		 * Triggered before a user's membership level is changed
		 *
		 * @access  public
		 * @return  void
		 */

		public function before_change_membership_level( $level_id, $user_id ) {

			global $wpdb;

			$old_level = $wpdb->get_row("SELECT
										l.id AS ID
										FROM {$wpdb->pmpro_membership_levels} AS l
										JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
										WHERE mu.user_id = $user_id AND mu.status <> 'active'
										ORDER BY l.id DESC
										LIMIT 1");

			if(empty($old_level))
				return;

			$settings = get_option('wpf_pmp_' . $old_level->ID);

			if(empty($settings))
				return;

			if($settings['remove_tags'] != 1)
				return;

			wp_fusion()->user->remove_tags( $settings['apply_tags'], $user_id );

		}

		/**
		 * Triggered when a user's membership level is changed
		 *
		 * @access  public
		 * @return  void
		 */

		public function change_membership_level( $level_id, $user_id ) {

			$settings = get_option('wpf_pmp_' . $level_id);

			if(!empty($settings) && !empty($settings['apply_tags']))
				wp_fusion()->user->apply_tags($settings['apply_tags'], $user_id);

			// Update level meta
			$level_obj = pmpro_getLevel($level_id);
			
			if(!empty($level_obj))
				wp_fusion()->user->push_user_meta( $user_id, array( 'pmpro_membership_level' => $level_obj->name ) );

			global $wpdb;

			// Optionally apply tags when membership expires
			$expired_level = $wpdb->get_row("SELECT
								membership_id as ID
								FROM {$wpdb->pmpro_memberships_users}
								WHERE user_id = $user_id AND status = 'expired'
								ORDER BY id DESC
								LIMIT 1");

			if(!empty($expired_level)) {

				$settings = get_option('wpf_pmp_' . $expired_level->ID);

				if(!empty($settings['apply_tags_expired']))
					wp_fusion()->user->apply_tags($settings['apply_tags_expired'], $user_id);


			}

		}

		/**
		 * Updates user meta after checkout
		 *
		 * @access  public
		 * @return  array Post Data
		 */

		public function user_register( $post_data, $user_id ) {

			$post_data['user_email'] 		= $post_data['bemail'];
			$post_data['user_login'] 		= $post_data['username'];
			$post_data['user_pass']			= $post_data['password'];
			$post_data['billing_address_1']	= $post_data['baddress1'];
			$post_data['billing_address_2']	= $post_data['baddress2'];
			$post_data['billing_city']		= $post_data['bcity'];
			$post_data['billing_state']		= $post_data['bstate'];
			$post_data['billing_postcode']	= $post_data['bzipcode'];
			$post_data['billing_country']	= $post_data['bcountry'];
			$post_data['phone_number']		= $post_data['bphone'];

			return $post_data;

		}

		/**
		 * Adds PMP meta fields to WPF contact fields list
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$meta_fields['billing_address_1'] 	= array('label' => 'Billing Address 1', 'type' => 'text');
			$meta_fields['billing_address_2'] 	= array('label' => 'Billing Address 2', 'type' => 'text');
			$meta_fields['billing_city'] 		= array('label' => 'Billing City', 'type' => 'text');
			$meta_fields['billing_state'] 		= array('label' => 'Billing State', 'type' => 'text');
			$meta_fields['billing_country'] 	= array('label' => 'Billing Country', 'type' => 'text');
			$meta_fields['billing_postcode'] 	= array('label' => 'Billing Postcode', 'type' => 'text');
			$meta_fields['phone_number'] 		= array('label' => 'Phone Number', 'type' => 'text');
			$meta_fields['pmpro_membership_level'] 		= array('label' => 'Membership Level', 'type' => 'text');

			return $meta_fields;

		}
		
	}

	new WPF_PMP;

}