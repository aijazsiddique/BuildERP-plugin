<?php
/**
 * Payroll Register Report Template
 *
 * @package BuildERP
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rows   = isset( $report_data['rows'] ) ? $report_data['rows'] : array();
$totals = isset( $report_data['totals'] ) ? $report_data['totals'] : array();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Payroll Register Report', 'builderp' ); ?></h1>
	<p class="description"><?php esc_html_e( 'View payroll totals for a selected month and export to CSV or PDF.', 'builderp' ); ?></p>

	<form method="get" class="berp-register-filters">
		<input type="hidden" name="post_type" value="berp_payroll">
		<input type="hidden" name="page" value="berp-payroll-register">

		<label for="payroll_month">
			<?php esc_html_e( 'Payroll Month', 'builderp' ); ?>
			<input type="month" name="payroll_month" id="payroll_month" value="<?php echo esc_attr( $selected_month ); ?>">
		</label>

		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Generate Report', 'builderp' ); ?>
		</button>

		<?php if ( ! empty( $rows ) ) : ?>
			<a class="button" href="<?php echo esc_url( add_query_arg( array( 'export' => 'csv' ) ) ); ?>">
				<?php esc_html_e( 'Export CSV', 'builderp' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( add_query_arg( array( 'export' => 'pdf' ) ) ); ?>">
				<?php esc_html_e( 'Export PDF', 'builderp' ); ?>
			</a>
		<?php endif; ?>
	</form>

	<div class="berp-register-meta">
		<strong><?php esc_html_e( 'Selected Month:', 'builderp' ); ?></strong>
		<?php echo esc_html( $selected_month ? gmdate( 'F Y', strtotime( $selected_month . '-01' ) ) : __( 'All Months', 'builderp' ) ); ?>
	</div>

	<?php if ( empty( $rows ) ) : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'No payroll records found for the selected month.', 'builderp' ); ?></p></div>
	<?php else : ?>
		<table class="widefat striped berp-register-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Employee Name', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Employee ID', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Basic Salary', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Allowances', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Present Days', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'OT Hours', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'OT Amount', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Gross Salary', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Deductions', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Net Salary', 'builderp' ); ?></th>
					<th><?php esc_html_e( 'Status', 'builderp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['employee_name'] ); ?></td>
						<td><?php echo esc_html( $row['employee_code'] ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['basic_salary'] ) ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['total_allowances'] ) ); ?></td>
						<td><?php echo esc_html( $row['present_days'] ); ?></td>
						<td><?php echo esc_html( number_format( (float) $row['overtime_hours'], 2 ) ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['overtime_amount'] ) ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['gross_salary'] ) ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['total_deductions'] ) ); ?></td>
						<td><?php echo esc_html( berp_format_currency( $row['net_salary'] ) ); ?></td>
						<td>
							<?php if ( 'paid' === $row['status'] ) : ?>
								<span class="berp-badge berp-badge-success"><?php esc_html_e( 'Paid', 'builderp' ); ?></span>
							<?php else : ?>
								<span class="berp-badge berp-badge-warning"><?php esc_html_e( 'Pending', 'builderp' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Totals', 'builderp' ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['basic_salary'] ) ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['total_allowances'] ) ); ?></th>
					<th><?php echo esc_html( $totals['present_days'] ); ?></th>
					<th><?php echo esc_html( number_format( (float) $totals['overtime_hours'], 2 ) ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['overtime_amount'] ) ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['gross_salary'] ) ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['total_deductions'] ) ); ?></th>
					<th><?php echo esc_html( berp_format_currency( $totals['net_salary'] ) ); ?></th>
					<th></th>
				</tr>
			</tfoot>
		</table>
	<?php endif; ?>
</div>

