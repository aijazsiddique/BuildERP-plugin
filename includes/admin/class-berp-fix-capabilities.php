<?php
/**
 * Temporary capability fix admin page.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles temporary capability fix page.
 */
class BERP_Fix_Capabilities {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 999 );
	}

	/**
	 * Add menu item.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Fix BuildERP Capabilities', 'builderp' ),
			__( 'Fix BuildERP Caps', 'builderp' ),
			'manage_options',
			'berp-fix-capabilities',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'builderp' ) );
		}

		// Handle fix action.
		if ( isset( $_POST['berp_fix_caps'] ) && check_admin_referer( 'berp_fix_caps_action', 'berp_fix_caps_nonce' ) ) {
			$this->fix_capabilities();
			$this->clear_user_cache();
			echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Capabilities fixed successfully! Cache cleared.', 'builderp' ) . '</strong></p></div>';
			echo '<div class="notice notice-info"><p>' . esc_html__( 'Please refresh this page or click the "Open Formula Builder" button below.', 'builderp' ) . '</p></div>';
		}

		// Handle cache clear action.
		if ( isset( $_POST['berp_clear_cache'] ) && check_admin_referer( 'berp_clear_cache_action', 'berp_clear_cache_nonce' ) ) {
			$this->clear_user_cache();
			echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'User cache cleared successfully!', 'builderp' ) . '</strong></p></div>';
			echo '<div class="notice notice-info"><p>' . esc_html__( 'Please try accessing the Formula Builder now.', 'builderp' ) . '</p></div>';
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fix BuildERP Capabilities', 'builderp' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Current Status', 'builderp' ); ?></h2>
				<p><?php esc_html_e( 'Current user:', 'builderp' ); ?> <strong><?php echo esc_html( wp_get_current_user()->user_login ); ?></strong></p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Capability', 'builderp' ); ?></th>
							<th><?php esc_html_e( 'Status', 'builderp' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>manage_options</td>
							<td><?php echo current_user_can( 'manage_options' ) ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
						</tr>
						<tr>
							<td>berp_view_dashboard</td>
							<td><?php echo current_user_can( 'berp_view_dashboard' ) ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
						</tr>
						<tr>
							<td>berp_manage_payroll</td>
							<td><?php echo current_user_can( 'berp_manage_payroll' ) ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
						</tr>
						<tr>
							<td>berp_view_reports</td>
							<td><?php echo current_user_can( 'berp_view_reports' ) ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
						</tr>
						<tr>
							<td>berp_manage_settings</td>
							<td><?php echo current_user_can( 'berp_manage_settings' ) ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Fix Capabilities', 'builderp' ); ?></h2>
				<p><?php esc_html_e( 'Click the button below to add all BuildERP capabilities to the Administrator role.', 'builderp' ); ?></p>

				<form method="post">
					<?php wp_nonce_field( 'berp_fix_caps_action', 'berp_fix_caps_nonce' ); ?>
					<p>
						<button type="submit" name="berp_fix_caps" class="button button-primary button-large">
							<?php esc_html_e( 'Fix All Capabilities Now', 'builderp' ); ?>
						</button>
					</p>
				</form>

				<hr style="margin: 20px 0;">

				<h3><?php esc_html_e( 'Clear User Cache', 'builderp' ); ?></h3>
				<p><?php esc_html_e( 'If you still cannot access the Formula Builder after fixing capabilities, click this button to clear your user cache:', 'builderp' ); ?></p>

				<form method="post">
					<?php wp_nonce_field( 'berp_clear_cache_action', 'berp_clear_cache_nonce' ); ?>
					<p>
						<button type="submit" name="berp_clear_cache" class="button button-secondary">
							<?php esc_html_e( 'Clear User Cache', 'builderp' ); ?>
						</button>
					</p>
				</form>
			</div>

			<?php if ( current_user_can( 'berp_view_dashboard' ) ) : ?>
			<div class="card" style="margin-top: 20px; border-left: 4px solid #00a32a;">
				<h2 style="color: #00a32a;"><?php esc_html_e( 'Ready to Go!', 'builderp' ); ?></h2>
				<p><?php esc_html_e( 'All capabilities are working correctly. You can now:', 'builderp' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=builderp-formula' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Open Formula Builder', 'builderp' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=builderp' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Go to Dashboard', 'builderp' ); ?></a></li>
				</ul>
			</div>
			<?php endif; ?>
		</div>
		<style>
			.card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; }
			.card h2 { margin-top: 0; }
		</style>
		<?php
	}

	/**
	 * Clear user cache to force capability refresh.
	 *
	 * @return void
	 */
	private function clear_user_cache() {
		$user_id = get_current_user_id();

		// Clear WordPress user caches.
		wp_cache_delete( $user_id, 'users' );
		wp_cache_delete( $user_id, 'user_meta' );
		clean_user_cache( $user_id );

		// Force current user object refresh.
		wp_set_current_user( $user_id );

		// Clear all user-related transients.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%_user_' . $user_id . '_%'
			)
		);
	}

	/**
	 * Fix capabilities by adding them to administrator role.
	 *
	 * @return void
	 */
	private function fix_capabilities() {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role ) {
			return;
		}

		$capabilities = array(
			'berp_view_dashboard',
			'berp_manage_payroll',
			'berp_process_payroll',
			'berp_view_payroll',
			'berp_edit_payroll',
			'berp_delete_payroll',
			'berp_view_reports',
			'berp_manage_settings',
			'berp_manage_employees',
			'berp_view_employees',
			'berp_edit_employees',
			'berp_delete_employees',
			'berp_manage_attendance',
			'berp_log_attendance',
			'berp_edit_attendance',
			'berp_view_attendance',
			'berp_delete_attendance',
			'berp_manage_expenses',
			'berp_view_expenses',
			'berp_edit_expenses',
			'berp_delete_expenses',
			'berp_manage_clients',
			'berp_view_clients',
			'berp_edit_clients',
			'berp_delete_clients',
			'berp_manage_sites',
			'berp_view_sites',
			'berp_edit_sites',
			'berp_delete_sites',
			'berp_manage_quotations',
			'berp_view_quotations',
			'berp_edit_quotations',
			'berp_delete_quotations',
			'berp_manage_invoices',
			'berp_view_invoices',
			'berp_edit_invoices',
			'berp_delete_invoices',
		);

		foreach ( $capabilities as $cap ) {
			$admin_role->add_cap( $cap );
		}
	}
}

