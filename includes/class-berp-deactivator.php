<?php
/**
 * Fired during plugin deactivation
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Deactivator Class
 *
 * Handles plugin deactivation tasks
 */
class BERP_Deactivator {


	/**
	 * Deactivate the plugin
	 *
	 * - Flush rewrite rules
	 * - Clear scheduled cron jobs
	 * - Clear transients/cache
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Remove custom roles and capabilities added by this plugin.
		self::remove_roles_and_caps();

		// Clear scheduled cron jobs.
		self::clear_scheduled_events();

		// Clear transients/cache.
		self::clear_transients();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear all scheduled cron events
	 *
	 * @since 1.0.0
	 */
	private static function clear_scheduled_events() {
		// Deactivate recurring expense cron job.
		BERP_Recurring_Expense_Cron::deactivate();

		// Clear any other scheduled events.
		wp_clear_scheduled_hook( 'berp_daily_tasks' );
		wp_clear_scheduled_hook( 'berp_cleanup_tasks' );
	}

	/**
	 * Clear all plugin transients
	 *
	 * @since 1.0.0
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all BuildErp transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_berp_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_berp_' ) . '%'
			)
		);
	}

	/**
	 * Remove custom roles and strip capabilities we added
	 *
	 * @since 1.0.0
	 */
	private static function remove_roles_and_caps() {
		// Custom roles created on activation.
		remove_role( 'berp_employee' );
		remove_role( 'berp_timekeeper' );

		// Capabilities we added to administrator.
		$caps = array(
			'berp_manage_employees',
			'berp_view_employees',
			'berp_edit_employees',
			'berp_delete_employees',
			'berp_manage_attendance',
			'berp_log_attendance',
			'berp_edit_attendance',
			'berp_view_attendance',
			'berp_delete_attendance',
			'berp_manage_payroll',
			'berp_process_payroll',
			'berp_view_payroll',
			'berp_edit_payroll',
			'berp_delete_payroll',
			'berp_manage_expenses',
			'berp_view_expenses',
			'berp_edit_expenses',
			'berp_delete_expenses',
			'berp_manage_clients',
			'berp_view_clients',
			'berp_edit_clients',
			'berp_delete_clients',
			'berp_manage_sites',
			'berp_view_sites',
			'berp_edit_sites',
			'berp_delete_sites',
			'berp_manage_quotations',
			'berp_view_quotations',
			'berp_edit_quotations',
			'berp_delete_quotations',
			'berp_manage_invoices',
			'berp_view_invoices',
			'berp_edit_invoices',
			'berp_delete_invoices',
			'berp_manage_settings',
			'berp_view_reports',
			'berp_view_dashboard',
		);

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $caps as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}
}
