<?php
/**
 * Findings storage: custom table + scan metadata option.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom table {prefix}a11yfix_findings; one batch per scan.
 */
class A11yFix_Store {

	/**
	 * Table name with prefix.
	 *
	 * @global wpdb $wpdb
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'a11yfix_findings';
	}

	/**
	 * Create the table (activation and test bootstrap).
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id bigint(20) unsigned NOT NULL DEFAULT 0,
			page_url varchar(500) NOT NULL DEFAULT '',
			url_hash char(32) NOT NULL DEFAULT '',
			rule varchar(64) NOT NULL DEFAULT '',
			severity varchar(10) NOT NULL DEFAULT '',
			message varchar(255) NOT NULL DEFAULT '',
			locator varchar(191) NOT NULL DEFAULT '',
			fragment varchar(191) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY  (id),
			KEY batch_id (batch_id),
			KEY url_hash (url_hash),
			KEY rule (rule)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Store findings of one scan. Findings are A11yFix_Finding[] or arrays.
	 *
	 * @param array $pages url => findings list.
	 * @return int Batch id.
	 */
	public static function record_batch( array $pages ) {
		global $wpdb;

		$batch_id = (int) round( microtime( true ) * 1000 );
		$now      = current_time( 'mysql' );
		$table    = self::table_name();

		foreach ( $pages as $url => $findings ) {
			$url_hash = md5( (string) $url );
			foreach ( $findings as $finding ) {
				$data = $finding instanceof A11yFix_Finding ? $finding->to_array() : (array) $finding;
				$wpdb->insert(
					$table,
					array(
						'batch_id'   => $batch_id,
						'page_url'   => substr( (string) $url, 0, 500 ),
						'url_hash'   => $url_hash,
						'rule'       => substr( (string) $data['rule'], 0, 64 ),
						'severity'   => substr( (string) $data['severity'], 0, 10 ),
						'message'    => substr( (string) $data['message'], 0, 255 ),
						'locator'    => substr( (string) $data['locator'], 0, 191 ),
						'fragment'   => substr( (string) $data['fragment'], 0, 191 ),
						'created_at' => $now,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}

		return $batch_id;
	}

	/**
	 * Findings of a batch.
	 *
	 * @param int $batch_id Batch.
	 * @return array[] Rows.
	 */
	public static function findings( $batch_id ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT page_url, rule, severity, message, locator, fragment FROM {$table} WHERE batch_id = %d ORDER BY id ASC", (int) $batch_id ),
			ARRAY_A
		);
	}

	/**
	 * Distinct scanned pages of a batch.
	 *
	 * @param int $batch_id Batch.
	 * @return array[] page_url + finding counts.
	 */
	public static function pages_of( $batch_id ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT page_url, COUNT(*) AS findings FROM {$table} WHERE batch_id = %d GROUP BY page_url ORDER BY page_url ASC", (int) $batch_id ),
			ARRAY_A
		);
	}

	/**
	 * Delete findings older than N days.
	 *
	 * @param int $days Days.
	 * @return int|false Deleted rows.
	 */
	public static function prune( $days ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < DATE_SUB(%s, INTERVAL %d DAY)", current_time( 'mysql' ), (int) $days )
		);
	}

	/**
	 * Wipe all findings.
	 *
	 * @return void
	 */
	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Drop the table (uninstall).
	 *
	 * @return void
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier is derived from $wpdb->prefix only.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
