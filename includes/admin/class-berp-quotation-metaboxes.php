<?php
/**
 * Quotation Metaboxes
 *
 * Handles all metaboxes for the Quotation CPT.
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
 * BERP_Quotation_Metaboxes Class
 *
 * @since 1.0.0
 */
class BERP_Quotation_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_berp_quotation', array( $this, 'save_metaboxes' ), 10, 2 );
	}

	/**
	 * Register metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		add_meta_box(
			'berp_quotation_main',
			__( 'Quotation Information', 'aic_builderp' ),
			array( $this, 'render_main_metabox' ),
			'berp_quotation',
			'normal',
			'high'
		);
	}

	/**
	 * Render main quotation metabox with tabs
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_main_metabox( $post ) {
		// Nonce field for security.
		wp_nonce_field( 'berp_save_quotation', 'berp_quotation_nonce' );

		// Get existing values.
		$quotation_number = get_post_meta( $post->ID, '_berp_quotation_number', true );
		$client_id        = get_post_meta( $post->ID, '_berp_client_id', true );
		$quotation_date   = get_post_meta( $post->ID, '_berp_quotation_date', true );
		$validity_date    = get_post_meta( $post->ID, '_berp_validity_date', true );
		$status           = get_post_meta( $post->ID, '_berp_status', true );
		$line_items       = get_post_meta( $post->ID, '_berp_line_items', true );
		$subtotal         = get_post_meta( $post->ID, '_berp_subtotal', true );
		$tax_rate         = get_post_meta( $post->ID, '_berp_tax_rate', true );
		$tax_amount       = get_post_meta( $post->ID, '_berp_tax_amount', true );
		$discount_type    = get_post_meta( $post->ID, '_berp_discount_type', true );
		$discount_value   = get_post_meta( $post->ID, '_berp_discount_value', true );
		$discount_amount  = get_post_meta( $post->ID, '_berp_discount_amount', true );
		$grand_total      = get_post_meta( $post->ID, '_berp_grand_total', true );
		$payment_terms    = get_post_meta( $post->ID, '_berp_payment_terms', true );
		$notes            = get_post_meta( $post->ID, '_berp_notes', true );
		$attachments      = get_post_meta( $post->ID, '_berp_attachments', true );

		// Default values.
		if ( empty( $quotation_date ) ) {
			$quotation_date = current_time( 'Y-m-d' );
		}
		if ( empty( $status ) ) {
			$status = 'draft';
		}
		if ( ! is_array( $line_items ) ) {
			$line_items = array();
		}
		if ( ! is_array( $attachments ) ) {
			$attachments = array();
		}
		if ( empty( $discount_type ) ) {
			$discount_type = 'fixed';
		}

		// Get default values from settings.
		$settings = berp_get_quotation_settings();
		if ( empty( $tax_rate ) && isset( $settings['default_tax_rate'] ) ) {
			$tax_rate = $settings['default_tax_rate'];
		}
		if ( empty( $validity_date ) && isset( $settings['default_validity_days'] ) ) {
			$validity_days = absint( $settings['default_validity_days'] );
			$validity_date = gmdate( 'Y-m-d', strtotime( "+{$validity_days} days" ) );
		}

		// Get all clients for dropdown.
		$clients = get_posts(
			array(
				'post_type'      => 'berp_client',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<!-- Tab Navigation -->
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Quotation sections', 'aic_builderp' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="details-tab"><?php esc_html_e( 'Details', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="items-tab"><?php esc_html_e( 'Line Items', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="calculations-tab"><?php esc_html_e( 'Calculations', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="terms-tab"><?php esc_html_e( 'Terms & Attachments', 'aic_builderp' ); ?></button>
				</nav>

			<!-- Tab Panels -->
			<div class="berp-metabox-panels">
				<!-- Details Tab -->
				<div class="berp-metabox-panel is-active" data-tab-panel="details-tab">
					<?php $this->render_details_tab( $post, $quotation_number, $client_id, $quotation_date, $validity_date, $status, $clients ); ?>
				</div>

				<!-- Line Items Tab -->
				<div class="berp-metabox-panel" data-tab-panel="items-tab">
					<?php $this->render_line_items_tab( $post, $line_items ); ?>
				</div>

				<!-- Calculations Tab -->
				<div class="berp-metabox-panel" data-tab-panel="calculations-tab">
					<?php $this->render_calculations_tab( $post, $subtotal, $tax_rate, $tax_amount, $discount_type, $discount_value, $discount_amount, $grand_total ); ?>
				</div>

				<!-- Terms & Attachments Tab -->
				<div class="berp-metabox-panel" data-tab-panel="terms-tab">
					<?php $this->render_terms_tab( $post, $payment_terms, $notes, $attachments ); ?>
				</div>
			</div>
		</div>
	</div>
		<?php
	}

	/**
	 * Render Details tab
	 *
	 * @since 1.0.0
	 * @param WP_Post $post             The post object.
	 * @param string  $quotation_number Quotation number.
	 * @param int     $client_id        Client ID.
	 * @param string  $quotation_date   Quotation date.
	 * @param string  $validity_date    Validity date.
	 * @param string  $status           Status.
	 * @param array   $clients          Clients array.
	 */
	protected function render_details_tab( $post, $quotation_number, $client_id, $quotation_date, $validity_date, $status, $clients ) {
		?>
		<table class="form-table berp-form-table">
			<tr>
				<th><label for="berp_quotation_number"><?php esc_html_e( 'Quotation Number', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="text" name="berp_quotation_number" id="berp_quotation_number"
						value="<?php echo esc_attr( $quotation_number ); ?>"
						class="regular-text" readonly />
					<p class="description">
						<?php esc_html_e( 'Auto-generated. Will be assigned when you save this quotation.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_client_id"><?php esc_html_e( 'Client', 'aic_builderp' ); ?> <span class="required">*</span></label></th>
				<td>
					<select name="berp_client_id" id="berp_client_id" class="regular-text" required>
						<option value=""><?php esc_html_e( '-- Select Client --', 'aic_builderp' ); ?></option>
						<?php foreach ( $clients as $client ) : ?>
							<option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $client_id, $client->ID ); ?>>
								<?php echo esc_html( $client->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select the client for this quotation.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_quotation_date"><?php esc_html_e( 'Quotation Date', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="date" name="berp_quotation_date" id="berp_quotation_date"
						value="<?php echo esc_attr( $quotation_date ); ?>"
						class="regular-text" />
				</td>
			</tr>

			<tr>
				<th><label for="berp_validity_date"><?php esc_html_e( 'Valid Until', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="date" name="berp_validity_date" id="berp_validity_date"
						value="<?php echo esc_attr( $validity_date ); ?>"
						class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Date until which this quotation is valid.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_status"><?php esc_html_e( 'Status', 'aic_builderp' ); ?></label></th>
				<td>
					<select name="berp_status" id="berp_status" class="regular-text">
						<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'aic_builderp' ); ?></option>
						<option value="sent" <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'aic_builderp' ); ?></option>
						<option value="accepted" <?php selected( $status, 'accepted' ); ?>><?php esc_html_e( 'Accepted', 'aic_builderp' ); ?></option>
						<option value="rejected" <?php selected( $status, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'aic_builderp' ); ?></option>
						<option value="expired" <?php selected( $status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'aic_builderp' ); ?></option>
					</select>
				</td>
			</tr>

			<?php
			// Show conversion info if converted.
			$converted_site     = get_post_meta( $post->ID, '_berp_converted_to_site', true );
			$converted_invoices = get_post_meta( $post->ID, '_berp_converted_to_invoices', true );

			if ( $converted_site ) :
				$site = get_post( $converted_site );
				?>
				<tr>
					<th><?php esc_html_e( 'Converted to Site', 'aic_builderp' ); ?></th>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $converted_site ) ); ?>">
							<?php echo esc_html( $site ? $site->post_title : __( 'View Site', 'aic_builderp' ) ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>

			<?php if ( ! empty( $converted_invoices ) && is_array( $converted_invoices ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Converted to Invoices', 'aic_builderp' ); ?></th>
					<td>
						<?php
						foreach ( $converted_invoices as $invoice_id ) {
							$invoice = get_post( $invoice_id );
							if ( $invoice ) {
								echo '<a href="' . esc_url( get_edit_post_link( $invoice_id ) ) . '">' . esc_html( $invoice->post_title ) . '</a><br>';
							}
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Render Line Items tab
	 *
	 * @since 1.0.0
	 * @param WP_Post $post       The post object.
	 * @param array   $line_items Line items array.
	 */
	protected function render_line_items_tab( $post, $line_items ) {
		?>
		<div class="berp-line-items-wrapper">
			<div class="berp-repeater berp-items-repeater" id="berp-items-repeater">
				<div class="berp-repeater-items">
					<?php
					if ( ! empty( $line_items ) ) {
						foreach ( $line_items as $index => $item ) {
							$this->render_item_row( $item, $index );
						}
					}
					?>
				</div>

				<script type="text/html" id="berp-item-row-template">
					<?php $this->render_item_row( array(), '{{INDEX}}', true ); ?>
				</script>

				<button type="button" class="button button-secondary" id="berp-add-item">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php esc_html_e( 'Add Line Item', 'aic_builderp' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render single line item row
	 *
	 * @since 1.0.0
	 * @param array $item        Item data.
	 * @param mixed $index       Item index.
	 * @param bool  $is_template Is template row.
	 */
	protected function render_item_row( $item, $index, $is_template = false ) {
		$description = isset( $item['description'] ) ? $item['description'] : '';
		$quantity    = isset( $item['quantity'] ) ? $item['quantity'] : '';
		$rate        = isset( $item['rate'] ) ? $item['rate'] : '';
		$amount      = isset( $item['amount'] ) ? $item['amount'] : '';
		?>
		<div class="berp-item-row" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="berp-item-field berp-item-description-field">
				<label><?php esc_html_e( 'Description', 'aic_builderp' ); ?></label>
				<input type="text" name="berp_items[<?php echo esc_attr( $index ); ?>][description]"
					class="berp-item-description"
					value="<?php echo esc_attr( $description ); ?>"
					placeholder="<?php esc_attr_e( 'Item description', 'aic_builderp' ); ?>" />
			</div>

			<div class="berp-item-field berp-item-quantity-field">
				<label><?php esc_html_e( 'Quantity', 'aic_builderp' ); ?></label>
				<input type="number" name="berp_items[<?php echo esc_attr( $index ); ?>][quantity]"
					class="berp-item-quantity"
					value="<?php echo esc_attr( $quantity ); ?>"
					step="0.01"
					min="0"
					placeholder="0.00" />
			</div>

			<div class="berp-item-field berp-item-rate-field">
				<label><?php esc_html_e( 'Rate', 'aic_builderp' ); ?></label>
				<input type="number" name="berp_items[<?php echo esc_attr( $index ); ?>][rate]"
					class="berp-item-rate"
					value="<?php echo esc_attr( $rate ); ?>"
					step="0.01"
					min="0"
					placeholder="0.00" />
			</div>

			<div class="berp-item-field berp-item-amount-field">
				<label><?php esc_html_e( 'Amount', 'aic_builderp' ); ?></label>
				<input type="number" name="berp_items[<?php echo esc_attr( $index ); ?>][amount]"
					class="berp-item-amount"
					value="<?php echo esc_attr( $amount ); ?>"
					step="0.01"
					min="0"
					readonly
					placeholder="0.00" />
			</div>

			<div class="berp-item-field berp-item-actions-field">
				<label>&nbsp;</label>
				<button type="button" class="button button-secondary berp-remove-item">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Calculations tab
	 *
	 * @since 1.0.0
	 * @param WP_Post $post            The post object.
	 * @param float   $subtotal        Subtotal.
	 * @param float   $tax_rate        Tax rate.
	 * @param float   $tax_amount      Tax amount.
	 * @param string  $discount_type   Discount type.
	 * @param float   $discount_value  Discount value.
	 * @param float   $discount_amount Discount amount.
	 * @param float   $grand_total     Grand total.
	 */
	protected function render_calculations_tab( $post, $subtotal, $tax_rate, $tax_amount, $discount_type, $discount_value, $discount_amount, $grand_total ) {
		?>
		<table class="form-table berp-form-table">
			<tr>
				<th><label for="berp_subtotal"><?php esc_html_e( 'Subtotal', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_subtotal" id="berp_subtotal"
						value="<?php echo esc_attr( $subtotal ); ?>"
						class="regular-text"
						step="0.01"
						readonly />
					<p class="description">
						<?php esc_html_e( 'Calculated automatically from line items.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_tax_rate"><?php esc_html_e( 'Tax Rate (%)', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_tax_rate" id="berp_tax_rate"
						value="<?php echo esc_attr( $tax_rate ); ?>"
						class="small-text"
						step="0.01"
						min="0"
						max="100" />
					<p class="description">
						<?php esc_html_e( 'Tax percentage applied to (Subtotal - Discount).', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_tax_amount"><?php esc_html_e( 'Tax Amount', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_tax_amount" id="berp_tax_amount"
						value="<?php echo esc_attr( $tax_amount ); ?>"
						class="regular-text"
						step="0.01"
						readonly />
					<p class="description">
						<?php esc_html_e( 'Calculated automatically: (Subtotal - Discount) × Tax Rate.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Discount Type', 'aic_builderp' ); ?></label></th>
				<td>
					<label>
						<input type="radio" name="berp_discount_type" value="fixed"
							<?php checked( $discount_type, 'fixed' ); ?> />
						<?php esc_html_e( 'Fixed Amount', 'aic_builderp' ); ?>
					</label>
					&nbsp;&nbsp;
					<label>
						<input type="radio" name="berp_discount_type" value="percentage"
							<?php checked( $discount_type, 'percentage' ); ?> />
						<?php esc_html_e( 'Percentage', 'aic_builderp' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th><label for="berp_discount_value"><?php esc_html_e( 'Discount Value', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_discount_value" id="berp_discount_value"
						value="<?php echo esc_attr( $discount_value ); ?>"
						class="regular-text"
						step="0.01"
						min="0" />
					<p class="description">
						<?php esc_html_e( 'Enter discount amount or percentage based on type selected above.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_discount_amount"><?php esc_html_e( 'Discount Amount', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_discount_amount" id="berp_discount_amount"
						value="<?php echo esc_attr( $discount_amount ); ?>"
						class="regular-text"
						step="0.01"
						readonly />
					<p class="description">
						<?php esc_html_e( 'Calculated automatically based on discount type and value.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_grand_total"><?php esc_html_e( 'Grand Total', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="number" name="berp_grand_total" id="berp_grand_total"
						value="<?php echo esc_attr( $grand_total ); ?>"
						class="regular-text"
						step="0.01"
						readonly
						style="font-weight: bold; font-size: 1.2em;" />
					<p class="description">
						<?php esc_html_e( 'Final amount: (Subtotal - Discount) + Tax.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Terms & Attachments tab
	 *
	 * @since 1.0.0
	 * @param WP_Post $post           The post object.
	 * @param string  $payment_terms  Payment terms.
	 * @param string  $notes          Notes.
	 * @param array   $attachments    Attachments array.
	 */
	protected function render_terms_tab( $post, $payment_terms, $notes, $attachments ) {
		?>
		<table class="form-table berp-form-table">
			<tr>
				<th><label for="berp_payment_terms"><?php esc_html_e( 'Payment Terms', 'aic_builderp' ); ?></label></th>
				<td>
					<textarea name="berp_payment_terms" id="berp_payment_terms"
						rows="4"
						class="large-text"><?php echo esc_textarea( $payment_terms ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Payment terms and conditions for this quotation.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_notes"><?php esc_html_e( 'Notes', 'aic_builderp' ); ?></label></th>
				<td>
					<textarea name="berp_notes" id="berp_notes"
						rows="4"
						class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Additional notes or information for this quotation.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Attachments', 'aic_builderp' ); ?></label></th>
				<td>
					<div class="berp-repeater berp-attachments-repeater" id="berp-attachments-repeater">
						<div class="berp-repeater-items">
							<?php
							if ( ! empty( $attachments ) ) {
								foreach ( $attachments as $index => $attachment ) {
									$this->render_attachment_row( $attachment, $index );
								}
							}
							?>
						</div>

						<script type="text/html" id="berp-attachment-row-template">
							<?php $this->render_attachment_row( array(), '{{INDEX}}', true ); ?>
						</script>

						<button type="button" class="button button-secondary" id="berp-add-attachment">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Add Attachment', 'aic_builderp' ); ?>
						</button>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render single attachment row
	 *
	 * @since 1.0.0
	 * @param array $attachment  Attachment data.
	 * @param mixed $index       Attachment index.
	 * @param bool  $is_template Is template row.
	 */
	protected function render_attachment_row( $attachment, $index, $is_template = false ) {
		$name = isset( $attachment['name'] ) ? $attachment['name'] : '';
		$url  = isset( $attachment['url'] ) ? $attachment['url'] : '';
		?>
		<div class="berp-attachment-row" style="margin-bottom: 10px;">
			<input type="text" name="berp_attachments[<?php echo esc_attr( $index ); ?>][name]"
				placeholder="<?php esc_attr_e( 'Attachment name', 'aic_builderp' ); ?>"
				value="<?php echo esc_attr( $name ); ?>"
				class="regular-text" />
			<input type="text" name="berp_attachments[<?php echo esc_attr( $index ); ?>][url]"
				placeholder="<?php esc_attr_e( 'URL or file path', 'aic_builderp' ); ?>"
				value="<?php echo esc_attr( $url ); ?>"
				class="berp-attachment-url large-text" />
			<button type="button" class="button button-secondary berp-upload-attachment">
				<?php esc_html_e( 'Upload', 'aic_builderp' ); ?>
			</button>
			<button type="button" class="button button-secondary berp-remove-attachment">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Save metabox data
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_metaboxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['berp_quotation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_quotation_nonce'] ) ), 'berp_save_quotation' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_manage_quotations', $post_id ) ) {
			return;
		}

		// Generate quotation number if empty.
		$quotation_number = get_post_meta( $post_id, '_berp_quotation_number', true );
		if ( empty( $quotation_number ) ) {
			$quotation_number = berp_generate_quotation_number();
			update_post_meta( $post_id, '_berp_quotation_number', $quotation_number );

			// Update post title with quotation number.
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $quotation_number,
				)
			);
		}

		// Save details.
		if ( isset( $_POST['berp_client_id'] ) ) {
			update_post_meta( $post_id, '_berp_client_id', absint( $_POST['berp_client_id'] ) );
		}

		if ( isset( $_POST['berp_quotation_date'] ) ) {
			update_post_meta( $post_id, '_berp_quotation_date', sanitize_text_field( $_POST['berp_quotation_date'] ) );
		}

		if ( isset( $_POST['berp_validity_date'] ) ) {
			update_post_meta( $post_id, '_berp_validity_date', sanitize_text_field( $_POST['berp_validity_date'] ) );
		}

		if ( isset( $_POST['berp_status'] ) ) {
			update_post_meta( $post_id, '_berp_status', sanitize_text_field( $_POST['berp_status'] ) );
		}

		// Save line items.
		$line_items = array();
		if ( isset( $_POST['berp_items'] ) && is_array( $_POST['berp_items'] ) ) {
			foreach ( $_POST['berp_items'] as $item ) {
				if ( ! empty( $item['description'] ) ) {
					$quantity = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
					$rate     = isset( $item['rate'] ) ? floatval( $item['rate'] ) : 0;
					$amount   = $quantity * $rate;

					$line_items[] = array(
						'description' => sanitize_text_field( $item['description'] ),
						'quantity'    => $quantity,
						'rate'        => $rate,
						'amount'      => $amount,
					);
				}
			}
		}
		update_post_meta( $post_id, '_berp_line_items', $line_items );

		// Save tax and discount.
		$tax_rate       = isset( $_POST['berp_tax_rate'] ) ? floatval( $_POST['berp_tax_rate'] ) : 0;
		$discount_type  = isset( $_POST['berp_discount_type'] ) ? sanitize_text_field( $_POST['berp_discount_type'] ) : 'fixed';
		$discount_value = isset( $_POST['berp_discount_value'] ) ? floatval( $_POST['berp_discount_value'] ) : 0;

		update_post_meta( $post_id, '_berp_tax_rate', $tax_rate );
		update_post_meta( $post_id, '_berp_discount_type', $discount_type );
		update_post_meta( $post_id, '_berp_discount_value', $discount_value );

		// Calculate totals server-side.
		$totals = berp_calculate_quotation_totals( $line_items, $tax_rate, $discount_type, $discount_value );

		update_post_meta( $post_id, '_berp_subtotal', $totals['subtotal'] );
		update_post_meta( $post_id, '_berp_tax_amount', $totals['tax_amount'] );
		update_post_meta( $post_id, '_berp_discount_amount', $totals['discount_amount'] );
		update_post_meta( $post_id, '_berp_grand_total', $totals['grand_total'] );

		// Save payment terms and notes.
		if ( isset( $_POST['berp_payment_terms'] ) ) {
			update_post_meta( $post_id, '_berp_payment_terms', sanitize_textarea_field( $_POST['berp_payment_terms'] ) );
		}

		if ( isset( $_POST['berp_notes'] ) ) {
			update_post_meta( $post_id, '_berp_notes', sanitize_textarea_field( $_POST['berp_notes'] ) );
		}

		// Save attachments.
		$attachments = array();
		if ( isset( $_POST['berp_attachments'] ) && is_array( $_POST['berp_attachments'] ) ) {
			foreach ( $_POST['berp_attachments'] as $attachment ) {
				if ( ! empty( $attachment['name'] ) || ! empty( $attachment['url'] ) ) {
					$attachments[] = array(
						'name' => sanitize_text_field( $attachment['name'] ),
						'url'  => esc_url_raw( $attachment['url'] ),
					);
				}
			}
		}
		update_post_meta( $post_id, '_berp_attachments', $attachments );

		// Check if quotation is expired and update status.
		if ( berp_is_quotation_expired( $post_id ) ) {
			$current_status = get_post_meta( $post_id, '_berp_status', true );
			if ( 'draft' === $current_status || 'sent' === $current_status ) {
				update_post_meta( $post_id, '_berp_status', 'expired' );
			}
		}

		// Fire action hook.
		do_action( 'berp_after_quotation_saved', $post_id, $_POST );
	}
}
