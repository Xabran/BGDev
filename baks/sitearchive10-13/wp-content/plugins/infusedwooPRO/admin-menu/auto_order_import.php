<?php 

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	if($_POST) {
		if(isset($_POST['http_order_status'])) {
			if(isset($iwpro->settings)) {
				$settings = $iwpro->settings;
			} else {
				$settings = array();
			}

			$settings['http_order_status'] = $_POST['http_order_status'];


			update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
			$iwpro->settings = $settings;
			$iwpro->http_order_status =  $_POST['http_order_status'];
		}
	}
?>

<h1>Infusionsoft to Woocommerce Order Auto Import</h1>
<hr>

<p>InfusedWoo automatically imports orders from Woocommerce to Infusionsoft but not the other way. To automatically import from Infusionsoft to Woocommerce, the following steps must be followed. HTTP Post functionality of Infusionsoft will be used: </p>

<h4>1. Add a new Billing Automation Trigger</h4>
Infusionsoft, go to Ecommerce --> Settings --> Billing Automation. Then set the trigger to "After a successful purchase in the shopping cart or Order Form"<br>
Then click "Add Trigger" afterwards.

<br><br>
<img src="https://s3.amazonaws.com/infusedaddons/screenshots/Billing_Automation_2016-05-24_18-24-52.png" style="width: 100%" />
<br><br>

<h4>2. Configure the Billing Automation Trigger</h4>
A new window will appear. Add a new action "Send HTTP Post to another server" and enter this URL:<br><br>
<center>
<b>
	<?php 
	$key = substr($iwpro->apikey, 0,6);
	echo admin_url( 'admin-ajax.php?action=iw_create_wcorder&key=' . $key);?>
</b>
</center>
<br><br>
Make sure you don't copy white spaces. When done, save the action, then save the trigger.

<h4>3. Configure the Woocommerce Order Status</h4>
By default, orders are flagged with "processing" status in woocommerce. If you want to change the status, you may change it below.

<br><br>
<div class="big-row">
	<form method="POST">
			<label style="width: 200px;">Woocommerce Order Status</label>
			<?php
					$terms = get_terms('shop_order_status');
					$val = isset($iwpro->settings['http_order_status']) ? $iwpro->settings['http_order_status'] : 'wc-processing';

					echo '<select name="http_order_status">';
					if(!is_array($terms) || count($terms) == 0) {
						$terms = wc_get_order_statuses();
						
						foreach ( $terms as $k => $term ) {
							$selected = $k == $val ? ' selected ' : '';
							echo '<option value="' . esc_attr( $k ) . '"' . $selected;
							echo '>' . esc_html__( $term, 'woocommerce' ) . '</option>';
						}
					} else {
						foreach ( $terms as $term ) {
							$selected = esc_attr( $term->slug )  == $val ? ' selected ' : '';
							echo '<option class="wstat iw-checkbox" value="' . esc_attr( $term->slug ) . '"'  . $selected;
							echo '>' . esc_html__( $term->name, 'woocommerce' ) . '</option>';
						}
					}
					echo '</select>';
				?>
			<input type="submit" class="next-button" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
	</form>
<br>
</div><br><br>
That's it, and when orders are created in Infusionsoft, they will be automatically sent to woocommerce. If you want to disable this feature in the future.
Simply remove the billing automation trigger in Infusionsoft.