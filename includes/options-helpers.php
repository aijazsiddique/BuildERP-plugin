<?php
/**
 * Options helper functions.
 *
 * @package BuildERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function berp_normalize_array_option( $value ) {
	return is_array( $value ) ? $value : array();
}

function berp_normalize_bool_option( $value ) {
	return (bool) $value;
}

function berp_normalize_int_option( $value ) {
	return (int) $value;
}

function berp_normalize_float_option( $value ) {
	return (float) $value;
}

function berp_normalize_string_option( $value ) {
	return is_string( $value ) ? $value : '';
}

function berp_get_settings_option() {
	$settings = get_option( 'berp_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

function berp_set_settings_option( $settings ) {
	return update_option( 'berp_settings', berp_normalize_array_option( $settings ) );
}

function berp_get_setting_value( $section, $key, $default ) {
	$settings = berp_get_settings_option();
	if ( isset( $settings[ $section ] ) && is_array( $settings[ $section ] ) && array_key_exists( $key, $settings[ $section ] ) ) {
		return $settings[ $section ][ $key ];
	}
	return $default;
}

function berp_set_setting_value( $section, $key, $value ) {
	$settings = berp_get_settings_option();
	if ( ! isset( $settings[ $section ] ) || ! is_array( $settings[ $section ] ) ) {
		$settings[ $section ] = array();
	}
	$settings[ $section ][ $key ] = $value;
	return berp_set_settings_option( $settings );
}

/* =============================
 * General settings
 * ===========================*/

function berp_get_general_company_name_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'company_name', '' ) );
}

function berp_set_general_company_name_setting( $value ) {
	return berp_set_setting_value( 'general', 'company_name', sanitize_text_field( $value ) );
}

function berp_get_general_company_email_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'company_email', '' ) );
}

function berp_set_general_company_email_setting( $value ) {
	return berp_set_setting_value( 'general', 'company_email', sanitize_email( $value ) );
}

function berp_get_general_company_phone_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'company_phone', '' ) );
}

function berp_set_general_company_phone_setting( $value ) {
	return berp_set_setting_value( 'general', 'company_phone', sanitize_text_field( $value ) );
}

function berp_get_general_company_address_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'company_address', '' ) );
}

function berp_set_general_company_address_setting( $value ) {
	return berp_set_setting_value( 'general', 'company_address', sanitize_text_field( $value ) );
}

function berp_get_general_company_logo_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'company_logo', '' ) );
}

function berp_set_general_company_logo_setting( $value ) {
	return berp_set_setting_value( 'general', 'company_logo', esc_url_raw( $value ) );
}

function berp_get_general_currency_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'currency', 'USD' ) );
}

function berp_set_general_currency_setting( $value ) {
	return berp_set_setting_value( 'general', 'currency', sanitize_text_field( $value ) );
}

function berp_get_general_date_format_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'date_format', 'Y-m-d' ) );
}

function berp_set_general_date_format_setting( $value ) {
	return berp_set_setting_value( 'general', 'date_format', sanitize_text_field( $value ) );
}

function berp_get_general_time_format_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'time_format', '24' ) );
}

function berp_set_general_time_format_setting( $value ) {
	return berp_set_setting_value( 'general', 'time_format', sanitize_text_field( $value ) );
}

function berp_get_general_fiscal_year_start_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'general', 'fiscal_year_start', 'January' ) );
}

function berp_set_general_fiscal_year_start_setting( $value ) {
	return berp_set_setting_value( 'general', 'fiscal_year_start', sanitize_text_field( $value ) );
}

/* =============================
 * Payroll settings
 * ===========================*/

function berp_get_payroll_working_days_mode_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'payroll', 'working_days_mode', 'fixed' ) );
}

function berp_set_payroll_working_days_mode_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'working_days_mode', sanitize_text_field( $value ) );
}

function berp_get_payroll_working_days_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'payroll', 'working_days', 30 ) );
}

function berp_set_payroll_working_days_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'working_days', berp_normalize_int_option( $value ) );
}

function berp_get_payroll_weekend_days_setting() {
	return berp_normalize_array_option( berp_get_setting_value( 'payroll', 'weekend_days', array( 'sunday' ) ) );
}

function berp_set_payroll_weekend_days_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'weekend_days', berp_normalize_array_option( $value ) );
}

function berp_get_payroll_weekend_payment_rule_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'payroll', 'weekend_payment_rule', 0 ) );
}

function berp_set_payroll_weekend_payment_rule_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'weekend_payment_rule', berp_normalize_int_option( $value ) );
}

function berp_get_payroll_holidays_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'payroll', 'holidays', '' ) );
}

function berp_set_payroll_holidays_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'holidays', sanitize_textarea_field( $value ) );
}

function berp_get_payroll_calculation_mode_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'payroll', 'calculation_mode', 'auto' ) );
}

function berp_set_payroll_calculation_mode_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'calculation_mode', sanitize_text_field( $value ) );
}

function berp_get_payroll_processing_day_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'payroll', 'processing_day', 1 ) );
}

function berp_set_payroll_processing_day_setting( $value ) {
	return berp_set_setting_value( 'payroll', 'processing_day', berp_normalize_int_option( $value ) );
}

/* =============================
 * Attendance settings
 * ===========================*/

function berp_get_attendance_editable_days_limit_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'attendance', 'editable_days_limit', 7 ) );
}

function berp_set_attendance_editable_days_limit_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'editable_days_limit', berp_normalize_int_option( $value ) );
}

function berp_get_attendance_block_future_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'attendance', 'block_future', 1 ) );
}

function berp_set_attendance_block_future_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'block_future', berp_normalize_int_option( $value ) );
}

function berp_get_attendance_require_site_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'attendance', 'require_site', 1 ) );
}

function berp_set_attendance_require_site_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'require_site', berp_normalize_int_option( $value ) );
}

function berp_get_attendance_overtime_method_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'attendance', 'overtime_method', 'multiplier' ) );
}

function berp_set_attendance_overtime_method_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'overtime_method', sanitize_text_field( $value ) );
}

function berp_get_attendance_weekday_rate_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'attendance', 'weekday_rate', 1.5 ) );
}

function berp_set_attendance_weekday_rate_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'weekday_rate', berp_normalize_float_option( $value ) );
}

function berp_get_attendance_weekend_rate_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'attendance', 'weekend_rate', 2.0 ) );
}

function berp_set_attendance_weekend_rate_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'weekend_rate', berp_normalize_float_option( $value ) );
}

function berp_get_attendance_holiday_rate_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'attendance', 'holiday_rate', 2.5 ) );
}

function berp_set_attendance_holiday_rate_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'holiday_rate', berp_normalize_float_option( $value ) );
}

function berp_get_attendance_default_multiplier_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'attendance', 'default_multiplier', 1.5 ) );
}

function berp_set_attendance_default_multiplier_setting( $value ) {
	return berp_set_setting_value( 'attendance', 'default_multiplier', berp_normalize_float_option( $value ) );
}

/* =============================
 * Expense settings
 * ===========================*/

function berp_get_expense_default_categories_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'expense', 'default_categories', "Travel\nMaterials\nUtilities\nMiscellaneous" ) );
}

function berp_set_expense_default_categories_setting( $value ) {
	return berp_set_setting_value( 'expense', 'default_categories', sanitize_textarea_field( $value ) );
}

function berp_get_expense_payment_methods_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'expense', 'payment_methods', "Cash\nBank Transfer\nCheque\nCredit Card" ) );
}

function berp_set_expense_payment_methods_setting( $value ) {
	return berp_set_setting_value( 'expense', 'payment_methods', sanitize_textarea_field( $value ) );
}

function berp_get_expense_enable_recurring_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'expense', 'enable_recurring', 0 ) );
}

function berp_set_expense_enable_recurring_setting( $value ) {
	return berp_set_setting_value( 'expense', 'enable_recurring', berp_normalize_int_option( $value ) );
}

function berp_get_expense_approval_workflow_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'expense', 'approval_workflow', 0 ) );
}

function berp_set_expense_approval_workflow_setting( $value ) {
	return berp_set_setting_value( 'expense', 'approval_workflow', berp_normalize_int_option( $value ) );
}

function berp_get_expense_require_receipt_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'expense', 'require_receipt', 1 ) );
}

function berp_set_expense_require_receipt_setting( $value ) {
	return berp_set_setting_value( 'expense', 'require_receipt', berp_normalize_int_option( $value ) );
}

/* =============================
 * Quotation & invoice settings
 * ===========================*/

function berp_get_quotation_prefix_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'quotation_prefix', 'QUO' ) );
}

function berp_set_quotation_prefix_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'quotation_prefix', sanitize_text_field( $value ) );
}

function berp_get_invoice_prefix_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'invoice_prefix', 'INV' ) );
}

function berp_set_invoice_prefix_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'invoice_prefix', sanitize_text_field( $value ) );
}

function berp_get_quotation_number_format_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'number_format', 'sequential' ) );
}

function berp_set_quotation_number_format_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'number_format', sanitize_text_field( $value ) );
}

function berp_get_quotation_starting_number_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'quotation', 'starting_number', 1 ) );
}

function berp_set_quotation_starting_number_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'starting_number', berp_normalize_int_option( $value ) );
}

function berp_get_quotation_tax_rate_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'quotation', 'tax_rate', 5 ) );
}

function berp_set_quotation_tax_rate_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'tax_rate', berp_normalize_float_option( $value ) );
}

function berp_get_quotation_payment_terms_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'payment_terms', '30 days' ) );
}

function berp_set_quotation_payment_terms_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'payment_terms', sanitize_text_field( $value ) );
}

function berp_get_quotation_validity_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'quotation', 'quotation_validity', 30 ) );
}

function berp_set_quotation_validity_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'quotation_validity', berp_normalize_int_option( $value ) );
}

function berp_get_invoice_due_days_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'quotation', 'invoice_due_days', 14 ) );
}

function berp_set_invoice_due_days_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'invoice_due_days', berp_normalize_int_option( $value ) );
}

function berp_get_quotation_theme_color_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'theme_color', '#2271b1' ) );
}

function berp_set_quotation_theme_color_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'theme_color', sanitize_text_field( $value ) );
}

function berp_get_quotation_header_text_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'header_text', '' ) );
}

function berp_set_quotation_header_text_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'header_text', sanitize_textarea_field( $value ) );
}

function berp_get_quotation_terms_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'terms', '' ) );
}

function berp_set_quotation_terms_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'terms', sanitize_textarea_field( $value ) );
}

function berp_get_quotation_pdf_terms_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'quotation', 'pdf_terms', '' ) );
}

function berp_set_quotation_pdf_terms_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'pdf_terms', sanitize_textarea_field( $value ) );
}

function berp_get_quotation_enable_quote_email_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'quotation', 'enable_quote_email', 1 ) );
}

function berp_set_quotation_enable_quote_email_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'enable_quote_email', berp_normalize_int_option( $value ) );
}

function berp_get_quotation_enable_invoice_email_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'quotation', 'enable_invoice_email', 1 ) );
}

function berp_set_quotation_enable_invoice_email_setting( $value ) {
	return berp_set_setting_value( 'quotation', 'enable_invoice_email', berp_normalize_int_option( $value ) );
}

/* =============================
 * Site settings
 * ===========================*/

function berp_get_site_default_status_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'site', 'default_status', 'planning' ) );
}

function berp_set_site_default_status_setting( $value ) {
	return berp_set_setting_value( 'site', 'default_status', sanitize_text_field( $value ) );
}

function berp_get_site_budget_alert_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'site', 'budget_alert', 80 ) );
}

function berp_set_site_budget_alert_setting( $value ) {
	return berp_set_setting_value( 'site', 'budget_alert', berp_normalize_float_option( $value ) );
}

function berp_get_site_require_manager_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'site', 'require_manager', 0 ) );
}

function berp_set_site_require_manager_setting( $value ) {
	return berp_set_setting_value( 'site', 'require_manager', berp_normalize_int_option( $value ) );
}

function berp_get_site_enable_budget_tracking_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'site', 'enable_budget_tracking', 1 ) );
}

function berp_set_site_enable_budget_tracking_setting( $value ) {
	return berp_set_setting_value( 'site', 'enable_budget_tracking', berp_normalize_int_option( $value ) );
}

/* =============================
 * Employee settings
 * ===========================*/

function berp_get_employee_id_format_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'employee', 'id_format', 'auto' ) );
}

function berp_set_employee_id_format_setting( $value ) {
	return berp_set_setting_value( 'employee', 'id_format', sanitize_text_field( $value ) );
}

function berp_get_employee_starting_id_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'employee', 'starting_id', 1 ) );
}

function berp_set_employee_starting_id_setting( $value ) {
	return berp_set_setting_value( 'employee', 'starting_id', berp_normalize_int_option( $value ) );
}

function berp_get_employee_default_status_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'employee', 'default_status', 'active' ) );
}

function berp_set_employee_default_status_setting( $value ) {
	return berp_set_setting_value( 'employee', 'default_status', sanitize_text_field( $value ) );
}

function berp_get_employee_advance_requires_approval_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'employee', 'advance_requires_approval', 1 ) );
}

function berp_set_employee_advance_requires_approval_setting( $value ) {
	return berp_set_setting_value( 'employee', 'advance_requires_approval', berp_normalize_int_option( $value ) );
}

function berp_get_employee_advance_max_percent_setting() {
	return berp_normalize_float_option( berp_get_setting_value( 'employee', 'advance_max_percent', 50 ) );
}

function berp_set_employee_advance_max_percent_setting( $value ) {
	return berp_set_setting_value( 'employee', 'advance_max_percent', berp_normalize_float_option( $value ) );
}

function berp_get_employee_allow_installments_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'employee', 'allow_installments', 1 ) );
}

function berp_set_employee_allow_installments_setting( $value ) {
	return berp_set_setting_value( 'employee', 'allow_installments', berp_normalize_int_option( $value ) );
}

function berp_get_employee_custom_fields_setting() {
	return berp_normalize_array_option( berp_get_setting_value( 'employee', 'custom_fields', array() ) );
}

function berp_set_employee_custom_fields_setting( $value ) {
	return berp_set_setting_value( 'employee', 'custom_fields', berp_normalize_array_option( $value ) );
}

/* =============================
 * Notification settings
 * ===========================*/

function berp_get_notification_employee_notifications_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'notification', 'employee_notifications', 1 ) );
}

function berp_set_notification_employee_notifications_setting( $value ) {
	return berp_set_setting_value( 'notification', 'employee_notifications', berp_normalize_int_option( $value ) );
}

function berp_get_notification_admin_notifications_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'notification', 'admin_notifications', 1 ) );
}

function berp_set_notification_admin_notifications_setting( $value ) {
	return berp_set_setting_value( 'notification', 'admin_notifications', berp_normalize_int_option( $value ) );
}

function berp_get_notification_client_notifications_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'notification', 'client_notifications', 1 ) );
}

function berp_set_notification_client_notifications_setting( $value ) {
	return berp_set_setting_value( 'notification', 'client_notifications', berp_normalize_int_option( $value ) );
}

function berp_get_notification_timekeeper_notifications_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'notification', 'timekeeper_notifications', 1 ) );
}

function berp_set_notification_timekeeper_notifications_setting( $value ) {
	return berp_set_setting_value( 'notification', 'timekeeper_notifications', berp_normalize_int_option( $value ) );
}

function berp_get_notification_smtp_host_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'smtp_host', '' ) );
}

function berp_set_notification_smtp_host_setting( $value ) {
	return berp_set_setting_value( 'notification', 'smtp_host', sanitize_text_field( $value ) );
}

function berp_get_notification_smtp_port_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'notification', 'smtp_port', 0 ) );
}

function berp_set_notification_smtp_port_setting( $value ) {
	return berp_set_setting_value( 'notification', 'smtp_port', berp_normalize_int_option( $value ) );
}

function berp_get_notification_smtp_encryption_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'smtp_encryption', '' ) );
}

function berp_set_notification_smtp_encryption_setting( $value ) {
	return berp_set_setting_value( 'notification', 'smtp_encryption', sanitize_text_field( $value ) );
}

function berp_get_notification_smtp_user_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'smtp_user', '' ) );
}

function berp_set_notification_smtp_user_setting( $value ) {
	return berp_set_setting_value( 'notification', 'smtp_user', sanitize_text_field( $value ) );
}

function berp_get_notification_smtp_pass_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'smtp_pass', '' ) );
}

function berp_set_notification_smtp_pass_setting( $value ) {
	return berp_set_setting_value( 'notification', 'smtp_pass', sanitize_text_field( $value ) );
}

function berp_get_notification_email_subject_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'email_subject', '' ) );
}

function berp_set_notification_email_subject_setting( $value ) {
	return berp_set_setting_value( 'notification', 'email_subject', sanitize_text_field( $value ) );
}

function berp_get_notification_email_template_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'notification', 'email_template', '' ) );
}

function berp_set_notification_email_template_setting( $value ) {
	return berp_set_setting_value( 'notification', 'email_template', sanitize_textarea_field( $value ) );
}

/* =============================
 * Portal settings
 * ===========================*/

function berp_get_portal_portal_slug_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'portal_slug', 'berp-portal' ) );
}

function berp_set_portal_portal_slug_setting( $value ) {
	return berp_set_setting_value( 'portal', 'portal_slug', sanitize_title( $value ) );
}

function berp_get_portal_enable_portal_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'enable_portal', 1 ) );
}

function berp_set_portal_enable_portal_setting( $value ) {
	return berp_set_setting_value( 'portal', 'enable_portal', berp_normalize_int_option( $value ) );
}

function berp_get_portal_login_page_id_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'login_page_id', 0 ) );
}

function berp_set_portal_login_page_id_setting( $value ) {
	return berp_set_setting_value( 'portal', 'login_page_id', berp_normalize_int_option( $value ) );
}

function berp_get_portal_dashboard_page_id_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'dashboard_page_id', 0 ) );
}

function berp_set_portal_dashboard_page_id_setting( $value ) {
	return berp_set_setting_value( 'portal', 'dashboard_page_id', berp_normalize_int_option( $value ) );
}

function berp_get_portal_use_standalone_template_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'use_standalone_template', 1 ) );
}

function berp_set_portal_use_standalone_template_setting( $value ) {
	return berp_set_setting_value( 'portal', 'use_standalone_template', berp_normalize_int_option( $value ) );
}

function berp_get_portal_allow_profile_edit_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'allow_profile_edit', 1 ) );
}

function berp_set_portal_allow_profile_edit_setting( $value ) {
	return berp_set_setting_value( 'portal', 'allow_profile_edit', berp_normalize_int_option( $value ) );
}

function berp_get_portal_branding_color_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'branding_color', '#2271b1' ) );
}

function berp_set_portal_branding_color_setting( $value ) {
	return berp_set_setting_value( 'portal', 'branding_color', sanitize_text_field( $value ) );
}

function berp_get_portal_session_timeout_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'session_timeout', 30 ) );
}

function berp_set_portal_session_timeout_setting( $value ) {
	return berp_set_setting_value( 'portal', 'session_timeout', berp_normalize_int_option( $value ) );
}

function berp_get_portal_username_format_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'username_format', 'email' ) );
}

function berp_set_portal_username_format_setting( $value ) {
	return berp_set_setting_value( 'portal', 'username_format', sanitize_text_field( $value ) );
}

function berp_get_portal_password_strength_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'password_strength', 'strong' ) );
}

function berp_set_portal_password_strength_setting( $value ) {
	return berp_set_setting_value( 'portal', 'password_strength', sanitize_text_field( $value ) );
}

function berp_get_portal_portal_access_behavior_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'portal_access_behavior', 'keep_user' ) );
}

function berp_set_portal_portal_access_behavior_setting( $value ) {
	return berp_set_setting_value( 'portal', 'portal_access_behavior', sanitize_text_field( $value ) );
}

function berp_get_portal_employee_delete_behavior_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'employee_delete_behavior', 'keep_user' ) );
}

function berp_set_portal_employee_delete_behavior_setting( $value ) {
	return berp_set_setting_value( 'portal', 'employee_delete_behavior', sanitize_text_field( $value ) );
}

function berp_get_portal_send_welcome_email_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'portal', 'send_welcome_email', 1 ) );
}

function berp_set_portal_send_welcome_email_setting( $value ) {
	return berp_set_setting_value( 'portal', 'send_welcome_email', berp_normalize_int_option( $value ) );
}

function berp_get_portal_welcome_email_subject_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'welcome_email_subject', 'Welcome to the Employee Portal' ) );
}

function berp_set_portal_welcome_email_subject_setting( $value ) {
	return berp_set_setting_value( 'portal', 'welcome_email_subject', sanitize_text_field( $value ) );
}

function berp_get_portal_welcome_email_template_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'portal', 'welcome_email_template', 'Hi {name}, your portal account is ready...' ) );
}

function berp_set_portal_welcome_email_template_setting( $value ) {
	return berp_set_setting_value( 'portal', 'welcome_email_template', sanitize_textarea_field( $value ) );
}

/* =============================
 * Advanced settings
 * ===========================*/

function berp_get_advanced_activity_logging_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'advanced', 'activity_logging', 1 ) );
}

function berp_set_advanced_activity_logging_setting( $value ) {
	return berp_set_setting_value( 'advanced', 'activity_logging', berp_normalize_int_option( $value ) );
}

function berp_get_advanced_data_retention_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'advanced', 'data_retention', 0 ) );
}

function berp_set_advanced_data_retention_setting( $value ) {
	return berp_set_setting_value( 'advanced', 'data_retention', berp_normalize_int_option( $value ) );
}

function berp_get_advanced_uninstall_behavior_setting() {
	return berp_normalize_string_option( berp_get_setting_value( 'advanced', 'uninstall_behavior', 'keep' ) );
}

function berp_set_advanced_uninstall_behavior_setting( $value ) {
	return berp_set_setting_value( 'advanced', 'uninstall_behavior', sanitize_text_field( $value ) );
}

function berp_get_advanced_report_caching_setting() {
	return berp_normalize_int_option( berp_get_setting_value( 'advanced', 'report_caching', 1 ) );
}

function berp_set_advanced_report_caching_setting( $value ) {
	return berp_set_setting_value( 'advanced', 'report_caching', berp_normalize_int_option( $value ) );
}

/* =============================
 * Standalone options
 * ===========================*/

function berp_get_salary_formula_option() {
	return berp_normalize_array_option( get_option( 'berp_salary_formula', array() ) );
}

function berp_set_salary_formula_option( $value ) {
	return update_option( 'berp_salary_formula', berp_normalize_array_option( $value ) );
}

function berp_get_activity_log_option() {
	return berp_normalize_array_option( get_option( 'berp_activity_log', array() ) );
}

function berp_set_activity_log_option( $value ) {
	return update_option( 'berp_activity_log', berp_normalize_array_option( $value ) );
}

function berp_get_version_option() {
	return berp_normalize_string_option( get_option( 'berp_version', '' ) );
}

function berp_set_version_option( $value ) {
	return update_option( 'berp_version', sanitize_text_field( $value ) );
}

function berp_get_delete_data_on_uninstall_option() {
	return berp_normalize_bool_option( get_option( 'berp_delete_data_on_uninstall', false ) );
}

function berp_set_delete_data_on_uninstall_option( $value ) {
	return update_option( 'berp_delete_data_on_uninstall', berp_normalize_bool_option( $value ) );
}

function berp_get_default_terms_created_option() {
	return berp_normalize_bool_option( get_option( 'berp_default_terms_created', true ) );
}

function berp_set_default_terms_created_option( $value ) {
	return update_option( 'berp_default_terms_created', berp_normalize_bool_option( $value ) );
}

function berp_get_project_statuses_created_option() {
	return berp_normalize_int_option( get_option( 'berp_project_statuses_created', 1 ) );
}

function berp_set_project_statuses_created_option( $value ) {
	return update_option( 'berp_project_statuses_created', berp_normalize_int_option( $value ) );
}

function berp_get_expense_categories_created_option() {
	return berp_normalize_int_option( get_option( 'berp_expense_categories_created', 1 ) );
}

function berp_set_expense_categories_created_option( $value ) {
	return update_option( 'berp_expense_categories_created', berp_normalize_int_option( $value ) );
}

function berp_get_departments_created_option() {
	return berp_normalize_int_option( get_option( 'berp_departments_created', 1 ) );
}

function berp_set_departments_created_option( $value ) {
	return update_option( 'berp_departments_created', berp_normalize_int_option( $value ) );
}

/* =============================
 * Legacy options
 * ===========================*/

function berp_get_legacy_general_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_general_settings', array() ) );
}

function berp_set_legacy_general_settings_option( $value ) {
	return update_option( 'berp_general_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_payroll_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_payroll_settings', array() ) );
}

function berp_set_legacy_payroll_settings_option( $value ) {
	return update_option( 'berp_payroll_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_attendance_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_attendance_settings', array() ) );
}

function berp_set_legacy_attendance_settings_option( $value ) {
	return update_option( 'berp_attendance_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_expense_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_expense_settings', array() ) );
}

function berp_set_legacy_expense_settings_option( $value ) {
	return update_option( 'berp_expense_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_portal_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_portal_settings', array() ) );
}

function berp_set_legacy_portal_settings_option( $value ) {
	return update_option( 'berp_portal_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_notification_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_notification_settings', array() ) );
}

function berp_set_legacy_notification_settings_option( $value ) {
	return update_option( 'berp_notification_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_quotation_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_quotation_settings', array() ) );
}

function berp_set_legacy_quotation_settings_option( $value ) {
	return update_option( 'berp_quotation_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_site_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_site_settings', array() ) );
}

function berp_set_legacy_site_settings_option( $value ) {
	return update_option( 'berp_site_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_employee_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_employee_settings', array() ) );
}

function berp_set_legacy_employee_settings_option( $value ) {
	return update_option( 'berp_employee_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_advanced_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_advanced_settings', array() ) );
}

function berp_set_legacy_advanced_settings_option( $value ) {
	return update_option( 'berp_advanced_settings', berp_normalize_array_option( $value ) );
}

function berp_get_legacy_formula_settings_option() {
	return berp_normalize_array_option( get_option( 'berp_formula_settings', array() ) );
}

function berp_set_legacy_formula_settings_option( $value ) {
	return update_option( 'berp_formula_settings', berp_normalize_array_option( $value ) );
}

function berp_get_custom_variables_option() {
	return berp_normalize_array_option( get_option( 'berp_custom_variables', array() ) );
}

function berp_set_custom_variables_option( $value ) {
	return update_option( 'berp_custom_variables', berp_normalize_array_option( $value ) );
}

function berp_get_formula_presets_option() {
	return berp_normalize_array_option( get_option( 'berp_formula_presets', array() ) );
}

function berp_set_formula_presets_option( $value ) {
	return update_option( 'berp_formula_presets', berp_normalize_array_option( $value ) );
}

function berp_get_db_version_option() {
	return berp_normalize_string_option( get_option( 'berp_db_version', '' ) );
}

function berp_set_db_version_option( $value ) {
	return update_option( 'berp_db_version', sanitize_text_field( $value ) );
}

function berp_get_first_install_option() {
	return berp_normalize_string_option( get_option( 'berp_first_install', '' ) );
}

function berp_set_first_install_option( $value ) {
	return update_option( 'berp_first_install', sanitize_text_field( $value ) );
}

function berp_get_dashboard_cache_option() {
	return berp_normalize_array_option( get_option( 'berp_dashboard_cache', array() ) );
}

function berp_set_dashboard_cache_option( $value ) {
	return update_option( 'berp_dashboard_cache', berp_normalize_array_option( $value ) );
}

function berp_get_dashboard_cache_expiry_option() {
	return berp_normalize_int_option( get_option( 'berp_dashboard_cache_expiry', 0 ) );
}

function berp_set_dashboard_cache_expiry_option( $value ) {
	return update_option( 'berp_dashboard_cache_expiry', berp_normalize_int_option( $value ) );
}

function berp_get_last_recurring_check_option() {
	return berp_normalize_string_option( get_option( 'berp_last_recurring_check', '' ) );
}

function berp_set_last_recurring_check_option( $value ) {
	return update_option( 'berp_last_recurring_check', sanitize_text_field( $value ) );
}

function berp_get_import_history_option() {
	return berp_normalize_array_option( get_option( 'berp_import_history', array() ) );
}

function berp_set_import_history_option( $value ) {
	return update_option( 'berp_import_history', berp_normalize_array_option( $value ) );
}

function berp_get_export_history_option() {
	return berp_normalize_array_option( get_option( 'berp_export_history', array() ) );
}

function berp_set_export_history_option( $value ) {
	return update_option( 'berp_export_history', berp_normalize_array_option( $value ) );
}
