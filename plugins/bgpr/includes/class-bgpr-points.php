<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
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
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Bgpr
 * @subpackage Bgpr/includes
 * @author     BoardGames <jetogonon@gmail.com>
 */
class BGPointActions {

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

		$this->table = 'bg_action_points';
		$this->id = 0;

	}

	public  function getAllActionPoints()
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


	public function actionTypeArray()
	{
		$arr =['signup_bonus','product_review','wantlist_add','lovelist_add','ownlist_add'];

		return $arr;
	}
	
	public function updateData($data)
	{
		global $wpdb;
		
		$where = ['id'=>$this->id];
		$result  = $wpdb->update( $wpdb->prefix . $this->table, $data, $where);

		return $result;
	}

	public function getDataByType($type)
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE action_type = '$type' LIMIT 1");
		return $result;
	}

}
