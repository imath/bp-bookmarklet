<?php
/**
 * Plugin Name: BP BookMarklet
 * Plugin URI: http://imathi.eu/tag/bp-bookmarklet/
 * Description: Allows your member to add a bookmarklet to their browser to easily share links in your BuddyPress powered community 
 * Version: 3.0.0
 * Requires at least: 4.4
 * Tested up to: 4.5
 * License: GNU/GPL 2
 * Author: imath
 * Author URI: http://imathi.eu/
 * Text Domain: bp-bookmarklet
 * Domain Path: /languages/
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Bookmarklet' ) ) :
/**
 * The main class
 *
 * @package BP Bookmarklet
 */
final class BP_Bookmarklet {
	
	private static $instance = null;
	
	/**
	 * Make sure the instance is load once
	 */
	public static function instance() {
		if ( null == self::$instance ) {
	    	self::$instance = new self;
	    }
	    return self::$instance;
	}
	
	/**
	 * The constructor
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
	}
	
	/**
	 * Registers plugin's globals
	 */
	private function setup_globals() {

		/** Version ***********************************************************/

		$this->version    = '3.0.0';

		$this->required_versions = array(
			'wp' => 4.4,
			'bp' => 2.5
		);

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = apply_filters( 'bkmklet_plugin_basename',  plugin_basename( $this->file ) );
		$this->plugin_dir = apply_filters( 'bkmklet_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url = apply_filters( 'bkmklet_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		// Includes
		$this->includes_dir  = apply_filters( 'bkmklet_includes_dir',  trailingslashit( $this->plugin_dir . 'includes'   ) );
		$this->includes_url  = apply_filters( 'bkmklet_includes_url',  trailingslashit( $this->plugin_url . 'includes'   ) );
		$this->css_url       = apply_filters( 'bkmklet_includes_url',  trailingslashit( $this->plugin_url . 'css'        ) );
		$this->js_url        = apply_filters( 'bkmklet_includes_url',  trailingslashit( $this->plugin_url . 'js'         ) );
		$this->templates_dir = apply_filters( 'bkmklet_templates_dir', trailingslashit( $this->plugin_dir . 'templates'  ) );

		// Languages
		$this->lang_dir = apply_filters( 'bkmklet_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );
		
		// slug and name
		$this->bkmklet_slug = apply_filters( 'bkmklet_slug', 'bookmarklet' );
		$this->bkmklet_name = apply_filters( 'bkmklet_name', 'Bookmarklet' );


		/** Misc **************************************************************/

		$this->domain         = 'bp-bookmarklet';
		$this->errors         = new WP_Error(); // Feedback
	}

	/**
	 * Should we load the plugin ?
	 */
	private function bail() {
		$return = false;

		$wp_version = 0;
		if ( isset( $GLOBALS['wp_version'] ) ) {
			$wp_version = (float) $GLOBALS['wp_version'];
		}

		if ( $this->required_versions['wp'] > $wp_version || $this->required_versions['bp'] > (float) bp_get_version() || ! bp_is_active( 'activity') ) {
			$return = true;
		}

		return $return;
	}
	
	/**
	 * Includes the needed files
	 *
	 */
	private function includes() {
		if ( $this->bail() ) {
			return;
		}

		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'images.php'    );
		require( $this->includes_dir . 'component.php' );
	}
	
	/**
	 * Fires some hooks to extend BuddyPress with plugin's functionalities
	 *
	 */
	private function setup_hooks() {
		if ( $this->bail() ) {
			return;
		} else {
			//loads the scripts after BuddyPress has loaded his
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			
			//Adds extra html in member's profile header to display the bookmarklet button
			add_action( 'bp_profile_header_meta', 'bkmklet_profile_header_bookmarklet' );
			
			//Adds a checkbox to the activity post form to let user attach link's images
			add_action( 'bp_activity_post_form_options', 'bkmklet_activity_post_option' );
			
			//Filters the content before save to include the image in the content
			add_filter( 'bp_activity_new_update_content', 'bkmklet_activity_new_update_content', 10, 1 );
			
			//Forces image height to avoid full/max width images in activity stream
			add_action( 'wp_head', 'bkmklet_force_image_height', 99 );
			
			//Removes the plugin's directory page from blog's default menu
			add_filter( 'wp_page_menu_args', 'bkmklet_hide_directory_page', 20, 1 );
		}
		
		//Loads the translation
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 6 );
	}
	
	/**
	 * Enqueues Scripts if on logged in member's profile pages
	 */
	public function enqueue_scripts() {
		if( bp_is_my_profile() && !bkmklet_is_widget_loaded() ) {
			wp_enqueue_style( 'bp-bkmk-widget-style', bkmklet_get_css_url() . 'bkmk-button.css', false, bkmklet_get_version() );
			wp_enqueue_script( 'bookmarklet-button-js', bkmklet_get_js_url() .'bookmarklet-button.js', array( 'jquery'), bkmklet_get_version(), true );
			wp_localize_script('bookmarklet-button-js', 'bookmarklet_button_vars', array(
						'drag_message' => __('Just drag the button to your Bookmarks Toolbar!', 'bp-bookmarklet' )
			));
		}
	}

	/**
	 * Loads the translation files
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/bp-bookmarklet/' . $mofile;

		// Look in global /wp-content/languages/bp-bookmarklet folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/bp-bookmarklet/languages/ folder
		load_textdomain( $this->domain, $mofile_local );
	}
}
endif;

/**
 * Main plugin's function
 */
function bp_bookmarklet() {
	return BP_Bookmarklet::instance();
}
add_action( 'bp_include', 'bp_bookmarklet' );

