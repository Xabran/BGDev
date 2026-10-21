<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the BG points index page
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/admin/partials
 */


$points = new BGPointActions;
$levels = new BGLevel;
$pointLog = new BGPointLog;
?>


<div class="wrap">

<?php
if ( isset ( $_GET['tab'] ) ) $this->bgpr_admin_points_tabs($_GET['tab']); else $this->bgpr_admin_points_tabs('actionpoints');

?>

	<?php if ((!isset($_GET['tab'])) || $_GET['tab']=='') : ?>
	<div id="edit_box" style="display:none;">
		<form id="form_point_edit" action="" method="POST">
			<input type="hidden" name="p_id" id="p_id" value="">
			<h3>Edit Action Point</h3>
			<table class="form-table">
			<tr class="form-control">
				<th>Action Name</th>
				<td><input type="text" class="regular-text" name="action_name" id="action_name"/></td>
			</tr>
			<tr class="form-control">
				<th>Action Type</th>
				<td>
					<select id="action_type" name="action_type">
					<?php foreach($points->actionTypeArray() as $act): ?>
						<option value="<?=$act?>"><?=$act?></option>
					<?php endforeach;?>
					</select>
				</td>
			</tr>
			<tr class="form-control">
				<th>Action Point</th>
				<td><input type="text" class="regular-text" name="action_value" id="action_value"/></td>
			</tr>
			<tr class="form-control">
				<th>&nbsp;</th>
				<td><input type="submit" class="button button-primary" value="Save Changes" name="btn_save" id="btn_save"/></td>
			</tr>
			</table>
		</form>
		<div class="pull-right"><a href="#" class="btn_closebox">[x]<a/><br/></div>
	</div>

	<div id="flashMessage"></div>
     
    <div id="icon-themes" class="icon32"></div>
    	<h2>BGPR Action Points</h2>
    	<?php $actionPoints = $points->getAllActionPoints(); ?>

    	<table class="widefat fixed wp-list-table striped" id="tbl_action_points">
    		<thead>
    			<tr><th>Action Name</th><th>Action Type</th><th>Action Value</th><th>Options</th></tr>
    		</thead>
    		<tbody>
    		<?php foreach ($actionPoints as $act => $a): ?>
    		<tr class="row_<?=$a->id?>"><td class="column-primary page-title"><?=$a->action_name?></td><td><?=$a->action_type?></td><td><?=$a->action_value?></td>
    			<td><a href="admin.php?page=bgpr&action=edit&id=<?=$a->id?>" class="btn_edit" data="<?=$a->id?>">Edit</a>&nbsp;<!-- <a href="admin.php?page=bgpr&action=delete&id=<?=$a->id?>" data="<?=$a->id?>" class="btn_delete">Delete</a> --></td>
    		</tr>
    		<?php endforeach;?>
    		</tbody>
    	</table>

    <?php endif; ?>


    <?php if ((isset($_GET['tab'])) && $_GET['tab']=='levels') : ?>

    		<?php if (isset($_POST['btn_save_changes'])) :   

    				unset($_POST['btn_save_changes']);
    				$result = $levels->updateData($_POST); 
    				
    				if ($result==true):
    					echo '<div id="flashMessage">
    							<div id="message" class="updated notice notice-success is-dismissible"><p>Successfully updated the Levels data!</p></div></div>';
    				endif;
    				
    			endif; 
    		?>
    				

			    <div id="icon-themes" class="icon32"></div>
			    	<h2>BGPR Levels</h2>
			    	<?php $pointLevels = $levels->getAllPointLevels(); ?>
			    	<form name="level_form" action="admin.php?page=bgpr&tab=levels" method="POST">
				    	<table class="widefat fixed wp-list-table striped">
				    		<thead>
				    			<tr><th>Level Name</th><th>Points Required</th></tr>
				    		</thead>
				    		<tbody>
				    		<?php foreach ($pointLevels as $l): ?>
				    		<tr><td class="column-primary page-title"><?=$l->level_name?></td><td><input name="l_<?=$l->id?>" type="text" value="<?=$l->points_required?>"/></td>
				    		</tr>
				    		<?php endforeach;?>
				    		</tbody>
				    	</table>
			    	
				    	<div class="pull-right">
				    		<button type="submit" name="btn_save_changes" class="button button-primary button-large">Save Changes</button>
				    	</div>
			    	</form>
    <?php endif; ?>

    <?php if ((isset($_GET['tab'])) && $_GET['tab']=='log') : ?>
    	
    

		    <div id="icon-themes" class="icon32"></div>
		    <h2>Points Log</h2>
		    <?php $logs = $pointLog->getAllPointsLog();?>
    
        <table class="widefat fixed wp-list-table striped">
            <thead>
                <tr><th>Date</th><th>User</th><th>Action Type</th><th>Admin Change Reason</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): 
                $user = get_userdata($l->user_id);
                $user_nicename = $user->user_nicename;
                $actionname = BGPointLog::getAction($l->action_type_id);
            ?>
            <tr><td class="column-primary page-title"><?=$l->created_at?></td>
                <td><?=$user_nicename?></td>
                <td><?=$actionname?></td>
                <td><?=nl2br($l->admin_change_reason)?></td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>

    <?php endif; ?>

</div><!-- /.wrap -->
