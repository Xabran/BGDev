<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used for the BG Bucks
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/includes
 */

/**
 * The core plugin class.
 *
 *
 * @since      1.0.0
 * @package    Bgpr
 * @subpackage Bgpr/includes
 * @author     BoardGames <jetogonon@gmail.com>
 */
class BGBuckLog {

	/**
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Bgpr_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $table;

	var $id;


	/**
	 * This class handles the functions and hooks for bg points
	 *
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->table = 'bg_bucks_log';
		$this->id = 0;

	}

	public  function getAllBucksLog()
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$this->table} ORDER BY id ASC");
		return $results;
	}

	public function getData()
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE id = {$this->id} LIMIT 1");
		return $result;
	}



	public function insertData($data)
	{
		global $wpdb;

		$data['created_at'] = date('Y-m-d H:i:s');
		$data['modified_at'] = date('Y-m-d H:i:s');

		$result = $wpdb->insert( $wpdb->prefix . $this->table, $data ); 

		return $result;
	}


	public static function myBuckLog($user_id)
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bg_bucks_log WHERE user_id = {$user_id} ORDER BY created_at DESC");
		return $results;
	}

	public static function translate($str)
	{
		$str = str_replace('_',' ',ucfirst($str));

		return $str;
	}

	public static function myBuckLogTable($user_id)
	{
		$tbl ='';
		$data = self::myBuckLog($user_id);
		

		$tbl = '<table class="widefat table">';
		$tbl .= '<tr><th>Date</th><th><sup>+</sup>/-</th><th>Transaction Note</th><th>New Balance</th></tr>';
		if ($data):
			foreach ($data as $log):
				$plusMinus = ($log->negative==0) ? $log->bucks_earned : '-'.$log->bucks_earned;
				$tbl .= '<tr><td>'.self::formatDate($log->created_at).'</td><td>'.$plusMinus.'</td><td>'.self::translate($log->action_type).'</td>';
				$tbl .= '<td>'.$log->new_balance.'</td>';
				$tbl .= '</tr>';
			endforeach;			
		endif;
		$tbl .= '</table>';

		return $tbl;
	}

	public static function formatDate($str)
	{
		$time = strtotime($str);
		$formatted = date("m/d/Y", $time);

		return $formatted;
	}

}
