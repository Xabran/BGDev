<?php
/**
 * Package Included for Board Games
 * 
 * Do not Modify this under any circumtances
 *
 * @package     Board Games
 * @subpackage  Board Games Woocommerce List
 * @author      SilverKenn
 * @version     1.0
 */
if( !defined( 'ABSPATH' ) ) exit;

// Duplication is not allowed
if( !class_exists( 'BR_List' ) ) {

    //Lets Rock!
    class BR_List {

        public $buttons = array();
        public $have_it = array();
        public $want_it = array();
        public $love_it = array();
        public $action  = '_brgm_wclist';

        public function __construct() {
			//this class is lazy ass. ain't do nothin'
            add_action('wp_head', array( $this, 'get_list') );
            add_action( "wp_ajax_{$this->action}", array( $this, 'ajax' ) );

            add_action('woocommerce_checkout_order_processed', array( $this, 'order_update_list') );
        }

        public function init() {
            if (  in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ) {
                add_action('wp_enqueue_scripts', array( $this, 'scripts' ) );
                add_shortcode( 'brgm_list', array($this, 'shortcode') );
                add_shortcode( 'br_list_btn', array( $this, 'buttons' ) );

                $this->have_it = $this->meta('have_it');
                $this->want_it = $this->meta('want_it');
                $this->love_it = $this->meta('love_it');

            } else {
                add_action('admin_notices', function(){
                    echo '<div class="notice is-dismissible error"><p>'.
                    __('You need to enable Woocommerce', 'brgm' ).'</p></div>';    
                });
                
            }            
        }

        public function nonce() {
            return ( is_user_logged_in() ) ? wp_create_nonce( "br_action_list" ) : '0' ;
        }

        public function scripts() {
            wp_enqueue_script('br-list', BR_URL. 'assets/js/br-list.js', array(), time(), true );
            wp_localize_script( 'br-list', 'br_list', array(
                'action'   => $this->action,
            ));
        }

        public function load( $id ) {
            $nonce = $this->nonce();
            $item = "<ul class='br-woo-list' data-nonce='{$nonce}'>";

            foreach ($this->buttons() as $key => $val ) {
                $action = ( is_array( $this->meta( $key ) ) && in_array( $id, $this->meta( $key ) ) ) ? 'removefrom' : 'addto' ;
				$text = ( is_array( $this->meta( $key ) ) && in_array( $id, $this->meta( $key ) ) ) ? 'Added to '.$val.' List' : $val ;
                $item .= "<li><button class='{$key}' data-action='{$action}' data-text='{$val}' type='button' data-id='{$id}' data-list='{$key}'>{$text}</button></li>";
            }
            $item .= '</ul>';
            return $item;

        }
        public function buttons() {
            $this->buttons = array(
                'love_it' => __('Love It', 'brgm'),
                'want_it' => __('Want It', 'brgm'),
		'have_it' => __('Own It', 'brgm'),                
            );
            return $this->buttons;
        }

        public function ajax() {
			//wp_send_json( $_REQUEST ); 
            check_ajax_referer( 'br_action_list', 'security' );
            $list_id = $_POST['listID'];
            $prod_id = $_POST['productID'];

            $status = false;
            
            if ( isset( $list_id ) && $_POST['type'] === 'addto') {
                $status  = $this->meta( $list_id, $prod_id, 'update' );
                $counter = $this->add_counter( $prod_id, $list_id );
            } else if ( isset( $list_id ) && $_POST['type'] === 'removefrom' ) {
                $status = $this->meta( $list_id, $prod_id, 'delete' );
                $counter = $this->add_counter( $prod_id, $list_id, 'remove' );
            }
			$count = get_post_meta( $prod_id, '_brgm_'.$list_id. '_added', true );
            $response = array(
                'status'    => $status,
                'count'     => $count,
                'action'    => $_POST['type'],
            );

            error_log( 'bg-woocommerce-list: sending to brgm/after/wclist');
            
            do_action('brgm/after/wclist', $prod_id, $list_id, $_POST['type'], $count, $status );

            wp_send_json( $response ); 
        }

        public function meta( $key, $value = NULL, $action = 'get' ) {
            $meta_key = $this->action. '_'. $key;
            $meta_value = get_user_meta( get_current_user_id(), $meta_key, true );

            if ( $action === 'update' && $value && !in_array( $value, $meta_value ) ) {
                $meta_value[] = $value;
                return update_user_meta( get_current_user_id(), $meta_key, $meta_value );
            } else if ( $action === 'delete' && $value && in_array( $value, $meta_value ) ) {
                $updatad_value = array_diff($meta_value, array( $value ));
                return update_user_meta( get_current_user_id(), $meta_key, $updatad_value );
            } else if ( $action === 'get' ) {
                return ( $meta_value ) ? $meta_value : array() ;
            } 
            return false;  
        }

        public function add_counter( $id, $list, $type='add' ) {
            $meta = '_brgm_'.$list. '_added';
            $current = get_post_meta( $id, $meta, true );
            $val = ( $type === 'add') ? $current + 1 : $current - 1;
            if ( $val >= 0 ) return update_post_meta( $id, $meta, $val );
            return update_post_meta( $id, $meta, 0);
        }

        public function get_list( $list=null, $id='', $class='', $type = 'list', $heading = null ) {
            $item = array_merge( $this->have_it, $this->want_it, $this->love_it );
            if ( $list === 'have_it' ) {$item = $this->have_it; $heading = '<h3>MY \'HAVE IT\' LIST</h3>';}
            if ( $list === 'want_it' ) {$item = $this->want_it; $heading = '<h3>MY \'WANT IT\' LIST</h3>';}
            if ( $list === 'love_it' ) {$item = $this->love_it; $heading = '<h3>MY \'LOVE IT\' LIST</h3>';}
            $prod = array();
            if ( $item ) {
                
                $arg = array(
                    'post_type'         => 'product',
                    'posts_per_page'    => -1,
                    'orderby'           => 'date',
                    'order'             => 'DESC',
                    'post__in'          => $item,
                );
                $query = new WP_QUery( $arg ); 
                $results = $query->get_posts();
                foreach ($results as $res => $data ) {
                    $product = new WC_Product( $data->ID );
                    $link = get_the_permalink( $data->ID );
                    $avail = $product->get_availability();
                    if ( $type === 'list' ) {
                        $prod[] = '<li style="text-align:left;"><a href="'.$link.'">'. $data->post_title.'</a></li>';
                    }
                    else {
                        $anItem = '<li id="list-'.$data->ID.'"><span class="list-img"><a href="'.$link.'">'.$product->get_image($size = 'thumnbail'). $data->post_title.'</a></span>'.
                        '<span class="list-rating">'.$product->get_rating_html().'<a href="'.$link.'">Learn More</a></span>'.
                        '<span class="list-price">'.$product->get_price_html().'</span>'.
                        '<span class="list-avail">'.$avail['availability'].'</span>';
                        if ( $list === 'want_it' && $type != 'list' ) {
                                $anItem.= '<span class="list-review"><a class="btn orange" href="'.$link.'">Get this game!</a></span>';
                        }
                        else{
                            $anItem.= '<span class="list-review"><a class="btn orange" href="'.$link.'#review_form">Write a review</a></span>';
                        }
                        $anItem.='<span class="list-remove"><button class="remove-item" data-id="'. $data->ID .'">X</button></span></li>';
                        $prod[] = $anItem;
                    }
                }

            }
            $list_head = ( $type === 'list' ) ? '' : '<li class="list-heading"><span class="list-img">Game</span><span class="list-rating">Rating</span><span class="list-price">Price/BG Price</span><span class="list-avail">Availability</span><span class="list-review">Review This Game</span><span class="list-remove">Remove</span></li>';
            return '<div id="'. $id .'" class="'.$class.'">'.$heading .'<ul class="block brgm-list '.$list.'" data-list="'. $list .'" data-nonce="'.$this->nonce().'">'.$list_head.implode("\n", $prod ).'</ul></div>';
        } 

        public function order_update_list( $id, $data ) {
            $want = $this->meta('want_it');
            $owned = $this->meta('have_it');
            $order = new WC_Order( $id );
            if ( !$order ) return false;
            foreach ( $order->get_items() as $key => $item ) {
                if ( $want && in_array( $item['product_id'], $want ) ) {
                    $this->meta( 'want_it', $item['product_id'], 'delete' );
                    $this->add_counter( $item['product_id'], 'want_it', 'remove' );
                }
                if ( !$owned || ( $owned && !in_array( $item['product_id'], $owned ) ) ) {
                    $this->meta( 'have_it', $item['product_id'], 'update' );
                    $this->add_counter( $item['product_id'], 'have_it' );
                }
            }
        }

        public function shortcode( $atts ) {
            $a = shortcode_atts( array(
                'type'  => '',
            ) , $atts);
            return $this->get_list( $a['type'] );
        }
    }
    $br_wclist = new BR_List;   
}