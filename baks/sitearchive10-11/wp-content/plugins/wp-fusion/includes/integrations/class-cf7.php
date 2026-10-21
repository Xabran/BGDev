<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'wpcf7' )) {
	
	class WPF_CF7 extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			add_filter( 'wpcf7_editor_panels', 	array( $this, 'add_panel' ));
			add_action(	'wpcf7_mail_sent', 		array( $this, 'send_data' ));
			add_action( 'wpcf7_after_save', 	array( $this, 'save_form' ));
				
		}

		/**
		 * Adds panel to CF7 settings page
		 *
		 * @access  public
		 * @return  array Panels
		 */

		public function add_panel( $panels ) {
			
			$panels['wp-fusion-tab'] = array('title' => wp_fusion()->crm->name,
											'callback' => array($this, 'add_form'));
			
			return $panels;

		}
		
		
		/**
		 * Adds form content to panel
		 *
		 * @access  public
		 * @return  mixed Panel content
		 */
		
		public function add_form( $info ) {

			wp_nonce_field( 'cf7_wpf_nonce', 'cf7_wpf_nonce' );
			
			$post_id = $info->id();			
			$content = $info->prop('form');
			$inputs = array();

			// Get inputs from saved form config
			preg_match_all("/\[.*\]/", $content, $matches);

			foreach ($matches[0] as $input){		
				$input = substr($input, 1, -1);
				$input = str_replace('*', '', $input);
				$elements = explode(' ', $input);
			
				if(!($elements[1] == '"Send"'))
					$inputs[$elements[1]] = $elements[0];
			}
			
			$settings = get_post_meta($post_id, 'cf7_wpf_settings', true);
			
			echo '<h2>' . wp_fusion()->crm->name . ' Settings</h2>';
			
			echo '<fieldset><legend>For each field in the form, select a field to sync with in ' . wp_fusion()->crm->name . '</legend>';
			
			echo '<table id="wpf-cf7-table">';
			 	echo '<tbody id="wp-fusion-inputs">';				
				foreach($inputs as $name => $type){
				$capitalName = str_replace('-', '  ', $name);
				if(!isset($settings[$name]['crm_field']))$settings[$name]['crm_field'] = '';
					echo '<tr id="input-row">';
						echo '<td><label> '. ucwords($capitalName) .' <label></td>' ;
						echo '<td><label for ="cf7_wpf_settings"> '. $type .' <label></td>' ;
						echo '<td class="crm-field">' ;
						wpf_render_crm_field_select($settings[$name]['crm_field'], 'cf7_wpf_settings', $name);
						echo '</td>';
					echo '</tr>';
				}
				echo '</tbody>';
			echo '</table>';
		
			$multiselect = $name;
			if(isset($settings['tags'])) $multiselect = $settings['tags'];
			
			
			echo '<p class="description"><label for="tags">Apply the following tags when the form is submitted:</label><br />';
				wpf_render_tag_multiselect($multiselect, 'cf7_wpf_settings', 'tags');
			echo '</p>';

			echo '</fieldset>';
		
		}
	
		/**
		 * Save WPF settings fields
		 * @access public
		 * @param unknown $contact_form
		 */
		
		public function save_form( $contact_form ){
			
			if ( !isset( $_POST ) || empty( $_POST ) || !isset( $_POST['cf7_wpf_nonce'] ) )
				return;

			$post_id = $contact_form->id();
			update_post_meta($post_id, 'cf7_wpf_settings', $_POST['cf7_wpf_settings']);

		}
		
		/**
		 * Send data to CRM on form submission
		 *
		 * @access  public
		 * @return  array Classes
		 */
		
		public function send_data($contact_form){
			
			$contact_form_id = $contact_form->id();
			$submission = WPCF7_Submission::get_instance();
			$posted_data = $submission->get_posted_data();			

			$wpf_settings = get_post_meta($contact_form_id, 'cf7_wpf_settings', true);

			$update_data = array();
		
			foreach($posted_data as $key => $value) {

				if(!isset($wpf_settings[$key]) || empty($wpf_settings[$key]['crm_field']))
					continue;

				if(is_array($value))
					$value = implode(', ', $value);

				if(is_email($value))
					$email = $value;

				$update_data[$wpf_settings[$key]['crm_field']] = $value;

			}
			
			
			if(!is_user_logged_in() && !isset($email) ) {

				// Don't send anything if user isn't logged in and no email provided
				return;


			} elseif (!isset($email) && is_user_logged_in()) {
			
				$user_id = get_current_user_id();
				$contact_id = wp_fusion()->user->get_contact_id( $user_id );
			
			} else {

				if(is_object(get_user_by( 'email', $email ))) {
			
					// Check and see if a local user exists
					$user = get_user_by( 'email', $email );
					$user_id = $user->ID;
					$contact_id = wp_fusion()->user->get_contact_id($user_id);
			
				} else {
			
					// Check and see if user exists in CRM
					$user_id = false;
					$contact_id = wp_fusion()->crm->get_contact_id( $email );
			
				}
			}
			
			$update_data = apply_filters( 'wpf_cf7_pre_submission', $update_data, $user_id, $contact_id );
			

			// Update / add contact

			if($contact_id != false) {

				wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

			} else {

				$contact_id = wp_fusion()->crm->add_contact( $update_data, false );

			}

			// Apply tags

			if(isset($wpf_settings['tags']) && !empty($wpf_settings['tags'])) {

				if($user_id != false) {

					wp_fusion()->user->apply_tags( $wpf_settings['tags'], $user_id );

				} else {

					wp_fusion()->crm->apply_tags( $wpf_settings['tags'], $contact_id );

				}

			}
			
			do_action( 'wpf_cf7_post_submission', $update_data, $user_id, $contact_id );
			
		}
	}

	new WPF_CF7;

}