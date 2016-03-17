<?php

/**
 * Checks if the current theme has something to do with BP Default or is a standalone BuddyPress theme
 *
 * @uses get_stylesheet() to check for BP Default as the current theme
 * @uses get_template() to check for BP Default child theme
 * @uses current_theme_supports() to check for a standalone theme
 * @return boolean true or false
 *
 * @since BuddyPress 1.7
 */
function bp_bookmarklet_is_bp_default() {
	if( in_array( 'bp-default', array( get_stylesheet(), get_template() ) ) )
        return true;

    else if( current_theme_supports( 'buddypress') )
    	return true;

    else
        return false;
}

/**
 * Filters BuddyPress templates stack to reference the plugin's templates dir
 *
 * @uses bp_is_current_component() to check we're on the bookmarklet directory page
 * @uses bp_bookmarklet_is_bp_default() to check it's a WordPress theme
 * @uses bkmklet_get_templates_dir() to get plugin's templates dir
 * @return array the template stack
 *
 * @since BuddyPress 1.7
 */
function bp_bkmklet_get_template_part( $templates ) {
	
	if ( bp_is_current_component( 'bookmarklet' ) && !bp_bookmarklet_is_bp_default() ) {
		
		$templates[] = bkmklet_get_templates_dir();
	}
	
	return $templates;
}

add_filter( 'bp_get_template_stack', 'bp_bkmklet_get_template_part', 10, 1 );

/**
 * Filters BuddyPress templates stack to reference the plugin's templates dir
 *
 * @param array $found_template the list of available templates 
 * @param string $templates
 * @uses bp_bookmarklet_is_bp_default() to abort if it's a WordPress theme
 * @uses bp_is_current_component() to abort if we're not on the bookmarklet directory page
 * @uses bkmklet_get_templates_dir() to get plugin's templates dir
 * @return array the filtered list of templates
 */
function bp_bkmklet_load_template_filter( $found_template, $templates ) {
	global $bp,$bp_deactivated;
	
	if ( !bp_bookmarklet_is_bp_default() )
		return $found_template;

	//Only filter the template location when we're on the example component pages.
	if ( !bp_is_current_component( 'bookmarklet' ) )
		return $found_template;

	foreach ( (array) $templates as $template ) {
		if ( file_exists( STYLESHEETPATH . '/' . $template ) )
			$filtered_templates[] = STYLESHEETPATH . '/' . $template;
		else
			$filtered_templates[] = bkmklet_get_templates_dir() . $template;
	}

	$found_template = $filtered_templates[0];

	return apply_filters( 'bp_bkmklet_load_template_filter', $found_template );
}

add_filter( 'bp_located_template', 'bp_bkmklet_load_template_filter', 10, 2 );

/**
 * Hides admin bar for the plugin's directory page
 *
 * @param boolean $show 
 * @return boolean false
 */
function bkmklet_hide_admin_bar( $show ) {
	return false;
}

/**
 * Informs we're on bookmarklet's directory page, loads some css / script, and disable Admin Bar
 *
 * @uses bp_displayed_user_id() to check we're not on a member's page
 * @uses bp_is_current_component() to check we're on the bookmarklet directory page
 * @uses bp_current_action() to check no action is set
 * @uses bp_update_is_directory() to inform BuddyPress we're on bookmarklet directory area
 * @uses wp_enqueue_style() to enqueue the script into the WordPress style stack
 * @uses bkmklet_get_css_url() to get plugin's url to css folder
 * @uses bkmklet_get_version() to get plugin's version
 * @uses wp_enqueue_script() to enqueue the script into the WordPress script stack
 * @uses bkmklet_get_js_url() to get plugin's url to js folder
 * @uses wp_localize_script() to attach translatable messages into the js file
 * @uses bp_core_load_template() to ask BuddyPress to load the plugin's template
 */
function bp_bkmklet_screen_index() {
	
	if ( !bp_displayed_user_id() && bp_is_current_component( 'bookmarklet' ) && !bp_current_action() ) {
		bp_update_is_directory( true, 'bookmarklet' );
		
		add_filter( 'show_admin_bar', 'bkmklet_hide_admin_bar', 99999, 1 );
		remove_action( 'wp_head', '_admin_bar_bump_cb');

		wp_enqueue_style( 'bookmarklet-css', bkmklet_get_css_url() . 'bp-bookmarklet-style.css', false, bkmklet_get_version() );
		wp_enqueue_script( 'bookmarklet-js', bkmklet_get_js_url() .'bookmarklet.js', array( 'jquery' ), bkmklet_get_version() );
		wp_localize_script('bookmarklet-js', 'bookmarklet_vars', array(
			'copied_message'  => __( '[Your content here]', 'bp-bookmarklet' ),
			'shared_message'  => __( 'Success, link shared!', 'bp-bookmarklet' ),
			'loading_message' => __( 'Loading the images, please wait..', 'bp-bookmarklet' ),
			'no_image'        => __( 'Sorry no images were found', 'bp-bookmarklet' ),
			'arrows_message'  => __( 'Use the arrows to select the image', 'bp-bookmarklet' )
		));

		bp_core_load_template( apply_filters( 'bp_bkmklet_template_dir', 'bookmarklet-dir' ) );
	}
}
add_action( 'bp_screens', 'bp_bkmklet_screen_index' );



/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for Bookmarklet dir
 *
 * @since BP Bookmarklet (2.0)
 */
class BP_Bkmklet_Theme_Compat {

	/**
	 * Setup the bookmarklet component theme compatibility
	 *
	 * @since BP Bookmarklet (2.0)
	 */
	public function __construct() {
		global $bp;
		
		add_action( 'bp_setup_theme_compat', array( $this, 'is_bkmklet' ) );
	}

	/**
	 * Are we looking at something that needs bp bookmarklet theme compatability?
	 *
	 * @uses bp_displayed_user_id() to check we're not on a member's page
	 * @uses bp_is_current_component() to check we're on the bookmarklet directory page
	 * @uses bp_current_action() to check no action is set
	 * 
	 * @since BP Bookmarklet (2.0)
	 */
	public function is_bkmklet() {
		
		if ( ! bp_current_action() && !bp_displayed_user_id() && bp_is_current_component( 'bookmarklet' ) ) {

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'directory_content'    ) );			

		}
		
	}

	/** Directory *************************************************************/

	/**
	 * Update the global $post with directory data
	 *
	 * @uses bkmklet_get_the_page_title() to get the title for the plugin's directory page
	 * @uses bp_theme_compat_reset_post() to reset post values
	 *
	 * @since BP Bookmarklet (2.0)
	 */
	public function directory_dummy_post() {

		$title = bkmklet_get_the_page_title();

		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_bookmarklet',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the bookmarklet index template part
	 *
	 * @uses bp_buffer_template_part() to buffer the plugin's template
	 *
	 * @since BP Bookmarklet (2.0)
	 */
	public function directory_content() {
		
		bp_buffer_template_part( 'bookmarklet-dir' );
	}
	
}

new BP_Bkmklet_Theme_Compat;

