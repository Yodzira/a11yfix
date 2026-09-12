<?php
/**
 * Repair contract and the change record.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One applied change (value object) — the unit of the diff preview.
 */
class A11yFix_Change {

	/** @var string Repair id. */
	public $repair;

	/** @var string Element locator, e.g. img[src~="logo.jpg"]. */
	public $target;

	/** @var string Attribute that was added/filled. */
	public $attr;

	/** @var string Previous value ('' = absent). */
	public $before;

	/** @var string New value. */
	public $after;

	/**
	 * Constructor.
	 *
	 * @param array $args {repair, target, attr, before, after}.
	 */
	public function __construct( array $args ) {
		foreach ( array( 'repair', 'target', 'attr', 'before', 'after' ) as $key ) {
			$this->$key = isset( $args[ $key ] ) ? (string) $args[ $key ] : '';
		}
	}

	/**
	 * Export for JSON.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'repair' => $this->repair,
			'target' => $this->target,
			'attr'   => $this->attr,
			'before' => $this->before,
			'after'  => $this->after,
		);
	}
}

/**
 * A repair is a pure DOM transformation: run it, get the change list.
 * It must never remove existing markup — only add or fill attributes.
 */
interface A11yFix_Repair {

	/**
	 * Stable id, e.g. "img_alt".
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Human label for the admin UI (EN).
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Risky repairs default to OFF; safe ones to ON.
	 *
	 * @return bool
	 */
	public function risky();

	/**
	 * One-phrase description of what it changes (EN).
	 *
	 * @return string
	 */
	public function description();

	/**
	 * Apply to a parsed document.
	 *
	 * @param DOMDocument $dom Document to mutate.
	 * @param array       $ctx Injectables: alt_lookup, lang, submit_label,
	 *                         iframe_title (callables/strings).
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() );
}
