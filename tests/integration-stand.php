<?php
/**
 * A11yFix integration tests — run INSIDE the QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/a11yfix-integration.php --allow-root
 *
 * Real plugin classes against the real database and real page renders.
 * Stand plumbing: WP URLs use host port 8080, the container's webserver
 * listens on 80 — internal fetches rewrite the port.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

/** Stand plumbing: fetch a site URL from inside the container (port 80). */
function local_fetch( $url ) {
	$internal = str_replace( ':8080', ':80', $url );
	$response = wp_remote_get( $internal . ( strpos( $internal, '?' ) ? '&' : '?' ) . 'nocache=' . time(), array( 'timeout' => 30, 'sslverify' => false ) );

	return $response;
}

echo "== A11yFix integration ==\n";

global $wpdb;

// 0. Plugin active, classes available, table created by the activation hook.
check( 'plugin active', is_plugin_active( 'a11yfix/a11yfix.php' ) );
check( 'classes loaded', class_exists( 'A11yFix_Scanner' ) && class_exists( 'A11yFix_Store' ) );
$table     = A11yFix_Store::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
check( 'findings table created on activation', $has_table === $table );

// 1. Baseline: repairs OFF so the first render is the true original.
A11yFix_Settings::save( array( 'master_enabled' => '' ) );

// 2. A post with planted issues; its rendered page must produce findings.
$content  = '<h2>Section</h2><h4>Skipped level</h4>';
$content .= '<img src="/wp-content/uploads/2026/09/no-alt-photo.jpg">';
$content .= '<a href="/unknown-icon-page/"><span class="dash"></span></a>';
$content .= '<table><tr><th>Col</th></tr><tr><td>1</td></tr></table>';
$post_id  = wp_insert_post(
	array(
		'post_title'   => 'A11yFix integration target',
		'post_name'    => 'a11yfix-integration-target',
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_author'  => 1,
	)
);
check( 'post created', (bool) $post_id );

$permalink = get_permalink( $post_id );
$response  = local_fetch( $permalink );
$html      = wp_remote_retrieve_body( $response );
check( 'post page fetched (repairs off)', 200 === (int) wp_remote_retrieve_response_code( $response ) && false !== strpos( $html, 'A11yFix integration target' ) );
check( 'original has no scope injection', false === strpos( $html, 'scope="col"' ) );

$findings = ( new A11yFix_Scanner() )->scan( $html );
$rules    = array_unique( wp_list_pluck( array_map( static function ( $f ) { return $f->to_array(); }, $findings ), 'rule' ) );
check( 'scanner finds planted heading_skip', in_array( 'heading_skip', $rules, true ) );
check( 'scanner finds planted missing_alt', in_array( 'missing_alt', $rules, true ) );
check( 'scanner finds planted empty_link', in_array( 'empty_link', $rules, true ) );

// 3. Storage: record, read, prune.
A11yFix_Store::erase_all();
$batch = A11yFix_Store::record_batch( array( $permalink => $findings ) );
check( 'batch recorded', $batch > 0 );
check( 'findings readable', count( A11yFix_Store::findings( $batch ) ) === count( $findings ) );
check( 'pages listed', 1 === count( A11yFix_Store::pages_of( $batch ) ) );
check( 'prune keeps fresh rows', 0 === A11yFix_Store::prune( 90 ) );

// 4. Guard semantics.
check( 'guard allows own permalink', A11yFix_Guard::url_allowed( $permalink, home_url( '/' ) )[0] );
check( 'guard rejects foreign host', ! A11yFix_Guard::url_allowed( 'https://attacker.example/', home_url( '/' ) )[0] );

// 5. Front-end repairs E2E: enable everything, fetch, assert patched HTML.
A11yFix_Settings::save(
	array(
		'master_enabled' => '1',
		'repairs'        => array_fill_keys( array_keys( A11yFix_Repairs::defaults() ), '1' ),
	)
);
$patched_response = local_fetch( $permalink );
$patched          = wp_remote_retrieve_body( $patched_response );

check( 'repaired page still 200', 200 === (int) wp_remote_retrieve_response_code( $patched_response ) );
check( 'repair: lang present on html', (bool) preg_match( '/<html[^>]*lang="/i', $patched ) );
check( 'repair: table scope added', false !== strpos( $patched, 'scope="col"' ) );
check( 'repair: icon link got aria-label', (bool) preg_match( '/aria-label="Unknown icon page"/i', $patched ) );
check( 'repair: content text intact', false !== strpos( $patched, 'Skipped level' ) );
check( 'repair: planted markup intact', false !== strpos( $patched, 'no-alt-photo.jpg' ) );

// 6. Golden Master on the LIVE rendered page.
$violations = A11yFix_GoldenMaster::compare( $html, $patched );
check( 'golden master on live page: no unexpected changes', array() === $violations );
if ( $violations ) {
	echo "    violations:\n    - " . implode( "\n    - ", array_slice( $violations, 0, 10 ) ) . "\n";
}

// 7. Master switch off -> page returns without repairs.
A11yFix_Settings::save( array( 'master_enabled' => '' ) );
$plain = wp_remote_retrieve_body( local_fetch( $permalink ) );
check( 'master off: no scope injection', false === strpos( $plain, 'scope="col"' ) );

// 8. Perf: page with 500 hardcoded images, all repairs ON (worst case).
$imgs = '';
for ( $i = 0; $i < 500; $i++ ) {
	$imgs .= '<img src="/wp-content/uploads/perf/img-' . $i . '.jpg">';
}
$perf_id = wp_insert_post(
	array(
		'post_title'   => 'A11yFix perf target',
		'post_name'    => 'a11yfix-perf-target',
		'post_content' => $imgs,
		'post_status'  => 'publish',
		'post_author'  => 1,
	)
);
A11yFix_Settings::save(
	array(
		'master_enabled' => '1',
		'repairs'        => array_fill_keys( array_keys( A11yFix_Repairs::defaults() ), '1' ),
	)
);
$t0      = microtime( true );
$resp    = local_fetch( get_permalink( $perf_id ) );
$perf_ms = ( microtime( true ) - $t0 ) * 1000;
check( 'perf page renders 200', 200 === (int) wp_remote_retrieve_response_code( $resp ) );
printf( "    perf: 500-img page, all repairs ON: %.0f ms total round-trip\n", $perf_ms );
check( 'perf: total round-trip under 1500 ms (host budget)', $perf_ms < 1500 );

// 9. Crawler scan end-to-end (from inside the container home_url:8080 is
//    unreachable — stand-only artifact; the scan must degrade gracefully).
$summary = A11yFix_Crawler::run_scan();
check( 'scan wrote last-scan meta', is_array( get_option( 'a11yfix_last_scan' ) ) );
check( 'scan attempted every page', count( $summary['pages'] ) > 0 );
check( 'scan degrades to honest errors', ! empty( $summary['pages'] ) && array_key_exists( 'error', reset( $summary['pages'] ) ) );

// 10. Preview pipeline on real HTML (use the fetched page as capture source).
update_option( 'a11yfix_preview_html', substr( $html, 0, 300 * 1024 ), false );
$preview = A11yFix_Preview::run(
	get_option( 'a11yfix_preview_html' ),
	array( 'table_scope' => true, 'control_name' => true ),
	array()
);
check( 'preview returns change list', is_array( $preview['changes'] ) && count( $preview['changes'] ) >= 2 );

// Cleanup: delete posts, reset settings, wipe store.
wp_delete_post( $post_id, true );
wp_delete_post( $perf_id, true );
A11yFix_Settings::save( array() );
A11yFix_Store::erase_all();
delete_option( 'a11yfix_last_scan' );
delete_option( 'a11yfix_preview_html' );

printf( "\n== A11yFix integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
