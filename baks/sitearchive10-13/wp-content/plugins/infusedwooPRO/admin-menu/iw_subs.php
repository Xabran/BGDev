<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h1>Setting up Subscriptions in InfusedWoo</h1>
<hr>
	
<?php if(!(isset($iwpro->settings['pgenabled']) && $iwpro->settings['pgenabled'] == 'yes')) { ?>
	<div style="color:red; margin: 20px 0; padding: 20px; border: 1px dashed #999;">Note: InfusedWoo subscriptions requires Infusionsoft Payment Gateway.
		Please enable Infusionsoft Payment Gateway first to use this feature of InfusedWoo. 

		<br><br><br>
		<center>
		<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=ia_woopaymentgateway');?>">
		<div class="big-button">Configure Infusionsoft Gateway
		</div>
		</a>
		</center>
		<br>
	</div>
<?php } ?>

	In InfusedWoo, you can set your woocommerce products as subscriptions. 
	When you do this, the woocommerce product will be tied up to an infusionsoft subscription and when purchased it will start a subscription in Infusionsoft. 
<br><br>
	Follow the steps below to setup your subscriptions in woocommerce. 

<h2>Steps</h2>

<b>1. Add Subscription Plans to your Infusionsoft Products</b>
<br><br>
In infusionsoft, go to Ecommerce → Products. Then go to the "Subscription Plans" tab as shown above. Add a subscription here by entering the billing frequency, billing cycle and plan price. Once done, hit "Save".
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-20-17-11/Screen-Shot-2014-09-25-20-17-11.png" style="max-width: 100%;" />
<br><br>

<b>2. Add Subscription Plans to your Infusionsoft Products</b>
<br><br>
When editing or adding a new product, go to Product Data → Infusionsoft Tab. 
In the "Product or Subscription?" dropdown, select "Subscription". 
Then select the subscription you want to use for this product. If the subscription plan doesn't appear in the dropdown, simply click the refresh link at the bottom. 
<br><br>
You can also configure the subscription to have trial days.
</b>
<br><br>
<img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/Screen-Shot-2014-09-25-20-23-05/Screen-Shot-2014-09-25-20-23-05.png" style="max-width: 100%;" />
