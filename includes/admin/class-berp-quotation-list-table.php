<?php
/**
 * Quotation List Table Customization
 *
 * Handles custom columns, filters, and sorting for the Quotation CPT list table.
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
 * BERP_Quotation_List_Table Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_List_Table {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_berp_quotation_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_quotation_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_quotation_sortable_columns', array( $this, 'sortable_columns' ) );
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
			'cb'             => $columns['cb'],
			'title'          => __( 'Quotation #', 'BuildERP' ),
			'client'         => __( 'Client', 'BuildERP' ),
			'quotation_date' => __( 'Date', 'BuildERP' ),
			'validity_date'  => __( 'Valid Until', 'BuildERP' ),
			'status'         => __( 'Status', 'BuildERP' ),
			'grand_total'    => __( 'Total', 'BuildERP' ),
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

			case 'quotation_date':
				$date = get_post_meta( $post_id, '_berp_quotation_date', true );
				echo $date ? esc_html( gmdate( 'M j, Y', strtotime( $date ) ) ) : '—';
				break;

			case 'validity_date':
				$date = get_post_meta( $post_id, '_berp_validity_date', true );
				if ( $date ) {
					$is_expired = berp_is_quotation_expired( $post_id );
					$color      = $is_expired ? 'red' : 'green';
					echo '<span style="color: ' . esc_attr( $color ) . ';">';
					echo esc_html( gmdate( 'M j, Y', strtotime( $date ) ) );
					echo '</span>';
				} else {
					echo '—';
				}
				break;

			case 'status':
				$status      = get_post_meta( $post_id, '_berp_status', true );
				$label       = berp_get_quotation_status_label( $status );
				$badge_class = 'berp-status-badge berp-status-' . esc_attr( $status );
				echo '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( $label ) . '</span>';
				break;

			case 'grand_total':
				$total    = get_post_meta( $post_id, '_berp_grand_total', true );
				$total    = ! empty( $total ) ? floatval( $total ) : 0.00;
				$currency = berp_get_currency_symbol();
				echo esc_html( $currency . number_format( $total, 2 ) );
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
		$columns['quotation_date'] = 'quotation_date';
		$columns['validity_date']  = 'validity_date';
		$columns['status']         = 'status';
		$columns['grand_total']    = 'grand_total';
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

		if ( 'berp_quotation' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'quotation_date':
				$query->set( 'meta_key', '_berp_quotation_date' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'validity_date':
				$query->set( 'meta_key', '_berp_validity_date' );
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
		}
	}

	/**
	 * Add filter dropdowns
	 *
	 * @since 1.0.0
	 * @param string $post_type Post type.
	 */
	public function add_filters( $post_type ) {
		if ( 'berp_quotation' !== $post_type ) {
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
			<option value=""><?php esc_html_e( 'All Clients', 'BuildERP' ); ?></option>
			<?php foreach ( $clients as $client ) : ?>
				<option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $selected_client, $client->ID ); ?>>
					<?php echo esc_html( $client->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php

		// Status filter.
		$statuses        = array( 'draft', 'sent', 'accepted', 'rejected', 'expired' );
		$selected_status = isset( $_GET['berp_status_filter'] ) ? sanitize_text_field( $_GET['berp_status_filter'] ) : '';
		?>
		<select name="berp_status_filter">
			<option value=""><?php esc_html_e( 'All Statuses', 'BuildERP' ); ?></option>
			<?php foreach ( $statuses as $status ) : ?>
				<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $selected_status, $status ); ?>>
					<?php echo esc_html( berp_get_quotation_status_label( $status ) ); ?>
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

		if ( 'berp_quotation' !== $query->get( 'post_type' ) ) {
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

		// Filter by status.
		if ( isset( $_GET['berp_status_filter'] ) && ! empty( $_GET['berp_status_filter'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_status',
				'value'   => sanitize_text_field( $_GET['berp_status_filter'] ),
				'compare' => '=',
			);
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
		if ( 'berp_quotation' !== $post->post_type ) {
			return $actions;
		}

		$status = get_post_meta( $post->ID, '_berp_status', true );

		// Add "View PDF" action.
		$actions['view_pdf'] = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'action' => 'berp_view_quotation_pdf',
						'id'     => $post->ID,
					),
					admin_url( 'admin-ajax.php' )
				)
			),
			__( 'View PDF', 'BuildERP' )
		);

		// Add "Send to Client" action (if not sent or status is draft).
		if ( 'draft' === $status || 'sent' === $status ) {
			$actions['send_email'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'berp_send_quotation',
								'id'     => $post->ID,
							)
						),
						'berp_send_quotation_' . $post->ID
					)
				),
				__( 'Send to Client', 'BuildERP' )
			);
		}

		// Add "Convert to Site" action (if accepted).
		if ( 'accepted' === $status ) {
			$converted_site = get_post_meta( $post->ID, '_berp_converted_to_site', true );
			if ( ! $converted_site ) {
				$actions['convert_site'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'berp_convert_to_site',
									'id'     => $post->ID,
								)
							),
							'berp_convert_site_' . $post->ID
						)
					),
					__( 'Convert to Site', 'BuildERP' )
				);
			}
		}

		return $actions;
	}
}

