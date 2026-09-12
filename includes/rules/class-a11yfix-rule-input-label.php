<?php
/**
 * Rule: form fields without an associated label.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A field announced as "edit text" is guesswork. Placeholder is NOT a
 * label: it disappears on input and is not always announced.
 */
class A11yFix_Rule_Input_Label implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'input_no_label';
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
		return 'Form fields without labels are announced as anonymous "edit text" fields; a placeholder is not a label.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Associate a <label for=id> with every field, or add aria-label. Keep placeholders as examples, not labels.';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$out  = array();
		$root = $dom->documentElement;

		foreach ( array( 'input', 'select', 'textarea' ) as $tag ) {
			foreach ( $dom->getElementsByTagName( $tag ) as $el ) {
				if ( ! $el instanceof DOMElement || ! A11yFix_A11yName::is_labelable( $el ) ) {
					continue;
				}
				if ( '' !== A11yFix_A11yName::label_of( $el, $root ) ) {
					continue;
				}
				$out[] = new A11yFix_Finding(
					array(
						'rule'     => $this->id(),
						'severity' => $this->severity(),
						'message'  => ucfirst( $tag ) . ' field has no associated label.',
						'locator'  => $tag . ( $el->hasAttribute( 'name' ) ? '[name="' . A11yFix_Parser::fragment( $el->getAttribute( 'name' ), 40 ) . '"]' : '' ),
						'fragment' => A11yFix_Parser::fragment( $el->ownerDocument->saveHTML( $el ), 100 ),
					)
				);
			}
		}

		return $out;
	}
}
