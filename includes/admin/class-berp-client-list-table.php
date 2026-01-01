<?php
/**
 * Client List Table Customizations
 *
 * Customizes the Client CPT list table with custom columns and filters.
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
 * BERP_Client_List_Table Class
 *
 * @since 1.0.0
 */
class BERP_Client_List_Table {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_berp_client_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_berp_client_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-berp_client_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_sorting' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_by_meta' ) );
		add_filter( 'post_row_actions', array( $this, 'modify_row_actions' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'display_error_notices' ) );
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
			'cb'            => $columns['cb'],
			'title'         => $columns['title'],
			'berp_company'  => __( 'Company Name', 'aic_builderp' ),
			'berp_contact'  => __( 'Contact Person', 'aic_builderp' ),
			'berp_email'    => __( 'Email', 'aic_builderp' ),
			'berp_phone'    => __( 'Phone', 'aic_builderp' ),
			'berp_city'     => __( 'City', 'aic_builderp' ),
			'berp_projects' => __( 'Active Projects', 'aic_builderp' ),
			'date'          => __( 'Created', 'aic_builderp' ),
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
		switch ( $column ) {
			case 'berp_company':
				$company = get_post_meta( $post_id, '_berp_company_name', true );
				echo esc_html( $company ? $company : '—' );
				break;

			case 'berp_contact':
				$contact = get_post_meta( $post_id, '_berp_contact_person', true );
				echo esc_html( $contact ? $contact : '—' );
				break;

			case 'berp_email':
				$email = get_post_meta( $post_id, '_berp_client_email', true );
				if ( $email ) {
					echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				} else {
					echo '—';
				}
				break;

			case 'berp_phone':
				$phone = get_post_meta( $post_id, '_berp_client_phone', true );
				if ( $phone ) {
					echo '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
				} else {
					echo '—';
				}
				break;

			case 'berp_city':
				$city  = get_post_meta( $post_id, '_berp_client_city', true );
				$state = get_post_meta( $post_id, '_berp_client_state', true );

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

			case 'berp_projects':
				$projects = get_posts(
					array(
						'post_type'      => 'berp_site',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'meta_query'     => array(
							array(
								'key'     => '_berp_client_id',
								'value'   => $post_id,
								'compare' => '=',
							),
						),
						'fields'         => 'ids',
					)
				);

				$count = count( $projects );
				if ( $count > 0 ) {
					$url = admin_url( 'edit.php?post_type=berp_site&berp_client=' . $post_id );
					echo '<a href="' . esc_url( $url ) . '">' . sprintf(
						/* translators: %d: number of projects */
						_n( '%d Project', '%d Projects', $count, 'aic_builderp' ),
						$count
					) . '</a>';
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
		$columns['berp_company'] = 'berp_company';
		$columns['berp_email']   = 'berp_email';
		$columns['berp_city']    = 'berp_city';

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

		if ( $query->get( 'post_type' ) !== 'berp_client' ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'berp_company':
				$query->set( 'meta_key', '_berp_company_name' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'berp_email':
				$query->set( 'meta_key', '_berp_client_email' );
				$query->set( 'orderby', 'meta_value' );
				break;

			case 'berp_city':
				$query->set( 'meta_key', '_berp_client_city' );
				$query->set( 'orderby', 'meta_value' );
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
		if ( $post_type !== 'berp_client' ) {
			return;
		}

		// Filter by city
		$cities = $this->get_unique_cities();

		if ( ! empty( $cities ) ) {
			$selected = isset( $_GET['berp_city'] ) ? sanitize_text_field( $_GET['berp_city'] ) : '';

			echo '<select name="berp_city" id="berp_city_filter">';
			echo '<option value="">' . esc_html__( 'All Cities', 'aic_builderp' ) . '</option>';

			foreach ( $cities as $city ) {
				if ( empty( $city ) ) {
					continue;
				}
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $city ),
					selected( $selected, $city, false ),
					esc_html( $city )
				);
			}

			echo '</select>';
		}

		// Filter by country
		$countries = $this->get_unique_countries();

		if ( ! empty( $countries ) ) {
			$selected = isset( $_GET['berp_country'] ) ? sanitize_text_field( $_GET['berp_country'] ) : '';

			echo '<select name="berp_country" id="berp_country_filter">';
			echo '<option value="">' . esc_html__( 'All Countries', 'aic_builderp' ) . '</option>';

			foreach ( $countries as $country ) {
				if ( empty( $country ) ) {
					continue;
				}
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $country ),
					selected( $selected, $country, false ),
					esc_html( $country )
				);
			}

			echo '</select>';
		}
	}

	/**
	 * Get unique cities from all clients
	 *
	 * @since 1.0.0
	 * @return array Array of cities.
	 */
	private function get_unique_cities() {
		global $wpdb;

		$cities = $wpdb->get_col(
			"SELECT DISTINCT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_berp_client_city'
            AND meta_value != ''
            ORDER BY meta_value ASC"
		);

		return $cities ? $cities : array();
	}

	/**
	 * Get unique countries from all clients
	 *
	 * @since 1.0.0
	 * @return array Array of countries.
	 */
	private function get_unique_countries() {
		global $wpdb;

		$countries = $wpdb->get_col(
			"SELECT DISTINCT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_berp_client_country'
            AND meta_value != ''
            ORDER BY meta_value ASC"
		);

		return $countries ? $countries : array();
	}

	/**
	 * Filter posts by meta values
	 *
	 * @since 1.0.0
	 * @param WP_Query $query WordPress query object.
	 */
	public function filter_by_meta( $query ) {
		global $pagenow;

		if ( ! is_admin() || $pagenow !== 'edit.php' || ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'berp_client' ) {
			return;
		}

		$meta_query = array();

		// Filter by city
		if ( isset( $_GET['berp_city'] ) && ! empty( $_GET['berp_city'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_client_city',
				'value'   => sanitize_text_field( $_GET['berp_city'] ),
				'compare' => '=',
			);
		}

		// Filter by country
		if ( isset( $_GET['berp_country'] ) && ! empty( $_GET['berp_country'] ) ) {
			$meta_query[] = array(
				'key'     => '_berp_client_country',
				'value'   => sanitize_text_field( $_GET['berp_country'] ),
				'compare' => '=',
			);
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
		if ( $post->post_type !== 'berp_client' ) {
			return $actions;
		}

		// Add "View Projects" action
		$projects_count = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'meta_query'     => array(
					array(
						'key'     => '_berp_client_id',
						'value'   => $post->ID,
						'compare' => '=',
					),
				),
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $projects_count ) ) {
			$url                      = admin_url( 'edit.php?post_type=berp_site&berp_client=' . $post->ID );
			$actions['view_projects'] = '<a href="' . esc_url( $url ) . '">' . __( 'View Projects', 'aic_builderp' ) . '</a>';
		}

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
			case 'company_name_required':
				$message = __( 'Error: Company name is required.', 'aic_builderp' );
				break;

			case 'email_required':
				$message = __( 'Error: Email address is required.', 'aic_builderp' );
				break;

			case 'duplicate_email':
				$message = __( 'Error: A client with this email address already exists.', 'aic_builderp' );
				break;
		}

		if ( $message ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}
}

// Initialize
new BERP_Client_List_Table();
