<?php
/**
 * Minimal bootstrap for standalone (non-WP) PHPUnit runs.
 * The audit core (parser, rules, repairs, preview, guard) is pure PHP;
 * only the few WP functions it calls are shimmed here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'A11YFIX_DIR' ) ) {
	define( 'A11YFIX_DIR', dirname( __DIR__ ) );
}

$GLOBALS['__a11yfix_options'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['__a11yfix_options'] ) ? $GLOBALS['__a11yfix_options'][ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['__a11yfix_options'][ $key ] = $value;

	return true;
}

function delete_option( $key ) {
	unset( $GLOBALS['__a11yfix_options'][ $key ] );

	return true;
}

/**
 * WP-faithful shim: strips tags, removes script/style bodies first.
 */
function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = (string) $text;
	if ( strpos( $text, '<' ) !== false ) {
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
		$text = strip_tags( $text );
	}
	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
	}

	return trim( $text );
}

// Same autoloader contract as the plugin entry file.
spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'A11yFix_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 8 ) ) );
		$snake = str_replace( '_', '-', $snake );
		if ( 0 === strpos( $snake, 'rule-' ) ) {
			$file = A11YFIX_DIR . '/includes/rules/class-a11yfix-' . $snake . '.php';
		} elseif ( 0 === strpos( $snake, 'repair-' ) ) {
			$file = A11YFIX_DIR . '/includes/repairs/class-a11yfix-' . $snake . '.php';
		} else {
			$file = A11YFIX_DIR . '/includes/class-a11yfix-' . $snake . '.php';
		}
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);
