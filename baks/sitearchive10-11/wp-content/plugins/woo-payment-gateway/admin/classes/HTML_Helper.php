<?php
class HTML_Helper {
	private static $inputTypes = array (
			'text',
			'radio' 
	);
	private static $htmlTypes = array (
			'img',
			'span',
			'div',
			'label' 
	);
	private static $defaults = array (
			'title' => '',
			'value' => '',
			'type' => '',
			'tool_tip' => false,
			'decription' => '',
			'default' => '',
			'disabled' => false,
			'placeholder' => '',
			'class' => array () 
	);
	public static function buildSettings($key, $value) {
		$html = null;
		$value = wp_parse_args ( $value, self::$defaults );
		switch ($value ['type']) {
			case 'title' :
				$html = self::buildTitle ( $key, $value );
				break;
			case 'text' :
			case 'checkbox' :
			case 'radio' :
			case 'password' :
				$html = '<tr class="braintree-table-row">' . self::buildLabel ( $key, $value ) . self::buildInputHTML ( $key, $value ) . '</tr>';
				break;
			case 'select' :
				$html = '<tr class="braintree-table-row">' . self::buildLabel ( $key, $value ) . self::buildSelectHTML ( $key, $value ) . '</tr>';
				break;
			case 'textarea' :
				$html = '<tr class="braintree-table-row">' . self::buildLabel ( $key, $value ) . self::buildHTML_Element ( $key, $value );
				break;
			case 'custom' :
				$html = '<tr class="braintree-table-row">' . self::buildLabel ( $key, $value ) . self::callCustomFunction ( $value ) . '</tr>';
				break;
		}
		return $html;
	}
	private static function buildTitle($key, $value) {
		$title = '<div class="account-title">';
		$classes = implode ( ' ', $value ['class'] );
		if (! empty ( $value ['title'] )) {
			$title .= '<h1 class="' . $classes . '">' . $value ['title'] . '</h1>';
		}
		$title .= '</div>';
		if (! empty ( $value ['description'] )) {
			$title .= self::buildTitleDescription ( $value );
		}
		return $title;
	}
	
	/**
	 * Builds the input field from the given parameters.
	 * 
	 * @param string $key        	
	 * @param array $value        	
	 */
	private static function buildLabel($key, $value) {
		$label = false;
		if (isset ( $value ['title'] )) {
			$label = '<p><th><label class="settingsLabel">' . $value ['title'] . '</label>';
			if ($value ['tool_tip']) {
				$label .= self::buildToolTip ( $value );
			}
			$label .= '</th></p>';
		}
		
		return $label;
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param array $value        	
	 */
	private static function buildInputHTML($key, $value) {
		if (isset ( $value ['value'] ) && is_array ( $value ['value'] )) {
			return self::buildInputsHTML ( $key, $value );
		}
		$option = BT_Manager ()->get_option ( $key );
		$input = '<td class="braintree-settings-td"><input ';
		if (! empty ( $value ['type'] )) {
			$input .= 'type="' . $value ['type'] . '" ';
		}
		if (isset ( $key )) {
			$input .= 'name="' . $key . '" id="' . $key . '" ';
		}
		if (! empty ( $value ['placeholder'] )) {
			$input .= 'placeholder="' . $value ['placeholder'] . '" ';
		}
		if ($value ['disabled']) {
			$input .= 'disabled ';
		}
		if (! empty ( $value ['class'] ) && is_array ( $value ['class'] )) {
			$string = '';
			foreach ( $value ['class'] as $class ) {
				$string .= $class . ' ';
			}
			$input .= 'class="' . $string . '" ';
		} else
			$input .= 'class="" ';
		
		if (! empty ( $option )) {
			if ($value ['type'] === 'text') {
				$input .= 'value="' . BT_Manager ()->get_option ( $key ) . '" ';
			}
			if ($value ['type'] === 'checkbox') {
				$input .= 'checked="checked" ';
				if (! empty ( $value ['value'] )) {
					$input .= 'value="' . $value ['value'] . '" ';
				} else
					$input .= 'value="' . $value ['default'] . '" ';
			}
			if ($value ['type'] === 'password') {
				$input .= 'value="' . BT_Manager ()->get_option ( $key ) . '" ';
			}
		} else {
			if ($value ['type'] === 'text') {
				if (! empty ( $value ['value'] )) {
					$input .= 'value="' . $value ['value'] . '" ';
				} else
					$input .= 'value="' . $value ['default'] . '" ';
			} elseif ($value ['type'] === 'checkbox') {
				if (! empty ( $value ['value'] )) {
					$input .= 'value="' . $value ['value'] . '" ';
				} else
					$input .= 'value="yes" ';
			}
		}
		$input .= '/>';
		if (isset ( $value ['img'] )) {
			$class = '';
			if (! empty ( $value ['img'] ['class'] )) {
				$class = implode ( ' ', $value ['img'] ['class'] );
			}
			$input .= '<img src="' . $value ['img'] ['src'] . '" class="' . $class . '"/>';
		}
		$input .= '</td>';
		return $input;
	}
	public static function buildHTML_Element($key, $value) {
		$classes = implode ( ' ', $value ['class'] );
		$element = '<td class="braintree-settings-td"><' . $value ['type'] . ' name="' . $key . '" id="' . $key . '" class="' . $classes . '">';
		$element .= BT_Manager ()->get_option ( $key ) . '</' . $value ['type'] . '></td>';
		return $element;
	}
	public static function buildSelectHTML($key, $value) {
		$classes = implode ( ' ', $value ['class'] );
		$element = '<td class="braintree-settings-td"><select name="' . $key . '" id="' . $key . '" class="' . $classes . '">';
		$option = BT_Manager ()->get_option ( $key );
		if (isset ( $value ['options'] )) {
			foreach ( $value ['options'] as $i => $v ) {
				$element .= '<option value="' . $i . '" ';
				if (! empty ( $option )) {
					if ($option === $i) {
						$element .= 'selected="selected" ';
					}
				} else {
					if ($i === $value ['default']) {
						$element .= 'selected="selected" ';
					}
				}
				$element .= '>' . $v . '</option>';
			}
		} else {
			foreach ( $value ['value'] as $i => $v ) {
				$element .= '<option value="' . $v . '" ';
				if (! empty ( $option ) && $option === $v) {
					$element .= 'selected="selected" ';
				}
				$element .= '>' . $v . '</option>';
			}
		}
		$element .= '</select></td>';
		return $element;
	}
	private static function buildInputsHTML($key, $value) {
		$element = '<td class="braintree-settings-td">';
		$option = BT_Manager ()->get_option ( $key );
		foreach ( $value ['value'] as $index => $array ) {
			$element .= '<div class="braintree-settings-div"><input type="' . $value ['type'] . '" value="' . $index . '" ';
			if ($value ['type'] === 'radio') {
				$element .= 'name="' . $key . '" ';
			} elseif ($value ['type'] === 'checkbox') {
				$element .= 'name="' . $index . '" ';
			}
			if ($option == $index || ! empty ( $option [$index] )) {
				$element .= 'checked="checked" ';
			}
			$element .= '/>';
			if (in_array ( $value ['type'], self::$inputTypes )) {
				$element .= '<input type="' . $array ['type'] . '" ';
				foreach ( $array as $k => $v ) {
					$element .= $k . '="' . $v . '" ';
				}
				$element .= '/>';
			} elseif (in_array ( $array ['type'], self::$htmlTypes )) {
				$element .= self::buildHTMLElement ( $array, $array ['type'] );
			}
			$element .= '</div>';
		}
		$element .= '</td>';
		return $element;
	}
	private static function buildHTMLElement($array) {
		$type = $array ['type'];
		unset ( $array ['type'] );
		$element = '<' . $type . ' ';
		foreach ( $array as $k => $v ) {
			$element .= $k . '="' . $v . '" ';
		}
		$element .= '</' . $type . '>';
		return $element;
	}
	private static function buildToolTip($value) {
		return '<span class="braintree-tooltip"><img src="' . WC_BRAINTREE_ASSETS . 'images/question.png"/>
						<p class="tooltip-description">' . $value ['description'] . '</span>';
	}
	private static function buildTitleDescription($value) {
		return '<div class="braintree-title-description">' . $value ['description'] . '</div>';
	}
	
	/**
	 * Call the function specified and add the html.
	 * 
	 * @param array $value        	
	 */
	private static function callCustomFunction($value) {
		$class = implode ( ' ', $value ['class'] );
		return '<td class="braintree-settings-td' . ' ' . $class . '">' . call_user_func ( $value ['function'], $value ) . '</td>';
	}
}