<?php 

class WPF_CRM_Queue {

	/**
	 * Holds the current active CRM object
	 */

	private $crm;

	/**
	 * Buffer for queued API calls
	 */

	private $buffer;

	public function __construct($crm) {

		$this->crm = $crm;
		$this->buffer = array();

		add_action( 'shutdown', array( $this, 'shutdown' ) );

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
	 * Routes queue-able API calls to the buffer
	 *
	 * @access  public
	 * @return  void
	 */

	public function __call( $method, $args ) {

		// Queue sending data
		if( $method == 'apply_tags' || $method == 'remove_tags' || $method == 'update_contact' ) {

			$this->add_to_buffer( $method, $args );
			return true;

		} else {

			$result = call_user_func_array(array($this->crm, $method), $args);
			return $result;

		}	

	}

	/**
	 * Adds API requests to the API buffer
	 *
	 * @access  private
	 * @return  void
	 */

	private function add_to_buffer( $method, $args ) {

		if($method == 'apply_tags' || $method == 'remove_tags') {
			$cid = $args[1];
		} else {
			$cid = $args[0];
		}

		if(!isset($this->buffer[$method])) {

			$this->buffer[$method] = array( $cid => $args );

		} elseif(!isset($this->buffer[$method][$cid])) {

			$this->buffer[$method][$cid] = $args;				

		} else {

			if($method == 'apply_tags' || $method == 'remove_tags') {

				$this->buffer[$method][$cid][0] = array_unique(array_merge($this->buffer[$method][$cid][0], $args[0]));

			} else {

				$this->buffer[$method][$cid][1] = array_merge($this->buffer[$method][$cid][1], $args[1]);

			}

		}

	}

	/**
	 * Executes the queued API requests on PHP shutdown
	 *
	 * @access  public
	 * @return  void
	 */

	public function shutdown() {

		if(empty($this->buffer))
			return;

		foreach($this->buffer as $method => $contacts) {

			foreach($contacts as $cid => $args) {
				call_user_func_array(array($this->crm, $method), $args);
			}
		}

	}


}