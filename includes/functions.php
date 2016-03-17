<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gets plugin's version
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's version
 */
function bkmklet_get_version() {
	return bp_bookmarklet()->version;
}

/**
 * Gets plugin's path to includes dir
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's path to includes dir
 */
function bkmklet_get_includes_dir() {
	return bp_bookmarklet()->includes_dir;
}

/**
 * Gets plugin's url to css folder
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's url to css folder
 */
function bkmklet_get_css_url() {
	return bp_bookmarklet()->css_url;
}

/**
 * Gets plugin's url to js folder
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's url to js folder
 */
function bkmklet_get_js_url() {
	return bp_bookmarklet()->js_url;
}

/**
 * Gets plugin's path to templates dir
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's path to templates dir
 */
function bkmklet_get_templates_dir() {
	return bp_bookmarklet()->templates_dir;
}

/**
 * Gets plugin's slug
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's slug
 */
function bkmklet_get_slug() {
	return bp_bookmarklet()->bkmklet_slug;
}

/**
 * Gets plugin's name
 *
 * @uses bp_bookmarklet() to get Bookmarklet globals
 * @return string plugin's name
 */
function bkmklet_get_name() {
	return bp_bookmarklet()->bkmklet_name;
}

/**
 * Builds the plugin's directory page link
 *
 * @uses bp_get_root_domain() to get the url of the root blog
 * @uses bkmklet_get_slug() to get the plugin's slug
 * @uses trailingslashit() to add a slash at the end of the url if needed
 * @return string the plugin's directory page link
 */
function bkmklet_get_bookmarklet_root_url() {
	$root_domain_url = bp_get_root_domain();
	$bkmklet_slug = bkmklet_get_slug();
	$bkmklet_root_url = trailingslashit( $root_domain_url ) . $bkmklet_slug;
	return $bkmklet_root_url;
}

/**
 * Hides the plugin's directory page from site's menu
 *
 * @param string $args the excluded pages if existing
 * @uses buddypress() to get BuddyPress globals
 * @return string the args with our excluded page
 */
function bkmklet_hide_directory_page( $args ) {
	$bp = buddypress();
	
	if( !empty( $args['exclude'] ) )
		$args['exclude'] .= ','. $bp->pages->bookmarklet->id;
		
	else
		$args['exclude'] = $bp->pages->bookmarklet->id;
	
	return $args;
}

/**
 * Displays the directory page title
 *
 * @uses bkmklet_get_the_page_title() to get it.
 */
function bkmklet_the_page_title() {
	echo bkmklet_get_the_page_title();
}
	
	/**
	 * Gets the directory page title
	 *
	 * @uses get_bloginfo() to get the name of the blog.
	 * @uses is_user_logged_in() to check the user is logged in.
	 * @return string the page title
	 */
	function bkmklet_get_the_page_title() {
		$title = sprintf( __( 'Please log in %s', 'bp-bookmarklet' ), get_bloginfo( 'name' ) );
		
		if( is_user_logged_in() )
			$title = sprintf( __( 'Share on %s', 'bp-bookmarklet' ), get_bloginfo( 'name' ) );
			
		return apply_filters( 'bkmklet_get_the_page_title', $title );
	}



/**
 * Displays the bookmarklet (main purpose of the plugin!) in member's profile header
 *
 * @uses bkmklet_is_widget_loaded() to check if the bookmarklet widget is active
 * @uses bp_displayed_user_id() to get displayed member id
 * @uses bp_loggedin_user_id() to get current logged in member id
 * @uses bp_bkmklet_the_bookmarklet() to display the bookmarklet
 * @return string the html part
 */
function bkmklet_profile_header_bookmarklet() {
	
	if( bkmklet_is_widget_loaded() )
		return false;
	
	if( bp_displayed_user_id() == bp_loggedin_user_id() ){
		?>
		<div class="bp-bkmklet-container">
			<div class="bp-bkmklet-activate"><a href="javascript:void(0)" title="<?php _e( 'Show the bookmarklet', 'bp-bookmarklet' );?>"><?php _e( 'What about adding a bookmarklet in your browser bookmarks toolbar ?', 'bp-bookmarklet' );?></a></div>
			<div class="bp-bkmklet-drag" style="display:none">
				<p><?php _e( 'To simply share content/links in your profile or group updates, just drag the button to your Bookmarks Toolbar!', 'bp-bookmarklet' );?></p>
				<?php bp_bkmklet_the_bookmarklet();?>
			</div>
		</div>
		<?php
	}
}

/**
 * Displays the name of the bookmarklet used in browser toolbar
 *
 * @uses get_bloginfo() to get the name of the blog.
 * @return string the name
 */
function bkmklet_href_name(){
	/* Want to change the mention on the bookmarklet, use this filter...*/
	echo apply_filters( 'bkmklet_href_name', sprintf( __('Share on %s', 'bp-bookmarklet'), get_bloginfo( 'name' ) ) );
}

/**
 * Builds the bookmarklet
 *
 * @uses bkmklet_get_bookmarklet_root_url() to get url to plugin's directory page.
 * @uses get_bloginfo() to get the name of the blog.
 * @uses bkmklet_href_name() to get the name of the bookmarklet.
 * @return string the html part (link)
 */
function bp_bkmklet_the_bookmarklet() {
	$url = bkmklet_get_bookmarklet_root_url();
	
	?>
	<a class="bookmarklet-button" title="<?php _e('Share on', 'bp-bookmarklet');?> <?php echo get_bloginfo('name');?>" href="javascript:(function(){f=&#39;<?php echo $url;?>?url=&#39;+encodeURIComponent(window.location.href)+&#39;&amp;title=&#39;+encodeURIComponent(document.title)+&#39;&amp;copied=&#39;+encodeURIComponent(''+(window.getSelection?window.getSelection():document.getSelection?document.getSelection():document.selection.createRange().text))+&#39;&amp;+v=1&amp;&#39;;a=function(){if(!window.open(f+&#39;noui=1&amp;jump=doclose&#39;,&#39;<?php echo get_bloginfo('name');?>&#39;,&#39;location=O,links=0,scrollbars=0,toolbar=0,width=550,height=230&#39;))location.href=f+&#39;jump=yes&#39;};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})()"><?php bkmklet_href_name();?></a>
	<?php
}

/**
 * Adds a checkbox to activity post options to allow the user to attach images from the link
 *
 * @return string the html part
 */
function bkmklet_activity_post_option() {
	if( bp_is_current_component( 'bookmarklet' ) ) {
		?>
		<div id="bkmklet-container">
			<input type="checkbox" id="bkmklet-image-cb" name="_bkmklet_url"/> <?php _e( 'Attach image', 'bp-bookmarklet' );?>
			<input type="hidden" id="bkmklet-image-url" name="_bkmklet_image_url"/>
		</div>
		<?php
	}
}

/**
 * Filters the activity content before it's saved in db
 *
 * @uses bkmklet_get_bookmarklet_root_url() to get url to plugin's directory page.
 * @uses esc_url_raw() to sanitize url.
 * @uses wp_parse_args() to parse the bp-cookies sent.
 * @uses esc_attr() to sanitize the src attribute of the image
 * @return string the filtered activity content
 */
function bkmklet_activity_new_update_content( $activity_content = '' ) {
	$src = false;
	$link = false;
	
	if( !empty( $_POST['_bkmklet_image_url'] ) ) {
		$src = esc_url_raw( $_POST['_bkmklet_image_url'] );
		$link = esc_url_raw( $_POST['_bkmklet_url'] );
	// we search for cookies	
	} else {
		if ( !empty( $_POST['cookie'] ) )
			$_BP_COOKIE = wp_parse_args( str_replace( '; ', '&', urldecode( $_POST['cookie'] ) ) );
		else
			$_BP_COOKIE = &$_COOKIE;
			
		if( !empty( $_BP_COOKIE['bp-bkmklet_image_url'] ) ) {
			$src = esc_url_raw( $_BP_COOKIE['bp-bkmklet_image_url'] );
			$link = esc_url_raw( $_BP_COOKIE['bp-bkmklet_url'] );

			@setcookie( 'bp-bkmklet_image_url', false, time() - 1000, COOKIEPATH );
			@setcookie( 'bp-bkmklet_url', false, time() - 1000, COOKIEPATH );
		}
			
	}
	
	if( empty( $src ) )
		return $activity_content;
		
	$image = '<img src="' . esc_attr( $src ) . '" height="100px" alt="' . __( 'Thumbnail', 'bp-bookmarklet' ) . '" class="align-left thumbnail bkmklet-thumbnail" />';

	if ( !empty( $link ) ) {
		$image = '<a href="' . $link . '">' . $image . '</a>';
	}

	$activity_content = $image . $activity_content;
	
	return apply_filters( 'bkmklet_activity_new_update_content', $activity_content, $src, $link );
}

/**
 * Forces the height of the image to avoid full/max width display of it
 *
 * @return string the style declaration
 */
function bkmklet_force_image_height() {
	?>
	<style>
	.bkmklet-thumbnail{ height:100px!important}
	</style>
	<?php
}

/**
 * Checks if the bookmarklet widget is active
 *
 * @uses is_active_widget() to know this
 * @return boolean true or false
 */
function bkmklet_is_widget_loaded() {
	return is_active_widget( false, false, 'bp_bookmarklet_widget' );
}
