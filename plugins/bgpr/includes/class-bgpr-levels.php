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
class BGLevel {

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

		$this->table = 'bg_levels';
		$this->id = 0;

	}

	public  function getAllPointLevels()
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

	public function updateLevel($user_id, $current_total_points)
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE points_required <= $current_total_points ORDER BY points_required DESC LIMIT 1");
		$levelName  =$result->level_name;

		$Level = filter_var($levelName,FILTER_SANITIZE_NUMBER_INT);

		$bgpr_current_level = get_user_meta($user_id, 'bgpr_current_level', true ); 

		$current_level = filter_var($bgpr_current_level, FILTER_SANITIZE_NUMBER_INT);

		if ($Level > $curLevel){
			update_user_meta($user_id, 'bgpr_current_level', $Level , $current_level ); 
		}
	}

	public function next_level($points)
	{
		global $wpdb;

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE points_required >  $points ORDER BY id ASC LIMIT 1");
		//$nextLevel  =$result->id;
		$nextLevel_pointsrequired  =$result->points_required;

		return $nextLevel_pointsrequired;
	}


	public function user_current_level_box()
	{
		$user = wp_get_current_user();
		$level = get_user_meta($user->ID, 'bgpr_current_level', 1);
		$myBGPoints = get_user_meta($user->ID, 'bgpr_current_point_total',true);

		$str = 'Your Current Level: '.  $level;
		$nextLevelPointsReq = $this->next_level($myBGPoints);
		$pointsNeeded = $nextLevelPointsReq - $myBGPoints;
		$str .= '<br/>Points required to reach next level: '. $pointsNeeded;
		$str .= '<br/>Next Level Reward: ';
		$str .= '<br/><br/>';

		return $str;
	}
}
