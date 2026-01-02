<?php
/**
 * Timekeeper Dashboard Logic.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Timekeeper_Dashboard Class.
 */
class BERP_Timekeeper_Dashboard {

	/**
	 * Get quick stats for timekeeper.
	 *
	 * @return array Stats.
	 */
	public static function get_stats() {
		// Total Active Employees (include those with 'active' status or no status set).
		$employee_args   = array(
			'post_type'      => 'berp_employee',
			'posts_per_page' => -1,
			'fields'         => 'ids',
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
		$employees       = get_posts( $employee_args );
		$total_employees = count( $employees );

		// Today's Attendance.
		$today            = gmdate( 'Y-m-d' );
		$attendance_args  = array(
			'post_type'      => 'berp_attendance',
			'posts_per_page' => -1,
			'meta_key'       => '_berp_date',
			'meta_value'     => $today,
			'fields'         => 'ids',
		);
		$attendance       = get_posts( $attendance_args );
		$today_attendance = count( $attendance );

		return array(
			'total_employees'  => $total_employees,
			'today_attendance' => $today_attendance,
		);
	}

	/**
	 * Get recent attendance entries.
	 *
	 * @param int $limit Limit.
	 * @return array Attendance records.
	 */
	public static function get_recent_attendance( $limit = 10 ) {
		$args  = array(
			'post_type'      => 'berp_attendance',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$query = new WP_Query( $args );
		return $query->posts;
	}
}

