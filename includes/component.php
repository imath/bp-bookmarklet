<?php
/**
 * Bookmarklet component.
 *
 * @package BP Bookmarklet
 * @subpackage Component
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Component' ) ) :
/**
 * the BuddyPress Bookmarklet component's class
 */
class BP_Bookmarklet_Component extends BP_Component {
	/**
	 * The constructor
	 */
	public function __construct() {
		parent::start(
			'bookmarklet',
			__( 'Bookmarklet', 'bp-bookmarklet' )
		);

		buddypress()->active_components[$this->id] = '1';
	}

	/**
	 * Create the component navigation
	 *
	 * @since 3.0.0
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		// This nav will only be created if the Settings component is not active
		if ( ! is_user_logged_in() || ! bp_is_my_profile() ) {
			return;
		}

		if ( ! bp_is_active( 'settings' ) ) {
			$this->link      = trailingslashit( bp_loggedin_user_domain() . $this->slug );
			$access          = bp_is_my_profile();

			// Add 'Bookmarklet' to the main navigation.
			$main_nav = array(
				'name'                    => _x( 'Bookmarklet', 'Profile Bookmarklet screen nav', 'bp-bookmarklet' ),
				'slug'                    => $this->slug,
				'position'                => 500,
				'show_for_displayed_user' => $access,
				'screen_function'         => array( $this, 'set_screen' ),
				'default_subnav_slug'     => 'my-bookmarklet',
				'item_css_id'             => $this->id
			);

			// Add the subnav items to the Bookmarklet nav item if we are using a theme that supports this.
			$sub_nav[] = array(
				'name'            => _x( 'My bookmarklet', 'Profile Bookmarklet screen sub nav', 'bp-bookmarklet' ),
				'slug'            => 'my-bookmarklet',
				'parent_url'      => $this->link,
				'parent_slug'     => $this->slug,
				'screen_function' => array( $this, 'set_screen' ),
				'position'        => 10,
				'user_has_access' => $access
			);
		} else {
			$this->link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . $this->slug;

			$this->setup_settings_nav();
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Add our subnavigation to the Settings component
	 *
	 * @since 3.0.0
	 */
	public function setup_settings_nav() {
		bp_core_new_subnav_item( array(
			'name'            => _x( 'Bookmarklet', 'Profile Bookmarklet settings nav', 'bp-bookmarklet' ),
			'slug'            => $this->slug,
			'parent_url'      => trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ),
			'parent_slug'     => bp_get_settings_slug(),
			'screen_function' => array( $this, 'set_screen' ),
			'position'        => 100,
			'user_has_access' => bp_is_my_profile()
		) );
	}

	/**
	 * Add our subnavigation to the WP Admin Bar
	 *
	 * @since 3.0.0
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			if ( ! bp_is_active( 'settings' ) ) {
				$this->link = trailingslashit( bp_loggedin_user_domain() . $this->slug );

				// Add the "Bookmarklet" sub menu.
				$wp_admin_nav[] = array(
					'parent' => buddypress()->my_account_menu_id,
					'id'     => 'my-account-' . $this->id,
					'title'  => _x( 'Bookmarklet', 'Profile Bookmarklet admin bar nav', 'bp-bookmarklet' ),
					'href'   => $this->link
				);

				// My Bookmarklet.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-mine',
					'title'    => _x( 'My bookmarklet', 'Profile Bookmarklet admin bar sub nav', 'bp-bookmarklet' ),
					'href'     => $this->link,
					'position' => 10
				);

			// Otherwise add a subnav to the settings component
			} else {
				add_filter( 'bp_settings_admin_nav', array( $this, 'setup_settings_admin_bar' ), 10, 1 );
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Add a new WP Admin Bar submenu to the Settings component
	 *
	 * @since 3.0.0
	 */
	public function setup_settings_admin_bar( $wp_admin_nav = array() ) {
		$wp_admin_nav[] =  array(
			'parent' => 'my-account-' . buddypress()->settings->id,
			'id'     => 'my-account-' . $this->id,
			'title'  => _x( 'Bookmarklet', 'Profile Bookmarklet settings admin bar', 'bp-bookmarklet' ),
			'href'   => trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . $this->slug,
		);

		return $wp_admin_nav;
	}

	/**
	 * Set the BuddyPress screen for the requested action
	 *
	 * @since 3.0.0
	 */
	public function set_screen() {
		// Allow plugins to do things there..
		do_action( 'bp_bookmarklet_user_screen' );

		// Prepare the template part.
		add_action( 'bp_template_content', array( $this, 'output_content' ) );

		// Load the template
		bp_core_load_template( apply_filters( 'bp_bookmarklet_user_template', 'members/single/plugins' ) );
	}

	/**
	 * Output the content according to the current action.
	 *
	 * @since 3.0.0
	 */
	public function output_content() {
		$name = esc_html( apply_filters( 'bp_bookmarklet_get_button_name', get_bloginfo( 'name' ) ) );

		bp_bookmarklet()->enqueue_button_cssjs(); ?>

		<p><?php esc_html_e( 'Drag the bookmarklet below to your bookmarks bar. Then, when you&#8217;re on a page you want to share, simply &#8220;Bookmark&#8221; it.', 'bp-bookmarklet' ); ?></p>

		<p class="bookmarklet-button-wrapper">
			<a class="bp-bookmarklet button" onclick="return false;" href="<?php echo bp_bookmarklet_get_bookmarklet_link( $name ); ?>"><span><?php printf( esc_html__( 'Bookmark on %s', 'bp-bookmarklet' ), $name ); ?></span></a>
			<button type="button" class="bp-bookmarklet button" aria-expanded="false" aria-controls="bookmarklet-code-wrap">
				<span class="dashicons dashicons-clipboard"></span>
				<span class="bp-screen-reader-text"><?php _e( 'Copy &#8220;Bookmarklet This&#8221; code', 'bp-bookmarklet' ) ?></span>
			</button>
		</p>

		<div class="bookmarklet-hidden" id="bookmarklet-code-wrap">
			<p id="bookmarklet-code-desc">
				<?php esc_html_e( 'If you can&#8217;t drag the bookmarklet to your bookmarks, copy the following code and create a new bookmark. Paste the code into the new bookmark&#8217;s URL field.', 'bp-bookmarklet' ) ?>
			</p>
			<p>
				<textarea class="bookmarklet-code" rows="5" cols="80" readonly="readonly" aria-labelledby="bookmarklet-code-desc"><?php echo bp_bookmarklet_get_bookmarklet_link( $name ); ?></textarea>
			</p>
		</div>
		<?php
	}
}

/**
 * Load the Bookmarklet component into the buddypress() main instance
 *
 * @uses buddypress()
 */
function bp_bookmarklet_component() {
	buddypress()->bookmarklet = new BP_Bookmarklet_Component();
}
add_action( 'bp_loaded', 'bp_bookmarklet_component' );

endif;
