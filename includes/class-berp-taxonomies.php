<?php
/**
 * Custom Taxonomies
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Taxonomies Class
 *
 * Registers and manages all custom taxonomies
 */
class BERP_Taxonomies {


	/**
	 * Register all taxonomies
	 *
	 * @since 1.0.0
	 */
	public function register_all() {
		$this->register_expense_category();
		$this->register_department();
		$this->register_project_status();
		$this->maybe_seed_default_terms();
	}

	/**
	 * Register Expense Category taxonomy
	 *
	 * @since 1.0.0
	 */
	private function register_expense_category() {
		$labels = array(
			'name'              => _x( 'Expense Categories', 'taxonomy general name', 'aic_builderp' ),
			'singular_name'     => _x( 'Expense Category', 'taxonomy singular name', 'aic_builderp' ),
			'search_items'      => __( 'Search Categories', 'aic_builderp' ),
			'all_items'         => __( 'All Categories', 'aic_builderp' ),
			'parent_item'       => __( 'Parent Category', 'aic_builderp' ),
			'parent_item_colon' => __( 'Parent Category:', 'aic_builderp' ),
			'edit_item'         => __( 'Edit Category', 'aic_builderp' ),
			'update_item'       => __( 'Update Category', 'aic_builderp' ),
			'add_new_item'      => __( 'Add New Category', 'aic_builderp' ),
			'new_item_name'     => __( 'New Category Name', 'aic_builderp' ),
			'menu_name'         => __( 'Expense Categories', 'aic_builderp' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'expense-category' ),
			'show_in_rest'      => false,
			'capabilities'      => array(
				'manage_terms' => 'berp_manage_expenses',
				'edit_terms'   => 'berp_manage_expenses',
				'delete_terms' => 'berp_manage_expenses',
				'assign_terms' => 'berp_edit_expenses',
			),
		);

		register_taxonomy( 'berp_expense_category', array( 'berp_expense' ), $args );
	}

	/**
	 * Register Department taxonomy
	 *
	 * @since 1.0.0
	 */
	private function register_department() {
		$labels = array(
			'name'              => _x( 'Departments', 'taxonomy general name', 'aic_builderp' ),
			'singular_name'     => _x( 'Department', 'taxonomy singular name', 'aic_builderp' ),
			'search_items'      => __( 'Search Departments', 'aic_builderp' ),
			'all_items'         => __( 'All Departments', 'aic_builderp' ),
			'parent_item'       => __( 'Parent Department', 'aic_builderp' ),
			'parent_item_colon' => __( 'Parent Department:', 'aic_builderp' ),
			'edit_item'         => __( 'Edit Department', 'aic_builderp' ),
			'update_item'       => __( 'Update Department', 'aic_builderp' ),
			'add_new_item'      => __( 'Add New Department', 'aic_builderp' ),
			'new_item_name'     => __( 'New Department Name', 'aic_builderp' ),
			'menu_name'         => __( 'Departments', 'aic_builderp' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'department' ),
			'show_in_rest'      => false,
			'capabilities'      => array(
				'manage_terms' => 'berp_manage_employees',
				'edit_terms'   => 'berp_manage_employees',
				'delete_terms' => 'berp_manage_employees',
				'assign_terms' => 'berp_edit_employees',
			),
		);

		register_taxonomy( 'berp_department', array( 'berp_employee' ), $args );
	}

	/**
	 * Register Project Status taxonomy
	 *
	 * @since 1.0.0
	 */
	private function register_project_status() {
		$labels = array(
			'name'          => _x( 'Project Statuses', 'taxonomy general name', 'aic_builderp' ),
			'singular_name' => _x( 'Project Status', 'taxonomy singular name', 'aic_builderp' ),
			'search_items'  => __( 'Search Statuses', 'aic_builderp' ),
			'all_items'     => __( 'All Statuses', 'aic_builderp' ),
			'edit_item'     => __( 'Edit Status', 'aic_builderp' ),
			'update_item'   => __( 'Update Status', 'aic_builderp' ),
			'add_new_item'  => __( 'Add New Status', 'aic_builderp' ),
			'new_item_name' => __( 'New Status Name', 'aic_builderp' ),
			'menu_name'     => __( 'Project Status', 'aic_builderp' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'project-status' ),
			'show_in_rest'      => false,
			'capabilities'      => array(
				'manage_terms' => 'berp_manage_sites',
				'edit_terms'   => 'berp_manage_sites',
				'delete_terms' => 'berp_manage_sites',
				'assign_terms' => 'berp_edit_sites',
			),
		);

		register_taxonomy( 'berp_project_status', array( 'berp_site' ), $args );
	}

	/**
	 * Ensure default taxonomy terms exist (covers missed activations)
	 *
	 * @since 1.0.0
	 */
	private function maybe_seed_default_terms() {
		$project_statuses = array(
			'Planning',
			'In Progress',
			'On Hold',
			'Completed',
			'Cancelled',
		);

		$expense_categories = array(
			'Payroll',
			'Materials',
			'Equipment',
			'Subcontractors',
			'Transportation',
			'Utilities',
			'Office Supplies',
			'Marketing',
			'Legal & Professional',
			'Maintenance',
			'Other',
		);

		$departments = array(
			'Construction',
			'Project Management',
			'Administration',
			'Finance',
			'Human Resources',
			'Procurement',
			'Safety',
			'Quality Control',
		);

		$created_any = false;

		$project_status_created = $this->create_terms_if_missing( 'berp_project_status', $project_statuses );
		$expense_created        = $this->create_terms_if_missing( 'berp_expense_category', $expense_categories );
		$department_created     = $this->create_terms_if_missing( 'berp_department', $departments );

		$created_any = $project_status_created || $expense_created || $department_created || $created_any;

		if ( $project_status_created || $this->terms_exist_in_taxonomy( 'berp_project_status', $project_statuses ) ) {
			update_option( 'berp_project_statuses_created', 1 );
		}

		if ( $expense_created || $this->terms_exist_in_taxonomy( 'berp_expense_category', $expense_categories ) ) {
			update_option( 'berp_expense_categories_created', 1 );
		}

		if ( $department_created || $this->terms_exist_in_taxonomy( 'berp_department', $departments ) ) {
			update_option( 'berp_departments_created', 1 );
		}

		if ( $created_any && ! get_option( 'berp_default_terms_created' ) ) {
			update_option( 'berp_default_terms_created', true );
		}
	}

	/**
	 * Create taxonomy terms if they are missing
	 *
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  array  $terms    List of term names.
	 * @return bool True if any terms were created.
	 *
	 * @since 1.0.0
	 */
	private function create_terms_if_missing( $taxonomy, $terms ) {
		$created = false;

		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, $taxonomy ) ) {
				$result = wp_insert_term( $term, $taxonomy );

				if ( ! is_wp_error( $result ) ) {
					$created = true;
				}
			}
		}

		return $created;
	}

	/**
	 * Check if all provided terms exist in a taxonomy
	 *
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  array  $terms    List of term names.
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	private function terms_exist_in_taxonomy( $taxonomy, $terms ) {
		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, $taxonomy ) ) {
				return false;
			}
		}

		return true;
	}
}
