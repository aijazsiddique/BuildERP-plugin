<?php
/**
 * Employee Portal - Request Advance Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();

if ( ! $employee_id ) {
	echo '<div class="berp-portal-alert berp-portal-alert-danger">' . esc_html__( 'Employee record not found.', 'BuildERP' ) . '</div>';
	return;
}

// Get employee salary info.
$basic_salary       = (float) get_post_meta( $employee_id, '_berp_basic_salary', true );
$max_percent        = berp_get_employee_advance_max_percent_setting();
$max_advance        = $basic_salary ? ( $basic_salary * $max_percent ) / 100 : 0;
$allow_installments = berp_get_employee_allow_installments_setting();
$account_balance    = (float) get_post_meta( $employee_id, '_berp_account_balance', true );

// Check for pending requests.
$pending_requests = get_posts(
	array(
		'post_type'      => 'berp_advance',
		'posts_per_page' => 1,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'   => '_berp_employee_id',
				'value' => $employee_id,
			),
			array(
				'key'   => '_berp_advance_status',
				'value' => 'pending',
			),
		),
	)
);
$has_pending      = ! empty( $pending_requests );

// Get recent advance history.
$advance_history = get_posts(
	array(
		'post_type'      => 'berp_advance',
		'posts_per_page' => 10,
		'meta_key'       => '_berp_request_date',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'   => '_berp_employee_id',
				'value' => $employee_id,
			),
		),
	)
);
?>

<div class="berp-portal-advances">
	<h2><?php esc_html_e( 'Request Salary Advance', 'BuildERP' ); ?></h2>

	<div class="berp-portal-row">
		<!-- Request Form Column -->
		<div class="berp-portal-col berp-portal-col-6">
			<div class="berp-portal-card">
				<div class="berp-card-header">
					<h3><?php esc_html_e( 'New Advance Request', 'BuildERP' ); ?></h3>
				</div>
				<div class="berp-card-body">
					<?php if ( $has_pending ) : ?>
						<div class="berp-portal-alert berp-portal-alert-warning">
							<strong><?php esc_html_e( 'Pending Request', 'BuildERP' ); ?></strong><br>
							<?php esc_html_e( 'You have a pending advance request. Please wait for it to be processed before submitting a new one.', 'BuildERP' ); ?>
						</div>
					<?php elseif ( ! $basic_salary ) : ?>
						<div class="berp-portal-alert berp-portal-alert-danger">
							<?php esc_html_e( 'Your salary information is not set. Please contact HR.', 'BuildERP' ); ?>
						</div>
					<?php else : ?>
						<div class="berp-portal-info-box">
							<p>
								<strong><?php esc_html_e( 'Maximum Advance:', 'BuildERP' ); ?></strong>
								<?php echo esc_html( berp_format_currency( $max_advance ) ); ?>
								<small>(<?php echo esc_html( $max_percent ); ?>% <?php esc_html_e( 'of basic salary', 'BuildERP' ); ?>)</small>
							</p>
							<p>
								<strong><?php esc_html_e( 'Current Balance:', 'BuildERP' ); ?></strong>
								<?php
								if ( $account_balance < 0 ) {
									echo '<span class="berp-text-danger">' . esc_html( berp_format_currency( $account_balance ) ) . '</span>';
									echo ' <small>(' . esc_html__( 'You owe', 'BuildERP' ) . ')</small>';
								} else {
									echo esc_html( berp_format_currency( $account_balance ) );
								}
								?>
							</p>
						</div>

						<form id="berp-advance-request-form" class="berp-portal-form">
							<?php wp_nonce_field( 'wp_rest', 'berp_advance_nonce' ); ?>

							<div class="berp-form-group">
								<label for="advance_amount"><?php esc_html_e( 'Amount Requested', 'BuildERP' ); ?> <span class="required">*</span></label>
								<input type="number" 
									id="advance_amount" 
									name="amount" 
									class="berp-form-control" 
									step="0.01" 
									min="1" 
									max="<?php echo esc_attr( $max_advance ); ?>" 
									required 
								/>
								<small class="berp-form-help">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: maximum amount */
											__( 'Enter amount up to %s', 'BuildERP' ),
											berp_format_currency( $max_advance )
										)
									);
									?>
								</small>
							</div>

							<?php if ( $allow_installments ) : ?>
								<div class="berp-form-group">
									<label for="repayment_type"><?php esc_html_e( 'Repayment Type', 'BuildERP' ); ?></label>
									<select id="repayment_type" name="repayment_type" class="berp-form-control">
										<option value="full"><?php esc_html_e( 'Full Deduction (Next Payroll)', 'BuildERP' ); ?></option>
										<option value="installments"><?php esc_html_e( 'Installments', 'BuildERP' ); ?></option>
									</select>
								</div>

								<div class="berp-form-group berp-installments-field" style="display: none;">
									<label for="installments"><?php esc_html_e( 'Number of Installments', 'BuildERP' ); ?></label>
									<select id="installments" name="installments" class="berp-form-control">
										<?php for ( $i = 2; $i <= 12; $i++ ) : ?>
											<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></option>
										<?php endfor; ?>
									</select>
									<small class="berp-form-help"><?php esc_html_e( 'Amount will be deducted equally over selected number of payroll periods.', 'BuildERP' ); ?></small>
								</div>
							<?php endif; ?>

							<div class="berp-form-group">
								<label for="advance_reason"><?php esc_html_e( 'Reason for Request', 'BuildERP' ); ?></label>
								<textarea id="advance_reason" name="reason" class="berp-form-control" rows="3" placeholder="<?php esc_attr_e( 'Please explain why you need this advance...', 'BuildERP' ); ?>"></textarea>
							</div>

							<div class="berp-form-group">
								<button type="submit" class="berp-btn berp-btn-primary berp-btn-block">
									<span class="berp-btn-text"><?php esc_html_e( 'Submit Request', 'BuildERP' ); ?></span>
									<span class="berp-btn-loading" style="display: none;">
										<span class="berp-spinner"></span>
										<?php esc_html_e( 'Submitting...', 'BuildERP' ); ?>
									</span>
								</button>
							</div>

							<div id="berp-advance-message" class="berp-portal-alert" style="display: none;"></div>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- History Column -->
		<div class="berp-portal-col berp-portal-col-6">
			<div class="berp-portal-card">
				<div class="berp-card-header">
					<h3><?php esc_html_e( 'Request History', 'BuildERP' ); ?></h3>
				</div>
				<div class="berp-card-body">
					<?php if ( ! empty( $advance_history ) ) : ?>
						<table class="berp-portal-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date', 'BuildERP' ); ?></th>
									<th><?php esc_html_e( 'Amount', 'BuildERP' ); ?></th>
									<th><?php esc_html_e( 'Status', 'BuildERP' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $advance_history as $advance ) :
									$request_date = get_post_meta( $advance->ID, '_berp_request_date', true );
									$amount       = get_post_meta( $advance->ID, '_berp_advance_amount', true );
									$status       = get_post_meta( $advance->ID, '_berp_advance_status', true );

									$status_labels = array(
										'pending'  => array(
											'label' => __( 'Pending', 'BuildERP' ),
											'class' => 'berp-badge-warning',
										),
										'approved' => array(
											'label' => __( 'Approved', 'BuildERP' ),
											'class' => 'berp-badge-success',
										),
										'rejected' => array(
											'label' => __( 'Rejected', 'BuildERP' ),
											'class' => 'berp-badge-danger',
										),
										'paid'     => array(
											'label' => __( 'Paid', 'BuildERP' ),
											'class' => 'berp-badge-info',
										),
									);
									$status_data   = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status_labels['pending'];
									?>
									<tr>
										<td><?php echo esc_html( berp_format_date( $request_date ) ); ?></td>
										<td><?php echo esc_html( berp_format_currency( $amount ) ); ?></td>
										<td>
											<span class="berp-badge <?php echo esc_attr( $status_data['class'] ); ?>">
												<?php echo esc_html( $status_data['label'] ); ?>
											</span>
											<?php
											if ( 'rejected' === $status ) :
												$rejection_note = get_post_meta( $advance->ID, '_berp_rejection_note', true );
												if ( $rejection_note ) :
													?>
												<span class="berp-tooltip" title="<?php echo esc_attr( $rejection_note ); ?>">
													<span class="dashicons dashicons-info"></span>
												</span>
													<?php
											endif;
endif;
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="berp-text-muted"><?php esc_html_e( 'No advance requests found.', 'BuildERP' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Toggle installments field
	$('#repayment_type').on('change', function() {
		if ($(this).val() === 'installments') {
			$('.berp-installments-field').show();
		} else {
			$('.berp-installments-field').hide();
		}
	});

	// Handle form submission
	$('#berp-advance-request-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $btn = $form.find('button[type="submit"]');
		var $message = $('#berp-advance-message');
		
		// Disable button and show loading
		$btn.prop('disabled', true);
		$btn.find('.berp-btn-text').hide();
		$btn.find('.berp-btn-loading').show();
		$message.hide();
		
		var data = {
			amount: parseFloat($('#advance_amount').val()),
			reason: $('#advance_reason').val(),
			repayment_type: $('#repayment_type').val() || 'full',
			installments: parseInt($('#installments').val()) || 1
		};
		
		$.ajax({
			url: '<?php echo esc_url( rest_url( 'berp/v1/advance/request' ) ); ?>',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify(data),
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
			},
			success: function(response) {
				$message
					.removeClass('berp-portal-alert-danger')
					.addClass('berp-portal-alert-success')
					.html(response.message)
					.show();
				
				// Reset form
				$form[0].reset();
				
				// Reload page after delay to show updated history
				setTimeout(function() {
					location.reload();
				}, 2000);
			},
			error: function(xhr) {
				var error = xhr.responseJSON && xhr.responseJSON.message 
					? xhr.responseJSON.message 
					: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'BuildERP' ) ); ?>';
				
				$message
					.removeClass('berp-portal-alert-success')
					.addClass('berp-portal-alert-danger')
					.html(error)
					.show();
			},
			complete: function() {
				$btn.prop('disabled', false);
				$btn.find('.berp-btn-text').show();
				$btn.find('.berp-btn-loading').hide();
			}
		});
	});
});
</script>

<style>
.berp-portal-advances .berp-portal-info-box {
	background: #f8f9fa;
	padding: 15px;
	border-radius: 4px;
	margin-bottom: 20px;
}
.berp-portal-advances .berp-portal-info-box p {
	margin: 5px 0;
}
.berp-portal-advances .berp-text-danger {
	color: #dc3545;
}
.berp-portal-advances .berp-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 12px;
}
.berp-portal-advances .berp-badge-warning {
	background: #ffc107;
	color: #212529;
}
.berp-portal-advances .berp-badge-success {
	background: #28a745;
	color: #fff;
}
.berp-portal-advances .berp-badge-danger {
	background: #dc3545;
	color: #fff;
}
.berp-portal-advances .berp-badge-info {
	background: #17a2b8;
	color: #fff;
}
.berp-portal-advances .berp-spinner {
	display: inline-block;
	width: 16px;
	height: 16px;
	border: 2px solid #fff;
	border-radius: 50%;
	border-top-color: transparent;
	animation: berp-spin 1s linear infinite;
	margin-right: 5px;
	vertical-align: middle;
}
@keyframes berp-spin {
	to { transform: rotate(360deg); }
}
.berp-portal-advances .berp-tooltip {
	cursor: help;
}
.berp-portal-advances .berp-form-help {
	color: #6c757d;
	font-size: 12px;
}
.berp-portal-col-6 {
	width: 50%;
}
@media (max-width: 768px) {
	.berp-portal-col-6 {
		width: 100%;
	}
}
</style>

