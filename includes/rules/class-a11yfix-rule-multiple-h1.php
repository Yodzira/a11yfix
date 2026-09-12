<?php
/**
 * Rule: more than one h1 on a page.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One page, one main heading. Extra h1 elements (theme + page builders
 * love to add them) dilute the outline.
 */
class A11yFix_Rule_Multiple_H1 implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'multiple_h1';
	}

	/**
	 * Severity.
	 *
	 * @return string
	 */
	public function severity() {
		return 'medium';
	}

	/**
	 * Summary.
	 *
	 * @return string
	 */
	public function summary() {
		return 'A page should have a single h1 that names its main content.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Keep the page-level h1 and demote the rest to h2/h3 (or to styled text if they are purely decorative).';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$h1s = array();
		foreach ( $dom->getElementsByTagName( 'h1' ) as $el ) {
			if ( $el instanceof DOMElement ) {
				$h1s[] = $el;
			}
		}

		if ( count( $h1s ) < 2 ) {
			return array();
		}

		$titles = array();
		foreach ( $h1s as $el ) {
			$titles[] = A11yFix_Parser::fragment( A11yFix_Parser::text_of( $el ), 40 );
		}

		return array(
			new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => count( $h1s ) . ' h1 elements found on one page.',
					'locator'  => 'h1',
					'fragment' => A11yFix_Parser::fragment( implode( ' | ', $titles ), 100 ),
				)
			),
		);
	}
}
