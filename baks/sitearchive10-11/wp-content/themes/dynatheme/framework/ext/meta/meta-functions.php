<?php

require_once( dirname(__FILE__). '/fields/select2/cmb-field-select2.php');

function _load_cmb2_lib() {
	if ( ! class_exists( 'CMB2_Bootstrap_209') ) {
		require_once 'init.php';
	}
	if ( ! class_exists( 'Taxonomy_MetaData_CMB2' ) ) {
        require_once( 'Taxonomy_MetaData_CMB2.php' );
    }

}
add_action( 'init', '_load_cmb2_lib', 9999 );

function cmk_script_css_metaboxes() {

	// Start with an underscore to hide fields from custom fields list
	$types = get_post_types( array( 'public' => true ) );
	$prefix = '_cmk_box_';
	
	$cmk_boxes = new_cmb2_box( array(
		'id'			=> '_cmk_metas',
		'title'         => __( 'Dynatheme Option', 'cmk' ),
		'object_types'  => apply_filters('cmk/register/metabox', $types), // Post type
		#'closed'		=> true,
		#'show_names'    => false, 
		'context'      => 'normal',
		'priority'     => 'core',
        'cmb_styles' => true,
	) );

	$cmk_boxes->add_field( array(
		'name' => __( 'Hide Title', 'cmk' ),
		'desc' => __( 'Do not show post title on the front-end', 'cmk' ),
		'id'   => $prefix . 'hide_title',
		'type' => 'checkbox',
		'class' => 'fullbox'
	));

	$cmk_boxes->add_field( array(
	    'name'    => 'Load Script',
	    'id'      => $prefix . 'enque_scripts',
	    'desc'    => 'Load Additional Javascript. Drag to reorder',
	    'type'    => 'pw_multiselect',
	    'options' => CMK::get_cmk_unenqueue_lists(),
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Header Script', 'cmk' ),
		'desc' => __( 'Added on wp_head() action hook. You can add any HTML tags in here like &lt;script&gt;&lt;/script&gt;, &lt;style&gt;&lt;/style&gt; etc.', 'cmk' ),
		'id'   => $prefix . 'wp_head',
		'default' => get_post_meta(get_the_ID(), 'CmkBoxes_headscript', false),
		'type' => 'textarea_code',
		'class' => 'fullbox'
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Footer Script', 'cmk' ),
		'desc' => __( 'Added on wp_footer() action hook. You can add any HTML tags in here like &lt;script&gt;&lt;/script&gt;, &lt;style&gt;&lt;/style&gt; etc.', 'cmk' ),
		'id'   => $prefix . 'wp_footer',
		'type' => 'textarea_code',
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Display Scripts', 'cmk' ),
		'desc' => __( 'Display List of enqueue scripts in the front end separated by "|"', 'cmk' ),
		'id'   => $prefix . 'showenqueued_script',
		'type' => 'checkbox',
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Display Styles', 'cmk' ),
		'desc' => __( 'Display List of enqueue styles in the front end separated by "|"', 'cmk' ),
		'id'   => $prefix . 'showenqueued_styles',
		'type' => 'checkbox',
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Remove Scripts', 'cmk' ),
		'after' => __( 'Add the script handle to remove scripts on this page (separate by pipe "|")', 'cmk' ),
		'id'   => $prefix . 'remove_scripts',
		'type' => 'text',
	));
	$cmk_boxes->add_field( array(
		'name' => __( 'Remove Styles', 'cmk' ),
		'after' => __( 'Add the style handle to remove styles on this page (separate by pipe "|")', 'cmk' ),
		'id'   => $prefix . 'remove_styles',
		'type' => 'text',
	));
}
add_action( 'cmb2_admin_init', 'cmk_script_css_metaboxes', 9 );



