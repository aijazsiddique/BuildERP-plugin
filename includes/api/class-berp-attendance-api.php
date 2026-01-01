<?php
/**
 * Attendance REST API endpoints.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Attendance_API Class
 *
 * Registers REST routes for attendance operations.
 */
class BERP_Attendance_API {

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			'berp/v1',
			'/attendance/bulk-log',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_log' ),
				'permission_callback' => array( $this, 'can_log' ),
			)
		);

		register_rest_route(
			'berp/v1',
			'/attendance/log',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'log_single' ),
				'permission_callback' => array( $this, 'can_log' ),
			)
		);

		register_rest_route(
			'berp/v1',
			'/attendance/edit',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'edit' ),
				'permission_callback' => array( $this, 'can_edit' ),
			)
		);

		register_rest_route(
			'berp/v1',
			'/attendance/check-existing',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'check_existing' ),
				'permission_callback' => array( $this, 'can_log' ),
			)
		);

		register_rest_route(
			'berp/v1',
			'/attendance/list',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'list_records' ),
				'permission_callback' => array( $this, 'can_view' ),
			)
		);

		register_rest_route(
			'berp/v1',
			'/attendance/delete',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => array( $this, 'can_delete' ),
			)
		);
	}

	/**
	 * Capability callback for logging.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_log() {
		return current_user_can( 'berp_log_attendance' );
	}

	/**
	 * Capability callback for viewing.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_view() {
		return current_user_can( 'berp_view_attendance' );
	}

	/**
	 * Capability callback for editing.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_edit() {
		return current_user_can( 'berp_edit_attendance' );
	}

	/**
	 * Capability callback for deleting.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_delete() {
		return current_user_can( 'berp_delete_attendance' );
	}

	/**
	 * Bulk log attendance.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_log( WP_REST_Request $request ) {
		$valid = $this->validate_request( $request );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$date                = $valid['date'];
		$site_id             = $valid['site_id'];
		$override_duplicates = $valid['override_duplicates'];
		$attendance_items    = $request->get_param( 'attendance' );
		$user_id             = get_current_user_id();

		if ( empty( $attendance_items ) || ! is_array( $attendance_items ) ) {
			return new WP_Error( 'invalid_attendance', __( 'No attendance records provided.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$selected_employee_ids = array();
		$created               = 0;
		$updated               = 0;
		$skipped               = 0;
		$duplicates            = array();

		foreach ( $attendance_items as $item ) {
			$employee_id = isset( $item['employee_id'] ) ? absint( $item['employee_id'] ) : 0;
			$overtime    = isset( $item['overtime'] ) ? floatval( $item['overtime'] ) : 0;
			$notes       = isset( $item['notes'] ) ? sanitize_textarea_field( $item['notes'] ) : '';

			if ( ! $employee_id ) {
				++$skipped;
				continue;
			}

			$selected_employee_ids[] = $employee_id;

			$existing_id = $this->find_attendance( $employee_id, $date );

			if ( $existing_id ) {
				if ( $override_duplicates ) {
					$this->update_attendance_meta( $existing_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id );
					++$updated;
				} else {
					$duplicates[] = $employee_id;
					++$skipped;
				}
				continue;
			}

			$created_id = $this->create_attendance_post( $employee_id, $date, $site_id, $overtime, $notes, $user_id );
			if ( is_wp_error( $created_id ) ) {
				++$skipped;
				continue;
			}

			++$created;
		}

		if ( $site_id ) {
			update_user_meta( $user_id, '_berp_last_site', $site_id );
			$this->update_user_site_employee_selections( $user_id, $site_id, $selected_employee_ids, false );
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'created'    => $created,
				'updated'    => $updated,
				'skipped'    => $skipped,
				'duplicates' => $duplicates,
				'message'    => sprintf(
					/* translators: 1: created count */
					__( '%d attendance records processed.', 'aic_builderp' ),
					$created + $updated
				),
			),
			200
		);
	}

	/**
	 * Log single attendance (quick entry).
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function log_single( WP_REST_Request $request ) {
		$valid = $this->validate_request( $request );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$employee_id = absint( $request->get_param( 'employee_id' ) );
		$overtime    = floatval( $request->get_param( 'overtime' ) );
		$notes       = sanitize_textarea_field( $request->get_param( 'notes' ) );

		if ( ! $employee_id ) {
			return new WP_Error( 'invalid_employee', __( 'Employee is required.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$date        = $valid['date'];
		$site_id     = $valid['site_id'];
		$user_id     = get_current_user_id();
		$existing_id = $this->find_attendance( $employee_id, $date );

		if ( $existing_id ) {
			$this->update_attendance_meta( $existing_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id );
			$result_id = $existing_id;
			$action    = 'updated';
		} else {
			$result_id = $this->create_attendance_post( $employee_id, $date, $site_id, $overtime, $notes, $user_id );
			$action    = 'created';
		}

		if ( $site_id ) {
			update_user_meta( $user_id, '_berp_last_site', $site_id );
			$this->update_user_site_employee_selections( $user_id, $site_id, array( $employee_id ), true );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $result_id,
				'action'  => $action,
				'message' => __( 'Attendance saved.', 'aic_builderp' ),
			),
			200
		);
	}

	/**
	 * Edit an existing attendance record.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function edit( WP_REST_Request $request ) {
		$attendance_id = absint( $request->get_param( 'attendance_id' ) );
		if ( ! $attendance_id ) {
			return new WP_Error( 'invalid_attendance', __( 'Attendance ID is required.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$date     = sanitize_text_field( $request->get_param( 'date' ) );
		$site_id  = absint( $request->get_param( 'site_id' ) );
		$overtime = floatval( $request->get_param( 'overtime' ) );
		$notes    = sanitize_textarea_field( $request->get_param( 'notes' ) );

		$settings_check = $this->validate_date_rules( $date );
		if ( is_wp_error( $settings_check ) ) {
			return $settings_check;
		}

		$this->update_attendance_meta( $attendance_id, null, $date, $site_id, $overtime, $notes, get_current_user_id() );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Attendance updated.', 'aic_builderp' ),
			),
			200
		);
	}

	/**
	 * Delete an attendance record.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'aic_builderp' ), array( 'status' => 403 ) );
		}

		$attendance_id = absint( $request->get_param( 'attendance_id' ) );
		if ( ! $attendance_id ) {
			return new WP_Error( 'invalid_attendance', __( 'Attendance ID is required.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$post = get_post( $attendance_id );
		if ( ! $post || 'berp_attendance' !== $post->post_type ) {
			return new WP_Error( 'invalid_attendance', __( 'Invalid attendance record.', 'aic_builderp' ), array( 'status' => 404 ) );
		}

		$result = wp_trash_post( $attendance_id );
		if ( ! $result ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete attendance record.', 'aic_builderp' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Attendance deleted successfully.', 'aic_builderp' ),
			),
			200
		);
	}

	/**
	 * Check existing attendance for a date + employees.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function check_existing( WP_REST_Request $request ) {
		$date      = sanitize_text_field( $request->get_param( 'date' ) );
		$employees = $request->get_param( 'employees' );

		$settings_check = $this->validate_date_rules( $date );
		if ( is_wp_error( $settings_check ) ) {
			return $settings_check;
		}

		if ( empty( $employees ) || ! is_array( $employees ) ) {
			return new WP_Error( 'invalid_employees', __( 'Employees are required.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$found        = array();
		$existing_map = array();
		foreach ( $employees as $employee_id ) {
			$employee_id = absint( $employee_id );
			if ( ! $employee_id ) {
				continue;
			}
			$existing_id = $this->find_attendance( $employee_id, $date );
			if ( $existing_id ) {
				$found[]                      = $employee_id;
				$existing_map[ $employee_id ] = $existing_id;
			}
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'existing' => $found,
				'records'  => $existing_map,
			),
			200
		);
	}

	/**
	 * Validate request common parameters.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return array|WP_Error
	 */
	protected function validate_request( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'aic_builderp' ), array( 'status' => 403 ) );
		}

		$date           = sanitize_text_field( $request->get_param( 'date' ) );
		$site_id        = absint( $request->get_param( 'site_id' ) );
		$override       = (bool) $request->get_param( 'override_duplicates' );
		$settings_check = $this->validate_date_rules( $date );
		if ( is_wp_error( $settings_check ) ) {
			return $settings_check;
		}

		$settings = $this->get_attendance_settings();
		if ( 1 === (int) $settings['require_site'] && ! $site_id ) {
			return new WP_Error( 'site_required', __( 'Please select a site.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		return array(
			'date'                => $date,
			'site_id'             => $site_id,
			'override_duplicates' => $override,
		);
	}

	/**
	 * List attendance records for view tab.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_records( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'aic_builderp' ), array( 'status' => 403 ) );
		}

		$employee_id = absint( $request->get_param( 'employee_id' ) );
		$month       = sanitize_text_field( $request->get_param( 'month' ) );
		$today       = gmdate( 'Y-m-d' );

		if ( $employee_id > 0 ) {
			$month      = $this->normalize_month( $month );
			$start_date = $month . '-01';
			$end_date   = gmdate( 'Y-m-t', strtotime( $start_date ) );
		} else {
			$end_date   = $today;
			$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $today ) ) );
		}

		$meta_query = array(
			array(
				'key'     => '_berp_date',
				'value'   => array( $start_date, $end_date ),
				'compare' => 'BETWEEN',
				'type'    => 'CHAR',
			),
		);

		if ( $employee_id > 0 ) {
			$meta_query[] = array(
				'key'     => '_berp_employee_id',
				'value'   => $employee_id,
				'compare' => '=',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'berp_attendance',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
				'meta_key'       => '_berp_date',
				'meta_query'     => $meta_query,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$records             = array();
		$total_overtime      = 0.0;
		$total_days          = 0;
		$total_paid_holidays = 0;
		foreach ( $query->posts as $post_id ) {
			$emp_id    = (int) get_post_meta( $post_id, '_berp_employee_id', true );
			$emp_name  = $emp_id ? get_post_field( 'post_title', $emp_id ) : '';
			$emp_code  = $emp_id ? get_post_meta( $emp_id, '_berp_employee_id', true ) : '';
			$date      = get_post_meta( $post_id, '_berp_date', true );
			$site_id   = (int) get_post_meta( $post_id, '_berp_site_id', true );
			$site_name = $site_id ? get_post_field( 'post_title', $site_id ) : '';
			$overtime  = get_post_meta( $post_id, '_berp_overtime_hours', true );
			$notes     = get_post_meta( $post_id, '_berp_notes', true );

			// Day flags (weekend/holiday/payable) for richer client display and totals.
			$flags = $this->get_day_flags( $emp_id, $date );

			// Accumulate totals when viewing a single employee for the period.
			if ( $employee_id > 0 ) {
				$total_overtime += floatval( $overtime );
				++$total_days;
				// Count paid holidays: either a holiday flagged, or a weekend that is payable.
				if ( ( isset( $flags['is_holiday'] ) && $flags['is_holiday'] ) || ( isset( $flags['is_weekend'] ) && $flags['is_weekend'] && isset( $flags['weekend_payable'] ) && $flags['weekend_payable'] ) ) {
					++$total_paid_holidays;
				}
			}

			$records[] = array(
				'id'              => $post_id,
				'date'            => $date,
				'employee_id'     => $emp_id,
				'employee_name'   => $emp_name,
				'employee_code'   => $emp_code,
				'site_id'         => $site_id,
				'site'            => $site_name,
				'overtime'        => $overtime,
				'is_weekend'      => isset( $flags['is_weekend'] ) ? (bool) $flags['is_weekend'] : false,
				'is_holiday'      => isset( $flags['is_holiday'] ) ? (bool) $flags['is_holiday'] : false,
				'weekend_payable' => isset( $flags['weekend_payable'] ) ? (bool) $flags['weekend_payable'] : false,
				'notes'           => $notes,
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'start'   => $start_date,
				'end'     => $end_date,
				'records' => $records,
				'totals'  => $employee_id > 0 ? array(
					'overtime'      => $total_overtime,
					'days'          => $total_days,
					'paid_holidays' => $total_paid_holidays,
				) : null,
			),
			200
		);
	}

	/**
	 * Normalize a month string to YYYY-MM.
	 *
	 * @since 1.0.0
	 * @param string $month Month string.
	 * @return string
	 */
	protected function normalize_month( $month ) {
		if ( preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			return $month;
		}
		return gmdate( 'Y-m' );
	}

	/**
	 * Validate date based on settings.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return true|WP_Error
	 */
	protected function validate_date_rules( $date ) {
		if ( empty( $date ) ) {
			return new WP_Error( 'invalid_date', __( 'Date is required.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$settings = $this->get_attendance_settings();
		$today_ts = current_datetime()->getTimestamp();
		$date_ts  = strtotime( $date );

		if ( false === $date_ts ) {
			return new WP_Error( 'invalid_date', __( 'Invalid date format.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		if ( 1 === (int) $settings['block_future'] && $date_ts > $today_ts ) {
			return new WP_Error( 'future_blocked', __( 'Cannot log attendance for future dates.', 'aic_builderp' ), array( 'status' => 400 ) );
		}

		$editable_limit = isset( $settings['editable_days_limit'] ) ? absint( $settings['editable_days_limit'] ) : 0;
		if ( $editable_limit > 0 ) {
			$diff_days = floor( ( $today_ts - $date_ts ) / DAY_IN_SECONDS );
			if ( $diff_days > $editable_limit ) {
				return new WP_Error(
					'edit_window_closed',
					sprintf(
						/* translators: %d: number of days */
						__( 'Attendance older than %d days cannot be logged or edited.', 'aic_builderp' ),
						$editable_limit
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Create attendance post and meta.
	 *
	 * @since 1.0.0
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date.
	 * @param int    $site_id Site ID.
	 * @param float  $overtime Overtime hours.
	 * @param string $notes Notes.
	 * @param int    $user_id User logging.
	 * @return int|WP_Error
	 */
	protected function create_attendance_post( $employee_id, $date, $site_id, $overtime, $notes, $user_id ) {
		$title = sprintf(
			/* translators: 1: employee id 2: date */
			__( 'Attendance - %1$s - %2$s', 'aic_builderp' ),
			$employee_id,
			$date
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_attendance',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->update_attendance_meta( $post_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id );

		return $post_id;
	}

	/**
	 * Update attendance meta.
	 *
	 * @since 1.0.0
	 * @param int         $attendance_id Attendance post ID.
	 * @param int|null    $employee_id Employee ID.
	 * @param string|null $date Date string.
	 * @param int         $site_id Site ID.
	 * @param float       $overtime Overtime hours.
	 * @param string      $notes Notes.
	 * @param int         $user_id User ID.
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

		// Weekend/holiday flags for downstream payroll logic.
		$flags = $this->get_day_flags( $employee_id ? $employee_id : (int) get_post_meta( $attendance_id, '_berp_employee_id', true ), $date );
		update_post_meta( $attendance_id, '_berp_is_weekend', $flags['is_weekend'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_is_holiday', $flags['is_holiday'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_weekend_payable', $flags['weekend_payable'] ? 1 : 0 );
	}

	/**
	 * Find attendance by employee/date.
	 *
	 * @since 1.0.0
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date.
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
	 * Normalize the stored site/employee selection structure.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw user meta value.
	 * @return array
	 */
	protected function normalize_site_employee_selections( $value ) {
		$value      = is_array( $value ) ? $value : array();
		$normalized = array();

		foreach ( $value as $site_id => $employee_ids ) {
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

			$normalized[ (string) $site_id ] = $employee_ids;
		}

		return $normalized;
	}

	/**
	 * Store last-selected employees for a site (per user).
	 *
	 * @since 1.0.0
	 * @param int   $user_id User ID.
	 * @param int   $site_id Site ID.
	 * @param array $employee_ids Employee IDs.
	 * @param bool  $merge Whether to merge with existing selection.
	 * @return void
	 */
	protected function update_user_site_employee_selections( $user_id, $site_id, $employee_ids, $merge ) {
		$user_id = absint( $user_id );
		$site_id = absint( $site_id );
		if ( ! $user_id || ! $site_id ) {
			return;
		}

		$employee_ids = is_array( $employee_ids ) ? $employee_ids : array();
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
			return;
		}

		$selections = $this->normalize_site_employee_selections(
			get_user_meta( $user_id, '_berp_attendance_site_employee_selections', true )
		);

		$key = (string) $site_id;

		if ( $merge && isset( $selections[ $key ] ) && is_array( $selections[ $key ] ) ) {
			$employee_ids = array_values(
				array_unique(
					array_merge(
						$selections[ $key ],
						$employee_ids
					)
				)
			);
		}

		$selections[ $key ] = $employee_ids;

		update_user_meta( $user_id, '_berp_attendance_site_employee_selections', $selections );
	}

	/**
	 * Get attendance settings with defaults.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_attendance_settings() {
		$settings = get_option( 'berp_settings', array() );
		$defaults = array(
			'attendance' => array(
				'editable_days_limit' => 7,
				'block_future'        => 1,
				'require_site'        => 0,
			),
		);

		$settings = wp_parse_args( $settings, $defaults );
		return isset( $settings['attendance'] ) && is_array( $settings['attendance'] ) ? wp_parse_args( $settings['attendance'], $defaults['attendance'] ) : $defaults['attendance'];
	}

	/**
	 * Get payroll settings (weekend/holiday rules).
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
	 * Get day flags (weekend/holiday/payable).
	 *
	 * @since 1.0.0
	 * @param int    $employee_id Employee ID.
	 * @param string $date        Date string.
	 * @return array
	 */
	protected function get_day_flags( $employee_id, $date ) {
		$payroll = $this->get_payroll_settings();

		$is_weekend = $this->is_weekend( $date, $payroll );
		$is_holiday = $this->is_holiday( $date, $payroll );

		$weekend_payable = true;
		if ( $is_weekend ) {
			$weekend_payable = false;
			if ( ! empty( $payroll['weekend_payment_rule'] ) ) {
				// Pay weekends if there is attendance on neighboring days.
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
	 * Determine if date is weekend.
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
	 * Determine if date is holiday.
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
}
