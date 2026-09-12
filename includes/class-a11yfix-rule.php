<?php
/**
 * Rule contract for audit checks.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every audit rule is a pure DOM check: no WP calls, no side effects.
 */
interface A11yFix_Rule {

	/**
	 * Stable rule id (snake case), e.g. "missing_alt".
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Severity: 'high' | 'medium' | 'low'.
	 *
	 * @return string
	 */
	public function severity();

	/**
	 * One-phrase human explanation of why this matters (EN).
	 *
	 * @return string
	 */
	public function summary();

	/**
	 * How to fix it manually (EN).
	 *
	 * @return string
	 */
	public function hint();

	/**
	 * Run the check.
	 *
	 * @param DOMDocument $dom  Parsed document.
	 * @param string      $html Original HTML (for raw-string checks).
	 * @return A11yFix_Finding[]
	 */
	public function check( DOMDocument $dom, $html );
}
