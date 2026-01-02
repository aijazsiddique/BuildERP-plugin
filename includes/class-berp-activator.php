<?php
/**
 * Fired during plugin activation
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Activator Class
 *
 * Handles plugin activation tasks
 */
class BERP_Activator {


	/**
	 * Activate the plugin
	 *
	 * - Create custom user roles and capabilities
	 * - Set default options
	 * - Flush rewrite rules
	 * - Store plugin version
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Load required class for cron activation.
		require_once BERP_PLUGIN_DIR . 'includes/class-berp-recurring-expense-cron.php';

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			wp_die(
				esc_html__( 'BuildErp requires WordPress 6.0 or higher. Please upgrade WordPress.', 'builderp' ),
				esc_html__( 'Plugin Activation Error', 'builderp' ),
				array( 'back_link' => true )
			);
		}

		// Check PHP version.
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			wp_die(
				esc_html__( 'BuildErp requires PHP 8.0 or higher. Please upgrade PHP.', 'builderp' ),
				esc_html__( 'Plugin Activation Error', 'builderp' ),
				array( 'back_link' => true )
			);
		}

		// Register CPTs and Taxonomies temporarily for activation.
		self::register_post_types_and_taxonomies();

		// Create custom roles and capabilities.
		self::create_roles();

		// Add capabilities to administrator.
		self::add_admin_capabilities();

		// Set default options.
		self::set_default_options();

		// Create default taxonomy terms.
		self::create_default_taxonomy_terms();

		// Store plugin version.
		update_option( 'berp_version', BERP_VERSION );

		// Activate recurring expense cron job.
		BERP_Recurring_Expense_Cron::activate();

		// Flush rewrite rules (must be after CPT registration).
		flush_rewrite_rules();
	}

	/**
	 * Create custom user roles
	 *
	 * @since 1.0.0
	 */
	private static function create_roles() {
		// BERP Employee Role.
		add_role(
			'berp_employee',
			__( 'BERP Employee', 'builderp' ),
			array(
				'read'                     => true,
				'berp_view_own_attendance' => true,
				'berp_view_own_salary'     => true,
				'berp_edit_own_profile'    => true,
			)
		);

		// BERP Timekeeper Role.
		add_role(
			'berp_timekeeper',
			__( 'BERP Timekeeper', 'builderp' ),
			array(
				'read'                 => true,
				'berp_log_attendance'  => true,
				'berp_edit_attendance' => true,
				'berp_view_attendance' => true,
				'berp_view_employees'  => true,
			)
		);
	}

	/**
	 * Add capabilities to administrator role
	 *
	 * @since 1.0.0
	 */
	private static function add_admin_capabilities() {
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			// Employee management.
			$admin_role->add_cap( 'berp_manage_employees' );
			$admin_role->add_cap( 'berp_view_employees' );
			$admin_role->add_cap( 'berp_edit_employees' );
			$admin_role->add_cap( 'berp_delete_employees' );

			// Attendance management.
			$admin_role->add_cap( 'berp_manage_attendance' );
			$admin_role->add_cap( 'berp_log_attendance' );
			$admin_role->add_cap( 'berp_edit_attendance' );
			$admin_role->add_cap( 'berp_view_attendance' );
			$admin_role->add_cap( 'berp_delete_attendance' );

			// Payroll management.
			$admin_role->add_cap( 'berp_manage_payroll' );
			$admin_role->add_cap( 'berp_process_payroll' );
			$admin_role->add_cap( 'berp_view_payroll' );
			$admin_role->add_cap( 'berp_edit_payroll' );
			$admin_role->add_cap( 'berp_delete_payroll' );

			// Expense management.
			$admin_role->add_cap( 'berp_manage_expenses' );
			$admin_role->add_cap( 'berp_view_expenses' );
			$admin_role->add_cap( 'berp_edit_expenses' );
			$admin_role->add_cap( 'berp_delete_expenses' );

			// Client management.
			$admin_role->add_cap( 'berp_manage_clients' );
			$admin_role->add_cap( 'berp_view_clients' );
			$admin_role->add_cap( 'berp_edit_clients' );
			$admin_role->add_cap( 'berp_delete_clients' );

			// Site management.
			$admin_role->add_cap( 'berp_manage_sites' );
			$admin_role->add_cap( 'berp_view_sites' );
			$admin_role->add_cap( 'berp_edit_sites' );
			$admin_role->add_cap( 'berp_delete_sites' );

			// Quotation management.
			$admin_role->add_cap( 'berp_manage_quotations' );
			$admin_role->add_cap( 'berp_view_quotations' );
			$admin_role->add_cap( 'berp_edit_quotations' );
			$admin_role->add_cap( 'berp_delete_quotations' );

			// Invoice management.
			$admin_role->add_cap( 'berp_manage_invoices' );
			$admin_role->add_cap( 'berp_view_invoices' );
			$admin_role->add_cap( 'berp_edit_invoices' );
			$admin_role->add_cap( 'berp_delete_invoices' );

			// Advance request management.
			$admin_role->add_cap( 'berp_manage_advances' );
			$admin_role->add_cap( 'berp_view_advances' );
			$admin_role->add_cap( 'berp_edit_advances' );
			$admin_role->add_cap( 'berp_delete_advances' );

			// Settings and reports.
			$admin_role->add_cap( 'berp_manage_settings' );
			$admin_role->add_cap( 'berp_view_reports' );
			$admin_role->add_cap( 'berp_view_dashboard' );
		}
	}

	/**
	 * Set default plugin options
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		// Ensure helper functions are available for defaults.
		if ( ! function_exists( 'berp_get_default_salary_formula' ) ) {
			require_once BERP_PLUGIN_DIR . 'includes/functions.php';
		}

		// General settings defaults.
		$general_defaults = array(
			'company_name'      => '',
			'company_email'     => get_option( 'admin_email' ),
			'company_phone'     => '',
			'company_address'   => '',
			'currency'          => 'USD',
			'currency_symbol'   => '$',
			'currency_position' => 'before',
			'date_format'       => 'Y-m-d',
			'time_format'       => 'H:i',
		);
		add_option( 'berp_general_settings', $general_defaults );

		// Payroll settings defaults.
		$payroll_defaults = array(
			'working_days_per_month'   => 26,
			'overtime_rate_multiplier' => 1.5,
			'weekend_payment_enabled'  => false,
			'weekend_rate_multiplier'  => 2.0,
			'auto_calculate_payroll'   => true,
		);
		add_option( 'berp_payroll_settings', $payroll_defaults );

		// Attendance settings defaults.
		$attendance_defaults = array(
			'allow_edit_days'        => 7,
			'remember_last_site'     => true,
			'default_overtime_hours' => 0,
			'weekend_days'           => array( 'saturday', 'sunday' ),
		);
		add_option( 'berp_attendance_settings', $attendance_defaults );

		// Expense settings defaults.
		$expense_defaults = array(
			'default_payment_method' => 'cash',
			'require_receipt'        => false,
		);
		add_option( 'berp_expense_settings', $expense_defaults );

		// Portal settings defaults.
		$portal_defaults = array(
			'enable_portal'         => true,
			'portal_page_id'        => 0,
			'username_format'       => 'employee_id',
			'auto_send_credentials' => true,
		);
		add_option( 'berp_portal_settings', $portal_defaults );

		// Notification settings defaults.
		$notification_defaults = array(
			'enable_email_notifications'   => true,
			'notify_payroll_processed'     => true,
			'notify_portal_access_enabled' => true,
		);
		add_option( 'berp_notification_settings', $notification_defaults );

		// Salary formula builder defaults.
		add_option( 'berp_salary_formula', berp_get_default_salary_formula() );
	}

	/**
	 * Register CPTs and Taxonomies temporarily during activation
	 *
	 * This allows us to create default taxonomy terms
	 *
	 * @since 1.0.0
	 */
	private static function register_post_types_and_taxonomies() {
		// Register taxonomies so we can add terms.
		// Expense Category.
		register_taxonomy(
			'berp_expense_category',
			array( 'berp_expense' ),
			array(
				'hierarchical' => true,
				'show_ui'      => true,
				'public'       => false,
			)
		);

		// Department.
		register_taxonomy(
			'berp_department',
			array( 'berp_employee' ),
			array(
				'hierarchical' => true,
				'show_ui'      => true,
				'public'       => false,
			)
		);

		// Project Status.
		register_taxonomy(
			'berp_project_status',
			array( 'berp_site' ),
			array(
				'hierarchical' => true,
				'show_ui'      => true,
				'public'       => false,
			)
		);
	}

	/**
	 * Create default taxonomy terms
	 *
	 * @since 1.0.0
	 */
	private static function create_default_taxonomy_terms() {
		// Expense Categories.
		$expense_categories = array(
			'Payroll',
			'Materials',
			'Equipment',
			'Subcontractors',
			'Transportation',
			'Utilities',
			'Office Supplies',
			'Marketing',
			'Legal & Professional',
			'Maintenance',
			'Other',
		);

		foreach ( $expense_categories as $category ) {
			if ( ! term_exists( $category, 'berp_expense_category' ) ) {
				wp_insert_term( $category, 'berp_expense_category' );
			}
		}
		update_option( 'berp_expense_categories_created', 1 );

		// Departments.
		$departments = array(
			'Construction',
			'Project Management',
			'Administration',
			'Finance',
			'Human Resources',
			'Procurement',
			'Safety',
			'Quality Control',
		);

		foreach ( $departments as $department ) {
			if ( ! term_exists( $department, 'berp_department' ) ) {
				wp_insert_term( $department, 'berp_department' );
			}
		}
		update_option( 'berp_departments_created', 1 );

		// Project Statuses.
		$project_statuses = array(
			'Planning',
			'In Progress',
			'On Hold',
			'Completed',
			'Cancelled',
		);

		foreach ( $project_statuses as $status ) {
			if ( ! term_exists( $status, 'berp_project_status' ) ) {
				wp_insert_term( $status, 'berp_project_status' );
			}
		}
		update_option( 'berp_project_statuses_created', 1 );

		// Mark that default terms have been created.
		update_option( 'berp_default_terms_created', true );
	}
}

