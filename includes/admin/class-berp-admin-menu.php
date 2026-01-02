<?php
/**
 * Admin Menu
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Admin_Menu Class
 *
 * Handles the admin menu structure
 */
class BERP_Admin_Menu {


	/**
	 * Settings handler.
	 *
	 * @var BERP_Settings|null
	 */
	protected $settings;

	/**
	 * Formula builder handler.
	 *
	 * @var BERP_Formula_Builder|null
	 */
	protected $formula_builder;

	/**
	 * Tools handler.
	 *
	 * @var BERP_Tools|null
	 */
	protected $tools;

	/**
	 * Constructor.
	 *
	 * @param BERP_Settings|null        $settings        Settings handler.
	 * @param BERP_Formula_Builder|null $formula_builder Formula builder handler.
	 */
	public function __construct( $settings = null, $formula_builder = null ) {
		$this->settings        = $settings;
		$this->formula_builder = $formula_builder;
		$this->tools           = new BERP_Tools();
	}

	/**
	 * Register admin menus
	 *
	 * @since 1.0.0
	 */
	public function register_menus() {
		// Main menu - BuildErp.
		add_menu_page(
			__( 'BuildErp', 'builderp' ),                    // Page title.
			__( 'BuildErp', 'builderp' ),                    // Menu title.
			'berp_view_dashboard',                              // Capability.
			'builderp',                                         // Menu slug.
			array( $this, 'dashboard_page' ),                     // Callback.
			'dashicons-hammer',                                 // Icon.
			30                                                  // Position.
		);

		// Dashboard submenu.
		add_submenu_page(
			'builderp',
			__( 'Dashboard', 'builderp' ),
			__( 'Dashboard', 'builderp' ),
			'berp_view_dashboard',
			'builderp',
			array( $this, 'dashboard_page' )
		);

		// Clients submenu.
		add_submenu_page(
			'builderp',
			__( 'Clients', 'builderp' ),
			__( 'Clients', 'builderp' ),
			'berp_view_clients',
			'edit.php?post_type=berp_client'
		);

		// Sites submenu.
		add_submenu_page(
			'builderp',
			__( 'Sites/Projects', 'builderp' ),
			__( 'Sites/Projects', 'builderp' ),
			'berp_view_sites',
			'edit.php?post_type=berp_site'
		);

		// Employees submenu.
		add_submenu_page(
			'builderp',
			__( 'Employees', 'builderp' ),
			__( 'Employees', 'builderp' ),
			'berp_view_employees',
			'edit.php?post_type=berp_employee'
		);

		// Attendance submenu.
		add_submenu_page(
			'builderp',
			__( 'Attendance', 'builderp' ),
			__( 'Attendance', 'builderp' ),
			'berp_view_attendance',
			'builderp-attendance',
			array( $this, 'attendance_page' )
		);

		// Payroll submenu.
		add_submenu_page(
			'builderp',
			__( 'Payroll', 'builderp' ),
			__( 'Payroll', 'builderp' ),
			'berp_view_payroll',
			'edit.php?post_type=berp_payroll'
		);

		// Advances submenu.
		add_submenu_page(
			'builderp',
			__( 'Advances', 'builderp' ),
			__( 'Advances', 'builderp' ),
			'berp_view_advances',
			'edit.php?post_type=berp_advance'
		);

		// Expenses submenu.
		add_submenu_page(
			'builderp',
			__( 'Expenses', 'builderp' ),
			__( 'Expenses', 'builderp' ),
			'berp_view_expenses',
			'edit.php?post_type=berp_expense'
		);

		// Quotations submenu.
		add_submenu_page(
			'builderp',
			__( 'Quotations', 'builderp' ),
			__( 'Quotations', 'builderp' ),
			'berp_view_quotations',
			'edit.php?post_type=berp_quotation'
		);

		// Invoices submenu.
		add_submenu_page(
			'builderp',
			__( 'Invoices', 'builderp' ),
			__( 'Invoices', 'builderp' ),
			'berp_view_invoices',
			'edit.php?post_type=berp_invoice'
		);

		// Reports submenu.
		add_submenu_page(
			'builderp',
			__( 'Reports', 'builderp' ),
			__( 'Reports', 'builderp' ),
			'berp_view_reports',
			'builderp-reports',
			array( $this, 'reports_page' )
		);

		// Formula Builder submenu (before Settings).
		add_submenu_page(
			'builderp',
			__( 'Salary Formula Builder', 'builderp' ),
			__( 'Formula Builder', 'builderp' ),
			'manage_options',
			'builderp-formula',
			array( $this, 'formula_builder_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'builderp',
			__( 'Settings', 'builderp' ),
			__( 'Settings', 'builderp' ),
			'berp_manage_settings',
			'builderp-settings',
			array( $this, 'settings_page' )
		);

		// Tools submenu.
		add_submenu_page(
			'builderp',
			__( 'Tools', 'builderp' ),
			__( 'Tools', 'builderp' ),
			'manage_options',
			'builderp-tools',
			array( $this, 'tools_page' )
		);
	}

	/**
	 * Dashboard page callback
	 *
	 * @since 1.0.0
	 */
	public function dashboard_page() {
		// Verify capability.
		if ( ! current_user_can( 'berp_view_dashboard' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		$dashboard = new BERP_Dashboard();
		$dashboard->render_page();
	}

	/**
	 * Attendance page callback
	 *
	 * @since 1.0.0
	 */
	public function attendance_page() {
		// Verify capability.
		if ( ! current_user_can( 'berp_view_attendance' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		$attendance_admin = new BERP_Attendance_Admin();
		$attendance_admin->render_page();
	}

	/**
	 * Reports page callback
	 *
	 * @since 1.0.0
	 */
	public function reports_page() {
		// Verify capability.
		if ( ! current_user_can( 'berp_view_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		$reports_admin = new BERP_Reports_Admin();
		$reports_admin->render_page();
	}

	/**
	 * Settings page callback
	 *
	 * @since 1.0.0
	 */
	public function settings_page() {
		// Verify capability.
		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		if ( $this->settings instanceof BERP_Settings ) {
			$this->settings->render_page();
			return;
		}

		echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1></div>';
	}

	/**
	 * Formula Builder page callback
	 *
	 * @since 1.0.0
	 */
	public function formula_builder_page() {
		// Verify capability - allow manage_options for administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		if ( $this->formula_builder instanceof BERP_Formula_Builder ) {
			$this->formula_builder->render_page();
			return;
		}

		// Fallback if formula builder not injected.
		$formula_builder = new BERP_Formula_Builder();
		$formula_builder->render_page();
	}

	/**
	 * Tools page callback
	 *
	 * @since 1.0.0
	 */
	public function tools_page() {
		// Verify capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		if ( $this->tools instanceof BERP_Tools ) {
			$this->tools->render_page();
			return;
		}

		// Fallback if tools not injected.
		$tools = new BERP_Tools();
		$tools->render_page();
	}
}

