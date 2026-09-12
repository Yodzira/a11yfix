<?php
/**
 * Accessible-name computation for interactive elements (pure DOM).
 *
 * Implements the practical subset of the accname spec used by rules and
 * repairs: text content > aria-labelledby > aria-label > title > (img alt).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static helpers.
 */
class A11yFix_A11yName {

	/**
	 * Tags whose accessible name comes from their content.
	 *
	 * @return string[]
	 */
	public static function content_tags() {
		return array( 'a', 'button' );
	}

	/**
	 * Compute the accessible name of an element, or '' when it has none.
	 *
	 * @param DOMElement $el Element.
	 * @return string Collapsed name, '' when empty.
	 */
	public static function of( DOMElement $el ) {
		$tag = strtolower( $el->tagName );

		if ( 'input' === $tag ) {
			$type = strtolower( $el->getAttribute( 'type' ) );
			if ( in_array( $type, array( 'submit', 'reset', 'button' ), true ) ) {
				return self::collapse( $el->getAttribute( 'value' ) );
			}
			if ( 'image' === $type ) {
				return self::collapse( $el->getAttribute( 'alt' ) );
			}
		}

		foreach ( array( 'aria-labelledby', 'aria-label', 'title' ) as $attr ) {
			if ( ! $el->hasAttribute( $attr ) ) {
				continue;
			}
			$value = self::collapse( $el->getAttribute( $attr ) );
			if ( '' !== $value ) {
				if ( 'aria-labelledby' === $attr ) {
					$value = self::collapse( self::ids_text( $el, $el->getAttribute( $attr ) ) );
					if ( '' !== $value ) {
						return $value;
					}
					continue;
				}
				return $value;
			}
		}

		if ( in_array( $tag, self::content_tags(), true ) ) {
			$text = A11yFix_Parser::text_of( $el );
			if ( '' !== $text ) {
				return $text;
			}
			foreach ( $el->getElementsByTagName( 'img' ) as $img ) {
				if ( $img instanceof DOMElement && $img->hasAttribute( 'alt' ) ) {
					$alt = self::collapse( $img->getAttribute( 'alt' ) );
					if ( '' !== $alt ) {
						return $alt;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Whether an element is a form control that needs a label.
	 *
	 * @param DOMElement $el Element.
	 * @return bool
	 */
	public static function is_labelable( DOMElement $el ) {
		$tag = strtolower( $el->tagName );
		if ( in_array( $tag, array( 'select', 'textarea' ), true ) ) {
			return true;
		}
		if ( 'input' !== $tag ) {
			return false;
		}
		$type = strtolower( $el->getAttribute( 'type' ) );

		return ! in_array( $type, array( 'hidden', 'submit', 'reset', 'button', 'image' ), true );
	}

	/**
	 * Label for a labelable control: <label for=id>, wrapping label,
	 * aria-label/-labelledby, or title.
	 *
	 * @param DOMElement $el Element.
	 * @param DOMElement $root Document element (search scope).
	 * @return string
	 */
	public static function label_of( DOMElement $el, DOMElement $root ) {
		foreach ( array( 'aria-labelledby', 'aria-label', 'title' ) as $attr ) {
			if ( $el->hasAttribute( $attr ) ) {
				$value = self::collapse( $el->getAttribute( $attr ) );
				if ( '' !== $value && 'aria-labelledby' !== $attr ) {
					return $value;
				}
			}
		}

		$id = $el->getAttribute( 'id' );
		if ( $id ) {
			$quoted = str_replace( '"', '\\"', $id );
			$labels = $root->ownerDocument ? $root->ownerDocument : $el->ownerDocument;
			$xpath  = new DOMXPath( $labels );
			foreach ( $xpath->query( '//label[@for="' . $quoted . '"]' ) as $label ) {
				$text = A11yFix_Parser::text_of( $label );
				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		for ( $ancestor = $el->parentNode; $ancestor instanceof DOMElement; $ancestor = $ancestor->parentNode ) {
			if ( 'label' === strtolower( $ancestor->tagName ) ) {
				$text = A11yFix_Parser::text_of( $ancestor );
				if ( '' !== $text ) {
					return $text;
				}
			}
		}

		return '';
	}

	/**
	 * Collapse whitespace in a string.
	 *
	 * @param string $value Raw.
	 * @return string
	 */
	public static function collapse( $value ) {
		return trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );
	}

	/**
	 * Locale-safe lowercase without a hard mbstring dependency
	 * (ASCII via strtolower, Cyrillic via map fallback).
	 *
	 * @param string $value Raw.
	 * @return string
	 */
	public static function lower( $value ) {
		$value = (string) $value;
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $value, 'UTF-8' );
		}
		$value = strtolower( $value );
		$upper = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';
		$lower = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';

		return strtr( $value, $upper, $lower );
	}

	/**
	 * Concatenated text of the elements referenced by an idlist.
	 *
	 * @param DOMElement $el     Context element.
	 * @param string     $idlist Space-separated ids.
	 * @return string
	 */
	private static function ids_text( DOMElement $el, $idlist ) {
		$doc  = $el->ownerDocument;
		$text = '';
		foreach ( preg_split( '/\s+/', trim( (string) $idlist ) ) as $id ) {
			if ( ! $id ) {
				continue;
			}
			$quoted = str_replace( '"', '\\"', $id );
			$xpath  = new DOMXPath( $doc );
			$node   = $xpath->query( '//*[@id="' . $quoted . '"]' )->item( 0 );
			if ( $node instanceof DOMElement ) {
				$text .= ' ' . A11yFix_Parser::text_of( $node );
			}
		}

		return $text;
	}
}
