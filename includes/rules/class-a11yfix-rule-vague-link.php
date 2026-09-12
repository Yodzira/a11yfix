<?php
/**
 * Rule: vague link text ("read more", "click here").
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen-reader users pull up a list of links out of visual context;
 * "read more" x30 is a dead end.
 */
class A11yFix_Rule_Vague_Link implements A11yFix_Rule {

	const VAGUE = array(
		'read more',
		'more',
		'click here',
		'here',
		'learn more',
		'details',
		'подробнее',
		'читать далее',
		'далее',
		'ещё',
		'еще',
		'тут',
		'здесь',
		'ссылка',
	);

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'vague_link';
	}

	/**
	 * Severity.
	 *
	 * @return string
	 */
	public function severity() {
		return 'low';
	}

	/**
	 * Summary.
	 *
	 * @return string
	 */
	public function summary() {
		return 'Generic link text ("read more") means nothing in a screen reader\'s link list.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Make the link text name its target ("Read more: pricing"), or add aria-label with the context.';
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
			if ( '' === A11yFix_Parser::text_of( $el ) ) {
				continue; // Nameless links are the empty_link rule's job.
			}
			if ( $el->hasAttribute( 'aria-label' ) || $el->hasAttribute( 'aria-labelledby' ) ) {
				continue;
			}
			$text = A11yFix_Parser::text_of( $el );
			if ( ! in_array( A11yFix_A11yName::lower( A11yFix_A11yName::collapse( $text ) ), self::VAGUE, true ) ) {
				continue;
			}
			$out[] = new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => 'Vague link text: "' . $text . '".',
					'locator'  => 'a',
					'fragment' => A11yFix_Parser::fragment( $el->ownerDocument->saveHTML( $el ), 100 ),
				)
			);
		}

		return $out;
	}
}
