<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 *
 * @package    Bgpr
 * @subpackage Bgpr/public
 * @author     BoardGames <jetogonon@gmail.com>
 */
class Bgpr_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name='', $version='' ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {


		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bgpr-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'magnific-popup', plugin_dir_url( __FILE__ ) . 'css/magnific-popup.css', array(), $this->version, '' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {


		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bgpr-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'magnific-popup-js', plugin_dir_url( __FILE__ ) . 'js/jquery-magnific-popup.min.js', array( 'jquery' ), $this->version, false );

	}




	function bgpr_registration_event( $user_id ) {

	    add_user_meta( $user_id, 'bgpr_current_level', 1);
	    add_user_meta( $user_id, 'bgpr_current_point_total', 0);
	    add_user_meta( $user_id, 'bg_earned_bucks',1); 

	    $buckLog = new BGBuckLog;
	    $data = [
	    	'user_id'=>$user_id,	
	    	'action_type'=>'signup_bonus',
	    	'bucks_earned'=>1,	   	
	    	'negative'=>'0',
	    	'new_balance'=>1
	    	];

	    $buckLog->insertData($data);

	    //TODO
	    //auto add bg_earned_bucks 1 if someone created an account
	    //if it for any member, or when someone subscribed?

	    $pointAction = new BGPointActions;
	    $action = $pointAction->getDataByType('signup_bonus');

	    $pointLog = new BGPointLog;
	    $data = [
	    	'user_id'=>$user_id,
	    	'action_type_id'=>$action->id,
	    	'points_earned'=>$action->action_value,	   	
	    	'new_balance'=>$action->action_value,
	    	];

	    $pointLog->insertData($data);

	    $current_point_total = get_user_meta( $user_id, 'bgpr_current_point_total', true ); 
	    $current_point_total_new = $current_point_total + $action->action_value;

	    update_user_meta( $user_id, 'bgpr_current_point_total', $current_point_total_new, $current_point_total ); 

	    return true;

	}

	function bgpr_add_review_event($comment_ID ){
		global $current_user;
      	get_currentuserinfo();


      	$user_id = $current_user->ID;

      	$comment = get_comment( $comment_ID );
      	$post_id = $comment->comment_post_ID;

      	$post = get_post($post_id);

      	if ($post->post_type == 'post'):
      		$actionType = 'blog_comment';
      	endif;

      	if ($post->post_type == 'product'):
      		$actionType = 'product_review';
      	endif;

      	$pointAction = new BGPointActions;
	    $action = $pointAction->getDataByType($actionType);

	   // error_log('Blog Post  '.$post_id.' '. print_r($post,true));

	    $current_point_total = get_user_meta( $user_id, 'bgpr_current_point_total', true ); 
	    $point_total_new = $current_point_total + $action->action_value;

	    $pointLog = new BGPointLog;
	    $data = [
	    	'user_id'=>$user_id,
	    	'action_type_id'=>$action->id,
	    	'points_earned'=>$action->action_value,	    	
	    	'new_balance'=>$point_total_new,
	    	];

	    $pointLog->insertData($data);

	   

	    update_user_meta( $user_id, 'bgpr_current_point_total', $point_total_new, $current_point_total ); 

	    $level = new BGLevel;
		$level->updateLevel($user_id, $point_total_new);
	}


	/*
	Function to add points to user
	When user clicks Want, Love it, OWn list
	*/

	public function bgpr_action_list_event($prod_id, $list_id, $type,$count, $status)
	{
		global $current_user;
      	get_currentuserinfo();

		$listID = $_POST['listID'];
		$pointAction = '';

		 error_log( 'on this script: '. $listID);

		switch ($listID) {
			case 'love_it':
				$pointAct = 'lovelist_add';
			break;
			case 'want_it':
				$pointAct = 'wantlist_add';					
			break;
			case 'have_it':
				$pointAct = 'ownlist_add';
			break;
			
			default:				
			break;
		}


		if ($pointAct):

			$user_id = $current_user->ID;

	      	$pointAction = new BGPointActions;
		    $action = $pointAction->getDataByType($pointAct);

		    $current_point_total = get_user_meta( $user_id, 'bgpr_current_point_total', true ); 
		    $point_total_new = $current_point_total + $action->action_value;

		    $pointLog = new BGPointLog;
		    $data = [
		    	'user_id'=>$user_id,
		    	'action_type_id'=>$action->id,
		    	'points_earned'=>$action->action_value,
		    	'new_balance'=>$point_total_new,
		    	];

		    $pointLog->insertData($data);
		    

		    update_user_meta( $user_id, 'bgpr_current_point_total', $point_total_new, $current_point_total ); 

		    $level = new BGLevel;
		    $level->updateLevel($user_id, $point_total_new);

		 endif;
	}

	public function action_check_if_bgpr_shipping( $chosen_method ) {
	    global $woocommerce;

	    //mail('jetogonon@gmail.com','testing ship method change',$chosen_method);

	    error_log('shipping method chosen: '. $chosen_method, 0, plugin_dir_path( __FILE__ ) .'debug.log');

	    
	    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	    error_log(print_r($chosen_methods, true ), 0, plugin_dir_path( __FILE__ ) .'debug.log');
	    $chosen_shipping = $chosen_methods[0]; 
	    if ($chosen_method == 'bgpr-free-shipping') { 
	            /*2158*/
	            $woocommerce->cart->add_to_cart( 2244 ); //simple product

	            error_log('shipping method chosen : ', 0, plugin_dir_path( __FILE__ ) .'debug.log');
	        return true;
	    }
	}

	public function bgpr_free_shipping_selected_callback()
	{
		global $woocommerce;

		//mail('jetogonon@gmail.com','testing ship method change','added via ajax');

		

		$wctotal 	= preg_replace('/&#36;/', '', WC()->cart->get_cart_total());

		//mail('jetogonon@gmail.com','testing ship method change','WC TOTAL: '. $wctotal);

		$instantBGBucks = 20;
		
		$wctotal = filter_var($wctotal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$wctotal = preg_replace("/[^0-9\.]/", "",$wctotal);
		$totalBeforePlayersClub = $wctotal;

		if ($totalBeforePlayersClub < 20){
			$instantBGBucksToBeUsed = $totalBeforePlayersClub;

		} else {
			$instantBGBucksToBeUsed = 20;

		}

		

		if (self::has_woocommerce_subscription('', 2244, 'active')==false) :
			$res = $woocommerce->cart->add_to_cart( 2244 ); //simple product
			do_action('bgpr_create_instantbucks_coupon', $instantBGBucksToBeUsed);
			echo 'added 2244';
		endif;

		
	}

	public function bgpr_free_shipping_deselected_callback()
	{
		global $woocommerce;

		 	$cart = WC()->instance()->cart;
			$id = 2244;
			$cart_id = $cart->generate_cart_id($id);
			$cart_item_id = $cart->find_product_in_cart($cart_id);

			if($cart_item_id){
			   $cart->set_quantity($cart_item_id,0);
			}

			//deselect free shipping
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

			$chosen_shipping_methods[0] = wc_clean( 'usps:I_FIRST_CLASS' );

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

			WC()->cart->calculate_totals();

			woocommerce_cart_totals();

		 return true;
	}


	public static function check_if_PlayersClubInCart()
	{
		$product_id = 2244;
		if (empty(WC()->cart->get_cart())):
			return false;
		else:

			foreach( WC()->cart->get_cart() as $cart_item_key => $values ) :
				$_product = $values['data'];
			
				if( $product_id == $_product->id ) :
					return true;
				endif;
			endforeach;
			return false;
		endif;
	}

	public function bgpr_claim_reward_callback()
	{
		global $woocommerce;
		global $current_user;
      	get_currentuserinfo();


      	$user_id = $current_user->ID;

		$userBucks = get_user_meta( $user_id, 'bg_earned_bucks',true); 


      	
		//add_action('woocommerce_calculate_totals', 'custom_discount' );

		//create acoupon
		$coupon_code = self::create_userreward_coupon();

		$woocommerce->cart->add_discount(sanitize_text_field($coupon_code));

		$_SESSION["cart_reward_bucks_used"] = $userBucks;
		$_SESSION["cart_reward_coupon_used"] = $coupon_code;

		//echo 'applied discount ' . $coupon_code;
	}

	public static function create_userreward_coupon($bucks = null)
	{
		global $current_user;
      	get_currentuserinfo();


      	$user_id = $current_user->ID;

      	if ($bucks==null):
			$userBucks = get_user_meta( $user_id, 'bg_earned_bucks',true);
			$amount = $userBucks; // Amount
			$coupon_code = $user_id . '-USERBUCKS-'.date('m.d.y'); // Code
		else:
			$amount = $bucks;
			$coupon_code = $user_id . '-CARTINSTANTBUCKS-'.date('m.d.y'); // Code
		endif;
		
		
		$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
							
		$coupon = array(
			'post_title' => $coupon_code,
			'post_content' => '',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'		=> 'shop_coupon'
		);
							
		$new_coupon_id = wp_insert_post( $coupon );
							
		// Add meta
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', 'no' );
		update_post_meta( $new_coupon_id, 'product_ids', '' );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', '1' );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

		return $coupon_code;
	}

	
	public function bgpr_coupon_removed($coupon_code)
	{
		global $woocommerce;

		unset($_SESSION['cart_reward_bucks_used']);
		unset($_SESSION['cart_reward_coupon_used']);

		$coupon_data = new WC_Coupon($coupon_code);
		if(!empty($coupon_data->id))
		{
		    wp_delete_post($coupon_data->id);
		}

		$cart = WC()->instance()->cart;
			$id = 2244;
			$cart_id = $cart->generate_cart_id($id);
			$cart_item_id = $cart->find_product_in_cart($cart_id);

			if($cart_item_id){
			   $cart->set_quantity($cart_item_id,0);
			}
	}

	public function bgpr_create_instantbucks_coupon_callback($bucks){
		session_start();
		global $woocommerce;
		global $current_user;
      	get_currentuserinfo();

		//create acoupon
		$coupon_code = self::create_userreward_coupon($bucks);

		$woocommerce->cart->add_discount(sanitize_text_field($coupon_code));

		$_SESSION["cart_reward_bucks_used"] = $userBucks;
		$_SESSION["cart_reward_coupon_used"] = $coupon_code;
		$userBucksLeft = 20 - $bucks;
		$_SESSION["cart_reward_bucks_left"] = $userBucksLeft;
		$_SESSION["cart_instantreward_coupon_used"] = $coupon_code;
	}


	public function bgpr_pre_payment_complete($order_id)
	{
		global $current_user;
		global $woocommerce;
      	get_currentuserinfo();
      	$user_id = $current_user->ID;

      //	mail('jetogonon@gmail.com','BGPR pre_payment_action','session : '. $_SESSION["cart_reward_bucks_used"] .' userid:'.$user_id);

      	if (!empty($_SESSION["cart_reward_bucks_used"]) ):

			$userBucks = $_SESSION["cart_reward_bucks_used"];
			$currentUserBucks = get_user_meta( $user_id, 'bg_earned_bucks',true); 

			$newUserBucks = $currentUserBucks - $userBucks;

			//update
			update_user_meta( $user_id, 'bg_earned_bucks', $newUserBucks, $currentUserBucks); 

			unset($_SESSION["cart_reward_bucks_used"]);

			$buckLog = new BGBuckLog;
		    $data = [
		    	'user_id'=>$user_id,	
		    	'action_type'=>'points_redemption',
		    	'bucks_earned'=>$userBucks,	   	
		    	'negative'=>'1',
		    	'new_balance'=>$newUserBucks,		    	
		    	];

		    $buckLog->insertData($data);

		   

		   // mail('jetogonon@gmail.com','BGPR pre_payment_action','session : '. $_SESSION["cart_reward_bucks_used"] .' should have logged buck.');

		endif;

	}


	public function bgpr_update_bottom_left_callback()
	{
		session_start();

		global $woocommerce;


		$count = 0;
		$bgReward = 0;

		$ClubProduct_inCart = false;
		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) :
			//echo '<pre>'.print_r($values).'</pre>';
			$_product = $values['data'];
			if( 2244 != $_product->id ) :
				$count = $count +  $values['quantity'];
			endif;
		endforeach;
		$bgBuck = new BGBuck;
		$bgReward = $bgBuck->getDataByType($count);

		echo '<div class="alert alert-green">
				<p>Complete your order to earn $'.$bgReward->buck_reward.' in Board Games!
			
		';
		if (!is_user_logged_in()):
			echo '<br/>(You will be able to create an account on the next page)';
		endif;

		echo '</p></div>';
		if (self::has_woocommerce_subscription('', 2244, 'active')==false) :
			echo '<a href="" id="playersclubimg"><img src="'.get_option('home').'/wp-content/plugins/bgpr/public/images/checkout-image-playersclub.jpg"/></a>';
		endif;

		//check coupon 
		//if exists, check if product items (simple products) are more or less 20
		//adjust coupon accordingly


		if ($_SESSION["cart_instantreward_coupon_used"]):

			$ClubProduct_inCart = false;
			$totalBeforePlayersClub = 0;

			foreach( WC()->cart->get_cart() as $cart_item_key => $values ) :
				$_product = $values['data'];
			
				if( 2244 == $_product->id ) :
					$ClubProduct_inCart = true;
					$totalBeforePlayersClub = 0 + $totalBeforePlayersClub;
				else:
					$totalBeforePlayersClub = ($_product->price * $values['quantity'])  + $totalBeforePlayersClub;
				endif;
			endforeach;		

			$coupon_data = new WC_Coupon($_SESSION["cart_instantreward_coupon_used"]);
			if ($coupon_data->coupon_amount == $totalBeforePlayersClub):
				//do nothing

			endif;
			//echo 'c_amount: '.$coupon_data->coupon_amount .' = '.$totalBeforePlayersClub ;

			if (($coupon_data->coupon_amount > $totalBeforePlayersClub) || ($coupon_data->coupon_amount < $totalBeforePlayersClub)):
				//total amount has changed

				if ($totalBeforePlayersClub < 20){
					$instantBGBucksToBeUsed = $totalBeforePlayersClub;

				} else {
					$instantBGBucksToBeUsed = 20;

				}

				$woocommerce->cart->remove_coupons( sanitize_text_field( $_SESSION["cart_instantreward_coupon_used"]));

				if(!empty($coupon_data->id))
				{
				    wp_delete_post($coupon_data->id);
				}

				//unset($_SESSION["cart_instantreward_coupon_used"]);

				//create new
				do_action('bgpr_create_instantbucks_coupon', $instantBGBucksToBeUsed);

			endif;
		endif;/*endif sess exists*/
		die();
	}

	public function bgpr_update_amountforfreeshipping_callback()
	{
		session_start();

		$bucksNeeded = 0;
		$wcsubtotal 	= preg_replace('/&#36;/', '', WC()->cart->get_cart_subtotal());

		$wcsubtotal = filter_var($wcsubtotal, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$wcsubtotal = preg_replace("/[^0-9\.]/", "",$wcsubtotal);

		if ($wcsubtotal <= 75):
			$bucksNeeded = 75 - $wcsubtotal;
		endif;
		if ($bucksNeeded == 0):
			echo '<p>You got Free Shipping!</p>';
		else:

			echo '<p>Add $'.$bucksNeeded.' more to get free shipping for any orders with at least $75 worth of games! </p>';
		endif;


		die();
	}

	public function bgpr_custom_field_checkout($fields)
	{
			global $woocommerce;

			$count = 0;
			$bgReward = 0;

			$ClubProduct_inCart = false;
			foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) :
				//echo '<pre>'.print_r($values).'</pre>';
				$_product = $values['data'];
				if( 2244 != $_product->id ) :
					$count = $count +  $values['quantity'];
				endif;
			endforeach;
			$bgBuck = new BGBuck;
			$bgReward = $bgBuck->getDataByType($count);

			unset($fields['billing']['billing_company']);
     		return $fields;
 			
 
	}

	public function bgpr_woocommerce_thankyou($order_id)
	{
		global $woocommerce;

		$order = new WC_Order($order_id);
		$itemCount = $order->get_item_count();

		//delete coupon
		if ($_SESSION["cart_reward_coupon_used"]):
			$woocommerce->cart->remove_coupons( sanitize_text_field( $_SESSION["cart_reward_coupon_used"]));

			$coupon_data = new WC_Coupon($_SESSION["cart_reward_coupon_used"]);
			if(!empty($coupon_data->id))
			{
			    wp_delete_post($coupon_data->id);
			}
			unset($_SESSION["cart_reward_bucks_used"]);
			unset($_SESSION["cart_reward_coupon_used"]);
			unset($_SESSION["cart_instantreward_coupon_used"]);
		endif;

		//error_log('THank you page, order_id'. $order_id);

		$paymentMethod = get_post_meta( $order_id, '_payment_method', true );
		if ($paymentMethod == 'paypal'):
			return;
		endif;

		$reward = 1;
		if ($itemCount > 0){
			$bgBuckReward = new BGBuck;
    		$reward = $bgBuckReward->getDataByType($itemCount);
    	}

    	$user_id = get_post_meta( $order_id, '_customer_user', true );

    	$current_earnedBucks = get_user_meta( $user_id, 'bg_earned_bucks', true ); 
    	$bucks_total_new = $current_earnedBucks + $reward->buck_reward;

    	if ($_SESSION["cart_reward_bucks_left"] > 0){
    		$bucks_total_new = $bucks_total_new + $_SESSION["cart_reward_bucks_left"];
    	}

    	$buckLog = new BGBuckLog;
	    $data = [
	    	'user_id'=>$user_id,	
	    	'action_type'=>'game_purchase',
	    	'bucks_earned'=>$reward->buck_reward,	   	
	    	'negative'=>'0',
	    	'new_balance'=>$bucks_total_new,		    	
	    	];

	    $buckLog->insertData($data);

	    update_user_meta( $user_id, 'bg_earned_bucks', $bucks_total_new, $current_earnedBucks); 



		


	    //mail('jetogonon@gmail.com','Thank you page, rewards','user_id: '.$user_id.' rewards : '. $reward->buck_reward .' payment method:' . $paymentMethod);
	}

	public function bgpr_user_logout()
	{
		global $woocommerce;

		if ($_SESSION["cart_reward_coupon_used"]):
			$woocommerce->cart->remove_coupons( sanitize_text_field( $_SESSION["cart_reward_coupon_used"]));

			$coupon_data = new WC_Coupon($_SESSION["cart_reward_coupon_used"]);
			if(!empty($coupon_data->id))
			{
			    wp_delete_post($coupon_data->id);
			}
		endif;

		unset($_SESSION["cart_reward_bucks_used"]);
		unset($_SESSION["cart_reward_coupon_used"]);
		unset($_SESSION["cart_instantreward_coupon_used"]);
	}

	public function my_new_carttotals_after()
	{
		global $woocommerce;

		// Cost - 1 currency = 100 points
		$cost = $woocommerce->cart->total * 100;

		?>
		
		<?php
	}

	public function bgpr_playersclub_removed()
	{
		//remove coupon
		WC()->cart->remove_coupon( $_SESSION["cart_reward_coupon_used"] );
	}

	public static function has_woocommerce_subscription($the_user_id, $the_product_id, $the_status) {
		$current_user = wp_get_current_user();
		if (empty($the_user_id)) {
			$the_user_id = $current_user->ID;
		}
		if (WC_Subscriptions_Manager::user_has_subscription( $the_user_id, $the_product_id, $the_status)) {
			return true;
		}
	}
} 

