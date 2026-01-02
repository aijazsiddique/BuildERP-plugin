<?php
/**
 * Expense List Table Customizations
 *
 * Customizes the Expense CPT list table with custom columns and filters.
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
 * BERP_Expense_List_Table Class
 *
 * @since 1.0.0
 */
class BERP_Expense_List_Table {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_berp_expense_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_expense_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_expense_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_by_meta' ) );
		add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
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
			'cb'                  => $columns['cb'],
			'title'               => $columns['title'],
			'berp_date'           => __( 'Date', 'builderp' ),
			'berp_amount'         => __( 'Amount', 'builderp' ),
			'berp_category'       => __( 'Category', 'builderp' ),
			'berp_site'           => __( 'Site', 'builderp' ),
			'berp_payment_method' => __( 'Payment Method', 'builderp' ),
			'berp_recurring'      => __( 'Recurring', 'builderp' ),
			'date'                => __( 'Created', 'builderp' ),
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
			case 'berp_date':
				$date = get_post_meta( $post_id, '_berp_expense_date', true );
				if ( $date ) {
					echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
				} else {
					echo '—';
				}
				break;

			case 'berp_amount':
				$amount = floatval( get_post_meta( $post_id, '_berp_expense_amount', true ) );
				if ( $amount > 0 ) {
					echo '<strong>' . esc_html( $currency_symbol . number_format( $amount, 2 ) ) . '</strong>';
				} else {
					echo '—';
				}
				break;

			case 'berp_category':
				$category = get_post_meta( $post_id, '_berp_expense_category', true );
				if ( $category ) {
					$color = $this->get_category_color( $category );
					$label = ucfirst( str_replace( '_', ' ', $category ) );
					echo '<span class="berp-category-badge" style="background-color: ' . esc_attr( $color ) . '; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; display: inline-block;">' . esc_html( $label ) . '</span>';
				} else {
					echo '—';
				}
				break;

			case 'berp_site':
				$site_id = get_post_meta( $post_id, '_berp_site_id', true );
				if ( $site_id ) {
					$site = get_post( $site_id );
					if ( $site ) {
						$edit_url = admin_url( 'post.php?post=' . $site_id . '&action=edit' );
						echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $site->post_title ) . '</a>';
					} else {
						echo '<span style="color: #999;">' . esc_html__( '(Site not found)', 'builderp' ) . '</span>';
					}
				} else {
					echo '—';
				}
				break;

			case 'berp_payment_method':
				$method = get_post_meta( $post_id, '_berp_payment_method', true );
				if ( $method ) {
					$label = ucfirst( str_replace( '_', ' ', $method ) );
					echo esc_html( $label );
				} else {
					echo '—';
				}
				break;

			case 'berp_recurring':
				$is_recurring = get_post_meta( $post_id, '_berp_is_recurring', true );
				if ( $is_recurring == '1' ) {
					$interval  = get_post_meta( $post_id, '_berp_recurring_interval', true );
					$next_date = get_post_meta( $post_id, '_berp_recurring_next_date', true );
					$label     = ucfirst( $interval );

					/* translators: %s: interval label */
					echo '<span class="dashicons dashicons-update" style="color: #2271b1;" title="' . esc_attr( sprintf( __( 'Recurring: %s', 'builderp' ), $label ) ) . '"></span>';

					if ( $next_date ) {
						echo '<br><small style="color: #666;">' . esc_html__( 'Next:', 'builderp' ) . ' ' . esc_html( date_i18n( 'M j', strtotime( $next_date ) ) ) . '</small>';
					}
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Get category badge color
	 *
	 * @since 1.0.0
	 * @param string $category Category slug.
	 * @return string Color code.
	 */
	private function get_category_color( $category ) {
		$colors = array(
			'payroll'       => '#2271b1',
			'materials'     => '#00a32a',
			'equipment'     => '#f0b849',
			'subcontractor' => '#8c8f94',
			'other'         => '#d63638',
		);

		return isset( $colors[ strtolower( $category ) ] ) ? $colors[ strtolower( $category ) ] : '#72aee6';
	}

	/**
	 * Make columns sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['berp_date']     = 'berp_date';
		$columns['berp_amount']   = 'berp_amount';
		$columns['berp_category'] = 'berp_category';
		return $columns;
	}

	/**
	 * Handle column sorting
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query object.
	 */
	public function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'berp_expense' ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'berp_date':
				$query->set( 'meta_key', '_berp_expense_date' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'berp_amount':
				$query->set( 'meta_key', '_berp_expense_amount' );
				$query->set( 'orderby', 'meta_value_num' );
				break;

			case 'berp_category':
				$query->set( 'meta_key', '_berp_expense_category' );
				$query->set( 'orderby', 'meta_value' );
				break;
		}
	}

	/**
	 * Add filter dropdowns
	 *
	 * @since 1.0.0
	 * @param string $post_type Current post type.
	 */
	public function add_filters( $post_type ) {
		if ( $post_type !== 'berp_expense' ) {
			return;
		}

		// Category filter
		$this->render_category_filter();

		// Site filter
		$this->render_site_filter();

		// Date range filters
		$this->render_date_filters();

		// Payment method filter
		$this->render_payment_method_filter();

		// Export buttons
		$this->render_export_buttons();
	}

	/**
	 * Render category filter dropdown
	 *
	 * @since 1.0.0
	 */
	private function render_category_filter() {
		$settings        = get_option( 'berp_settings', array() );
		$categories_text = isset( $settings['expense']['default_categories'] ) ? $settings['expense']['default_categories'] : "Payroll\nMaterials\nEquipment\nSubcontractor\nOther";
		$categories      = array_filter( array_map( 'trim', explode( "\n", $categories_text ) ) );

		$selected = isset( $_GET['berp_category'] ) ? sanitize_text_field( $_GET['berp_category'] ) : '';

		echo '<select name="berp_category">';
		echo '<option value="">' . esc_html__( 'All Categories', 'builderp' ) . '</option>';
		foreach ( $categories as $category ) {
			$value = strtolower( $category );
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>' . esc_html( $category ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render site filter dropdown
	 *
	 * @since 1.0.0
	 */
	private function render_site_filter() {
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$selected = isset( $_GET['berp_site'] ) ? absint( $_GET['berp_site'] ) : 0;

		echo '<select name="berp_site">';
		echo '<option value="">' . esc_html__( 'All Sites', 'builderp' ) . '</option>';
		foreach ( $sites as $site ) {
			echo '<option value="' . esc_attr( $site->ID ) . '" ' . selected( $selected, $site->ID, false ) . '>' . esc_html( $site->post_title ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render date range filters
	 *
	 * @since 1.0.0
	 */
	private function render_date_filters() {
		$date_from = isset( $_GET['berp_date_from'] ) ? sanitize_text_field( $_GET['berp_date_from'] ) : '';
		$date_to   = isset( $_GET['berp_date_to'] ) ? sanitize_text_field( $_GET['berp_date_to'] ) : '';

		echo '<input type="date" name="berp_date_from" value="' . esc_attr( $date_from ) . '" placeholder="' . esc_attr__( 'From date', 'builderp' ) . '" style="line-height: 28px; height: auto; margin: 0;" />';
		echo '<input type="date" name="berp_date_to" value="' . esc_attr( $date_to ) . '" placeholder="' . esc_attr__( 'To date', 'builderp' ) . '" style="line-height: 28px; height: auto; margin: 0;" />';
	}

	/**
	 * Render payment method filter dropdown
	 *
	 * @since 1.0.0
	 */
	private function render_payment_method_filter() {
		$settings     = get_option( 'berp_settings', array() );
		$methods_text = isset( $settings['expense']['payment_methods'] ) ? $settings['expense']['payment_methods'] : "Cash\nBank Transfer\nCheque\nCredit Card";
		$methods      = array_filter( array_map( 'trim', explode( "\n", $methods_text ) ) );

		$selected = isset( $_GET['berp_payment_method'] ) ? sanitize_text_field( $_GET['berp_payment_method'] ) : '';

		echo '<select name="berp_payment_method">';
		echo '<option value="">' . esc_html__( 'All Payment Methods', 'builderp' ) . '</option>';
		foreach ( $methods as $method ) {
			$value = strtolower( str_replace( ' ', '_', $method ) );
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>' . esc_html( $method ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render export buttons
	 *
	 * @since 1.0.0
	 */
	private function render_export_buttons() {
		$nonce    = wp_create_nonce( 'berp_export_expenses' );
		$base_url = admin_url( 'edit.php?post_type=berp_expense' );

		// Build query string from current filters
		$query_args = array( 'berp_nonce' => $nonce );

		if ( isset( $_GET['berp_category'] ) && ! empty( $_GET['berp_category'] ) ) {
			$query_args['berp_category'] = sanitize_text_field( $_GET['berp_category'] );
		}
		if ( isset( $_GET['berp_site'] ) && ! empty( $_GET['berp_site'] ) ) {
			$query_args['berp_site'] = absint( $_GET['berp_site'] );
		}
		if ( isset( $_GET['berp_date_from'] ) && ! empty( $_GET['berp_date_from'] ) ) {
			$query_args['berp_date_from'] = sanitize_text_field( $_GET['berp_date_from'] );
		}
		if ( isset( $_GET['berp_date_to'] ) && ! empty( $_GET['berp_date_to'] ) ) {
			$query_args['berp_date_to'] = sanitize_text_field( $_GET['berp_date_to'] );
		}
		if ( isset( $_GET['berp_payment_method'] ) && ! empty( $_GET['berp_payment_method'] ) ) {
			$query_args['berp_payment_method'] = sanitize_text_field( $_GET['berp_payment_method'] );
		}

		$csv_url = add_query_arg( array_merge( $query_args, array( 'berp_action' => 'export_expenses_csv' ) ), $base_url );
		$pdf_url = add_query_arg( array_merge( $query_args, array( 'berp_action' => 'export_expenses_pdf' ) ), $base_url );

		echo ' <a href="' . esc_url( $csv_url ) . '" class="button">' . esc_html__( 'Export CSV', 'builderp' ) . '</a>';
		echo ' <a href="' . esc_url( $pdf_url ) . '" class="button">' . esc_html__( 'Export PDF', 'builderp' ) . '</a>';
	}

	/**
	 * Filter posts by meta values
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query object.
	 */
	public function filter_by_meta( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'berp_expense' ) {
			return;
		}

		$meta_query = array();

		// Category filter
		if ( isset( $_GET['berp_category'] ) && ! empty( $_GET['berp_category'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_category',
				'value'   => sanitize_text_field( $_GET['berp_category'] ),
				'compare' => '=',
			);
		}

		// Site filter
		if ( isset( $_GET['berp_site'] ) && ! empty( $_GET['berp_site'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => absint( $_GET['berp_site'] ),
				'compare' => '=',
			);
		}

		// Date range filter
		$date_from = isset( $_GET['berp_date_from'] ) ? sanitize_text_field( $_GET['berp_date_from'] ) : '';
		$date_to   = isset( $_GET['berp_date_to'] ) ? sanitize_text_field( $_GET['berp_date_to'] ) : '';

		if ( $date_from && $date_to ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => array( $date_from, $date_to ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
		} elseif ( $date_from ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		} elseif ( $date_to ) {
			$meta_query[] = array(
				'key'     => '_berp_expense_date',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		// Payment method filter
		if ( isset( $_GET['berp_payment_method'] ) && ! empty( $_GET['berp_payment_method'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_payment_method',
				'value'   => sanitize_text_field( $_GET['berp_payment_method'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
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
		if ( $post->post_type !== 'berp_expense' ) {
			return $actions;
		}

		// Add view receipts action if receipts exist
		$receipts = get_post_meta( $post->ID, '_berp_expense_receipts', true );
		if ( ! empty( $receipts ) && is_array( $receipts ) ) {
			$actions['view_receipts'] = sprintf(
				'<span class="dashicons dashicons-media-document" style="color: #2271b1;" title="%s"></span> %d %s',
				esc_attr__( 'Receipts attached', 'builderp' ),
				count( $receipts ),
				esc_html( _n( 'Receipt', 'Receipts', count( $receipts ), 'builderp' ) )
			);
		}

		// Add linked payroll action if exists
		$linked_payroll_id = get_post_meta( $post->ID, '_berp_linked_payroll_id', true );
		if ( $linked_payroll_id ) {
			$actions['view_payroll'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'post.php?post=' . $linked_payroll_id . '&action=edit' ) ),
				esc_html__( 'View Payroll', 'builderp' )
			);
		}

		return $actions;
	}
}

