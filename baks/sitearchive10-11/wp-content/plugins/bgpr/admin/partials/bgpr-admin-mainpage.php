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


$points = new BGPoints;
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->




<div class="wrap">
     
    <div id="icon-themes" class="icon32"></div>
    	<h2>BGPR Action Points</h2>
    	<?php $actionPoints = $points->getAllActionPoints(); ?>

    	<table class="widefat fixed wp-list-table striped">
    		<thead>
    			<tr><th>Action Name</th><th>Action Type</th><th>Action Value</th><th>Options</th></tr>
    		</thead>
    		<tbody>
    		<?php foreach ($actionPoints as $act): ?>
    		<tr><td class="column-primary page-title"><?=$act->action_name?></td><td><?=$act->action_type?></td><td><?=$act->action_value?></td>
    			<td><a href="admin.php?page=bgpr&action=edit&id=<?=$act->id?>">Edit</a>&nbsp;<a href="admin.php?page=bgpr&action=delete&id=<?=$act->id?>">Delete</a></td>
    		</tr>
    		<?php endforeach;?>
    		</tbody>
    	</table>

         
</div><!-- /.wrap -->
