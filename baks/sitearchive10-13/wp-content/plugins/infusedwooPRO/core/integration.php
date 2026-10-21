<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('plugins_loaded', 'ia_woocommerce_init', 0);
if( !class_exists("iaSDK")) include(INFUSEDWOO_PRO_DIR . 'core/sdk/iasdk.php');

function ia_woocommerce_init() {

	if (!class_exists('WC_Integration')) {
		add_action('admin_notices', 'ia_woo_required');
		return;
	}

	/**
	 * Integration Gateway Class
	 **/
	 
	class IA_Woocommerce extends WC_Integration {	
		public function __construct() { 
			$this->id					= 'infusionsoft';
	        $this->method_title			= __( 'Infusionsoft', 'woocommerce' );
	        $this->method_description	= __( 'Integrates WooCommerce with Infusionsoft CRM. You need an Infusionsoft account to make this plugin work.', 'woocommerce' );
	        $this->has_fields 			= false;
	        $this->appconnected 		= false;
			
			// Load the form fields
			$this->init_form_fields();
			
			// Load settings
			$this->load_settings();
			$this->recent_update_routines();

			// ADMIN HOOKS
			add_action('admin_notices', array(&$this,'ia_woocommerce_notices'));
			add_action('admin_notices', array(&$this,'installation_guide'));
			add_action('woocommerce_update_options_integration_infusionsoft', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_integration_infusionsoft', array(&$this, 'ia_woocommerce_update_product_options'));

			add_action('woocommerce_update_options_integration', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_integration', array(&$this, 'ia_woocommerce_update_product_options'));
			
			$plugin = INFUSEDWOO_PRO_BASE; 
			add_filter("plugin_action_links_$plugin", array(&$this, 'ia_woocommerce_settings_link') );


			do_action("iwpro_ready", $this );
		}

		function load_settings() {
			// Load the settings.
			$this->init_settings();

			// Get setting values
			$this->enabled 		= isset($this->settings['enabled']) ? $this->settings['enabled'] : "no";

			if(isset($this->settings['title'])) 		$this->title = $this->settings['title'];
			if(isset($this->settings['description'])) 	$this->description	= $this->settings['description'];

			$this->machine_name	= isset($this->settings['machinename']) ? $this->settings['machinename'] : "";
			$this->apikey		= isset($this->settings['apikey']) ? $this->settings['apikey'] : "";
			$this->success_as   = isset($this->settings['success_as']) ? $this->settings['success_as'] : "";
			$this->saveOrders	= isset($this->settings['saveOrders']) ? $this->settings['saveOrders'] : "no";
			$this->autosave_address	= isset($this->settings['autosaveAddress']) ? $this->settings['autosaveAddress'] : "no";

			// Version 1.1.0 new fields:
			$this->reg_as   	= isset($this->settings['reg_as']) ? $this->settings['reg_as'] : "";
			$this->regtoifs		= isset($this->settings['regtoifs']) ? $this->settings['regtoifs'] : "";
			$this->addsku		= isset($this->settings['addsku']) ? $this->settings['addsku'] : "no";
			
			// Version 1.2.4 new fields:
			$this->lic_key 		= isset($this->settings['lic']) ? $this->settings['lic'] : "";

			// Version 1.2.5 new fields
			if(isset($this->settings['apperror'])) {
				$this->apperror = $this->settings['apperror'];
				$this->apperrormsg  = $this->settings['apperrormsg'];
			} else {
				$this->apperror = 0;
				$this->apperrormsg  = "";
			}

			// Version 1.4.1 new fields
			$this->overwriteBD 		= isset($this->settings['overwriteBD']) ? $this->settings['overwriteBD'] : "";
		}

		function recent_update_routines() {
			if(is_admin()) {   
				if($this->enabled == "yes") {
					// check last version
					$last_ver = get_option('infusedwoo_last_version');
					$perform_routines = false;

					if(!empty($last_ver)) {
						if(version_compare( INFUSEDWOO_PRO_VER, $last_ver, '>' )) {
							$perform_routines = true;
							update_option( "infusedwoo_last_version", INFUSEDWOO_PRO_VER );
						}
					} else {
						$perform_routines = false;
						update_option( "infusedwoo_last_version", INFUSEDWOO_PRO_VER );
					}

					if($perform_routines) {
						$this->ia_app_connect();
					}
				}
			}
		}

		function ia_woocommerce_notices() {	
			if(is_admin()) {     
				if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
					$wcs = 'wc-settings';
				else
					$wcs = 'woocommerce_settings';

				$this->load_settings();

				$app_name 	= $this->machine_name;
				$app_apikey = $this->apikey;

				if($this->enabled=='yes') {
					if(empty($app_name) || empty($app_apikey)) {
						echo '<div class="error"><p>'.sprintf(__('To enable infusionsoft, you need to <a href="%s">input</a> your Infusionsoft application name and API key.', 'woothemes'), admin_url('admin.php?page='.$wcs.'&tab=integration&section=infusionsoft')).'</p></div>';
					} else {
						if($this->apperror) {
							echo '<div class="error"><p><strong>' . sprintf(__('FATAL ERROR: Problem connecting to infusionsoft. Please <a href="%s">check your Infusionsoft API Credentials</a>. (%s)', 'woothemes'), admin_url('admin.php?page='.$wcs.'&tab=integration&section=infusionsoft'), $this->apperrormsg) . '</strong></p></div>';		
						}
					}
				}
			}
		}

		function installation_guide() {	
			if(is_admin() && $this->enabled != "yes") {     
				?>
				<div class="infusedwoo-alerts" style="width: 97%; height: 30px; padding: 4px; background-color: #293D67; margin-left: 18px; color: white; margin-top: 4px;">
					&nbsp;&nbsp;<img style="height: 30px; width: 115px;" src="<?php echo INFUSEDWOO_PRO_URL . "images/infusedwoo.png" ?>" />
					&nbsp;&nbsp;
					<span style="font-size: 11pt; position:relative; top: -10px;">
						<a href="<?php echo admin_url('admin.php?page=infusedwoo-menu-2&submenu=quick_install'); ?>" style="color: #94D020;">Click here</a>
						 to instantly enable InfusedWoo and access all its integration features</span>
				</div>
				<?php
			}
		}

		function ia_woocommerce_settings_link($links) { 
			if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
				$wcs = 'wc-settings';
			else
				$wcs = 'woocommerce_settings';


   		  $settings_link = '<a href="admin.php?page='.$wcs.'&tab=integration&section=infusionsoft">Settings</a>'; 
   		  $support_link = '<a href="http://infusedaddons.com/support" target="_blank">Support</a>'; 
   		  array_unshift($links, $support_link); 
		  array_unshift($links, $settings_link); 
		  return $links; 
		}

		function validate_settings_fields( $form_fields = false ) { 
			 if ( ! $form_fields )
				 $form_fields = $this->form_fields;

			 $this->sanitized_fields = array();
			 $this->sanitized_fields = $this->settings;
	 
			 foreach ( $form_fields as $k => $v ) {
				 if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] == 'text'; } // Default to "text" field type.
	 
				if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
					 $field = $this->{'validate_' . $v['type'] . '_field'}( $k );
					$this->sanitized_fields[$k] = $field;
				 } else {
					 $this->sanitized_fields[$k] = $this->settings[$k];
				 }
			 }
		 }

		public function admin_options() {
			if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
				$pgurl = admin_url('admin.php?page=wc-settings&tab=checkout&section=ia_woopaymentgateway');
			else
				$pgurl = admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&section=IA_WooPaymentGateway');
			?>
			<h3><?php _e('Infusionsoft','woothemes'); ?></h3>	    	
	    	<p><?php _e( 'Infusionsoft Integration Settings.', 'woothemes' ); ?></p>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->    	
	    	<p><?php _e( '<hr /><b>Tip 1: </b>You can trigger actions based on a specific product purchases, you will see these settings when you add/edit new product in wordpress. "Infusionsoft" tab will appear in the woocommerce tabs.', 'woothemes' ); ?></p>
	    	<?php
			echo sprintf(__('<p><b>Tip 2: </b>Customers can pay you through your Infusionsoft merchant account. <a href="%s">Click here to setup Infusionsoft as a payment gateway</a>.', 'woothemes'), $pgurl).'</p>';
			

		}

		 public function process_admin_options() {
	        $this->validate_settings_fields();
	        if(isset($_POST[$this->plugin_id . $this->id . '_infusedwoo_save'])) {
	          if ( count( $this->errors ) > 0 ) {
	              $this->display_errors();
	              return false;
	          } else {
	              update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
	              $this->settings = $this->sanitized_fields;
	              $this->ia_app_connect();
	              return true;
	          }
	      	}
	      }


		function init_form_fields() {
			$blogurl = get_bloginfo('url');	
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woothemes' ), 
								'label' => __( 'Enable Infusionsoft Integration', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Once enabled, woocommerce will be automatically integrated to infusionsoft to create/update contact record','woothemes' ), 
								'default' => 'no'
							), 
				'machinename' => array(
								'title' => __( 'Application Name', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Your Infusionsoft Application Name', 'woothemes' ), 
								'default' => ''
							), 
				'apikey' => array(
								'title' => __( 'API Key', 'woothemes' ), 
								'type' => 'password', 
								'description' => __( 'This is the API Key supplied by Infusionsoft.', 'woothemes' ), 
								'default' => ''
							),

	
				'regtoifs' => array(
								'title' => __( 'Send new user registration to infusionsoft?', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __("If checked, newly registered users will be sent to infusionsoft and new contact record will be created.<br>
														Alternatively, you can also process HTTP post in infusionsoft to <b>{$blogurl}?ia_fcn=reg.</b> 
														<a target=\"_blank\" href=\"http://infusedaddons.com/redir.php?to=regpostfcn\">
														Click here for more info</a>",'woothemes' ), 
								'default' => 'yes'
							),

				'reg_as' => array(
								'title' => __( 'Action Set # to Run After New User Registration', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( '<b>NOTE: </b> The action set will only be run when you check the "Send new user registration to infusionsoft?" setting above.', 'woothemes' ), 
								'default' => '0',
								'class' => "requirenum"
							),

							
				'success_as' => array(
								'title' => __( 'Action Set # to Run After Successful Purchase', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This action set will be triggered to the customer for every successful purchase.', 'woothemes' ), 
								'default' => '0',
								'class' => "requirenum"
							),
			
				'saveOrders' => array(
								'title' => __( 'Create invoices in infusionsoft for all payment gateways', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'If checked, invoices will be generated in infusionsoft for all payment methods. (Orders will be marked as paid if they pay using payment gateways other than infusionsoft.)','woothemes' ), 
								'default' => 'yes'
							),
				'autosaveAddress' => array(
								'title' => __( 'Update contact record in Infusionsoft when customers update their address.', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'If checked, contact record will be updated whenever customer edits their address in the wooocommerce my account page.','woothemes' ), 
								'default' => 'no'
							),
				'overwriteBD' => array(
								'title' => __( 'Do NOT overwrite Contact Info in infusionsoft with the most recent info.', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Check this if you don\'t want InfusedWoo to overwrite the Contact information of the existing contact in Infusionsoft','woothemes' ), 
								'default' => 'no'
							),
				'addsku' => array(
								'title' => __( 'Dynamic SKU Matching: Automatically add product in Infusionsoft', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'If woocommerce product is set to tie to infusionsoft product using SKU and if no such product in infusionsoft exists with same SKU with woocommerce product, the product will be automatically added to infusionsoft','woothemes' ), 
								'default' => 'no'
							),
				'lic' => array(
								'title' => __( 'InfusedWoo Purchase License Key', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Paste your license key here to allow plugin updates.', 'woothemes' ), 
								'default' => ''
							),
				'infusedwoo_save' => array(
								'type' => 'hidden', 
								'default' => 'yes'
							),

			);
			
	    }

	    // CORE FUNCTIONS

		function has_sub() {
			global $woocommerce;
			$has_sub = false;
			if(isset($woocommerce->cart->cart_contents)) {
				foreach($woocommerce->cart->cart_contents as $item) {
					$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
					if($ifstype == 'Subscription') {
						$has_sub = true;
						break;
					}			
				}
			}
			
			return $has_sub;
		}
				
			
		/**
	  	 * Infusionsoft Connector
	 	 **/

		function ia_app_connect() {
			if($this->appconnected) return true;

			$this->load_settings();

			$app_name 	= $this->machine_name;
			$app_apikey = $this->apikey;
			
			if(!empty($app_name) && !empty($app_apikey)) {
				$this->app = new iaSDK;
				try {
					$this->app->configCon($app_name, $app_apikey);				
					$checker = $this->app->dsGetSetting('Contact', 'optiontypes');
				
	
					//VALIDATE CREDENTIALS
					$pos = strrpos($checker, "ERROR");				
					if ($pos === false)  {
						$this->is_error = 0;
						if($this->apperror) {
							$this->settings['apperror'] = 0;
							$this->settings['apperrormsg'] = "";
							update_option( $this->plugin_id . $this->id . '_settings', $this->settings ); 
						}
						$this->appconnected = true;
						return true;						
					} else {
						$this->is_error = 1;
						$this->settings['apperror'] = 1;
						$this->settings['apperrormsg'] = $checker;
						update_option( $this->plugin_id . $this->id . '_settings', $this->settings );
						$this->appconnected = false;
						return false; 	
					}
				} catch(Exception $e) {
					$this->is_error = 1;
					$this->settings['apperror'] = 1;
					$this->settings['apperrormsg'] = $e->getMessage();
					update_option( $this->plugin_id . $this->id . '_settings', $this->settings );
					$this->appconnected = false;
					return false; 
				}		
			} else {
				$this->is_error = 1;
				$this->settings['apperror'] = 1;
				$this->settings['apperrormsg'] = __("ERROR: API Key or APP Name Missing", 'woothemes'); 
				update_option( $this->plugin_id . $this->id . '_settings', $this->settings );
				$this->appconnected = false;
				return false;	
			}
		}
	
		function ia_get_emails() {
			$count = $this->app->dsCount('Template', array('PieceType' => 'Email'));
			if($count > 6000) return 'exceed';

			$returnFields = array('Id','PieceTitle');
			$results = array();
			$page = 0;
			do {
				$bucket = $this->app->dsFind('Template',1000,$page,'PieceType','Email',$returnFields);
				if(is_array($bucket)) {
					$results = array_merge($results, $bucket);
					$page++;
				} else break;
			}
			while( count($bucket) == 1000 );
			return $results;
		}

		function ia_get_tags() {
			$count = $this->app->dsCount('ContactGroup', array('Id' => '%'));
			if($count > 6000) return 'exceed';

			$returnFields = array('Id','GroupName');
			$results = array();
			$page = 0;
			do {
				$bucket = $this->app->dsFind('ContactGroup',1000,$page,'Id','%',$returnFields);
				if(is_array($bucket)) {
					$results = array_merge($results, $bucket);
					$page++;
				} else break;
			}
			while( count($bucket) == 1000 );
			return $results;
		}
	  
		function ia_get_actions() {
			$count = $this->app->dsCount('ActionSequence', array('Id' => '%'));
			if($count > 6000) return 'exceed';

			$returnFields = array('Id','TemplateName');
			$results = array();
			$page = 0;
			do {
				$bucket = $this->app->dsFind('ActionSequence',1000,$page,'Id','%',$returnFields);
				if(is_array($bucket)) {
					$results = array_merge($results, $bucket);
					$page++;
				} else break;
			}
			while( count($bucket) == 1000 );
			return $results;
		}

		function ia_get_products() {
			sleep(0.1);
			$returnFields = array('Id','ProductName','ProductPrice');
			$results = array();
			$page = 0;			
			do {
				$bucket = $this->app->dsFind('Product',1000,$page,'Id','%',$returnFields);
				if(is_array($bucket)) {
					$results = array_merge($results, $bucket);
					$page++;
				} else break;
			}
			while( count($bucket) == 1000 );
			return $results;
		}
  		
		function ia_get_subs() {	
			sleep(0.1);
			$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
			$results = array();
			$page = 0;
			
			do {				
				$bucket = $this->app->dsFind('CProgram',1000,$page,'Id','%',$returnFields);
				if(is_array($bucket)) {
					$results = array_merge($results, $bucket);
					$page++;
				} else break;			
			} while( count($bucket) == 1000 );			
			
			return $results;		
		}

		function ia_get_sub_price($sub_id, $default_price) {
			if($this->ia_app_connect()) {
				$subplan = $this->app->dsLoad('SubscriptionPlan', (int) $sub_id, array('PlanPrice'));
				if( !empty($subplan['PlanPrice']) && $subplan['PlanPrice'] != 'E' ) return $subplan['PlanPrice'];
				else return $default_price;
			}			
		}

		function ia_get_creditcards($email="") {
			$current_user = wp_get_current_user();

			
			if(empty($email)) $log_email = $current_user->user_email;
			else $log_email = $email;

			$ccs = array();   
			
			if($this->ia_app_connect()) {
				$returnFields = array('Id');
				$contact = $this->app->dsFind('Contact',5,0,'Email',$log_email,$returnFields);
				if(is_array($contact) && count($contact) > 0) {
					$contact = $contact[0];
					$contactId = $contact['Id'];
				} else $contactId = 0;

				if(!empty($contactId)) {
					$returnFields = array('Id','Last4','ExpirationMonth','ExpirationYear','CardType' );
					$query = array('ContactId' => $contactId, 'Status'=> 3);
					$ccs = $this->app->dsQuery('CreditCard',1000,0,$query, $returnFields);					
				}
				
				return $ccs;
			}
		}

		function ia_woocommerce_update_product_options() {
			if($this->ia_app_connect()) {
				update_option('ia_app_data', array());
				$tags		= $this->ia_get_tags();
				$emails		= $this->ia_get_emails();
				$actions	= $this->ia_get_actions();		
				$products	= $this->ia_get_products();	
				$subs		= $this->ia_get_subs();		
				
				$app_data['tags'] 		= $tags;
				$app_data['emails'] 	= $emails;
				$app_data['actions'] 	= $actions;
				$app_data['products'] 	= $products;
				$app_data['subs']	 	= $subs;
				
				update_option('ia_app_data', $app_data);
			}
		}

	}



	function add_infusionsoft_integration( $methods ) {
		$methods[] = 'IA_Woocommerce'; return $methods;
	}
	
	add_filter('woocommerce_integrations', 'add_infusionsoft_integration' );
}	

function ia_woo_required() {
	echo '<div class="error"><p>'.sprintf(__('<b>InfusedWoo ERROR:</b> Woocommerce is not Installed. InfusedWoo requires Woocommerce. <a href="%s" target="_blank">Please install and activate Woocommerce.</a> ', 'woothemes'), 
		admin_url('plugin-install.php?tab=search&s=Woocommerce&plugin-search-input=Search+Plugins')).'</p></div>';
}

include(INFUSEDWOO_PRO_DIR . 'assets/countries.php');
