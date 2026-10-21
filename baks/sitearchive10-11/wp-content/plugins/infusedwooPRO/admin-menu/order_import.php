<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h1>Order Import / Export Wizard</h1>
<hr>
<div class="pending-ord-process" style="display:none;">
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

<div class="step-by-step order-import">
<div class="steps-wrap">

	<div class="step-block">

		<span class="circle-step">1</span> <span class="step-head">What do you want to do?</span><br>
		<div class="big-row">
		
		<div class="iw-selection iw-order-1" val="import"><b>Import Orders:</b> Woocommerce &larr; Infusionsoft</div>
		<div class="iw-selection iw-order-1" val="export"><b>Export Orders:</b> Woocommerce &rarr; Infusionsoft</div>

		</div>
	</div>

	<div class="step-block">

		<span class="circle-step">2</span> <span class="step-head">Specify orders to copy</span><br>
		<div class="big-row">
		
		<div class="iw-selection iw-order-2" val="all">All Orders</div>
		<div class="iw-selection iw-order-2" val="cat">Specific Order Status</div>
		<div class="iw-selection iw-order-2" val="id">Specific Order IDs</div>
		</div>

		<div class="big-row order-step-2-further">
			
			<div class="istats">
				<hr>
				Choose Order Status: <br>
				<br>
				
				<div class="istat iw-checkbox" value="paid">Orders marked Paid</div>
				<div class="istat iw-checkbox" value="unpaid">Orders marked Unpaid</div>
				
			</div>
			<div class="wstats">
				<hr>
				Choose Order Status: <br>
				<br>
				<?php
					$terms = get_terms('shop_order_status');
					if(!is_array($terms) || count($terms) == 0) {
						$terms = wc_get_order_statuses();
						foreach ( $terms as $k => $term ) {
							echo '<div class="wstat iw-checkbox" value="' . esc_attr( $k ) . '"';
							echo '>' . esc_html__( $term, 'woocommerce' ) . '</div>';
						}
					} else {
						foreach ( $terms as $term ) {
							echo '<div class="wstat iw-checkbox" value="' . esc_attr( $term->slug ) . '"';
							echo '>' . esc_html__( $term->name, 'woocommerce' ) . '</div>';
						}
					}
				?>
			</div>	
			<div class="orderid">
				<hr>
				<label style="width: 180px; font-size: 10pt; ">Enter Order IDs<br> (e.g. 1-5, 8, 11-13)</label>
				<input type="text" name="order_ids" value="" style="width: 200px;" />
			</div>		
		</div>

		<div class="big-row">
			<br>
			<div class="back-button just-back" style="">Back</div>
			<div class="next-button iw-order-2-next" style="display: none">Next</div>
			
		</div>	
	</div>

	<div class="step-block">

		<div class="iw-specify-import" style="display: none;">
			<span class="circle-step">3</span> <span class="step-head">Review before Processing</span><br>
			<div class="big-row">
				You are about to import products from Infusionsoft to Woocommerce.<br><br>
			
				<b>Orders to Import</b>: <span class="iw-order-step3-review iw-order-data1"></span><br>
				<b>Orders to Process</b>: <span class="iw-order-step3-review iw-order-data2"></span><br>
				<b>Estimated time needed</b>: <span class="iw-order-step3-review iw-order-data3"></span>
				<br><br>
				Click "Process" to begin the Import Process. While processing, do not click your browser's
				"Stop","Refresh","Back", and "Close" buttons or else the import process will fail.
			</div>

			<div class="big-row">
				<hr>
				<b>Advanced Settings:</b><br><br>
				
				<label style="width: 180px; font-size: 10pt; ">Flag Orders as:</label>
				<select name="iw_order_step3">
				<?php
					$terms = get_terms('shop_order_status');
					if(!is_array($terms) || count($terms) == 0) {
						$terms = wc_get_order_statuses();
						$default = array("wc-completed","completed","complete");
						foreach ( $terms as $k => $term ) {
							$selected = (in_array($k, $default)) ? "selected " : " ";
							echo '<option value="' . esc_attr( $k ) . '"' . $selected;
							echo '>' . esc_html__( $term, 'woocommerce' ) . '</option>';
						}
					} else {
						foreach ( $terms as $term ) {
							echo '<option class="wstat iw-checkbox" value="' . esc_attr( $term->slug ) . '"'  . $selected;
							echo '>' . esc_html__( $term->name, 'woocommerce' ) . '</option>';
						}
					}
				?>
				</select>
			</div>

			<div class="big-row">
				<br>
				<div class="back-button just-back" style="">Back</div>
				<div class="next-button iw-order-process" style="width: 100px;">PROCESS</div>
				
			</div>	
		</div>

		<div class="iw-specify-export" style="display: none;">
			<span class="circle-step">3</span> <span class="step-head">Review before Processing</span><br>
			<div class="big-row">
				You are about to export products from Woocommerce to Infusionsoft.<br><br>
				
				<b>Orders to Export</b>: <span class="iw-order-step3-review iw-order-data1"></span><br>
				<b>Orders to Process</b>: <span class="iw-order-step3-review iw-order-data2"></span><br>
				<b>Estimated time needed</b>: <span class="iw-order-step3-review iw-order-data3"></span>
				<br><br>
				Click "Process" to begin the Import Process. While processing, do not click your browser's
				"Stop","Refresh","Back", and "Close" buttons or else the export process will fail.
			</div>

			<div class="big-row">
				<hr>
				<b>Advanced Settings (Optional):</b><br><br>
				
				<label style="width: 180px; font-size: 10pt; ">Action Set ID to Run:</label>
				<input type="text" name="iw_order_step3e" value="" style="width: 200px;" />
			</div>


			<div class="big-row">
				<br>
				<div class="back-button just-back" style="">Back</div>
				<div class="next-button iw-order-process" style="width: 100px;">PROCESS</div>
				
			</div>		
		</div>
	</div>

	<div class="step-block order-process-block">
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
