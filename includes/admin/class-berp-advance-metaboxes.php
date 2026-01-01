<?php
/**
 * Advance Request Metaboxes
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Advance_Metaboxes Class
 *
 * Handles metaboxes for advance requests
 */
class BERP_Advance_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_berp_advance', array( $this, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts for advance metaboxes
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		global $post_type;
		if ( 'berp_advance' !== $post_type ) {
			return;
		}
		wp_enqueue_style( 'berp-admin-metabox' );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		$is_new = isset( $_GET['post'] ) ? false : true;
		$details_title = $is_new 
			? __( 'Pay Advance to Employee', 'aic_builderp' )
			: __( 'Advance Details', 'aic_builderp' );

		add_meta_box(
			'berp_advance_details',
			$details_title,
			array( $this, 'render_details_metabox' ),
			'berp_advance',
			'normal',
			'high'
		);

		add_meta_box(
			'berp_advance_status',
			__( 'Payment Status', 'aic_builderp' ),
			array( $this, 'render_status_metabox' ),
			'berp_advance',
			'side',
			'high'
		);
	}

	/**
	 * Render details metabox
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_details_metabox( $post ) {
		wp_nonce_field( 'berp_advance_meta', 'berp_advance_nonce' );

		$employee_id    = get_post_meta( $post->ID, '_berp_employee_id', true );
		$amount         = get_post_meta( $post->ID, '_berp_advance_amount', true );
		$reason         = get_post_meta( $post->ID, '_berp_advance_reason', true );
		$request_date   = get_post_meta( $post->ID, '_berp_request_date', true );
		$repayment_type = get_post_meta( $post->ID, '_berp_repayment_type', true );
		$installments   = get_post_meta( $post->ID, '_berp_installments', true );

		// Default values.
		if ( empty( $request_date ) ) {
			$request_date = current_time( 'Y-m-d' );
		}
		if ( empty( $repayment_type ) ) {
			$repayment_type = 'full';
		}
		if ( empty( $installments ) ) {
			$installments = 1;
		}

		// Get employees for dropdown.
		$employees = get_posts(
			array(
				'post_type'      => 'berp_employee',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		// Get employee salary for max advance calculation.
		$max_percent = berp_get_employee_advance_max_percent_setting();
		?>
		<div class="berp-metabox-wrap">
			<div class="berp-metabox-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
				<div class="berp-field-group">
					<label for="berp_employee_id"><strong><?php esc_html_e( 'Employee', 'aic_builderp' ); ?></strong> <span class="required">*</span></label>
					<select id="berp_employee_id" name="berp_advance[employee_id]" class="widefat" required>
						<option value=""><?php esc_html_e( '— Select Employee —', 'aic_builderp' ); ?></option>
						<?php foreach ( $employees as $emp ) : ?>
							<option value="<?php echo esc_attr( $emp->ID ); ?>" <?php selected( $employee_id, $emp->ID ); ?>>
								<?php echo esc_html( $emp->post_title ); ?>
								<?php
								$emp_salary = get_post_meta( $emp->ID, '_berp_basic_salary', true );
								if ( $emp_salary ) {
									$max_advance = ( $emp_salary * $max_percent ) / 100;
									echo ' (' . esc_html( sprintf( __( 'Max: %s', 'aic_builderp' ), berp_format_currency( $max_advance ) ) ) . ')';
								}
								?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the employee requesting the advance.', 'aic_builderp' ); ?></p>
				</div>

				<div class="berp-field-group">
					<label for="berp_request_date"><strong><?php esc_html_e( 'Request Date', 'aic_builderp' ); ?></strong></label>
					<input type="date" id="berp_request_date" name="berp_advance[request_date]" value="<?php echo esc_attr( $request_date ); ?>" class="widefat" />
				</div>

				<div class="berp-field-group">
					<label for="berp_advance_amount"><strong><?php esc_html_e( 'Amount Requested', 'aic_builderp' ); ?></strong> <span class="required">*</span></label>
					<input type="number" id="berp_advance_amount" name="berp_advance[amount]" value="<?php echo esc_attr( $amount ); ?>" step="0.01" min="0" class="widefat" required />
					<p class="description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: maximum advance percentage */
								__( 'Maximum allowed: %d%% of basic salary.', 'aic_builderp' ),
								$max_percent
							)
						);
						?>
					</p>
				</div>

				<div class="berp-field-group">
					<label for="berp_repayment_type"><strong><?php esc_html_e( 'Repayment Type', 'aic_builderp' ); ?></strong></label>
					<?php
					$allow_installments = berp_get_employee_allow_installments_setting();
					?>
					<select id="berp_repayment_type" name="berp_advance[repayment_type]" class="widefat" <?php echo ! $allow_installments ? 'disabled' : ''; ?>>
						<option value="full" <?php selected( $repayment_type, 'full' ); ?>><?php esc_html_e( 'Full Deduction (Next Payroll)', 'aic_builderp' ); ?></option>
						<?php if ( $allow_installments ) : ?>
							<option value="installments" <?php selected( $repayment_type, 'installments' ); ?>><?php esc_html_e( 'Installments', 'aic_builderp' ); ?></option>
						<?php endif; ?>
					</select>
					<?php if ( ! $allow_installments ) : ?>
						<input type="hidden" name="berp_advance[repayment_type]" value="full" />
						<p class="description"><?php esc_html_e( 'Installment repayment is disabled in settings.', 'aic_builderp' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="berp-field-group berp-installments-field" style="<?php echo 'installments' !== $repayment_type ? 'display: none;' : ''; ?>">
					<label for="berp_installments"><strong><?php esc_html_e( 'Number of Installments', 'aic_builderp' ); ?></strong></label>
					<input type="number" id="berp_installments" name="berp_advance[installments]" value="<?php echo esc_attr( $installments ); ?>" min="1" max="12" class="widefat" />
					<p class="description"><?php esc_html_e( 'Spread repayment across multiple payroll periods (max 12).', 'aic_builderp' ); ?></p>
				</div>

				<div class="berp-field-group" style="grid-column: 1 / -1;">
					<label for="berp_advance_reason"><strong><?php esc_html_e( 'Reason for Request', 'aic_builderp' ); ?></strong></label>
					<textarea id="berp_advance_reason" name="berp_advance[reason]" rows="4" class="widefat"><?php echo esc_textarea( $reason ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Employee\'s reason for requesting the advance.', 'aic_builderp' ); ?></p>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#berp_repayment_type').on('change', function() {
				if ($(this).val() === 'installments') {
					$('.berp-installments-field').show();
				} else {
					$('.berp-installments-field').hide();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render status metabox
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_status_metabox( $post ) {
		$status          = get_post_meta( $post->ID, '_berp_advance_status', true );
		$approved_date   = get_post_meta( $post->ID, '_berp_approved_date', true );
		$approved_by     = get_post_meta( $post->ID, '_berp_approved_by', true );
		$rejection_note  = get_post_meta( $post->ID, '_berp_rejection_note', true );
		$paid_in_payroll = get_post_meta( $post->ID, '_berp_paid_in_payroll', true );
		$is_new          = 'auto-draft' === get_post_status( $post->ID ) || ! $post->ID;

		// Default status - for new posts created by admin, default to approved.
		if ( empty( $status ) ) {
			// Admin creating new = approved by default (they're paying the advance).
			$status = 'approved';
		}

		$statuses = array(
			'pending'  => __( 'Pending Approval', 'aic_builderp' ),
			'approved' => __( 'Approved / Paid Out', 'aic_builderp' ),
			'rejected' => __( 'Rejected', 'aic_builderp' ),
			'paid'     => __( 'Deducted from Payroll', 'aic_builderp' ),
		);
		?>
		<div class="berp-status-metabox">
			<?php if ( $is_new ) : ?>
				<div class="berp-admin-info-box" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 10px; margin-bottom: 15px;">
					<strong><?php esc_html_e( 'Creating Advance Payment', 'aic_builderp' ); ?></strong><br>
					<small><?php esc_html_e( 'Use this to log an advance payment to an employee. The amount will be deducted from their account balance.', 'aic_builderp' ); ?></small>
				</div>
			<?php endif; ?>

			<div class="berp-field-group">
				<label for="berp_advance_status"><strong><?php esc_html_e( 'Status', 'aic_builderp' ); ?></strong></label>
				<select id="berp_advance_status" name="berp_advance[status]" class="widefat">
					<?php foreach ( $statuses as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description" style="margin-top: 5px;">
					<?php esc_html_e( 'Approved = Money given to employee. Deducted = Recovered via payroll.', 'aic_builderp' ); ?>
				</p>
			</div>

			<?php if ( 'approved' === $status || 'paid' === $status ) : ?>
				<div class="berp-field-group" style="margin-top: 15px;">
					<p><strong><?php esc_html_e( 'Approved Date:', 'aic_builderp' ); ?></strong><br>
						<?php echo $approved_date ? esc_html( berp_format_date( $approved_date ) ) : '—'; ?>
					</p>
					<?php if ( $approved_by ) : ?>
						<p><strong><?php esc_html_e( 'Approved By:', 'aic_builderp' ); ?></strong><br>
							<?php
							$approver = get_userdata( $approved_by );
							echo $approver ? esc_html( $approver->display_name ) : '—';
							?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( 'paid' === $status && $paid_in_payroll ) : ?>
				<div class="berp-field-group" style="margin-top: 15px;">
					<p><strong><?php esc_html_e( 'Deducted in Payroll:', 'aic_builderp' ); ?></strong><br>
						<a href="<?php echo esc_url( get_edit_post_link( $paid_in_payroll ) ); ?>">
							<?php echo esc_html( get_the_title( $paid_in_payroll ) ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<?php
			// Show linked expense if exists.
			$linked_expense = get_post_meta( $post->ID, '_berp_expense_id', true );
			if ( $linked_expense && get_post( $linked_expense ) ) :
			?>
				<div class="berp-field-group" style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
					<p style="margin: 0;"><strong><?php esc_html_e( 'Expense Record:', 'aic_builderp' ); ?></strong><br>
						<a href="<?php echo esc_url( get_edit_post_link( $linked_expense ) ); ?>">
							<?php echo esc_html( get_the_title( $linked_expense ) ); ?>
						</a>
						<br><small style="color: #666;"><?php esc_html_e( 'This advance is tracked in expenses for reporting.', 'aic_builderp' ); ?></small>
					</p>
				</div>
			<?php endif; ?>

			<div class="berp-rejection-note-field" style="margin-top: 15px; <?php echo 'rejected' !== $status ? 'display: none;' : ''; ?>">
				<label for="berp_rejection_note"><strong><?php esc_html_e( 'Rejection Note', 'aic_builderp' ); ?></strong></label>
				<textarea id="berp_rejection_note" name="berp_advance[rejection_note]" rows="3" class="widefat"><?php echo esc_textarea( $rejection_note ); ?></textarea>
			</div>

			<?php if ( 'pending' === $status && $post->ID ) : ?>
				<div class="berp-quick-actions" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
					<p><strong><?php esc_html_e( 'Quick Actions', 'aic_builderp' ); ?></strong></p>
					<p>
						<button type="button" class="button button-primary berp-approve-advance" data-status="approved" style="width: 100%; margin-bottom: 5px;">
							<?php esc_html_e( 'Approve Request', 'aic_builderp' ); ?>
						</button>
						<button type="button" class="button berp-reject-advance" data-status="rejected" style="width: 100%;">
							<?php esc_html_e( 'Reject Request', 'aic_builderp' ); ?>
						</button>
					</p>
				</div>

				<script>
				jQuery(document).ready(function($) {
					$('.berp-approve-advance, .berp-reject-advance').on('click', function() {
						var status = $(this).data('status');
						$('#berp_advance_status').val(status).trigger('change');
						if (status === 'rejected') {
							$('.berp-rejection-note-field').show();
							$('#berp_rejection_note').focus();
						}
					});

					$('#berp_advance_status').on('change', function() {
						if ($(this).val() === 'rejected') {
							$('.berp-rejection-note-field').show();
						} else {
							$('.berp-rejection-note-field').hide();
						}
					});
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save meta boxes
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// Verify nonce.
		if ( ! isset( $_POST['berp_advance_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_advance_nonce'] ) ), 'berp_advance_meta' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'berp_manage_advances', $post_id ) ) {
			return;
		}

		// Get submitted data.
		$data = isset( $_POST['berp_advance'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['berp_advance'] ) ) : array();

		// Get old status for comparison.
		$old_status = get_post_meta( $post_id, '_berp_advance_status', true );
		$new_status = isset( $data['status'] ) ? $data['status'] : 'pending';

		// Save meta fields.
		if ( isset( $data['employee_id'] ) ) {
			update_post_meta( $post_id, '_berp_employee_id', absint( $data['employee_id'] ) );
		}
		if ( isset( $data['amount'] ) ) {
			update_post_meta( $post_id, '_berp_advance_amount', floatval( $data['amount'] ) );
		}
		if ( isset( $data['reason'] ) ) {
			update_post_meta( $post_id, '_berp_advance_reason', sanitize_textarea_field( $data['reason'] ) );
		}
		if ( isset( $data['request_date'] ) ) {
			update_post_meta( $post_id, '_berp_request_date', sanitize_text_field( $data['request_date'] ) );
		}
		if ( isset( $data['repayment_type'] ) ) {
			update_post_meta( $post_id, '_berp_repayment_type', sanitize_text_field( $data['repayment_type'] ) );
		}
		if ( isset( $data['installments'] ) ) {
			update_post_meta( $post_id, '_berp_installments', absint( $data['installments'] ) );
		}
		if ( isset( $data['rejection_note'] ) ) {
			update_post_meta( $post_id, '_berp_rejection_note', sanitize_textarea_field( $data['rejection_note'] ) );
		}

		// Save status.
		update_post_meta( $post_id, '_berp_advance_status', $new_status );

		// Handle status change.
		if ( $old_status !== $new_status ) {
			if ( 'approved' === $new_status && 'approved' !== $old_status ) {
				// Record approval details.
				update_post_meta( $post_id, '_berp_approved_date', current_time( 'Y-m-d' ) );
				update_post_meta( $post_id, '_berp_approved_by', get_current_user_id() );

				// Update employee account balance (add to their balance as they now have money).
				$employee_id = get_post_meta( $post_id, '_berp_employee_id', true );
				$amount      = get_post_meta( $post_id, '_berp_advance_amount', true );
				if ( $employee_id && $amount ) {
					$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
					// Negative balance means employee owes company.
					$new_balance = $current_balance - floatval( $amount );
					update_post_meta( $employee_id, '_berp_account_balance', $new_balance );

					// Create expense record for tracking.
					$this->create_advance_expense( $post_id, $employee_id, floatval( $amount ) );
				}

				/**
				 * Fires when an advance request is approved.
				 *
				 * @param int   $post_id     Advance post ID.
				 * @param int   $employee_id Employee post ID.
				 * @param float $amount      Advance amount.
				 */
				do_action( 'berp_advance_approved', $post_id, $employee_id, $amount );
			} elseif ( 'rejected' === $new_status ) {
				/**
				 * Fires when an advance request is rejected.
				 *
				 * @param int    $post_id        Advance post ID.
				 * @param string $rejection_note Rejection reason.
				 */
				do_action( 'berp_advance_rejected', $post_id, isset( $data['rejection_note'] ) ? $data['rejection_note'] : '' );
			}
		}
	}

	/**
	 * Create expense record when advance is paid.
	 *
	 * @param int   $advance_id  Advance post ID.
	 * @param int   $employee_id Employee post ID.
	 * @param float $amount      Advance amount.
	 */
	private function create_advance_expense( $advance_id, $employee_id, $amount ) {
		$employee_name = get_the_title( $employee_id );
		$reason        = get_post_meta( $advance_id, '_berp_advance_reason', true );

		// Create expense title.
		/* translators: %s: Employee name */
		$expense_title = sprintf( __( 'Salary Advance - %s', 'aic_builderp' ), $employee_name );

		// Create the expense post.
		$expense_id = wp_insert_post(
			array(
				'post_type'   => 'berp_expense',
				'post_title'  => $expense_title,
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $expense_id ) && $expense_id ) {
			// Save expense meta.
			update_post_meta( $expense_id, '_berp_expense_amount', $amount );
			update_post_meta( $expense_id, '_berp_expense_date', current_time( 'Y-m-d' ) );
			update_post_meta( $expense_id, '_berp_expense_status', 'paid' );
			update_post_meta( $expense_id, '_berp_payment_method', 'cash' );
			update_post_meta( $expense_id, '_berp_expense_employee_id', $employee_id );
			update_post_meta( $expense_id, '_berp_expense_advance_id', $advance_id );

			// Set description with reference to advance.
			/* translators: 1: Advance ID, 2: Reason */
			$description = sprintf(
				__( 'Salary advance payment (Advance #%1$d). %2$s', 'aic_builderp' ),
				$advance_id,
				$reason ? $reason : ''
			);
			update_post_meta( $expense_id, '_berp_expense_description', $description );

			// Assign to "Salary Advance" expense category.
			$this->assign_salary_advance_category( $expense_id );

			// Link expense back to advance.
			update_post_meta( $advance_id, '_berp_expense_id', $expense_id );

			/**
			 * Fires when an expense is created for an advance payment.
			 *
			 * @param int $expense_id  Created expense post ID.
			 * @param int $advance_id  Advance post ID.
			 * @param int $employee_id Employee post ID.
			 */
			do_action( 'berp_advance_expense_created', $expense_id, $advance_id, $employee_id );
		}
	}

	/**
	 * Assign "Salary Advance" category to an expense.
	 *
	 * @param int $expense_id Expense post ID.
	 */
	private function assign_salary_advance_category( $expense_id ) {
		$category_name = __( 'Salary Advance', 'aic_builderp' );

		// Set the category as post meta (this is how expenses store category).
		update_post_meta( $expense_id, '_berp_expense_category', $category_name );

		// Also ensure "Salary Advance" exists in settings categories.
		$settings        = get_option( 'berp_settings', array() );
		$categories_text = isset( $settings['expense']['default_categories'] ) ? $settings['expense']['default_categories'] : "Payroll\nMaterials\nEquipment\nSubcontractor\nOther";
		$categories      = array_filter( array_map( 'trim', explode( "\n", $categories_text ) ) );

		// Check if "Salary Advance" is already in the list.
		$found = false;
		foreach ( $categories as $cat ) {
			if ( strtolower( trim( $cat ) ) === strtolower( $category_name ) ) {
				$found = true;
				break;
			}
		}

		// Add it if not found.
		if ( ! $found ) {
			$categories[]                              = $category_name;
			$settings['expense']['default_categories'] = implode( "\n", $categories );
			update_option( 'berp_settings', $settings );
		}
	}
}
