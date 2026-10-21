<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	$catTerms = get_terms('product_cat', array('hide_empty' => 0, 'orderby' => 'ASC'));
    $wcats = array();

    foreach($catTerms as $cat) {
    	$wcats[$cat->term_id] = $cat->name;
    }

    if($iwpro->ia_app_connect()) {
  		$cats = $iwpro->app->dsFind('ProductCategory', 1000, 0, 'Id', '%', array('Id', 'CategoryDisplayName'));
  		
  		$icats = array();
  		foreach($cats as $cat) {
  			$icats[$cat['Id']] = $cat['CategoryDisplayName'];
  		}

  		
  	}
?>

<h1>Product Import / Export Wizard</h1>
<hr>

<?php if($iwpro->settings['enabled'] == "yes") { ?>

<div class="pending-prod-process" style="display:none;">
	<h4>Pending Import / Export Process</h4>
	You have a pending import / export process. Do you want to resume this process? Or would you like to start a new import / export process?
	<br>
	<div class="process-info">
		
	</div>
	<center>
	<div class="next-button resume-pending-process" style="width: auto; padding: 5px;">Yes, Resume this Process</div>
	<div class="back-button new-process" style="width: auto; padding: 5px;">No, Start a new Process</div>
	</center>

</div>
<div class="step-by-step product-import">
<div class="steps-wrap">

	<div class="step-block">

		<span class="circle-step">1</span> <span class="step-head">What do you want to do?</span><br>
		<div class="big-row">
		
		<div class="iw-selection iw-prod-1" val="import"><b>Import Products:</b> Woocommerce &larr; Infusionsoft</div>
		<div class="iw-selection iw-prod-1" val="export"><b>Export Products:</b> Woocommerce &rarr; Infusionsoft</div>

		</div>
	</div>

	<div class="step-block">

		<span class="circle-step">2</span> <span class="step-head">Specify products to copy</span><br>
		<div class="big-row">
		
		<div class="iw-selection iw-prod-2" val="all">All Products</div>
		<div class="iw-selection iw-prod-2" val="cat">Products from Categories</div>
		<div class="iw-selection iw-prod-2" val="id">Specific Product IDs</div>
		</div>

		<div class="big-row prod-step-2-further">
			
			<div class="icats">
				<hr>
				Choose Categories: <br>
				<br>
				<?php foreach($icats as $k => $icat) { ?>
					<div class="icat iw-checkbox" value="<?php echo $k; ?>"><?php echo $icat; ?></div>
				<?php } ?>
			</div>
			<div class="wcats">
				<hr>
				Choose Categories: <br>
				<br>
				<?php foreach($wcats as $k => $wcat) { ?>
					<div class="wcat iw-checkbox" value="<?php echo $k; ?>"><?php echo $wcat; ?></div>
				<?php } ?>
			</div>
			<div class="prodid">
				<hr>
				<label style="width: 180px; font-size: 10pt; ">Enter Product IDs<br> (e.g. 1-5, 8, 11-13)</label>
				<input type="text" name="prod_ids" value="" style="width: 200px;" />
			</div>
			
		</div>

		<div class="big-row">
			<br>
			<div class="back-button just-back" style="">Back</div>
			<div class="next-button iw-import-2-next" style="display: none">Next</div>
			
		</div>	
	</div>

	<div class="step-block">

		<div class="iw-specify-import" style="display: none;">
			<span class="circle-step">3</span> <span class="step-head">Specify Import Settings</span><br>
			<div class="big-row">
				Copy Content from Infusionsoft Field: <br>
					<select name="import-content" class="map">
									<option value="">(Do not copy)</option>
									<option value="shortdesc">Short Description</option>
									<option value="desc">Description</option>
									<option value="topbottom">Top HTML + Bottom HTML</option>
									<option value="topdescbottom">Top HTML + Description + Bottom HTML</option>
								</select>
				<br><br>
				Copy Short Description from Infusionsoft Field: <br>
					<select name="import-shortdesc" class="map">
									<option value="">(Do not copy)</option>
									<option value="shortdesc">Short Description</option>
									<option value="desc">Description</option>
								</select>
			</div>
			<div class="big-row">
				Other Settings:<br><br>
				<div class="iw-import-settings iw-checkbox" name="import-images" value="">Copy Product Images? (Only supports legacy product images)</div>
				 <div class="iw-import-settings iw-checkbox" name="import-virtual" value="">Set Product to 'Virtual' if not shippable in Infusion</div>
				  <div class="iw-import-settings iw-checkbox" name="import-tax" value="">Copy Tax Status</div>
			
			</div>

			<div class="big-row">
				<br>
				<div class="back-button just-back" style="">Back</div>
				<div class="next-button iw-import-process" style="width: 100px;">PROCESS</div>
				
			</div>	
		</div>

		<div class="iw-specify-export" style="display: none;">
			<span class="circle-step">3</span> <span class="step-head">Specify Export Settings</span><br>
			<div class="big-row">
				<form id="iw-specify-export-vals">
				Copy Product Name from Woocommerce Field: <br>
					<select name="export-ProductName" class="map">
									<option value="title">Product Title</option>
									<option value="meta">Custom Meta Field</option>
								</select>
				<br><br>

				Copy Price from Woocommerce Field: <br>
					<select name="export-ProductPrice" class="map">
									<option value="regprice">Regular Price</option>
									<option value="saleprice">Sale Price</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>

				Copy SKU from Woocommerce Field: <br>
					<select name="export-Sku" class="map">
									<option value="">(Do not copy)</option>
									<option value="sku">SKU</option>
									<option value="meta">Custom Meta Field</option>
								</select>
				<br><br>
				Copy Short Description from Woocommerce Field: <br>
					<select name="export-ShortDescription" class="map">
									<option value="">(Do not copy)</option>
									<option value="short">Product Short Description</option>
									<option value="content">Content</option>
									<option value="meta">Custom Meta Field</option>
								</select>
				<br><br>
				Copy Description from Woocommerce Field: <br>
					<select name="export-Description" class="map">
									<option value="">(Do not copy)</option>
									<option value="short">Product Short Description</option>
									<option value="content">Content</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>
				Copy Weight from Woocommerce Field: <br>
					<select name="export-Weight" class="map">
									<option value="">(Do not copy)</option>
									<option value="weight">Weight</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>
				Copy Shippable from Woocommerce Field: <br>
					<select name="export-Shippable" class="map">
									<option value="">(Do not copy)</option>
									<option value="virtual">Set Yes if not 'Virtual' Product</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>
				Copy Inventory Limit from Woocommerce Field: <br>
					<select name="export-InventoryLimit" class="map">
									<option value="">(Do not copy)</option>
									<option value="stock">Stock</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>
				Copy Taxable from Woocommerce Field: <br>
					<select name="export-Taxable" class="map">
									<option value="">(Do not copy)</option>
									<option value="taxstatus">Tax Status</option>
									<option value="meta">Custom Meta Field</option>
								</select>

				<br><br>
				Copy Product Image from Woocommerce Field: <br>
					<select name="export-LargeImage" class="map">
									<option value="">(Do not copy)</option>
									<option value="productimage">Product Image</option>
								</select>
				</form>
			</div>
			



			<div class="big-row">
				<br>
				<div class="back-button just-back" style="">Back</div>
				<div class="next-button iw-export-process" style="width: 100px;">PROCESS</div>
				

			</div>	
		</div>
	</div>

	<div class="step-block product-process-block">
		<br><br>
		<div class="progress-holder import-progress">
			<div class="actual-progress"></div>
		</div>
		<div class="pause-proc"><i class="fa fa-pause" title="Pause Process"></i></div>
		<div class="play-proc"><i class="fa fa-play" title="Play Process"></i></div>
		<div class="repeat-proc"><i class="fa fa-repeat" title="Retry Process"></i></div>

		<div class="progress-status">Processing...</div>
		<br><br>
		<center>
			<a href="#" class="show-logs">Show Detailed Status</a>
		</center>
		<div class="process-logs" style="display:none;">
		</div>
	</div>

<?php } else { ?>
	Please enable first Infusionsoft. To set up integration, please proceed to Getting Started &rarr; Guided Setup
<?php } ?>

