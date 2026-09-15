<?php
/**
 * Admin pages: audit dashboard and repairs with preview.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything under the A11yFix menu.
 */
class A11yFix_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_a11yfix_run_scan', array( $this, 'handle_run_scan' ) );
		add_action( 'admin_post_a11yfix_save_repairs', array( $this, 'handle_save_repairs' ) );
		add_action( 'wp_ajax_a11yfix_preview', array( $this, 'handle_preview' ) );
	}

	/**
	 * Menu structure.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page( 'A11yFix', 'A11yFix', 'manage_options', 'a11yfix', array( $this, 'render_dashboard' ), 'dashicons-universal-access' );
		add_submenu_page( 'a11yfix', 'A11yFix — Audit', 'Audit', 'manage_options', 'a11yfix', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'a11yfix', 'A11yFix — Repairs', 'Repairs', 'manage_options', 'a11yfix-repairs', array( $this, 'render_repairs' ) );
	}

	/**
	 * Run-scan handler.
	 *
	 * @return void
	 */
	public function handle_run_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a11yfix' ) );
		}
		check_admin_referer( 'a11yfix_run_scan' );

		A11yFix_Crawler::run_scan();
		wp_safe_redirect( admin_url( 'admin.php?page=a11yfix&scanned=1' ) );
		exit;
	}

	/**
	 * Save repairs/settings handler.
	 *
	 * @return void
	 */
	public function handle_save_repairs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a11yfix' ) );
		}
		check_admin_referer( 'a11yfix_save_repairs' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Settings::sanitize().
		$payload = isset( $_POST['a11yfix'] ) ? (array) $_POST['a11yfix'] : array();
		A11yFix_Settings::save( $payload );

		// Sync the daily cron with the toggle.
		if ( ! empty( $payload['scan_enabled'] ) && ! wp_next_scheduled( 'a11yfix_daily_scan' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'a11yfix_daily_scan' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=a11yfix-repairs&saved=1' ) );
		exit;
	}

	/**
	 * AJAX: preview changes of a repair set over the cached homepage HTML.
	 *
	 * @return void
	 */
	public function handle_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
		}
		check_ajax_referer( 'a11yfix_preview', 'nonce' );

		$html = get_option( A11yFix_Crawler::PREVIEW_OPTION, '' );
		if ( ! is_string( $html ) || '' === $html ) {
			wp_send_json_error( array( 'message' => __( 'No page captured yet — run an audit first.', 'a11yfix' ) ) );
		}

		$map = array();
		if ( isset( $_POST['repairs'] ) && is_array( $_POST['repairs'] ) ) {
			foreach ( A11yFix_Repairs::all() as $repair ) {
				$map[ $repair->id() ] = ! empty( $_POST['repairs'][ $repair->id() ] );
			}
		}

		$result          = A11yFix_Preview::run( $html, $map, $this->preview_context() );
		$result['html']  = ''; // Not needed in the preview payload.
		$result['total'] = count( $result['changes'] );
		$result['changes'] = array_map(
			static function ( A11yFix_Change $change ) {
				return $change->to_array();
			},
			$result['changes']
		);

		wp_send_json_success( $result );
	}

	/**
	 * Context for preview (same injectables as the front end).
	 *
	 * @return array
	 */
	private function preview_context() {
		return array(
			'lang'         => get_bloginfo( 'language' ),
			'submit_label' => __( 'Submit', 'a11yfix' ),
			'iframe_title' => apply_filters( 'a11yfix_iframe_default_title', 'Embedded content' ),
			'alt_lookup'   => array( 'A11yFix_Front', 'alt_by_url' ),
		);
	}

	/**
	 * Audit dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$last = get_option( A11yFix_Crawler::LAST_SCAN_OPTION, array() );
		$just_scanned = isset( $_GET['scanned'] ) ? sanitize_key( wp_unslash( $_GET['scanned'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag only.
		$this->header();
		?>
		<div class="wrap">
			<h1>A11yFix — Audit</h1>

			<?php if ( '1' === $just_scanned ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Scan finished.', 'a11yfix' ); ?></p></div>
			<?php endif; ?>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a11yfix_run_scan' ), 'a11yfix_run_scan' ) ); ?>">
					<?php esc_html_e( 'Run scan now', 'a11yfix' ); ?>
				</a>
				<?php if ( ! empty( $last['at'] ) ) : ?>
					<span class="description" style="margin-left:12px">
						<?php
						printf(
							/* translators: %s: datetime */
							esc_html__( 'Last scan: %s', 'a11yfix' ),
							esc_html( $last['at'] )
						);
						?>
					</span>
				<?php endif; ?>
			</p>

			<?php
			if ( empty( $last ) ) {
				echo '<p><em>' . esc_html__( 'Not scanned yet. Press “Run scan now” to audit the homepage, latest posts and one page.', 'a11yfix' ) . '</em></p>';
				$this->footer();
				return;
			}

			$batch_id = isset( $last['batch_id'] ) ? (int) $last['batch_id'] : 0;
			$rows     = $batch_id ? A11yFix_Store::findings( $batch_id ) : array();
			$pages    = $batch_id ? A11yFix_Store::pages_of( $batch_id ) : array();
			$rules    = array();
			foreach ( A11yFix_Scanner::default_rules() as $rule ) {
				$rules[ $rule->id() ] = $rule;
			}

			foreach ( $last['pages'] as $url => $info ) :
				if ( empty( $info['ok'] ) ) :
					?>
					<div class="card" style="max-width:960px;padding:12px 16px;margin-bottom:12px">
						<strong><?php echo esc_html( $url ); ?></strong> —
						<span style="color:#b32d2e"><?php echo esc_html( isset( $info['error'] ) ? $info['error'] : __( 'fetch failed', 'a11yfix' ) ); ?></span>
					</div>
					<?php
					continue;
				endif;

				$page_rows = array_values(
					array_filter(
						$rows,
						static function ( $row ) use ( $url ) {
							return $row['page_url'] === $url;
						}
					)
				);
				?>
				<div class="card" style="max-width:960px;padding:12px 16px;margin-bottom:12px">
					<strong><?php echo esc_html( $url ); ?></strong>
					<span class="count"><?php echo esc_html( count( $page_rows ) ); ?> issue(s)</span>
					<?php if ( ! $page_rows ) : ?>
						<p style="color:#00a32a;margin:6px 0 0">✓ <?php esc_html_e( 'No issues found on this page.', 'a11yfix' ); ?></p>
					<?php else : ?>
						<table class="widefat striped" style="margin-top:8px">
							<thead><tr>
								<th style="width:70px"><?php esc_html_e( 'Severity', 'a11yfix' ); ?></th>
								<th style="width:160px"><?php esc_html_e( 'Rule', 'a11yfix' ); ?></th>
								<th><?php esc_html_e( 'Issue', 'a11yfix' ); ?></th>
								<th style="width:30%"><?php esc_html_e( 'How to fix', 'a11yfix' ); ?></th>
							</tr></thead>
							<tbody>
							<?php foreach ( $page_rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row['severity'] ); ?></td>
									<td><code><?php echo esc_html( $row['rule'] ); ?></code></td>
									<td>
										<?php echo esc_html( $row['message'] ); ?>
										<?php if ( $row['fragment'] ) : ?><br><code style="font-size:11px"><?php echo esc_html( $row['locator'] . ' — ' . $row['fragment'] ); ?></code><?php elseif ( $row['locator'] ) : ?><br><code style="font-size:11px"><?php echo esc_html( $row['locator'] ); ?></code><?php endif; ?>
									</td>
									<td><?php echo esc_html( isset( $rules[ $row['rule'] ] ) ? $rules[ $row['rule'] ]->hint() : '' ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$this->footer();
	}

	/**
	 * Repairs page.
	 *
	 * @return void
	 */
	public function render_repairs() {
		$settings = A11yFix_Settings::get();
		$has_preview = get_option( A11yFix_Crawler::PREVIEW_OPTION, '' ) ? true : false;
		$just_saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag only.
		$this->header();
		?>
		<div class="wrap">
			<h1>A11yFix — Repairs</h1>

			<?php if ( '1' === $just_saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'a11yfix' ); ?></p></div>
			<?php endif; ?>

			<p><?php esc_html_e( 'Every repair is a separate toggle. Risky repairs are OFF by default — enable the preview first, review what would change, then switch on.', 'a11yfix' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="a11yfix_save_repairs">
				<?php wp_nonce_field( 'a11yfix_save_repairs' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Front-end fixes', 'a11yfix' ); ?></th>
						<td>
							<label><input type="checkbox" name="a11yfix[master_enabled]" value="1" <?php checked( $settings['master_enabled'] ); ?>>
							<?php esc_html_e( 'Apply enabled repairs to visitors’ pages', 'a11yfix' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Scheduled scan', 'a11yfix' ); ?></th>
						<td>
							<label><input type="checkbox" name="a11yfix[scan_enabled]" value="1" <?php checked( $settings['scan_enabled'] ); ?>>
							<?php esc_html_e( 'Scan daily', 'a11yfix' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Pages per scan', 'a11yfix' ); ?></th>
						<td><input type="number" min="1" max="50" name="a11yfix[scan_max_pages]" value="<?php echo esc_attr( $settings['scan_max_pages'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Keep results (days)', 'a11yfix' ); ?></th>
						<td><input type="number" min="7" max="365" name="a11yfix[retention_days]" value="<?php echo esc_attr( $settings['retention_days'] ); ?>" class="small-text"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Repairs', 'a11yfix' ); ?></h2>
				<table class="widefat striped" style="max-width:960px" id="a11yfix-repairs">
					<thead><tr>
						<th style="width:60px"><?php esc_html_e( 'On', 'a11yfix' ); ?></th>
						<th style="width:120px"></th>
						<th><?php esc_html_e( 'Repair', 'a11yfix' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( A11yFix_Repairs::all() as $repair ) : ?>
						<tr>
							<td><input type="checkbox" name="a11yfix[repairs][<?php echo esc_attr( $repair->id() ); ?>]" value="1"
								class="a11yfix-repair-toggle" data-repair="<?php echo esc_attr( $repair->id() ); ?>"
								<?php checked( ! empty( $settings['repairs'][ $repair->id() ] ) ); ?>></td>
							<td>
								<button type="button" class="button a11yfix-preview-btn" data-repair="<?php echo esc_attr( $repair->id() ); ?>">
									<?php esc_html_e( 'Preview', 'a11yfix' ); ?>
								</button>
							</td>
							<td>
								<strong><?php echo esc_html( $repair->label() ); ?></strong>
								<?php if ( $repair->risky() ) : ?>
									<span style="color:#dba617">● <?php esc_html_e( 'risky — off by default', 'a11yfix' ); ?></span>
								<?php endif; ?>
								<br><span class="description"><?php echo esc_html( $repair->description() ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<div id="a11yfix-preview-result" style="max-width:960px;margin-top:16px"></div>

				<p style="margin-top:16px">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'a11yfix' ); ?></button>
				</p>
			</form>

			<?php if ( ! $has_preview ) : ?>
				<p class="description"><?php esc_html_e( 'Previews use the homepage captured during the last scan. Run the audit once to enable them.', 'a11yfix' ); ?></p>
			<?php endif; ?>
		</div>

		<script>
		(function () {
			var box = document.getElementById('a11yfix-preview-result');
			document.querySelectorAll('.a11yfix-preview-btn').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var payload = new FormData();
					payload.append('action', 'a11yfix_preview');
					payload.append('nonce', '<?php echo esc_js( wp_create_nonce( 'a11yfix_preview' ) ); ?>');
					payload.append('repairs[' + btn.dataset.repair + ']', '1');
					box.innerHTML = '<p><em>…</em></p>';
					fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: payload })
						.then(function (r) { return r.json(); })
						.then(function (json) {
							if (!json.success) { box.innerHTML = '<div class="notice notice-error inline"><p>' + (json.data && json.data.message ? json.data.message : 'Preview failed') + '</p></div>'; return; }
							var rows = json.data.changes.map(function (c) {
								return '<tr><td>' + c.target + '</td><td><code>' + c.attr + '</code></td><td>' + (c.after || '') + '</td></tr>';
							}).join('');
							box.innerHTML = '<div class="notice notice-info inline"><p><strong>' + btn.dataset.repair + '</strong>: ' + json.data.total + ' change(s) on the captured homepage</p></div>' +
								(rows ? '<table class="widefat striped"><thead><tr><th>Target</th><th>Attribute</th><th>New value</th></tr></thead><tbody>' + rows + '</tbody></table>' : '<p><em>No changes needed.</em></p>');
						});
				});
			});
		})();
		</script>
		<?php
		$this->footer();
	}

	/**
	 * Shared page chrome top.
	 *
	 * @return void
	 */
	private function header() {
		echo '<style>.a11yfix-wrap h1{margin-bottom:12px}</style>';
		echo '<div class="notice notice-info"><p>⚡ <strong>A11yFix Pro</strong> — client-ready compliance report (print to PDF), weekly rescans with change digests, agency white-label. <a href="' . esc_url( 'https://yodsira.com/buy/a11yfix' ) . '" target="_blank" rel="noopener">Buy — $99/year &rarr;</a></p></div>';
	}

	/**
	 * Shared page chrome bottom.
	 *
	 * @return void
	 */
	private function footer() {
		echo '<p style="margin-top:24px"><a href="https://github.com/Yodzira/a11yfix" target="_blank" rel="noopener">A11yFix on GitHub</a></p>';
	}
}
