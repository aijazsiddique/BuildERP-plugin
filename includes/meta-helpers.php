<?php
/**
 * Post meta helper functions.
 *
 * @package BuildERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize array meta values.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function berp_normalize_array_meta( $value ) {
	return is_array( $value ) ? $value : array();
}

/**
 * Normalize booleanish meta to int 0/1.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function berp_normalize_flag_meta( $value ) {
	return (int) (bool) $value;
}

/**
 * Normalize float meta.
 *
 * @param mixed $value Raw value.
 * @return float
 */
function berp_normalize_float_meta( $value ) {
	return (float) $value;
}

/**
 * Normalize int meta.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function berp_normalize_int_meta( $value ) {
	return (int) $value;
}

/**
 * Normalize string meta.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function berp_normalize_string_meta( $value ) {
	return is_string( $value ) ? $value : '';
}

/* =============================
 * Employee meta helpers
 * ===========================*/

function berp_get_employee_employee_id_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_id', true ) );
}

function berp_set_employee_employee_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_id', sanitize_text_field( $value ) );
}

function berp_get_employee_employee_email_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_email', true ) );
}

function berp_set_employee_employee_email_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_email', sanitize_email( $value ) );
}

function berp_get_employee_employee_phone_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_phone', true ) );
}

function berp_set_employee_employee_phone_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_phone', sanitize_text_field( $value ) );
}

function berp_get_employee_employee_address_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_address', true ) );
}

function berp_set_employee_employee_address_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_address', sanitize_text_field( $value ) );
}

function berp_get_employee_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_status', true ) );
}

function berp_set_employee_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_status', sanitize_text_field( $value ) );
}

function berp_get_employee_hire_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_hire_date', true ) );
}

function berp_set_employee_hire_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_hire_date', sanitize_text_field( $value ) );
}

function berp_get_employee_basic_salary_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_basic_salary', true ) );
}

function berp_set_employee_basic_salary_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_basic_salary', berp_normalize_float_meta( $value ) );
}

function berp_get_employee_allowances_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_allowances', true ) );
}

function berp_set_employee_allowances_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_allowances', berp_normalize_array_meta( $value ) );
}

function berp_get_employee_deductions_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_deductions', true ) );
}

function berp_set_employee_deductions_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_deductions', berp_normalize_array_meta( $value ) );
}

function berp_get_employee_account_balance_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_account_balance', true ) );
}

function berp_set_employee_account_balance_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_account_balance', berp_normalize_float_meta( $value ) );
}

function berp_get_employee_documents_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_documents', true ) );
}

function berp_set_employee_documents_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_documents', berp_normalize_array_meta( $value ) );
}

function berp_get_employee_portal_access_enabled_meta( $post_id ) {
	return berp_normalize_flag_meta( get_post_meta( $post_id, '_berp_portal_access_enabled', true ) );
}

function berp_set_employee_portal_access_enabled_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_portal_access_enabled', berp_normalize_flag_meta( $value ) );
}

function berp_get_employee_linked_user_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_linked_user_id', true ) );
}

function berp_set_employee_linked_user_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_linked_user_id', berp_normalize_int_meta( $value ) );
}

function berp_get_employee_soft_deleted_meta( $post_id ) {
	return berp_normalize_flag_meta( get_post_meta( $post_id, '_berp_soft_deleted', true ) );
}

function berp_set_employee_soft_deleted_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_soft_deleted', berp_normalize_flag_meta( $value ) );
}

function berp_get_employee_weekend_payable_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_weekend_payable', true ) );
}

function berp_set_employee_weekend_payable_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_weekend_payable', berp_normalize_int_meta( $value ) );
}

function berp_get_employee_email_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_email', true ) );
}

function berp_set_employee_email_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_email', sanitize_email( $value ) );
}

function berp_get_employee_phone_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_phone', true ) );
}

function berp_set_employee_phone_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_phone', sanitize_text_field( $value ) );
}

function berp_get_employee_address_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_employee_address', true ) );
}

function berp_set_employee_address_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_address', sanitize_text_field( $value ) );
}

/* =============================
 * Attendance meta helpers
 * ===========================*/

function berp_get_attendance_employee_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_employee_id', true ) );
}

function berp_set_attendance_employee_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_employee_id', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_date', true ) );
}

function berp_set_attendance_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_date', sanitize_text_field( $value ) );
}

function berp_get_attendance_site_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_site_id', true ) );
}

function berp_set_attendance_site_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_id', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_overtime_hours_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_overtime_hours', true ) );
}

function berp_set_attendance_overtime_hours_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_overtime_hours', berp_normalize_float_meta( $value ) );
}

function berp_get_attendance_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_notes', true ) );
}

function berp_set_attendance_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_notes', sanitize_text_field( $value ) );
}

function berp_get_attendance_logged_by_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_logged_by', true ) );
}

function berp_set_attendance_logged_by_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_logged_by', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_logged_at_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_logged_at', true ) );
}

function berp_set_attendance_logged_at_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_logged_at', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_is_weekend_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_is_weekend', true ) );
}

function berp_set_attendance_is_weekend_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_is_weekend', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_is_holiday_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_is_holiday', true ) );
}

function berp_set_attendance_is_holiday_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_is_holiday', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_weekend_payable_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_weekend_payable', true ) );
}

function berp_set_attendance_weekend_payable_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_weekend_payable', berp_normalize_int_meta( $value ) );
}

function berp_get_attendance_attendance_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_date', true ) );
}

function berp_set_attendance_attendance_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_date', sanitize_text_field( $value ) );
}

/* =============================
 * Payroll meta helpers
 * ===========================*/

function berp_get_payroll_payroll_employee_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_payroll_employee_id', true ) );
}

function berp_set_payroll_payroll_employee_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_employee_id', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_payroll_month_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payroll_month', true ) );
}

function berp_set_payroll_payroll_month_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_month', sanitize_text_field( $value ) );
}

function berp_get_payroll_calculation_mode_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_calculation_mode', true ) );
}

function berp_set_payroll_calculation_mode_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_calculation_mode', sanitize_text_field( $value ) );
}

function berp_get_payroll_payroll_basic_salary_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_payroll_basic_salary', true ) );
}

function berp_set_payroll_payroll_basic_salary_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_basic_salary', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_payroll_allowances_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_payroll_allowances', true ) );
}

function berp_set_payroll_payroll_allowances_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_allowances', berp_normalize_array_meta( $value ) );
}

function berp_get_payroll_payroll_deductions_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_payroll_deductions', true ) );
}

function berp_set_payroll_payroll_deductions_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_deductions', berp_normalize_array_meta( $value ) );
}

function berp_get_payroll_present_days_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_present_days', true ) );
}

function berp_set_payroll_present_days_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_present_days', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_paid_weekends_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_paid_weekends', true ) );
}

function berp_set_payroll_paid_weekends_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_paid_weekends', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_holidays_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_holidays', true ) );
}

function berp_set_payroll_holidays_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_holidays', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_total_paid_days_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_total_paid_days', true ) );
}

function berp_set_payroll_total_paid_days_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_total_paid_days', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_overtime_hours_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_overtime_hours', true ) );
}

function berp_set_payroll_overtime_hours_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_overtime_hours', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_overtime_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_overtime_amount', true ) );
}

function berp_set_payroll_overtime_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_overtime_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_gross_salary_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_gross_salary', true ) );
}

function berp_set_payroll_gross_salary_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_gross_salary', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_net_salary_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_net_salary', true ) );
}

function berp_set_payroll_net_salary_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_net_salary', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payroll_status', true ) );
}

function berp_set_payroll_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_status', sanitize_text_field( $value ) );
}

function berp_get_payroll_paid_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_paid_date', true ) );
}

function berp_set_payroll_paid_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_paid_date', sanitize_text_field( $value ) );
}

function berp_get_payroll_linked_expense_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_linked_expense_id', true ) );
}

function berp_set_payroll_linked_expense_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_linked_expense_id', berp_normalize_int_meta( $value ) );
}

function berp_get_payroll_formula_used_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_formula_used', true ) );
}

function berp_set_payroll_formula_used_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_formula_used', sanitize_text_field( $value ) );
}

function berp_get_payroll_payroll_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payroll_notes', true ) );
}

function berp_set_payroll_payroll_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payroll_notes', sanitize_text_field( $value ) );
}

function berp_get_payroll_email_sent_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_email_sent', true ) );
}

function berp_set_payroll_email_sent_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_email_sent', sanitize_text_field( $value ) );
}

function berp_get_payroll_email_sent_to_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_email_sent_to', true ) );
}

function berp_set_payroll_email_sent_to_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_email_sent_to', sanitize_text_field( $value ) );
}

function berp_get_payroll_attendance_details_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_attendance_details', true ) );
}

function berp_set_payroll_attendance_details_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_attendance_details', berp_normalize_array_meta( $value ) );
}

function berp_get_payroll_total_allowances_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_total_allowances', true ) );
}

function berp_set_payroll_total_allowances_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_total_allowances', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_total_deductions_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_total_deductions', true ) );
}

function berp_set_payroll_total_deductions_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_total_deductions', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_month_meta_value( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_month', true ) );
}

function berp_set_payroll_month_meta_value( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_month', sanitize_text_field( $value ) );
}

function berp_get_payroll_total_allowance_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_total_allowances', true ) );
}

function berp_set_payroll_total_allowance_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_total_allowances', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_total_deduction_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_total_deductions', true ) );
}

function berp_set_payroll_total_deduction_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_total_deductions', berp_normalize_float_meta( $value ) );
}

function berp_get_payroll_payment_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payment_status', true ) );
}

function berp_set_payroll_payment_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payment_status', sanitize_text_field( $value ) );
}

/* =============================
 * Expense meta helpers
 * ===========================*/

function berp_get_expense_expense_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_expense_date', true ) );
}

function berp_set_expense_expense_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_expense_date', sanitize_text_field( $value ) );
}

function berp_get_expense_expense_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_expense_amount', true ) );
}

function berp_set_expense_expense_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_expense_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_expense_expense_category_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_expense_category', true ) );
}

function berp_set_expense_expense_category_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_expense_category', sanitize_text_field( $value ) );
}

function berp_get_expense_site_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_site_id', true ) );
}

function berp_set_expense_site_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_id', berp_normalize_int_meta( $value ) );
}

function berp_get_expense_payment_method_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payment_method', true ) );
}

function berp_set_expense_payment_method_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payment_method', sanitize_text_field( $value ) );
}

function berp_get_expense_expense_description_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_expense_description', true ) );
}

function berp_set_expense_expense_description_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_expense_description', sanitize_text_field( $value ) );
}

function berp_get_expense_expense_receipts_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_expense_receipts', true ) );
}

function berp_set_expense_expense_receipts_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_expense_receipts', berp_normalize_array_meta( $value ) );
}

function berp_get_expense_linked_payroll_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_linked_payroll_id', true ) );
}

function berp_set_expense_linked_payroll_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_linked_payroll_id', berp_normalize_int_meta( $value ) );
}

function berp_get_expense_linked_employee_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_linked_employee_id', true ) );
}

function berp_set_expense_linked_employee_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_linked_employee_id', berp_normalize_int_meta( $value ) );
}

function berp_get_expense_is_recurring_meta( $post_id ) {
	return berp_normalize_flag_meta( get_post_meta( $post_id, '_berp_is_recurring', true ) );
}

function berp_set_expense_is_recurring_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_is_recurring', berp_normalize_flag_meta( $value ) );
}

function berp_get_expense_recurring_interval_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_recurring_interval', true ) );
}

function berp_set_expense_recurring_interval_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_recurring_interval', sanitize_text_field( $value ) );
}

function berp_get_expense_recurring_next_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_recurring_next_date', true ) );
}

function berp_set_expense_recurring_next_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_recurring_next_date', sanitize_text_field( $value ) );
}

function berp_get_expense_recurring_end_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_recurring_end_date', true ) );
}

function berp_set_expense_recurring_end_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_recurring_end_date', sanitize_text_field( $value ) );
}

function berp_get_expense_recurring_parent_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_recurring_parent_id', true ) );
}

function berp_set_expense_recurring_parent_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_recurring_parent_id', berp_normalize_int_meta( $value ) );
}

function berp_get_expense_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_amount', true ) );
}

function berp_set_expense_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_amount', berp_normalize_float_meta( $value ) );
}

/* =============================
 * Client meta helpers
 * ===========================*/

function berp_get_client_company_name_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_company_name', true ) );
}

function berp_set_client_company_name_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_company_name', sanitize_text_field( $value ) );
}

function berp_get_client_contact_person_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_contact_person', true ) );
}

function berp_set_client_contact_person_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_contact_person', sanitize_text_field( $value ) );
}

function berp_get_client_client_email_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_email', true ) );
}

function berp_set_client_client_email_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_email', sanitize_email( $value ) );
}

function berp_get_client_client_phone_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_phone', true ) );
}

function berp_set_client_client_phone_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_phone', sanitize_text_field( $value ) );
}

function berp_get_client_client_address_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_address', true ) );
}

function berp_set_client_client_address_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_address', sanitize_text_field( $value ) );
}

function berp_get_client_client_city_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_city', true ) );
}

function berp_set_client_client_city_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_city', sanitize_text_field( $value ) );
}

function berp_get_client_client_state_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_state', true ) );
}

function berp_set_client_client_state_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_state', sanitize_text_field( $value ) );
}

function berp_get_client_client_zip_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_zip', true ) );
}

function berp_set_client_client_zip_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_zip', sanitize_text_field( $value ) );
}

function berp_get_client_client_country_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_country', true ) );
}

function berp_set_client_client_country_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_country', sanitize_text_field( $value ) );
}

function berp_get_client_registration_number_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_registration_number', true ) );
}

function berp_set_client_registration_number_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_registration_number', sanitize_text_field( $value ) );
}

function berp_get_client_tax_id_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_tax_id', true ) );
}

function berp_set_client_tax_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_id', sanitize_text_field( $value ) );
}

function berp_get_client_client_website_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_website', true ) );
}

function berp_set_client_client_website_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_website', esc_url_raw( $value ) );
}

function berp_get_client_client_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_client_notes', true ) );
}

function berp_set_client_client_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_notes', sanitize_text_field( $value ) );
}

function berp_get_client_client_documents_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_client_documents', true ) );
}

function berp_set_client_client_documents_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_documents', berp_normalize_array_meta( $value ) );
}

function berp_get_client_client_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_client_id', true ) );
}

function berp_set_client_client_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_id', berp_normalize_int_meta( $value ) );
}

/* =============================
 * Site meta helpers
 * ===========================*/

function berp_get_site_client_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_client_id', true ) );
}

function berp_set_site_client_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_id', berp_normalize_int_meta( $value ) );
}

function berp_get_site_site_address_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_address', true ) );
}

function berp_set_site_site_address_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_address', sanitize_text_field( $value ) );
}

function berp_get_site_site_city_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_city', true ) );
}

function berp_set_site_site_city_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_city', sanitize_text_field( $value ) );
}

function berp_get_site_site_state_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_state', true ) );
}

function berp_set_site_site_state_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_state', sanitize_text_field( $value ) );
}

function berp_get_site_site_zip_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_zip', true ) );
}

function berp_set_site_site_zip_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_zip', sanitize_text_field( $value ) );
}

function berp_get_site_start_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_start_date', true ) );
}

function berp_set_site_start_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_start_date', sanitize_text_field( $value ) );
}

function berp_get_site_end_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_end_date', true ) );
}

function berp_set_site_end_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_end_date', sanitize_text_field( $value ) );
}

function berp_get_site_site_description_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_description', true ) );
}

function berp_set_site_site_description_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_description', sanitize_text_field( $value ) );
}

function berp_get_site_site_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_site_status', true ) );
}

function berp_set_site_site_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_status', sanitize_text_field( $value ) );
}

function berp_get_site_project_manager_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_project_manager', true ) );
}

function berp_set_site_project_manager_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_project_manager', berp_normalize_int_meta( $value ) );
}

function berp_get_site_project_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_project_notes', true ) );
}

function berp_set_site_project_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_project_notes', sanitize_text_field( $value ) );
}

function berp_get_site_budget_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_budget', true ) );
}

function berp_set_site_budget_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget', berp_normalize_float_meta( $value ) );
}

function berp_get_site_budget_spent_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_budget_spent', true ) );
}

function berp_set_site_budget_spent_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget_spent', berp_normalize_float_meta( $value ) );
}

function berp_get_site_budget_alert_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_budget_alert', true ) );
}

function berp_set_site_budget_alert_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget_alert', berp_normalize_int_meta( $value ) );
}

function berp_get_site_budget_used_percentage_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_budget_used_percentage', true ) );
}

function berp_set_site_budget_used_percentage_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget_used_percentage', berp_normalize_float_meta( $value ) );
}

function berp_get_site_budget_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_budget', true ) );
}

function berp_set_site_budget_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget', berp_normalize_float_meta( $value ) );
}

function berp_get_site_quotation_source_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_quotation_source', true ) );
}

function berp_set_site_quotation_source_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_quotation_source', berp_normalize_int_meta( $value ) );
}

function berp_get_site_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_status', true ) );
}

function berp_set_site_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_status', sanitize_text_field( $value ) );
}

function berp_get_site_site_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_site_id', true ) );
}

function berp_set_site_site_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_id', berp_normalize_int_meta( $value ) );
}

/* =============================
 * Quotation meta helpers
 * ===========================*/

function berp_get_quotation_quotation_number_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_quotation_number', true ) );
}

function berp_set_quotation_quotation_number_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_quotation_number', sanitize_text_field( $value ) );
}

function berp_get_quotation_client_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_client_id', true ) );
}

function berp_set_quotation_client_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_id', berp_normalize_int_meta( $value ) );
}

function berp_get_quotation_quotation_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_quotation_date', true ) );
}

function berp_set_quotation_quotation_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_quotation_date', sanitize_text_field( $value ) );
}

function berp_get_quotation_validity_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_validity_date', true ) );
}

function berp_set_quotation_validity_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_validity_date', sanitize_text_field( $value ) );
}

function berp_get_quotation_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_status', true ) );
}

function berp_set_quotation_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_status', sanitize_text_field( $value ) );
}

function berp_get_quotation_line_items_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_line_items', true ) );
}

function berp_set_quotation_line_items_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_line_items', berp_normalize_array_meta( $value ) );
}

function berp_get_quotation_tax_rate_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_tax_rate', true ) );
}

function berp_set_quotation_tax_rate_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_rate', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_tax_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_tax_amount', true ) );
}

function berp_set_quotation_tax_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_discount_type_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_discount_type', true ) );
}

function berp_set_quotation_discount_type_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_type', sanitize_text_field( $value ) );
}

function berp_get_quotation_discount_value_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_discount_value', true ) );
}

function berp_set_quotation_discount_value_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_value', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_discount_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_discount_amount', true ) );
}

function berp_set_quotation_discount_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_subtotal_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_subtotal', true ) );
}

function berp_set_quotation_subtotal_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_subtotal', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_grand_total_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_grand_total', true ) );
}

function berp_set_quotation_grand_total_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_grand_total', berp_normalize_float_meta( $value ) );
}

function berp_get_quotation_payment_terms_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payment_terms', true ) );
}

function berp_set_quotation_payment_terms_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payment_terms', sanitize_text_field( $value ) );
}

function berp_get_quotation_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_notes', true ) );
}

function berp_set_quotation_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_notes', sanitize_text_field( $value ) );
}

function berp_get_quotation_attachments_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_attachments', true ) );
}

function berp_set_quotation_attachments_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_attachments', berp_normalize_array_meta( $value ) );
}

function berp_get_quotation_converted_to_site_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_converted_to_site', true ) );
}

function berp_set_quotation_converted_to_site_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_converted_to_site', berp_normalize_int_meta( $value ) );
}

function berp_get_quotation_converted_to_invoices_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_converted_to_invoices', true ) );
}

function berp_set_quotation_converted_to_invoices_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_converted_to_invoices', berp_normalize_array_meta( $value ) );
}

function berp_get_quotation_sent_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_sent_date', true ) );
}

function berp_set_quotation_sent_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_sent_date', sanitize_text_field( $value ) );
}

function berp_get_quotation_sent_count_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_sent_count', true ) );
}

function berp_set_quotation_sent_count_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_sent_count', berp_normalize_int_meta( $value ) );
}

function berp_get_quotation_invoice_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_invoice_id', true ) );
}

function berp_set_quotation_invoice_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_invoice_id', berp_normalize_int_meta( $value ) );
}

/* =============================
 * Invoice meta helpers
 * ===========================*/

function berp_get_invoice_invoice_number_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_invoice_number', true ) );
}

function berp_set_invoice_invoice_number_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_invoice_number', sanitize_text_field( $value ) );
}

function berp_get_invoice_client_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_client_id', true ) );
}

function berp_set_invoice_client_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_id', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_site_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_site_id', true ) );
}

function berp_set_invoice_site_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_id', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_quotation_id_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_quotation_id', true ) );
}

function berp_set_invoice_quotation_id_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_quotation_id', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_quotation_source_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_quotation_source', true ) );
}

function berp_set_invoice_quotation_source_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_quotation_source', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_invoice_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_invoice_date', true ) );
}

function berp_set_invoice_invoice_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_invoice_date', sanitize_text_field( $value ) );
}

function berp_get_invoice_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_date', true ) );
}

function berp_set_invoice_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_date', sanitize_text_field( $value ) );
}

function berp_get_invoice_due_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_due_date', true ) );
}

function berp_set_invoice_due_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_due_date', sanitize_text_field( $value ) );
}

function berp_get_invoice_status_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_status', true ) );
}

function berp_set_invoice_status_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_status', sanitize_text_field( $value ) );
}

function berp_get_invoice_line_items_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_line_items', true ) );
}

function berp_set_invoice_line_items_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_line_items', berp_normalize_array_meta( $value ) );
}

function berp_get_invoice_tax_rate_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_tax_rate', true ) );
}

function berp_set_invoice_tax_rate_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_rate', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_tax_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_tax_amount', true ) );
}

function berp_set_invoice_tax_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_tax_total_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_tax_total', true ) );
}

function berp_set_invoice_tax_total_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_tax_total', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_discount_type_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_discount_type', true ) );
}

function berp_set_invoice_discount_type_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_type', sanitize_text_field( $value ) );
}

function berp_get_invoice_discount_value_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_discount_value', true ) );
}

function berp_set_invoice_discount_value_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_value', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_discount_amount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_discount_amount', true ) );
}

function berp_set_invoice_discount_amount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount_amount', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_discount_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_discount', true ) );
}

function berp_set_invoice_discount_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_discount', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_subtotal_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_subtotal', true ) );
}

function berp_set_invoice_subtotal_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_subtotal', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_grand_total_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_grand_total', true ) );
}

function berp_set_invoice_grand_total_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_grand_total', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_payment_terms_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_payment_terms', true ) );
}

function berp_set_invoice_payment_terms_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payment_terms', sanitize_text_field( $value ) );
}

function berp_get_invoice_notes_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_notes', true ) );
}

function berp_set_invoice_notes_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_notes', sanitize_text_field( $value ) );
}

function berp_get_invoice_payments_meta( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_payments', true ) );
}

function berp_set_invoice_payments_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_payments', berp_normalize_array_meta( $value ) );
}

function berp_get_invoice_amount_paid_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_amount_paid', true ) );
}

function berp_set_invoice_amount_paid_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_amount_paid', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_amount_due_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_amount_due', true ) );
}

function berp_set_invoice_amount_due_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_amount_due', berp_normalize_float_meta( $value ) );
}

function berp_get_invoice_paid_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_paid_date', true ) );
}

function berp_set_invoice_paid_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_paid_date', sanitize_text_field( $value ) );
}

function berp_get_invoice_sent_date_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_sent_date', true ) );
}

function berp_set_invoice_sent_date_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_sent_date', sanitize_text_field( $value ) );
}

function berp_get_invoice_sent_count_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_sent_count', true ) );
}

function berp_set_invoice_sent_count_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_sent_count', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_reference_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_reference', true ) );
}

function berp_set_invoice_reference_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_reference', sanitize_text_field( $value ) );
}

function berp_get_invoice_po_number_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_po_number', true ) );
}

function berp_set_invoice_po_number_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_po_number', sanitize_text_field( $value ) );
}

function berp_get_invoice_milestone_index_meta( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_milestone_index', true ) );
}

function berp_set_invoice_milestone_index_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_milestone_index', berp_normalize_int_meta( $value ) );
}

function berp_get_invoice_milestone_name_meta( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_milestone_name', true ) );
}

function berp_set_invoice_milestone_name_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_milestone_name', sanitize_text_field( $value ) );
}

function berp_get_invoice_milestone_percentage_meta( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_milestone_percentage', true ) );
}

function berp_set_invoice_milestone_percentage_meta( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_milestone_percentage', berp_normalize_float_meta( $value ) );
}

/* =============================
 * Generic meta helper aliases
 * Short-form helpers for common meta keys
 * ===========================*/

/**
 * Get email meta (_berp_email).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function berp_get_email( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_email', true ) );
}

/**
 * Set email meta (_berp_email).
 *
 * @param int    $post_id Post ID.
 * @param string $value   Email value.
 * @return int|bool
 */
function berp_set_email( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_email', sanitize_email( $value ) );
}

/**
 * Get phone meta (_berp_phone).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function berp_get_phone( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_phone', true ) );
}

/**
 * Set phone meta (_berp_phone).
 *
 * @param int    $post_id Post ID.
 * @param string $value   Phone value.
 * @return int|bool
 */
function berp_set_phone( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_phone', sanitize_text_field( $value ) );
}

/**
 * Get basic salary meta (_berp_basic_salary).
 *
 * @param int $post_id Post ID.
 * @return float
 */
function berp_get_basic_salary( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_basic_salary', true ) );
}

/**
 * Set basic salary meta (_berp_basic_salary).
 *
 * @param int   $post_id Post ID.
 * @param float $value   Salary value.
 * @return int|bool
 */
function berp_set_basic_salary( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_basic_salary', berp_normalize_float_meta( $value ) );
}

/**
 * Get allowances meta (_berp_allowances).
 *
 * @param int $post_id Post ID.
 * @return array
 */
function berp_get_allowances( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_allowances', true ) );
}

/**
 * Set allowances meta (_berp_allowances).
 *
 * @param int   $post_id Post ID.
 * @param array $value   Allowances array.
 * @return int|bool
 */
function berp_set_allowances( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_allowances', berp_normalize_array_meta( $value ) );
}

/**
 * Get deductions meta (_berp_deductions).
 *
 * @param int $post_id Post ID.
 * @return array
 */
function berp_get_deductions( $post_id ) {
	return berp_normalize_array_meta( get_post_meta( $post_id, '_berp_deductions', true ) );
}

/**
 * Set deductions meta (_berp_deductions).
 *
 * @param int   $post_id Post ID.
 * @param array $value   Deductions array.
 * @return int|bool
 */
function berp_set_deductions( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_deductions', berp_normalize_array_meta( $value ) );
}

/**
 * Get status meta (_berp_status).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function berp_get_status( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_status', true ) );
}

/**
 * Set status meta (_berp_status).
 *
 * @param int    $post_id Post ID.
 * @param string $value   Status value.
 * @return int|bool
 */
function berp_set_status( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_status', sanitize_text_field( $value ) );
}

/**
 * Get date meta (_berp_date).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function berp_get_date( $post_id ) {
	return berp_normalize_string_meta( get_post_meta( $post_id, '_berp_date', true ) );
}

/**
 * Set date meta (_berp_date).
 *
 * @param int    $post_id Post ID.
 * @param string $value   Date value (YYYY-MM-DD).
 * @return int|bool
 */
function berp_set_date( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_date', sanitize_text_field( $value ) );
}

/**
 * Get site ID meta (_berp_site_id).
 *
 * @param int $post_id Post ID.
 * @return int
 */
function berp_get_site_id( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_site_id', true ) );
}

/**
 * Set site ID meta (_berp_site_id).
 *
 * @param int $post_id Post ID.
 * @param int $value   Site ID value.
 * @return int|bool
 */
function berp_set_site_id( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_site_id', berp_normalize_int_meta( $value ) );
}

/**
 * Get overtime hours meta (_berp_overtime_hours).
 *
 * @param int $post_id Post ID.
 * @return float
 */
function berp_get_overtime_hours( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_overtime_hours', true ) );
}

/**
 * Set overtime hours meta (_berp_overtime_hours).
 *
 * @param int   $post_id Post ID.
 * @param float $value   Overtime hours value.
 * @return int|bool
 */
function berp_set_overtime_hours( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_overtime_hours', berp_normalize_float_meta( $value ) );
}

/**
 * Get client ID meta (_berp_client_id).
 *
 * @param int $post_id Post ID.
 * @return int
 */
function berp_get_client_id( $post_id ) {
	return berp_normalize_int_meta( get_post_meta( $post_id, '_berp_client_id', true ) );
}

/**
 * Set client ID meta (_berp_client_id).
 *
 * @param int $post_id Post ID.
 * @param int $value   Client ID value.
 * @return int|bool
 */
function berp_set_client_id( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_client_id', berp_normalize_int_meta( $value ) );
}

/**
 * Get budget meta (_berp_budget).
 *
 * @param int $post_id Post ID.
 * @return float
 */
function berp_get_budget( $post_id ) {
	return berp_normalize_float_meta( get_post_meta( $post_id, '_berp_budget', true ) );
}

/**
 * Set budget meta (_berp_budget).
 *
 * @param int   $post_id Post ID.
 * @param float $value   Budget value.
 * @return int|bool
 */
function berp_set_budget( $post_id, $value ) {
	return update_post_meta( $post_id, '_berp_budget', berp_normalize_float_meta( $value ) );
}
