<?php
/**
 * Expense Admin Handler
 *
 * Handles admin actions for expenses including exports.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Expense_Admin Class
 *
 * @since 1.0.0
 */
class BERP_Expense_Admin {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	/**
	 * Handle export requests
	 *
	 * @since 1.0.0
	 */
	public function handle_export() {
		if ( ! isset( $_GET['berp_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['berp_action'] );

		if ( $action !== 'export_expenses_csv' && $action !== 'export_expenses_pdf' ) {
			return;
		}

		// Security checks
		if ( ! current_user_can( 'berp_manage_expenses' ) ) {
			wp_die( __( 'Permission denied', 'aic_builderp' ) );
		}

		if ( ! isset( $_GET['berp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['berp_nonce'] ) ), 'berp_export_expenses' ) ) {
			wp_die( __( 'Security check failed', 'aic_builderp' ) );
		}

		// Build query args from filters
		$args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => '_berp_expense_date',
			'order'          => 'DESC',
		);

		$meta_query = array();

		// Category filter
		if ( isset( $_GET['berp_category'] ) && ! empty( $_GET['berp_category'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_category',
				'value'   => sanitize_text_field( $_GET['berp_category'] ),
				'compare' => '=',
			);
		}

		// Site filter
		if ( isset( $_GET['berp_site'] ) && ! empty( $_GET['berp_site'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => absint( $_GET['berp_site'] ),
				'compare' => '=',
			);
		}

		// Date range filter
		$date_from = isset( $_GET['berp_date_from'] ) ? sanitize_text_field( $_GET['berp_date_from'] ) : '';
		$date_to   = isset( $_GET['berp_date_to'] ) ? sanitize_text_field( $_GET['berp_date_to'] ) : '';

		if ( $date_from && $date_to ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => array( $date_from, $date_to ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
		} elseif ( $date_from ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		} elseif ( $date_to ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		// Payment method filter
		if ( isset( $_GET['berp_payment_method'] ) && ! empty( $_GET['berp_payment_method'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_payment_method',
				'value'   => sanitize_text_field( $_GET['berp_payment_method'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}
			$args['meta_query'] = $meta_query;
		}

		// Call appropriate exporter
		if ( $action === 'export_expenses_csv' ) {
			BERP_Expense_CSV_Exporter::export( $args );
		} else {
			BERP_Expense_PDF_Exporter::export( $args );
		}
	}
}
