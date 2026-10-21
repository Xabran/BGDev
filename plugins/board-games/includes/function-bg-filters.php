<?php
/**
 * Package Included for Board Games Plugin
 * 
 * Do not Modify this under any circumtances
 *
 * @package     Board Games 
 * @subpackage  Board Games Filter Function
 * @author      SilverKenn
 * @version     1.0
 */
if( !defined( 'ABSPATH' ) ) exit;
if(isset( $_GET['games'])){
    add_filter( 'loop_shop_per_page', create_function( '$cols', 'return -1;' ), 30 ); 
}else{
    add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 20;' ), 30 );
}
function _bg_filter_display() {
    //if ( ( is_search() || is_archive() ) && is_woocommerce() && is_user_logged_in() ) {
    if ( ( is_search() || is_archive() ) && is_woocommerce()) {
        if(isset( $_GET['games'])){
            //Reduced page for find all, remove filter and pagination etc.
            remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
            add_action('woocommerce_before_shop_loop', '_bg_order_count', 20);
            remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
            remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
            //add_action('woocommerce_after_shop_loop', '_brgm_quick_loader', 10);
            add_action('woocommerce_after_shop_loop_item_title', '_bg_rate_this', 9);
        }
        else{
            remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
            add_action('woocommerce_before_shop_loop', '_bg_order_count', 20);
            remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
            remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
            add_action('woocommerce_after_shop_loop', '_brgm_quick_loader', 10);
            add_action('woocommerce_after_shop_loop_item_title', '_bg_rate_this', 9);
        }

        //if ( (is_tax("product_cat", "adventure-games") || is_tax("product_cat", "city-building-games")) && is_woocommerce()  ) {
            #if ( is_page( 4 ) ) return false;

    }
}
add_action('wp_head', '_bg_filter_display');

function _bg_order_count() {
	global $wp_query;
        $object = get_queried_object();
        $viewAllLink = '?games=findall';
        $backlink = str_replace($viewAllLink,'',$_SERVER['REQUEST_URI']);
	$paged    = max( 1, $wp_query->get( 'paged' ) );
	$per_page = $wp_query->get( 'posts_per_page' );
	$total    = $wp_query->found_posts;
	$first    = ( $per_page * $paged ) - $per_page + 1;
	$last     = min( $total, $wp_query->get( 'posts_per_page' ) * $paged );
        $resultTxt = '<span id="resultCount" >';
        /*echo '$paged: ' . $paged . '<br/>';
        echo '$per_page: ' . $per_page . '<br/>';
        echo '$total: ' . $total . '<br/>';
        echo '$first: ' . $first . '<br/>';
        echo '$last: ' . $last . '<br/>';*/

	$sort = array(
		//'menu_order'	=> 'Default',
                'date'		=> 'Newest Arrivals',
		'popularity'	=> 'Best Selling',
		'rating'	=> 'Highest Rated',
		'price'		=> 'Price: Low to High',
		'price-desc'	=> 'Price: High to Low',
		'title-asc' 	=> 'Game Title (A-Z)',
		'title-desc'	=> 'Game Title (Z-A)',
	);
                if ( 1 === $total ) {
            $resultTxt .= '1 game found';
        }else{
            $resultTxt .= $total . ' total found';
        }
        if(is_tax()){
            $resultTxt .= ' in <b>'.$object->name.'</b>';
        }elseif(is_search()){
            $resultTxt .=  ' for <b>"'.get_search_query(). '"</b>';
        }
        $resultTxt .= '</span>';
          /*if ( 1 === $total ) {
		_e( '1 Result', 'brgm' );
	} elseif ( $total <= $per_page || -1 === $per_page ) {
		printf( __( '%d total games found', 'brgm' ), $total );
	} else {
		printf( _x( '%3$d total games found <a id="view-all" href="#">View All >></a>', '%1$d = first, %2$d = last, %3$d = total', 'brgm' ), $first, $last, $total );
	}*/      
        
        echo '<span class="res-stat">';
        if(isset( $_GET['games'])){
            _e( ' <a id="view-less" href="'.$backlink.'" style="font-size:10pt;">(&laquo; go back)</a></span>');
            _e( $resultTxt, 'brgm' );
            
            echo '</span><div class="bg-result-oc">';
            echo '<p class="brgm-oc">';
            echo '<span class="count">Show: <select id="brgm-count" class="brgm-select"><option value="9999">All</option></select></span>';
        }else{
             _e( $resultTxt, 'brgm' );
            if($total > $per_page && -1 !== $per_page ){
                _e( ' <a href="'.$viewAllLink.'">View All &raquo;</a></span>');
            }
            echo '</span><div class="bg-result-oc">';
            echo '<p class="brgm-oc"><span class="count">Show: '._brgm_select( array(20,24,36,48), 'brgm-count', '', false). '</span>';
        }
        
        echo '<span class="sort"> Sort By: '._brgm_select( $sort, 'brgm-order').'</span>';
        echo '<input type="submit" style="margin-left: 5px;" value="Show Me!"/></p>';
	echo '</div></form>';
}

function _brgm_select( $opt=array(), $id='', $class='', $usekey = true ) {
	if ( !$opt || !is_array( $opt) ) return false;
	$select = '<select id="'.$id.'" class="brgm-select '.$class.'">';
	foreach ($opt as $val => $lab ) {
		$label = ( $lab ) ? $lab : $val;
		$value = ( $usekey ) ? $val : $lab;
		$select .= '<option value="'. $value .'">'. $label .'</option>';
	}
	return $select. '</select>';
}
add_shortcode('brgm_filter', '_brgm_filter_config');
function _brgm_filter_config( $viewAllPage=false, $atts=null ) {
    	global $wp_query;
        $object = get_queried_object();
	$a = shortcode_atts( array(
        'count' => 20,
        'order'	=> 'menu_order',
        'key'	=> null,
        'top'	=> false,
    ), $atts );
        
        if($viewAllPage){
            $show .= '<form method="post" id="brgm-filter">';
        }else{
            //Check if this is a category page that is also listed in the search filter
            //by sending its parent category to the filter function if so, it will disable those filters
            $parent = $object->parent;
            
            $show = apply_filters('brgm/filter/heading','<h4>Refine Your Result</h4>');
            //$show .= _brgm_filter('price', 'Price Range');
            $show .= '<form method="post" id="brgm-filter">';
            $show .= '<input type="submit" value="Show Me!"/>';
            $show .= _brgm_filter('price', 'Price Range',$parent,$object->term_id);
            $show .= _brgm_filter('age_range', 'Age Range',$parent,$object->term_id);
            $show .= _brgm_filter('player_num', 'Number of Players',$parent,$object->term_id);
            //$show .= _brgm_filter('categories', 'Categories');
            $show .= _brgm_filter('playtime', 'Play Time',$parent,$object->term_id);
            //$show .= _brgm_filter('availability', 'Availability Status');
        }
	$show .= '<input type="hidden" id="ppg" name="count" value="'.$a['count'].'"/>';
	$show .= '<input type="hidden" id="ordby" name="order" value="'.$a['order'].'"/>';
        //no n o n c e for guests - breaks caching and the page for all guests
        if(is_user_logged_in()){
            $show .= '<input type="hidden" name="validate" value="'.wp_create_nonce('filter_result').'">';
        }
	
	$show .= '<input type="hidden" name="action" value="brgm_filter_product">';
	$show .= '<input type="hidden" id="paging" name="page" value="1">';
	if ( isset ( $wp_query->query ) && isset( $wp_query->query['s'] ) ) 
		$show .= '<input type="hidden" name="s" value="'.$wp_query->query['s'].'"/>';
	if ( $a['key'] ) $show .= '<input type="hidden" name="key" value="'.$a['key'].'"/>';
	if ( $a['top'] ) $show .= '<input type="hidden" name="toprated" value="yes"/>';

        //check to see if we're on a category page instead of the search
        if(is_tax('pa_publisher'))
            $show .= '<input type="hidden" name="attr[]" value="'. $object->term_id .'"/>';
        if(is_tax('product_cat'))
            $show .= '<input type="hidden" name="term[]" value="'. $object->term_id .'"/>';
        $show .= '<input type="hidden" name="termParent" value="'. $object->parent .'"/>';
        
        if(!$viewAllPage){
            $show .= '<input type="submit" value="Show Me!"/><br/><br/>';
        }
	return $show;
}

function _brgm_show_filter() {
     //if ( (is_tax("product_cat", "adventure-games") || is_tax("product_cat", "city-building-games")) && is_woocommerce()  ) {
    //if ( ( is_search() || is_archive() ) && is_woocommerce() && is_user_logged_in() ) {
    if ( ( is_search() || is_archive() ) && is_woocommerce() ) {
        if(!isset( $_GET['games'])){
            echo _brgm_filter_config();
        }else{
            //For view all pages, only need to start form tag and basic hidden fields
            echo _brgm_filter_config(true);
        }
    }
}
add_action('brgm/filter/display', '_brgm_show_filter');

function _brgm_quick_loader() {
	global $wp_query;
	echo '<div class="quickloader">';
	if ( $wp_query->max_num_pages <= 1 ) return '</div>';
	echo '<button data-columns="4" class="q-load-more">Quick Load More Games</button></div>';
	//echo '<pre>', print_r( $wp_query, 1), '</pre>';
}

function _brgm_filter( $filter, $heading=null, $parentID = null, $pageCatID=null ) {
	$type = array(
		'price' => array(
			'1,9.99'		=> 'Less Than $10',
			'10,19.99'		=> '$10 to $20',
			'20,29.99'		=> '$20 to $30',
			'30,39.99'		=> '$30 to $40',
                        '40,49.99'		=> '$40 to $50',
			'50,99999'	=> '$50 or more',
		),
		'age_range'		=> _brgm_tax_filter( 35 ),
		'player_num'	=> _brgm_tax_filter( 227 ),
		'playtime'	=> _brgm_tax_filter( 37 ),
		//'companies'		=> _brgm_tax_filter( 37 ),
		//'availability'	=> array(
			//'instock'	=> 'In Stock Only'
		//),
	);
	$request = $type[ $filter ];
	if ( !is_array( $request ) ) return $request;
	$out = array();
	foreach ( $request as $req => $val ) {
            //Check to see if we are on the actual category archive page for these search option categories.
            //If so, grey them out - search must be locked in
            $blockOption = false;
            $disableString = '';
            $disabledClass = '';
            $checkMe = '';
            if($req == $pageCatID){$checkMe = ' checked ';}
            if ( $filter === 'price' ) $type = 'price[]';
            if ( $filter === 'companies' ) $type = 'attr[]';
            if ( $filter === 'availability' ) $type = 'stock';
            if ( $filter === 'age_range' ) {
                $type = 'age_ranges[]';
                if($parentID == 35){$blockOption = true;}
            };
            if ( $filter === 'player_num' ) {
                $type = 'players[]';
                if($parentID == 227){$blockOption = true;}
            }
            if ( $filter === 'playtime' ) {
                $type = 'playtimes[]';
                if($parentID == 37){$blockOption = true;}
            }
            if($blockOption){
                $disableString = 'disabled';
                $disabledClass = 'blocksearch';
            }
            $out[] = '<p class="filter-list"><input type="checkbox" '.$disableString.$checkMe.' id="'. $req .'" name="'. $type .'" value="'. $req .'"/><label class="'.$disabledClass.'" for="'.$req.'">'. $val .'</label></p>';
	}

	$head = ( $heading ) ? '<h4 class="filter-heading">'.$heading.'</h4>': '' ;
	return '<div class="filter-section filter_'.$filter.'">'.$head. implode("\n", $out).'</div>';
}

function _brgm_tax_filter( $parent=false, $tax = 'product_cat' ) {

	$object = get_queried_object();

	$term_arg = array(
        'hide_empty'	=> 0,
        'orderby'       => 'name',
        'parent'		=>  $parent,
    );
	if ( $parent === 'no' ) unset( $term_arg['parent'] );
	$terms  = get_terms( $tax, $term_arg);

    $obj = array();
    //if ( isset( $object->parent ) && $object->parent === $parent ) 
    /*if ( isset( $object->term_id ) ) {
    	if ( $object->parent === $parent  )
    		return '<input type="hidden" name="term[]" value="'. $object->term_id .'"/>';
    	if ( $object->taxonomy === 'pa_publisher' && $tax === 'pa_publisher' )
    		return '<input type="hidden" name="attr[]" value="'. $object->term_id .'"/>';
    }*/

    foreach ($terms as $term => $data) {
		$obj[ $data->term_id ] = $data->name;
	}
	return $obj;
}

function _brgm_filter_ajax() {
        if(is_user_logged_in()){
            //Only verify logged in users, breaks everything including cache for guests
            check_ajax_referer( 'filter_result', 'validate' );
        }
	$arg = array(
	'post_type'			=> 'product', 
        'posts_per_page'		=> $_POST['count'],
        'orderby'			=> $_POST['order'],
        'post_status'			=> 'publish',
        //'stock_status'                  => 'instock',   
	'ignore_sticky_posts'	        => 1,
        'orderby'                       => 'ASC',
	);
        $perRow = 4;
        if(isset($_POST['columns']) && strlen($_POST['columns']) > 0){
            $perRow = (int) $_POST['columns'];
        }
        
	if ( isset( $_POST['key'] ) ) $arg['meta_key'] = $_POST['key'];
	
	if ( $_POST['order'] === 'price' || $_POST['order'] === 'price-desc' ) {
		$arg['meta_key'] = '_sale_price';
		$arg['orderby']  = 'meta_value_num';
	}
	if ( $_POST['order'] === 'price' ) {
		$arg['order']  = 'ASC';
	}
	if ( $_POST['order'] === 'rating' ) {
		add_filter( 'posts_clauses', array( 'WC_Shortcodes', 'order_by_rating_post_clauses' ) );
	}
        if ( $_POST['order'] === 'date' ) {
		$arg['orderby']  = 'date';
                $arg['order']  = 'DESC';
	}
	if ( $_POST['order'] === 'popularity' ) {
		$arg['meta_key'] = 'total_sales';
		$arg['orderby']  = 'meta_value_num';
                $arg['order']  = 'DESC';
	}
	if ( $_POST['order'] === 'title-asc' ) {
		$arg['order'] = 'ASC';
		$arg['orderby']  = 'title';
	}
        if ( $_POST['order'] === 'title-desc' ) {
		$arg['order'] = 'DESC';
		$arg['orderby']  = 'title';
	}
        if ( isset( $_POST['toprated'] ) ){
            $arg['orderby']  = 'meta_value_num';
            $arg['meta_key'] = '_wc_average_rating';
        }
	if ( isset( $_POST['s'] ) ) $arg['s'] = $_POST['s'];
	if ( isset( $_POST['reqtype'] ) ) $arg['paged'] = (int) $_POST['page'] + 1;
	if ( isset( $_POST['viewall'] ) ) $arg['posts_per_page'] = -1;
	$meta_query = array( 'relation' => 'AND' );
	$tax_query  = array( 'relation' => 'AND' );
	if ( isset( $_POST['price'] ) ) {
		$meta_query[0] = array('relation' => 'OR');
		foreach ($_POST['price'] as $price => $val ) {
			$meta_query[0][] = array( 
                'key'           => '_sale_price',
                'value'         => explode(',', $val ),
                'compare' 		=> 'BETWEEN',
            	'type' 			=> 'NUMERIC'
            );}
            $arg['meta_query'] = $meta_query;
	}
        /*$meta_query[] = array(
            'key' => '_stock_status',
            'value' => 'instock'
        );*/
        $arg['meta_query'] = $meta_query;
        //$debug .= 'METAQUERY:'.print_r($meta_query, true). '<br><br>';
        
        //Now do category terms
        if ( isset( $_POST['term'] ) ) {
            //this is a product category or attribute archive page
            $tax_query[] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $_POST['term'],
                        'field'    => 'term_id',
                        'operator' => 'IN',
		);
            $arg['tax_query'] = $tax_query;
        }
        
	if ( isset( $_POST['age_ranges'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $_POST['age_ranges'],
                        'field'    => 'term_id',
                        'operator' => 'IN',
		);
                $arg['tax_query'] = $tax_query;
	}
        if ( isset( $_POST['players'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $_POST['players'],
                        'field'    => 'term_id',
                        'operator' => 'IN',
		);
                $arg['tax_query'] = $tax_query;
	}
        if ( isset( $_POST['playtimes'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $_POST['playtimes'],
                        'field'    => 'term_id',
                        'operator' => 'IN',
		);
                $arg['tax_query'] = $tax_query;
	}
	if ( isset( $_POST['attr'] ) ) {
		$tax_query[] = array(
			'taxonomy' => 'pa_publisher',
			'terms'    => $_POST['attr'],
		);
                $arg['tax_query'] = $tax_query;
	}
        $arg['tax_query'] = $tax_query;
        //$debug .= 'TAXQUERY:'.print_r($arg['tax_query'], true). '<br><br>';
	$query = new WP_QUery( $arg ); //Run First Query

	//if ( isset( $_POST['toprated'] ) )
	//add_filter('posts_clauses', array( 'WC_Shortcodes', 'order_by_rating_post_clauses' ) );
        $results = $query->get_posts(); 
        $current_page = isset( $query->query['paged'] ) ? $query->query['paged'] : 1;
        $out = array('status' => false, 'paged' => $query->max_num_pages, 'current' => $current_page );
        //$testOut .= 'RESULTS: '.print_r($results);
        if ( $results ) {
            $out['status'] = count( $results );
            $item = array();
            $i = 0;
            foreach ($results as $result => $data) {
                    $i++;
                    $c = (  $i % $perRow == 0 ) ? 'product last' : 'product';
                    $f = ( 0 === ( $i - 1 ) % $perRow || 1 === $perRow ) ? ' first' : '' ;
                    $product = new WC_Product( (int) $data->ID );
                    $link = get_the_permalink( $data->ID );
                    $item[] = '<li class="'.$c.$f.'"><a href="'.$link.'">'.
                    $product->get_image().'<h3>'.$data->post_title.'</h3>'.
                    //$product->get_rating_html().'<span class="price">'.$product->get_price_html().'</span></a>'.do_shortcode('[add_to_cart style="" show_price="false" id="'.$data->ID.'"]').'</li>';
                    dynamic_rating_output($product, $link).'<span class="price">'.$product->get_price_html().'</span></a>'.do_shortcode('[add_to_cart style="" show_price="false" id="'.$data->ID.'"]').'</li>';
            }
            $out['result'] = implode("\n", $item );
        }
        //$out['result'] = '<div>' . $debug . '</div>' . $out['result'];
        $test = json_encode($out);
        $error = json_last_error();
        if($error){ 
            $out['error'] = $error;
            //echo 'ERROR: '.$error;
        }
        
        /*if(!is_user_logged_in()){
            //Auto success for guests, security only valid for logged in users
            wp_send_json_success($out);
        }else{*/
            wp_send_json( $out );
        //}
}
function dynamic_rating_output($product, $link) {
    if($product->get_rating_html()){
        return $product->get_rating_html();
    }
    else{
        return '<div style="margin-top:-4px !important;"><a href="'.$link.'#review_form" class="rateGameText gimmeArrowsafter" >Rate this game</a></div>';
    }
}
//if ( ( is_search() || is_archive() ) && is_woocommerce() && is_user_logged_in() ) {
//if ( ( is_search() || is_archive() ) && is_woocommerce()) {
    add_action( 'wp_ajax_brgm_filter_product', '_brgm_filter_ajax' );
    add_action( 'wp_ajax_nopriv_brgm_filter_product', '_brgm_filter_ajax' );
//}
