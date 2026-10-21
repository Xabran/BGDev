<?php

/**
 * The file that defines the core plugin class
 *
 * A class that is used for BG Point Log
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
class BGPointLog {

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

		$this->table = 'bg_points_log';
		$this->id = 0;

	}

	public  function getAllPointsLogByUser($user_id)
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE user_id = {$user_id} ORDER BY created_at DESC");
		return $results;
	}

	public function getAllPointsLog()
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$this->table} ORDER BY created_at DESC");
		return $results;
	}

	public static function getAction($id)
	{
		global $wpdb;

		$results = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bg_action_points WHERE id = {$id}");
		return $results->action_type;
	}

	public function getData()
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE id = {$this->id} LIMIT 1");
		return $result;
	}
	
	public function updateData($data)
	{
		global $wpdb;

		$where = ['id'=>$this->id];
		$result  = $wpdb->update( $wpdb->prefix . $this->table, $data, $where);

		return $result;
	}

	public function insertData($data)
	{
		global $wpdb;

		$data['created_at'] = date('Y-m-d H:i:s');

		$result = $wpdb->insert( $wpdb->prefix . $this->table, $data ); 

		return $result;
	}

	public static function myPointsLog($user_id)
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bg_points_log WHERE user_id = {$user_id} ORDER BY created_at DESC");
		return $results;
	}


	public static function myPointsLogTable($user_id)
	{
		$tbl ='';
		$data = self::myPointsLog($user_id);
		

		$tbl = '<table class="widefat table">';
		$tbl .= '<tr><th>Date</th><th><sup>+</sup>/-</th><th>Transaction Note</th><th>New Balance</th></tr>';
		if ($data):
			foreach ($data as $log):
				$plusMinus = ($log->negative==0) ? $log->points_earned : '-'.$log->points_earned;
				$tbl .= '<tr><td>'.self::formatDate($log->created_at).'</td><td>'.$plusMinus.'</td><td>'.self::getActionName($log->action_type_id).'</td>';
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

	public static function getActionName($id)
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bg_action_points WHERE id = {$id} LIMIT 1");
		return $result->action_name;
	}

}
