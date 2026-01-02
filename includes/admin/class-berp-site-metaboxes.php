<?php
/**
 * Site Metaboxes
 *
 * Handles all metaboxes for the Site CPT.
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
 * BERP_Site_Metaboxes Class
 *
 * @since 1.0.0
 */
class BERP_Site_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_berp_site', array( $this, 'save_metaboxes' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'display_budget_alerts' ) );
	}

	/**
	 * Register metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		add_meta_box(
			'berp_site_main',
			__( 'Site / Project Details', 'BuildERP' ),
			array( $this, 'render_main_metabox' ),
			'berp_site',
			'normal',
			'high'
		);
	}

	/**
	 * Render main site metabox with tabs
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_main_metabox( $post ) {
		// Nonce field for security
		wp_nonce_field( 'berp_site_metabox', 'berp_site_metabox_nonce' );

		// Get existing values - Information
		$client_id    = get_post_meta( $post->ID, '_berp_client_id', true );
		$site_address = get_post_meta( $post->ID, '_berp_site_address', true );
		$city         = get_post_meta( $post->ID, '_berp_site_city', true );
		$state        = get_post_meta( $post->ID, '_berp_site_state', true );
		$zip          = get_post_meta( $post->ID, '_berp_site_zip', true );
		$start_date   = get_post_meta( $post->ID, '_berp_start_date', true );
		$end_date     = get_post_meta( $post->ID, '_berp_end_date', true );
		$description  = get_post_meta( $post->ID, '_berp_site_description', true );

		// Project Details
		$status = get_post_meta( $post->ID, '_berp_site_status', true );
		if ( empty( $status ) ) {
			$status = 'planning';
		}
		$manager = get_post_meta( $post->ID, '_berp_project_manager', true );
		$notes   = get_post_meta( $post->ID, '_berp_project_notes', true );

		// Budget
		$budget       = get_post_meta( $post->ID, '_berp_budget', true );
		$budget_spent = $this->calculate_budget_spent( $post->ID );
		update_post_meta( $post->ID, '_berp_budget_spent', $budget_spent );

		// Convert to float to prevent type errors
		$budget       = floatval( $budget );
		$budget_spent = floatval( $budget_spent );

		// Calculate percentage
		$budget_percentage = 0;
		if ( $budget > 0 ) {
			$budget_percentage = ( $budget_spent / $budget ) * 100;
		}

		// Get all clients for dropdown
		$clients = get_posts(
			array(
				'post_type'      => 'berp_client',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Custom fields
		$employee_settings = berp_get_employee_settings();
		$custom_fields     = isset( $employee_settings['custom_fields'] ) ? $employee_settings['custom_fields'] : array();

		// Get settings
		$currency_symbol = berp_get_currency_symbol();

		$site_settings   = berp_get_site_settings();
		$alert_threshold = isset( $site_settings['budget_alert_threshold'] ) ? absint( $site_settings['budget_alert_threshold'] ) : 80;

		// Determine status color
		$status_class = 'berp-budget-ok';
		if ( $budget_percentage >= 100 ) {
			$status_class = 'berp-budget-exceeded';
		} elseif ( $budget_percentage >= $alert_threshold ) {
			$status_class = 'berp-budget-warning';
		}
		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Site sections', 'BuildERP' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="berp-site-info"><?php esc_html_e( 'Information', 'BuildERP' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-site-project"><?php esc_html_e( 'Project Details', 'BuildERP' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-site-budget"><?php esc_html_e( 'Budget Tracking', 'BuildERP' ); ?></button>
					<?php if ( ! empty( $custom_fields ) ) : ?>
						<button type="button" class="berp-metabox-tab" data-tab-target="berp-site-custom"><?php esc_html_e( 'Additional Info', 'BuildERP' ); ?></button>
					<?php endif; ?>
				</nav>

				<div class="berp-metabox-panels">
					<!-- Information Tab -->
					<div class="berp-metabox-panel is-active" data-tab-panel="berp-site-info">
						<div class="berp-metabox-grid">
							<div class="berp-field-group berp-field-full">
								<label for="berp_client_id"><?php esc_html_e( 'Client', 'BuildERP' ); ?> <span class="required">*</span></label>
								<select id="berp_client_id" name="berp_client_id" required>
									<option value=""><?php esc_html_e( '-- Select Client --', 'BuildERP' ); ?></option>
									<?php foreach ( $clients as $client ) : ?>
										<option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $client_id, $client->ID ); ?>>
											<?php echo esc_html( $client->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( empty( $clients ) ) : ?>
									<p class="description">
										<?php
										printf(
											/* translators: %s: URL to add new client */
											esc_html__( 'No clients found. %s', 'BuildERP' ),
											'<a href="' . esc_url( admin_url( 'post-new.php?post_type=berp_client' ) ) . '">' . esc_html__( 'Add a client first', 'BuildERP' ) . '</a>'
										);
										?>
									</p>
								<?php endif; ?>
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_site_address"><?php esc_html_e( 'Site Address', 'BuildERP' ); ?></label>
								<textarea id="berp_site_address" name="berp_site_address" rows="3"><?php echo esc_textarea( $site_address ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Physical location of the construction site', 'BuildERP' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_site_city"><?php esc_html_e( 'City', 'BuildERP' ); ?></label>
								<input type="text" id="berp_site_city" name="berp_site_city" value="<?php echo esc_attr( $city ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_site_state"><?php esc_html_e( 'State/Province', 'BuildERP' ); ?></label>
								<input type="text" id="berp_site_state" name="berp_site_state" value="<?php echo esc_attr( $state ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_site_zip"><?php esc_html_e( 'ZIP/Postal Code', 'BuildERP' ); ?></label>
								<input type="text" id="berp_site_zip" name="berp_site_zip" value="<?php echo esc_attr( $zip ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_start_date"><?php esc_html_e( 'Start Date', 'BuildERP' ); ?></label>
								<input type="date" id="berp_start_date" name="berp_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_end_date"><?php esc_html_e( 'End Date', 'BuildERP' ); ?></label>
								<input type="date" id="berp_end_date" name="berp_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
								<p class="description"><?php esc_html_e( 'Expected or actual completion date', 'BuildERP' ); ?></p>
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_site_description"><?php esc_html_e( 'Project Description', 'BuildERP' ); ?></label>
								<textarea id="berp_site_description" name="berp_site_description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Brief description of the project scope and objectives', 'BuildERP' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Project Details Tab -->
					<div class="berp-metabox-panel" data-tab-panel="berp-site-project">
						<div class="berp-metabox-grid">
							<div class="berp-field-group">
								<label for="berp_site_status"><?php esc_html_e( 'Project Status', 'BuildERP' ); ?></label>
								<select id="berp_site_status" name="berp_site_status">
									<option value="planning" <?php selected( $status, 'planning' ); ?>><?php esc_html_e( 'Planning', 'BuildERP' ); ?></option>
									<option value="in_progress" <?php selected( $status, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'BuildERP' ); ?></option>
									<option value="on_hold" <?php selected( $status, 'on_hold' ); ?>><?php esc_html_e( 'On Hold', 'BuildERP' ); ?></option>
									<option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'BuildERP' ); ?></option>
								</select>
							</div>

							<div class="berp-field-group">
								<label for="berp_project_manager"><?php esc_html_e( 'Project Manager', 'BuildERP' ); ?></label>
								<input type="text" id="berp_project_manager" name="berp_project_manager" value="<?php echo esc_attr( $manager ); ?>" />
								<p class="description"><?php esc_html_e( 'Person responsible for managing this project', 'BuildERP' ); ?></p>
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_project_notes"><?php esc_html_e( 'Project Notes', 'BuildERP' ); ?></label>
								<textarea id="berp_project_notes" name="berp_project_notes" rows="8"><?php echo esc_textarea( $notes ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Internal notes, milestones, or important information', 'BuildERP' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Budget Tracking Tab -->
					<div class="berp-metabox-panel" data-tab-panel="berp-site-budget">
						<div class="berp-metabox-grid">
							<div class="berp-field-group">
								<label for="berp_budget"><strong><?php esc_html_e( 'Total Budget', 'BuildERP' ); ?></strong></label>
								<input type="number" id="berp_budget" name="berp_budget" value="<?php echo esc_attr( $budget ); ?>" step="0.01" min="0" placeholder="0.00" />
								<p class="description"><?php esc_html_e( 'Total allocated budget for this project', 'BuildERP' ); ?></p>
							</div>

							<div class="berp-field-group berp-field-full">
								<div class="berp-budget-summary <?php echo esc_attr( $status_class ); ?>">
									<div class="berp-budget-stats">
										<div class="berp-budget-stat">
											<strong><?php esc_html_e( 'Budget Spent:', 'BuildERP' ); ?></strong>
											<span class="berp-budget-value"><?php echo esc_html( $currency_symbol . number_format( $budget_spent, 2 ) ); ?></span>
										</div>

										<div class="berp-budget-stat">
											<strong><?php esc_html_e( 'Remaining:', 'BuildERP' ); ?></strong>
											<span class="berp-budget-value"><?php echo esc_html( $currency_symbol . number_format( $budget - $budget_spent, 2 ) ); ?></span>
										</div>

										<div class="berp-budget-stat">
											<strong><?php esc_html_e( 'Usage:', 'BuildERP' ); ?></strong>
											<span class="berp-budget-percentage"><?php echo esc_html( number_format( $budget_percentage, 1 ) ); ?>%</span>
										</div>
									</div>

									<div class="berp-budget-bar">
										<div class="berp-budget-bar-fill" style="width: <?php echo esc_attr( min( $budget_percentage, 100 ) ); ?>%;"></div>
									</div>

									<?php if ( $budget_percentage >= 100 ) : ?>
										<p class="berp-budget-alert berp-alert-danger">
											<span class="dashicons dashicons-warning"></span>
											<?php esc_html_e( 'Budget exceeded!', 'BuildERP' ); ?>
										</p>
									<?php elseif ( $budget_percentage >= $alert_threshold ) : ?>
										<p class="berp-budget-alert berp-alert-warning">
											<span class="dashicons dashicons-info"></span>
											<?php
											printf(
												/* translators: %s: threshold percentage */
												esc_html__( 'Warning: Budget usage above %s%%', 'BuildERP' ),
												esc_html( $alert_threshold )
											);
											?>
										</p>
									<?php endif; ?>

									<p class="description" style="margin-top: 15px;">
										<?php esc_html_e( 'Budget spent is automatically calculated from linked expenses.', 'BuildERP' ); ?>
									</p>
								</div>
							</div>
						</div>

						<style>
							.berp-budget-summary {
								padding: 20px;
								background: #f9f9f9;
								border-radius: 4px;
								border: 1px solid #ddd;
								margin-top: 10px;
								width: max-content;
							}
							.berp-budget-summary.berp-budget-warning {
								background: #fff8e5;
								border-color: #f0b849;
							}
							.berp-budget-summary.berp-budget-exceeded {
								background: #fce4e4;
								border-color: #d63638;
							}
							.berp-budget-stats {
								display: grid;
								grid-template-columns: repeat(3, 1fr);
								gap: 15px;
								margin-bottom: 15px;
							}
							.berp-budget-stat {
								text-align: center;
							}
							.berp-budget-stat strong {
								display: block;
								margin-bottom: 5px;
								color: #666;
								font-size: 13px;
							}
							.berp-budget-value,
							.berp-budget-percentage {
								font-size: 24px;
								font-weight: bold;
								color: #2271b1;
								display: block;
							}
							.berp-budget-exceeded .berp-budget-value,
							.berp-budget-exceeded .berp-budget-percentage {
								color: #d63638;
							}
							.berp-budget-warning .berp-budget-value,
							.berp-budget-warning .berp-budget-percentage {
								color: #f0b849;
							}
							.berp-budget-bar {
								height: 20px;
								background: #e0e0e0;
								border-radius: 10px;
								overflow: hidden;
								margin: 15px 0;
								
							}
							.berp-budget-bar-fill {
								height: 100%;
								background: linear-gradient(to right, #4caf50, #2271b1);
								transition: width 0.3s ease;
							}
							.berp-budget-warning .berp-budget-bar-fill {
								background: #f0b849;
							}
							.berp-budget-exceeded .berp-budget-bar-fill {
								background: #d63638;
							}
							.berp-budget-alert {
								padding: 10px 15px;
								border-radius: 4px;
								margin-top: 15px;
								font-weight: bold;
								text-align: center;
							}
							.berp-alert-warning {
								background: #f0b849;
								color: #fff;
							}
							.berp-alert-danger {
								background: #d63638;
								color: #fff;
							}
							.berp-budget-alert .dashicons {
								vertical-align: middle;
								margin-right: 5px;
							}
						</style>
					</div>

					<!-- Custom Fields Tab -->
					<?php if ( ! empty( $custom_fields ) ) : ?>
						<div class="berp-metabox-panel" data-tab-panel="berp-site-custom">
							<div class="berp-metabox-grid">
								<?php
								foreach ( $custom_fields as $field ) {
									if ( empty( $field['name'] ) || empty( $field['type'] ) ) {
										continue;
									}

									$field_key   = 'berp_site_custom_' . sanitize_key( $field['name'] );
									$field_value = get_post_meta( $post->ID, '_' . $field_key, true );
									$field_label = ! empty( $field['label'] ) ? $field['label'] : $field['name'];
									?>
									<div class="berp-field-group<?php echo ( $field['type'] === 'textarea' ? ' berp-field-full' : '' ); ?>">
										<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field_label ); ?></label>
										<?php
										switch ( $field['type'] ) {
											case 'text':
												echo '<input type="text" id="' . esc_attr( $field_key ) . '" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $field_value ) . '" />';
												break;

											case 'textarea':
												echo '<textarea id="' . esc_attr( $field_key ) . '" name="' . esc_attr( $field_key ) . '" rows="4">' . esc_textarea( $field_value ) . '</textarea>';
												break;

											case 'number':
												echo '<input type="number" id="' . esc_attr( $field_key ) . '" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $field_value ) . '" />';
												break;

											case 'date':
												echo '<input type="date" id="' . esc_attr( $field_key ) . '" name="' . esc_attr( $field_key ) . '" value="' . esc_attr( $field_value ) . '" />';
												break;

											case 'select':
												$options = ! empty( $field['options'] ) ? explode( ',', $field['options'] ) : array();
												echo '<select id="' . esc_attr( $field_key ) . '" name="' . esc_attr( $field_key ) . '">';
												echo '<option value="">' . esc_html__( '-- Select --', 'BuildERP' ) . '</option>';
												foreach ( $options as $option ) {
													$option = trim( $option );
													echo '<option value="' . esc_attr( $option ) . '" ' . selected( $field_value, $option, false ) . '>' . esc_html( $option ) . '</option>';
												}
												echo '</select>';
												break;
										}
										?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate budget spent from expenses
	 *
	 * @since 1.0.0
	 * @param int $site_id Site post ID.
	 * @return float Total expenses for this site.
	 */
	private function calculate_budget_spent( $site_id ) {
		global $wpdb;

		$total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_berp_expense_amount'
            AND pm2.meta_key = '_berp_site_id'
            AND pm2.meta_value = %d
            AND p.post_type = 'berp_expense'
            AND p.post_status = 'publish'",
				$site_id
			)
		);

		return floatval( $total );
	}

	/**
	 * Display budget alerts in admin notices
	 *
	 * @since 1.0.0
	 */
	public function display_budget_alerts() {
		$screen = get_current_screen();

		// Only show on site list and edit screens
		if ( ! $screen || ( $screen->post_type !== 'berp_site' ) ) {
			return;
		}

		// Get settings
		$site_settings   = berp_get_site_settings();
		$alert_threshold = isset( $site_settings['budget_alert_threshold'] ) ? absint( $site_settings['budget_alert_threshold'] ) : 80;
		$show_alerts     = isset( $site_settings['show_budget_alerts'] ) ? $site_settings['show_budget_alerts'] : true;

		if ( ! $show_alerts ) {
			return;
		}

		// Get sites exceeding budget
		global $wpdb;
		$sites = $wpdb->get_results(
			"SELECT p.ID, p.post_title,
                CAST(pm_budget.meta_value AS DECIMAL(10,2)) as budget,
                CAST(pm_spent.meta_value AS DECIMAL(10,2)) as spent
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_budget ON p.ID = pm_budget.post_id AND pm_budget.meta_key = '_berp_budget'
            LEFT JOIN {$wpdb->postmeta} pm_spent ON p.ID = pm_spent.post_id AND pm_spent.meta_key = '_berp_budget_spent'
            WHERE p.post_type = 'berp_site'
            AND p.post_status = 'publish'
            AND CAST(pm_budget.meta_value AS DECIMAL(10,2)) > 0
            HAVING (spent / budget * 100) >= {$alert_threshold}
            ORDER BY (spent / budget * 100) DESC"
		);

		if ( empty( $sites ) ) {
			return;
		}

		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p><strong>' . esc_html__( 'Budget Alerts:', 'BuildERP' ) . '</strong></p>';
		echo '<ul>';
		foreach ( $sites as $site ) {
			// Ensure float types for calculation
			$budget     = floatval( $site->budget );
			$spent      = floatval( $site->spent );
			$percentage = $budget > 0 ? ( $spent / $budget ) * 100 : 0;
			$edit_url   = admin_url( 'post.php?post=' . $site->ID . '&action=edit' );

			printf(
				'<li><a href="%s">%s</a>: %s%% ' . esc_html__( 'of budget used', 'BuildERP' ) . '</li>',
				esc_url( $edit_url ),
				esc_html( $site->post_title ),
				esc_html( number_format( $percentage, 1 ) )
			);
		}
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Save metabox data
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_metaboxes( $post_id, $post ) {
		// Security checks
		if ( ! isset( $_POST['berp_site_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['berp_site_metabox_nonce'], 'berp_site_metabox' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_manage_sites', $post_id ) ) {
			return;
		}

		// Save Site Information
		$fields = array(
			'berp_client_id'        => 'absint',
			'berp_site_address'     => 'sanitize_textarea_field',
			'berp_site_city'        => 'sanitize_text_field',
			'berp_site_state'       => 'sanitize_text_field',
			'berp_site_zip'         => 'sanitize_text_field',
			'berp_start_date'       => 'sanitize_text_field',
			'berp_end_date'         => 'sanitize_text_field',
			'berp_site_description' => 'sanitize_textarea_field',
		);

		foreach ( $fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}

		// Validate required client
		$client_id = isset( $_POST['berp_client_id'] ) ? absint( $_POST['berp_client_id'] ) : 0;
		if ( empty( $client_id ) ) {
			add_filter(
				'redirect_post_location',
				function ( $location ) {
					return add_query_arg( 'berp_error', 'client_required', $location );
				}
			);
		}

		// Save Project Details
		$project_fields = array(
			'berp_site_status'     => 'sanitize_text_field',
			'berp_project_manager' => 'sanitize_text_field',
			'berp_project_notes'   => 'sanitize_textarea_field',
		);

		foreach ( $project_fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}

		// Save Budget
		if ( isset( $_POST['berp_budget'] ) ) {
			$budget = floatval( $_POST['berp_budget'] );
			if ( $budget < 0 ) {
				$budget = 0;
			}
			update_post_meta( $post_id, '_berp_budget', $budget );
		}

		// Recalculate budget spent
		$budget_spent = $this->calculate_budget_spent( $post_id );
		update_post_meta( $post_id, '_berp_budget_spent', $budget_spent );

		// Save Custom Fields
		$employee_settings = berp_get_employee_settings();
		$custom_fields     = isset( $employee_settings['custom_fields'] ) ? $employee_settings['custom_fields'] : array();

		foreach ( $custom_fields as $field ) {
			if ( empty( $field['name'] ) ) {
				continue;
			}

			$field_key = 'berp_site_custom_' . sanitize_key( $field['name'] );

			if ( isset( $_POST[ $field_key ] ) ) {
				$value = '';
				switch ( $field['type'] ) {
					case 'text':
					case 'select':
						$value = sanitize_text_field( $_POST[ $field_key ] );
						break;
					case 'textarea':
						$value = sanitize_textarea_field( $_POST[ $field_key ] );
						break;
					case 'number':
						$value = floatval( $_POST[ $field_key ] );
						break;
					case 'date':
						$value = sanitize_text_field( $_POST[ $field_key ] );
						break;
				}
				update_post_meta( $post_id, '_' . $field_key, $value );
			}
		}

		// Fire action hook for extensibility
		do_action( 'berp_after_site_saved', $post_id, $_POST );
	}
}

// Initialize
new BERP_Site_Metaboxes();

