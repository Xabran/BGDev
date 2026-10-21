<?php 

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Renders the field select HTML for admin pages
 *
 * @access public
 * @return mixed HTML field
 */

function wpf_render_tag_multiselect( $settings, $meta_name, $field_id = null, $field_sub_id = null, $disabled = false, $placeholder = false, $limit = null ) {

	$available_tags = wp_fusion()->settings->get('available_tags');

	// If no tags, set a blank array
	if(!is_array($available_tags))
		$available_tags = array();

	if( is_array( reset($available_tags) ) ) {

		// Handling for select with category groupings

		$tag_categories = array();
		foreach( $available_tags as $value ) {
			$tag_categories[] = $value['category'];
		}

		$tag_categories = array_unique($tag_categories);

		echo '<select' . ($disabled == true ? ' disabled' : '') . ' data-placeholder="' . (empty($placeholder) ? 'Select tags' : $placeholder) . '" multiple="multiple" data-limit="' . $limit .'" id="' . $field_id . (isset($field_sub_id) ? '-' . $field_sub_id : '') . '" class="select4-wpf-tags" name="' . $meta_name . (isset($field_id) ? '[' . $field_id . ']' : '') . (isset($field_sub_id) ? '[' . $field_sub_id . ']' : '') . '[]">';

		foreach($tag_categories as $tag_category) {

			echo '<optgroup label="' . $tag_category . '">';

				foreach($available_tags as $id => $field_data) {

					if($field_data['category'] == $tag_category) {
						echo '<option value="'.esc_attr($id).'"'. (is_null($field_sub_id) ? selected(true, in_array($id, (array)$settings ), FALSE) : selected(true, in_array($id, (array)$settings [$field_sub_id]), FALSE)).'>'.$field_data['label'].'</option>';
					}

				}
			echo '</optgroup>';
		}

		echo '</select>';

	} else {

		// Handling for single level select (no categories)

		echo '<select ' . ($disabled == true ? ' disabled' : '') .  ' data-placeholder="' . (empty($placeholder) ? 'Select tags' : $placeholder) . '" multiple="multiple" id="' . $field_id . (isset($field_sub_id) ? '-' . $field_sub_id : '') . '" data-limit="' . $limit .'" class="select4-wpf-tags" name="' . $meta_name . (isset($field_id) ? '[' . $field_id . ']' : '') . (isset($field_sub_id) ? '[' . $field_sub_id . ']' : '') . '[]">';

			foreach( $available_tags as $id => $tag ) {

				// Fix for bug with "0" appearing as an available tag
				if($tag == "0")
					continue;

				echo '<option value="' . esc_attr($id) . '"'. (is_null($field_sub_id) ? selected(true, in_array($id, (array)$settings), FALSE) : selected(true, in_array($id, (array)$settings[$field_sub_id]), FALSE)).'>' . $tag . '</option>';

			}


			if(in_array('add_tags', wp_fusion()->crm->supports)) {

				// Check to see if new custom tags have been added
				foreach( (array)$settings as $i => $tag ) {

					// For settings with sub-ids (like Woo variations)
					if(is_array($tag)) {

						foreach($tag as $sub_tag) {

							if(!in_array($sub_tag, $available_tags) && $i == $field_sub_id ) {

								echo '<option value="' . esc_attr($sub_tag) . '" selected="selected">' . $sub_tag . '</option>';
								$available_tags[$sub_tag] = $sub_tag;
								wp_fusion()->settings->set('available_tags', $available_tags);
							}

						}

					} elseif(!in_array($tag, $available_tags)) {

						echo '<option value="' . esc_attr($tag) . '" selected="selected">' . $tag . '</option>';
						$available_tags[$tag] = $tag;
						wp_fusion()->settings->set('available_tags', $available_tags);

					}

				}

			}


		echo '</select>';


	}

}

/**
 * Renders a dropdown with all custom fields for the current CRM
 *
 * @access public
 * @return mixed HTML field
 */

function wpf_render_crm_field_select( $setting, $meta_name, $field_id, $field_sub_id = null ) {

	echo '<select id="' . $field_id . (isset($field_sub_id) ? '-' . $field_sub_id : '') . '" class="select4-crm-field" name="' . $meta_name . '[' . $field_id . ']' . (isset($field_sub_id) ? '[' . $field_sub_id . ']' : '') . '[crm_field]" data-placeholder="Select a field">';

		echo '<option></option>';

		$crm_fields = wp_fusion()->settings->get('crm_fields');

		if(!empty( $crm_fields )) {

			foreach( $crm_fields as $group_header => $fields ) {

				// For CRMs with separate custom and built in fields
				if(is_array($fields)) {
				
					echo '<optgroup label="' . $group_header . '">';

						foreach($crm_fields[$group_header] as $field => $label) {

							if(is_array($label))
								$label = $label['label'];

							echo '<option ' . selected(esc_attr( $setting ), $field ) . ' value="' . $field . '">' . $label . '</option>';
						}


					echo '</optgroup>';

				} else {

					$field = $group_header;
					$label = $fields;

					echo '<option ' . selected(esc_attr( $setting ), $field ) . ' value="' . $field . '">' . $label . '</option>';


				}

			}

		}

		// Save custom added fields to the DB
		if(in_array('add_fields', wp_fusion()->crm->supports)) {

			// Check to see if new custom fields have been added
			if(!empty($setting) && !isset($crm_fields[$setting])) {

				// Lowercase and remove spaces
				$setting_safe = strtolower( str_replace(' ', '', $setting) );

				echo '<option value="' . $setting_safe . '" selected="selected">' . $setting . '</option>';
				$crm_fields[$setting_safe] = $setting;
				wp_fusion()->settings->set('crm_fields', $crm_fields);

				// Save safe crm field to DB
				$contact_fields = wp_fusion()->settings->get('contact_fields');
				$contact_fields[$field_sub_id]['crm_field'] = $setting_safe;
				wp_fusion()->settings->set('contact_fields', $contact_fields);

			}

		}

	echo '</select>';
}






