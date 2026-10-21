<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('woocommerce_product_write_panels', 'ia_woocommerce_options');
add_action('woocommerce_product_write_panel_tabs', 'ia_woocommerce_tab'); 
add_action( 'admin_enqueue_scripts', 'ia_searchable' );
add_action('save_post','ia_woocommerce_process_product', 10, 2 );

function ia_woocommerce_options() {
	global $post;
	global $iwpro;

	$allowsubs 		= false;			
	$pgenabled 		= isset($iwpro->settings['pgenabled']) ? $iwpro->settings['pgenabled'] : ""; 			
	if($pgenabled == 'yes') $allowsubs	= true;
	
	if(isset($_GET['ia']) && $_GET['ia'] == 'refresh') {
		$iwpro->ia_woocommerce_update_product_options();
	}
	
	$iwpro->ia_app_connect();		
	$app_data 	= get_option('ia_app_data');		

	$tags 		= $app_data['tags'];
	$emails 	= $app_data['emails'];	
	$actions 	= $app_data['actions'];	
	$products 	= $app_data['products'];	
	$subs 		= $app_data['subs'];				

	$tid = get_post_meta($post->ID, 'infusionsoft_tag', 	true);
	$eid = get_post_meta($post->ID, 'infusionsoft_email', 	true);
	$aid = get_post_meta($post->ID, 'infusionsoft_action', 	true);
	$pid = (int) get_post_meta($post->ID, 'infusionsoft_product', 	true);
	$sid = (int) get_post_meta($post->ID, 'infusionsoft_sub', 	true);			
	$trial = (int) get_post_meta($post->ID, 'infusionsoft_trial', 	true);	
	$sign_up_fee = (int) get_post_meta($post->ID, 'infusionsoft_sign_up_fee', 	true);	

	$sub_incl_disc = get_post_meta($post->ID, 'infusionsoft_sub_incl_disc', 	true);	
	$sub_incl_ship = get_post_meta($post->ID, 'infusionsoft_sub_incl_ship', 	true);	
	
	$tag_select			= array(
							'id' 			=> 'infusionsoft_tag[]', 
							'class'			=> 'chzn-select',
							'value' 		=> $tid, 
							'label' 		=> __('Tags to apply upon successful purchase', 'woothemes'),
							'desc_tip' 	=> __('If you want to apply more than one tags, create an action set in infusionsoft
												and use the action set option below.','woothemes'),
							'custom_attributes' => array('data-placeholder' => 'Type to select Tags...')
							);	
	$email_select		= array(
							'id' 			=> 'infusionsoft_email[]', 
							'class'			=> 'chzn-select',
							'value' 		=> $eid, 
							'label' 		=> __('Email Templates to Send upon successful purchase','woothemes'),
							'desc_tip' 	=> __('If you want to send more than one email template, create an action set in 		
													infusionsoft and use the action set option below.', 'woothemes'),
							'custom_attributes' => array('data-placeholder' => 'Type to select Email Templates...')
							);	
	$action_select		=  array(
							'id' 			=> 'infusionsoft_action[]', 
							'class'			=> 'chzn-select',
							'value' 		=> $aid, 
							'label' 		=> __('Action set to run upon successful purchase','woothemes'),
							'desc_tip' 	=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes'),
							'custom_attributes' => array('data-placeholder' => 'Type to select Action Sets...')
							);

	$product_select		=  array(
							'id' 			=> 'infusionsoft_product', 
							'class'			=> 'chzn-select',
							'value' 		=> $pid, 
							'label' 		=> __('Product being sold','woothemes'),
							'desc_tip' 		=> __('This will appear in the customer\'s invoice','woothemes')
							);
	$sub_select		=  array(									
							'id' 			=> 'infusionsoft_sub', 									
							'class'			=> 'chzn-select',									
							'value' 		=> $sid, 									
							'label' 		=> __('Select Subscription','woothemes'),									
							'desc_tip' 		=> __('Note that woocommerce price you set here will be ignored. It will use infusionsoft set price.','woothemes')
							);

	//$tag_select		['options']['0'] = __('Please select tag','woothemes');
	//$email_select	['options']['0'] = __('Please select email','woothemes');
	//$action_select	['options']['0'] = __('Please select action set','woothemes');
	$product_select	['options']['0'] = __('Search Infusionsoft Product using SKU setting','woothemes');						
	$sub_select		['options']['0'] = __('Please select subscription','woothemes');
	
	if(is_array($tags) && count($tags) > 0) {
		foreach($tags as $tag) {
			$value = $tag['Id'];
			$name = isset($tag['GroupName']) ? $tag['GroupName'] : "";
			$text = "{$name} [ {$tag['Id']} ]";
			$tag_select['options'][$value] = $text;
		}
	}

	if(is_array($emails) && count($emails) > 0) {
		foreach($emails as $email) {
			$value = $email['Id'];
			$name = isset($email['PieceTitle']) ? $email['PieceTitle'] : "";
			$text = "{$name} [ {$email['Id']} ]";
			$email_select['options'][$value] = $text;
		}
	}

	if(is_array($actions) && count($actions) > 0) {
		foreach($actions as $action) {
			$value = $action['Id'];
			$name = isset($action['TemplateName']) ? $action['TemplateName'] : "";
			$text = "{$name} [ {$action['Id']} ]";
			$action_select['options'][$value] = $text;
		}
	}

	if(count($products) > 0) {
		foreach($products as $product) {
			$value = $product['Id'];
			$name = isset($product['ProductName']) ? $product['ProductName'] : "";
			$text = "{$name} (" .'$' ." {$product['ProductPrice']}) [ {$product['Id']} ]";
			$product_select['options'][$value] = $text;
		}
	}			
	
	if(count($subs) > 0) {				
		foreach($subs as $sub) {					
			$value = $sub['Id'];										
			switch($sub['DefaultCycle']) {						
				case 1: $stringCycle = 'year'; break;						
				case 2: $stringCycle = 'month'; break;						
				case 3: $stringCycle = 'week'; break;						
				case 6: $stringCycle = 'day'; break;					
			}		
			
			$addS = '';					
			if($sub['DefaultFrequency'] > 1) $addS = 's';	
				
			$sub_price = $iwpro->ia_get_sub_price($value, $sub['DefaultPrice']);
			$text = "{$sub['ProgramName']} (" .'$' ." {$sub_price} every {$sub['DefaultFrequency']} {$stringCycle}{$addS}) [ {$sub['Id']} ]";	
			$sub_select['options'][$value] = $text;				
		
		}			
	}						
	?>
	<div id="infusionsoft_tab" class="panel woocommerce_options_panel">
	<div class="options_group ia-product" style="margin-bottom: 20px;">
	<?php				
	
		$ifstype 	= get_post_meta($post->ID, 'infusionsoft_type', true);									
		if($allowsubs) {					
			$type_select =  array(
				'id' 			=> 'infusionsoft_type',
				'class'			=> 'chzn-select',
				'value' 		=> $ifstype,
				'label' 		=> __('Product or Subscription?','woothemes'),
				'desc_tip' 		=> __('Select if you are selling a product or a subscription.','woothemes'),
				'options'		=> array('Product' => 'Product', 'Subscription' => 'Subscription')
				);

			woocommerce_wp_select( $type_select );											
			woocommerce_wp_select( $sub_select );
			
			$trial_input = array(
				'id' 			=> 'infusionsoft_trial',
				'value' 		=> $trial,
				'label' 		=> __('Number of Trial Days','woothemes'),
				'desc_tip' 		=> __('Number of days until infusionsoft starts charging','woothemes'),	
			);

			$sign_up_input = array(
				'id' 			=> 'infusionsoft_sign_up_fee',
				'value' 		=> $sign_up_fee,
				'label' 		=> __('Sign Up Fee' ,'woothemes') . ' (' . get_woocommerce_currency_symbol() . ')',
				'desc_tip' 		=> __('Sign Up Fee for trying the subscription.','woothemes'),	
				'data_type'		=> 'Price'
			);


			woocommerce_wp_text_input($trial_input);
			woocommerce_wp_text_input($sign_up_input);

			

			echo "<div class=\"calc-rec\"><hr><h3 style='margin-top: 15px;'>Calculation of Recurring Amount</h3>";

			
			woocommerce_wp_checkbox(array(
					'id' 			=> 'infusionsoft_sub_incl_disc',
					'value' 		=> $sub_incl_disc != "no" ? "yes" : "no",
					'label'			=> __('Include Discounts' ,'woothemes'),
					'description' 	=> __('Check to Include discounts in the Recurring Price (if present)' ,'woothemes'),
				));

			woocommerce_wp_checkbox(array(
					'id' 			=> 'infusionsoft_sub_incl_ship',
					'value' 		=> $sub_incl_ship != "no" ? "yes" : "no",
					'label'			=> __('Include Shipping' ,'woothemes'),
					'description' 	=> __('Check to Include shipping fee in the Recurring Price (if shipping is present)' ,'woothemes'),
				));

			echo "</div>";

		}	
		
		woocommerce_wp_select( $product_select );
		echo '</div><div class="options_group" style="margin-bottom: 20px;">';

		if(is_array($tags)) {
			infusedwoo_wp_select_multiple( $tag_select );
		} else {
			$tag_select['label'] .= ' <br>' . __('(Enter Tag ID)','woothemes');
			$tag_select['class'] = 'short';
			$tag_select['options'] = null;
			woocommerce_wp_text_input($tag_select);
		}

		if(is_array($emails)) {
			infusedwoo_wp_select_multiple( $email_select );
		} else {
			$email_select['label'] .= ' <br>' . __('(Enter Template ID)','woothemes');
			$email_select['class'] = 'short';
			$email_select['options'] = null;
			woocommerce_wp_text_input($email_select);
		}
		
		if(is_array($actions)) {
			infusedwoo_wp_select_multiple( $action_select );
		} else {
			$action_select['label'] .= ' <br>' . __('(Enter Action Set ID)','woothemes');
			$action_select['class'] = 'short';
			$action_select['options'] = null;
			woocommerce_wp_text_input($action_select);
		}

		
	?>
	<a href="<?php echo (isset($_GET['ia']) && $_GET['ia'] == 'refresh') ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'] . '&ia=refresh'; ?>" style="margin: 10px;">
		<?php echo __("Can't find a specific product, action, tag or template? Click here to Refresh", "woothemes"); ?></a>
	</div>
	<div class="options_group ia-woosubs" style="margin-bottom: 20px;">
		<?php
			 

			if(is_array($actions)) {
				 woocommerce_wp_select(array(
							'id' 			=> 'infusionsoft_sub_activated', 
							'class'			=> 'chzn-select',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_activated', 	true), 
							'label' 		=> __('Action to run when Subscription is <b>Activated</b>','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes'),
							'options' 		=> $action_select['options']
							));
			} else {
				woocommerce_wp_text_input(array(
							'id' 			=> 'infusionsoft_sub_activated', 
							'class'			=> 'short',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_activated', 	true), 
							'label' 		=> __('Action to run when Subscription is <b>Activated</b><br>(Enter Action Set ID)','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes')
							));
			}

			if(is_array($actions)) {
				 woocommerce_wp_select(array(
							'id' 			=> 'infusionsoft_sub_cancelled', 
							'class'			=> 'chzn-select',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_cancelled', 	true), 
							'label' 		=> __('Action to run when Subscription is <b>Cancelled</b>','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes'),
							'options' 		=> $action_select['options']
							));
			} else {
				woocommerce_wp_text_input(array(
						'id' 			=> 'infusionsoft_sub_cancelled', 
						'class'			=> 'short',
						'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_cancelled', 	true), 
						'label' 		=> __('Action to run when Subscription is <b>Cancelled</b><br>(Enter Action Set ID)','woothemes'),
						'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
												option.','woothemes')
						));

			}

			if(is_array($actions)) {
				 woocommerce_wp_select(array(
							'id' 			=> 'infusionsoft_sub_on-hold', 
							'class'			=> 'chzn-select',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_on-hold', 	true), 
							'label' 		=> __('Action to run when Subscription is <b>Set to On-Hold</b>','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes'),
							'options' 		=> $action_select['options']
							));
			 } else {
			 	woocommerce_wp_text_input(array(
							'id' 			=> 'infusionsoft_sub_on-hold', 
							'class'			=> 'short',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_on-hold', 	true), 
							'label' 		=> __('Action to run when Subscription is <b>Set to On-Hold</b><br>(Enter Action Set ID)','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes')
							));
			 }

			 if(is_array($actions)) {
				 woocommerce_wp_select(array(
							'id' 			=> 'infusionsoft_sub_expired', 
							'class'			=> 'chzn-select',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_expired', 	true), 
							'label' 		=> __('Action to run when Subscription <b>Expires</b>','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes'),
							'options' 		=> $action_select['options']
							));
			 } else {
			 	 woocommerce_wp_text_input(array(
							'id' 			=> 'infusionsoft_sub_expired', 
							'class'			=> 'short',
							'value' 		=> get_post_meta($post->ID, 'infusionsoft_sub_expired', 	true), 
							'label' 		=> __('Action to run when Subscription <b>Expires</b><br>(Enter Action Set ID)','woothemes'),
							'desc_tip' 		=> __('You can start follow up sequence, subscriptions, send HTTP post, etc using this
													option.','woothemes')
							));
			 }

		?>
	<a href="<?php echo (isset($_GET['ia']) && $_GET['ia'] == 'refresh') ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'] . '&ia=refresh'; ?>" style="margin: 10px;">
		<?php echo __("Can't find a specific action set? Click here to Refresh", "woothemes"); ?></a>
	</div>
	</div>						
	<?php if($allowsubs) { ?>			
		<script>							
		jQuery('.infusionsoft_sub_field').hide();
		
		var ifstype = jQuery('select#infusionsoft_type').val();	
		if(ifstype == 'Subscription') {	
			jQuery('.infusionsoft_sub_field').show();
			jQuery('.infusionsoft_trial_field').show();
			jQuery('.infusionsoft_product_field').hide();
			jQuery('.calc-rec').show();	

			if(parseInt(jQuery('#infusionsoft_trial').val()) > 0) {
				jQuery('.infusionsoft_sign_up_fee_field').show();	
			}
		} else {
			jQuery('.infusionsoft_sub_field').hide();
			jQuery('.infusionsoft_trial_field').hide();
			jQuery('.infusionsoft_product_field').show();
			jQuery('.infusionsoft_sign_up_fee_field').hide();

			jQuery('.calc-rec').hide();	
		}								
		
		jQuery('select#infusionsoft_type').change(function() {
			var ifstype = jQuery('select#infusionsoft_type').val();
			if(ifstype == 'Subscription') {						
				jQuery('.infusionsoft_sub_field').show();
				jQuery('.infusionsoft_trial_field').show();
				jQuery('.infusionsoft_product_field').hide();	
				jQuery('.calc-rec').show();					
			} else {
				jQuery('.infusionsoft_sub_field').hide();
				jQuery('.infusionsoft_trial_field').hide();
				jQuery('.infusionsoft_product_field').show();
				jQuery('.calc-rec').hide();	
			}				
		});

		jQuery('#infusionsoft_trial').keyup(function() {
				if(parseInt(jQuery(this).val()) > 0) {
					jQuery('.infusionsoft_sign_up_fee_field').show();
				} else {
					jQuery('.infusionsoft_sign_up_fee_field').hide();
				}
			});
		</script>
	<?php	} ?>
	<script>
		jQuery('[name=product-type]').change( function() {
		var product_type = jQuery(this).val();

		if(product_type == 'subscription' || product_type == 'variable-subscription') {
			jQuery('.ia-product').hide();
			jQuery('.ia-woosubs').show();
		} else {
			jQuery('.ia-product').show();
			jQuery('.ia-woosubs').hide();
		}
	});
	</script>
	<?php
}

function ia_woocommerce_tab() {
	?>
	<li class="custom_tab linked_product_options"><a href="#infusionsoft_tab"><?php _e('Infusionsoft', 'woothemes'); ?></a></li>
	<?php
}		

function ia_searchable($hook) {			
	global $woocommerce;

	$showto = array(
			"woocommerce_page_woocommerce_settings",
			"post.php",
			"post-new.php",
			"product_page_ia_custom_fields"
		);

	if(!in_array($hook, $showto)) return;

	$type = isset($_GET['post']) ? get_post_type( $_GET['post'] ) : false;
	if(!$type) $type = isset($_GET['post_type']) ? $_GET['post_type'] : "";

	if($type == 'product') {
		wp_enqueue_style( 'ia_admin_styles', (INFUSEDWOO_PRO_URL . 'assets/custom.css') ); 
		wp_enqueue_script( 'ia_searchable', (INFUSEDWOO_PRO_URL . 'assets/chosen_v1.3.0/chosen.jquery.min.js'), array('jquery') ); 
	    wp_enqueue_script( 'ia_admin_scripts', (INFUSEDWOO_PRO_URL . 'assets/admin_scripts.js'), array('ia_searchable') ); 
	    wp_enqueue_style( 'ia_searchable_styles', (INFUSEDWOO_PRO_URL . 'assets/chosen_v1.3.0/chosen.min.css') );
  	    wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );
	}

	if('product_page_ia_custom_fields' != $hook ) return;
	wp_enqueue_script( 'ia_custfield_scripts', (INFUSEDWOO_PRO_URL . 'assets/admin_custfields.js'), array('jquery')  ); 			
}		

function ia_woocommerce_process_product( $post_id, $post = null  ) {
	global $woocommerce; 
	global $iwpro;

	if ( $post->post_type == "product" ) {
		if(isset($_POST['infusionsoft_type']) && $_POST['infusionsoft_type'] == 'Subscription') {
			if($_POST['product-type'] == 'variable') wp_die(__('Sorry but subscriptions don\'t work with variable products for now..','woothemes'));		
			if(empty($_POST['infusionsoft_sub'])) wp_die(__('You need to select a subscription plan. Click back button.','woothemes'));		
			
			if($iwpro->ia_app_connect()) {		
				$sid = (int) $_POST['infusionsoft_sub'];				
				$returnFields = array('DefaultPrice');
				$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);

				$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);			
				update_post_meta( $post_id, '_regular_price', $sub_price);
			}
		}


		if(isset($_POST['infusionsoft_tag'])) 	update_post_meta( $post_id, 'infusionsoft_tag', 	$_POST['infusionsoft_tag']);
		else update_post_meta( $post_id, 'infusionsoft_tag', array());

		if(isset($_POST['infusionsoft_email'])) update_post_meta( $post_id, 'infusionsoft_email', 	$_POST['infusionsoft_email']);
		else update_post_meta( $post_id, 'infusionsoft_email', 	array());

		if(isset($_POST['infusionsoft_action'])) update_post_meta( $post_id, 'infusionsoft_action', 	$_POST['infusionsoft_action']);
		else update_post_meta( $post_id, 'infusionsoft_action', 	array());
		
		if(isset($_POST['infusionsoft_product'])) update_post_meta( $post_id, 'infusionsoft_product', $_POST['infusionsoft_product']);								
		if(isset($_POST['infusionsoft_sub'])) update_post_meta( $post_id, 'infusionsoft_sub', $_POST['infusionsoft_sub']);								
		if(isset($_POST['infusionsoft_type'])) update_post_meta( $post_id, 'infusionsoft_type', $_POST['infusionsoft_type']);
		if(isset($_POST['infusionsoft_trial'])) update_post_meta( $post_id, 'infusionsoft_trial', $_POST['infusionsoft_trial']);
		if(isset($_POST['infusionsoft_sign_up_fee'])) update_post_meta( $post_id, 'infusionsoft_sign_up_fee', $_POST['infusionsoft_sign_up_fee']);
		
		if(isset($_POST['infusionsoft_sub_incl_disc'])) update_post_meta( $post_id, 'infusionsoft_sub_incl_disc', $_POST['infusionsoft_sub_incl_disc']);
		else update_post_meta( $post_id, 'infusionsoft_sub_incl_disc', "no");

		if(isset($_POST['infusionsoft_sub_incl_ship'])) update_post_meta( $post_id, 'infusionsoft_sub_incl_ship', $_POST['infusionsoft_sub_incl_ship']);
		else update_post_meta( $post_id, 'infusionsoft_sub_incl_ship', "no");

		if(isset($_POST['infusionsoft_sub_activated'])) update_post_meta( $post_id, 'infusionsoft_sub_activated', 	$_POST['infusionsoft_sub_activated']);
		if(isset($_POST['infusionsoft_sub_cancelled'])) update_post_meta( $post_id, 'infusionsoft_sub_cancelled', 	$_POST['infusionsoft_sub_cancelled']);
		if(isset($_POST['infusionsoft_sub_on-hold'])) update_post_meta( $post_id, 'infusionsoft_sub_on-hold', 	$_POST['infusionsoft_sub_on-hold']);
		if(isset($_POST['infusionsoft_sub_expired'])) update_post_meta( $post_id, 'infusionsoft_sub_expired', 	$_POST['infusionsoft_sub_expired']);

	}
}

function infusedwoo_wp_select_multiple( $field ) {
    global $thepostid, $post, $woocommerce;

     // Custom attribute handling
	  $custom_attributes = array();

	  if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {

	    foreach ( $field['custom_attributes'] as $attribute => $value ){
	      $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
	    }
	  }


    $thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
    $field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
    $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
    $field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
    $field['value']         = isset( $field['value'] ) ? $field['value'] : ( get_post_meta( $thepostid, $field['id'], true ) ? get_post_meta( $thepostid, $field['id'], true ) : array() );

    echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" multiple="multiple" >';
    foreach ( $field['options'] as $key => $value ) {

        echo '<option value="' . esc_attr( $key ) . '" ' . (( (is_array($field['value']) && in_array( $key, $field['value'])) || (!is_array($field['value']) && $key == $field['value'])) ? 'selected="selected"' : '' ) . '>' . esc_html( $value ) . '</option>';

    }

    echo '</select> ';

    if ( ! empty( $field['description'] ) ) {

        if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
            echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( WC()->plugin_url() ) . '/assets/images/help.png" height="16" width="16" />';
        } else {
            echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
        }

    }
    echo '</p>';
}



