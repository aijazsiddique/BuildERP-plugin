<?php
/**
 * Employee Dashboard Logic.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Employee_Dashboard Class.
 */
class BERP_Employee_Dashboard {

	/**
	 * Get employee ID for current user.
	 *
	 * @return int|false Employee ID or false.
	 */
	public static function get_current_employee_id() {
		$user_id = get_current_user_id();
		return get_user_meta( $user_id, '_berp_employee_id', true );
	}

	/**
	 * Get quick stats for employee.
	 *
	 * @param int $employee_id Employee ID.
	 * @return array Stats.
	 */
	public static function get_stats( $employee_id ) {
		$month_start = date( 'Y-m-01' );
		$month_end   = date( 'Y-m-t' );

		// Present days.
		$attendance_args  = array(
			'post_type'      => 'berp_attendance',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_berp_employee_id',
					'value' => $employee_id,
				),
				array(
					'key'     => '_berp_date',
					'value'   => array( $month_start, $month_end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);
		$attendance_query = new WP_Query( $attendance_args );
		$present_days     = $attendance_query->found_posts;

		// Overtime.
		$overtime_hours = 0;
		if ( $attendance_query->have_posts() ) {
			while ( $attendance_query->have_posts() ) {
				$attendance_query->the_post();
				$overtime_hours += (float) get_post_meta( get_the_ID(), '_berp_overtime_hours', true );
			}
		}
		wp_reset_postdata();

		// Account Balance.
		$balance = get_post_meta( $employee_id, '_berp_account_balance', true );

		return array(
			'present_days'   => $present_days,
			'overtime_hours' => $overtime_hours,
			'balance'        => $balance ? $balance : 0,
		);
	}

	/**
	 * Get recent attendance.
	 *
	 * @param int $employee_id Employee ID.
	 * @param int $limit Limit.
	 * @return array Attendance records.
	 */
	public static function get_recent_attendance( $employee_id, $limit = 5 ) {
		$args  = array(
			'post_type'      => 'berp_attendance',
			'posts_per_page' => $limit,
			'meta_key'       => '_berp_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_berp_employee_id',
					'value' => $employee_id,
				),
			),
		);
		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Get latest salary slip.
	 *
	 * @param int $employee_id Employee ID.
	 * @return WP_Post|null Payroll post or null.
	 */
	public static function get_latest_salary_slip( $employee_id ) {
		$args  = array(
			'post_type'      => 'berp_payroll',
			'posts_per_page' => 1,
			'meta_key'       => '_berp_month',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_berp_employee_id',
					'value' => $employee_id,
				),
			),
		);
		$query = new WP_Query( $args );
		return $query->have_posts() ? $query->posts[0] : null;
	}
}
