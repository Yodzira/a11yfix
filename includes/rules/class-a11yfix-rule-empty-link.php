<?php
/**
 * Rule: links with no accessible name.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An <a href> without a name is announced as "link" — unusable out of
 * context, and invisible to keyboard users on icon links.
 */
class A11yFix_Rule_Empty_Link implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'empty_link';
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
		return 'Links without a text or an accessible name (icon-only links) are announced as just "link".';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Add text, or aria-label on icon-only links, or alt on the image inside the link.';
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
		foreach ( $dom->getElementsByTagName( 'a' ) as $el ) {
			if ( ! $el instanceof DOMElement || ! $el->hasAttribute( 'href' ) ) {
				continue;
			}
			if ( '' !== A11yFix_A11yName::of( $el ) ) {
				continue;
			}
			$out[] = new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => 'Link has no accessible name.',
					'locator'  => 'a[href="' . A11yFix_Parser::fragment( $el->getAttribute( 'href' ), 60 ) . '"]',
					'fragment' => A11yFix_Parser::fragment( $el->ownerDocument->saveHTML( $el ), 100 ),
				)
			);
		}

		return $out;
	}
}
