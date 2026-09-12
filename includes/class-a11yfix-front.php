<?php
/**
 * Front-end fixes: WP filters first, output-buffered DOM fixes second.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies enabled repairs to rendered pages.
 */
class A11yFix_Front {

	/**
	 * Register the enabled mechanisms.
	 *
	 * @return void
	 */
	public static function boot() {
		$settings = A11yFix_Settings::get();
		if ( ! $settings['master_enabled'] ) {
			return;
		}
		$map = $settings['repairs'];

		if ( ! empty( $map['img_alt'] ) ) {
			add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'filter_attachment_attrs' ), 10, 2 );
		}

		// OB is required when any DOM-level repair is on.
		if ( ! empty( $map['html_lang'] ) || ! empty( $map['table_scope'] ) || ! empty( $map['control_name'] ) || ! empty( $map['iframe_title'] ) || ! empty( $map['img_alt'] ) ) {
			add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ) );
		}
	}

	/**
	 * Fill missing alt on attachment images at the WP filter level.
	 *
	 * @param array    $attr       Image attributes.
	 * @param WP_Post  $attachment Attachment post.
	 * @return array
	 */
	public static function filter_attachment_attrs( $attr, $attachment ) {
		if ( ! empty( $attr['alt'] ) || ! $attachment instanceof WP_Post ) {
			return $attr;
		}
		$alt = self::attachment_alt( $attachment->ID );
		if ( '' !== $alt ) {
			$attr['alt'] = $alt;
		}

		return $attr;
	}

	/**
	 * Start output buffering on regular front-end pages.
	 *
	 * @return void
	 */
	public static function start_buffer() {
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'buffer_end' ) );
	}

	/**
	 * OB callback: run enabled DOM repairs.
	 *
	 * @param string $html Rendered page.
	 * @return string
	 */
	public static function buffer_end( $html ) {
		try {
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
				return $html;
			}
			if ( ! is_string( $html ) || strlen( $html ) > 1536 * 1024 || false === stripos( $html, '<html' ) ) {
				return $html;
			}

			$settings = A11yFix_Settings::get();
			if ( ! $settings['master_enabled'] ) {
				return $html;
			}

			$result = A11yFix_Preview::run( $html, $settings['repairs'], self::context() );

			return $result['html'];
		} catch ( Throwable $e ) {
			return $html; // Never break a page because of an audit tool.
		}
	}

	/**
	 * Injectables for repairs.
	 *
	 * @return array
	 */
	private static function context() {
		return array(
			'lang'         => get_bloginfo( 'language' ),
			'submit_label' => __( 'Submit', 'a11yfix' ),
			'iframe_title' => apply_filters( 'a11yfix_iframe_default_title', 'Embedded content' ),
			'alt_lookup'   => array( __CLASS__, 'alt_by_url' ),
		);
	}

	/**
	 * Alt text for an attachment URL (alt field > title).
	 * External URLs are skipped without a DB hit; lookup misses are capped
	 * per request so a huge hardcoded gallery cannot hammer the database.
	 *
	 * @param string $src Image URL.
	 * @return string
	 */
	public static function alt_by_url( $src ) {
		static $cache = array();
		static $misses = 0;

		if ( array_key_exists( $src, $cache ) ) {
			return $cache[ $src ];
		}

		$alt    = '';
		$src_host  = wp_parse_url( $src, PHP_URL_HOST );
		$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( is_string( $src_host ) && is_string( $home_host ) && strtolower( $src_host ) === strtolower( $home_host ) && $misses < 30 ) {
			$attachment_id = attachment_url_to_postid( $src );
			if ( $attachment_id ) {
				$alt = self::attachment_alt( $attachment_id );
			} else {
				$misses++;
			}
		}
		$cache[ $src ] = $alt;

		return $alt;
	}

	/**
	 * Alt text of one attachment.
	 *
	 * @param int $attachment_id Attachment.
	 * @return string
	 */
	private static function attachment_alt( $attachment_id ) {
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( is_string( $alt ) && '' !== trim( $alt ) ) {
			return trim( $alt );
		}
		$title = get_the_title( $attachment_id );
		if ( is_string( $title ) && '' !== trim( $title ) ) {
			return trim( $title );
		}

		return '';
	}
}
