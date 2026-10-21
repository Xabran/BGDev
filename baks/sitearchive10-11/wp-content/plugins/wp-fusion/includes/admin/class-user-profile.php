<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_User_Profile {

    public function __construct() {

		// User profile display / edit
		add_action( 'show_user_profile', array( $this, 'user_profile' ));
		add_action( 'edit_user_profile', array( $this, 'user_profile' ));

		// Scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'user_profile_enqueue_scripts' ) );

		// AJAX
		add_action( 'wp_ajax_resync_contact', array( $this, 'resync_contact' ));

		// Updates
		add_action( 'user_register', array( $this, 'user_profile_update' ));
		add_action( 'profile_update', array( $this, 'user_profile_update' ));

		// Filters for posted data from internal forms
		add_filter( 'wpf_user_register', array( $this, 'filter_form_fields' ), 10, 2 );
		add_filter( 'wpf_user_update', array( $this, 'filter_form_fields' ), 10, 2 );

    }


    /**
	 * Updates the contact record in the CRM when a profile is edited in the backend
	 *
	 * @access public
	 * @return void
	 */

	public function user_profile_update( $user_id ) {

		if(wp_fusion()->settings->get('push') == true)
			wp_fusion()->user->push_user_meta( $user_id );

	}


    /**
	 * Resynchronize local user ID with IS contact record
	 *
	 * @access public
	 * @return mixed
	 */

	public function resync_contact() {

		$user_id = $_POST['user_id'];

		// Force reset contact ID and search for new match
		$contact_id = wp_fusion()->user->get_contact_id($user_id, true);

		// If no contact found
		if(empty($contact_id))
			wp_send_json_error();

		// Force reset tags and search for new tags
		$user_tags = wp_fusion()->user->get_tags($user_id, true);

		$response = array(
			'contact_id'	=> $contact_id,
			'user_tags'		=> $user_tags
			);

		do_action('wpf_resync_contact', $user_id);

		// Return the result to the script and die
		echo json_encode($response);

		wp_die();

	}


	/**
	 * Enqueue scripts pn user profile page
	 *
	 * @access public
	 * @return void
	 */

	public function user_profile_enqueue_scripts($hook) {

		if ( 'user-edit.php' == $hook || 'profile.php' == $hook) {

			wp_enqueue_script( 'wpf-options', WPF_DIR_URL . '/assets/js/wpf-admin.js', array('jquery'));

		}

	}


	/**
	 * Filters registration data before sending to the CRM (internal add / edit fields)
	 *
	 * @access public
	 * @return array Registration data
	 */

	public function filter_form_fields( $post_data, $user_id ) {

		global $pagenow;

		if($pagenow == 'profile.php' || $pagenow == 'user-edit.php' || $pagenow == 'user-new.php') {

			if(isset( $post_data['email'] )) {

				$post_data['user_email'] = $post_data['email'];
				unset($post_data['email']);

				$post_data['user_url'] = $post_data['url'];
				unset($post_data['url']);

				if(isset($post_data['pass1-text'])) {
					$post_data['user_pass'] = $post_data['pass1-text'];
					unset($post_data['pass1-text']);
				}

			}

			if(isset($post_data['user_password'])) {

				$post_data['user_pass'] = $post_data['user_password'];
				unset($post_data['user_password']);

			}

		}

		return $post_data;

	}


	/**
	 * Adds fields to user profile
	 *
	 * @access public
	 * @return void
	 */

	public function user_profile($user) { ?>
		<h3>WP Fusion</h3>

		<table class="form-table">
			<tr>
				<th><label for="contact_id"><?php echo wp_fusion()->crm->name ?> Contact ID</label></th>
				<td id="contact-id">
					<?php if(wp_fusion()->user->get_contact_id($user->ID)) : ?>
					 		<?php echo wp_fusion()->user->get_contact_id($user->ID); ?>
					<?php else : ?>

						No <?php echo wp_fusion()->crm->name ?> contact record found.

					<?php endif; ?>
				</td>
			</tr>
			<?php if(wp_fusion()->user->get_contact_id($user->ID)) : ?>
				<tr id="wpf-tags-row">
					<th><label for="wpf_tags"><?php echo wp_fusion()->crm->name ?> Tags</label></th>
					<td id="wpf-tags-td">
						
						<?php $user_tags = wp_fusion()->user->get_tags($user->ID) ?>

						<?php if(!empty($user_tags)) { ?>

							<select disabled multiple="multiple" id="wpf_tags" class="select4-wpf-tags">

								<?php foreach($user_tags as $id) { ?>
									<?php // if(empty($label)) $label = wp_fusion()->user->sync_single_tag($id, $user->ID); ?>
									<option selected value="<?php echo $id; ?>"><?php echo wp_fusion()->user->get_tag_label($id) ?></option>
								<?php } ?>

							</select>
							<p class="description">These tags are currently applied to the user in <?php echo wp_fusion()->crm->name ?>.</p>

						<?php } else { ?>

							No tags applied.

						<?php } ?>

					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label for="resync_contact">Resync Tags</label></th>
				<td>

					<a id="resync-contact" class="button button-default" data-user_id="<?php echo $user->ID ?>">Resync Tags</a>
					<p class="description">If the contact ID or tags aren't in sync, click here to reset the local data and load from the <?php echo wp_fusion()->crm->name ?> contact record.</p>

				</td>
			</tr>
		</table>
		<?php
	}
}

new WPF_User_Profile;