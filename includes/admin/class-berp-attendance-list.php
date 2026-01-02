<?php
/**
 * Attendance list table customizations.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Attendance_List Class
 *
 * Handles admin list columns, filters, sorting, and export for attendance CPT.
 */
class BERP_Attendance_List {

	/**
	 * Meta keys used by attendance CPT.
	 *
	 * @var array
	 */
	protected $meta_keys = array(
		'employee'  => '_berp_employee_id',
		'date'      => '_berp_date',
		'site'      => '_berp_site_id',
		'overtime'  => '_berp_overtime_hours',
		'logged_by' => '_berp_logged_by',
	);

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_filter( 'manage_berp_attendance_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_attendance_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_attendance_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_filter( 'parse_query', array( $this, 'apply_filters' ) );
		add_filter( 'bulk_actions-edit-berp_attendance', array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-berp_attendance', array( $this, 'handle_bulk_actions' ), 10, 3 );
	}

	/**
	 * Add custom columns.
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		unset( $columns['date'] );

		$new                   = array();
		$new['cb']             = $columns['cb'];
		$new['title']          = __( 'Title', 'BuildERP' );
		$new['berp_employee']  = __( 'Employee', 'BuildERP' );
		$new['berp_date']      = __( 'Date', 'BuildERP' );
		$new['berp_site']      = __( 'Site/Project', 'BuildERP' );
		$new['berp_overtime']  = __( 'Overtime (hrs)', 'BuildERP' );
		$new['berp_logged_by'] = __( 'Logged By', 'BuildERP' );
		$new['date']           = __( 'Created', 'BuildERP' );

		return $new;
	}

	/**
	 * Render column content.
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'berp_employee':
				$employee_id = (int) get_post_meta( $post_id, $this->meta_keys['employee'], true );
				$name        = $employee_id ? berp_get_employee_name( $employee_id ) : '';
				echo esc_html( $name ? $name : __( 'N/A', 'BuildERP' ) );
				break;
			case 'berp_date':
				$date = get_post_meta( $post_id, $this->meta_keys['date'], true );
				echo esc_html( $date ? $date : __( 'N/A', 'BuildERP' ) );
				break;
			case 'berp_site':
				$site_id = (int) get_post_meta( $post_id, $this->meta_keys['site'], true );
				$name    = $site_id ? berp_get_site_name( $site_id ) : '';
				echo esc_html( $name ? $name : __( 'N/A', 'BuildERP' ) );
				break;
			case 'berp_overtime':
				$overtime = get_post_meta( $post_id, $this->meta_keys['overtime'], true );
				$overtime = '' === $overtime ? 0 : floatval( $overtime );
				echo esc_html( number_format( $overtime, 2 ) );
				break;
			case 'berp_logged_by':
				$user_id = (int) get_post_meta( $post_id, $this->meta_keys['logged_by'], true );
				$user    = $user_id ? get_user_by( 'id', $user_id ) : null;
				echo esc_html( $user ? $user->display_name : __( 'System', 'BuildERP' ) );
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @since 1.0.0
	 * @param array $columns Columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['berp_date']     = 'berp_date';
		$columns['berp_employee'] = 'berp_employee';
		return $columns;
	}

	/**
	 * Handle sortable queries.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query Query.
	 */
	public function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_attendance' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'berp_date' === $orderby ) {
			$query->set( 'meta_key', $this->meta_keys['date'] );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'berp_employee' === $orderby ) {
			$query->set( 'meta_key', $this->meta_keys['employee'] );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Render filters above list table.
	 *
	 * @since 1.0.0
	 */
	public function add_filters() {
		global $typenow;

		if ( 'berp_attendance' !== $typenow ) {
			return;
		}

		$selected_site     = isset( $_GET['berp_site'] ) ? absint( wp_unslash( $_GET['berp_site'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$selected_employee = isset( $_GET['berp_employee'] ) ? absint( wp_unslash( $_GET['berp_employee'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$date_from         = isset( $_GET['berp_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$date_to           = isset( $_GET['berp_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		$sites     = function_exists( 'berp_get_sites' ) ? berp_get_sites() : array();
		$employees = function_exists( 'berp_get_employees' ) ? berp_get_employees() : array();

		?>
		<label class="screen-reader-text" for="berp_site"><?php esc_html_e( 'Filter by site', 'BuildERP' ); ?></label>
		<select name="berp_site" id="berp_site">
			<option value="0"><?php esc_html_e( 'All Sites', 'BuildERP' ); ?></option>
			<?php foreach ( $sites as $site ) : ?>
				<option value="<?php echo esc_attr( $site->ID ); ?>" <?php selected( $selected_site, $site->ID ); ?>>
					<?php echo esc_html( $site->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label class="screen-reader-text" for="berp_employee"><?php esc_html_e( 'Filter by employee', 'BuildERP' ); ?></label>
		<select name="berp_employee" id="berp_employee">
			<option value="0"><?php esc_html_e( 'All Employees', 'BuildERP' ); ?></option>
			<?php foreach ( $employees as $employee ) : ?>
				<option value="<?php echo esc_attr( $employee->ID ); ?>" <?php selected( $selected_employee, $employee->ID ); ?>>
					<?php echo esc_html( $employee->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input type="date" name="berp_date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'From date', 'BuildERP' ); ?>" />
		<input type="date" name="berp_date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php esc_attr_e( 'To date', 'BuildERP' ); ?>" />
		<?php
	}

	/**
	 * Apply filters to query.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query Query.
	 */
	public function apply_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_attendance' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = array();

		if ( isset( $_GET['berp_site'] ) && absint( $_GET['berp_site'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
			$meta_query[] = array(
				'key'   => $this->meta_keys['site'],
				'value' => absint( $_GET['berp_site'] ), // phpcs:ignore WordPress.Security.NonceVerification
			);
		}

		if ( isset( $_GET['berp_employee'] ) && absint( $_GET['berp_employee'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
			$meta_query[] = array(
				'key'   => $this->meta_keys['employee'],
				'value' => absint( $_GET['berp_employee'] ), // phpcs:ignore WordPress.Security.NonceVerification
			);
		}

		$date_from = isset( $_GET['berp_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$date_to   = isset( $_GET['berp_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $date_from || $date_to ) {
			$date_filter  = array(
				'key'     => $this->meta_keys['date'],
				'compare' => 'BETWEEN',
				'value'   => array(
					$date_from ? $date_from : '0000-00-00',
					$date_to ? $date_to : '9999-12-31',
				),
				'type'    => 'CHAR',
			);
			$meta_query[] = $date_filter;
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Register custom bulk actions.
	 *
	 * @since 1.0.0
	 * @param array $actions Actions.
	 * @return array
	 */
	public function register_bulk_actions( $actions ) {
		$actions['berp_export_csv'] = __( 'Export CSV', 'BuildERP' );
		return $actions;
	}

	/**
	 * Handle custom bulk actions.
	 *
	 * @since 1.0.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action name.
	 * @param array  $post_ids    Selected post IDs.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( 'berp_export_csv' !== $action ) {
			return $redirect_to;
		}

		if ( ! current_user_can( 'berp_view_attendance' ) ) {
			return add_query_arg( 'berp_export', 'no_cap', $redirect_to );
		}

		if ( empty( $post_ids ) ) {
			return add_query_arg( 'berp_export', 'empty', $redirect_to );
		}

		$this->export_csv( $post_ids );
		exit;
	}

	/**
	 * Export selected attendance to CSV.
	 *
	 * @since 1.0.0
	 * @param array $post_ids Post IDs.
	 */
	protected function export_csv( $post_ids ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=attendance-export.csv' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv(
			$output,
			array(
				__( 'Employee', 'BuildERP' ),
				__( 'Date', 'BuildERP' ),
				__( 'Site', 'BuildERP' ),
				__( 'Overtime (hrs)', 'BuildERP' ),
				__( 'Logged By', 'BuildERP' ),
			)
		);

		foreach ( $post_ids as $post_id ) {
			$employee_id = (int) get_post_meta( $post_id, $this->meta_keys['employee'], true );
			$date        = get_post_meta( $post_id, $this->meta_keys['date'], true );
			$site_id     = (int) get_post_meta( $post_id, $this->meta_keys['site'], true );
			$overtime    = get_post_meta( $post_id, $this->meta_keys['overtime'], true );
			$user_id     = (int) get_post_meta( $post_id, $this->meta_keys['logged_by'], true );

			$employee_name = $employee_id ? berp_get_employee_name( $employee_id ) : '';
			$site_name     = $site_id ? berp_get_site_name( $site_id ) : '';
			$user          = $user_id ? get_user_by( 'id', $user_id ) : null;

			fputcsv(
				$output,
				array(
					$employee_name,
					$date,
					$site_name,
					number_format( ( '' === $overtime ? 0 : floatval( $overtime ) ), 2 ),
					$user ? $user->display_name : '',
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
}


