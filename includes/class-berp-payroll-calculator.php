<?php
/**
 * Payroll Calculator
 *
 * Core calculation engine for payroll processing. Integrates with Formula Builder
 * and Attendance System to calculate monthly salaries.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Payroll_Calculator Class
 *
 * Handles all payroll calculations including:
 * - Fetching employee salary data
 * - Querying attendance records
 * - Calculating overtime payment
 * - Applying salary formula
 * - Generating complete payroll data
 */
class BERP_Payroll_Calculator {

	/**
	 * Calculate payroll for a single employee
	 *
	 * @param int    $employee_id Employee post ID
	 * @param string $month       Month in YYYY-MM format
	 * @return array|WP_Error     Complete payroll data array or error
	 */
	public function calculate( $employee_id, $month ) {
		// Memory monitoring - Track usage and trigger garbage collection if needed
		$memory_limit       = ini_get( 'memory_limit' );
		$memory_limit_bytes = $this->convert_to_bytes( $memory_limit );
		$current_usage      = memory_get_usage( true );
		$usage_percent      = ( $current_usage / $memory_limit_bytes ) * 100;

		if ( $usage_percent > 80 ) {
			error_log(
				sprintf(
					'[BERP Payroll] High memory usage (%.1f%%) before processing employee %d',
					$usage_percent,
					$employee_id
				)
			);

			// Try to free memory proactively
			if ( function_exists( 'gc_collect_cycles' ) ) {
				gc_collect_cycles();
			}
		}

		// Validate inputs
		$validation = $this->validate_inputs( $employee_id, $month );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Get employee data
		$employee_data = $this->get_employee_data( $employee_id );
		if ( is_wp_error( $employee_data ) ) {
			return $employee_data;
		}

		// Fetch attendance records for the month
		$attendance_records = $this->fetch_attendance( $employee_id, $month );

		// Calculate attendance metrics
		$attendance_metrics = $this->calculate_attendance_metrics( $attendance_records, $month );

		// Calculate overtime payment
		$overtime_amount = $this->calculate_overtime(
			$attendance_metrics['overtime_hours'],
			$employee_data['basic_salary'],
			$this->get_working_days()
		);

		// Apply salary formula
		$formula_result = $this->apply_formula(
			$employee_data,
			$attendance_metrics,
			$overtime_amount
		);

		if ( is_wp_error( $formula_result ) ) {
			return $formula_result;
		}

		// Create attendance summary (memory-efficient replacement for full details)
		$attendance_ids = array();
		foreach ( $attendance_records as $record ) {
			$attendance_ids[] = $record->ID;
		}

		$attendance_summary = array(
			'total_records'  => count( $attendance_records ),
			'total_days'     => $attendance_metrics['total_paid_days'],
			'total_overtime' => $attendance_metrics['overtime_hours'],
			'date_range'     => sprintf( '%s to %s', $month . '-01', gmdate( 'Y-m-t', strtotime( $month . '-01' ) ) ),
			'attendance_ids' => $attendance_ids, // Just IDs for reference
		);

		// Compile complete payroll data
		$payroll_data = array(
			'employee_id'        => $employee_id,
			'month'              => $month,
			'basic_salary'       => $employee_data['basic_salary'],
			'allowances'         => $employee_data['allowances'],
			'deductions'         => $employee_data['deductions'],
			'present_days'       => $attendance_metrics['present_days'],
			'paid_weekends'      => $attendance_metrics['paid_weekends'],
			'holidays'           => $attendance_metrics['holidays'],
			'total_paid_days'    => $attendance_metrics['total_paid_days'],
			'overtime_hours'     => $attendance_metrics['overtime_hours'],
			'overtime_amount'    => $overtime_amount,
			'gross_salary'       => $formula_result['gross_salary'],
			'net_salary'         => $formula_result['net_salary'],
			'formula_used'       => $formula_result['formula_snapshot'],
			'attendance_summary' => $attendance_summary, // Store summary instead of full details
			'total_allowances'   => $employee_data['total_allowances'],
			'total_deductions'   => $employee_data['total_deductions'],
		);

		return apply_filters( 'berp_calculated_payroll_data', $payroll_data, $employee_id, $month );
	}

	/**
	 * Calculate payroll for multiple employees (bulk processing)
	 *
	 * @param array  $employee_ids Array of employee post IDs
	 * @param string $month        Month in YYYY-MM format
	 * @return array               Array of results with success/error data
	 */
	public function bulk_calculate( $employee_ids, $month ) {
		$results = array(
			'success' => array(),
			'errors'  => array(),
		);

		foreach ( $employee_ids as $employee_id ) {
			$employee_id = absint( $employee_id );
			$result      = $this->calculate( $employee_id, $month );

			if ( is_wp_error( $result ) ) {
				$results['errors'][ $employee_id ] = $result->get_error_message();
			} else {
				$results['success'][ $employee_id ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Validate calculation inputs
	 *
	 * @param int    $employee_id Employee post ID
	 * @param string $month       Month in YYYY-MM format
	 * @return true|WP_Error      True if valid, WP_Error otherwise
	 */
	protected function validate_inputs( $employee_id, $month ) {
		$employee_id = absint( $employee_id );

		// Validate employee ID
		if ( empty( $employee_id ) ) {
			return new WP_Error(
				'berp_payroll_invalid_employee',
				__( 'Invalid employee ID', 'aic_builderp' )
			);
		}

		// Check if employee exists
		$employee = get_post( $employee_id );
		if ( ! $employee || 'berp_employee' !== $employee->post_type ) {
			return new WP_Error(
				'berp_payroll_employee_not_found',
				__( 'Employee not found', 'aic_builderp' )
			);
		}

		// Check if employee is active
		$status = get_post_meta( $employee_id, '_berp_employee_status', true );
		if ( 'inactive' === $status ) {
			return new WP_Error(
				'berp_payroll_employee_inactive',
				__( 'Cannot process payroll for inactive employee', 'aic_builderp' )
			);
		}

		// Validate month format
		if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			return new WP_Error(
				'berp_payroll_invalid_month',
				__( 'Invalid month format. Use YYYY-MM', 'aic_builderp' )
			);
		}

		return true;
	}

	/**
	 * Get employee salary data
	 *
	 * @param int $employee_id Employee post ID
	 * @return array|WP_Error  Employee data array or error
	 */
	protected function get_employee_data( $employee_id ) {
		$basic_salary = get_post_meta( $employee_id, '_berp_basic_salary', true );
		$allowances   = get_post_meta( $employee_id, '_berp_allowances', true );
		$deductions   = get_post_meta( $employee_id, '_berp_deductions', true );

		// Validate basic salary
		if ( empty( $basic_salary ) || $basic_salary <= 0 ) {
			return new WP_Error(
				'berp_payroll_no_salary',
				__( 'Employee has no basic salary set', 'aic_builderp' )
			);
		}

		// Ensure arrays
		$allowances = is_array( $allowances ) ? $allowances : array();
		$deductions = is_array( $deductions ) ? $deductions : array();

		// Calculate totals
		$total_allowances = 0;
		foreach ( $allowances as $allowance ) {
			if ( isset( $allowance['amount'] ) && is_numeric( $allowance['amount'] ) ) {
				$total_allowances += floatval( $allowance['amount'] );
			}
		}

		$total_deductions = 0;
		foreach ( $deductions as $deduction ) {
			if ( isset( $deduction['amount'] ) && is_numeric( $deduction['amount'] ) ) {
				$total_deductions += floatval( $deduction['amount'] );
			}
		}

		return array(
			'basic_salary'     => floatval( $basic_salary ),
			'allowances'       => $allowances,
			'deductions'       => $deductions,
			'total_allowances' => $total_allowances,
			'total_deductions' => $total_deductions,
		);
	}

	/**
	 * Fetch attendance records for employee in given month (Memory & Performance Optimized)
	 *
	 * Uses WP_Query with 'fields' => 'ids' to minimize memory usage.
	 * Batch-loads all meta with single SQL query instead of N queries per record.
	 *
	 * @param int    $employee_id Employee post ID
	 * @param string $month       Month in YYYY-MM format
	 * @return array              Array of attendance data arrays
	 */
	protected function fetch_attendance( $employee_id, $month ) {
		$start_date = $month . '-01';
		$end_date   = gmdate( 'Y-m-t', strtotime( $start_date ) );

		// Use WP_Query with minimal fields for memory efficiency
		$args = array(
			'post_type'      => 'berp_attendance',
			'post_status'    => 'publish',
			'fields'         => 'ids', // CRITICAL: Only load IDs (saves ~95% memory)
			'posts_per_page' => -1,
			'no_found_rows'  => true, // Skip pagination count query
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_key'       => '_berp_date',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_employee_id',
					'value'   => $employee_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => '_berp_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$attendance_ids = get_posts( $args );

		if ( empty( $attendance_ids ) ) {
			return array();
		}

		// PERFORMANCE FIX: Batch load all meta with single SQL query
		// Instead of 6 queries per attendance record, this does just 1 query total
		global $wpdb;
		$ids_placeholder = implode( ',', array_fill( 0, count( $attendance_ids ), '%d' ) );

		$meta_query = $wpdb->prepare(
			"SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id IN ($ids_placeholder)
            AND meta_key IN ('_berp_date', '_berp_overtime_hours', '_berp_site_id', '_berp_is_weekend', '_berp_is_holiday', '_berp_weekend_payable')",
			$attendance_ids
		);

		$meta_results = $wpdb->get_results( $meta_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Organize meta by post ID for fast lookup
		$meta_by_post = array();
		foreach ( $meta_results as $meta_row ) {
			if ( ! isset( $meta_by_post[ $meta_row->post_id ] ) ) {
				$meta_by_post[ $meta_row->post_id ] = array();
			}
			$meta_by_post[ $meta_row->post_id ][ $meta_row->meta_key ] = $meta_row->meta_value;
		}

		// Build attendance data from batched meta
		$attendance_data = array();
		foreach ( $attendance_ids as $att_id ) {
			$meta = isset( $meta_by_post[ $att_id ] ) ? $meta_by_post[ $att_id ] : array();

			$attendance_data[] = (object) array(
				'ID'              => $att_id,
				'date'            => $meta['_berp_date'] ?? '',
				'overtime_hours'  => $meta['_berp_overtime_hours'] ?? 0,
				'site_id'         => $meta['_berp_site_id'] ?? 0,
				'is_weekend'      => $meta['_berp_is_weekend'] ?? false,
				'is_holiday'      => $meta['_berp_is_holiday'] ?? false,
				'weekend_payable' => $meta['_berp_weekend_payable'] ?? false,
			);
		}

		return $attendance_data;
	}

	/**
	 * Calculate attendance metrics from records
	 *
	 * @param array  $records Attendance record objects
	 * @param string $month   Month in YYYY-MM format
	 * @return array          Calculated metrics
	 */
	protected function calculate_attendance_metrics( $records, $month ) {
		$present_days   = 0;
		$paid_weekends  = 0;
		$holidays       = 0;
		$overtime_hours = 0;

		foreach ( $records as $record ) {
			$is_weekend      = ! empty( $record->is_weekend ) ? (bool) $record->is_weekend : false;
			$is_holiday      = ! empty( $record->is_holiday ) ? (bool) $record->is_holiday : false;
			$weekend_payable = ! empty( $record->weekend_payable ) ? (bool) $record->weekend_payable : false;

			// Count present days (non-weekend, non-holiday)
			if ( ! $is_weekend && ! $is_holiday ) {
				++$present_days;
			}

			// Count paid weekends
			if ( $is_weekend && $weekend_payable ) {
				++$paid_weekends;
			}

			// Count holidays
			if ( $is_holiday ) {
				++$holidays;
			}

			// Sum overtime hours
			if ( ! empty( $record->overtime_hours ) ) {
				$overtime_hours += floatval( $record->overtime_hours );
			}
		}

		$total_paid_days = $present_days + $paid_weekends + $holidays;

		return array(
			'present_days'    => $present_days,
			'paid_weekends'   => $paid_weekends,
			'holidays'        => $holidays,
			'total_paid_days' => $total_paid_days,
			'overtime_hours'  => $overtime_hours,
		);
	}

	/**
	 * Calculate overtime payment
	 *
	 * @param float $overtime_hours Total overtime hours
	 * @param float $basic_salary   Employee basic salary
	 * @param int   $working_days   Working days per month
	 * @return float                Overtime payment amount
	 */
	protected function calculate_overtime( $overtime_hours, $basic_salary, $working_days = 26 ) {
		if ( empty( $overtime_hours ) || $overtime_hours <= 0 ) {
			return 0;
		}

		// Get overtime multiplier from attendance settings (default 1.5x)
		$settings             = get_option( 'berp_settings', array() );
		$attendance_settings  = isset( $settings['attendance'] ) ? $settings['attendance'] : array();
		$overtime_multiplier  = isset( $attendance_settings['default_multiplier'] ) ? floatval( $attendance_settings['default_multiplier'] ) : 1.5;

		// Ensure minimum multiplier of 1 to avoid zero/negative calculations
		if ( $overtime_multiplier <= 0 ) {
			$overtime_multiplier = 1.0;
		}

		// Calculate hourly rate
		// Daily rate = basic_salary / working_days
		// Hourly rate = daily_rate / 8 (assuming 8-hour work day)
		$daily_rate  = $basic_salary / $working_days;
		$hourly_rate = $daily_rate / 8;

		// Overtime rate with multiplier
		$overtime_rate = $hourly_rate * $overtime_multiplier;

		// Total overtime amount
		$overtime_amount = $overtime_hours * $overtime_rate;

		return round( $overtime_amount, 2 );
	}

	/**
	 * Apply salary formula to calculate final salary
	 *
	 * @param array $employee_data      Employee salary data
	 * @param array $attendance_metrics Attendance metrics
	 * @param float $overtime_amount    Calculated overtime amount
	 * @return array|WP_Error           Result with gross/net salary and formula snapshot
	 */
	protected function apply_formula( $employee_data, $attendance_metrics, $overtime_amount ) {
		// Get active formula configuration
		if ( function_exists( 'berp_get_formula_config_with_settings' ) ) {
			$config = berp_get_formula_config_with_settings();
		} else {
			return new WP_Error(
				'berp_payroll_formula_unavailable',
				__( 'Salary formula not available', 'aic_builderp' )
			);
		}

		$formula = isset( $config['active']['formula'] ) ? $config['active']['formula'] : '';

		if ( empty( $formula ) ) {
			return new WP_Error(
				'berp_payroll_no_formula',
				__( 'No active salary formula found', 'aic_builderp' )
			);
		}

		// Prepare variables for formula
		$working_days = $this->get_working_days();

		$variables = array(
			'basic_salary'     => $employee_data['basic_salary'],
			'working_days'     => $working_days,
			'days_in_month'    => gmdate( 't', strtotime( $attendance_metrics['month'] ?? gmdate( 'Y-m-01' ) ) ),
			'present_days'     => $attendance_metrics['present_days'],
			'weekends'         => $attendance_metrics['paid_weekends'],
			'holidays'         => $attendance_metrics['holidays'],
			'total_paid_days'  => $attendance_metrics['total_paid_days'],
			'overtime_hours'   => $attendance_metrics['overtime_hours'],
			'overtime_rate'    => $overtime_amount > 0 && $attendance_metrics['overtime_hours'] > 0
									? $overtime_amount / $attendance_metrics['overtime_hours']
									: 0,
			'total_allowances' => $employee_data['total_allowances'],
			'total_deductions' => $employee_data['total_deductions'],
		);

		// Evaluate formula
		$evaluator = new BERP_Formula_Evaluator();
		$result    = $evaluator->evaluate( $formula, $variables );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$net_salary = round( $result, 2 );

		// Calculate gross (net + deductions)
		$gross_salary = round( $net_salary + $employee_data['total_deductions'], 2 );

		// Create formula snapshot for audit trail
		$formula_snapshot = wp_json_encode(
			array(
				'formula'   => $formula,
				'variables' => $variables,
				'timestamp' => current_time( 'mysql' ),
				'version'   => isset( $config['active']['version'] ) ? $config['active']['version'] : 1,
			)
		);

		return array(
			'gross_salary'     => $gross_salary,
			'net_salary'       => $net_salary,
			'formula_snapshot' => $formula_snapshot,
		);
	}

	/**
	 * Get working days per month from settings
	 *
	 * @return int Working days (default 26)
	 */
	protected function get_working_days() {
		$settings         = get_option( 'berp_settings', array() );
		$payroll_settings = isset( $settings['payroll'] ) ? $settings['payroll'] : array();
		
		// Check for working_days (new key) first, then working_days_per_month (legacy)
		$working_days = 26;
		if ( isset( $payroll_settings['working_days'] ) ) {
			$working_days = absint( $payroll_settings['working_days'] );
		} elseif ( isset( $payroll_settings['working_days_per_month'] ) ) {
			$working_days = absint( $payroll_settings['working_days_per_month'] );
		}

		return $working_days > 0 ? $working_days : 26;
	}

	/**
	 * Get attendance details for display/export (on-demand loading - Performance Optimized)
	 *
	 * Loads full attendance details only when needed (e.g., for salary slips, reports).
	 * More memory-efficient than storing serialized arrays in postmeta.
	 * Uses batched SQL query instead of N individual meta queries.
	 *
	 * @param int $payroll_id Payroll post ID
	 * @return array          Array of attendance detail objects
	 */
	public function get_attendance_details_for_display( $payroll_id ) {
		$summary = get_post_meta( $payroll_id, '_berp_attendance_summary', true );

		if ( empty( $summary ) || empty( $summary['attendance_ids'] ) ) {
			return array();
		}

		// PERFORMANCE FIX: Batch load all meta with single SQL query
		global $wpdb;
		$attendance_ids  = $summary['attendance_ids'];
		$ids_placeholder = implode( ',', array_fill( 0, count( $attendance_ids ), '%d' ) );

		$meta_query = $wpdb->prepare(
			"SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id IN ($ids_placeholder)
            AND meta_key IN ('_berp_date', '_berp_overtime_hours', '_berp_site_id', '_berp_is_weekend', '_berp_is_holiday')",
			$attendance_ids
		);

		$meta_results = $wpdb->get_results( $meta_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Organize meta by post ID
		$meta_by_post = array();
		foreach ( $meta_results as $meta_row ) {
			if ( ! isset( $meta_by_post[ $meta_row->post_id ] ) ) {
				$meta_by_post[ $meta_row->post_id ] = array();
			}
			$meta_by_post[ $meta_row->post_id ][ $meta_row->meta_key ] = $meta_row->meta_value;
		}

		// Build details from batched meta
		$details = array();
		foreach ( $attendance_ids as $att_id ) {
			$meta    = isset( $meta_by_post[ $att_id ] ) ? $meta_by_post[ $att_id ] : array();
			$site_id = isset( $meta['_berp_site_id'] ) ? (int) $meta['_berp_site_id'] : 0;

			$details[] = array(
				'id'         => $att_id,
				'date'       => $meta['_berp_date'] ?? '',
				'overtime'   => (float) ( $meta['_berp_overtime_hours'] ?? 0 ),
				'site_id'    => $site_id,
				'site_name'  => $site_id ? get_the_title( $site_id ) : '',
				'is_weekend' => (bool) ( $meta['_berp_is_weekend'] ?? false ),
				'is_holiday' => (bool) ( $meta['_berp_is_holiday'] ?? false ),
			);
		}

		return $details;
	}

	/**
	 * Get attendance details with backward compatibility
	 *
	 * Supports both new summary format and old serialized format.
	 * Ensures existing payroll records continue to work.
	 *
	 * @param int $payroll_id Payroll post ID
	 * @return array          Array of attendance details
	 */
	public function get_attendance_details( $payroll_id ) {
		// Try new summary format first
		$summary = get_post_meta( $payroll_id, '_berp_attendance_summary', true );
		if ( ! empty( $summary ) ) {
			return $this->get_attendance_details_for_display( $payroll_id );
		}

		// Fallback to old serialized format for backward compatibility
		$old_details = get_post_meta( $payroll_id, '_berp_attendance_details', true );
		if ( ! empty( $old_details ) ) {
			return $old_details; // Old format still works
		}

		return array();
	}

	/**
	 * Convert PHP memory notation to bytes
	 *
	 * Helper function for memory monitoring.
	 *
	 * @param string $value Memory value (e.g., '128M', '1G')
	 * @return int          Memory in bytes
	 */
	private function convert_to_bytes( $value ) {
		$value = trim( $value );
		$last  = strtolower( $value[ strlen( $value ) - 1 ] );
		$value = (int) $value;

		switch ( $last ) {
			case 'g':
				$value *= 1024;
				// Fall through
			case 'm':
				$value *= 1024;
				// Fall through
			case 'k':
				$value *= 1024;
		}

		return $value;
	}
}
