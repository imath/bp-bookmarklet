<?php
/**
 * Contains the bookmarklet frame template.
 *
 * @since 3.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php echo wp_get_document_title(); ?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<?php
	/**
	 * Print scripts or data in t<head> tag.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_bookmarklet_frame_head' );
	?>
</head>
<body <?php body_class(); ?>>

	<div id="buddypress">
		<?php bp_get_template_part( 'activity/bookmarklet-form' ); ?>
	</div>

<?php
/**
 * Print scripts or data before the closing body tag.
 *
 * @since 3.0.0
 */
do_action( 'bp_bookmarklet_frame_footer' );
?>
</body>
</html>
