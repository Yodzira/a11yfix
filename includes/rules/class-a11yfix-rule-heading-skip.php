<?php
/**
 * Rule: heading level skips (h1 -> h3).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen-reader users navigate by headings; a skipped level suggests a
 * missing section and breaks the outline.
 */
class A11yFix_Rule_Heading_Skip implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'heading_skip';
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
		return 'Skipped heading levels (e.g. h2 straight to h4) break the document outline users navigate by.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Use the next lower level after the previous heading, or restructure the section. Nest headings by content, not by visual size.';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$out       = array();
		$prev_rank = 0;

		foreach ( $dom->getElementsByTagName( '*' ) as $el ) {
			if ( ! $el instanceof DOMElement || ! preg_match( '/^h([1-6])$/i', $el->tagName, $m ) ) {
				continue;
			}
			$rank = (int) $m[1];
			if ( $prev_rank > 0 && $rank - $prev_rank > 1 ) {
				$out[] = new A11yFix_Finding(
					array(
						'rule'     => $this->id(),
						'severity' => $this->severity(),
						'message'  => 'Heading level jumps from h' . $prev_rank . ' to h' . $rank . '.',
						'locator'  => 'h' . $rank,
						'fragment' => A11yFix_Parser::fragment( A11yFix_Parser::text_of( $el ), 60 ),
					)
				);
			}
			$prev_rank = $rank;
		}

		return $out;
	}
}
