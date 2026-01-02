<?php
/**
 * Quotation PDF Generator
 *
 * Handles PDF generation for quotations using mPDF library.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/pdf
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Quotation_PDF Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_PDF {

	/**
	 * mPDF instance
	 *
	 * @var \Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * Generate quotation PDF
	 *
	 * @since 1.0.0
	 * @param int    $quotation_id Quotation post ID.
	 * @param string $output_mode  Output mode: 'download', 'inline', 'save', 'string'.
	 * @return mixed PDF output or file path or WP_Error.
	 */
	public function generate( $quotation_id, $output_mode = 'download' ) {
		// Get quotation data.
		$data = $this->get_quotation_data( $quotation_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Get mPDF instance.
		$this->mpdf = $this->get_mpdf_instance();

		// Set document info.
		$this->mpdf->SetTitle( 'Quotation ' . $data['quotation_number'] );
		$this->mpdf->SetAuthor( $data['company_name'] );

		// Write HTML.
		$this->mpdf->WriteHTML( $this->get_styles() );
		$this->mpdf->WriteHTML( $this->build_html( $data ) );

		// Output based on mode.
		$filename = 'quotation-' . $data['quotation_number'] . '.pdf';

		switch ( $output_mode ) {
			case 'download':
				return $this->mpdf->Output( $filename, \Mpdf\Output\Destination::DOWNLOAD );

			case 'inline':
				return $this->mpdf->Output( $filename, \Mpdf\Output\Destination::INLINE );

			case 'save':
				$upload_dir = wp_upload_dir();
				$save_path  = $upload_dir['basedir'] . '/berp-quotations/';
				if ( ! file_exists( $save_path ) ) {
					wp_mkdir_p( $save_path );
				}
				$full_path = $save_path . $filename;
				$this->mpdf->Output( $full_path, \Mpdf\Output\Destination::FILE );
				return $full_path;

			case 'string':
				return $this->mpdf->Output( $filename, \Mpdf\Output\Destination::STRING_RETURN );

			default:
				return new WP_Error( 'invalid_output_mode', __( 'Invalid PDF output mode', 'BuildERP' ) );
		}
	}

	/**
	 * Get quotation data
	 *
	 * @since 1.0.0
	 * @param int $quotation_id Quotation post ID.
	 * @return array|WP_Error Quotation data or error.
	 */
	protected function get_quotation_data( $quotation_id ) {
		$post = berp_get_quotation( $quotation_id );

		if ( ! $post ) {
			return new WP_Error( 'invalid_quotation', __( 'Invalid quotation ID', 'BuildERP' ) );
		}

		// Get all meta data.
		$data = array(
			'quotation_number' => get_post_meta( $quotation_id, '_berp_quotation_number', true ),
			'quotation_date'   => get_post_meta( $quotation_id, '_berp_quotation_date', true ),
			'validity_date'    => get_post_meta( $quotation_id, '_berp_validity_date', true ),
			'client_id'        => get_post_meta( $quotation_id, '_berp_client_id', true ),
			'line_items'       => get_post_meta( $quotation_id, '_berp_line_items', true ),
			'subtotal'         => get_post_meta( $quotation_id, '_berp_subtotal', true ),
			'tax_rate'         => get_post_meta( $quotation_id, '_berp_tax_rate', true ),
			'tax_amount'       => get_post_meta( $quotation_id, '_berp_tax_amount', true ),
			'discount_type'    => get_post_meta( $quotation_id, '_berp_discount_type', true ),
			'discount_value'   => get_post_meta( $quotation_id, '_berp_discount_value', true ),
			'discount_amount'  => get_post_meta( $quotation_id, '_berp_discount_amount', true ),
			'grand_total'      => get_post_meta( $quotation_id, '_berp_grand_total', true ),
			'payment_terms'    => get_post_meta( $quotation_id, '_berp_payment_terms', true ),
			'notes'            => get_post_meta( $quotation_id, '_berp_notes', true ),
		);

		// Get client info.
		if ( $data['client_id'] ) {
			$client                 = get_post( $data['client_id'] );
			$data['client_name']    = $client ? $client->post_title : '';
			$data['client_company'] = get_post_meta( $data['client_id'], '_berp_company_name', true );
			$data['client_email']   = get_post_meta( $data['client_id'], '_berp_client_email', true );
			$data['client_phone']   = get_post_meta( $data['client_id'], '_berp_client_phone', true );
			$data['client_address'] = get_post_meta( $data['client_id'], '_berp_client_address', true );
		}

		// Get company info from settings.
		$general_settings        = berp_get_general_settings();
		$data['company_name']    = isset( $general_settings['company_name'] ) ? $general_settings['company_name'] : get_bloginfo( 'name' );
		$data['company_address'] = isset( $general_settings['company_address'] ) ? $general_settings['company_address'] : '';
		$data['company_phone']   = isset( $general_settings['company_phone'] ) ? $general_settings['company_phone'] : '';
		$data['company_email']   = isset( $general_settings['company_email'] ) ? $general_settings['company_email'] : get_option( 'admin_email' );
		$data['company_logo']    = isset( $general_settings['company_logo'] ) ? $general_settings['company_logo'] : '';

		// Get terms and conditions from settings.
		$quotation_settings       = berp_get_quotation_settings();
		$data['terms_conditions'] = isset( $quotation_settings['terms_conditions'] ) ? $quotation_settings['terms_conditions'] : '';

		// Ensure line_items is array.
		if ( ! is_array( $data['line_items'] ) ) {
			$data['line_items'] = array();
		}

		return apply_filters( 'berp_quotation_pdf_data', $data, $quotation_id );
	}

	/**
	 * Build HTML content for PDF
	 *
	 * @since 1.0.0
	 * @param array $data Quotation data.
	 * @return string HTML content.
	 */
	protected function build_html( $data ) {
		ob_start();
		?>
		<div class="quotation-container">
			<!-- Header -->
			<table class="header-table">
				<tr>
					<td class="company-section">
						<?php if ( ! empty( $data['company_logo'] ) ) : ?>
							<img src="<?php echo esc_url( $data['company_logo'] ); ?>" class="company-logo" />
						<?php else : ?>
							<h1 class="company-name-text"><?php echo esc_html( $data['company_name'] ); ?></h1>
						<?php endif; ?>
						<div class="company-details">
							<?php echo nl2br( esc_html( $data['company_address'] ) ); ?><br>
							<?php if ( ! empty( $data['company_phone'] ) ) : ?>
								<?php echo esc_html__( 'Phone:', 'BuildERP' ); ?> <?php echo esc_html( $data['company_phone'] ); ?><br>
							<?php endif; ?>
							<?php if ( ! empty( $data['company_email'] ) ) : ?>
								<?php echo esc_html__( 'Email:', 'BuildERP' ); ?> <?php echo esc_html( $data['company_email'] ); ?>
							<?php endif; ?>
						</div>
					</td>
					<td class="quotation-meta">
						<h1 class="doc-title"><?php echo esc_html__( 'QUOTATION', 'BuildERP' ); ?></h1>
						<table class="meta-table">
							<tr>
								<th><?php echo esc_html__( 'Quotation #:', 'BuildERP' ); ?></th>
								<td><?php echo esc_html( $data['quotation_number'] ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Date:', 'BuildERP' ); ?></th>
								<td><?php echo esc_html( gmdate( 'd M Y', strtotime( $data['quotation_date'] ) ) ); ?></td>
							</tr>
							<tr>
								<th><?php echo esc_html__( 'Valid Until:', 'BuildERP' ); ?></th>
								<td><?php echo esc_html( gmdate( 'd M Y', strtotime( $data['validity_date'] ) ) ); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<!-- Client Info -->
			<div class="client-section">
				<div class="section-label"><?php echo esc_html__( 'Quotation For:', 'BuildERP' ); ?></div>
				<div class="client-details">
					<strong><?php echo esc_html( $data['client_company'] ); ?></strong><br>
					<?php if ( ! empty( $data['client_name'] ) ) : ?>
						<?php echo esc_html__( 'Attn:', 'BuildERP' ); ?> <?php echo esc_html( $data['client_name'] ); ?><br>
					<?php endif; ?>
					<?php echo nl2br( esc_html( $data['client_address'] ) ); ?><br>
					<?php if ( ! empty( $data['client_phone'] ) ) : ?>
						<?php echo esc_html( $data['client_phone'] ); ?><br>
					<?php endif; ?>
					<?php if ( ! empty( $data['client_email'] ) ) : ?>
						<?php echo esc_html( $data['client_email'] ); ?>
					<?php endif; ?>
				</div>
			</div>

			<!-- Line Items -->
			<table class="items-table">
				<thead>
					<tr>
						<th class="col-desc"><?php echo esc_html__( 'Description', 'BuildERP' ); ?></th>
						<th class="col-qty"><?php echo esc_html__( 'Qty', 'BuildERP' ); ?></th>
						<th class="col-rate"><?php echo esc_html__( 'Rate', 'BuildERP' ); ?></th>
						<th class="col-amount"><?php echo esc_html__( 'Amount', 'BuildERP' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 0;
					foreach ( $data['line_items'] as $item ) :
						++$i;
						?>
					<tr class="<?php echo ( $i % 2 == 0 ) ? 'even' : 'odd'; ?>">
						<td class="col-desc">
							<div class="item-title"><?php echo esc_html( $item['description'] ); ?></div>
						</td>
						<td class="col-qty"><?php echo esc_html( number_format( $item['quantity'], 2 ) ); ?></td>
						<td class="col-rate"><?php echo esc_html( berp_get_currency_symbol() . number_format( $item['rate'], 2 ) ); ?></td>
						<td class="col-amount"><?php echo esc_html( berp_get_currency_symbol() . number_format( $item['amount'], 2 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Totals -->
			<table class="totals-table">
				<tr>
					<td class="label"><?php echo esc_html__( 'Subtotal', 'BuildERP' ); ?></td>
					<td class="value"><?php echo esc_html( berp_get_currency_symbol() . number_format( $data['subtotal'], 2 ) ); ?></td>
				</tr>
				<?php if ( $data['discount_amount'] > 0 ) : ?>
				<tr>
					<td class="label"><?php echo esc_html__( 'Discount', 'BuildERP' ); ?></td>
					<td class="value text-danger">-<?php echo esc_html( berp_get_currency_symbol() . number_format( $data['discount_amount'], 2 ) ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td class="label"><?php echo esc_html__( 'Tax', 'BuildERP' ); ?> (<?php echo esc_html( $data['tax_rate'] ); ?>%)</td>
					<td class="value"><?php echo esc_html( berp_get_currency_symbol() . number_format( $data['tax_amount'], 2 ) ); ?></td>
				</tr>
				<tr class="grand-total">
					<td class="label"><?php echo esc_html__( 'Total', 'BuildERP' ); ?></td>
					<td class="value"><?php echo esc_html( berp_get_currency_symbol() . number_format( $data['grand_total'], 2 ) ); ?></td>
				</tr>
			</table>

			<div style="clear: both;"></div>

			<!-- Notes & Terms -->
			<div class="footer-content">
				<?php if ( ! empty( $data['notes'] ) ) : ?>
				<div class="notes-section">
					<h3><?php echo esc_html__( 'Notes', 'BuildERP' ); ?></h3>
					<div class="content"><?php echo nl2br( esc_html( $data['notes'] ) ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['payment_terms'] ) ) : ?>
				<div class="terms-section">
					<h3><?php echo esc_html__( 'Payment Terms', 'BuildERP' ); ?></h3>
					<div class="content"><?php echo nl2br( esc_html( $data['payment_terms'] ) ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['terms_conditions'] ) ) : ?>
				<div class="terms-section">
					<h3><?php echo esc_html__( 'Terms & Conditions', 'BuildERP' ); ?></h3>
					<div class="content"><?php echo nl2br( esc_html( $data['terms_conditions'] ) ); ?></div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Signature -->
			<table class="signature-table">
				<tr>
					<td class="signature-box">
						<div class="line"></div>
						<div class="label"><?php echo esc_html__( 'Authorized Signature', 'BuildERP' ); ?></div>
					</td>
				</tr>
			</table>

			<div class="page-footer">
				<?php echo esc_html__( 'Thank you for your business!', 'BuildERP' ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get CSS styles for PDF
	 *
	 * @since 1.0.0
	 * @return string CSS styles.
	 */
	protected function get_styles() {
		return '
		<style>
			body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.5; }
			.quotation-container { padding: 0; }
			
			/* Header */
			.header-table { width: 100%; border-bottom: 2px solid #0a4b78; padding-bottom: 20px; margin-bottom: 30px; }
			.company-section { vertical-align: top; width: 60%; }
			.company-logo { max-height: 60px; margin-bottom: 10px; }
			.company-name-text { font-size: 18pt; font-weight: bold; color: #0a4b78; margin: 0 0 5px 0; }
			.company-details { font-size: 9pt; color: #555; line-height: 1.4; }
			
			.quotation-meta { vertical-align: top; width: 40%; text-align: right; }
			.doc-title { font-size: 24pt; color: #0a4b78; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px; }
			.meta-table { width: 100%; border-collapse: collapse; }
			.meta-table th { text-align: right; padding: 3px 10px 3px 0; color: #666; font-weight: normal; width: 60%; }
			.meta-table td { text-align: right; padding: 3px 0; font-weight: bold; color: #333; width: 40%; }

			/* Client Section */
			.client-section { background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 30px; border-left: 4px solid #0a4b78; }
			.section-label { font-size: 9pt; text-transform: uppercase; color: #666; margin-bottom: 5px; letter-spacing: 0.5px; }
			.client-details { font-size: 11pt; line-height: 1.4; }
			
			/* Items Table */
			.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
			.items-table th { background-color: #0a4b78; color: #fff; padding: 10px; text-align: left; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
			.items-table td { padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 10pt; vertical-align: top; }
			.items-table tr.even { background-color: #f9f9f9; }
			
			.col-desc { width: 50%; }
			.col-qty { width: 10%; text-align: center; }
			.col-rate { width: 20%; text-align: right; }
			.col-amount { width: 20%; text-align: right; }
			
			.item-title { font-weight: bold; color: #333; margin-bottom: 2px; }
			
			/* Totals */
			.totals-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-bottom: 40px; }
			.totals-table td { padding: 8px 10px; text-align: right; }
			.totals-table .label { color: #666; font-weight: normal; }
			.totals-table .value { font-weight: bold; color: #333; }
			.totals-table .grand-total { background-color: #0a4b78; color: #fff; }
			.totals-table .grand-total td { padding: 12px 10px; font-size: 12pt; border-top: 2px solid #0a4b78; }
			.totals-table .grand-total .label { color: #fff; }
			.totals-table .grand-total .value { color: #fff; }
			.text-danger { color: #dc3545; }

			/* Footer Content */
			.footer-content { margin-bottom: 40px; }
			.notes-section, .terms-section { margin-bottom: 20px; page-break-inside: avoid; }
			.notes-section h3, .terms-section h3 { font-size: 10pt; color: #0a4b78; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 8px; text-transform: uppercase; }
			.content { font-size: 9pt; color: #555; line-height: 1.4; }

			/* Signature */
			.signature-table { width: 100%; margin-top: 50px; page-break-inside: avoid; }
			.signature-box { width: 40%; margin-left: auto; text-align: center; }
			.signature-box .line { border-top: 1px solid #333; margin-bottom: 8px; }
			.signature-box .label { font-size: 9pt; color: #666; }

			/* Page Footer */
			.page-footer { text-align: center; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 15px; margin-top: 30px; }
		</style>
		';
	}

	/**
	 * Get mPDF instance
	 *
	 * @since 1.0.0
	 * @return \Mpdf\Mpdf mPDF instance.
	 */
	protected function get_mpdf_instance() {
		if ( ! class_exists( '\\Mpdf\\Mpdf' ) ) {
			require_once BERP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		return new \Mpdf\Mpdf(
			array(
				'format'        => 'A4',
				'margin_left'   => 15,
				'margin_right'  => 15,
				'margin_top'    => 20,
				'margin_bottom' => 20,
				'margin_header' => 10,
				'margin_footer' => 10,
			)
		);
	}
}

