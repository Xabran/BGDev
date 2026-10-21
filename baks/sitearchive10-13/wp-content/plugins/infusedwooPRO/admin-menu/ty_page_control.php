<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<style>
	.step-guide {display: none;}
</style>

<h1>Thank You Page Control</h1>
<hr>

You can control the woocommerce checkout thank you page URL here based on the conditions you desire.
Note that this will only work if there are no other plugins / scripts that override woocommerce thank you page setting.
<br><br>
To control the woocommerce checkout thank you page, first add an override below and specify the conditions.
<br><br>
You can add multiple overrides and the topmost override setting will be the highest priority. To adjust priority, simply drag and drop the overrides below.

<br>
<div class="step-by-step iw_ty_control">
<div class="steps-wrap">

<div class="step-block">
	<ul class="iw_ty_ov">

	</ul>

	<div class="iw_ty_ov_add">
			<span class="iw_ty_ov_name">Click to Add New Override...</span>
		</div>
</div>
<div class="step-block" style="min-height: 700px;">
<h3 class="iw-ov-title">Add new Override...</h3>

<div class="big-row iw-ov-edit">
	<form method="POST">
			<input type="hidden" name="iw-ov-id" value="" />
			<label style="width: 200px;">Override Name</label><br>
			<input name="iw-ov-name" type="text" value="" style="width: 210px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(Any name will work to decribe the override / thank you page.)</span>
			<br><br>
			<label style="width: 200px;">Thank You Page URL</label><br>
			<input name="iw-ov-url" type="text" value="" style="width: 350px;" /><br>
			<span style="font-size: 9pt; margin-top: 4px;">(Should begin with http:// or https://)</span>
			<br><br>

			<div class="ui-toggle checked" name="iw-ov-pass">
						<div class="slider"></div>
						<div class="check"><i class="fa fa-check"></i></div>
						<div class="ex"><i class="fa fa-times"></i></div>
					</div>
	
	&nbsp;&nbsp;Pass Contact Information to Thank You Page
	<br><br><br>

			<b>Conditions</b><br><br>
			<div class="condition-wrap">
				<select name="iw-ov-condition" class="iw-ov-condition">
					<option value="always">Always redirect customers to this URL</option>
					<option value="product">Only when order contains certain products...</option>
					<option value="categ">Only when order contains products from certain categories...</option>
					<option value="morevalue">Only when total order value is more than...</option>
					<option value="lessvalue">Only when total order value is less than...</option>
					<option value="moreitem">Only when total order item count is more than...</option>
					<option value="lessitem">Only when total order item count is less than...</option>
					<option value="coupon">When coupon code is applied...</option>
					<option value="pg">When payment gateway used is...</option>
				</select>
				<br><br>
				<div class="iw-ov-further iw-ov-further-products">
				Enter Product(s) <br>
				<input name="iw-ov-further-productsearch" class="productsearch" type="text" value="" placeholder="Begin typing to search products..." style="width: 350px;" />
				<div class="iw-ov-products iw-tokens">

				</div>
				<br><br>
				</div>


				<div class="iw-ov-further iw-ov-further-categ">
				Select Categories<br>
				<select class="" multiple name="iw-ov-further-categ" data-placeholder="Select Categories..." style="height: 200px; max-width: 360px; min-width: 280px">
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

				<div class="iw-ov-further iw-ov-further-value">
				Enter Amount (<?php echo get_woocommerce_currency_symbol(); ?>)<br>
				<input name="iw-ov-further-value" type="text" value="" style="width: 210px;" /><br><br>

				</div>

				<div class="iw-ov-further iw-ov-further-item">
				Enter Number<br>
				<input name="iw-ov-further-item" type="text" value="" style="width: 210px;" /><br><br>
				</div>

					

				<div class="iw-ov-further iw-ov-further-coupon">
				Enter Coupon Code <br>
				<input name="iw-ov-further-coupon" type="text" value="" style="width: 300px;" /><br>
				<span style="font-size: 9pt; margin-top: 4px;">(Separate by comma, leave empty if applies to all coupons)</span>
				<br><br>
				</div>

				<div class="iw-ov-further iw-ov-further-pg">
				Select Payment Gateway<br>
				<select class="" name="iw-ov-further-pg" data-placeholder="Select Payment Gateway">
					<?php 
					$wcpg = new WC_Payment_Gateways;
					$pgs = $wcpg->get_available_payment_gateways();

					foreach ($pgs as $pg) {
				        echo '<option value="';
			  			echo $pg->id;
			  			echo '">' . $pg->title . " [ {$pg->id} ]";
			  			echo "</option>";
					}

					?>  
				</select>
				<br><br>
				</div>
			</div>
			<div class="cond-connector cond-adder" style="display:none;">
				<div class="cond-connect"></div>
				<div class="cond-add"><i class="fa fa-plus"></i> Add new Condition</div>
			</div>

			<br>
			<div class="back-button just-back" style="">Cancel</div>
			&nbsp;<input type="submit" class="next-button iw-ov-save" style="position: relative; top: 2px; left: 3px;" value="Save"></input>
		</form>
	</div>
</div>


</div>
</div>
