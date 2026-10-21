<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $iwpro;
$int = $iwpro;

$remcc = $pg->settings['pgremcc'];
$ti    = $pg->settings['pgti'];
$useUI = $pg->settings['pgui'];
if($remcc == 'yes') $ccs = $int->ia_get_creditcards();

if(!empty($ccs)) {
	if($useUI != 'yes') {
		$content = '<select name="ia_cardId" class="woocommerce-select" style="min-width: 280px;"><option value="">'. __("Select Credit Card") .'</option>';
		foreach($ccs as $cc) {			  
			 $content .= '<option value="'.$cc['Id'].'">'. $cc['CardType'] . __(" ending in ", 'woocommerce') . $cc['Last4'] . __(" expiring on ", 'woocommerce') . $cc['ExpirationMonth'] . '/' .$cc['ExpirationYear'] . '</option>';
			}
		$content .=  '</select>';
	} else {
		wp_enqueue_script( "ia-ddslick", (INFUSEDWOO_PRO_URL . "assets/jquery.ddslick.min.js"), array("jquery"), false );
		$content = '<select id="ia_cardId_select" name="ia_cardId_select" class="woocommerce-select" style="min-width: 280px;">';
		$content .=  '</select><input type="hidden" name="ia_cardId" value ="" />';
	}
}

?>
<style type="text/css">
#oldcc {width: 100% !important;}
.dd-selected-image, .dd-option-image {margin: 0 !important; margin-right: 5px !important;}
.dd-container {margin-top: 5px !important; margin-bottom: 5px;}
.dd-option-image, .dd-selected-image {width: 32px !important; height: 32px !important; border-radius: 0 !important; box-shadow: none !important; }
<?php if(!empty($ccs) && $useUI == "yes") { ?> .ia-new-cc {display: none;} <?php } ?>
.ccimages {width: 45px; height: 45px;  border-radius: 0 !important; box-shadow: none !important;  margin-left: 3px;}
.ia-cc-images { padding: 3px; margin-left: 5px;  display: inline-block;}
.unselect {opacity: 0.5}
</style>
<?php if($ti != 'yes') { ?>
<div class="payment_box payment_method_infusionsoft">
<?php } else { ?>
<div class="payment_box payment_method_authorize">
<?php } ?>

<?php if(!empty($ccs)) { ?>
	<div class="ia_select_cc">
	<fieldset>
	<p class="form-row form-row-first" id="oldcc">
	<label for="ia_cardId"><?php echo __("Select Credit Card", 'woocommerce') ?> <span class="required">*</span></label>
	<?php echo $content; ?> 
	</p>
	</fieldset>		
	</div>

	<?php if($useUI != "yes") { ?>
		<center><?php echo __("or enter new credit card below", 'woocommerce') ?></center>
		<hr />
	<?php } ?>
<?php } ?>
<div class="ia-new-cc">
<fieldset id="ccnew">
<p class="form-row form-row-first">
	<label for="ia_ccnum"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
	<input type="text" class="input-text" id="ia_ccnum" name="ia_ccnum" />
</p>

<p class="form-row form-row-last">
	<?php if($useUI != "yes") { ?>
	<label for="ia_cardtype"><?php echo __("Card type", 'woocommerce') ?> <span class="required">*</span></label>
	
	<select name="ia_cardtype" id="ia_cardtype" class="woocommerce-select">
		<?php 
	        foreach($pg->cardtypes as $type) :
		        ?>
		        <option value="<?php echo $type ?>"><?php _e($type, 'woocommerce'); ?></option>
	            <?php
            endforeach;
		?>
	</select>
	<?php } else { ?>
		<div class="ia-cc-images">
		<?php 
			if(is_array($pg->cardtypes) && in_array("Visa", $pg->cardtypes)) echo '<img class="ia-visa ccimages" src="'. INFUSEDWOO_PRO_URL . 'assets/cc-images/visa.svg' .'"></img>';
			if(is_array($pg->cardtypes) && in_array("MasterCard", $pg->cardtypes)) echo '<img class="ia-master ccimages" src="'. INFUSEDWOO_PRO_URL . 'assets/cc-images/mastercard.svg' .'"></img>';
			if(is_array($pg->cardtypes) && in_array("Discover", $pg->cardtypes)) echo '<img class="ia-disc ccimages" src="'. INFUSEDWOO_PRO_URL . 'assets/cc-images/discover.svg' .'"></img>';
			if(is_array($pg->cardtypes) && in_array("American Express", $pg->cardtypes)) echo '<img class="ia-amex ccimages" src="'. INFUSEDWOO_PRO_URL . 'assets/cc-images/amex.svg' .'"></img>';
		?>
		</div>
		<input type="hidden" name="ia_cardtype" value ="" />
	<?php } ?>

</p>

<div class="clear"></div>

<p class="form-row form-row-first">
	<label for="cc-expire-month"><?php echo __("Expiration date", 'woocommerce') ?> <span class="required">*</span></label><br />
	<select name="ia_expmonth" id="ia_expmonth" class="woocommerce-select woocommerce-cc-month">
		<option value=""><?php _e('Month', 'woocommerce') ?></option>
		<?php
			$months = array();
			for ($i = 1; $i <= 12; $i++) {
			    $timestamp = mktime(0, 0, 0, $i, 1);
			    $months[date('n', $timestamp)] = date('F', $timestamp);
			}
			foreach ($months as $num => $name) {
				$num2 = str_pad($num,2,"0",STR_PAD_LEFT);
	            printf('<option value="%s">%s</option>', $num2, $name);
	        }
		?>
	</select>
	<select name="ia_expyear" id="ia_expyear" class="woocommerce-select woocommerce-cc-year">
		<option value=""><?php _e('Year', 'woocommerce') ?></option>
		<?php
			$years = array();
			for ($i = date('y'); $i <= date('y') + 15; $i++) {
			    printf('<option value="20%u">20%u</option>', $i, $i);
			}
		?>
	</select>
</p>
<?php if ($pg->cvv == 'yes') { ?>

<p class="form-row form-row-last">
	<label for="ia_cvv"><?php _e("Card security code", 'woocommerce') ?> <span class="required">*</span></label><br />
	<input type="text" class="input-text" id="ia_cvv" name="ia_cvv" maxlength="4" style="width:70px" />
</p>
<?php } ?>

<div class="clear"></div>
</fieldset>			
</div>
<script>
<?php if($useUI == "yes" && !empty($ccs)) { ?>
var ddData = [
	<?php foreach($ccs as $cc) { 
			switch($cc['CardType']) {
				case 'visa':
				case 'VISA':
				case 'Visa':
					$logo = 'visa';
					break;
				case 'MC':
				case 'mastercard':
				case 'MasterCard':
				case 'Mastercard':
				case 'MASTERCARD':
					$logo = 'mastercard';
					break;
				case 'discover':
				case 'Discover':
				case 'DISCOVER':
				case 'disc':
				case 'DISC':
					$logo = 'discover';
					break;
				case 'amex':
				case 'AMEX':
				case 'American Express':
				case 'Amex':
					$logo = 'amex';
					break;

			}

		?>
	    {
	        text: "<?php echo $cc['CardType']; ?> <?php _e(' ending in ','woocommerce'); ?><?php echo $cc['Last4']  ?>",
	        value: "<?php echo $cc['Id']; ?>",
	        selected: false,
	        description: "<?php echo $cc['CardType'] . __(' Expiring on ', 'woocommerce') . $cc['ExpirationMonth'] . '/' .$cc['ExpirationYear']; ?>  ",
	        imageSrc: "<?php echo INFUSEDWOO_PRO_URL . 'assets/cc-images/'.$logo.'.svg'; ?>"
	    },
	<?php } ?>
    {
        text: "Use a New Credit Card...",
        value: 0,
        selected: false,
        description: "",
       	imageSrc: "<?php echo INFUSEDWOO_PRO_URL . 'assets/cc-images/plus.svg'; ?>"
    }
];

jQuery(document).ready(function() {
		jQuery('#ia_cardId_select').ddslick({
		    data: ddData,
		    width: '90%',
		    imagePosition: "left",
		    selectText: "Select Credit Card...",
		    onSelected: function (data) {
		   		jQuery('[name=ia_cardId]').val(data.selectedData.value);
		        if(data.selectedData.value == 0) {
		        	jQuery('.ia_select_cc').hide();
		        	jQuery('.ia-new-cc').show();
		        }
		    }
		});
});
<?php } ?>

<?php if($useUI == 'yes') { ?>
jQuery(document).ready(function() {
		jQuery('[name=ia_ccnum]').keyup(function() {
		    var cred = jQuery(this).val();
		    jQuery(".ccimages").addClass('unselect');
		    jQuery("[name=ia_cardtype]").val("");

			switch(cred.substring(0, 1)) {
				case '4': 
					jQuery("[name=ia_cardtype]").val('Visa'); 
					jQuery(".ia-visa").removeClass('unselect');
					break;
				case '5':  
					jQuery("[name=ia_cardtype]").val('MasterCard'); 
					jQuery(".ia-master").removeClass('unselect');
					break;
				case '6':  
					jQuery("[name=ia_cardtype]").val('Discover'); 
					jQuery(".ia-disc").removeClass('unselect');
					break;
				case '3': 
					jQuery("[name=ia_cardtype]").val('American Express');  
					jQuery(".ia-amex").removeClass('unselect');
					break;
			}
		});
});
<?php } ?>
</script>


</div>
<?php

?>