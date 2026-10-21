<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

/**
 * Doantion class which handles donation payments.
 *
 * @author Clayton Rogers
 * @since 3/18/16
 */
class Braintree_Donations {
	public static $_isModal;
	public static $_captureName;
	public static $_captureAddress;
	public static $_captureEmail;
	public static $_donationType;
	const TRANSACTION = 'transaction';
	const RECURRING = 'recurring';
	public static function init() {
		/* Short code for donation container. */
		add_shortcode ( 'braintree_donations', __CLASS__ . '::showTransactionDonationContainer' );
		
		add_shortcode('braintree_recurring_donation', __CLASS__ . '::showRecurringDonationContainer');
		
		/* Load scripts and styles. */
		add_action ( 'braintree_for_woocommerce_load_scripts', __CLASS__ . '::loadScripts' );
		
		/* Add ajax action for processing payment. */
		add_action ( 'wp_ajax_process_braintree_donation', __CLASS__ . '::processDonation' );
		
		/* Add ajax actino for users with no privileges. */
		add_action ( 'wp_ajax_nopriv_process_braintree_donation', __CLASS__ . '::processDonation' );
		
		add_filter('braintree_for_woocommerce_donation_attrs', __CLASS__ . '::add_tax_exempt');
		
		add_filter('braintree_for_woocommerce_donation_attrs', __CLASS__ . '::add_partner_code');
		
		self::initializeVariables ();
	}
	public static function initializeVariables() {
		self::$_isModal = BT_Manager ()->get_option ( 'donation_form_layout' ) === 'modal' ? true : false;
		self::$_captureAddress = BT_Manager ()->isActive ( 'donation_address' );
		self::$_captureName = BT_Manager ()->isActive ( 'donation_name' );
		self::$_captureEmail = BT_Manager ()->isActive ( 'donation_email' );
	}
	public static function loadScripts() {
		wp_enqueue_script ( 'braintree-dropin-script', BRAINTREE_DROPIN_JS, null, null, false );
		if (self::$_isModal) {
			wp_enqueue_style ( 'braintree-donation-style', WC_BRAINTREE_ASSETS . 'css/braintree-donations-modal.css' );
			wp_enqueue_script ( 'braintree-modal-donations', WC_BRAINTREE_ASSETS . 'js/donations-modal.js', array (
					'jquery' 
			), BT_Manager ()->version, true );
		} else {
			wp_enqueue_style ( 'braintree-donation-style', WC_BRAINTREE_ASSETS . 'css/braintree-donations-inline.css' );
			wp_enqueue_script ( 'braintree-inline-donations', WC_BRAINTREE_ASSETS . 'js/donations-inline.js', array (
					'jquery' 
			), BT_Manager ()->version, true );
		}
	}
	
	/**
	 * Process the donation payments.
	 */
	public static function processDonation() {
		if(isset($_POST['recurring_donaton_plan'])){
			self::processRecurringDonation();
		}else{
			self::processTransactionDonation();
		}
	}
	
	public static function processRecurringDonation() {
		$plan = $_POST ['recurring_donaton_plan'];
		$firstAndLast = explode ( ' ', BT_Manager ()->getRequestParameter ( 'donor_name' ) );
		$customerExists = isset ( $_POST ['customer_id_exists'] );
		// create the customer and payment method if none exists.
		if (! $customerExists) {
			$result = BT_Manager ()->createBraintreeCustomer ( wp_get_current_user()->ID, array (
					'firstName' => $firstAndLast [0],
					'lastName' => $firstAndLast [1],
					'email' => BT_Manager ()->getRequestParameter ( 'billing_email' ),
					'paymentMethodNonce' => BT_Manager ()->getRequestParameter ( 'payment_method_nonce' ) 
			) );
			if ($result->success) {
				$token = $result->customer->paymentMethods [0]->token;
			} else {
				BT_Manager ()->log->writeToLog ( sprintf ( 'Donation failure: Response: %s', print_r ( $response, true ) ) );
				wp_send_json ( array (
						'result' => 'failure',
						'message' => $response->message 
				) );
			}
		}
		$attribs = [];
		$attribs['planId'] = $plan;
		if($customerExists){
			$attribs['paymentMethodNonce'] = BT_Manager()->getRequestParameter('payment_method_nonce');
		}else{
			$attribs['paymentMethodToken'] = $token;
		}
		$response = Braintree_Subscription::create ( $attribs );
	if ($response->success) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation success: Response: %s', print_r ( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'success',
					'url' => self::getOption ( 'donation_success_url' )
			) );
		} else {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation failure: Response: %s', print_r ( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'failure',
					'message' => $response->message
			) );
		}
	}
	
	public static function processTransactionDonation(){
		$attrs = array (
				'amount' => BT_Manager ()->getRequestParameter ( 'donation_amount' ),
				'paymentMethodNonce' => BT_Manager ()->getRequestParameter ( 'payment_method_nonce' ),
				'options' => array (
						'submitForSettlement' => true
				)
		);
		$option = BT_Manager ()->get_option ( 'donation_merchant_account_id' );
		if (! empty ( $option )) {
			$attrs ['merchantAccountId'] = $option;
		}
		if (self::$_captureName) {
			$name = BT_Manager ()->getRequestParameter ( 'donor_name' );
			$firstAndLast = explode ( ' ', $name );
			$attrs ['billing'] = array (
					'firstName' => $firstAndLast [0],
					'lastName' => $firstAndLast [1]
			);
			$attrs ['customer'] = array (
					'firstName' => $firstAndLast [0],
					'lastName' => $firstAndLast [1]
			);
		}
		if (self::$_captureAddress) {
			if (! isset ( $attrs ['billing'] )) {
				$attrs ['billing'] = array ();
			}
			$attrs ['billing'] ['postalCode'] = BT_Manager ()->getRequestParameter ( 'postal_code' );
			$attrs ['billing'] ['streetAddress'] = BT_Manager ()->getRequestParameter ( 'street_address' );
		}
		if (self::$_captureEmail) {
			if (! isset ( $attrs ['customer'] )) {
				$attrs ['customer'] = array ();
			}
			$attrs ['customer'] ['email'] = BT_Manager ()->getRequestParameter ( 'billing_email' );
		}
		$attrs = apply_filters ( 'braintree_for_woocommerce_donation_attrs', $attrs );
		$response = BT_Manager ()->sale ( $attrs );
		if ($response->success) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation success: Response: %s', print_r ( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'success',
					'url' => self::getOption ( 'donation_success_url' )
			) );
		} else {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Donation failure: Response: %s', print_r ( $response, true ) ) );
			wp_send_json ( array (
					'result' => 'failure',
					'message' => $response->message
			) );
		}
	}
	
	/**
	 * Method that renders the donation container.
	 */
	public static function showDonationContainer($atts) {
		do_action ( 'braintree_for_woocommerce_load_scripts' );
		ob_start ();
		self::generateClientToken ();
		self::getAjaxUrl ();
		if (self::$_isModal) {
			self::showModalDonationContainer ( $atts );
		} else {
			self::showInlineDonationContainer ( $atts );
		}
		$html = ob_get_clean ();
		return $html;
	}
	
	public static function showTransactionDonationContainer($atts){
		self::$_donationType = self::TRANSACTION;
		return self::showDonationContainer($atts);
	}
	
	public static function showRecurringDonationContainer($atts){
		self::$_donationType = self::RECURRING;
		//fetch the subscription plans.
		global $plans;
		$plans = self::getRecurringDonationPlans($atts);
		return self::showDonationContainer($atts);
	}
	
	public static function getRecurringDonationPlans($atts){
		$plans = BT_Manager()->getBraintreePlans();
		$recurring_plans = array();
		foreach($atts as $index => $planId){
			$recurring_plans[] = BT_Manager()->getBraintreePlan($planId, $plans);
		}
		return $recurring_plans;
	}
	
	/**
	 * Method that generates the html for the modal donation container.
	 */
	public static function showModalDonationContainer($atts) {
		self::showModalButton ();
		self::buildModalContainer ( $atts );
	}
	
	/**
	 * Method that generates the inline html
	 */
	public static function showInlineDonationContainer($atts) {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/donation/inline-container.php';
	}
	
	/**
	 * Method that generates the html button for the modal container.
	 */
	public static function showModalButton() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/donation/modal-button.php';
	}
	
	/**
	 * Method that builds the modal container.
	 */
	public static function buildModalContainer($atts) {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/donation/modal-container.php';
	}
	public static function getModalButtonStyles() {
		$backgroundColor = self::getOption ( 'donation_modal_button_background' );
		$borderColor = self::getOption ( 'donation_modal_button_background' );
		$textColor = self::getOption ( 'donation_modal_button_text_color' );
		$style = 'background-color:' . $backgroundColor . '; border-color:' . $borderColor . '; color:' . $textColor;
		return $style;
	}
	public static function getDonationButtonStyles() {
		$backgroundColor = self::getOption ( 'donation_button_background' );
		$borderColor = self::getOption ( 'donation_button_border' );
		$textColor = self::getOption ( 'donation_button_text_color' );
		$style = 'background-color:' . $backgroundColor . '; border-color:' . $borderColor . '; color:' . $textColor;
		return $style;
	}
	public static function getOption($string) {
		return BT_Manager ()->get_option ( $string );
	}
	public static function buildInputField(array $attrs) {
		$html = '<div class="' . $attrs ['class'] . '"><div class="invalid-input-field"></div>
				<input type="' . $attrs ['type'] . '" name="' . $attrs ['name'] . '" id="' . $attrs ['id'] . '" placeholder="' . $attrs ['placeholder'] . '"/>
				</div>';
		return $html;
	}
	public static function buildSelectField(array $attrs) {
		$html = '<div class="' . $attrs ['class'] . '">
				<label for="' . $attrs ['id'] . '">
				<select type="' . $attrs ['type'] . '" name="' . $attrs ['name'] . '" id="' . $attrs ['id'] . '" placeholder="' . $attrs ['placeholder'] . '">
				' . $attrs ['options'] . '
				</select>
				</label>
				</div>';
		return $html;
	}
	public static function buildAmountField($atts) {
		$html = null;
		if (empty ( $atts )) {
			$html = self::buildInputField ( array (
					'type' => 'text',
					'placeholder' => self::getCurrency ( self::getOption ( 'donation_currency' ) ) . ' ' . __ ( 'Amount', 'braintree' ),
					'class' => 'div--donationInput',
					'name' => 'donation_amount',
					'id' => 'donation_amount' 
			) );
		} else if (count ( $atts ) == 1) {
			$html = '<div class="div--donationInput"><div class="invalid-input-field"></div>
				<span>' . self::getCurrency ( self::getOption ( 'donation_currency' ) ) . '</span><span>' . $atts [1] . '</span><input type="hidden" id="donation_amount" name="donation_amount" value="' . $atts [1] . '"/>
				</div>';
		} else {
			$html = '<div class="div--donationSelect"><span>' . __ ( 'Amount', 'braintree' ) . '</span><select id="donation_amount" name="donation_amount">';
			foreach ( $atts as $index => $attr ) {
				$html .= '<option value="' . $attr . '">' . self::getCurrency ( self::getOption ( 'donation_currency' ) ) . ' ' . $attr . '</option>';
			}
			$html .= '</select></div>';
		}
		return $html;
	}
	public static function generateClientToken() {
		$attribs = [ ];
		$customerId = BT_Manager ()->getBraintreeCustomerId ( wp_get_current_user ()->ID );
		if ($customerId && self::$_donationType === self::RECURRING) {
			$attribs = array (
					'customerId' => $customerId 
			);
			global $customerExists;
			$customerExists = true;
		}
		?>
		<input type="hidden" name="client_token" id="client_token" value="<?php echo BT_Manager()->getClientToken($attribs)?>" />
		<?php
	}
	public static function getAjaxUrl() {
		?>
		<input type="hidden" name="ajax_url" id="ajax_url" value="<?php echo admin_url().'admin-ajax.php?action=process_braintree_donation'?>" />
		<?php
	}
	public static function getCountryOptions($defaultCountry) {
		$html = null;
		foreach ( Braintree_Countries::$countries as $prefix => $country ) {
			$selected = $prefix === $defaultCountry ? 'selected' : '';
			$html .= '<option value="' . $prefix . '" ' . $selected . '>' . $country . '</option>';
		}
		return $html;
	}
	public static function getCurrency($country) {
		return Braintree_Currencies::getCurrencySymbol ( $country );
	}
	public static function getLoader() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/donation/loader.php';
	}
	public static function buildRecurringPaymentsSelectField($settings) {
		global $plans;
		$html = '<div class="'.$settings['class'].'"><label><select id="recurring_donaton_plan" name="recurring_donaton_plan">';
		foreach ( $plans as $plan ) {
			$html .= '<option value="' . $plan->id . '">' . $plan->description . '</option>';
		}
		$html .= '</select></label></div>';
		global $customerExists;
		if($customerExists){
			$html .= '<input type="hidden" name="customer_id_exists" id="customer_id_exists" value="true"/>';
		}
		return $html;
	}
	
	public static function add_tax_exempt($attrs)
	{
		if(BT_Manager()->isActive('donation_text_exempt')){
			$attrs['taxExempt'] = true;	
		}
		return $attrs;
	}
	
	public static function add_partner_code($attrs)
	{
		$attrs['channel'] = 'PaymentPlugins_BT';
		return $attrs;
	}
}
Braintree_Donations::init ();