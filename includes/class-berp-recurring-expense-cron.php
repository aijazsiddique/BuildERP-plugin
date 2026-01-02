<?php
/**
 * Recurring Expense Cron Handler
 *
 * Handles automated creation of recurring expenses via WordPress cron.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Recurring_Expense_Cron Class
 *
 * @since 1.0.0
 */
class BERP_Recurring_Expense_Cron {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'berp_recurring_expense_check', array( $this, 'process_recurring_expenses' ) );
	}

	/**
	 * Activate cron job
	 *
	 * Called during plugin activation.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'berp_recurring_expense_check' ) ) {
			wp_schedule_event( time(), 'daily', 'berp_recurring_expense_check' );
		}
	}

	/**
	 * Deactivate cron job
	 *
	 * Called during plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'berp_recurring_expense_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'berp_recurring_expense_check' );
		}
	}

	/**
	 * Process recurring expenses
	 *
	 * Called daily by WordPress cron.
	 *
	 * @since 1.0.0
	 */
	public function process_recurring_expenses() {
		// Check if recurring expenses are enabled in settings
		$settings = get_option( 'berp_settings', array() );
		$enabled  = isset( $settings['expense']['enable_recurring'] ) ? $settings['expense']['enable_recurring'] : true;

		if ( ! $enabled ) {
			return;
		}

		$today = current_time( 'Y-m-d' );

		// Query recurring expenses that are due today or earlier
		$args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_berp_is_recurring',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_berp_recurring_next_date',
					'value'   => $today,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
		);

		$expenses = get_posts( $args );

		if ( empty( $expenses ) ) {
			return;
		}

		foreach ( $expenses as $expense ) {
			// Check if recurring has ended
			$end_date = get_post_meta( $expense->ID, '_berp_recurring_end_date', true );
			if ( ! empty( $end_date ) && $end_date < $today ) {
				// Disable recurring for this expense
				update_post_meta( $expense->ID, '_berp_is_recurring', '0' );
				do_action( 'berp_recurring_expense_ended', $expense->ID, $end_date );
				continue;
			}

			// Create new expense
			$new_expense_id = $this->create_recurring_expense( $expense->ID );

			if ( ! is_wp_error( $new_expense_id ) ) {
				// Calculate next due date
				$interval          = get_post_meta( $expense->ID, '_berp_recurring_interval', true );
				$current_next_date = get_post_meta( $expense->ID, '_berp_recurring_next_date', true );
				$next_date         = $this->calculate_next_date( $current_next_date, $interval );

				// Update next date on template
				update_post_meta( $expense->ID, '_berp_recurring_next_date', $next_date );

				// Fire action hook
				do_action( 'berp_recurring_expense_created', $new_expense_id, $expense->ID, $next_date );
			}
		}
	}

	/**
	 * Create a new expense from a recurring template
	 *
	 * @since 1.0.0
	 * @param int $template_id Template expense post ID.
	 * @return int|WP_Error New expense ID or error object.
	 */
	private function create_recurring_expense( $template_id ) {
		$template = get_post( $template_id );

		if ( ! $template || $template->post_type !== 'berp_expense' ) {
			return new WP_Error( 'berp_invalid_template', __( 'Invalid expense template', 'builderp' ) );
		}

		// Create new expense post
		$new_expense_id = wp_insert_post(
			array(
				'post_type'    => 'berp_expense',
				'post_title'   => $template->post_title,
				'post_content' => $template->post_content,
				'post_status'  => 'publish',
				'post_author'  => $template->post_author,
			)
		);

		if ( is_wp_error( $new_expense_id ) ) {
			return $new_expense_id;
		}

		// Copy expense details (but not recurring settings)
		update_post_meta( $new_expense_id, '_berp_expense_date', current_time( 'Y-m-d' ) );
		update_post_meta( $new_expense_id, '_berp_expense_amount', get_post_meta( $template_id, '_berp_expense_amount', true ) );
		update_post_meta( $new_expense_id, '_berp_expense_category', get_post_meta( $template_id, '_berp_expense_category', true ) );
		update_post_meta( $new_expense_id, '_berp_site_id', get_post_meta( $template_id, '_berp_site_id', true ) );
		update_post_meta( $new_expense_id, '_berp_payment_method', get_post_meta( $template_id, '_berp_payment_method', true ) );
		update_post_meta( $new_expense_id, '_berp_expense_description', get_post_meta( $template_id, '_berp_expense_description', true ) );
		update_post_meta( $new_expense_id, '_berp_expense_receipts', get_post_meta( $template_id, '_berp_expense_receipts', true ) );

		// Link to parent template
		update_post_meta( $new_expense_id, '_berp_recurring_parent_id', $template_id );

		// Do NOT copy recurring settings - new expense is not recurring
		update_post_meta( $new_expense_id, '_berp_is_recurring', '0' );

		// Update site budget if linked to a site
		$site_id = get_post_meta( $new_expense_id, '_berp_site_id', true );
		if ( $site_id ) {
			$this->update_site_budget( $site_id );
		}

		return $new_expense_id;
	}

	/**
	 * Calculate next due date based on interval
	 *
	 * @since 1.0.0
	 * @param string $current_date Current due date (Y-m-d format).
	 * @param string $interval     Interval (monthly or yearly).
	 * @return string Next due date (Y-m-d format).
	 */
	private function calculate_next_date( $current_date, $interval ) {
		try {
			$date = new DateTime( $current_date );

			if ( $interval === 'monthly' ) {
				$date->modify( '+1 month' );
			} elseif ( $interval === 'yearly' ) {
				$date->modify( '+1 year' );
			} else {
				// Default to monthly if interval is invalid
				$date->modify( '+1 month' );
			}

			return $date->format( 'Y-m-d' );
		} catch ( Exception $e ) {
			// If date parsing fails, return current date + 1 month as fallback
			return gmdate( 'Y-m-d', strtotime( '+1 month', strtotime( $current_date ) ) );
		}
	}

	/**
	 * Update site budget spent
	 *
	 * @since 1.0.0
	 * @param int $site_id Site post ID.
	 */
	private function update_site_budget( $site_id ) {
		if ( empty( $site_id ) ) {
			return;
		}

		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_berp_expense_amount'
            AND pm2.meta_key = '_berp_site_id'
            AND pm2.meta_value = %d
            AND p.post_type = 'berp_expense'
            AND p.post_status = 'publish'",
				$site_id
			)
		);

		update_post_meta( $site_id, '_berp_budget_spent', floatval( $total ) );
	}
}


