<?php
/**
 * Invoice Metaboxes
 *
 * Handles all metaboxes for the Invoice CPT.
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
 * BERP_Invoice_Metaboxes Class
 *
 * @since 1.0.0
 */
class BERP_Invoice_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_berp_invoice', array( $this, 'save_metaboxes' ), 10, 2 );
	}

	/**
	 * Register metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		add_meta_box(
			'berp_invoice_main',
			__( 'Invoice Information', 'aic_builderp' ),
			array( $this, 'render_main_metabox' ),
			'berp_invoice',
			'normal',
			'high'
		);
	}

	/**
	 * Render main invoice metabox with tabs
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_main_metabox( $post ) {
		// Nonce field for security.
		wp_nonce_field( 'berp_save_invoice', 'berp_invoice_nonce' );

		// Get existing values.
		$invoice_number  = get_post_meta( $post->ID, '_berp_invoice_number', true );
		$client_id       = get_post_meta( $post->ID, '_berp_client_id', true );
		$site_id         = get_post_meta( $post->ID, '_berp_site_id', true );
		$quotation_id    = get_post_meta( $post->ID, '_berp_quotation_source', true );
		$invoice_date    = get_post_meta( $post->ID, '_berp_invoice_date', true );
		$due_date        = get_post_meta( $post->ID, '_berp_due_date', true );
		$status          = get_post_meta( $post->ID, '_berp_status', true );
		$line_items      = get_post_meta( $post->ID, '_berp_line_items', true );
		$subtotal        = get_post_meta( $post->ID, '_berp_subtotal', true );
		$tax_rate        = get_post_meta( $post->ID, '_berp_tax_rate', true );
		$tax_amount      = get_post_meta( $post->ID, '_berp_tax_amount', true );
		$discount_type   = get_post_meta( $post->ID, '_berp_discount_type', true );
		$discount_value  = get_post_meta( $post->ID, '_berp_discount_value', true );
		$discount_amount = get_post_meta( $post->ID, '_berp_discount_amount', true );
		$grand_total     = get_post_meta( $post->ID, '_berp_grand_total', true );
		$payments        = get_post_meta( $post->ID, '_berp_payments', true );
		$amount_paid     = get_post_meta( $post->ID, '_berp_amount_paid', true );
		$amount_due      = get_post_meta( $post->ID, '_berp_amount_due', true );
		$payment_terms   = get_post_meta( $post->ID, '_berp_payment_terms', true );
		$notes           = get_post_meta( $post->ID, '_berp_notes', true );

		// Default values.
		if ( empty( $invoice_date ) ) {
			$invoice_date = current_time( 'Y-m-d' );
		}
		if ( empty( $status ) ) {
			$status = 'draft';
		}
		if ( ! is_array( $line_items ) ) {
			$line_items = array();
		}
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}
		if ( empty( $discount_type ) ) {
			$discount_type = 'fixed';
		}
		if ( empty( $amount_paid ) ) {
			$amount_paid = 0;
		}

		// Get default values from settings.
		$settings = berp_get_quotation_settings();
		if ( empty( $tax_rate ) && isset( $settings['tax_rate'] ) ) {
			$tax_rate = $settings['tax_rate'];
		}
		if ( empty( $due_date ) && isset( $settings['invoice_due_days'] ) ) {
			$due_days = absint( $settings['invoice_due_days'] );
			$due_date = gmdate( 'Y-m-d', strtotime( "+{$due_days} days" ) );
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

		// Get all sites for dropdown.
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
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
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Invoice sections', 'aic_builderp' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="details-tab"><?php esc_html_e( 'Details', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="items-tab"><?php esc_html_e( 'Line Items', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="calculations-tab"><?php esc_html_e( 'Calculations', 'aic_builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="payments-tab"><?php esc_html_e( 'Payment Tracking', 'aic_builderp' ); ?></button>
				</nav>

				<!-- Tab Panels -->
				<div class="berp-metabox-panels">
					<!-- Details Tab -->
					<div class="berp-metabox-panel is-active" data-tab-panel="details-tab">
						<?php $this->render_details_tab( $post, $invoice_number, $client_id, $site_id, $quotation_id, $invoice_date, $due_date, $status, $payment_terms, $notes, $clients, $sites ); ?>
					</div>

					<!-- Line Items Tab -->
					<div class="berp-metabox-panel" data-tab-panel="items-tab">
						<?php $this->render_line_items_tab( $post, $line_items ); ?>
					</div>

					<!-- Calculations Tab -->
					<div class="berp-metabox-panel" data-tab-panel="calculations-tab">
						<?php $this->render_calculations_tab( $post, $subtotal, $tax_rate, $tax_amount, $discount_type, $discount_value, $discount_amount, $grand_total ); ?>
					</div>

					<!-- Payment Tracking Tab -->
					<div class="berp-metabox-panel" data-tab-panel="payments-tab">
						<?php $this->render_payments_tab( $post, $payments, $grand_total, $amount_paid, $amount_due ); ?>
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
	 * @param WP_Post $post           The post object.
	 * @param string  $invoice_number Invoice number.
	 * @param int     $client_id      Client ID.
	 * @param int     $site_id        Site ID.
	 * @param int     $quotation_id   Quotation source ID.
	 * @param string  $invoice_date   Invoice date.
	 * @param string  $due_date       Due date.
	 * @param string  $status         Status.
	 * @param string  $payment_terms  Payment terms.
	 * @param string  $notes          Notes.
	 * @param array   $clients        Clients array.
	 * @param array   $sites          Sites array.
	 */
	protected function render_details_tab( $post, $invoice_number, $client_id, $site_id, $quotation_id, $invoice_date, $due_date, $status, $payment_terms, $notes, $clients, $sites ) {
		?>
		<table class="form-table berp-form-table">
			<tr>
				<th><label for="berp_invoice_number"><?php esc_html_e( 'Invoice Number', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="text" name="berp_invoice_number" id="berp_invoice_number"
						value="<?php echo esc_attr( $invoice_number ); ?>"
						class="regular-text" readonly />
					<p class="description">
						<?php esc_html_e( 'Auto-generated. Will be assigned when you save this invoice.', 'aic_builderp' ); ?>
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
				</td>
			</tr>

			<tr>
				<th><label for="berp_site_id"><?php esc_html_e( 'Site/Project', 'aic_builderp' ); ?></label></th>
				<td>
					<select name="berp_site_id" id="berp_site_id" class="regular-text">
						<option value=""><?php esc_html_e( '-- Select Site (Optional) --', 'aic_builderp' ); ?></option>
						<?php
						foreach ( $sites as $site ) :
							$site_client_id = get_post_meta( $site->ID, '_berp_client_id', true );
							?>
							<option value="<?php echo esc_attr( $site->ID ); ?>"
								data-client-id="<?php echo esc_attr( $site_client_id ); ?>"
								<?php selected( $site_id, $site->ID ); ?>>
								<?php echo esc_html( $site->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Link invoice to a site for P&L tracking. Sites are filtered by selected client.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<?php if ( $quotation_id ) : ?>
				<tr>
					<th><?php esc_html_e( 'Source Quotation', 'aic_builderp' ); ?></th>
					<td>
						<?php
						$quotation = get_post( $quotation_id );
						if ( $quotation ) {
							$quotation_number = get_post_meta( $quotation_id, '_berp_quotation_number', true );
							echo '<a href="' . esc_url( get_edit_post_link( $quotation_id ) ) . '">';
							echo esc_html( $quotation_number ?: $quotation->post_title );
							echo '</a>';
						}
						?>
						<input type="hidden" name="berp_quotation_source" value="<?php echo esc_attr( $quotation_id ); ?>" />
					</td>
				</tr>
			<?php endif; ?>

			<tr>
				<th><label for="berp_invoice_date"><?php esc_html_e( 'Invoice Date', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="date" name="berp_invoice_date" id="berp_invoice_date"
						value="<?php echo esc_attr( $invoice_date ); ?>"
						class="regular-text" />
				</td>
			</tr>

			<tr>
				<th><label for="berp_due_date"><?php esc_html_e( 'Due Date', 'aic_builderp' ); ?></label></th>
				<td>
					<input type="date" name="berp_due_date" id="berp_due_date"
						value="<?php echo esc_attr( $due_date ); ?>"
						class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Payment due date for this invoice.', 'aic_builderp' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="berp_status"><?php esc_html_e( 'Status', 'aic_builderp' ); ?></label></th>
				<td>
					<select name="berp_status" id="berp_status" class="regular-text">
						<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'aic_builderp' ); ?></option>
						<option value="sent" <?php selected( $status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'aic_builderp' ); ?></option>
						<option value="partially_paid" <?php selected( $status, 'partially_paid' ); ?>><?php esc_html_e( 'Partially Paid', 'aic_builderp' ); ?></option>
						<option value="paid" <?php selected( $status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'aic_builderp' ); ?></option>
						<option value="overdue" <?php selected( $status, 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'aic_builderp' ); ?></option>
						<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'aic_builderp' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="berp_payment_terms"><?php esc_html_e( 'Payment Terms', 'aic_builderp' ); ?></label></th>
				<td>
					<textarea name="berp_payment_terms" id="berp_payment_terms"
						rows="3"
						class="large-text"><?php echo esc_textarea( $payment_terms ); ?></textarea>
				</td>
			</tr>

			<tr>
				<th><label for="berp_notes"><?php esc_html_e( 'Notes', 'aic_builderp' ); ?></label></th>
				<td>
					<textarea name="berp_notes" id="berp_notes"
						rows="3"
						class="large-text"><?php echo esc_textarea( $notes ); ?></textarea>
				</td>
			</tr>
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
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Payment Tracking tab
	 *
	 * @since 1.0.0
	 * @param WP_Post $post        The post object.
	 * @param array   $payments    Payments array.
	 * @param float   $grand_total Grand total.
	 * @param float   $amount_paid Amount paid.
	 * @param float   $amount_due  Amount due.
	 */
	protected function render_payments_tab( $post, $payments, $grand_total, $amount_paid, $amount_due ) {
		$currency = berp_get_currency_symbol();
		?>
		<div class="berp-payment-summary">
			<table class="form-table berp-form-table">
				<tr>
					<th><?php esc_html_e( 'Invoice Total', 'aic_builderp' ); ?></th>
					<td>
						<strong style="font-size: 1.2em;"><?php echo esc_html( $currency . number_format( floatval( $grand_total ), 2 ) ); ?></strong>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Amount Paid', 'aic_builderp' ); ?></th>
					<td>
						<span style="color: green; font-size: 1.2em;"><?php echo esc_html( $currency . number_format( floatval( $amount_paid ), 2 ) ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Amount Due', 'aic_builderp' ); ?></th>
					<td>
						<?php
						$due   = floatval( $grand_total ) - floatval( $amount_paid );
						$color = $due > 0 ? 'red' : 'green';
						?>
						<span style="color: <?php echo esc_attr( $color ); ?>; font-size: 1.2em; font-weight: bold;">
							<?php echo esc_html( $currency . number_format( $due, 2 ) ); ?>
						</span>
					</td>
				</tr>
			</table>
		</div>

		<h3><?php esc_html_e( 'Payment History', 'aic_builderp' ); ?></h3>

		<div class="berp-payments-wrapper">
			<div class="berp-repeater berp-payments-repeater" id="berp-payments-repeater">
				<div class="berp-repeater-items">
					<?php
					if ( ! empty( $payments ) ) {
						foreach ( $payments as $index => $payment ) {
							$this->render_payment_row( $payment, $index );
						}
					}
					?>
				</div>

				<script type="text/html" id="berp-payment-row-template">
					<?php $this->render_payment_row( array(), '{{INDEX}}', true ); ?>
				</script>

				<button type="button" class="button button-primary" id="berp-add-payment">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php esc_html_e( 'Record Payment', 'aic_builderp' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render single payment row
	 *
	 * @since 1.0.0
	 * @param array $payment     Payment data.
	 * @param mixed $index       Payment index.
	 * @param bool  $is_template Is template row.
	 */
	protected function render_payment_row( $payment, $index, $is_template = false ) {
		$date      = isset( $payment['date'] ) ? $payment['date'] : current_time( 'Y-m-d' );
		$amount    = isset( $payment['amount'] ) ? $payment['amount'] : '';
		$method    = isset( $payment['method'] ) ? $payment['method'] : 'bank_transfer';
		$reference = isset( $payment['reference'] ) ? $payment['reference'] : '';
		$notes     = isset( $payment['notes'] ) ? $payment['notes'] : '';

		$payment_methods = array(
			'cash'          => __( 'Cash', 'aic_builderp' ),
			'bank_transfer' => __( 'Bank Transfer', 'aic_builderp' ),
			'cheque'        => __( 'Cheque', 'aic_builderp' ),
			'credit_card'   => __( 'Credit Card', 'aic_builderp' ),
			'online'        => __( 'Online Payment', 'aic_builderp' ),
			'other'         => __( 'Other', 'aic_builderp' ),
		);
		?>
		<div class="berp-payment-row" data-index="<?php echo esc_attr( $index ); ?>" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
			<div style="display: flex; gap: 15px; flex-wrap: wrap;">
				<div class="berp-payment-field" style="flex: 0 0 150px;">
					<label><?php esc_html_e( 'Date', 'aic_builderp' ); ?></label>
					<input type="date" name="berp_payments[<?php echo esc_attr( $index ); ?>][date]"
						class="berp-payment-date"
						value="<?php echo esc_attr( $date ); ?>" />
				</div>

				<div class="berp-payment-field" style="flex: 0 0 150px;">
					<label><?php esc_html_e( 'Amount', 'aic_builderp' ); ?></label>
					<input type="number" name="berp_payments[<?php echo esc_attr( $index ); ?>][amount]"
						class="berp-payment-amount"
						value="<?php echo esc_attr( $amount ); ?>"
						step="0.01"
						min="0"
						placeholder="0.00" />
				</div>

				<div class="berp-payment-field" style="flex: 0 0 150px;">
					<label><?php esc_html_e( 'Method', 'aic_builderp' ); ?></label>
					<select name="berp_payments[<?php echo esc_attr( $index ); ?>][method]" class="berp-payment-method">
						<?php foreach ( $payment_methods as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $method, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="berp-payment-field" style="flex: 0 0 150px;">
					<label><?php esc_html_e( 'Reference #', 'aic_builderp' ); ?></label>
					<input type="text" name="berp_payments[<?php echo esc_attr( $index ); ?>][reference]"
						class="berp-payment-reference"
						value="<?php echo esc_attr( $reference ); ?>"
						placeholder="<?php esc_attr_e( 'Check/Trans #', 'aic_builderp' ); ?>" />
				</div>

				<div class="berp-payment-field" style="flex: 1 1 200px;">
					<label><?php esc_html_e( 'Notes', 'aic_builderp' ); ?></label>
					<input type="text" name="berp_payments[<?php echo esc_attr( $index ); ?>][notes]"
						class="berp-payment-notes"
						value="<?php echo esc_attr( $notes ); ?>"
						placeholder="<?php esc_attr_e( 'Payment notes', 'aic_builderp' ); ?>" />
				</div>

				<div class="berp-payment-field" style="flex: 0 0 auto; align-self: flex-end;">
					<button type="button" class="button button-secondary berp-remove-payment">
						<span class="dashicons dashicons-trash"></span>
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
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_metaboxes( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['berp_invoice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_invoice_nonce'] ) ), 'berp_save_invoice' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_manage_invoices', $post_id ) ) {
			return;
		}

		// Generate invoice number if empty.
		$invoice_number = get_post_meta( $post_id, '_berp_invoice_number', true );
		if ( empty( $invoice_number ) ) {
			$invoice_number = berp_generate_invoice_number();
			update_post_meta( $post_id, '_berp_invoice_number', $invoice_number );

			// Update post title with invoice number.
			remove_action( 'save_post_berp_invoice', array( $this, 'save_metaboxes' ), 10 );
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $invoice_number,
				)
			);
			add_action( 'save_post_berp_invoice', array( $this, 'save_metaboxes' ), 10, 2 );
		}

		// Save details.
		if ( isset( $_POST['berp_client_id'] ) ) {
			update_post_meta( $post_id, '_berp_client_id', absint( $_POST['berp_client_id'] ) );
		}

		if ( isset( $_POST['berp_site_id'] ) ) {
			update_post_meta( $post_id, '_berp_site_id', absint( $_POST['berp_site_id'] ) );
		}

		if ( isset( $_POST['berp_quotation_source'] ) ) {
			update_post_meta( $post_id, '_berp_quotation_source', absint( $_POST['berp_quotation_source'] ) );
		}

		if ( isset( $_POST['berp_invoice_date'] ) ) {
			update_post_meta( $post_id, '_berp_invoice_date', sanitize_text_field( $_POST['berp_invoice_date'] ) );
		}

		if ( isset( $_POST['berp_due_date'] ) ) {
			update_post_meta( $post_id, '_berp_due_date', sanitize_text_field( $_POST['berp_due_date'] ) );
		}

		if ( isset( $_POST['berp_payment_terms'] ) ) {
			update_post_meta( $post_id, '_berp_payment_terms', sanitize_textarea_field( $_POST['berp_payment_terms'] ) );
		}

		if ( isset( $_POST['berp_notes'] ) ) {
			update_post_meta( $post_id, '_berp_notes', sanitize_textarea_field( $_POST['berp_notes'] ) );
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

		// Save payments.
		$payments   = array();
		$total_paid = 0;
		if ( isset( $_POST['berp_payments'] ) && is_array( $_POST['berp_payments'] ) ) {
			foreach ( $_POST['berp_payments'] as $payment ) {
				if ( ! empty( $payment['amount'] ) && floatval( $payment['amount'] ) > 0 ) {
					$payment_amount = floatval( $payment['amount'] );
					$total_paid    += $payment_amount;

					$payments[] = array(
						'date'      => sanitize_text_field( $payment['date'] ),
						'amount'    => $payment_amount,
						'method'    => sanitize_text_field( $payment['method'] ),
						'reference' => sanitize_text_field( $payment['reference'] ),
						'notes'     => sanitize_text_field( $payment['notes'] ),
					);
				}
			}
		}
		update_post_meta( $post_id, '_berp_payments', $payments );
		update_post_meta( $post_id, '_berp_amount_paid', $total_paid );

		// Calculate amount due.
		$amount_due = $totals['grand_total'] - $total_paid;
		update_post_meta( $post_id, '_berp_amount_due', max( 0, $amount_due ) );

		// Auto-update status based on payments.
		$current_status = isset( $_POST['berp_status'] ) ? sanitize_text_field( $_POST['berp_status'] ) : 'draft';

		// Only auto-update if not manually set to cancelled.
		if ( 'cancelled' !== $current_status ) {
			if ( $total_paid >= $totals['grand_total'] && $totals['grand_total'] > 0 ) {
				$current_status = 'paid';
			} elseif ( $total_paid > 0 && $total_paid < $totals['grand_total'] ) {
				$current_status = 'partially_paid';
			} elseif ( berp_is_invoice_overdue( $post_id ) && in_array( $current_status, array( 'draft', 'sent' ), true ) ) {
				$current_status = 'overdue';
			}
		}

		update_post_meta( $post_id, '_berp_status', $current_status );

		// Fire action hook.
		do_action( 'berp_after_invoice_saved', $post_id, $_POST );
	}
}
