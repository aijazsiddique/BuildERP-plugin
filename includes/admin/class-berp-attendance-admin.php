<?php
/**
 * Attendance admin page (bulk + quick entry).
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Attendance_Admin Class
 *
 * Renders the Attendance admin interface (bulk + quick entry).
 */
class BERP_Attendance_Admin {

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_attendance_data' ), 15 );
		add_action( 'admin_post_berp_attendance_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_berp_attendance_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_berp_attendance_export_pdf', array( $this, 'handle_export_pdf' ) );
	}

	/**
	 * Localize data for attendance page scripts.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function localize_attendance_data( $hook ) {
		if ( strpos( $hook, 'builderp-attendance' ) === false ) {
			return;
		}

		$settings   = $this->get_attendance_settings();
		$user_id    = get_current_user_id();
		$last_site  = (int) get_user_meta( $user_id, '_berp_last_site', true );
		$selections = $this->get_user_site_employee_selections( $user_id );
		$employees  = $this->get_active_employees();
		$sites      = $this->get_active_sites();
		$rest_nonce = wp_create_nonce( 'wp_rest' );

		wp_localize_script(
			'berp-admin-scripts',
			'berpAttendance',
			array(
				'restUrl'        => esc_url_raw( rest_url( 'berp/v1/attendance' ) ),
				'nonce'          => $rest_nonce,
				'settings'       => array(
					'editable_days_limit' => $settings['editable_days_limit'],
					'block_future'        => $settings['block_future'],
					'require_site'        => $settings['require_site'],
				),
				'lastSite'       => $last_site,
				'siteSelections' => $selections,
				'employees'      => $employees,
				'sites'          => $sites,
				'strings'        => array(
					'save_success'      => __( 'Attendance saved successfully.', 'BuildERP' ),
					'save_error'        => __( 'Unable to save attendance.', 'BuildERP' ),
					'select_date'       => __( 'Please select a date.', 'BuildERP' ),
					'select_employee'   => __( 'Please select at least one employee.', 'BuildERP' ),
					'select_employee_q' => __( 'Please select an employee.', 'BuildERP' ),
					'select_site'       => __( 'Please select a site.', 'BuildERP' ),
					'selected'          => __( 'selected', 'BuildERP' ),
					'duplicate'         => __( 'Already logged', 'BuildERP' ),
					'no_match'          => __( 'No employee matched your search.', 'BuildERP' ),
				),
			)
		);
	}

	/**
	 * Get the last-selected employees per site for a user (used by admin UI).
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	protected function get_user_site_employee_selections( $user_id ) {
		$raw        = get_user_meta( $user_id, '_berp_attendance_site_employee_selections', true );
		$selections = array();

		if ( ! is_array( $raw ) ) {
			return $selections;
		}

		foreach ( $raw as $site_id => $employee_ids ) {
			$site_id = absint( $site_id );
			if ( ! $site_id ) {
				continue;
			}

			if ( ! is_array( $employee_ids ) ) {
				continue;
			}

			$employee_ids = array_values(
				array_unique(
					array_filter(
						array_map(
							'absint',
							$employee_ids
						)
					)
				)
			);

			if ( empty( $employee_ids ) ) {
				continue;
			}

			$selections[ (string) $site_id ] = $employee_ids;
		}

		return $selections;
	}

	/**
	 * Render attendance page (bulk + quick).
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'berp_view_attendance' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'BuildERP' ) );
		}

		$settings      = $this->get_attendance_settings();
		$today         = wp_date( 'Y-m-d' );
		$user_id       = get_current_user_id();
		$last_site     = (int) get_user_meta( $user_id, '_berp_last_site', true );
		$employees     = $this->get_active_employees();
		$sites         = $this->get_active_sites();
		$default_ot    = isset( $settings['default_overtime_hours'] ) ? floatval( $settings['default_overtime_hours'] ) : 0;
		$block_future  = 1 === (int) $settings['block_future'];
		$max_date_attr = $block_future ? $today : '';
		?>
		<div class="wrap berp-attendance-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php $this->render_notices(); ?>

			<div class="berp-attendance-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="berp-inline-form">
					<?php wp_nonce_field( 'berp_attendance_export', 'berp_attendance_export_nonce' ); ?>
					<input type="hidden" name="action" value="berp_attendance_export" />
					<button type="submit" class="button"><?php esc_html_e( 'Export Attendance (CSV)', 'BuildERP' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="berp-inline-form">
					<?php wp_nonce_field( 'berp_attendance_export', 'berp_attendance_export_nonce' ); ?>
					<input type="hidden" name="action" value="berp_attendance_export_pdf" />
					<button type="submit" class="button"><?php esc_html_e( 'Export Attendance (PDF)', 'BuildERP' ); ?></button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="berp-inline-form">
					<?php wp_nonce_field( 'berp_attendance_import', 'berp_attendance_import_nonce' ); ?>
					<input type="hidden" name="action" value="berp_attendance_import" />
					<label class="screen-reader-text" for="berp_attendance_csv"><?php esc_html_e( 'Import Attendance CSV', 'BuildERP' ); ?></label>
					<input type="file" id="berp_attendance_csv" name="berp_attendance_csv" accept=".csv,text/csv" />
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import CSV', 'BuildERP' ); ?></button>
				</form>
				<p class="description"><?php esc_html_e( 'CSV columns: employee_code, date (YYYY-MM-DD), site_id, overtime_hours, notes', 'BuildERP' ); ?></p>
			</div>

			<h2 class="nav-tab-wrapper">
				<a href="#berp-attendance-bulk" class="nav-tab nav-tab-active" data-berp-tab="bulk"><?php esc_html_e( 'Bulk Entry', 'BuildERP' ); ?></a>
				<a href="#berp-attendance-quick" class="nav-tab" data-berp-tab="quick"><?php esc_html_e( 'Quick Entry', 'BuildERP' ); ?></a>
				<a href="#berp-attendance-view" class="nav-tab" data-berp-tab="view"><?php esc_html_e( 'View Attendance', 'BuildERP' ); ?></a>
			</h2>

			<div id="berp-attendance-bulk" class="berp-tab-panel is-active">
				<form id="berp-bulk-attendance-form">
					<?php wp_nonce_field( 'berp_attendance_bulk', 'berp_attendance_nonce' ); ?>
					<div class="berp-card">
						<div class="berp-row">
							<label class="berp-field">
								<span class="berp-label"><?php esc_html_e( 'Date', 'BuildERP' ); ?></span>
								<input type="date" name="date" value="<?php echo esc_attr( $today ); ?>" <?php echo $max_date_attr ? 'max="' . esc_attr( $max_date_attr ) . '"' : ''; ?> required />
							</label>
							<label class="berp-field">
								<p>
								<span class="berp-label"><?php esc_html_e( 'Site', 'BuildERP' ); ?> </span> 
								<small class="description"><?php esc_html_e( 'Remembers your last selection.', 'BuildERP' ); ?></small>
								</p>
								<select name="site_id" id="berp-bulk-site">
									<option value=""><?php esc_html_e( 'Select site', 'BuildERP' ); ?></option>
									<?php foreach ( $sites as $site ) : ?>
										<option value="<?php echo esc_attr( $site['id'] ); ?>" <?php selected( $last_site, $site['id'] ); ?>>
											<?php echo esc_html( $site['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								
							</label>
							<label class="berp-field">
								<span class="berp-label"><?php esc_html_e( 'Default OT (hrs)', 'BuildERP' ); ?></span>
								<input type="number" name="default_overtime" min="0" step="0.25" value="<?php echo esc_attr( $default_ot ); ?>" />
							</label>
							<label class="berp-field berp-toggle">
								<span class="berp-label"><?php esc_html_e( 'Override duplicates', 'BuildERP' ); ?></span>
								<input type="checkbox" name="override_duplicates" value="1" />
							</label>
							<div class="berp-field compact align-center">
								<span class="berp-label"><?php esc_html_e( 'Selection', 'BuildERP' ); ?></span>
								<div class="berp-attendance-toggles">
									<button type="button" class="button button-secondary" id="berp-select-all"><?php esc_html_e( 'Select All', 'BuildERP' ); ?></button>
									<button type="button" class="button button-secondary" id="berp-deselect-all"><?php esc_html_e( 'Deselect All', 'BuildERP' ); ?></button>
									<span class="berp-progress" id="berp-selection-progress"></span>
								</div>
							</div>
						</div>

						<div class="berp-row control-row">
							<label class="berp-field grow">
								<span class="berp-label"><?php esc_html_e( 'Search employees', 'BuildERP' ); ?></span>
								<input type="search" id="berp-employee-search" placeholder="<?php esc_attr_e( 'Search employee...', 'BuildERP' ); ?>" />
							</label>
							
						</div>
					</div>

					<div class="berp-card">
						<?php if ( empty( $employees ) ) : ?>
							<p class="description"><?php esc_html_e( 'No employees found. Please add active employees first.', 'BuildERP' ); ?></p>
						<?php else : ?>
							<div class="berp-inline-notice">
								<p><strong><?php esc_html_e( 'Duplicate handling:', 'BuildERP' ); ?></strong> <?php esc_html_e( 'Existing attendance on this date is highlighted. Enable "Override duplicates" to update those entries; otherwise they are skipped.', 'BuildERP' ); ?></p>
							</div>
							<div class="berp-attendance-list" id="berp-attendance-list">
								<?php foreach ( $employees as $employee ) : ?>
									<div class="berp-attendance-row" data-employee-id="<?php echo esc_attr( $employee['id'] ); ?>" data-employee-name="<?php echo esc_attr( $employee['name'] ); ?>">
										<label class="berp-attendance-checkbox">
											<input type="checkbox" name="employees[]" value="<?php echo esc_attr( $employee['id'] ); ?>" checked />
											<span class="berp-employee-label">
												<?php echo esc_html( $employee['code'] ); ?> - <?php echo esc_html( $employee['name'] ); ?>
											</span>
										</label>
										<div class="berp-attendance-overtime">
											<label>
												<span class="berp-label-small"><?php esc_html_e( 'Overtime', 'BuildERP' ); ?></span>
												<input type="number" step="0.25" min="0" name="overtime[<?php echo esc_attr( $employee['id'] ); ?>]" value="<?php echo esc_attr( $default_ot ); ?>" />
											</label>
											<span class="berp-duplicate-flag" aria-live="polite"></span>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="berp-card align-end">
						<p class="submit">
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Save Attendance', 'BuildERP' ); ?>
							</button>
							<span class="spinner" aria-hidden="true"></span>
							<span class="berp-response-message" aria-live="polite"></span>
						</p>
					</div>
				</form>
			</div>

			<div id="berp-attendance-quick" class="berp-tab-panel">
				<form id="berp-quick-attendance-form" class="berp-card">
					<?php wp_nonce_field( 'berp_attendance_quick', 'berp_attendance_quick_nonce' ); ?>
					<div class="berp-row">
						<label class="berp-field">
							<span class="berp-label"><?php esc_html_e( 'Date', 'BuildERP' ); ?></span>
							<input type="date" name="date" value="<?php echo esc_attr( $today ); ?>" <?php echo $max_date_attr ? 'max="' . esc_attr( $max_date_attr ) . '"' : ''; ?> required />
						</label>
						<label class="berp-field">
							<span class="berp-label"><?php esc_html_e( 'Site', 'BuildERP' ); ?></span>
							<select name="site_id">
								<option value=""><?php esc_html_e( 'Select site', 'BuildERP' ); ?></option>
								<?php foreach ( $sites as $site ) : ?>
									<option value="<?php echo esc_attr( $site['id'] ); ?>" <?php selected( $last_site, $site['id'] ); ?>>
										<?php echo esc_html( $site['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="berp-field">
							<span class="berp-label"><?php esc_html_e( 'Overtime (hrs)', 'BuildERP' ); ?></span>
							<input type="number" name="overtime" step="0.25" min="0" value="<?php echo esc_attr( $default_ot ); ?>" />
						</label>
					</div>

					<div class="berp-row">
						<label class="berp-field grow">
							<span class="berp-label"><?php esc_html_e( 'Employee', 'BuildERP' ); ?></span>
							<input type="text" id="berp-quick-employee-input" list="berp-quick-employee-list" autocomplete="off" placeholder="<?php esc_attr_e( 'Search employee...', 'BuildERP' ); ?>" />
							<input type="hidden" name="employee_id" id="berp-quick-employee-id" value="" />
							<datalist id="berp-quick-employee-list">
								<?php foreach ( $employees as $employee ) : ?>
									<option data-id="<?php echo esc_attr( $employee['id'] ); ?>" value="<?php echo esc_attr( $employee['code'] . ' - ' . $employee['name'] ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</label>
						<label class="berp-field grow">
							<span class="berp-label"><?php esc_html_e( 'Notes', 'BuildERP' ); ?></span>
							<textarea name="notes" rows="2" placeholder="<?php esc_attr_e( 'Optional notes...', 'BuildERP' ); ?>"></textarea>
						</label>
					</div>

					<div class="align-end">
						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save & Next', 'BuildERP' ); ?></button>
							<span class="spinner" aria-hidden="true"></span>
							<span class="berp-response-message" aria-live="polite"></span>
						</p>
					</div>
				</form>
			</div>

			<div id="berp-attendance-view" class="berp-tab-panel">
				<div class="berp-card">
					<div class="berp-view-filters">
						<label class="berp-field">
							<span class="berp-label"><?php esc_html_e( 'Employee', 'BuildERP' ); ?></span>
							<select id="berp-view-employee">
								<option value="0"><?php esc_html_e( 'All employees (last 7 days)', 'BuildERP' ); ?></option>
								<?php foreach ( $employees as $employee ) : ?>
									<option value="<?php echo esc_attr( $employee['id'] ); ?>">
										<?php echo esc_html( $employee['code'] . ' - ' . $employee['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="berp-field">
							<span class="berp-label"><?php esc_html_e( 'Month', 'BuildERP' ); ?></span>
							<input type="month" id="berp-view-month" value="<?php echo esc_attr( gmdate( 'Y-m' ) ); ?>" />
						</label>
						<div class="berp-field berp-view-action">
							
							<span class="berp-label"><?php esc_html_e( 'Load Attendance', 'BuildERP' ); ?></span>
							<button type="button" class="button button-primary" id="berp-view-load"><?php esc_html_e( 'Load Attendance', 'BuildERP' ); ?></button>
							<span class="spinner" id="berp-view-spinner"></span>
						</div>
					</div>
					<div id="berp-view-totals" class="berp-totals-strip">
						<div class="berp-total-card berp-total-overtime">
							<span class="berp-total-icon dashicons dashicons-clock"></span>
							<div class="berp-total-content">
								<span class="berp-total-value" id="berp-view-total-overtime">0</span>
								<span class="berp-total-label"><?php esc_html_e( 'Overtime Hours', 'BuildERP' ); ?></span>
							</div>
						</div>
						<div class="berp-total-card berp-total-days">
							<span class="berp-total-icon dashicons dashicons-calendar-alt"></span>
							<div class="berp-total-content">
								<span class="berp-total-value" id="berp-view-total-days">0</span>
								<span class="berp-total-label"><?php esc_html_e( 'Days Worked', 'BuildERP' ); ?></span>
							</div>
						</div>
						<div class="berp-total-card berp-total-holidays">
							<span class="berp-total-icon dashicons dashicons-palmtree"></span>
							<div class="berp-total-content">
								<span class="berp-total-value" id="berp-view-total-holidays">0</span>
								<span class="berp-total-label"><?php esc_html_e( 'Paid Holidays', 'BuildERP' ); ?></span>
							</div>
						</div>
					</div>
				</div>
				<div class="berp-card">
					<table class="widefat fixed striped" id="berp-view-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'BuildERP' ); ?></th>
								<th><?php esc_html_e( 'Employee', 'BuildERP' ); ?></th>
								<th><?php esc_html_e( 'Site', 'BuildERP' ); ?></th>
								<th><?php esc_html_e( 'Overtime', 'BuildERP' ); ?></th>
								<th><?php esc_html_e( 'Notes', 'BuildERP' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'BuildERP' ); ?></th>
							</tr>
						</thead>
						<tbody id="berp-view-body">
							<tr><td colspan="6"><?php esc_html_e( 'Use the filters above to load attendance.', 'BuildERP' ); ?></td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get attendance settings with defaults.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_attendance_settings() {
		$settings = get_option(
			'berp_settings',
			array(
				'attendance' => array(),
			)
		);

		$defaults = array(
			'editable_days_limit'    => 7,
			'block_future'           => 1,
			'require_site'           => 0,
			'default_overtime_hours' => 0,
		);

		$attendance = isset( $settings['attendance'] ) && is_array( $settings['attendance'] ) ? $settings['attendance'] : array();

		return wp_parse_args( $attendance, $defaults );
	}

	/**
	 * Render admin notices for attendance actions.
	 *
	 * @since 1.0.0
	 */
	protected function render_notices() {
		if ( ! isset( $_GET['berp_attendance_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$code    = sanitize_text_field( wp_unslash( $_GET['berp_attendance_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$message = '';
		$class   = 'notice notice-info';

		if ( 'import_success' === $code ) {
			$created = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$message = sprintf(
				/* translators: 1: created count, 2: skipped count */
				esc_html__( 'Attendance import completed: %1$d created, %2$d skipped.', 'BuildERP' ),
				$created,
				$skipped
			);
			$class = 'notice notice-success';
		} elseif ( 'import_error' === $code ) {
			$message = esc_html__( 'Attendance import failed. Please check the file and try again.', 'BuildERP' );
			$class   = 'notice notice-error';
		} elseif ( 'export_error' === $code ) {
			$message = esc_html__( 'Attendance export failed.', 'BuildERP' );
			$class   = 'notice notice-error';
		}

		if ( $message ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	/**
	 * Handle CSV export.
	 *
	 * @since 1.0.0
	 */
	public function handle_export() {
		if ( ! current_user_can( 'berp_view_attendance' ) ) {
			wp_die( esc_html__( 'You do not have permission to export attendance.', 'BuildERP' ) );
		}

		check_admin_referer( 'berp_attendance_export', 'berp_attendance_export_nonce' );

		$query = new WP_Query(
			array(
				'post_type'      => 'berp_attendance',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$filename = 'attendance-' . gmdate( 'Ymd-His' ) . '.csv';
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv(
			$output,
			array(
				'employee_code',
				'date',
				'site_id',
				'overtime_hours',
				'notes',
			)
		);

		foreach ( $query->posts as $post_id ) {
			$employee_id   = get_post_meta( $post_id, '_berp_employee_id', true );
			$employee_code = $employee_id ? get_post_meta( (int) $employee_id, '_berp_employee_id', true ) : '';
			$date          = get_post_meta( $post_id, '_berp_date', true );
			$site_id       = get_post_meta( $post_id, '_berp_site_id', true );
			$overtime      = get_post_meta( $post_id, '_berp_overtime_hours', true );
			$notes         = get_post_meta( $post_id, '_berp_notes', true );

			fputcsv(
				$output,
				array(
					$employee_code,
					$date,
					$site_id,
					$overtime,
					$notes,
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Handle PDF export (simple table layout).
	 *
	 * @since 1.0.0
	 */
	public function handle_export_pdf() {
		if ( ! current_user_can( 'berp_view_attendance' ) ) {
			wp_die( esc_html__( 'You do not have permission to export attendance.', 'BuildERP' ) );
		}

		check_admin_referer( 'berp_attendance_export', 'berp_attendance_export_nonce' );

		$query = new WP_Query(
			array(
				'post_type'      => 'berp_attendance',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$html  = '<h1 style="font-family: sans-serif;">Attendance Export</h1>';
		$html .= '<table cellspacing="0" cellpadding="6" border="1" width="100%" style="font-family: sans-serif; border-collapse: collapse;">';
		$html .= '<thead><tr><th>Employee</th><th>Date</th><th>Site</th><th>Overtime (hrs)</th><th>Notes</th></tr></thead><tbody>';

		foreach ( $query->posts as $post_id ) {
			$employee_id   = get_post_meta( $post_id, '_berp_employee_id', true );
			$employee_code = $employee_id ? get_post_meta( (int) $employee_id, '_berp_employee_id', true ) : '';
			$date          = get_post_meta( $post_id, '_berp_date', true );
			$site_id       = get_post_meta( $post_id, '_berp_site_id', true );
			$overtime      = get_post_meta( $post_id, '_berp_overtime_hours', true );
			$notes         = get_post_meta( $post_id, '_berp_notes', true );

			$html .= sprintf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td></tr>',
				esc_html( $employee_code ),
				esc_html( $date ),
				esc_html( $site_id ),
				esc_html( $overtime ),
				esc_html( $notes )
			);
		}

		$html .= '</tbody></table>';

		$filename = 'attendance-' . gmdate( 'Ymd-His' ) . '.pdf';

		// If a PDF generator is available (TCPDF/mPDF), use it; otherwise, force browser print-to-PDF fallback.
		if ( class_exists( 'TCPDF' ) ) {
			$pdf = new TCPDF();
			$pdf->SetCreator( 'BuildERP' );
			$pdf->SetAuthor( 'BuildERP' );
			$pdf->SetTitle( 'Attendance Export' );
			$pdf->AddPage();
			$pdf->writeHTML( $html, true, false, true, false, '' );
			$pdf->Output( $filename, 'D' );
			exit;
		} elseif ( class_exists( 'Mpdf\\Mpdf' ) ) {
			$mpdf = new \Mpdf\Mpdf();
			$mpdf->WriteHTML( $html );
			$mpdf->Output( $filename, 'D' );
			exit;
		}

		// Fallback: serve HTML as PDF download header (browser can save as PDF via print dialog).
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle CSV import.
	 *
	 * @since 1.0.0
	 */
	public function handle_import() {
		if ( ! current_user_can( 'berp_log_attendance' ) ) {
			wp_die( esc_html__( 'You do not have permission to import attendance.', 'BuildERP' ) );
		}

		check_admin_referer( 'berp_attendance_import', 'berp_attendance_import_nonce' );

		if ( empty( $_FILES['berp_attendance_csv']['tmp_name'] ) ) {
			$this->redirect_import( 'import_error' );
		}

		$file = $_FILES['berp_attendance_csv'];
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			$this->redirect_import( 'import_error' );
		}

		// Validate file type - only allow CSV.
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			$this->redirect_import( 'import_error' );
		}

		// Validate MIME type.
		$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : null;
		if ( $finfo ) {
			$mime = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
			$allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				$this->redirect_import( 'import_error' );
			}
		}

		$handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$this->redirect_import( 'import_error' );
		}

		// Skip header.
		fgetcsv( $handle );

		$created = 0;
		$skipped = 0;

		for ( $row = fgetcsv( $handle ); false !== $row; $row = fgetcsv( $handle ) ) {
			if ( count( $row ) < 4 ) {
				++$skipped;
				continue;
			}

			list( $employee_code, $date, $site_id, $overtime, $notes ) = array_pad( $row, 5, '' );

			$date = sanitize_text_field( $date );
			if ( ! $this->is_valid_date( $date ) ) {
				++$skipped;
				continue;
			}

			$employee_id = $this->find_employee_id_from_code( sanitize_text_field( $employee_code ) );
			if ( ! $employee_id ) {
				++$skipped;
				continue;
			}

			$existing = $this->find_attendance( $employee_id, $date );
			if ( $existing ) {
				++$skipped;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'berp_attendance',
					'post_status' => 'publish',
					/* translators: 1: employee code, 2: date. */
					'post_title'  => sprintf( __( 'Attendance - %1$s - %2$s', 'BuildERP' ), $employee_code, $date ),
				)
			);

			if ( is_wp_error( $post_id ) ) {
				++$skipped;
				continue;
			}

			$site_id  = absint( $site_id );
			$overtime = floatval( $overtime );
			$notes    = sanitize_textarea_field( $notes );

			$this->update_attendance_meta( $post_id, $employee_id, $date, $site_id, $overtime, $notes, get_current_user_id() );

			++$created;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->redirect_import(
			'import_success',
			array(
				'created' => $created,
				'skipped' => $skipped,
			)
		);
	}

	/**
	 * Redirect back to attendance page with notice.
	 *
	 * @since 1.0.0
	 * @param string $code   Notice code.
	 * @param array  $params Extra params.
	 */
	protected function redirect_import( $code, $params = array() ) {
		$args = array_merge(
			array( 'berp_attendance_notice' => $code ),
			$params
		);

		wp_safe_redirect(
			add_query_arg(
				$args,
				admin_url( 'admin.php?page=builderp-attendance' )
			)
		);
		exit;
	}

	/**
	 * Validate date format.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return bool
	 */
	protected function is_valid_date( $date ) {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}

	/**
	 * Find employee ID by code or post ID.
	 *
	 * @since 1.0.0
	 * @param string $code Employee code or numeric post ID.
	 * @return int|null
	 */
	protected function find_employee_id_from_code( $code ) {
		if ( empty( $code ) ) {
			return null;
		}

		if ( is_numeric( $code ) ) {
			$post_id = absint( $code );
			$post    = get_post( $post_id );
			if ( $post && 'berp_employee' === $post->post_type ) {
				return $post_id;
			}
		}

		global $wpdb;
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				'_berp_employee_id',
				$code
			)
		);

		return $post_id ? (int) $post_id : null;
	}

	/**
	 * Find attendance for employee/date.
	 *
	 * @since 1.0.0
	 * @param int    $employee_id Employee ID.
	 * @param string $date        Date.
	 * @return int|null
	 */
	protected function find_attendance( $employee_id, $date ) {
		global $wpdb;

		$attendance_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_berp_employee_id'
				INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_berp_date'
				WHERE p.post_type = 'berp_attendance'
				AND p.post_status = 'publish'
				AND pm1.meta_value = %d
				AND pm2.meta_value = %s
				LIMIT 1",
				$employee_id,
				$date
			)
		);

		return $attendance_id ? intval( $attendance_id ) : null;
	}

	/**
	 * Update attendance meta (mirrors REST behaviour).
	 *
	 * @since 1.0.0
	 * @param int         $attendance_id Attendance post ID.
	 * @param int|null    $employee_id   Employee ID.
	 * @param string|null $date          Date.
	 * @param int         $site_id       Site ID.
	 * @param float       $overtime      Overtime hours.
	 * @param string      $notes         Notes.
	 * @param int         $user_id       User ID.
	 */
	protected function update_attendance_meta( $attendance_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id ) {
		if ( $employee_id ) {
			update_post_meta( $attendance_id, '_berp_employee_id', absint( $employee_id ) );
		}
		if ( $date ) {
			update_post_meta( $attendance_id, '_berp_date', sanitize_text_field( $date ) );
		}
		update_post_meta( $attendance_id, '_berp_site_id', absint( $site_id ) );
		update_post_meta( $attendance_id, '_berp_overtime_hours', floatval( $overtime ) );
		update_post_meta( $attendance_id, '_berp_notes', $notes );
		update_post_meta( $attendance_id, '_berp_logged_by', absint( $user_id ) );
		update_post_meta( $attendance_id, '_berp_logged_at', current_datetime()->getTimestamp() );

		$flags = $this->get_day_flags( $employee_id ? $employee_id : (int) get_post_meta( $attendance_id, '_berp_employee_id', true ), $date );
		update_post_meta( $attendance_id, '_berp_is_weekend', $flags['is_weekend'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_is_holiday', $flags['is_holiday'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_weekend_payable', $flags['weekend_payable'] ? 1 : 0 );
	}

	/**
	 * Get payroll settings for weekend/holiday logic.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_payroll_settings() {
		$settings = get_option( 'berp_settings', array() );
		$defaults = array(
			'payroll' => array(
				'weekend_days'         => array( 'saturday', 'sunday' ),
				'weekend_payment_rule' => 0,
				'holidays'             => '',
			),
		);

		$settings = wp_parse_args( $settings, $defaults );
		return isset( $settings['payroll'] ) && is_array( $settings['payroll'] ) ? wp_parse_args( $settings['payroll'], $defaults['payroll'] ) : $defaults['payroll'];
	}

	/**
	 * Get day flags (weekend/holiday).
	 *
	 * @since 1.0.0
	 * @param int    $employee_id Employee ID.
	 * @param string $date        Date.
	 * @return array
	 */
	protected function get_day_flags( $employee_id, $date ) {
		$payroll         = $this->get_payroll_settings();
		$is_weekend      = $this->is_weekend( $date, $payroll );
		$is_holiday      = $this->is_holiday( $date, $payroll );
		$weekend_payable = true;

		if ( $is_weekend ) {
			$weekend_payable = false;
			if ( ! empty( $payroll['weekend_payment_rule'] ) ) {
				$prev            = gmdate( 'Y-m-d', strtotime( $date . ' -1 day' ) );
				$next            = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) );
				$prev_attendance = $this->find_attendance( $employee_id, $prev );
				$next_attendance = $this->find_attendance( $employee_id, $next );
				if ( $prev_attendance || $next_attendance ) {
					$weekend_payable = true;
				}
			}
		}

		return array(
			'is_weekend'      => $is_weekend,
			'is_holiday'      => $is_holiday,
			'weekend_payable' => $weekend_payable,
		);
	}

	/**
	 * Check weekend.
	 *
	 * @since 1.0.0
	 * @param string $date    Date.
	 * @param array  $payroll Payroll settings.
	 * @return bool
	 */
	protected function is_weekend( $date, $payroll ) {
		$weekday      = strtolower( gmdate( 'l', strtotime( $date ) ) );
		$weekend_days = isset( $payroll['weekend_days'] ) && is_array( $payroll['weekend_days'] ) ? array_map( 'strtolower', $payroll['weekend_days'] ) : array();
		return in_array( $weekday, $weekend_days, true );
	}

	/**
	 * Check holiday.
	 *
	 * @since 1.0.0
	 * @param string $date    Date.
	 * @param array  $payroll Payroll settings.
	 * @return bool
	 */
	protected function is_holiday( $date, $payroll ) {
		if ( empty( $payroll['holidays'] ) ) {
			return false;
		}

		$holidays = array_filter(
			array_map(
				'trim',
				explode( ',', $payroll['holidays'] )
			)
		);

		return in_array( $date, $holidays, true );
	}

	/**
	 * Get active employees.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_active_employees() {
		$args = array(
			'post_type'      => 'berp_employee',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_berp_employee_status',
					'value'   => 'active',
					'compare' => '=',
				),
				array(
					'key'     => '_berp_employee_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_berp_employee_status',
					'value'   => '',
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );

		$employees = array();

		foreach ( $query->posts as $employee ) {
			$code        = get_post_meta( $employee->ID, '_berp_employee_id', true );
			$employees[] = array(
				'id'   => (int) $employee->ID,
				'name' => $employee->post_title,
				'code' => $code ? $code : '#' . $employee->ID,
			);
		}

		// Fallback: if no active found (legacy data), load all published employees.
		if ( empty( $employees ) ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'berp_employee',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			foreach ( $query->posts as $employee ) {
				$code        = get_post_meta( $employee->ID, '_berp_employee_id', true );
				$employees[] = array(
					'id'   => (int) $employee->ID,
					'name' => $employee->post_title,
					'code' => $code ? $code : '#' . $employee->ID,
				);
			}
		}

		return $employees;
	}

	/**
	 * Get active sites.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_active_sites() {
		$args  = array(
			'post_type'      => 'berp_site',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		$query = new WP_Query( $args );

		$sites = array();
		foreach ( $query->posts as $site ) {
			$sites[] = array(
				'id'   => (int) $site->ID,
				'name' => $site->post_title,
			);
		}

		return $sites;
	}
}


