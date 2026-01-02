<?php
/**
 * Invoice CSV Exporter
 *
 * Exports invoices to CSV format.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/export
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Invoice_CSV_Exporter Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_CSV_Exporter {

	/**
	 * Currency symbol
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $currency;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->currency = berp_get_currency_symbol();
	}

	/**
	 * Export invoices to CSV
	 *
	 * @since 1.0.0
	 */
	public function export() {
		$args = array(
			'post_type'      => 'berp_invoice',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(),
		);

		// Apply filters from request.
		if ( isset( $_GET['client_id'] ) && $_GET['client_id'] ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_client_id',
				'value' => absint( $_GET['client_id'] ),
			);
		}

		if ( isset( $_GET['site_id'] ) && $_GET['site_id'] ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_site_id',
				'value' => absint( $_GET['site_id'] ),
			);
		}

		if ( isset( $_GET['invoice_status'] ) && $_GET['invoice_status'] ) {
			$args['meta_query'][] = array(
				'key'   => '_berp_status',
				'value' => sanitize_text_field( wp_unslash( $_GET['invoice_status'] ) ),
			);
		}

		if ( isset( $_GET['date_from'] ) && $_GET['date_from'] ) {
			$args['date_query'][] = array(
				'after'     => sanitize_text_field( wp_unslash( $_GET['date_from'] ) ),
				'inclusive' => true,
			);
		}

		if ( isset( $_GET['date_to'] ) && $_GET['date_to'] ) {
			$args['date_query'][] = array(
				'before'    => sanitize_text_field( wp_unslash( $_GET['date_to'] ) ),
				'inclusive' => true,
			);
		}

		$query = new WP_Query( $args );

		// Set headers for CSV download.
		$filename = 'invoices-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Add UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write headers.
		fputcsv(
			$output,
			array(
				__( 'Invoice Number', 'BuildERP' ),
				__( 'Invoice Date', 'BuildERP' ),
				__( 'Due Date', 'BuildERP' ),
				__( 'Client', 'BuildERP' ),
				__( 'Site/Project', 'BuildERP' ),
				__( 'Status', 'BuildERP' ),
				__( 'Reference', 'BuildERP' ),
				__( 'PO Number', 'BuildERP' ),
				__( 'Subtotal', 'BuildERP' ),
				__( 'Tax', 'BuildERP' ),
				__( 'Discount', 'BuildERP' ),
				__( 'Grand Total', 'BuildERP' ),
				__( 'Amount Paid', 'BuildERP' ),
				__( 'Amount Due', 'BuildERP' ),
				__( 'Days Overdue', 'BuildERP' ),
				__( 'Payments Count', 'BuildERP' ),
				__( 'Created Date', 'BuildERP' ),
			)
		);

		// Write data rows.
		foreach ( $query->posts as $post ) {
			$invoice_id = $post->ID;

			$client_id = get_post_meta( $invoice_id, '_berp_client_id', true );
			$site_id   = get_post_meta( $invoice_id, '_berp_site_id', true );
			$client    = $client_id ? get_post( $client_id ) : null;
			$site      = $site_id ? get_post( $site_id ) : null;
			$status    = get_post_meta( $invoice_id, '_berp_status', true );
			$payments  = get_post_meta( $invoice_id, '_berp_payments', true );

			fputcsv(
				$output,
				array(
					get_post_meta( $invoice_id, '_berp_invoice_number', true ),
					get_post_meta( $invoice_id, '_berp_invoice_date', true ),
					get_post_meta( $invoice_id, '_berp_due_date', true ),
					$client ? $client->post_title : '',
					$site ? $site->post_title : '',
					berp_get_invoice_status_label( $status ),
					get_post_meta( $invoice_id, '_berp_reference', true ),
					get_post_meta( $invoice_id, '_berp_po_number', true ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_subtotal', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_tax_total', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_discount', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_amount_paid', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_amount_due', true ) ), 2 ),
					berp_get_invoice_days_overdue( $invoice_id ),
					is_array( $payments ) ? count( $payments ) : 0,
					$post->post_date,
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Export payments to CSV
	 *
	 * @since 1.0.0
	 * @param int $invoice_id Optional. Invoice ID to export payments for.
	 */
	public function export_payments( $invoice_id = 0 ) {
		$args = array(
			'post_type'      => 'berp_invoice',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $invoice_id ) {
			$args['post__in'] = array( $invoice_id );
		}

		$query = new WP_Query( $args );

		// Set headers for CSV download.
		$filename = 'payments-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Add UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write headers.
		fputcsv(
			$output,
			array(
				__( 'Invoice Number', 'BuildERP' ),
				__( 'Client', 'BuildERP' ),
				__( 'Payment Date', 'BuildERP' ),
				__( 'Amount', 'BuildERP' ),
				__( 'Method', 'BuildERP' ),
				__( 'Reference', 'BuildERP' ),
				__( 'Notes', 'BuildERP' ),
			)
		);

		$payment_methods = array(
			'cash'          => __( 'Cash', 'BuildERP' ),
			'check'         => __( 'Check', 'BuildERP' ),
			'bank_transfer' => __( 'Bank Transfer', 'BuildERP' ),
			'credit_card'   => __( 'Credit Card', 'BuildERP' ),
			'other'         => __( 'Other', 'BuildERP' ),
		);

		// Write data rows.
		foreach ( $query->posts as $post ) {
			$invoice_number = get_post_meta( $post->ID, '_berp_invoice_number', true );
			$client_id      = get_post_meta( $post->ID, '_berp_client_id', true );
			$client         = $client_id ? get_post( $client_id ) : null;
			$client_name    = $client ? $client->post_title : '';
			$payments       = get_post_meta( $post->ID, '_berp_payments', true );

			if ( ! is_array( $payments ) || empty( $payments ) ) {
				continue;
			}

			foreach ( $payments as $payment ) {
				$method       = isset( $payment['method'] ) ? $payment['method'] : '';
				$method_label = isset( $payment_methods[ $method ] ) ? $payment_methods[ $method ] : $method;

				fputcsv(
					$output,
					array(
						$invoice_number,
						$client_name,
						isset( $payment['date'] ) ? $payment['date'] : '',
						number_format( isset( $payment['amount'] ) ? floatval( $payment['amount'] ) : 0, 2 ),
						$method_label,
						isset( $payment['reference'] ) ? $payment['reference'] : '',
						isset( $payment['notes'] ) ? $payment['notes'] : '',
					)
				);
			}
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Export aging report to CSV
	 *
	 * @since 1.0.0
	 */
	public function export_aging_report() {
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

		$query = new WP_Query( $args );

		// Set headers for CSV download.
		$filename = 'aging-report-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Add UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write headers.
		fputcsv(
			$output,
			array(
				__( 'Invoice Number', 'BuildERP' ),
				__( 'Client', 'BuildERP' ),
				__( 'Invoice Date', 'BuildERP' ),
				__( 'Due Date', 'BuildERP' ),
				__( 'Days Overdue', 'BuildERP' ),
				__( 'Aging Bucket', 'BuildERP' ),
				__( 'Grand Total', 'BuildERP' ),
				__( 'Amount Paid', 'BuildERP' ),
				__( 'Amount Due', 'BuildERP' ),
			)
		);

		$today = strtotime( 'today' );

		// Write data rows.
		foreach ( $query->posts as $post ) {
			$invoice_id = $post->ID;
			$client_id  = get_post_meta( $invoice_id, '_berp_client_id', true );
			$client     = $client_id ? get_post( $client_id ) : null;
			$due_date   = get_post_meta( $invoice_id, '_berp_due_date', true );
			$amount_due = floatval( get_post_meta( $invoice_id, '_berp_amount_due', true ) );

			if ( ! $due_date || $amount_due <= 0 ) {
				continue;
			}

			$due_timestamp = strtotime( $due_date );
			$days_overdue  = floor( ( $today - $due_timestamp ) / DAY_IN_SECONDS );

			// Determine aging bucket.
			if ( $days_overdue <= 0 ) {
				$bucket = __( 'Current', 'BuildERP' );
			} elseif ( $days_overdue <= 30 ) {
				$bucket = __( '1-30 Days', 'BuildERP' );
			} elseif ( $days_overdue <= 60 ) {
				$bucket = __( '31-60 Days', 'BuildERP' );
			} elseif ( $days_overdue <= 90 ) {
				$bucket = __( '61-90 Days', 'BuildERP' );
			} else {
				$bucket = __( '90+ Days', 'BuildERP' );
			}

			fputcsv(
				$output,
				array(
					get_post_meta( $invoice_id, '_berp_invoice_number', true ),
					$client ? $client->post_title : '',
					get_post_meta( $invoice_id, '_berp_invoice_date', true ),
					$due_date,
					max( 0, $days_overdue ),
					$bucket,
					number_format( floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) ), 2 ),
					number_format( floatval( get_post_meta( $invoice_id, '_berp_amount_paid', true ) ), 2 ),
					number_format( $amount_due, 2 ),
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}


