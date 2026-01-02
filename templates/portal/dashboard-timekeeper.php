<?php
/**
 * Timekeeper Dashboard Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats             = BERP_Timekeeper_Dashboard::get_stats();
$recent_attendance = BERP_Timekeeper_Dashboard::get_recent_attendance();
$dashboard_url     = get_permalink();
?>

<div class="berp-portal-dashboard">
	<h2><?php esc_html_e( 'Timekeeper Dashboard', 'builderp' ); ?></h2>

	<div class="berp-portal-stats-grid">
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Active Employees', 'builderp' ); ?></h3>
			<div class="berp-stat-value"><?php echo esc_html( $stats['total_employees'] ); ?></div>
		</div>
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Attendance Logged Today', 'builderp' ); ?></h3>
			<div class="berp-stat-value"><?php echo esc_html( $stats['today_attendance'] ); ?></div>
			<div class="berp-stat-label"><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></div>
		</div>
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Quick Actions', 'builderp' ); ?></h3>
			<div class="berp-quick-actions">
				<a href="<?php echo esc_url( add_query_arg( 'view', 'log-attendance', $dashboard_url ) ); ?>" class="berp-btn berp-btn-primary berp-btn-sm"><?php esc_html_e( 'Log Attendance', 'builderp' ); ?></a>
			</div>
		</div>
	</div>

	<div class="berp-portal-card">
		<div class="berp-card-header">
			<h3><?php esc_html_e( 'Recent Attendance Entries', 'builderp' ); ?></h3>
		</div>
		<div class="berp-card-body">
			<?php if ( $recent_attendance ) : ?>
				<div class="berp-table-responsive">
					<table class="berp-portal-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Employee', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Site', 'builderp' ); ?></th>
								<th><?php esc_html_e( 'Overtime', 'builderp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $recent_attendance as $attendance ) :
								$date        = get_post_meta( $attendance->ID, '_berp_date', true );
								$employee_id = get_post_meta( $attendance->ID, '_berp_employee_id', true );
								$site_id     = get_post_meta( $attendance->ID, '_berp_site_id', true );
								$overtime    = get_post_meta( $attendance->ID, '_berp_overtime_hours', true );

								$employee_name = $employee_id ? get_the_title( $employee_id ) : '-';
								$site_name     = $site_id ? get_the_title( $site_id ) : '-';
								?>
								<tr>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></td>
									<td><?php echo esc_html( $employee_name ); ?></td>
									<td><?php echo esc_html( $site_name ); ?></td>
									<td><?php echo esc_html( $overtime ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No recent attendance found.', 'builderp' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

