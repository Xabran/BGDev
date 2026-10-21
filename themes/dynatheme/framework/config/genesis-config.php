<?php
/**
 * Package Included for DynaTheme
 * 
 * Do not Modify this under any circumtances
 *
 * @package    DynaTheme
 * @subpackage  Genesis Template Config
 * @author      SilverKenn
 * @version     1.1.0
 */
 
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

//Hooks and Filter
function cmk_genesis_setup() {
	#
	# Configuring Genesis Supports and Capability
	#
	add_action( 'widgets_init', 'cmk_extra_widgets', 1);
	
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
	remove_action( 'genesis_header','genesis_do_header' );
	add_action( 'genesis_header', 'cmk_genesis_do_header' );
	add_theme_support( 'genesis-footer-widgets', cmk_footer_widgets_count() );


	add_theme_support( 'genesis-structural-wraps', array('header','nav','site-inner','footer-widgets','footer')); // Adds Structural div for main selector
	
	add_action( 'init', 'cmk_mobile_responsive' );
	add_filter( 'genesis_pre_load_favicon', 'cmk_favicon' ); //Change Favicon 
	
	add_action('init', '_cmk_optimize_wordpress_genesis_defaults', 10);
	add_filter( 'wp_default_scripts', '_cmk_remove_jquery_migrate' );

	add_filter( 'style_loader_src', '_cmk_remove_query_string', 10, 2 );
	add_filter( 'script_loader_src', '_cmk_remove_query_string', 10, 2 );
	#
	# Removing uneeded elements 
	#
	add_filter( 'edit_post_link', '__return_false' ); //remove edit links
	
	#
	# Additional Added Functionality
	#
	add_filter( 'body_class', 'cmk_additional_body_classes' );
	add_filter('widget_text','do_shortcode');
	add_filter('wp_head','filter_title_display'); //Remove Post Title if no-title added in post class
	add_action('wp_head','genesis_nav_position'); //Move Navigation
	add_action('wp_head','test_login_form');
	
	#
	# Replace Default Genesis Footer
	#
	add_action('wp_head','cmk_footer_content');
	#
	# Reposition Footer Widgets
	#
	add_action('wp_head','cmk_footer_widgets_position');
	add_filter('the_content', 'cmk_clean_shortcode');
}

function _cmk_optimize_wordpress_genesis_defaults() {
	if ( !is_admin() ) {
	    remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // remove the next and previous post links
		remove_action( 'wp_head', 'feed_links_extra', 3 ); 
		remove_action( 'wp_head', 'feed_links', 2 ); 
		remove_action( 'wp_head', 'rsd_link' ); 
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); 
		remove_action( 'wp_head', 'wp_generator' ); 
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0); 
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 ); 
		remove_action('wp_head', 'rel_canonical');
		remove_action('wp_head', 'genesis_canonical', 5);
		remove_theme_support( 'automatic-feed-links' );
		wp_deregister_script( 'comment-reply' );
	    wp_deregister_script( 'wp-embed' );
	    remove_action( 'admin_print_styles', 'print_emoji_styles' );
	    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	    remove_action( 'wp_print_styles', 'print_emoji_styles' );
	    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	    add_filter( 'tiny_mce_plugins', 'disable_emojicons_tinymce' );

	    remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
	}
}

function _cmk_remove_jquery_migrate( &$scripts) {
	
    if( !is_admin() ) {
        $scripts->remove( 'jquery');
        $scripts->add( 'jquery', false, array( 'jquery-core' ), '1.12.3' );
    }

}
		
function _cmk_remove_query_string( $src ) {
	$mode = CMK::v('cmk-site-build-mode');
	 if( strpos( $src, '?ver=' ) && $mode === '1')
	 $src = remove_query_arg( 'ver', $src );
	 return $src;
}

function cmk_mobile_responsive() {
	$responsive = CMK::v('cmk-mobile-responsive');
	$min = CMK::v('cmk-minify-script');
	if ( $responsive === '1' && !is_admin() ) {
		add_theme_support( 'genesis-responsive-viewport' ); // Add Viewport Tag
	}
}

function filter_title_display() {
	$classes = get_body_class();
	$title = get_the_title();
	$activeclass = implode(' ', $classes );
	if ( strpos( $activeclass, 'no-title' ) !== false  || CMK::_meta('hide_title') === 'on' ) {
		remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
		remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
        remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_open', 5 );
		remove_action( 'genesis_entry_header', 'genesis_entry_header_markup_close', 15 );
	}
}

function genesis_nav_position() {
	remove_action('genesis_after_header','genesis_do_nav');
	$nav_pos_val = CMK::v('cmk-nav-position');
	if( $nav_pos_val == 'a' ) {
		add_action('genesis_before_header','genesis_do_nav');
	} 
	if ( $nav_pos_val == 'b' ) {
		add_filter( 'body_class', function($classes) {
			$classes[] = 'nav-in-header';
			return $classes;
		});
		add_action('cmk/after/header/widget','genesis_do_nav');
		#add_action('genesis_header_right','genesis_do_nav');
		add_theme_support( 'genesis-structural-wraps', array('header','site-inner','footer-widgets','footer'));
	}
	if ( $nav_pos_val == 'c' ) {
		add_action('genesis_after_header','genesis_do_nav');
	}
	
}

function cmk_footer_content() {
	
	remove_action('genesis_footer','genesis_do_footer'); //Remove Default Genesis Footer
	# Ad CMK Footer
	add_action('genesis_footer', function() {
		echo do_shortcode( trim( CMK::v('cmk-footer-content') ) )."\n";
	});	
}

function cmk_footer_widgets_count() {
	return CMK::v('cmk-footerwidgets-count');
}

function cmk_footer_widgets_position() {
	$fw_position = CMK::v('cmk-footerwidgets-position');
	if( $fw_position === 'after-foot' ) {
		remove_action( 'genesis_before_footer', 'genesis_footer_widget_areas' );
		add_action( 'genesis_after_footer', 'genesis_footer_widget_areas' );
	}
}

function cmk_favicon() {
	return CMK::a('cmk-favico', 'url');
}

function cmk_extra_widgets() {	
	unregister_sidebar( 'sidebar' );
	unregister_sidebar( 'sidebar-alt' );
	genesis_register_sidebar( array(
		'id'            => 'header-middle',
		'name'          => __( 'Header Middle', 'cmk' ),
		'description'   => __( 'This is the Header Middle area', 'cmk' ),
		'before_widget' => '<div class="header-middle-area">',
		'after_widget'  => '</div>',
	));
	genesis_register_sidebar( array(
		'id'            => 'sidebar',
		'name'          => __( 'Primary Sidebar', 'cmk' ),
		'description'   => __( 'This is the primary sidebar if you are using a two or three column site layout option.', 'cmk' ),
	));
	genesis_register_sidebar( array(
		'id'            => 'sidebar-alt',
		'name'          => __( 'Secondary Sidebar', 'cmk' ),
		'description'   => __( 'This is the secondary sidebar if you are using a three column site layout option.', 'cmk' ),
	));
}

function cmk_additional_body_classes( $classes ) {
	if ( is_active_sidebar( 'header-middle' ) ) {
		$classes[]	 = 'header-middle-active';
	}
	//$classes[] = 'cmk-'.Dynatheme::$cmk_version;
	return $classes;
}

function cmk_genesis_do_header() {

	global $wp_registered_sidebars;
	do_action('cmk/before/header/title');
	genesis_markup( array(
		'html5'   => '<div %s>',
		'xhtml'   => '<div id="title-area">',
		'context' => 'title-area',
	));
	
	cmk_headline_image();
	echo '</div>';
	do_action( 'cmk/before/header/widget' );
	genesis_widget_area( 'header-middle', array(
		'before' => '<aside class="header-middle widget-area header-widget-area">',
		'after'  => '</aside>',
	));

	if ( ( isset( $wp_registered_sidebars['header-right'] ) && is_active_sidebar( 'header-right' ) ) || has_action( 'genesis_header_right' ) ) {
		genesis_markup( array(
			'html5'   => '<aside %s>',
			'xhtml'   => '<div class="widget-area header-widget-area">',
			'context' => 'header-widget-area',
		) );
		#do_action( 'cmk/header/widget' );
		do_action( 'genesis_header_right' );
		add_filter( 'wp_nav_menu_args', 'genesis_header_menu_args' );
		add_filter( 'wp_nav_menu', 'genesis_header_menu_wrap' );
		dynamic_sidebar( 'header-right' );
		remove_filter( 'wp_nav_menu_args', 'genesis_header_menu_args' );
		remove_filter( 'wp_nav_menu', 'genesis_header_menu_wrap' );
		
		genesis_markup( array(
			'html5' => '</aside>',
			'xhtml' => '</div>',
		));
	}
	do_action( 'cmk/after/header/widget' );
}

function cmk_headline_image() {
	
	$logo = CMK::a('cmk-header-logo','url');
	$sitename = get_bloginfo('name');
	if( !empty( $logo ) ) {
		$sitelogourl = apply_filters( 'cmk/logo/url', $logo );
		$sitelogohtml = '<a href="'.get_bloginfo('url').'" title="'.$sitename.'"><img src="'.$sitelogourl.'" alt="'.$sitename.'" title="'.$sitename.'" class="site-logo"/></a>';
		echo apply_filters('cmk/logo/html', $sitelogohtml);
	} else {
		$sitelogohtml = '<a href="'.get_bloginfo('url').'"><p class="site-title" itemprop="headline">'.$sitename.'</p><p class="site-description" itemprop="description">'.get_bloginfo('description').'</p></a>';
		echo apply_filters('cmk_logo_html', $sitelogohtml );
	}
}

function cmk_force_fullwidth_page() {
	$forcepagefullwidth = CMK::v('cmk-force-page-fullwidth');
	if ( $forcepagefullwidth == '1' && is_page() ) {
		add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );
	}
}
add_action('wp_head', 'cmk_force_fullwidth_page');

function cmk_clean_shortcode( $content ){   
	$array = array (
		'<p>[' => '[', 
		']</p>' => ']', 
		']<br />' => ']'
	);
	$content = strtr($content, $array);
	return $content;
}

function test_login_form() {
	if( is_page('login') && !is_user_logged_in() ) {
		remove_action( 'genesis_loop', 'genesis_do_loop' );
		if ( isset($_GET['do']) && $_GET['do'] === 'login' ) {
			add_action( 'genesis_loop', function() {
				wp_login_form(); 
			});
		}
		if ( isset($_GET['do']) && $_GET['do'] === 'register' ) { 
			add_action( 'genesis_loop', function() { ?>
				<div style="display:block"> <!-- Registration -->
					<div id="register-form">
						<div class="title">
							<h1>Register your Account</h1>
							<span>Sign Up with us and Enjoy!</span>
						</div>
						<form action="<?php echo site_url('wp-login.php?action=register', 'login_post') ?>" method="post">
							<input type="text" name="user_login" value="Username" id="user_login" class="input" />
							<input type="text" name="user_email" value="E-Mail" id="user_email" class="input"  />
							<?php do_action('register_form'); ?>
							<input type="submit" value="Register" id="register" />
							<p class="statement">A password will be e-mailed to you.</p>
						</form>
					</div>
				</div><!-- /Registration --><?php
			});
		}
	}	
}
	/* Support is_post_type() argument */
if ( !function_exists( 'is_post_type' ) ) {
	function is_post_type($type){
		global $wp_query;
		if( $type == get_post_type($wp_query->post->ID) ) return true;
			return false;
	}
}

# Done Done Done !!
add_action('redux/loaded', 'cmk_genesis_setup', 11);

