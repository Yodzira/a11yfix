<?php
/**
 * Rule: images without alt attribute.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * alt="" is valid (decorative image); a MISSING alt is the problem:
 * screen readers announce the file name instead.
 */
class A11yFix_Rule_Missing_Alt implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'missing_alt';
	}

	/**
	 * Severity.
	 *
	 * @return string
	 */
	public function severity() {
		return 'high';
	}

	/**
	 * Summary.
	 *
	 * @return string
	 */
	public function summary() {
		return 'Images without an alt attribute are announced by file name, which tells a screen-reader user nothing.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Add alt to every <img>: meaningful text for informative images, alt="" for purely decorative ones.';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$out = array();
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			if ( ! $img instanceof DOMElement || $img->hasAttribute( 'alt' ) ) {
				continue;
			}
			if ( $img->hasAttribute( 'role' ) && 'presentation' === strtolower( $img->getAttribute( 'role' ) ) ) {
				continue;
			}
			if ( $img->hasAttribute( 'aria-hidden' ) && 'true' === strtolower( $img->getAttribute( 'aria-hidden' ) ) ) {
				continue;
			}
			$src  = $img->getAttribute( 'src' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure core.
			$name = $src ? basename( (string) parse_url( $src, PHP_URL_PATH ) ) : '';
			$out[] = new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => 'Image has no alt attribute.',
					'locator'  => $src ? 'img[src~="' . $name . '"]' : 'img',
					'fragment' => A11yFix_Parser::fragment( $name ? $name : $img->getAttribute( 'title' ) ),
				)
			);
		}

		return $out;
	}
}
