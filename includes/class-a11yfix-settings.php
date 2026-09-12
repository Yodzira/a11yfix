<?php
/**
 * Plugin settings.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single option array with defaults and clamped sanitization.
 */
class A11yFix_Settings {

	const OPTION = 'a11yfix_settings';

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'master_enabled' => true,   // Front-end fixes on/off.
			'scan_enabled'   => true,   // Daily cron scan.
			'scan_max_pages' => 10,
			'retention_days' => 90,
			'repairs'        => A11yFix_Repairs::defaults(),
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$merged = array_merge( self::defaults(), $stored );
		$merged['repairs'] = array_merge( A11yFix_Repairs::defaults(), isset( $stored['repairs'] ) && is_array( $stored['repairs'] ) ? $stored['repairs'] : array() );

		return $merged;
	}

	/**
	 * Sanitize a settings payload.
	 *
	 * @param mixed $in Raw input.
	 * @return array Clean settings.
	 */
	public static function sanitize( $in ) {
		$in       = is_array( $in ) ? $in : array();
		$defaults = self::defaults();

		$clean = array(
			'master_enabled' => ! empty( $in['master_enabled'] ),
			'scan_enabled'   => ! empty( $in['scan_enabled'] ),
			'scan_max_pages' => max( 1, min( 50, isset( $in['scan_max_pages'] ) ? (int) $in['scan_max_pages'] : $defaults['scan_max_pages'] ) ),
			'retention_days' => max( 7, min( 365, isset( $in['retention_days'] ) ? (int) $in['retention_days'] : $defaults['retention_days'] ) ),
			'repairs'        => array(),
		);

		foreach ( A11yFix_Repairs::all() as $repair ) {
			$clean['repairs'][ $repair->id() ] = ! empty( $in['repairs'][ $repair->id() ] );
		}

		return $clean;
	}

	/**
	 * Save sanitized settings.
	 *
	 * @param mixed $in Raw input.
	 * @return array Saved settings.
	 */
	public static function save( $in ) {
		$clean = self::sanitize( $in );
		update_option( self::OPTION, $clean );

		return $clean;
	}
}
