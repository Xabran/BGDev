<?php 

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WPF_Meta_Boxes {

	public function __construct() {

		add_action( 'add_meta_boxes', array($this, 'add_meta_box' ), 20);
		add_action( 'admin_enqueue_scripts', array($this, 'metabox_scripts'));
		add_action( 'save_post', array($this, 'save_meta_box_data' ));

		// Meta box content
		add_action( 'wpf_meta_box_content', array( $this, 'restrict_content_checkbox' ), 10, 2 );
		add_action( 'wpf_meta_box_content', array( $this, 'required_tags_select' ), 15, 2 );
		add_action( 'wpf_meta_box_content', array( $this, 'page_redirect_select' ), 20, 2 );
		add_action( 'wpf_meta_box_content', array( $this, 'external_redirect_input' ), 25, 2 );
		add_action( 'wpf_meta_box_content', array( $this, 'apply_tags_select' ), 30, 2 );
		add_action( 'wpf_meta_box_content', array( $this, 'apply_to_children' ), 40, 2 );


		add_action( 'wpf_meta_box_save', array( $this, 'save_changes_to_children' ), 10, 2 );

	}


	/**
	 * Enqueue meta box scripts
	 *
	 * @access public
	 * @return void
	 */

	public function metabox_scripts() {

		wp_enqueue_style( 'select4', WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.css', array(), '4.0.1');
		wp_enqueue_script( 'select4', WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.js', array('jquery'), '4.0.1');

		wp_enqueue_style( 'wpf-admin', WPF_DIR_URL . 'assets/css/wpf-admin.css', array(), WP_FUSION_VERSION );

		wp_enqueue_script( 'wpf-admin', WPF_DIR_URL . 'assets/js/wpf-admin.js', array('jquery', 'select4'), WP_FUSION_VERSION, true);
		wp_localize_script( 'wpf-admin', 'wpf', array( 'crm_supports' => wp_fusion()->crm->supports ) );

	}


	/**
	 * Adds meta boxes to the configured post types
	 *
	 * @access public
	 * @return void
	 */


	public function add_meta_box() {

		$post_types = get_post_types();

		unset($post_types['attachment']);
		unset($post_types['revision']);

		$post_types = apply_filters( 'wpf_meta_box_post_types', $post_types );

		foreach ( $post_types as $post_type ) {
			add_meta_box('wpf-meta','WP Fusion', array($this, 'meta_box_callback'), $post_type, 'side', 'core');
		}

	}


	/**
	 * Shows restrict content checkbox
	 *
	 * @access public
	 * @return void
	 */

	public function restrict_content_checkbox( $post, $settings ) {

		$post_type_object = get_post_type_object( $post->post_type );

		echo '<p><input class="checkbox" type="checkbox" data-unlock="allow_tags wpf-redirect wpf-redirect-url" id="wpf-lock-content" name="wpf-settings[lock_content]" value="1" '.checked($settings['lock_content'], 1, FALSE).' /> <strong>Restrict access to this ' . strtolower($post_type_object->labels->singular_name) . '</strong></p>';

	}

	/**
	 * Shows required tags input
	 *
	 * @access public
	 * @return void
	 */

	public function required_tags_select( $post, $settings ) {

		echo '<p><label' .($settings['lock_content'] != true ? "" : ' class="disabled"') .' for="wpf-allow-tags"><small>Required tags (any):</small></label>';

		if($settings['lock_content'] != true) {
			$disabled = true;
		} else {
			$disabled = false;
		}

		wpf_render_tag_multiselect( $settings['allow_tags'], 'wpf-settings', 'allow_tags', null, $disabled );

		echo '</p>';

	}


	/**
	 * Shows page redirect select
	 *
	 * @access public
	 * @return void
	 */

	public function page_redirect_select( $post, $settings ) {

		$post_types = get_post_types(array('public' => true));
		$available_posts = array();

		unset($post_types['attachment']);
		$post_types = apply_filters( 'wpf_redirect_post_types', $post_types );

		foreach ($post_types as $post_type) {

			$posts = get_posts(array('post_type' => $post_type, 'posts_per_page' => 400, 'orderby' => 'post_title', 'order' => 'ASC', 'post__not_in' => array( $post->ID )));

			foreach($posts as $post) {
				$available_posts[$post_type][$post->post_title] = $post->ID;
			}

		}

		echo '<p><label' .($settings['lock_content'] == 1 ? "" : ' class="disabled"') .' for="wpf-redirect"><small>Redirect if access is denied:</small></label><br />';

		// Compatibility fix for earlier versions where we stored the URL
		if(!empty($settings['redirect']) && !is_numeric($settings['redirect']))
			$settings['redirect'] = url_to_postid( $settings['redirect'] );

		echo '<select' . ($settings['lock_content'] == 1 ? "" : ' disabled') . ' id="wpf-redirect" class="select4-search" style="width: 100%;" data-placeholder="None" name="wpf-settings[redirect]">';

		echo '<option></option>';

		foreach($available_posts as $post_type => $data) {

			echo '<optgroup label="' . $post_type . '">';

			foreach($available_posts[$post_type] as $post_name => $id) {
				echo '<option value="' . $id . '"' . selected($id, $settings['redirect'], FALSE) . '>' . $post_name . '</option>';
			}

			echo '</optgroup>';
		}

		echo '</select></p>';


	}


	/**
	 * Shows external redirect text input
	 *
	 * @access public
	 * @return void
	 */

	public function external_redirect_input( $post, $settings ) {

		echo '<p><label' .($settings['lock_content'] == 1 ? "" : ' class="disabled"') .' for="wpf-redirect-url"><small>Or enter a URL below:</small></label>';
		echo '<input' .($settings['lock_content'] == 1 ? "" : ' disabled') .' type="text" id="wpf-redirect-url" name="wpf-settings[redirect_url]" value="' . esc_attr( $settings['redirect_url'] ) . '" />';
		echo '</p>';

	}


	/**
	 * Shows select field with tags to apply on page load, with delay
	 *
	 * @access public
	 * @return void
	 */

	public function apply_tags_select( $post, $settings ) {

		$post_type_object = get_post_type_object( $post->post_type );

		echo '<p><label for="wpf-apply-tags"><small>Apply tags when a user views this ' . strtolower($post_type_object->labels->singular_name) . ':</small></label>';
		wpf_render_tag_multiselect( $settings['apply_tags'], 'wpf-settings', 'apply_tags' );
		echo '</p>';

		/*
		// Delay before applying tags
		*/

		echo '<p><label for="wpf-apply-delay"><small>Delay (in ms) before applying tags:</small></label>';
		echo '<input type="text" id="wpf-apply-delay" name="wpf-settings[apply_delay]" value="' . esc_attr( $settings['apply_delay'] ) . '" size="15" />';
		echo '</p>';


	}


	/**
	 * Shows apply settings to children textbox
	 *
	 * @access public
	 * @return void
	 */

	public function apply_to_children( $post, $settings ) {

		$children = get_children( array('post_parent' => $post->ID, 'post_type' => $post->post_type));

		if(count($children) > 0)
			echo '<p><input class="checkbox" type="checkbox" id="wpf-apply-children" name="wpf-settings[apply_children]" value="1" '.checked($settings['apply_children'], 1, FALSE).' /> Apply these settings to ' . count($children) . ' children</p>';	

	}


	/**
	 * Shows apply settings to children textbox
	 *
	 * @access public
	 * @return void
	 */

	public function save_changes_to_children( $post_id, $data ) {

		// Apply settings to children if required
		if(isset($data['apply_children'])) {

			$children = get_children( array('post_parent' => $post_id, 'post_type' => $_POST['post_type']));

			foreach($children as $child) {
				update_post_meta( $child->ID, 'wpf-settings', $data );
			}
		}

	}

	/**
	 * Renders WPF meta box
	 *
	 * @access public
	 * @return void
	 */

	public function meta_box_callback( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box', 'wpf_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */

		$settings = array(
			'lock_content'	=> 0,
			'allow_tags'	=> array(),
			'apply_tags'	=> array(),
			'apply_delay'	=> 0,
			'redirect'		=> '',
			'redirect_url'	=> ''
		);

		if(get_post_meta( $post->ID, 'wpf-settings', true )) 
			$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings', true ));

		// Outputs the different input fields for the WPF meta box
		do_action( 'wpf_meta_box_content', $post, $settings );


	}

	function save_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_nonce'] ) )
			return;

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_nonce'], 'wpf_meta_box' ) )
			return;

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;


		// Don't update on revisions
		if($_POST['post_type'] == 'revision')
			return;
		

		if(isset($_POST['wpf-settings'])) {

			$data = $_POST['wpf-settings'];

			// Sanitize
			if(isset($data['apply_delay']))
				$data['apply_delay'] = sanitize_text_field( $data['apply_delay'] );

			if(isset($data['redirect_url']))
				$data['redirect_url'] = sanitize_text_field( $data['redirect_url'] );

		} else {
			$data = array();
		}

		if(!isset($data['lock_content']))
			$data['lock_content'] = 0;

		// Allow other plugins to save their own data
		do_action( 'wpf_meta_box_save', $post_id, $data );

		// Update the meta field in the database.
		update_post_meta( $post_id, 'wpf-settings', $data );
	}

}