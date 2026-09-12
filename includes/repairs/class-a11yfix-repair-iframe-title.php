<?php
/**
 * Repair: title on frames (risk-toggled, OFF by default).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frames need a title to be skippable/announcable. The generic value is
 * filterable; well-known providers get a specific one.
 */
class A11yFix_Repair_Iframe_Title implements A11yFix_Repair {

	/**
	 * Id.
	 *
	 * @return string
	 */
	public function id() {
		return 'iframe_title';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Title untitled iframes';
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
		return 'Adds title to iframes that have none ("Video" for YouTube/Vimeo, otherwise a generic label).';
	}

	/**
	 * Apply.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx iframe_title: string default label.
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() ) {
		$changes = array();
		$default = isset( $ctx['iframe_title'] ) && '' !== trim( (string) $ctx['iframe_title'] ) ? trim( (string) $ctx['iframe_title'] ) : 'Embedded content';

		foreach ( $dom->getElementsByTagName( 'iframe' ) as $el ) {
			if ( ! $el instanceof DOMElement ) {
				continue;
			}
			if ( '' !== trim( $el->getAttribute( 'title' ) ) ) {
				continue;
			}
			if ( 'true' === strtolower( $el->getAttribute( 'aria-hidden' ) ) || 'presentation' === strtolower( $el->getAttribute( 'role' ) ) ) {
				continue;
			}
			$src  = strtolower( $el->getAttribute( 'src' ) );
			$name = $default;
			if ( false !== strpos( $src, 'youtube' ) || false !== strpos( $src, 'youtu.be' ) || false !== strpos( $src, 'vimeo' ) ) {
				$name = 'Video';
			} elseif ( false !== strpos( $src, 'maps.google' ) || false !== strpos( $src, 'yandex map' ) ) {
				$name = 'Map';
			}
			$el->setAttribute( 'title', $name );
			$changes[] = new A11yFix_Change(
				array(
					'repair' => $this->id(),
					'target' => 'iframe',
					'attr'   => 'title',
					'before' => '',
					'after'  => $name,
				)
			);
		}

		return $changes;
	}
}
