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
 * Get the bookmarklet frame url for a given user ID
 *
 * @since  3.0.0
 *
 * @param  int $user_id The user id to get the bookmarklet frame for.
 * @return string       The url to the bookmarklet frame.
 */
function bp_bookmarklet_get_frame_url( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$user_link = bp_core_get_user_domain( $user_id );

	if ( empty( $user_link ) ) {
		return;
	}

	$user_link = trailingslashit( $user_link . bp_get_activity_slug() );

	return trailingslashit( $user_link . 'bp-bookmarklet-frame' );
}

/**
 * Get the link to the bookmarklet frame
 *
 * @since  3.0.0
 *
 * @param  string $name The current site name.
 * @return string       Javascript Bookmarklet.
 */
function bp_bookmarklet_get_bookmarklet_link( $name = '' ) {
	$url  = esc_url( bp_bookmarklet_get_frame_url() );

	if ( empty( $name ) ) {
		$name = esc_html( get_bloginfo( 'name' ) );
	}

	return apply_filters( 'bp_bookmarklet_get_bookmarklet_link',
		"javascript:(function(){f=&#39;" . $url . "?url=&#39;+encodeURIComponent(window.location.href)+&#39;&amp;title=&#39;+encodeURIComponent(document.title)+&#39;&amp;description=&#39;+encodeURIComponent((d=[].filter.call(document.getElementsByTagName('meta'),function(v){if(v.name==='description')return v;})[0])?d.content:null)+&#39;&amp;copied=&#39;+encodeURIComponent(''+(window.getSelection?window.getSelection():document.getSelection?document.getSelection():document.selection.createRange().text))+&#39;&amp;+v=1&amp;&#39;;a=function(){if(!window.open(f+&#39;noui=1&amp;jump=doclose&#39;,&#39;" . $name . "&#39;,&#39;location=O,links=0,scrollbars=0,toolbar=0,width=600,height=850&#39;))location.href=f+&#39;jump=yes&#39;};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})()",
		$url,
		$name
	);
}

/**
 * Is this the Bookmarklet frame ?
 *
 * @since 3.0.0
 *
 * @return bool True if the bookmarklet frame is requested.
 *              False otherwise.
 */
function bp_bookmarklet_is_frame() {
	return bp_bookmarklet()->is_bookmarklet_frame;
}

/**
 * Load the Bookmarklet frame
 *
 * @since 3.0.0
 */
function bp_bookmarklet_frame_screen() {
	if ( ! bp_bookmarklet_is_frame() ) {
		return;
	}

	// User
	if ( bp_is_user_activity() && 'bp-bookmarklet-frame' === bp_current_action() ) {
		if ( ! is_user_logged_in() ) {
			bp_core_redirect( wp_login_url( bp_get_requested_url() ) );
		}

		if ( ! bp_is_my_profile() ) {
			$query = parse_url( bp_get_requested_url(), PHP_URL_QUERY );

			if ( ! empty( $query ) ) {
				bp_core_redirect( bp_bookmarklet_get_frame_url() . '?' . $query );
			} else {
				return;
			}
		}

		bp_core_load_template( 'assets/bookmarklet-frame' );
	}
}
add_action( 'bp_screens', 'bp_bookmarklet_frame_screen' );

/**
 * Enqueue scripts for the Bookmarklet form
 *
 * @since 3.0.0
 */
function bp_bookmarklet_enqueue_form() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$params = array(
		'user_id'     => bp_loggedin_user_id(),
		'object'      => 'user',
		'post_nonce'  => wp_create_nonce( 'bp_bookmarklet_create', '_wpnonce_bookmarklet_create' ),
	);

	$user_displayname = bp_get_loggedin_user_fullname();

	if ( buddypress()->avatar->show_avatars ) {
		$width  = bp_core_avatar_thumb_width();
		$height = bp_core_avatar_thumb_height();
		$params = array_merge( $params, array(
			'avatar_url'    => bp_get_loggedin_user_avatar( array(
				'width'  => $width,
				'height' => $height,
				'html'   => false,
			) ),
			'avatar_width'  => $width,
			'avatar_height' => $height,
			'avatar_alt'    => sprintf( __( 'Profile photo of %s', 'bp-bookmarklet' ), $user_displayname ),
			'user_domain'   => bp_loggedin_user_domain()
		) );
	}

	$objects = array(
		'profile' => array(
			'text'                     => __( 'Post in: Profile', 'bp-bookmarklet' ),
			'autocomplete_placeholder' => '',
			'priority'                 => 5,
		),
	);

	// the groups component is active & the current user is at least a member of 1 group
	if ( bp_is_active( 'groups' ) && bp_has_groups( array( 'user_id' => bp_loggedin_user_id(), 'max' => 1 ) ) ) {
		$objects['group'] = array(
			'text'                     => __( 'Post in: Group', 'bp-bookmarklet' ),
			'autocomplete_placeholder' => __( 'Start typing the group name...', 'bp-bookmarklet' ),
			'priority'                 => 10,
		);
	}

	$params['objects'] = apply_filters( 'bp_bookmarklet_objects', $objects );

	$strings = array(
		'submitText'          => __( 'Publish', 'bp-bookmarklet' ),
		'cancelText'          => __( 'Cancel', 'bp-bookmarklet' ),
		'textareaPlaceholder' => sprintf( __( "What's new, %s?", 'bp-bookmarklet' ), bp_get_user_firstname( $user_displayname ) ),
		'textareaLabel'       => __( 'Post what\'s new', 'bp-bookmarklet' ),
		'errorGeneric'        => __( 'Error: the bookmark cannot be published.', 'bp-bookmarklet' ),
		'errorObject'         => __( 'Error: the bookmark cannot be published for the selected item.', 'bp-bookmarklet' ),
	);

	wp_enqueue_style ( 'bp-bookmarklet-style' );
	wp_enqueue_script( 'bp-bookmarklet-script' );

	// Finally print settings and strings
	wp_localize_script( 'bp-bookmarklet-script', 'BP_Bookmarklet', array( 'params' => $params, 'strings' => $strings ) );
}

/**
 * Register the Bookmark activity actions.
 *
 * @since 3.0.0
 */
function bp_bookmarklet_register_activity_actions() {
	$activity_params = array(
		'id'              => 'bookmark_published',
		'admin_filter'    => __( 'Bookmarked a site', 'bp-bookmarklet' ),
		'format_callback' => 'bp_bookmarklet_format_activity_action_published',
		'front_filter'    => __( 'Bookmarks', 'bp-bookmarklet' )
	);

	bp_activity_set_action(
		'activity',
		$activity_params['id'],
		$activity_params['admin_filter'],
		$activity_params['format_callback'],
		$activity_params['front_filter'],
		array( 'activity', 'member' ),
		100
	);

	if ( bp_is_active( 'groups' ) ) {
		bp_activity_set_action(
			'groups',
			$activity_params['id'],
			$activity_params['admin_filter'],
			$activity_params['format_callback'],
			$activity_params['front_filter'],
			array( 'group' ),
			100
		);
	}
}
add_action( 'bp_register_activity_actions', 'bp_bookmarklet_register_activity_actions' );

/**
 * Format the activity action at run time
 *
 * @since  3.0.0
 *
 * @param  string $action   The activity action
 * @param  object $activity The activity object
 * @return string           The activity action
 */
function bp_bookmarklet_format_activity_action_published( $action, $activity ) {
	if ( 'groups' === $activity->component && ! empty( $activity->item_id ) ) {
		$group = groups_get_group( array(
			'group_id'        => $activity->item_id,
			'populate_extras' => false,
		) );
		$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

		$action = sprintf(
			__( '%1$s bookmarked a new site in the group %2$s', 'bp-bookmarklet' ),
			bp_core_get_userlink( $activity->user_id ),
			$group_link
		);
	} else {
		$action = sprintf( __( '%s bookmarked a new site', 'bp-bookmarklet' ), bp_core_get_userlink( $activity->user_id ) );
	}

	return apply_filters( 'bp_bookmarklet_format_activity_action_published', $action, $activity );
}

/**
 * Head scripts for the Bookmarklet frame
 *
 * @since 3.0.0
 */
function bp_bookmarklet_frame_head_scripts() {
	do_action( 'bp_enqueue_scripts' );

	foreach ( wp_scripts()->queue as $js_handle ) {
		wp_dequeue_script( $js_handle );
	}

	// Enqueue WP Embed to make sure WordPress embeds will be displayed
	wp_enqueue_script( 'wp-embed' );

	$allowed_styles = apply_filters( 'bp_bookmarklet_frame_allowed_head_styles', array(
		'bp-legacy-css'          => true,
		'bp-parent-css'          => true,
		'bp-child-css'           => true,
		'bp-' . get_stylesheet() => true,
		'bp-' . get_template()   => true,
	) );

	foreach ( wp_styles()->queue as $css_handle ) {
		if ( isset( $allowed_styles[ $css_handle ] ) ) {
			continue;
		}

		wp_dequeue_style( $css_handle );
	}

	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	wp_enqueue_style( 'theme-style', get_stylesheet_uri(), array( 'open-sans' ) );

	// Print styles
	wp_print_styles();
}
add_action( 'bp_bookmarklet_frame_head', 'bp_bookmarklet_frame_head_scripts' );

/**
 * Fetches the embed code or Website metas/images for a link
 *
 * @since 3.0.0
 *
 * @return string json encoded Ajax response
 */
function bp_bookmarklet_fetch_link() {
	if ( empty( $_POST['url'] ) ) {
		wp_send_json_error();
	}

	$url   = esc_url_raw( $_POST['url'] );
	$bp    = buddypress();
	$embed = false;

	// First try oembed
	if ( isset( $bp->embed ) ) {
		$reset_return_false_on_fail = $bp->embed->return_false_on_fail;

		// Make sure to get an embed object or false
		$bp->embed->return_false_on_fail = true;

		// Fake an id
		add_filter( 'embed_post_id',      '__return_true'         );

		// Do not use cache
		add_filter( 'bp_embed_get_cache', '__return_empty_string' );

		$embed = $bp->embed->shortcode( array( 'bp_bookmarklet_embed' => true ), $url );

		// Reset BuddyPress Embded & remove filters
		$bp->embed->return_false_on_fail = $reset_return_false_on_fail;
		remove_filter( 'embed_post_id',      '__return_true'         );
		remove_filter( 'bp_embed_get_cache', '__return_empty_string' );
	}

	// We got the oembed object
	if ( $embed ) {
		// Finally send the successful oembed response
		wp_send_json_success( array(
			'type'        => 'oembed',
			'url'         => $url,
			'description' => $embed,
			'fetching'    => false,
		) );

	// No oembed, so let's fetch some data about the link
	} else {
		if ( ! class_exists( 'WP_Press_This' ) ) {
			require( ABSPATH . 'wp-admin/includes/class-wp-press-this.php' );
		}

		require_once( bp_bookmarklet()->includes_dir . 'class.php' );

		$link = new BP_Bookmarklet_This;
		$data = $link->source_data_fetch_fallback( $url );

		// Return an error to give the user some feedback
		if ( isset( $data['errors'] ) ) {
			$data['fetching'] = false;
			wp_send_json_error( $data );
		}

		if ( ! empty( $link->source_content ) && preg_match( '/<title>(.*?)<\/title>/', $link->source_content, $match ) ) {
			if ( ! empty( $match[1] ) ) {
				$data['_meta']['title'] = esc_html( trim( $match[1] ) );
			}
		}

		// Finally send the successful link response
		wp_send_json_success( array(
			'type'        => 'link',
			'url'         => $url,
			'title'       => html_entity_decode( $link->get_suggested_title( $data ) ),
			'description' => html_entity_decode( wp_kses( $link->get_suggested_content( $data ), array() ) ),
			'images'      => $link->get_images( $data ),
			'fetching'    => false
		) );
	}
}
add_action( 'wp_ajax_bp_bookmarklet_fetch_link', 'bp_bookmarklet_fetch_link' );

/**
 * Format an item for a json reply
 *
 * @since  3.0.0
 *
 * @param  object $item The object to format
 * @return array        The formatted object
 */
function bp_bookmarklet_prepare_item_js( $item ) {
	if ( empty( $item->id ) ) {
		return array();
	}

	$item_avatar_url = bp_core_fetch_avatar( array(
		'item_id'    => $item->id,
		'object'     => 'group',
		'type'       => 'thumb',
		'html'       => false
	) );

	return array(
		'id'          => $item->id,
		'name'        => $item->name,
		'avatar_url'  => $item_avatar_url,
		'object_type' => 'group',
	);
}

/**
 * Get Items to publish the Bookmark into (eg: groups)
 *
 * @since 3.0.0
 *
 * @return string json encoded Ajax response
 */
function bp_bookmarklet_get_items() {
	$response = array();

	if ( 'group' === $_POST['type'] ) {
		$groups = groups_get_groups( array(
			'user_id'           => bp_loggedin_user_id(),
			'search_terms'      => $_POST['search'],
			'show_hidden'       => true,
			'per_page'          => 5,
		) );

		wp_send_json_success( array_map( 'bp_bookmarklet_prepare_item_js', $groups['groups'] ) );
	} else {
		$response = apply_filters( 'bp_bookmarklet_get_items', $response, $_POST['type'] );
	}

	if ( empty( $response ) ) {
		wp_send_json_error( array( 'error' => __( 'No items were found.', 'bp-bookmarklet' ) ) );
	} else {
		wp_send_json_success( $response );
	}
}
add_action( 'wp_ajax_bp_bookmarklet_get_items', 'bp_bookmarklet_get_items' );

/**
 * Publish a new Bookmark
 *
 * @since 3.0.0
 *
 * @return string json encoded Ajax response
 */
function bp_bookmarklet_post_update() {
	$response = array(
		'error' => __( 'There was a problem publishing your bookmark. Please try again.', 'bp-bookmarklet' ),
	);

	// Bail if not a POST action.
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		wp_send_json_error( $response );
	}

	// Check the nonce.
	check_admin_referer( 'bp_bookmarklet_create', '_wpnonce_bookmarklet_create' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( $response );
	}

	$link_type = false;
	if ( ! empty( $_POST['type'] ) ) {
		$link_type = sanitize_key( $_POST['type'] );
		unset( $_POST['type'] );
	}

	$r = wp_parse_args( $_POST, array(
		'action'            => '',
		'content'           => '',
		'component'         => 'activity',
		'type'              => 'bookmark_published',
		'user_id'           => bp_loggedin_user_id(),
		'item_id'           => false,
		'hide_sitewide'     => false,
	) );

	if ( ! $link_type || empty( $r['url'] ) ) {
		wp_send_json_error( $response );
	}

	$user_link   = bp_core_get_userlink( $r['user_id'] );
	$r['action'] = sprintf( __( '%s bookmarked a new site', 'bp-bookmarklet' ), $user_link );

	if ( 'group' === $r['object'] && bp_is_active( 'groups' ) && $r['item_id'] ) {
		if ( ! groups_is_user_member( $r['user_id'], $r['item_id'] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( array( 'error' => __( 'To publish a bookmark in this group, you must be a member of it.', 'bp-bookmarklet' ) ) );
		}

		if ( (int) $r['item_id'] === (int) bp_get_current_group_id() ) {
			$group = groups_get_current_group();
		} else {
			$group = groups_get_group( array( 'group_id' => $r['item_id'] ) );
		}

		if ( ! empty( $group->id ) ) {
			$r['component'] = 'groups';
			$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

			$r['action'] = sprintf(
				__( '%1$s bookmarked a new site in the group %2$s', 'bp-bookmarklet' ),
				$user_link,
				$group_link
			);
		} else {
			wp_send_json_error( array( 'error' => __( 'Unknown group', 'bp-bookmarklet' ) ) );
		}

		if ( isset( $group->status ) && 'public' !== $group->status ) {
			$r['hide_sitewide'] = true;
		}
	}

	$activity_id = bp_activity_add( apply_filters( 'bp_bookmarklet_post_update_args', $r ) );

	if ( empty( $activity_id ) ) {
		wp_send_json_error( $response );
	}

	$r['url'] = esc_url_raw( $r['url'] );
	$bookmarklet_data = $r['url'];

	if ( 'link' === $link_type ) {
		$bookmarklet_data = (object) array_intersect_key( $r, array(
			'url'         => true,
			'title'       => true,
			'description' => true,
			'image'       => true,
		) );
	}

	bp_activity_add_meta( $activity_id, '_bookmarklet_data', $bookmarklet_data );

	wp_send_json_success( array( 'message' => __( 'Success! Bookmark published.', 'bp-bookmarklet' ) ) );
}
add_action( 'wp_ajax_bp_bookmarklet_post_update', 'bp_bookmarklet_post_update' );

/**
 * Output A bookmark when no content has been published inside the activity item.
 *
 * NB: If you override the activity/entry.php template within your theme, make sure
 * it includes the 'bp_activity_entry_content' action
 *
 * @since  3.0.0
 *
 * @return string Bookmark output
 */
function bp_bookmarklet_output_bookmark() {
	if ( 'bookmark_published' !== bp_get_activity_type() ) {
		return;
	}

	$bookmarklets = bp_bookmarklet()->prepended;

	if ( ! isset( $bookmarklets[ bp_get_activity_id() ] ) ) {
		global $activities_template;

		add_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );
		$output = apply_filters_ref_array( 'bp_get_activity_content_body', array( $activities_template->activity->content, &$activities_template->activity ) );
		remove_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		echo apply_filters( 'bp_bookmarklet_output_bookmark', $output );
	}
}
add_action( 'bp_activity_entry_content', 'bp_bookmarklet_output_bookmark' );

/**
 * Get A bookmark for a given activity ID
 *
 * @since  3.0.0
 *
 * @param  int    $activity_id The Activity ID
 * @return string              The bookmark output
 */
function bp_bookmarklet_get_bookmark( $activity_id ) {
	$bookmarklet = bp_activity_get_meta( $activity_id, '_bookmarklet_data' );
	$output      = '';

	if ( ! empty( $bookmarklet ) ) {
		bp_bookmarklet()->prepended[ $activity_id ] = $bookmarklet;

		if ( is_object( $bookmarklet ) ) {
			$url = '';
			if ( isset( $bookmarklet->url ) ) {
				$url = $bookmarklet->url;
			}

			$title = '';
			if ( isset( $bookmarklet->title ) ) {
				$title = $bookmarklet->title;
			}

			$image = '';
			if ( isset( $bookmarklet->image ) ) {
				$image = $bookmarklet->image;
			}

			$output_one = '';
			if ( ! empty( $image ) ) {
				$output_one = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '"><img src="' . esc_url( $image ) . '"></a>';
			}

			$output_two = '';
			if ( ! empty( $title ) ) {
				$output_two = '<h5><a href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a></h5>';
			}

			$description = '';
			if ( isset( $bookmarklet->description ) ) {
				$description = $bookmarklet->description;
			}

			$output_three = '';
			if ( ! empty( $description ) ) {
				$output_three = '<p class="description">' . esc_html( $description ) . '</p>';
			}

			$output = sprintf( '<div class="bookmarklet-inner">%1$s%2$s%3$s</div>',
				$output_one,
				$output_two,
				$output_three

			);
		} else {
			$output = $bookmarklet;
		}
	}

	return apply_filters( 'bp_bookmarklet_get_bookmark', $output, $bookmarklet, $activity_id );
}

/**
 * BuddyPress should include the activity object as an argument each time it uses
 * apply_filters( 'bp_get_activity_content_body' )
 *
 * @see https://buddypress.trac.wordpress.org/ticket/6971
 *
 * @since  3.0.0
 *
 * @param  object $activity The activity object
 */
function bp_bookmarklet_read_more_fix( $activity ) {
	if ( ! empty( $activity->type ) && 'bookmark_published' === $activity->type ) {
		bp_bookmarklet()->read_more_id = $activity->id;
	}
}
add_action( 'bp_legacy_theme_get_single_activity_content', 'bp_bookmarklet_read_more_fix', 10, 1 );

/**
 * Prepend the content with the Bookmark
 *
 * @since  3.0.0
 *
 * @param  string $content  The activity content
 * @param  object $activity The activity object
 * @return string           The activity content
 */
function bp_bookmarklet_prepend_bookmarklet( $content, $activity = null ) {
	$bp_bookmarklet = bp_bookmarklet();

	/**
	 * Make sure the Activity is an object (Activity Administration screen is using an array)
	 */
	if ( ! empty( $activity ) && ! is_object( $activity ) ) {
		$activity = (object) $activity;
	}

	if ( ( isset( $activity->type ) && 'bookmark_published' === $activity->type ) || ! empty( $bp_bookmarklet->read_more_id ) ) {
		if ( ! empty( $activity->id ) ) {
			$activity_id = $activity->id;
		} else {
			$activity_id = $bp_bookmarklet->read_more_id;
		}

		$bookmarklet = bp_bookmarklet_get_bookmark( $activity_id );

		if ( ! empty( $bookmarklet ) ) {
			$content = sprintf( "%s \n{$content}", $bookmarklet );
		}

		$bp_bookmarklet->read_more_id = false;
	}

	return $content;
}
add_filter( 'bp_get_activity_content_body',      'bp_bookmarklet_prepend_bookmarklet', 2, 2 );
add_filter( 'bp_activity_admin_comment_content', 'bp_bookmarklet_prepend_bookmarklet', 2, 2 );

/**
 * Add an inline style in activity streams
 *
 * @since  3.0.0
 */
function bp_bookmarklet_activity_style() {
	// Do not include this style for the Bookmarklet frame
	if ( bp_bookmarklet_is_frame() ) {
		return;
	}

	$bp_bookmarklet = bp_bookmarklet();

	// Only add the style for the activity streams
	if ( ! bp_is_group_activity() && ! bp_is_activity_component() && empty( $bp_bookmarklet->admin_css_handle ) ) {
		return;
	}

	$handle = 'bp-legacy-css';

	if ( ! empty( $bp_bookmarklet->admin_css_handle ) ) {
		$handle = $bp_bookmarklet->admin_css_handle;
	}

	/**
	 * Filter here to use another css handle
	 *
	 * @param string Css Handle to attach the inline style to.
	 */
	wp_add_inline_style( apply_filters( 'bp_bookmarklet_activity_css_handle', $handle ), '
		#buddypress .bookmarklet-inner {
			margin: 2px 2px 25px 2px;
			padding: 1em;
			-moz-box-shadow: 0px 0px 2px 0px #ccc;
			-webkit-box-shadow: 0px 0px 2px 0px #ccc;
			box-shadow: 0px 0px 2px 0px #ccc;
		}

		#buddypress .bookmarklet-inner:empty {
			display: none;
		}

		#buddypress .bookmarklet-inner img {
			max-width: 100%;
			display: block;
			margin: 0 auto;
		}

		#buddypress .bookmarklet-inner br {
			display: none;
		}

		#buddypress .bookmarklet-inner p.description {
			font-size: 80%;
			color: #888;
		}
	' );
}
add_action( 'bp_enqueue_scripts', 'bp_bookmarklet_activity_style', 20 );
