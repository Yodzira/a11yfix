<?php
/**
 * Repair: fill missing alt from attachment metadata.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * alt is filled from a lookup callable (WP: attachment title/caption by
 * URL). Never overwrites an existing alt; never touches decorative images.
 */
class A11yFix_Repair_Img_Alt implements A11yFix_Repair {

	/**
	 * Id.
	 *
	 * @return string
	 */
	public function id() {
		return 'img_alt';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Fill missing image alt text';
	}

	/**
	 * Risky?
	 *
	 * @return bool
	 */
	public function risky() {
		return false;
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return 'Adds alt to images that have none, using the media-library title/caption. Decorative images are skipped.';
	}

	/**
	 * Apply.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx alt_lookup: callable(string $src): ?string.
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() ) {
		$lookup = isset( $ctx['alt_lookup'] ) && is_callable( $ctx['alt_lookup'] ) ? $ctx['alt_lookup'] : null;
		if ( ! $lookup ) {
			return array();
		}

		$changes = array();
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			if ( ! $img instanceof DOMElement ) {
				continue;
			}
			if ( $img->hasAttribute( 'alt' ) && '' !== trim( $img->getAttribute( 'alt' ) ) ) {
				continue;
			}
			if ( $img->hasAttribute( 'role' ) && 'presentation' === strtolower( $img->getAttribute( 'role' ) ) ) {
				continue;
			}
			if ( $img->hasAttribute( 'aria-hidden' ) && 'true' === strtolower( $img->getAttribute( 'aria-hidden' ) ) ) {
				continue;
			}
			$src = $img->getAttribute( 'src' );
			if ( '' === $src ) {
				continue;
			}
			$alt = trim( (string) call_user_func( $lookup, $src ) );
			if ( '' === $alt ) {
				continue;
			}
			$before   = $img->getAttribute( 'alt' );
			$img->setAttribute( 'alt', $alt );
			$changes[] = new A11yFix_Change(
				array(
					'repair' => $this->id(),
					// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure core.
				'target' => 'img[src~="' . basename( (string) parse_url( $src, PHP_URL_PATH ) ) . '"]',
					'attr'   => 'alt',
					'before' => $before,
					'after'  => $alt,
				)
			);
		}

		return $changes;
	}
}
