<?php
/**
 * Payroll REST API
 *
 * Handles all REST API endpoints for payroll operations.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/api
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Payroll_API Class
 *
 * REST API endpoints for:
 * - Payroll calculation (preview)
 * - Bulk payroll processing
 * - Mark as paid
 * - Email salary slips
 */
class BERP_Payroll_API {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Calculate payroll (preview)
		register_rest_route(
			'berp/v1',
			'/payroll/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate_payroll' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Process payroll (create posts)
		register_rest_route(
			'berp/v1',
			'/payroll/process',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process_payroll' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Mark as paid
		register_rest_route(
			'berp/v1',
			'/payroll/mark-paid',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'mark_paid' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Email salary slip
		register_rest_route(
			'berp/v1',
			'/payroll/email-slip',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'email_slip' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// View salary slip PDF
		register_rest_route(
			'berp/v1',
			'/payroll/salary-slip',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'view_salary_slip' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permission for all endpoints
	 *
	 * @return bool
	 */
	public function check_permission() {
		return current_user_can( 'berp_process_payroll' );
	}

	/**
	 * Calculate payroll (preview mode)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function calculate_payroll( WP_REST_Request $request ) {
		$employee_id  = $request->get_param( 'employee_id' );
		$month        = $request->get_param( 'month' );
		$employee_ids = $request->get_param( 'employee_ids' );

		// Single employee calculation
		if ( ! empty( $employee_id ) && ! empty( $month ) ) {
			$employee_id = absint( $employee_id );
			$month       = sanitize_text_field( $month );

			$calculator = new BERP_Payroll_Calculator();
			$result     = $calculator->calculate( $employee_id, $month );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Return COMPLETE calculation data for recalculate functionality
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						// Salary fields
						'basic_salary'    => floatval( $result['basic_salary'] ),
						'gross_salary'    => floatval( $result['gross_salary'] ),
						'net_salary'      => floatval( $result['net_salary'] ),

						// Allowances array
						'allowances'      => isset( $result['allowances'] ) ? $result['allowances'] : array(),

						// Deductions array
						'deductions'      => isset( $result['deductions'] ) ? $result['deductions'] : array(),

						// Attendance data
						'total_days'      => isset( $result['attendance_summary']['total_days'] )
							? intval( $result['attendance_summary']['total_days'] ) : 0,
						'total_overtime'  => isset( $result['attendance_summary']['total_overtime'] )
							? floatval( $result['attendance_summary']['total_overtime'] ) : 0,
						'overtime_amount' => isset( $result['overtime_amount'] )
							? floatval( $result['overtime_amount'] ) : 0,

						// Complete data for backward compatibility
						'full_data'       => $result,
					),
				),
				200
			);
		}

		// Bulk calculation
		if ( ! empty( $employee_ids ) && is_array( $employee_ids ) && ! empty( $month ) ) {
			$month        = sanitize_text_field( $month );
			$employee_ids = array_map( 'absint', $employee_ids );

			$calculator = new BERP_Payroll_Calculator();
			$results    = $calculator->bulk_calculate( $employee_ids, $month );

			// Format results for display
			$preview_data = array();
			$total_gross  = 0;
			$total_net    = 0;

			foreach ( $results['success'] as $emp_id => $data ) {
				$employee_name = get_the_title( $emp_id );
				$emp_code      = get_post_meta( $emp_id, '_berp_employee_id', true );

				$preview_data[] = array(
					'employee_id'    => $emp_id,
					'employee_name'  => $employee_name,
					'employee_code'  => $emp_code,
					'present_days'   => $data['present_days'] ?? 0,
					'overtime_hours' => $data['overtime_hours'] ?? 0,
					'gross_salary'   => $data['gross_salary'] ?? 0,
					'net_salary'     => $data['net_salary'] ?? 0,
				);

				$total_gross += $data['gross_salary'] ?? 0;
				$total_net   += $data['net_salary'] ?? 0;
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						'calculations' => $preview_data,
						'totals'       => array(
							'gross' => $total_gross,
							'net'   => $total_net,
						),
						'count'        => count( $preview_data ),
						'errors'       => $results['errors'],
					),
				),
				200
			);
		}

		return new WP_Error(
			'berp_invalid_params',
			__( 'Invalid parameters provided', 'BuildERP' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Process payroll (create posts) with batch processing support
	 *
	 * Processes employees in batches to prevent memory exhaustion.
	 * Batch size is 10 employees per request.
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function process_payroll( WP_REST_Request $request ) {
		$employee_ids = $request->get_param( 'employee_ids' );
		$month        = $request->get_param( 'month' );
		$batch_number = absint( $request->get_param( 'batch_number' ) ) ?: 0;
		$auto_paid    = (bool) $request->get_param( 'auto_paid' );
		$send_emails  = (bool) $request->get_param( 'send_emails' );

		if ( empty( $employee_ids ) || ! is_array( $employee_ids ) || empty( $month ) ) {
			return new WP_Error(
				'berp_invalid_params',
				__( 'Invalid parameters provided', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		$month        = sanitize_text_field( $month );
		$employee_ids = array_map( 'absint', $employee_ids );

		// Batch configuration
		$batch_size      = 10; // Process 10 employees at a time
		$total_employees = count( $employee_ids );

		// Calculate batch range
		$start_index     = $batch_number * $batch_size;
		$end_index       = min( $start_index + $batch_size, $total_employees );
		$batch_employees = array_slice( $employee_ids, $start_index, $batch_size );

		// Process this batch
		$batch_results = array();
		$success_count = 0;
		$error_count   = 0;
		$emails_sent   = 0;

		foreach ( $batch_employees as $employee_id ) {
			// Increase memory limit per employee if needed
			@ini_set( 'memory_limit', '256M' );

			$calculator = new BERP_Payroll_Calculator();
			$data       = $calculator->calculate( $employee_id, $month );

			if ( is_wp_error( $data ) ) {
				++$error_count;
				$batch_results[] = array(
					'employee_id'   => $employee_id,
					'employee_name' => get_the_title( $employee_id ),
					'success'       => false,
					'error'         => $data->get_error_message(),
				);
				continue;
			}

			// Check for duplicate
			$existing = $this->find_existing_payroll( $employee_id, $month );
			if ( $existing ) {
				++$error_count;
				$batch_results[] = array(
					'employee_id'   => $employee_id,
					'employee_name' => get_the_title( $employee_id ),
					'success'       => false,
					'error'         => __( 'Payroll already exists for this month', 'BuildERP' ),
				);
				continue;
			}

			// Create payroll post
			$payroll_id = wp_insert_post(
				array(
					'post_type'   => 'berp_payroll',
					'post_title'  => sprintf( '%s - %s', get_the_title( $employee_id ), $month ),
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
				)
			);

			if ( is_wp_error( $payroll_id ) ) {
				++$error_count;
				$batch_results[] = array(
					'employee_id'   => $employee_id,
					'employee_name' => get_the_title( $employee_id ),
					'success'       => false,
					'error'         => $payroll_id->get_error_message(),
				);
				continue;
			}

			// Save all meta (using new attendance_summary format)
			update_post_meta( $payroll_id, '_berp_payroll_employee_id', $data['employee_id'] );
			update_post_meta( $payroll_id, '_berp_payroll_month', $data['month'] );
			update_post_meta( $payroll_id, '_berp_calculation_mode', 'auto' );
			update_post_meta( $payroll_id, '_berp_payroll_basic_salary', $data['basic_salary'] );
			update_post_meta( $payroll_id, '_berp_payroll_allowances', $data['allowances'] );
			update_post_meta( $payroll_id, '_berp_payroll_deductions', $data['deductions'] );
			update_post_meta( $payroll_id, '_berp_present_days', $data['present_days'] );
			update_post_meta( $payroll_id, '_berp_paid_weekends', $data['paid_weekends'] );
			update_post_meta( $payroll_id, '_berp_holidays', $data['holidays'] );
			update_post_meta( $payroll_id, '_berp_total_paid_days', $data['total_paid_days'] );
			update_post_meta( $payroll_id, '_berp_overtime_hours', $data['overtime_hours'] );
			update_post_meta( $payroll_id, '_berp_overtime_amount', $data['overtime_amount'] );
			update_post_meta( $payroll_id, '_berp_gross_salary', $data['gross_salary'] );
			update_post_meta( $payroll_id, '_berp_net_salary', $data['net_salary'] );
			update_post_meta( $payroll_id, '_berp_payroll_status', 'pending' );
			update_post_meta( $payroll_id, '_berp_formula_used', $data['formula_used'] );
			update_post_meta( $payroll_id, '_berp_attendance_summary', $data['attendance_summary'] ); // New format!
			update_post_meta( $payroll_id, '_berp_total_allowances', $data['total_allowances'] );
			update_post_meta( $payroll_id, '_berp_total_deductions', $data['total_deductions'] );

			// Mark as paid if requested
			if ( $auto_paid ) {
				$paid_result = $this->mark_payroll_paid( $payroll_id );
				if ( is_wp_error( $paid_result ) ) {
					// Log error but continue
					error_log( sprintf( '[BERP] Failed to mark payroll %d as paid: %s', $payroll_id, $paid_result->get_error_message() ) );
				}
			}

			// Send email if requested
			if ( $send_emails ) {
				$email_result = $this->send_email( $payroll_id );
				if ( ! is_wp_error( $email_result ) && $email_result ) {
					++$emails_sent;
				}
			}

			++$success_count;
			$batch_results[] = array(
				'employee_id'   => $employee_id,
				'employee_name' => get_the_title( $employee_id ),
				'success'       => true,
				'payroll_id'    => $payroll_id,
			);

			// Free memory after each employee
			unset( $calculator, $data );
			if ( function_exists( 'gc_collect_cycles' ) ) {
				gc_collect_cycles();
			}
		}

		// Calculate progress
		$completed = $end_index;
		$has_more  = $completed < $total_employees;

		return new WP_REST_Response(
			array(
				'success'       => true,
				'batch_number'  => $batch_number,
				'completed'     => $completed,
				'total'         => $total_employees,
				'has_more'      => $has_more,
				'next_batch'    => $has_more ? $batch_number + 1 : null,
				'batch_results' => $batch_results,
				'batch_success' => $success_count,
				'batch_errors'  => $error_count,
				'emails_sent'   => $emails_sent,
				/* translators: 1: completed employees, 2: total employees, 3: succeeded count, 4: failed count */
				'message'       => sprintf(
					__( 'Processed %1$d of %2$d employees (%3$d succeeded, %4$d failed)', 'BuildERP' ),
					$completed,
					$total_employees,
					$success_count,
					$error_count
				),
			),
			200
		);
	}

	/**
	 * Mark payroll as paid
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_paid( WP_REST_Request $request ) {
		$payroll_id = $request->get_param( 'payroll_id' );

		if ( empty( $payroll_id ) ) {
			return new WP_Error(
				'berp_invalid_params',
				__( 'Invalid payroll ID', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		$payroll_id = absint( $payroll_id );

		$result = $this->mark_payroll_paid( $payroll_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Payroll marked as paid successfully', 'BuildERP' ),
				'expense_id' => $result,
			),
			200
		);
	}

	/**
	 * Email salary slip
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function email_slip( WP_REST_Request $request ) {
		$payroll_id = $request->get_param( 'payroll_id' );

		if ( empty( $payroll_id ) ) {
			return new WP_Error(
				'berp_invalid_params',
				__( 'Invalid payroll ID', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		$payroll_id = absint( $payroll_id );

		$result = $this->send_email( $payroll_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error(
				'berp_email_failed',
				__( 'Failed to send email', 'BuildERP' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Salary slip emailed successfully', 'BuildERP' ),
			),
			200
		);
	}

	/**
	 * View salary slip PDF inline.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function view_salary_slip( WP_REST_Request $request ) {
		$payroll_id = absint( $request->get_param( 'payroll_id' ) );

		if ( empty( $payroll_id ) ) {
			return new WP_Error(
				'berp_invalid_params',
				__( 'Invalid payroll ID', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		$pdf     = new BERP_Salary_Slip_PDF();
		$content = $pdf->generate( $payroll_id, 'string' );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$month         = get_post_meta( $payroll_id, '_berp_payroll_month', true );
		$employee_id   = get_post_meta( $payroll_id, '_berp_payroll_employee_id', true );
		$employee_name = $employee_id ? get_the_title( $employee_id ) : 'employee';

		$filename = sprintf(
			'salary-slip-%s-%s.pdf',
			sanitize_title( $employee_name ),
			sanitize_title( $month )
		);

		// Serve raw PDF bytes (REST normally JSON-encodes responses).
		add_filter(
			'rest_pre_serve_request',
			function ( $served, $result, $request, $server ) use ( $content, $filename ) {
				if ( '/berp/v1/payroll/salary-slip' !== $request->get_route() ) {
					return $served;
				}

				nocache_headers();
				$server->send_header( 'Content-Type', 'application/pdf' );
				$server->send_header( 'Content-Disposition', 'inline; filename="' . $filename . '"' );
				$server->send_header( 'Content-Length', (string) strlen( $content ) );

				echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				return true;
			},
			10,
			4
		);

		// Returning an empty response lets rest_pre_serve_request output the PDF directly.
		return new WP_REST_Response( null, 200 );
	}

	/**
	 * Mark payroll as paid (internal method)
	 *
	 * @param int $payroll_id Payroll post ID
	 * @return int|WP_Error   Expense ID or error
	 */
	protected function mark_payroll_paid( $payroll_id ) {
		// Validate payroll exists
		$payroll = get_post( $payroll_id );
		if ( ! $payroll || 'berp_payroll' !== $payroll->post_type ) {
			return new WP_Error(
				'berp_invalid_payroll',
				__( 'Invalid payroll record', 'BuildERP' )
			);
		}

		// Check if already paid
		$status = get_post_meta( $payroll_id, '_berp_payroll_status', true );
		if ( 'paid' === $status ) {
			return new WP_Error(
				'berp_already_paid',
				__( 'Payroll already marked as paid', 'BuildERP' )
			);
		}

		// Get payroll data
		$employee_id = get_post_meta( $payroll_id, '_berp_payroll_employee_id', true );
		$month       = get_post_meta( $payroll_id, '_berp_payroll_month', true );
		$net_salary  = get_post_meta( $payroll_id, '_berp_net_salary', true );

		if ( empty( $employee_id ) || empty( $net_salary ) ) {
			return new WP_Error(
				'berp_incomplete_payroll',
				__( 'Payroll data is incomplete', 'BuildERP' )
			);
		}

		// Create expense record
		$employee_name = get_the_title( $employee_id );
		/* translators: 1: employee name, 2: payroll month */
		$expense_title = sprintf( __( 'Payroll - %1$s - %2$s', 'BuildERP' ), $employee_name, gmdate( 'F Y', strtotime( $month . '-01' ) ) );

		$expense_id = wp_insert_post(
			array(
				'post_type'   => 'berp_expense',
				'post_title'  => $expense_title,
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			)
		);

		if ( is_wp_error( $expense_id ) ) {
			return $expense_id;
		}

		// Save expense meta
		update_post_meta( $expense_id, '_berp_expense_date', current_time( 'Y-m-d' ) );
		update_post_meta( $expense_id, '_berp_expense_amount', $net_salary );
		update_post_meta( $expense_id, '_berp_expense_category', 'payroll' );
		update_post_meta( $expense_id, '_berp_payment_method', 'bank_transfer' );
		update_post_meta( $expense_id, '_berp_linked_payroll_id', $payroll_id );
		update_post_meta( $expense_id, '_berp_linked_employee_id', $employee_id );

		// Update payroll meta
		update_post_meta( $payroll_id, '_berp_payroll_status', 'paid' );
		update_post_meta( $payroll_id, '_berp_paid_date', current_time( 'Y-m-d' ) );
		update_post_meta( $payroll_id, '_berp_linked_expense_id', $expense_id );

		// Update employee account balance
		$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
		$new_balance     = $current_balance + $net_salary;
		update_post_meta( $employee_id, '_berp_account_balance', $new_balance );

		// Trigger action hook
		do_action( 'berp_after_payroll_paid', $payroll_id, $employee_id, $expense_id );

		// Log activity.
		if ( function_exists( 'berp_log_activity' ) ) {
			berp_log_activity(
				'payroll_paid',
				sprintf( 'Payroll marked as paid: %s for %s', $month, $employee_name ),
				array(
					'payroll_id'  => $payroll_id,
					'employee_id' => $employee_id,
					'amount'      => $net_salary,
				)
			);
		}

		return $expense_id;
	}

	/**
	 * Send salary slip email (internal method)
	 *
	 * @param int $payroll_id Payroll post ID
	 * @return bool|WP_Error  True on success, false/error on failure
	 */
	protected function send_email( $payroll_id ) {
		// Get employee email
		$employee_id = get_post_meta( $payroll_id, '_berp_payroll_employee_id', true );
		$email       = get_post_meta( $employee_id, '_berp_employee_email', true );

		// Validate email
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error(
				'berp_invalid_email',
				__( 'Employee does not have a valid email address', 'BuildERP' )
			);
		}

		// Get month for subject
		$month      = get_post_meta( $payroll_id, '_berp_payroll_month', true );
		$month_name = gmdate( 'F Y', strtotime( $month . '-01' ) );

		// Email subject
		/* translators: %s: payroll month */
		$subject = sprintf( __( 'Salary Slip - %s', 'BuildERP' ), $month_name );

		// Email message (HTML)
		$employee_name = get_the_title( $employee_id );
		$message       = $this->get_email_template( $employee_name, $month_name );

		// Headers
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$pdf         = new BERP_Salary_Slip_PDF();
		$pdf_content = $pdf->generate( $payroll_id, 'string' );
		if ( is_wp_error( $pdf_content ) ) {
			return $pdf_content;
		}

		$tmp_file = wp_tempnam( 'salary-slip-' . $payroll_id );
		if ( false === $tmp_file ) {
			return new WP_Error(
				'berp_temp_file',
				__( 'Unable to create temporary file for PDF attachment.', 'BuildERP' )
			);
		}

		file_put_contents( $tmp_file, $pdf_content );

		$sent = wp_mail( $email, $subject, $message, $headers, array( $tmp_file ) );

		// Clean up temp file.
		if ( file_exists( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
		}

		// Log email sent
		if ( $sent ) {
			update_post_meta( $payroll_id, '_berp_email_sent', current_time( 'mysql' ) );
			update_post_meta( $payroll_id, '_berp_email_sent_to', $email );
			do_action( 'berp_after_salary_slip_emailed', $payroll_id, $employee_id, $email );
		}

		return $sent;
	}

	/**
	 * Get email template
	 *
	 * @param string $employee_name Employee name
	 * @param string $month_name    Month name
	 * @return string HTML email content
	 */
	protected function get_email_template( $employee_name, $month_name ) {
		$settings     = get_option( 'berp_settings', array() );
		$general      = isset( $settings['general'] ) ? $settings['general'] : array();
		$company_name = isset( $general['company_name'] ) ? $general['company_name'] : get_bloginfo( 'name' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
			<div style="background: #f4f4f4; padding: 20px; border-radius: 5px;">
				<h2 style="color: #2271b1; margin-top: 0;"><?php echo esc_html( $company_name ); ?></h2>
				<p><?php esc_html_e( 'Dear', 'BuildERP' ); ?> <?php echo esc_html( $employee_name ); ?>,</p>
				<p>
					<?php
					/* translators: %s: payroll month */
					printf( esc_html__( 'Please find attached your salary slip for %s.', 'BuildERP' ), esc_html( $month_name ) );
					?>
				</p>
				<p><?php esc_html_e( 'If you have any questions regarding your salary, please contact the HR department.', 'BuildERP' ); ?></p>
				<hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
				<p style="font-size: 12px; color: #666;">
					<?php esc_html_e( 'This is an automated email. Please do not reply to this message.', 'BuildERP' ); ?>
				</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Find existing payroll for employee and month
	 *
	 * @param int    $employee_id Employee post ID
	 * @param string $month       Month (YYYY-MM)
	 * @return int|null           Payroll post ID or null
	 */
	protected function find_existing_payroll( $employee_id, $month ) {
		$args = array(
			'post_type'      => 'berp_payroll',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_payroll_employee_id',
					'value'   => $employee_id,
					'compare' => '=',
				),
				array(
					'key'     => '_berp_payroll_month',
					'value'   => $month,
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $args );

		return $query->have_posts() ? $query->posts[0] : null;
	}
}

