<?php
/**
 * Quotation Admin Actions Handler
 *
 * Handles admin actions like sending emails and converting quotations.
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
 * BERP_Quotation_Admin Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_Admin {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'wp_ajax_berp_view_quotation_pdf', array( $this, 'view_quotation_pdf' ) );
	}

	/**
	 * Handle admin actions
	 *
	 * @since 1.0.0
	 */
	public function handle_admin_actions() {
		// Handle "Send to Client" action.
		if ( isset( $_GET['action'] ) && 'berp_send_quotation' === $_GET['action'] ) {
			$quotation_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'berp_send_quotation_' . $quotation_id ) ) {
				wp_die( esc_html__( 'Security check failed', 'aic_builderp' ) );
			}

			if ( ! current_user_can( 'berp_manage_quotations' ) ) {
				wp_die( esc_html__( 'You do not have permission', 'aic_builderp' ) );
			}

			$result = $this->send_quotation_email( $quotation_id );

			if ( is_wp_error( $result ) ) {
				set_transient( 'berp_quotation_send_error', $result->get_error_message(), 30 );
			} else {
				set_transient( 'berp_quotation_send_success', __( 'Quotation sent successfully', 'aic_builderp' ), 30 );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=berp_quotation' ) );
			exit;
		}

		// Handle "Convert to Site" action.
		if ( isset( $_GET['action'] ) && 'berp_convert_to_site' === $_GET['action'] ) {
			$quotation_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'berp_convert_site_' . $quotation_id ) ) {
				wp_die( esc_html__( 'Security check failed', 'aic_builderp' ) );
			}

			if ( ! current_user_can( 'berp_manage_quotations' ) ) {
				wp_die( esc_html__( 'You do not have permission', 'aic_builderp' ) );
			}

			$site_id = $this->convert_to_site( $quotation_id );

			if ( is_wp_error( $site_id ) ) {
				set_transient( 'berp_quotation_convert_error', $site_id->get_error_message(), 30 );
				wp_safe_redirect( admin_url( 'edit.php?post_type=berp_quotation' ) );
			} else {
				set_transient( 'berp_quotation_convert_success', __( 'Quotation converted to site successfully', 'aic_builderp' ), 30 );
				wp_safe_redirect( admin_url( 'post.php?post=' . $site_id . '&action=edit' ) );
			}
			exit;
		}
	}

	/**
	 * Send quotation email to client
	 *
	 * @since 1.0.0
	 * @param int $quotation_id Quotation post ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_quotation_email( $quotation_id ) {
		$quotation = berp_get_quotation( $quotation_id );

		if ( ! $quotation ) {
			return new WP_Error( 'invalid_quotation', __( 'Invalid quotation', 'aic_builderp' ) );
		}

		// Get client email.
		$client_id = get_post_meta( $quotation_id, '_berp_client_id', true );
		if ( ! $client_id ) {
			return new WP_Error( 'no_client', __( 'No client assigned to quotation', 'aic_builderp' ) );
		}

		$client_email = get_post_meta( $client_id, '_berp_client_email', true );
		if ( ! $client_email || ! is_email( $client_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Client email is invalid', 'aic_builderp' ) );
		}

		// Generate PDF.
		$pdf_generator = new BERP_Quotation_PDF();
		$pdf_content   = $pdf_generator->generate( $quotation_id, 'string' );

		if ( is_wp_error( $pdf_content ) ) {
			return $pdf_content;
		}

		// Prepare email.
		$quotation_number = get_post_meta( $quotation_id, '_berp_quotation_number', true );
		$client           = get_post( $client_id );
		$general_settings = berp_get_general_settings();
		$company_name     = isset( $general_settings['company_name'] ) ? $general_settings['company_name'] : get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: 1: Quotation number, 2: Company name */
			__( 'Quotation %1$s from %2$s', 'aic_builderp' ),
			$quotation_number,
			$company_name
		);

		$message = sprintf(
			/* translators: 1: Client name, 2: Quotation number, 3: Company name */
			__( "Dear %1\$s,\n\nPlease find attached quotation %2\$s.\n\nThank you for your business.\n\nBest regards,\n%3\$s", 'aic_builderp' ),
			$client->post_title,
			$quotation_number,
			$company_name
		);

		$subject = apply_filters( 'berp_quotation_email_subject', $subject, $quotation_id );
		$message = apply_filters( 'berp_quotation_email_message', $message, $quotation_id );

		// Prepare attachment.
		$upload_dir = wp_upload_dir();
		$temp_file  = $upload_dir['basedir'] . '/berp-temp/quotation-' . $quotation_number . '.pdf';
		wp_mkdir_p( dirname( $temp_file ) );
		file_put_contents( $temp_file, $pdf_content );

		// Send email.
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$sent    = wp_mail( $client_email, $subject, $message, $headers, array( $temp_file ) );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', __( 'Failed to send email', 'aic_builderp' ) );
		}

		// Update quotation status and metadata.
		update_post_meta( $quotation_id, '_berp_status', 'sent' );
		update_post_meta( $quotation_id, '_berp_sent_date', current_time( 'mysql' ) );

		$sent_count = get_post_meta( $quotation_id, '_berp_sent_count', true );
		update_post_meta( $quotation_id, '_berp_sent_count', absint( $sent_count ) + 1 );

		do_action( 'berp_quotation_sent', $quotation_id, $client_id, $client_email );

		return true;
	}

	/**
	 * Convert quotation to site
	 *
	 * @since 1.0.0
	 * @param int $quotation_id Quotation post ID.
	 * @return int|WP_Error Site ID on success, WP_Error on failure.
	 */
	public function convert_to_site( $quotation_id ) {
		$quotation = berp_get_quotation( $quotation_id );

		if ( ! $quotation ) {
			return new WP_Error( 'invalid_quotation', __( 'Invalid quotation', 'aic_builderp' ) );
		}

		// Check if already converted.
		$existing_site = get_post_meta( $quotation_id, '_berp_converted_to_site', true );
		if ( $existing_site ) {
			return new WP_Error( 'already_converted', __( 'Quotation already converted to site', 'aic_builderp' ) );
		}

		// Get quotation data.
		$client_id        = get_post_meta( $quotation_id, '_berp_client_id', true );
		$grand_total      = get_post_meta( $quotation_id, '_berp_grand_total', true );
		$quotation_number = get_post_meta( $quotation_id, '_berp_quotation_number', true );

		// Create site.
		$site_data = array(
			'post_title'  => sprintf( __( 'Site from Quotation %s', 'aic_builderp' ), $quotation_number ),
			'post_type'   => 'berp_site',
			'post_status' => 'publish',
		);

		$site_id = wp_insert_post( $site_data );

		if ( is_wp_error( $site_id ) ) {
			return $site_id;
		}

		// Set site meta.
		update_post_meta( $site_id, '_berp_client_id', $client_id );
		update_post_meta( $site_id, '_berp_budget', $grand_total );
		update_post_meta( $site_id, '_berp_budget_spent', 0 );
		update_post_meta( $site_id, '_berp_status', 'planning' );
		update_post_meta( $site_id, '_berp_start_date', current_time( 'Y-m-d' ) );
		update_post_meta( $site_id, '_berp_quotation_source', $quotation_id );

		// Update quotation.
		update_post_meta( $quotation_id, '_berp_converted_to_site', $site_id );

		do_action( 'berp_quotation_converted_to_site', $quotation_id, $site_id );

		return $site_id;
	}

	/**
	 * View quotation PDF via AJAX
	 *
	 * @since 1.0.0
	 */
	public function view_quotation_pdf() {
		$quotation_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( ! $quotation_id ) {
			wp_die( esc_html__( 'Invalid quotation ID', 'aic_builderp' ) );
		}

		if ( ! current_user_can( 'berp_view_quotations', $quotation_id ) ) {
			wp_die( esc_html__( 'You do not have permission', 'aic_builderp' ) );
		}

		$pdf_generator = new BERP_Quotation_PDF();
		$pdf_generator->generate( $quotation_id, 'inline' );
		exit;
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {
		if ( $success = get_transient( 'berp_quotation_send_success' ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
			delete_transient( 'berp_quotation_send_success' );
		}

		if ( $error = get_transient( 'berp_quotation_send_error' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'berp_quotation_send_error' );
		}

		if ( $success = get_transient( 'berp_quotation_convert_success' ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
			delete_transient( 'berp_quotation_convert_success' );
		}

		if ( $error = get_transient( 'berp_quotation_convert_error' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'berp_quotation_convert_error' );
		}
	}
}
