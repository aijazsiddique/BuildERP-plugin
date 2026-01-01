<?php
/**
 * User meta helper functions.
 *
 * @package BuildERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function berp_user_normalize_array( $value ) {
	return is_array( $value ) ? $value : array();
}

function berp_user_normalize_int( $value ) {
	return (int) $value;
}

function berp_user_normalize_string( $value ) {
	return is_string( $value ) ? $value : '';
}

function berp_get_user_employee_id_meta( $user_id ) {
	return berp_user_normalize_int( get_user_meta( $user_id, '_berp_employee_id', true ) );
}

function berp_set_user_employee_id_meta( $user_id, $employee_id ) {
	return update_user_meta( $user_id, '_berp_employee_id', berp_user_normalize_int( $employee_id ) );
}

function berp_get_user_last_site_meta( $user_id ) {
	return berp_user_normalize_int( get_user_meta( $user_id, '_berp_last_site', true ) );
}

function berp_set_user_last_site_meta( $user_id, $site_id ) {
	return update_user_meta( $user_id, '_berp_last_site', berp_user_normalize_int( $site_id ) );
}

function berp_get_user_legacy_employee_id_meta( $user_id ) {
	return berp_user_normalize_int( get_user_meta( $user_id, 'berp_employee_id', true ) );
}

function berp_set_user_legacy_employee_id_meta( $user_id, $employee_id ) {
	return update_user_meta( $user_id, 'berp_employee_id', berp_user_normalize_int( $employee_id ) );
}

function berp_get_user_portal_preferences_meta( $user_id ) {
	return berp_user_normalize_array( get_user_meta( $user_id, 'berp_portal_preferences', true ) );
}

function berp_set_user_portal_preferences_meta( $user_id, $preferences ) {
	return update_user_meta( $user_id, 'berp_portal_preferences', berp_user_normalize_array( $preferences ) );
}

function berp_get_user_dashboard_layout_meta( $user_id ) {
	return berp_user_normalize_array( get_user_meta( $user_id, 'berp_dashboard_layout', true ) );
}

function berp_set_user_dashboard_layout_meta( $user_id, $layout ) {
	return update_user_meta( $user_id, 'berp_dashboard_layout', berp_user_normalize_array( $layout ) );
}

function berp_get_user_last_attendance_meta( $user_id ) {
	return berp_user_normalize_int( get_user_meta( $user_id, 'berp_last_attendance', true ) );
}

function berp_set_user_last_attendance_meta( $user_id, $timestamp ) {
	return update_user_meta( $user_id, 'berp_last_attendance', berp_user_normalize_int( $timestamp ) );
}

function berp_get_user_notification_preferences_meta( $user_id ) {
	return berp_user_normalize_array( get_user_meta( $user_id, 'berp_notification_preferences', true ) );
}

function berp_set_user_notification_preferences_meta( $user_id, $preferences ) {
	return update_user_meta( $user_id, 'berp_notification_preferences', berp_user_normalize_array( $preferences ) );
}
