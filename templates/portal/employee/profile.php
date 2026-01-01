<?php
/**
 * Employee Profile View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();
$employee    = get_post( $employee_id );

$email     = get_post_meta( $employee_id, '_berp_employee_email', true );
$phone     = get_post_meta( $employee_id, '_berp_employee_phone', true );
$address   = get_post_meta( $employee_id, '_berp_employee_address', true );
$hire_date = get_post_meta( $employee_id, '_berp_hire_date', true );
$status    = get_post_meta( $employee_id, '_berp_employee_status', true );

// Salary information (read-only)
$basic_salary = get_post_meta( $employee_id, '_berp_basic_salary', true );
$allowances   = get_post_meta( $employee_id, '_berp_allowances', true );
$deductions   = get_post_meta( $employee_id, '_berp_deductions', true );

// Normalize arrays
if ( ! is_array( $allowances ) ) {
	$allowances = array();
}
if ( ! is_array( $deductions ) ) {
	$deductions = array();
}

// Calculate totals
$total_allowances = 0;
$total_deductions = 0;

foreach ( $allowances as $allowance ) {
	if ( isset( $allowance['amount'] ) ) {
		$total_allowances += floatval( $allowance['amount'] );
	}
}

foreach ( $deductions as $deduction ) {
	if ( isset( $deduction['amount'] ) ) {
		$total_deductions += floatval( $deduction['amount'] );
	}
}

$gross_salary = floatval( $basic_salary ) + $total_allowances;
$net_salary   = $gross_salary - $total_deductions;
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'My Profile', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<form class="berp-portal-form">
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Full Name', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( $employee->post_title ); ?>" readonly>
				</div>
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Employee ID', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( get_post_meta( $employee_id, '_berp_employee_id', true ) ); ?>" readonly>
				</div>
			</div>

			<div class="berp-form-row">
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Email', 'aic_builderp' ); ?></label>
					<input type="email" class="berp-form-control" value="<?php echo esc_attr( $email ); ?>" readonly>
				</div>
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Phone', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( $phone ); ?>" readonly>
				</div>
			</div>

			<div class="berp-form-group">
				<label><?php esc_html_e( 'Address', 'aic_builderp' ); ?></label>
				<textarea class="berp-form-control" readonly><?php echo esc_textarea( $address ); ?></textarea>
			</div>

			<div class="berp-form-row">
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Hire Date', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( $hire_date ); ?>" readonly>
				</div>
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Status', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( ucfirst( $status ) ); ?>" readonly>
				</div>
			</div>

			<div class="berp-form-actions">
				<p class="berp-text-muted"><?php esc_html_e( 'To update your profile information, please contact HR.', 'aic_builderp' ); ?></p>
			</div>
		</form>
	</div>
</div>

<!-- Salary Information Card -->
<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Salary Information', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<div class="berp-salary-summary">
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Basic Salary', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( berp_format_currency( $basic_salary ) ); ?>" readonly>
				</div>
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Gross Salary', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control" value="<?php echo esc_attr( berp_format_currency( $gross_salary ) ); ?>" readonly>
				</div>
			</div>
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Total Allowances', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control berp-text-success" value="+ <?php echo esc_attr( berp_format_currency( $total_allowances ) ); ?>" readonly>
				</div>
				<div class="berp-form-group berp-col-6">
					<label><?php esc_html_e( 'Total Deductions', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control berp-text-danger" value="- <?php echo esc_attr( berp_format_currency( $total_deductions ) ); ?>" readonly>
				</div>
			</div>
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-12">
					<label><?php esc_html_e( 'Net Salary', 'aic_builderp' ); ?></label>
					<input type="text" class="berp-form-control berp-net-salary" value="<?php echo esc_attr( berp_format_currency( $net_salary ) ); ?>" readonly>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( ! empty( $allowances ) ) : ?>
<!-- Allowances Breakdown Card -->
<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Allowances Breakdown', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<div class="berp-table-responsive">
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Allowance Type', 'aic_builderp' ); ?></th>
						<th class="berp-text-right"><?php esc_html_e( 'Amount', 'aic_builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $allowances as $allowance ) :
						$name   = isset( $allowance['name'] ) ? $allowance['name'] : __( 'Allowance', 'aic_builderp' );
						$amount = isset( $allowance['amount'] ) ? floatval( $allowance['amount'] ) : 0;
						?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td class="berp-text-right berp-text-success">+ <?php echo esc_html( berp_format_currency( $amount ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Total Allowances', 'aic_builderp' ); ?></th>
						<th class="berp-text-right berp-text-success">+ <?php echo esc_html( berp_format_currency( $total_allowances ) ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( ! empty( $deductions ) ) : ?>
<!-- Deductions Breakdown Card -->
<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Deductions Breakdown', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<div class="berp-table-responsive">
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Deduction Type', 'aic_builderp' ); ?></th>
						<th class="berp-text-right"><?php esc_html_e( 'Amount', 'aic_builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $deductions as $deduction ) :
						$name   = isset( $deduction['name'] ) ? $deduction['name'] : __( 'Deduction', 'aic_builderp' );
						$amount = isset( $deduction['amount'] ) ? floatval( $deduction['amount'] ) : 0;
						?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td class="berp-text-right berp-text-danger">- <?php echo esc_html( berp_format_currency( $amount ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Total Deductions', 'aic_builderp' ); ?></th>
						<th class="berp-text-right berp-text-danger">- <?php echo esc_html( berp_format_currency( $total_deductions ) ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
<?php endif; ?>

<div class="berp-salary-notice">
	<p class="berp-text-muted"><em><?php esc_html_e( 'Note: Salary information is for reference only. Actual payment may vary based on attendance, overtime, and other factors. For any queries, please contact HR.', 'aic_builderp' ); ?></em></p>
</div>
