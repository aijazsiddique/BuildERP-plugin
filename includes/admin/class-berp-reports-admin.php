<?php
/**
 * Reports Admin Class
 *
 * Handles the reports page and report generation.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Reports_Admin Class
 */
class BERP_Reports_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hooks will be added here if needed.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_exports' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'builderp-reports' ) === false ) {
			return;
		}

		// Enqueue Chart.js from CDN or local if available.
		// For now using CDN for simplicity, but in production should be local.
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );

		wp_enqueue_style(
			'berp-reports-css',
			plugins_url( 'assets/css/reports.css', BERP_PLUGIN_FILE ),
			array(),
			BERP_VERSION
		);

		wp_enqueue_script(
			'berp-reports-js',
			plugins_url( 'assets/js/reports.js', BERP_PLUGIN_FILE ),
			array( 'jquery', 'chart-js', 'jquery-ui-datepicker' ),
			BERP_VERSION,
			true
		);

		wp_localize_script(
			'berp-reports-js',
			'berpReports',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'berp_reports_nonce' ),
				'strings' => array(
					'loading' => __( 'Loading report data...', 'aic_builderp' ),
					'error'   => __( 'Error loading report.', 'aic_builderp' ),
				),
			)
		);
	}

	/**
	 * Render the reports page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'berp_view_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'aic_builderp' ) );
		}

		$current_report = isset( $_GET['report'] ) ? sanitize_key( $_GET['report'] ) : 'attendance_summary';
		$reports        = $this->get_available_reports();

		?>
		<div class="wrap berp-reports-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Reports & Analytics', 'aic_builderp' ); ?></h1>
			<hr class="wp-header-end">

			<div class="berp-reports-container">
				<div class="berp-reports-sidebar">
					<ul class="berp-reports-nav">
						<?php foreach ( $reports as $key => $report ) : ?>
							<li class="<?php echo $current_report === $key ? 'active' : ''; ?>">
								<a href="<?php echo esc_url( add_query_arg( 'report', $key ) ); ?>">
									<span class="dashicons <?php echo esc_attr( $report['icon'] ); ?>"></span>
									<?php echo esc_html( $report['title'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="berp-reports-content">
					<div class="berp-report-header">
						<h2><?php echo esc_html( $reports[ $current_report ]['title'] ); ?></h2>
						<div class="berp-report-actions">
							<button type="button" class="button button-secondary" id="berp-print-report">
								<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print', 'aic_builderp' ); ?>
							</button>
							<button type="button" class="button button-secondary" id="berp-export-excel">
								<span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'Excel', 'aic_builderp' ); ?>
							</button>
							<button type="button" class="button button-secondary" id="berp-export-pdf">
								<span class="dashicons dashicons-pdf"></span> <?php esc_html_e( 'PDF', 'aic_builderp' ); ?>
							</button>
						</div>
					</div>

					<div class="berp-report-filters">
						<form id="berp-report-filter-form" method="get">
							<input type="hidden" name="page" value="builderp-reports">
							<input type="hidden" name="report" value="<?php echo esc_attr( $current_report ); ?>">
							
							<?php $this->render_filters( $current_report ); ?>
							
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Report', 'aic_builderp' ); ?></button>
						</form>
					</div>

					<div class="berp-report-body" id="berp-report-body">
						<?php $this->render_report( $current_report ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get list of available reports.
	 *
	 * @return array
	 */
	private function get_available_reports() {
		return array(
			'attendance_summary' => array(
				'title' => __( 'Employee Attendance Summary', 'aic_builderp' ),
				'icon'  => 'dashicons-calendar-alt',
			),
			'payroll_register'   => array(
				'title' => __( 'Payroll Register', 'aic_builderp' ),
				'icon'  => 'dashicons-money-alt',
			),
			'site_profitability' => array(
				'title' => __( 'Site Profitability', 'aic_builderp' ),
				'icon'  => 'dashicons-chart-bar',
			),
			'expense_breakdown'  => array(
				'title' => __( 'Expense Breakdown', 'aic_builderp' ),
				'icon'  => 'dashicons-chart-pie',
			),
			'invoice_aging'      => array(
				'title' => __( 'Invoice Aging', 'aic_builderp' ),
				'icon'  => 'dashicons-clock',
			),
			'client_payments'    => array(
				'title' => __( 'Client Payment History', 'aic_builderp' ),
				'icon'  => 'dashicons-businessman',
			),
			'employee_balances'  => array(
				'title' => __( 'Employee Account Balances', 'aic_builderp' ),
				'icon'  => 'dashicons-id-alt',
			),
			'budget_vs_actual'   => array(
				'title' => __( 'Budget vs Actual', 'aic_builderp' ),
				'icon'  => 'dashicons-performance',
			),
			'overtime_analysis'  => array(
				'title' => __( 'Overtime Analysis', 'aic_builderp' ),
				'icon'  => 'dashicons-clock',
			),
			'revenue_trends'     => array(
				'title' => __( 'Revenue Trends', 'aic_builderp' ),
				'icon'  => 'dashicons-graph-bar',
			),
		);
	}

	/**
	 * Render filters based on report type.
	 *
	 * @param string $report_type Report type key.
	 */
	private function render_filters( $report_type ) {
		// Common Date Range Filter
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );

		echo '<div class="berp-filter-group">';
		echo '<label>' . esc_html__( 'Date Range:', 'aic_builderp' ) . '</label>';
		echo '<input type="date" name="start_date" value="' . esc_attr( $start_date ) . '" class="regular-text" style="width: 150px;">';
		echo ' <span>' . esc_html__( 'to', 'aic_builderp' ) . '</span> ';
		echo '<input type="date" name="end_date" value="' . esc_attr( $end_date ) . '" class="regular-text" style="width: 150px;">';
		echo '</div>';

		// Specific filters based on report type
		switch ( $report_type ) {
			case 'attendance_summary':
			case 'overtime_analysis':
				$this->render_site_filter();
				$this->render_employee_filter();
				break;

			case 'payroll_register':
			case 'employee_balances':
				$this->render_employee_filter();
				break;

			case 'site_profitability':
			case 'budget_vs_actual':
				$this->render_site_filter();
				break;

			case 'expense_breakdown':
				$this->render_site_filter();
				// Add category filter here if needed
				break;

			case 'invoice_aging':
			case 'client_payments':
			case 'revenue_trends':
				$this->render_client_filter();
				break;
		}
	}

	/**
	 * Render Site Filter.
	 */
	private function render_site_filter() {
		$sites    = berp_get_sites();
		$selected = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

		echo '<div class="berp-filter-group">';
		echo '<label>' . esc_html__( 'Site:', 'aic_builderp' ) . '</label>';
		echo '<select name="site_id">';
		echo '<option value="0">' . esc_html__( 'All Sites', 'aic_builderp' ) . '</option>';
		foreach ( $sites as $site ) {
			echo '<option value="' . esc_attr( $site->ID ) . '" ' . selected( $selected, $site->ID, false ) . '>' . esc_html( $site->post_title ) . '</option>';
		}
		echo '</select>';
		echo '</div>';
	}

	/**
	 * Render Employee Filter.
	 */
	private function render_employee_filter() {
		$employees = berp_get_employees();
		$selected  = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

		echo '<div class="berp-filter-group">';
		echo '<label>' . esc_html__( 'Employee:', 'aic_builderp' ) . '</label>';
		echo '<select name="employee_id">';
		echo '<option value="0">' . esc_html__( 'All Employees', 'aic_builderp' ) . '</option>';
		foreach ( $employees as $employee ) {
			echo '<option value="' . esc_attr( $employee->ID ) . '" ' . selected( $selected, $employee->ID, false ) . '>' . esc_html( $employee->post_title ) . '</option>';
		}
		echo '</select>';
		echo '</div>';
	}

	/**
	 * Render Client Filter.
	 */
	private function render_client_filter() {
		$clients  = berp_get_clients();
		$selected = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

		echo '<div class="berp-filter-group">';
		echo '<label>' . esc_html__( 'Client:', 'aic_builderp' ) . '</label>';
		echo '<select name="client_id">';
		echo '<option value="0">' . esc_html__( 'All Clients', 'aic_builderp' ) . '</option>';
		foreach ( $clients as $client ) {
			echo '<option value="' . esc_attr( $client->ID ) . '" ' . selected( $selected, $client->ID, false ) . '>' . esc_html( $client->post_title ) . '</option>';
		}
		echo '</select>';
		echo '</div>';
	}

	/**
	 * Render the selected report.
	 *
	 * @param string $report_type Report type key.
	 */
	private function render_report( $report_type ) {
		$method = 'render_report_' . $report_type;
		if ( method_exists( $this, $method ) ) {
			$this->$method();
		} else {
			echo '<p>' . esc_html__( 'Report not implemented yet.', 'aic_builderp' ) . '</p>';
		}
	}

	// Placeholder methods for reports - to be implemented in subsequent steps

	/**
	 * Render Attendance Summary Report.
	 */
	private function render_report_attendance_summary() {
		$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );
		$site_id     = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;
		$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

		$data = $this->get_attendance_summary_data( $start_date, $end_date, $site_id, $employee_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Present Days', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( $data['totals']['present_days'] ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Overtime Hours', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['overtime_hours'], 2 ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Avg. Attendance Rate', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['attendance_rate'], 1 ) ) . '%</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-attendance-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Employee Name', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Employee ID', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Present Days', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Overtime Hours', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Attendance Rate', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No attendance records found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td>' . esc_html( $row['employee_code'] ) . '</td>';
				echo '<td class="num">' . esc_html( $row['present_days'] ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['overtime_hours'], 2 ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['attendance_rate'], 1 ) ) . '%</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td colspan="2">' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( $data['totals']['present_days'] ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['overtime_hours'], 2 ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['attendance_rate'], 1 ) ) . '%</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'attendance',
				'labels'   => wp_list_pluck( $data['rows'], 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Present Days', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'present_days' ),
						'backgroundColor' => '#2271b1',
					),
					array(
						'label'           => __( 'Overtime Hours', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'overtime_hours' ),
						'backgroundColor' => '#f0ad4e',
					),
				),
			)
		);
	}

	/**
	 * Get data for Attendance Summary Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $site_id    Site ID.
	 * @param int    $employee_id Employee ID.
	 * @return array Report data.
	 */
	private function get_attendance_summary_data( $start_date, $end_date, $site_id = 0, $employee_id = 0 ) {
		global $wpdb;

		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_berp_date',
				'value'   => array( $start_date, $end_date ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		);

		if ( $site_id ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => $site_id,
				'compare' => '=',
			);
		}

		if ( $employee_id ) {
			$meta_query[] = array(
				'key'     => '_berp_employee_id',
				'value'   => $employee_id,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'berp_attendance',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'fields'         => 'ids', // We only need IDs to fetch meta
		);

		$attendance_ids = get_posts( $args );

		// Calculate total working days in period (excluding weekends if configured)
		// For simplicity, just using calendar days for now, but ideally should check settings
		$start      = new DateTime( $start_date );
		$end        = new DateTime( $end_date );
		$interval   = $start->diff( $end );
		$total_days = $interval->days + 1;

		$employee_stats = array();

		foreach ( $attendance_ids as $post_id ) {
			$emp_id = get_post_meta( $post_id, '_berp_employee_id', true );
			$ot     = get_post_meta( $post_id, '_berp_overtime_hours', true );

			if ( ! isset( $employee_stats[ $emp_id ] ) ) {
				$employee_stats[ $emp_id ] = array(
					'present_days'   => 0,
					'overtime_hours' => 0,
				);
			}

			++$employee_stats[ $emp_id ]['present_days'];
			$employee_stats[ $emp_id ]['overtime_hours'] += floatval( $ot );
		}

		// Format rows
		$rows   = array();
		$totals = array(
			'present_days'    => 0,
			'overtime_hours'  => 0,
			'attendance_rate' => 0,
		);

		foreach ( $employee_stats as $emp_id => $stats ) {
			$employee = get_post( $emp_id );
			if ( ! $employee ) {
				continue;
			}

			$rate = ( $stats['present_days'] / $total_days ) * 100;

			$rows[] = array(
				'name'            => $employee->post_title,
				'employee_code'   => get_post_meta( $emp_id, '_berp_employee_id', true ),
				'present_days'    => $stats['present_days'],
				'overtime_hours'  => $stats['overtime_hours'],
				'attendance_rate' => $rate,
			);

			$totals['present_days']   += $stats['present_days'];
			$totals['overtime_hours'] += $stats['overtime_hours'];
		}

		// Calculate average attendance rate
		if ( count( $rows ) > 0 ) {
			$totals['attendance_rate'] = ( $totals['present_days'] / ( count( $rows ) * $total_days ) ) * 100;
		}

		// Sort by name
		usort(
			$rows,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Render Payroll Register Report.
	 */
	private function render_report_payroll_register() {
		$month       = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : date( 'Y-m' );
		$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

		$data = $this->get_payroll_register_data( $month, $employee_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Payroll Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['gross_salary'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Net Payable', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['net_salary'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Deductions', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['total_deduction'] ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-payroll-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Employee', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Basic Salary', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Allowances', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Overtime', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Gross Salary', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Deductions', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Net Salary', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="8">' . esc_html__( 'No payroll records found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['basic_salary'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['total_allowance'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['overtime_amount'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['gross_salary'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['total_deduction'] ) ) . '</td>';
				echo '<td class="num"><strong>' . esc_html( berp_format_currency( $row['net_salary'] ) ) . '</strong></td>';
				echo '<td><span class="berp-status-badge status-' . esc_attr( $row['status'] ) . '">' . esc_html( ucfirst( $row['status'] ) ) . '</span></td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['basic_salary'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['total_allowance'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['overtime_amount'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['gross_salary'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['total_deduction'] ) ) . '</td>';
		echo '<td class="num"><strong>' . esc_html( berp_format_currency( $data['totals']['net_salary'] ) ) . '</strong></td>';
		echo '<td></td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'payroll',
				'labels'   => wp_list_pluck( $data['rows'], 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Net Salary', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'net_salary' ),
						'backgroundColor' => '#2271b1',
					),
					array(
						'label'           => __( 'Deductions', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'total_deduction' ),
						'backgroundColor' => '#d63638',
					),
				),
			)
		);
	}

	/**
	 * Get data for Payroll Register Report.
	 *
	 * @param string $month       Month (YYYY-MM).
	 * @param int    $employee_id Employee ID.
	 * @return array Report data.
	 */
	private function get_payroll_register_data( $month, $employee_id = 0 ) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_berp_month',
				'value'   => $month,
				'compare' => '=',
			),
		);

		if ( $employee_id ) {
			$meta_query[] = array(
				'key'     => '_berp_employee_id',
				'value'   => $employee_id,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'berp_payroll',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
		);

		$payrolls = get_posts( $args );

		$rows   = array();
		$totals = array(
			'basic_salary'    => 0,
			'total_allowance' => 0,
			'overtime_amount' => 0,
			'gross_salary'    => 0,
			'total_deduction' => 0,
			'net_salary'      => 0,
		);

		foreach ( $payrolls as $payroll ) {
			$emp_id   = get_post_meta( $payroll->ID, '_berp_employee_id', true );
			$employee = get_post( $emp_id );

			if ( ! $employee ) {
				continue;
			}

			$basic_salary    = floatval( get_post_meta( $payroll->ID, '_berp_basic_salary', true ) );
			$total_allowance = floatval( get_post_meta( $payroll->ID, '_berp_total_allowances', true ) );
			$overtime_amount = floatval( get_post_meta( $payroll->ID, '_berp_overtime_amount', true ) );
			$gross_salary    = floatval( get_post_meta( $payroll->ID, '_berp_gross_salary', true ) );
			$total_deduction = floatval( get_post_meta( $payroll->ID, '_berp_total_deductions', true ) );
			$net_salary      = floatval( get_post_meta( $payroll->ID, '_berp_net_salary', true ) );
			$status          = get_post_meta( $payroll->ID, '_berp_payment_status', true );

			$rows[] = array(
				'name'            => $employee->post_title,
				'basic_salary'    => $basic_salary,
				'total_allowance' => $total_allowance,
				'overtime_amount' => $overtime_amount,
				'gross_salary'    => $gross_salary,
				'total_deduction' => $total_deduction,
				'net_salary'      => $net_salary,
				'status'          => $status ? $status : 'pending',
			);

			$totals['basic_salary']    += $basic_salary;
			$totals['total_allowance'] += $total_allowance;
			$totals['overtime_amount'] += $overtime_amount;
			$totals['gross_salary']    += $gross_salary;
			$totals['total_deduction'] += $total_deduction;
			$totals['net_salary']      += $net_salary;
		}

		// Sort by name
		usort(
			$rows,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Render Site Profitability Report.
	 */
	private function render_report_site_profitability() {
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
		$site_id    = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

		$data = $this->get_site_profitability_data( $start_date, $end_date, $site_id );

		// Summary Cards - Row 1
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Income', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['income'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Expenses', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['expense'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Net Profit', 'aic_builderp' ) . '</h3>';
		$profit_class = $data['totals']['profit'] >= 0 ? 'positive' : 'negative';
		echo '<div class="value ' . esc_attr( $profit_class ) . '">' . esc_html( berp_format_currency( $data['totals']['profit'] ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Summary Cards - Row 2: Manpower Statistics
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Manpower Days', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['manpower_days'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Overtime Hours', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['overtime_hours'], 1 ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Est. Labor Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['labor_cost'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Profit Margin', 'aic_builderp' ) . '</h3>';
		$margin_class = $data['totals']['margin'] >= 0 ? 'positive' : 'negative';
		echo '<div class="value ' . esc_attr( $margin_class ) . '">' . esc_html( number_format( $data['totals']['margin'], 1 ) ) . '%</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-profitability-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Site Name', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Income', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Expenses', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Manpower Days', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'OT Hours', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Labor Cost', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Profit/Loss', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Margin', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="8">' . esc_html__( 'No data found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				$row_profit_class = $row['profit'] >= 0 ? 'positive' : 'negative';
				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['income'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['expense'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['manpower_days'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['overtime_hours'], 1 ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['labor_cost'] ) ) . '</td>';
				echo '<td class="num ' . esc_attr( $row_profit_class ) . '">' . esc_html( berp_format_currency( $row['profit'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['margin'], 1 ) ) . '%</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['income'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['expense'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['manpower_days'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['overtime_hours'], 1 ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['labor_cost'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['profit'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['margin'], 1 ) ) . '%</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'profitability',
				'labels'   => wp_list_pluck( $data['rows'], 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Income', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'income' ),
						'backgroundColor' => '#2271b1',
					),
					array(
						'label'           => __( 'Expenses', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'expense' ),
						'backgroundColor' => '#d63638',
					),
					array(
						'label'           => __( 'Labor Cost', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'labor_cost' ),
						'backgroundColor' => '#f0ad4e',
					),
				),
			)
		);
	}

	/**
	 * Get data for Site Profitability Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $site_id    Site ID.
	 * @return array Report data.
	 */
	private function get_site_profitability_data( $start_date, $end_date, $site_id = 0 ) {
		// Get all sites or specific site
		$args = array(
			'post_type'      => 'berp_site',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( $site_id ) {
			$args['include'] = array( $site_id );
		}

		$site_ids = get_posts( $args );
		$rows     = array();
		$totals   = array(
			'income'         => 0,
			'expense'        => 0,
			'profit'         => 0,
			'margin'         => 0,
			'manpower_days'  => 0,
			'overtime_hours' => 0,
			'labor_cost'     => 0,
		);

		foreach ( $site_ids as $s_id ) {
			$site_name = get_the_title( $s_id );

			// Calculate Income (Invoices)
			$income = $this->get_site_income( $s_id, $start_date, $end_date );

			// Calculate Expenses
			$expense = $this->get_site_expenses( $s_id, $start_date, $end_date );

			// Calculate Manpower (Attendance & Labor Cost)
			$manpower = $this->get_site_manpower( $s_id, $start_date, $end_date );

			$profit = $income - $expense;
			$margin = $income > 0 ? ( $profit / $income ) * 100 : 0;

			$rows[] = array(
				'name'           => $site_name,
				'income'         => $income,
				'expense'        => $expense,
				'manpower_days'  => $manpower['days'],
				'overtime_hours' => $manpower['overtime_hours'],
				'labor_cost'     => $manpower['labor_cost'],
				'profit'         => $profit,
				'margin'         => $margin,
			);

			$totals['income']         += $income;
			$totals['expense']        += $expense;
			$totals['manpower_days']  += $manpower['days'];
			$totals['overtime_hours'] += $manpower['overtime_hours'];
			$totals['labor_cost']     += $manpower['labor_cost'];
		}

		$totals['profit'] = $totals['income'] - $totals['expense'];
		$totals['margin'] = $totals['income'] > 0 ? ( $totals['profit'] / $totals['income'] ) * 100 : 0;

		// Sort by profit descending
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['profit'] - $a['profit'];
			}
		);

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Helper to get site income from invoices.
	 */
	private function get_site_income( $site_id, $start_date, $end_date ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_site_id',
					'value'   => $site_id,
					'compare' => '=',
				),
				array(
					'key'     => '_berp_invoice_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_berp_status',
					'value'   => 'paid',
					'compare' => '=',
				),
			),
			'fields'         => 'ids',
		);

		$invoice_ids = get_posts( $args );
		$total       = 0;

		foreach ( $invoice_ids as $id ) {
			$total += floatval( get_post_meta( $id, '_berp_grand_total', true ) );
		}

		return $total;
	}

	/**
	 * Helper to get site expenses.
	 */
	private function get_site_expenses( $site_id, $start_date, $end_date ) {
		$args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_site_id',
					'value'   => $site_id,
					'compare' => '=',
				),
				array(
					'key'     => '_berp_expense_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
			'fields'         => 'ids',
		);

		$expense_ids = get_posts( $args );
		$total       = 0;

		foreach ( $expense_ids as $id ) {
			$total += floatval( get_post_meta( $id, '_berp_expense_amount', true ) );
		}

		return $total;
	}

	/**
	 * Helper to get site manpower stats (attendance, overtime, labor cost).
	 *
	 * @param int    $site_id    Site ID.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array Manpower data with days, overtime_hours, and labor_cost.
	 */
	private function get_site_manpower( $site_id, $start_date, $end_date ) {
		// Get attendance records for this site in date range
		$args = array(
			'post_type'      => 'berp_attendance',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_site_id',
					'value'   => $site_id,
					'compare' => '=',
				),
				array(
					'key'     => '_berp_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$attendance_records = get_posts( $args );

		$total_days          = count( $attendance_records );
		$total_overtime      = 0;
		$total_labor_cost    = 0;
		$employee_daily_cost = array(); // Cache employee costs

		// Get payroll settings for working days
		$settings         = get_option( 'berp_settings', array() );
		$payroll_settings = isset( $settings['payroll'] ) ? $settings['payroll'] : array();
		$working_days     = isset( $payroll_settings['working_days'] ) ? intval( $payroll_settings['working_days'] ) : 26;

		// Get attendance settings for overtime multiplier
		$attendance_settings = isset( $settings['attendance'] ) ? $settings['attendance'] : array();
		$overtime_multiplier = isset( $attendance_settings['default_multiplier'] ) ? floatval( $attendance_settings['default_multiplier'] ) : 1.5;

		foreach ( $attendance_records as $record ) {
			$employee_id    = get_post_meta( $record->ID, '_berp_employee_id', true );
			$overtime_hours = floatval( get_post_meta( $record->ID, '_berp_overtime_hours', true ) );

			$total_overtime += $overtime_hours;

			// Calculate daily cost for this employee (cache it)
			if ( ! isset( $employee_daily_cost[ $employee_id ] ) ) {
				$employee_daily_cost[ $employee_id ] = $this->calculate_employee_daily_cost( $employee_id, $working_days );
			}

			$daily_cost = $employee_daily_cost[ $employee_id ];

			// Labor cost = daily cost + overtime cost
			$day_labor_cost = $daily_cost['daily_rate'];

			// Overtime cost: (daily_rate / 8) * overtime_multiplier * hours
			if ( $overtime_hours > 0 ) {
				$hourly_rate     = $daily_cost['daily_rate'] / 8;
				$overtime_cost   = $hourly_rate * $overtime_multiplier * $overtime_hours;
				$day_labor_cost += $overtime_cost;
			}

			$total_labor_cost += $day_labor_cost;
		}

		return array(
			'days'           => $total_days,
			'overtime_hours' => $total_overtime,
			'labor_cost'     => round( $total_labor_cost, 2 ),
		);
	}

	/**
	 * Calculate employee daily cost including allowances.
	 *
	 * @param int $employee_id Employee post ID.
	 * @param int $working_days Working days per month.
	 * @return array Daily rate and breakdown.
	 */
	private function calculate_employee_daily_cost( $employee_id, $working_days = 26 ) {
		$basic_salary = floatval( get_post_meta( $employee_id, '_berp_basic_salary', true ) );
		$allowances   = get_post_meta( $employee_id, '_berp_allowances', true );

		// Calculate total allowances
		$total_allowances = 0;
		if ( is_array( $allowances ) ) {
			foreach ( $allowances as $allowance ) {
				if ( isset( $allowance['amount'] ) && is_numeric( $allowance['amount'] ) ) {
					$total_allowances += floatval( $allowance['amount'] );
				}
			}
		}

		// Monthly cost = basic salary + allowances
		$monthly_cost = $basic_salary + $total_allowances;

		// Daily rate
		$daily_rate = $working_days > 0 ? $monthly_cost / $working_days : 0;

		return array(
			'basic_salary'     => $basic_salary,
			'total_allowances' => $total_allowances,
			'monthly_cost'     => $monthly_cost,
			'daily_rate'       => round( $daily_rate, 2 ),
		);
	}

	/**
	 * Get manpower totals across all sites or for a specific site.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $site_id    Site ID (0 for all sites).
	 * @return array Manpower totals.
	 */
	private function get_manpower_totals( $start_date, $end_date, $site_id = 0 ) {
		if ( $site_id ) {
			// Single site - use existing method
			return array(
				'manpower_days'  => $this->get_site_manpower( $site_id, $start_date, $end_date )['days'],
				'overtime_hours' => $this->get_site_manpower( $site_id, $start_date, $end_date )['overtime_hours'],
				'labor_cost'     => $this->get_site_manpower( $site_id, $start_date, $end_date )['labor_cost'],
			);
		}

		// All sites - get attendance across all sites
		$args = array(
			'post_type'      => 'berp_attendance',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_berp_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$attendance_records = get_posts( $args );

		$total_days          = count( $attendance_records );
		$total_overtime      = 0;
		$total_labor_cost    = 0;
		$employee_daily_cost = array();

		// Get payroll settings
		$settings            = get_option( 'berp_settings', array() );
		$payroll_settings    = isset( $settings['payroll'] ) ? $settings['payroll'] : array();
		$working_days        = isset( $payroll_settings['working_days'] ) ? intval( $payroll_settings['working_days'] ) : 26;
		$attendance_settings = isset( $settings['attendance'] ) ? $settings['attendance'] : array();
		$overtime_multiplier = isset( $attendance_settings['default_multiplier'] ) ? floatval( $attendance_settings['default_multiplier'] ) : 1.5;

		foreach ( $attendance_records as $record ) {
			$employee_id    = get_post_meta( $record->ID, '_berp_employee_id', true );
			$overtime_hours = floatval( get_post_meta( $record->ID, '_berp_overtime_hours', true ) );

			$total_overtime += $overtime_hours;

			if ( ! isset( $employee_daily_cost[ $employee_id ] ) ) {
				$employee_daily_cost[ $employee_id ] = $this->calculate_employee_daily_cost( $employee_id, $working_days );
			}

			$daily_cost     = $employee_daily_cost[ $employee_id ];
			$day_labor_cost = $daily_cost['daily_rate'];

			if ( $overtime_hours > 0 ) {
				$hourly_rate     = $daily_cost['daily_rate'] / 8;
				$overtime_cost   = $hourly_rate * $overtime_multiplier * $overtime_hours;
				$day_labor_cost += $overtime_cost;
			}

			$total_labor_cost += $day_labor_cost;
		}

		return array(
			'manpower_days'  => $total_days,
			'overtime_hours' => $total_overtime,
			'labor_cost'     => round( $total_labor_cost, 2 ),
		);
	}

	/**
	 * Render Expense Breakdown Report.
	 */
	private function render_report_expense_breakdown() {
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
		$site_id    = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

		$data = $this->get_expense_breakdown_data( $start_date, $end_date, $site_id );

		// Get manpower data for selected site(s)
		$manpower_totals = $this->get_manpower_totals( $start_date, $end_date, $site_id );

		// Summary Cards - Row 1
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Expenses', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['total'] ) ) . '</div>';
		echo '</div>';

		$top_category = ! empty( $data['rows'] ) ? $data['rows'][0]['category'] : '-';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Top Expense Category', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( $top_category ) . '</div>';
		echo '</div>';

		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Expense Count', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( $data['count'] ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Summary Cards - Row 2: Manpower Statistics
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Manpower Days', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $manpower_totals['manpower_days'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Overtime Hours', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $manpower_totals['overtime_hours'], 1 ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Est. Labor Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $manpower_totals['labor_cost'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Cost (Exp + Labor)', 'aic_builderp' ) . '</h3>';
		$total_cost = $data['total'] + $manpower_totals['labor_cost'];
		echo '<div class="value">' . esc_html( berp_format_currency( $total_cost ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-expense-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Category', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Amount', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( '% of Total', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Transaction Count', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No expenses found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['category'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['amount'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['percentage'], 1 ) ) . '%</td>';
				echo '<td class="num">' . esc_html( $row['count'] ) . '</td>';
				echo '</tr>';
			}
		}

		// Add Labor Cost as a special row
		if ( $manpower_totals['labor_cost'] > 0 ) {
			$labor_percent = $total_cost > 0 ? ( $manpower_totals['labor_cost'] / $total_cost ) * 100 : 0;
			echo '<tr class="berp-labor-row">';
			echo '<td><strong>' . esc_html__( 'Labor Cost (Calculated)', 'aic_builderp' ) . '</strong></td>';
			echo '<td class="num"><strong>' . esc_html( berp_format_currency( $manpower_totals['labor_cost'] ) ) . '</strong></td>';
			echo '<td class="num"><strong>' . esc_html( number_format( $labor_percent, 1 ) ) . '%</strong></td>';
			echo '<td class="num"><strong>' . esc_html( $manpower_totals['manpower_days'] ) . ' ' . esc_html__( 'days', 'aic_builderp' ) . '</strong></td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals (Expenses Only)', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['total'] ) ) . '</td>';
		echo '<td class="num">-</td>';
		echo '<td class="num">' . esc_html( $data['count'] ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'expense',
				'labels'   => wp_list_pluck( $data['rows'], 'category' ),
				'datasets' => array(
					array(
						'label'           => __( 'Amount', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'amount' ),
						'backgroundColor' => array( '#2271b1', '#d63638', '#f0ad4e', '#46b450', '#9b59b6', '#34495e' ),
					),
				),
			)
		);
	}

	/**
	 * Get data for Expense Breakdown Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $site_id    Site ID.
	 * @return array Report data.
	 */
	private function get_expense_breakdown_data( $start_date, $end_date, $site_id = 0 ) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_berp_expense_date',
				'value'   => array( $start_date, $end_date ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		);

		if ( $site_id ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => $site_id,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
		);

		$expenses = get_posts( $args );

		$categories   = array();
		$total_amount = 0;
		$total_count  = 0;

		foreach ( $expenses as $expense ) {
			// Get category term
			$terms         = get_the_terms( $expense->ID, 'berp_expense_category' );
			$category_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : __( 'Uncategorized', 'aic_builderp' );

			$amount = floatval( get_post_meta( $expense->ID, '_berp_expense_amount', true ) );

			if ( ! isset( $categories[ $category_name ] ) ) {
				$categories[ $category_name ] = array(
					'amount' => 0,
					'count'  => 0,
				);
			}

			$categories[ $category_name ]['amount'] += $amount;
			++$categories[ $category_name ]['count'];

			$total_amount += $amount;
			++$total_count;
		}

		$rows = array();
		foreach ( $categories as $name => $data ) {
			$rows[] = array(
				'category'   => $name,
				'amount'     => $data['amount'],
				'count'      => $data['count'],
				'percentage' => $total_amount > 0 ? ( $data['amount'] / $total_amount ) * 100 : 0,
			);
		}

		// Sort by amount descending
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['amount'] - $a['amount'];
			}
		);

		return array(
			'rows'  => $rows,
			'total' => $total_amount,
			'count' => $total_count,
		);
	}

	/**
	 * Render Invoice Aging Report.
	 */
	private function render_report_invoice_aging() {
		$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

		$data = $this->get_invoice_aging_data( $client_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Outstanding', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['total_outstanding'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Overdue (90+ Days)', 'aic_builderp' ) . '</h3>';
		echo '<div class="value negative">' . esc_html( berp_format_currency( $data['buckets']['90+']['amount'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Invoice Count', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( $data['count'] ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-aging-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Aging Bucket', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Invoice Count', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Total Amount', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( '% of Total', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$buckets = array( '0-30', '31-60', '61-90', '90+' );
		foreach ( $buckets as $bucket ) {
			$info       = $data['buckets'][ $bucket ];
			$percentage = $data['total_outstanding'] > 0 ? ( $info['amount'] / $data['total_outstanding'] ) * 100 : 0;

			echo '<tr>';
			echo '<td>' . esc_html( $bucket . ' ' . __( 'Days', 'aic_builderp' ) ) . '</td>';
			echo '<td class="num">' . esc_html( $info['count'] ) . '</td>';
			echo '<td class="num">' . esc_html( berp_format_currency( $info['amount'] ) ) . '</td>';
			echo '<td class="num">' . esc_html( number_format( $percentage, 1 ) ) . '%</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( $data['count'] ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['total_outstanding'] ) ) . '</td>';
		echo '<td class="num">100%</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Detailed List
		echo '<h3>' . esc_html__( 'Outstanding Invoices', 'aic_builderp' ) . '</h3>';
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Invoice #', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Client', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Due Date', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Age (Days)', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Amount Due', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['invoices'] ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No outstanding invoices found.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['invoices'] as $inv ) {
				echo '<tr>';
				echo '<td>' . esc_html( $inv['number'] ) . '</td>';
				echo '<td>' . esc_html( $inv['client'] ) . '</td>';
				echo '<td>' . esc_html( $inv['due_date'] ) . '</td>';
				echo '<td>' . esc_html( $inv['age'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $inv['amount_due'] ) ) . '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'aging',
				'labels'   => array( '0-30 Days', '31-60 Days', '61-90 Days', '90+ Days' ),
				'datasets' => array(
					array(
						'label'           => __( 'Amount Outstanding', 'aic_builderp' ),
						'data'            => array(
							$data['buckets']['0-30']['amount'],
							$data['buckets']['31-60']['amount'],
							$data['buckets']['61-90']['amount'],
							$data['buckets']['90+']['amount'],
						),
						'backgroundColor' => array( '#46b450', '#f0ad4e', '#e67e22', '#d63638' ),
					),
				),
			)
		);
	}

	/**
	 * Get data for Invoice Aging Report.
	 *
	 * @param int $client_id Client ID.
	 * @return array Report data.
	 */
	private function get_invoice_aging_data( $client_id = 0 ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_berp_status',
					'value'   => 'paid',
					'compare' => '!=',
				),
			),
		);

		if ( $client_id ) {
			$args['meta_query'][] = array(
				'key'     => '_berp_client_id',
				'value'   => $client_id,
				'compare' => '=',
			);
		}

		$invoices = get_posts( $args );

		$buckets = array(
			'0-30'  => array(
				'count'  => 0,
				'amount' => 0,
			),
			'31-60' => array(
				'count'  => 0,
				'amount' => 0,
			),
			'61-90' => array(
				'count'  => 0,
				'amount' => 0,
			),
			'90+'   => array(
				'count'  => 0,
				'amount' => 0,
			),
		);

		$invoice_list      = array();
		$total_outstanding = 0;
		$count             = 0;
		$today             = new DateTime();

		foreach ( $invoices as $invoice ) {
			$due_date_str = get_post_meta( $invoice->ID, '_berp_due_date', true );
			if ( ! $due_date_str ) {
				continue;
			}

			$due_date = new DateTime( $due_date_str );
			$interval = $due_date->diff( $today );
			$age      = $interval->invert ? 0 : $interval->days; // If due date is future, age is 0 (or negative, but let's treat as 0-30)

			// If due date is in future, it's not really "aged" yet, but it is outstanding.
			// Standard aging usually counts from invoice date or due date.
			// If we use Due Date:
			// Past due by X days.
			// If not past due, it falls in 0-30 (Current).

			if ( $interval->invert ) {
				// Future due date
				$age = 0;
			}

			$grand_total = floatval( get_post_meta( $invoice->ID, '_berp_grand_total', true ) );
			$amount_paid = floatval( get_post_meta( $invoice->ID, '_berp_amount_paid', true ) );
			$amount_due  = $grand_total - $amount_paid;

			if ( $amount_due <= 0 ) {
				continue; // Should be filtered by status, but double check
			}

			$bucket_key = '0-30';
			if ( $age > 90 ) {
				$bucket_key = '90+';
			} elseif ( $age > 60 ) {
				$bucket_key = '61-90';
			} elseif ( $age > 30 ) {
				$bucket_key = '31-60';
			}

			++$buckets[ $bucket_key ]['count'];
			$buckets[ $bucket_key ]['amount'] += $amount_due;

			$client_id   = get_post_meta( $invoice->ID, '_berp_client_id', true );
			$client_name = get_the_title( $client_id );

			$invoice_list[] = array(
				'number'     => get_post_meta( $invoice->ID, '_berp_invoice_number', true ),
				'client'     => $client_name,
				'due_date'   => $due_date_str,
				'age'        => $age,
				'amount_due' => $amount_due,
			);

			$total_outstanding += $amount_due;
			++$count;
		}

		// Sort invoice list by age desc
		usort(
			$invoice_list,
			function ( $a, $b ) {
				return $b['age'] - $a['age'];
			}
		);

		return array(
			'buckets'           => $buckets,
			'invoices'          => $invoice_list,
			'total_outstanding' => $total_outstanding,
			'count'             => $count,
		);
	}

	/**
	 * Render Client Payment History Report.
	 */
	private function render_report_client_payments() {
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
		$client_id  = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

		$data = $this->get_client_payments_data( $start_date, $end_date, $client_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Received', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['total_received'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Payment Count', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( $data['count'] ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-payments-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Date', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Client', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Invoice #', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Method', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Amount', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No payments found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['date'] ) . '</td>';
				echo '<td>' . esc_html( $row['client'] ) . '</td>';
				echo '<td>' . esc_html( $row['invoice_number'] ) . '</td>';
				echo '<td>' . esc_html( ucfirst( $row['method'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['amount'] ) ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td colspan="4">' . esc_html__( 'Total', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['total_received'] ) ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Prepare chart data (group by month)
		$chart_data = array();
		foreach ( $data['rows'] as $row ) {
			$month = substr( $row['date'], 0, 7 ); // YYYY-MM
			if ( ! isset( $chart_data[ $month ] ) ) {
				$chart_data[ $month ] = 0;
			}
			$chart_data[ $month ] += $row['amount'];
		}
		ksort( $chart_data );

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'payments',
				'labels'   => array_keys( $chart_data ),
				'datasets' => array(
					array(
						'label'           => __( 'Payments Received', 'aic_builderp' ),
						'data'            => array_values( $chart_data ),
						'borderColor'     => '#2271b1',
						'backgroundColor' => 'rgba(34, 113, 177, 0.1)',
						'fill'            => true,
					),
				),
			)
		);
	}

	/**
	 * Get data for Client Payment History Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $client_id  Client ID.
	 * @return array Report data.
	 */
	private function get_client_payments_data( $start_date, $end_date, $client_id = 0 ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		if ( $client_id ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_berp_client_id',
					'value'   => $client_id,
					'compare' => '=',
				),
			);
		}

		$invoices       = get_posts( $args );
		$payments       = array();
		$total_received = 0;

		foreach ( $invoices as $invoice ) {
			$inv_payments = get_post_meta( $invoice->ID, '_berp_payments', true );
			if ( ! is_array( $inv_payments ) ) {
				continue;
			}

			$client_id      = get_post_meta( $invoice->ID, '_berp_client_id', true );
			$client_name    = get_the_title( $client_id );
			$invoice_number = get_post_meta( $invoice->ID, '_berp_invoice_number', true );

			foreach ( $inv_payments as $payment ) {
				// Check date range
				if ( $payment['date'] >= $start_date && $payment['date'] <= $end_date ) {
					$payments[]      = array(
						'date'           => $payment['date'],
						'client'         => $client_name,
						'invoice_number' => $invoice_number,
						'method'         => isset( $payment['method'] ) ? $payment['method'] : '-',
						'amount'         => floatval( $payment['amount'] ),
					);
					$total_received += floatval( $payment['amount'] );
				}
			}
		}

		// Sort by date desc
		usort(
			$payments,
			function ( $a, $b ) {
				return strcmp( $b['date'], $a['date'] );
			}
		);

		return array(
			'rows'           => $payments,
			'total_received' => $total_received,
			'count'          => count( $payments ),
		);
	}

	/**
	 * Render Employee Account Balances Report.
	 */
	private function render_report_employee_balances() {
		$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

		$data = $this->get_employee_balances_data( $employee_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Balance (Company Receivables)', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['total_receivable'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Balance (Company Payables)', 'aic_builderp' ) . '</h3>';
		echo '<div class="value negative">' . esc_html( berp_format_currency( abs( $data['total_payable'] ) ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Net Position', 'aic_builderp' ) . '</h3>';
		$net   = $data['total_receivable'] + $data['total_payable']; // payable is negative
		$class = $net >= 0 ? 'positive' : 'negative';
		echo '<div class="value ' . esc_attr( $class ) . '">' . esc_html( berp_format_currency( $net ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-balances-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Employee Name', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Employee ID', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Balance', 'aic_builderp' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No employee balances found.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				$class  = $row['balance'] > 0 ? 'positive' : ( $row['balance'] < 0 ? 'negative' : '' );
				$status = $row['balance'] > 0 ? __( 'Owes Company', 'aic_builderp' ) : ( $row['balance'] < 0 ? __( 'Company Owes', 'aic_builderp' ) : '-' );

				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td>' . esc_html( $row['employee_code'] ) . '</td>';
				echo '<td class="num ' . esc_attr( $class ) . '">' . esc_html( berp_format_currency( $row['balance'] ) ) . '</td>';
				echo '<td>' . esc_html( $status ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td colspan="2">' . esc_html__( 'Net Total', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $net ) ) . '</td>';
		echo '<td></td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart (Top 10 debtors/creditors)
		$chart_rows = array_slice( $data['rows'], 0, 10 );
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'balances',
				'labels'   => wp_list_pluck( $chart_rows, 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Balance', 'aic_builderp' ),
						'data'            => wp_list_pluck( $chart_rows, 'balance' ),
						'backgroundColor' => array_map(
							function ( $row ) {
								return $row['balance'] >= 0 ? '#2271b1' : '#d63638';
							},
							$chart_rows
						),
					),
				),
			)
		);
	}

	/**
	 * Get data for Employee Balances Report.
	 *
	 * @param int $employee_id Employee ID.
	 * @return array Report data.
	 */
	private function get_employee_balances_data( $employee_id = 0 ) {
		$args = array(
			'post_type'      => 'berp_employee',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		if ( $employee_id ) {
			$args['include'] = array( $employee_id );
		}

		$employees        = get_posts( $args );
		$rows             = array();
		$total_receivable = 0;
		$total_payable    = 0;

		foreach ( $employees as $employee ) {
			$balance = floatval( get_post_meta( $employee->ID, '_berp_account_balance', true ) );

			if ( $balance == 0 && ! $employee_id ) {
				continue; // Skip zero balances unless specific employee selected
			}

			$rows[] = array(
				'name'          => $employee->post_title,
				'employee_code' => get_post_meta( $employee->ID, '_berp_employee_id', true ),
				'balance'       => $balance,
			);

			if ( $balance > 0 ) {
				$total_receivable += $balance;
			} else {
				$total_payable += $balance;
			}
		}

		// Sort by absolute balance desc
		usort(
			$rows,
			function ( $a, $b ) {
				return abs( $b['balance'] ) - abs( $a['balance'] );
			}
		);

		return array(
			'rows'             => $rows,
			'total_receivable' => $total_receivable,
			'total_payable'    => $total_payable,
		);
	}

	/**
	 * Render Budget vs Actual Report.
	 */
	private function render_report_budget_vs_actual() {
		$site_id    = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );

		$data = $this->get_budget_vs_actual_data( $site_id, $start_date, $end_date );

		// Summary Cards - Row 1
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Budget', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['budget'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Spent', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['spent'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Remaining Budget', 'aic_builderp' ) . '</h3>';
		$remaining = $data['totals']['budget'] - $data['totals']['spent'];
		$class     = $remaining >= 0 ? 'positive' : 'negative';
		echo '<div class="value ' . esc_attr( $class ) . '">' . esc_html( berp_format_currency( $remaining ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Summary Cards - Row 2: Manpower Statistics
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Manpower Days', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['manpower_days'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Overtime Hours', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['overtime_hours'], 1 ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Est. Labor Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['labor_cost'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( '% Used', 'aic_builderp' ) . '</h3>';
		$percent_class = $data['totals']['percent'] > 100 ? 'negative' : ( $data['totals']['percent'] > 90 ? 'warning' : 'positive' );
		echo '<div class="value ' . esc_attr( $percent_class ) . '">' . esc_html( number_format( $data['totals']['percent'], 1 ) ) . '%</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-budget-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Site Name', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Budget', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Spent', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Manpower Days', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'OT Hours', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Labor Cost', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Remaining', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( '% Used', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="8">' . esc_html__( 'No sites found with budget data.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				$remaining     = $row['budget'] - $row['spent'];
				$class         = $remaining >= 0 ? 'positive' : 'negative';
				$percent_class = $row['percent'] > 100 ? 'negative' : ( $row['percent'] > 90 ? 'warning' : '' );

				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['budget'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['spent'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['manpower_days'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['overtime_hours'], 1 ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['labor_cost'] ) ) . '</td>';
				echo '<td class="num ' . esc_attr( $class ) . '">' . esc_html( berp_format_currency( $remaining ) ) . '</td>';
				echo '<td class="num ' . esc_attr( $percent_class ) . '">' . esc_html( number_format( $row['percent'], 1 ) ) . '%</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['budget'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['spent'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['manpower_days'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['overtime_hours'], 1 ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['labor_cost'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['budget'] - $data['totals']['spent'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['percent'], 1 ) ) . '%</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'budget',
				'labels'   => wp_list_pluck( $data['rows'], 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Budget', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'budget' ),
						'backgroundColor' => '#2271b1',
					),
					array(
						'label'           => __( 'Spent', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'spent' ),
						'backgroundColor' => '#d63638',
					),
				),
			)
		);
	}

	/**
	 * Get data for Budget vs Actual Report.
	 *
	 * @param int    $site_id    Site ID.
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array Report data.
	 */
	private function get_budget_vs_actual_data( $site_id = 0, $start_date = '', $end_date = '' ) {
		// Default date range if not provided
		if ( empty( $start_date ) ) {
			$start_date = date( 'Y-01-01' );
		}
		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-12-31' );
		}

		$args = array(
			'post_type'      => 'berp_site',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		if ( $site_id ) {
			$args['include'] = array( $site_id );
		}

		$sites  = get_posts( $args );
		$rows   = array();
		$totals = array(
			'budget'         => 0,
			'spent'          => 0,
			'manpower_days'  => 0,
			'overtime_hours' => 0,
			'labor_cost'     => 0,
		);

		foreach ( $sites as $site ) {
			$budget = floatval( get_post_meta( $site->ID, '_berp_budget', true ) );
			$spent  = floatval( get_post_meta( $site->ID, '_berp_budget_spent', true ) );

			// Get manpower data for this site
			$manpower = $this->get_site_manpower( $site->ID, $start_date, $end_date );

			if ( $budget == 0 && $spent == 0 && $manpower['days'] == 0 ) {
				continue;
			}

			$rows[] = array(
				'name'           => $site->post_title,
				'budget'         => $budget,
				'spent'          => $spent,
				'manpower_days'  => $manpower['days'],
				'overtime_hours' => $manpower['overtime_hours'],
				'labor_cost'     => $manpower['labor_cost'],
				'percent'        => $budget > 0 ? ( $spent / $budget ) * 100 : 0,
			);

			$totals['budget']         += $budget;
			$totals['spent']          += $spent;
			$totals['manpower_days']  += $manpower['days'];
			$totals['overtime_hours'] += $manpower['overtime_hours'];
			$totals['labor_cost']     += $manpower['labor_cost'];
		}

		$totals['percent'] = $totals['budget'] > 0 ? ( $totals['spent'] / $totals['budget'] ) * 100 : 0;

		// Sort by percent used desc
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['percent'] - $a['percent'];
			}
		);

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Render Overtime Analysis Report.
	 */
	private function render_report_overtime_analysis() {
		$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
		$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );
		$site_id     = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;
		$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

		$data = $this->get_overtime_analysis_data( $start_date, $end_date, $site_id, $employee_id );

		// Get manpower totals for context
		$manpower_totals = $this->get_manpower_totals( $start_date, $end_date, $site_id );

		// Summary Cards - Row 1
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Overtime Hours', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $data['totals']['hours'], 2 ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Estimated OT Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['totals']['cost'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Employees with OT', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( count( $data['rows'] ) ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Summary Cards - Row 2: Manpower Context
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Manpower Days', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( number_format( $manpower_totals['manpower_days'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Regular Labor Cost', 'aic_builderp' ) . '</h3>';
		$regular_labor = $manpower_totals['labor_cost'] - $data['totals']['cost'];
		echo '<div class="value">' . esc_html( berp_format_currency( max( 0, $regular_labor ) ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Labor Cost', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $manpower_totals['labor_cost'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'OT % of Labor', 'aic_builderp' ) . '</h3>';
		$ot_percent = $manpower_totals['labor_cost'] > 0 ? ( $data['totals']['cost'] / $manpower_totals['labor_cost'] ) * 100 : 0;
		$ot_class   = $ot_percent > 20 ? 'warning' : '';
		echo '<div class="value ' . esc_attr( $ot_class ) . '">' . esc_html( number_format( $ot_percent, 1 ) ) . '%</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-overtime-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Employee Name', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Days Worked', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'OT Hours', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Avg. OT/Day', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Est. OT Cost', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No overtime records found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			foreach ( $data['rows'] as $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $row['name'] ) . '</td>';
				echo '<td class="num">' . esc_html( $row['days'] ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['hours'], 2 ) ) . '</td>';
				echo '<td class="num">' . esc_html( number_format( $row['avg_hours'], 2 ) ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['cost'] ) ) . '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Totals', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( $data['totals']['days'] ) . '</td>';
		echo '<td class="num">' . esc_html( number_format( $data['totals']['hours'], 2 ) ) . '</td>';
		echo '<td class="num">-</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['totals']['cost'] ) ) . '</td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		$chart_rows = array_slice( $data['rows'], 0, 10 );
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'overtime',
				'labels'   => wp_list_pluck( $chart_rows, 'name' ),
				'datasets' => array(
					array(
						'label'           => __( 'Overtime Hours', 'aic_builderp' ),
						'data'            => wp_list_pluck( $chart_rows, 'hours' ),
						'backgroundColor' => '#f0ad4e',
					),
				),
			)
		);
	}

	/**
	 * Get data for Overtime Analysis Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $site_id    Site ID.
	 * @param int    $employee_id Employee ID.
	 * @return array Report data.
	 */
	private function get_overtime_analysis_data( $start_date, $end_date, $site_id = 0, $employee_id = 0 ) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_berp_date',
				'value'   => array( $start_date, $end_date ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
			array(
				'key'     => '_berp_overtime_hours',
				'value'   => 0,
				'compare' => '>',
			),
		);

		if ( $site_id ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => $site_id,
				'compare' => '=',
			);
		}

		if ( $employee_id ) {
			$meta_query[] = array(
				'key'     => '_berp_employee_id',
				'value'   => $employee_id,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => 'berp_attendance',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'fields'         => 'ids',
		);

		$attendance_ids = get_posts( $args );
		$employee_stats = array();

		foreach ( $attendance_ids as $post_id ) {
			$emp_id = get_post_meta( $post_id, '_berp_employee_id', true );
			$hours  = floatval( get_post_meta( $post_id, '_berp_overtime_hours', true ) );

			if ( ! isset( $employee_stats[ $emp_id ] ) ) {
				$employee_stats[ $emp_id ] = array(
					'hours' => 0,
					'days'  => 0,
				);
			}

			$employee_stats[ $emp_id ]['hours'] += $hours;
			++$employee_stats[ $emp_id ]['days'];
		}

		$rows   = array();
		$totals = array(
			'hours' => 0,
			'days'  => 0,
			'cost'  => 0,
		);

		// Get payroll settings for working days
		$settings            = get_option( 'berp_settings', array() );
		$payroll_settings    = isset( $settings['payroll'] ) ? $settings['payroll'] : array();
		$working_days        = isset( $payroll_settings['working_days'] ) ? intval( $payroll_settings['working_days'] ) : 26;
		$attendance_settings = isset( $settings['attendance'] ) ? $settings['attendance'] : array();
		$overtime_multiplier = isset( $attendance_settings['default_multiplier'] ) ? floatval( $attendance_settings['default_multiplier'] ) : 1.5;

		foreach ( $employee_stats as $emp_id => $stats ) {
			$employee = get_post( $emp_id );
			if ( ! $employee ) {
				continue;
			}

			// Calculate overtime cost using employee daily cost
			$employee_cost = $this->calculate_employee_daily_cost( $emp_id, $working_days );
			$hourly_rate   = $employee_cost['daily_rate'] / 8;
			$cost          = $hourly_rate * $overtime_multiplier * $stats['hours'];

			$rows[] = array(
				'name'      => $employee->post_title,
				'days'      => $stats['days'],
				'hours'     => $stats['hours'],
				'avg_hours' => $stats['days'] > 0 ? $stats['hours'] / $stats['days'] : 0,
				'cost'      => round( $cost, 2 ),
			);

			$totals['hours'] += $stats['hours'];
			$totals['days']  += $stats['days'];
			$totals['cost']  += $cost;
		}

		$totals['cost'] = round( $totals['cost'], 2 );

		// Sort by hours desc
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['hours'] - $a['hours'];
			}
		);

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Render Revenue Trends Report.
	 */
	private function render_report_revenue_trends() {
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
		$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
		$client_id  = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

		$data = $this->get_revenue_trends_data( $start_date, $end_date, $client_id );

		// Summary Cards
		echo '<div class="berp-summary-cards">';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Total Revenue', 'aic_builderp' ) . '</h3>';
		echo '<div class="value">' . esc_html( berp_format_currency( $data['total_revenue'] ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Avg. Monthly Revenue', 'aic_builderp' ) . '</h3>';
		$avg = count( $data['rows'] ) > 0 ? $data['total_revenue'] / count( $data['rows'] ) : 0;
		echo '<div class="value">' . esc_html( berp_format_currency( $avg ) ) . '</div>';
		echo '</div>';
		echo '<div class="berp-summary-card">';
		echo '<h3>' . esc_html__( 'Best Month', 'aic_builderp' ) . '</h3>';
		$best_month = '-';
		$max_rev    = 0;
		foreach ( $data['rows'] as $row ) {
			if ( $row['revenue'] > $max_rev ) {
				$max_rev    = $row['revenue'];
				$best_month = $row['month'];
			}
		}
		echo '<div class="value">' . esc_html( $best_month ) . '</div>';
		echo '</div>';
		echo '</div>';

		// Chart Container
		echo '<div class="berp-chart-container">';
		echo '<canvas id="berp-revenue-chart"></canvas>';
		echo '</div>';

		// Data Table
		echo '<table class="berp-report-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Month', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Revenue', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Invoice Count', 'aic_builderp' ) . '</th>';
		echo '<th class="num">' . esc_html__( 'Growth', 'aic_builderp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( empty( $data['rows'] ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No revenue data found for the selected period.', 'aic_builderp' ) . '</td></tr>';
		} else {
			$prev_revenue = 0;
			foreach ( $data['rows'] as $row ) {
				$growth = 0;
				if ( $prev_revenue > 0 ) {
					$growth = ( ( $row['revenue'] - $prev_revenue ) / $prev_revenue ) * 100;
				}
				$growth_class = $growth > 0 ? 'positive' : ( $growth < 0 ? 'negative' : '' );
				$growth_text  = $prev_revenue == 0 ? '-' : number_format( $growth, 1 ) . '%';

				echo '<tr>';
				echo '<td>' . esc_html( $row['month'] ) . '</td>';
				echo '<td class="num">' . esc_html( berp_format_currency( $row['revenue'] ) ) . '</td>';
				echo '<td class="num">' . esc_html( $row['count'] ) . '</td>';
				echo '<td class="num ' . esc_attr( $growth_class ) . '">' . esc_html( $growth_text ) . '</td>';
				echo '</tr>';

				$prev_revenue = $row['revenue'];
			}
		}

		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<td>' . esc_html__( 'Total', 'aic_builderp' ) . '</td>';
		echo '<td class="num">' . esc_html( berp_format_currency( $data['total_revenue'] ) ) . '</td>';
		echo '<td class="num">' . esc_html( $data['total_count'] ) . '</td>';
		echo '<td></td>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		// Pass data to JS for chart
		wp_localize_script(
			'berp-reports-js',
			'berpReportData',
			array(
				'type'     => 'revenue',
				'labels'   => wp_list_pluck( $data['rows'], 'month' ),
				'datasets' => array(
					array(
						'label'           => __( 'Revenue', 'aic_builderp' ),
						'data'            => wp_list_pluck( $data['rows'], 'revenue' ),
						'borderColor'     => '#46b450',
						'backgroundColor' => 'rgba(70, 180, 80, 0.1)',
						'fill'            => true,
					),
				),
			)
		);
	}

	/**
	 * Get data for Revenue Trends Report.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @param int    $client_id  Client ID.
	 * @return array Report data.
	 */
	private function get_revenue_trends_data( $start_date, $end_date, $client_id = 0 ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_invoice_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		if ( $client_id ) {
			$args['meta_query'][] = array(
				'key'     => '_berp_client_id',
				'value'   => $client_id,
				'compare' => '=',
			);
		}

		$invoices      = get_posts( $args );
		$monthly_data  = array();
		$total_revenue = 0;
		$total_count   = 0;

		// Initialize months in range
		$start    = new DateTime( $start_date );
		$end      = new DateTime( $end_date );
		$interval = DateInterval::createFromDateString( '1 month' );
		$period   = new DatePeriod( $start, $interval, $end->modify( '+1 day' ) ); // Include end date month

		foreach ( $period as $dt ) {
			$monthly_data[ $dt->format( 'Y-m' ) ] = array(
				'revenue' => 0,
				'count'   => 0,
			);
		}

		foreach ( $invoices as $invoice ) {
			$date  = get_post_meta( $invoice->ID, '_berp_invoice_date', true );
			$month = substr( $date, 0, 7 ); // YYYY-MM

			if ( isset( $monthly_data[ $month ] ) ) {
				$amount                             = floatval( get_post_meta( $invoice->ID, '_berp_grand_total', true ) );
				$monthly_data[ $month ]['revenue'] += $amount;
				++$monthly_data[ $month ]['count'];

				$total_revenue += $amount;
				++$total_count;
			}
		}

		$rows = array();
		foreach ( $monthly_data as $month => $data ) {
			$rows[] = array(
				'month'   => $month,
				'revenue' => $data['revenue'],
				'count'   => $data['count'],
			);
		}

		return array(
			'rows'          => $rows,
			'total_revenue' => $total_revenue,
			'total_count'   => $total_count,
		);
	}

	/**
	 * Handle report exports.
	 */
	public function handle_exports() {
		if ( ! isset( $_GET['page'] ) || 'builderp-reports' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['export'] ) ) {
			return;
		}

		if ( ! current_user_can( 'berp_view_reports' ) ) {
			return;
		}

		$report = isset( $_GET['report'] ) ? sanitize_key( $_GET['report'] ) : 'attendance_summary';
		$format = sanitize_key( $_GET['export'] );

		if ( 'excel' === $format ) {
			$this->export_to_csv( $report );
		} elseif ( 'pdf' === $format ) {
			// PDF export to be implemented using mPDF or similar library.
			// For now, we can redirect or show a message.
			wp_die( esc_html__( 'PDF Export is coming soon.', 'aic_builderp' ) );
		}
	}

	/**
	 * Export report to CSV.
	 *
	 * @param string $report Report type.
	 */
	private function export_to_csv( $report ) {
		$filename = 'report-' . $report . '-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// Get data based on report type
		switch ( $report ) {
			case 'attendance_summary':
				$headers = array( 'Employee Name', 'Employee ID', 'Present Days', 'Overtime Hours', 'Attendance Rate (%)' );
				fputcsv( $output, $headers );

				$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
				$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );
				$site_id     = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;
				$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

				$data = $this->get_attendance_summary_data( $start_date, $end_date, $site_id, $employee_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['name'],
							$row['employee_code'],
							$row['present_days'],
							number_format( $row['overtime_hours'], 2 ),
							number_format( $row['attendance_rate'], 1 ),
						)
					);
				}
				break;

			case 'payroll_register':
				$headers = array( 'Employee', 'Basic Salary', 'Allowances', 'Overtime', 'Gross Salary', 'Deductions', 'Net Salary', 'Status' );
				fputcsv( $output, $headers );

				$month       = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : date( 'Y-m' );
				$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

				$data = $this->get_payroll_register_data( $month, $employee_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['name'],
							$row['basic_salary'],
							$row['total_allowance'],
							$row['overtime_amount'],
							$row['gross_salary'],
							$row['total_deduction'],
							$row['net_salary'],
							ucfirst( $row['status'] ),
						)
					);
				}
				break;

			case 'site_profitability':
				$headers = array( 'Site Name', 'Total Revenue', 'Total Expenses', 'Payroll Cost', 'Net Profit', 'Margin (%)' );
				fputcsv( $output, $headers );

				$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
				$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
				$site_id    = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

				$data = $this->get_site_profitability_data( $start_date, $end_date, $site_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['site_name'],
							$row['revenue'],
							$row['expenses'],
							$row['payroll'],
							$row['profit'],
							number_format( $row['margin'], 1 ),
						)
					);
				}
				break;

			case 'expense_breakdown':
				$headers = array( 'Category', 'Amount', 'Percentage (%)' );
				fputcsv( $output, $headers );

				$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
				$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );
				$site_id    = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

				$data = $this->get_expense_breakdown_data( $start_date, $end_date, $site_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['category'],
							$row['amount'],
							number_format( $row['percentage'], 1 ),
						)
					);
				}
				break;

			case 'invoice_aging':
				$headers = array( 'Client', 'Current', '1-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total Due' );
				fputcsv( $output, $headers );

				$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

				$data = $this->get_invoice_aging_data( $client_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['client_name'],
							$row['current'],
							$row['days_30'],
							$row['days_60'],
							$row['days_90'],
							$row['days_90_plus'],
							$row['total_due'],
						)
					);
				}
				break;

			case 'client_payments':
				$headers = array( 'Client', 'Total Invoiced', 'Total Paid', 'Outstanding', 'Payment Rate (%)' );
				fputcsv( $output, $headers );

				$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-01-01' );
				$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-12-31' );
				$client_id  = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

				$data = $this->get_client_payments_data( $start_date, $end_date, $client_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['client_name'],
							$row['invoiced'],
							$row['paid'],
							$row['outstanding'],
							number_format( $row['payment_rate'], 1 ),
						)
					);
				}
				break;

			case 'employee_balances':
				$headers = array( 'Employee', 'Total Earnings', 'Total Paid', 'Balance Due' );
				fputcsv( $output, $headers );

				$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

				$data = $this->get_employee_balances_data( $employee_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['name'],
							$row['earnings'],
							$row['paid'],
							$row['balance'],
						)
					);
				}
				break;

			case 'budget_vs_actual':
				$headers = array( 'Site', 'Budget', 'Actual Cost', 'Variance', 'Status' );
				fputcsv( $output, $headers );

				$site_id = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;

				$data = $this->get_budget_vs_actual_data( $site_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['site_name'],
							$row['budget'],
							$row['actual'],
							$row['variance'],
							ucfirst( $row['status'] ),
						)
					);
				}
				break;

			case 'overtime_analysis':
				$headers = array( 'Employee', 'Regular Hours', 'Overtime Hours', 'Overtime Cost', 'OT % of Total' );
				fputcsv( $output, $headers );

				$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
				$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-t' );
				$site_id     = isset( $_GET['site_id'] ) ? absint( $_GET['site_id'] ) : 0;
				$employee_id = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;

				$data = $this->get_overtime_analysis_data( $start_date, $end_date, $site_id, $employee_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['name'],
							$row['regular_hours'],
							$row['overtime_hours'],
							$row['overtime_cost'],
							number_format( $row['ot_percentage'], 1 ),
						)
					);
				}
				break;

			case 'revenue_trends':
				$headers = array( 'Month', 'Revenue', 'Growth (%)' );
				fputcsv( $output, $headers );

				$year      = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : date( 'Y' );
				$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

				$data = $this->get_revenue_trends_data( $year, $client_id );

				foreach ( $data['rows'] as $row ) {
					fputcsv(
						$output,
						array(
							$row['month'],
							$row['revenue'],
							number_format( $row['growth'], 1 ),
						)
					);
				}
				break;
		}

		fclose( $output );
		exit;
	}
}

