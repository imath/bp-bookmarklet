<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if( class_exists( 'BP_Component' ) ):
/**
 * the BuddyPress Bookmarklet component's class
 *
 * @package BP Bookmarklet
 */
class BP_BKMKLET_Component extends BP_Component {
	
	/**
	 * The constructor
	 *
	 * @uses bkmklet_get_slug() to get plugin's slug
	 * @uses BP_Component::start() to reference bookmarklet in BuddyPress components
	 * @uses bkmklet_get_name() to get plugin's name
	 * @uses bkmklet_get_includes_dir() to get path to the plugin's includes dir
	 * @uses self::includes() to include the needed files
	 * @uses setup_globals() to register some global vars
	 * @uses buddypress() to reference the component as active
	 */
	public function __construct() {
		$this->slug = bkmklet_get_slug();
		$this->has_directory = true;
		parent::start(
			bkmklet_get_slug(),
			bkmklet_get_name(),
			bkmklet_get_includes_dir()
		);
		$this->includes();
		$this->setup_globals();
		
		buddypress()->active_components[$this->id] = '1';
	}
	
	/**
	 * Includes the needed files
	 *
	 * @uses BP_Component::includes()
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'screens.php',
			'widget.php'
		);

		parent::includes( $includes );
	}
	
	/**
	 * Sets some globals
	 *
	 * @uses buddypress() to get some BuddyPress globals
	 * @uses bkmklet_get_slug() to get plugin's slug
	 * @uses BP_Component::setup_globals()
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		$globals = array(
			'slug'                  => bkmklet_get_slug(),
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : bkmklet_get_slug(),
			'has_directory'         => true,
		);

		parent::setup_globals( $globals );
	}
}

/**
 * Finally Loads the component into the $bp global
 *
 * @uses buddypress()
 */
function bkmklet_load_component() {
	buddypress()->bookmarklet = new BP_BKMKLET_Component();
}
add_action( 'bp_loaded', 'bkmklet_load_component' );

endif;