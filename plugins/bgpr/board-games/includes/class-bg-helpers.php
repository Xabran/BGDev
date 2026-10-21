<?php
/**
 * Package Included for Board Games Plugin
 * 
 * Do not Modify this under any circumtances
 *
 * @package     Board Games 
 * @subpackage  Board Games Facebook API
 * @author      SilverKenn
 * @version     1.0
 */
if( !defined( 'ABSPATH' ) ) exit;

// Duplication is not allowed
if( !class_exists( 'BG_help' ) ) { 

	class BG_help { 
		
		public static function oath_id() {
			if(isset($_COOKIE['board_gamesid'])){
				if(get_site_transient('n_'.$_COOKIE['board_gamesid']) !== false){
					return $_COOKIE['board_gamesid'];
	            }
	        }
	        $_COOKIE['board_gamesid'] = uniqid('bgid', true);
	        setcookie('board_gamesid', $_COOKIE['board_gamesid'], time() + 3600, '/');
	        set_site_transient('n_'.$_COOKIE['board_gamesid'], 1, 3600);
	        return $_COOKIE['board_gamesid'];
		}
	}

}