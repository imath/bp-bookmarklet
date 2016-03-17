<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Displays a warning message in network admin notices or admin notices
 * if required version of BuddyPress (1.7) is not activated.
 * 
 * @since  2.0
 * 
 * @return string html of the warning message
 */
function bkmklet_warning_message() {
	?>
	<div id="message" class="updated fade">
		<p>Hi, Since version 2.0 of BP Bookmarklet, the plugin requires at least version 1.7 of BuddyPress.
		   Do not worry, you can still <a href="http://wordpress.org/plugins/bp-bookmarklet/developers/" title="List of Versions of the plugin" target="_blank">download version 1.1</a> of the plugin to roll back to it</p>
	</div>
	<?php
}

add_action( is_multisite() ? 'network_admin_notices' : 'admin_notices', 'bkmklet_warning_message' );