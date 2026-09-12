<?php
/**
 * Uninstall cleanup: options, cron events, findings table, cached HTML.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

a11yfix_uninstall_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function a11yfix_uninstall_cleanup() {
	global $wpdb;

	delete_option( 'a11yfix_settings' );
	delete_option( 'a11yfix_last_scan' );
	delete_option( 'a11yfix_preview_html' );

	wp_clear_scheduled_hook( 'a11yfix_daily_scan' );

	$table = $wpdb->prefix . 'a11yfix_findings';
	// phpcs:ignore WordPress.DB.PreparedSQL -- identifier is derived from $wpdb->prefix only.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
