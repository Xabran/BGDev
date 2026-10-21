<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Bgpr
 * @subpackage Bgpr/admin
 * @author     BoardGames <jetogonon@gmail.com>
 */
class Bgpr_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bgpr-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bgpr-admin.js', array( 'jquery' ), $this->version, false );

	}


	/**
		*
		* admin/class-wp-cbf-admin.php - Don't add this
		*
		**/

		/**
		 * Register the administration menu for this plugin into the WordPress Dashboard menu.
		 *
		 * @since    1.0.0
		 */
		 
		public function add_plugin_admin_menu() {

		    /*
		     * Add a settings page for this plugin to the Settings menu.
		     *
		    
		     */
		    
			 add_menu_page( 
			        __( 'BG Reward System', 'bgpr' ),
			        'BG Reward System',
			        'manage_options',
			        $this->plugin_name,
			        array($this, 'bgpr_plugin_setup_page'),
			        '',
			        81
			    ); 

			add_submenu_page( $this->plugin_name, 'BG Points', 'BG Points',
    			'manage_options',  $this->plugin_name);

			add_submenu_page( $this->plugin_name, "BG Bucks", "BG Bucks",
    				'manage_options', 'bgpr-bucks',array($this, 'bgpr_bgbucks_page')
    				);
		}

		 /**
		 * Add settings action link to the plugins page.
		 *
		 * @since    1.0.0
		 */
		 
		public function add_action_links( $links ) {
		    /*
		    *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
		    */
		   $settings_link = array(
		    '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __('Settings', $this->plugin_name) . '</a>',
		   );
		   return array_merge(  $settings_link, $links );

		}

		/**
		 * Render the settings page for this plugin.
		 *
		 * @since    1.0.0
		 */
		 
		public function bgpr_plugin_setup_page() {
			
		    include_once( 'partials/bgpr-admin-bgpr-index.php' );
		   	
		}

		public function bgpr_bgbucks_page()
		{

			include_once( 'partials/bgpr-admin-bgpr-bucks-index.php' );
		}

		public function bgpr_admin_js()
		{
			?>
			<script type="text/javascript" >
				jQuery(document).ready(function($) {

					
				});
				</script>
			<?php
		}

		/*
		Function to process AJAX
		Adds points whenever user click button to add to Want List, Love List, Own List
		*/

		public function bgpr_point_actions_callback()
		{
			global $wpdb; 


			$points = new BGPointActions;


			$p_id = intval( $_POST['p_id'] );
			$event = $_POST['event'];

			switch ($event):
				case 'edit':
					$points->id = $p_id;
					$data = $points->getData();
					
					echo json_encode($data);
				break;

				case 'update':
					$points->id = $p_id;
					unset($_POST['p_id']);
					unset($_POST['action']);
					unset($_POST['event']);

					$data = $_POST;
					
					$res = $points->updateData($data);

					if ($res==false){
						$success = false;
					} else {
						$success = true;
					}
					echo json_encode(array('success'=>$success));
				break;
			endswitch;

			
			wp_die(); 
		}

		/*
		Displays tabs in the Admin BG Points Page
		*/
		public function bgpr_admin_points_tabs( $current = 'homepage' ) {
		    $tabs = array( '' => 'Point Actions', 'levels' => 'Point Levels','log' => 'Log');
		    echo '<div id="icon-themes" class="icon32"><br></div>';
		    echo '<h2 class="nav-tab-wrapper">';
		    foreach( $tabs as $tab => $name ){
		        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
		        echo "<a class='nav-tab$class' href='admin.php?page=bgpr&tab=$tab'>$name</a>";

		    }
		    echo '</h2>';
		}


		/*
		Displays tabs in the Admin BG Bucks Page
		*/
		public function bgpr_admin_bucks_tabs( $current = 'homepage' ) {
		    $tabs = array( 'bucks' => 'Bucks Action Rewards', 'members' => 'Members', 'log' => 'Log');
		    
		    echo '<div id="icon-themes" class="icon32"><br></div>';
		    echo '<h2 class="nav-tab-wrapper">';
		    foreach( $tabs as $tab => $name ){
		        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
		        echo "<a class='nav-tab$class' href='admin.php?page=bgpr-bucks&tab=$tab'>$name</a>";

		    }
		    echo '</h2>';
		}


		/*
		Function to process AJAX
		When Admin edit or change the member total points or bucks
		It pulls the data from DB and send back via ajax
		*/
		public function bgpr_edit_member_meta_callback()
		{
			global $wpdb; 

			$points = new BGPointLog;

			$event = $_POST['event'];
			switch ($event):
				case 'edit':
					$user = get_userdata( $_POST['user_id'] );
					
					$data['user_nicename'] = $user->user_nicename;
					$data['total_points'] = get_user_meta($user->ID, 'bgpr_current_point_total', true );
					$data['total_bucks'] = get_user_meta($user->ID, 'bg_earned_bucks', true );
					$data['user_id'] =  $_POST['user_id'];
					echo json_encode($data);
				break;

				case 'update':				

					$data = $_POST;
					$user_id = $_POST['user_id'];
					
					$new_total_points = filter_var($_POST['bgpr_current_point_total'], FILTER_VALIDATE_INT);
					$new_total_bucks = filter_var($_POST['bgpr_total_bucks'], FILTER_VALIDATE_INT);

					$orig_total_points = $_POST['orig_current_point_total'];
					$orig_total_bucks = $_POST['orig_total_bucks'];

					//update points
					update_user_meta( $user_id, 'bgpr_current_point_total', $new_total_points, $orig_total_points );
					update_user_meta( $user_id, 'bg_earned_bucks', $new_total_bucks, $orig_total_bucks);

					$pointAction = new BGPointActions;
		    		$action = $pointAction->getDataByType('admin_change');

		    		if ($new_total_points > $orig_total_points):
		    			$points_diff = $new_total_points -  $orig_total_points;
		    			$negative_point = 0;
		    		else :
		    			$points_diff =  $orig_total_points - $new_total_points;
		    			$negative_point = 1;
		    		endif;
		    		
					$pointLog = new BGPointLog;
				    $data = [
				    	'user_id'=>$user_id,
				    	'action_type_id'=>$action->id,
				    	'points_earned'=>$points_diff,
				    	'negative_point'=>$negative_point,
				    	'admin_change_reason'=>$_POST['reason'],
				    	'new_balance'=>$new_total_points
				    	];

				    $pointLog->insertData($data);

				    $level = new BGLevel;
				    $level->updateLevel($user_id, $new_total_points);


				    $buckLog = new BGBuckLog;
				    $data2 = [
				    	'user_id'=>$user_id,
				    	'action_type'=>'admin_change',
				    	'admin_change_reason'=>$_POST['reason']
				    	];

				    $buckLog->insertData($data2);

					//if ($res==false){
						$success = true;
					///} else {
					//	$success = true;
					//}
					echo json_encode(array('success'=>$success));
				break;
			endswitch;

			wp_die();
		}


		public function bgpr_after_validIPN()
		{
			
			mail('jetogonon@gmail.com','BGPR IPN notification', print_r($_POST,true));

			$custom = $_POST['custom'];
			$custom = stripslashes($custom);
			$obj = json_decode($custom);

			error_log('received IPN , Custom data '. print_r($obj,true));

			$order_id = $obj->order_id;

			$user_id = get_post_meta( $order_id, '_customer_user', true );

			$order = new WC_Order($order_id);
			$itemCount = $order->get_item_count();

			error_log('received IPN, user_id'. $user_id);

			$reward = 1;
			if ($itemCount > 0){
				$bgBuckReward = new BGBuck;
	    		$reward = $bgBuckReward->getDataByType($itemCount);
	    	}

	    	error_log('received IPN , Reward data'. print_r($reward,true));


	    	$current_earnedBucks = get_user_meta( $user_id, 'bg_earned_bucks', true ); 
	    	$bucks_total_new = $current_earnedBucks + $reward->buck_reward;

	    	$buckLog = new BGBuckLog;
		    $data = [
		    	'user_id'=>$user_id,	
		    	'action_type'=>'game_purchase',
		    	'bucks_earned'=>$reward->buck_reward,	   	
		    	'negative'=>'0',
		    	'new_balance'=>$bucks_total_new,		    	
		    	];

		    $buckLog->insertData($data);
		    mail('jetogonon@gmail.com','BGPR IPN notification','rewards : '. $reward->buck_reward);


	    	update_user_meta( $user_id, 'bg_earned_bucks', $bucks_total_new, $current_earnedBucks); 


		}

		function bgpr_registration_byadmin( $user_id ) {

		    add_user_meta( $user_id, 'bgpr_current_level', 1);
		    add_user_meta( $user_id, 'bgpr_current_point_total', 0);

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

}
