<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if( defined( 'RCP_PLUGIN_VERSION' ) ) {

	class WPF_Restrict_Content_Pro extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @since   1.0
		 * @return  void
		 */

		public function init() {

			// Meta fields
			add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

			// Add interfaces to admin
			add_action( 'rcp_edit_subscription_form', array( $this, 'subscription_settings' ) );
			add_action( 'rcp_add_subscription_form', array( $this, 'subscription_settings' ) );
			add_action( 'rcp_edit_subscription_level', array( $this, 'save_subscription_settings' ), 10, 2);
			add_action( 'rcp_add_subscription', array( $this, 'save_subscription_settings_new' ), 10, 2);

			// Registrations and future profile updates
			add_action( 'rcp_edit_member', array( $this, 'status_change' ), 1000 );
			add_action( 'wpf_tags_modified', array( $this, 'change_level' ), 10, 2);

		}


		/**
		 * Applies a subscription to any users who have the specified CRM tag
		 *
		 * @access  private
		 * @return  void
		 */

		private function tag_to_sub( $tag_id, $level_id ) {

			$args = array(
				'fields'	=> 'ID',
				'meta_query' => array(
					array(
						'key'     =>  wp_fusion()->crm->slug . '_tags',
						'value'   => '"' . $tag_id . '"',
			 			'compare' => 'LIKE'
					)
				)
			);

			$users = get_users( $args );

			foreach ($users as $user_id) {
				update_user_meta( $user_id, 'rcp_subscription_level', $level_id );	
			}

		}


		/**
		 * Maps RCP specific POST fields to standard ones
		 *
		 * @access  public
		 * @return  array Post Data
		 */

		public function user_register( $post_data, $user_id ) {

			$post_data['user_pass'] = $post_data['rcp_user_pass'];
			$post_data['user_login'] = $post_data['rcp_user_login'];

			return $post_data;

		}

		/**
		 * Adds RCP meta fields to WPF contact fields list
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$meta_fields['rcp_level'] = array('label' => 'RCP Membership Level', 'type' => 'text');

			return $meta_fields;

		}

		/**
		 * Adds CRM tag association field to edit view for single Subscription
		 *
		 * @access  public
		 * @return  mixed
		 */

		public function subscription_settings( $level = false ) { ?>


			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-role"><?php _e( 'Link with ' . wp_fusion()->crm->name . ' tag', 'wp-fusion' ); ?></label>
				</th>
				<td>
					<?php $settings = get_option( 'wpf_rcp_tags', array() ); ?>

					<?php 
					if(is_object($level) && isset($settings[$level->level])) {
						$settings = array( 'apply_tags' => $settings[$level->level]['apply_tags'] );
					} else {
						$settings = array( 'apply_tags' );
					}
					?>

					<?php wpf_render_tag_multiselect( $settings['apply_tags'], 'wpf-settings', 'apply_tags', null, false, false, 1 ); ?>
					<p class="description"><?php _e( 'This tag will be applied to the user when they register. You can also grant any user this subscription level by manually applying the tag.', 'wp-fusion' ); ?></p>
				</td>
			</tr>

			<?php

		}

		/**
		 * Saves changes to WPF fields on the subscription edit screen
		 *
		 * @access  public
		 * @return  void
		 */

		public function save_subscription_settings( $id, $args ) {

			if(!isset($args['wpf-settings']))
				return;
			
			$settings = get_option( 'wpf_rcp_tags', array() );
			$settings[$args['level']] = $args['wpf-settings'];

			update_option( 'wpf_rcp_tags', $settings );

			$this->tag_to_sub( $args['wpf-settings']['apply_tags'][0], $args['level'] );

		}


		/**
		 * Saves WPF settings for new subscription levels
		 *
		 * @access  public
		 * @return  void
		 */

		public function save_subscription_settings_new( $id, $args ) {

			if(!isset($_POST['wpf-settings']))
				return;
			
			$settings = get_option( 'wpf_rcp_tags', array() );
			$settings[$id] = $_POST['wpf-settings'];
			update_option( 'wpf_rcp_tags', $settings );

			$this->tag_to_sub( $_POST['wpf-settings']['apply_tags'][0], $id );

		}


		/**
		 * Triggered when a user's status is changed in RCP
		 *
		 * @access  public
		 * @return  void
		 */

		public function status_change( $user_id ) {

			$level = get_user_meta( $user_id, 'rcp_subscription_level', true );
			$tags = get_option( 'wpf_rcp_tags' );

			if(!empty($tags[$level])) 
				wp_fusion()->user->apply_tags( $tags[$level]['apply_tags'], $user_id );

		}


		/**
		 * Triggered when a user's tags are modified
		 *
		 * @access  public
		 * @return  void
		 */

		public function change_level( $user_id, $user_tags ) {

			if($user_tags == false)
				return;

			$rcp_tag_map = get_option( 'wpf_rcp_tags', array() );

			$user_current_level = get_user_meta( $user_id, 'rcp_subscription_level', true );

			foreach( $rcp_tag_map as $level_id => $tags ) {

				$tag = $tags['apply_tags'][0];

				if( in_array( $tag, $user_tags ) && $level_id != $user_current_level )
					update_user_meta( $user_id, 'rcp_subscription_level', $level_id );

			}


		}

	}

	new WPF_Restrict_Content_Pro;

}