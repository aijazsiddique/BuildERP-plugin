<?php
/**
 * Schema Validator Tool
 *
 * Validates plugin schema compliance and provides quick fixes
 *
 * @package    BuildErp
 * @subpackage BuildErp/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BERP_Tools_Validator {

	/**
	 * Render the validator tool
	 */
	public function render() {
		?>
		<div class="berp-validator-tool">
			<div class="berp-tool-header">
				<h2><?php esc_html_e( 'Schema Validator', 'BuildERP' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Validate your BuildErp schema for compliance and consistency. Identify issues and apply quick fixes.', 'BuildERP' ); ?>
				</p>
			</div>

			<div class="berp-validator-actions">
				<button type="button" id="berp-run-validator" class="button button-primary">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Run Validation', 'BuildERP' ); ?>
				</button>
			</div>

			<div id="berp-validator-results" class="berp-validator-results" style="display: none;">
				<div class="berp-compliance-score">
					<h3><?php esc_html_e( 'Compliance Score', 'BuildERP' ); ?></h3>
					<div class="berp-score-meter">
						<div class="berp-score-bar" data-score="0">
							<span class="berp-score-text">0%</span>
						</div>
					</div>
					<p class="berp-score-description"></p>
				</div>

				<div class="berp-validation-issues">
					<h3><?php esc_html_e( 'Validation Issues', 'BuildERP' ); ?></h3>
					<div id="berp-issues-list"></div>
				</div>
			</div>

			<div id="berp-validator-loading" class="berp-loading-spinner" style="display: none;">
				<span class="spinner is-active"></span>
				<p><?php esc_html_e( 'Running validation...', 'BuildERP' ); ?></p>
			</div>
		</div>

		<style>
			.berp-validator-tool {
				max-width: 1200px;
				margin: 20px 0;
			}

			.berp-tool-header {
				margin-bottom: 20px;
			}

			.berp-validator-actions {
				margin-bottom: 30px;
			}

			.berp-validator-actions button .dashicons {
				vertical-align: middle;
				margin-right: 5px;
			}

			.berp-compliance-score {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-bottom: 20px;
			}

			.berp-score-meter {
				background: #e0e0e0;
				height: 40px;
				border-radius: 20px;
				overflow: hidden;
				position: relative;
				margin: 15px 0;
			}

			.berp-score-bar {
				height: 100%;
				transition: width 0.5s ease, background-color 0.5s ease;
				display: flex;
				align-items: center;
				justify-content: center;
				position: relative;
			}

			.berp-score-bar[data-score="100"] {
				background: linear-gradient(90deg, #46b450 0%, #55d461 100%);
			}

			.berp-score-bar[data-score="90"],
			.berp-score-bar[data-score="80"],
			.berp-score-bar[data-score="70"] {
				background: linear-gradient(90deg, #ffb900 0%, #ffc632 100%);
			}

			.berp-score-bar[data-score="60"],
			.berp-score-bar[data-score="50"],
			.berp-score-bar[data-score="40"],
			.berp-score-bar[data-score="30"],
			.berp-score-bar[data-score="20"],
			.berp-score-bar[data-score="10"],
			.berp-score-bar[data-score="0"] {
				background: linear-gradient(90deg, #dc3232 0%, #e84444 100%);
			}

			.berp-score-text {
				color: #fff;
				font-weight: bold;
				font-size: 16px;
				text-shadow: 0 1px 2px rgba(0,0,0,0.3);
			}

			.berp-score-description {
				margin-top: 10px;
				color: #666;
				font-size: 14px;
			}

			.berp-validation-issues {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
			}

			.berp-issue-item {
				border-left: 4px solid #dc3232;
				background: #fff;
				padding: 15px;
				margin-bottom: 10px;
				border-radius: 0 4px 4px 0;
			}

			.berp-issue-item.warning {
				border-left-color: #ffb900;
			}

			.berp-issue-item.info {
				border-left-color: #00a0d2;
			}

			.berp-issue-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}

			.berp-issue-title {
				font-weight: 600;
				font-size: 14px;
				color: #23282d;
			}

			.berp-issue-severity {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
			}

			.berp-issue-severity.error {
				background: #dc3232;
				color: #fff;
			}

			.berp-issue-severity.warning {
				background: #ffb900;
				color: #000;
			}

			.berp-issue-severity.info {
				background: #00a0d2;
				color: #fff;
			}

			.berp-issue-description {
				color: #555;
				font-size: 13px;
				margin-bottom: 10px;
				line-height: 1.5;
			}

			.berp-issue-actions {
				margin-top: 10px;
			}

			.berp-quick-fix-btn {
				font-size: 12px;
			}

			.berp-loading-spinner {
				text-align: center;
				padding: 40px;
			}

			.berp-loading-spinner .spinner {
				float: none;
				margin: 0 auto;
			}

			.berp-no-issues {
				text-align: center;
				padding: 40px;
				color: #46b450;
			}

			.berp-no-issues .dashicons {
				font-size: 48px;
				width: 48px;
				height: 48px;
				margin-bottom: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Run validation
	 *
	 * @return array Validation results
	 */
	public function run_validation() {
		$issues        = array();
		$total_checks  = 0;
		$passed_checks = 0;

		// Check 1: Validate meta key naming convention
		$meta_key_issues = $this->validate_meta_keys();
		$issues          = array_merge( $issues, $meta_key_issues );
		$total_checks   += count( $this->get_all_meta_keys() );
		$passed_checks  += count( $this->get_all_meta_keys() ) - count( $meta_key_issues );

		// Check 2: Validate function naming convention
		$function_issues = $this->validate_functions();
		$issues          = array_merge( $issues, $function_issues );
		$total_checks   += 10; // Approximate
		$passed_checks  += 10 - count( $function_issues );

		// Check 3: Validate post type registrations
		$post_type_issues = $this->validate_post_types();
		$issues           = array_merge( $issues, $post_type_issues );
		$total_checks    += 8; // We have 8 post types
		$passed_checks   += 8 - count( $post_type_issues );

		// Check 4: Validate capability assignments
		$capability_issues = $this->validate_capabilities();
		$issues            = array_merge( $issues, $capability_issues );
		$total_checks     += 5;
		$passed_checks    += 5 - count( $capability_issues );

		// Check 5: Validate helper functions exist
		$helper_issues  = $this->validate_helper_functions();
		$issues         = array_merge( $issues, $helper_issues );
		$total_checks  += count( $this->get_all_meta_keys() );
		$passed_checks += count( $this->get_all_meta_keys() ) - count( $helper_issues );

		// Calculate compliance score
		$score = $total_checks > 0 ? round( ( $passed_checks / $total_checks ) * 100 ) : 100;

		return array(
			'score'         => $score,
			'total_checks'  => $total_checks,
			'passed_checks' => $passed_checks,
			'issues'        => $issues,
			'summary'       => $this->get_score_summary( $score ),
		);
	}

	/**
	 * Validate meta key naming conventions
	 *
	 * @return array Issues found
	 */
	private function validate_meta_keys() {
		global $wpdb;
		$issues = array();

		// Get all BuildErp meta keys from database
		$meta_keys = $wpdb->get_col(
			"SELECT DISTINCT meta_key FROM {$wpdb->postmeta}
             WHERE meta_key LIKE '_berp_%' OR meta_key LIKE 'berp_%'"
		);

		foreach ( $meta_keys as $meta_key ) {
			// Check if meta key follows naming convention
			if ( ! preg_match( '/^_berp_[a-z_]+$/', $meta_key ) ) {
				$issues[] = array(
					'type'        => 'meta_key_naming',
					'severity'    => 'warning',
					/* translators: %s: meta key */
					'title'       => sprintf( __( 'Invalid meta key naming: %s', 'BuildERP' ), $meta_key ),
					'description' => __( 'Meta keys should follow the pattern: _berp_lowercase_with_underscores', 'BuildERP' ),
					'data'        => array( 'meta_key' => $meta_key ),
					'fixable'     => false,
				);
			}
		}

		return $issues;
	}

	/**
	 * Validate function naming conventions
	 *
	 * @return array Issues found
	 */
	private function validate_functions() {
		$issues = array();

		// Get all defined functions
		$functions      = get_defined_functions();
		$user_functions = $functions['user'];

		foreach ( $user_functions as $function ) {
			// Check if it's a BuildErp function
			if ( strpos( $function, 'berp_' ) === 0 ) {
				// Check naming convention
				if ( ! preg_match( '/^berp_[a-z_]+$/', $function ) ) {
					$issues[] = array(
						'type'        => 'function_naming',
						'severity'    => 'warning',
						/* translators: %s: function name */
						'title'       => sprintf( __( 'Invalid function naming: %s', 'BuildERP' ), $function ),
						'description' => __( 'Functions should follow the pattern: berp_lowercase_with_underscores', 'BuildERP' ),
						'data'        => array( 'function' => $function ),
						'fixable'     => false,
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Validate post type registrations
	 *
	 * @return array Issues found
	 */
	private function validate_post_types() {
		$issues = array();

		$expected_post_types = array(
			'berp_employee',
			'berp_attendance',
			'berp_payroll',
			'berp_expense',
			'berp_client',
			'berp_site',
			'berp_quotation',
			'berp_invoice',
		);

		foreach ( $expected_post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				$issues[] = array(
					'type'        => 'missing_post_type',
					'severity'    => 'error',
					/* translators: %s: post type */
					'title'       => sprintf( __( 'Missing post type: %s', 'BuildERP' ), $post_type ),
					'description' => __( 'Required post type is not registered', 'BuildERP' ),
					'data'        => array( 'post_type' => $post_type ),
					'fixable'     => false,
				);
			}
		}

		return $issues;
	}

	/**
	 * Validate capability assignments
	 *
	 * @return array Issues found
	 */
	private function validate_capabilities() {
		$issues = array();

		$expected_capabilities = array(
			'berp_manage_employees',
			'berp_log_attendance',
			'berp_process_payroll',
			'berp_manage_settings',
		);

		// Get administrator role
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			foreach ( $expected_capabilities as $cap ) {
				if ( ! $admin_role->has_cap( $cap ) ) {
					$issues[] = array(
						'type'        => 'missing_capability',
						'severity'    => 'error',
						/* translators: %s: capability name */
						'title'       => sprintf( __( 'Missing capability: %s', 'BuildERP' ), $cap ),
						'description' => __( 'Required capability is not assigned to administrator role', 'BuildERP' ),
						'data'        => array( 'capability' => $cap ),
						'fixable'     => true,
					);
				}
			}
		}

		return $issues;
	}

	/**
	 * Validate helper functions exist
	 *
	 * @return array Issues found
	 */
	private function validate_helper_functions() {
		$issues = array();

		$meta_keys = $this->get_all_meta_keys();

		foreach ( $meta_keys as $meta_key ) {
			// Generate expected getter function name
			$function_name = 'berp_get_' . str_replace( '_berp_', '', $meta_key );

			if ( ! function_exists( $function_name ) ) {
				$issues[] = array(
					'type'        => 'missing_helper',
					'severity'    => 'info',
					/* translators: %s: function name */
					'title'       => sprintf( __( 'Missing helper function: %s', 'BuildERP' ), $function_name ),
					/* translators: %s: meta key */
					'description' => sprintf( __( 'Consider creating a helper function for meta key %s', 'BuildERP' ), $meta_key ),
					'data'        => array(
						'meta_key' => $meta_key,
						'function' => $function_name,
					),
					'fixable'     => false,
				);
			}
		}

		return $issues;
	}

	/**
	 * Get all meta keys
	 *
	 * @return array Meta keys
	 */
	private function get_all_meta_keys() {
		return array(
			'_berp_employee_id',
			'_berp_email',
			'_berp_phone',
			'_berp_basic_salary',
			'_berp_allowances',
			'_berp_deductions',
			'_berp_status',
			'_berp_date',
			'_berp_site_id',
			'_berp_overtime_hours',
			'_berp_client_id',
			'_berp_budget',
		);
	}

	/**
	 * Get score summary
	 *
	 * @param int $score Compliance score
	 * @return string Summary message
	 */
	private function get_score_summary( $score ) {
		if ( $score >= 90 ) {
			return __( 'Excellent! Your BuildErp schema is highly compliant.', 'BuildERP' );
		} elseif ( $score >= 70 ) {
			return __( 'Good! Minor issues detected. Review warnings below.', 'BuildERP' );
		} elseif ( $score >= 50 ) {
			return __( 'Fair. Several issues need attention.', 'BuildERP' );
		} else {
			return __( 'Poor. Critical issues detected. Immediate action required.', 'BuildERP' );
		}
	}

	/**
	 * Apply quick fix
	 *
	 * @param string $issue_type Issue type
	 * @param array  $issue_data Issue data
	 * @return bool Success status
	 */
	public function apply_quick_fix( $issue_type, $issue_data ) {
		switch ( $issue_type ) {
			case 'missing_capability':
				return $this->fix_missing_capability( $issue_data );
			default:
				return false;
		}
	}

	/**
	 * Fix missing capability
	 *
	 * @param array $issue_data Issue data
	 * @return bool Success status
	 */
	private function fix_missing_capability( $issue_data ) {
		if ( empty( $issue_data['capability'] ) ) {
			return false;
		}

		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( $issue_data['capability'] );
			return true;
		}

		return false;
	}
}

