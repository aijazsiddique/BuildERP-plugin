<?php
/**
 * Settings management for BuildErp.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Settings Class
 *
 * Handles settings registration, rendering, and import/export.
 */
class BERP_Settings {


	/**
	 * Option name used to store settings.
	 *
	 * @var string
	 */
	protected $option_name = 'berp_settings';

	/**
	 * Register settings and sanitization.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'berp_settings_group',
			$this->option_name,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Render the settings page content.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'aic_builderp' ) );
		}

		$settings      = $this->get_settings();
		$sections      = $this->get_sections();
		$active_tab    = isset( $_REQUEST['berp_tab'] ) ? sanitize_key( wp_unslash( $_REQUEST['berp_tab'] ) ) : 'general';
		$active_subtab = isset( $_REQUEST['berp_subtab'] ) ? sanitize_key( wp_unslash( $_REQUEST['berp_subtab'] ) ) : '';
		$currencies    = $this->get_currencies();
		?>
		<div class="wrap builderp-settings-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php settings_errors( 'berp_settings' ); ?>

			<div class="berp-settings-layout">
				<nav class="berp-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'aic_builderp' ); ?>">
					<ul>
		<?php foreach ( $sections as $section_id => $section_label ) : ?>
							<li>
								<a href="#<?php echo esc_attr( $section_id ); ?>" data-tab-target="<?php echo esc_attr( $section_id ); ?>" class="<?php echo $active_tab === $section_id ? 'is-active' : ''; ?>">
			<?php echo esc_html( $section_label ); ?>
								</a>
							</li>
		<?php endforeach; ?>
					</ul>
				</nav>

				<div class="berp-settings-panels">
					<form method="post" action="options.php" class="berp-settings-form">
		<?php settings_fields( 'berp_settings_group' ); ?>
						<input type="hidden" name="berp_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
						<input type="hidden" name="berp_subtab" value="<?php echo esc_attr( $active_subtab ); ?>" />

		<?php foreach ( $sections as $section_id => $section_label ) : ?>
							<div class="berp-tab-panel <?php echo $active_tab === $section_id ? 'is-active' : ''; ?>" data-tab-panel="<?php echo esc_attr( $section_id ); ?>">
								<h2><?php echo esc_html( $section_label ); ?></h2>
			<?php
			switch ( $section_id ) {
				case 'general':
					$this->render_general_settings( $settings, $currencies );
					break;
				case 'payroll':
					$this->render_payroll_settings( $settings );
					break;
				case 'attendance':
					$this->render_attendance_settings( $settings );
					break;
				case 'expense':
					$this->render_expense_settings( $settings );
					break;
				case 'quotation':
					$this->render_quotation_settings( $settings );
					break;
				case 'site':
					$this->render_site_settings( $settings );
					break;
				case 'employee':
					$this->render_employee_settings( $settings );
					break;
				case 'notification':
					$this->render_notification_settings( $settings );
					break;
				case 'portal':
					$this->render_portal_settings( $settings );
					break;
				case 'advance':
					$this->render_advance_settings( $settings );
					break;
				case 'advanced':
					$this->render_advanced_settings( $settings );
					break;
			}
			?>
							</div>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save Settings', 'aic_builderp' ) ); ?>
					</form>

					<div class="berp-settings-io">
						<div class="berp-settings-card">
							<h2><?php esc_html_e( 'Export Settings', 'aic_builderp' ); ?></h2>
							<p><?php esc_html_e( 'Download a JSON backup of your current settings.', 'aic_builderp' ); ?></p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'berp_settings_export', 'berp_settings_export_nonce' ); ?>
								<input type="hidden" name="action" value="berp_settings_export">
								<?php submit_button( __( 'Export Settings', 'aic_builderp' ), 'secondary', 'submit', false ); ?>
							</form>
						</div>

						<div class="berp-settings-card">
							<h2><?php esc_html_e( 'Import Settings', 'aic_builderp' ); ?></h2>
							<p><?php esc_html_e( 'Upload a JSON file exported from BuildErp.', 'aic_builderp' ); ?></p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
								<?php wp_nonce_field( 'berp_settings_import', 'berp_settings_import_nonce' ); ?>
								<input type="hidden" name="action" value="berp_settings_import">
								<input type="file" name="berp_settings_file" accept="application/json" />
								<?php submit_button( __( 'Import Settings', 'aic_builderp' ), 'secondary', 'submit', false ); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Handle export of settings as JSON.
	 *
	 * @since 1.0.0
	 */
	public function handle_export() {
		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to export settings.', 'aic_builderp' ) );
		}

		check_admin_referer( 'berp_settings_export', 'berp_settings_export_nonce' );

		$settings = $this->get_settings();
		$json     = wp_json_encode( $settings );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="builderp-settings.json"' );
		header( 'Content-Length: ' . strlen( $json ) );
		echo $json;
		exit;
	}

	/**
	 * Handle import of settings from JSON.
	 *
	 * @since 1.0.0
	 */
	public function handle_import() {
		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to import settings.', 'aic_builderp' ) );
		}

		check_admin_referer( 'berp_settings_import', 'berp_settings_import_nonce' );

		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=builderp-settings' );

		if ( empty( $_FILES['berp_settings_file']['tmp_name'] ) ) {
			add_settings_error( 'berp_settings', 'berp_settings_import', esc_html__( 'Please select a JSON file to import.', 'aic_builderp' ), 'error' );
			return $this->redirect_with_errors( $redirect );
		}

		// Validate file type - only allow JSON.
		$file_ext = strtolower( pathinfo( $_FILES['berp_settings_file']['name'], PATHINFO_EXTENSION ) );
		if ( 'json' !== $file_ext ) {
			add_settings_error( 'berp_settings', 'berp_settings_import', esc_html__( 'Invalid file type. Only JSON files are allowed.', 'aic_builderp' ), 'error' );
			return $this->redirect_with_errors( $redirect );
		}

		$raw  = file_get_contents( $_FILES['berp_settings_file']['tmp_name'] );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			add_settings_error( 'berp_settings', 'berp_settings_import', esc_html__( 'Invalid settings file.', 'aic_builderp' ), 'error' );
			return $this->redirect_with_errors( $redirect );
		}

		$sanitized = $this->sanitize( $data );
		update_option( $this->option_name, $sanitized );

		// Log activity.
		if ( function_exists( 'berp_log_activity' ) ) {
			berp_log_activity(
				'settings_imported',
				__( 'Plugin settings imported from file', 'aic_builderp' ),
				array()
			);
		}

		add_settings_error( 'berp_settings', 'berp_settings_import_success', esc_html__( 'Settings imported successfully.', 'aic_builderp' ), 'updated' );
		return $this->redirect_with_errors( $redirect );
	}
	/**
	 * Redirect back to the settings page with settings errors preserved.
	 *
	 * @param string $redirect Redirect URL.
	 */
	private function redirect_with_errors( $redirect ) {
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Get current settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$saved = get_option( $this->option_name, array() );
		return $this->merge_settings( $saved, $this->get_defaults() );
	}

	/**
	 * Deep merge saved settings with defaults.
	 *
	 * @param  array $saved    Saved settings.
	 * @param  array $defaults Default settings.
	 * @return array
	 */
	private function merge_settings( $saved, $defaults ) {
		return array_replace_recursive( $defaults, is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Sanitize incoming settings.
	 *
	 * @param  array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize( $input ) {
		$defaults = $this->get_defaults();
		$clean    = $defaults;
		$input    = is_array( $input ) ? $input : array();

		// General.
		$general                               = isset( $input['general'] ) ? $input['general'] : array();
		$clean['general']['company_name']      = sanitize_text_field( $general['company_name'] ?? '' );
		$clean['general']['company_email']     = sanitize_email( $general['company_email'] ?? '' );
		$clean['general']['company_phone']     = sanitize_text_field( $general['company_phone'] ?? '' );
		$clean['general']['company_address']   = sanitize_textarea_field( $general['company_address'] ?? '' );
		$clean['general']['company_logo']      = esc_url_raw( $general['company_logo'] ?? '' );
		$clean['general']['currency']          = sanitize_text_field( $general['currency'] ?? $defaults['general']['currency'] );
		$clean['general']['date_format']       = sanitize_text_field( $general['date_format'] ?? $defaults['general']['date_format'] );
		$clean['general']['time_format']       = sanitize_text_field( $general['time_format'] ?? $defaults['general']['time_format'] );
		$clean['general']['fiscal_year_start'] = sanitize_text_field( $general['fiscal_year_start'] ?? $defaults['general']['fiscal_year_start'] );

		// Payroll.
		$payroll                                  = isset( $input['payroll'] ) ? $input['payroll'] : array();
		$mode                                     = isset( $payroll['working_days_mode'] ) && in_array( $payroll['working_days_mode'], array( 'fixed', 'actual' ), true ) ? $payroll['working_days_mode'] : $defaults['payroll']['working_days_mode'];
		$clean['payroll']['working_days_mode']    = $mode;
		$clean['payroll']['working_days']         = 'fixed' === $mode ? max( 1, min( 31, absint( $payroll['working_days'] ?? $defaults['payroll']['working_days'] ) ) ) : 0;
		$clean['payroll']['weekend_days']         = array_values( array_intersect( array( 'friday', 'saturday', 'sunday' ), $payroll['weekend_days'] ?? array() ) );
		$clean['payroll']['weekend_payment_rule'] = ! empty( $payroll['weekend_payment_rule'] ) ? 1 : 0;
		$clean['payroll']['holidays']             = sanitize_textarea_field( $payroll['holidays'] ?? '' );
		$clean['payroll']['calculation_mode']     = in_array( $payroll['calculation_mode'] ?? '', array( 'auto', 'manual' ), true ) ? $payroll['calculation_mode'] : $defaults['payroll']['calculation_mode'];
		$clean['payroll']['processing_day']       = min( 31, max( 1, absint( $payroll['processing_day'] ?? $defaults['payroll']['processing_day'] ) ) );

		// Attendance.
		$attendance                                 = isset( $input['attendance'] ) ? $input['attendance'] : array();
		$clean['attendance']['editable_days_limit'] = absint( $attendance['editable_days_limit'] ?? $defaults['attendance']['editable_days_limit'] );
		$clean['attendance']['block_future']        = ! empty( $attendance['block_future'] ) ? 1 : 0;
		$clean['attendance']['require_site']        = ! empty( $attendance['require_site'] ) ? 1 : 0;
		$clean['attendance']['overtime_method']     = in_array( $attendance['overtime_method'] ?? '', array( 'flat', 'multiplier', 'custom' ), true ) ? $attendance['overtime_method'] : $defaults['attendance']['overtime_method'];
		$clean['attendance']['weekday_rate']        = max( 0, floatval( $attendance['weekday_rate'] ?? $defaults['attendance']['weekday_rate'] ) );
		$clean['attendance']['weekend_rate']        = max( 0, floatval( $attendance['weekend_rate'] ?? $defaults['attendance']['weekend_rate'] ) );
		$clean['attendance']['holiday_rate']        = max( 0, floatval( $attendance['holiday_rate'] ?? $defaults['attendance']['holiday_rate'] ) );
		$clean['attendance']['default_multiplier']  = max( 0, floatval( $attendance['default_multiplier'] ?? $defaults['attendance']['default_multiplier'] ) );

		// Expense.
		$expense                                = isset( $input['expense'] ) ? $input['expense'] : array();
		$clean['expense']['default_categories'] = sanitize_textarea_field( $expense['default_categories'] ?? $defaults['expense']['default_categories'] );
		$clean['expense']['payment_methods']    = sanitize_textarea_field( $expense['payment_methods'] ?? $defaults['expense']['payment_methods'] );
		$clean['expense']['enable_recurring']   = ! empty( $expense['enable_recurring'] ) ? 1 : 0;
		$clean['expense']['approval_workflow']  = ! empty( $expense['approval_workflow'] ) ? 1 : 0;
		$clean['expense']['require_receipt']    = ! empty( $expense['require_receipt'] ) ? 1 : 0;

		// Quotation & Invoice.
		$quotation                                  = isset( $input['quotation'] ) ? $input['quotation'] : array();
		$clean['quotation']['quotation_prefix']     = sanitize_text_field( $quotation['quotation_prefix'] ?? $defaults['quotation']['quotation_prefix'] );
		$clean['quotation']['invoice_prefix']       = sanitize_text_field( $quotation['invoice_prefix'] ?? $defaults['quotation']['invoice_prefix'] );
		$clean['quotation']['number_format']        = in_array( $quotation['number_format'] ?? '', array( 'sequential', 'year', 'custom' ), true ) ? $quotation['number_format'] : $defaults['quotation']['number_format'];
		$clean['quotation']['starting_number']      = absint( $quotation['starting_number'] ?? $defaults['quotation']['starting_number'] );
		$clean['quotation']['tax_rate']             = max( 0, floatval( $quotation['tax_rate'] ?? $defaults['quotation']['tax_rate'] ) );
		$clean['quotation']['payment_terms']        = sanitize_text_field( $quotation['payment_terms'] ?? $defaults['quotation']['payment_terms'] );
		$clean['quotation']['quotation_validity']   = absint( $quotation['quotation_validity'] ?? $defaults['quotation']['quotation_validity'] );
		$clean['quotation']['invoice_due_days']     = absint( $quotation['invoice_due_days'] ?? $defaults['quotation']['invoice_due_days'] );
		$clean['quotation']['theme_color']          = sanitize_text_field( $quotation['theme_color'] ?? $defaults['quotation']['theme_color'] );
		$clean['quotation']['header_text']          = sanitize_textarea_field( $quotation['header_text'] ?? $defaults['quotation']['header_text'] );
		$clean['quotation']['terms']                = sanitize_textarea_field( $quotation['terms'] ?? '' );
		$clean['quotation']['pdf_terms']            = sanitize_textarea_field( $quotation['pdf_terms'] ?? '' );
		$clean['quotation']['enable_quote_email']   = ! empty( $quotation['enable_quote_email'] ) ? 1 : 0;
		$clean['quotation']['enable_invoice_email'] = ! empty( $quotation['enable_invoice_email'] ) ? 1 : 0;

		// Site.
		$site                                    = isset( $input['site'] ) ? $input['site'] : array();
		$clean['site']['default_status']         = sanitize_text_field( $site['default_status'] ?? $defaults['site']['default_status'] );
		$clean['site']['budget_alert']           = max( 0, floatval( $site['budget_alert'] ?? $defaults['site']['budget_alert'] ) );
		$clean['site']['require_manager']        = ! empty( $site['require_manager'] ) ? 1 : 0;
		$clean['site']['enable_budget_tracking'] = ! empty( $site['enable_budget_tracking'] ) ? 1 : 0;
		// Employee.
		$employee                                       = isset( $input['employee'] ) ? $input['employee'] : array();
		$clean['employee']['id_format']                 = in_array( $employee['id_format'] ?? '', array( 'manual', 'auto' ), true ) ? $employee['id_format'] : $defaults['employee']['id_format'];
		$clean['employee']['starting_id']               = absint( $employee['starting_id'] ?? $defaults['employee']['starting_id'] );
		$clean['employee']['default_status']            = sanitize_text_field( $employee['default_status'] ?? $defaults['employee']['default_status'] );
		$clean['employee']['advance_requires_approval'] = ! empty( $employee['advance_requires_approval'] ) ? 1 : 0;
		$clean['employee']['advance_max_percent']       = max( 0, floatval( $employee['advance_max_percent'] ?? $defaults['employee']['advance_max_percent'] ) );
		$clean['employee']['allow_installments']        = ! empty( $employee['allow_installments'] ) ? 1 : 0;
		$clean['employee']['custom_fields']             = $this->sanitize_custom_fields( $employee['custom_fields'] ?? $defaults['employee']['custom_fields'] );

		// Notification.
		$notification                                      = isset( $input['notification'] ) ? $input['notification'] : array();
		$clean['notification']['employee_notifications']   = ! empty( $notification['employee_notifications'] ) ? 1 : 0;
		$clean['notification']['admin_notifications']      = ! empty( $notification['admin_notifications'] ) ? 1 : 0;
		$clean['notification']['client_notifications']     = ! empty( $notification['client_notifications'] ) ? 1 : 0;
		$clean['notification']['timekeeper_notifications'] = ! empty( $notification['timekeeper_notifications'] ) ? 1 : 0;
		$clean['notification']['smtp_host']                = sanitize_text_field( $notification['smtp_host'] ?? '' );
		$clean['notification']['smtp_port']                = absint( $notification['smtp_port'] ?? 0 );
		$clean['notification']['smtp_encryption']          = sanitize_text_field( $notification['smtp_encryption'] ?? '' );
		$clean['notification']['smtp_user']                = sanitize_text_field( $notification['smtp_user'] ?? '' );
		$clean['notification']['smtp_pass']                = sanitize_text_field( $notification['smtp_pass'] ?? '' );
		$clean['notification']['email_subject']            = sanitize_text_field( $notification['email_subject'] ?? '' );
		$clean['notification']['email_template']           = sanitize_textarea_field( $notification['email_template'] ?? '' );

		// Portal.
		$portal                                      = isset( $input['portal'] ) ? $input['portal'] : array();
		$clean['portal']['portal_slug']              = sanitize_title( $portal['portal_slug'] ?? $defaults['portal']['portal_slug'] );
		$clean['portal']['enable_portal']            = ! empty( $portal['enable_portal'] ) ? 1 : 0;
		$clean['portal']['login_page_id']            = absint( $portal['login_page_id'] ?? 0 );
		$clean['portal']['dashboard_page_id']        = absint( $portal['dashboard_page_id'] ?? 0 );
		$clean['portal']['use_standalone_template']  = ! empty( $portal['use_standalone_template'] ) ? 1 : 0;
		$clean['portal']['allow_profile_edit']       = ! empty( $portal['allow_profile_edit'] ) ? 1 : 0;
		$clean['portal']['branding_color']           = sanitize_text_field( $portal['branding_color'] ?? $defaults['portal']['branding_color'] );
		$clean['portal']['session_timeout']          = max( 1, absint( $portal['session_timeout'] ?? $defaults['portal']['session_timeout'] ) );
		$clean['portal']['username_format']          = in_array( $portal['username_format'] ?? '', array( 'email', 'employee_id', 'custom' ), true ) ? $portal['username_format'] : $defaults['portal']['username_format'];
		$clean['portal']['password_strength']        = in_array( $portal['password_strength'] ?? '', array( 'strong', 'medium', 'none' ), true ) ? $portal['password_strength'] : $defaults['portal']['password_strength'];
		$clean['portal']['portal_access_behavior']   = sanitize_text_field( $portal['portal_access_behavior'] ?? $defaults['portal']['portal_access_behavior'] );
		$clean['portal']['employee_delete_behavior'] = sanitize_text_field( $portal['employee_delete_behavior'] ?? $defaults['portal']['employee_delete_behavior'] );
		$clean['portal']['send_welcome_email']       = ! empty( $portal['send_welcome_email'] ) ? 1 : 0;
		$clean['portal']['welcome_email_subject']    = sanitize_text_field( $portal['welcome_email_subject'] ?? $defaults['portal']['welcome_email_subject'] );
		$clean['portal']['welcome_email_template']   = sanitize_textarea_field( $portal['welcome_email_template'] ?? $defaults['portal']['welcome_email_template'] );

		// Advanced.
		$advanced                                = isset( $input['advanced'] ) ? $input['advanced'] : array();
		$clean['advanced']['activity_logging']   = ! empty( $advanced['activity_logging'] ) ? 1 : 0;
		$clean['advanced']['data_retention']     = max( 0, absint( $advanced['data_retention'] ?? $defaults['advanced']['data_retention'] ) );
		$clean['advanced']['uninstall_behavior'] = sanitize_text_field( $advanced['uninstall_behavior'] ?? $defaults['advanced']['uninstall_behavior'] );
		$clean['advanced']['report_caching']     = ! empty( $advanced['report_caching'] ) ? 1 : 0;

		return $clean;
	}
	/**
	 * Sections used for vertical navigation.
	 *
	 * @return array
	 */
	private function get_sections() {
		return array(
			'general'      => esc_html__( 'General Settings', 'aic_builderp' ),
			'payroll'      => esc_html__( 'Payroll Settings', 'aic_builderp' ),
			'attendance'   => esc_html__( 'Attendance Settings', 'aic_builderp' ),
			'expense'      => esc_html__( 'Expense Settings', 'aic_builderp' ),
			'quotation'    => esc_html__( 'Quotation & Invoice Settings', 'aic_builderp' ),
			'site'         => esc_html__( 'Site/Project Settings', 'aic_builderp' ),
			'employee'     => esc_html__( 'Employee Settings', 'aic_builderp' ),
			'notification' => esc_html__( 'Notification Settings', 'aic_builderp' ),
			'portal'       => esc_html__( 'Employee Portal Settings', 'aic_builderp' ),
			'advance'      => esc_html__( 'Advance Settings', 'aic_builderp' ),
			'advanced'     => esc_html__( 'Advanced Settings', 'aic_builderp' ),
		);
	}

	/**
	 * Default settings structure.
	 *
	 * @return array
	 */
	private function get_defaults() {
		return array(
			'general'      => array(
				'company_name'      => '',
				'company_email'     => '',
				'company_phone'     => '',
				'company_address'   => '',
				'company_logo'      => '',
				'currency'          => 'USD',
				'date_format'       => 'Y-m-d',
				'time_format'       => '24',
				'fiscal_year_start' => 'January',
			),
			'payroll'      => array(
				'working_days_mode'    => 'fixed',
				'working_days'         => 30,
				'weekend_days'         => array( 'sunday' ),
				'weekend_payment_rule' => 0,
				'holidays'             => '',
				'calculation_mode'     => 'auto',
				'processing_day'       => 1,
			),
			'attendance'   => array(
				'editable_days_limit' => 7,
				'block_future'        => 1,
				'require_site'        => 1,
				'overtime_method'     => 'multiplier',
				'weekday_rate'        => 1.5,
				'weekend_rate'        => 2.0,
				'holiday_rate'        => 2.5,
				'default_multiplier'  => 1.5,
			),
			'expense'      => array(
				'default_categories' => "Travel\nMaterials\nUtilities\nMiscellaneous",
				'payment_methods'    => "Cash\nBank Transfer\nCheque\nCredit Card",
				'enable_recurring'   => 0,
				'approval_workflow'  => 0,
				'require_receipt'    => 1,
			),
			'advance'      => array(
				'default_repayment_type' => 'installments',
				'installment_threshold'  => 500,
				'installments_high'      => 5,
				'installments_low'       => 2,
			),
			'quotation'    => array(
				'quotation_prefix'     => 'QUO',
				'invoice_prefix'       => 'INV',
				'number_format'        => 'sequential',
				'starting_number'      => 1,
				'tax_rate'             => 5,
				'payment_terms'        => '30 days',
				'quotation_validity'   => 30,
				'invoice_due_days'     => 14,
				'theme_color'          => '#2271b1',
				'header_text'          => '',
				'terms'                => '',
				'pdf_terms'            => '',
				'enable_quote_email'   => 1,
				'enable_invoice_email' => 1,
			),
			'site'         => array(
				'default_status'         => 'planning',
				'budget_alert'           => 80,
				'require_manager'        => 0,
				'enable_budget_tracking' => 1,
			),
			'employee'     => array(
				'id_format'                 => 'auto',
				'starting_id'               => 1,
				'default_status'            => 'active',
				'advance_requires_approval' => 1,
				'advance_max_percent'       => 50,
				'allow_installments'        => 1,
				'custom_fields'             => array(),
			),
			'notification' => array(
				'employee_notifications'   => 1,
				'admin_notifications'      => 1,
				'client_notifications'     => 1,
				'timekeeper_notifications' => 1,
				'smtp_host'                => '',
				'smtp_port'                => 0,
				'smtp_encryption'          => '',
				'smtp_user'                => '',
				'smtp_pass'                => '',
				'email_subject'            => '',
				'email_template'           => '',
			),
			'portal'       => array(
				'portal_slug'              => 'berp-portal',
				'enable_portal'            => 1,
				'login_page_id'            => 0,
				'dashboard_page_id'        => 0,
				'use_standalone_template'  => 1,
				'allow_profile_edit'       => 1,
				'branding_color'           => '#2271b1',
				'session_timeout'          => 30,
				'username_format'          => 'email',
				'password_strength'        => 'strong',
				'portal_access_behavior'   => 'keep_user',
				'employee_delete_behavior' => 'keep_user',
				'send_welcome_email'       => 1,
				'welcome_email_subject'    => __( 'Welcome to the Employee Portal', 'aic_builderp' ),
				'welcome_email_template'   => __( 'Hi {name}, your portal account is ready. Username: {username}', 'aic_builderp' ),
			),
			'advanced'     => array(
				'activity_logging'   => 1,
				'data_retention'     => 0,
				'uninstall_behavior' => 'keep',
				'report_caching'     => 1,
			),
		);
	}
	/**
	 * Currency options for dropdown.
	 *
	 * @return array
	 */
	private function get_currencies() {
		return array(
			'USD' => __( 'US Dollar (USD)', 'aic_builderp' ),
			'EUR' => __( 'Euro (EUR)', 'aic_builderp' ),
			'GBP' => __( 'British Pound (GBP)', 'aic_builderp' ),
			'AED' => __( 'UAE Dirham (AED)', 'aic_builderp' ),
			'INR' => __( 'Indian Rupee (INR)', 'aic_builderp' ),
			'AUD' => __( 'Australian Dollar (AUD)', 'aic_builderp' ),
			'CAD' => __( 'Canadian Dollar (CAD)', 'aic_builderp' ),
			'SGD' => __( 'Singapore Dollar (SGD)', 'aic_builderp' ),
			'SAR' => __( 'Saudi Riyal (SAR)', 'aic_builderp' ),
			'QAR' => __( 'Qatari Riyal (QAR)', 'aic_builderp' ),
			'ZAR' => __( 'South African Rand (ZAR)', 'aic_builderp' ),
			'JPY' => __( 'Japanese Yen (JPY)', 'aic_builderp' ),
			'CNY' => __( 'Chinese Yuan (CNY)', 'aic_builderp' ),
			'CHF' => __( 'Swiss Franc (CHF)', 'aic_builderp' ),
			'NZD' => __( 'New Zealand Dollar (NZD)', 'aic_builderp' ),
			'HKD' => __( 'Hong Kong Dollar (HKD)', 'aic_builderp' ),
			'MYR' => __( 'Malaysian Ringgit (MYR)', 'aic_builderp' ),
			'PKR' => __( 'Pakistani Rupee (PKR)', 'aic_builderp' ),
			'BDT' => __( 'Bangladeshi Taka (BDT)', 'aic_builderp' ),
			'LKR' => __( 'Sri Lankan Rupee (LKR)', 'aic_builderp' ),
			'NGN' => __( 'Nigerian Naira (NGN)', 'aic_builderp' ),
			'KES' => __( 'Kenyan Shilling (KES)', 'aic_builderp' ),
			'EGP' => __( 'Egyptian Pound (EGP)', 'aic_builderp' ),
			'TRY' => __( 'Turkish Lira (TRY)', 'aic_builderp' ),
			'THB' => __( 'Thai Baht (THB)', 'aic_builderp' ),
			'PHP' => __( 'Philippine Peso (PHP)', 'aic_builderp' ),
			'MXN' => __( 'Mexican Peso (MXN)', 'aic_builderp' ),
			'BRL' => __( 'Brazilian Real (BRL)', 'aic_builderp' ),
			'ARS' => __( 'Argentine Peso (ARS)', 'aic_builderp' ),
			'CLP' => __( 'Chilean Peso (CLP)', 'aic_builderp' ),
			'COP' => __( 'Colombian Peso (COP)', 'aic_builderp' ),
			'DKK' => __( 'Danish Krone (DKK)', 'aic_builderp' ),
			'NOK' => __( 'Norwegian Krone (NOK)', 'aic_builderp' ),
			'SEK' => __( 'Swedish Krona (SEK)', 'aic_builderp' ),
			'PLN' => __( 'Polish Zloty (PLN)', 'aic_builderp' ),
			'CZK' => __( 'Czech Koruna (CZK)', 'aic_builderp' ),
			'HUF' => __( 'Hungarian Forint (HUF)', 'aic_builderp' ),
		);
	}

	/**
	 * Sanitize custom fields repeater for employees.
	 *
	 * @param  array $fields Raw fields.
	 * @return array
	 */
	private function sanitize_custom_fields( $fields ) {
		$clean = array();
		if ( ! is_array( $fields ) ) {
			return $clean;
		}

		// Support grouped inputs (label/key/type/options/required arrays).
		if ( isset( $fields['label'] ) || isset( $fields['key'] ) ) {
			$labels   = isset( $fields['label'] ) && is_array( $fields['label'] ) ? $fields['label'] : array();
			$keys     = isset( $fields['key'] ) && is_array( $fields['key'] ) ? $fields['key'] : array();
			$types    = isset( $fields['type'] ) && is_array( $fields['type'] ) ? $fields['type'] : array();
			$options  = isset( $fields['options'] ) && is_array( $fields['options'] ) ? $fields['options'] : array();
			$required = isset( $fields['required'] ) && is_array( $fields['required'] ) ? $fields['required'] : array();

			$max = max( count( $labels ), count( $keys ), count( $types ), count( $options ), count( $required ) );

			for ( $i = 0; $i < $max; $i++ ) {
				$field     = array(
					'label'    => $labels[ $i ] ?? '',
					'key'      => $keys[ $i ] ?? '',
					'type'     => $types[ $i ] ?? '',
					'options'  => $options[ $i ] ?? '',
					'required' => $required[ $i ] ?? '',
				);
				$processed = $this->sanitize_custom_fields( array( $field ) );
				if ( ! empty( $processed ) ) {
					$clean[] = $processed[0];
				}
			}

			return $clean;
		}

		// Array of field objects.
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$key         = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
			$label       = isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '';
			$type        = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			$required    = ! empty( $field['required'] ) ? 1 : 0;
			$options_raw = isset( $field['options'] ) ? $field['options'] : '';

			if ( '' === $key || '' === $label ) {
				continue;
			}

			$type = in_array( $type, array( 'text', 'number', 'date', 'select', 'textarea', 'checkbox' ), true ) ? $type : 'text';

			$options = array();
			if ( 'select' === $type && ! empty( $options_raw ) ) {
				$lines = is_array( $options_raw ) ? $options_raw : explode( "\n", (string) $options_raw );
				foreach ( $lines as $line ) {
					$trimmed = trim( $line );
					if ( '' !== $trimmed ) {
						$options[] = sanitize_text_field( $trimmed );
					}
				}
			}

			$clean[] = array(
				'key'      => $key,
				'label'    => $label,
				'type'     => $type,
				'required' => $required,
				'options'  => $options,
			);
		}

		return $clean;
	}
	/**
	 * Render General settings panel.
	 *
	 * @param array $settings   Current settings.
	 * @param array $currencies Currency list.
	 */
	private function render_general_settings( $settings, $currencies ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="general-company" class="is-active"><?php esc_html_e( 'Company', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="general-locale"><?php esc_html_e( 'Localization', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="general-company">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Company Name', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[general][company_name]" value="<?php echo esc_attr( $settings['general']['company_name'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Company Email', 'aic_builderp' ); ?></th>
							<td><input type="email" name="berp_settings[general][company_email]" value="<?php echo esc_attr( $settings['general']['company_email'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Company Phone', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[general][company_phone]" value="<?php echo esc_attr( $settings['general']['company_phone'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Company Address', 'aic_builderp' ); ?></th>
							<td>
								<textarea name="berp_settings[general][company_address]" rows="3" class="large-text"><?php echo esc_textarea( $settings['general']['company_address'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Company Logo URL', 'aic_builderp' ); ?></th>
							<td>
								<div class="berp-media-field">
									<input type="url" name="berp_settings[general][company_logo]" id="berp_company_logo" value="<?php echo esc_attr( $settings['general']['company_logo'] ); ?>" class="regular-text" />
									<button class="button berp-media-upload" data-target="#berp_company_logo" type="button"><?php esc_html_e( 'Select Logo', 'aic_builderp' ); ?></button>
								</div>
								<?php if ( ! empty( $settings['general']['company_logo'] ) ) : ?>
									<div class="berp-media-preview">
										<img src="<?php echo esc_url( $settings['general']['company_logo'] ); ?>" alt="<?php esc_attr_e( 'Logo preview', 'aic_builderp' ); ?>" />
									</div>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="general-locale">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Currency', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[general][currency]">
									<?php foreach ( $currencies as $code => $label ) : ?>
										<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['general']['currency'], $code ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Comprehensive currency list for global usage.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Date Format', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[general][date_format]">
									<option value="d/m/Y" <?php selected( $settings['general']['date_format'], 'd/m/Y' ); ?>><?php esc_html_e( 'DD/MM/YYYY', 'aic_builderp' ); ?></option>
									<option value="m/d/Y" <?php selected( $settings['general']['date_format'], 'm/d/Y' ); ?>><?php esc_html_e( 'MM/DD/YYYY', 'aic_builderp' ); ?></option>
									<option value="Y-m-d" <?php selected( $settings['general']['date_format'], 'Y-m-d' ); ?>><?php esc_html_e( 'YYYY-MM-DD', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Time Format', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[general][time_format]">
									<option value="12" <?php selected( $settings['general']['time_format'], '12' ); ?>><?php esc_html_e( '12-hour', 'aic_builderp' ); ?></option>
									<option value="24" <?php selected( $settings['general']['time_format'], '24' ); ?>><?php esc_html_e( '24-hour', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Fiscal Year Start Month', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[general][fiscal_year_start]">
									<?php
									$months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
									foreach ( $months as $month ) :
										?>
										<option value="<?php echo esc_attr( $month ); ?>" <?php selected( $settings['general']['fiscal_year_start'], $month ); ?>><?php echo esc_html( $month ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Uses WordPress timezone; adjust timezone in General Settings.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render Payroll settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_payroll_settings( $settings ) {
		$mode            = isset( $settings['payroll']['working_days_mode'] ) ? $settings['payroll']['working_days_mode'] : 'fixed';
		$formula_config  = function_exists( 'berp_get_salary_formula_config' ) ? berp_get_salary_formula_config() : array();
		$active_formula  = isset( $formula_config['active'] ) ? $formula_config['active'] : array();
		$current_formula = isset( $active_formula['formula'] ) ? $active_formula['formula'] : '';
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="payroll-schedule" class="is-active"><?php esc_html_e( 'Schedule', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="payroll-holidays"><?php esc_html_e( 'Holidays & Weekends', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="payroll-formula"><?php esc_html_e( 'Formula Builder', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="payroll-schedule">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Working Days Per Month', 'aic_builderp' ); ?></th>
							<td>
								<label>
									<input type="radio" name="berp_settings[payroll][working_days_mode]" value="fixed" <?php checked( $mode, 'fixed' ); ?> />
									<?php esc_html_e( 'Fixed', 'aic_builderp' ); ?>
								</label>
								<label style="margin-left:12px;">
									<input type="radio" name="berp_settings[payroll][working_days_mode]" value="actual" <?php checked( $mode, 'actual' ); ?> />
									<?php esc_html_e( 'Actual days in month', 'aic_builderp' ); ?>
								</label>
								<div class="berp-working-days-fixed" style="<?php echo 'actual' === $mode ? 'display:none;' : ''; ?>">
									<input type="number" min="1" max="31" name="berp_settings[payroll][working_days]" value="<?php echo esc_attr( $settings['payroll']['working_days'] ); ?>" />
									<p class="description"><?php esc_html_e( 'Use 30 for simplified mode or adjust 28-31 as needed.', 'aic_builderp' ); ?></p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Payroll Calculation Mode', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[payroll][calculation_mode]">
									<option value="auto" <?php selected( $settings['payroll']['calculation_mode'], 'auto' ); ?>><?php esc_html_e( 'Automatic', 'aic_builderp' ); ?></option>
									<option value="manual" <?php selected( $settings['payroll']['calculation_mode'], 'manual' ); ?>><?php esc_html_e( 'Manual', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Payroll Processing Day', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" max="31" name="berp_settings[payroll][processing_day]" value="<?php echo esc_attr( $settings['payroll']['processing_day'] ); ?>" /></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="payroll-holidays">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Weekend Days', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[payroll][weekend_days][]" value="friday" <?php checked( in_array( 'friday', $settings['payroll']['weekend_days'], true ) ); ?> /> <?php esc_html_e( 'Friday', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[payroll][weekend_days][]" value="saturday" <?php checked( in_array( 'saturday', $settings['payroll']['weekend_days'], true ) ); ?> /> <?php esc_html_e( 'Saturday', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[payroll][weekend_days][]" value="sunday" <?php checked( in_array( 'sunday', $settings['payroll']['weekend_days'], true ) ); ?> /> <?php esc_html_e( 'Sunday', 'aic_builderp' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Weekend Payment Rule', 'aic_builderp' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="berp_settings[payroll][weekend_payment_rule]" value="1" <?php checked( $settings['payroll']['weekend_payment_rule'], 1 ); ?> />
									<?php esc_html_e( 'Pay weekends if present on neighboring days', 'aic_builderp' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'General Holidays', 'aic_builderp' ); ?></th>
							<td>
								<textarea name="berp_settings[payroll][holidays]" rows="3" class="large-text"><?php echo esc_textarea( $settings['payroll']['holidays'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Comma-separated list of YYYY-MM-DD holiday dates.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="payroll-formula">
					<div class="berp-formula-summary">
						<p class="description"><?php esc_html_e( 'Manage formulas from the dedicated builder. Current active formula preview shown below.', 'aic_builderp' ); ?></p>
						<p><code><?php echo esc_html( $current_formula ); ?></code></p>
						<p>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=builderp-formula' ) ); ?>"><?php esc_html_e( 'Open Formula Builder', 'aic_builderp' ); ?></a>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render Attendance settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_attendance_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="attendance-rules" class="is-active"><?php esc_html_e( 'Rules', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="attendance-overtime"><?php esc_html_e( 'Overtime Rates', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="attendance-rules">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Editable Days Limit', 'aic_builderp' ); ?></th>
							<td><input type="number" min="0" name="berp_settings[attendance][editable_days_limit]" value="<?php echo esc_attr( $settings['attendance']['editable_days_limit'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Block Future Attendance', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[attendance][block_future]" value="1" <?php checked( $settings['attendance']['block_future'], 1 ); ?> /> <?php esc_html_e( 'Prevent logging future dates', 'aic_builderp' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Require Site Selection', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[attendance][require_site]" value="1" <?php checked( $settings['attendance']['require_site'], 1 ); ?> /> <?php esc_html_e( 'Site selection required when logging attendance', 'aic_builderp' ); ?></label>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="attendance-overtime">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Overtime Calculation Method', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[attendance][overtime_method]">
									<option value="flat" <?php selected( $settings['attendance']['overtime_method'], 'flat' ); ?>><?php esc_html_e( 'Flat rate', 'aic_builderp' ); ?></option>
									<option value="multiplier" <?php selected( $settings['attendance']['overtime_method'], 'multiplier' ); ?>><?php esc_html_e( 'Multiplier', 'aic_builderp' ); ?></option>
									<option value="custom" <?php selected( $settings['attendance']['overtime_method'], 'custom' ); ?>><?php esc_html_e( 'Custom rates', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Weekday Overtime Rate', 'aic_builderp' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="berp_settings[attendance][weekday_rate]" value="<?php echo esc_attr( $settings['attendance']['weekday_rate'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Weekend Overtime Rate', 'aic_builderp' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="berp_settings[attendance][weekend_rate]" value="<?php echo esc_attr( $settings['attendance']['weekend_rate'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Holiday Overtime Rate', 'aic_builderp' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="berp_settings[attendance][holiday_rate]" value="<?php echo esc_attr( $settings['attendance']['holiday_rate'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Overtime Multiplier', 'aic_builderp' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="berp_settings[attendance][default_multiplier]" value="<?php echo esc_attr( $settings['attendance']['default_multiplier'] ); ?>" /></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render Expense settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_expense_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="expense-defaults" class="is-active"><?php esc_html_e( 'Defaults', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="expense-workflow"><?php esc_html_e( 'Workflow', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="expense-defaults">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Categories', 'aic_builderp' ); ?></th>
							<td>
								<textarea name="berp_settings[expense][default_categories]" rows="3" class="large-text"><?php echo esc_textarea( $settings['expense']['default_categories'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One category per line.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Payment Methods', 'aic_builderp' ); ?></th>
							<td>
								<textarea name="berp_settings[expense][payment_methods]" rows="3" class="large-text"><?php echo esc_textarea( $settings['expense']['payment_methods'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'One method per line.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="expense-workflow">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Recurring Expense Auto-Creation', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[expense][enable_recurring]" value="1" <?php checked( $settings['expense']['enable_recurring'], 1 ); ?> /> <?php esc_html_e( 'Enable automatic creation of recurring expenses', 'aic_builderp' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Expense Approval Workflow', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[expense][approval_workflow]" value="1" <?php checked( $settings['expense']['approval_workflow'], 1 ); ?> /> <?php esc_html_e( 'Require approval for expenses', 'aic_builderp' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Require Receipt', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[expense][require_receipt]" value="1" <?php checked( $settings['expense']['require_receipt'], 1 ); ?> /> <?php esc_html_e( 'Make receipt attachment mandatory', 'aic_builderp' ); ?></label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render Quotation & Invoice settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_quotation_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="quotation-numbering" class="is-active"><?php esc_html_e( 'Numbering & Terms', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="quotation-pdf"><?php esc_html_e( 'PDF & Emails', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="quotation-numbering">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Quotation Number Prefix', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[quotation][quotation_prefix]" value="<?php echo esc_attr( $settings['quotation']['quotation_prefix'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Invoice Number Prefix', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[quotation][invoice_prefix]" value="<?php echo esc_attr( $settings['quotation']['invoice_prefix'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Number Format', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[quotation][number_format]">
									<option value="sequential" <?php selected( $settings['quotation']['number_format'], 'sequential' ); ?>><?php esc_html_e( 'Sequential', 'aic_builderp' ); ?></option>
									<option value="year" <?php selected( $settings['quotation']['number_format'], 'year' ); ?>><?php esc_html_e( 'Year-based', 'aic_builderp' ); ?></option>
									<option value="custom" <?php selected( $settings['quotation']['number_format'], 'custom' ); ?>><?php esc_html_e( 'Custom', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Starting Number', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" name="berp_settings[quotation][starting_number]" value="<?php echo esc_attr( $settings['quotation']['starting_number'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Tax Rate (%)', 'aic_builderp' ); ?></th>
							<td><input type="number" step="0.1" min="0" name="berp_settings[quotation][tax_rate]" value="<?php echo esc_attr( $settings['quotation']['tax_rate'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Payment Terms', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[quotation][payment_terms]" value="<?php echo esc_attr( $settings['quotation']['payment_terms'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Quotation Validity (days)', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" name="berp_settings[quotation][quotation_validity]" value="<?php echo esc_attr( $settings['quotation']['quotation_validity'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Invoice Due (days)', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" name="berp_settings[quotation][invoice_due_days]" value="<?php echo esc_attr( $settings['quotation']['invoice_due_days'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Terms & Conditions', 'aic_builderp' ); ?></th>
							<td><textarea name="berp_settings[quotation][terms]" rows="3" class="large-text"><?php echo esc_textarea( isset( $settings['quotation']['terms'] ) ? $settings['quotation']['terms'] : '' ); ?></textarea></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="quotation-pdf">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'PDF Theme Color', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[quotation][theme_color]" value="<?php echo esc_attr( $settings['quotation']['theme_color'] ); ?>" class="regular-text berp-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Header/Footer Text', 'aic_builderp' ); ?></th>
							<td><textarea name="berp_settings[quotation][header_text]" rows="3" class="large-text"><?php echo esc_textarea( $settings['quotation']['header_text'] ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'PDF Terms Block', 'aic_builderp' ); ?></th>
							<td><textarea name="berp_settings[quotation][pdf_terms]" rows="3" class="large-text"><?php echo esc_textarea( isset( $settings['quotation']['pdf_terms'] ) ? $settings['quotation']['pdf_terms'] : '' ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Notifications', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[quotation][enable_quote_email]" value="1" <?php checked( $settings['quotation']['enable_quote_email'], 1 ); ?> /> <?php esc_html_e( 'Send quotation emails', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[quotation][enable_invoice_email]" value="1" <?php checked( $settings['quotation']['enable_invoice_email'], 1 ); ?> /> <?php esc_html_e( 'Send invoice emails', 'aic_builderp' ); ?></label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Render Site settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_site_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="site-defaults" class="is-active"><?php esc_html_e( 'Defaults', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="site-budget"><?php esc_html_e( 'Budget & Workflow', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="site-defaults">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Project Status', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[site][default_status]" value="<?php echo esc_attr( $settings['site']['default_status'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Site Manager Required', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[site][require_manager]" value="1" <?php checked( $settings['site']['require_manager'], 1 ); ?> /> <?php esc_html_e( 'Require site manager assignment', 'aic_builderp' ); ?></label></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="site-budget">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Budget Alert Threshold (%)', 'aic_builderp' ); ?></th>
							<td><input type="number" step="1" min="0" name="berp_settings[site][budget_alert]" value="<?php echo esc_attr( $settings['site']['budget_alert'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Budget Tracking', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[site][enable_budget_tracking]" value="1" <?php checked( $settings['site']['enable_budget_tracking'], 1 ); ?> /> <?php esc_html_e( 'Track budget and spend', 'aic_builderp' ); ?></label></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Employee settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_employee_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="employee-ids" class="is-active"><?php esc_html_e( 'IDs & Status', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="employee-advances"><?php esc_html_e( 'Advances', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="employee-custom"><?php esc_html_e( 'Custom Fields', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="employee-ids">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Employee ID Format', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[employee][id_format]">
									<option value="auto" <?php selected( $settings['employee']['id_format'], 'auto' ); ?>><?php esc_html_e( 'Auto-generated', 'aic_builderp' ); ?></option>
									<option value="manual" <?php selected( $settings['employee']['id_format'], 'manual' ); ?>><?php esc_html_e( 'Manual', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Starting Employee ID', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" name="berp_settings[employee][starting_id]" value="<?php echo esc_attr( $settings['employee']['starting_id'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Employee Status', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[employee][default_status]" value="<?php echo esc_attr( $settings['employee']['default_status'] ); ?>" class="regular-text" /></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="employee-advances">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Advance Requires Approval', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[employee][advance_requires_approval]" value="1" <?php checked( $settings['employee']['advance_requires_approval'], 1 ); ?> /> <?php esc_html_e( 'Enable approval workflow for advances', 'aic_builderp' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Max Advance Percentage', 'aic_builderp' ); ?></th>
							<td><input type="number" step="1" min="0" name="berp_settings[employee][advance_max_percent]" value="<?php echo esc_attr( $settings['employee']['advance_max_percent'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Allow Installments', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[employee][allow_installments]" value="1" <?php checked( $settings['employee']['allow_installments'], 1 ); ?> /> <?php esc_html_e( 'Allow repayment in installments', 'aic_builderp' ); ?></label></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="employee-custom">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Custom Fields', 'aic_builderp' ); ?></th>
							<td>
								<div class="berp-repeater" data-repeater="employee-custom-fields">
									<?php
									if ( ! empty( $settings['employee']['custom_fields'] ) ) {
										foreach ( $settings['employee']['custom_fields'] as $field ) {
											$this->render_employee_custom_field_row( $field );
										}
									}
									$this->render_employee_custom_field_row( array(), true );
									?>
									<button type="button" class="button button-secondary berp-repeater-add"><?php esc_html_e( 'Add Field', 'aic_builderp' ); ?></button>
								</div>
								<p class="description"><?php esc_html_e( 'Define additional employee fields. Keys should be unique. Options are only used for select fields (one per line).', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single custom field row in settings.
	 *
	 * @param array $field       Field config.
	 * @param bool  $is_template Template marker.
	 */
	private function render_employee_custom_field_row( $field = array(), $is_template = false ) {
		$key      = isset( $field['key'] ) ? $field['key'] : '';
		$label    = isset( $field['label'] ) ? $field['label'] : '';
		$type     = isset( $field['type'] ) ? $field['type'] : 'text';
		$required = ! empty( $field['required'] );
		$options  = isset( $field['options'] ) && is_array( $field['options'] ) ? implode( "\n", $field['options'] ) : '';
		$class    = $is_template ? 'berp-repeater-item berp-repeater-template berp-hidden' : 'berp-repeater-item';
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<span class="berp-repeater-remove dashicons dashicons-no-alt" aria-label="<?php esc_attr_e( 'Remove field', 'aic_builderp' ); ?>"></span>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Label', 'aic_builderp' ); ?></label>
				<input type="text" name="berp_settings[employee][custom_fields][label][]" value="<?php echo esc_attr( $label ); ?>" />
			</div>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Key', 'aic_builderp' ); ?></label>
				<input type="text" name="berp_settings[employee][custom_fields][key][]" value="<?php echo esc_attr( $key ); ?>" />
				<p class="description"><?php esc_html_e( 'Lowercase, no spaces. Used for meta key.', 'aic_builderp' ); ?></p>
			</div>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Field Type', 'aic_builderp' ); ?></label>
				<select name="berp_settings[employee][custom_fields][type][]">
		<?php
		$types = array(
			'text'     => __( 'Text', 'aic_builderp' ),
			'number'   => __( 'Number', 'aic_builderp' ),
			'date'     => __( 'Date', 'aic_builderp' ),
			'textarea' => __( 'Textarea', 'aic_builderp' ),
			'select'   => __( 'Select', 'aic_builderp' ),
			'checkbox' => __( 'Checkbox', 'aic_builderp' ),
		);
		foreach ( $types as $type_key => $type_label ) :
			?>
						<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
		<?php endforeach; ?>
				</select>
			</div>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Options (for select)', 'aic_builderp' ); ?></label>
				<textarea rows="3" name="berp_settings[employee][custom_fields][options][]" placeholder="<?php esc_attr_e( 'One option per line', 'aic_builderp' ); ?>"><?php echo esc_textarea( $options ); ?></textarea>
			</div>
			<div class="berp-field-group">
				<label><input type="checkbox" name="berp_settings[employee][custom_fields][required][]" value="1" <?php checked( $required, true ); ?> /> <?php esc_html_e( 'Required', 'aic_builderp' ); ?></label>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Notification settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_notification_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="notification-toggles" class="is-active"><?php esc_html_e( 'Toggles', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="notification-smtp"><?php esc_html_e( 'SMTP', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="notification-templates"><?php esc_html_e( 'Templates', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="notification-toggles">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Notification Toggles', 'aic_builderp' ); ?></th>
							<td>
								<label><input type="checkbox" name="berp_settings[notification][employee_notifications]" value="1" <?php checked( $settings['notification']['employee_notifications'], 1 ); ?> /> <?php esc_html_e( 'Employee notifications', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[notification][admin_notifications]" value="1" <?php checked( $settings['notification']['admin_notifications'], 1 ); ?> /> <?php esc_html_e( 'Admin notifications', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[notification][client_notifications]" value="1" <?php checked( $settings['notification']['client_notifications'], 1 ); ?> /> <?php esc_html_e( 'Client notifications', 'aic_builderp' ); ?></label><br />
								<label><input type="checkbox" name="berp_settings[notification][timekeeper_notifications]" value="1" <?php checked( $settings['notification']['timekeeper_notifications'], 1 ); ?> /> <?php esc_html_e( 'Timekeeper notifications', 'aic_builderp' ); ?></label>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="notification-smtp">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Host', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[notification][smtp_host]" value="<?php echo esc_attr( $settings['notification']['smtp_host'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Port', 'aic_builderp' ); ?></th>
							<td><input type="number" min="0" name="berp_settings[notification][smtp_port]" value="<?php echo esc_attr( $settings['notification']['smtp_port'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Encryption', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[notification][smtp_encryption]" value="<?php echo esc_attr( $settings['notification']['smtp_encryption'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'ssl/tls', 'aic_builderp' ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Username', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[notification][smtp_user]" value="<?php echo esc_attr( $settings['notification']['smtp_user'] ); ?>" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'SMTP Password', 'aic_builderp' ); ?></th>
							<td><input type="password" name="berp_settings[notification][smtp_pass]" value="<?php echo esc_attr( $settings['notification']['smtp_pass'] ); ?>" class="regular-text" autocomplete="new-password" /></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="notification-templates">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Template (global)', 'aic_builderp' ); ?></th>
							<td>
								<input type="text" name="berp_settings[notification][email_subject]" value="<?php echo esc_attr( isset( $settings['notification']['email_subject'] ) ? $settings['notification']['email_subject'] : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Default subject', 'aic_builderp' ); ?>" />
								<textarea name="berp_settings[notification][email_template]" rows="4" class="large-text"><?php echo esc_textarea( isset( $settings['notification']['email_template'] ) ? $settings['notification']['email_template'] : '' ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Use placeholders like {name}, {link}. Specific templates can override in future phases.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Portal settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_portal_settings( $settings ) {
		// Get all published pages for dropdown.
		$pages = get_pages( array( 'post_status' => 'publish' ) );
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="portal-pages" class="is-active"><?php esc_html_e( 'Portal Pages', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="portal-access"><?php esc_html_e( 'Access', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="portal-accounts"><?php esc_html_e( 'Accounts', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="portal-emails"><?php esc_html_e( 'Emails', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="portal-pages">
					<div class="berp-notice berp-notice-info">
						<p><strong><?php esc_html_e( 'Portal Pages Setup:', 'aic_builderp' ); ?></strong></p>
						<ol>
							<li><?php esc_html_e( 'Create two pages in WordPress (e.g., "Employee Login" and "Employee Dashboard").', 'aic_builderp' ); ?></li>
							<li><?php esc_html_e( 'Select those pages below.', 'aic_builderp' ); ?></li>
							<li><?php esc_html_e( 'Enable "Use Standalone Template" to render without theme header/footer.', 'aic_builderp' ); ?></li>
						</ol>
					</div>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Login Page', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][login_page_id]">
									<option value="0"><?php esc_html_e( '— Select a Page —', 'aic_builderp' ); ?></option>
									<?php foreach ( $pages as $page ) : ?>
										<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['portal']['login_page_id'] ?? 0, $page->ID ); ?>>
											<?php echo esc_html( $page->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select the page to use as the employee login page. Page content will be replaced by the login form.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Dashboard Page', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][dashboard_page_id]">
									<option value="0"><?php esc_html_e( '— Select a Page —', 'aic_builderp' ); ?></option>
									<?php foreach ( $pages as $page ) : ?>
										<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['portal']['dashboard_page_id'] ?? 0, $page->ID ); ?>>
											<?php echo esc_html( $page->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select the page to use as the employee dashboard. Page content will be replaced by the dashboard interface.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Use Standalone Template', 'aic_builderp' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="berp_settings[portal][use_standalone_template]" value="1" <?php checked( $settings['portal']['use_standalone_template'] ?? 1, 1 ); ?> />
									<?php esc_html_e( 'Render portal pages without theme header/footer (recommended)', 'aic_builderp' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'When enabled, portal pages will use a clean standalone template independent of your theme.', 'aic_builderp' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="portal-access">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Portal Slug', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[portal][portal_slug]" value="<?php echo esc_attr( $settings['portal']['portal_slug'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Portal', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[portal][enable_portal]" value="1" <?php checked( $settings['portal']['enable_portal'], 1 ); ?> /> <?php esc_html_e( 'Allow portal access', 'aic_builderp' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Allow Profile Editing', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[portal][allow_profile_edit]" value="1" <?php checked( $settings['portal']['allow_profile_edit'], 1 ); ?> /> <?php esc_html_e( 'Employees can edit their profile', 'aic_builderp' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Portal Branding Color', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[portal][branding_color]" value="<?php echo esc_attr( $settings['portal']['branding_color'] ); ?>" class="regular-text berp-color-picker" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Session Timeout (minutes)', 'aic_builderp' ); ?></th>
							<td><input type="number" min="1" name="berp_settings[portal][session_timeout]" value="<?php echo esc_attr( $settings['portal']['session_timeout'] ); ?>" /></td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="portal-accounts">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Username Format', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][username_format]">
									<option value="email" <?php selected( $settings['portal']['username_format'], 'email' ); ?>><?php esc_html_e( 'Email', 'aic_builderp' ); ?></option>
									<option value="employee_id" <?php selected( $settings['portal']['username_format'], 'employee_id' ); ?>><?php esc_html_e( 'Employee ID', 'aic_builderp' ); ?></option>
									<option value="custom" <?php selected( $settings['portal']['username_format'], 'custom' ); ?>><?php esc_html_e( 'Custom pattern', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Password Strength', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][password_strength]">
									<option value="strong" <?php selected( $settings['portal']['password_strength'], 'strong' ); ?>><?php esc_html_e( 'Strong', 'aic_builderp' ); ?></option>
									<option value="medium" <?php selected( $settings['portal']['password_strength'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'aic_builderp' ); ?></option>
									<option value="none" <?php selected( $settings['portal']['password_strength'], 'none' ); ?>><?php esc_html_e( 'No requirement', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'When Portal Access Disabled', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][portal_access_behavior]">
									<option value="keep_user" <?php selected( $settings['portal']['portal_access_behavior'], 'keep_user' ); ?>><?php esc_html_e( 'Keep user account', 'aic_builderp' ); ?></option>
									<option value="delete_user" <?php selected( $settings['portal']['portal_access_behavior'], 'delete_user' ); ?>><?php esc_html_e( 'Delete user account', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'When Employee Deleted', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[portal][employee_delete_behavior]">
									<option value="keep_user" <?php selected( $settings['portal']['employee_delete_behavior'], 'keep_user' ); ?>><?php esc_html_e( 'Keep user account', 'aic_builderp' ); ?></option>
									<option value="delete_user" <?php selected( $settings['portal']['employee_delete_behavior'], 'delete_user' ); ?>><?php esc_html_e( 'Delete user account', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="portal-emails">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Send Welcome Email', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[portal][send_welcome_email]" value="1" <?php checked( $settings['portal']['send_welcome_email'], 1 ); ?> /> <?php esc_html_e( 'Send email when enabling portal access', 'aic_builderp' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Welcome Email Subject', 'aic_builderp' ); ?></th>
							<td><input type="text" name="berp_settings[portal][welcome_email_subject]" value="<?php echo esc_attr( $settings['portal']['welcome_email_subject'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Welcome Email Template', 'aic_builderp' ); ?></th>
							<td><textarea name="berp_settings[portal][welcome_email_template]" rows="4" class="large-text"><?php echo esc_textarea( $settings['portal']['welcome_email_template'] ); ?></textarea></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Advance settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_advance_settings( $settings ) {
		$defaults = $this->get_defaults();
		$advance_settings = isset( $settings['advance'] ) ? $settings['advance'] : $defaults['advance'];
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Repayment Type', 'aic_builderp' ); ?></th>
				<td>
					<select name="berp_settings[advance][default_repayment_type]">
						<option value="installments" <?php selected( $advance_settings['default_repayment_type'], 'installments' ); ?>><?php esc_html_e( 'Installments', 'aic_builderp' ); ?></option>
						<option value="full" <?php selected( $advance_settings['default_repayment_type'], 'full' ); ?>><?php esc_html_e( 'Full Deduction (Next Payroll)', 'aic_builderp' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Default repayment method for salary advances.', 'aic_builderp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Installment Threshold Amount', 'aic_builderp' ); ?></th>
				<td>
					<input type="number" step="0.01" min="0" name="berp_settings[advance][installment_threshold]" value="<?php echo esc_attr( $advance_settings['installment_threshold'] ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Amounts greater than this will use the "High Amount" installment count.', 'aic_builderp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Installments (High Amount)', 'aic_builderp' ); ?></th>
				<td>
					<input type="number" min="1" name="berp_settings[advance][installments_high]" value="<?php echo esc_attr( $advance_settings['installments_high'] ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Number of installments for amounts above the threshold.', 'aic_builderp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Installments (Low Amount)', 'aic_builderp' ); ?></th>
				<td>
					<input type="number" min="1" name="berp_settings[advance][installments_low]" value="<?php echo esc_attr( $advance_settings['installments_low'] ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Number of installments for amounts equal to or below the threshold.', 'aic_builderp' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Advanced settings panel.
	 *
	 * @param array $settings Current settings.
	 */
	private function render_advanced_settings( $settings ) {
		?>
		<div class="berp-subtabs">
			<div class="berp-subtab-nav">
				<a href="#" data-subtab-target="advanced-data" class="is-active"><?php esc_html_e( 'Data & Security', 'aic_builderp' ); ?></a>
				<a href="#" data-subtab-target="advanced-performance"><?php esc_html_e( 'Performance', 'aic_builderp' ); ?></a>
			</div>
			<div class="berp-subtab-panels">
				<div class="berp-subtab-panel is-active" data-subtab-panel="advanced-data">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Activity Logging', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[advanced][activity_logging]" value="1" <?php checked( $settings['advanced']['activity_logging'], 1 ); ?> /> <?php esc_html_e( 'Enable admin activity logging', 'aic_builderp' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Data Retention (months)', 'aic_builderp' ); ?></th>
							<td><input type="number" min="0" name="berp_settings[advanced][data_retention]" value="<?php echo esc_attr( $settings['advanced']['data_retention'] ); ?>" /> <span class="description"><?php esc_html_e( '0 means keep indefinitely.', 'aic_builderp' ); ?></span></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Uninstall Behavior', 'aic_builderp' ); ?></th>
							<td>
								<select name="berp_settings[advanced][uninstall_behavior]">
									<option value="keep" <?php selected( $settings['advanced']['uninstall_behavior'], 'keep' ); ?>><?php esc_html_e( 'Keep data', 'aic_builderp' ); ?></option>
									<option value="delete" <?php selected( $settings['advanced']['uninstall_behavior'], 'delete' ); ?>><?php esc_html_e( 'Delete data', 'aic_builderp' ); ?></option>
									<option value="prompt" <?php selected( $settings['advanced']['uninstall_behavior'], 'prompt' ); ?>><?php esc_html_e( 'Ask on uninstall', 'aic_builderp' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div class="berp-subtab-panel" data-subtab-panel="advanced-performance">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Report Caching', 'aic_builderp' ); ?></th>
							<td><label><input type="checkbox" name="berp_settings[advanced][report_caching]" value="1" <?php checked( $settings['advanced']['report_caching'], 1 ); ?> /> <?php esc_html_e( 'Enable caching for reports', 'aic_builderp' ); ?></label></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
