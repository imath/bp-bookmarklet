<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Registers Bookmarklet widget
 *
 * @global int $blog_id the current blog id
 * @uses is_multisite() to check WordPress config and eventually abort if not on root blog
 */
function bp_bookmarklet_register_widget() {
	/* only for root blog !*/
	if ( is_multisite() && ! bp_is_root_blog() ) {
		return false;
	}
	
	add_action( 'bp_widgets_init', create_function('', 'return register_widget("BP_Bookmarklet_Widget");' ) );
	
}
add_action( 'bp_loaded', 'bp_bookmarklet_register_widget', 9 );


/*** Bookmarklet WIDGET *****************/

/**
 * The Bookmarklet Widget
 *
 * @package BP Bookmarklet
 */
class BP_Bookmarklet_Widget extends WP_Widget {
	/**
	 * The constructor : defines the widget's settings
	 *
	 * @uses WP_Widget::__construct()
	 */
	function __construct() {
		$widget_ops = array( 'description' => __( 'A Bookmarklet Widget to allow your member to easily share content in activity stream.', 'bp-bookmarklet' ) );
		parent::__construct( false, $name = __( 'Bookmarklet', 'bp-bookmarklet' ), $widget_ops );
		
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Enqueues the script file and the css one
	 *
	 * @uses wp_enqueue_style() to enqueue the script into the WordPress style stack
	 * @uses bkmklet_get_css_url() to get plugin's url to css folder
	 * @uses bkmklet_get_version() to get plugin's version
	 * @uses wp_enqueue_script() to enqueue the script into the WordPress script stack
	 * @uses bkmklet_get_js_url() to get plugin's url to js folder
	 * @uses wp_localize_script() to attach translatable messages into the js file
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'bp-bkmk-widget-style', bkmklet_get_css_url() . 'bkmk-button.css', false, bkmklet_get_version() );
		wp_enqueue_script( 'bookmarklet-button-js', bkmklet_get_js_url() .'bookmarklet-button.js', array( 'jquery' ), bkmklet_get_version(), true );
		wp_localize_script('bookmarklet-button-js', 'bookmarklet_button_vars', array(
					'drag_message' => __('Just drag the button to your Bookmarks Toolbar!', 'bp-bookmarklet' )
		));
	}

	/**
	 * Displays the widget
	 *
	 * @param array $args the widget settings
	 * @param array $instance the widget configuration
	 * @uses bkmklet_widget_when_to_show() to check if we need to show the widget
	 * @uses bp_bkmklet_the_bookmarklet() to display the bookmarklet
	 * @return the widget's html part
	 */
	function widget( $args, $instance ) {

		extract( $args );

		if ( empty( $instance['bkmk_member_only'] ) )
			$instance['bkmk_member_only'] = 1;
			
		if( bkmklet_widget_when_to_show( $instance['bkmk_member_only'] ) ):

		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title; ?>

			<ul id="bkmklet-widget">
				<li class="bp-bkmklet-drag">
					<?php bp_bkmklet_the_bookmarklet();?>
				</li>
			</ul>

		<?php echo $after_widget; ?>
		
		<?php endif; ?>
	<?php
	}
	
	/**
	 * Updates the widget's instance with user's choices
	 *
	 * @param array $new_instance 
	 * @param array $old_instance 
	 * @return array the updated instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['bkmk_member_only'] = strip_tags( $new_instance['bkmk_member_only'] );

		return $instance;
	}
	
	/**
	 * Displays the configuration form in WordPress admin area
	 *
	 * @param array $instance 
	 * @uses wp_parse_args() to merge instance with defaults array
	 * @uses WP_Widget::get_field_id() to get the field's id
	 * @uses WP_Widget::get_field_name() to get the field's name
	 * @uses esc_attr() to sanitize data
	 * @return string the html part for the form
	 */
	function form( $instance ) {
		$defaults = array(
			'title' => __( 'Bookmarklet', 'bp-bookmarklet' ),
			'bkmk_member_only' => 1
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = strip_tags( $instance['title'] );
		$bkmk_member_only = strip_tags( $instance['bkmk_member_only'] );
		?>

		<p><label for="bp-bookmarklet-widget-title"><?php _e('Title:', 'bp-bookmarklet'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" /></label></p>

		<p><label for="bp-bookmarklet-widget-show"><?php _e('Show only on loggedin members profile:', 'bp-bookmarklet'); ?> 
			<input id="<?php echo $this->get_field_id( 'bkmk_member_only' ); ?>-yes" name="<?php echo $this->get_field_name( 'bkmk_member_only' ); ?>" type="radio" value="1" <?php if($bkmk_member_only != 2) echo 'checked';?> /><?php _e('Yes');?></input>
			<input id="<?php echo $this->get_field_id( 'bkmk_member_only' ); ?>-no" name="<?php echo $this->get_field_name( 'bkmk_member_only' ); ?>" type="radio" value="2" <?php if($bkmk_member_only == 2) echo 'checked';?> /><?php _e('No');?></input>
			</label></p>

	<?php
	}
}

/**
 * Informs on which part of BuddyPress we are and hides the widget if user is not logged in
 *
 * @param boolean $widgetopts 
 * @uses is_user_logged_in() to check the user is logged in
 * @uses bp_is_my_profile() to check we're on logged in member's single page
 * @return boolean true or false
 */
function bkmklet_widget_when_to_show( $widgetopts = false ) {
	
	if( empty( $widgetopts ) || $widgetopts == 2 ) {
		
		return is_user_logged_in();
		
	} else {
		
		if( bp_is_my_profile() )
			return true;
			
		else
			return false;
	}
	
}
?>