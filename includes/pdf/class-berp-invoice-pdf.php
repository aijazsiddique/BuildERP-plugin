<?php
/**
 * Invoice PDF Generator
 *
 * Generates PDF invoices using mPDF library.
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
 * BERP_Invoice_PDF Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_PDF {

	/**
	 * Invoice post object
	 *
	 * @since 1.0.0
	 * @var WP_Post
	 */
	private $invoice;

	/**
	 * Invoice ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $invoice_id;

	/**
	 * Currency symbol
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $currency;

	/**
	 * General settings
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $settings;

	/**
	 * Generate PDF for an invoice
	 *
	 * @since 1.0.0
	 * @param int    $invoice_id Invoice post ID.
	 * @param string $output     Output type: 'inline', 'download', 'string', or 'file'.
	 * @param string $file_path  Optional file path when output is 'file'.
	 * @return string|WP_Error PDF content on success, WP_Error on failure.
	 */
	public function generate( $invoice_id, $output = 'inline', $file_path = '' ) {
		$this->invoice_id = $invoice_id;
		$this->invoice    = berp_get_invoice( $invoice_id );

		if ( ! $this->invoice ) {
			return new WP_Error( 'invalid_invoice', __( 'Invalid invoice', 'aic_builderp' ) );
		}

		$this->currency = berp_get_currency_symbol();
		$this->settings = berp_get_general_settings();

		// Include mPDF.
		if ( ! class_exists( 'Mpdf\\Mpdf' ) ) {
			require_once BERP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		try {
			$mpdf = new \Mpdf\Mpdf(
				array(
					'mode'          => 'utf-8',
					'format'        => 'A4',
					'margin_left'   => 15,
					'margin_right'  => 15,
					'margin_top'    => 15,
					'margin_bottom' => 15,
					'tempDir'       => wp_upload_dir()['basedir'] . '/berp-temp',
				)
			);

			$mpdf->SetTitle( $this->get_invoice_title() );
			$mpdf->SetAuthor( isset( $this->settings['company_name'] ) ? $this->settings['company_name'] : get_bloginfo( 'name' ) );
			$mpdf->SetCreator( 'BuildERP' );

			$html = $this->get_html();
			$mpdf->WriteHTML( $html );

			$invoice_number = get_post_meta( $invoice_id, '_berp_invoice_number', true );
			$filename       = 'Invoice-' . $invoice_number . '.pdf';

			switch ( $output ) {
				case 'download':
					$mpdf->Output( $filename, 'D' );
					break;

				case 'string':
					return $mpdf->Output( '', 'S' );

				case 'file':
					if ( ! $file_path ) {
						$upload_dir = wp_upload_dir();
						$file_path  = $upload_dir['basedir'] . '/berp-invoices/' . $filename;
					}
					wp_mkdir_p( dirname( $file_path ) );
					$mpdf->Output( $file_path, 'F' );
					return $file_path;

				case 'inline':
				default:
					$mpdf->Output( $filename, 'I' );
					break;
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_error', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Get invoice title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_invoice_title() {
		$invoice_number = get_post_meta( $this->invoice_id, '_berp_invoice_number', true );
		return sprintf(
			/* translators: Invoice number */
			__( 'Invoice %s', 'aic_builderp' ),
			$invoice_number
		);
	}

	/**
	 * Get complete HTML for PDF
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_html() {
		$html  = '<!DOCTYPE html><html><head><style>' . $this->get_styles() . '</style></head><body>';
		$html .= '<div class="invoice-container">';
		$html .= $this->get_header();
		$html .= $this->get_client_info();
		// Invoice details are now merged into header/client info or handled separately if needed,
		// but let's keep the method call if we want a separate bar, or merge it.
		// Actually, the new design puts invoice details in the header meta table.
		// So I will remove get_invoice_details() from the main flow and integrate it into get_header().
		// Wait, get_invoice_details had dates and PO numbers. I should integrate that into the header meta.

		$html .= $this->get_line_items();
		$html .= $this->get_totals();
		$html .= $this->get_payment_status();
		$html .= $this->get_payment_history();
		$html .= $this->get_footer();
		$html .= '</div>';
		$html .= '</body></html>';

		return $html;
	}

	/**
	 * Get PDF styles
	 *
	 * @since 1.0.0
	 * @return string CSS styles.
	 */
	private function get_styles() {
		return '
			body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.5; }
			.invoice-container { padding: 0; }
			.text-danger { color: #dc3545; }
			.text-right { text-align: right; }
			.text-center { text-align: center; }
			
			/* Header */
			.header-table { width: 100%; border-bottom: 2px solid #0a4b78; padding-bottom: 20px; margin-bottom: 30px; }
			.company-section { vertical-align: top; width: 60%; }
			.company-logo { max-height: 60px; margin-bottom: 10px; }
			.company-name-text { font-size: 18pt; font-weight: bold; color: #0a4b78; margin: 0 0 5px 0; }
			.company-details { font-size: 9pt; color: #555; line-height: 1.4; }
			
			.invoice-meta { vertical-align: top; width: 40%; text-align: right; }
			.doc-title { font-size: 24pt; color: #0a4b78; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px; }
			.status-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 9pt; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
			.status-draft { background: #e0e0e0; color: #666; }
			.status-sent { background: #fff3cd; color: #856404; }
			.status-viewed { background: #d1ecf1; color: #0c5460; }
			.status-partial { background: #cce5ff; color: #004085; }
			.status-partially_paid { background: #cce5ff; color: #004085; }
			.status-paid { background: #d4edda; color: #155724; }
			.status-overdue { background: #f8d7da; color: #721c24; }

			.meta-table { width: 100%; border-collapse: collapse; }
			.meta-table th { text-align: right; padding: 3px 10px 3px 0; color: #666; font-weight: normal; width: 60%; }
			.meta-table td { text-align: right; padding: 3px 0; font-weight: bold; color: #333; width: 40%; }

			/* Client Section */
			.client-section { margin-bottom: 30px; }
			.client-table { width: 100%; border-collapse: collapse; }
			.client-box { width: 48%; background-color: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #0a4b78; vertical-align: top; }
			.project-box { width: 48%; background-color: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #6c757d; vertical-align: top; }
			.spacer-col { width: 4%; }
			
			.section-label { font-size: 9pt; text-transform: uppercase; color: #666; margin-bottom: 5px; letter-spacing: 0.5px; font-weight: bold; }
			.client-details { font-size: 10pt; line-height: 1.4; }
			
			/* Items Table */
			.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
			.items-table th { background-color: #0a4b78; color: #fff; padding: 10px; text-align: left; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
			.items-table td { padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 10pt; vertical-align: top; }
			.items-table tr.even { background-color: #f9f9f9; }
			
			.col-desc { width: 50%; }
			.col-qty { width: 10%; text-align: center; }
			.col-rate { width: 20%; text-align: right; }
			.col-amount { width: 20%; text-align: right; }
			
			/* Totals */
			.totals-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-bottom: 30px; }
			.totals-table td { padding: 8px 10px; text-align: right; }
			.totals-table .label { color: #666; font-weight: normal; }
			.totals-table .value { font-weight: bold; color: #333; }
			.totals-table .grand-total { background-color: #0a4b78; color: #fff; }
			.totals-table .grand-total td { padding: 12px 10px; font-size: 12pt; border-top: 2px solid #0a4b78; }
			.totals-table .grand-total .label { color: #fff; }
			.totals-table .grand-total .value { color: #fff; }

			/* Payment Status Box */
			.payment-status-box { margin-bottom: 30px; padding: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
			.box-paid { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
			.box-partial { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
			.box-due { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
			.box-overdue { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
			
			.status-label { font-size: 9pt; color: #666; text-transform: uppercase; margin-bottom: 5px; }
			.status-value { font-size: 12pt; }

			/* Payment History */
			.history-section { margin-bottom: 30px; }
			.history-title { font-size: 11pt; color: #0a4b78; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
			.history-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
			.history-table th { background: #f1f1f1; padding: 8px; text-align: left; color: #555; }
			.history-table td { padding: 8px; border-bottom: 1px solid #eee; color: #333; }

			/* Footer Content */
			.footer-content { margin-bottom: 30px; }
			.notes-box { margin-bottom: 15px; page-break-inside: avoid; background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #eee; }
			.notes-title { font-size: 9pt; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase; }
			.content { font-size: 9pt; color: #555; line-height: 1.4; }

			/* Page Footer */
			.footer { text-align: center; font-size: 8pt; color: #999; margin-top: 30px; }
			.footer-line { border-top: 1px solid #eee; margin-bottom: 15px; }
		';
	}

	/**
	 * Get header section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_header() {
		$company_name    = isset( $this->settings['company_name'] ) ? $this->settings['company_name'] : get_bloginfo( 'name' );
		$company_address = isset( $this->settings['company_address'] ) ? $this->settings['company_address'] : '';
		$company_phone   = isset( $this->settings['company_phone'] ) ? $this->settings['company_phone'] : '';
		$company_email   = isset( $this->settings['company_email'] ) ? $this->settings['company_email'] : get_bloginfo( 'admin_email' );
		$company_logo    = isset( $this->settings['company_logo'] ) ? $this->settings['company_logo'] : '';

		$invoice_number = get_post_meta( $this->invoice_id, '_berp_invoice_number', true );
		$status         = get_post_meta( $this->invoice_id, '_berp_status', true );
		$invoice_date   = get_post_meta( $this->invoice_id, '_berp_invoice_date', true );
		$due_date       = get_post_meta( $this->invoice_id, '_berp_due_date', true );
		$po_number      = get_post_meta( $this->invoice_id, '_berp_po_number', true );

		$html = '<table class="header-table"><tr>';

		// Left Column: Company Info
		$html .= '<td class="company-section">';
		if ( ! empty( $company_logo ) ) {
			$html .= '<img src="' . esc_url( $company_logo ) . '" class="company-logo" />';
		} else {
			$html .= '<h1 class="company-name-text">' . esc_html( $company_name ) . '</h1>';
		}

		$html .= '<div class="company-details">';
		if ( $company_address ) {
			$html .= nl2br( esc_html( $company_address ) ) . '<br>';
		}
		if ( $company_phone ) {
			$html .= esc_html__( 'Phone:', 'aic_builderp' ) . ' ' . esc_html( $company_phone ) . '<br>';
		}
		if ( $company_email ) {
			$html .= esc_html__( 'Email:', 'aic_builderp' ) . ' ' . esc_html( $company_email );
		}
		$html .= '</div>';
		$html .= '</td>';

		// Right Column: Invoice Meta
		$html .= '<td class="invoice-meta">';
		$html .= '<h1 class="doc-title">' . esc_html__( 'INVOICE', 'aic_builderp' ) . '</h1>';
		$html .= '<span class="status-badge status-' . esc_attr( $status ) . '">' . esc_html( berp_get_invoice_status_label( $status ) ) . '</span>';

		$html .= '<table class="meta-table">';
		$html .= '<tr><th>' . esc_html__( 'Invoice #:', 'aic_builderp' ) . '</th><td>' . esc_html( $invoice_number ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html__( 'Date:', 'aic_builderp' ) . '</th><td>' . esc_html( $invoice_date ? gmdate( 'd M Y', strtotime( $invoice_date ) ) : '' ) . '</td></tr>';
		$html .= '<tr><th>' . esc_html__( 'Due Date:', 'aic_builderp' ) . '</th><td>' . esc_html( $due_date ? gmdate( 'd M Y', strtotime( $due_date ) ) : '' ) . '</td></tr>';

		if ( $po_number ) {
			$html .= '<tr><th>' . esc_html__( 'PO #:', 'aic_builderp' ) . '</th><td>' . esc_html( $po_number ) . '</td></tr>';
		}

		$html .= '</table>';
		$html .= '</td>';

		$html .= '</tr></table>';

		return $html;
	}

	/**
	 * Get client information section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_client_info() {
		$client_id = get_post_meta( $this->invoice_id, '_berp_client_id', true );
		$site_id   = get_post_meta( $this->invoice_id, '_berp_site_id', true );

		$html  = '<div class="client-section">';
		$html .= '<table class="client-table"><tr>';

		// Bill To Box
		$html .= '<td class="client-box">';
		$html .= '<div class="section-label">' . esc_html__( 'Bill To:', 'aic_builderp' ) . '</div>';
		$html .= '<div class="client-details">';

		if ( $client_id ) {
			$client = get_post( $client_id );
			if ( $client ) {
				$html .= '<strong>' . esc_html( $client->post_title ) . '</strong><br>';

				$contact_person = get_post_meta( $client_id, '_berp_contact_person', true );
				$address        = get_post_meta( $client_id, '_berp_client_address', true );
				$email          = get_post_meta( $client_id, '_berp_client_email', true );
				$phone          = get_post_meta( $client_id, '_berp_client_phone', true );

				if ( $contact_person ) {
					$html .= esc_html__( 'Attn:', 'aic_builderp' ) . ' ' . esc_html( $contact_person ) . '<br>';
				}
				if ( $address ) {
					$html .= nl2br( esc_html( $address ) ) . '<br>';
				}
				if ( $email ) {
					$html .= esc_html( $email ) . '<br>';
				}
				if ( $phone ) {
					$html .= esc_html( $phone );
				}
			}
		}
		$html .= '</div>';
		$html .= '</td>';

		// Spacer
		$html .= '<td class="spacer-col"></td>';

		// Project/Site Box
		$html .= '<td class="project-box">';
		$html .= '<div class="section-label">' . esc_html__( 'Project / Site:', 'aic_builderp' ) . '</div>';
		$html .= '<div class="client-details">';

		if ( $site_id ) {
			$site = get_post( $site_id );
			if ( $site ) {
				$html .= '<strong>' . esc_html( $site->post_title ) . '</strong><br>';

				$site_address = get_post_meta( $site_id, '_berp_site_address', true );
				if ( $site_address ) {
					$html .= nl2br( esc_html( $site_address ) );
				}
			}
		} else {
			$html .= '<em>' . esc_html__( 'N/A', 'aic_builderp' ) . '</em>';
		}

		$html .= '</div>';
		$html .= '</td>';

		$html .= '</tr></table>';
		$html .= '</div>';

		return $html;
	}



	/**
	 * Get line items table
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_line_items() {
		$line_items = get_post_meta( $this->invoice_id, '_berp_line_items', true );

		$html  = '<table class="items-table">';
		$html .= '<thead><tr>';
		$html .= '<th class="col-desc">' . esc_html__( 'Description', 'aic_builderp' ) . '</th>';
		$html .= '<th class="col-qty">' . esc_html__( 'Qty', 'aic_builderp' ) . '</th>';
		$html .= '<th class="col-rate">' . esc_html__( 'Unit Price', 'aic_builderp' ) . '</th>';
		$html .= '<th class="col-amount">' . esc_html__( 'Amount', 'aic_builderp' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		if ( ! is_array( $line_items ) || empty( $line_items ) ) {
			// Show empty row or message to maintain layout
			$html .= '<tr>';
			$html .= '<td colspan="4" style="text-align: center; padding: 20px; color: #999;">' . esc_html__( 'No items found', 'aic_builderp' ) . '</td>';
			$html .= '</tr>';
		} else {
			$i = 0;
			foreach ( $line_items as $item ) {
				++$i;
				$row_class = ( $i % 2 == 0 ) ? 'even' : 'odd';

				$description = isset( $item['description'] ) ? $item['description'] : '';
				$quantity    = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
				// Support both 'rate' and 'unit_price' keys for compatibility.
				$unit_price = isset( $item['rate'] ) ? floatval( $item['rate'] ) : ( isset( $item['unit_price'] ) ? floatval( $item['unit_price'] ) : 0 );
				// Calculate amount or use stored amount.
				$amount = isset( $item['amount'] ) ? floatval( $item['amount'] ) : ( isset( $item['total'] ) ? floatval( $item['total'] ) : ( $quantity * $unit_price ) );

				$html .= '<tr class="' . $row_class . '">';
				$html .= '<td class="col-desc">' . esc_html( $description ) . '</td>';
				$html .= '<td class="col-qty">' . esc_html( number_format( $quantity, 2 ) ) . '</td>';
				$html .= '<td class="col-rate">' . esc_html( $this->currency . number_format( $unit_price, 2 ) ) . '</td>';
				$html .= '<td class="col-amount">' . esc_html( $this->currency . number_format( $amount, 2 ) ) . '</td>';
				$html .= '</tr>';
			}
		}

		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * Get totals section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_totals() {
		$subtotal = floatval( get_post_meta( $this->invoice_id, '_berp_subtotal', true ) );
		// Support both _berp_tax_total and _berp_tax_amount for compatibility.
		$tax_total = floatval( get_post_meta( $this->invoice_id, '_berp_tax_amount', true ) );
		if ( ! $tax_total ) {
			$tax_total = floatval( get_post_meta( $this->invoice_id, '_berp_tax_total', true ) );
		}
		// Support both _berp_discount and _berp_discount_value for compatibility.
		$discount = floatval( get_post_meta( $this->invoice_id, '_berp_discount_value', true ) );
		if ( ! $discount ) {
			$discount = floatval( get_post_meta( $this->invoice_id, '_berp_discount', true ) );
		}
		$discount_type = get_post_meta( $this->invoice_id, '_berp_discount_type', true );
		$grand_total   = floatval( get_post_meta( $this->invoice_id, '_berp_grand_total', true ) );

		// Get tax rate to show percentage in label.
		$tax_rate = floatval( get_post_meta( $this->invoice_id, '_berp_tax_rate', true ) );

		$html = '<table class="totals-table">';

		$html .= '<tr>';
		$html .= '<td class="label">' . esc_html__( 'Subtotal', 'aic_builderp' ) . '</td>';
		$html .= '<td class="value">' . esc_html( $this->currency . number_format( $subtotal, 2 ) ) . '</td>';
		$html .= '</tr>';

		if ( $tax_total > 0 || $tax_rate > 0 ) {
			$tax_label = __( 'Tax', 'aic_builderp' );
			if ( $tax_rate > 0 ) {
				$tax_label .= ' (' . number_format( $tax_rate, 0 ) . '%)';
			}
			$html .= '<tr>';
			$html .= '<td class="label">' . esc_html( $tax_label ) . '</td>';
			$html .= '<td class="value">' . esc_html( $this->currency . number_format( $tax_total, 2 ) ) . '</td>';
			$html .= '</tr>';
		}

		if ( $discount > 0 ) {
			$discount_label = __( 'Discount', 'aic_builderp' );
			if ( 'percentage' === $discount_type ) {
				$discount_label .= ' (' . number_format( $discount, 0 ) . '%)';
				$discount_amount = floatval( get_post_meta( $this->invoice_id, '_berp_discount_amount', true ) );
				if ( ! $discount_amount ) {
					$discount_amount = ( $subtotal + $tax_total ) * ( $discount / 100 );
				}
			} else {
				$discount_amount = $discount;
			}

			$html .= '<tr>';
			$html .= '<td class="label">' . esc_html( $discount_label ) . '</td>';
			$html .= '<td class="value text-danger">-' . esc_html( $this->currency . number_format( $discount_amount, 2 ) ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '<tr class="grand-total">';
		$html .= '<td class="label">' . esc_html__( 'Total', 'aic_builderp' ) . '</td>';
		$html .= '<td class="value">' . esc_html( $this->currency . number_format( $grand_total, 2 ) ) . '</td>';
		$html .= '</tr>';

		$html .= '</table>';

		// Clear float
		$html .= '<div style="clear: both;"></div>';

		return $html;
	}

	/**
	 * Get payment status section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_payment_status() {
		$status = get_post_meta( $this->invoice_id, '_berp_status', true );
		$paid   = floatval( get_post_meta( $this->invoice_id, '_berp_amount_paid', true ) );
		$total  = floatval( get_post_meta( $this->invoice_id, '_berp_grand_total', true ) );
		$due    = floatval( get_post_meta( $this->invoice_id, '_berp_amount_due', true ) );

		// Fallback calculation if due amount is missing but total exists
		if ( ! $due && $total > 0 && 'paid' !== $status ) {
			$due = $total - $paid;
		}

		$status_label = berp_get_invoice_status_label( $status );
		$status_class = 'status-' . strtolower( $status );

		$html  = '<div class="payment-status-box">';
		$html .= '<table width="100%">';
		$html .= '<tr>';

		// Status
		$html .= '<td width="33%">';
		$html .= '<div class="status-label">' . esc_html__( 'Status', 'aic_builderp' ) . '</div>';
		$html .= '<div class="status-value ' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</div>';
		$html .= '</td>';

		// Amount Paid
		$html .= '<td width="33%">';
		$html .= '<div class="status-label">' . esc_html__( 'Amount Paid', 'aic_builderp' ) . '</div>';
		$html .= '<div class="status-value">' . esc_html( $this->currency . number_format( $paid, 2 ) ) . '</div>';
		$html .= '</td>';

		// Amount Due
		$html .= '<td width="33%">';
		$html .= '<div class="status-label">' . esc_html__( 'Amount Due', 'aic_builderp' ) . '</div>';
		$html .= '<div class="status-value text-danger">' . esc_html( $this->currency . number_format( $due, 2 ) ) . '</div>';
		$html .= '</td>';

		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get payment history section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_payment_history() {
		$payments = get_post_meta( $this->invoice_id, '_berp_payments', true );

		if ( empty( $payments ) || ! is_array( $payments ) ) {
			return '';
		}

		$html  = '<div class="history-section">';
		$html .= '<div class="history-title">' . esc_html__( 'Payment History', 'aic_builderp' ) . '</div>';
		$html .= '<table class="history-table">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th width="25%">' . esc_html__( 'Date', 'aic_builderp' ) . '</th>';
		$html .= '<th width="25%">' . esc_html__( 'Method', 'aic_builderp' ) . '</th>';
		$html .= '<th width="25%">' . esc_html__( 'Reference', 'aic_builderp' ) . '</th>';
		$html .= '<th width="25%" align="right">' . esc_html__( 'Amount', 'aic_builderp' ) . '</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		foreach ( $payments as $payment ) {
			$html .= '<tr>';
			$html .= '<td>' . esc_html( isset( $payment['date'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $payment['date'] ) ) : '-' ) . '</td>';
			$html .= '<td>' . esc_html( isset( $payment['method'] ) ? ucfirst( $payment['method'] ) : '-' ) . '</td>';
			$html .= '<td>' . esc_html( isset( $payment['reference'] ) ? $payment['reference'] : '-' ) . '</td>';
			$html .= '<td align="right">' . esc_html( $this->currency . number_format( floatval( isset( $payment['amount'] ) ? $payment['amount'] : 0 ), 2 ) ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get footer section
	 *
	 * @since 1.0.0
	 * @return string HTML content.
	 */
	private function get_footer() {
		$notes         = get_post_meta( $this->invoice_id, '_berp_notes', true );
		$terms         = get_post_meta( $this->invoice_id, '_berp_terms', true );
		$default_terms = isset( $this->settings['invoice_terms'] ) ? $this->settings['invoice_terms'] : '';
		$company_name  = isset( $this->settings['company_name'] ) ? $this->settings['company_name'] : get_bloginfo( 'name' );

		$html = '';

		if ( $notes ) {
			$html .= '<div class="notes-box">';
			$html .= '<div class="notes-title">' . esc_html__( 'Notes:', 'aic_builderp' ) . '</div>';
			$html .= nl2br( esc_html( $notes ) );
			$html .= '</div>';
		}

		$terms_text = $terms ? $terms : $default_terms;
		if ( $terms_text ) {
			$html .= '<div class="notes-box">';
			$html .= '<div class="notes-title">' . esc_html__( 'Terms & Conditions:', 'aic_builderp' ) . '</div>';
			$html .= nl2br( esc_html( $terms_text ) );
			$html .= '</div>';
		}

		$html .= '<div class="footer">';
		$html .= '<div class="footer-line"></div>';
		$html .= esc_html(
			sprintf(
			/* translators: Company name */
				__( 'Thank you for your business! | %s', 'aic_builderp' ),
				$company_name
			)
		);
		$html .= '</div>';

		return $html;
	}
}
