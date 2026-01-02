<?php
/**
 * Payroll Metaboxes
 *
 * Handles payroll post type metaboxes, save handlers, and list table customization.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Payroll_Metaboxes Class
 *
 * Manages:
 * - 5-tab metabox interface
 * - Save handler with auto/manual modes
 * - List table columns and filters
 * - Mark as paid workflow
 * - Email and PDF functionality
 */
class BERP_Payroll_Metaboxes {

	/**
	 * Meta keys mapping
	 *
	 * @var array
	 */
	protected $meta_keys = array(
		'employee_id'        => '_berp_payroll_employee_id',
		'month'              => '_berp_payroll_month',
		'calculation_mode'   => '_berp_calculation_mode',
		'basic_salary'       => '_berp_payroll_basic_salary',
		'allowances'         => '_berp_payroll_allowances',
		'deductions'         => '_berp_payroll_deductions',
		'present_days'       => '_berp_present_days',
		'paid_weekends'      => '_berp_paid_weekends',
		'holidays'           => '_berp_holidays',
		'total_paid_days'    => '_berp_total_paid_days',
		'overtime_hours'     => '_berp_overtime_hours',
		'overtime_amount'    => '_berp_overtime_amount',
		'gross_salary'       => '_berp_gross_salary',
		'net_salary'         => '_berp_net_salary',
		'status'             => '_berp_payroll_status',
		'paid_date'          => '_berp_paid_date',
		'expense_id'         => '_berp_linked_expense_id',
		'formula_used'       => '_berp_formula_used',
		'notes'              => '_berp_payroll_notes',
		'email_sent'         => '_berp_email_sent',
		'email_sent_to'      => '_berp_email_sent_to',
		'attendance_details' => '_berp_attendance_details',
		'total_allowances'   => '_berp_total_allowances',
		'total_deductions'   => '_berp_total_deductions',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_berp_payroll', array( $this, 'save_payroll' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );

		// List table customization
		add_filter( 'manage_berp_payroll_posts_columns', array( $this, 'register_columns' ) );
		add_action( 'manage_berp_payroll_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-berp_payroll_sortable_columns', array( $this, 'register_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_column_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_by_meta' ) );
	}

	/**
	 * Register metaboxes
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'berp-payroll-main',
			__( 'Payroll Details', 'BuildERP' ),
			array( $this, 'render_main_metabox' ),
			'berp_payroll',
			'normal',
			'high'
		);
	}

	/**
	 * Render main tabbed metabox
	 *
	 * @param WP_Post $post Current post object
	 */
	public function render_main_metabox( $post ) {
		wp_nonce_field( 'berp_save_payroll', 'berp_payroll_nonce' );

		// Get current values
		$employee_id      = get_post_meta( $post->ID, $this->meta_keys['employee_id'], true );
		$month            = get_post_meta( $post->ID, $this->meta_keys['month'], true );
		$calculation_mode = get_post_meta( $post->ID, $this->meta_keys['calculation_mode'], true );
		$calculation_mode = ! empty( $calculation_mode ) ? $calculation_mode : 'auto';

		// Get calculated values
		$basic_salary       = get_post_meta( $post->ID, $this->meta_keys['basic_salary'], true );
		$allowances         = get_post_meta( $post->ID, $this->meta_keys['allowances'], true );
		$deductions         = get_post_meta( $post->ID, $this->meta_keys['deductions'], true );
		$present_days       = get_post_meta( $post->ID, $this->meta_keys['present_days'], true );
		$paid_weekends      = get_post_meta( $post->ID, $this->meta_keys['paid_weekends'], true );
		$holidays           = get_post_meta( $post->ID, $this->meta_keys['holidays'], true );
		$total_paid_days    = get_post_meta( $post->ID, $this->meta_keys['total_paid_days'], true );
		$overtime_hours     = get_post_meta( $post->ID, $this->meta_keys['overtime_hours'], true );
		$overtime_amount    = get_post_meta( $post->ID, $this->meta_keys['overtime_amount'], true );
		$gross_salary       = get_post_meta( $post->ID, $this->meta_keys['gross_salary'], true );
		$net_salary         = get_post_meta( $post->ID, $this->meta_keys['net_salary'], true );
		$status             = get_post_meta( $post->ID, $this->meta_keys['status'], true );
		$status             = ! empty( $status ) ? $status : 'pending';
		$paid_date          = get_post_meta( $post->ID, $this->meta_keys['paid_date'], true );
		$expense_id         = get_post_meta( $post->ID, $this->meta_keys['expense_id'], true );
		$formula_used       = get_post_meta( $post->ID, $this->meta_keys['formula_used'], true );
		$notes              = get_post_meta( $post->ID, $this->meta_keys['notes'], true );
		$email_sent         = get_post_meta( $post->ID, $this->meta_keys['email_sent'], true );
		$email_sent_to      = get_post_meta( $post->ID, $this->meta_keys['email_sent_to'], true );
		$attendance_details = get_post_meta( $post->ID, $this->meta_keys['attendance_details'], true );
		$total_allowances   = get_post_meta( $post->ID, $this->meta_keys['total_allowances'], true );
		$total_deductions   = get_post_meta( $post->ID, $this->meta_keys['total_deductions'], true );

		$allowances         = is_array( $allowances ) ? $allowances : array();
		$deductions         = is_array( $deductions ) ? $deductions : array();
		$attendance_details = is_array( $attendance_details ) ? $attendance_details : array();

		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<!-- Tab Navigation -->
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Payroll sections', 'BuildERP' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="berp-payroll-employee">
						<?php esc_html_e( 'Employee & Month', 'BuildERP' ); ?>
					</button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-payroll-salary">
						<?php esc_html_e( 'Salary Breakdown', 'BuildERP' ); ?>
					</button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-payroll-attendance">
						<?php esc_html_e( 'Attendance Summary', 'BuildERP' ); ?>
					</button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-payroll-payment">
						<?php esc_html_e( 'Payment Status', 'BuildERP' ); ?>
					</button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-payroll-formula">
						<?php esc_html_e( 'Formula & Notes', 'BuildERP' ); ?>
					</button>
				</nav>

				<!-- Tab Panels -->
				<div class="berp-metabox-panels">
					<!-- Tab 1: Employee & Month -->
					<div class="berp-metabox-panel is-active" data-tab-panel="berp-payroll-employee">
						<?php $this->render_employee_tab( $employee_id, $month, $calculation_mode, $post->ID ); ?>
					</div>

					<!-- Tab 2: Salary Breakdown -->
					<div class="berp-metabox-panel" data-tab-panel="berp-payroll-salary">
						<?php $this->render_salary_tab( $basic_salary, $allowances, $deductions, $gross_salary, $net_salary, $total_allowances, $total_deductions, $calculation_mode ); ?>
					</div>

					<!-- Tab 3: Attendance Summary -->
					<div class="berp-metabox-panel" data-tab-panel="berp-payroll-attendance">
						<?php $this->render_attendance_tab( $present_days, $paid_weekends, $holidays, $total_paid_days, $overtime_hours, $overtime_amount, $attendance_details ); ?>
					</div>

					<!-- Tab 4: Payment Status -->
					<div class="berp-metabox-panel" data-tab-panel="berp-payroll-payment">
						<?php $this->render_payment_tab( $status, $paid_date, $expense_id, $email_sent, $email_sent_to, $post->ID ); ?>
					</div>

					<!-- Tab 5: Formula & Notes -->
					<div class="berp-metabox-panel" data-tab-panel="berp-payroll-formula">
						<?php $this->render_formula_tab( $formula_used, $notes ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Tab 1: Employee & Month
	 */
	protected function render_employee_tab( $employee_id, $month, $calculation_mode, $post_id ) {
		// Get all active employees
		$employees = get_posts(
			array(
				'post_type'      => 'berp_employee',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_berp_employee_status',
						'value'   => 'active',
						'compare' => '=',
					),
				),
			)
		);

		// Default month (previous month)
		if ( empty( $month ) ) {
			$month = gmdate( 'Y-m', strtotime( '-1 month' ) );
		}
		?>
		<table class="form-table berp-form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="berp_employee_id"><?php esc_html_e( 'Employee', 'BuildERP' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<select name="berp_payroll[employee_id]" id="berp_employee_id" class="regular-text" required>
							<option value=""><?php esc_html_e( 'Select Employee', 'BuildERP' ); ?></option>
							<?php foreach ( $employees as $employee ) : ?>
								<option value="<?php echo esc_attr( $employee->ID ); ?>" <?php selected( $employee_id, $employee->ID ); ?>>
									<?php echo esc_html( $employee->post_title ); ?>
									<?php
									$emp_id = get_post_meta( $employee->ID, '_berp_employee_id', true );
									if ( $emp_id ) {
										echo ' (' . esc_html( $emp_id ) . ')';
									}
									?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select the employee for this payroll entry', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="berp_payroll_month"><?php esc_html_e( 'Payroll Month', 'BuildERP' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="month" name="berp_payroll[month]" id="berp_payroll_month" value="<?php echo esc_attr( $month ); ?>" class="regular-text" required>
						<p class="description"><?php esc_html_e( 'Select the month for payroll calculation (YYYY-MM)', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Calculation Mode', 'BuildERP' ); ?></label>
					</th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="berp_payroll[calculation_mode]" value="auto" <?php checked( $calculation_mode, 'auto' ); ?>>
								<?php esc_html_e( 'Auto-Calculate (using formula builder)', 'BuildERP' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="berp_payroll[calculation_mode]" value="manual" <?php checked( $calculation_mode, 'manual' ); ?>>
								<?php esc_html_e( 'Manual Entry', 'BuildERP' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Choose calculation mode. Auto mode uses formula builder and attendance data.', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<button type="button" class="button button-primary" id="berp-recalculate-payroll" data-payroll-id="<?php echo esc_attr( $post_id ); ?>">
							<?php esc_html_e( 'Recalculate', 'BuildERP' ); ?>
						</button>
						<span class="spinner" style="float: none; margin-top: 0;"></span>
						<p class="description"><?php esc_html_e( 'Click to recalculate payroll using current employee and month', 'BuildERP' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Tab 2: Salary Breakdown
	 */
	protected function render_salary_tab( $basic_salary, $allowances, $deductions, $gross_salary, $net_salary, $total_allowances, $total_deductions, $calculation_mode ) {
		$readonly = ( 'auto' === $calculation_mode ) ? 'readonly' : '';
		?>
		<table class="form-table berp-form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="berp_basic_salary"><?php esc_html_e( 'Basic Salary', 'BuildERP' ); ?></label>
					</th>
					<td>
						<input type="number" name="berp_payroll[basic_salary]" id="berp_basic_salary" value="<?php echo esc_attr( $basic_salary ); ?>" step="0.01" class="regular-text" <?php echo esc_attr( $readonly ); ?>>
						<p class="description"><?php esc_html_e( 'Monthly basic salary', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Allowances', 'BuildERP' ); ?></label>
					</th>
					<td>
						<?php if ( ! empty( $allowances ) ) : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Type', 'BuildERP' ); ?></th>
										<th><?php esc_html_e( 'Amount', 'BuildERP' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $allowances as $allowance ) : ?>
										<tr>
											<td><?php echo esc_html( $allowance['label'] ?? '' ); ?></td>
											<td><?php echo esc_html( berp_format_currency( $allowance['amount'] ?? 0 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
									<tr>
										<td><strong><?php esc_html_e( 'Total', 'BuildERP' ); ?></strong></td>
										<td><strong><?php echo esc_html( berp_format_currency( $total_allowances ) ); ?></strong></td>
									</tr>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php esc_html_e( 'No allowances', 'BuildERP' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Deductions', 'BuildERP' ); ?></label>
					</th>
					<td>
						<?php if ( ! empty( $deductions ) ) : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Type', 'BuildERP' ); ?></th>
										<th><?php esc_html_e( 'Amount', 'BuildERP' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $deductions as $deduction ) : ?>
										<tr>
											<td><?php echo esc_html( $deduction['label'] ?? '' ); ?></td>
											<td><?php echo esc_html( berp_format_currency( $deduction['amount'] ?? 0 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
									<tr>
										<td><strong><?php esc_html_e( 'Total', 'BuildERP' ); ?></strong></td>
										<td><strong><?php echo esc_html( berp_format_currency( $total_deductions ) ); ?></strong></td>
									</tr>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php esc_html_e( 'No deductions', 'BuildERP' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="berp_gross_salary"><?php esc_html_e( 'Gross Salary', 'BuildERP' ); ?></label>
					</th>
					<td>
						<input type="number" name="berp_payroll[gross_salary]" id="berp_gross_salary" value="<?php echo esc_attr( $gross_salary ); ?>" step="0.01" min="0" class="regular-text berp-salary-field" <?php echo esc_attr( $readonly ); ?>>
						<p class="description">
							<?php if ( 'manual' === $calculation_mode ) : ?>
								<?php esc_html_e( 'Enter gross salary manually', 'BuildERP' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Calculated gross salary (before deductions)', 'BuildERP' ); ?>
							<?php endif; ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="berp_net_salary"><?php esc_html_e( 'Net Salary', 'BuildERP' ); ?></label>
					</th>
					<td>
						<input type="number" name="berp_payroll[net_salary]" id="berp_net_salary" value="<?php echo esc_attr( $net_salary ); ?>" step="0.01" min="0" class="regular-text berp-salary-field" <?php echo esc_attr( $readonly ); ?>>
						<p class="description">
							<?php if ( 'manual' === $calculation_mode ) : ?>
								<?php esc_html_e( 'Enter net salary manually', 'BuildERP' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Calculated net salary (after deductions)', 'BuildERP' ); ?>
							<?php endif; ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Tab 3: Attendance Summary
	 */
	protected function render_attendance_tab( $present_days, $paid_weekends, $holidays, $total_paid_days, $overtime_hours, $overtime_amount, $attendance_details ) {
		?>
		<table class="form-table berp-form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Present Days', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( $present_days ); ?></strong>
						<p class="description"><?php esc_html_e( 'Working days attended (excluding weekends and holidays)', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Paid Weekend Days', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( $paid_weekends ); ?></strong>
						<p class="description"><?php esc_html_e( 'Weekend days counted as paid (based on neighboring attendance)', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Holidays', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( $holidays ); ?></strong>
						<p class="description"><?php esc_html_e( 'Public holidays during this month', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Total Paid Days', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( $total_paid_days ); ?></strong>
						<p class="description"><?php esc_html_e( 'Total days to be paid for (present + paid weekends + holidays)', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Overtime Hours', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( $overtime_hours ); ?></strong>
						<p class="description"><?php esc_html_e( 'Total overtime hours logged', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Overtime Amount', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( berp_format_currency( $overtime_amount ) ); ?></strong>
						<p class="description"><?php esc_html_e( 'Calculated overtime payment', 'BuildERP' ); ?></p>
					</td>
				</tr>
				<?php if ( ! empty( $attendance_details ) ) : ?>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Attendance Details', 'BuildERP' ); ?></label>
					</th>
					<td>
						<details>
							<summary><?php esc_html_e( 'View detailed attendance breakdown', 'BuildERP' ); ?></summary>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'BuildERP' ); ?></th>
										<th><?php esc_html_e( 'Site', 'BuildERP' ); ?></th>
										<th><?php esc_html_e( 'OT Hours', 'BuildERP' ); ?></th>
										<th><?php esc_html_e( 'Type', 'BuildERP' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $attendance_details as $detail ) : ?>
										<tr>
											<td><?php echo esc_html( berp_format_date( $detail->date ?? '' ) ); ?></td>
											<td>
												<?php
												if ( ! empty( $detail->site_id ) ) {
													echo esc_html( get_the_title( $detail->site_id ) );
												} else {
													echo '&mdash;';
												}
												?>
											</td>
											<td><?php echo esc_html( $detail->overtime_hours ?? 0 ); ?></td>
											<td>
												<?php
												if ( ! empty( $detail->is_holiday ) ) {
													echo esc_html__( 'Holiday', 'BuildERP' );
												} elseif ( ! empty( $detail->is_weekend ) ) {
													echo esc_html__( 'Weekend', 'BuildERP' );
													if ( ! empty( $detail->weekend_payable ) ) {
														echo ' (' . esc_html__( 'Paid', 'BuildERP' ) . ')';
													}
												} else {
													echo esc_html__( 'Regular', 'BuildERP' );
												}
												?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</details>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Tab 4: Payment Status
	 */
	protected function render_payment_tab( $status, $paid_date, $expense_id, $email_sent, $email_sent_to, $post_id ) {
		?>
		<table class="form-table berp-form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Payment Status', 'BuildERP' ); ?></label>
					</th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="berp_payroll[status]" value="pending" <?php checked( $status, 'pending' ); ?>>
								<?php esc_html_e( 'Pending', 'BuildERP' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="berp_payroll[status]" value="paid" <?php checked( $status, 'paid' ); ?> <?php disabled( 'paid', $status ); ?>>
								<?php esc_html_e( 'Paid', 'BuildERP' ); ?>
							</label>
						</fieldset>
						<?php if ( 'paid' === $status ) : ?>
							<p class="description" style="color: green;"><?php esc_html_e( 'This payroll has been marked as paid', 'BuildERP' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( 'paid' === $status && ! empty( $paid_date ) ) : ?>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Date Paid', 'BuildERP' ); ?></label>
					</th>
					<td>
						<strong><?php echo esc_html( berp_format_date( $paid_date ) ); ?></strong>
					</td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $expense_id ) ) : ?>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Linked Expense', 'BuildERP' ); ?></label>
					</th>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $expense_id ) ); ?>" target="_blank">
							<?php
							/* translators: %d: expense ID */
							echo esc_html( sprintf( __( 'View Expense #%d', 'BuildERP' ), (int) $expense_id ) );
							?>
						</a>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"></th>
					<td>
						<?php if ( 'pending' === $status && $post_id > 0 && 'auto-draft' !== get_post_status( $post_id ) ) : ?>
							<button type="button" class="button button-primary" id="berp-mark-paid" data-payroll-id="<?php echo esc_attr( $post_id ); ?>">
								<?php esc_html_e( 'Mark as Paid', 'BuildERP' ); ?>
							</button>
							<span class="spinner" style="float: none; margin-top: 0;"></span>
							<p class="description"><?php esc_html_e( 'Create expense record and update employee balance', 'BuildERP' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<?php if ( $post_id > 0 && 'auto-draft' !== get_post_status( $post_id ) ) : ?>
							<button type="button" class="button" id="berp-view-salary-slip" data-payroll-id="<?php echo esc_attr( $post_id ); ?>">
								<?php esc_html_e( 'View Salary Slip (PDF)', 'BuildERP' ); ?>
							</button>
							<button type="button" class="button" id="berp-email-salary-slip" data-payroll-id="<?php echo esc_attr( $post_id ); ?>">
								<?php esc_html_e( 'Email Salary Slip', 'BuildERP' ); ?>
							</button>
							<span class="spinner" style="float: none; margin-top: 0;"></span>
						<?php endif; ?>
						<?php if ( ! empty( $email_sent ) ) : ?>
							<p class="description" style="color: green;">
								<?php
								/* translators: 1: email sent date, 2: recipient email */
								printf(
									esc_html__( 'Last emailed on %1$s to %2$s', 'BuildERP' ),
									esc_html( berp_format_date( $email_sent ) ),
									esc_html( $email_sent_to )
								);
								?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Tab 5: Formula & Notes
	 */
	protected function render_formula_tab( $formula_used, $notes ) {
		$formula_data = ! empty( $formula_used ) ? json_decode( $formula_used, true ) : null;
		?>
		<table class="form-table berp-form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Formula Used', 'BuildERP' ); ?></label>
					</th>
					<td>
						<?php if ( $formula_data ) : ?>
							<textarea readonly class="large-text code" rows="3"><?php echo esc_textarea( $formula_data['formula'] ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Salary formula used for this calculation', 'BuildERP' ); ?></p>
							<details>
								<summary><?php esc_html_e( 'View formula variables', 'BuildERP' ); ?></summary>
								<pre><?php echo esc_html( wp_json_encode( $formula_data['variables'] ?? array(), JSON_PRETTY_PRINT ) ); ?></pre>
							</details>
						<?php else : ?>
							<p><?php esc_html_e( 'No formula data available', 'BuildERP' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="berp_payroll_notes"><?php esc_html_e( 'Admin Notes', 'BuildERP' ); ?></label>
					</th>
					<td>
						<textarea name="berp_payroll[notes]" id="berp_payroll_notes" class="large-text" rows="4"><?php echo esc_textarea( $notes ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Internal notes about this payroll entry', 'BuildERP' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save payroll meta data
	 *
	 * @param int     $post_id Post ID
	 * @param WP_Post $post    Post object
	 */
	public function save_payroll( $post_id, $post ) {
		// Nonce verification
		if ( ! isset( $_POST['berp_payroll_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_payroll_nonce'] ) ), 'berp_save_payroll' ) ) {
			return;
		}

		// Autosave check
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Capability check
		if ( ! current_user_can( 'berp_process_payroll', $post_id ) ) {
			return;
		}

		// Get data
		$data = isset( $_POST['berp_payroll'] ) ? (array) wp_unslash( $_POST['berp_payroll'] ) : array();

		// Sanitize basic fields
		$employee_id      = isset( $data['employee_id'] ) ? absint( $data['employee_id'] ) : 0;
		$month            = isset( $data['month'] ) ? sanitize_text_field( $data['month'] ) : '';
		$calculation_mode = isset( $data['calculation_mode'] ) ? sanitize_key( $data['calculation_mode'] ) : 'auto';
		$notes            = isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '';

		// Validate required fields
		if ( empty( $employee_id ) || empty( $month ) ) {
			$this->add_notice( 'error', __( 'Employee and month are required', 'BuildERP' ) );
			return;
		}

		// Update basic meta
		update_post_meta( $post_id, $this->meta_keys['employee_id'], $employee_id );
		update_post_meta( $post_id, $this->meta_keys['month'], $month );
		update_post_meta( $post_id, $this->meta_keys['calculation_mode'], $calculation_mode );
		update_post_meta( $post_id, $this->meta_keys['notes'], $notes );

		// If auto mode, run calculation
		if ( 'auto' === $calculation_mode ) {
			$calculator = new BERP_Payroll_Calculator();
			$result     = $calculator->calculate( $employee_id, $month );

			if ( is_wp_error( $result ) ) {
				$this->add_notice( 'error', $result->get_error_message() );
				return;
			}

			// Save all calculated data
			foreach ( $result as $key => $value ) {
				if ( isset( $this->meta_keys[ $key ] ) ) {
					update_post_meta( $post_id, $this->meta_keys[ $key ], $value );
				}
			}

			$this->add_notice( 'success', __( 'Payroll calculated successfully', 'BuildERP' ) );

			// Update post title - PREVENT RECURSION by unhooking save handler
			$employee_name = get_the_title( $employee_id );
			$new_title     = sprintf( '%s - %s', $employee_name, $month );

			// Only update if title actually changed
			if ( $post->post_title !== $new_title ) {
				// Unhook this save handler to prevent infinite loop
				remove_action( 'save_post_berp_payroll', array( $this, 'save_payroll' ), 10 );

				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $new_title,
					),
					false,
					false
				); // Skip slashing and sanitization (already clean)

				// Re-hook the save handler
				add_action( 'save_post_berp_payroll', array( $this, 'save_payroll' ), 10, 2 );
			}
		} else {
			// Manual mode - save user-entered values
			$basic_salary = isset( $data['basic_salary'] ) ? floatval( $data['basic_salary'] ) : 0;
			$gross_salary = isset( $data['gross_salary'] ) ? floatval( $data['gross_salary'] ) : 0;
			$net_salary   = isset( $data['net_salary'] ) ? floatval( $data['net_salary'] ) : 0;

			update_post_meta( $post_id, $this->meta_keys['basic_salary'], $basic_salary );
			update_post_meta( $post_id, $this->meta_keys['gross_salary'], $gross_salary );
			update_post_meta( $post_id, $this->meta_keys['net_salary'], $net_salary );

			$this->add_notice( 'success', __( 'Payroll saved successfully', 'BuildERP' ) );
		}

		// Status handling
		if ( isset( $data['status'] ) ) {
			$new_status     = sanitize_key( $data['status'] );
			$current_status = get_post_meta( $post_id, $this->meta_keys['status'], true );

			// Don't allow changing from paid back to pending via form
			if ( 'paid' !== $current_status ) {
				update_post_meta( $post_id, $this->meta_keys['status'], $new_status );
			}
		}
	}

	/**
	 * Add admin notice
	 *
	 * @param string $type    Notice type (success, error, warning, info)
	 * @param string $message Notice message
	 */
	protected function add_notice( $type, $message ) {
		$notices = get_transient( $this->get_notice_key() );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'type'    => $type,
			'message' => $message,
		);

		set_transient( $this->get_notice_key(), $notices, MINUTE_IN_SECONDS );
	}

	/**
	 * Get notice transient key
	 *
	 * @return string
	 */
	protected function get_notice_key() {
		return 'berp_payroll_notices_' . get_current_user_id();
	}

	/**
	 * Render admin notices
	 */
	public function render_admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'berp_payroll' !== $screen->post_type ) {
			return;
		}

		$notices = get_transient( $this->get_notice_key() );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		delete_transient( $this->get_notice_key() );

		foreach ( $notices as $notice ) {
			$class = 'notice notice-' . sanitize_key( $notice['type'] );
			echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}
	}

	/**
	 * Register list table columns
	 *
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public function register_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new_columns['cb']       = $columns['cb'] ?? '';
				$new_columns['employee'] = __( 'Employee', 'BuildERP' );
				$new_columns['month']    = __( 'Month', 'BuildERP' );
				$new_columns['gross']    = __( 'Gross Salary', 'BuildERP' );
				$new_columns['net']      = __( 'Net Salary', 'BuildERP' );
				$new_columns['status']   = __( 'Status', 'BuildERP' );
				$new_columns['date']     = __( 'Date Created', 'BuildERP' );
			} elseif ( 'date' !== $key && 'title' !== $key ) {
				$new_columns[ $key ] = $label;
			}
		}

		return $new_columns;
	}

	/**
	 * Render list table column content
	 *
	 * @param string $column  Column name
	 * @param int    $post_id Post ID
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'employee':
				$employee_id = get_post_meta( $post_id, $this->meta_keys['employee_id'], true );
				if ( $employee_id ) {
					echo '<a href="' . esc_url( get_edit_post_link( $employee_id ) ) . '">';
					echo esc_html( get_the_title( $employee_id ) );
					echo '</a>';
				} else {
					echo '&mdash;';
				}
				break;

			case 'month':
				$month = get_post_meta( $post_id, $this->meta_keys['month'], true );
				if ( $month ) {
					echo esc_html( gmdate( 'F Y', strtotime( $month . '-01' ) ) );
				} else {
					echo '&mdash;';
				}
				break;

			case 'gross':
				$gross = get_post_meta( $post_id, $this->meta_keys['gross_salary'], true );
				echo esc_html( berp_format_currency( $gross ) );
				break;

			case 'net':
				$net = get_post_meta( $post_id, $this->meta_keys['net_salary'], true );
				echo esc_html( berp_format_currency( $net ) );
				break;

			case 'status':
				$status = get_post_meta( $post_id, $this->meta_keys['status'], true );
				$status = ! empty( $status ) ? $status : 'pending';

				if ( 'paid' === $status ) {
					echo '<span class="berp-badge berp-badge-success">' . esc_html__( 'Paid', 'BuildERP' ) . '</span>';
				} else {
					echo '<span class="berp-badge berp-badge-warning">' . esc_html__( 'Pending', 'BuildERP' ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Register sortable columns
	 *
	 * @param array $columns Existing sortable columns
	 * @return array Modified sortable columns
	 */
	public function register_sortable_columns( $columns ) {
		$columns['month']  = 'month';
		$columns['gross']  = 'gross';
		$columns['net']    = 'net';
		$columns['status'] = 'status';

		return $columns;
	}

	/**
	 * Handle column sorting
	 *
	 * @param WP_Query $query Query object
	 */
	public function handle_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'berp_payroll' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		$meta_mappings = array(
			'month'  => array(
				'key'  => $this->meta_keys['month'],
				'type' => 'CHAR',
			),
			'gross'  => array(
				'key'  => $this->meta_keys['gross_salary'],
				'type' => 'NUMERIC',
			),
			'net'    => array(
				'key'  => $this->meta_keys['net_salary'],
				'type' => 'NUMERIC',
			),
			'status' => array(
				'key'  => $this->meta_keys['status'],
				'type' => 'CHAR',
			),
		);

		if ( isset( $meta_mappings[ $orderby ] ) ) {
			$query->set( 'meta_key', $meta_mappings[ $orderby ]['key'] );
			$query->set( 'orderby', 'meta_value' === $meta_mappings[ $orderby ]['type'] ? 'meta_value' : 'meta_value_num' );
		}
	}

	/**
	 * Add filter dropdowns
	 *
	 * @param string $post_type Current post type
	 */
	public function add_filters( $post_type ) {
		if ( 'berp_payroll' !== $post_type ) {
			return;
		}

		// Month filter
		$this->render_month_filter();

		// Employee filter
		$this->render_employee_filter();

		// Status filter
		$this->render_status_filter();
	}

	/**
	 * Render month filter dropdown
	 */
	protected function render_month_filter() {
		global $wpdb;

		// Get unique months
		$months = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
                WHERE meta_key = %s
                ORDER BY meta_value DESC",
				$this->meta_keys['month']
			)
		);

		if ( empty( $months ) ) {
			return;
		}

		$selected = isset( $_GET['berp_month'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_month'] ) ) : '';
		?>
		<select name="berp_month">
			<option value=""><?php esc_html_e( 'All Months', 'BuildERP' ); ?></option>
			<?php foreach ( $months as $month ) : ?>
				<option value="<?php echo esc_attr( $month ); ?>" <?php selected( $selected, $month ); ?>>
					<?php echo esc_html( gmdate( 'F Y', strtotime( $month . '-01' ) ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render employee filter dropdown
	 */
	protected function render_employee_filter() {
		$employees = get_posts(
			array(
				'post_type'      => 'berp_employee',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $employees ) ) {
			return;
		}

		$selected = isset( $_GET['berp_employee'] ) ? absint( $_GET['berp_employee'] ) : 0;
		?>
		<select name="berp_employee">
			<option value=""><?php esc_html_e( 'All Employees', 'BuildERP' ); ?></option>
			<?php foreach ( $employees as $employee ) : ?>
				<option value="<?php echo esc_attr( $employee->ID ); ?>" <?php selected( $selected, $employee->ID ); ?>>
					<?php echo esc_html( $employee->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render status filter dropdown
	 */
	protected function render_status_filter() {
		$selected = isset( $_GET['berp_status'] ) ? sanitize_key( wp_unslash( $_GET['berp_status'] ) ) : '';
		?>
		<select name="berp_status">
			<option value=""><?php esc_html_e( 'All Statuses', 'BuildERP' ); ?></option>
			<option value="pending" <?php selected( $selected, 'pending' ); ?>><?php esc_html_e( 'Pending', 'BuildERP' ); ?></option>
			<option value="paid" <?php selected( $selected, 'paid' ); ?>><?php esc_html_e( 'Paid', 'BuildERP' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter posts by meta
	 *
	 * @param WP_Query $query Query object
	 */
	public function filter_by_meta( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'berp_payroll' !== $_GET['post_type'] ) {
			return;
		}

		$meta_query = array();

		// Month filter
		if ( isset( $_GET['berp_month'] ) && ! empty( $_GET['berp_month'] ) ) {
			$meta_query[] = array(
				'key'     => $this->meta_keys['month'],
				'value'   => sanitize_text_field( wp_unslash( $_GET['berp_month'] ) ),
				'compare' => '=',
			);
		}

		// Employee filter
		if ( isset( $_GET['berp_employee'] ) && ! empty( $_GET['berp_employee'] ) ) {
			$meta_query[] = array(
				'key'     => $this->meta_keys['employee_id'],
				'value'   => absint( $_GET['berp_employee'] ),
				'compare' => '=',
			);
		}

		// Status filter
		if ( isset( $_GET['berp_status'] ) && ! empty( $_GET['berp_status'] ) ) {
			$meta_query[] = array(
				'key'     => $this->meta_keys['status'],
				'value'   => sanitize_key( wp_unslash( $_GET['berp_status'] ) ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$existing = $query->get( 'meta_query' );
			if ( is_array( $existing ) ) {
				$meta_query = array_merge( $existing, $meta_query );
			}
			$query->set( 'meta_query', $meta_query );
		}
	}
}

