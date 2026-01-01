<?php
/**
 * System Info Tool
 *
 * Display complete plugin inventory and export system report
 *
 * @package    BuildErp
 * @subpackage BuildErp/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BERP_Tools_System_Info {

	/**
	 * Render the system info tool
	 */
	public function render() {
		$info = $this->gather_system_info();
		?>
		<div class="berp-system-info-tool">
			<div class="berp-tool-header">
				<h2><?php esc_html_e( 'System Information', 'aic_builderp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Complete inventory of BuildErp plugin elements and system configuration.', 'aic_builderp' ); ?>
				</p>
			</div>

			<div class="berp-system-actions">
				<button type="button" id="berp-export-system-info" class="button button-secondary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Report', 'aic_builderp' ); ?>
				</button>
				<button type="button" id="berp-copy-system-info" class="button button-secondary">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Copy to Clipboard', 'aic_builderp' ); ?>
				</button>
			</div>

			<div class="berp-system-sections">

				<!-- WordPress Environment -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'WordPress Environment', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<tr>
							<th><?php esc_html_e( 'WordPress Version', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['wordpress']['version'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Site URL', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['wordpress']['site_url'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Home URL', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['wordpress']['home_url'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Language', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['wordpress']['language'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Debug Mode', 'aic_builderp' ); ?></th>
							<td>
								<?php if ( $info['wordpress']['debug_mode'] ) : ?>
									<span class="berp-status enabled"><?php esc_html_e( 'Enabled', 'aic_builderp' ); ?></span>
								<?php else : ?>
									<span class="berp-status disabled"><?php esc_html_e( 'Disabled', 'aic_builderp' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Server Environment -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Server Environment', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<tr>
							<th><?php esc_html_e( 'PHP Version', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['php_version'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'MySQL Version', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['mysql_version'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Server Software', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['server_software'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Memory Limit', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['memory_limit'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Max Execution Time', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['max_execution_time'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Max Upload Size', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['server']['max_upload_size'] ); ?></td>
						</tr>
					</table>
				</div>

				<!-- BuildErp Plugin -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'BuildErp Plugin', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<tr>
							<th><?php esc_html_e( 'Plugin Version', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['plugin']['version'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Plugin Path', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['plugin']['path'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Plugin URL', 'aic_builderp' ); ?></th>
							<td><?php echo esc_html( $info['plugin']['url'] ); ?></td>
						</tr>
					</table>
				</div>

				<!-- Post Types -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Registered Post Types', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<?php foreach ( $info['post_types'] as $post_type => $data ) : ?>
							<tr>
								<th><?php echo esc_html( $post_type ); ?></th>
								<td>
									<span class="berp-badge"><?php echo esc_html( number_format( $data['count'] ) ); ?> <?php esc_html_e( 'items', 'aic_builderp' ); ?></span>
									<?php if ( $data['public'] ) : ?>
										<span class="berp-badge public"><?php esc_html_e( 'Public', 'aic_builderp' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

				<!-- Capabilities -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Custom Capabilities', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<?php foreach ( $info['capabilities'] as $cap => $roles ) : ?>
							<tr>
								<th><?php echo esc_html( $cap ); ?></th>
								<td><?php echo esc_html( implode( ', ', $roles ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

				<!-- User Roles -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Custom User Roles', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<?php foreach ( $info['roles'] as $role_slug => $role_data ) : ?>
							<tr>
								<th><?php echo esc_html( $role_data['name'] ); ?></th>
								<td>
									<span class="berp-badge"><?php echo esc_html( number_format( $role_data['user_count'] ) ); ?> <?php esc_html_e( 'users', 'aic_builderp' ); ?></span>
									<details>
										<summary><?php esc_html_e( 'Capabilities', 'aic_builderp' ); ?></summary>
										<code><?php echo esc_html( implode( ', ', $role_data['capabilities'] ) ); ?></code>
									</details>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

				<!-- Meta Keys -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Meta Keys', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<?php foreach ( $info['meta_keys'] as $meta_key => $count ) : ?>
							<tr>
								<th><code><?php echo esc_html( $meta_key ); ?></code></th>
								<td><span class="berp-badge"><?php echo esc_html( number_format( $count ) ); ?> <?php esc_html_e( 'uses', 'aic_builderp' ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

				<!-- Options -->
				<div class="berp-system-section">
					<h3><?php esc_html_e( 'Plugin Options', 'aic_builderp' ); ?></h3>
					<table class="berp-info-table">
						<?php foreach ( $info['options'] as $option => $value ) : ?>
							<tr>
								<th><code><?php echo esc_html( $option ); ?></code></th>
								<td>
									<?php if ( is_array( $value ) ) : ?>
										<span class="berp-badge"><?php echo esc_html( count( $value ) ); ?> <?php esc_html_e( 'items', 'aic_builderp' ); ?></span>
									<?php else : ?>
										<?php echo esc_html( $value ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>

			</div>
		</div>

		<style>
			.berp-system-info-tool {
				max-width: 1200px;
				margin: 20px 0;
			}

			.berp-system-actions {
				margin-bottom: 20px;
			}

			.berp-system-actions button {
				margin-right: 10px;
			}

			.berp-system-actions button .dashicons {
				vertical-align: middle;
				margin-right: 5px;
			}

			.berp-system-sections {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
				gap: 20px;
			}

			.berp-system-section {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
			}

			.berp-system-section h3 {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 2px solid #0073aa;
				color: #0073aa;
			}

			.berp-info-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 15px;
			}

			.berp-info-table th,
			.berp-info-table td {
				padding: 10px;
				text-align: left;
				border-bottom: 1px solid #eee;
				font-size: 13px;
			}

			.berp-info-table th {
				width: 40%;
				font-weight: 600;
				color: #23282d;
			}

			.berp-info-table td {
				color: #555;
			}

			.berp-info-table code {
				background: #f0f0f1;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 12px;
			}

			.berp-badge {
				display: inline-block;
				background: #f0f0f1;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
				margin-right: 5px;
			}

			.berp-badge.public {
				background: #d5e5ff;
				color: #0073aa;
			}

			.berp-status {
				display: inline-block;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
			}

			.berp-status.enabled {
				background: #d4edda;
				color: #155724;
			}

			.berp-status.disabled {
				background: #f8d7da;
				color: #721c24;
			}

			details {
				margin-top: 5px;
			}

			details summary {
				cursor: pointer;
				color: #0073aa;
				font-size: 12px;
			}

			details summary:hover {
				text-decoration: underline;
			}

			details code {
				display: block;
				margin-top: 5px;
				padding: 10px;
				white-space: pre-wrap;
				word-break: break-word;
			}
		</style>
		<?php
	}

	/**
	 * Gather system information
	 *
	 * @return array System information
	 */
	private function gather_system_info() {
		global $wpdb;

		$info = array(
			'wordpress'    => $this->get_wordpress_info(),
			'server'       => $this->get_server_info(),
			'plugin'       => $this->get_plugin_info(),
			'post_types'   => $this->get_post_types_info(),
			'capabilities' => $this->get_capabilities_info(),
			'roles'        => $this->get_roles_info(),
			'meta_keys'    => $this->get_meta_keys_info(),
			'options'      => $this->get_options_info(),
		);

		return $info;
	}

	/**
	 * Get WordPress information
	 *
	 * @return array WordPress info
	 */
	private function get_wordpress_info() {
		return array(
			'version'    => get_bloginfo( 'version' ),
			'site_url'   => get_site_url(),
			'home_url'   => get_home_url(),
			'language'   => get_locale(),
			'debug_mode' => defined( 'WP_DEBUG' ) && WP_DEBUG,
		);
	}

	/**
	 * Get server information
	 *
	 * @return array Server info
	 */
	private function get_server_info() {
		global $wpdb;

		return array(
			'php_version'        => PHP_VERSION,
			'mysql_version'      => $wpdb->db_version(),
			'server_software'    => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown',
			'memory_limit'       => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ) . 's',
			'max_upload_size'    => size_format( wp_max_upload_size() ),
		);
	}

	/**
	 * Get plugin information
	 *
	 * @return array Plugin info
	 */
	private function get_plugin_info() {
		return array(
			'version' => defined( 'BERP_VERSION' ) ? BERP_VERSION : '1.0.0',
			'path'    => defined( 'BERP_PLUGIN_DIR' ) ? BERP_PLUGIN_DIR : '',
			'url'     => defined( 'BERP_PLUGIN_URL' ) ? BERP_PLUGIN_URL : '',
		);
	}

	/**
	 * Get post types information
	 *
	 * @return array Post types info
	 */
	private function get_post_types_info() {
		$post_types = array(
			'berp_employee',
			'berp_attendance',
			'berp_payroll',
			'berp_expense',
			'berp_client',
			'berp_site',
			'berp_quotation',
			'berp_invoice',
		);

		$info = array();

		foreach ( $post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				$post_type_object = get_post_type_object( $post_type );
				$count            = wp_count_posts( $post_type );

				$info[ $post_type ] = array(
					'count'  => isset( $count->publish ) ? $count->publish : 0,
					'public' => $post_type_object->public,
				);
			}
		}

		return $info;
	}

	/**
	 * Get capabilities information
	 *
	 * @return array Capabilities info
	 */
	private function get_capabilities_info() {
		$capabilities = array(
			'berp_manage_employees',
			'berp_log_attendance',
			'berp_process_payroll',
			'berp_manage_settings',
			'berp_view_own_attendance',
		);

		$info  = array();
		$roles = wp_roles()->roles;

		foreach ( $capabilities as $cap ) {
			$info[ $cap ] = array();

			foreach ( $roles as $role_slug => $role_data ) {
				$role = get_role( $role_slug );
				if ( $role && $role->has_cap( $cap ) ) {
					$info[ $cap ][] = $role_data['name'];
				}
			}
		}

		return $info;
	}

	/**
	 * Get roles information
	 *
	 * @return array Roles info
	 */
	private function get_roles_info() {
		$custom_roles = array( 'berp_employee', 'berp_timekeeper' );
		$info         = array();

		foreach ( $custom_roles as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				$users      = count_users();
				$user_count = isset( $users['avail_roles'][ $role_slug ] ) ? $users['avail_roles'][ $role_slug ] : 0;

				$info[ $role_slug ] = array(
					'name'         => ucwords( str_replace( '_', ' ', $role_slug ) ),
					'user_count'   => $user_count,
					'capabilities' => array_keys( $role->capabilities ),
				);
			}
		}

		return $info;
	}

	/**
	 * Get meta keys information
	 *
	 * @return array Meta keys info
	 */
	private function get_meta_keys_info() {
		global $wpdb;

		$meta_keys = $wpdb->get_results(
			"SELECT meta_key, COUNT(*) as count
             FROM {$wpdb->postmeta}
             WHERE meta_key LIKE '_berp_%' OR meta_key LIKE 'berp_%'
             GROUP BY meta_key
             ORDER BY count DESC"
		);

		$info = array();
		foreach ( $meta_keys as $row ) {
			$info[ $row->meta_key ] = (int) $row->count;
		}

		return $info;
	}

	/**
	 * Get options information
	 *
	 * @return array Options info
	 */
	private function get_options_info() {
		$options = array(
			'berp_version',
			'berp_general_settings',
			'berp_payroll_settings',
			'berp_attendance_settings',
			'berp_portal_settings',
		);

		$info = array();

		foreach ( $options as $option ) {
			$value           = get_option( $option );
			$info[ $option ] = $value !== false ? $value : __( 'Not set', 'aic_builderp' );
		}

		return $info;
	}

	/**
	 * Generate system report
	 *
	 * @return string Report text
	 */
	public function generate_report() {
		$info = $this->gather_system_info();

		$report  = '=== BuildErp System Report ===' . PHP_EOL;
		$report .= 'Generated: ' . current_time( 'Y-m-d H:i:s' ) . PHP_EOL . PHP_EOL;

		$report .= '== WordPress Environment ==' . PHP_EOL;
		foreach ( $info['wordpress'] as $key => $value ) {
			$report .= ucwords( str_replace( '_', ' ', $key ) ) . ': ' . ( is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : $value ) . PHP_EOL;
		}
		$report .= PHP_EOL;

		$report .= '== Server Environment ==' . PHP_EOL;
		foreach ( $info['server'] as $key => $value ) {
			$report .= ucwords( str_replace( '_', ' ', $key ) ) . ': ' . $value . PHP_EOL;
		}
		$report .= PHP_EOL;

		$report .= '== BuildErp Plugin ==' . PHP_EOL;
		foreach ( $info['plugin'] as $key => $value ) {
			$report .= ucwords( str_replace( '_', ' ', $key ) ) . ': ' . $value . PHP_EOL;
		}
		$report .= PHP_EOL;

		$report .= '== Post Types ==' . PHP_EOL;
		foreach ( $info['post_types'] as $post_type => $data ) {
			$report .= $post_type . ': ' . $data['count'] . ' items' . ( $data['public'] ? ' (Public)' : '' ) . PHP_EOL;
		}
		$report .= PHP_EOL;

		$report .= '== Meta Keys ==' . PHP_EOL;
		foreach ( $info['meta_keys'] as $meta_key => $count ) {
			$report .= $meta_key . ': ' . $count . ' uses' . PHP_EOL;
		}

		return $report;
	}
}
