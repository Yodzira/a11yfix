<?php
/**
 * Self-crawl scanner: builds the page list, fetches with an SSRF guard,
 * audits and stores results.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scheduled and on-demand scanning.
 */
class A11yFix_Crawler {

	const LAST_SCAN_OPTION = 'a11yfix_last_scan';
	const PREVIEW_OPTION   = 'a11yfix_preview_html';

	/**
	 * Pages to audit: home, up to three latest posts, first static page.
	 *
	 * @param int $max Upper bound.
	 * @return string[]
	 */
	public static function pages( $max = 10 ) {
		$urls = array( home_url( '/' ) );

		$posts = get_posts(
			array(
				'numberposts'      => 3,
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		foreach ( $posts as $post_id ) {
			$permalink = get_permalink( $post_id );
			if ( $permalink ) {
				$urls[] = $permalink;
			}
		}

		$pages = get_pages(
			array(
				'number'      => 1,
				'sort_column' => 'menu_order',
			)
		);
		foreach ( (array) $pages as $page ) {
			$permalink = get_permalink( $page->ID );
			if ( $permalink ) {
				$urls[] = $permalink;
			}
		}

		$urls = array_values( array_unique( array_filter( $urls ) ) );

		return array_slice( $urls, 0, max( 1, (int) $max ) );
	}

	/**
	 * Fetch one page of this site.
	 *
	 * @param string $url URL.
	 * @return array {ok, code, html?, error?}
	 */
	public static function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'redirection' => 2,
				'sslverify'  => true,
				'user-agent' => 'A11yFix/' . A11YFIX_VERSION . ' (self-scan; ' . home_url( '/' ) . ')',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'    => false,
				'code'  => 0,
				'error' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== $code ) {
			return array(
				'ok'    => false,
				'code'  => $code,
				'error' => 'HTTP ' . $code,
			);
		}

		return array(
			'ok'   => true,
			'code' => $code,
			'html' => $body,
		);
	}

	/**
	 * Run a full scan now (used by cron and the admin button).
	 *
	 * @return array Summary {batch_id, at, pages: url => {ok, code, error?, findings}}.
	 */
	public static function run_scan() {
		$settings = A11yFix_Settings::get();
		$scanner  = new A11yFix_Scanner();
		$home     = home_url( '/' );

		$pages  = self::pages( $settings['scan_max_pages'] );
		$stored = array();
		$report = array();

		foreach ( $pages as $url ) {
			$allowed = A11yFix_Guard::url_allowed( $url, $home );
			if ( ! $allowed[0] ) {
				$report[ $url ] = array(
					'ok'    => false,
					'code'  => 0,
					'error' => 'Skipped: ' . $allowed[1],
				);
				continue;
			}

			$response = self::fetch( $url );
			if ( ! $response['ok'] ) {
				$report[ $url ] = $response;
				continue;
			}

			$findings = $scanner->scan( $response['html'] );
			$stored[ $url ] = $findings;
			$report[ $url ] = array(
				'ok'       => true,
				'code'     => $response['code'],
				'findings' => count( $findings ),
			);

			// Cache the homepage HTML (capped) for the repair preview.
			if ( self::is_home( $url ) ) {
				update_option( self::PREVIEW_OPTION, substr( $response['html'], 0, 300 * 1024 ), false );
			}
		}

		$batch_id = $stored ? A11yFix_Store::record_batch( $stored ) : 0;
		A11yFix_Store::prune( $settings['retention_days'] );

		$summary = array(
			'batch_id' => $batch_id,
			'at'       => current_time( 'mysql' ),
			'pages'    => $report,
		);
		update_option( self::LAST_SCAN_OPTION, $summary, false );

		return $summary;
	}

	/**
	 * Cron entry point.
	 *
	 * @return void
	 */
	public static function run_scheduled() {
		$settings = A11yFix_Settings::get();
		if ( ! $settings['scan_enabled'] ) {
			return;
		}
		self::run_scan();
	}

	/**
	 * Whether a URL is the site homepage.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private static function is_home( $url ) {
		return rtrim( (string) $url, '/' ) === rtrim( home_url( '/' ), '/' );
	}
}
