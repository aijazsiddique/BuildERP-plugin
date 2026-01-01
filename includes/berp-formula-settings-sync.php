<?php
/**
 * Formula Builder - Settings Integration
 * Syncs formula variables with payroll settings
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get formula variable defaults from payroll settings.
 * This ensures formula builder always uses current settings values.
 *
 * @return array Default values for formula variables based on settings.
 */
function berp_get_formula_defaults_from_settings() {
	$payroll_settings    = berp_get_payroll_settings();
	$attendance_settings = berp_get_attendance_settings();

	// Working days per month from settings (check both new and legacy keys).
	$working_days = 26;
	if ( isset( $payroll_settings['working_days'] ) ) {
		$working_days = absint( $payroll_settings['working_days'] );
	} elseif ( isset( $payroll_settings['working_days_per_month'] ) ) {
		$working_days = absint( $payroll_settings['working_days_per_month'] );
	}

	// Calculate weekend days in a typical month.
	$weekend_days_setting = isset( $attendance_settings['weekend_days'] ) && is_array( $attendance_settings['weekend_days'] )
		? $attendance_settings['weekend_days']
		: array( 'saturday', 'sunday' );

	// Typical month has ~4.3 weeks, so weekends = count(weekend_days) * 4.
	$weekends_per_month = count( $weekend_days_setting ) * 4;

	// Overtime rate from attendance settings (default_multiplier is the current key).
	$overtime_multiplier = isset( $attendance_settings['default_multiplier'] )
		? floatval( $attendance_settings['default_multiplier'] )
		: 1.5;

	// Days in month (typically 30).
	$days_in_month = 30;

	// Calculate total paid days (working days + holidays, sample).
	$sample_holidays      = 1; // Sample value.
	$sample_present_days  = $working_days - 1; // Sample: 1 day absent.
	$sample_paid_weekends = 4; // Sample: 4 weekend days paid.
	$sample_total_paid    = $sample_present_days + $sample_paid_weekends + $sample_holidays;

	return array(
		'working_days'        => $working_days,
		'days_in_month'       => $days_in_month,
		'weekends_per_month'  => $weekends_per_month,
		'overtime_multiplier' => $overtime_multiplier,
		'weekend_days_count'  => count( $weekend_days_setting ),
		'sample_holidays'     => $sample_holidays,
		'sample_present'      => $sample_present_days,
		'sample_weekends'     => $sample_paid_weekends,
		'sample_total_paid'   => $sample_total_paid,
	);
}

/**
 * Get updated formula configuration with settings-synced DEFAULTS only.
 * Called by formula builder to ensure DEFAULT values reflect current settings.
 * IMPORTANT: This function ONLY updates 'default' column, NOT 'sample' column.
 * User-saved sample values are preserved for testing purposes.
 *
 * @return array Formula config with synced defaults (sample values untouched).
 */
function berp_get_formula_config_with_settings() {
	$config   = berp_get_salary_formula_config();
	$defaults = berp_get_formula_defaults_from_settings();

	// Update variable defaults in active formula.
	if ( isset( $config['active']['variables'] ) && is_array( $config['active']['variables'] ) ) {
		foreach ( $config['active']['variables'] as &$var ) {
			switch ( $var['key'] ) {
				case 'working_days':
					$var['default'] = $defaults['working_days'];
					break;

				case 'days_in_month':
					$var['default'] = $defaults['days_in_month'];
					break;

				case 'present_days':
					$var['default'] = $defaults['sample_present'];
					break;

				case 'weekends':
					$var['default'] = $defaults['sample_weekends'];
					break;

				case 'holidays':
					$var['default'] = $defaults['sample_holidays'];
					break;

				case 'total_paid_days':
					$var['default'] = $defaults['sample_total_paid'];
					break;

				case 'overtime_rate':
					// Calculate default overtime rate: assume daily rate * multiplier.
					// Default: basic_salary 1200 / 26 days = 46.15 per day, * 1.5 = 69.23 / 8 hours = ~8.65.
					$sample_basic   = 1200;
					$daily_rate     = $sample_basic / $defaults['working_days'];
					$overtime_rate  = ( $daily_rate * $defaults['overtime_multiplier'] ) / 8; // Per hour.
					$var['default'] = round( $overtime_rate, 2 );
					break;
			}
		}
	}

	return $config;
}
