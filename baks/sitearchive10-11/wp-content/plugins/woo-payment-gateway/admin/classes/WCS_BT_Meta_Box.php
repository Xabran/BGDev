<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Static class used to add html to meta boxes required by admin configuration.
 * @author Clayton Rogers
 *
 */
class WCS_BT_Meta_Box{
	
	/**
	 * Setup all filters for functionality. 
	 */
	public static function init(){
		if(BT_Manager()->isActive('braintree_subscriptions')){
			if(BT_Manager()->subscriptionsActive()){
				add_action('woocommerce_subscriptions_product_options_pricing', __CLASS__.'::doSubscriptionOutput', 99 );
				add_action( 'woocommerce_product_after_variable_attributes', __CLASS__.'::doVariableSubscriptionOutput', 10, 3 );
			}
			else add_action('woocommerce_product_options_general_product_data', __CLASS__.'::doSubscriptionOutput', 15 );
		
			add_action( 'woocommerce_product_options_general_product_data', __CLASS__.'::doDynamicDescriptorsOutput', 1 );
		}
	}
	
	/**
	 * Output the Braintree Plan ID's to the WooCommerce product box. 
	 */
	public static function doSubscriptionOutput(){
		global $thepostid, $post;
		?>
		<div class="options_group braintree_plans_options">
		   <h3><?php echo __('Braintree Subscription Config', 'braintree')?></h3>
		
		<?php
		woocommerce_wp_checkbox(array(
				'label'=>__('Sell As Subscription', 'braintree'),
				'name'=>'braintree_subscription',
				'id'=>'braintree_subscription',
				'cbvalue'=>'yes',
				'desc_tip'=>true,
				'description'=>__('If you want this product to be sold as a subscription, then click the checkbox and select the planId(s) associated with the subsciption.', 'braintree')
		));
	
		
		/* commented for now 4/13/2016. The admin should not select the subscription length because that is determined by the Braintree Plan. Issues could
		 * arise if an admin set a Braintree Plan for USD that billed every month and another plan for GBP that billed every other month. If the this setting were
		 * enabled, you couldn't determine the number of billing cycles.
		 * woocommerce_wp_select(array(
				'name'=>'_subscription_length',
				'id'=>'_subscription_length',
				'class'=>'form-field',
				'label'=>__('Subscription Length', 'braintree'),
				'options'=>wc_braintree_get_subscription_lengths(),
				'description'=>__('The subscription length is how long the subscription will bill.', 'worldpayus'),
				'desc_tip'=>true
		)); */
		
		?>
		<div class="div_braintree_plans">
		<?php
		
		$plans = WC_Braintree_Subscriptions::getBraintreePlans();
		echo '<p class="form-field"><label for="braintree_subscriptions['.$post->ID.']">' . __('Brainree Plan ID\'s', 'braintree') . '</label>';
		echo '<select id="braintree_subscriptions['.$post->ID.']" class="form-field">';
		foreach( $plans as $plan ){
			echo '<option value="' . $plan->id . '" currency="' . $plan->currencyIsoCode . '">' . $plan->id . ' - ' . $plan->currencyIsoCode . '</option>';
		};
		echo '</select>';
		echo wc_help_tip( __('In order to assign a Braintree Plan Id to the product, you must first configure
				a recurring plan inside your Braintree account. You can assign multiple plans to each product because Braintree plans are created using an assigned currency. By
			    adding multiple Braintree Plans, you can use a currency switcher to ensure the proper plan is assigned to the subscription order.', 'braintree') );
		
		echo '<button class="button add_braintree_plan" post_id="' . $post->ID . '" class="button">' .  __('Add Plan', 'braintree') . '</button>';
		echo '</p>';
		
		self::doBraintreeSavedPlansOutput( $post->ID );
		
		?>
		
		
		</div>
		</div>
		
		<?php
	}
	

	/**
	 * Output the Braintree Plan ID's to the WooCommerce variable product box.
	 */
	public static function doVariableSubscriptionOutput( $loop, $variation_data, $variation ){
		?>
		<div class="options_group braintree_plans_options">
		   <h3><?php echo __('Braintree Subscription Config', 'braintree')?></h3>
		
		<?php
		woocommerce_wp_checkbox(array(
				'label'=>__('Sell As Subscription', 'braintree'),
				'name'=>'braintree_subscription['.$loop.']',
				'id'=>'braintree_subscription['.$loop.']',
				'cbvalue'=>'yes',
				'desc_tip'=>true,
				'value'=>get_post_meta( $variation->ID, 'braintree_subscription', true ),
				'description'=>__('If you want this product to be sold as a subscription, then click the checkbox and select the planId associated with the subsciption.', 'braintree')
		));
	
		?>
		<div class="div_braintree_plans">
		<?php
		
		$plans = WC_Braintree_Subscriptions::getBraintreePlans();
		echo '<p class="form-field"><label for="braintree_subscriptions['.$variation->ID.']">' . __('Brainree Plan ID\'s', 'braintree') . '</label>';
		echo '<select id="braintree_subscriptions['.$variation->ID.']" class="form-field">';
		foreach( $plans as $plan ){
			echo '<option value="' . $plan->id . '" currency="' . $plan->currencyIsoCode . '">' . $plan->id . ' - ' . $plan->currencyIsoCode . '</option>';
		};
		echo '</select>';
		echo wc_help_tip( __('In order to assign a Braintree Plan Id to the variable subscription, you must first configure
				a recurring plan inside your Braintree account. You can assign multiple plans to each product because Braintree plans are created using an assigned currency. By
			    adding multiple Braintree Plans, you can use a currency switcher to ensure the proper plan is assigned to the subscription order.', 'braintree') );
		
		echo '<button class="button add_braintree_plan" class="button" post_id="' . $variation->ID . '">' .  __('Add Plan', 'braintree') . '</button>';
		echo '</p>';
		
		self::doBraintreeSavedPlansOutput( $variation->ID );
		?>
		
		
		</div>
		</div>
		
		<?php
	}
	
	public static function doBraintreeSavedPlansOutput( $post_id ){
		$saved_plans = get_post_meta( $post_id, 'braintree_plans', true );
		?>
		<div class="saved_braintree_plans">
		<div class="blockUI blockOverlay addPlanOverlay"></div>
		<h4><?php echo __('Saved Plans', 'braintree')?></h4>
		<?php 
		if( ! empty( $saved_plans ) ){
			foreach( $saved_plans as $currency=>$plan ){?>
					<p class="saved_braintree_plan"><label><?php echo sprintf(__('Braintree Plan (%s)', 'braintree'), $currency )?></label><span><?php echo $plan?></span><span><a currency="<?php echo $currency?>" post_id="<?php echo $post_id?>" class="remove_row remove_braintree_plan" href="#"><?php echo __('remove', 'braintree')?></a></span></p>
			<?php }
				
		}?>
		</div>
		<?php
	}
	
	public static function doDynamicDescriptorsOutput( $post_id = null ){
		global $post;
		
		if( !$post_id ){
			$post_id = $post->ID;
		}
		$value = get_post_meta( $post_id, 'dynamic_descriptor_name', true );
		
		$value = ! empty( $value ) ? $value : '';
		
		$tip = __( 'Dynamic descriptors allow you to control what appears on a customers credit card statement on a per transaction basis. The following rules must be follwed when adding a descriptor:
						<div><ul><li>Comprised of a business name and product name, separated by an asterisk (*).
						<li>Business name must be either 3, 7, or 12 characters exactly; product descriptor can be up to 18, 14, or 9 characters respectively (with an \* in between for a total of 22 characters) If the company name plus the product name and asterick does not add up to
						22 characters, that is fine. The plugin will automatically add spaces for a total length of 22 characters.</li>
						<li>Can contain special characters . + - </li>
						<li>Can contain lower and upper case</li>
						<li>Can contain spaces, but cannot start with a space</li></ul></div>', 'braintree' );
		$note = '<div><h4>' .__( 'Braintree Subscriptions', 'braintree' ). '</h4><div>' . __( 'If you have enabled Braintree Subscriptions, then you can set a unique descriptor for each item. Each Braintree subscription that is created can have its own
				unique dynamic descriptor. You cannot have unique descriptors for regular WooCommerce products because an order can contain multiple products and it would be impossible to choose which descriptor to use.') . '</div></div>';
		
		$examples = '<div><h4>' . __('Examples:', 'braintree') . '</h4><div>' . __('cool_company*towels   ', 'braintree') . '</div><div>' . __('PaymntPlugin*braintree', 'braintree') . '</div></h4></div>';
		
		echo '<div id="dynamic_descriptor_tutorial_name" class="dynamic_descriptor_tutorial"><div class="explanation"><h4>' . __('Dynamic Descriptors Explanation', 'braintree') . '</h4><span class="close">' . __('Close', 'braintree') . '</span>' . $tip . $note . $examples . '</div></div>';
		
		echo '<p class="form-field"><label for="dynamic_descriptor_name">' . __('Dynamic Descriptor Name', 'braintree' ) . '</label><input id="dynamic_descriptor_name" name="dynamic_descriptor_name" type="text" maxlength="22" value="' . $value . '"/><span tutorial="_name" class="dynamic_descriptor_help">' . __( 'help', 'braintree' ) . '</span></p>';
	}
}
add_action('admin_init', 'WCS_BT_Meta_Box::init');
?>