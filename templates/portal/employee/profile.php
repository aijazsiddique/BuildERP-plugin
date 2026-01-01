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
$employee = get_post( $employee_id );

$email = get_post_meta( $employee_id, '_berp_employee_email', true );
$phone = get_post_meta( $employee_id, '_berp_employee_phone', true );
$address = get_post_meta( $employee_id, '_berp_employee_address', true );
$hire_date = get_post_meta( $employee_id, '_berp_hire_date', true );
$status = get_post_meta( $employee_id, '_berp_employee_status', true );
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
