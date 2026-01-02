<?php
/**
 * Invoice Admin Actions Handler
 *
 * Handles admin actions like sending emails, marking as paid, and exporting.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Invoice_Admin Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_Admin {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'wp_ajax_berp_view_invoice_pdf', array( $this, 'view_invoice_pdf' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	/**
	 * Handle admin actions
	 *
	 * @since 1.0.0
	 */
	public function handle_admin_actions() {
		// Handle "Send to Client" action.
		if ( isset( $_GET['action'] ) && 'berp_send_invoice' === $_GET['action'] ) {
			$invoice_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'berp_send_invoice_' . $invoice_id ) ) {
				wp_die( esc_html__( 'Security check failed', 'builderp' ) );
			}

			if ( ! current_user_can( 'berp_manage_invoices' ) ) {
				wp_die( esc_html__( 'You do not have permission', 'builderp' ) );
			}

			$result = $this->send_invoice_email( $invoice_id );

			if ( is_wp_error( $result ) ) {
				set_transient( 'berp_invoice_send_error', $result->get_error_message(), 30 );
			} else {
				set_transient( 'berp_invoice_send_success', __( 'Invoice sent successfully', 'builderp' ), 30 );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=berp_invoice' ) );
			exit;
		}

		// Handle "Mark as Paid" action.
		if ( isset( $_GET['action'] ) && 'berp_mark_invoice_paid' === $_GET['action'] ) {
			$invoice_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'berp_mark_paid_' . $invoice_id ) ) {
				wp_die( esc_html__( 'Security check failed', 'builderp' ) );
			}

			if ( ! current_user_can( 'berp_manage_invoices' ) ) {
				wp_die( esc_html__( 'You do not have permission', 'builderp' ) );
			}

			$result = $this->mark_invoice_paid( $invoice_id );

			if ( is_wp_error( $result ) ) {
				set_transient( 'berp_invoice_paid_error', $result->get_error_message(), 30 );
			} else {
				set_transient( 'berp_invoice_paid_success', __( 'Invoice marked as paid', 'builderp' ), 30 );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=berp_invoice' ) );
			exit;
		}
	}

	/**
	 * Send invoice email to client
	 *
	 * @since 1.0.0
	 * @param int $invoice_id Invoice post ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_invoice_email( $invoice_id ) {
		$invoice = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error( 'invalid_invoice', __( 'Invalid invoice', 'builderp' ) );
		}

		// Get client email.
		$client_id = get_post_meta( $invoice_id, '_berp_client_id', true );
		if ( ! $client_id ) {
			return new WP_Error( 'no_client', __( 'No client assigned to invoice', 'builderp' ) );
		}

		$client_email = get_post_meta( $client_id, '_berp_client_email', true );
		if ( ! $client_email || ! is_email( $client_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Client email is invalid', 'builderp' ) );
		}

		// Generate PDF.
		$pdf_generator = new BERP_Invoice_PDF();
		$pdf_content   = $pdf_generator->generate( $invoice_id, 'string' );

		if ( is_wp_error( $pdf_content ) ) {
			return $pdf_content;
		}

		// Prepare email.
		$invoice_number   = get_post_meta( $invoice_id, '_berp_invoice_number', true );
		$client           = get_post( $client_id );
		$general_settings = berp_get_general_settings();
		$company_name     = isset( $general_settings['company_name'] ) ? $general_settings['company_name'] : get_bloginfo( 'name' );
		$grand_total      = get_post_meta( $invoice_id, '_berp_grand_total', true );
		$due_date         = get_post_meta( $invoice_id, '_berp_due_date', true );
		$currency         = berp_get_currency_symbol();

		$subject = sprintf(
			/* translators: 1: Invoice number, 2: Company name */
			__( 'Invoice %1$s from %2$s', 'builderp' ),
			$invoice_number,
			$company_name
		);

		$message = sprintf(
			/* translators: 1: Client name, 2: Invoice number, 3: Amount, 4: Due date, 5: Company name */
			__( "Dear %1\$s,\n\nPlease find attached invoice %2\$s for %3\$s.\n\nPayment is due by %4\$s.\n\nThank you for your business.\n\nBest regards,\n%5\$s", 'builderp' ),
			$client->post_title,
			$invoice_number,
			$currency . number_format( floatval( $grand_total ), 2 ),
			gmdate( 'F j, Y', strtotime( $due_date ) ),
			$company_name
		);

		$subject = apply_filters( 'berp_invoice_email_subject', $subject, $invoice_id );
		$message = apply_filters( 'berp_invoice_email_message', $message, $invoice_id );

		// Prepare attachment.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['basedir'] . '/berp-temp/invoice-' . $invoice_number . '.pdf';
		wp_mkdir_p( dirname( $temp_file ) );
		file_put_contents( $temp_file, $pdf_content );

		// Send email.
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$sent    = wp_mail( $client_email, $subject, $message, $headers, array( $temp_file ) );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', __( 'Failed to send email', 'builderp' ) );
		}

		// Update invoice status and metadata.
		$current_status = get_post_meta( $invoice_id, '_berp_status', true );
		if ( 'draft' === $current_status ) {
			update_post_meta( $invoice_id, '_berp_status', 'sent' );
		}
		update_post_meta( $invoice_id, '_berp_sent_date', current_time( 'mysql' ) );

		$sent_count = get_post_meta( $invoice_id, '_berp_sent_count', true );
		update_post_meta( $invoice_id, '_berp_sent_count', absint( $sent_count ) + 1 );

		do_action( 'berp_invoice_sent', $invoice_id, $client_id, $client_email );

		return true;
	}

	/**
	 * Mark invoice as fully paid
	 *
	 * @since 1.0.0
	 * @param int $invoice_id Invoice post ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function mark_invoice_paid( $invoice_id ) {
		$invoice = berp_get_invoice( $invoice_id );

		if ( ! $invoice ) {
			return new WP_Error( 'invalid_invoice', __( 'Invalid invoice', 'builderp' ) );
		}

		$grand_total = floatval( get_post_meta( $invoice_id, '_berp_grand_total', true ) );
		$amount_paid = floatval( get_post_meta( $invoice_id, '_berp_amount_paid', true ) );
		$remaining   = $grand_total - $amount_paid;

		if ( $remaining > 0 ) {
			// Add a final payment record for the remaining amount.
			$payments = get_post_meta( $invoice_id, '_berp_payments', true );
			if ( ! is_array( $payments ) ) {
				$payments = array();
			}

			$payments[] = array(
				'date'      => current_time( 'Y-m-d' ),
				'amount'    => $remaining,
				'method'    => 'other',
				'reference' => __( 'Final payment', 'builderp' ),
				'notes'     => __( 'Automatically recorded when marked as paid', 'builderp' ),
			);

			update_post_meta( $invoice_id, '_berp_payments', $payments );
			update_post_meta( $invoice_id, '_berp_amount_paid', $grand_total );
			update_post_meta( $invoice_id, '_berp_amount_due', 0 );
		}

		update_post_meta( $invoice_id, '_berp_status', 'paid' );
		update_post_meta( $invoice_id, '_berp_paid_date', current_time( 'mysql' ) );

		do_action( 'berp_invoice_marked_paid', $invoice_id );

		// Log activity.
		if ( function_exists( 'berp_log_activity' ) ) {
			berp_log_activity(
				'invoice_paid',
				sprintf( 'Invoice marked as paid: %s', $invoice->post_title ),
				array(
					'invoice_id' => $invoice_id,
					'amount'     => $grand_total,
				)
			);
		}

		return true;
	}

	/**
	 * View invoice PDF via AJAX
	 *
	 * @since 1.0.0
	 */
	public function view_invoice_pdf() {
		$invoice_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $invoice_id ) {
			wp_die( esc_html__( 'Invalid invoice ID', 'builderp' ) );
		}

		if ( ! current_user_can( 'berp_view_invoices', $invoice_id ) ) {
			wp_die( esc_html__( 'You do not have permission', 'builderp' ) );
		}

		$pdf_generator = new BERP_Invoice_PDF();
		$pdf_generator->generate( $invoice_id, 'inline' );
		exit;
	}

	/**
	 * Handle CSV export
	 *
	 * @since 1.0.0
	 */
	public function handle_export() {
		if ( ! isset( $_GET['berp_export_invoices'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'berp_export_invoices' ) ) {
			wp_die( esc_html__( 'Security check failed', 'builderp' ) );
		}

		if ( ! current_user_can( 'berp_manage_invoices' ) ) {
			wp_die( esc_html__( 'You do not have permission', 'builderp' ) );
		}

		$exporter = new BERP_Invoice_CSV_Exporter();
		$exporter->export();
		exit;
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {
		if ( $success = get_transient( 'berp_invoice_send_success' ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
			delete_transient( 'berp_invoice_send_success' );
		}

		if ( $error = get_transient( 'berp_invoice_send_error' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'berp_invoice_send_error' );
		}

		if ( $success = get_transient( 'berp_invoice_paid_success' ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
			delete_transient( 'berp_invoice_paid_success' );
		}

		if ( $error = get_transient( 'berp_invoice_paid_error' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'berp_invoice_paid_error' );
		}

		// Show export button on invoice list.
		$screen = get_current_screen();
		if ( $screen && 'edit-berp_invoice' === $screen->id ) {
			?>
			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'Export invoices:', 'builderp' ); ?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'berp_export_invoices', '1' ), 'berp_export_invoices' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Export to CSV', 'builderp' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
}

