<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_API
 * This class handles the main API functions of sending and recieving API calls.
 *
 * @since 1.0
 */
class WC_TradeGecko_API {

	public $access_token;

	public $refresh_token;

	public $token_url = 'https://api.tradegecko.com/oauth/token/';

	public $api_url = 'https://api.tradegecko.com/';

	public function __construct() {

		$this->client_id	= WC_TradeGecko_Init::get_setting( 'client_id' );
		$this->client_secret	= WC_TradeGecko_Init::get_setting( 'client_secret' );
		$this->redirect_uri	= WC_TradeGecko_Init::get_setting( 'redirect_uri' );
		$this->auth_code	= WC_TradeGecko_Init::get_setting( 'auth_code' );
		$this->privileged_access_token		= WC_TradeGecko_Init::get_setting( 'privileged_access_token' );
		$this->use_privileged_access_token	= '' != $this->privileged_access_token ? true : false;

		if ( ! class_exists( 'TG_Mutex' ) ) {
			require_once( 'mutex/class-tg-mutex.php' );
		}

		$this->mutex = new TG_Mutex( 'tradegecko-access-token-mutex' );

	}

	/**
	 * Check if we have a valid token <br />
	 * Get a new token, if we don't have a valid one.
	 *
	 * @access public
	 * @since 1.0
	 * @return boolean
	 * @throws Exception
	 */
	public function check_valid_access_token() {
		// Check for the correct token type
		if ( $this->use_privileged_access_token ) {
			$token = $this->is_valid_privileged_access_token();
		} else {
			$token = $this->is_valid_standard_access_token();
		}

		return $token;
	}

	/**
	 * Check, if we have a valid Privileged Access Token.
	 *
	 * @since 1.7
	 * @return boolean
	 * @throws Exception
	 */
	public function is_valid_privileged_access_token() {
		return $this->double_check_privileged_token();
	}

	/**
	 * Make a test request to the TG API to make sure we have a valid token
	 *
	 * @since 1.7
	 * @return bool
	 * @throws Exception
	 */
	private function double_check_privileged_token() {
		$token_checked = get_transient( 'validate_privileged_token' );

		if ( false === $token_checked ) {

			$auth_error = get_option( 'wc_tg_auth_error' );

			// We should not have an error logged already
			if ( 'error' == $auth_error ) {
				WC_TradeGecko_Init::add_log( 'Existing error with Privileged Access Token.' );
				throw new Exception( sprintf( __( 'Your Privileged Access Token is invalid or revoked. %sClick here%s to double check the token.', WC_TradeGecko_Init::$text_domain ),
					'<a href="'. WC_TradeGecko_Init::get_admin_url( 'api' ) .'">',
					'</a>' ), 400 );
			}

			$params = array(
				'method'	=> 'GET',
				'headers'	=> array( 'Authorization' => 'Bearer '. $this->get_token(), 'Content-Type' => 'application/json' ),
				'sslverify'	=> false,
				'timeout'	=> apply_filters( 'tradegecko_api_request_timeout', 60 ),
				'redirection'	=> 0,
				'user-agent'	=> 'WooCommerceTradeGecko/'. WC_TradeGecko_Init::VERSION,
			);

			// Build the request url
			$url = $this->api_url . 'users/current/';

			$validation = WC_TradeGecko_Init::get_decoded_response_body( $this->send( $url, $params ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Check for a valid Privileged Token Response: '. print_r( $validation, true ) );

			if ( isset( $validation->error ) ) {
				WC_TradeGecko_Init::add_log( 'Error validating the Privileged Access Token. '. $validation->error_description );

				// If the error is one of the error codes, we will mark auth error and prevent further syncs until
				// credentials are double checked
				if ( in_array( $validation->error, WC_TradeGecko_Init::auth_error_codes() ) ) {
					update_option( 'wc_tg_auth_error', 'error' );
				}

				throw new Exception( __( $validation->error_description,
					WC_TradeGecko_Init::$text_domain ), $validation->error );
			}

			WC_TradeGecko_Init::add_log( 'Privileged Access Token valid.' );

			// Set the time to re-check to 12 hours
			set_transient( 'validate_privileged_token', 'valid', 12 * HOUR_IN_SECONDS);
		}

		return true;
	}

	/**
	 * Check, if we have a valid Standard Access Token.
	 *
	 * @since 1.7
	 * @return boolean True, if token is obtained and valid
	 * @throws Exception When there is a problem with obtaining/validating the token
	 */
	public function is_valid_standard_access_token() {
		// We cannot have an access token, if any of the the main authentication parameters are missing from the settings.
		// These settings need to be present at all times
		if ( empty( $this->client_id ) ||
			empty( $this->client_secret ) ||
			empty( $this->redirect_uri ) ||
			empty( $this->auth_code ) )
		{
			throw new Exception( __( 'Cannot obtain TradeGecko API access token.'
				. ' Some or all of the essential API settings are missing.'
				. ' Please check "API Application Id", "API Secret", "Redirect URI" and "Authorization Code".'
				. ' If any of them are missing, please fill them in and re-authenticate the application.',
				WC_TradeGecko_Init::$text_domain ), 400 );
		}

		if ( ! $this->mutex->lock() ) {
			throw new Exception( __( 'Could not execute access token request.',
				WC_TradeGecko_Init::$text_domain ), 400 );
		}

		// Check if we have a valid access token saved in the database
		$this->access_token = get_transient( 'wc_tradegecko_api_access_token' );
		$this->refresh_token = get_option( 'wc_tradegecko_api_refresh_token' );

		if ( false === $this->access_token ) {

			$auth_error = get_option( 'wc_tg_auth_error' );

			// Don't attempt to obtain a token, if an error was already logged.
			// Just log the Error to the Sync Log and end the process.
			if ( 'error' == $auth_error ) {

				$this->mutex->unlock();

				throw new Exception( sprintf( __( 'There was an error with your credentials. %sClick here%s to obtain new Authorization code.', WC_TradeGecko_Init::$text_domain ),
					'<a href="'. WC_TradeGecko_Init::get_admin_url( 'api' ) .'">',
					'</a>' ), 400 );
			}

			// Add log
			WC_TradeGecko_Init::add_log( 'Access token expired. Obtaining a new access token' );

			if ( empty( $this->refresh_token ) ) {
				$token_data = $this->build_token_request( 'token' );
			} else {
				$token_data = $this->build_token_request( 'refresh_token' );
			}

			$token_data = json_decode( $token_data );

			if ( isset( $token_data->error ) ) {
				// We have an auth error so log it in a option field
				update_option( 'wc_tg_auth_error', 'error' );

				$this->mutex->unlock();

				throw new Exception( sprintf( __( 'Access token could not be generated.'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$token_data->error,
					$token_data->error_description ), 400 );
			}

			// Save the new refresh token
			update_option( 'wc_tradegecko_api_refresh_token', $token_data->refresh_token );
			$this->refresh_token = $token_data->refresh_token;

			// Save the access token to a transient.
			// Set it to expire 60 second earlier, to make sure no request is interupted because of expired token.
			set_transient( 'wc_tradegecko_api_access_token', $token_data->access_token, (int) $token_data->expires_in - 60 );
			$this->access_token = $token_data->access_token;

			$this->mutex->unlock();

			return true;
		}
		$this->mutex->unlock();

		return true;
	}

	/**
	 * Build an access token request
	 *
	 * @access public
	 * @since 1.0
	 * @param string $request
	 * @return string The json encoded string of the response.
	 */
	private function build_token_request( $request = 'token' ) {

		if ( 'token' == $request ) {
			$body = json_encode( array(
				'client_id'		=> $this->client_id,
				'client_secret'	=> $this->client_secret,
				'redirect_uri'	=> $this->redirect_uri,
				'code'		=> $this->auth_code,
				'grant_type'	=> 'authorization_code'
			));

		} else {
			$body = json_encode( array(
				'client_id'		=> $this->client_id,
				'client_secret'	=> $this->client_secret,
				'redirect_uri'	=> $this->redirect_uri,
				'refresh_token'	=> $this->refresh_token,
				'grant_type'	=> 'refresh_token'
			));
		}

		$url = $this->token_url;
		$params = array(
			'method'	=> 'POST',
			'headers'	=> array( 'Content-Type' => 'application/json' ),
			'body'		=> $body,
			'sslverify'	=> false,
			'timeout' 	=> apply_filters( 'tradegecko_token_request_timeout', 60 ),
			'user-agent'	=> 'WooCommerceTradeGecko/'. WC_TradeGecko_Init::VERSION,
		);

		$response = $this->send( $url, $params );

		// Add log
		WC_TradeGecko_Init::add_log( 'Token Response: '. print_r( $response, true ) );

		// Data we need is in the body of the response
		$data = $response['body'];

		return $data;

	}

	/**
	 * Build an API request
	 *
	 * @access public
	 * @since 1.0
	 * @param string $method Method of request GET, POST, PUT, DELETE.
	 * @param string $request_type The type of request performed exp: orders, products, order_line_items
	 * @param mixed|optional $request_body The request body. Can be associative array or a json string.
	 * @param int|optional $specific_id The ID of a specific item we want to request.
	 * @param array|optional $filters Parameters to filter the request by. Exp: ids, company_id, order_id, purchase_order_id, since etc.
	 * @return string The json encoded string of the response.
	 */
	public function process_api_request( $method, $request_type, $request_body = null, $specific_id = null, $filters = array() ) {

		$url = '';
		if ( $this->check_valid_access_token() ) {

			// Build the request url
			$url .= $this->api_url . $request_type .'/';

			// Add the id is we want to call a specific item
			if ( ! empty( $specific_id ) ) {
				$url .= $specific_id .'/';
			} elseif ( ! empty( $filters ) ) {
				// Add the filter query to the url
				$query = '';
				foreach ( $filters as $key => $value ) {
					// IDs should be a query with an array key
					if ( 'ids' == $key ) {
						$k = 'ids[]=';
						if ( is_array( $value ) ) {
							foreach ( $value as $v ) {
								$query .= $k . urlencode( $v ) .'&';
							}
						} else {
							$query .= $k . urlencode( $value ) .'&';
						}
						continue;
					}

					$query .= $key .'='. urlencode( $value ) .'&';
				}
				$query = trim( $query, '&' );

				$url .= '?'. $query;

			}

			$params = array(
				'method'	=> $method,
				'headers'	=> array( 'Authorization' => 'Bearer '. $this->get_token(), 'Content-Type' => 'application/json' ),
				'sslverify'	=> false,
				'timeout'	=> apply_filters( 'tradegecko_api_request_timeout', 60 ),
				'redirection'	=> 0,
				'user-agent'	=> 'WooCommerceTradeGecko/'. WC_TradeGecko_Init::VERSION,
			);

			// Add the body of the request if needed
			if ( ! empty( $request_body ) ) {
				$body = is_array( $request_body ) ? json_encode( $request_body ) : $request_body;
				$params['body'] = $body;
			}

			return $this->send( $url, $params );
		}

	}

	/**
	 * Send and Receive API calls
	 *
	 * @access public
	 * @since 1.0
	 * @param string $url URL to send the request to
	 * @param string $params The parameters of the request.
	 * @return string The json string of the response
	 */
	private function send( $url, $params ) {

		$return = array();

		// Send the request and get the response
		$response = wp_remote_post($url, $params);

		// If Error return the code and message
		if ( is_wp_error($response) ) {
			$return['body']['error'] = $response->get_error_code();
			$return['body']['error_description'] = $response->get_error_message();

			// Json encode the error as the main response is encoded, too.
			$return['body'] = json_encode( $return['body'] );
			return $return;
		}

		// Return code should be 200 < code < 300. If it's not in this range return error.
		if ( 200 > $response['response']['code'] || 300 <= $response['response']['code'] ) {

			$return['body']['error'] = $response['response']['code'];

			$desc = '';
			$body = json_decode( $response['body'] );

			// If we had a json response, we should have its object
			if ( is_object( $body ) ) {
				if ( isset( $body->errors ) ) {

					foreach( $body->errors as $code => $message ) {
						$desc .= $code .': '. $message[0] .', ';
					}

					$desc = substr( $desc, 0, -2 );

					$return['body']['error_description'] = $desc;
				} else {
					$return['body']['error_description'] = isset( $body->message ) ? $body->message : '';
				}

			} else {
				$return['body']['error_description'] = $response['response']['message'];
			}

			$return['body'] = json_encode( $return['body'] );
			return $return;
		}

		// Return the response body
		return $response;

	}

	/**
	 * Get the token we will use for requests generated
	 *
	 * @return string
	 */
	private function get_token() {
		// Check for the correct token type
		if ( $this->use_privileged_access_token ) {
			$token = $this->privileged_access_token;
		} else {
			$token = $this->access_token;
		}

		return $token;
	}

}