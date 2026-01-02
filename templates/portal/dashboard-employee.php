<?php
/**
 * Employee Dashboard Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();

if ( ! $employee_id ) {
	echo '<div class="berp-portal-alert berp-portal-alert-danger">' . esc_html__( 'Employee record not found.', 'builderp' ) . '</div>';
	return;
}

$stats             = BERP_Employee_Dashboard::get_stats( $employee_id );
$recent_attendance = BERP_Employee_Dashboard::get_recent_attendance( $employee_id );
$latest_salary     = BERP_Employee_Dashboard::get_latest_salary_slip( $employee_id );
?>

<div class="berp-portal-dashboard">
	<h2><?php esc_html_e( 'Dashboard', 'builderp' ); ?></h2>

	<div class="berp-portal-stats-grid">
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Present Days', 'builderp' ); ?></h3>
			<div class="berp-stat-value"><?php echo esc_html( $stats['present_days'] ); ?></div>
			<div class="berp-stat-label"><?php esc_html_e( 'This Month', 'builderp' ); ?></div>
		</div>
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Overtime Hours', 'builderp' ); ?></h3>
			<div class="berp-stat-value"><?php echo esc_html( $stats['overtime_hours'] ); ?></div>
			<div class="berp-stat-label"><?php esc_html_e( 'This Month', 'builderp' ); ?></div>
		</div>
		<div class="berp-portal-stat-card">
			<h3><?php esc_html_e( 'Account Balance', 'builderp' ); ?></h3>
			<div class="berp-stat-value"><?php echo esc_html( berp_format_currency( $stats['balance'] ) ); ?></div>
			<div class="berp-stat-label"><?php esc_html_e( 'Current Balance', 'builderp' ); ?></div>
		</div>
	</div>

	<div class="berp-portal-row">
		<div class="berp-portal-col">
			<div class="berp-portal-card">
				<div class="berp-card-header">
					<h3><?php esc_html_e( 'Recent Attendance', 'builderp' ); ?></h3>
				</div>
				<div class="berp-card-body">
					<?php if ( $recent_attendance ) : ?>
						<table class="berp-portal-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'builderp' ); ?></th>
									<th><?php esc_html_e( 'Site', 'builderp' ); ?></th>
									<th><?php esc_html_e( 'Overtime', 'builderp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $recent_attendance as $attendance ) :
									$date      = get_post_meta( $attendance->ID, '_berp_date', true );
									$site_id   = get_post_meta( $attendance->ID, '_berp_site_id', true );
									$overtime  = get_post_meta( $attendance->ID, '_berp_overtime_hours', true );
									$site_name = $site_id ? get_the_title( $site_id ) : '-';
									?>
									<tr>
										<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></td>
										<td><?php echo esc_html( $site_name ); ?></td>
										<td><?php echo esc_html( $overtime ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'No recent attendance found.', 'builderp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="berp-portal-col">
			<div class="berp-portal-card">
				<div class="berp-card-header">
					<h3><?php esc_html_e( 'Latest Salary Slip', 'builderp' ); ?></h3>
				</div>
				<div class="berp-card-body">
					<?php
					if ( $latest_salary ) :
						$month      = get_post_meta( $latest_salary->ID, '_berp_month', true );
						$net_salary = get_post_meta( $latest_salary->ID, '_berp_net_salary', true );
						?>
						<div class="berp-salary-summary">
							<div class="berp-salary-month">
								<strong><?php esc_html_e( 'Month:', 'builderp' ); ?></strong>
								<?php echo esc_html( date_i18n( 'F Y', strtotime( $month . '-01' ) ) ); ?>
							</div>
							<div class="berp-salary-amount">
								<strong><?php esc_html_e( 'Net Salary:', 'builderp' ); ?></strong>
								<?php echo esc_html( berp_format_currency( $net_salary ) ); ?>
							</div>
							<div class="berp-salary-action">
								<!-- Link to download PDF would go here -->
								<button class="berp-btn berp-btn-sm berp-btn-outline"><?php esc_html_e( 'Download PDF', 'builderp' ); ?></button>
							</div>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No salary slips found.', 'builderp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

