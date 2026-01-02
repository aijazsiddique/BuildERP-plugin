<?php
/**
 * Salary Slip PDF Generator.
 *
 * Generates salary slip PDFs for payroll records using mPDF.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/pdf
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Salary_Slip_PDF Class
 *
 * Handles PDF generation for payroll salary slips.
 */
class BERP_Salary_Slip_PDF {

	/**
	 * Generate salary slip PDF.
	 *
	 * @param int    $payroll_id Payroll post ID.
	 * @param string $output     Output mode: download|save|string|inline.
	 *
	 * @return string|bool|WP_Error File path, PDF string, true on direct output, or WP_Error.
	 */
	public function generate( $payroll_id, $output = 'download' ) {
		$data = $this->get_payroll_data( $payroll_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$mpdf = $this->get_mpdf_instance();
		if ( is_wp_error( $mpdf ) ) {
			return $mpdf;
		}

		$filename = sprintf(
			'salary-slip-%s-%s.pdf',
			sanitize_title( $data['employee_name'] ),
			$data['month']
		);

		/* translators: %s: payroll month */
		$mpdf->SetTitle( sprintf( __( 'Salary Slip - %s', 'builderp' ), $data['month_name'] ) );
		$mpdf->WriteHTML( $this->get_styles() );
		$mpdf->WriteHTML( $this->build_html( $data ) );

		switch ( $output ) {
			case 'string':
				return $mpdf->Output( $filename, \Mpdf\Output\Destination::STRING_RETURN );
			case 'save':
				$path = $this->get_upload_path( $data['year'], $data['month_num'] );
				if ( is_wp_error( $path ) ) {
					return $path;
				}
				$file_path = trailingslashit( $path ) . $filename;
				$mpdf->Output( $file_path, \Mpdf\Output\Destination::FILE );
				return $file_path;
			case 'inline':
				$mpdf->Output( $filename, \Mpdf\Output\Destination::INLINE );
				return true;
			case 'download':
			default:
				$mpdf->Output( $filename, \Mpdf\Output\Destination::DOWNLOAD );
				return true;
		}
	}

	/**
	 * Get payroll data for PDF.
	 *
	 * @param int $payroll_id Payroll post ID.
	 * @return array|WP_Error
	 */
	protected function get_payroll_data( $payroll_id ) {
		$payroll = get_post( $payroll_id );
		if ( ! $payroll || 'berp_payroll' !== $payroll->post_type ) {
			return new WP_Error(
				'berp_invalid_payroll',
				__( 'Invalid payroll record.', 'builderp' )
			);
		}

		$employee_id = get_post_meta( $payroll_id, '_berp_payroll_employee_id', true );
		if ( empty( $employee_id ) ) {
			return new WP_Error(
				'berp_payroll_missing_employee',
				__( 'Employee not found for this payroll.', 'builderp' )
			);
		}

		$month         = get_post_meta( $payroll_id, '_berp_payroll_month', true );
		$month_ts      = $month ? strtotime( $month . '-01' ) : time();
		$month_name    = gmdate( 'F Y', $month_ts );
		$month_num     = gmdate( 'm', $month_ts );
		$year          = gmdate( 'Y', $month_ts );
		$general       = berp_get_general_settings();
		$employee_name = get_the_title( $employee_id );
		$employee_code = get_post_meta( $employee_id, '_berp_employee_id', true );
		$paid_date     = get_post_meta( $payroll_id, '_berp_paid_date', true );
		$formula_used  = get_post_meta( $payroll_id, '_berp_formula_used', true );

		$data = array(
			'payroll_id'       => $payroll_id,
			'employee_id'      => $employee_id,
			'employee_name'    => $employee_name,
			'employee_code'    => $employee_code,
			'month'            => $month,
			'month_name'       => $month_name,
			'month_num'        => $month_num,
			'year'             => $year,
			'paid_date'        => $paid_date,
			'basic_salary'     => (float) get_post_meta( $payroll_id, '_berp_payroll_basic_salary', true ),
			'allowances'       => get_post_meta( $payroll_id, '_berp_payroll_allowances', true ),
			'deductions'       => get_post_meta( $payroll_id, '_berp_payroll_deductions', true ),
			'total_allowances' => (float) get_post_meta( $payroll_id, '_berp_total_allowances', true ),
			'total_deductions' => (float) get_post_meta( $payroll_id, '_berp_total_deductions', true ),
			'gross_salary'     => (float) get_post_meta( $payroll_id, '_berp_gross_salary', true ),
			'net_salary'       => (float) get_post_meta( $payroll_id, '_berp_net_salary', true ),
			'present_days'     => (int) get_post_meta( $payroll_id, '_berp_present_days', true ),
			'paid_weekends'    => (int) get_post_meta( $payroll_id, '_berp_paid_weekends', true ),
			'holidays'         => (int) get_post_meta( $payroll_id, '_berp_holidays', true ),
			'total_paid_days'  => (int) get_post_meta( $payroll_id, '_berp_total_paid_days', true ),
			'overtime_hours'   => (float) get_post_meta( $payroll_id, '_berp_overtime_hours', true ),
			'overtime_amount'  => (float) get_post_meta( $payroll_id, '_berp_overtime_amount', true ),
			'company'          => array(
				'name'    => isset( $general['company_name'] ) ? $general['company_name'] : get_bloginfo( 'name' ),
				'email'   => isset( $general['company_email'] ) ? $general['company_email'] : get_option( 'admin_email' ),
				'phone'   => isset( $general['company_phone'] ) ? $general['company_phone'] : '',
				'address' => isset( $general['company_address'] ) ? $general['company_address'] : '',
				'logo'    => isset( $general['company_logo'] ) ? $general['company_logo'] : '',
			),
			'formula_used'     => $formula_used,
		);

		$data['allowances'] = is_array( $data['allowances'] ) ? $data['allowances'] : array();
		$data['deductions'] = is_array( $data['deductions'] ) ? $data['deductions'] : array();

		return $data;
	}

	/**
	 * Build PDF HTML.
	 *
	 * @param array $data Payroll data.
	 * @return string
	 */
	protected function build_html( $data ) {
		ob_start();
		?>
		<div class="slip-container">
			<!-- Header Section -->
			<table class="header-table">
				<tr>
					<td class="company-info">
						<?php if ( ! empty( $data['company']['logo'] ) ) : ?>
							<img src="<?php echo esc_url( $data['company']['logo'] ); ?>" class="company-logo" />
						<?php endif; ?>
						<h1 class="company-name"><?php echo esc_html( $data['company']['name'] ); ?></h1>
						<div class="company-details">
							<?php echo esc_html( $data['company']['address'] ); ?><br>
							<?php if ( ! empty( $data['company']['phone'] ) ) : ?>
								<?php echo esc_html( $data['company']['phone'] ); ?> |
							<?php endif; ?>
							<?php if ( ! empty( $data['company']['email'] ) ) : ?>
								<?php echo esc_html( $data['company']['email'] ); ?>
							<?php endif; ?>
						</div>
					</td>
					<td class="slip-title">
						<h2><?php esc_html_e( 'SALARY SLIP', 'builderp' ); ?></h2>
						<div class="period"><?php echo esc_html( $data['month_name'] ); ?></div>
					</td>
				</tr>
			</table>

			<!-- Employee & Payment Info -->
			<table class="info-table">
				<tr>
					<td width="50%" class="info-box">
						<h3><?php esc_html_e( 'Employee Details', 'builderp' ); ?></h3>
						<table class="details-table">
							<tr>
								<th><?php esc_html_e( 'Name', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( $data['employee_name'] ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'ID', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( $data['employee_code'] ? $data['employee_code'] : '-' ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Designation', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( $data['designation'] ?? '-' ); ?></td>
							</tr>
						</table>
					</td>
					<td width="50%" class="info-box">
						<h3><?php esc_html_e( 'Payment Details', 'builderp' ); ?></h3>
						<table class="details-table">
							<tr>
								<th><?php esc_html_e( 'Pay Period', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( $data['month_name'] ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Paid Date', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( ! empty( $data['paid_date'] ) ? berp_format_date( $data['paid_date'] ) : __( 'Pending', 'builderp' ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Total Paid Days', 'builderp' ); ?>:</th>
								<td><?php echo esc_html( $data['total_paid_days'] ); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<!-- Attendance Summary -->
			<div class="section-title"><?php esc_html_e( 'Attendance Summary', 'builderp' ); ?></div>
			<table class="attendance-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Present', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Weekends', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Holidays', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Total Days', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Overtime (Hrs)', 'builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo esc_html( $data['present_days'] ); ?></td>
						<td><?php echo esc_html( $data['paid_weekends'] ); ?></td>
						<td><?php echo esc_html( $data['holidays'] ); ?></td>
						<td><?php echo esc_html( $data['total_paid_days'] ); ?></td>
						<td><?php echo esc_html( number_format( (float) $data['overtime_hours'], 2 ) ); ?></td>
					</tr>
				</tbody>
			</table>

			<!-- Earnings & Deductions -->
			<table class="financials-table">
				<tr>
					<td class="earnings-col">
						<div class="col-header"><?php esc_html_e( 'Earnings', 'builderp' ); ?></div>
						<table class="line-items">
							<tr>
								<td><?php esc_html_e( 'Basic Salary', 'builderp' ); ?></td>
								<td class="amount"><?php echo esc_html( berp_format_currency( $data['basic_salary'] ) ); ?></td>
							</tr>
							<?php foreach ( $data['allowances'] as $allowance ) : ?>
								<tr>
									<td><?php echo esc_html( $allowance['label'] ?? __( 'Allowance', 'builderp' ) ); ?></td>
									<td class="amount"><?php echo esc_html( berp_format_currency( $allowance['amount'] ?? 0 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if ( $data['overtime_amount'] > 0 ) : ?>
							<tr>
								<td><?php esc_html_e( 'Overtime', 'builderp' ); ?></td>
								<td class="amount"><?php echo esc_html( berp_format_currency( $data['overtime_amount'] ) ); ?></td>
							</tr>
							<?php endif; ?>
						</table>
					</td>
					<td class="deductions-col">
						<div class="col-header"><?php esc_html_e( 'Deductions', 'builderp' ); ?></div>
						<table class="line-items">
							<?php if ( ! empty( $data['deductions'] ) ) : ?>
								<?php foreach ( $data['deductions'] as $deduction ) : ?>
									<tr>
										<td><?php echo esc_html( $deduction['label'] ?? __( 'Deduction', 'builderp' ) ); ?></td>
										<td class="amount"><?php echo esc_html( berp_format_currency( $deduction['amount'] ?? 0 ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="2" class="empty-message"><?php esc_html_e( 'No deductions', 'builderp' ); ?></td>
								</tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
				<tr class="totals-row">
					<td class="earnings-total">
						<span><?php esc_html_e( 'Total Earnings', 'builderp' ); ?></span>
						<span class="amount"><?php echo esc_html( berp_format_currency( $data['gross_salary'] ) ); ?></span>
					</td>
					<td class="deductions-total">
						<span><?php esc_html_e( 'Total Deductions', 'builderp' ); ?></span>
						<span class="amount"><?php echo esc_html( berp_format_currency( $data['total_deductions'] ) ); ?></span>
					</td>
				</tr>
			</table>

			<!-- Net Pay -->
			<div class="net-pay-section">
				<div class="net-pay-label"><?php esc_html_e( 'Net Salary Payable', 'builderp' ); ?></div>
				<div class="net-pay-amount"><?php echo esc_html( berp_format_currency( $data['net_salary'] ) ); ?></div>
			</div>

			<!-- Footer / Signatures -->
			<table class="signatures-table">
				<tr>
					<td>
						<div class="signature-line"></div>
						<?php esc_html_e( 'Employee Signature', 'builderp' ); ?>
					</td>
					<td>
						<div class="signature-line"></div>
						<?php esc_html_e( 'Employer Signature', 'builderp' ); ?>
					</td>
				</tr>
			</table>
			
			<div class="footer-note">
				<?php
				/* translators: %s: generated date */
				printf( esc_html__( 'Generated on %s', 'builderp' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) );
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Return style block for PDF.
	 *
	 * @return string
	 */
	protected function get_styles() {
		return '
		<style>
			body { font-family: sans-serif; color: #333; font-size: 10pt; line-height: 1.4; }
			.slip-container { padding: 0; }
			
			/* Header */
			.header-table { width: 100%; border-bottom: 2px solid #0a4b78; padding-bottom: 15px; margin-bottom: 20px; }
			.company-info { vertical-align: top; }
			.company-logo { max-height: 50px; margin-bottom: 5px; }
			.company-name { font-size: 16pt; font-weight: bold; color: #0a4b78; margin: 0; }
			.company-details { font-size: 9pt; color: #555; }
			.slip-title { text-align: right; vertical-align: top; }
			.slip-title h2 { font-size: 18pt; color: #0a4b78; margin: 0; text-transform: uppercase; }
			.slip-title .period { font-size: 11pt; color: #555; font-weight: bold; margin-top: 5px; }

			/* Info Tables */
			.info-table { width: 100%; margin-bottom: 20px; }
			.info-box { vertical-align: top; padding-right: 10px; }
			.info-box h3 { font-size: 11pt; color: #0a4b78; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px; margin-top: 0; }
			.details-table { width: 100%; font-size: 9pt; }
			.details-table th { text-align: left; width: 35%; color: #555; font-weight: normal; padding: 2px 0; }
			.details-table td { font-weight: bold; color: #333; padding: 2px 0; }

			/* Attendance */
			.section-title { font-size: 11pt; font-weight: bold; color: #0a4b78; margin-bottom: 8px; }
			.attendance-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
			.attendance-table th { background-color: #f0f6fc; color: #0a4b78; font-weight: bold; padding: 8px; border: 1px solid #e0e0e0; font-size: 9pt; text-align: center; }
			.attendance-table td { padding: 8px; border: 1px solid #e0e0e0; text-align: center; font-size: 9pt; }

			/* Financials */
			.financials-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
			.financials-table td { vertical-align: top; padding: 0; }
			.earnings-col { padding-right: 10px; border-right: 1px solid #eee; }
			.deductions-col { padding-left: 10px; }
			
			.col-header { background-color: #f0f6fc; color: #0a4b78; font-weight: bold; padding: 8px; margin-bottom: 5px; border-radius: 3px; }
			.line-items { width: 100%; font-size: 9pt; }
			.line-items td { padding: 5px 2px; border-bottom: 1px solid #f0f0f0; }
			.line-items .amount { text-align: right; font-weight: bold; }
			.empty-message { color: #999; font-style: italic; text-align: center; padding: 10px; }

			.totals-row td { padding-top: 10px; }
			.earnings-total, .deductions-total { background-color: #f9f9f9; padding: 8px !important; font-weight: bold; border-radius: 3px; }
			.earnings-total { margin-right: 10px; color: #2e7d32; }
			.deductions-total { margin-left: 10px; color: #c62828; }
			.earnings-total span, .deductions-total span { display: inline-block; }
			.earnings-total .amount, .deductions-total .amount { float: right; }

			/* Net Pay */
			.net-pay-section { background-color: #0a4b78; color: #fff; padding: 15px; text-align: center; border-radius: 4px; margin-bottom: 30px; }
			.net-pay-label { font-size: 10pt; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 5px; }
			.net-pay-amount { font-size: 20pt; font-weight: bold; }
			.amount-words { font-size: 9pt; font-style: italic; opacity: 0.8; margin-top: 5px; }

			/* Signatures */
			.signatures-table { width: 100%; margin-top: 40px; }
			.signatures-table td { width: 50%; text-align: center; vertical-align: top; padding: 0 20px; }
			.signature-line { border-top: 1px solid #ccc; margin-bottom: 8px; width: 80%; margin-left: auto; margin-right: auto; }

			/* Footer */
			.footer-note { text-align: center; font-size: 8pt; color: #999; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
		</style>';
	}

	/**
	 * Extract formula text from stored JSON snapshot.
	 *
	 * @param string $formula_snapshot JSON string or raw formula.
	 * @return string
	 */
	protected function extract_formula_text( $formula_snapshot ) {
		$decoded = json_decode( $formula_snapshot, true );

		if ( is_array( $decoded ) && isset( $decoded['formula'] ) ) {
			return $decoded['formula'];
		}

		return $formula_snapshot;
	}

	/**
	 * Ensure mPDF is available and return instance.
	 *
	 * @return \Mpdf\Mpdf|WP_Error
	 */
	protected function get_mpdf_instance() {
		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			$this->load_library();
		}

		if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
			return new WP_Error(
				'berp_mpdf_missing',
				__( 'PDF library is not available. Please install mpdf/mpdf via Composer.', 'builderp' )
			);
		}

		try {
			return new \Mpdf\Mpdf(
				array(
					'format'        => 'A4',
					'margin_left'   => 12,
					'margin_right'  => 12,
					'margin_top'    => 12,
					'margin_bottom' => 16,
				)
			);
		} catch ( \Mpdf\MpdfException $e ) {
			return new WP_Error(
				'berp_mpdf_error',
				sprintf(
					/* translators: %s: error message */
					__( 'Unable to initialize PDF generator: %s', 'builderp' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Attempt to load Composer autoloader.
	 *
	 * @return void
	 */
	protected function load_library() {
		$autoload = BERP_PLUGIN_DIR . 'vendor/autoload.php';

		if ( file_exists( $autoload ) ) {
			require_once $autoload;
		}
	}

	/**
	 * Get upload path for storing PDFs.
	 *
	 * @param string $year  Year.
	 * @param string $month Month (02).
	 * @return string|WP_Error
	 */
	protected function get_upload_path( $year, $month ) {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error(
				'berp_upload_error',
				sprintf(
					/* translators: %s: upload error message */
					__( 'Upload directory error: %s', 'builderp' ),
					$uploads['error']
				)
			);
		}

		$path = trailingslashit( $uploads['basedir'] ) . 'berp-salary-slips/' . $year . '/' . $month . '/';

		if ( ! wp_mkdir_p( $path ) ) {
			return new WP_Error(
				'berp_upload_permission',
				__( 'Unable to create salary slip directory.', 'builderp' )
			);
		}

		return $path;
	}
}


