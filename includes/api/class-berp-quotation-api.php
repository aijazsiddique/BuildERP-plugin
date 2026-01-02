<?php
/**
 * Quotation REST API Endpoints
 *
 * Handles REST API endpoints for quotations.
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
 * BERP_Quotation_API Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_API {

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
		// Create quotation.
		register_rest_route(
			'berp/v1',
			'/quotation/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_quotation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get quotations by client.
		register_rest_route(
			'berp/v1',
			'/quotations/client/(?P<client_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_quotations_by_client' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Convert to invoice (single) - Requires Phase 11.
		register_rest_route(
			'berp/v1',
			'/quotation/(?P<id>\d+)/convert-invoice',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'convert_to_invoice' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Convert to milestone invoices - Requires Phase 11.
		register_rest_route(
			'berp/v1',
			'/quotation/(?P<id>\d+)/convert-milestones',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'convert_to_milestone_invoices' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check permission callback
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_permission() {
		return current_user_can( 'berp_manage_quotations' );
	}

	/**
	 * Create quotation via API
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_quotation( $request ) {
		$params = $request->get_json_params();

		// Validate required fields.
		if ( empty( $params['client_id'] ) ) {
			return new WP_Error( 'missing_client', __( 'Client ID is required', 'builderp' ), array( 'status' => 400 ) );
		}

		// Create quotation post.
		$quotation_data = array(
			'post_title'  => 'Auto-generated',
			'post_type'   => 'berp_quotation',
			'post_status' => 'publish',
		);

		$quotation_id = wp_insert_post( $quotation_data );

		if ( is_wp_error( $quotation_id ) ) {
			return $quotation_id;
		}

		// Generate quotation number.
		$quotation_number = berp_generate_quotation_number();

		// Update post title.
		wp_update_post(
			array(
				'ID'         => $quotation_id,
				'post_title' => $quotation_number,
			)
		);

		// Save meta data.
		update_post_meta( $quotation_id, '_berp_quotation_number', $quotation_number );
		update_post_meta( $quotation_id, '_berp_client_id', absint( $params['client_id'] ) );
		update_post_meta( $quotation_id, '_berp_quotation_date', isset( $params['quotation_date'] ) ? sanitize_text_field( $params['quotation_date'] ) : current_time( 'Y-m-d' ) );
		update_post_meta( $quotation_id, '_berp_validity_date', isset( $params['validity_date'] ) ? sanitize_text_field( $params['validity_date'] ) : '' );
		update_post_meta( $quotation_id, '_berp_status', 'draft' );
		update_post_meta( $quotation_id, '_berp_line_items', isset( $params['line_items'] ) ? $params['line_items'] : array() );
		update_post_meta( $quotation_id, '_berp_tax_rate', isset( $params['tax_rate'] ) ? floatval( $params['tax_rate'] ) : 0 );
		update_post_meta( $quotation_id, '_berp_discount_type', isset( $params['discount_type'] ) ? sanitize_text_field( $params['discount_type'] ) : 'fixed' );
		update_post_meta( $quotation_id, '_berp_discount_value', isset( $params['discount_value'] ) ? floatval( $params['discount_value'] ) : 0 );

		// Calculate totals.
		$totals = berp_calculate_quotation_totals(
			isset( $params['line_items'] ) ? $params['line_items'] : array(),
			isset( $params['tax_rate'] ) ? floatval( $params['tax_rate'] ) : 0,
			isset( $params['discount_type'] ) ? sanitize_text_field( $params['discount_type'] ) : 'fixed',
			isset( $params['discount_value'] ) ? floatval( $params['discount_value'] ) : 0
		);

		update_post_meta( $quotation_id, '_berp_subtotal', $totals['subtotal'] );
		update_post_meta( $quotation_id, '_berp_tax_amount', $totals['tax_amount'] );
		update_post_meta( $quotation_id, '_berp_discount_amount', $totals['discount_amount'] );
		update_post_meta( $quotation_id, '_berp_grand_total', $totals['grand_total'] );

		do_action( 'berp_after_quotation_created', $quotation_id, $params );

		return new WP_REST_Response(
			array(
				'success'          => true,
				'quotation_id'     => $quotation_id,
				'quotation_number' => $quotation_number,
			),
			201
		);
	}

	/**
	 * Get quotations by client
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response with quotations.
	 */
	public function get_quotations_by_client( $request ) {
		$client_id = $request['client_id'];

		$args = array(
			'post_type'      => 'berp_quotation',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_berp_client_id',
					'value'   => absint( $client_id ),
					'compare' => '=',
				),
			),
		);

		$quotations = get_posts( $args );

		$results = array();
		foreach ( $quotations as $quotation ) {
			$results[] = array(
				'id'               => $quotation->ID,
				'quotation_number' => get_post_meta( $quotation->ID, '_berp_quotation_number', true ),
				'date'             => get_post_meta( $quotation->ID, '_berp_quotation_date', true ),
				'validity_date'    => get_post_meta( $quotation->ID, '_berp_validity_date', true ),
				'status'           => get_post_meta( $quotation->ID, '_berp_status', true ),
				'grand_total'      => get_post_meta( $quotation->ID, '_berp_grand_total', true ),
			);
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Convert quotation to single invoice
	 *
	 * NOTE: This requires Phase 11 (Invoice Management) to be implemented.
	 * The berp_invoice CPT and related functionality must exist.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function convert_to_invoice( $request ) {
		$quotation_id = $request['id'];

		$quotation = berp_get_quotation( $quotation_id );
		if ( ! $quotation ) {
			return new WP_Error( 'invalid_quotation', __( 'Invalid quotation', 'builderp' ), array( 'status' => 404 ) );
		}

		// Check if already converted.
		$existing_invoices = get_post_meta( $quotation_id, '_berp_converted_to_invoices', true );
		if ( ! empty( $existing_invoices ) ) {
			return new WP_Error( 'already_converted', __( 'Quotation already converted to invoice(s)', 'builderp' ), array( 'status' => 400 ) );
		}

		// Check if berp_invoice CPT exists (Phase 11).
		if ( ! post_type_exists( 'berp_invoice' ) ) {
			return new WP_Error( 'invoice_not_available', __( 'Invoice functionality not yet implemented (Phase 11 required)', 'builderp' ), array( 'status' => 501 ) );
		}

		// Get quotation data.
		$client_id      = get_post_meta( $quotation_id, '_berp_client_id', true );
		$site_id        = get_post_meta( $quotation_id, '_berp_converted_to_site', true );
		$line_items     = get_post_meta( $quotation_id, '_berp_line_items', true );
		$tax_rate       = get_post_meta( $quotation_id, '_berp_tax_rate', true );
		$discount_type  = get_post_meta( $quotation_id, '_berp_discount_type', true );
		$discount_value = get_post_meta( $quotation_id, '_berp_discount_value', true );
		$payment_terms  = get_post_meta( $quotation_id, '_berp_payment_terms', true );

		// Generate invoice number (function should exist in Phase 11).
		if ( ! function_exists( 'berp_generate_invoice_number' ) ) {
			return new WP_Error( 'function_not_available', __( 'Invoice number generator not available (Phase 11 required)', 'builderp' ), array( 'status' => 501 ) );
		}
		$invoice_number = berp_generate_invoice_number();

		// Create invoice.
		$invoice_data = array(
			'post_title'  => $invoice_number,
			'post_type'   => 'berp_invoice',
			'post_status' => 'publish',
		);

		$invoice_id = wp_insert_post( $invoice_data );

		if ( is_wp_error( $invoice_id ) ) {
			return $invoice_id;
		}

		// Set invoice meta.
		update_post_meta( $invoice_id, '_berp_invoice_number', $invoice_number );
		update_post_meta( $invoice_id, '_berp_client_id', $client_id );
		update_post_meta( $invoice_id, '_berp_site_id', $site_id );
		update_post_meta( $invoice_id, '_berp_quotation_id', $quotation_id );
		update_post_meta( $invoice_id, '_berp_date', current_time( 'Y-m-d' ) );
		update_post_meta( $invoice_id, '_berp_due_date', gmdate( 'Y-m-d', strtotime( '+30 days' ) ) );
		update_post_meta( $invoice_id, '_berp_status', 'draft' );
		update_post_meta( $invoice_id, '_berp_line_items', $line_items );
		update_post_meta( $invoice_id, '_berp_tax_rate', $tax_rate );
		update_post_meta( $invoice_id, '_berp_discount_type', $discount_type );
		update_post_meta( $invoice_id, '_berp_discount_value', $discount_value );
		update_post_meta( $invoice_id, '_berp_payment_terms', $payment_terms );

		// Calculate and save totals.
		$totals = berp_calculate_quotation_totals( $line_items, $tax_rate, $discount_type, $discount_value );
		update_post_meta( $invoice_id, '_berp_subtotal', $totals['subtotal'] );
		update_post_meta( $invoice_id, '_berp_tax_amount', $totals['tax_amount'] );
		update_post_meta( $invoice_id, '_berp_discount_amount', $totals['discount_amount'] );
		update_post_meta( $invoice_id, '_berp_grand_total', $totals['grand_total'] );
		update_post_meta( $invoice_id, '_berp_amount_paid', 0 );
		update_post_meta( $invoice_id, '_berp_amount_due', $totals['grand_total'] );

		// Update quotation.
		update_post_meta( $quotation_id, '_berp_converted_to_invoices', array( $invoice_id ) );

		do_action( 'berp_quotation_converted_to_invoice', $quotation_id, $invoice_id );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'invoice_id'     => $invoice_id,
				'invoice_number' => $invoice_number,
			),
			201
		);
	}

	/**
	 * Convert quotation to milestone invoices
	 *
	 * NOTE: This requires Phase 11 (Invoice Management) to be implemented.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function convert_to_milestone_invoices( $request ) {
		$quotation_id = $request['id'];
		$params       = $request->get_json_params();

		$quotation = berp_get_quotation( $quotation_id );
		if ( ! $quotation ) {
			return new WP_Error( 'invalid_quotation', __( 'Invalid quotation', 'builderp' ), array( 'status' => 404 ) );
		}

		// Validate milestones.
		if ( empty( $params['milestones'] ) || ! is_array( $params['milestones'] ) ) {
			return new WP_Error( 'missing_milestones', __( 'Milestones array is required', 'builderp' ), array( 'status' => 400 ) );
		}

		// Validate milestone percentages sum to 100.
		$total_percentage = 0;
		foreach ( $params['milestones'] as $milestone ) {
			$total_percentage += floatval( isset( $milestone['percentage'] ) ? $milestone['percentage'] : 0 );
		}

		if ( abs( $total_percentage - 100 ) > 0.01 ) {
			return new WP_Error( 'invalid_percentages', __( 'Milestone percentages must sum to 100%', 'builderp' ), array( 'status' => 400 ) );
		}

		// Check if berp_invoice CPT exists (Phase 11).
		if ( ! post_type_exists( 'berp_invoice' ) ) {
			return new WP_Error( 'invoice_not_available', __( 'Invoice functionality not yet implemented (Phase 11 required)', 'builderp' ), array( 'status' => 501 ) );
		}

		// Get quotation data.
		$client_id     = get_post_meta( $quotation_id, '_berp_client_id', true );
		$site_id       = get_post_meta( $quotation_id, '_berp_converted_to_site', true );
		$grand_total   = get_post_meta( $quotation_id, '_berp_grand_total', true );
		$payment_terms = get_post_meta( $quotation_id, '_berp_payment_terms', true );

		$invoice_ids = array();

		// Create invoice for each milestone.
		foreach ( $params['milestones'] as $index => $milestone ) {
			$milestone_name       = sanitize_text_field( isset( $milestone['name'] ) ? $milestone['name'] : 'Milestone ' . ( $index + 1 ) );
			$milestone_percentage = floatval( isset( $milestone['percentage'] ) ? $milestone['percentage'] : 0 );
			$milestone_amount     = ( $grand_total * $milestone_percentage ) / 100;

			// Generate invoice number.
			if ( ! function_exists( 'berp_generate_invoice_number' ) ) {
				continue;
			}
			$invoice_number = berp_generate_invoice_number();

			// Create invoice.
			$invoice_data = array(
				'post_title'  => $invoice_number . ' - ' . $milestone_name,
				'post_type'   => 'berp_invoice',
				'post_status' => 'publish',
			);

			$invoice_id = wp_insert_post( $invoice_data );

			if ( is_wp_error( $invoice_id ) ) {
				continue;
			}

			// Create single line item for milestone.
			$milestone_line_items = array(
				array(
					'description' => $milestone_name . ' (' . $milestone_percentage . '%)',
					'quantity'    => 1,
					'rate'        => $milestone_amount,
					'amount'      => $milestone_amount,
				),
			);

			// Set invoice meta.
			update_post_meta( $invoice_id, '_berp_invoice_number', $invoice_number );
			update_post_meta( $invoice_id, '_berp_client_id', $client_id );
			update_post_meta( $invoice_id, '_berp_site_id', $site_id );
			update_post_meta( $invoice_id, '_berp_quotation_id', $quotation_id );
			update_post_meta( $invoice_id, '_berp_date', current_time( 'Y-m-d' ) );
			update_post_meta( $invoice_id, '_berp_due_date', isset( $milestone['due_date'] ) ? sanitize_text_field( $milestone['due_date'] ) : gmdate( 'Y-m-d', strtotime( '+30 days' ) ) );
			update_post_meta( $invoice_id, '_berp_status', 'draft' );
			update_post_meta( $invoice_id, '_berp_line_items', $milestone_line_items );
			update_post_meta( $invoice_id, '_berp_tax_rate', 0 );
			update_post_meta( $invoice_id, '_berp_discount_type', 'fixed' );
			update_post_meta( $invoice_id, '_berp_discount_value', 0 );
			update_post_meta( $invoice_id, '_berp_payment_terms', $payment_terms );
			update_post_meta( $invoice_id, '_berp_subtotal', $milestone_amount );
			update_post_meta( $invoice_id, '_berp_tax_amount', 0 );
			update_post_meta( $invoice_id, '_berp_discount_amount', 0 );
			update_post_meta( $invoice_id, '_berp_grand_total', $milestone_amount );
			update_post_meta( $invoice_id, '_berp_amount_paid', 0 );
			update_post_meta( $invoice_id, '_berp_amount_due', $milestone_amount );
			update_post_meta( $invoice_id, '_berp_milestone_index', $index );
			update_post_meta( $invoice_id, '_berp_milestone_name', $milestone_name );
			update_post_meta( $invoice_id, '_berp_milestone_percentage', $milestone_percentage );

			$invoice_ids[] = $invoice_id;
		}

		// Update quotation.
		update_post_meta( $quotation_id, '_berp_converted_to_invoices', $invoice_ids );

		do_action( 'berp_quotation_converted_to_milestone_invoices', $quotation_id, $invoice_ids );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'invoice_ids' => $invoice_ids,
				'count'       => count( $invoice_ids ),
			),
			201
		);
	}
}

