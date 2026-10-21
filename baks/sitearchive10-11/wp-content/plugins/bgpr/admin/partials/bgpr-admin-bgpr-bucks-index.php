<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://boardgames.com
 * @since      1.0.0
 *
 * @package    Bgpr
 * @subpackage Bgpr/admin/partials
 */


$buck = new BGBuck;
$log = new BGBuckLog;
?>


<div class="wrap">

<?php
if ( isset ( $_GET['tab'] ) ) $this->bgpr_admin_bucks_tabs($_GET['tab']); else $this->bgpr_admin_bucks_tabs('actionpoints');

?>

<?php if ((!isset($_GET['tab'])) || $_GET['tab']=='bucks') : ?>
 	<div id="icon-themes" class="icon32"></div>
	<h2>BGPR Bucks</h2>
	<?php $rewards = $buck->getAllBucksRewards(); ?>
	<form name="level_form" action="admin.php?page=bgpr-bucks&tab=bucks" method="POST">
    	<table class="widefat fixed wp-list-table striped">
    		<thead>
    			<tr><th>Action Name</th><th>Action Type</th><th>Bucks Reward</th></tr>
    		</thead>
    		<tbody>
    		<?php foreach ($rewards as $r): ?>
    		<tr><td class="column-primary page-title"><?=$r->action_name?></td>
    			<td><?=$r->action_type?></td>
    			<td><input name="l_<?=$r->id?>" type="text" value="<?=$r->buck_reward?>"/></td>
    		</tr>
    		<?php endforeach;?>
    		</tbody>
    	</table>

	
    	<div class="pull-right">
    		<button type="submit" name="btn_save_changes" class="button button-primary button-large">Save Changes</button>
    	</div>
	</form>

<?php endif; ?>
<!-- end first tab screen bucks -->

<!-- start tab screen Members -->
<?php if ( isset($_GET['tab']) && $_GET['tab']=='members') : ?>
<h2>Members</h2>

<div id="edit_box" style="display:none;">
        <form id="form_member_edit" action="" method="POST">
            <input type="hidden" name="user_id" id="user_id" value="">
            <input type="hidden" name="orig_current_point_total" id="orig_current_point_total" value="">
            <input type="hidden" name="orig_total_bucks" id="orig_total_bucks" value="">
            <h3>Edit Member Points and Bucks</h3>
            <table class="form-table">
            <tr class="form-control">
                <th>Member Name</th>
                <td><input type="text" class="regular-text" name="user_name" id="user_name" disabled/></td>
            </tr>           
            <tr class="form-control">
                <th>Current Total Points</th>
                <td><input type="text" class="regular-text" name="bgpr_current_point_total" id="bgpr_current_point_total"/></td>
            </tr>

            <tr class="form-control">
                <th>Total Bucks Rewards</th>
                <td><input type="text" class="regular-text" name="bgpr_total_bucks" id="bgpr_total_bucks"/></td>
            </tr>

            <tr class="form-control">
                <th>Reason for Changing Data</th>
                <td><textarea name="reason" id="reason" rows="4" style="width:80%;"></textarea></td>
            </tr>
            <tr class="form-control">
                <th>&nbsp;</th>
                <td><input type="submit" class="button button-primary" value="Save Changes" name="btn_save" id="btn_save"/></td>
            </tr>
            </table>
        </form>
        <div class="pull-right"><a href="#" class="btn_closebox">[x]<a/><br/></div>
    </div>

    <?php $users = get_users( 'orderby=nicename&role=customer' );
    echo '<table class="widefat fixed wp-list-table striped">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Points</th><th>Earned Bucks</th><th>Level</th><th>Options</th></tr>
            </thead>
            <tbody>';
    foreach ( $users as $user ) {
        $bucks_received = get_user_meta($user->ID, 'bg_earned_bucks',true);
        $points_received = get_user_meta($user->ID, 'bgpr_current_point_total',true);
        $member_level = get_user_meta($user->ID, 'bgpr_current_level',true);
        echo '<tr>';
        echo '<td>' . esc_html( $user->user_nicename ) . '</td><td>' . esc_html( $user->user_email ) . '</td><td>'.$points_received.'</td><td>'.$bucks_received.'</td><td>'.$member_level.'</td><td><a href="" class="btn_edit_member" data="'.$user->ID.'">Edit</a></td>';
        echo '</tr>';
    }
    echo '</tbody>
    </table>'; 
    ?>
<?php endif; ?>
<!-- end tab screen Members -->


<!-- start tab Log -->
<?php if ( isset($_GET['tab']) && $_GET['tab']=='log') : ?>
<h2>Bucks Log</h2>
    

    <div id="icon-themes" class="icon32"></div>
    <h2>BGPR Bucks</h2>
    <?php $logs = $log->getAllBucksLog();?>
    <form name="level_form" action="admin.php?page=bgpr-bucks&tab=log" method="POST">
        <table class="widefat fixed wp-list-table striped">
            <thead>
                <tr><th>Date</th><th>User</th><th>Action Type</th><th>Admin Change</th></tr>
            </thead>
            <tbody>
            <?php 
            foreach ($logs as $l): 
                $user = get_userdata($l->user_id);
                $user_nicename = $user->user_nicename;

                ?>
                <tr><td class="column-primary page-title"><?=$l->created_at?></td>
                    <td><?=$user_nicename?></td>
                    <td><?=$l->action_type?></td>
                    <td><?=nl2br($l->admin_change_reason)?></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>


    </form>
<?php endif; ?>

<!-- end tab Log -->

</div>