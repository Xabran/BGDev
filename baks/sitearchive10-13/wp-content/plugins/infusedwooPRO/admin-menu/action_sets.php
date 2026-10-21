<?php 

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	if($_POST) {
		if(isset($_POST['upd-success-as'])) {
			if(isset($iwpro->settings)) {
				$settings = $iwpro->settings;
			} else {
				$settings = array();
			}

			$settings['success_as'] = (int) $_POST['upd-success-as'];


			update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
			$iwpro->settings = $settings;
			$iwpro->success_as = (int) $_POST['upd-success-as'];
		}
	}
?>

<h1>Automation via Action Sets</h1>
<hr>

<h4 id="case-1-running-an-action-when-a-particular-product-is-purchased">Case 1: Running an Action when a particular product is purchased.</h4>
<p>When editing a woocommerce product, you can setup different actions to be triggered when a product is purchased.</p>
<p>You can set this up in Product Data → Infusionsoft Tab when you&#39;re currently editing or adding a product. (See screenshot below).</p>
<p><img src="https://mjtokyo.s3-ap-northeast-1.amazonaws.com/infusionsoft_tab/infusionsoft_tab.png" alt="Infusionsoft Tab" style="width: 100%"></p>
<p>Here, you can set a tag to be applied, email template to be sent or action set to be applied when the contact purchases the product.</p>
<p>If you need to run other actions like set up appointment, run follow up sequence, etc, you can always create an action set first and set this action set in the product settings.</p>
<p>For more information on action sets and how to set this up, you can refer to infusionsoft&#39;s documentation on this <a href="http://ug.infusionsoft.com/article/AA-00861/0/What-is-an-action-set-Legacy.html" target="_blank">Click for more info on Action Sets.</a></p>
<h4 id="case-2-run-an-action-when-any-product-is-purchased">Case 2: Run an action when <em>any</em> product is purchased</h4>
<p>You can run an action set when a woocommerce purchase is made regardless to what product they have purchased. To do this, make sure to create first an <a href="http://ug.infusionsoft.com/article/AA-00861/0/What-is-an-action-set-Legacy.html" target="_blank">action set</a> inside infusionsoft and remember the action set ID.
And then enter the Action Set ID below and hit "Save".</p>

<br>
<div class="big-row">
	<form method="POST">
			<label style="width: 200px;">Action Set # to Run After Successful Purchase</label>
			<input style="width: 90px;" name="upd-success-as" type="text" value="<?php echo isset($iwpro->success_as) ? $iwpro->success_as : ""; ?>" />
			<input type="submit" class="next-button" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
	</form>
<br>
</div>