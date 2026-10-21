<?php
//ADD THUMBNAIL TO BRAND AND BOTTOM TEXT TO PUBLISHER,ARTIST,DESIGNER
function _brgm_brand_image_meta() {
	$p = '_brgm_';

	$cmb_term = new_cmb2_box( array(
		'id'               => $p . 'brand',
		'title'            => __( 'Brand Image', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'pa_publisher'),
	));
	$cmb_term->add_field( array(
	    'name' => 'Image',
	    'id'   => $p. 'image',
	    'type' => 'file',
	    'options' => array(
	        'url' => false, 
	    ),
	));
        
        //Now the 'bottom text' col1
        $cmb_term_btext = new_cmb2_box( array(
		'id'               => $p . 'bottom_txt_attr',
		'title'            => __( 'Bottom Text Column1', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'pa_publisher', 'pa_artist', 'pa_designer'),
	));
	$cmb_term_btext->add_field( array(
	    'name' => 'Bottom Text Column 1',
	    'id'   => $p. 'bottom_txt_attr',
	    'type' => 'wysiwyg',
	));
        //column 2
        $cmb_term_btext2 = new_cmb2_box( array(
		'id'               => $p . 'bottom_txt_attr2',
		'title'            => __( 'Bottom Text Column 2', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'pa_publisher', 'pa_artist', 'pa_designer'),
	));
	$cmb_term_btext2->add_field( array(
	    'name' => 'Bottom Text Column2',
	    'id'   => $p. 'bottom_txt_attr2',
	    'type' => 'wysiwyg',
	));
}
add_action( 'cmb2_admin_init', '_brgm_brand_image_meta' );

//ADD TAGLINE & BOTTOM TEXT FIELDS TO PRODUCT CATEGORIES (for page display)
function _brgm_category_tagline_meta() {
	$p = '_brgm_';

        //First the tagline
	$cmb_term_tagline = new_cmb2_box( array(
		'id'               => $p . 'tagline',
		'title'            => __( 'Tagline', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'product_cat'),
	));
	$cmb_term_tagline->add_field( array(
	    'name' => 'Tagline',
	    'id'   => $p. 'tagline',
	    'type' => 'text',
	    'options' => array(
	        'url' => false, 
	    ),
	));
        //Now the 'bottom text' column 1
        $cmb_term_btext = new_cmb2_box( array(
		'id'               => $p . 'bottom_txt_cat',
		'title'            => __( 'Bottom Text Column 1', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'product_cat'),
	));
	$cmb_term_btext->add_field( array(
	    'name' => 'Bottom Text Column 1',
	    'id'   => $p. 'bottom_txt_cat',
	    'type' => 'wysiwyg',
	));
        //Bottom text column 2
        $cmb_term_btext2 = new_cmb2_box( array(
        'id'               => $p . 'bottom_txt_cat2',
        'title'            => __( 'Bottom Text Column 2', 'cmb2' ),
        'object_types'     => array( 'term' ),
        'taxonomies'       => array( 'product_cat'),
	));
	$cmb_term_btext2->add_field( array(
	    'name' => 'Bottom Text Column 2',
	    'id'   => $p. 'bottom_txt_cat2',
	    'type' => 'wysiwyg',
	));
        
        //Now the headline, used for longer titles on top of archive pages
        $cmb_term_headline = new_cmb2_box( array(
		'id'               => $p . 'cat_headline',
		'title'            => __( 'Headline', 'cmb2' ),
		'object_types'     => array( 'term' ),
		'taxonomies'       => array( 'product_cat'),
	));
	$cmb_term_headline->add_field( array(
	    'name' => 'Headline',
	    'id'   => $p. 'cat_headline',
	    'type' => 'text',
	));
}
add_action( 'cmb2_admin_init', '_brgm_category_tagline_meta' );

function _brgm_account_page_left() {
	$user = wp_get_current_user();
	echo '<div class="one-fifth">';
	do_action('brgm/account/nav');
	echo '</div>';
	echo '<div class="four-fifths last user-data">';	
	echo '<div id="my-account" class="section account-tab">';
	echo '<h3 class="heading">'.$user->display_name.'\'s BoardGames.com Account:</h3>';
	do_action('brgm/account/data');
	echo '<div class="two-thirds">';
}

function _brgm_account_page_right() {
	global $br_wclist;
	$user = wp_get_current_user();
	echo '</div>'; // two-thirds close
	echo '<div class="one-third last" style="text-align:left;">';
	echo $br_wclist->get_list('want_it');
	echo '</div>';
	echo '</div>'; // #my-account close
 	show_myorders_section();
	echo $br_wclist->get_list('want_it', 'games-i-want', 'section account-tab', 'full');
	echo $br_wclist->get_list('love_it', 'games-i-love', 'section account-tab', 'full');
	echo $br_wclist->get_list('have_it', 'games-i-own', 'section account-tab', 'full');
	//Only showing these is bgpr plugin active
        echo show_bgbucks_section();
	echo show_bgpoints_section();
        //echo show_subscriptions_tab();
	echo show_myproduct_reviews();
        //echo show_facebook_tab();
        //echo show_events_tab();
        
	echo '<div id="edit-account" class="section account-tab">';
        echo '<h3>EDIT ACCOUNT DETAILS</h3>';
	wc_get_template( 'myaccount/form-edit-account.php', array( 
		'user' => get_user_by( 'id', get_current_user_id() ) ) 
	);
	echo '</div>';
	echo '<div id="addresses" class="section account-tab">';
        echo '<h3>MY ADDRESSES</h3>';
	wc_get_template( 'myaccount/my-address.php', array( 
		'user' => get_user_by( 'id', get_current_user_id() ) ) 
	);
	echo '</div>';
	echo '<div id="payment-method" class="section account-tab">';
        echo '<h3>MY SAVED PAYMENT METHODS</h3>';
	wc_get_template( 'myaccount/payment-methods.php', array( 
		'user' => get_user_by( 'id', get_current_user_id() ) ) 
	);
	echo '</div>';
	do_action('brgm/accouont/section');
	echo '</div>'; // three-fourths close
}

function show_myorders_section()
{
	echo '<div id="my-orders" class="section account-tab" style="display:none;">';
	echo '<h3>MY ORDERS</h3>';
	wc_get_template( 'myaccount/my-orders.php', array(
            'current_user'  => get_user_by( 'id', get_current_user_id() ),
            'order_count'   => 10
        ) );
        
        //If zero orders, show something else:
        $customer_orders = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => get_current_user_id(),
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
        ) );
        if ( count( $customer_orders ) <= 0) {
            echo '<p>You have no order history!  Let\'s change that.</p>';
            echo '<a href="/discover/sale/discount-board-games/">Go here to see what\'s on sale and get in the game!</a>';
        }
	echo '</div>';
	
}

function show_bgbucks_section()
{
        if( is_plugin_active( 'bgpr/bgpr.php' ) ) {
            setlocale(LC_MONETARY, 'en_US');
            $user = wp_get_current_user();

            $myBGBuck = get_user_meta($user->ID, 'bg_earned_bucks',true);
            $buckLog = new BGBuckLog;
            $box = '<div id="my-bg-bucks" class="section account-tab" style="display:none;">';
            $box .= '<h3>MY BG BUCKS</h3>';
            $box .= '<p>Earn Board Game Bucks with every order and by earning BG Points. Redeem Board Games Bucks like cash on any BoardGames.com purchase. Go <a href="/board-game-bucks/">here</a> for full details.</p>';
            $box .=  '<div class="bg-account-data">';
            $box .=  '<div class="one-third"><b>My BG Bucks:</b><span class="bucks">'. number_format($myBGBuck, 2, '.', ' ').'</span></div>';
            $box .=  '</div>'; 
            $box .= $buckLog->myBuckLogTable($user->ID);
            $box .= '</div>';
            return $box;
        }
}

function show_bgpoints_section()
{
    if( is_plugin_active( 'bgpr/bgpr.php' ) ) {
	$user = wp_get_current_user();

	$myBGPoints = get_user_meta($user->ID, 'bgpr_current_point_total',true);
	$pointLog = new BGPointLog;
	$level = new BGLevel;
	$box = '<div id="my-bg-points" class="section account-tab" style="display:none;">';
	$box .= '<h3>MY BG POINTS</h3>';	
	$box .= '<p><br/>My current BG Points: '.$myBGPoints.'</p>';
	$box .= $level->user_current_level_box();
	$box .= $pointLog->myPointsLogTable($user->ID);
	$box .= '</div>';
	return $box;
    }
}

function show_myproduct_reviews()
{
	$user = wp_get_current_user();
	$siteUrl = get_site_url();
	
	$args = array(
	    'user_id' => $user->ID,
            'post_type'   => 'product' 
	);
	$comments_query = new WP_Comment_Query;
	$comments = $comments_query->query( $args );
	echo '<div id="my-game-reviews" class="section account-tab" style="display:none;">';
	echo '<h3>MY PRODUCT REVIEWS</h3>';
	echo '<table>';
	echo '<tr><th></th><th>Game</th><th>Rating</th></tr>';
	if ($comments):

		foreach ($comments as $key => $comment) {
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
			$attachment_ids[0] = get_post_thumbnail_id( $comment->comment_post_ID );
			$img_url = $image = wp_get_attachment_image_src( $attachment_ids[0], 'thumbnail' ); 

			$product = get_post( $comment->comment_post_ID  ); 
			$link =get_post_permalink($comment->comment_post_ID);
			$rate_img = $siteUrl .'/wp-content/uploads/2016/07/rate-'. $rating .'.jpg';
			echo '<tr><td align="center"><a href="'.$link.'"><img class="aligncenter" src="'. $image[0].'" data-id="'. $comment->comment_post_ID .'" style="width:50px !important;"></a></td>';
			echo '<td><a href="'.$link.'" title="'.$product->post_title.'">'.$product->post_title.'</a></td><td><img src="'.$rate_img.'"></td></tr>';
		}
	endif;
	echo '</table>';
	echo '</div>';
}
function show_facebook_tab()
{
    	$user = wp_get_current_user();

	//$myBGPoints = get_user_meta($user->ID, 'bgpr_current_point_total',true);

	$box = '<div id="my-facebook" class="section account-tab" style="display:none;">';
	$box .= '<h3>FACEBOOK CONNECT</h3>';	
	$box .= '<p><br/>You can connect your BoardGames.com account to your Faceboook account below:</p>';
        $box .= do_shortcode('brgm_fb_link');
	$box .= '</div>';
	return $box;
}

function show_events_tab()
{
    	$user = wp_get_current_user();

	//$myBGPoints = get_user_meta($user->ID, 'bgpr_current_point_total',true);

	$box = '<div id="my-local" class="section account-tab" style="display:none;">';
	$box .= '<h3>Local Board Game Happenings Near You!</h3>';	
	$box .= '<p>(Based off of your current location: Columbus, OH)<br/><br/>';
        $box .= '<input type="checkbox"/ checked>Conventions</input>';
        $box .= '<input style="margin-left:5px;" type="checkbox"/>Gaming Groups</input>';
        $box .= '<input style="margin-left:5px;" type="checkbox"/>Local Game Stores</input></p>';
        $box .= '<table style="text-align:left;border:0px;width:600px;"><tr>';
        $box .= '<td width="300px"><a href="/event/origins/">Origins Game Fair</a></td>';
        $box .= '<td width="200px">Convention</td>';
        $box .= '<td>6/15/2016</td>';
        $box .= '</tr></table>';
        //$box .= '<ul><li><a href="/event/origins/">Origins Game Fair</a> - 6/15/2016</li></ul></p>';
	$box .= '</div>';
	return $box;
}

function show_subscriptions_tab(){
    	//wc_get_template( 'myaccount/my-subscriptions.php', array( 'subscriptions' => $subscriptions, 'user_id' => $user_id ), '', plugin_dir_path( __FILE__ ) . 'templates/' );
        /*
        // buffer contents
        ob_start();
        ?><div class="woocommerce"><?php
        //output buffered content
        do_shortcode('wmd_my_custom_function');
        echo ob_get_clean();*/
        
        
        $box = '<div id="my-subscriptions" class="section account-tab" style="display:none;">';
        $box .= '<h3>Your Subscriptions</h3>';
        $box .= do_shortcode('wmd_my_custom_function');
        //wc_get_template( 'myaccount/my-subscriptions.php', array( 
        //'user' => get_user_by( 'id', get_current_user_id() ) ) );
        $box .= '</div>';
        return $box;
}
add_action('woocommerce_before_my_account', '_brgm_account_page_left', 5);
add_action('woocommerce_after_my_account', '_brgm_account_page_right', 99);