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
class BGBuck {

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

		$this->table = 'bg_bucks_rewards';
		$this->id = 0;

	}

	public  function getAllBucksRewards()
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


	public function updateData($data)
	{
		global $wpdb;

		$x = 1;
		$success = false;
		foreach ($_POST as $levl => $val):
			$x++;
			$data = ['points_required'=>$val];
			$id = str_replace('l_','',$levl);


			$where = ['id'=>$id];
			$result  = $wpdb->update( $wpdb->prefix . $this->table, $data, $where);
			
		endforeach;

		

		return true;
	}

	public function getDataByType($num_cart_items)
	{
		global $wpdb;

		$type = $num_cart_items.'_game_purchase';

		if ($num_cart_items > 5){
		 	$type = '5_game_purchase';
		}

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE action_type = '$type' LIMIT 1");
		return $result;
	}

}
