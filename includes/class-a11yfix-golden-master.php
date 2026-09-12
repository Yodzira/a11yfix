<?php
/**
 * Golden Master comparator: proves a repaired document differs from the
 * original ONLY in allowlisted attributes.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Side-by-side DOM tree comparison.
 */
class A11yFix_GoldenMaster {

	/**
	 * Attributes each repair is allowed to add/change, per tag.
	 *
	 * @return array<string,string[]>
	 */
	public static function allowlist() {
		return array(
			'html'     => array( 'lang' ),
			'img'      => array( 'alt' ),
			'th'       => array( 'scope' ),
			'a'        => array( 'aria-label' ),
			'button'   => array( 'aria-label' ),
			'input'    => array( 'aria-label', 'value' ),
			'select'   => array( 'aria-label' ),
			'textarea' => array( 'aria-label' ),
			'iframe'   => array( 'title' ),
		);
	}

	/**
	 * Compare before/after documents.
	 *
	 * @param string                  $before_html Original HTML.
	 * @param string                  $after_html  Repaired HTML.
	 * @param array<string,string[]>|null $allow    Custom allowlist (tag => attrs).
	 * @return string[] Human-readable violations; empty array = pass.
	 */
	public static function compare( $before_html, $after_html, array $allow = null ) {
		$allow = $allow ? $allow : self::allowlist();

		$before = A11yFix_Parser::load( $before_html );
		$after  = A11yFix_Parser::load( $after_html );
		if ( ! $before || ! $after || ! $before->documentElement || ! $after->documentElement ) {
			return array( 'unparsable document' );
		}

		$violations = array();
		self::walk( $before->documentElement, $after->documentElement, $allow, $violations );

		return $violations;
	}

	/**
	 * Recursive comparison of two element nodes.
	 *
	 * @param DOMElement $before     Original element.
	 * @param DOMElement $after      Repaired element.
	 * @param array      $allow      Allowlist.
	 * @param array      $violations Collector.
	 * @return void
	 */
	private static function walk( DOMElement $before, DOMElement $after, array $allow, array &$violations ) {
		$tag_before = strtolower( $before->tagName );
		$tag_after  = strtolower( $after->tagName );

		if ( $tag_before !== $tag_after ) {
			$violations[] = 'tag changed: ' . $tag_before . ' -> ' . $tag_after;
			return;
		}

		$attrs_before = self::attrs( $before );
		$attrs_after  = self::attrs( $after );
		$allowed      = isset( $allow[ $tag_before ] ) ? $allow[ $tag_before ] : array();

		foreach ( array_unique( array_merge( array_keys( $attrs_before ), array_keys( $attrs_after ) ) ) as $name ) {
			$had = isset( $attrs_before[ $name ] );
			$has = isset( $attrs_after[ $name ] );

			if ( in_array( $name, $allowed, true ) ) {
				// Allowed: may appear (when absent/empty before), otherwise must be equal.
				$was_blank = ! $had || '' === trim( $attrs_before[ $name ] );
				if ( $had && ! $was_blank && ( ! $has || $attrs_before[ $name ] !== $attrs_after[ $name ] ) ) {
					$violations[] = $tag_before . '@' . $name . ' changed: "' . $attrs_before[ $name ] . '" -> "' . ( $has ? $attrs_after[ $name ] : '' ) . '"';
				}
				continue;
			}

			if ( $had !== $has || ( $has && $attrs_before[ $name ] !== $attrs_after[ $name ] ) ) {
				$violations[] = $tag_before . '@' . $name . ' changed: "' . ( $had ? $attrs_before[ $name ] : '' ) . '" -> "' . ( $has ? $attrs_after[ $name ] : '' ) . '"';
			}
		}

		$kids_before = self::children( $before );
		$kids_after  = self::children( $after );

		if ( count( $kids_before ) !== count( $kids_after ) ) {
			$violations[] = 'child count changed at <' . $tag_before . '>: ' . count( $kids_before ) . ' -> ' . count( $kids_after );
			return;
		}

		$count = count( $kids_before );
		for ( $i = 0; $i < $count; $i++ ) {
			$a = $kids_before[ $i ];
			$b = $kids_after[ $i ];
			if ( $a instanceof DOMElement && $b instanceof DOMElement ) {
				self::walk( $a, $b, $allow, $violations );
				continue;
			}
			if ( get_class( $a ) !== get_class( $b ) ) {
				$violations[] = 'node type changed at <' . $tag_before . '> child #' . $i;
				continue;
			}
			if ( method_exists( $a, 'data' ) && $a->data !== $b->data ) {
				$violations[] = 'text changed at <' . $tag_before . '> child #' . $i . ': "' . substr( $a->data, 0, 40 ) . '" -> "' . substr( $b->data, 0, 40 ) . '"';
			}
		}
	}

	/**
	 * Attribute map of an element (name => value).
	 *
	 * @param DOMElement $el Element.
	 * @return array<string,string>
	 */
	private static function attrs( DOMElement $el ) {
		$out = array();
		foreach ( $el->attributes as $attr ) {
			$out[ $attr->nodeName ] = $attr->nodeValue;
		}

		return $out;
	}

	/**
	 * Meaningful children (elements, text, comments) of an element.
	 *
	 * @param DOMElement $el Element.
	 * @return DOMNode[]
	 */
	private static function children( DOMElement $el ) {
		$out = array();
		foreach ( $el->childNodes as $node ) {
			if ( $node instanceof DOMElement || $node instanceof DOMText || $node instanceof DOMComment ) {
				$out[] = $node;
			}
		}

		return $out;
	}
}
