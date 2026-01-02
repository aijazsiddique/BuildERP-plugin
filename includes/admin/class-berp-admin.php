<?php
/**
 * Admin functionality.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Admin Class
 *
 * Handles admin-specific functionality
 */
class BERP_Admin {


	/**
	 * Enqueue admin styles.
	 *
	 * Only load on BuildErp pages.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		// Check if we're on a BuildErp page.
		if ( ! $this->is_berp_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'berp-admin-styles',
			BERP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BERP_VERSION,
			'all'
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * Only load on BuildErp pages.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Check if we're on a BuildErp page.
		if ( ! $this->is_berp_page( $hook ) ) {
			return;
		}

		// Media uploader for logo/uploads on settings page.
		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		// Color picker support.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script(
			'berp-admin-scripts',
			BERP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			BERP_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'berp-admin-scripts',
			'berpAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'berp_admin_nonce' ),
				'strings' => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item?', 'BuildERP' ),
					'error'          => __( 'An error occurred. Please try again.', 'BuildERP' ),
					'success'        => __( 'Operation completed successfully.', 'BuildERP' ),
				),
			)
		);

		// Quotation and Invoice specific scripts (both use same line items repeater).
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->post_type, array( 'berp_quotation', 'berp_invoice' ), true ) ) {
			wp_enqueue_script(
				'berp-quotation-admin',
				BERP_PLUGIN_URL . 'assets/js/quotation-admin.js',
				array( 'jquery' ),
				BERP_VERSION,
				true
			);
		}
	}

	/**
	 * Check if current page is a BuildErp page.
	 *
	 * @since  1.0.0
	 * @param  string $hook Current admin page hook.
	 * @return bool
	 */
	private function is_berp_page( $hook ) {
		// BuildErp top-level pages (dashboard/settings/reports/attendance).
		if ( strpos( $hook, 'builderp' ) !== false ) {
			return true;
		}

		// Current screen awareness for reliability.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ( strpos( $screen->id, 'builderp' ) !== false || strpos( $screen->base, 'builderp' ) !== false ) ) {
			return true;
		}

		// BuildErp custom post types.
		$berp_post_types = array(
			'berp_employee',
			'berp_attendance',
			'berp_payroll',
			'berp_expense',
			'berp_client',
			'berp_site',
			'berp_quotation',
			'berp_invoice',
		);

		// Global post type or GET param.
		global $post_type;
		if ( in_array( $post_type, $berp_post_types, true ) ) {
			return true;
		}
		if ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $berp_post_types, true ) ) {
			return true;
		}

		// Editing an existing post of our types.
		if ( isset( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
			$post    = get_post( $post_id );
			if ( $post && in_array( $post->post_type, $berp_post_types, true ) ) {
				return true;
			}
		}

		return false;
	}
}

