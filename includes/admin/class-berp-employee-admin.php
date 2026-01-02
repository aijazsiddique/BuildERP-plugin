<?php
/**
 * Employee list admin enhancements (filters, bulk actions, export).
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Employee_Admin Class
 */
class BERP_Employee_Admin {


	/**
	 * Boot hooks.
	 */
	public function hooks() {
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filters' ), 9 );
		add_filter( 'bulk_actions-edit-berp_employee', array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-berp_employee', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'render_export_button' ), 20, 1 );
		add_action( 'admin_post_berp_export_employees', array( $this, 'handle_export' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'render_import_form' ), 25, 1 );
		add_action( 'admin_footer-edit.php', array( $this, 'render_import_form_footer' ) );
		add_action( 'admin_post_berp_import_employees', array( $this, 'handle_import' ) );
		add_action( 'admin_post_berp_export_employees_pdf', array( $this, 'handle_export_pdf' ) );
	}

	/**
	 * Render dropdown filters on employee list.
	 *
	 * @param string $post_type Current post type.
	 */
	public function render_filters( $post_type ) {
		if ( 'berp_employee' !== $post_type ) {
			return;
		}

		$selected_status = isset( $_GET['berp_status'] ) ? sanitize_key( wp_unslash( $_GET['berp_status'] ) ) : '';
		$statuses        = array(
			''         => __( 'All statuses', 'builderp' ),
			'active'   => __( 'Active', 'builderp' ),
			'inactive' => __( 'Inactive', 'builderp' ),
			'on_leave' => __( 'On Leave', 'builderp' ),
		);
		?>
		<select name="berp_status">
		<?php foreach ( $statuses as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_status, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
		$soft = isset( $_GET['berp_soft_deleted'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_soft_deleted'] ) ) : '';
		?>
		<select name="berp_soft_deleted">
			<option value=""><?php esc_html_e( 'Active records', 'builderp' ); ?></option>
			<option value="with_deleted" <?php selected( $soft, 'with_deleted' ); ?>><?php esc_html_e( 'Include soft deleted', 'builderp' ); ?></option>
			<option value="only_deleted" <?php selected( $soft, 'only_deleted' ); ?>><?php esc_html_e( 'Only soft deleted', 'builderp' ); ?></option>
		</select>
		<?php
		if ( taxonomy_exists( 'berp_department' ) ) {
			$dept_selected = isset( $_GET['berp_department'] ) ? sanitize_title( wp_unslash( $_GET['berp_department'] ) ) : '';
			wp_dropdown_categories(
				array(
					'show_option_all' => __( 'All departments', 'builderp' ),
					'taxonomy'        => 'berp_department',
					'name'            => 'berp_department',
					'orderby'         => 'name',
					'selected'        => $dept_selected,
					'hide_empty'      => false,
					'hierarchical'    => true,
					'value_field'     => 'slug',
				)
			);
		}
	}

	/**
	 * Apply filters to query.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function apply_filters( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_employee' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Start with any existing meta queries so we don't clobber defaults.
		$meta_query = (array) $query->get( 'meta_query' );
		if ( empty( $meta_query ) ) {
			$meta_query = array();
		}

		$status = isset( $_GET['berp_status'] ) ? sanitize_key( wp_unslash( $_GET['berp_status'] ) ) : '';
		if ( $status ) {
			$meta_query[] = array(
				'key'   => '_berp_employee_status',
				'value' => $status,
			);
		}

		$soft = isset( $_GET['berp_soft_deleted'] ) ? sanitize_text_field( wp_unslash( $_GET['berp_soft_deleted'] ) ) : '';
		if ( 'only_deleted' === $soft ) {
			$meta_query[] = array(
				'key'   => '_berp_soft_deleted',
				'value' => '1',
			);
		} elseif ( 'with_deleted' !== $soft ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_berp_soft_deleted',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_berp_soft_deleted',
					'value' => '0',
				),
			);
		}

		if ( ! empty( $meta_query ) ) {
			if ( ! isset( $meta_query['relation'] ) ) {
				$meta_query['relation'] = 'AND';
			}
			$query->set( 'meta_query', $meta_query );
		}

		$dept = isset( $_GET['berp_department'] ) ? sanitize_title( wp_unslash( $_GET['berp_department'] ) ) : '';
		if ( $dept ) {
			$tax_query = (array) $query->get( 'tax_query' );
			if ( empty( $tax_query ) ) {
				$tax_query = array();
			}
			$tax_query[] = array(
				'taxonomy' => 'berp_department',
				'field'    => 'slug',
				'terms'    => $dept,
			);
			if ( ! isset( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			$query->set( 'tax_query', $tax_query );
		}
	}

	/**
	 * Register bulk actions.
	 *
	 * @param  array $actions Actions.
	 * @return array
	 */
	public function register_bulk_actions( $actions ) {
		$actions['berp_soft_delete'] = __( 'Soft delete', 'builderp' );
		$actions['berp_restore']     = __( 'Restore (soft delete)', 'builderp' );
		return $actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect Redirect.
	 * @param  string $action   Action name.
	 * @param  array  $post_ids Post IDs.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect, $action, $post_ids ) {
		if ( 'berp_soft_delete' !== $action ) {
			if ( 'berp_restore' === $action ) {
				foreach ( $post_ids as $post_id ) {
					delete_post_meta( $post_id, '_berp_soft_deleted' );
				}
				$redirect = add_query_arg( 'berp_soft_restored', count( $post_ids ), $redirect );
			}
			return $redirect;
		}

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_berp_soft_deleted', '1' );
		}

		$redirect = add_query_arg( 'berp_soft_deleted_count', count( $post_ids ), $redirect );
		return $redirect;
	}

	/**
	 * Render export button on list table.
	 *
	 * @param string $which Position.
	 */
	public function render_export_button( $which ) {
		global $typenow;
		if ( 'top' !== $which || 'berp_employee' !== $typenow ) {
			return;
		}
		$csv_url = wp_nonce_url( admin_url( 'admin-post.php?action=berp_export_employees' ), 'berp_export_employees', 'berp_export_nonce' );
		$pdf_url = wp_nonce_url( admin_url( 'admin-post.php?action=berp_export_employees_pdf' ), 'berp_export_employees_pdf', 'berp_export_pdf_nonce' );
		echo '<div class="alignleft actions">';
		echo '<a class="button" href="' . esc_url( $csv_url ) . '">' . esc_html__( 'Export CSV', 'builderp' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $pdf_url ) . '">' . esc_html__( 'Export PDF', 'builderp' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Render import form above list table.
	 *
	 * @param string $which Position.
	 */
	public function render_import_form( $which ) {
		global $typenow;
		if ( 'top' !== $which || 'berp_employee' !== $typenow ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<button type="button" class="button" id="berp-employee-import-trigger"><?php esc_html_e( 'Import CSV', 'builderp' ); ?></button>
			<span id="berp-employee-import-filename" style="margin-left:6px;"></span>
		</div>
		<?php
	}

	/**
	 * Render hidden import form outside the list table form to avoid nested forms.
	 */
	public function render_import_form_footer() {
		global $typenow;
		if ( 'berp_employee' !== $typenow ) {
			return;
		}
		?>
		<form id="berp-employee-import-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="position:absolute;left:-9999px;top:auto;">
		<?php wp_nonce_field( 'berp_import_employees', 'berp_import_nonce' ); ?>
			<input type="hidden" name="action" value="berp_import_employees" />
			<input type="file" id="berp_employee_csv_hidden" name="berp_employee_csv" accept=".csv,text/csv" />
		</form>
		<script>
		(function() {
			const trigger = document.getElementById('berp-employee-import-trigger');
			const fileInput = document.getElementById('berp_employee_csv_hidden');
			const fileNameEl = document.getElementById('berp-employee-import-filename');
			if (!trigger || !fileInput) {
				return;
			}
			trigger.addEventListener('click', function(e) {
				e.preventDefault();
				fileInput.click();
			});
			fileInput.addEventListener('change', function() {
				if (!fileInput.files.length) {
					return;
				}
				if (fileNameEl) {
					fileNameEl.textContent = fileInput.files[0].name;
				}
				fileInput.form.submit();
			});
		})();
		</script>
		<?php
	}

	/**
	 * Export employees to CSV.
	 */
	public function handle_export() {
		if ( ! current_user_can( 'berp_manage_employees' ) ) {
			wp_die( esc_html__( 'You do not have permission to export employees.', 'builderp' ) );
		}
		if ( empty( $_GET['berp_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['berp_export_nonce'] ) ), 'berp_export_employees' ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'builderp' ) );
		}

		$args      = array(
			'post_type'      => 'berp_employee',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'     => '_berp_soft_deleted',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => '_berp_soft_deleted',
						'value' => '0',
					),
				),
			),
		);
		$employees = get_posts( $args );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="employees.csv"' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, array( 'Name', 'Employee ID', 'Email', 'Phone', 'Status', 'Basic Salary' ) );

		foreach ( $employees as $employee ) {
			fputcsv(
				$output,
				array(
					$employee->post_title,
					get_post_meta( $employee->ID, '_berp_employee_id', true ),
					get_post_meta( $employee->ID, '_berp_employee_email', true ),
					get_post_meta( $employee->ID, '_berp_employee_phone', true ),
					get_post_meta( $employee->ID, '_berp_employee_status', true ),
					get_post_meta( $employee->ID, '_berp_basic_salary', true ),
				)
			);
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Export employees to PDF (simple HTML -> PDF via DOMPDF if available).
	 */
	public function handle_export_pdf() {
		if ( ! current_user_can( 'berp_manage_employees' ) ) {
			wp_die( esc_html__( 'You do not have permission to export employees.', 'builderp' ) );
		}
		if ( empty( $_GET['berp_export_pdf_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['berp_export_pdf_nonce'] ) ), 'berp_export_employees_pdf' ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'builderp' ) );
		}

		$args      = array(
			'post_type'      => 'berp_employee',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'     => '_berp_soft_deleted',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => '_berp_soft_deleted',
						'value' => '0',
					),
				),
			),
		);
		$employees = get_posts( $args );

		$rows = '';
		foreach ( $employees as $employee ) {
			$rows .= '<tr>';
			$rows .= '<td>' . esc_html( $employee->post_title ) . '</td>';
			$rows .= '<td>' . esc_html( get_post_meta( $employee->ID, '_berp_employee_id', true ) ) . '</td>';
			$rows .= '<td>' . esc_html( get_post_meta( $employee->ID, '_berp_employee_email', true ) ) . '</td>';
			$rows .= '<td>' . esc_html( get_post_meta( $employee->ID, '_berp_employee_phone', true ) ) . '</td>';
			$rows .= '<td>' . esc_html( get_post_meta( $employee->ID, '_berp_employee_status', true ) ) . '</td>';
			$rows .= '<td>' . esc_html( get_post_meta( $employee->ID, '_berp_basic_salary', true ) ) . '</td>';
			$rows .= '</tr>';
		}

		$html = '<html><head><style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            table { width:100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background: #f6f6f6; }
        </style></head><body>
        <h2>' . esc_html__( 'Employees', 'builderp' ) . '</h2>
        <table>
        <thead><tr><th>' . esc_html__( 'Name', 'builderp' ) . '</th><th>' . esc_html__( 'Employee ID', 'builderp' ) . '</th><th>' . esc_html__( 'Email', 'builderp' ) . '</th><th>' . esc_html__( 'Phone', 'builderp' ) . '</th><th>' . esc_html__( 'Status', 'builderp' ) . '</th><th>' . esc_html__( 'Basic Salary', 'builderp' ) . '</th></tr></thead>
        <tbody>' . $rows . '</tbody></table></body></html>';

		if ( ! class_exists( 'Dompdf\Dompdf' ) ) {
			// fallback: output HTML with hint.
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="employees.html"' );
			echo wp_kses_post( $html );
			exit;
		}

		$dompdf = new Dompdf\Dompdf();
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();

		$dompdf->stream( 'employees.pdf', array( 'Attachment' => true ) );
		exit;
	}

	/**
	 * Handle CSV import of employees.
	 */
	public function handle_import() {
		if ( ! current_user_can( 'berp_manage_employees' ) ) {
			wp_die( esc_html__( 'You do not have permission to import employees.', 'builderp' ) );
		}
		if ( empty( $_POST['berp_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_import_nonce'] ) ), 'berp_import_employees' ) ) {
			wp_die( esc_html__( 'Invalid import request.', 'builderp' ) );
		}
		if ( empty( $_FILES['berp_employee_csv']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Please upload a CSV file.', 'builderp' ) );
		}

		// Validate file type - only allow CSV.
		$file_ext = strtolower( pathinfo( $_FILES['berp_employee_csv']['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			wp_die( esc_html__( 'Invalid file type. Only CSV files are allowed.', 'builderp' ) );
		}

		// Validate MIME type.
		$finfo = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : null;
		if ( $finfo ) {
			$mime = finfo_file( $finfo, $_FILES['berp_employee_csv']['tmp_name'] );
			finfo_close( $finfo );
			$allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				wp_die( esc_html__( 'Invalid file type. Only CSV files are allowed.', 'builderp' ) );
			}
		}

		$tmp_name = sanitize_text_field( wp_unslash( $_FILES['berp_employee_csv']['tmp_name'] ) );
		$file     = fopen( $tmp_name, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $file ) {
			wp_die( esc_html__( 'Could not read CSV file.', 'builderp' ) );
		}

		$created = 0;
		$updated = 0;
		$row     = 0;
		while ( true ) {
			$data = fgetcsv( $file );
			if ( false === $data ) {
				break;
			}
			++$row;
			if ( 1 === $row ) {
				// header row skip.
				continue;
			}
			$name   = isset( $data[0] ) ? sanitize_text_field( $data[0] ) : '';
			$emp_id = isset( $data[1] ) ? sanitize_text_field( $data[1] ) : '';
			$email  = isset( $data[2] ) ? sanitize_email( $data[2] ) : '';
			$phone  = isset( $data[3] ) ? sanitize_text_field( $data[3] ) : '';
			$status = isset( $data[4] ) ? sanitize_key( $data[4] ) : 'active';
			$salary = isset( $data[5] ) ? floatval( $data[5] ) : '';

			if ( '' === $name ) {
				continue;
			}

			// try to find existing by employee id.
			$existing = $this->find_employee_by_meta( '_berp_employee_id', $emp_id );
			$postarr  = array(
				'post_title'  => $name,
				'post_type'   => 'berp_employee',
				'post_status' => 'publish',
			);

			if ( $existing ) {
				$postarr['ID'] = $existing;
				wp_update_post( $postarr );
				++$updated;
				$post_id = $existing;
			} else {
				$post_id = wp_insert_post( $postarr );
				if ( $post_id && ! is_wp_error( $post_id ) ) {
					++$created;
				}
			}

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				if ( $emp_id ) {
					update_post_meta( $post_id, '_berp_employee_id', $emp_id );
				}
				if ( $email ) {
					update_post_meta( $post_id, '_berp_employee_email', $email );
				}
				if ( $phone ) {
					update_post_meta( $post_id, '_berp_employee_phone', $phone );
				}
				if ( $status ) {
					update_post_meta( $post_id, '_berp_employee_status', $status );
				}
				if ( '' !== $salary ) {
					update_post_meta( $post_id, '_berp_basic_salary', number_format( (float) $salary, 2, '.', '' ) );
				}
			}
		}
		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$redirect = admin_url( 'edit.php?post_type=berp_employee' );
		$redirect = add_query_arg(
			array(
				'berp_imported' => $created,
				'berp_updated'  => $updated,
			),
			$redirect
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Find employee post by meta.
	 *
	 * @param  string $meta_key Meta key.
	 * @param  string $value    Value.
	 * @return int|false
	 */
	private function find_employee_by_meta( $meta_key, $value ) {
		if ( '' === $value ) {
			return false;
		}
		$query = new WP_Query(
			array(
				'post_type'      => 'berp_employee',
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
		if ( $query->have_posts() ) {
			return (int) $query->posts[0];
		}
		return false;
	}
}


