<?php
/**
 * Bookmarklet Page parser
 *
 * @package BP Bookmarklet
 * @subpackage Class
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Press_This' ) ) :
/**
 * Site Image/Site metas Parser class.
 *
 * Extends WP_Press_Thi.
 *
 * @since 3.0.0
 */
class BP_Bookmarklet_This extends WP_Press_This {
	public function __construct() {}

	public function fetch_source_html( $url ) {
		$version = bp_bookmarklet()->version;

		if ( empty( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'bp-bookmarklet' ) );
		}

		$remote_url = wp_safe_remote_get( $url, array(
			'timeout' => 30,
			// Use an explicit user-agent for BP Bookmarklet
			'user-agent' => 'Bookmark This (BP Bookmarklet/' . $version . '); ' . get_bloginfo( 'url' )
		) );

		if ( is_wp_error( $remote_url ) ) {
			return $remote_url;
		}

		$useful_html_elements = array(
			'title' => array(),
			'img' => array(
				'src'      => true,
				'width'    => true,
				'height'   => true,
			),
			'iframe' => array(
				'src'      => true,
			),
			'link' => array(
				'rel'      => true,
				'itemprop' => true,
				'href'     => true,
			),
			'meta' => array(
				'property' => true,
				'name'     => true,
				'content'  => true,
			)
		);

		$this->source_content = wp_remote_retrieve_body( $remote_url );
		$this->source_content = wp_kses( $this->source_content, $useful_html_elements );

		return $this->source_content;
	}
}

endif ;
