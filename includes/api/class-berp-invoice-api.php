<?php
/**
 * Invoice REST API
 *
 * Provides REST API endpoints for invoice operations.
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
 * BERP_Invoice_API Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_API {

	/**
	 * REST API namespace
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $namespace = 'berp/v1';

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
		// Get single invoice.
		register_rest_route(
			$this->namespace,
			'/invoices/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_invoice' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Get all invoices.
		register_rest_route(
			$this->namespace,
			'/invoices',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_invoices' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'client_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'site_id'   => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'status'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page'  => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'page'      => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Record payment.
		register_rest_route(
			$this->namespace,
			'/invoices/(?P<id>\d+)/payments',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_payment' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'id'        => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'amount'    => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => 'floatval',
					),
					'date'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'method'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'enum'              => array( 'cash', 'check', 'bank_transfer', 'credit_card', 'other' ),
					),
					'reference' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'notes'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Get payments for an invoice.
		register_rest_route(
			$this->namespace,
			'/invoices/(?P<id>\d+)/payments',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_payments' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Delete a payment.
		register_rest_route(
			$this->namespace,
			'/invoices/(?P<id>\d+)/payments/(?P<payment_index>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_payment' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'id'            => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'payment_index' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Update invoice status.
		register_rest_route(
			$this->namespace,
			'/invoices/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'id'     => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'status' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'enum'              => array( 'draft', 'sent', 'viewed', 'partial', 'paid', 'overdue' ),
					),
				),
			)
		);

		// Get aging report.
		register_rest_route(
			$this->namespace,
			'/invoices/aging',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_aging_report' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'client_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Generate invoice number.
		register_rest_route(
			$this->namespace,
			'/invoices/generate-number',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'generate_number' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Check read permission
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_read_permission() {
		return current_user_can( 'berp_view_invoices' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Check write permission
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_write_permission() {
		return current_user_can( 'berp_manage_invoices' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Get single invoice
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_invoice( $request ) {
		$invoice_id = absint( $request->get_param( 'id' ) );
		$invoice    = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error(
				'invoice_not_found',
				__( 'Invoice not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $this->format_invoice( $invoice_id ) );
	}

	/**
	 * Get all invoices
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_invoices( $request ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
			'post_status'    => 'publish',
			'meta_query'     => array(),
		);

		if ( $request->get_param( 'client_id' ) ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_client_id',
				'value' => $request->get_param( 'client_id' ),
			);
		}

		if ( $request->get_param( 'site_id' ) ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_site_id',
				'value' => $request->get_param( 'site_id' ),
			);
		}

		if ( $request->get_param( 'status' ) ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_status',
				'value' => $request->get_param( 'status' ),
			);
		}

		$query    = new WP_Query( $args );
		$invoices = array();

		foreach ( $query->posts as $post ) {
			$invoices[] = $this->format_invoice( $post->ID );
		}

		return rest_ensure_response(
			array(
				'invoices' => $invoices,
				'total'    => $query->found_posts,
				'pages'    => $query->max_num_pages,
			)
		);
	}

	/**
	 * Record a payment
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function record_payment( $request ) {
		$invoice_id = absint( $request->get_param( 'id' ) );
		$invoice    = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error(
				'invoice_not_found',
				__( 'Invoice not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		$amount    = floatval( $request->get_param( 'amount' ) );
		$date      = $request->get_param( 'date' ) ? $request->get_param( 'date' ) : current_time( 'Y-m-d' );
		$method    = $request->get_param( 'method' ) ? $request->get_param( 'method' ) : 'other';
		$reference = $request->get_param( 'reference' ) ? $request->get_param( 'reference' ) : '';
		$notes     = $request->get_param( 'notes' ) ? $request->get_param( 'notes' ) : '';

		if ( $amount <= 0 ) {
			return new WP_Error(
				'invalid_amount',
				__( 'Payment amount must be greater than zero', 'builderp' ),
				array( 'status' => 400 )
			);
		}

		// Get current payments.
		$payments = get_post_meta( $invoice_id, '_berp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		// Add new payment.
		$payments[] = array(
			'date'      => $date,
			'amount'    => $amount,
			'method'    => $method,
			'reference' => $reference,
			'notes'     => $notes,
		);

		update_post_meta( $invoice_id, '_berp_payments', $payments );

		// Recalculate totals.
		$grand_total = floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) );
		$amount_paid = array_sum( array_column( $payments, 'amount' ) );
		$amount_due  = max( 0, $grand_total - $amount_paid );

		update_post_meta( $invoice_id, '_berp_amount_paid', $amount_paid );
		update_post_meta( $invoice_id, '_berp_amount_due', $amount_due );

		// Update status based on payment.
		if ( $amount_due <= 0 ) {
			update_post_meta( $invoice_id, '_berp_status', 'paid' );
			update_post_meta( $invoice_id, '_berp_paid_date', current_time( 'mysql' ) );
		} elseif ( $amount_paid > 0 ) {
			update_post_meta( $invoice_id, '_berp_status', 'partial' );
		}

		do_action( 'berp_invoice_payment_recorded', $invoice_id, $amount, $payments );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Payment recorded successfully', 'builderp' ),
				'invoice' => $this->format_invoice( $invoice_id ),
			)
		);
	}

	/**
	 * Get payments for an invoice
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_payments( $request ) {
		$invoice_id = absint( $request->get_param( 'id' ) );
		$invoice    = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error(
				'invoice_not_found',
				__( 'Invoice not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		$payments = get_post_meta( $invoice_id, '_berp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		// Add index to each payment for reference.
		$payments = array_map(
			function ( $payment, $index ) {
				$payment['index'] = $index;
				return $payment;
			},
			$payments,
			array_keys( $payments )
		);

		return rest_ensure_response(
			array(
				'payments'    => $payments,
				'amount_paid' => floatval( get_post_meta( $invoice_id, '_berp_amount_paid', true ) ),
				'amount_due'  => floatval( get_post_meta( $invoice_id, '_berp_amount_due', true ) ),
			)
		);
	}

	/**
	 * Delete a payment
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_payment( $request ) {
		$invoice_id    = absint( $request->get_param( 'id' ) );
		$payment_index = absint( $request->get_param( 'payment_index' ) );
		$invoice       = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error(
				'invoice_not_found',
				__( 'Invoice not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		$payments = get_post_meta( $invoice_id, '_berp_payments', true );
		if ( ! is_array( $payments ) || ! isset( $payments[ $payment_index ] ) ) {
			return new WP_Error(
				'payment_not_found',
				__( 'Payment not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		// Remove payment.
		unset( $payments[ $payment_index ] );
		$payments = array_values( $payments ); // Re-index array.
		update_post_meta( $invoice_id, '_berp_payments', $payments );

		// Recalculate totals.
		$grand_total = floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) );
		$amount_paid = ! empty( $payments ) ? array_sum( array_column( $payments, 'amount' ) ) : 0;
		$amount_due  = max( 0, $grand_total - $amount_paid );

		update_post_meta( $invoice_id, '_berp_amount_paid', $amount_paid );
		update_post_meta( $invoice_id, '_berp_amount_due', $amount_due );

		// Update status.
		$status   = get_post_meta( $invoice_id, '_berp_status', true );
		$due_date = get_post_meta( $invoice_id, '_berp_due_date', true );
		$is_past  = $due_date && strtotime( $due_date ) < strtotime( 'today' );

		if ( $amount_due <= 0 ) {
			update_post_meta( $invoice_id, '_berp_status', 'paid' );
		} elseif ( $amount_paid > 0 ) {
			update_post_meta( $invoice_id, '_berp_status', 'partial' );
		} elseif ( $is_past ) {
			update_post_meta( $invoice_id, '_berp_status', 'overdue' );
		} elseif ( in_array( $status, array( 'paid', 'partial' ), true ) ) {
			update_post_meta( $invoice_id, '_berp_status', 'sent' );
		}

		do_action( 'berp_invoice_payment_deleted', $invoice_id, $payment_index );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Payment deleted successfully', 'builderp' ),
				'invoice' => $this->format_invoice( $invoice_id ),
			)
		);
	}

	/**
	 * Update invoice status
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_status( $request ) {
		$invoice_id = absint( $request->get_param( 'id' ) );
		$status     = sanitize_text_field( $request->get_param( 'status' ) );
		$invoice    = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error(
				'invoice_not_found',
				__( 'Invoice not found', 'builderp' ),
				array( 'status' => 404 )
			);
		}

		$old_status = get_post_meta( $invoice_id, '_berp_status', true );
		update_post_meta( $invoice_id, '_berp_status', $status );

		if ( 'paid' === $status && 'paid' !== $old_status ) {
			update_post_meta( $invoice_id, '_berp_paid_date', current_time( 'mysql' ) );
		}

		do_action( 'berp_invoice_status_changed', $invoice_id, $status, $old_status );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Status updated successfully', 'builderp' ),
				'invoice' => $this->format_invoice( $invoice_id ),
			)
		);
	}

	/**
	 * Get aging report
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_aging_report( $request ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_berp_status',
					'value'   => 'paid',
					'compare' => '!=',
				),
			),
		);

		if ( $request->get_param( 'client_id' ) ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_client_id',
				'value' => $request->get_param( 'client_id' ),
			);
		}

		$query = new WP_Query( $args );

		$aging = array(
			'current' => array(
				'invoices' => array(),
				'total'    => 0,
			),
			'1-30'    => array(
				'invoices' => array(),
				'total'    => 0,
			),
			'31-60'   => array(
				'invoices' => array(),
				'total'    => 0,
			),
			'61-90'   => array(
				'invoices' => array(),
				'total'    => 0,
			),
			'90+'     => array(
				'invoices' => array(),
				'total'    => 0,
			),
		);

		$today = strtotime( 'today' );

		foreach ( $query->posts as $post ) {
			$due_date   = get_post_meta( $post->ID, '_berp_due_date', true );
			$amount_due = floatval( get_post_meta( $post->ID, '_berp_amount_due', true ) );

			if ( ! $due_date ) {
				continue;
			}

			$due_timestamp = strtotime( $due_date );
			$days_overdue  = floor( ( $today - $due_timestamp ) / DAY_IN_SECONDS );

			$invoice_data = $this->format_invoice( $post->ID );

			if ( $days_overdue <= 0 ) {
				$aging['current']['invoices'][] = $invoice_data;
				$aging['current']['total']     += $amount_due;
			} elseif ( $days_overdue <= 30 ) {
				$aging['1-30']['invoices'][] = $invoice_data;
				$aging['1-30']['total']     += $amount_due;
			} elseif ( $days_overdue <= 60 ) {
				$aging['31-60']['invoices'][] = $invoice_data;
				$aging['31-60']['total']     += $amount_due;
			} elseif ( $days_overdue <= 90 ) {
				$aging['61-90']['invoices'][] = $invoice_data;
				$aging['61-90']['total']     += $amount_due;
			} else {
				$aging['90+']['invoices'][] = $invoice_data;
				$aging['90+']['total']     += $amount_due;
			}
		}

		$grand_total = $aging['current']['total'] + $aging['1-30']['total'] + $aging['31-60']['total'] + $aging['61-90']['total'] + $aging['90+']['total'];

		return rest_ensure_response(
			array(
				'aging'       => $aging,
				'grand_total' => $grand_total,
				'generated'   => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Generate invoice number
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	public function generate_number() {
		$number = berp_generate_invoice_number();

		return rest_ensure_response(
			array(
				'number' => $number,
			)
		);
	}

	/**
	 * Format invoice data for response
	 *
	 * @since 1.0.0
	 * @param int $invoice_id Invoice post ID.
	 * @return array Formatted invoice data.
	 */
	private function format_invoice( $invoice_id ) {
		$invoice = get_post( $invoice_id );

		$client_id = get_post_meta( $invoice_id, '_berp_client_id', true );
		$site_id   = get_post_meta( $invoice_id, '_berp_site_id', true );
		$client    = $client_id ? get_post( $client_id ) : null;
		$site      = $site_id ? get_post( $site_id ) : null;

		return array(
			'id'             => $invoice_id,
			'title'          => $invoice->post_title,
			'invoice_number' => get_post_meta( $invoice_id, '_berp_invoice_number', true ),
			'status'         => get_post_meta( $invoice_id, '_berp_status', true ),
			'status_label'   => berp_get_invoice_status_label( get_post_meta( $invoice_id, '_berp_status', true ) ),
			'client_id'      => $client_id,
			'client_name'    => $client ? $client->post_title : '',
			'site_id'        => $site_id,
			'site_name'      => $site ? $site->post_title : '',
			'invoice_date'   => get_post_meta( $invoice_id, '_berp_invoice_date', true ),
			'due_date'       => get_post_meta( $invoice_id, '_berp_due_date', true ),
			'subtotal'       => floatval( get_post_meta( $invoice_id, '_berp_subtotal', true ) ),
			'tax_total'      => floatval( get_post_meta( $invoice_id, '_berp_tax_total', true ) ),
			'discount'       => floatval( get_post_meta( $invoice_id, '_berp_discount', true ) ),
			'discount_type'  => get_post_meta( $invoice_id, '_berp_discount_type', true ),
			'grand_total'    => floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) ),
			'amount_paid'    => floatval( get_post_meta( $invoice_id, '_berp_amount_paid', true ) ),
			'amount_due'     => floatval( get_post_meta( $invoice_id, '_berp_amount_due', true ) ),
			'is_overdue'     => berp_is_invoice_overdue( $invoice_id ),
			'days_overdue'   => berp_get_invoice_days_overdue( $invoice_id ),
			'line_items'     => get_post_meta( $invoice_id, '_berp_line_items', true ) ?: array(),
			'payments_count' => count( get_post_meta( $invoice_id, '_berp_payments', true ) ?: array() ),
			'reference'      => get_post_meta( $invoice_id, '_berp_reference', true ),
			'po_number'      => get_post_meta( $invoice_id, '_berp_po_number', true ),
			'created'        => $invoice->post_date,
			'modified'       => $invoice->post_modified,
		);
	}
}

