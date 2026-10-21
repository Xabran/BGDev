<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}


	if($_POST) {
		if(isset($_POST['upd-reg-as'])) {
			if(isset($iwpro->settings)) {
				$settings = $iwpro->settings;
			} else {
				$settings = array();
			}

			$settings['reg_as'] = (int) $_POST['upd-reg-as'];


			update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
			$iwpro->settings = $settings;
			$iwpro->reg_as = (int) $_POST['upd-reg-as'];
		}
	}
?>


<h1>User Registration</h1>
<hr>
<h2 id="steps">Enable / Disable</h2>
You can send user registration information to infusionsoft. Toggle the switch below to enable or disable this feature.
<br><br>

<div class="big-row">
	
		<div class="ui-toggle<?php echo isset($iwpro->settings['regtoifs']) && $iwpro->settings['regtoifs'] == "yes" ? " checked" : ""; ?>" name="ia_enable_regtoifs">
						<div class="slider"></div>
						<div class="check"><i class="fa fa-check"></i></div>
						<div class="ex"><i class="fa fa-times"></i></div>
					</div>
	
	&nbsp;&nbsp;Send new user registration to infusionsoft?
	
</div><br>

If checked, newly registered users will be sent to infusionsoft and new contact record will be created.


<h2 id="steps">Run Action Set</h2>

<div class="big-row">
	<form method="POST">
			<label style="width: 200px;">Action Set # to Run After New User Registration</label>
			<input style="width: 90px;" name="upd-reg-as" type="text" value="<?php echo isset($iwpro->reg_as) ? $iwpro->reg_as : ""; ?>" />
			<input type="submit" class="next-button" style="position: relative; top: 2px; left: 3px;" value="Save"></input>

	</form>
<br>
</div>

<b>NOTE:</b> The action set will only be triggered when you enable the "Send new user registration to infusionsoft?" setting above.



<h2 id="steps">Tips</h2>
<div class="big-row">
<ul style="list-style-type: disc;">
<li>You can control woocommerce registration settings by going to 
	<a href="<?php echo admin_url('admin.php?page=wc-settings&tab=account');?>">Woocommerce Settings > Accounts Tab</a></li>
<li>If you need to add multiple fields to your woocommerce registration form, you may look into 
	<a href="https://support.woothemes.com/hc/en-us/articles/203182373-How-to-add-custom-fields-in-user-registration-on-the-My-Account-page" target="_blank">this article</a>

</li>
<li>
	If you want to use an Infusionsoft Web Form to process wordpress registrations, you may 
	<a href="http://infusedaddons.com/docu/InfusedWooPRO/lessons/Using_Infusionsoft_Web_Form_as_Wordpress_Registration_Form.html" target="_blank">
		follow our guide.</a>
</li>

</ul>
</div>