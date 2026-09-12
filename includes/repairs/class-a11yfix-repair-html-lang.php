<?php
/**
 * Repair: declare the page language on <html>.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds lang="<locale>" to the html element when missing. The value comes
 * from WordPress locale (ru-RU, en-US, ...).
 */
class A11yFix_Repair_Html_Lang implements A11yFix_Repair {

	/**
	 * Id.
	 *
	 * @return string
	 */
	public function id() {
		return 'html_lang';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Declare the page language';
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
		return 'Adds lang to <html> from the site locale when the theme did not print it.';
	}

	/**
	 * Apply.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx lang: string locale tag.
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() ) {
		$root = $dom->documentElement;
		if ( ! $root instanceof DOMElement || 'html' !== strtolower( $root->tagName ) ) {
			return array();
		}
		if ( '' !== trim( $root->getAttribute( 'lang' ) ) ) {
			return array();
		}

		$lang = isset( $ctx['lang'] ) ? trim( (string) $ctx['lang'] ) : '';
		if ( '' === $lang ) {
			return array();
		}

		$root->setAttribute( 'lang', $lang );

		return array(
			new A11yFix_Change(
				array(
					'repair' => $this->id(),
					'target' => 'html',
					'attr'   => 'lang',
					'before' => '',
					'after'  => $lang,
				)
			),
		);
	}
}
