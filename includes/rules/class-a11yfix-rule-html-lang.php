<?php
/**
 * Rule: <html> element without lang.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Screen readers pick the pronunciation language from <html lang>;
 * without it, a Russian page may be read with English phonetics.
 */
class A11yFix_Rule_Html_Lang implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'html_lang';
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
		return 'The page language is not declared, so screen readers may pick the wrong pronunciation.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Set lang on the <html> element, e.g. <html lang="ru">. In WordPress, themes get it from language_attributes().';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$root = $dom->documentElement;
		if ( ! $root instanceof DOMElement || 'html' !== strtolower( $root->tagName ) ) {
			return array();
		}
		$lang = trim( (string) $root->getAttribute( 'lang' ) );
		if ( '' !== $lang ) {
			return array();
		}

		return array(
			new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => 'The <html> element has no lang attribute.',
					'locator'  => 'html',
					'fragment' => '',
				)
			),
		);
	}
}
