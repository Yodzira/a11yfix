<?php
/**
 * Repair: accessible names for controls (risk-toggled, OFF by default).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conservative name-guessing: link names from the URL slug, submit-button
 * labels, aria-label from placeholder. Every branch skips elements that
 * already have a name.
 */
class A11yFix_Repair_Control_Name implements A11yFix_Repair {

	/**
	 * Id.
	 *
	 * @return string
	 */
	public function id() {
		return 'control_name';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Name unlabeled controls (from URL / placeholder)';
	}

	/**
	 * Risky?
	 *
	 * @return bool
	 */
	public function risky() {
		return true;
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return 'Adds aria-label to icon links (derived from the URL), value="Submit" to blank submit buttons, and aria-label from placeholder to unlabeled fields. Review the preview before enabling.';
	}

	/**
	 * Apply.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx submit_label: string.
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() ) {
		$changes = array();
		$root    = $dom->documentElement;

		foreach ( $dom->getElementsByTagName( 'a' ) as $el ) {
			if ( ! $el instanceof DOMElement || ! $el->hasAttribute( 'href' ) ) {
				continue;
			}
			if ( '' !== A11yFix_A11yName::of( $el ) ) {
				continue;
			}
			$name = $this->name_from_url( $el->getAttribute( 'href' ) );
			if ( '' === $name ) {
				continue;
			}
			$el->setAttribute( 'aria-label', $name );
			$changes[] = new A11yFix_Change(
				array(
					'repair' => $this->id(),
					'target' => 'a[href="' . A11yFix_Parser::fragment( $el->getAttribute( 'href' ), 60 ) . '"]',
					'attr'   => 'aria-label',
					'before' => '',
					'after'  => $name,
				)
			);
		}

		foreach ( $dom->getElementsByTagName( 'input' ) as $el ) {
			if ( ! $el instanceof DOMElement ) {
				continue;
			}
			$type = strtolower( $el->getAttribute( 'type' ) );
			if ( in_array( $type, array( 'submit', 'reset' ), true ) && '' === trim( $el->getAttribute( 'value' ) ) ) {
				$label = isset( $ctx['submit_label'] ) && '' !== trim( (string) $ctx['submit_label'] ) ? trim( (string) $ctx['submit_label'] ) : 'Submit';
				$el->setAttribute( 'value', $label );
				$changes[] = new A11yFix_Change(
					array(
						'repair' => $this->id(),
						'target' => 'input[type=' . $type . ']',
						'attr'   => 'value',
						'before' => '',
						'after'  => $label,
					)
				);
				continue;
			}
			if ( A11yFix_A11yName::is_labelable( $el ) && '' === A11yFix_A11yName::label_of( $el, $root ) ) {
				$placeholder = A11yFix_A11yName::collapse( $el->getAttribute( 'placeholder' ) );
				if ( '' !== $placeholder ) {
					$el->setAttribute( 'aria-label', $placeholder );
					$changes[] = new A11yFix_Change(
						array(
							'repair' => $this->id(),
							'target' => 'input[name="' . A11yFix_Parser::fragment( $el->getAttribute( 'name' ), 40 ) . '"]',
							'attr'   => 'aria-label',
							'before' => '',
							'after'  => $placeholder,
						)
					);
				}
			}
		}

		return $changes;
	}

	/**
	 * Human-readable name from a URL slug: /about-team/ -> "About team".
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function name_from_url( $url ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure core.
		$path = (string) parse_url( $url, PHP_URL_PATH );
		$slug = basename( trim( $path, '/' ) );
		if ( '' === $slug || ! preg_match( '/[a-z]/i', $slug ) ) {
			return '';
		}
		$slug = preg_replace( '/\.[a-z0-9]{2,5}$/i', '', $slug );
		$slug = preg_replace( '/[-_]+/', ' ', $slug );
		$slug = A11yFix_A11yName::collapse( $slug );
		if ( '' === $slug || strlen( $slug ) > 60 ) {
			return '';
		}

		return ucfirst( $slug );
	}
}
