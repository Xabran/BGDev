<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include_once INFUSEDWOO_PRO_DIR . "admin-menu/assets/ifsfields.php"; 

?>

<style>
	.step-guide {display: none;}
</style>

<h1>Checkout Custom Fields</h1>
<hr>

To add a custom field, add first a group. Then you can add custom fields under each group.
Note that the group name will appear as the field group header in the woocommerce checkout.
<br><br>
You can also drag-and-drop groups and custom fields to re-position their order of appearance in the checkout page.
<br>
<div class="step-by-step checkout-fields">
<div class="steps-wrap">

<div class="step-block">
	<ul class="iw_checkoutfields">

	</ul>

	<div class="iw_cf_group_add">
			<span class="iw_cf_group_name">Click to Add New Group...</span>
		</div>
</div>
<div class="step-block">
<h3 class="iw-grp-title">Add a New Group</h3>

<div class="big-row iw-grp-edit">
	<form method="POST">
			<input type="hidden" name="iw-grp-id" value="" />
			<label>Group Name</label><br>
			<input name="iw-grp-name" type="text" value="" style="width: 210px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(This will appear as fieldset header in the checkout page.)</span>
			<br><br>
			<b>Display Options</b>
			<select name="iw-grp-display" class="iw-grp-display">
				<option value="always">Always show this Group</option>
				<option value="product">Only when cart contains certain products...</option>
				<option value="categ">Only when cart contains products from certain categories...</option>
				<option value="morevalue">Only when total cart value is more than...</option>
				<option value="lessvalue">Only when total cart value is less than...</option>
				<option value="moreitem">Only when total cart item count is more than...</option>
				<option value="lessitem">Only when total cart item count is less than...</option>
			</select>
			<br><br>
			<div class="iw-grp-further iw-grp-further-products">
			Select Products<br>
			<select class="chzn-select" multiple name="iw-grp-further-products" data-placeholder="Select Products...">
				<?php 
					$args = array(
						'posts_per_page'   => 999999,
						'orderby'          => 'post_date',
						'order'            => 'ASC',
						'post_type'        => 'product',
						'post_status'      => 'publish',
					);

			  		$allwooprods = get_posts( $args );

			  		foreach($allwooprods as $prod) {
			  			echo '<option value="';
			  			echo $prod->ID;
			  			echo '">' . $prod->post_title . " [ {$prod->ID} ]";
			  			echo "</option>";
			  		}
				?>
			</select>
			<br><br>
			</div>


			<div class="iw-grp-further iw-grp-further-categ">
			Select Categories<br>
			<select class="chzn-select" multiple name="iw-grp-further-categ" data-placeholder="Select Categories...">
				<?php
				  $taxonomy     = 'product_cat';
				  $orderby      = 'name';  
				  $show_count   = 0;      // 1 for yes, 0 for no
				  $pad_counts   = 0;      // 1 for yes, 0 for no
				  $hierarchical = 1;      // 1 for yes, 0 for no  
				  $title        = '';  
				  $empty        = 0;
				$args = array(
				  'taxonomy'     => $taxonomy,
				  'orderby'      => $orderby,
				  'show_count'   => $show_count,
				  'pad_counts'   => $pad_counts,
				  'hierarchical' => $hierarchical,
				  'title_li'     => $title,
				  'hide_empty'   => $empty
				);
				?>
				<?php $all_categories = get_categories( $args );

				foreach ($all_categories as $cat) {
				    if($cat->category_parent == 0) {
				        $category_id = $cat->term_id;
				        echo '<option value="';
			  			echo $category_id;
			  			echo '">' . $cat->name . " [ {$category_id} ]";
			  			echo "</option>";
				    }
				}

				?>  
			</select>
			<br><br>
			</div>

			<div class="iw-grp-further iw-grp-further-value">
			Enter Amount (<?php echo get_woocommerce_currency_symbol(); ?>)<br>
			<input name="iw-grp-further-value" type="text" value="" style="width: 210px;" /><br><br>

			</div>

			<div class="iw-grp-further iw-grp-further-item">
			Enter Number<br>
			<input name="iw-grp-further-item" type="text" value="" style="width: 210px;" /><br><br>
			</div>

				

			<div class="iw-grp-further iw-grp-further-coupon">
			Enter Coupon Code <br>
			<input name="iw-grp-further-coupon" type="text" value="" style="width: 300px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(Separate by comma, leave empty if applies to all coupons)</span>
			<br><br>
			</div>

			<div class="back-button just-back" style="">Cancel</div>
			&nbsp;<input type="submit" class="next-button iw-grp-save" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
		</form>
	</div>

<div class="big-row iw-field-edit">
	<form method="POST">
			<input type="hidden" name="iw-field-id" value="" />
			<input type="hidden" name="iw-field-grpid" value="" />
			<label>Field Name</label><br>
			<input name="iw-field-name" type="text" value="" style="width: 210px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(This will serve as the label of the field)</span>
			<br><br>
			<label>Field Type</label><br>
			<select name="iw-field-type" class="iw-field-type">
				<option value="text">Text Input</option>
				<option value="textarea">Text Area</option>
				<option value="dropdown">Single Select Drop Down</option>
				<option value="multidropdown">Multi Select Dropdown</option>
				<option value="date">Date</option>
				<option value="radio">Radio Selection</option>
				<option value="checkbox">Yes / No (Checkbox)</option>
			</select>
			<div class="iw-field-options" style="display:none;">
			<br><br>
				<div class="options-for-dropdown options-for-multidropdown options-for-radio">
				Available Options<br>
				<span style="font-size: 9pt; margin-top: 4px;">(One entry per line)</span><br>
				<textarea name="iw-field-options" style="border: 1px solid #293D67; width: 300px; height: 150px;"></textarea>
				</div>

				<div class="options-for-checkbox">
				Default Value<br>
				<select name="iw-field-default" class="iw-field-type">
					<option value="0">no</option>
					<option value="1">yes</option>
				</select>
				</div>
			</div>
			<br><br>
			Is a required field?<br>
			<select name="iw-field-required" class="iw-field-type">
				<option value="no" selected>no</option>
				<option value="yes">yes</option>
			</select>
			<br><br>
			Infusionsoft Field<br>
			<select name="iw-field-infusionsoft" class="iw-field-type chzn-select">
				<option value="">--Do not save in infusionsoft--</option>
				<?php 
					$options = '';
					foreach($ifsfields as $k => $v) {
						if(is_array($v)) {
							$options .= "<optgroup label=\"$k\">";
							foreach($v as $kk => $vv) {
								//if($kk == $val && $val != "") $sel = " selected ";
								$sel = "";
								$options .= '<option value="'.$kk.'"'.$sel.'>'.$vv.'</option>';
							}
							$options .= "</optgroup>";
						} else {
							//if($k == $val  && $val != "") $sel = " selected ";
							$sel = "";
							$options .= '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
						}
					}

					echo $options;
				?>
			</select>
			<br><br>
			<b>Display Options</b>
			<select name="iw-field-display" class="iw-field-display">
				<option value="inherit">Inherit from Group Settings</option>
				<option value="product">Only when cart contains certain products...</option>
				<option value="categ">Only when cart contains products from certain categories...</option>
				<option value="morevalue">Only when total cart value is more than...</option>
				<option value="lessvalue">Only when total cart value is less than...</option>
				<option value="moreitem">Only when total cart item count is more than...</option>
				<option value="lessitem">Only when total cart item count is less than...</option>
			</select>
			<br><br>
			<div class="iw-field-further iw-field-further-products">
			Select Products<br>
			<select class="chzn-select" multiple name="iw-field-further-products" data-placeholder="Select Products...">
				<?php 
					$args = array(
						'posts_per_page'   => 999999,
						'orderby'          => 'post_date',
						'order'            => 'ASC',
						'post_type'        => 'product',
						'post_status'      => 'publish',
					);

			  		$allwooprods = get_posts( $args );

			  		foreach($allwooprods as $prod) {
			  			echo '<option value="';
			  			echo $prod->ID;
			  			echo '">' . $prod->post_title . " [ {$prod->ID} ]";
			  			echo "</option>";
			  		}
				?>
			</select>
			<br><br>
			</div>


			<div class="iw-field-further iw-field-further-categ">
			Select Categories<br>
			<select class="chzn-select" multiple name="iw-field-further-categ" data-placeholder="Select Categories...">
				<?php
				  $taxonomy     = 'product_cat';
				  $orderby      = 'name';  
				  $show_count   = 0;      // 1 for yes, 0 for no
				  $pad_counts   = 0;      // 1 for yes, 0 for no
				  $hierarchical = 1;      // 1 for yes, 0 for no  
				  $title        = '';  
				  $empty        = 0;
				$args = array(
				  'taxonomy'     => $taxonomy,
				  'orderby'      => $orderby,
				  'show_count'   => $show_count,
				  'pad_counts'   => $pad_counts,
				  'hierarchical' => $hierarchical,
				  'title_li'     => $title,
				  'hide_empty'   => $empty
				);
				?>
				<?php $all_categories = get_categories( $args );

				foreach ($all_categories as $cat) {
				    if($cat->category_parent == 0) {
				        $category_id = $cat->term_id;
				        echo '<option value="';
			  			echo $category_id;
			  			echo '">' . $cat->name . " [ {$category_id} ]";
			  			echo "</option>";
				    }
				}

				?>  
			</select>
			<br><br>
			</div>

			<div class="iw-field-further iw-field-further-value">
			Enter Amount (<?php echo get_woocommerce_currency_symbol(); ?>)<br>
			<input name="iw-field-further-value" type="text" value="" style="width: 210px;" /><br><br>
			</div>

			<div class="iw-field-further iw-field-further-item">
			Enter Number<br>
			<input name="iw-field-further-item" type="text" value="" style="width: 210px;" /><br><br>
			</div>

				

			<div class="iw-field-further iw-field-further-coupon">
			Enter Coupon Code <br>
			<input name="iw-field-further-coupon" type="text" value="" style="width: 300px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(Separate by comma, leave empty if applies to all coupons)</span>
			<br><br>
			</div>

			<div class="back-button just-back" style="">Cancel</div>
			&nbsp;<input type="submit" class="next-button iw-field-save" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
		</form>
	</div>

</div>


</div>
</div>
