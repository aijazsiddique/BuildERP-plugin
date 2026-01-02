<?php
/**
 * Employee Account Statement View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();
$balance     = get_post_meta( $employee_id, '_berp_account_balance', true );
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Account Statement', 'BuildERP' ); ?></h3>
		<div class="berp-header-actions">
			<span class="berp-balance-label"><?php esc_html_e( 'Current Balance:', 'BuildERP' ); ?></span>
			<span class="berp-balance-amount"><?php echo esc_html( berp_format_currency( $balance ) ); ?></span>
		</div>
	</div>
	<div class="berp-card-body">
		<p><?php esc_html_e( 'Transaction history feature coming soon.', 'BuildERP' ); ?></p>
	</div>
</div>

