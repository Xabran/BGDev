<?php

ob_start();
$ct = wp_get_theme();
$this->theme = $ct;
$item_name = $this->theme->get('Name'); 
$tags = $this->theme->Tags;
$screenshot = $this->theme->get_screenshot();
$class = $screenshot ? 'has-screenshot' : '';

$customize_title = sprintf( __( 'Customize &#8220;%s&#8221;','cmk' ), $this->theme->display('Name') );

?>
<div id="current-theme" class="<?php echo esc_attr( $class ); ?>">
	<?php if ( $screenshot ) : ?>
		<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
		<a href="<?php echo wp_customize_url(); ?>" class="load-customize hide-if-no-customize" title="<?php echo esc_attr( $customize_title ); ?>">
<img src="<?php echo esc_url( $screenshot ); ?>" alt="<?php esc_attr_e( 'Current theme preview' ); ?>" />
		</a>
		<?php endif; ?>
		<img class="hide-if-customize" src="<?php echo esc_url( $screenshot ); ?>" alt="<?php esc_attr_e( 'Current theme preview' ); ?>" />
	<?php endif; ?>

	<h4>
		<?php echo $this->theme->display('Name'); ?>
	</h4>

	<div>
		<ul class="theme-info">
<li><?php printf( __('By %s','cmk'), $this->theme->display('Author') ); ?></li>
<li><?php printf( __('Version %s','cmk'), $this->theme->display('Version') ); ?></li>
<li><?php echo '<strong>'.__('Tags', 'cmk').':</strong> '; ?><?php printf( $this->theme->display('Tags') ); ?></li>
		</ul>
		<p class="theme-description"><?php echo $this->theme->display('Description'); ?></p>
		<?php if ( $this->theme->parent() ) {

printf( ' <p class="howto">' . __( 'This <a href="%1$s">child theme</a> requires its parent theme, %2$s.' ) . '</p>',
	__( 'http://codex.wordpress.org/Child_Themes','cmk' ),
	$this->theme->parent()->display( 'Name' ) );
		} ?>
		
	</div>

</div>

<?php
$item_info = ob_get_contents();
    
ob_end_clean();
$sections = array(
	'icon'   => 'el el-info-circle',
	'title'  => __( 'Theme Information', 'cmk' ),
	'desc'   => __( '<!--p class="description">This is the Description. Again HTML is allowed</p-->', 'cmk' ),
	'fields' => array(
		array(
			'id'      => 'opt-raw-info',
			'type'    => 'raw',
			'content' => $item_info,
		)
	),
);
Redux::setSection( $opt_name, $sections);
if ( file_exists( trailingslashit( dirname( __FILE__ ) ) . 'README.html' ) ) {
	$tabs['docs'] = array(
		'icon'    => 'el el-book',
		'title'   => __( 'Documentation', 'cmk' ),
		'content' => nl2br( file_get_contents( trailingslashit( dirname( __FILE__ ) ) . 'README.html' )	)
	);
	Redux::setHelpTab( $opt_name, $tabs );
}