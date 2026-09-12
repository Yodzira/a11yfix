<?php
/**
 * Rule: missing or empty <title>.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The title is the first thing announced when a page loads and the name
 * of its browser tab / history entry.
 */
class A11yFix_Rule_Page_Title implements A11yFix_Rule {

	/**
	 * Rule id.
	 *
	 * @return string
	 */
	public function id() {
		return 'page_title';
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
		return 'Every page needs a non-empty <title>: it names the tab and is announced first on load.';
	}

	/**
	 * Fix hint.
	 *
	 * @return string
	 */
	public function hint() {
		return 'Add <title>…</title> to the <head>; in WordPress, add_theme_support( \'title-tag\' ) plus a meaningful document title.';
	}

	/**
	 * Check.
	 *
	 * @param DOMDocument $dom  Document.
	 * @param string      $html Raw HTML.
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html ) {
		$titles = $dom->getElementsByTagName( 'title' );
		if ( $titles->length > 0 && '' !== A11yFix_A11yName::collapse( $titles->item( 0 )->textContent ) ) {
			return array();
		}

		return array(
			new A11yFix_Finding(
				array(
					'rule'     => $this->id(),
					'severity' => $this->severity(),
					'message'  => $titles->length > 0 ? 'The <title> element is empty.' : 'No <title> element in the document.',
					'locator'  => 'head > title',
					'fragment' => '',
				)
			),
		);
	}
}
