<?php
/**
 * Invoice List Table Customization
 *
 * Handles custom columns, filters, and sorting for the Invoice CPT list table.
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
 * BERP_Invoice_List_Table Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_List_Table {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_berp_invoice_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_invoice_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_invoice_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_action( 'parse_query', array( $this, 'filter_by_meta' ) );
		add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
	}

	/**
	 * Add custom columns
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		$new_columns = array(
			'cb'           => $columns['cb'],
			'title'        => __( 'Invoice #', 'aic_builderp' ),
			'client'       => __( 'Client', 'aic_builderp' ),
			'site'         => __( 'Site', 'aic_builderp' ),
			'invoice_date' => __( 'Date', 'aic_builderp' ),
			'due_date'     => __( 'Due Date', 'aic_builderp' ),
			'status'       => __( 'Status', 'aic_builderp' ),
			'grand_total'  => __( 'Total', 'aic_builderp' ),
			'amount_paid'  => __( 'Paid', 'aic_builderp' ),
			'amount_due'   => __( 'Due', 'aic_builderp' ),
		);
		return $new_columns;
	}

	/**
	 * Render custom column content
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		$currency = berp_get_currency_symbol();

		switch ( $column ) {
			case 'client':
				$client_id = get_post_meta( $post_id, '_berp_client_id', true );
				if ( $client_id ) {
					$client = get_post( $client_id );
					echo esc_html( $client ? $client->post_title : '—' );
				} else {
					echo '—';
				}
				break;

			case 'site':
				$site_id = get_post_meta( $post_id, '_berp_site_id', true );
				if ( $site_id ) {
					$site = get_post( $site_id );
					echo esc_html( $site ? $site->post_title : '—' );
				} else {
					echo '—';
				}
				break;

			case 'invoice_date':
				$date = get_post_meta( $post_id, '_berp_invoice_date', true );
				echo $date ? esc_html( gmdate( 'M j, Y', strtotime( $date ) ) ) : '—';
				break;

			case 'due_date':
				$date = get_post_meta( $post_id, '_berp_due_date', true );
				if ( $date ) {
					$is_overdue = berp_is_invoice_overdue( $post_id );
					$status     = get_post_meta( $post_id, '_berp_status', true );
					$color      = ( $is_overdue && ! in_array( $status, array( 'paid', 'cancelled' ), true ) ) ? 'red' : 'inherit';
					echo '<span style="color: ' . esc_attr( $color ) . ';">';
					echo esc_html( gmdate( 'M j, Y', strtotime( $date ) ) );
					if ( $is_overdue && ! in_array( $status, array( 'paid', 'cancelled' ), true ) ) {
						$days_overdue = berp_get_invoice_days_overdue( $post_id );
						echo ' <strong>(' . esc_html( sprintf( __( '%d days overdue', 'aic_builderp' ), $days_overdue ) ) . ')</strong>';
					}
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'status':
				$status      = get_post_meta( $post_id, '_berp_status', true );
				$label       = berp_get_invoice_status_label( $status );
				$badge_class = 'berp-status-badge berp-status-' . esc_attr( $status );
				echo '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( $label ) . '</span>';
				break;

			case 'grand_total':
				$total = get_post_meta( $post_id, '_berp_grand_total', true );
				$total = ! empty( $total ) ? floatval( $total ) : 0.00;
				echo esc_html( $currency . number_format( $total, 2 ) );
				break;

			case 'amount_paid':
				$paid = get_post_meta( $post_id, '_berp_amount_paid', true );
				$paid = ! empty( $paid ) ? floatval( $paid ) : 0.00;
				echo '<span style="color: green;">' . esc_html( $currency . number_format( $paid, 2 ) ) . '</span>';
				break;

			case 'amount_due':
				$total = floatval( get_post_meta( $post_id, '_berp_grand_total', true ) );
				$paid  = floatval( get_post_meta( $post_id, '_berp_amount_paid', true ) );
				$due   = $total - $paid;
				$color = $due > 0 ? 'red' : 'green';
				echo '<span style="color: ' . esc_attr( $color ) . '; font-weight: bold;">';
				echo esc_html( $currency . number_format( max( 0, $due ), 2 ) );
				echo '</span>';
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 1.0.0
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['invoice_date'] = 'invoice_date';
		$columns['due_date']     = 'due_date';
		$columns['status']       = 'status';
		$columns['grand_total']  = 'grand_total';
		$columns['amount_due']   = 'amount_due';
		return $columns;
	}

	/**
	 * Handle column sorting
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function handle_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_invoice' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'invoice_date':
				$query->set( 'meta_key', '_berp_invoice_date' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'due_date':
				$query->set( 'meta_key', '_berp_due_date' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'status':
				$query->set( 'meta_key', '_berp_status' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'grand_total':
				$query->set( 'meta_key', '_berp_grand_total' );
				$query->set( 'orderby', 'meta_value_num' );
				break;

			case 'amount_due':
				$query->set( 'meta_key', '_berp_amount_due' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
		}
	}

	/**
	 * Add filter dropdowns
	 *
	 * @since 1.0.0
	 * @param string $post_type Post type.
	 */
	public function add_filters( $post_type ) {
		if ( 'berp_invoice' !== $post_type ) {
			return;
		}

		// Client filter.
		$clients = get_posts(
			array(
				'post_type'      => 'berp_client',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$selected_client = isset( $_GET['berp_client_filter'] ) ? absint( $_GET['berp_client_filter'] ) : 0;
		?>
		<select name="berp_client_filter">
			<option value=""><?php esc_html_e( 'All Clients', 'aic_builderp' ); ?></option>
			<?php foreach ( $clients as $client ) : ?>
				<option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $selected_client, $client->ID ); ?>>
					<?php echo esc_html( $client->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php

		// Site filter.
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$selected_site = isset( $_GET['berp_site_filter'] ) ? absint( $_GET['berp_site_filter'] ) : 0;
		?>
		<select name="berp_site_filter">
			<option value=""><?php esc_html_e( 'All Sites', 'aic_builderp' ); ?></option>
			<?php foreach ( $sites as $site ) : ?>
				<option value="<?php echo esc_attr( $site->ID ); ?>" <?php selected( $selected_site, $site->ID ); ?>>
					<?php echo esc_html( $site->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php

		// Status filter.
		$statuses        = array( 'draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled' );
		$selected_status = isset( $_GET['berp_status_filter'] ) ? sanitize_text_field( $_GET['berp_status_filter'] ) : '';
		?>
		<select name="berp_status_filter">
			<option value=""><?php esc_html_e( 'All Statuses', 'aic_builderp' ); ?></option>
			<?php foreach ( $statuses as $status ) : ?>
				<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $selected_status, $status ); ?>>
					<?php echo esc_html( berp_get_invoice_status_label( $status ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php
		// Aging filter.
		$aging_options  = array(
			'current' => __( 'Current (not due)', 'aic_builderp' ),
			'1-30'    => __( '1-30 days overdue', 'aic_builderp' ),
			'31-60'   => __( '31-60 days overdue', 'aic_builderp' ),
			'61-90'   => __( '61-90 days overdue', 'aic_builderp' ),
			'90+'     => __( '90+ days overdue', 'aic_builderp' ),
		);
		$selected_aging = isset( $_GET['berp_aging_filter'] ) ? sanitize_text_field( $_GET['berp_aging_filter'] ) : '';
		?>
		<select name="berp_aging_filter">
			<option value=""><?php esc_html_e( 'All Ages', 'aic_builderp' ); ?></option>
			<?php foreach ( $aging_options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_aging, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter posts by meta
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function filter_by_meta( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'berp_invoice' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = array();

		// Filter by client.
		if ( isset( $_GET['berp_client_filter'] ) && ! empty( $_GET['berp_client_filter'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_client_id',
				'value'   => absint( $_GET['berp_client_filter'] ),
				'compare' => '=',
			);
		}

		// Filter by site.
		if ( isset( $_GET['berp_site_filter'] ) && ! empty( $_GET['berp_site_filter'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_site_id',
				'value'   => absint( $_GET['berp_site_filter'] ),
				'compare' => '=',
			);
		}

		// Filter by status.
		if ( isset( $_GET['berp_status_filter'] ) && ! empty( $_GET['berp_status_filter'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_status',
				'value'   => sanitize_text_field( $_GET['berp_status_filter'] ),
				'compare' => '=',
			);
		}

		// Filter by aging.
		if ( isset( $_GET['berp_aging_filter'] ) && ! empty( $_GET['berp_aging_filter'] ) ) {
			$today = current_time( 'Y-m-d' );
			$aging = sanitize_text_field( $_GET['berp_aging_filter'] );

			switch ( $aging ) {
				case 'current':
					$meta_query[] = array(
						'key'     => '_berp_due_date',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					);
					break;

				case '1-30':
					$meta_query[] = array(
						'key'     => '_berp_due_date',
						'value'   => array( gmdate( 'Y-m-d', strtotime( '-30 days' ) ), gmdate( 'Y-m-d', strtotime( '-1 day' ) ) ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					);
					break;

				case '31-60':
					$meta_query[] = array(
						'key'     => '_berp_due_date',
						'value'   => array( gmdate( 'Y-m-d', strtotime( '-60 days' ) ), gmdate( 'Y-m-d', strtotime( '-31 days' ) ) ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					);
					break;

				case '61-90':
					$meta_query[] = array(
						'key'     => '_berp_due_date',
						'value'   => array( gmdate( 'Y-m-d', strtotime( '-90 days' ) ), gmdate( 'Y-m-d', strtotime( '-61 days' ) ) ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					);
					break;

				case '90+':
					$meta_query[] = array(
						'key'     => '_berp_due_date',
						'value'   => gmdate( 'Y-m-d', strtotime( '-90 days' ) ),
						'compare' => '<',
						'type'    => 'DATE',
					);
					break;
			}

			// For overdue filters, exclude paid and cancelled.
			if ( 'current' !== $aging ) {
				$meta_query[] = array(
					'key'     => '_berp_status',
					'value'   => array( 'paid', 'cancelled' ),
					'compare' => 'NOT IN',
				);
			}
		}

		if ( ! empty( $meta_query ) ) {
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
		if ( 'berp_invoice' !== $post->post_type ) {
			return $actions;
		}

		$status = get_post_meta( $post->ID, '_berp_status', true );

		// Add "View PDF" action.
		$actions['view_pdf'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action' => 'berp_view_invoice_pdf',
						'id'     => $post->ID,
					),
					admin_url( 'admin-ajax.php' )
				)
			),
			__( 'View PDF', 'aic_builderp' )
		);

		// Add "Send to Client" action.
		if ( ! in_array( $status, array( 'paid', 'cancelled' ), true ) ) {
			$actions['send_email'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'berp_send_invoice',
								'id'     => $post->ID,
							)
						),
						'berp_send_invoice_' . $post->ID
					)
				),
				__( 'Send to Client', 'aic_builderp' )
			);
		}

		// Add "Record Payment" action.
		if ( ! in_array( $status, array( 'paid', 'cancelled' ), true ) ) {
			$actions['record_payment'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $post->ID ) . '#payments-tab' ),
				__( 'Record Payment', 'aic_builderp' )
			);
		}

		// Add "Mark as Paid" action for partially paid invoices.
		if ( 'partially_paid' === $status ) {
			$actions['mark_paid'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'berp_mark_invoice_paid',
								'id'     => $post->ID,
							)
						),
						'berp_mark_paid_' . $post->ID
					)
				),
				__( 'Mark as Paid', 'aic_builderp' )
			);
		}

		return $actions;
	}
}
