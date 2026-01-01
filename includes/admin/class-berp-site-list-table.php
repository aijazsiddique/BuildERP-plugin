<?php
/**
 * Site List Table Customizations
 *
 * Customizes the Site CPT list table with custom columns and filters.
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
 * BERP_Site_List_Table Class
 *
 * @since 1.0.0
 */
class BERP_Site_List_Table {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_berp_site_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_site_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_site_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_by_meta' ) );
		add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'display_error_notices' ) );
		add_filter( 'post_updated_messages', array( $this, 'custom_messages' ) );
	}

	/**
	 * Add custom columns to list table
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		// Remove default columns
		unset( $columns['date'] );

		// Add custom columns
		$custom_columns = array(
			'cb'               => $columns['cb'],
			'title'            => $columns['title'],
			'berp_client'      => __( 'Client', 'aic_builderp' ),
			'berp_status'      => __( 'Status', 'aic_builderp' ),
			'berp_city'        => __( 'Location', 'aic_builderp' ),
			'berp_budget'      => __( 'Budget', 'aic_builderp' ),
			'berp_budget_used' => __( 'Budget Used', 'aic_builderp' ),
			'berp_dates'       => __( 'Timeline', 'aic_builderp' ),
			'date'             => __( 'Created', 'aic_builderp' ),
		);

		return $custom_columns;
	}

	/**
	 * Render custom column content
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		// Get currency symbol
		$currency_symbol = berp_get_currency_symbol();

		switch ( $column ) {
			case 'berp_client':
				$client_id = get_post_meta( $post_id, '_berp_client_id', true );
				if ( $client_id ) {
					$client = get_post( $client_id );
					if ( $client ) {
						$edit_url = admin_url( 'post.php?post=' . $client_id . '&action=edit' );
						echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $client->post_title ) . '</a>';
					} else {
						echo '<span style="color: #999;">' . esc_html__( '(Client not found)', 'aic_builderp' ) . '</span>';
					}
				} else {
					echo '—';
				}
				break;

			case 'berp_status':
				$status = get_post_meta( $post_id, '_berp_site_status', true );
				if ( empty( $status ) ) {
					$status = 'planning';
				}

				$status_labels = array(
					'planning'    => __( 'Planning', 'aic_builderp' ),
					'in_progress' => __( 'In Progress', 'aic_builderp' ),
					'on_hold'     => __( 'On Hold', 'aic_builderp' ),
					'completed'   => __( 'Completed', 'aic_builderp' ),
				);

				$status_colors = array(
					'planning'    => '#72aee6',
					'in_progress' => '#00a32a',
					'on_hold'     => '#f0b849',
					'completed'   => '#8c8f94',
				);

				$label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );
				$color = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : '#999';

				echo '<span class="berp-status-badge" style="background-color: ' . esc_attr( $color ) . '; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; display: inline-block;">' . esc_html( $label ) . '</span>';
				break;

			case 'berp_city':
				$city  = get_post_meta( $post_id, '_berp_site_city', true );
				$state = get_post_meta( $post_id, '_berp_site_state', true );

				if ( $city && $state ) {
					echo esc_html( $city . ', ' . $state );
				} elseif ( $city ) {
					echo esc_html( $city );
				} elseif ( $state ) {
					echo esc_html( $state );
				} else {
					echo '—';
				}
				break;

			case 'berp_budget':
				$budget = floatval( get_post_meta( $post_id, '_berp_budget', true ) );
				if ( $budget > 0 ) {
					echo esc_html( $currency_symbol . number_format( $budget, 2 ) );
				} else {
					echo '—';
				}
				break;

			case 'berp_budget_used':
				$budget       = floatval( get_post_meta( $post_id, '_berp_budget', true ) );
				$budget_spent = floatval( get_post_meta( $post_id, '_berp_budget_spent', true ) );

				if ( $budget > 0 ) {
					$percentage = ( $budget_spent / $budget ) * 100;

					// Determine color based on percentage
					$color = '#00a32a'; // Green
					if ( $percentage >= 100 ) {
						$color = '#d63638'; // Red
					} elseif ( $percentage >= 80 ) {
						$color = '#f0b849'; // Orange
					}

					echo '<div class="berp-budget-progress">';
					echo '<div style="font-weight: bold; color: ' . esc_attr( $color ) . ';">' . esc_html( number_format( $percentage, 1 ) ) . '%</div>';
					echo '<div style="background: #e0e0e0; height: 6px; border-radius: 3px; margin-top: 4px; overflow: hidden;">';
					echo '<div style="background: ' . esc_attr( $color ) . '; height: 100%; width: ' . esc_attr( min( $percentage, 100 ) ) . '%;"></div>';
					echo '</div>';
					echo '<small style="color: #666;">' . esc_html( $currency_symbol . number_format( $budget_spent, 2 ) . ' / ' . $currency_symbol . number_format( $budget, 2 ) ) . '</small>';
					echo '</div>';
				} else {
					echo '—';
				}
				break;

			case 'berp_dates':
				$start_date = get_post_meta( $post_id, '_berp_start_date', true );
				$end_date   = get_post_meta( $post_id, '_berp_end_date', true );

				if ( $start_date && $end_date ) {
					echo '<strong>' . esc_html__( 'Start:', 'aic_builderp' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) ) . '<br>';
					echo '<strong>' . esc_html__( 'End:', 'aic_builderp' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $end_date ) ) );
				} elseif ( $start_date ) {
					echo '<strong>' . esc_html__( 'Start:', 'aic_builderp' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) );
				} elseif ( $end_date ) {
					echo '<strong>' . esc_html__( 'End:', 'aic_builderp' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $end_date ) ) );
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['berp_status']      = 'berp_status';
		$columns['berp_budget']      = 'berp_budget';
		$columns['berp_budget_used'] = 'berp_budget_used';

		return $columns;
	}

	/**
	 * Handle column sorting
	 *
	 * @since 1.0.0
	 * @param WP_Query $query WordPress query object.
	 */
	public function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'berp_site' ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'berp_status':
				$query->set( 'meta_key', '_berp_site_status' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'berp_budget':
				$query->set( 'meta_key', '_berp_budget' );
				$query->set( 'orderby', 'meta_value_num' );
				break;

			case 'berp_budget_used':
				$query->set( 'meta_key', '_berp_budget_spent' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
		}
	}

	/**
	 * Add filter dropdowns to list table
	 *
	 * @since 1.0.0
	 * @param string $post_type Current post type.
	 */
	public function add_filters( $post_type ) {
		if ( $post_type !== 'berp_site' ) {
			return;
		}

		// Filter by client
		$clients = get_posts(
			array(
				'post_type'      => 'berp_client',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( ! empty( $clients ) ) {
			$selected = isset( $_GET['berp_client'] ) ? absint( $_GET['berp_client'] ) : 0;

			echo '<select name="berp_client" id="berp_client_filter">';
			echo '<option value="">' . esc_html__( 'All Clients', 'aic_builderp' ) . '</option>';

			foreach ( $clients as $client ) {
				printf(
					'<option value="%d"%s>%s</option>',
					$client->ID,
					selected( $selected, $client->ID, false ),
					esc_html( $client->post_title )
				);
			}

			echo '</select>';
		}

		// Filter by status
		$selected_status = isset( $_GET['berp_status'] ) ? sanitize_text_field( $_GET['berp_status'] ) : '';

		$statuses = array(
			'planning'    => __( 'Planning', 'aic_builderp' ),
			'in_progress' => __( 'In Progress', 'aic_builderp' ),
			'on_hold'     => __( 'On Hold', 'aic_builderp' ),
			'completed'   => __( 'Completed', 'aic_builderp' ),
		);

		echo '<select name="berp_status" id="berp_status_filter">';
		echo '<option value="">' . esc_html__( 'All Statuses', 'aic_builderp' ) . '</option>';

		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $selected_status, $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';

		// Filter by budget alert
		$selected_alert = isset( $_GET['berp_budget_alert'] ) ? sanitize_text_field( $_GET['berp_budget_alert'] ) : '';

		echo '<select name="berp_budget_alert" id="berp_budget_alert_filter">';
		echo '<option value="">' . esc_html__( 'All Budgets', 'aic_builderp' ) . '</option>';
		echo '<option value="exceeded"' . selected( $selected_alert, 'exceeded', false ) . '>' . esc_html__( 'Budget Exceeded', 'aic_builderp' ) . '</option>';
		echo '<option value="warning"' . selected( $selected_alert, 'warning', false ) . '>' . esc_html__( 'Budget Warning', 'aic_builderp' ) . '</option>';
		echo '<option value="ok"' . selected( $selected_alert, 'ok', false ) . '>' . esc_html__( 'Budget OK', 'aic_builderp' ) . '</option>';
		echo '</select>';
	}

	/**
	 * Filter posts by meta values
	 *
	 * @since 1.0.0
	 * @param WP_Query $query WordPress query object.
	 */
	public function filter_by_meta( $query ) {
		global $pagenow;

		if ( ! is_admin() || $pagenow !== 'edit.php' ) {
			return;
		}

		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '';

		$meta_query = array();

		/**
		 * Filter expenses/attendance screens when coming from site row actions.
		 */
		if ( in_array( $post_type, array( 'berp_expense', 'berp_attendance' ), true ) && isset( $_GET['berp_site'] ) && ! empty( $_GET['berp_site'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => absint( $_GET['berp_site'] ),
				'compare' => '=',
			);
		}

		if ( $post_type !== 'berp_site' ) {
			if ( ! empty( $meta_query ) ) {
				$existing_meta_query = $query->get( 'meta_query' );

				if ( is_array( $existing_meta_query ) ) {
					$meta_query = array_merge( $existing_meta_query, $meta_query );
				}

				$query->set( 'meta_query', $meta_query );
			}
			return;
		}

		// Filter by client
		if ( isset( $_GET['berp_client'] ) && ! empty( $_GET['berp_client'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_client_id',
				'value'   => absint( $_GET['berp_client'] ),
				'compare' => '=',
			);
		}

		// Filter by status
		if ( isset( $_GET['berp_status'] ) && ! empty( $_GET['berp_status'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_site_status',
				'value'   => sanitize_text_field( $_GET['berp_status'] ),
				'compare' => '=',
			);
		}

		// Filter by budget alert
		if ( isset( $_GET['berp_budget_alert'] ) && ! empty( $_GET['berp_budget_alert'] ) ) {
			global $wpdb;

			// Get sites matching budget criteria
			$alert_type = sanitize_text_field( $_GET['berp_budget_alert'] );
			$site_ids   = array();

			$sites = $wpdb->get_results(
				"SELECT p.ID,
                    CAST(pm_budget.meta_value AS DECIMAL(10,2)) as budget,
                    CAST(pm_spent.meta_value AS DECIMAL(10,2)) as spent
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm_budget ON p.ID = pm_budget.post_id AND pm_budget.meta_key = '_berp_budget'
                LEFT JOIN {$wpdb->postmeta} pm_spent ON p.ID = pm_spent.post_id AND pm_spent.meta_key = '_berp_budget_spent'
                WHERE p.post_type = 'berp_site'
                AND p.post_status = 'publish'
                AND CAST(pm_budget.meta_value AS DECIMAL(10,2)) > 0"
			);

			foreach ( $sites as $site ) {
				// Ensure float types for calculation
				$budget     = floatval( $site->budget );
				$spent      = floatval( $site->spent );
				$percentage = $budget > 0 ? ( $spent / $budget ) * 100 : 0;

				if ( $alert_type === 'exceeded' && $percentage >= 100 ) {
					$site_ids[] = $site->ID;
				} elseif ( $alert_type === 'warning' && $percentage >= 80 && $percentage < 100 ) {
					$site_ids[] = $site->ID;
				} elseif ( $alert_type === 'ok' && $percentage < 80 ) {
					$site_ids[] = $site->ID;
				}
			}

			if ( ! empty( $site_ids ) ) {
				$query->set( 'post__in', $site_ids );
			} else {
				// No sites match, show empty result
				$query->set( 'post__in', array( 0 ) );
			}
		}

		if ( ! empty( $meta_query ) ) {
			$existing_meta_query = $query->get( 'meta_query' );

			if ( is_array( $existing_meta_query ) ) {
				$meta_query = array_merge( $existing_meta_query, $meta_query );
			}

			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Modify row actions
	 *
	 * @since 1.0.0
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified row actions.
	 */
	public function modify_row_actions( $actions, $post ) {
		if ( $post->post_type !== 'berp_site' ) {
			return $actions;
		}

		// Add "View Expenses" action
		$expenses_url             = admin_url( 'edit.php?post_type=berp_expense&berp_site=' . $post->ID );
		$actions['view_expenses'] = '<a href="' . esc_url( $expenses_url ) . '">' . __( 'View Expenses', 'aic_builderp' ) . '</a>';

		// Add "View Attendance" action
		$attendance_url             = admin_url( 'edit.php?post_type=berp_attendance&berp_site=' . $post->ID );
		$actions['view_attendance'] = '<a href="' . esc_url( $attendance_url ) . '">' . __( 'View Attendance', 'aic_builderp' ) . '</a>';

		return $actions;
	}

	/**
	 * Display error notices from save validation
	 *
	 * @since 1.0.0
	 */
	public function display_error_notices() {
		if ( ! isset( $_GET['berp_error'] ) ) {
			return;
		}

		$error   = sanitize_text_field( $_GET['berp_error'] );
		$message = '';

		switch ( $error ) {
			case 'client_required':
				$message = __( 'Error: Client is required.', 'aic_builderp' );
				break;
		}

		if ( $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Custom post updated messages
	 *
	 * @since 1.0.0
	 * @param array $messages Existing messages.
	 * @return array Modified messages.
	 */
	public function custom_messages( $messages ) {
		$post = get_post();

		$messages['berp_site'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Site updated.', 'aic_builderp' ),
			2  => __( 'Custom field updated.', 'aic_builderp' ),
			3  => __( 'Custom field deleted.', 'aic_builderp' ),
			4  => __( 'Site updated.', 'aic_builderp' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Site restored to revision from %s', 'aic_builderp' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Site created.', 'aic_builderp' ),
			7  => __( 'Site saved.', 'aic_builderp' ),
			8  => __( 'Site submitted.', 'aic_builderp' ),
			9  => sprintf(
				__( 'Site scheduled for: <strong>%1$s</strong>.', 'aic_builderp' ),
				date_i18n( __( 'M j, Y @ G:i', 'aic_builderp' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Site draft updated.', 'aic_builderp' ),
		);

		return $messages;
	}
}

// Initialize
new BERP_Site_List_Table();
