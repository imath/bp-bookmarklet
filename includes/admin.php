<?php
/**
 * Bookmarklet functions.
 *
 * @package BP Bookmarklet
 * @subpackage Functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Avoid a duplicate option in the Administration screen dropdown
 *
 * @since 3.0.0
 */
function bp_bookmarklet_unset_groups_activity_action() {
	$bp = buddypress();

	if ( isset( $bp->activity->actions->groups->bookmark_published ) ) {
		unset( $bp->activity->actions->groups->bookmark_published );
	}
}
add_action( 'bp_activity_admin_index', 'bp_bookmarklet_unset_groups_activity_action' );

/**
 * Add a metabox in Activity Edit Administration screen
 *
 * @since  3.0.0
 */
function bp_bookmarklet_activity_admin_meta_box() {
	add_meta_box(
		'bp_bookmarklet_preview',
		_x( 'Bookmark preview', 'activity admin edit screen', 'bp-bookmarklet' ),
		'bp_bookmarklet_activity_admin_preview_metabox',
		get_current_screen()->id,
		'normal',
		'high'
	);

	bp_bookmarklet()->admin_css_handle = 'bp_activity_admin_css';

	// Attach the inline style when on the Activity Administration screen
	add_action( 'bp_activity_admin_enqueue_scripts', 'bp_bookmarklet_activity_style', 20 );
}
add_action( 'bp_activity_admin_meta_boxes', 'bp_bookmarklet_activity_admin_meta_box' );

/**
 * Display the Activity Edit Administration screen metabox to preview the Bookmark
 *
 * @since  3.0.0
 */
function bp_bookmarklet_activity_admin_preview_metabox( $item )  {
	$bookmark = bp_bookmarklet_get_bookmark( $item->id );

	if ( false === strpos( $bookmark, '<div class="bookmarklet-inner">' ) ) {
		$embed = wp_oembed_get( $bookmark );

		if ( ! empty( $embed ) ) {
			$bookmark = $embed;
			wp_enqueue_script( 'wp-embed' );
		}
	}

	printf( '<div id="buddypress">%s</div>', $bookmark );
}

/**
 * Upgrade plugin
 *
 * @since  3.0.0
 */
function bp_bookmarklet_upgrade() {
	$db_version = bp_get_option( 'bp-bookmarklet-version', 0 );

	if ( version_compare( $db_version, bp_bookmarklet()->version, '<' ) ) {
		bp_update_option( 'bp-bookmarklet-version', bp_bookmarklet()->version );
	}
}
add_action( 'bp_admin_init', 'bp_bookmarklet_upgrade', 1200 );
