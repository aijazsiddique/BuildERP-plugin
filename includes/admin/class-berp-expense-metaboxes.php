<?php
/**
 * Expense Metaboxes
 *
 * Handles all metaboxes for the Expense CPT.
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
 * BERP_Expense_Metaboxes Class
 *
 * @since 1.0.0
 */
class BERP_Expense_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_berp_expense', array( $this, 'save_metaboxes' ), 10, 2 );
	}

	/**
	 * Register metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		add_meta_box(
			'berp_expense_main',
			__( 'Expense Information', 'builderp' ),
			array( $this, 'render_main_metabox' ),
			'berp_expense',
			'normal',
			'high'
		);
	}

	/**
	 * Render main expense metabox with tabs
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_main_metabox( $post ) {
		// Nonce field for security
		wp_nonce_field( 'berp_save_expense', 'berp_expense_nonce' );

		// Get existing values - Details
		$expense_date = get_post_meta( $post->ID, '_berp_expense_date', true );
		if ( empty( $expense_date ) ) {
			$expense_date = current_time( 'Y-m-d' );
		}
		$expense_amount      = get_post_meta( $post->ID, '_berp_expense_amount', true );
		$expense_category    = get_post_meta( $post->ID, '_berp_expense_category', true );
		$site_id             = get_post_meta( $post->ID, '_berp_site_id', true );
		$payment_method      = get_post_meta( $post->ID, '_berp_payment_method', true );
		$expense_description = get_post_meta( $post->ID, '_berp_expense_description', true );

		// Linked payroll/employee (display only)
		$linked_payroll_id  = get_post_meta( $post->ID, '_berp_linked_payroll_id', true );
		$linked_employee_id = get_post_meta( $post->ID, '_berp_linked_employee_id', true );

		// Receipts
		$receipts = get_post_meta( $post->ID, '_berp_expense_receipts', true );
		if ( ! is_array( $receipts ) ) {
			$receipts = array();
		}

		// Recurring settings
		$is_recurring        = get_post_meta( $post->ID, '_berp_is_recurring', true );
		$recurring_interval  = get_post_meta( $post->ID, '_berp_recurring_interval', true );
		$recurring_next_date = get_post_meta( $post->ID, '_berp_recurring_next_date', true );
		$recurring_end_date  = get_post_meta( $post->ID, '_berp_recurring_end_date', true );

		// Get categories from settings
		$settings        = get_option( 'berp_settings', array() );
		$categories_text = isset( $settings['expense']['default_categories'] ) ? $settings['expense']['default_categories'] : "Payroll\nMaterials\nEquipment\nSubcontractor\nOther";
		$categories      = array_filter( array_map( 'trim', explode( "\n", $categories_text ) ) );

		// Get payment methods from settings
		$payment_methods_text = isset( $settings['expense']['payment_methods'] ) ? $settings['expense']['payment_methods'] : "Cash\nBank Transfer\nCheque\nCredit Card";
		$payment_methods      = array_filter( array_map( 'trim', explode( "\n", $payment_methods_text ) ) );

		// Get all sites for dropdown
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Get currency symbol
		$currency_symbol = berp_get_currency_symbol();
		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Expense sections', 'builderp' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="berp-expense-details"><?php esc_html_e( 'Details', 'builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-expense-receipts"><?php esc_html_e( 'Receipts', 'builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-expense-recurring"><?php esc_html_e( 'Recurring Settings', 'builderp' ); ?></button>
				</nav>

				<div class="berp-metabox-panels">
					<!-- Details Tab -->
					<div class="berp-metabox-panel is-active" data-tab-panel="berp-expense-details">
						<div class="berp-metabox-grid">
							<?php if ( $linked_payroll_id || $linked_employee_id ) : ?>
								<div class="berp-field-group berp-field-full">
									<div class="notice notice-info inline">
										<p>
											<strong><?php esc_html_e( 'Note:', 'builderp' ); ?></strong>
											<?php esc_html_e( 'This expense was automatically created from payroll.', 'builderp' ); ?>
											<?php if ( $linked_payroll_id ) : ?>
												<a href="<?php echo esc_url( get_edit_post_link( $linked_payroll_id ) ); ?>">
													<?php esc_html_e( 'View Payroll Record', 'builderp' ); ?>
												</a>
											<?php endif; ?>
											<?php if ( $linked_employee_id ) : ?>
												| <a href="<?php echo esc_url( get_edit_post_link( $linked_employee_id ) ); ?>">
													<?php esc_html_e( 'View Employee', 'builderp' ); ?>
												</a>
											<?php endif; ?>
										</p>
									</div>
								</div>
							<?php endif; ?>

							<div class="berp-field-group">
								<label for="berp_expense_date"><?php esc_html_e( 'Date', 'builderp' ); ?> <span class="required">*</span></label>
								<input type="date" id="berp_expense_date" name="berp_expense_date" value="<?php echo esc_attr( $expense_date ); ?>" required />
							</div>

							<div class="berp-field-group">
								<label for="berp_expense_amount"><?php esc_html_e( 'Amount', 'builderp' ); ?> <span class="required">*</span></label>
								<div class="berp-input-with-prefix">
									<span class="berp-input-prefix"><?php echo esc_html( $currency_symbol ); ?></span>
									<input type="number" id="berp_expense_amount" name="berp_expense_amount" value="<?php echo esc_attr( $expense_amount ); ?>" min="0" step="0.01" required />
								</div>
							</div>

							<div class="berp-field-group">
								<label for="berp_expense_category"><?php esc_html_e( 'Category', 'builderp' ); ?> <span class="required">*</span></label>
								<select id="berp_expense_category" name="berp_expense_category" required>
									<option value=""><?php esc_html_e( '-- Select Category --', 'builderp' ); ?></option>
									<?php foreach ( $categories as $category ) : ?>
										<option value="<?php echo esc_attr( strtolower( $category ) ); ?>" <?php selected( $expense_category, strtolower( $category ) ); ?>>
											<?php echo esc_html( $category ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php
									printf(
										/* translators: %s: URL to settings page */
										esc_html__( 'Manage categories in %s', 'builderp' ),
										'<a href="' . esc_url( admin_url( 'admin.php?page=builderp-settings' ) ) . '">' . esc_html__( 'Settings', 'builderp' ) . '</a>'
									);
									?>
								</p>
							</div>

							<div class="berp-field-group">
								<label for="berp_site_id"><?php esc_html_e( 'Site / Project', 'builderp' ); ?></label>
								<select id="berp_site_id" name="berp_site_id">
									<option value=""><?php esc_html_e( '-- None --', 'builderp' ); ?></option>
									<?php foreach ( $sites as $site ) : ?>
										<option value="<?php echo esc_attr( $site->ID ); ?>" <?php selected( $site_id, $site->ID ); ?>>
											<?php echo esc_html( $site->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Link this expense to a specific site/project for budget tracking.', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_payment_method"><?php esc_html_e( 'Payment Method', 'builderp' ); ?> <span class="required">*</span></label>
								<select id="berp_payment_method" name="berp_payment_method" required>
									<option value=""><?php esc_html_e( '-- Select Method --', 'builderp' ); ?></option>
									<?php foreach ( $payment_methods as $method ) : ?>
										<option value="<?php echo esc_attr( strtolower( str_replace( ' ', '_', $method ) ) ); ?>" <?php selected( $payment_method, strtolower( str_replace( ' ', '_', $method ) ) ); ?>>
											<?php echo esc_html( $method ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_expense_description"><?php esc_html_e( 'Description / Notes', 'builderp' ); ?></label>
								<textarea id="berp_expense_description" name="berp_expense_description" rows="4"><?php echo esc_textarea( $expense_description ); ?></textarea>
							</div>
						</div>
					</div>

					<!-- Receipts Tab -->
					<div class="berp-metabox-panel" data-tab-panel="berp-expense-receipts">
						<div class="berp-repeater-wrapper">
							<h4><?php esc_html_e( 'Receipt Attachments', 'builderp' ); ?></h4>
							<p class="description"><?php esc_html_e( 'Upload receipt images or documents for this expense.', 'builderp' ); ?></p>

							<div class="berp-repeater berp-receipts-repeater" id="berp-receipts-repeater">
								<div class="berp-repeater-items">
									<?php
									if ( ! empty( $receipts ) ) {
										foreach ( $receipts as $index => $receipt ) {
											$this->render_receipt_row( $receipt, $index );
										}
									}
									?>
								</div>

								<!-- Template for new rows (hidden) -->
								<script type="text/html" id="berp-receipt-row-template">
									<?php $this->render_receipt_row( array(), '{{INDEX}}', true ); ?>
								</script>

								<button type="button" class="button button-secondary berp-add-receipt">
									<span class="dashicons dashicons-plus-alt"></span>
									<?php esc_html_e( 'Add Receipt', 'builderp' ); ?>
								</button>
							</div>
						</div>
					</div>

					<!-- Recurring Settings Tab -->
					<div class="berp-metabox-panel" data-tab-panel="berp-expense-recurring">
						<div class="berp-metabox-grid">
							<div class="berp-field-group berp-field-full">
								<label>
									<input type="checkbox" id="berp_is_recurring" name="berp_is_recurring" value="1" <?php checked( $is_recurring, '1' ); ?> />
									<?php esc_html_e( 'This is a recurring expense', 'builderp' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable to automatically create this expense on a regular schedule.', 'builderp' ); ?></p>
							</div>

							<div class="berp-recurring-fields" style="<?php echo ( $is_recurring != '1' ) ? 'display: none;' : ''; ?>">
								<div class="berp-field-group">
									<label for="berp_recurring_interval"><?php esc_html_e( 'Interval', 'builderp' ); ?></label>
									<select id="berp_recurring_interval" name="berp_recurring_interval">
										<option value="monthly" <?php selected( $recurring_interval, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'builderp' ); ?></option>
										<option value="yearly" <?php selected( $recurring_interval, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'builderp' ); ?></option>
									</select>
								</div>

								<div class="berp-field-group">
									<label for="berp_recurring_next_date"><?php esc_html_e( 'Next Due Date', 'builderp' ); ?></label>
									<input type="date" id="berp_recurring_next_date" name="berp_recurring_next_date" value="<?php echo esc_attr( $recurring_next_date ); ?>" />
									<p class="description"><?php esc_html_e( 'The date when the next expense should be created.', 'builderp' ); ?></p>
								</div>

								<div class="berp-field-group">
									<label for="berp_recurring_end_date"><?php esc_html_e( 'End Date (Optional)', 'builderp' ); ?></label>
									<input type="date" id="berp_recurring_end_date" name="berp_recurring_end_date" value="<?php echo esc_attr( $recurring_end_date ); ?>" />
									<p class="description"><?php esc_html_e( 'Leave empty for no end date.', 'builderp' ); ?></p>
								</div>

								<div class="berp-field-group berp-field-full">
									<div class="notice notice-warning inline">
										<p><?php esc_html_e( 'Note: Recurring expenses are processed daily by a scheduled task. The new expense will be created on or after the next due date.', 'builderp' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Tab switching
			$('.berp-metabox-tab').on('click', function() {
				var target = $(this).data('tab-target');

				// Update tabs
				$('.berp-metabox-tab').removeClass('is-active');
				$(this).addClass('is-active');

				// Update panels
				$('.berp-metabox-panel').removeClass('is-active');
				$('[data-tab-panel="' + target + '"]').addClass('is-active');
			});

			// Recurring checkbox toggle
			$('#berp_is_recurring').on('change', function() {
				if ($(this).is(':checked')) {
					$('.berp-recurring-fields').slideDown();
				} else {
					$('.berp-recurring-fields').slideUp();
				}
			});

			// Receipt repeater
			var receiptIndex = <?php echo count( $receipts ); ?>;

			$('.berp-add-receipt').on('click', function() {
				var template = $('#berp-receipt-row-template').html();
				var newRow = template.replace(/\{\{INDEX\}\}/g, receiptIndex);
				$('.berp-repeater-items').append(newRow);
				receiptIndex++;
			});

			$(document).on('click', '.berp-remove-receipt', function() {
				$(this).closest('.berp-receipt-row').remove();
			});

			// WordPress Media Uploader
			$(document).on('click', '.berp-upload-receipt', function(e) {
				e.preventDefault();
				var button = $(this);
				var input = button.siblings('.berp-receipt-url');
				var preview = button.siblings('.berp-receipt-preview');

				var mediaUploader = wp.media({
					title: '<?php echo esc_js( __( 'Select Receipt', 'builderp' ) ); ?>',
					button: {
						text: '<?php echo esc_js( __( 'Use this file', 'builderp' ) ); ?>'
					},
					multiple: false
				});

				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					input.val(attachment.url);
					button.siblings('.berp-receipt-file-id').val(attachment.id);

					var link = '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>';
					preview.html(link).show();
				});

				mediaUploader.open();
			});
		});
		</script>
		<?php
	}

	/**
	 * Render a single receipt row
	 *
	 * @since 1.0.0
	 * @param array      $receipt Receipt data
	 * @param int|string $index Row index
	 * @param bool       $is_template Whether this is a template row
	 */
	private function render_receipt_row( $receipt = array(), $index = 0, $is_template = false ) {
		$title   = isset( $receipt['title'] ) ? $receipt['title'] : '';
		$url     = isset( $receipt['url'] ) ? $receipt['url'] : '';
		$file_id = isset( $receipt['file_id'] ) ? $receipt['file_id'] : '';
		?>
		<div class="berp-receipt-row" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="berp-receipt-fields">
				<div class="berp-receipt-field">
					<label><?php esc_html_e( 'Title', 'builderp' ); ?></label>
					<input type="text" name="berp_receipts[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g., Invoice #12345', 'builderp' ); ?>" />
				</div>

				<div class="berp-receipt-field">
					<label><?php esc_html_e( 'File', 'builderp' ); ?></label>
					<input type="hidden" class="berp-receipt-url" name="berp_receipts[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_url( $url ); ?>" />
					<input type="hidden" class="berp-receipt-file-id" name="berp_receipts[<?php echo esc_attr( $index ); ?>][file_id]" value="<?php echo esc_attr( $file_id ); ?>" />
					<button type="button" class="button berp-upload-receipt">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Upload', 'builderp' ); ?>
					</button>
					<div class="berp-receipt-preview" style="<?php echo empty( $url ) ? 'display:none;' : ''; ?>">
						<?php if ( ! empty( $url ) ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank">
								<?php echo esc_html( basename( $url ) ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="berp-receipt-actions">
					<button type="button" class="button berp-remove-receipt" title="<?php esc_attr_e( 'Remove', 'builderp' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save metabox data
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID
	 * @param WP_Post $post Post object
	 */
	public function save_metaboxes( $post_id, $post ) {
		// Security checks
		if ( ! isset( $_POST['berp_expense_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_expense_nonce'] ) ), 'berp_save_expense' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_manage_expenses', $post_id ) ) {
			return;
		}

		// Save Details
		if ( isset( $_POST['berp_expense_date'] ) ) {
			update_post_meta( $post_id, '_berp_expense_date', sanitize_text_field( $_POST['berp_expense_date'] ) );
		}

		if ( isset( $_POST['berp_expense_amount'] ) ) {
			$amount = floatval( $_POST['berp_expense_amount'] );
			if ( $amount < 0 ) {
				$amount = 0;
			}
			update_post_meta( $post_id, '_berp_expense_amount', $amount );
		}

		if ( isset( $_POST['berp_expense_category'] ) ) {
			update_post_meta( $post_id, '_berp_expense_category', sanitize_text_field( $_POST['berp_expense_category'] ) );
		}

		if ( isset( $_POST['berp_site_id'] ) ) {
			$old_site_id = get_post_meta( $post_id, '_berp_site_id', true );
			$new_site_id = absint( $_POST['berp_site_id'] );

			update_post_meta( $post_id, '_berp_site_id', $new_site_id );

			// Update budget for old and new sites
			if ( $old_site_id && $old_site_id != $new_site_id ) {
				$this->update_site_budget( $old_site_id );
			}
			if ( $new_site_id ) {
				$this->update_site_budget( $new_site_id );
			}
		}

		if ( isset( $_POST['berp_payment_method'] ) ) {
			update_post_meta( $post_id, '_berp_payment_method', sanitize_text_field( $_POST['berp_payment_method'] ) );
		}

		if ( isset( $_POST['berp_expense_description'] ) ) {
			update_post_meta( $post_id, '_berp_expense_description', sanitize_textarea_field( $_POST['berp_expense_description'] ) );
		}

		// Save Receipts
		$receipts = array();
		if ( isset( $_POST['berp_receipts'] ) && is_array( $_POST['berp_receipts'] ) ) {
			foreach ( $_POST['berp_receipts'] as $receipt ) {
				if ( ! empty( $receipt['url'] ) ) {
					$receipts[] = array(
						'title'   => sanitize_text_field( $receipt['title'] ),
						'url'     => esc_url_raw( $receipt['url'] ),
						'file_id' => absint( $receipt['file_id'] ),
					);
				}
			}
		}
		update_post_meta( $post_id, '_berp_expense_receipts', $receipts );

		// Save Recurring Settings
		$is_recurring = isset( $_POST['berp_is_recurring'] ) ? '1' : '0';
		update_post_meta( $post_id, '_berp_is_recurring', $is_recurring );

		if ( $is_recurring === '1' ) {
			if ( isset( $_POST['berp_recurring_interval'] ) ) {
				update_post_meta( $post_id, '_berp_recurring_interval', sanitize_text_field( $_POST['berp_recurring_interval'] ) );
			}
			if ( isset( $_POST['berp_recurring_next_date'] ) ) {
				update_post_meta( $post_id, '_berp_recurring_next_date', sanitize_text_field( $_POST['berp_recurring_next_date'] ) );
			}
			if ( isset( $_POST['berp_recurring_end_date'] ) ) {
				update_post_meta( $post_id, '_berp_recurring_end_date', sanitize_text_field( $_POST['berp_recurring_end_date'] ) );
			}
		}

		// Fire action hook for extensibility
		do_action( 'berp_after_expense_saved', $post_id, $_POST );
	}

	/**
	 * Update site budget spent
	 *
	 * @since 1.0.0
	 * @param int $site_id Site post ID
	 */
	private function update_site_budget( $site_id ) {
		if ( empty( $site_id ) ) {
			return;
		}

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

		update_post_meta( $site_id, '_berp_budget_spent', floatval( $total ) );
	}
}

