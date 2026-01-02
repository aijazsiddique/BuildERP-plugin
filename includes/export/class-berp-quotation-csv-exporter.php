<?php
/**
 * Quotation CSV Exporter
 *
 * Handles exporting quotations to CSV format.
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
 * BERP_Quotation_CSV_Exporter Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_CSV_Exporter {

	/**
	 * Export quotations to CSV
	 *
	 * @since 1.0.0
	 * @param array $quotations Array of quotation posts.
	 */
	public function export( $quotations ) {
		// Set headers for CSV download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=quotations-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// UTF-8 BOM for Excel compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// CSV Headers.
		fputcsv(
			$output,
			array(
				__( 'Quotation #', 'BuildERP' ),
				__( 'Client', 'BuildERP' ),
				__( 'Date', 'BuildERP' ),
				__( 'Valid Until', 'BuildERP' ),
				__( 'Status', 'BuildERP' ),
				__( 'Subtotal', 'BuildERP' ),
				__( 'Tax', 'BuildERP' ),
				__( 'Discount', 'BuildERP' ),
				__( 'Total', 'BuildERP' ),
			)
		);

		// Data rows.
		foreach ( $quotations as $quotation ) {
			$client_id = get_post_meta( $quotation->ID, '_berp_client_id', true );
			$client    = $client_id ? get_post( $client_id ) : null;

			fputcsv(
				$output,
				array(
					get_post_meta( $quotation->ID, '_berp_quotation_number', true ),
					$client ? $client->post_title : '',
					get_post_meta( $quotation->ID, '_berp_quotation_date', true ),
					get_post_meta( $quotation->ID, '_berp_validity_date', true ),
					get_post_meta( $quotation->ID, '_berp_status', true ),
					get_post_meta( $quotation->ID, '_berp_subtotal', true ),
					get_post_meta( $quotation->ID, '_berp_tax_amount', true ),
					get_post_meta( $quotation->ID, '_berp_discount_amount', true ),
					get_post_meta( $quotation->ID, '_berp_grand_total', true ),
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}


