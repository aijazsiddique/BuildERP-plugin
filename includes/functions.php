<?php
/**
 * Helper Functions
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure required options exist (self-heal if removed)
 *
 * @since 1.0.0
 */
function berp_ensure_default_options() {
	$defaults = array(
		'berp_general_settings'      => array(
			'company_name'      => '',
			'company_email'     => get_option( 'admin_email' ),
			'company_phone'     => '',
			'company_address'   => '',
			'currency'          => 'USD',
			'currency_symbol'   => '$',
			'currency_position' => 'before',
			'date_format'       => 'Y-m-d',
			'time_format'       => 'H:i',
		),
		'berp_payroll_settings'      => array(
			'working_days_per_month'   => 26,
			'overtime_rate_multiplier' => 1.5,
			'weekend_payment_enabled'  => false,
			'weekend_rate_multiplier'  => 2.0,
			'auto_calculate_payroll'   => true,
		),
		'berp_attendance_settings'   => array(
			'allow_edit_days'        => 7,
			'remember_last_site'     => true,
			'default_overtime_hours' => 0,
			'weekend_days'           => array( 'saturday', 'sunday' ),
		),
		'berp_expense_settings'      => array(
			'default_payment_method' => 'cash',
			'require_receipt'        => false,
		),
		'berp_portal_settings'       => array(
			'enable_portal'         => true,
			'portal_page_id'        => 0,
			'username_format'       => 'employee_id',
			'auto_send_credentials' => true,
		),
		'berp_notification_settings' => array(
			'enable_email_notifications'   => true,
			'notify_payroll_processed'     => true,
			'notify_portal_access_enabled' => true,
		),
		'berp_salary_formula'        => berp_get_default_salary_formula(),
	);

	foreach ( $defaults as $option_name => $default_value ) {
		if ( get_option( $option_name, null ) === null ) {
			add_option( $option_name, $default_value );
		}
	}

	if ( get_option( 'berp_version', null ) === null ) {
		add_option( 'berp_version', BERP_VERSION );
	}
}
add_action( 'init', 'berp_ensure_default_options', 1 );

/**
 * Ensure administrator retains critical capabilities (self-heal for existing installs).
 *
 * @since 1.0.0
 * @return void
 */
function berp_ensure_admin_capabilities() {
	$admin = get_role( 'administrator' );
	if ( ! $admin ) {
		return;
	}

	$caps = array(
		'berp_manage_payroll',
		'berp_process_payroll',
		'berp_view_payroll',
		'berp_edit_payroll',
		'berp_delete_payroll',
		'berp_manage_settings',
	);

	foreach ( $caps as $cap ) {
		if ( ! $admin->has_cap( $cap ) ) {
			$admin->add_cap( $cap );
		}
	}
}
add_action( 'admin_init', 'berp_ensure_admin_capabilities', 1 );

/**
 * Get plugin settings
 *
 * @param  string $option_name   Option name.
 * @param  mixed  $default_value Default value if option not found.
 * @return mixed
 */
function berp_get_option( $option_name, $default_value = array() ) {
	return get_option( $option_name, $default_value );
}

/**
 * Get general settings from the unified settings
 *
 * @return array
 */
function berp_get_general_settings() {
	$settings = get_option( 'berp_settings', array() );
	$general  = isset( $settings['general'] ) ? $settings['general'] : array();

	return array(
		'company_name'      => isset( $general['company_name'] ) ? $general['company_name'] : '',
		'company_email'     => isset( $general['company_email'] ) ? $general['company_email'] : '',
		'company_phone'     => isset( $general['company_phone'] ) ? $general['company_phone'] : '',
		'company_address'   => isset( $general['company_address'] ) ? $general['company_address'] : '',
		'company_logo'      => isset( $general['company_logo'] ) ? $general['company_logo'] : '',
		'currency'          => isset( $general['currency'] ) ? $general['currency'] : 'AED',
		'date_format'       => isset( $general['date_format'] ) ? $general['date_format'] : 'Y-m-d',
		'time_format'       => isset( $general['time_format'] ) ? $general['time_format'] : 'H:i',
		'fiscal_year_start' => isset( $general['fiscal_year_start'] ) ? $general['fiscal_year_start'] : '1',
	);
}

/**
 * Get payroll settings from the unified settings
 *
 * @return array
 */
function berp_get_payroll_settings() {
	$settings = get_option( 'berp_settings', array() );
	$payroll  = isset( $settings['payroll'] ) ? $settings['payroll'] : array();

	return array(
		'working_days_mode'      => isset( $payroll['working_days_mode'] ) ? $payroll['working_days_mode'] : 'fixed',
		'working_days'           => isset( $payroll['working_days'] ) ? $payroll['working_days'] : 26,
		'working_days_per_month' => isset( $payroll['working_days'] ) ? $payroll['working_days'] : 26,
		'weekend_days'           => isset( $payroll['weekend_days'] ) ? $payroll['weekend_days'] : array( 'friday', 'saturday' ),
		'weekend_payment_rule'   => isset( $payroll['weekend_payment_rule'] ) ? $payroll['weekend_payment_rule'] : 0,
		'holidays'               => isset( $payroll['holidays'] ) ? $payroll['holidays'] : '',
		'calculation_mode'       => isset( $payroll['calculation_mode'] ) ? $payroll['calculation_mode'] : 'auto',
		'processing_day'         => isset( $payroll['processing_day'] ) ? $payroll['processing_day'] : 25,
	);
}

/**
 * Get attendance settings from the unified settings
 *
 * @return array
 */
function berp_get_attendance_settings() {
	$settings   = get_option( 'berp_settings', array() );
	$attendance = isset( $settings['attendance'] ) ? $settings['attendance'] : array();

	return array(
		'editable_days_limit' => isset( $attendance['editable_days_limit'] ) ? $attendance['editable_days_limit'] : 3,
		'block_future'        => isset( $attendance['block_future'] ) ? $attendance['block_future'] : 1,
		'require_site'        => isset( $attendance['require_site'] ) ? $attendance['require_site'] : 0,
		'overtime_method'     => isset( $attendance['overtime_method'] ) ? $attendance['overtime_method'] : 'flat',
		'weekday_rate'        => isset( $attendance['weekday_rate'] ) ? $attendance['weekday_rate'] : 1.25,
		'weekend_rate'        => isset( $attendance['weekend_rate'] ) ? $attendance['weekend_rate'] : 1.5,
		'holiday_rate'        => isset( $attendance['holiday_rate'] ) ? $attendance['holiday_rate'] : 2.0,
		'default_multiplier'  => isset( $attendance['default_multiplier'] ) ? $attendance['default_multiplier'] : 1.5,
		'weekend_days'        => isset( $settings['payroll']['weekend_days'] ) ? $settings['payroll']['weekend_days'] : array( 'friday', 'saturday' ),
	);
}

/**
 * Get quotation settings from the unified settings
 *
 * Maps the unified berp_settings['quotation'] to the expected keys.
 *
 * @since 1.0.0
 * @return array Quotation settings with mapped keys.
 */
function berp_get_quotation_settings() {
	$all_settings = get_option( 'berp_settings', array() );
	$quotation    = isset( $all_settings['quotation'] ) ? $all_settings['quotation'] : array();

	// Map settings keys to expected keys used by metaboxes and functions.
	return array(
		'quotation_prefix'      => isset( $quotation['quotation_prefix'] ) ? $quotation['quotation_prefix'] : 'QUO',
		'invoice_prefix'        => isset( $quotation['invoice_prefix'] ) ? $quotation['invoice_prefix'] : 'INV',
		'number_format'         => isset( $quotation['number_format'] ) ? $quotation['number_format'] : 'sequential',
		'starting_number'       => isset( $quotation['starting_number'] ) ? $quotation['starting_number'] : 1,
		'default_tax_rate'      => isset( $quotation['tax_rate'] ) ? $quotation['tax_rate'] : 5,
		'tax_rate'              => isset( $quotation['tax_rate'] ) ? $quotation['tax_rate'] : 5,
		'payment_terms'         => isset( $quotation['payment_terms'] ) ? $quotation['payment_terms'] : '30 days',
		'default_validity_days' => isset( $quotation['quotation_validity'] ) ? $quotation['quotation_validity'] : 30,
		'quotation_validity'    => isset( $quotation['quotation_validity'] ) ? $quotation['quotation_validity'] : 30,
		'invoice_due_days'      => isset( $quotation['invoice_due_days'] ) ? $quotation['invoice_due_days'] : 14,
		'theme_color'           => isset( $quotation['theme_color'] ) ? $quotation['theme_color'] : '#2271b1',
		'header_text'           => isset( $quotation['header_text'] ) ? $quotation['header_text'] : '',
		'terms_conditions'      => isset( $quotation['terms'] ) ? $quotation['terms'] : '',
		'terms'                 => isset( $quotation['terms'] ) ? $quotation['terms'] : '',
		'pdf_terms'             => isset( $quotation['pdf_terms'] ) ? $quotation['pdf_terms'] : '',
		'enable_quote_email'    => isset( $quotation['enable_quote_email'] ) ? $quotation['enable_quote_email'] : 1,
		'enable_invoice_email'  => isset( $quotation['enable_invoice_email'] ) ? $quotation['enable_invoice_email'] : 1,
	);
}

/**
 * Get site settings from the unified settings
 *
 * @since 1.0.0
 * @return array
 */
function berp_get_site_settings() {
	$settings = get_option( 'berp_settings', array() );
	$site     = isset( $settings['site'] ) ? $settings['site'] : array();

	return array(
		'default_status'         => isset( $site['default_status'] ) ? $site['default_status'] : 'planning',
		'budget_alert'           => isset( $site['budget_alert'] ) ? $site['budget_alert'] : 80,
		'budget_alert_threshold' => isset( $site['budget_alert'] ) ? $site['budget_alert'] : 80, // Alias
		'require_manager'        => isset( $site['require_manager'] ) ? $site['require_manager'] : 0,
		'enable_budget_tracking' => isset( $site['enable_budget_tracking'] ) ? $site['enable_budget_tracking'] : 1,
		'show_budget_alerts'     => isset( $site['enable_budget_tracking'] ) ? $site['enable_budget_tracking'] : 1, // Show alerts if tracking enabled
	);
}

/**
 * Get employee settings from the unified settings
 *
 * @since 1.0.0
 * @return array
 */
function berp_get_employee_settings() {
	$settings = get_option( 'berp_settings', array() );
	$employee = isset( $settings['employee'] ) ? $settings['employee'] : array();

	return array(
		'id_format'                 => isset( $employee['id_format'] ) ? $employee['id_format'] : 'auto',
		'starting_id'               => isset( $employee['starting_id'] ) ? $employee['starting_id'] : 1,
		'default_status'            => isset( $employee['default_status'] ) ? $employee['default_status'] : 'active',
		'advance_requires_approval' => isset( $employee['advance_requires_approval'] ) ? $employee['advance_requires_approval'] : 1,
		'advance_max_percent'       => isset( $employee['advance_max_percent'] ) ? $employee['advance_max_percent'] : 50,
		'allow_installments'        => isset( $employee['allow_installments'] ) ? $employee['allow_installments'] : 1,
		'custom_fields'             => isset( $employee['custom_fields'] ) ? $employee['custom_fields'] : array(),
	);
}

/**
 * Get portal settings from the unified settings
 *
 * @since 1.0.0
 * @return array
 */
function berp_get_portal_settings() {
	$settings = get_option( 'berp_settings', array() );
	$portal   = isset( $settings['portal'] ) ? $settings['portal'] : array();

	return array(
		'portal_slug'              => isset( $portal['portal_slug'] ) ? $portal['portal_slug'] : 'berp-portal',
		'enable_portal'            => isset( $portal['enable_portal'] ) ? $portal['enable_portal'] : 1,
		'login_page_id'            => isset( $portal['login_page_id'] ) ? $portal['login_page_id'] : 0,
		'dashboard_page_id'        => isset( $portal['dashboard_page_id'] ) ? $portal['dashboard_page_id'] : 0,
		'use_standalone_template'  => isset( $portal['use_standalone_template'] ) ? $portal['use_standalone_template'] : 1,
		'allow_profile_edit'       => isset( $portal['allow_profile_edit'] ) ? $portal['allow_profile_edit'] : 1,
		'branding_color'           => isset( $portal['branding_color'] ) ? $portal['branding_color'] : '#2271b1',
		'session_timeout'          => isset( $portal['session_timeout'] ) ? $portal['session_timeout'] : 30,
		'username_format'          => isset( $portal['username_format'] ) ? $portal['username_format'] : 'email',
		'password_strength'        => isset( $portal['password_strength'] ) ? $portal['password_strength'] : 'strong',
		'portal_access_behavior'   => isset( $portal['portal_access_behavior'] ) ? $portal['portal_access_behavior'] : 'keep_user',
		'employee_delete_behavior' => isset( $portal['employee_delete_behavior'] ) ? $portal['employee_delete_behavior'] : 'keep_user',
		'send_welcome_email'       => isset( $portal['send_welcome_email'] ) ? $portal['send_welcome_email'] : 1,
		'welcome_email_subject'    => isset( $portal['welcome_email_subject'] ) ? $portal['welcome_email_subject'] : '',
		'welcome_email_template'   => isset( $portal['welcome_email_template'] ) ? $portal['welcome_email_template'] : '',
	);
}

/**
 * Default salary formula config.
 *
 * @since 1.0.0
 * @return array
 */
function berp_get_default_salary_formula() {
	$base_variables = array(
		array(
			'key'         => 'basic_salary',
			'label'       => __( 'Basic Salary', 'BuildERP' ),
			'type'        => 'currency',
			'default'     => '0',
			'sample'      => '1200',
			'description' => __( 'Base monthly salary.', 'BuildERP' ),
		),
		array(
			'key'         => 'working_days',
			'label'       => __( 'Working Days (month)', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '26',
			'sample'      => '26',
			'description' => __( 'Standard working days for the period.', 'BuildERP' ),
		),
		array(
			'key'         => 'days_in_month',
			'label'       => __( 'Days in Month', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '30',
			'sample'      => '30',
			'description' => __( 'Total calendar days in the month.', 'BuildERP' ),
		),
		array(
			'key'         => 'present_days',
			'label'       => __( 'Present Days', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '26',
			'sample'      => '25',
			'description' => __( 'Days employee was present (attendance marked).', 'BuildERP' ),
		),
		array(
			'key'         => 'weekends',
			'label'       => __( 'Weekend Days', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '0',
			'sample'      => '4',
			'description' => __( 'Weekend days in the period (may be paid based on attendance rules).', 'BuildERP' ),
		),
		array(
			'key'         => 'holidays',
			'label'       => __( 'Holidays', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '0',
			'sample'      => '1',
			'description' => __( 'Paid holidays in the period.', 'BuildERP' ),
		),
		array(
			'key'         => 'total_paid_days',
			'label'       => __( 'Total Paid Days', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '26',
			'sample'      => '30',
			'description' => __( 'Total payable days (present + paid weekends + holidays).', 'BuildERP' ),
		),
		array(
			'key'         => 'overtime_hours',
			'label'       => __( 'Overtime Hours', 'BuildERP' ),
			'type'        => 'number',
			'default'     => '0',
			'sample'      => '8',
			'description' => __( 'Total overtime hours in the period.', 'BuildERP' ),
		),
		array(
			'key'         => 'overtime_rate',
			'label'       => __( 'Overtime Rate', 'BuildERP' ),
			'type'        => 'currency',
			'default'     => '0',
			'sample'      => '8',
			'description' => __( 'Hourly overtime amount.', 'BuildERP' ),
		),
		array(
			'key'         => 'total_allowances',
			'label'       => __( 'Total Allowances', 'BuildERP' ),
			'type'        => 'currency',
			'default'     => '0',
			'sample'      => '200',
			'description' => __( 'Sum of allowances for the period.', 'BuildERP' ),
		),
		array(
			'key'         => 'total_deductions',
			'label'       => __( 'Total Deductions', 'BuildERP' ),
			'type'        => 'currency',
			'default'     => '0',
			'sample'      => '50',
			'description' => __( 'Sum of deductions for the period.', 'BuildERP' ),
		),
	);

	$default_formula = '(basic_salary / working_days) * total_paid_days + (overtime_hours * overtime_rate) + total_allowances - total_deductions';

	return array(
		'active'    => array(
			'label'       => __( 'Default Net Pay', 'BuildERP' ),
			'formula'     => $default_formula,
			'variables'   => $base_variables,
			'notes'       => __( 'Prorates basic salary, adds overtime and allowances, subtracts deductions.', 'BuildERP' ),
			'version'     => 1,
			'saved_at'    => time(),
			'saved_by'    => get_current_user_id(),
			'is_active'   => true,
			'preview'     => array(),
			'description' => __( 'Base formula used for initial payroll calculations.', 'BuildERP' ),
		),
		'history'   => array(),
		'templates' => array(
			array(
				'key'         => 'net_basic',
				'label'       => __( 'Net = Basic + OT - Deductions', 'BuildERP' ),
				'formula'     => '(basic_salary / working_days) * total_paid_days + (overtime_hours * overtime_rate) + total_allowances - total_deductions',
				'description' => __( 'Prorated basic salary using total paid days, plus overtime, minus deductions.', 'BuildERP' ),
			),
			array(
				'key'         => 'with_present_days',
				'label'       => __( 'Simple: Present Days Only', 'BuildERP' ),
				'formula'     => '(basic_salary / working_days) * present_days + (overtime_hours * overtime_rate) + total_allowances - total_deductions',
				'description' => __( 'Uses only present days (no weekend/holiday calculation).', 'BuildERP' ),
			),
			array(
				'key'         => 'with_allowance_threshold',
				'label'       => __( 'Allowance Threshold Example', 'BuildERP' ),
				'formula'     => 'if(total_allowances > 500, (basic_salary / working_days) * total_paid_days + total_allowances * 0.9, (basic_salary / working_days) * total_paid_days + total_allowances) - total_deductions',
				'description' => __( 'Applies a 10% cap if allowances exceed 500 (example of IF function).', 'BuildERP' ),
			),
			array(
				'key'         => 'weekend_bonus',
				'label'       => __( 'Weekend Bonus Example', 'BuildERP' ),
				'formula'     => '(basic_salary / days_in_month) * (present_days + holidays) + (weekends * (basic_salary / days_in_month) * 1.5) + (overtime_hours * overtime_rate) + total_allowances - total_deductions',
				'description' => __( 'Example: Pays 1.5x daily rate for weekend days (demonstrates weekend variable usage).', 'BuildERP' ),
			),
		),
	);
}

/**
 * Get salary formula configuration with defaults.
 *
 * @since 1.0.0
 * @return array
 */
function berp_get_salary_formula_config() {
	$stored   = get_option( 'berp_salary_formula', array() );
	$defaults = berp_get_default_salary_formula();

	return wp_parse_args(
		is_array( $stored ) ? $stored : array(),
		$defaults
	);
}

/**
 * Save salary formula configuration.
 *
 * @param array $config Config.
 * @return void
 */
function berp_save_salary_formula_config( $config ) {
	update_option( 'berp_salary_formula', $config );
}

/**
 * Get currency symbol mapping.
 *
 * @return array Currency code to symbol mapping.
 */
function berp_get_currency_symbols() {
	return array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'AED' => 'د.إ',
		'INR' => '₹',
		'AUD' => 'A$',
		'CAD' => 'C$',
		'SGD' => 'S$',
		'SAR' => '﷼',
		'QAR' => 'ر.ق',
		'ZAR' => 'R',
		'JPY' => '¥',
		'CNY' => '¥',
		'CHF' => 'CHF',
		'NZD' => 'NZ$',
		'HKD' => 'HK$',
		'MYR' => 'RM',
		'PKR' => '₨',
		'BDT' => '৳',
		'LKR' => 'Rs',
		'NGN' => '₦',
		'KES' => 'KSh',
		'EGP' => 'E£',
		'TRY' => '₺',
		'THB' => '฿',
		'PHP' => '₱',
		'MXN' => 'MX$',
		'BRL' => 'R$',
		'ARS' => 'AR$',
		'CLP' => 'CL$',
		'COP' => 'CO$',
		'DKK' => 'kr',
		'NOK' => 'kr',
		'SEK' => 'kr',
		'PLN' => 'zł',
		'CZK' => 'Kč',
		'HUF' => 'Ft',
	);
}

/**
 * Get currency symbol
 *
 * @return string
 */
function berp_get_currency_symbol() {
	$settings         = berp_get_general_settings();
	$currency_code    = isset( $settings['currency'] ) ? $settings['currency'] : 'USD';
	$currency_symbols = berp_get_currency_symbols();

	return isset( $currency_symbols[ $currency_code ] ) ? $currency_symbols[ $currency_code ] : $currency_code;
}

/**
 * Format currency amount
 *
 * @param  float $amount         Amount to format.
 * @param  bool  $include_symbol Include currency symbol.
 * @return string
 */
function berp_format_currency( $amount, $include_symbol = true ) {
	$settings = berp_get_general_settings();
	$symbol   = berp_get_currency_symbol();
	$position = isset( $settings['currency_position'] ) ? $settings['currency_position'] : 'before';

	$formatted = number_format( (float) $amount, 2, '.', ',' );

	if ( ! $include_symbol ) {
		return $formatted;
	}

	if ( 'before' === $position ) {
		return $symbol . $formatted;
	} else {
		return $formatted . $symbol;
	}
}

/**
 * Format date.
 *
 * @param  string $date   Date string.
 * @param  string $format Date format (optional).
 * @return string
 */
function berp_format_date( $date, $format = null ) {
	if ( empty( $date ) ) {
		return '';
	}

	if ( ! $format ) {
		$settings = berp_get_general_settings();
		$format   = isset( $settings['date_format'] ) ? $settings['date_format'] : 'Y-m-d';
	}

	return wp_date( $format, strtotime( $date ) );
}

/**
 * Get employee display name
 *
 * @param  int $employee_id Employee post ID.
 * @return string
 */
function berp_get_employee_name( $employee_id ) {
	$employee = get_post( $employee_id );
	return $employee ? $employee->post_title : '';
}

/**
 * Get employee ID (employee number)
 *
 * @param  int $employee_post_id Employee post ID.
 * @return string
 */
function berp_get_employee_id( $employee_post_id ) {
	return get_post_meta( $employee_post_id, '_berp_employee_id', true );
}

/**
 * Get site/project name.
 *
 * @param  int $site_id Site post ID.
 * @return string
 */
function berp_get_site_name( $site_id ) {
	$site = get_post( $site_id );
	return $site ? $site->post_title : '';
}

/**
 * Get client name.
 *
 * @param  int $client_id Client post ID.
 * @return string
 */
function berp_get_client_name( $client_id ) {
	$client = get_post( $client_id );
	return $client ? $client->post_title : '';
}

/**
 * Check if user has BERP capability.
 *
 * @param  string $capability Capability to check.
 * @param  int    $user_id    User ID (optional, defaults to current user).
 * @return bool
 */
function berp_user_can( $capability, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$user = get_user_by( 'id', $user_id );
	return $user && $user->has_cap( $capability );
}

/**
 * Log plugin activity.
 *
 * For debugging purposes.
 *
 * @param string $message Log message.
 * @param string $type    Log type (info, error, warning).
 */
function berp_log( $message, $type = 'info' ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		error_log( sprintf( '[BuildErp][%s] %s', strtoupper( $type ), $message ) );
	}
}

/**
 * Log activity for audit trail.
 *
 * Stores activity logs in an option for display on the dashboard.
 * Keeps only the most recent 100 entries.
 *
 * @since 1.0.0
 *
 * @param string $action  Action performed (e.g., 'payroll_processed', 'employee_created').
 * @param string $message Human-readable message.
 * @param array  $context Additional context data (optional).
 */
function berp_log_activity( $action, $message, $context = array() ) {
	$logs = get_option( 'berp_activity_log', array() );

	// Add new entry at the beginning.
	array_unshift(
		$logs,
		array(
			'action'    => sanitize_key( $action ),
			'message'   => sanitize_text_field( $message ),
			'context'   => array_map( 'sanitize_text_field', (array) $context ),
			'user_id'   => get_current_user_id(),
			'timestamp' => current_time( 'mysql' ),
			'ip'        => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		)
	);

	// Keep only the last 100 entries.
	$logs = array_slice( $logs, 0, 100 );

	update_option( 'berp_activity_log', $logs, false );
}

/**
 * Get recent activity logs.
 *
 * @since 1.0.0
 *
 * @param int    $limit  Number of entries to return (default 10).
 * @param string $action Filter by action type (optional).
 * @return array Array of activity log entries.
 */
function berp_get_activity_log( $limit = 10, $action = '' ) {
	$logs = get_option( 'berp_activity_log', array() );

	if ( ! empty( $action ) ) {
		$logs = array_filter(
			$logs,
			function ( $log ) use ( $action ) {
				return $log['action'] === $action;
			}
		);
	}

	return array_slice( $logs, 0, $limit );
}

/**
 * Get all employees (active)
 *
 * @param  array $args Additional query arguments.
 * @return array Array of employee post objects
 */
function berp_get_employees( $args = array() ) {
	$defaults = array(
		'post_type'      => 'berp_employee',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_berp_status',
				'value'   => 'active',
				'compare' => '=',
			),
		),
	);

	$args  = wp_parse_args( $args, $defaults );
	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Get all sites/projects
 *
 * @param  array $args Additional query arguments.
 * @return array Array of site post objects
 */
function berp_get_sites( $args = array() ) {
	$defaults = array(
		'post_type'      => 'berp_site',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$args  = wp_parse_args( $args, $defaults );
	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Get all clients
 *
 * @param  array $args Additional query arguments.
 * @return array Array of client post objects
 */
function berp_get_clients( $args = array() ) {
	$defaults = array(
		'post_type'      => 'berp_client',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$args  = wp_parse_args( $args, $defaults );
	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Generate unique employee ID
 *
 * @param  string $prefix Prefix for employee ID.
 * @return string
 */
function berp_generate_employee_id( $prefix = 'EMP' ) {
	global $wpdb;

	// Get the highest employee ID.
	$last_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta}
        WHERE meta_key = %s
        ORDER BY CAST(SUBSTRING(meta_value, %d) AS UNSIGNED) DESC
        LIMIT 1",
			'_berp_employee_id',
			strlen( $prefix ) + 1
		)
	);

	if ( $last_id ) {
		$number = intval( substr( $last_id, strlen( $prefix ) ) ) + 1;
	} else {
		$number = 1;
	}

	return $prefix . str_pad( $number, 4, '0', STR_PAD_LEFT );
}

/**
 * Sanitize and validate employee data
 *
 * @param  array $data Employee data.
 * @return array Sanitized data
 */
function berp_sanitize_employee_data( $data ) {
	$sanitized = array();

	if ( isset( $data['employee_id'] ) ) {
		$sanitized['employee_id'] = sanitize_text_field( $data['employee_id'] );
	}

	if ( isset( $data['email'] ) ) {
		$sanitized['email'] = sanitize_email( $data['email'] );
	}

	if ( isset( $data['phone'] ) ) {
		$sanitized['phone'] = sanitize_text_field( $data['phone'] );
	}

	if ( isset( $data['basic_salary'] ) ) {
		$sanitized['basic_salary'] = floatval( $data['basic_salary'] );
	}

	if ( isset( $data['status'] ) ) {
		$sanitized['status'] = sanitize_text_field( $data['status'] );
	}

	return $sanitized;
}

/**
 * Get attendance for employee on specific date
 *
 * @param  int    $employee_id Employee post ID.
 * @param  string $date        Date (YYYY-MM-DD).
 * @return int|null Attendance post ID or null if not found
 */
function berp_get_attendance( $employee_id, $date ) {
	global $wpdb;

	$attendance_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_berp_employee_id'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_berp_date'
        WHERE p.post_type = 'berp_attendance'
        AND p.post_status = 'publish'
        AND pm1.meta_value = %d
        AND pm2.meta_value = %s
        LIMIT 1",
			$employee_id,
			$date
		)
	);

	return $attendance_id ? intval( $attendance_id ) : null;
}

/**
 * Helper function to generate plain language explanation of formula
 *
 * @param string $formula   The formula string.
 * @param array  $variables The variables defined.
 * @return string Plain language explanation.
 */
function berp_explain_formula( $formula, $variables = array() ) {
	if ( empty( $formula ) ) {
		return __( 'No formula defined.', 'BuildERP' );
	}

	// Create variable labels map.
	$var_labels = array();
	foreach ( $variables as $var ) {
		if ( isset( $var['key'] ) && isset( $var['label'] ) ) {
			$var_labels[ $var['key'] ] = $var['label'];
		}
	}

	// Simple pattern replacements.
	$explained = $formula;

	// Replace variables with their labels.
	foreach ( $var_labels as $key => $label ) {
		$explained = preg_replace( '/\b' . preg_quote( $key, '/' ) . '\b/', '[' . $label . ']', $explained );
	}

	// Replace operators with words.
	$explained = str_replace( array( '/', '*', '+', '-' ), array( ' divided by ', ' multiplied by ', ' plus ', ' minus ' ), $explained );

	// Replace functions.
	$explained = preg_replace( '/if\s*\(/i', 'IF (', $explained );
	$explained = preg_replace( '/min\s*\(/i', 'MINIMUM of (', $explained );
	$explained = preg_replace( '/max\s*\(/i', 'MAXIMUM of (', $explained );
	$explained = preg_replace( '/round\s*\(/i', 'ROUND (', $explained );
	$explained = preg_replace( '/abs\s*\(/i', 'ABSOLUTE VALUE of (', $explained );

	// Replace comparison operators.
	$explained = str_replace( array( '==', '!=', '>=', '<=', '>', '<', '&&', '||' ), array( ' equals ', ' does not equal ', ' is greater than or equal to ', ' is less than or equal to ', ' is greater than ', ' is less than ', ' AND ', ' OR ' ), $explained );

	return $explained;
}

/**
 * Get formula documentation helper content.
 *
 * @return string HTML content for formula documentation.
 */
function berp_get_formula_documentation() {
	ob_start();
	?>
	<div class="berp-formula-docs">
		<h3><?php esc_html_e( 'Formula Documentation', 'BuildERP' ); ?></h3>

		<h4><?php esc_html_e( 'Available Functions:', 'BuildERP' ); ?></h4>
		<ul>
			<li><code>IF(condition, value_if_true, value_if_false)</code> - <?php esc_html_e( 'Conditional logic', 'BuildERP' ); ?></li>
			<li><code>MIN(a, b)</code> - <?php esc_html_e( 'Return the smaller of two values', 'BuildERP' ); ?></li>
			<li><code>MAX(a, b)</code> - <?php esc_html_e( 'Return the larger of two values', 'BuildERP' ); ?></li>
			<li><code>ROUND(value, decimals)</code> - <?php esc_html_e( 'Round to specified decimal places', 'BuildERP' ); ?></li>
			<li><code>ABS(value)</code> - <?php esc_html_e( 'Absolute value (remove negative sign)', 'BuildERP' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Operators:', 'BuildERP' ); ?></h4>
		<ul>
			<li><code>+</code> - <?php esc_html_e( 'Addition', 'BuildERP' ); ?></li>
			<li><code>-</code> - <?php esc_html_e( 'Subtraction', 'BuildERP' ); ?></li>
			<li><code>*</code> - <?php esc_html_e( 'Multiplication', 'BuildERP' ); ?></li>
			<li><code>/</code> - <?php esc_html_e( 'Division', 'BuildERP' ); ?></li>
			<li><code>%</code> - <?php esc_html_e( 'Modulo (remainder)', 'BuildERP' ); ?></li>
			<li><code>^</code> - <?php esc_html_e( 'Exponentiation (power)', 'BuildERP' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Comparison Operators:', 'BuildERP' ); ?></h4>
		<ul>
			<li><code>==</code> - <?php esc_html_e( 'Equal to', 'BuildERP' ); ?></li>
			<li><code>!=</code> - <?php esc_html_e( 'Not equal to', 'BuildERP' ); ?></li>
			<li><code>&gt;</code> - <?php esc_html_e( 'Greater than', 'BuildERP' ); ?></li>
			<li><code>&lt;</code> - <?php esc_html_e( 'Less than', 'BuildERP' ); ?></li>
			<li><code>&gt;=</code> - <?php esc_html_e( 'Greater than or equal to', 'BuildERP' ); ?></li>
			<li><code>&lt;=</code> - <?php esc_html_e( 'Less than or equal to', 'BuildERP' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Logical Operators:', 'BuildERP' ); ?></h4>
		<ul>
			<li><code>&&</code> - <?php esc_html_e( 'AND (both conditions must be true)', 'BuildERP' ); ?></li>
			<li><code>||</code> - <?php esc_html_e( 'OR (at least one condition must be true)', 'BuildERP' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Examples:', 'BuildERP' ); ?></h4>
		<ul>
			<li><code>(basic_salary / working_days) * total_paid_days</code> - <?php esc_html_e( 'Daily rate times paid days', 'BuildERP' ); ?></li>
			<li><code>IF(present_days &gt; 25, 100, 0)</code> - <?php esc_html_e( 'Bonus if present more than 25 days', 'BuildERP' ); ?></li>
			<li><code>MIN(overtime_hours * overtime_rate, 500)</code> - <?php esc_html_e( 'Overtime payment capped at 500', 'BuildERP' ); ?></li>
			<li><code>weekends * (basic_salary / days_in_month) * 1.5</code> - <?php esc_html_e( 'Weekend pay at 1.5x daily rate', 'BuildERP' ); ?></li>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Generate quotation number with custom pattern
 *
 * @since 1.0.0
 * @param string $pattern Pattern like {PREFIX}-{YYYY}-{MM}-{####}.
 * @return string Generated quotation number.
 */
function berp_generate_quotation_number( $pattern = '' ) {
	// Get quotation settings from unified settings.
	$settings = berp_get_quotation_settings();

	if ( empty( $pattern ) ) {
		$pattern = isset( $settings['number_pattern'] ) ? $settings['number_pattern'] : '{PREFIX}-{YYYY}-{####}';
	}

	global $wpdb;

	// Extract counter placeholder.
	preg_match( '/\{(#+)\}/', $pattern, $matches );
	$counter_length = isset( $matches[1] ) ? strlen( $matches[1] ) : 4;

	// Get current values.
	$prefix = isset( $settings['quotation_prefix'] ) ? $settings['quotation_prefix'] : 'QUO';
	$year   = gmdate( 'Y' );
	$month  = gmdate( 'm' );

	// Build search pattern for SQL.
	$search_pattern = str_replace(
		array( '{PREFIX}', '{YYYY}', '{MM}' ),
		array( $prefix, $year, $month ),
		$pattern
	);
	$search_pattern = preg_replace( '/\{#+\}/', '%', $search_pattern );

	// Get last number.
	$last_number = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value LIKE %s
			ORDER BY meta_id DESC
			LIMIT 1",
			'_berp_quotation_number',
			$search_pattern
		)
	);

	// Extract numeric part and increment.
	if ( $last_number ) {
		preg_match( '/(\d+)$/', $last_number, $num_matches );
		$next_number = isset( $num_matches[1] ) ? intval( $num_matches[1] ) + 1 : 1;
	} else {
		$next_number = 1;
	}

	// Generate final number.
	$quotation_number = str_replace(
		array( '{PREFIX}', '{YYYY}', '{MM}', '{####}' ),
		array( $prefix, $year, $month, str_pad( $next_number, $counter_length, '0', STR_PAD_LEFT ) ),
		$pattern
	);

	return apply_filters( 'berp_quotation_number_generated', $quotation_number, $pattern, $next_number );
}

/**
 * Calculate quotation totals
 *
 * @since 1.0.0
 * @param array  $line_items     Line items array.
 * @param float  $tax_rate       Tax percentage.
 * @param string $discount_type  'fixed' or 'percentage'.
 * @param float  $discount_value Discount amount or percentage.
 * @return array Calculated totals.
 */
function berp_calculate_quotation_totals( $line_items, $tax_rate = 0, $discount_type = 'fixed', $discount_value = 0 ) {
	$subtotal = 0;

	// Calculate subtotal.
	if ( is_array( $line_items ) ) {
		foreach ( $line_items as $item ) {
			$quantity  = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
			$rate      = isset( $item['rate'] ) ? floatval( $item['rate'] ) : 0;
			$subtotal += ( $quantity * $rate );
		}
	}

	// Calculate discount.
	$discount_amount = 0;
	if ( 'percentage' === $discount_type ) {
		$discount_amount = $subtotal * ( floatval( $discount_value ) / 100 );
	} else {
		$discount_amount = floatval( $discount_value );
	}

	// Calculate tax on (subtotal - discount).
	$taxable_amount = $subtotal - $discount_amount;
	$tax_amount     = $taxable_amount * ( floatval( $tax_rate ) / 100 );

	// Grand total.
	$grand_total = $taxable_amount + $tax_amount;

	return array(
		'subtotal'        => $subtotal,
		'discount_amount' => $discount_amount,
		'tax_amount'      => $tax_amount,
		'grand_total'     => $grand_total,
	);
}

/**
 * Get quotation status label
 *
 * @since 1.0.0
 * @param string $status Status slug.
 * @return string Translated label.
 */
function berp_get_quotation_status_label( $status ) {
	$statuses = array(
		'draft'    => __( 'Draft', 'BuildERP' ),
		'sent'     => __( 'Sent', 'BuildERP' ),
		'accepted' => __( 'Accepted', 'BuildERP' ),
		'rejected' => __( 'Rejected', 'BuildERP' ),
		'expired'  => __( 'Expired', 'BuildERP' ),
	);

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Get quotation by ID
 *
 * @since 1.0.0
 * @param int $quotation_id Quotation post ID.
 * @return WP_Post|false Quotation post object or false.
 */
function berp_get_quotation( $quotation_id ) {
	$post = get_post( $quotation_id );

	if ( ! $post || 'berp_quotation' !== $post->post_type ) {
		return false;
	}

	return $post;
}

/**
 * Check if quotation is expired
 *
 * @since 1.0.0
 * @param int $quotation_id Quotation post ID.
 * @return bool True if expired.
 */
function berp_is_quotation_expired( $quotation_id ) {
	$validity_date = get_post_meta( $quotation_id, '_berp_validity_date', true );

	if ( empty( $validity_date ) ) {
		return false;
	}

	$validity_timestamp = strtotime( $validity_date );
	$current_timestamp  = time();

	return $current_timestamp > $validity_timestamp;
}

/**
 * Generate invoice number with custom pattern
 *
 * @since 1.0.0
 * @param string $pattern Pattern like {PREFIX}-{YYYY}-{MM}-{####}.
 * @return string Generated invoice number.
 */
function berp_generate_invoice_number( $pattern = '' ) {
	$settings = berp_get_quotation_settings();

	if ( empty( $pattern ) ) {
		$pattern = '{PREFIX}-{YYYY}-{####}';
	}

	global $wpdb;

	// Extract counter placeholder.
	preg_match( '/\{(#+)\}/', $pattern, $matches );
	$counter_length = isset( $matches[1] ) ? strlen( $matches[1] ) : 4;

	// Get current values.
	$prefix = isset( $settings['invoice_prefix'] ) ? $settings['invoice_prefix'] : 'INV';
	$year   = gmdate( 'Y' );
	$month  = gmdate( 'm' );

	// Build search pattern for SQL.
	$search_pattern = str_replace(
		array( '{PREFIX}', '{YYYY}', '{MM}' ),
		array( $prefix, $year, $month ),
		$pattern
	);
	$search_pattern = preg_replace( '/\{#+\}/', '%', $search_pattern );

	// Get last number.
	$last_number = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value LIKE %s
			ORDER BY meta_id DESC
			LIMIT 1",
			'_berp_invoice_number',
			$search_pattern
		)
	);

	// Extract numeric part and increment.
	if ( $last_number ) {
		preg_match( '/(\d+)$/', $last_number, $num_matches );
		$next_number = isset( $num_matches[1] ) ? intval( $num_matches[1] ) + 1 : 1;
	} else {
		$next_number = 1;
	}

	// Generate final number.
	$invoice_number = str_replace(
		array( '{PREFIX}', '{YYYY}', '{MM}', '{####}' ),
		array( $prefix, $year, $month, str_pad( $next_number, $counter_length, '0', STR_PAD_LEFT ) ),
		$pattern
	);

	return apply_filters( 'berp_invoice_number_generated', $invoice_number, $pattern, $next_number );
}

/**
 * Get invoice by ID
 *
 * @since 1.0.0
 * @param int $invoice_id Invoice post ID.
 * @return WP_Post|false Invoice post object or false.
 */
function berp_get_invoice( $invoice_id ) {
	$post = get_post( $invoice_id );

	if ( ! $post || 'berp_invoice' !== $post->post_type ) {
		return false;
	}

	return $post;
}

/**
 * Get invoice status label
 *
 * @since 1.0.0
 * @param string $status Status slug.
 * @return string Translated label.
 */
function berp_get_invoice_status_label( $status ) {
	$status   = strtolower( $status );
	$statuses = array(
		'draft'          => __( 'Draft', 'BuildERP' ),
		'sent'           => __( 'Sent', 'BuildERP' ),
		'viewed'         => __( 'Viewed', 'BuildERP' ),
		'partial'        => __( 'Partially Paid', 'BuildERP' ),
		'partially_paid' => __( 'Partially Paid', 'BuildERP' ),
		'paid'           => __( 'Paid', 'BuildERP' ),
		'overdue'        => __( 'Overdue', 'BuildERP' ),
	);

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
}

/**
 * Check if invoice is overdue
 *
 * @since 1.0.0
 * @param int $invoice_id Invoice post ID.
 * @return bool True if overdue.
 */
function berp_is_invoice_overdue( $invoice_id ) {
	$status = get_post_meta( $invoice_id, '_berp_status', true );

	// Paid invoices are never overdue.
	if ( 'paid' === $status ) {
		return false;
	}

	$due_date = get_post_meta( $invoice_id, '_berp_due_date', true );

	if ( empty( $due_date ) ) {
		return false;
	}

	$due_timestamp     = strtotime( $due_date );
	$current_timestamp = strtotime( 'today' );

	return $current_timestamp > $due_timestamp;
}

/**
 * Get number of days an invoice is overdue
 *
 * @since 1.0.0
 * @param int $invoice_id Invoice post ID.
 * @return int Days overdue (0 if not overdue).
 */
function berp_get_invoice_days_overdue( $invoice_id ) {
	if ( ! berp_is_invoice_overdue( $invoice_id ) ) {
		return 0;
	}

	$due_date = get_post_meta( $invoice_id, '_berp_due_date', true );

	if ( empty( $due_date ) ) {
		return 0;
	}

	$due_timestamp   = strtotime( $due_date );
	$today_timestamp = strtotime( 'today' );

	$days_overdue = floor( ( $today_timestamp - $due_timestamp ) / DAY_IN_SECONDS );

	return max( 0, $days_overdue );
}

/**
 * Calculate invoice totals from line items
 *
 * @since 1.0.0
 * @param array  $line_items     Line items array.
 * @param string $discount_type  'fixed' or 'percentage'.
 * @param float  $discount_value Discount amount or percentage.
 * @return array Calculated totals.
 */
function berp_calculate_invoice_totals( $line_items, $discount_type = 'fixed', $discount_value = 0 ) {
	$subtotal  = 0;
	$tax_total = 0;

	// Calculate subtotal and tax.
	if ( is_array( $line_items ) ) {
		foreach ( $line_items as $item ) {
			$quantity   = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
			$unit_price = isset( $item['unit_price'] ) ? floatval( $item['unit_price'] ) : 0;
			$tax        = isset( $item['tax'] ) ? floatval( $item['tax'] ) : 0;

			$subtotal  += ( $quantity * $unit_price );
			$tax_total += $tax;
		}
	}

	// Calculate discount.
	$discount_amount = 0;
	if ( 'percentage' === $discount_type ) {
		$discount_amount = ( $subtotal + $tax_total ) * ( floatval( $discount_value ) / 100 );
	} else {
		$discount_amount = floatval( $discount_value );
	}

	// Grand total.
	$grand_total = $subtotal + $tax_total - $discount_amount;

	return array(
		'subtotal'        => $subtotal,
		'tax_total'       => $tax_total,
		'discount_amount' => $discount_amount,
		'grand_total'     => max( 0, $grand_total ),
	);
}

/**
 * Get invoice aging bucket
 *
 * @since 1.0.0
 * @param int $invoice_id Invoice post ID.
 * @return string Aging bucket label.
 */
function berp_get_invoice_aging_bucket( $invoice_id ) {
	$days_overdue = berp_get_invoice_days_overdue( $invoice_id );

	if ( $days_overdue <= 0 ) {
		return __( 'Current', 'BuildERP' );
	} elseif ( $days_overdue <= 30 ) {
		return __( '1-30 Days', 'BuildERP' );
	} elseif ( $days_overdue <= 60 ) {
		return __( '31-60 Days', 'BuildERP' );
	} elseif ( $days_overdue <= 90 ) {
		return __( '61-90 Days', 'BuildERP' );
	} else {
		return __( '90+ Days', 'BuildERP' );
	}
}

/**
 * Create invoice from quotation
 *
 * @since 1.0.0
 * @param int $quotation_id Quotation post ID.
 * @return int|WP_Error New invoice ID or WP_Error on failure.
 */
function berp_create_invoice_from_quotation( $quotation_id ) {
	$quotation = berp_get_quotation( $quotation_id );

	if ( ! $quotation ) {
		return new WP_Error( 'invalid_quotation', __( 'Invalid quotation', 'BuildERP' ) );
	}

	// Create invoice post.
	$invoice_id = wp_insert_post(
		array(
			'post_type'   => 'berp_invoice',
			'post_status' => 'publish',
			'post_title'  => sprintf(
				/* translators: Quotation title */
				__( 'Invoice for %s', 'BuildERP' ),
				$quotation->post_title
			),
		)
	);

	if ( is_wp_error( $invoice_id ) ) {
		return $invoice_id;
	}

	// Generate invoice number.
	$invoice_number = berp_generate_invoice_number();
	update_post_meta( $invoice_id, '_berp_invoice_number', $invoice_number );

	// Copy quotation data to invoice.
	$fields_to_copy = array(
		'_berp_client_id',
		'_berp_site_id',
		'_berp_line_items',
		'_berp_subtotal',
		'_berp_tax_amount',
		'_berp_discount_type',
		'_berp_discount_value',
		'_berp_discount_amount',
		'_berp_grand_total',
		'_berp_notes',
	);

	foreach ( $fields_to_copy as $field ) {
		$value = get_post_meta( $quotation_id, $field, true );
		if ( $value ) {
			// Map tax_amount to tax_total for invoices.
			$target_field = ( '_berp_tax_amount' === $field ) ? '_berp_tax_total' : $field;
			update_post_meta( $invoice_id, $target_field, $value );
		}
	}

	// Set invoice defaults.
	$settings = berp_get_quotation_settings();
	$due_days = isset( $settings['invoice_due_days'] ) ? intval( $settings['invoice_due_days'] ) : 14;

	update_post_meta( $invoice_id, '_berp_invoice_date', current_time( 'Y-m-d' ) );
	update_post_meta( $invoice_id, '_berp_due_date', gmdate( 'Y-m-d', strtotime( '+' . $due_days . ' days' ) ) );
	update_post_meta( $invoice_id, '_berp_status', 'draft' );
	update_post_meta( $invoice_id, '_berp_amount_paid', 0 );
	update_post_meta( $invoice_id, '_berp_amount_due', get_post_meta( $quotation_id, '_berp_grand_total', true ) );
	update_post_meta( $invoice_id, '_berp_quotation_id', $quotation_id );
	update_post_meta( $invoice_id, '_berp_payments', array() );

	// Link invoice back to quotation.
	update_post_meta( $quotation_id, '_berp_invoice_id', $invoice_id );

	do_action( 'berp_invoice_created_from_quotation', $invoice_id, $quotation_id );

	return $invoice_id;
}

/**
 * Adjust color brightness.
 *
 * @param string $hex    Hex color code.
 * @param int    $steps  Amount to adjust (-255 to 255).
 * @return string Adjusted hex color.
 */
function berp_adjust_color_brightness( $hex, $steps ) {
	// Remove # if present.
	$hex = ltrim( $hex, '#' );

	// Convert to RGB.
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Adjust brightness.
	$r = max( 0, min( 255, $r + $steps ) );
	$g = max( 0, min( 255, $g + $steps ) );
	$b = max( 0, min( 255, $b + $steps ) );

	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Render standalone login content.
 */
function berp_render_standalone_login() {
	if ( is_user_logged_in() ) {
		// Get dashboard page URL.
		$settings          = get_option( 'berp_settings', array() );
		$portal_settings   = isset( $settings['portal'] ) ? $settings['portal'] : array();
		$dashboard_page_id = isset( $portal_settings['dashboard_page_id'] ) ? absint( $portal_settings['dashboard_page_id'] ) : 0;

		if ( $dashboard_page_id ) {
			echo '<div class="berp-portal-login-container">';
			echo '<div class="berp-portal-login-card">';
			echo '<p>' . esc_html__( 'You are already logged in.', 'BuildERP' ) . '</p>';
			echo '<p><a href="' . esc_url( get_permalink( $dashboard_page_id ) ) . '" class="berp-btn berp-btn-primary">' . esc_html__( 'Go to Dashboard', 'BuildERP' ) . '</a></p>';
			echo '</div></div>';
		} else {
			echo '<p>' . esc_html__( 'You are already logged in.', 'BuildERP' ) . '</p>';
		}
		return;
	}

	// Include the login template.
	include BERP_PLUGIN_DIR . 'templates/portal/login.php';
}

/**
 * Render standalone dashboard content.
 */
function berp_render_standalone_dashboard() {
	if ( ! is_user_logged_in() ) {
		$settings        = get_option( 'berp_settings', array() );
		$portal_settings = isset( $settings['portal'] ) ? $settings['portal'] : array();
		$login_page_id   = isset( $portal_settings['login_page_id'] ) ? absint( $portal_settings['login_page_id'] ) : 0;

		echo '<div class="berp-portal-login-container">';
		echo '<div class="berp-portal-login-card">';
		echo '<p>' . esc_html__( 'Please log in to view the dashboard.', 'BuildERP' ) . '</p>';
		if ( $login_page_id ) {
			echo '<p><a href="' . esc_url( get_permalink( $login_page_id ) ) . '" class="berp-btn berp-btn-primary">' . esc_html__( 'Go to Login', 'BuildERP' ) . '</a></p>';
		}
		echo '</div></div>';
		return;
	}

	$user = wp_get_current_user();

	// Check if user has portal access.
	$allowed_roles = array( 'administrator', 'berp_employee', 'berp_timekeeper' );
	$has_access    = false;
	foreach ( $allowed_roles as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			$has_access = true;
			break;
		}
	}

	if ( ! $has_access ) {
		echo '<div class="berp-portal-login-container">';
		echo '<div class="berp-portal-login-card">';
		echo '<p>' . esc_html__( 'You do not have permission to access the employee portal.', 'BuildERP' ) . '</p>';
		echo '</div></div>';
		return;
	}

	// Load header.
	include BERP_PLUGIN_DIR . 'templates/portal/header.php';

	// Route based on role and query var.
	$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'dashboard';

	if ( in_array( 'berp_timekeeper', (array) $user->roles, true ) || current_user_can( 'manage_options' ) ) {
		// Timekeeper Dashboard.
		berp_render_timekeeper_view( $view );
	} else {
		// Employee Dashboard.
		berp_render_employee_view( $view );
	}

	// Load footer.
	include BERP_PLUGIN_DIR . 'templates/portal/footer.php';
}

/**
 * Render timekeeper view.
 *
 * @param string $view View name.
 */
function berp_render_timekeeper_view( $view ) {
	switch ( $view ) {
		case 'log-attendance':
			include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/log-attendance.php';
			break;
		case 'view-attendance':
			include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/view-attendance.php';
			break;
		case 'employees':
			include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/employees.php';
			break;
		// Employee self-service views for timekeeper (my-* views).
		case 'my-dashboard':
			include BERP_PLUGIN_DIR . 'templates/portal/dashboard-employee.php';
			break;
		case 'my-attendance':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/attendance.php';
			break;
		case 'my-salary':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/salary.php';
			break;
		case 'my-profile':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/profile.php';
			break;
		case 'dashboard':
		default:
			include BERP_PLUGIN_DIR . 'templates/portal/dashboard-timekeeper.php';
			break;
	}
}

/**
 * Render employee view.
 *
 * @param string $view View name.
 */
function berp_render_employee_view( $view ) {
	switch ( $view ) {
		case 'attendance':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/attendance.php';
			break;
		case 'salary':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/salary.php';
			break;
		case 'statement':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/statement.php';
			break;
		case 'profile':
			include BERP_PLUGIN_DIR . 'templates/portal/employee/profile.php';
			break;
		case 'dashboard':
		default:
			include BERP_PLUGIN_DIR . 'templates/portal/dashboard-employee.php';
			break;
	}
}

/**
 * Get cached report data.
 *
 * Retrieves cached report data from transients if available and not expired.
 *
 * @since 1.0.0
 * @param string $report_key  Unique key for the report.
 * @param array  $params      Report parameters for cache key generation.
 * @param int    $expiration  Cache expiration in seconds. Default 1 hour.
 * @return mixed|false Cached data or false if not cached.
 */
function berp_get_cached_report( $report_key, $params = array(), $expiration = HOUR_IN_SECONDS ) {
	$cache_key = berp_generate_report_cache_key( $report_key, $params );
	return get_transient( $cache_key );
}

/**
 * Set cached report data.
 *
 * Stores report data in transients for performance optimization.
 *
 * @since 1.0.0
 * @param string $report_key  Unique key for the report.
 * @param array  $params      Report parameters for cache key generation.
 * @param mixed  $data        Data to cache.
 * @param int    $expiration  Cache expiration in seconds. Default 1 hour.
 * @return bool True if cached successfully.
 */
function berp_set_cached_report( $report_key, $params, $data, $expiration = HOUR_IN_SECONDS ) {
	$cache_key = berp_generate_report_cache_key( $report_key, $params );
	return set_transient( $cache_key, $data, $expiration );
}

/**
 * Generate a cache key for reports.
 *
 * @since 1.0.0
 * @param string $report_key Report identifier.
 * @param array  $params     Report parameters.
 * @return string Cache key.
 */
function berp_generate_report_cache_key( $report_key, $params = array() ) {
	$param_hash = md5( wp_json_encode( $params ) );
	return 'berp_report_' . sanitize_key( $report_key ) . '_' . $param_hash;
}

/**
 * Clear all report caches.
 *
 * Clears all BuildERP report transients when data changes.
 *
 * @since 1.0.0
 * @return int Number of transients deleted.
 */
function berp_clear_report_caches() {
	global $wpdb;

	$deleted = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_berp_report_%',
			'_transient_timeout_berp_report_%'
		)
	);

	return $deleted;
}

/**
 * Clear specific report cache.
 *
 * @since 1.0.0
 * @param string $report_key Report identifier.
 * @param array  $params     Report parameters (optional).
 * @return bool True if deleted.
 */
function berp_clear_report_cache( $report_key, $params = array() ) {
	if ( ! empty( $params ) ) {
		$cache_key = berp_generate_report_cache_key( $report_key, $params );
		return delete_transient( $cache_key );
	}

	// Clear all caches for this report type.
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_berp_report_' . sanitize_key( $report_key ) . '_%',
			'_transient_timeout_berp_report_' . sanitize_key( $report_key ) . '_%'
		)
	);

	return true;
}

/**
 * Clear caches when data changes.
 *
 * Hook into data modification actions to clear relevant caches.
 *
 * @since 1.0.0
 * @param int $post_id Post ID being modified.
 */
function berp_clear_caches_on_save( $post_id ) {
	$post_type = get_post_type( $post_id );

	$cache_mappings = array(
		'berp_attendance' => array( 'attendance_summary', 'overtime_analysis' ),
		'berp_payroll'    => array( 'payroll_register', 'employee_balances' ),
		'berp_expense'    => array( 'expense_breakdown', 'site_profitability', 'budget_vs_actual' ),
		'berp_invoice'    => array( 'invoice_aging', 'client_payments', 'revenue_trends', 'site_profitability' ),
		'berp_site'       => array( 'site_profitability', 'budget_vs_actual' ),
		'berp_employee'   => array( 'employee_balances', 'attendance_summary' ),
	);

	if ( isset( $cache_mappings[ $post_type ] ) ) {
		foreach ( $cache_mappings[ $post_type ] as $report_key ) {
			berp_clear_report_cache( $report_key );
		}
	}

	// Clear dashboard cache.
	delete_transient( 'berp_dashboard_quick_stats' );
	delete_transient( 'berp_dashboard_chart_data' );
}
add_action( 'save_post', 'berp_clear_caches_on_save', 20 );
add_action( 'delete_post', 'berp_clear_caches_on_save', 20 );
add_action( 'trash_post', 'berp_clear_caches_on_save', 20 );

/**
 * Process advance repayment after payroll is paid.
 *
 * Updates the advance remaining amount and removes the deduction from
 * employee when fully repaid.
 *
 * @since 1.0.0
 * @param int $payroll_id  Payroll post ID.
 * @param int $employee_id Employee post ID.
 * @param int $expense_id  Expense post ID.
 */
function berp_process_advance_repayment( $payroll_id, $employee_id, $expense_id ) {
	// Get employee deductions
	$deductions = get_post_meta( $employee_id, '_berp_deductions', true );

	if ( ! is_array( $deductions ) || empty( $deductions ) ) {
		return;
	}

	$updated_deductions  = array();
	$removed_advance_ids = array();
	$processed_advances  = array();

	foreach ( $deductions as $deduction ) {
		// Check if this is an advance repayment deduction
		if ( isset( $deduction['type'] ) && 'advance_repayment' === $deduction['type'] && isset( $deduction['advance_id'] ) ) {
			$advance_id = absint( $deduction['advance_id'] );

			// Get advance data
			$remaining_amount   = (float) get_post_meta( $advance_id, '_berp_remaining_amount', true );
			$installment_amount = (float) $deduction['amount'];
			$installments_paid  = (int) get_post_meta( $advance_id, '_berp_installments_paid', true );
			$total_installments = (int) get_post_meta( $advance_id, '_berp_installments', true );

			// Deduct the installment from remaining amount
			$new_remaining = $remaining_amount - $installment_amount;

			// Ensure we don't go negative
			if ( $new_remaining < 0 ) {
				$new_remaining = 0;
			}

			// Update advance meta
			update_post_meta( $advance_id, '_berp_remaining_amount', $new_remaining );
			update_post_meta( $advance_id, '_berp_installments_paid', $installments_paid + 1 );
			update_post_meta( $advance_id, '_berp_last_deduction_date', current_time( 'Y-m-d' ) );
			update_post_meta( $advance_id, '_berp_last_payroll_id', $payroll_id );

			// Check if fully repaid
			if ( $new_remaining <= 0 || ( $installments_paid + 1 ) >= $total_installments ) {
				// Mark advance as completed
				update_post_meta( $advance_id, '_berp_advance_status', 'completed' );
				update_post_meta( $advance_id, '_berp_completed_date', current_time( 'Y-m-d' ) );

				// Don't include this deduction in the updated list (remove it)
				$removed_advance_ids[] = $advance_id;

				// Trigger hook for advance completion
				do_action( 'berp_advance_completed', $advance_id, $employee_id );
			} else {
				// Keep the deduction for next payroll
				$updated_deductions[] = $deduction;
			}

			$processed_advances[] = $advance_id;
		} else {
			// Keep non-advance deductions
			$updated_deductions[] = $deduction;
		}
	}

	// Update employee deductions (removes completed advances)
	update_post_meta( $employee_id, '_berp_deductions', $updated_deductions );

	// Log the processing
	if ( ! empty( $processed_advances ) ) {
		do_action( 'berp_advances_processed', $processed_advances, $employee_id, $payroll_id );
	}
}
add_action( 'berp_after_payroll_paid', 'berp_process_advance_repayment', 10, 3 );

/**
 * Get active advances for an employee.
 *
 * @since 1.0.0
 * @param int $employee_id Employee post ID.
 * @return array Array of active advance posts.
 */
function berp_get_employee_active_advances( $employee_id ) {
	return get_posts(
		array(
			'post_type'      => 'berp_advance',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_berp_employee_id',
					'value' => $employee_id,
				),
				array(
					'key'     => '_berp_advance_status',
					'value'   => 'active',
					'compare' => '=',
				),
			),
		)
	);
}

/**
 * Get total pending advance amount for an employee.
 *
 * @since 1.0.0
 * @param int $employee_id Employee post ID.
 * @return float Total pending advance amount.
 */
function berp_get_employee_pending_advance_total( $employee_id ) {
	$advances = berp_get_employee_active_advances( $employee_id );
	$total    = 0;

	foreach ( $advances as $advance ) {
		$remaining = get_post_meta( $advance->ID, '_berp_remaining_amount', true );
		$total    += floatval( $remaining );
	}

	return $total;
}

