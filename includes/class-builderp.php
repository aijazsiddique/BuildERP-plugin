<?php
/**
 * Core BuildErp bootstrap class.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main BuildErp Plugin Class
 *
 * Singleton pattern to ensure only one instance exists.
 *
 * @since 1.0.0
 */
final class BuildErp {


	/**
	 * Single instance of the class.
	 *
	 * @var BuildErp
	 */
	private static $instance = null;

	/**
	 * Loader instance.
	 *
	 * @var BERP_Loader
	 */
	protected $loader;

	/**
	 * Settings handler instance.
	 *
	 * @var BERP_Settings
	 */
	protected $settings;

	/**
	 * Get the singleton instance.
	 *
	 * @return BuildErp
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize the plugin.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		// Core classes.
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-loader.php';
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-activator.php';
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-deactivator.php';

		// Custom Post Types.
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-employee-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-attendance-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-payroll-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-expense-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-client-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-site-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-quotation-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-invoice-cpt.php';
		include_once BERP_PLUGIN_DIR . 'includes/cpt/class-berp-advance-cpt.php';

		// Taxonomies.
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-taxonomies.php';

		// Admin.
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-admin-menu.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-dashboard.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-settings.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-reports-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-tools.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-employee-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-employee-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-client-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-client-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-site-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-site-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-attendance-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-attendance-list.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-formula-builder.php';
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-formula-evaluator.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-fix-capabilities.php';
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-payroll-calculator.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-payroll-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-payroll-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-payroll-api.php';
		include_once BERP_PLUGIN_DIR . 'includes/pdf/class-berp-salary-slip-pdf.php';

		// Expense system.
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-expense-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-expense-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-expense-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-expense-api.php';
		include_once BERP_PLUGIN_DIR . 'includes/class-berp-recurring-expense-cron.php';
		include_once BERP_PLUGIN_DIR . 'includes/export/class-berp-expense-csv-exporter.php';
		include_once BERP_PLUGIN_DIR . 'includes/export/class-berp-expense-pdf-exporter.php';

		// Quotation system.
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-quotation-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-quotation-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-quotation-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-quotation-api.php';
		include_once BERP_PLUGIN_DIR . 'includes/pdf/class-berp-quotation-pdf.php';
		include_once BERP_PLUGIN_DIR . 'includes/export/class-berp-quotation-csv-exporter.php';

		// Invoice system.
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-invoice-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-invoice-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-invoice-admin.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-invoice-api.php';
		include_once BERP_PLUGIN_DIR . 'includes/pdf/class-berp-invoice-pdf.php';
		include_once BERP_PLUGIN_DIR . 'includes/export/class-berp-invoice-csv-exporter.php';

		// Advance request system.
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-advance-metaboxes.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-advance-list-table.php';
		include_once BERP_PLUGIN_DIR . 'includes/admin/class-berp-advance-notifications.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-advance-api.php';

		// Portal system.
		include_once BERP_PLUGIN_DIR . 'includes/portal/class-berp-portal.php';
		include_once BERP_PLUGIN_DIR . 'includes/portal/class-berp-portal-login.php';
		include_once BERP_PLUGIN_DIR . 'includes/portal/class-berp-employee-dashboard.php';
		include_once BERP_PLUGIN_DIR . 'includes/portal/class-berp-timekeeper-dashboard.php';

		// Helper functions.
		include_once BERP_PLUGIN_DIR . 'includes/functions.php';
		include_once BERP_PLUGIN_DIR . 'includes/options-helpers.php';
		include_once BERP_PLUGIN_DIR . 'includes/meta-helpers.php';
		include_once BERP_PLUGIN_DIR . 'includes/taxonomy-helpers.php';
		include_once BERP_PLUGIN_DIR . 'includes/user-meta-helpers.php';
		include_once BERP_PLUGIN_DIR . 'includes/berp-formula-settings-sync.php';
		include_once BERP_PLUGIN_DIR . 'includes/api/class-berp-attendance-api.php';

		$this->loader = new BERP_Loader();
	}

	/**
	 * Register all hooks with WordPress.
	 */
	private function register_hooks() {
		// Register CPTs and Taxonomies.
		$employee_cpt = new BERP_Employee_CPT();
		$this->loader->add_action( 'init', $employee_cpt, 'register' );

		$attendance_cpt = new BERP_Attendance_CPT();
		$this->loader->add_action( 'init', $attendance_cpt, 'register' );

		$payroll_cpt = new BERP_Payroll_CPT();
		$this->loader->add_action( 'init', $payroll_cpt, 'register' );

		$expense_cpt = new BERP_Expense_CPT();
		$this->loader->add_action( 'init', $expense_cpt, 'register' );

		$client_cpt = new BERP_Client_CPT();
		$this->loader->add_action( 'init', $client_cpt, 'register' );

		$site_cpt = new BERP_Site_CPT();
		$this->loader->add_action( 'init', $site_cpt, 'register' );

		$quotation_cpt = new BERP_Quotation_CPT();
		$this->loader->add_action( 'init', $quotation_cpt, 'register' );

		$invoice_cpt = new BERP_Invoice_CPT();
		$this->loader->add_action( 'init', $invoice_cpt, 'register' );

		$advance_cpt = new BERP_Advance_CPT();
		$this->loader->add_action( 'init', $advance_cpt, 'register' );

		// Register Taxonomies.
		$taxonomies = new BERP_Taxonomies();
		$this->loader->add_action( 'init', $taxonomies, 'register_all' );

		// Settings.
		$this->settings = new BERP_Settings();
		$this->loader->add_action( 'admin_init', $this->settings, 'register_settings' );
		$this->loader->add_action( 'admin_post_berp_settings_export', $this->settings, 'handle_export' );
		$this->loader->add_action( 'admin_post_berp_settings_import', $this->settings, 'handle_import' );

		// Admin.
		$admin = new BERP_Admin();
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		// Dashboard.
		new BERP_Dashboard();

		// Reports.
		$reports_admin = new BERP_Reports_Admin();
		$this->loader->add_action( 'admin_enqueue_scripts', $reports_admin, 'enqueue_scripts' );

		// Formula Builder.
		$formula_builder = new BERP_Formula_Builder();
		$this->loader->add_action( 'admin_post_berp_save_formula', $formula_builder, 'handle_save' );
		$this->loader->add_action( 'wp_ajax_berp_preview_formula', $formula_builder, 'ajax_preview_formula' );
		$this->loader->add_action( 'admin_enqueue_scripts', $formula_builder, 'localize_script' );

		// Admin Menu (with formula builder passed).
		$admin_menu = new BERP_Admin_Menu( $this->settings, $formula_builder );
		$this->loader->add_action( 'admin_menu', $admin_menu, 'register_menus' );

		// Employee metaboxes and admin columns.
		$employee_metaboxes = new BERP_Employee_Metaboxes();
		$this->loader->add_action( 'add_meta_boxes', $employee_metaboxes, 'register_meta_boxes' );
		$this->loader->add_action( 'save_post_berp_employee', $employee_metaboxes, 'save_employee', 10, 2 );
		$this->loader->add_filter( 'manage_berp_employee_posts_columns', $employee_metaboxes, 'register_columns' );
		$this->loader->add_action( 'manage_berp_employee_posts_custom_column', $employee_metaboxes, 'render_columns', 10, 2 );
		$this->loader->add_filter( 'manage_edit-berp_employee_sortable_columns', $employee_metaboxes, 'register_sortable_columns' );
		$this->loader->add_action( 'pre_get_posts', $employee_metaboxes, 'handle_column_sorting' );
		$this->loader->add_action( 'admin_notices', $employee_metaboxes, 'render_admin_notices' );
		$this->loader->add_action( 'wp_ajax_berp_resend_credentials', $employee_metaboxes, 'ajax_resend_credentials' );

		// Employee admin list enhancements.
		$employee_admin = new BERP_Employee_Admin();
		$employee_admin->hooks();

		// Attendance admin page + REST API.
		$attendance_admin = new BERP_Attendance_Admin();
		$attendance_admin->hooks();

		$attendance_list = new BERP_Attendance_List();
		$attendance_list->hooks();

		$attendance_api = new BERP_Attendance_API();
		$this->loader->add_action( 'rest_api_init', $attendance_api, 'register_routes' );

		// Temporary capability fix page.
		$fix_caps = new BERP_Fix_Capabilities();
		$fix_caps->hooks();

		// Payroll system.
		if ( is_admin() ) {
			new BERP_Payroll_Metaboxes();
			new BERP_Payroll_Admin();
		}
		new BERP_Payroll_API();

		// Expense system.
		$expense_metaboxes  = new BERP_Expense_Metaboxes();
		$expense_list_table = new BERP_Expense_List_Table();
		$expense_admin      = new BERP_Expense_Admin();
		$expense_api        = new BERP_Expense_API();
		$expense_cron       = new BERP_Recurring_Expense_Cron();

		// Quotation system.
		$quotation_metaboxes  = new BERP_Quotation_Metaboxes();
		$quotation_list_table = new BERP_Quotation_List_Table();
		$quotation_admin      = new BERP_Quotation_Admin();
		$quotation_api        = new BERP_Quotation_API();

		// Invoice system.
		$invoice_metaboxes  = new BERP_Invoice_Metaboxes();
		$invoice_list_table = new BERP_Invoice_List_Table();
		$invoice_admin      = new BERP_Invoice_Admin();
		$invoice_api        = new BERP_Invoice_API();

		// Advance request system.
		$advance_metaboxes     = new BERP_Advance_Metaboxes();
		$advance_list_table    = new BERP_Advance_List_Table();
		$advance_notifications = new BERP_Advance_Notifications();
		$advance_api           = new BERP_Advance_API();

		// Portal system.
		$portal = new BERP_Portal();
		$portal->init();

		$portal_login = new BERP_Portal_Login();
		$portal_login->init();
	}

	/**
	 * Run the loader to execute all hooks.
	 */
	public function run() {
		$this->loader->run();
	}
}

