<?php

function webtreats_one_third( $atts, $content = null ) {
   return '<div class="one-third">' . do_shortcode($content) . '</div>';
}
add_shortcode('one_third', 'webtreats_one_third');

function webtreats_one_third_last( $atts, $content = null ) {
   return '<div class="one-third last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('one_third_last', 'webtreats_one_third_last');

function webtreats_two_third( $atts, $content = null ) {
   return '<div class="two-thirds">' . do_shortcode($content) . '</div>';
}
add_shortcode('two_third', 'webtreats_two_third');

function webtreats_two_third_last( $atts, $content = null ) {
   return '<div class="two-thirds last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('two_third_last', 'webtreats_two_third_last');

function webtreats_one_half( $atts, $content = null ) {
   return '<div class="one-half">' . do_shortcode($content) . '</div>';
}
add_shortcode('one_half', 'webtreats_one_half');

function webtreats_one_half_last( $atts, $content = null ) {
   return '<div class="one-half last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('one_half_last', 'webtreats_one_half_last');

function webtreats_one_fourth( $atts, $content = null ) {
   return '<div class="one-fourth">' . do_shortcode($content) . '</div>';
}
add_shortcode('one_fourth', 'webtreats_one_fourth');

function webtreats_one_fourth_last( $atts, $content = null ) {
   return '<div class="one-fourth last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('one_fourth_last', 'webtreats_one_fourth_last');

function webtreats_three_fourth( $atts, $content = null ) {
   return '<div class="three-fourths">' . do_shortcode($content) . '</div>';
}
add_shortcode('three_fourth', 'webtreats_three_fourth');

function webtreats_three_fourth_last( $atts, $content = null ) {
   return '<div class="three-fourths last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('three_fourth_last', 'webtreats_three_fourth_last');

function webtreats_one_fifth( $atts, $content = null ) {
   return '<div class="one-fifth">' . do_shortcode($content) . '</div>';
}
add_shortcode('one_fifth', 'webtreats_one_fifth');

function webtreats_one_fifth_last( $atts, $content = null ) {
   return '<div class="one-fifth last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('one_fifth_last', 'webtreats_one_fifth_last');

function webtreats_two_fifth( $atts, $content = null ) {
   return '<div class="two-fifths">' . do_shortcode($content) . '</div>';
}
add_shortcode('two_fifth', 'webtreats_two_fifth');

function webtreats_two_fifth_last( $atts, $content = null ) {
   return '<div class="two-fifths last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('two_fifth_last', 'webtreats_two_fifth_last');

function webtreats_three_fifth( $atts, $content = null ) {
   return '<div class="three-fifths">' . do_shortcode($content) . '</div>';
}
add_shortcode('three_fifth', 'webtreats_three_fifth');

function webtreats_three_fifth_last( $atts, $content = null ) {
   return '<div class="three-fifths last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('three_fifth_last', 'webtreats_three_fifth_last');

function webtreats_four_fifth( $atts, $content = null ) {
   return '<div class="four-fifths">' . do_shortcode($content) . '</div>';
}
add_shortcode('four_fifth', 'webtreats_four_fifth');

function webtreats_four_fifth_last( $atts, $content = null ) {
   return '<div class="four-fifths last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('four_fifth_last', 'webtreats_four_fifth_last');

function webtreats_one_sixth( $atts, $content = null ) {
   return '<div class="one-sixth">' . do_shortcode($content) . '</div>';
}
add_shortcode('one_sixth', 'webtreats_one_sixth');

function webtreats_one_sixth_last( $atts, $content = null ) {
   return '<div class="one-sixth last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('one_sixth_last', 'webtreats_one_sixth_last');

function webtreats_five_sixth( $atts, $content = null ) {
   return '<div class="five-sixths">' . do_shortcode($content) . '</div>';
}
add_shortcode('five_sixth', 'webtreats_five_sixth');

function webtreats_five_sixth_last( $atts, $content = null ) {
   return '<div class="five-sixths last">' . do_shortcode($content) . '</div><div class="clearboth"></div>';
}
add_shortcode('five_sixth_last', 'webtreats_five_sixth_last');

function cmkdivision( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'cl' => '',
		'id'=> '',
		'is' => '',
		'animate' => '',
		'del' => '',
	), $atts ) );

	$acl = ( $cl ) ? ' class="'. esc_attr($cl) .'"' : '';
	$aid = ( $id ) ? ' id="'. esc_attr($id) .'"' : '';
	$ais = ( $is ) ? ' style="'. esc_attr($is) .'"' : '';
	$animated = ( $animate ) ? ' data-animate="'. esc_attr($animate) .'"' : '';
	$delay = ( $del ) ? ' data-delay="'. esc_attr($del) .'"' : '';

	return '<div'.$aid.$acl.$ais.$animated.$delay.'>' .  do_shortcode($content) . '</div>';
}
add_shortcode( 'cm', 'cmkdivision' );

function cmids( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'cl' => '',
		'id'=> '',
		'is' => '',
		'animate' => '',
		'del' => '',
	), $atts ) );
	$acl = ( $cl ) ? ' class="'. esc_attr($cl) .'"' : '';
	$aid = ( $id ) ? ' id="'. esc_attr($id) .'"' : '';
	$ais = ( $is ) ? ' style="'. esc_attr($is) .'"' : '';
	$animated = ( $animate ) ? ' data-animate="'. esc_attr($animate) .'"' : '';
	$delay = ( $del ) ? ' data-delay="'. esc_attr($del) .'"' : '';

	return '<div'.$aid.$acl.$ais.$animated.$delay.'>' .  do_shortcode($content) . '</div>';
}
add_shortcode( 'cmid', 'cmids' );

function cmdiv( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'cl' => '',
		'id'=> '',
		'is' => '',
		'animate' => '',
		'del' => '',
	), $atts ) );

	$acl = ( $cl ) ? ' class="'. esc_attr($cl) .'"' : '';
	$aid = ( $id ) ? ' id="'. esc_attr($id) .'"' : '';
	$ais = ( $is ) ? ' style="'. esc_attr($is) .'"' : '';
	$animated = ( $animate ) ? ' data-animate="'. esc_attr($animate) .'"' : '';
	$delay = ( $del ) ? ' data-delay="'. esc_attr($del) .'"' : '';

	return '<div'. $aid.$acl.$ais.$animated.$delay.'>' .  do_shortcode($content) . '</div>';
}
add_shortcode( 'cmdiv', 'cmdiv' );
function cmdivs( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'cl' => '',
		'id'=> '',
		'is' => '',
		'animate' => '',
		'del' => '',
	), $atts ) );

	$acl = ( $cl ) ? ' class="'. esc_attr($cl) .'"' : '';
	$aid = ( $id ) ? ' id="'. esc_attr($id) .'"' : '';
	$ais = ( $is ) ? ' style="'. esc_attr($is) .'"' : '';
	$animated = ( $animate ) ? ' data-animate="'. esc_attr($animate) .'" ' : '';
	$delay = ( $del ) ? ' data-delay="'. esc_attr($del) .'"' : '';

	return '<div'.$aid.$acl.$ais.$animated.$delay.'>' .  do_shortcode($content) . '</div>';
}
add_shortcode( 'cm-div', 'cmdivs' );

function cmkwrapper( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'cl' => '',
		'id'=> '',
		'is' => '',
		'data0' => '',
		'datacenter' => '',
		'datatopbottom' => '',
		'animate' => '',
		'del' => '',
		'wrapper' => 'wrap',
	), $atts ) );

	$adata0 = ( $data0 ) ? 'data-0="'.esc_attr($data0).'"' : '';
	$adatacenter = ( $datacenter ) ? 'data-center="'.esc_attr($datacenter).'"' : '';
	$adatatopbottom = ( $datatopbottom ) ? 'data-top-bottom="'.esc_attr($datatopbottom).'"' : '';
	$acl = ( $cl ) ? ' class="'. esc_attr($cl) .'"' : '';
	$aid = ( $id ) ? ' id="'. esc_attr($id) .'"' : '';
	$ais = ( $is ) ? ' style="'. esc_attr($is) .'"' : '';
	$animated = ( $animate ) ? ' data-animate="'. esc_attr($animate) .'" ' : '';
	$delay = ( $del ) ? ' data-delay="'. esc_attr($del) .'"' : '';
	
	return '<div'. $aid .$acl.$adata0.$adatacenter.$adatatopbottom.$ais.$animated.$delay.'><div class="'.esc_attr($wrapper).'">' .  do_shortcode($content) . '</div></div>';
}
add_shortcode( 'cmwrap', 'cmkwrapper' );

function webtreats_formatter($content) {
	$new_content = '';
	$pattern_full = '{(\[raw\].*?\[/raw\])}is';
	$pattern_contents = '{\[raw\](.*?)\[/raw\]}is';
	$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($pieces as $piece) {
		if (preg_match($pattern_contents, $piece, $matches)) {
			$new_content .= $matches[1];
		} else {
			$new_content .= wptexturize(wpautop($piece));
		}
	}

	return $new_content;
}

function _overwrite_formatter() {
	global $post;
    #if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'raw')  ) {
	if ( $post && strpos( $post->post_content, '[raw]') !== false ) {
        // Remove the 2 main auto-formatters
		remove_filter('the_content', 'wpautop');
		remove_filter('the_content', 'wptexturize');

		// Before displaying for viewing, apply this function
		add_filter('the_content', 'webtreats_formatter', 99);
		add_filter('widget_text', 'webtreats_formatter', 99);

		//Long posts should require a higher limit, see http://core.trac.wordpress.org/ticket/8553
		@ini_set('pcre.backtrack_limit', 500000);
    }
}
add_action('wp_head', '_overwrite_formatter');

add_shortcode('cmk_menu', '_cmk_nav_menu');
function _cmk_nav_menu( $atts ) {
	$a = shortcode_atts( array(
        'theme_location'  => '',
	    'menu'            => '',
	    'container'       => 'div',
	    'container_class' => 'menu-container',
	    'container_id'    => '',
	    'menu_class'      => 'menu',
	    'menu_id'         => '',
	    'echo'            => false,
	    'fallback_cb'     => 'wp_page_menu',
	    'before'          => '',
	    'after'           => '',
	    'link_before'     => '',
	    'link_after'      => '',
	    'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
	    'depth'           => 0,
	    'walker'          => '',
    ), $atts);
    return wp_nav_menu( $a );
}


