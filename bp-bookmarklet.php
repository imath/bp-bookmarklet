<?php
/**
 * Plugin Name: BP BookMarklet
 * Plugin URI: http://imathi.eu/tag/bp-bookmarklet/
 * Description: Let the members of your BuddyPress powered community add a Bookmarklet to their browser to share interesting web pages
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

		$this->version = '3.0.0';

		$this->required_versions = array(
			'wp' => 4.4,
			'bp' => 2.5
		);

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );

		// Paths & Urls
		$this->includes_dir  = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url  = trailingslashit( $this->plugin_url . 'includes' );
		$this->css_url       = trailingslashit( $this->plugin_url . 'css'      );
		$this->js_url        = trailingslashit( $this->plugin_url . 'js'       );
		$this->templates_dir = $this->plugin_dir . 'templates';

		// Languages
		$this->lang_dir = apply_filters( 'bp_bookmarklet_lang_dir', trailingslashit( $this->plugin_dir . 'languages' ) );


		/** Misc **************************************************************/
		$this->domain               = 'bp-bookmarklet';
		$this->minified             = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->is_bookmarklet_frame = false;
		$this->prepended            = array();
	}

	/**
	 * Should we load the plugin ?
	 */
	private function bail() {
		$return = false;

		$this->wp_version = 0;
		if ( isset( $GLOBALS['wp_version'] ) ) {
			$this->wp_version = (float) $GLOBALS['wp_version'];
		}

		if ( $this->required_versions['wp'] > $this->wp_version || $this->required_versions['bp'] > (float) bp_get_version() || ! bp_is_active( 'activity') ) {
			$return = true;
		}

		return $return;
	}

	/**
	 * Includes the needed files
	 */
	private function includes() {
		if ( $this->bail() ) {
			return;
		}

		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'component.php' );

		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php' );
		}
	}

	/**
	 * Setup Hooks if the config match requirements
	 */
	private function setup_hooks() {
		if ( $this->bail() ) {
			// Display a warning
			add_action( $this->is_network_active() ? 'network_admin_notices' : 'admin_notices', array( $this, 'warnings' ) );

		// load the plugin.
		} else {
			// Register the template directory
			add_action( 'bp_register_theme_directory', array( $this, 'register_template_dir' )    );

			// Register css & js
			add_action( 'bp_bookmarklet_frame_head',   array( $this, 'register_cssjs' ),  1 );
			add_action( 'bp_bookmarklet_frame_head',   'wp_site_icon',                   20 );
			add_action( 'bp_bookmarklet_frame_footer', 'wp_print_footer_scripts',        20 );

			// Set and locate the Bookmarklet Frame
			add_action( 'bp_init',             array( $this, 'set_frame'    ),     3 );
			add_filter( 'bp_located_template', array( $this, 'locate_frame' ), 10, 2 );

			// Make sure we will be the only one to use the slug 'bp-bookmarklet-frame'
			add_filter( 'groups_forbidden_names',    array( $this, 'restricted_name' ), 10, 1 );
			add_filter( 'site_option_illegal_names', array( $this, 'restricted_name' ), 10, 1 );
		}

		//Loads the translation
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Is the plugin active for network ?
	 *
	 * @since 3.0.0
	 */
	public function is_network_active() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		return is_plugin_active_for_network( $this->basename );
	}

	/**
	 * Required configuration notices
	 *
	 * @since 3.0.0
	 */
	public function warnings() {
		$warnings = array();

		if ( $this->required_versions['wp'] > $this->wp_version ) {
			$warnings[] = sprintf(
				esc_html__( 'BP Bookmarklet %1$s requires at least version %2$s of WordPress.', 'bp-bookmarklet' ),
				$this->version,
				$this->required_versions['wp']
			);
		}

		if ( $this->required_versions['bp'] > (float) bp_get_version() ) {
			$warnings[] = sprintf(
				esc_html__( 'BP Bookmarklet %1$s requires at least version %2$s of BuddyPress.', 'bp-bookmarklet' ),
				$this->version,
				$this->required_versions['bp']
			);
		}

		if ( ! bp_is_active( 'activity' ) ) {
			$warnings[] = sprintf(
				esc_html__( 'BP Bookmarklet %s requires the BuddyPress Activity component to be active.', 'bp-bookmarklet' ),
				$this->version
			);
		}

		if ( ! empty( $warnings ) ) : ?>

			<div id="message" class="error">
				<?php foreach ( $warnings as $warning ) : ?>
					<p><?php echo $warning; ?></p>
				<?php endforeach ; ?>
			</div>

		<?php endif;
	}

	/**
	 * Register our template dir into the BuddyPress stack
	 *
	 * @since 3.0.0
	 */
	public function register_template_dir() {
		bp_register_template_stack( array( $this, 'template_dir' ),  20 );
	}

	/**
	 * Get the template dir
	 *
	 * @since 1.0.0
	 */
	public function template_dir() {
		if ( ! $this->is_bookmarklet_frame ) {
			return;
		}

		return apply_filters( 'bp_bookmarklet_templates_dir', $this->templates_dir );
	}

	/**
	 * Set the Bookmarklet frame if needed
	 *
	 * @since 3.0.0
	 */
	public function set_frame() {
		$bp = buddypress();

		if ( isset( $bp->unfiltered_uri ) && array_search( 'bp-bookmarklet-frame', $bp->unfiltered_uri ) ) {
			$this->is_bookmarklet_frame = true;

			// No Admin Bar into the Bookmarklet frame!
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/**
	 * Locate the Bookmarklet frame if needed
	 *
	 * @since 3.0.0
	 */
	public function locate_frame( $located = '', $filtered = array() ) {
		if ( $this->is_bookmarklet_frame ) {
			$located = bp_locate_template( reset( $filtered ) );
		}

		return $located;
	}

	/**
	 * Register Scripts and styles
	 *
	 * @since 3.0.0
	 */
	public function register_cssjs() {
		// Style
		wp_register_style(
			'bp-bookmarklet-style',
			$this->css_url . "style{$this->minified}.css",
			array( 'dashicons' ),
			$this->version
		);

		// JS
		wp_register_script(
			'bp-bookmarklet-script',
			$this->js_url . "script{$this->minified}.js",
			array( 'jquery', 'json2', 'wp-backbone' ),
			$this->version,
			true
		);
	}

	/**
	 * Enqueues the script and style for the User's Bookmarklet
	 * screen.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_button_cssjs() {
		wp_enqueue_style(
			'bp-bookmarklet-button-style',
			$this->css_url . "button{$this->minified}.css",
			array( 'dashicons' ),
			$this->version
		);

		// JS
		wp_enqueue_script(
			'bp-bookmarklet-button-script',
			$this->js_url . "button{$this->minified}.js",
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	/**
	 * Add a restricted name
	 *
	 * @since 3.0.0
	 */
	public function restricted_name( $names = array() ) {
		if ( ! in_array( 'bp-bookmarklet-frame', $names ) ) {
			$names = array_merge( $names, array( 'bp-bookmarklet-frame' ) );
		}

		return $names;
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

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
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

