<?php
/**
 * Main and only template of the plugin
 *
 * @package BP Bookmarklet
 *
 */
if( bp_bookmarklet_is_bp_default() )
	get_header( 'buddypress' );
?>

<div id="buddypress">
	
	<h1 class="bookmarklet_title"><?php bkmklet_the_page_title();?></h1>
	
	<?php do_action( 'template_notices' ); ?>
	
	<?php if ( !is_user_logged_in() ) : ?>
		
		<?php wp_login_form();?>
		
	<?php else:?>
		
		<?php do_action( 'bp_before_directory_bookmarklet' ); ?>
	
		<?php bp_get_template_part( 'activity/post-form' );?>
		
		<div id="link-result"></div>
		
		<?php do_action( 'bp_after_directory_bookmarklet' ); ?>
		
	<?php endif;?>
	
</div>

<?php
if( bp_bookmarklet_is_bp_default() )
	get_footer( 'buddypress' );
?>