<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if (class_exists("GFForms")) {

    GFForms::include_feed_addon_framework();

    class WPF_GForms_Integration extends GFFeedAddOn {
        protected $_version = WP_FUSION_VERSION; 
        protected $_min_gravityforms_version = "1.7.9999";
        protected $_slug = "wpfgforms";
        protected $_full_path = __FILE__;
        protected $_title = "CRM Integration";
        protected $_short_title = "WP Fusion";
        protected $postvars = array();


		/**
		 * Get parent running
		 *
		 * @access  public
		 * @return  void
		 */

        public function init(){

            parent::init();

        }


		/**
		 * Triggered when form is submitted
		 *
		 * @access  public
		 * @return  void
		 */

        public function process_feed( $feed, $entry, $form ){

			$update_data = array();

			// Prepare update array
			foreach($feed['meta']['wpf_fields'] as $id => $data) {

				// Convert dashes back into points for isset
				$id = str_replace('-', '.', $id);

				if(isset($entry[$id]) && !empty($entry[$id]) && !empty($data['crm_field'])) {

					$value = apply_filters( 'wpf_format_field_value', $entry[$id], $data['type'], $data['crm_field'] );

					if(!empty($value)) {
						$update_data[$data['crm_field']] = $value;

						if($data['type'] == 'email') {
							$email_address = $entry[$id];
						}

					}
				}
			}

			// If no email and user not logged in don't bother
			if(!isset($email_address) && !is_user_logged_in()) {

				return;

			} elseif (!isset($email_address) && is_user_logged_in()) {

				global $current_user;
				$contact_id = wp_fusion()->user->get_contact_id( $current_user->ID );

			} else {

				if(is_object(get_user_by( 'email', $email_address ))) {

					// Check and see if a local user exists
					$user = get_user_by( 'email', $email_address );
					$user_id = $user->ID;
					$contact_id = wp_fusion()->user->get_contact_id($user_id);

				} else {

					// Check and see if user exists in CRM
					$user_id = false;
					$contact_id = wp_fusion()->crm->get_contact_id( $email_address );

				}
			}

			$update_data = apply_filters( 'wpf_gform_pre_submission', $update_data, $user_id, $contact_id );

			if($contact_id != false) {

				// Update Infusionsoft if contact ID exists
				wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

			} else {

				// Add contact if doesn't exist yet
				$contact_id = wp_fusion()->crm->add_contact( $update_data, false );

			}

			// Apply tags if set
			if(isset($feed['meta']['wpf_tags'])) {

				// Even if the user is logged in, they may have submitted the form with a different email. This makes sure the tags are applied to the right record
				if(isset($user_id))
					$user_info = get_userdata( $user_id );

				if(isset($user_info) && $user_info->user_email == $email_address) {

					// If user exists locally, apply the tags locally as well
					wp_fusion()->user->apply_tags($feed['meta']['wpf_tags'], $user_id);

				} else {

					wp_fusion()->crm->apply_tags( $feed['meta']['wpf_tags'], $contact_id );

				}

			}

			do_action( 'wpf_gform_post_submission', $update_data, $user_id, $contact_id );

        }

		/**
		 * Displays table for mapping fields
		 *
		 * @access  public
		 * @return  void
		 */

        public function settings_wpf_fields( $field ) {

        	$form = $this->get_current_form();

			// Quiz Handling
			$quiz_fields = GFAPI::get_fields_by_type( $form, array( 'quiz' ) );

			if ( !empty( $quiz_fields ) ) {

				$quiz_fields = array(
							'gquiz_score' 		=> 'Quiz Score Total',
							'gquiz_percent'	 	=> 'Quiz Score Percentage',
							'gquiz_grade' 		=> 'Quiz Grade',
							'gquiz_is_pass' 	=> 'Quiz Pass/Fail'
					);

			}

        	echo '<table class="settings-field-map-table wpf-field-map" cellspacing="0" cellpadding="0">';

        		echo '<tbody>';

        			$email_found = false;

        			foreach($form['fields'] as $field) {

        				if($field->inputs == null) {

        					// Handing for simple fields (no subfields)

        					if($field->type == 'email')
        						$email_found = true;

	        				echo '<tr>';
	        					echo '<td><label>' . $field->label . '<label></td>';
	        					echo '<td><i class="fa fa-angle-double-right"></i></td>';
	        					echo '<td>';
								wpf_render_crm_field_select( $this->get_setting('wpf_fields[' . $field->id . '][crm_field]'), '_gaddon_setting_wpf_fields', $field->id );

								$this->settings_hidden(
									array(
										'label'         => '',
										'name'          => 'wpf_fields[' . $field->id . '][type]',
										'default_value' => $field->type
									)
								);

	        					echo '</td>';
	        				echo '</tr>';

	        			} else {

	        				// Fields with subfields (Name, Address, etc.)

        					foreach($field->inputs as $input) {

        						if(!isset($input['isHidden']) || $input['isHidden'] == false) {

		        					if($field->type == 'email')
		        						$email_found = true;

	        						if($input['label'] == 'First') {
	        							$std = 'First Name';
	        							$name = 'FirstName';
	        						} elseif($input['label'] == 'Last') {
	        							$std = 'Last Name';
	        							$name = 'LastName';
	        						} else {
	        							$std = '';
	        							$name = '';
	        						}

		        					echo '<tr>';
		        					echo '<td><label>' . $field->label . ' - ' . $input['label'] .'<label></td>';
		        					echo '<td><i class="fa fa-angle-double-right"></i></td>';

		        					echo '<td>';
		        					wpf_render_crm_field_select( $this->get_setting('wpf_fields[' . str_replace('.', '-', $input['id']) . '][crm_field]'), '_gaddon_setting_wpf_fields', str_replace('.', '-', $input['id']) );
									
									$this->settings_hidden(
										array(
											'label'         => '',
											'name'          => 'wpf_fields[' . str_replace('.', '-', $input['id']) . '][type]',
											'default_value' => $field->type
										)
									);

		        					echo '</td>';
		        					echo '</tr>';

        						}

        					}

	        			}

        			}

					if ( !empty( $quiz_fields ) ) {

						echo '<tr><td colspan="2"><strong><br />Quiz Fields</strong></td></tr>';

						foreach( $quiz_fields as $id => $label ) {

	        				echo '<tr>';
	        					echo '<td><label>' . $label . '<label></td>';
	        					echo '<td><i class="fa fa-angle-double-right"></i></td>';
	        					echo '<td>';
								wpf_render_crm_field_select( $this->get_setting('wpf_fields[' . $id . '][crm_field]'), '_gaddon_setting_wpf_fields', $id );

								$this->settings_hidden(
									array(
										'label'         => '',
										'name'          => 'wpf_fields[' . $id . '][type]',
										'default_value' => 'text'
									)
								);

	        					echo '</td>';
	        				echo '</tr>';

						}

					}

        		echo '</tbody>';
        	echo '</table>';

        	if($email_found == false)
        		echo '<div class="alert danger"><strong>Warning:</strong> No <i>email</i> type field found on this form. Entries from guest users will not be sent to ' . wp_fusion()->crm->name . '.</div>';


        }

		/**
		 * Saves settings
		 *
		 * @access  public
		 * @return  array Settings
		 */

        public function save_wpf_fields($field, $setting) {

        	foreach($setting as $index => $fields) {

        		if(!empty($fields['crm_field'])) {
					$setting[$index]['crm_field'] = $setting[$index]['crm_field'];
        		} else {
        			unset($setting[$index]);
        		}

        	}

        	return $setting;

        }


		/**
		 * Renders tag multi select field
		 *
		 * @access  public
		 * @return  void
		 */

        public function settings_wpf_tags( $field ) {

        	wpf_render_tag_multiselect($this->get_setting('wpf_tags'), '_gaddon_setting_wpf_tags');

        }

		/**
		 * Defines settings for the feed
		 *
		 * @access  public
		 * @return  array Feed settings
		 */

    	public function feed_settings_fields() {
            return array(
                array(
                    "title"  => WPF_CRM_NAME . " Integration",
                    "fields" => array(

                        array(
                            "label"   => "Feed name",
                            "type"    => "text",
                            "name"    => "feedName",
                            "tooltip" => "Enter a name to remember this feed by.",
                            "class"   => "small"
                        ),

                        array(
                            "name" => "wpf_fields",
                            "label" => "Map Fields",
                            "type" => "wpf_fields",
                            "tooltip" => "Select a CRM field from the dropdown, or leave blank to disable sync",
                            'save_callback' => array( $this, 'save_wpf_fields' )
                        ),

                        array(
                            "name" => "wpf_tags",
                            "label" => "Apply Tags",
                            "type" => "wpf_tags",
                            "tooltip" => "Select tags to be applied when a user submits this form.",
                        ),

						array(
							'type'           => 'feed_condition',
							'name'           => 'condition',
							'label'          => 'Opt-In Condition',
							'checkbox_label' => 'Enable Condition',
							'instructions'   => 'Process this feed if'
						)
                    )
                )
            );
        }

		/**
		 * Creates columns for feed
		 *
		 * @access  public
		 * @return  array Feed settings
		 */

		public function feed_list_columns() {
			return array(
				'feedName' 	=> __('Name', 'wp-fusion'),
				'gftags' 	=> __('Applies Tags', 'wp-fusion'),
			);
		}

		/**
		 * Displays tags in custom column
		 *
		 * @access  public
		 * @return  string Configured tags
		 */


		public function get_column_value_gftags( $feed ){

			$tags = rgars( $feed, 'meta/wpf_tags' );
			$tag_labels = array();
			foreach($tags as $tag) {
				$tag_labels[] = wp_fusion()->user->get_tag_label($tag);
			}

		    return '<b>' . implode(', ', $tag_labels) . '</b>';
		}

		/**
		 * Loads stylesheets
		 *
		 * @access  public
		 * @return  array Styles
		 */

		public function styles() {
            $styles = array(
                array("handle"  => "wpf_gforms_css",
                      "src"     => WPF_DIR_URL . 'assets/css/wpf-gforms.css',
                      "version" => $this->_version,
                      "enqueue" => array(
                          array( 'tab' => 'wpfgforms' )
                      )
                ),
                array("handle"  => "select4",
                      "src"     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.css',
                      "version" => '4.0.1',
                      "enqueue" => array(
                          array( 'tab' => 'wpfgforms' )
                      )
                ),
                array("handle"  => "wpf-admin",
                      "src"     => WPF_DIR_URL . 'assets/css/wpf-admin.css',
                      "version" => $this->_version,
                      "enqueue" => array(
                          array( 'tab' => 'wpfgforms' )
                      )
                ),
            );

            return array_merge(parent::styles(), $styles);
        }

		/**
		 * Loads scripts
		 *
		 * @access  public
		 * @return  array Scripts
		 */

		public function scripts() {
			$scripts = array(
				array(
					'handle'  => 'select4',
					'src'     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.js',
					'version' => '4.0.1',
					'deps'    => array( 'jquery' ),
					'enqueue' => array(
						array(
							'admin_page' => array( 'form_settings' ),
							'tab'        => 'wpfgforms',
						),
					),
				),
				array(
					'handle'  => 'wpf-admin',
					'src'     => WPF_DIR_URL . 'assets/js/wpf-admin.js',
					'version' => $this->_version,
					'deps'    => array( 'jquery', 'select4' ),
					'enqueue' => array(
						array(
							'admin_page' => array( 'form_settings' ),
							'tab'        => 'wpfgforms',
						),
					),
				),
			);
			return array_merge( parent::scripts(), $scripts );
		}

    }

    new WPF_GForms_Integration;

}


