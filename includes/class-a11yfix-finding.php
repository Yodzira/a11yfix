<?php
/**
 * Single audit finding (value object).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One issue found by one rule on one page.
 */
class A11yFix_Finding {

	/** @var string Rule id, e.g. "missing-alt". */
	public $rule;

	/** @var string 'high'|'medium'|'low'. */
	public $severity;

	/** @var string Human message (EN). */
	public $message;

	/** @var string Element locator, e.g. img[src~="logo.jpg"]. */
	public $locator;

	/** @var string Short sanitized content fragment. */
	public $fragment;

	/**
	 * Constructor.
	 *
	 * @param array $args {rule, severity, message, locator, fragment}.
	 */
	public function __construct( array $args ) {
		foreach ( array( 'rule', 'severity', 'message', 'locator', 'fragment' ) as $key ) {
			$this->$key = isset( $args[ $key ] ) ? (string) $args[ $key ] : '';
		}
	}

	/**
	 * Export for storage/JSON.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'rule'     => $this->rule,
			'severity' => $this->severity,
			'message'  => $this->message,
			'locator'  => $this->locator,
			'fragment' => $this->fragment,
		);
	}
}
