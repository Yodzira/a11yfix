<?php
/**
 * Rule: buttons with no accessible name.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * <button> with no text/aria-label; <input type=submit> with no value.
 */
class A11yFix_Rule_Empty_Button implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'empty_button';
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
		return 'Buttons without a name give no clue what pressing them does.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Add text inside <button>, value on <input type=submit>, or aria-label for icon buttons.';
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

		foreach ( $dom->getElementsByTagName( 'button' ) as $el ) {
			if ( $el instanceof DOMElement && '' === A11yFix_A11yName::of( $el ) ) {
				$out[] = $this->finding( $el, 'button' );
			}
		}

		foreach ( $dom->getElementsByTagName( 'input' ) as $el ) {
			if ( ! $el instanceof DOMElement ) {
				continue;
			}
			$type = strtolower( $el->getAttribute( 'type' ) );
			if ( ! in_array( $type, array( 'submit', 'reset', 'button' ), true ) ) {
				continue;
			}
			if ( '' === A11yFix_A11yName::of( $el ) ) {
				$out[] = $this->finding( $el, 'input[type=' . $type . ']' );
			}
		}

		return $out;
	}

	/**
	 * Build the finding.
	 *
	 * @param DOMElement $el      Element.
	 * @param string     $locator Locator string.
	 * @return A11yFix_Finding
	 */
	private function finding( DOMElement $el, $locator ) {
		return new A11yFix_Finding(
			array(
				'rule'     => $this->id(),
				'severity' => $this->severity(),
				'message'  => 'Button has no accessible name.',
				'locator'  => $locator,
				'fragment' => A11yFix_Parser::fragment( $el->ownerDocument->saveHTML( $el ), 100 ),
			)
		);
	}
}
