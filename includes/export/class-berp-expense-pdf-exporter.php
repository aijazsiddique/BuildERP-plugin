<?php
/**
 * Expense PDF Exporter
 *
 * Handles exporting expenses to PDF format using mPDF.
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
 * BERP_Expense_PDF_Exporter Class
 *
 * @since 1.0.0
 */
class BERP_Expense_PDF_Exporter {

	/**
	 * Export expenses to PDF
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

		// Get settings
		$general_settings = berp_get_general_settings();
		$company_name     = ! empty( $general_settings['company_name'] ) ? $general_settings['company_name'] : get_bloginfo( 'name' );
		$currency_symbol  = berp_get_currency_symbol();

		// Build HTML content
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<style>
				body {
					font-family: Arial, sans-serif;
					font-size: 12px;
					color: #333;
				}
				h1 {
					color: #2271b1;
					margin-bottom: 5px;
				}
				h2 {
					color: #666;
					font-size: 16px;
					margin-top: 0;
					margin-bottom: 20px;
				}
				.meta-info {
					margin-bottom: 20px;
					font-size: 11px;
					color: #666;
				}
				table {
					width: 100%;
					border-collapse: collapse;
					margin-bottom: 20px;
				}
				th {
					background-color: #2271b1;
					color: #fff;
					padding: 8px;
					text-align: left;
					font-weight: bold;
				}
				td {
					padding: 8px;
					border-bottom: 1px solid #ddd;
				}
				tr:nth-child(even) {
					background-color: #f9f9f9;
				}
				.total-row {
					font-weight: bold;
					background-color: #e8f0f8 !important;
					border-top: 2px solid #2271b1;
				}
				.total-row td {
					padding: 10px 8px;
				}
				.text-right {
					text-align: right;
				}
				.category-badge {
					display: inline-block;
					padding: 2px 6px;
					border-radius: 3px;
					color: #fff;
					font-size: 10px;
					font-weight: bold;
				}
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( $company_name ); ?></h1>
			<h2><?php esc_html_e( 'Expense Report', 'builderp' ); ?></h2>

			<div class="meta-info">
				<?php
				/* translators: %s: generated date */
				printf(
					esc_html__( 'Generated on %s', 'builderp' ),
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) )
				);
				?>
				<br>
				<?php
				if ( ! empty( $args['meta_query'] ) || ! empty( $args['date_query'] ) ) {
					esc_html_e( 'Filtered Results', 'builderp' );
				}
				?>
			</div>

			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Title', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Category', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Site', 'builderp' ); ?></th>
						<th class="text-right"><?php esc_html_e( 'Amount', 'builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$total_amount = 0;
					foreach ( $expenses as $expense ) {
						$amount   = floatval( get_post_meta( $expense->ID, '_berp_expense_amount', true ) );
						$category = get_post_meta( $expense->ID, '_berp_expense_category', true );
						$site_id  = get_post_meta( $expense->ID, '_berp_site_id', true );
						$date     = get_post_meta( $expense->ID, '_berp_expense_date', true );

						$site_name = '—';
						if ( $site_id ) {
							$site      = get_post( $site_id );
							$site_name = $site ? $site->post_title : '-';
						}

						$category_label = ucfirst( str_replace( '_', ' ', $category ) );
						$category_color = self::get_category_color( $category );

						$total_amount += $amount;
						?>
						<tr>
							<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $date ) ) ); ?></td>
							<td><?php echo esc_html( $expense->post_title ); ?></td>
							<td>
								<span class="category-badge" style="background-color: <?php echo esc_attr( $category_color ); ?>">
									<?php echo esc_html( $category_label ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $site_name ); ?></td>
							<td class="text-right"><?php echo esc_html( $currency_symbol . number_format( $amount, 2 ) ); ?></td>
						</tr>
						<?php
					}
					?>
					<tr class="total-row">
						<td colspan="4" class="text-right"><strong><?php esc_html_e( 'TOTAL:', 'builderp' ); ?></strong></td>
						<td class="text-right"><strong><?php echo esc_html( $currency_symbol . number_format( $total_amount, 2 ) ); ?></strong></td>
					</tr>
				</tbody>
			</table>

			<div class="meta-info">
				<?php
				/* translators: %d: total expenses count */
				printf(
					esc_html__( 'Total Expenses: %d', 'builderp' ),
					count( $expenses )
				);
				?>
			</div>
		</body>
		</html>
		<?php
		$html = ob_get_clean();

		// Generate PDF using mPDF
		try {
			$mpdf = new \Mpdf\Mpdf(
				array(
					'format'        => 'A4',
					'orientation'   => 'L',
					'margin_top'    => 15,
					'margin_bottom' => 15,
					'margin_left'   => 15,
					'margin_right'  => 15,
				)
			);

			$mpdf->WriteHTML( $html );
			$mpdf->Output( 'expenses-' . gmdate( 'Y-m-d-His' ) . '.pdf', 'D' );
			exit;
		} catch ( Exception $e ) {
			wp_die(
				sprintf(
					/* translators: %s: Error message */
					esc_html__( 'PDF generation failed: %s', 'builderp' ),
					esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * Get category badge color
	 *
	 * @since 1.0.0
	 * @param string $category Category slug.
	 * @return string Color code.
	 */
	private static function get_category_color( $category ) {
		$colors = array(
			'payroll'       => '#2271b1',
			'materials'     => '#00a32a',
			'equipment'     => '#f0b849',
			'subcontractor' => '#8c8f94',
			'other'         => '#d63638',
		);

		return isset( $colors[ strtolower( $category ) ] ) ? $colors[ strtolower( $category ) ] : '#72aee6';
	}
}


