<?php
/**
 * Advance Request List Table
 *
 * Custom columns and filters for the advance requests list
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
 * BERP_Advance_List_Table Class
 */
class BERP_Advance_List_Table {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_filter( 'manage_berp_advance_posts_columns', array( $this, 'set_columns' ) );
		add_action( 'manage_berp_advance_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
		add_filter( 'manage_edit-berp_advance_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_query' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_action_berp_approve_advance', array( $this, 'handle_quick_approve' ) );
		add_action( 'admin_action_berp_reject_advance', array( $this, 'handle_quick_reject' ) );
	}

	/**
	 * Set custom columns
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function set_columns( $columns ) {
		$new_columns = array(
			'cb'             => $columns['cb'],
			'title'          => __( 'Advance ID', 'BuildERP' ),
			'employee'       => __( 'Employee', 'BuildERP' ),
			'amount'         => __( 'Amount', 'BuildERP' ),
			'request_date'   => __( 'Date', 'BuildERP' ),
			'repayment'      => __( 'Repayment', 'BuildERP' ),
			'advance_status' => __( 'Status', 'BuildERP' ),
		);
		return $new_columns;
	}

	/**
	 * Render custom columns
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'employee':
				$employee_id = get_post_meta( $post_id, '_berp_employee_id', true );
				if ( $employee_id ) {
					$employee = get_post( $employee_id );
					if ( $employee ) {
						echo '<a href="' . esc_url( get_edit_post_link( $employee_id ) ) . '">' . esc_html( $employee->post_title ) . '</a>';
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;

			case 'amount':
				$amount = get_post_meta( $post_id, '_berp_advance_amount', true );
				echo esc_html( berp_format_currency( $amount ? $amount : 0 ) );
				break;

			case 'request_date':
				$date = get_post_meta( $post_id, '_berp_request_date', true );
				echo $date ? esc_html( berp_format_date( $date ) ) : '—';
				break;

			case 'repayment':
				$type         = get_post_meta( $post_id, '_berp_repayment_type', true );
				$installments = get_post_meta( $post_id, '_berp_installments', true );
				if ( 'installments' === $type && $installments > 1 ) {
					/* translators: %d: number of installments */
					echo esc_html( sprintf( __( '%d Installments', 'BuildERP' ), $installments ) );
				} else {
					esc_html_e( 'Full', 'BuildERP' );
				}
				break;

			case 'advance_status':
				$status   = get_post_meta( $post_id, '_berp_advance_status', true );
				$statuses = array(
					'pending'  => array(
						'label' => __( 'Pending Approval', 'BuildERP' ),
						'class' => 'berp-badge-warning',
					),
					'approved' => array(
						'label' => __( 'Paid', 'BuildERP' ),
						'class' => 'berp-badge-success',
					),
					'rejected' => array(
						'label' => __( 'Rejected', 'BuildERP' ),
						'class' => 'berp-badge-error',
					),
					'paid'     => array(
						'label' => __( 'Paid', 'BuildERP' ),
						'class' => 'berp-badge-info',
					),
				);

				$status_data = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'];
				echo '<span class="berp-badge ' . esc_attr( $status_data['class'] ) . '">' . esc_html( $status_data['label'] ) . '</span>';
				break;
		}
	}

	/**
	 * Define sortable columns
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['amount']       = 'amount';
		$columns['request_date'] = 'request_date';
		return $columns;
	}

	/**
	 * Add filter dropdowns above list table
	 *
	 * @param string $post_type Current post type.
	 */
	public function add_filters( $post_type ) {
		if ( 'berp_advance' !== $post_type ) {
			return;
		}

		// Status filter.
		$current_status = isset( $_GET['advance_status'] ) ? sanitize_text_field( wp_unslash( $_GET['advance_status'] ) ) : '';
		$statuses       = array(
			''         => __( 'All Statuses', 'BuildERP' ),
			'pending'  => __( 'Pending', 'BuildERP' ),
			'approved' => __( 'Approved', 'BuildERP' ),
			'rejected' => __( 'Rejected', 'BuildERP' ),
			'paid'     => __( 'Paid', 'BuildERP' ),
		);

		echo '<select name="advance_status">';
		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current_status, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		// Employee filter.
		$employees = get_posts(
			array(
				'post_type'      => 'berp_employee',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		if ( ! empty( $employees ) ) {
			$current_employee = isset( $_GET['employee_id'] ) ? absint( $_GET['employee_id'] ) : 0;
			echo '<select name="employee_id">';
			echo '<option value="">' . esc_html__( 'All Employees', 'BuildERP' ) . '</option>';
			foreach ( $employees as $emp ) {
				printf(
					'<option value="%d" %s>%s</option>',
					esc_attr( $emp->ID ),
					selected( $current_employee, $emp->ID, false ),
					esc_html( $emp->post_title )
				);
			}
			echo '</select>';
		}
	}

	/**
	 * Filter query based on selected filters
	 *
	 * @param WP_Query $query The query object.
	 */
	public function filter_query( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || 'berp_advance' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = array();

		// Status filter.
		if ( ! empty( $_GET['advance_status'] ) ) {
			$meta_query[] = array(
				'key'   => '_berp_advance_status',
				'value' => sanitize_text_field( wp_unslash( $_GET['advance_status'] ) ),
			);
		}

		// Employee filter.
		if ( ! empty( $_GET['employee_id'] ) ) {
			$meta_query[] = array(
				'key'   => '_berp_employee_id',
				'value' => absint( $_GET['employee_id'] ),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Handle sorting.
		$orderby = $query->get( 'orderby' );
		if ( 'amount' === $orderby ) {
			$query->set( 'meta_key', '_berp_advance_amount' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'request_date' === $orderby ) {
			$query->set( 'meta_key', '_berp_request_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Add custom row actions
	 *
	 * @param array   $actions Existing actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified actions.
	 */
	public function row_actions( $actions, $post ) {
		if ( 'berp_advance' !== $post->post_type ) {
			return $actions;
		}

		$status = get_post_meta( $post->ID, '_berp_advance_status', true );

		if ( 'pending' === $status && current_user_can( 'berp_manage_advances' ) ) {
			$approve_url = wp_nonce_url(
				admin_url( 'admin.php?action=berp_approve_advance&post=' . $post->ID ),
				'berp_approve_advance_' . $post->ID
			);
			$reject_url  = wp_nonce_url(
				admin_url( 'admin.php?action=berp_reject_advance&post=' . $post->ID ),
				'berp_reject_advance_' . $post->ID
			);

			$actions['approve'] = '<a href="' . esc_url( $approve_url ) . '" style="color: green;">' . esc_html__( 'Approve', 'BuildERP' ) . '</a>';
			$actions['reject']  = '<a href="' . esc_url( $reject_url ) . '" style="color: red;">' . esc_html__( 'Reject', 'BuildERP' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle quick approve action
	 */
	public function handle_quick_approve() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'BuildERP' ) );
		}

		$post_id = absint( $_GET['post'] );

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'berp_approve_advance_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'BuildERP' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'berp_manage_advances' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'BuildERP' ) );
		}

		// Update status.
		update_post_meta( $post_id, '_berp_advance_status', 'approved' );
		update_post_meta( $post_id, '_berp_approved_date', current_time( 'Y-m-d' ) );
		update_post_meta( $post_id, '_berp_approved_by', get_current_user_id() );

		// Update employee balance.
		$employee_id = get_post_meta( $post_id, '_berp_employee_id', true );
		$amount      = get_post_meta( $post_id, '_berp_advance_amount', true );
		if ( $employee_id && $amount ) {
			$current_balance = (float) get_post_meta( $employee_id, '_berp_account_balance', true );
			$new_balance     = $current_balance - floatval( $amount );
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

		// Redirect back to list.
		wp_safe_redirect( admin_url( 'edit.php?post_type=berp_advance&approved=1' ) );
		exit;
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
		/* translators: %s: employee name */
		$expense_title = sprintf( __( 'Salary Advance - %s', 'BuildERP' ), $employee_name );

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
				__( 'Salary advance payment (Advance #%1$d). %2$s', 'BuildERP' ),
				$advance_id,
				$reason ? $reason : ''
			);
			update_post_meta( $expense_id, '_berp_expense_description', $description );

			// Assign to "Salary Advance" expense category.
			$this->assign_salary_advance_category( $expense_id );

			// Link expense back to advance.
			update_post_meta( $advance_id, '_berp_expense_id', $expense_id );
		}
	}

	/**
	 * Assign "Salary Advance" category to an expense.
	 *
	 * @param int $expense_id Expense post ID.
	 */
	private function assign_salary_advance_category( $expense_id ) {
		$category_name = __( 'Salary Advance', 'BuildERP' );

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

	/**
	 * Handle quick reject action
	 */
	public function handle_quick_reject() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'BuildERP' ) );
		}

		$post_id = absint( $_GET['post'] );

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'berp_reject_advance_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'BuildERP' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'berp_manage_advances' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'BuildERP' ) );
		}

		// Update status.
		update_post_meta( $post_id, '_berp_advance_status', 'rejected' );

		/**
		 * Fires when an advance request is rejected.
		 *
		 * @param int    $post_id        Advance post ID.
		 * @param string $rejection_note Rejection reason.
		 */
		do_action( 'berp_advance_rejected', $post_id, '' );

		// Redirect back to list.
		wp_safe_redirect( admin_url( 'edit.php?post_type=berp_advance&rejected=1' ) );
		exit;
	}
}

