<?php
/**
 * Plugin Name:       A11yFix
 * Plugin URI:        https://github.com/Yodzira/a11yfix
 * Description:       Accessibility audit with real markup fixes: find missing alt text, heading skips, unlabeled form fields — preview and apply safe DOM fixes. No overlay widgets.
 * Version:           0.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       a11yfix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'A11YFIX_VERSION', '0.1.1' );
define( 'A11YFIX_FILE', __FILE__ );
define( 'A11YFIX_DIR', __DIR__ );

spl_autoload_register(
	/**
	 * Class autoloader.
	 * A11yFix_Rule_Missing_Alt -> includes/rules/class-a11yfix-rule-missing-alt.php
	 * A11yFix_Repair_ImgAlt    -> includes/repairs/class-a11yfix-repair-imgalt.php
	 * A11yFix_Scanner          -> includes/class-a11yfix-scanner.php
	 *
	 * @param string $class Class name.
	 * @return void
	 */
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

/**
 * Boot the plugin on plugins_loaded so is_admin() and locale are known.
 *
 * @return void
 */
function a11yfix_boot() {
	A11yFix_Plugin::boot();
}
add_action( 'plugins_loaded', 'a11yfix_boot', 20 );

register_activation_hook(
	__FILE__,
	static function () {
		require_once A11YFIX_DIR . '/includes/class-a11yfix-store.php';
		A11yFix_Store::activate();
		// First scan on the next cron tick; keeps activation fast.
		if ( ! wp_next_scheduled( 'a11yfix_daily_scan' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'a11yfix_daily_scan' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		$timestamp = wp_next_scheduled( 'a11yfix_daily_scan' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'a11yfix_daily_scan' );
		}
		wp_clear_scheduled_hook( 'a11yfix_daily_scan' );
	}
);
