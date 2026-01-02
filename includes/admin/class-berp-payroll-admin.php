<?php
/**
 * Payroll Admin - Bulk Processing
 *
 * Handles bulk payroll generation interface and processing.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Payroll_Admin Class
 *
 * Manages bulk payroll processing:
 * - Bulk generation interface
 * - Employee selection
 * - Preview calculations
 * - Batch processing
 */
class BERP_Payroll_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add admin submenu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=berp_payroll',
			__( 'Process Bulk Payroll', 'builderp' ),
			__( 'Bulk Process', 'builderp' ),
			'manage_options', // Use admin capability as fallback
			'berp-process-payroll',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=berp_payroll',
			__( 'Bulk Pay Salaries', 'builderp' ),
			__( 'Bulk Pay', 'builderp' ),
			'manage_options',
			'berp-bulk-pay-salaries',
			array( $this, 'render_bulk_pay_page' )
		);

		add_submenu_page(
			'edit.php?post_type=berp_payroll',
			__( 'Payroll Register', 'builderp' ),
			__( 'Payroll Register', 'builderp' ),
			'manage_options',
			'berp-payroll-register',
			array( $this, 'render_register_report' )
		);

		// Add helpful notice to payroll list page
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Show helpful notice on payroll list page
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( $screen && $screen->id === 'edit-berp_payroll' ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Quick Actions:', 'builderp' ); ?></strong>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=berp_payroll&page=berp-process-payroll' ) ); ?>" class="button button-small button-primary">
						<?php esc_html_e( 'Bulk Process Payroll', 'builderp' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=berp_payroll&page=berp-bulk-pay-salaries' ) ); ?>" class="button button-small button-secondary">
						<?php esc_html_e( 'Bulk Pay Salaries', 'builderp' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type, $pagenow;

		// Load on bulk processing page, bulk pay page, OR payroll edit pages
		// Note: Hook can be 'berp_payroll_page_berp-process-payroll' or 'admin_page_berp-process-payroll'
		$is_bulk_page     = ( strpos( $hook, 'berp-process-payroll' ) !== false );
		$is_bulk_pay_page = ( strpos( $hook, 'berp-bulk-pay-salaries' ) !== false );
		$is_payroll_edit  = in_array( $pagenow, array( 'post.php', 'post-new.php' ) )
							&& $post_type === 'berp_payroll';

		if ( ! $is_bulk_page && ! $is_bulk_pay_page && ! $is_payroll_edit ) {
			return;
		}

		wp_enqueue_style(
			'berp-payroll-admin',
			BERP_PLUGIN_URL . 'assets/css/payroll-admin.css',
			array(),
			BERP_VERSION
		);

		wp_enqueue_script(
			'berp-payroll-admin',
			BERP_PLUGIN_URL . 'assets/js/payroll-admin.js',
			array( 'jquery' ),
			BERP_VERSION,
			true
		);

		// Get currency settings
		$general_settings  = berp_get_general_settings();
		$currency_symbol   = berp_get_currency_symbol();
		$currency_position = isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before';

		wp_localize_script(
			'berp-payroll-admin',
			'berpPayroll',
			array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'berp_payroll_actions' ),
				'restNonce'        => wp_create_nonce( 'wp_rest' ),
				'restUrl'          => rest_url( 'berp/v1/' ),
				'context'          => $is_bulk_page ? 'bulk' : 'edit',
				'currencySymbol'   => $currency_symbol,
				'currencyPosition' => $currency_position,
				'strings'          => array(
					'calculating'     => __( 'Calculating...', 'builderp' ),
					'processing'      => __( 'Processing payroll...', 'builderp' ),
					'complete'        => __( 'Complete!', 'builderp' ),
					'error'           => __( 'Error occurred', 'builderp' ),
					'confirmGenerate' => __( 'Generate payroll for selected employees?', 'builderp' ),
					'markPaidConfirm' => __( 'Mark this payroll as paid? This will create an expense record.', 'builderp' ),
					'markPaidSuccess' => __( 'Payroll marked as paid successfully', 'builderp' ),
					'markPaidError'   => __( 'Failed to mark as paid', 'builderp' ),
				),
			)
		);
	}

	/**
	 * Render bulk processing page
	 */
	public function render_page() {
		// Check permission
		if ( ! current_user_can( 'berp_process_payroll' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'builderp' ) );
		}

		// Get all active employees
		// Include employees with 'active' status OR those without a status set (defaulted to active)
		$employees = get_posts(
			array(
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
			)
		);

		// Default month (previous month)
		$default_month = gmdate( 'Y-m', strtotime( '-1 month' ) );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bulk Payroll Processing', 'builderp' ); ?></h1>
			<p><?php esc_html_e( 'Generate payroll for multiple employees at once. Select a month, choose employees, and preview calculations before generating.', 'builderp' ); ?></p>

			<div class="berp-bulk-payroll-form">
				<form id="berp-bulk-payroll-form">
					<?php wp_nonce_field( 'berp_bulk_payroll', 'berp_bulk_payroll_nonce' ); ?>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="payroll_month"><?php esc_html_e( 'Payroll Month', 'builderp' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="month" name="payroll_month" id="payroll_month" value="<?php echo esc_attr( $default_month ); ?>" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'Select the month for payroll processing', 'builderp' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Employee Selection', 'builderp' ); ?></label>
								</th>
								<td>
									<fieldset>
										<label>
											<input type="radio" name="employee_mode" value="all" checked>
											<?php
											/* translators: %d: number of active employees */
											printf(
												esc_html__( 'All Active Employees (%d)', 'builderp' ),
												count( $employees )
											);
											?>
										</label>
										<br>
										<label>
											<input type="radio" name="employee_mode" value="selected">
											<?php esc_html_e( 'Selected Employees Only', 'builderp' ); ?>
										</label>
									</fieldset>

									<div id="employee-select-container" style="display: none; margin-top: 10px;">
										<select name="employee_ids[]" id="employee_ids" multiple size="10" style="width: 400px;">
											<?php foreach ( $employees as $employee ) : ?>
												<option value="<?php echo esc_attr( $employee->ID ); ?>">
													<?php
													echo esc_html( $employee->post_title );
													$emp_id = get_post_meta( $employee->ID, '_berp_employee_id', true );
													if ( $emp_id ) {
														echo ' (' . esc_html( $emp_id ) . ')';
													}
													?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple employees', 'builderp' ); ?></p>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e( 'Processing Options', 'builderp' ); ?></label>
								</th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="auto_mark_paid" id="auto_mark_paid">
											<?php esc_html_e( 'Automatically mark as paid', 'builderp' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'Create expense records and update employee balances automatically', 'builderp' ); ?></p>
										<br>
										<label>
											<input type="checkbox" name="send_emails" id="send_emails">
											<?php esc_html_e( 'Send email notifications', 'builderp' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'Email salary slips to employees (requires valid email addresses)', 'builderp' ); ?></p>
									</fieldset>
								</td>
							</tr>
						</tbody>
					</table>

					<p class="submit">
						<button type="button" class="button button-secondary" id="berp-preview-payroll">
							<?php esc_html_e( 'Preview Calculations', 'builderp' ); ?>
						</button>
						<button type="button" class="button button-primary" id="berp-generate-payroll" disabled>
							<?php esc_html_e( 'Generate Payroll', 'builderp' ); ?>
						</button>
						<span class="spinner" style="float: none; margin-top: 0;"></span>
					</p>
				</form>

				<!-- Preview Results -->
				<div id="berp-payroll-preview" style="display: none; margin-top: 30px;">
					<h2><?php esc_html_e( 'Preview Results', 'builderp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Review calculations before generating payroll records', 'builderp' ); ?></p>
					<div id="berp-preview-content"></div>
				</div>

				<!-- Processing Results -->
				<div id="berp-payroll-results" style="display: none; margin-top: 30px;">
					<h2><?php esc_html_e( 'Processing Results', 'builderp' ); ?></h2>
					<div id="berp-results-content"></div>
				</div>
			</div>
		</div>

		<style>
			.berp-bulk-payroll-form {
				background: #fff;
				padding: 20px;
				margin-top: 20px;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				border: 1px solid #ccd0d4;
			}
			.berp-preview-table {
				width: 100%;
				margin-top: 15px;
			}
			.berp-preview-table th {
				text-align: left;
				padding: 8px;
				background: #f0f0f1;
				border: 1px solid #c3c4c7;
			}
			.berp-preview-table td {
				padding: 8px;
				border: 1px solid #c3c4c7;
			}
			.berp-preview-table .total-row {
				font-weight: bold;
				background: #f9f9f9;
			}
			.berp-results-summary {
				padding: 15px;
				margin-bottom: 20px;
				border-left: 4px solid #2271b1;
				background: #f0f6fc;
			}
			.berp-results-summary.error {
				border-left-color: #d63638;
				background: #fcf0f1;
			}
			.berp-results-summary.success {
				border-left-color: #00a32a;
				background: #f0fdf4;
			}
			.berp-progress-bar {
				width: 100%;
				height: 30px;
				background: #f0f0f1;
				border: 1px solid #c3c4c7;
				border-radius: 3px;
				overflow: hidden;
				margin: 10px 0;
			}
			.berp-progress-fill {
				height: 100%;
				background: #2271b1;
				transition: width 0.3s ease;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #fff;
				font-size: 12px;
				font-weight: 500;
			}
			.required {
				color: #d63638;
			}
		</style>
		<?php
	}

	/**
	 * Render payroll register report page.
	 */
	public function render_register_report() {
		if ( ! current_user_can( 'berp_view_payroll' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'builderp' ) );
		}

		$selected_month = isset( $_GET['payroll_month'] ) ? sanitize_text_field( wp_unslash( $_GET['payroll_month'] ) ) : gmdate( 'Y-m' );
		$report_data    = $this->get_register_data( $selected_month );

		if ( isset( $_GET['export'] ) ) {
			$export = sanitize_key( wp_unslash( $_GET['export'] ) );
			if ( 'csv' === $export ) {
				$this->export_csv( $report_data, $selected_month );
				exit;
			}
			if ( 'pdf' === $export ) {
				$this->export_pdf( $report_data, $selected_month );
				exit;
			}
		}

		include BERP_PLUGIN_DIR . 'templates/admin/payroll-register-report.php';
	}

	/**
	 * Build register data array.
	 *
	 * @param string $selected_month Month filter (YYYY-MM).
	 * @return array
	 */
	protected function get_register_data( $selected_month ) {
		$args = array(
			'post_type'      => 'berp_payroll',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value',
			'meta_key'       => '_berp_payroll_employee_id',
			'order'          => 'ASC',
			'meta_query'     => array(),
		);

		if ( ! empty( $selected_month ) ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_payroll_month',
				'value' => $selected_month,
			);
		}

		$query  = new WP_Query( $args );
		$rows   = array();
		$totals = array(
			'basic_salary'     => 0,
			'total_allowances' => 0,
			'present_days'     => 0,
			'overtime_hours'   => 0,
			'overtime_amount'  => 0,
			'gross_salary'     => 0,
			'total_deductions' => 0,
			'net_salary'       => 0,
		);

		foreach ( $query->posts as $post ) {
			$employee_id   = (int) get_post_meta( $post->ID, '_berp_payroll_employee_id', true );
			$employee_name = $employee_id ? get_the_title( $employee_id ) : '';
			$employee_code = $employee_id ? get_post_meta( $employee_id, '_berp_employee_id', true ) : '';
			$status        = get_post_meta( $post->ID, '_berp_payroll_status', true );
			$row           = array(
				'id'               => $post->ID,
				'employee_name'    => $employee_name,
				'employee_code'    => $employee_code,
				'basic_salary'     => (float) get_post_meta( $post->ID, '_berp_payroll_basic_salary', true ),
				'total_allowances' => (float) get_post_meta( $post->ID, '_berp_total_allowances', true ),
				'present_days'     => (int) get_post_meta( $post->ID, '_berp_present_days', true ),
				'overtime_hours'   => (float) get_post_meta( $post->ID, '_berp_overtime_hours', true ),
				'overtime_amount'  => (float) get_post_meta( $post->ID, '_berp_overtime_amount', true ),
				'gross_salary'     => (float) get_post_meta( $post->ID, '_berp_gross_salary', true ),
				'total_deductions' => (float) get_post_meta( $post->ID, '_berp_total_deductions', true ),
				'net_salary'       => (float) get_post_meta( $post->ID, '_berp_net_salary', true ),
				'status'           => ! empty( $status ) ? $status : 'pending',
			);

			$rows[] = $row;

			$totals['basic_salary']     += $row['basic_salary'];
			$totals['total_allowances'] += $row['total_allowances'];
			$totals['present_days']     += $row['present_days'];
			$totals['overtime_hours']   += $row['overtime_hours'];
			$totals['overtime_amount']  += $row['overtime_amount'];
			$totals['gross_salary']     += $row['gross_salary'];
			$totals['total_deductions'] += $row['total_deductions'];
			$totals['net_salary']       += $row['net_salary'];
		}

		return array(
			'rows'   => $rows,
			'totals' => $totals,
		);
	}

	/**
	 * Export payroll register to CSV.
	 *
	 * @param array  $report_data Report data.
	 * @param string $selected_month Month filter.
	 */
	protected function export_csv( $report_data, $selected_month ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="payroll-register-' . sanitize_file_name( $selected_month ) . '.csv"' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv(
			$output,
			array(
				__( 'Employee Name', 'builderp' ),
				__( 'Employee ID', 'builderp' ),
				__( 'Basic Salary', 'builderp' ),
				__( 'Total Allowances', 'builderp' ),
				__( 'Present Days', 'builderp' ),
				__( 'OT Hours', 'builderp' ),
				__( 'OT Amount', 'builderp' ),
				__( 'Gross Salary', 'builderp' ),
				__( 'Total Deductions', 'builderp' ),
				__( 'Net Salary', 'builderp' ),
				__( 'Status', 'builderp' ),
			)
		);

		foreach ( $report_data['rows'] as $row ) {
			fputcsv(
				$output,
				array(
					$row['employee_name'],
					$row['employee_code'],
					$row['basic_salary'],
					$row['total_allowances'],
					$row['present_days'],
					$row['overtime_hours'],
					$row['overtime_amount'],
					$row['gross_salary'],
					$row['total_deductions'],
					$row['net_salary'],
					$row['status'],
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Export payroll register to PDF.
	 *
	 * @param array  $report_data Report data.
	 * @param string $selected_month Month filter.
	 */
	protected function export_pdf( $report_data, $selected_month ) {
		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			$autoload = BERP_PLUGIN_DIR . 'vendor/autoload.php';
			if ( file_exists( $autoload ) ) {
				require_once $autoload;
			}
		}

		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			wp_die( esc_html__( 'PDF library is not available. Please install mpdf/mpdf.', 'builderp' ) );
		}

		try {
			$mpdf = new \Mpdf\Mpdf(
				array(
					'format'        => 'A4',
					'margin_left'   => 10,
					'margin_right'  => 10,
					'margin_top'    => 12,
					'margin_bottom' => 12,
				)
			);
		} catch ( \Mpdf\MpdfException $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}

		$mpdf->WriteHTML( $this->get_report_styles() );
		$mpdf->WriteHTML( $this->build_report_html( $report_data, $selected_month ) );

		$filename = 'payroll-register-' . sanitize_file_name( $selected_month ) . '.pdf';
		$mpdf->Output( $filename, \Mpdf\Output\Destination::DOWNLOAD );
	}

	/**
	 * Get PDF styles for register export.
	 *
	 * @return string
	 */
	protected function get_report_styles() {
		return '
        <style>
            body { font-family: sans-serif; font-size: 11px; color: #1d2327; }
            h1 { font-size: 16px; margin-bottom: 6px; }
            .meta { margin-bottom: 10px; color: #50575e; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 6px 8px; border: 1px solid #dcdcde; }
            th { background: #f0f6fc; color: #0a4b78; }
            tfoot td { font-weight: 700; background: #f6f7f7; }
            .num { text-align: right; }
            .status { text-transform: uppercase; font-weight: 700; }
        </style>';
	}

	/**
	 * Build HTML for register PDF.
	 *
	 * @param array  $report_data Report data.
	 * @param string $selected_month Month filter.
	 * @return string
	 */
	protected function build_report_html( $report_data, $selected_month ) {
		ob_start();
		?>
		<h1><?php esc_html_e( 'Payroll Register', 'builderp' ); ?></h1>
		<div class="meta">
			<strong><?php esc_html_e( 'Month:', 'builderp' ); ?></strong>
			<?php echo esc_html( $selected_month ? gmdate( 'F Y', strtotime( $selected_month . '-01' ) ) : __( 'All Months', 'builderp' ) ); ?>
		</div>
		<table>
			<thead>
				<tr>
					<th><?php esc_html_e( 'Employee Name', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Employee ID', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Basic Salary', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Allowances', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Present Days', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'OT Hours', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'OT Amount', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Gross', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Deductions', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Net', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Status', 'builderp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $report_data['rows'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['employee_name'] ); ?></td>
						<td><?php echo esc_html( $row['employee_code'] ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['basic_salary'] ) ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['total_allowances'] ) ); ?></td>
						<td class="num"><?php echo esc_html( $row['present_days'] ); ?></td>
						<td class="num"><?php echo esc_html( number_format( (float) $row['overtime_hours'], 2 ) ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['overtime_amount'] ) ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['gross_salary'] ) ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['total_deductions'] ) ); ?></td>
						<td class="num"><?php echo esc_html( berp_format_currency( $row['net_salary'] ) ); ?></td>
						<td class="status"><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2"><?php esc_html_e( 'Totals', 'builderp' ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['basic_salary'] ) ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['total_allowances'] ) ); ?></td>
					<td class="num"><?php echo esc_html( $report_data['totals']['present_days'] ); ?></td>
					<td class="num"><?php echo esc_html( number_format( (float) $report_data['totals']['overtime_hours'], 2 ) ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['overtime_amount'] ) ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['gross_salary'] ) ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['total_deductions'] ) ); ?></td>
					<td class="num"><?php echo esc_html( berp_format_currency( $report_data['totals']['net_salary'] ) ); ?></td>
					<td></td>
				</tr>
			</tfoot>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render bulk pay salaries page
	 */
	public function render_bulk_pay_page() {
		// Check permission
		if ( ! current_user_can( 'berp_process_payroll' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'builderp' ) );
		}

		// Default month (current month)
		$default_month = gmdate( 'Y-m' );

		// Handle form submission
		if ( isset( $_POST['berp_bulk_pay_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_bulk_pay_nonce'] ) ), 'berp_bulk_pay' ) ) {
			$this->process_bulk_payment();
		}

		// Get month from request or use default
		$selected_month = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : $default_month;

		// Get pending payrolls for selected month
		$pending_payrolls = $this->get_pending_payrolls( $selected_month );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bulk Pay Salaries', 'builderp' ); ?></h1>
			<p><?php esc_html_e( 'Pay multiple pending salaries at once. You can adjust the payment amount for each employee - underpayments will be added to balance, overpayments will be added to advance.', 'builderp' ); ?></p>

			<!-- Month Selection Form -->
			<div class="berp-bulk-pay-month-selector" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccd0d4;">
				<form method="get" style="display: flex; align-items: center; gap: 10px;">
					<input type="hidden" name="post_type" value="berp_payroll">
					<input type="hidden" name="page" value="berp-bulk-pay-salaries">
					<label for="month" style="font-weight: 600;">
						<?php esc_html_e( 'Select Month:', 'builderp' ); ?>
					</label>
					<input type="month" name="month" id="month" value="<?php echo esc_attr( $selected_month ); ?>" class="regular-text">
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Load Payrolls', 'builderp' ); ?>
					</button>
				</form>
			</div>

			<?php if ( empty( $pending_payrolls ) ) : ?>
				<div class="notice notice-info">
					<p>
						<?php
						printf(
							/* translators: %s: selected month */
							esc_html__( 'No pending payrolls found for %s.', 'builderp' ),
							esc_html( gmdate( 'F Y', strtotime( $selected_month . '-01' ) ) )
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<!-- Bulk Payment Form -->
				<form method="post" id="berp-bulk-pay-form" style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4;">
					<?php wp_nonce_field( 'berp_bulk_pay', 'berp_bulk_pay_nonce' ); ?>

					<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
						<h2 style="margin: 0;">
							<?php
							printf(
								/* translators: 1: month name, 2: count */
								esc_html__( 'Pending Payrolls - %1$s (%2$d employees)', 'builderp' ),
								esc_html( gmdate( 'F Y', strtotime( $selected_month . '-01' ) ) ),
								count( $pending_payrolls )
							);
							?>
						</h2>
						<div>
							<button type="button" id="berp-select-all-payrolls" class="button">
								<?php esc_html_e( 'Select All', 'builderp' ); ?>
							</button>
							<button type="button" id="berp-deselect-all-payrolls" class="button">
								<?php esc_html_e( 'Deselect All', 'builderp' ); ?>
							</button>
						</div>
					</div>

					<table class="wp-list-table widefat fixed striped" id="berp-bulk-pay-table">
						<thead>
							<tr>
								<th style="width: 40px;">
									<input type="checkbox" id="berp-select-all-checkbox">
								</th>
								<th><?php esc_html_e( 'Employee', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Employee ID', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Net Salary', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Current Balance', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Current Advance', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Payment Amount', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Difference', 'builderp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$total_net_salary = 0;
							$total_payment    = 0;
							foreach ( $pending_payrolls as $payroll ) :
								$employee_id   = $payroll['employee_id'];
								$employee_name = $payroll['employee_name'];
								$employee_code = $payroll['employee_code'];
								$net_salary    = $payroll['net_salary'];
								$balance       = $payroll['current_balance'];
								$advance       = $payroll['current_advance'];

								$total_net_salary += $net_salary;
								$total_payment    += $net_salary;
								?>
								<tr class="berp-payroll-row" data-payroll-id="<?php echo esc_attr( $payroll['payroll_id'] ); ?>" data-net-salary="<?php echo esc_attr( $net_salary ); ?>">
									<td>
										<input type="checkbox" name="payroll_ids[]" value="<?php echo esc_attr( $payroll['payroll_id'] ); ?>" class="berp-payroll-checkbox" checked>
									</td>
									<td>
										<strong><?php echo esc_html( $employee_name ); ?></strong>
									</td>
									<td><?php echo esc_html( $employee_code ? $employee_code : '-' ); ?></td>
									<td class="berp-net-salary">
										<?php echo esc_html( berp_format_currency( $net_salary ) ); ?>
									</td>
									<td class="berp-current-balance">
										<?php echo esc_html( berp_format_currency( $balance ) ); ?>
									</td>
									<td class="berp-current-advance">
										<?php echo esc_html( berp_format_currency( $advance ) ); ?>
									</td>
									<td>
										<input type="number"
											name="payment_amounts[<?php echo esc_attr( $payroll['payroll_id'] ); ?>]"
											class="berp-payment-amount regular-text"
											value="<?php echo esc_attr( number_format( $net_salary, 2, '.', '' ) ); ?>"
											step="0.01"
											min="0"
											data-net="<?php echo esc_attr( $net_salary ); ?>"
											style="width: 120px;">
									</td>
									<td class="berp-difference" style="font-weight: 600;">
										<span style="color: #666;">0.00</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr style="background: #f9f9f9; font-weight: 600;">
								<td colspan="3"><?php esc_html_e( 'Total', 'builderp' ); ?></td>
								<td id="berp-total-net-salary"><?php echo esc_html( berp_format_currency( $total_net_salary ) ); ?></td>
								<td colspan="2"></td>
								<td id="berp-total-payment"><?php echo esc_html( berp_format_currency( $total_payment ) ); ?></td>
								<td id="berp-total-difference">
									<span style="color: #666;">0.00</span>
								</td>
							</tr>
						</tfoot>
					</table>

					<div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1;">
						<p style="margin: 0 0 10px 0;"><strong><?php esc_html_e( 'Payment Notes:', 'builderp' ); ?></strong></p>
						<ul style="margin: 0; padding-left: 20px;">
							<li><?php esc_html_e( 'Payment = Net Salary: Normal payment (no balance/advance change)', 'builderp' ); ?></li>
							<li><?php esc_html_e( 'Payment < Net Salary: Difference added to employee balance (underpayment)', 'builderp' ); ?></li>
							<li><?php esc_html_e( 'Payment > Net Salary: Difference added to employee advance (overpayment)', 'builderp' ); ?></li>
						</ul>
					</div>

					<p class="submit" style="margin-top: 20px;">
						<button type="submit" class="button button-primary button-large" id="berp-submit-bulk-pay">
							<?php esc_html_e( 'Pay Selected Salaries', 'builderp' ); ?>
						</button>
						<span class="spinner" style="float: none; margin-top: 0;"></span>
					</p>
				</form>
			<?php endif; ?>
		</div>

		<style>
			.berp-difference.positive {
				color: #d63638 !important;
			}
			.berp-difference.negative {
				color: #00a32a !important;
			}
			#berp-bulk-pay-table input[type="number"] {
				text-align: right;
			}
			#berp-bulk-pay-table th {
				background: #f0f0f1;
			}
		</style>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Calculate difference when payment amount changes
			$('.berp-payment-amount').on('input change', function() {
				const $row = $(this).closest('tr');
				const netSalary = parseFloat($(this).data('net')) || 0;
				const paymentAmount = parseFloat($(this).val()) || 0;
				const difference = paymentAmount - netSalary;
				const $diffCell = $row.find('.berp-difference');

				if (difference === 0) {
					$diffCell.html('<span style="color: #666;">0.00</span>').removeClass('positive negative');
				} else if (difference > 0) {
					$diffCell.html('<span style="color: #d63638;">+' + Math.abs(difference).toFixed(2) + ' (Advance)</span>').removeClass('negative').addClass('positive');
				} else {
					$diffCell.html('<span style="color: #00a32a;">' + difference.toFixed(2) + ' (Balance)</span>').removeClass('positive').addClass('negative');
				}

				updateTotals();
			});

			// Update totals
			function updateTotals() {
				let totalPayment = 0;
				let totalDifference = 0;

				$('.berp-payroll-checkbox:checked').each(function() {
					const $row = $(this).closest('tr');
					const payment = parseFloat($row.find('.berp-payment-amount').val()) || 0;
					const netSalary = parseFloat($row.find('.berp-payment-amount').data('net')) || 0;
					totalPayment += payment;
					totalDifference += (payment - netSalary);
				});

				$('#berp-total-payment').text('<?php echo esc_js( berp_get_currency_symbol() ); ?>' + totalPayment.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));

				const $totalDiff = $('#berp-total-difference');
				if (totalDifference === 0) {
					$totalDiff.html('<span style="color: #666;">0.00</span>');
				} else if (totalDifference > 0) {
					$totalDiff.html('<span style="color: #d63638;">+' + totalDifference.toFixed(2) + '</span>');
				} else {
					$totalDiff.html('<span style="color: #00a32a;">' + totalDifference.toFixed(2) + '</span>');
				}
			}

			// Select all checkbox
			$('#berp-select-all-checkbox').on('change', function() {
				$('.berp-payroll-checkbox').prop('checked', $(this).is(':checked'));
				updateTotals();
			});

			// Individual checkbox change
			$('.berp-payroll-checkbox').on('change', function() {
				updateTotals();
			});

			// Select/Deselect buttons
			$('#berp-select-all-payrolls').on('click', function() {
				$('.berp-payroll-checkbox').prop('checked', true);
				$('#berp-select-all-checkbox').prop('checked', true);
				updateTotals();
			});

			$('#berp-deselect-all-payrolls').on('click', function() {
				$('.berp-payroll-checkbox').prop('checked', false);
				$('#berp-select-all-checkbox').prop('checked', false);
				updateTotals();
			});

			// Form submission
			$('#berp-bulk-pay-form').on('submit', function(e) {
				const checked = $('.berp-payroll-checkbox:checked').length;
				if (checked === 0) {
					e.preventDefault();
					alert('<?php echo esc_js( __( 'Please select at least one payroll to pay.', 'builderp' ) ); ?>');
					return false;
				}

				if (!confirm('<?php echo esc_js( __( 'Are you sure you want to pay the selected salaries? This will create expense records and update employee balances/advances.', 'builderp' ) ); ?>')) {
					e.preventDefault();
					return false;
				}

				$('#berp-submit-bulk-pay').prop('disabled', true);
				$('.spinner').addClass('is-active');
			});
		});
		</script>
		<?php
	}

	/**
	 * Get pending payrolls for a specific month
	 *
	 * @param string $month Month (YYYY-MM)
	 * @return array
	 */
	protected function get_pending_payrolls( $month ) {
		$args = array(
			'post_type'      => 'berp_payroll',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_berp_payroll_employee_id',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_berp_payroll_month',
					'value' => $month,
				),
				array(
					'key'   => '_berp_payroll_status',
					'value' => 'pending',
				),
			),
		);

		$query   = new WP_Query( $args );
		$results = array();

		foreach ( $query->posts as $post ) {
			$employee_id = (int) get_post_meta( $post->ID, '_berp_payroll_employee_id', true );
			$net_salary  = (float) get_post_meta( $post->ID, '_berp_net_salary', true );
			$balance     = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
			$advance     = (float) get_post_meta( $employee_id, '_berp_advance_amount', true );

			$results[] = array(
				'payroll_id'      => $post->ID,
				'employee_id'     => $employee_id,
				'employee_name'   => get_the_title( $employee_id ),
				'employee_code'   => get_post_meta( $employee_id, '_berp_employee_id', true ),
				'net_salary'      => $net_salary,
				'current_balance' => $balance,
				'current_advance' => $advance,
			);
		}

		return $results;
	}

	/**
	 * Process bulk payment submission
	 */
	protected function process_bulk_payment() {
		// Verify nonce already checked in render method

		if ( ! isset( $_POST['payroll_ids'] ) || ! is_array( $_POST['payroll_ids'] ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'No payrolls selected.', 'builderp' ); ?></p>
					</div>
					<?php
				}
			);
			return;
		}

		$payroll_ids     = array_map( 'absint', wp_unslash( $_POST['payroll_ids'] ) );
		$payment_amounts = isset( $_POST['payment_amounts'] ) ? array_map( 'floatval', wp_unslash( $_POST['payment_amounts'] ) ) : array();
		$success_count   = 0;
		$error_count     = 0;
		$errors          = array();

		foreach ( $payroll_ids as $payroll_id ) {
			$payment_amount = isset( $payment_amounts[ $payroll_id ] ) ? $payment_amounts[ $payroll_id ] : 0;

			if ( $payment_amount <= 0 ) {
				++$error_count;
				continue;
			}

			// Get payroll data
			$employee_id = get_post_meta( $payroll_id, '_berp_payroll_employee_id', true );
			$month       = get_post_meta( $payroll_id, '_berp_payroll_month', true );
			$net_salary  = (float) get_post_meta( $payroll_id, '_berp_net_salary', true );
			$status      = get_post_meta( $payroll_id, '_berp_payroll_status', true );

			// Validate
			if ( 'paid' === $status ) {
				++$error_count;
				$errors[] = get_the_title( $employee_id ) . ': ' . __( 'Already paid', 'builderp' );
				continue;
			}

			// Calculate difference
			$difference = $payment_amount - $net_salary;

			// Create expense record
			$employee_name = get_the_title( $employee_id );
			/* translators: 1: employee name, 2: payroll month */
			$expense_title = sprintf( __( 'Payroll - %1$s - %2$s', 'builderp' ), $employee_name, gmdate( 'F Y', strtotime( $month . '-01' ) ) );

			$expense_id = wp_insert_post(
				array(
					'post_type'   => 'berp_expense',
					'post_title'  => $expense_title,
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				)
			);

			if ( is_wp_error( $expense_id ) ) {
				++$error_count;
				$errors[] = $employee_name . ': ' . $expense_id->get_error_message();
				continue;
			}

			// Save expense meta
			update_post_meta( $expense_id, '_berp_expense_date', current_time( 'Y-m-d' ) );
			update_post_meta( $expense_id, '_berp_expense_amount', $payment_amount );
			update_post_meta( $expense_id, '_berp_expense_category', 'payroll' );
			update_post_meta( $expense_id, '_berp_payment_method', 'bank_transfer' );
			update_post_meta( $expense_id, '_berp_linked_payroll_id', $payroll_id );
			update_post_meta( $expense_id, '_berp_linked_employee_id', $employee_id );

			// Update payroll status
			update_post_meta( $payroll_id, '_berp_payroll_status', 'paid' );
			update_post_meta( $payroll_id, '_berp_paid_date', current_time( 'Y-m-d' ) );
			update_post_meta( $payroll_id, '_berp_linked_expense_id', $expense_id );
			update_post_meta( $payroll_id, '_berp_actual_payment', $payment_amount );

			// Handle balance/advance adjustments
			// Account Balance: Positive = Company owes employee, Negative = Employee owes company
			$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );

			// difference = payment_amount - net_salary
			// positive difference = overpayment (paid more than owed)
			// negative difference = underpayment (paid less than owed)

			if ( $difference < 0 ) {
				// Underpayment - company owes employee more
				// Add the unpaid amount to balance (company owes this)
				$new_balance = $current_balance + abs( $difference );
				update_post_meta( $employee_id, '_berp_account_balance', $new_balance );
			} elseif ( $difference > 0 ) {
				// Overpayment - paid more than net salary
				// First, check if company already owes employee (positive balance)
				$actual_advance_amount = 0;

				if ( $current_balance > 0 ) {
					// Company owes employee money - first pay off this debt
					if ( $difference <= $current_balance ) {
						// Extra payment is less than or equal to what company owes
						// Just reduce the company's debt, no advance needed
						$new_balance = $current_balance - $difference;
						update_post_meta( $employee_id, '_berp_account_balance', $new_balance );
						// No advance created - company was just paying what it owed
					} else {
						// Extra payment exceeds what company owes
						// Pay off the debt first, then create advance for the rest
						$actual_advance_amount = $difference - $current_balance;
						// Clear the balance (company no longer owes anything)
						update_post_meta( $employee_id, '_berp_account_balance', 0 );
					}
				} else {
					// Balance is zero or negative (employee already owes company)
					// Full extra payment becomes an advance
					$actual_advance_amount = $difference;
					// Balance stays as is (or could subtract if employee owes)
				}

				// Only create advance if there's actual advance amount
				if ( $actual_advance_amount > 0 ) {
					// Get advance settings
					$all_settings           = get_option( 'berp_settings', array() );
					$advance_settings       = isset( $all_settings['advance'] ) ? $all_settings['advance'] : array();
					$default_repayment_type = isset( $advance_settings['default_repayment_type'] ) ? $advance_settings['default_repayment_type'] : 'installments';
					$installment_threshold  = isset( $advance_settings['installment_threshold'] ) ? floatval( $advance_settings['installment_threshold'] ) : 500;
					$installments_high      = isset( $advance_settings['installments_high'] ) ? absint( $advance_settings['installments_high'] ) : 5;
					$installments_low       = isset( $advance_settings['installments_low'] ) ? absint( $advance_settings['installments_low'] ) : 2;

					// Determine number of installments based on amount
					$num_installments = $actual_advance_amount > $installment_threshold ? $installments_high : $installments_low;

					// Create berp_advance post for tracking
					$advance_title = sprintf(
						/* translators: 1: Employee name */
						__( 'Salary Advance - %s', 'builderp' ),
						$employee_name
					);

					$advance_id = wp_insert_post(
						array(
							'post_type'   => 'berp_advance',
							'post_title'  => $advance_title,
							'post_status' => 'publish',
							'post_author' => get_current_user_id(),
						)
					);

					if ( ! is_wp_error( $advance_id ) && $advance_id ) {
						// Calculate installment amount
						$installment_amount = $actual_advance_amount / $num_installments;

						// Save advance meta - use correct meta keys matching advance CPT
						update_post_meta( $advance_id, '_berp_employee_id', $employee_id );
						update_post_meta( $advance_id, '_berp_advance_amount', $actual_advance_amount );
						update_post_meta( $advance_id, '_berp_remaining_amount', $actual_advance_amount );
						update_post_meta( $advance_id, '_berp_installment_amount', $installment_amount );
						update_post_meta( $advance_id, '_berp_request_date', current_time( 'Y-m-d' ) );
						update_post_meta( $advance_id, '_berp_advance_status', 'active' );
						update_post_meta( $advance_id, '_berp_repayment_type', $default_repayment_type );
						update_post_meta( $advance_id, '_berp_installments', $num_installments );
						update_post_meta( $advance_id, '_berp_installments_paid', 0 );
						update_post_meta(
							$advance_id,
							'_berp_advance_reason',
							sprintf(
								/* translators: 1: Month, 2: Net salary, 3: Actual payment */
								__( 'Overpayment in %1$s payroll. Net Salary: %2$s, Paid: %3$s', 'builderp' ),
								gmdate( 'F Y', strtotime( $month . '-01' ) ),
								berp_format_currency( $net_salary ),
								berp_format_currency( $payment_amount )
							)
						);
						update_post_meta( $advance_id, '_berp_linked_payroll_id', $payroll_id );

						// Link advance to the main payroll expense (not creating separate expense)
						update_post_meta( $advance_id, '_berp_expense_id', $expense_id );
						update_post_meta( $expense_id, '_berp_linked_advance_id', $advance_id );

						// Add deduction to employee for advance repayment
						$deductions = get_post_meta( $employee_id, '_berp_deductions', true );
						if ( ! is_array( $deductions ) ) {
							$deductions = array();
						}

						// Add new advance deduction entry
						$deductions[] = array(
							'label'      => sprintf(
								/* translators: %d: Advance ID */
								__( 'Advance Repayment #%d', 'builderp' ),
								$advance_id
							),
							'amount'     => $installment_amount,
							'advance_id' => $advance_id,
							'type'       => 'advance_repayment',
						);

						update_post_meta( $employee_id, '_berp_deductions', $deductions );

						// Trigger hook
						do_action( 'berp_advance_created', $advance_id, $employee_id );
					}
				}
			}

			// Trigger action hook
			do_action( 'berp_after_payroll_paid', $payroll_id, $employee_id, $expense_id );

			++$success_count;
		}

		// Show success/error notice
		add_action(
			'admin_notices',
			function () use ( $success_count, $error_count, $errors ) {
				if ( $success_count > 0 ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							printf(
								/* translators: %d: number of payrolls */
								esc_html( _n( '%d salary paid successfully.', '%d salaries paid successfully.', $success_count, 'builderp' ) ),
								esc_html( $success_count )
							);
							?>
						</p>
					</div>
					<?php
				}

				if ( $error_count > 0 ) {
					?>
					<div class="notice notice-error is-dismissible">
						<p>
							<?php
							printf(
								/* translators: %d: number of errors */
								esc_html( _n( '%d error occurred.', '%d errors occurred.', $error_count, 'builderp' ) ),
								esc_html( $error_count )
							);
							?>
						</p>
						<?php if ( ! empty( $errors ) ) : ?>
							<ul>
								<?php foreach ( $errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php
				}
			}
		);
	}
}


