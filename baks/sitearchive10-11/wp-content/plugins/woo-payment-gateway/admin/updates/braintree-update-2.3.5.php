<?php
/**
 * File for updating data for version 2.3.5.
 */
$products = get_posts(array(
		'posts_per_page'=>-1,
		'post_type'=>'product'
));

$updated = false;

if( $products ){
	
	$braintree_plans = BT_Manager()->getBraintreePlans();
	
	if( !empty( $braintree_plans ) ){
		
		foreach( $products as $post ){
			$braintree_plan_id = get_post_meta( $post->ID, 'braintree_subscription_id', true );
			if( $braintree_plan_id ){
				
				$plan = BT_Manager()->getBraintreePlan( $braintree_plan_id, $braintree_plans );
				
				if( $plan ){
					$plans = array(
							$plan->currencyIsoCode => $plan->id
					);
					
					update_post_meta( $post->ID, 'braintree_plans', $plans );
					
					delete_post_meta( $post->ID, 'braintree_subscription_id' );
					
					BT_Manager()->log->writeToLog( sprintf( 'Braintree plans for product %s have 
							been updated to the new format.', $post->ID ) );
					$updated = true;
				}
			}
		}
		add_action( 'admin_notices', function(){
			?>
			<div class="notice notice-success">
			  <p><?php echo sprintf( __('Braintree For WooCommerce: version 2.3.5 added improvements to the subscription integration. Please check your products as a precaution to ensure all 
			  		of your configurations are the same.', 'braintree') )?></p>
			</div>
			<?php
		});
	}
}