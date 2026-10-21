<?php 

class WPF_CRM_Debug {

	/**
	 * Holds the current active CRM object
	 */

	private $crm;

	/**
	 * Whether or not a session has started
	 */

	private $started;

	/**
	 * Start time for the current session
	 */

	private $session_start_time;

	public function __construct($crm) {

		$this->crm = $crm;
		$this->started = false;

		add_action( 'shutdown', array( $this, 'shutdown' ), 20 );

	}

	/**
	 * Passes get requests to the base CRM class
	 *
	 * @access  public
	 * @return  void
	 */

	public function __get($name) {

		return $this->crm->$name;

	}

	/**
	 * Calls CRM class methods and logs result
	 *
	 * @access  public
	 * @return  void
	 */

	public function __call( $method, $args ) {

		if($this->started == false)
			$this->session_start();

		$start = microtime(true);
		$result = call_user_func_array(array($this->crm, $method), $args);
		$elapsed = microtime(true) - $start;

		error_log('Method: ' . $method . ' with args: ' . print_r($args, true) . 'Time elapsed: ' . $elapsed . '.');
		//$this->write_log( 'Method: ' . $method . ' with args: ' . print_r($args, true) . 'Time elapsed: ' . $elapsed . '.' );

		return $result;

	}

	/**
	 * Logs session start time
	 *
	 * @access  private
	 * @return  void
	 */

	private function session_start() {

		$this->session_start_time = microtime(true);
		$this->started = true;

		//$this->write_log('New session.');
		error_log('New session.');

	}

	/**
	 * Writes final messages to log on system shutdown
	 *
	 * @access  public
	 * @return  void
	 */

	public function shutdown() {

		if($this->started == true) {
			$elapsed = microtime(true) - $this->session_start_time;
			error_log('Session closed. Elapsed time ' . $elapsed);
			$this->write_log('Session closed. Elapsed time ' . $elapsed);
		}

	}

	/**
	 * Writes log messages
	 *
	 * @access  private
	 * @return  void
	 */

	private function write_log( $message ) {

		$logfile = WP_CONTENT_DIR . '/wp-fusion.log';

		// Get time of request
		if( ($time = $_SERVER['REQUEST_TIME']) == '') {
			$time = time();
		}

		// Get IP address
		if( ($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
			$remote_addr = "REMOTE_ADDR_UNKNOWN";
		}

		// Format the date and time
		$date = date("Y-m-d H:i:s", $time);

		// Append to the log file
		if($fd = @fopen($logfile, "a")) {
			$result = fputcsv($fd, array($date, $remote_addr, $message));
			fclose($fd);
		}

	}


}