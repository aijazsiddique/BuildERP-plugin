<?php
/**
 * Employee metaboxes and admin table columns.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Employee_Metaboxes Class
 *
 * Handles employee CPT metaboxes, saving, and list table columns.
 */
class BERP_Employee_Metaboxes {


	/**
	 * Meta keys used by the employee CPT.
	 *
	 * @var array
	 */
	protected $meta_keys = array(
		'employee_id'           => '_berp_employee_id',
		'email'                 => '_berp_employee_email',
		'phone'                 => '_berp_employee_phone',
		'address'               => '_berp_employee_address',
		'hire_date'             => '_berp_hire_date',
		'status'                => '_berp_employee_status',
		'basic_salary'          => '_berp_basic_salary',
		'allowances'            => '_berp_allowances',
		'deductions'            => '_berp_deductions',
		'account_balance'       => '_berp_account_balance',
		'documents'             => '_berp_documents',
		'portal_access_enabled' => '_berp_portal_access_enabled',
		'linked_user_id'        => '_berp_linked_user_id',
	);

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_berp_employee', array( $this, 'save_employee' ), 10, 2 );
		add_filter( 'manage_berp_employee_posts_columns', array( $this, 'register_columns' ) );
		add_action( 'manage_berp_employee_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-berp_employee_sortable_columns', array( $this, 'register_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_column_sorting' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );

		// AJAX handlers for portal access.
		add_action( 'wp_ajax_berp_resend_credentials', array( $this, 'ajax_resend_credentials' ) );
	}

	/**
	 * Register metaboxes for employee CPT.
	 *
	 * @since 1.0.0
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'berp-employee-main',
			__( 'Employee Details', 'aic_builderp' ),
			array( $this, 'render_main_metabox' ),
			'berp_employee',
			'normal',
			'high'
		);
	}

	/**
	 * Render main employee metabox with tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_main_metabox( $post ) {
		wp_nonce_field( 'berp_save_employee', 'berp_employee_nonce' );

		$values = array(
			'employee_id' => get_post_meta( $post->ID, $this->meta_keys['employee_id'], true ),
			'email'       => get_post_meta( $post->ID, $this->meta_keys['email'], true ),
			'phone'       => get_post_meta( $post->ID, $this->meta_keys['phone'], true ),
			'address'     => get_post_meta( $post->ID, $this->meta_keys['address'], true ),
			'hire_date'   => get_post_meta( $post->ID, $this->meta_keys['hire_date'], true ),
			'status'      => get_post_meta( $post->ID, $this->meta_keys['status'], true ),
		);

		$basic_salary    = get_post_meta( $post->ID, $this->meta_keys['basic_salary'], true );
		$account_balance = get_post_meta( $post->ID, $this->meta_keys['account_balance'], true );
		$allowances      = get_post_meta( $post->ID, $this->meta_keys['allowances'], true );
		$deductions      = get_post_meta( $post->ID, $this->meta_keys['deductions'], true );
		$documents       = get_post_meta( $post->ID, $this->meta_keys['documents'], true );
		$custom_fields   = $this->get_custom_fields_config();

		if ( ! is_array( $allowances ) ) {
			$allowances = array();
		}

		if ( ! is_array( $deductions ) ) {
			$deductions = array();
		}

		if ( ! is_array( $documents ) ) {
			$documents = array();
		}

		$statuses = $this->get_statuses();
		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Employee sections', 'aic_builderp' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="berp-emp-details"><?php esc_html_e( 'Details', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-emp-salary"><?php esc_html_e( 'Salary', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-emp-documents"><?php esc_html_e( 'Documents', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-emp-portal"><?php esc_html_e( 'Portal Access', 'aic_builderp' ); ?></button>
				</nav>

				<div class="berp-metabox-panels">
					<div class="berp-metabox-panel is-active" data-tab-panel="berp-emp-details">
						<div class="berp-metabox-grid">
						<div class="berp-field-group">
							<label for="berp_employee_id"><?php esc_html_e( 'Employee ID', 'aic_builderp' ); ?></label>
							<input type="text" id="berp_employee_id" name="berp_employee_details[employee_id]" value="<?php echo esc_attr( $values['employee_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Unique employee number used across attendance and payroll.', 'aic_builderp' ); ?></p>
						</div>

						<div class="berp-field-group">
							<label for="berp_employee_email"><?php esc_html_e( 'Email', 'aic_builderp' ); ?></label>
							<input type="email" id="berp_employee_email" name="berp_employee_details[email]" value="<?php echo esc_attr( $values['email'] ); ?>" />
						</div>

						<div class="berp-field-group">
							<label for="berp_employee_phone"><?php esc_html_e( 'Phone', 'aic_builderp' ); ?></label>
							<input type="text" id="berp_employee_phone" name="berp_employee_details[phone]" value="<?php echo esc_attr( $values['phone'] ); ?>" />
						</div>

						<div class="berp-field-group">
							<label for="berp_employee_address"><?php esc_html_e( 'Address', 'aic_builderp' ); ?></label>
							<textarea id="berp_employee_address" name="berp_employee_details[address]" rows="3"><?php echo esc_textarea( $values['address'] ); ?></textarea>
						</div>

						<div class="berp-field-group">
							<label for="berp_employee_hire_date"><?php esc_html_e( 'Hire Date', 'aic_builderp' ); ?></label>
							<input type="date" id="berp_employee_hire_date" name="berp_employee_details[hire_date]" value="<?php echo esc_attr( $values['hire_date'] ); ?>" />
						</div>

						<div class="berp-field-group">
							<label for="berp_employee_status"><?php esc_html_e( 'Status', 'aic_builderp' ); ?></label>
							<select id="berp_employee_status" name="berp_employee_details[status]">
								<?php foreach ( $statuses as $status_key => $status_label ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $values['status'], $status_key ); ?>>
									<?php echo esc_html( $status_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

		<?php if ( ! empty( $custom_fields ) ) : ?>
							<div class="berp-custom-fields">
								<h4><?php esc_html_e( 'Custom Fields', 'aic_builderp' ); ?></h4>
								<div class="berp-metabox-grid">
			<?php foreach ( $custom_fields as $field ) : ?>
				<?php
				$field_key   = isset( $field['key'] ) ? $field['key'] : '';
				$field_label = isset( $field['label'] ) ? $field['label'] : '';
				$field_type  = isset( $field['type'] ) ? $field['type'] : 'text';
				$field_req   = ! empty( $field['required'] );
				$field_opts  = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
				if ( ! $field_key ) {
					continue;
				}
				$meta_key    = $this->get_custom_field_meta_key( $field_key );
				$field_value = get_post_meta( $post->ID, $meta_key, true );
				?>
									<div class="berp-field-group">
										<label for="berp_cf_<?php echo esc_attr( $field_key ); ?>">
				<?php echo esc_html( $field_label ); ?>
				<?php
				if ( $field_req ) :
					?>
												<span class="required">*</span>
												<?php
				endif;
				?>
										</label>
				<?php
				switch ( $field_type ) {
					case 'number':
						?>
												<input type="number" id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $field_value ); ?>" />
						<?php
						break;
					case 'date':
						?>
												<input type="date" id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $field_value ); ?>" />
						<?php
						break;
					case 'textarea':
						?>
												<textarea id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]" rows="3"><?php echo esc_textarea( $field_value ); ?></textarea>
						<?php
						break;
					case 'select':
						?>
												<select id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]">
													<option value=""><?php esc_html_e( 'Select', 'aic_builderp' ); ?></option>
						<?php foreach ( $field_opts as $opt ) : ?>
														<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $field_value, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
					<?php endforeach; ?>
												</select>
						<?php
						break;
					case 'checkbox':
						?>
												<label><input type="checkbox" id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]" value="1" <?php checked( ! empty( $field_value ), true ); ?> /> <?php esc_html_e( 'Yes', 'aic_builderp' ); ?></label>
						<?php
						break;
					default:
						?>
												<input type="text" id="berp_cf_<?php echo esc_attr( $field_key ); ?>" name="berp_employee_custom[<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $field_value ); ?>" />
						<?php
						break;
				}
				?>
									</div>
			<?php endforeach; ?>
								</div>
							</div>
		<?php endif; ?>
						</div>
					</div>

					<div class="berp-metabox-panel" data-tab-panel="berp-emp-salary">
						<div class="berp-metabox-grid">
							<div class="berp-field-group">
								<label for="berp_basic_salary"><?php esc_html_e( 'Basic Salary', 'aic_builderp' ); ?></label>
								<input type="number" id="berp_basic_salary" name="berp_employee_salary[basic_salary]" value="<?php echo esc_attr( $basic_salary ); ?>" step="0.01" min="0" />
							</div>

							<div class="berp-field-group">
								<label for="berp_account_balance"><?php esc_html_e( 'Account Balance', 'aic_builderp' ); ?></label>
								<input type="number" id="berp_account_balance" name="berp_employee_salary[account_balance]" value="<?php echo esc_attr( $account_balance ); ?>" step="0.01" />
								<p class="description"><?php esc_html_e( 'Track advances or dues. Negative values indicate employee owes the company.', 'aic_builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label><?php esc_html_e( 'Allowances', 'aic_builderp' ); ?></label>
			<?php $this->render_repeater( 'berp_employee_salary[allowances]', $allowances, __( 'Add Allowance', 'aic_builderp' ) ); ?>
							</div>

							<div class="berp-field-group">
								<label><?php esc_html_e( 'Deductions', 'aic_builderp' ); ?></label>
			<?php $this->render_repeater( 'berp_employee_salary[deductions]', $deductions, __( 'Add Deduction', 'aic_builderp' ) ); ?>
							</div>
						</div>
					</div>

					<div class="berp-metabox-panel" data-tab-panel="berp-emp-documents">
						<div class="berp-metabox-grid">
							<div class="berp-field-group">
								<label><?php esc_html_e( 'Employee Documents', 'aic_builderp' ); ?></label>
			<?php $this->render_document_repeater( 'berp_employee_documents', $documents, __( 'Add Document', 'aic_builderp' ) ); ?>
							</div>
						</div>
					</div>

					<div class="berp-metabox-panel" data-tab-panel="berp-emp-portal">
						<?php $this->render_portal_access_panel( $post ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render documents metabox (unused when tabbed view is active).
	 *
	 * Kept for compatibility if needed later.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_documents_metabox( $post ) {
		$documents = get_post_meta( $post->ID, $this->meta_keys['documents'], true );

		if ( ! is_array( $documents ) ) {
			$documents = array();
		}
		?>
		<div class="berp-metabox-content">
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Documents', 'aic_builderp' ); ?></label>
		<?php $this->render_document_repeater( 'berp_employee_documents', $documents, __( 'Add Document', 'aic_builderp' ) ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save employee data.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_employee( $post_id, $post ) {
		if ( ! isset( $_POST['berp_employee_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_employee_nonce'] ) ), 'berp_save_employee' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_edit_employees', $post_id ) ) {
			return;
		}

		$details = isset( $_POST['berp_employee_details'] ) ? (array) wp_unslash( $_POST['berp_employee_details'] ) : array();
		$salary  = isset( $_POST['berp_employee_salary'] ) ? (array) wp_unslash( $_POST['berp_employee_salary'] ) : array();
		$docs    = isset( $_POST['berp_employee_documents'] ) ? (array) wp_unslash( $_POST['berp_employee_documents'] ) : array();

		$employee_id = isset( $details['employee_id'] ) ? sanitize_text_field( $details['employee_id'] ) : '';
		$email       = isset( $details['email'] ) ? sanitize_email( $details['email'] ) : '';

		if ( ! empty( $employee_id ) && $this->is_duplicate_meta( $this->meta_keys['employee_id'], $employee_id, $post_id ) ) {
			$this->add_notice( 'error', __( 'Employee ID must be unique. Duplicate ID found.', 'aic_builderp' ) );
			$employee_id = get_post_meta( $post_id, $this->meta_keys['employee_id'], true );
		}

		if ( ! empty( $email ) && ! is_email( $email ) ) {
			$this->add_notice( 'error', __( 'Please enter a valid email address.', 'aic_builderp' ) );
			$email = get_post_meta( $post_id, $this->meta_keys['email'], true );
		} elseif ( ! empty( $email ) && $this->is_duplicate_meta( $this->meta_keys['email'], $email, $post_id ) ) {
			$this->add_notice( 'error', __( 'Email must be unique. Duplicate email found.', 'aic_builderp' ) );
			$email = get_post_meta( $post_id, $this->meta_keys['email'], true );
		}

		$this->update_meta( $post_id, $this->meta_keys['employee_id'], $employee_id );
		$this->update_meta( $post_id, $this->meta_keys['email'], $email );
		$this->update_meta( $post_id, $this->meta_keys['phone'], isset( $details['phone'] ) ? sanitize_text_field( $details['phone'] ) : '' );
		$this->update_meta( $post_id, $this->meta_keys['address'], isset( $details['address'] ) ? sanitize_textarea_field( $details['address'] ) : '' );
		$this->update_meta( $post_id, $this->meta_keys['hire_date'], isset( $details['hire_date'] ) ? sanitize_text_field( $details['hire_date'] ) : '' );

		$status = isset( $details['status'] ) ? sanitize_key( $details['status'] ) : 'active';
		$status = array_key_exists( $status, $this->get_statuses() ) ? $status : 'active';
		$this->update_meta( $post_id, $this->meta_keys['status'], $status );

		$basic_salary    = ( isset( $salary['basic_salary'] ) && '' !== $salary['basic_salary'] ) ? $this->sanitize_money( $salary['basic_salary'] ) : '';
		$account_balance = ( isset( $salary['account_balance'] ) && '' !== $salary['account_balance'] ) ? $this->sanitize_money( $salary['account_balance'], true ) : '';

		$this->update_meta( $post_id, $this->meta_keys['basic_salary'], $basic_salary );
		$this->update_meta( $post_id, $this->meta_keys['account_balance'], $account_balance );
		$this->update_meta( $post_id, $this->meta_keys['allowances'], $this->sanitize_money_rows( isset( $salary['allowances'] ) ? $salary['allowances'] : array() ) );
		$this->update_meta( $post_id, $this->meta_keys['deductions'], $this->sanitize_money_rows( isset( $salary['deductions'] ) ? $salary['deductions'] : array() ) );
		$this->update_meta( $post_id, $this->meta_keys['documents'], $this->sanitize_document_rows( $docs ) );

		$custom_input  = isset( $_POST['berp_employee_custom'] ) ? (array) wp_unslash( $_POST['berp_employee_custom'] ) : array();
		$custom_config = $this->get_custom_fields_config();
		if ( ! empty( $custom_config ) ) {
			foreach ( $custom_config as $field ) {
				$key = isset( $field['key'] ) ? $field['key'] : '';
				if ( ! $key ) {
					continue;
				}
				$type     = isset( $field['type'] ) ? $field['type'] : 'text';
				$options  = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
				$value    = isset( $custom_input[ $key ] ) ? $custom_input[ $key ] : '';
				$clean    = $this->sanitize_custom_value( $type, $value, $options );
				$meta_key = $this->get_custom_field_meta_key( $key );
				$this->update_meta( $post_id, $meta_key, $clean );
			}
		}

		// Handle portal access.
		$portal_data = isset( $_POST['berp_portal_access'] ) ? (array) wp_unslash( $_POST['berp_portal_access'] ) : array();
		$this->handle_portal_access( $post_id, $post, $portal_data, $email );

		// Log activity.
		if ( function_exists( 'berp_log_activity' ) ) {
			berp_log_activity(
				'employee_updated',
				sprintf( 'Employee updated: %s', $post->post_title ),
				array(
					'employee_id'   => $post_id,
					'employee_code' => $employee_id,
					'status'        => $status,
				)
			);
		}
	}

	/**
	 * Register list table columns.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $columns Current columns.
	 * @return array
	 */
	public function register_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['employee_id']   = __( 'Employee ID', 'aic_builderp' );
				$new_columns['status']        = __( 'Status', 'aic_builderp' );
				$new_columns['basic_salary']  = __( 'Basic Salary', 'aic_builderp' );
				$new_columns['hire_date']     = __( 'Hire Date', 'aic_builderp' );
				$new_columns['portal_access'] = __( 'Portal Access', 'aic_builderp' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'employee_id':
				echo esc_html( get_post_meta( $post_id, $this->meta_keys['employee_id'], true ) );
				break;
			case 'status':
				$status   = get_post_meta( $post_id, $this->meta_keys['status'], true );
				$statuses = $this->get_statuses();
				echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Active', 'aic_builderp' ) );
				break;
			case 'basic_salary':
				$salary = get_post_meta( $post_id, $this->meta_keys['basic_salary'], true );
				echo '' !== $salary ? esc_html( berp_format_currency( $salary ) ) : '&mdash;';
				break;
			case 'hire_date':
				$hire_date = get_post_meta( $post_id, $this->meta_keys['hire_date'], true );
				echo $hire_date ? esc_html( berp_format_date( $hire_date ) ) : '&mdash;';
				break;
			case 'portal_access':
				$portal_enabled = get_post_meta( $post_id, $this->meta_keys['portal_access_enabled'], true );
				$linked_user_id = get_post_meta( $post_id, $this->meta_keys['linked_user_id'], true );
				$user_exists    = $linked_user_id && get_userdata( $linked_user_id );

				if ( $portal_enabled && $user_exists ) {
					echo '<span class="berp-portal-badge berp-portal-enabled">' . esc_html__( 'Enabled', 'aic_builderp' ) . '</span>';
				} elseif ( $portal_enabled && ! $user_exists ) {
					echo '<span class="berp-portal-badge berp-portal-pending">' . esc_html__( 'Pending', 'aic_builderp' ) . '</span>';
				} else {
					echo '<span class="berp-portal-badge berp-portal-disabled">' . esc_html__( 'Disabled', 'aic_builderp' ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Register sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $columns Sortable columns.
	 * @return array
	 */
	public function register_sortable_columns( $columns ) {
		$columns['employee_id']  = 'employee_id';
		$columns['basic_salary'] = 'basic_salary';
		$columns['hire_date']    = 'hire_date';
		$columns['status']       = 'status';

		return $columns;
	}

	/**
	 * Handle sorting for custom columns.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query instance.
	 */
	public function handle_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_employee' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'employee_id':
				$query->set( 'meta_key', $this->meta_keys['employee_id'] );
				$query->set( 'orderby', 'meta_value' );
				break;
			case 'basic_salary':
				$query->set( 'meta_key', $this->meta_keys['basic_salary'] );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			case 'hire_date':
				$query->set( 'meta_key', $this->meta_keys['hire_date'] );
				$query->set( 'orderby', 'meta_value' );
				break;
			case 'status':
				$query->set( 'meta_key', $this->meta_keys['status'] );
				$query->set( 'orderby', 'meta_value' );
				break;
		}
	}

	/**
	 * Render admin notices set during save_post.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_notices() {
		$notices = get_transient( $this->get_notice_key() );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		delete_transient( $this->get_notice_key() );

		foreach ( $notices as $notice ) {
			$type    = isset( $notice['type'] ) ? $notice['type'] : 'info';
			$message = isset( $notice['message'] ) ? $notice['message'] : '';

			if ( empty( $message ) ) {
				continue;
			}

			$class = 'notice notice-info';
			if ( 'error' === $type ) {
				$class = 'notice notice-error';
			} elseif ( 'success' === $type ) {
				$class = 'notice notice-success';
			} elseif ( 'warning' === $type ) {
				$class = 'notice notice-warning';
			}

			echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Get available employee statuses.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_statuses() {
		return array(
			'active'   => __( 'Active', 'aic_builderp' ),
			'inactive' => __( 'Inactive', 'aic_builderp' ),
			'on_leave' => __( 'On Leave', 'aic_builderp' ),
		);
	}

	/**
	 * Render a simple repeater (label + amount).
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Base input name.
	 * @param array  $rows      Existing rows.
	 * @param string $add_label Button label.
	 */
	protected function render_repeater( $name, $rows, $add_label ) {
		$count = ( is_array( $rows ) && ! empty( $rows ) ) ? count( $rows ) : 0;
		?>
		<div class="berp-repeater">
		<?php
		if ( ! empty( $rows ) ) {
			$index = 0;
			foreach ( $rows as $row ) {
					$this->render_repeater_row( $name, $row, false, $index );
					++$index;
			}
		}
		?>
		<?php
		$this->render_repeater_row(
			$name,
			array(
				'label'  => '',
				'amount' => '',
			),
			true,
			'__INDEX__'
		);
		?>
			<input type="hidden" class="berp-repeater-next" value="<?php echo esc_attr( $count ); ?>" />
			<button type="button" class="button button-secondary berp-repeater-add"><?php echo esc_html( $add_label ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render a repeater row (label + amount).
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name        Base input name.
	 * @param array      $row         Row data.
	 * @param bool       $is_template Whether this is the template row.
	 * @param int|string $index       Row index or placeholder.
	 */
	protected function render_repeater_row( $name, $row, $is_template = false, $index = 0 ) {
		$label      = isset( $row['label'] ) ? $row['label'] : '';
		$amount     = isset( $row['amount'] ) ? $row['amount'] : '';
		$advance_id = isset( $row['advance_id'] ) ? absint( $row['advance_id'] ) : 0;
		$type       = isset( $row['type'] ) ? $row['type'] : '';
		$is_advance = ( 'advance_repayment' === $type && $advance_id > 0 );
		$class      = $is_template ? 'berp-repeater-item berp-repeater-template berp-hidden' : 'berp-repeater-item';
		$style      = $is_template ? 'style="display:none;" aria-hidden="true"' : '';
		$key        = $is_template ? '__INDEX__' : intval( $index );
		$disabled   = $is_template ? 'disabled="disabled"' : '';

		// Get advance info if this is an advance deduction
		$advance_info = '';
		if ( $is_advance ) {
			$remaining    = get_post_meta( $advance_id, '_berp_remaining_amount', true );
			$total        = get_post_meta( $advance_id, '_berp_advance_amount', true );
			$paid         = get_post_meta( $advance_id, '_berp_installments_paid', true );
			$total_inst   = get_post_meta( $advance_id, '_berp_installments', true );
			$advance_info = sprintf(
				/* translators: 1: Remaining amount, 2: Total amount, 3: Paid installments, 4: Total installments */
				__( 'Remaining: %1$s of %2$s | Installments: %3$d/%4$d', 'aic_builderp' ),
				berp_format_currency( $remaining ),
				berp_format_currency( $total ),
				absint( $paid ),
				absint( $total_inst )
			);
			$class .= ' berp-advance-deduction';
		}
		?>
		<div class="<?php echo esc_attr( $class ); ?>" <?php echo $style; ?>>
			<?php if ( ! $is_advance ) : ?>
				<span class="berp-repeater-remove dashicons dashicons-no-alt" aria-label="<?php esc_attr_e( 'Remove item', 'aic_builderp' ); ?>"></span>
			<?php else : ?>
				<span class="berp-advance-icon dashicons dashicons-money-alt" title="<?php esc_attr_e( 'Advance Repayment (Auto-managed)', 'aic_builderp' ); ?>"></span>
			<?php endif; ?>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Label', 'aic_builderp' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" <?php echo $disabled; ?> <?php echo $is_advance ? 'readonly' : ''; ?> />
			</div>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Amount', 'aic_builderp' ); ?></label>
				<input type="number" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][amount]" value="<?php echo esc_attr( $amount ); ?>" step="0.01" min="0" <?php echo $disabled; ?> <?php echo $is_advance ? 'readonly' : ''; ?> />
			</div>
			<?php if ( $is_advance ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][advance_id]" value="<?php echo esc_attr( $advance_id ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][type]" value="advance_repayment" />
				<div class="berp-advance-info">
					<small><?php echo esc_html( $advance_info ); ?></small>
					<a href="<?php echo esc_url( get_edit_post_link( $advance_id ) ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View Advance', 'aic_builderp' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render document repeater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Base input name.
	 * @param array  $rows      Existing rows.
	 * @param string $add_label Button label.
	 */
	protected function render_document_repeater( $name, $rows, $add_label ) {
		$count = ( is_array( $rows ) && ! empty( $rows ) ) ? count( $rows ) : 0;
		?>
		<div class="berp-repeater">
		<?php
		if ( ! empty( $rows ) ) {
			$index = 0;
			foreach ( $rows as $row ) {
					$this->render_document_row( $name, $row, false, $index );
					++$index;
			}
		}
		?>
		<?php
		$this->render_document_row(
			$name,
			array(
				'label' => '',
				'url'   => '',
			),
			true,
			'__INDEX__'
		);
		?>
			<input type="hidden" class="berp-repeater-next" value="<?php echo esc_attr( $count ); ?>" />
			<button type="button" class="button button-secondary berp-repeater-add"><?php echo esc_html( $add_label ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render a document repeater row.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name        Base input name.
	 * @param array      $row         Row data.
	 * @param bool       $is_template Template flag.
	 * @param int|string $index       Row index or placeholder.
	 */
	protected function render_document_row( $name, $row, $is_template = false, $index = 0 ) {
		$label    = isset( $row['label'] ) ? $row['label'] : '';
		$url      = isset( $row['url'] ) ? $row['url'] : '';
		$class    = $is_template ? 'berp-repeater-item berp-repeater-template berp-hidden' : 'berp-repeater-item';
		$style    = $is_template ? 'style="display:none;" aria-hidden="true"' : '';
		$key      = $is_template ? '__INDEX__' : intval( $index );
		$disabled = $is_template ? 'disabled="disabled"' : '';
		?>
		<div class="<?php echo esc_attr( $class ); ?>" <?php echo $style; ?>>
			<span class="berp-repeater-remove dashicons dashicons-no-alt" aria-label="<?php esc_attr_e( 'Remove item', 'aic_builderp' ); ?>"></span>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'Document Name', 'aic_builderp' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" <?php echo $disabled; ?> />
			</div>
			<div class="berp-field-group">
				<label><?php esc_html_e( 'File URL', 'aic_builderp' ); ?></label>
				<div class="berp-media-field">
					<input type="url" class="berp-media-target" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" <?php echo $disabled; ?> />
					<button type="button" class="button button-secondary berp-media-upload"><?php esc_html_e( 'Select File', 'aic_builderp' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Upload or paste a link to employee documents (ID copies, certificates, contracts).', 'aic_builderp' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize monetary rows (allowances/deductions).
	 *
	 * @since 1.0.0
	 *
	 * @param  array $rows Raw rows.
	 * @return array
	 */
	protected function sanitize_money_rows( $rows ) {
		$clean = array();

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return $clean;
		}

		foreach ( $rows as $row ) {
			$label      = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
			$raw_amount = isset( $row['amount'] ) ? $row['amount'] : '';
			$amount     = '' === $raw_amount ? '' : $this->sanitize_money( $raw_amount );

			if ( '' === $label && '' === $amount && '' === $raw_amount ) {
				continue;
			}

			$clean[] = array(
				'label'  => $label,
				'amount' => $amount,
			);
		}

		return $clean;
	}

	/**
	 * Sanitize document rows.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $rows Raw rows.
	 * @return array
	 */
	protected function sanitize_document_rows( $rows ) {
		$clean = array();

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return $clean;
		}

		foreach ( $rows as $row ) {
			$label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
			$url   = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';

			if ( '' === $label && '' === $url ) {
				continue;
			}

			$clean[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		return $clean;
	}

	/**
	 * Sanitize currency/number field.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $value          Raw value.
	 * @param  bool  $allow_negative Allow negative values.
	 * @return string
	 */
	protected function sanitize_money( $value, $allow_negative = false ) {
		$number = floatval( $value );

		if ( ! $allow_negative ) {
			$number = $number < 0 ? 0 : $number;
		}

		return number_format( $number, 2, '.', '' );
	}

	/**
	 * Update or delete meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Value.
	 */
	protected function update_meta( $post_id, $key, $value ) {
		if ( ( is_array( $value ) && empty( $value ) ) || '' === $value || null === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Check for duplicate meta value on employee CPT.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $meta_key Meta key to check.
	 * @param  string $value    Value to check.
	 * @param  int    $post_id  Current post ID to exclude.
	 * @return bool
	 */
	protected function is_duplicate_meta( $meta_key, $value, $post_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'berp_employee',
				'post__not_in'   => array( $post_id ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => $meta_key,
						'value' => $value,
					),
				),
			)
		);

		return $query->have_posts();
	}

	/**
	 * Get custom field configuration from settings.
	 *
	 * @return array
	 */
	protected function get_custom_fields_config() {
		$settings = get_option( 'berp_settings', array() );
		if ( isset( $settings['employee']['custom_fields'] ) && is_array( $settings['employee']['custom_fields'] ) ) {
			return $settings['employee']['custom_fields'];
		}
		return array();
	}

	/**
	 * Build meta key for a custom field.
	 *
	 * @param  string $field_key Field key.
	 * @return string
	 */
	protected function get_custom_field_meta_key( $field_key ) {
		return '_berp_cf_' . sanitize_key( $field_key );
	}

	/**
	 * Sanitize custom field value by type.
	 *
	 * @param  string $type    Field type.
	 * @param  mixed  $value   Raw value.
	 * @param  array  $options Allowed options for select.
	 * @return mixed
	 */
	protected function sanitize_custom_value( $type, $value, $options = array() ) {
		switch ( $type ) {
			case 'number':
				return '' === $value ? '' : floatval( $value );
			case 'date':
				return sanitize_text_field( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'select':
				$allowed = is_array( $options ) ? $options : array();
				return in_array( $value, $allowed, true ) ? sanitize_text_field( $value ) : '';
			case 'checkbox':
				return ! empty( $value ) ? 1 : 0;
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Add an admin notice for the current user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type    Notice type.
	 * @param string $message Message text.
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
	 * Get notice cache key for current user.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_notice_key() {
		return 'berp_employee_notices_' . get_current_user_id();
	}

	/**
	 * Handle portal access enable/disable and user creation.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id     Employee post ID.
	 * @param WP_Post $post        Employee post object.
	 * @param array   $portal_data Portal access form data.
	 * @param string  $email       Employee email address.
	 */
	protected function handle_portal_access( $post_id, $post, $portal_data, $email ) {
		$portal_enabled   = ! empty( $portal_data['enabled'] );
		$previous_enabled = get_post_meta( $post_id, $this->meta_keys['portal_access_enabled'], true );
		$linked_user_id   = get_post_meta( $post_id, $this->meta_keys['linked_user_id'], true );
		$user_exists      = $linked_user_id && get_userdata( $linked_user_id );

		// Update portal access status.
		update_post_meta( $post_id, $this->meta_keys['portal_access_enabled'], $portal_enabled ? '1' : '0' );

		// If portal access is enabled.
		if ( $portal_enabled ) {
			// Validate email exists.
			if ( empty( $email ) || ! is_email( $email ) ) {
				$this->add_notice( 'error', __( 'Cannot enable portal access: Valid email address required.', 'aic_builderp' ) );
				update_post_meta( $post_id, $this->meta_keys['portal_access_enabled'], '0' );
				return;
			}

			// Check if user already exists.
			if ( $user_exists ) {
				// User exists - just restore the role.
				$user = new WP_User( $linked_user_id );
				if ( ! in_array( 'berp_employee', $user->roles, true ) ) {
					$user->add_role( 'berp_employee' );
					$this->add_notice( 'success', __( 'Portal access re-enabled successfully.', 'aic_builderp' ) );

					/**
					 * Fires after portal access is re-enabled for an employee.
					 *
					 * @since 1.0.0
					 *
					 * @param int $post_id  Employee post ID.
					 * @param int $user_id  WordPress user ID.
					 */
					do_action( 'berp_portal_access_enabled', $post_id, $linked_user_id );
				}
			} else {
				// No user exists - create new WordPress user account.
				$user_result = $this->create_portal_user( $post_id, $post, $email );

				if ( is_wp_error( $user_result ) ) {
					$this->add_notice(
						'error',
						sprintf(
						/* translators: %s: error message */
							__( 'Error creating portal user: %s', 'aic_builderp' ),
							$user_result->get_error_message()
						)
					);
					update_post_meta( $post_id, $this->meta_keys['portal_access_enabled'], '0' );
					return;
				}

				// Store user ID and send credentials.
				$user_id = $user_result;
				update_post_meta( $post_id, $this->meta_keys['linked_user_id'], $user_id );

				// Add bidirectional link from user to employee.
				update_user_meta( $user_id, '_berp_employee_id', $post_id );

				// Send credentials email.
				$email_sent = $this->send_portal_credentials_email( $post_id, $user_id );

				if ( $email_sent ) {
					$this->add_notice( 'success', __( 'Portal access enabled successfully. Credentials sent to employee email.', 'aic_builderp' ) );
				} else {
					$this->add_notice( 'warning', __( 'Portal access enabled, but email notification failed to send. Please manually reset the password.', 'aic_builderp' ) );
				}

				/**
				 * Fires after portal access is enabled for an employee.
				 *
				 * @since 1.0.0
				 *
				 * @param int $post_id  Employee post ID.
				 * @param int $user_id  WordPress user ID.
				 */
				do_action( 'berp_portal_access_enabled', $post_id, $user_id );
			}
		} else {
			// Portal access is disabled - remove user role but keep account.
			if ( $user_exists ) {
				$user = new WP_User( $linked_user_id );
				if ( in_array( 'berp_employee', $user->roles, true ) ) {
					$user->remove_role( 'berp_employee' );

					/**
					 * Fires after portal access is disabled for an employee.
					 *
					 * @since 1.0.0
					 *
					 * @param int $post_id  Employee post ID.
					 * @param int $user_id  WordPress user ID.
					 */
					do_action( 'berp_portal_access_disabled', $post_id, $linked_user_id );
				}
			}
		}
	}

	/**
	 * Create WordPress user account for employee portal access.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $employee_id   Employee post ID.
	 * @param WP_Post $employee_post Employee post object.
	 * @param string  $email         Employee email address.
	 * @return int|WP_Error          User ID on success, WP_Error on failure.
	 */
	protected function create_portal_user( $employee_id, $employee_post, $email ) {
		// Generate username from employee name.
		$username = $this->generate_portal_username( $employee_post->post_title, $employee_id );

		// Check if username already exists (shouldn't happen, but be safe).
		if ( username_exists( $username ) ) {
			$username = $username . '_' . $employee_id;
		}

		// Check if email already exists.
		if ( email_exists( $email ) ) {
			return new WP_Error( 'email_exists', __( 'An account with this email address already exists.', 'aic_builderp' ) );
		}

		// Generate random password.
		$password = wp_generate_password( 12, true, true );

		/**
		 * Filters the portal username before user creation.
		 *
		 * @since 1.0.0
		 *
		 * @param string  $username      Generated username.
		 * @param int     $employee_id   Employee post ID.
		 * @param WP_Post $employee_post Employee post object.
		 */
		$username = apply_filters( 'berp_portal_username', $username, $employee_id, $employee_post );

		// Create user.
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Set user role.
		$user = new WP_User( $user_id );
		$user->set_role( 'berp_employee' );

		// Update user data.
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $employee_post->post_title,
				'first_name'   => $employee_post->post_title,
			)
		);

		return $user_id;
	}

	/**
	 * Generate portal username from employee name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $employee_name Employee name.
	 * @param int    $employee_id   Employee post ID.
	 * @return string               Generated username.
	 */
	protected function generate_portal_username( $employee_name, $employee_id ) {
		// Convert to lowercase and remove special characters.
		$username = strtolower( $employee_name );
		$username = preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', $username ) );

		// Limit length.
		$username = substr( $username, 0, 50 );

		// Ensure it's not empty.
		if ( empty( $username ) ) {
			$username = 'employee_' . $employee_id;
		}

		return $username;
	}

	/**
	 * AJAX handler for resending portal credentials.
	 *
	 * @since 1.0.0
	 */
	public function ajax_resend_credentials() {
		// Verify nonce.
		check_ajax_referer( 'berp_admin_nonce', 'nonce' );

		// Check capabilities.
		if ( ! current_user_can( 'berp_manage_employees' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'aic_builderp' ),
				)
			);
		}

		// Get employee ID.
		$employee_id = isset( $_POST['employee_id'] ) ? absint( $_POST['employee_id'] ) : 0;

		if ( ! $employee_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid employee ID.', 'aic_builderp' ),
				)
			);
		}

		// Get linked user ID.
		$linked_user_id = get_post_meta( $employee_id, $this->meta_keys['linked_user_id'], true );

		if ( ! $linked_user_id || ! get_userdata( $linked_user_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No user account found for this employee.', 'aic_builderp' ),
				)
			);
		}

		// Send credentials email.
		$email_sent = $this->send_portal_credentials_email( $employee_id, $linked_user_id );

		if ( $email_sent ) {
			wp_send_json_success(
				array(
					'message' => __( 'Password reset email sent successfully.', 'aic_builderp' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to send email. Please check your WordPress email configuration.', 'aic_builderp' ),
				)
			);
		}
	}

	/**
	 * Send portal credentials email to employee.
	 *
	 * @since 1.0.0
	 *
	 * @param int $employee_id Employee post ID.
	 * @param int $user_id     WordPress user ID.
	 * @return bool            True on success, false on failure.
	 */
	protected function send_portal_credentials_email( $employee_id, $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$employee      = get_post( $employee_id );
		$employee_name = $employee ? $employee->post_title : __( 'Employee', 'aic_builderp' );

		// Get portal settings.
		$general_settings = berp_get_general_settings();
		$company_name     = ! empty( $general_settings['company_name'] ) ? $general_settings['company_name'] : get_bloginfo( 'name' );

		// Generate password reset key.
		$reset_key = get_password_reset_key( $user );
		if ( is_wp_error( $reset_key ) ) {
			return false;
		}

		$reset_url = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $user->user_login ), 'login' );
		$login_url = wp_login_url();

		// Email subject.
		$subject = sprintf(
			/* translators: %s: company name */
			__( 'Your Employee Portal Access - %s', 'aic_builderp' ),
			$company_name
		);

		// Email message.
		$message = sprintf(
			/* translators: %s: employee name */
			__( 'Hello %s,', 'aic_builderp' ),
			$employee_name
		) . "\r\n\r\n";

		$message .= __( 'Your employee portal account has been created. You can now access your attendance records, salary information, and account statements online.', 'aic_builderp' ) . "\r\n\r\n";

		$message .= __( 'Login Details:', 'aic_builderp' ) . "\r\n";
		$message .= sprintf(
			/* translators: %s: username */
			__( 'Username: %s', 'aic_builderp' ),
			$user->user_login
		) . "\r\n";
		$message .= sprintf(
			/* translators: %s: email address */
			__( 'Email: %s', 'aic_builderp' ),
			$user->user_email
		) . "\r\n\r\n";

		$message .= __( 'To set your password and access the portal, please click the link below:', 'aic_builderp' ) . "\r\n";
		$message .= $reset_url . "\r\n\r\n";

		$message .= __( 'After setting your password, you can log in at:', 'aic_builderp' ) . "\r\n";
		$message .= $login_url . "\r\n\r\n";

		$message .= __( 'If you did not request this account, please contact your administrator.', 'aic_builderp' ) . "\r\n\r\n";

		$message .= sprintf(
			/* translators: %s: company name */
			__( 'Thank you,%s', 'aic_builderp' ),
			"\r\n" . $company_name
		);

		/**
		 * Filters the portal credentials email message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message      Email message body.
		 * @param int    $employee_id  Employee post ID.
		 * @param int    $user_id      WordPress user ID.
		 * @param string $reset_url    Password reset URL.
		 */
		$message = apply_filters( 'berp_portal_credentials_email_message', $message, $employee_id, $user_id, $reset_url );

		/**
		 * Filters the portal credentials email subject.
		 *
		 * @since 1.0.0
		 *
		 * @param string $subject      Email subject.
		 * @param int    $employee_id  Employee post ID.
		 * @param int    $user_id      WordPress user ID.
		 */
		$subject = apply_filters( 'berp_portal_credentials_email_subject', $subject, $employee_id, $user_id );

		// Send email.
		return wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Render Portal Access panel.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 */
	protected function render_portal_access_panel( $post ) {
		// Get portal access data.
		$portal_enabled = get_post_meta( $post->ID, $this->meta_keys['portal_access_enabled'], true );
		$linked_user_id = get_post_meta( $post->ID, $this->meta_keys['linked_user_id'], true );

		// Check if WordPress user exists.
		$user_exists = false;
		$user_data   = null;
		if ( $linked_user_id ) {
			$user_data   = get_userdata( $linked_user_id );
			$user_exists = ( $user_data !== false );
		}

		// Get employee email for portal access.
		$employee_email = get_post_meta( $post->ID, $this->meta_keys['email'], true );
		?>
		<div class="berp-portal-access-panel">
			<div class="berp-metabox-grid">
				<div class="berp-field-group berp-portal-toggle-wrapper">
					<label for="berp_portal_access_enabled">
						<input
							type="checkbox"
							id="berp_portal_access_enabled"
							name="berp_portal_access[enabled]"
							value="1"
							<?php checked( $portal_enabled, '1' ); ?>
							<?php disabled( empty( $employee_email ) ); ?>
						/>
						<?php esc_html_e( 'Enable Portal Access', 'aic_builderp' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Allow this employee to log in to the employee portal to view attendance, salary, and account statement.', 'aic_builderp' ); ?>
					</p>
					<?php if ( empty( $employee_email ) ) : ?>
						<p class="description berp-error-text">
							<?php esc_html_e( 'Please add an email address in the Details tab before enabling portal access.', 'aic_builderp' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<?php if ( $portal_enabled && $user_exists && $user_data ) : ?>
					<div class="berp-portal-info-box berp-portal-active">
						<h4><?php esc_html_e( 'Portal Access: Active', 'aic_builderp' ); ?></h4>

						<table class="berp-portal-info-table">
							<tr>
								<th><?php esc_html_e( 'Username:', 'aic_builderp' ); ?></th>
								<td><code><?php echo esc_html( $user_data->user_login ); ?></code></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Email:', 'aic_builderp' ); ?></th>
								<td><?php echo esc_html( $user_data->user_email ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'User ID:', 'aic_builderp' ); ?></th>
								<td><?php echo esc_html( $linked_user_id ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Account Created:', 'aic_builderp' ); ?></th>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $user_data->user_registered ) ) ); ?></td>
							</tr>
						</table>

						<div class="berp-portal-actions">
							<button type="button" class="button button-secondary berp-resend-credentials" data-employee-id="<?php echo esc_attr( $post->ID ); ?>">
								<?php esc_html_e( 'Resend Login Credentials', 'aic_builderp' ); ?>
							</button>
							<span class="spinner"></span>
							<span class="berp-credentials-status"></span>
						</div>

						<p class="description">
							<?php esc_html_e( 'A password reset email will be sent to the employee\'s email address.', 'aic_builderp' ); ?>
						</p>
					</div>
				<?php elseif ( $portal_enabled && ! $user_exists ) : ?>
					<div class="berp-portal-info-box berp-portal-pending">
						<h4><?php esc_html_e( 'Portal Access: Pending Setup', 'aic_builderp' ); ?></h4>
						<p><?php esc_html_e( 'Save this employee to create the WordPress user account and send login credentials.', 'aic_builderp' ); ?></p>
						<p class="description">
							<?php
							printf(
								/* translators: %s: employee email address */
								esc_html__( 'A user account will be created with username generated from employee name and credentials will be sent to %s', 'aic_builderp' ),
								'<strong>' . esc_html( $employee_email ) . '</strong>'
							);
							?>
						</p>
					</div>
				<?php elseif ( ! $portal_enabled && $user_exists ) : ?>
					<div class="berp-portal-info-box berp-portal-inactive">
						<h4><?php esc_html_e( 'Portal Access: Disabled', 'aic_builderp' ); ?></h4>
						<p><?php esc_html_e( 'Portal access is currently disabled for this employee. Enable the checkbox above to grant access.', 'aic_builderp' ); ?></p>
						<p class="description">
							<?php esc_html_e( 'Note: The WordPress user account still exists and can be re-enabled at any time.', 'aic_builderp' ); ?>
						</p>
					</div>
				<?php else : ?>
					<div class="berp-portal-info-box berp-portal-disabled">
						<h4><?php esc_html_e( 'Portal Access: Not Configured', 'aic_builderp' ); ?></h4>
						<p><?php esc_html_e( 'Enable the checkbox above and save to create a portal account for this employee.', 'aic_builderp' ); ?></p>
					</div>
				<?php endif; ?>

				<?php
				// Show orphaned account warning.
				if ( $linked_user_id && ! $user_exists ) :
					?>
					<div class="berp-portal-warning">
						<p>
							<strong><?php esc_html_e( 'Warning:', 'aic_builderp' ); ?></strong>
							<?php
							printf(
								/* translators: %d: WordPress user ID */
								esc_html__( 'This employee was linked to WordPress user ID %d, but that user account no longer exists. Enabling portal access will create a new user account.', 'aic_builderp' ),
								absint( $linked_user_id )
							);
							?>
						</p>
					</div>
					<?php
				endif;
				?>
			</div>
		</div>
		<?php
	}
}
