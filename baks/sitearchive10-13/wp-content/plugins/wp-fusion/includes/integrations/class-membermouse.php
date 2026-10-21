<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'MemberMouse' )) {
	
	class WPF_MemberMouse extends WPF_Integrations_Base {

		private $saved;

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			// Settings
			add_filter( 'wpf_user_register', array( $this, 'member_register' ), 10, 2 );
			add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			
			// MemberMouse actions
			add_action( 'mm_member_account_update', array( $this, 'account_update' ) );
			add_action( 'mm_payment_received', array( $this, 'payment_received' ) );


		}

		/**
		 * Creates MM submenu item
		 *
		 * @access public
		 * @return void
		 */

		public function admin_menu() {

			$crm = wp_fusion()->crm->name;

			$id = add_submenu_page(
			        'mmdashboard',
			        $crm . ' Integration',
			        $crm,
			        'manage_options',
			        'mm-wpf-settings',
			        array($this, 'render_admin_menu')
			);

			add_action( 'load-' . $id, array($this, 'enqueue_scripts' ) );


			// Save settings

			if(!isset($_POST['wpf_mm_settings_nonce']))
				return;

			if(!wp_verify_nonce($_POST['wpf_mm_settings_nonce'], 'wpf_mm_settings')) {
				die('Security check. Invalid nonce.');
			}

			update_option( 'wpf_mm_settings', $_POST['wpf-settings']);

			$this->saved = true;

		}


		/**
		 * Enqueues WPF scripts and styles on MM options page
		 *
		 * @access public
		 * @return void
		 */

		public function enqueue_scripts() {

			wp_enqueue_style('bootstrap', WPF_DIR_URL . 'includes/admin/options/css/bootstrap.min.css');
			wp_enqueue_style('options-css', WPF_DIR_URL .'includes/admin/options/css/options.css');
			wp_enqueue_style('wpf-options', WPF_DIR_URL .'assets/css/wpf-options.css');

		}

		/**
		 * Renders MM submenu item
		 *
		 * @access public
		 * @return mixed
		 */

		public function render_admin_menu() { ?>

			<?php if ($this->saved) : ?>
		        <div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>
	    	<?php endif; ?>

			<div class="wrap">
				<h2><?php echo wp_fusion()->crm->name ?> Integration</h2>

				<form id="wpf-mm-settings" action="" method="post">
					<?php wp_nonce_field('wpf_mm_settings', 'wpf_mm_settings_nonce'); ?>
					<input type="hidden" name="action" value="update">

					<h4>Product Tags</h4>
					<p class="description">For each product below, specify tags to be applied in <?php echo wp_fusion()->crm->name ?> when purchased.</p>
					<br />

					<?php $view = new MM_ProductView(); ?>
					<?php $dataGrid = new MM_DataGrid($_REQUEST, "id", "desc", 10); ?>
					<?php $data = array_reverse($view->getViewData($dataGrid)); ?>
					<?php $settings = get_option('wpf_mm_settings'); ?>

					<table class="table table-hover" id="wpf-mm-products-table">
						<thead>
							<tr>
								<th>Product Name</th>
								<th>Price</th>
								<th>Apply Tags</th>
							</tr>
						</thead>
						<tbody>

							<?php foreach($data as $product) : ?>

								<?php if(!isset($settings['apply_tags'][$product->id])) $settings['apply_tags'][$product->id] = array(); ?>

								<tr>
									<td><?php echo $product->name; ?></td>
									<td>$<?php echo number_format((float)$product->price, 2, '.', ''); ?></td>
									<td><?php wpf_render_tag_multiselect( $settings['apply_tags'], 'wpf-settings', 'apply_tags', $product->id ) ?></td>
								</tr>

							<?php endforeach; ?>

						</tbody>

					</table>

					<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes" /></p>

				</form>

			</div>

		<?php 

		}

		/**
		 * Set field labels from MM field labels
		 *
		 * @access public
		 * @return array Meta fields
		 */

		public function set_contact_field_names( $meta_fields ) {

			$label_text = '<label class="label label-primary">MM</label> ';

			// Misc.

			$meta_fields['membership_level'] = array(
				'label'	=> $label_text . 'Membership Level ID',
				'type'	=> 'text'
				);

			$meta_fields['membership_level_name'] = array(
				'label'	=> $label_text . 'Membership Level Name',
				'type'	=> 'text'
				);

			$meta_fields['is_member'] = array(
				'label'	=> $label_text . 'Is Member',
				'type'	=> 'checkbox'
				);

			$meta_fields['is_free'] = array(
				'label'	=> $label_text . 'Member Is Free',
				'type'	=> 'checkbox'
				);

			$meta_fields['is_gift'] = array(
				'label'	=> $label_text . 'Member Is Gift',
				'type'	=> 'checkbox'
				);

			$meta_fields['phone'] = array(
				'label'	=> $label_text . 'Phone',
				'type'	=> 'text'
				);

			$meta_fields['coupon_code'] = array(
				'label'	=> $label_text . 'Coupon Code',
				'type'	=> 'text'
				);

			// Billing

			$meta_fields['billing_address'] = array(
				'label'	=> $label_text . 'Billing Address',
				'type'	=> 'text'
				);

			$meta_fields['billing_city'] = array(
				'label'	=> $label_text . 'Billing City',
				'type'	=> 'text'
				);

			$meta_fields['billing_state'] = array(
				'label'	=> $label_text . 'Billing State',
				'type'	=> 'text'
				);

			$meta_fields['billing_zip'] = array(
				'label'	=> $label_text . 'Billing ZIP',
				'type'	=> 'text'
				);

			$meta_fields['billing_country'] = array(
				'label'	=> $label_text . 'Billing Country',
				'type'	=> 'text'
				);

			// Shipping

			$meta_fields['shipping_address'] = array(
				'label'	=> $label_text . 'Shipping Address',
				'type'	=> 'text'
				);

			$meta_fields['shipping_city'] = array(
				'label'	=> $label_text . 'Shipping City',
				'type'	=> 'text'
				);

			$meta_fields['shipping_state'] = array(
				'label'	=> $label_text . 'Shipping State',
				'type'	=> 'text'
				);

			$meta_fields['shipping_zip'] = array(
				'label'	=> $label_text . 'Shipping ZIP',
				'type'	=> 'text'
				);

			$meta_fields['shipping_country'] = array(
				'label'	=> $label_text . 'Shipping Country',
				'type'	=> 'text'
				);

			// Custom fields

			$view = new MM_CustomFieldView();
			$dataGrid = new MM_DataGrid($_REQUEST, "id", "desc", 10);
			$data = $view->getViewData($dataGrid);

			foreach($data as $field_object) {

				if($field_object->type == 'input') {
					$type = 'text';
				} else {
					$type = $field_object->type;
				}

				$meta_fields['cf_' . $field_object->id] = array(
					'label'	=> $label_text . $field_object->display_name,
					'type'	=> $type
					);

			}

			return $meta_fields;

		}

		/**
		 * Triggered when new member is added
		 *
		 * @access  public
		 * @return  array Post data
		 */

		public function member_register( $post_data, $user_id ) {

			if(!isset($post_data['mm-security']))
				return $post_data;

			foreach($post_data as $key => $value) {

				if(strpos($key, 'mm_field_') !== FALSE) {
					unset($post_data[$key]);
					$key = str_replace('mm_field_', '', $key);
					$post_data[$key] = $value;
				} elseif(strpos($key, 'mm_') !== FALSE) {
					unset($post_data[$key]);
					$key = str_replace('mm_', '', $key);
					$post_data[$key] = $value;
				}

			}

			if(isset($post_data['email'])) {
				$post_data['user_email'] = $post_data['email'];
				unset($post_data['email']);
			}

			if(isset($post_data['password'])) {
				$post_data['user_pass'] = $post_data['password'];
				unset($post_data['password']);
			}

			return $post_data;

		}


		/**
		 * Triggered when a member's account is updated
		 *
		 * @access public
		 * @return void
		 */

		public function account_update( $member_data ) {

			wp_fusion()->user->push_user_meta($member_data['member_id'], $member_data);

		}


		/**
		 * Triggered when a payment is successfully processed
		 *
		 * @access public
		 * @return void
		 */

		public function payment_received( $order_data ) {

			$user_id = $order_data['member_id'];
			wp_fusion()->user->push_user_meta($user_id, $order_data);

			$settings = get_option('wpf_mm_settings');

			if(empty($settings) || !isset($settings['apply_tags']))
				return;

			$products = json_decode($order_data['order_products'], true);

			foreach($products as $product) {

				if(isset($settings['apply_tags'][$product['id']]))
					wp_fusion()->user->apply_tags($settings['apply_tags'][$product['id']], $user_id);

			}

		}



	}

	new WPF_MemberMouse;

}