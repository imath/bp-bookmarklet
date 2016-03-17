<?php
/*
Plugin Name: BP BookMarklet
Plugin URI: http://imathi.eu/tag/bp-bookmarklet/
Description: Allows your member to add a bookmarklet to their browser to easily share links in your BuddyPress powered community 
Version: 2.0.2
Requires at least: 3.5.1
Tested up to: 3.6
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Network: true
Text Domain: bp-bookmarklet
Domain Path: /languages/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Bkmklet_Start' ) ) :

/**
 * The boostrap class of the plugin
 *
 * @package BP Bookmarklet
 */
final class Bkmklet_Start {
	
	private static $instance = null;
	
	/**
	 * Make sure the instance is load once
	 *
	 * @return object the instance
	 */
	public static function instance() {
		if ( null == self::$instance ) {
	    	self::$instance = new self;
	    }
	    return self::$instance;
	}
	
	/**
	 * The constructor
	 *
	 * @uses self::setup_globals() to register plugin's globals
	 * @uses self::includes() to include the needed files
	 * @uses self::setup_hooks() to fire some actions and filters
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
	}
	
	/**
	 * Registers plugin's globals
	 *
	 * @uses plugin_basename() to get plugin name
	 * @uses plugin_dir_path() to get plugin dir path
	 * @uses plugin_dir_url() to get plugin dir url
	 * @uses trailingslashit() to add a final slash
	 */
	private function setup_globals() {

		/** Version ***********************************************************/

		$this->version    = '2.0.2';

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
	 * Includes the needed files
	 *
	 */
	private function includes() {
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'images.php'    );
		require( $this->includes_dir . 'component.php' );
	}
	
	/**
	 * Fires some hooks to extend BuddyPress with plugin's functionalities
	 *
	 */
	private function setup_hooks() {
		// gets the deactivation event
		add_action( 'deactivate_' . $this->basename, 'bkmklet_deactivation' );
		
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
		
		//Loads the translation
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 6 );
	}
	
	/**
	 * Enqueues Scripts if on logged in member's profile pages
	 *
	 * @uses wp_enqueue_style() to enqueue the script into the WordPress style stack
	 * @uses bkmklet_get_css_url() to get plugin's url to css folder
	 * @uses bkmklet_get_version() to get plugin's version
	 * @uses wp_enqueue_script() to enqueue the script into the WordPress script stack
	 * @uses bkmklet_get_js_url() to get plugin's url to js folder
	 * @uses wp_localize_script() to attach translatable messages into the js file
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
	 * 
	 * @uses get_locale() to get the language of WordPress config
	 * @uses load_texdomain() to load the translation if any is available for the language
	 */
	public function load_textdomain() {
		$locale = apply_filters( 'bp_bkmklet_load_textdomain_get_locale', get_locale() );

		// if we found a locale, try to load .mo file
		if ( !empty( $locale ) ) {
			// default .mo file path
			$mofile_default = sprintf( '%s/languages/%s-%s.mo', $this->plugin_dir, $this->domain, $locale );
			// final filtered file path
			$mofile = apply_filters( 'bp_bkmklet_load_textdomain_mofile', $mofile_default );
			// make sure file exists, and load it
			if ( file_exists( $mofile ) ) {
				load_textdomain( $this->domain, $mofile );
			}
		}
	}
	
	/**
	 * Launches the activation function if register activation hook is fired
	 *
	 * @uses bkmklet_activation() to create the directory page and store plugin's version
	 */
	public function activate() {
		bkmklet_activation();
	}
}

/**
 * Main plugin's function
 *
 * @uses plugin_dir_path() to build the path to the plugin
 * @uses bp_is_active() to abort if activity component is not available
 * @uses is_multisite() to check for multisite config
 * @uses Bkmklet_Start::instance() to load the main class.
 * @return object the instance of BoweStrap
 */
function bp_bookmarklet() {
	
	if( !defined( 'BP_VERSION' ) || version_compare( BP_VERSION, '1.7', '<' ) ) {
		require( plugin_dir_path( __FILE__ ) . 'includes/1.1.php'  );
		return false;
	}
	
	if( !bp_is_active( 'activity' ) ) {
		add_action( is_multisite() ? 'network_admin_notices' : 'admin_notices', 'bkmklet_activity_notice' );
		return false;
	}
	
	return Bkmklet_Start::instance();
}

add_action( 'bp_include', 'bp_bookmarklet' );


/**
 * Simply informs the user, this plugin requires Activity Stream
 *
 */
function bkmklet_activity_notice() {
	?>
	<div id="message" class="updated fade">
		<p>
		<?php _e( 'Well.. BP Bookmarklet is a BuddyPress activity plugin.. So it needs this component to be activated', 'bp-bookmarklet' ) ;?>
		</p>
	</div>
	<?php
}

/**
 * Hooks the activation hook to create the directory page
 *
 * @uses Bkmklet_Start::activate() to run the activation process of the plugin.
 */
function bkmklet_install(){
	
	if( !defined( 'BP_VERSION' ) || version_compare( BP_VERSION, '1.7', '<' ) )
		return false;
	
	$bkmklet_activation = Bkmklet_Start::instance();
	$bkmklet_activation->activate();
}

register_activation_hook( __FILE__, 'bkmklet_install' );

endif;
