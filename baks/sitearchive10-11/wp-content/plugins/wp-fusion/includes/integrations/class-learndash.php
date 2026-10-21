<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'SFWD_LMS' )) {

	class WPF_LearnDash extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @since   1.0
		 * @return  void
		 */

		public function init() {

			add_action( 'learndash_course_completed', array( $this, 'apply_tags_learndash_complete') );
			add_action( 'learndash_topic_completed',  array( $this, 'apply_tags_learndash_complete') );
			add_action( 'learndash_lesson_completed', array( $this, 'apply_tags_learndash_complete') );
			add_action( 'learndash_quiz_completed', array( $this, 'apply_tags_learndash_complete') );

			add_action( 'add_meta_boxes', 		array( $this, 'configure_meta_box' ));
			add_action( 'wpf_meta_box_content', array( $this, 'meta_box_content' ), 40, 2 );

			add_action( 'wpf_meta_box_save', 	array( $this, 'meta_box_save' ), 20, 2 );
			add_action( 'add_meta_boxes', 		array( $this, 'add_meta_box' ), 20, 2 );
			add_action( 'save_post', 			array( $this, 'save_meta_box_data' ), 20, 2 );
			add_action( 'wpf_tags_modified', 	array( $this, 'update_course_access' ), 10, 2);
			
		}


		/**
		 * Applies tags when a LearnDash course/lesson/topic is completed
		 *
		 * @access public
		 * @return void
		 */

		public function apply_tags_learndash_complete( $data ) {

			$wpf_settings = array();

			if(isset($data['topic'])) {
				$wpf_settings = get_post_meta($data['topic']->ID, 'wpf-settings', true);
			} elseif(isset($data['lesson'])) {
				$wpf_settings = get_post_meta($data['lesson']->ID, 'wpf-settings', true);
			} elseif(isset($data['course']) && !isset($data['quiz'])) {
				$wpf_settings = get_post_meta($data['course']->ID, 'wpf-settings', true);
			} if(isset($data['quiz']) && $data['pass'] == true) {
				$wpf_settings = get_post_meta($data['quiz']->ID, 'wpf-settings', true);
			}

			if(isset($wpf_settings['apply_tags_ld']))
				wp_fusion()->user->apply_tags($wpf_settings['apply_tags_ld'], $data['user']->ID);

		}

		/**
		 * Remove standard "Apply to children" field from meta box
		 *
		 * @access public
		 * @return void
		 */

		public function configure_meta_box() {

			global $post;

			if($post->post_type == 'sfwd-courses' || $post->post_type == 'sfwd-lessons' || $post->post_type == 'sfwd-topic')
				remove_action( 'wpf_meta_box_content', 'apply_to_children', 35 );

		}


		/**
		 * Adds learndash fields to WPF meta box
		 *
		 * @access public
		 * @return void
		 */

		public function meta_box_content( $post, $settings ) {

			if($post->post_type != 'sfwd-courses' && $post->post_type != 'sfwd-lessons' && $post->post_type != 'sfwd-topic' && $post->post_type != 'sfwd-quiz')
				return;

			echo '<p><label for="wpf-apply-tags-ld"><small>Apply these tags when marked complete:</small></label>';

			wpf_render_tag_multiselect( $settings['apply_tags_ld'], 'wpf-settings', 'apply_tags_ld' );
						

			if($post->post_type == 'sfwd-courses') {

				$children = get_posts(array('posts_per_page' => -1, 'post_type' => array('sfwd-lessons', 'sfwd-topic'), 'meta_key' => 'course_id', 'meta_value' => $post->ID));

				if(count($children) > 0)
					echo '<p><input class="checkbox" type="checkbox" id="wpf-apply-children-courses" name="wpf-settings[apply_children_courses]" value="1" '.checked($settings['apply_children_courses'], 1, FALSE).' /> <small>Apply to ' . count($children) . ' related lessons and topics</small></p>';	

			} elseif ($post->post_type == 'sfwd-lessons') {

				$children = get_posts(array('posts_per_page' => -1, 'post_type' => array('sfwd-topic'), 'meta_key' => 'lesson_id', 'meta_value' => $post->ID));

				if(count($children) > 0)
					echo '<p><input class="checkbox" type="checkbox" id="wpf-apply-children-lessons" name="wpf-settings[apply_children_lessons]" value="1" '.checked($settings['apply_children_lessons'], 1, FALSE).' /> <small>Apply to ' . count($children) . ' related topics</small></p>';	

			}


		}

		/**
		 * Runs when WPF meta box is saved
		 *
		 * @access public
		 * @return void
		 */

		public function meta_box_save( $post_id, $data ) {

			if(isset($data['apply_children_courses'])) {

				$children = get_posts(array('posts_per_page' => -1, 'post_type' => array('sfwd-lessons', 'sfwd-topic'), 'meta_key' => 'course_id', 'meta_value' => $post_id));

			} elseif (isset($data['apply_children_lessons'])) {

				$children = get_posts(array('posts_per_page' => -1, 'post_type' => array('sfwd-topic'), 'meta_key' => 'lesson_id', 'meta_value' => $post_id));

			}

			if(isset($children)) {

				foreach($children as $child) {
					update_post_meta( $child->ID, 'wpf-settings', $data );
				}

			}

		}
		
		/**
		 * Adds meta box
		 *
		 * @access public
		 * @return mixed
		 */

		public function add_meta_box( $post_id, $data ) {
			
			add_meta_box('wpf-learndash-meta', 'WP Fusion - Course Settings', array($this, 'meta_box_callback'), 'sfwd-courses');
			add_meta_box('wpf-learndash-meta', 'WP Fusion - Group Settings', array($this, 'meta_box_callback_groups'), 'groups');
			
		}

		/**
		 * Displays meta box content
		 *
		 * @access public
		 * @return mixed
		 */
		
		public function meta_box_callback( $post ) {
			
			wp_nonce_field( 'wpf_meta_box_learndash', 'wpf_meta_box_learndash_nonce' );
			
			$settings = array(
					'link_tag'	=> array()
			);
			
			if(get_post_meta( $post->ID, 'wpf-settings-learndash', true ))
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-learndash', true ));
			
			echo '<table class="form-table"><tbody>';
		
			echo '<tr>';
		
			echo '<th scope="row"><label for="tag_link">Link with tag:</label></th>';
			echo '<td>';

			wpf_render_tag_multiselect($settings['tag_link'], 'wpf-settings-learndash', 'tag_link', null, false, 'Select Tag', 1 );

			echo '<span class="description">Select a tag to link with this course. When the tag is applied, the user will automatically be enrolled.</span>';
			echo '</td>';
		
			echo '</tr>';
		
			echo '</tbody></table>';
			
			
		}

		/**
		 * Displays meta box content (groups)
		 *
		 * @access public
		 * @return mixed
		 */
		
		public function meta_box_callback_groups( $post ) {
			
			wp_nonce_field( 'wpf_meta_box_learndash', 'wpf_meta_box_learndash_nonce' );
			
			$settings = array(
					'link_tag'	=> array()
			);
			
			if(get_post_meta( $post->ID, 'wpf-settings-learndash', true ))
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-learndash', true ));
			
			echo '<table class="form-table"><tbody>';
		
			echo '<tr>';
		
			echo '<th scope="row"><label for="tag_link">Link with tag:</label></th>';
			echo '<td>';

			wpf_render_tag_multiselect($settings['tag_link'], 'wpf-settings-learndash', 'tag_link', null, false, 'Select Tag', 1 );

			echo '<span class="description">Select a tag to link with this group.</span>';
			echo '</td>';
		
			echo '</tr>';
		
			echo '</tbody></table>';
			
			
		}
		
		/**
		 * Runs when WPF meta box is saved
		 *
		 * @access public
		 * @return void
		 */		
		
		public function save_meta_box_data ( $post_id ){
			
			/*
			 * We need to verify this came from our screen and with proper authorization,
			 * because the save_post action can be triggered at other times.
			 */
			
			// Check if our nonce is set.
			if ( ! isset( $_POST['wpf_meta_box_learndash_nonce'] ) )
				return;
			
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['wpf_meta_box_learndash_nonce'], 'wpf_meta_box_learndash' ) )
				return;
				
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
				
			// Don't update on revisions
			if($_POST['post_type'] == 'revision')
				return;
								
				
			if(isset($_POST['wpf-settings-learndash'])) {
				$data = $_POST['wpf-settings-learndash'];
			} else {
				$data = array();
			}
				
			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-learndash', $data );
			
		}
			
			
		/**
		 * Update user course enrollment when tags are modified
		 *
		 * @access public
		 * @return void
		 */
			
		public function update_course_access($user_id, $user_tags) {

			if($user_tags == false)
				return;

			$linked_courses = get_posts( array(
				'post_type'	=> 'sfwd-courses',
				'nopaging'	=> true,
				'meta_query' => array(
						array(
						'key' => 'wpf-settings-learndash',
						'compare' => 'EXISTS'
						),
					),
				'fields'	=> 'ids'
				) );

			// Update course access based on user tags

			foreach( $linked_courses as $course_id ) {

				$settings = get_post_meta( $course_id, 'wpf-settings-learndash', true );

				if(empty($settings) || empty($settings['tag_link']))
					continue;

				$tag_id = $settings['tag_link'][0];

				if(in_array($tag_id, $user_tags) && !sfwd_lms_has_access($course_id, $user_id)) {

					ld_update_course_access($user_id, $course_id, $remove = false);

				} else if(!in_array($tag_id, $user_tags) && sfwd_lms_has_access($course_id, $user_id)){

					ld_update_course_access($user_id, $course_id, $remove = true);

				}

			}


			// Possibly update groups

			$linked_groups = get_posts( array(
				'post_type'	=> 'groups',
				'nopaging'	=> true,
				'meta_query' => array(
						array(
						'key' => 'wpf-settings-learndash',
						'compare' => 'EXISTS'
						),
					),
				'fields'	=> 'ids'
				) );

			if(empty($linked_groups))
				return;

			foreach( $linked_groups as $group_id ) {

				$settings = get_post_meta( $group_id, 'wpf-settings-learndash', true );

				if(empty($settings) || empty($settings['tag_link']))
					continue;

				$tag_id = $settings['tag_link'][0];

				if(in_array($tag_id, $user_tags) && learndash_is_user_in_group($user_id, $group_id) == false) {

					ld_update_group_access($user_id, $group_id, $remove = false);

				} elseif(!in_array($tag_id, $user_tags) && learndash_is_user_in_group($user_id, $group_id) != false){

					ld_update_group_access($user_id, $group_id, $remove = true);

				}

			}

		}
			
	}

	new WPF_LearnDash;

}