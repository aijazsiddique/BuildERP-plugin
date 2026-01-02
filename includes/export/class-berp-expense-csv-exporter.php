<?php
/**
 * Expense CSV Exporter
 *
 * Handles exporting expenses to CSV format.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/export
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Expense_CSV_Exporter Class
 *
 * @since 1.0.0
 */
class BERP_Expense_CSV_Exporter {

	/**
	 * Export expenses to CSV
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments for filtering expenses.
	 */
	public static function export( $args = array() ) {
		// Build query arguments
		$default_args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_berp_expense_date',
			'order'          => 'DESC',
		);

		$query_args = wp_parse_args( $args, $default_args );

		// Get expenses
		$expenses = get_posts( $query_args );

		// Get currency symbol
		$currency_symbol = berp_get_currency_symbol();

		// Prepare CSV data
		$csv_data = array();

		// Headers
		$csv_data[] = array(
			__( 'Title', 'builderp' ),
			__( 'Date', 'builderp' ),
			__( 'Amount', 'builderp' ),
			__( 'Category', 'builderp' ),
			__( 'Site', 'builderp' ),
			__( 'Payment Method', 'builderp' ),
			__( 'Description', 'builderp' ),
			__( 'Recurring', 'builderp' ),
		);

		// Data rows
		$total_amount = 0;
		foreach ( $expenses as $expense ) {
			$amount         = floatval( get_post_meta( $expense->ID, '_berp_expense_amount', true ) );
			$category       = get_post_meta( $expense->ID, '_berp_expense_category', true );
			$site_id        = get_post_meta( $expense->ID, '_berp_site_id', true );
			$payment_method = get_post_meta( $expense->ID, '_berp_payment_method', true );
			$description    = get_post_meta( $expense->ID, '_berp_expense_description', true );
			$is_recurring   = get_post_meta( $expense->ID, '_berp_is_recurring', true );

			$site_name = '';
			if ( $site_id ) {
				$site      = get_post( $site_id );
				$site_name = $site ? $site->post_title : '';
			}

			$csv_data[] = array(
				$expense->post_title,
				get_post_meta( $expense->ID, '_berp_expense_date', true ),
				$currency_symbol . number_format( $amount, 2 ),
				ucfirst( str_replace( '_', ' ', $category ) ),
				$site_name,
				ucfirst( str_replace( '_', ' ', $payment_method ) ),
				$description,
				$is_recurring == '1' ? __( 'Yes', 'builderp' ) : __( 'No', 'builderp' ),
			);

			$total_amount += $amount;
		}

		// Add total row
		$csv_data[] = array();
		$csv_data[] = array(
			__( 'TOTAL', 'builderp' ),
			'',
			$currency_symbol . number_format( $total_amount, 2 ),
			'',
			'',
			'',
			'',
			'',
		);

		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="expenses-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output CSV
		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// Add UTF-8 BOM for Excel compatibility
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write rows
		foreach ( $csv_data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}



