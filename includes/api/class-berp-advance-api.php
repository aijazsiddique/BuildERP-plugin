<?php
/**
 * Advance Request REST API Endpoints
 *
 * Handles REST API endpoints for advance requests.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/api
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Advance_API Class
 *
 * @since 1.0.0
 */
class BERP_Advance_API {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Create advance request endpoint (for portal).
		register_rest_route(
			'berp/v1',
			'/advance/request',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_request' ),
				'permission_callback' => array( $this, 'can_request_advance' ),
			)
		);

		// Get employee's advance requests.
		register_rest_route(
			'berp/v1',
			'/advance/my-requests',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_my_requests' ),
				'permission_callback' => array( $this, 'can_request_advance' ),
			)
		);

		// Admin: Get all advance requests.
		register_rest_route(
			'berp/v1',
			'/advance/list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_requests' ),
				'permission_callback' => function () {
					return current_user_can( 'berp_manage_advances' );
				},
			)
		);

		// Admin: Approve/reject advance request.
		register_rest_route(
			'berp/v1',
			'/advance/(?P<id>\d+)/status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => function () {
					return current_user_can( 'berp_manage_advances' );
				},
			)
		);

		// Get pending advances count (for dashboard).
		register_rest_route(
			'berp/v1',
			'/advance/pending-count',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_pending_count' ),
				'permission_callback' => function () {
					return current_user_can( 'berp_view_advances' );
				},
			)
		);
	}

	/**
	 * Check if current user can request advance
	 *
	 * @return bool
	 */
	public function can_request_advance() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$employee_id = get_user_meta( get_current_user_id(), '_berp_employee_id', true );
		return ! empty( $employee_id );
	}

	/**
	 * Create advance request (from portal)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function create_request( WP_REST_Request $request ) {
		$data        = $request->get_json_params();
		$user_id     = get_current_user_id();
		$employee_id = get_user_meta( $user_id, '_berp_employee_id', true );

		if ( ! $employee_id ) {
			return new WP_Error(
				'berp_not_employee',
				__( 'You are not linked to an employee record.', 'aic_builderp' ),
				array( 'status' => 403 )
			);
		}

		// Validate amount.
		$amount = isset( $data['amount'] ) ? floatval( $data['amount'] ) : 0;
		if ( $amount <= 0 ) {
			return new WP_Error(
				'berp_invalid_amount',
				__( 'Amount must be greater than zero.', 'aic_builderp' ),
				array( 'status' => 400 )
			);
		}

		// Check against max advance percentage.
		$max_percent  = berp_get_employee_advance_max_percent_setting();
		$basic_salary = get_post_meta( $employee_id, '_berp_basic_salary', true );

		if ( $basic_salary ) {
			$max_amount = ( $basic_salary * $max_percent ) / 100;
			if ( $amount > $max_amount ) {
				return new WP_Error(
					'berp_exceeds_max',
					sprintf(
						/* translators: 1: max percentage, 2: max amount */
						__( 'Amount exceeds maximum allowed (%1$d%% of salary = %2$s).', 'aic_builderp' ),
						$max_percent,
						berp_format_currency( $max_amount )
					),
					array( 'status' => 400 )
				);
			}
		}

		// Check if there's already a pending request.
		$existing = get_posts(
			array(
				'post_type'      => 'berp_advance',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_berp_employee_id',
						'value' => $employee_id,
					),
					array(
						'key'   => '_berp_advance_status',
						'value' => 'pending',
					),
				),
			)
		);

		if ( ! empty( $existing ) ) {
			return new WP_Error(
				'berp_pending_exists',
				__( 'You already have a pending advance request. Please wait for it to be processed.', 'aic_builderp' ),
				array( 'status' => 400 )
			);
		}

		// Determine initial status.
		$requires_approval = berp_get_employee_advance_requires_approval_setting();
		$initial_status    = $requires_approval ? 'pending' : 'approved';

		// Get employee name for title.
		$employee = get_post( $employee_id );
		$title    = sprintf(
			/* translators: 1: employee name, 2: date */
			__( 'Advance Request - %1$s - %2$s', 'aic_builderp' ),
			$employee ? $employee->post_title : 'Unknown',
			current_time( 'Y-m-d' )
		);

		// Create the advance request.
		$advance_id = wp_insert_post(
			array(
				'post_type'   => 'berp_advance',
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_author' => $user_id,
			)
		);

		if ( is_wp_error( $advance_id ) ) {
			return $advance_id;
		}

		// Save meta.
		update_post_meta( $advance_id, '_berp_employee_id', $employee_id );
		update_post_meta( $advance_id, '_berp_advance_amount', $amount );
		update_post_meta( $advance_id, '_berp_advance_reason', sanitize_textarea_field( $data['reason'] ?? '' ) );
		update_post_meta( $advance_id, '_berp_request_date', current_time( 'Y-m-d' ) );
		update_post_meta( $advance_id, '_berp_advance_status', $initial_status );

		// Repayment settings.
		$allow_installments = berp_get_employee_allow_installments_setting();
		$repayment_type     = ( $allow_installments && isset( $data['repayment_type'] ) ) ? $data['repayment_type'] : 'full';
		$installments       = ( 'installments' === $repayment_type && isset( $data['installments'] ) ) ? min( 12, max( 1, absint( $data['installments'] ) ) ) : 1;

		update_post_meta( $advance_id, '_berp_repayment_type', $repayment_type );
		update_post_meta( $advance_id, '_berp_installments', $installments );

		// If auto-approved, update employee balance.
		if ( 'approved' === $initial_status ) {
			update_post_meta( $advance_id, '_berp_approved_date', current_time( 'Y-m-d' ) );
			$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
			$new_balance     = $current_balance - $amount;
			update_post_meta( $employee_id, '_berp_account_balance', $new_balance );

			/**
			 * Fires when an advance request is auto-approved.
			 *
			 * @param int   $advance_id  Advance post ID.
			 * @param int   $employee_id Employee post ID.
			 * @param float $amount      Advance amount.
			 */
			do_action( 'berp_advance_approved', $advance_id, $employee_id, $amount );
		}

		/**
		 * Fires when a new advance request is created.
		 *
		 * @param int   $advance_id  Advance post ID.
		 * @param int   $employee_id Employee post ID.
		 * @param array $data        Request data.
		 */
		do_action( 'berp_advance_request_created', $advance_id, $employee_id, $data );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => $requires_approval
					? __( 'Your advance request has been submitted and is pending approval.', 'aic_builderp' )
					: __( 'Your advance request has been approved.', 'aic_builderp' ),
				'advance_id' => $advance_id,
				'status'     => $initial_status,
			),
			201
		);
	}

	/**
	 * Get current employee's advance requests
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_my_requests( WP_REST_Request $request ) {
		$user_id     = get_current_user_id();
		$employee_id = get_user_meta( $user_id, '_berp_employee_id', true );

		$args = array(
			'post_type'      => 'berp_advance',
			'posts_per_page' => 20,
			'meta_key'       => '_berp_request_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_berp_employee_id',
					'value' => $employee_id,
				),
			),
		);

		$query    = new WP_Query( $args );
		$requests = array();

		foreach ( $query->posts as $post ) {
			$requests[] = array(
				'id'             => $post->ID,
				'amount'         => (float) get_post_meta( $post->ID, '_berp_advance_amount', true ),
				'reason'         => get_post_meta( $post->ID, '_berp_advance_reason', true ),
				'request_date'   => get_post_meta( $post->ID, '_berp_request_date', true ),
				'status'         => get_post_meta( $post->ID, '_berp_advance_status', true ),
				'repayment_type' => get_post_meta( $post->ID, '_berp_repayment_type', true ),
				'installments'   => (int) get_post_meta( $post->ID, '_berp_installments', true ),
				'approved_date'  => get_post_meta( $post->ID, '_berp_approved_date', true ),
				'rejection_note' => get_post_meta( $post->ID, '_berp_rejection_note', true ),
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'requests' => $requests,
			),
			200
		);
	}

	/**
	 * Get all advance requests (admin)
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_all_requests( WP_REST_Request $request ) {
		$status      = $request->get_param( 'status' );
		$employee_id = $request->get_param( 'employee_id' );

		$args = array(
			'post_type'      => 'berp_advance',
			'posts_per_page' => 50,
			'meta_key'       => '_berp_request_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(),
		);

		if ( $status ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_advance_status',
				'value' => $status,
			);
		}

		if ( $employee_id ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_employee_id',
				'value' => absint( $employee_id ),
			);
		}

		$query    = new WP_Query( $args );
		$requests = array();

		foreach ( $query->posts as $post ) {
			$emp_id   = get_post_meta( $post->ID, '_berp_advance_id', true );
			$employee = get_post( get_post_meta( $post->ID, '_berp_employee_id', true ) );

			$requests[] = array(
				'id'             => $post->ID,
				'employee_id'    => get_post_meta( $post->ID, '_berp_employee_id', true ),
				'employee_name'  => $employee ? $employee->post_title : '—',
				'amount'         => (float) get_post_meta( $post->ID, '_berp_advance_amount', true ),
				'reason'         => get_post_meta( $post->ID, '_berp_advance_reason', true ),
				'request_date'   => get_post_meta( $post->ID, '_berp_request_date', true ),
				'status'         => get_post_meta( $post->ID, '_berp_advance_status', true ),
				'repayment_type' => get_post_meta( $post->ID, '_berp_repayment_type', true ),
				'installments'   => (int) get_post_meta( $post->ID, '_berp_installments', true ),
				'approved_date'  => get_post_meta( $post->ID, '_berp_approved_date', true ),
				'rejection_note' => get_post_meta( $post->ID, '_berp_rejection_note', true ),
				'edit_link'      => get_edit_post_link( $post->ID, 'raw' ),
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'requests' => $requests,
				'total'    => $query->found_posts,
			),
			200
		);
	}

	/**
	 * Update advance request status
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_status( WP_REST_Request $request ) {
		$advance_id = absint( $request->get_param( 'id' ) );
		$data       = $request->get_json_params();
		$new_status = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '';

		if ( ! in_array( $new_status, array( 'approved', 'rejected' ), true ) ) {
			return new WP_Error(
				'berp_invalid_status',
				__( 'Invalid status. Must be "approved" or "rejected".', 'aic_builderp' ),
				array( 'status' => 400 )
			);
		}

		$advance = get_post( $advance_id );
		if ( ! $advance || 'berp_advance' !== $advance->post_type ) {
			return new WP_Error(
				'berp_not_found',
				__( 'Advance request not found.', 'aic_builderp' ),
				array( 'status' => 404 )
			);
		}

		$current_status = get_post_meta( $advance_id, '_berp_advance_status', true );
		if ( 'pending' !== $current_status ) {
			return new WP_Error(
				'berp_already_processed',
				__( 'This request has already been processed.', 'aic_builderp' ),
				array( 'status' => 400 )
			);
		}

		// Update status.
		update_post_meta( $advance_id, '_berp_advance_status', $new_status );

		if ( 'approved' === $new_status ) {
			update_post_meta( $advance_id, '_berp_approved_date', current_time( 'Y-m-d' ) );
			update_post_meta( $advance_id, '_berp_approved_by', get_current_user_id() );

			// Update employee balance.
			$employee_id = get_post_meta( $advance_id, '_berp_employee_id', true );
			$amount      = get_post_meta( $advance_id, '_berp_advance_amount', true );
			if ( $employee_id && $amount ) {
				$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
				$new_balance     = $current_balance - floatval( $amount );
				update_post_meta( $employee_id, '_berp_account_balance', $new_balance );
			}

			do_action( 'berp_advance_approved', $advance_id, $employee_id, $amount );
		} else {
			$rejection_note = isset( $data['rejection_note'] ) ? sanitize_textarea_field( $data['rejection_note'] ) : '';
			update_post_meta( $advance_id, '_berp_rejection_note', $rejection_note );
			do_action( 'berp_advance_rejected', $advance_id, $rejection_note );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'approved' === $new_status
					? __( 'Advance request approved.', 'aic_builderp' )
					: __( 'Advance request rejected.', 'aic_builderp' ),
				'status'  => $new_status,
			),
			200
		);
	}

	/**
	 * Get pending advances count
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_pending_count( WP_REST_Request $request ) {
		$args = array(
			'post_type'      => 'berp_advance',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_berp_advance_status',
					'value' => 'pending',
				),
			),
		);

		$query = new WP_Query( $args );

		return new WP_REST_Response(
			array(
				'success' => true,
				'count'   => $query->found_posts,
			),
			200
		);
	}
}
