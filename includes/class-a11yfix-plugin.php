<?php
/**
 * Plugin boot.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires admin, cron and front-end layers.
 */
class A11yFix_Plugin {

	/**
	 * Boot on plugins_loaded.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( is_admin() ) {
			new A11yFix_Admin();
		}

		add_action( 'a11yfix_daily_scan', array( 'A11yFix_Crawler', 'run_scheduled' ) );

		A11yFix_Front::boot();
	}
}
