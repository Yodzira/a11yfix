<?php
/**
 * HTML loader built on DOMDocument.
 *
 * UTF-8 is assumed (WordPress core is UTF-8). The `<?xml encoding="utf-8"?>`
 * prefix makes libxml treat the input as UTF-8 without mb_convert_encoding,
 * so text nodes survive load->save round-trips without entity bloat.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static HTML parsing helpers.
 */
class A11yFix_Parser {

	/**
	 * Parse a full HTML document. Never throws; returns null only when
	 * libxml cannot produce a document at all.
	 *
	 * @param string $html Raw HTML.
	 * @return DOMDocument|null
	 */
	public static function load( $html ) {
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = true;
		$dom->resolveExternals   = false;
		$dom->substituteEntities = false;

		$internal = libxml_use_internal_errors( true );
		$ok       = $dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $internal );

		if ( ! $ok ) {
			return null;
		}

		// Drop the encoding processing instruction and pin the output
		// encoding, otherwise saveHTML() emits numeric entities for every
		// non-ASCII character (output bloat on any Cyrillic page).
		$child = $dom->firstChild;
		while ( $child ) {
			$next = $child->nextSibling;
			if ( XML_PI_NODE === $child->nodeType ) {
				$dom->removeChild( $child );
			}
			$child = $next;
		}
		$dom->encoding = 'UTF-8';

		return $dom;
	}

	/**
	 * Serialize a document back to HTML.
	 *
	 * saveHTML(node) emits raw UTF-8, while full-document saveHTML()
	 * converts non-ASCII to numeric entities — so we serialize from the
	 * root element and re-attach the original doctype declaration.
	 *
	 * @param DOMDocument $dom Document.
	 * @return string
	 */
	public static function save( DOMDocument $dom ) {
		$out = '';
		$dt  = $dom->doctype;
		if ( $dt instanceof DOMDocumentType ) {
			$out .= '<!DOCTYPE ' . $dt->name;
			if ( $dt->publicId ) {
				$out .= ' PUBLIC "' . $dt->publicId . '" "' . $dt->systemId . '"';
			} elseif ( $dt->systemId ) {
				$out .= ' SYSTEM "' . $dt->systemId . '"';
			}
			$out .= '>' . "\n";
		}
		if ( $dom->documentElement ) {
			$out .= $dom->saveHTML( $dom->documentElement );
		}

		return $out;
	}

	/**
	 * Collapsed, trimmed visible text of a node (used for accessible names).
	 *
	 * @param DOMNode $node Node.
	 * @return string
	 */
	public static function text_of( DOMNode $node ) {
		$text = $node->textContent;
		$text = preg_replace( '/\s+/u', ' ', (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Short sanitized fragment for reports: tags stripped, whitespace
	 * collapsed, hard length cap.
	 *
	 * @param string $raw    Raw string.
	 * @param int    $length Max length.
	 * @return string
	 */
	public static function fragment( $raw, $length = 100 ) {
		$raw = wp_strip_all_tags( (string) $raw );
		$raw = preg_replace( '/[\r\n\t ]+/', ' ', $raw );
		$raw = trim( (string) $raw );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $raw, 0, $length );
		}
		return substr( $raw, 0, $length );
	}
}
