<?php
/**
 * Expense REST API Endpoints
 *
 * Handles REST API endpoints for expense creation and reporting.
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
 * BERP_Expense_API Class
 *
 * @since 1.0.0
 */
class BERP_Expense_API {

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
		// Create expense endpoint
		register_rest_route(
			'berp/v1',
			'/expense/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_expense' ),
				'permission_callback' => function () {
					return current_user_can( 'berp_manage_expenses' );
				},
			)
		);

		// Expense breakdown endpoint
		register_rest_route(
			'berp/v1',
			'/expense/breakdown',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_breakdown' ),
				'permission_callback' => function () {
					return current_user_can( 'berp_view_reports' );
				},
			)
		);
	}

	/**
	 * Create expense via REST API
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function create_expense( WP_REST_Request $request ) {
		// Get request data
		$data = $request->get_json_params();

		// Validate required fields
		if ( empty( $data['category'] ) ) {
			return new WP_Error(
				'berp_missing_category',
				__( 'Category is required', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $data['amount'] ) || floatval( $data['amount'] ) <= 0 ) {
			return new WP_Error(
				'berp_invalid_amount',
				__( 'Amount must be greater than zero', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		// Validate category exists in settings
		$settings         = get_option( 'berp_settings', array() );
		$categories_text  = isset( $settings['expense']['default_categories'] ) ? $settings['expense']['default_categories'] : "Payroll\nMaterials\nEquipment\nSubcontractor\nOther";
		$categories       = array_filter( array_map( 'trim', explode( "\n", $categories_text ) ) );
		$valid_categories = array_map( 'strtolower', $categories );

		if ( ! in_array( strtolower( $data['category'] ), $valid_categories ) ) {
			return new WP_Error(
				'berp_invalid_category',
				__( 'Invalid category', 'BuildERP' ),
				array( 'status' => 400 )
			);
		}

		// Create expense post
		$expense_id = wp_insert_post(
			array(
				'post_type'    => 'berp_expense',
				'post_title'   => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'Expense', 'BuildERP' ),
				'post_content' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			)
		);

		if ( is_wp_error( $expense_id ) ) {
			return $expense_id;
		}

		// Save meta fields
		update_post_meta( $expense_id, '_berp_expense_date', isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : current_time( 'Y-m-d' ) );
		update_post_meta( $expense_id, '_berp_expense_amount', floatval( $data['amount'] ) );
		update_post_meta( $expense_id, '_berp_expense_category', sanitize_text_field( strtolower( $data['category'] ) ) );
		update_post_meta( $expense_id, '_berp_site_id', isset( $data['site_id'] ) ? absint( $data['site_id'] ) : 0 );
		update_post_meta( $expense_id, '_berp_payment_method', isset( $data['payment_method'] ) ? sanitize_text_field( $data['payment_method'] ) : '' );
		update_post_meta( $expense_id, '_berp_expense_description', isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '' );

		// Update site budget if site linked
		if ( ! empty( $data['site_id'] ) ) {
			$this->update_site_budget( absint( $data['site_id'] ) );
		}

		// Fire action hook
		do_action( 'berp_expense_created_via_api', $expense_id, $data );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Expense created successfully', 'BuildERP' ),
				'expense_id' => $expense_id,
			),
			201
		);
	}

	/**
	 * Get expense breakdown by category
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_breakdown( WP_REST_Request $request ) {
		global $wpdb;

		// Get query parameters
		$date_from = $request->get_param( 'date_from' );
		$date_to   = $request->get_param( 'date_to' );
		$site_id   = $request->get_param( 'site_id' );

		$date_from = $date_from ? sanitize_text_field( $date_from ) : '';
		$date_to   = $date_to ? sanitize_text_field( $date_to ) : '';
		$site_id   = $site_id ? absint( $site_id ) : 0;

		// Build base query
		$sql = "SELECT
                    pm_category.meta_value as category,
                    SUM(CAST(pm_amount.meta_value AS DECIMAL(10,2))) as total,
                    COUNT(DISTINCT p.ID) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_amount ON p.ID = pm_amount.post_id AND pm_amount.meta_key = '_berp_expense_amount'
                INNER JOIN {$wpdb->postmeta} pm_category ON p.ID = pm_category.post_id AND pm_category.meta_key = '_berp_expense_category'";

		$joins  = array();
		$where  = array();
		$params = array();

		// Add date filter if provided
		if ( $date_from || $date_to ) {
			$joins[] = "INNER JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_berp_expense_date'";
		}

		// Add site filter if provided
		if ( $site_id ) {
			$joins[] = "INNER JOIN {$wpdb->postmeta} pm_site ON p.ID = pm_site.post_id AND pm_site.meta_key = '_berp_site_id'";
		}

		$where[]  = 'p.post_type = %s';
		$params[] = 'berp_expense';
		$where[]  = 'p.post_status = %s';
		$params[] = 'publish';

		// Add date conditions
		if ( $date_from && $date_to ) {
			$where[]  = 'pm_date.meta_value BETWEEN %s AND %s';
			$params[] = $date_from;
			$params[] = $date_to;
		} elseif ( $date_from ) {
			$where[]  = 'pm_date.meta_value >= %s';
			$params[] = $date_from;
		} elseif ( $date_to ) {
			$where[]  = 'pm_date.meta_value <= %s';
			$params[] = $date_to;
		}

		// Add site condition
		if ( $site_id ) {
			$where[]  = 'pm_site.meta_value = %d';
			$params[] = $site_id;
		}

		if ( ! empty( $joins ) ) {
			$sql .= ' ' . implode( ' ', $joins );
		}

		$sql .= ' WHERE ' . implode( ' AND ', $where );
		$sql .= ' GROUP BY pm_category.meta_value ORDER BY total DESC';

		$sql = $wpdb->prepare( $sql, $params );

		// Execute query
		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return new WP_REST_Response(
				array(
					'success'   => true,
					'breakdown' => array(),
					'total'     => 0,
				),
				200
			);
		}

		// Calculate grand total
		$grand_total = 0;
		foreach ( $results as $result ) {
			$grand_total += floatval( $result->total );
		}

		// Format results
		$breakdown = array();
		foreach ( $results as $result ) {
			$breakdown[] = array(
				'category'   => ucfirst( str_replace( '_', ' ', $result->category ) ),
				'total'      => floatval( $result->total ),
				'count'      => intval( $result->count ),
				'percentage' => $grand_total > 0 ? round( ( $result->total / $grand_total ) * 100, 2 ) : 0,
			);
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'breakdown' => $breakdown,
				'total'     => $grand_total,
				'filters'   => array(
					'date_from' => $date_from,
					'date_to'   => $date_to,
					'site_id'   => $site_id,
				),
			),
			200
		);
	}

	/**
	 * Update site budget spent
	 *
	 * @since 1.0.0
	 * @param int $site_id Site post ID.
	 */
	private function update_site_budget( $site_id ) {
		if ( empty( $site_id ) ) {
			return;
		}

		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_berp_expense_amount'
            AND pm2.meta_key = '_berp_site_id'
            AND pm2.meta_value = %d
            AND p.post_type = 'berp_expense'
            AND p.post_status = 'publish'",
				$site_id
			)
		);

		update_post_meta( $site_id, '_berp_budget_spent', floatval( $total ) );
	}
}

