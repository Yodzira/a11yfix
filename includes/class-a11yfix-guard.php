<?php
/**
 * SSRF guard for the self-crawl scanner (pure logic).
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The scanner may only fetch public pages of the site it runs on.
 */
class A11yFix_Guard {

	/**
	 * Decide whether a URL is fetchable. The site's own configured host is
	 * always the reference point; everything else (foreign hosts, non-HTTP
	 * schemes, exotic ports, userinfo, private/reserved IP literals) is
	 * rejected.
	 *
	 * @param string $url Candidate URL.
	 * @param string $home Site home URL.
	 * @return array [bool allowed, string reason].
	 */
	public static function url_allowed( $url, $home ) {
		$parts = self::parse( $url );
		$base  = self::parse( $home );

		if ( ! $parts || ! $base ) {
			return array( false, 'unparsable URL' );
		}
		if ( ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) ) {
			return array( false, 'scheme not allowed: ' . $parts['scheme'] );
		}
		if ( '' !== $parts['user'] ) {
			return array( false, 'userinfo in URL not allowed' );
		}
		if ( ! in_array( $parts['port'], array( null, 80, 443, 8000, 8080, 8443, 8888 ), true ) ) {
			return array( false, 'port not allowed: ' . $parts['port'] );
		}
		$host = $parts['host'];
		$root = $base['host'];
		if ( '' === $host || '' === $root || strtolower( $host ) !== strtolower( $root ) ) {
			return array( false, 'host mismatch: ' . $host . ' != ' . $root );
		}
		if ( self::is_private_ip( $host ) ) {
			return array( false, 'private/reserved IP not allowed: ' . $host );
		}

		return array( true, '' );
	}

	/**
	 * Parse URL into normalized parts.
	 *
	 * @param string $url URL.
	 * @return array|null
	 */
	private static function parse( $url ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure PHP core, unit-tested without WordPress.
		$p = parse_url( trim( (string) $url ) );
		if ( ! $p || empty( $p['host'] ) ) {
			return null;
		}

		return array(
			'scheme' => strtolower( isset( $p['scheme'] ) ? $p['scheme'] : '' ),
			'host'   => strtolower( $p['host'] ),
			'port'   => isset( $p['port'] ) ? (int) $p['port'] : null,
			'user'   => isset( $p['user'] ) ? $p['user'] : '',
		);
	}

	/**
	 * Whether the host is a private/reserved IP literal.
	 *
	 * @param string $host Host.
	 * @return bool
	 */
	private static function is_private_ip( $host ) {
		if ( preg_match( '/^\d{1,3}(\.\d{1,3}){3}$/', $host ) ) {
			if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
			return false;
		}
		// IPv6 literal (strip brackets).
		$ipv6 = trim( $host, '[]' );
		if ( false !== strpos( $ipv6, ':' ) ) {
			if ( ! filter_var( $ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				return true;
			}
			$packed = @inet_pton( $ipv6 );
			if ( false === $packed ) {
				return true;
			}
			// ::1 loopback, ::/8 unspecified, fc00::/7 ULA, fe80::/10 link-local, ::ffff: IPv4-mapped.
			if ( substr( $packed, 0, 15 ) === str_repeat( "\0", 15 ) ) {
				return true; // :: and ::1.
			}
			$head = ord( $packed[0] );
			if ( $head >= 0xfc && $head <= 0xfd ) {
				return true; // fc00::/7.
			}
			if ( $head === 0xfe && ( ord( $packed[1] ) & 0xc0 ) === 0x80 ) {
				return true; // fe80::/10.
			}
			if ( substr( $packed, 0, 10 ) === str_repeat( "\0", 10 ) && "\xff\xff" === substr( $packed, 10, 2 ) ) {
				return self::is_private_ip( inet_ntop( substr( $packed, 12 ) ) ); // ::ffff:0:0/96 IPv4-mapped.
			}
		}

		return false;
	}
}
