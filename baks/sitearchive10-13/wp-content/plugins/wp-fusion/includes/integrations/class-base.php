<?php 

class WPF_Integrations_Base {

	public function __construct() {

		$this->init();

	}

	/**
	 * Returns all registered plugin integrations, internal and external @todo not sure if necessary
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function get_integrations() {

		$integrations = array();
		foreach(get_declared_classes() as $class) {
			if(is_subclass_of( $class, 'WPF_Integrations_Base' ))
				$integrations[] = $class;
		}

		return $integrations;
		
	}

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {
		// intentionally left blank
	}

}