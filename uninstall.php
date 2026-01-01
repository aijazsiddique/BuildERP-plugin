<?php
/**
 * Uninstall BuildERP Plugin
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data including:
 * - Custom post types and their meta
 * - Plugin options
 * - Custom database tables (if any)
 * - Transients
 * - User meta
 * - Cron events
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Security check - exit if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user permissions.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

/**
 * Check if data should be removed on uninstall
 *
 * This respects the user's preference in settings.
 * Default is to NOT delete data for safety.
 */
$settings           = get_option( 'berp_settings', array() );
$uninstall_behavior = isset( $settings['advanced']['uninstall_behavior'] ) ? $settings['advanced']['uninstall_behavior'] : 'keep';

// Also check legacy option.
$delete_data = get_option( 'berp_delete_data_on_uninstall', false );

if ( $uninstall_behavior !== 'delete' && ! $delete_data ) {
	// User opted to keep data - exit without deleting.
	return;
}

global $wpdb;

// ============================================================================
// Remove Custom Post Types and Their Data
// ============================================================================

$post_types = array(
	'berp_employee',
	'berp_site',
	'berp_client',
	'berp_attendance',
	'berp_expense',
	'berp_quotation',
	'berp_invoice',
	'berp_payroll',
);

foreach ( $post_types as $post_type ) {
	// Get all posts of this type.
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	// Delete each post and its meta.
	foreach ( $posts as $post_id ) {
		// Delete post meta.
		$wpdb->delete(
			$wpdb->postmeta,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Force delete post (bypass trash).
		wp_delete_post( $post_id, true );
	}
}

// ============================================================================
// Remove Custom Taxonomies Terms
// ============================================================================

$taxonomies = array(
	'berp_expense_category',
	'berp_department',
	'berp_project_status',
);

foreach ( $taxonomies as $taxonomy ) {
	// Get all terms.
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, $taxonomy );
		}
	}
}

// ============================================================================
// Remove Plugin Options
// ============================================================================

$options = array(
	// Main unified settings.
	'berp_settings',

	// Legacy settings (kept for backward compatibility during migration).
	'berp_general_settings',
	'berp_payroll_settings',
	'berp_attendance_settings',
	'berp_expense_settings',
	'berp_quotation_settings',
	'berp_site_settings',
	'berp_employee_settings',
	'berp_notification_settings',
	'berp_portal_settings',
	'berp_advanced_settings',

	// Formula builder settings.
	'berp_formula_settings',
	'berp_salary_formula',
	'berp_custom_variables',
	'berp_formula_presets',

	// Cache/version tracking.
	'berp_version',
	'berp_db_version',
	'berp_first_install',
	'berp_delete_data_on_uninstall',

	// Dashboard cache.
	'berp_dashboard_cache',
	'berp_dashboard_cache_expiry',

	// Activity log.
	'berp_activity_log',

	// Recurring expenses settings.
	'berp_last_recurring_check',

	// Taxonomy flags.
	'berp_default_terms_created',
	'berp_project_statuses_created',
	'berp_expense_categories_created',
	'berp_departments_created',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ============================================================================
// Remove Transients
// ============================================================================

// Delete all BuildErp transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_berp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_berp_' ) . '%'
	)
);

// ============================================================================
// Remove User Meta
// ============================================================================

$user_meta_keys = array(
	'berp_employee_id',
	'berp_portal_preferences',
	'berp_dashboard_layout',
	'berp_last_attendance',
	'berp_notification_preferences',
);

foreach ( $user_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

// ============================================================================
// Remove Custom Roles and Capabilities
// ============================================================================

// Remove custom roles.
remove_role( 'berp_employee' );
remove_role( 'berp_timekeeper' );

// Remove capabilities from administrator.
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

// ============================================================================
// Clear Scheduled Cron Events
// ============================================================================

wp_clear_scheduled_hook( 'berp_daily_tasks' );
wp_clear_scheduled_hook( 'berp_cleanup_tasks' );
wp_clear_scheduled_hook( 'berp_recurring_expense' );

// ============================================================================
// Remove Uploaded Files (optional - commented out for safety)
// ============================================================================

// Uncomment the following if you want to remove uploaded files.
// Be careful - this is irreversible!
/*
$upload_dir = wp_upload_dir();
$berp_uploads = $upload_dir['basedir'] . '/builderp';

if ( is_dir( $berp_uploads ) ) {
	// Recursively delete directory.
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $berp_uploads, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}

	rmdir( $berp_uploads );
}
*/

// ============================================================================
// Flush Rewrite Rules
// ============================================================================

flush_rewrite_rules();

// ============================================================================
// Clear Object Cache (if using external cache)
// ============================================================================

if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}
