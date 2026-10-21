<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	 
	set_site_transient('update_plugins', null);
	
	if($_POST) {
		if(isset($_POST['upd-lic-key'])) {
			if(isset($iwpro->settings)) {
				$settings = $iwpro->settings;
			} else {
				$settings = array();
			}

			$settings['lic'] = $_POST['upd-lic-key'];


			update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
			$iwpro->settings = $settings;
			$iwpro->lic_key = $_POST['upd-lic-key'];
			
		}
	}

	function ia_check_validity($key) {
		$validity = false;
	    if(is_admin()) {
	    	$sh = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	        $request = wp_remote_post(INFUSEDWOO_PRO_UPDATER, array('body' => array('action' => "install", 'd' => $sh, 'l' => $key, 'v' => INFUSEDWOO_PRO_VER)));  
	        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
	            $validity = $request['body'];
	        } 
	    }

	    return $validity;
	}

	$lic_validity = ia_check_validity($iwpro->lic_key);
	set_transient( 'infusedwoo_lic_validate', $lic_validity );


?>

<h1>Updating InfusedWoo</h1>
<hr>
To enable updates, paste your InfusedWoo License key below. The license key was sent to you via e-mail right after you purchased InfusedWoo.
<br><br>
<div class="big-row">
	<form method="POST">
			<label>License Key</label>
			<input name="upd-lic-key" type="text" value="<?php echo isset($iwpro->lic_key) ? $iwpro->lic_key : ""; ?>" style="width: 210px;" />
			<input type="submit" class="next-button" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
		</div>
	</form>
<br>
If for some reasons, you cannot locate your license key anymore, simply <a href="http://infusedaddons.com/support" target="_blank">contact us</a> and we'll find your license key.<br>
<br>
<h3>Check for available updates</h3>

<?php
	if(!isset($iwpro->lic_key) || empty($iwpro->lic_key)) {
		?>
		Please enter your license key first to check updates.
		<?php
	} else {
		$update_info = wp_remote_post('http://downloads.infusedaddons.com/updater/iwpro.php', array('body' => array('action' => 'info')));

		if (!is_wp_error($update_info) || wp_remote_retrieve_response_code($update_info) === 200) { 
			$update_info = unserialize($update_info['body']);
			set_transient( 'infusedwoo_remote_ver', $update_info->new_version );
			
			if(version_compare(INFUSEDWOO_PRO_VER, $update_info->new_version, '<')) {
				// check license key.
				$sh = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];


				?>
					<center><b>There is an update available (Version <?php echo $update_info->new_version; ?>)</b>
						<br><br>
						<?php if($lic_validity == 'valid') { ?>
							<a href="<?php echo wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=infusedwooPRO/infusedwooPRO.php'), 'upgrade-plugin_infusedwooPRO/infusedwooPRO.php'); ?>">
								<div class="big-button">Update Now to <?php echo $update_info->new_version; ?></div>
							</a>
							<br><br>
							<div class="iw-alert alert-green" style="width: 70%; margin-left: 10px;">
								<?php 
								$current_ver_maj = explode(".", INFUSEDWOO_PRO_VER);
								$current_ver_maj = $current_ver_maj[0];
								$remote_ver_maj = explode(".", $update_info->new_version);
								$remote_ver_maj = $remote_ver_maj[0];

								if($remote_ver_maj > $current_ver_maj) {
									echo "<b>InfusedWoo " . $update_info->new_version . " is a major update version. Please ensure all themes and other plugins are updated to their latest versions to avoid compatibility conflicts.</b><br><br>";
								}
								?>
								If you have trouble updating the plugin, you may also update <a href="https://infusedaddons.com/redir.php?to=ftpupdate" target="_blank">InfusedWoo manually using FTP.</a>
							</div>
							
						<?php } else { ?>
							<div class="big-button-grayed">Update Now to <?php echo $update_info->new_version; ?></div>
							<br><br>
							<div class="iw-alert alert-red" style="width: 70%; margin-left: 10px;">Cannot update. 
								<?php 
									if($lic_validity == 'invalid') {
										echo "License Key is not valid.";
									} else if($lic_validity == 'exceed') {
										echo "License Key reached its license limit (domain count exceeded).";
									} else if($lic_validity == 'expired') {
										echo "License key has already expired. To update, renew your license <a href=\"https://infusedaddons.com/portal\" target=\"_blank\">in the customer portal</a>. Renew your license within 30 days after license expiration to get 50% discount.";
									}else {
										echo "Cannot check License Key Validity.";
									}
								?>
							</div>
						<?php } ?>
					</center>
					<br><br>
					<div class="big-row">
						<div class="changelogs">
					<b><u>Release Log for <?php echo $update_info->new_version; ?>:</u></b>

					<?php echo $update_info->sections['changelog']; ?>
					</div>
				<?php
			} else {
 					if($lic_validity == 'valid') { ?>
							<center><b><i>You are currently using the latest version of InfusedWoo (<?php echo INFUSEDWOO_PRO_VER; ?>). </i></b></center>
						<?php } else { ?>
							
							<center>
							<div class="iw-alert alert-red" style="width: 70%; margin-left: 10px;">Cannot check for updates. 
								<?php 
									if($lic_validity == 'invalid') {
										echo "License Key is not valid.";
									} else if($lic_validity == 'exceed') {
										echo "License Key reached its license limit (domain count exceeded).";
									} else if($lic_validity == 'expired') {
										echo "License key has already expired. To update, renew your license <a href=\"https://infusedaddons.com/portal\" target=\"_blank\">in the customer portal</a>. Renew your license within 30 days after license expiration to get 50% discount.";
									} else {
										echo "Cannot check License Key Validity.";
									}
								?>
							</div>
							</center>
						<?php }


				?>
					
				<?php
			}
		} else {
			?>
			Sorry... The system was not able to check for updates, the InfusedAddons download server might be currently down at this moment. Please try again.

			<br><br>
			If you still cannot check updates after 24 hours, please <a href="http://infusedaddons.com/support" target="_blank">contact support</a>.
			<?php
		}

	}
	
?>