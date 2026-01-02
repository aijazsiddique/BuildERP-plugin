<?php
/**
 * Client Metaboxes
 *
 * Handles all metaboxes for the Client CPT.
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
 * BERP_Client_Metaboxes Class
 *
 * @since 1.0.0
 */
class BERP_Client_Metaboxes {

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
		add_action( 'save_post_berp_client', array( $this, 'save_metaboxes' ), 10, 2 );
	}

	/**
	 * Register metaboxes
	 *
	 * @since 1.0.0
	 */
	public function add_metaboxes() {
		add_meta_box(
			'berp_client_main',
			__( 'Client Information', 'builderp' ),
			array( $this, 'render_main_metabox' ),
			'berp_client',
			'normal',
			'high'
		);
	}

	/**
	 * Render main client metabox with tabs
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function render_main_metabox( $post ) {
		// Nonce field for security
		wp_nonce_field( 'berp_client_metabox', 'berp_client_metabox_nonce' );

		// Get existing values
		$company_name        = get_post_meta( $post->ID, '_berp_company_name', true );
		$contact_person      = get_post_meta( $post->ID, '_berp_contact_person', true );
		$email               = get_post_meta( $post->ID, '_berp_client_email', true );
		$phone               = get_post_meta( $post->ID, '_berp_client_phone', true );
		$address             = get_post_meta( $post->ID, '_berp_client_address', true );
		$city                = get_post_meta( $post->ID, '_berp_client_city', true );
		$state               = get_post_meta( $post->ID, '_berp_client_state', true );
		$zip                 = get_post_meta( $post->ID, '_berp_client_zip', true );
		$country             = get_post_meta( $post->ID, '_berp_client_country', true );
		$registration_number = get_post_meta( $post->ID, '_berp_registration_number', true );
		$tax_id              = get_post_meta( $post->ID, '_berp_tax_id', true );
		$website             = get_post_meta( $post->ID, '_berp_client_website', true );
		$notes               = get_post_meta( $post->ID, '_berp_client_notes', true );

		// Documents
		$documents = get_post_meta( $post->ID, '_berp_client_documents', true );
		if ( ! is_array( $documents ) ) {
			$documents = array();
		}

		// Custom fields
		$employee_settings = berp_get_employee_settings();
		$custom_fields     = isset( $employee_settings['custom_fields'] ) ? $employee_settings['custom_fields'] : array();
		?>
		<div class="berp-metabox-content">
			<div class="berp-metabox-layout">
				<nav class="berp-metabox-tabs" aria-label="<?php esc_attr_e( 'Client sections', 'builderp' ); ?>">
					<button type="button" class="berp-metabox-tab is-active" data-tab-target="berp-client-details"><?php esc_html_e( 'Details', 'builderp' ); ?></button>
					<button type="button" class="berp-metabox-tab" data-tab-target="berp-client-documents"><?php esc_html_e( 'Documents', 'builderp' ); ?></button>
					<?php if ( ! empty( $custom_fields ) ) : ?>
						<button type="button" class="berp-metabox-tab" data-tab-target="berp-client-custom"><?php esc_html_e( 'Additional Info', 'builderp' ); ?></button>
					<?php endif; ?>
				</nav>

				<div class="berp-metabox-panels">
					<!-- Details Tab -->
					<div class="berp-metabox-panel is-active" data-tab-panel="berp-client-details">
						<div class="berp-metabox-grid">
							<div class="berp-field-group">
								<label for="berp_company_name"><?php esc_html_e( 'Company Name', 'builderp' ); ?> <span class="required">*</span></label>
								<input type="text" id="berp_company_name" name="berp_company_name" value="<?php echo esc_attr( $company_name ); ?>" required />
								<p class="description"><?php esc_html_e( 'Official company/business name', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_contact_person"><?php esc_html_e( 'Contact Person', 'builderp' ); ?></label>
								<input type="text" id="berp_contact_person" name="berp_contact_person" value="<?php echo esc_attr( $contact_person ); ?>" />
								<p class="description"><?php esc_html_e( 'Primary contact person name', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_client_email"><?php esc_html_e( 'Email Address', 'builderp' ); ?> <span class="required">*</span></label>
								<input type="email" id="berp_client_email" name="berp_client_email" value="<?php echo esc_attr( $email ); ?>" required />
								<p class="description"><?php esc_html_e( 'Primary email address for communication', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_client_phone"><?php esc_html_e( 'Phone Number', 'builderp' ); ?></label>
								<input type="text" id="berp_client_phone" name="berp_client_phone" value="<?php echo esc_attr( $phone ); ?>" />
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_client_address"><?php esc_html_e( 'Street Address', 'builderp' ); ?></label>
								<textarea id="berp_client_address" name="berp_client_address" rows="3"><?php echo esc_textarea( $address ); ?></textarea>
							</div>

							<div class="berp-field-group">
								<label for="berp_client_city"><?php esc_html_e( 'City', 'builderp' ); ?></label>
								<input type="text" id="berp_client_city" name="berp_client_city" value="<?php echo esc_attr( $city ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_client_state"><?php esc_html_e( 'State/Province', 'builderp' ); ?></label>
								<input type="text" id="berp_client_state" name="berp_client_state" value="<?php echo esc_attr( $state ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_client_zip"><?php esc_html_e( 'ZIP/Postal Code', 'builderp' ); ?></label>
								<input type="text" id="berp_client_zip" name="berp_client_zip" value="<?php echo esc_attr( $zip ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_client_country"><?php esc_html_e( 'Country', 'builderp' ); ?></label>
								<input type="text" id="berp_client_country" name="berp_client_country" value="<?php echo esc_attr( $country ); ?>" />
							</div>

							<div class="berp-field-group">
								<label for="berp_registration_number"><?php esc_html_e( 'Registration Number', 'builderp' ); ?></label>
								<input type="text" id="berp_registration_number" name="berp_registration_number" value="<?php echo esc_attr( $registration_number ); ?>" />
								<p class="description"><?php esc_html_e( 'Business registration number', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_tax_id"><?php esc_html_e( 'Tax ID / VAT Number', 'builderp' ); ?></label>
								<input type="text" id="berp_tax_id" name="berp_tax_id" value="<?php echo esc_attr( $tax_id ); ?>" />
								<p class="description"><?php esc_html_e( 'Tax identification number', 'builderp' ); ?></p>
							</div>

							<div class="berp-field-group">
								<label for="berp_client_website"><?php esc_html_e( 'Website', 'builderp' ); ?></label>
								<input type="url" id="berp_client_website" name="berp_client_website" value="<?php echo esc_attr( $website ); ?>" placeholder="https://" />
							</div>

							<div class="berp-field-group berp-field-full">
								<label for="berp_client_notes"><?php esc_html_e( 'Notes', 'builderp' ); ?></label>
								<textarea id="berp_client_notes" name="berp_client_notes" rows="4"><?php echo esc_textarea( $notes ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Internal notes about this client', 'builderp' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Documents Tab -->
					<div class="berp-metabox-panel" data-tab-panel="berp-client-documents">
						<p class="description" style="margin-bottom: 15px;"><?php esc_html_e( 'Add contracts, agreements, or other important documents for this client.', 'builderp' ); ?></p>

						<div id="berp-client-documents-wrapper" class="berp-repeater-wrapper">
							<?php
							if ( ! empty( $documents ) ) {
								foreach ( $documents as $index => $doc ) {
									$this->render_document_row( $index, $doc );
								}
							}
							?>
						</div>

						<button type="button" class="button" id="berp-add-client-document">
							<?php esc_html_e( '+ Add Document', 'builderp' ); ?>
						</button>

						<!-- Template for new document row -->
						<script type="text/template" id="berp-client-document-template">
							<?php
							$this->render_document_row(
								'{{INDEX}}',
								array(
									'title' => '',
									'url'   => '',
								)
							);
							?>
						</script>
					</div>

					<!-- Custom Fields Tab -->
					<?php if ( ! empty( $custom_fields ) ) : ?>
						<div class="berp-metabox-panel" data-tab-panel="berp-client-custom">
							<div class="berp-metabox-grid">
								<?php
								foreach ( $custom_fields as $field ) {
									if ( empty( $field['name'] ) || empty( $field['type'] ) ) {
										continue;
									}

									$field_key   = 'berp_custom_' . sanitize_key( $field['name'] );
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
												echo '<option value="">' . esc_html__( '-- Select --', 'builderp' ) . '</option>';
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
	 * Render single document row
	 *
	 * @since 1.0.0
	 * @param int|string $index Row index.
	 * @param array      $doc Document data.
	 */
	private function render_document_row( $index, $doc ) {
		$title = isset( $doc['title'] ) ? $doc['title'] : '';
		$url   = isset( $doc['url'] ) ? $doc['url'] : '';
		?>
		<div class="berp-repeater-item berp-document-row" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="berp-repeater-content">
				<div class="berp-field-group">
					<label><?php esc_html_e( 'Document Title', 'builderp' ); ?></label>
					<input type="text" name="berp_client_documents[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'e.g., Contract Agreement', 'builderp' ); ?>" />
				</div>
				<div class="berp-field-group">
					<label><?php esc_html_e( 'Document URL / File Path', 'builderp' ); ?></label>
					<input type="url" name="berp_client_documents[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php esc_attr_e( 'https:// or file path', 'builderp' ); ?>" />
				</div>
				<div class="berp-repeater-actions">
					<button type="button" class="button button-small berp-remove-document"><?php esc_html_e( 'Remove', 'builderp' ); ?></button>
					<?php if ( $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View', 'builderp' ); ?></a>
					<?php endif; ?>
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
		// Security checks
		if ( ! isset( $_POST['berp_client_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['berp_client_metabox_nonce'], 'berp_client_metabox' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'berp_manage_clients', $post_id ) ) {
			return;
		}

		// Prepare sanitized values first so we can validate before saving.
		$fields = array(
			'berp_company_name'        => 'sanitize_text_field',
			'berp_contact_person'      => 'sanitize_text_field',
			'berp_client_email'        => 'sanitize_email',
			'berp_client_phone'        => 'sanitize_text_field',
			'berp_client_address'      => 'sanitize_textarea_field',
			'berp_client_city'         => 'sanitize_text_field',
			'berp_client_state'        => 'sanitize_text_field',
			'berp_client_zip'          => 'sanitize_text_field',
			'berp_client_country'      => 'sanitize_text_field',
			'berp_registration_number' => 'sanitize_text_field',
			'berp_tax_id'              => 'sanitize_text_field',
			'berp_client_website'      => 'esc_url_raw',
			'berp_client_notes'        => 'sanitize_textarea_field',
		);

		$sanitized_fields = array();
		foreach ( $fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$sanitized_fields[ $field ] = call_user_func( $sanitize_callback, wp_unslash( $_POST[ $field ] ) );
			}
		}

		// Validate required fields
		$company_name   = isset( $sanitized_fields['berp_company_name'] ) ? $sanitized_fields['berp_company_name'] : '';
		$email          = isset( $sanitized_fields['berp_client_email'] ) ? $sanitized_fields['berp_client_email'] : '';
		$existing_email = get_post_meta( $post_id, '_berp_client_email', true );
		$error_code     = '';

		if ( empty( $company_name ) ) {
			$error_code = 'company_name_required';
		}

		if ( empty( $email ) && ! $error_code ) {
			$error_code = 'email_required';
		}

		// Check for duplicate email (exclude current post)
		if ( empty( $error_code ) && ! empty( $email ) ) {
			global $wpdb;
			$duplicate = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_berp_client_email'
				AND meta_value = %s
				AND post_id != %d
				LIMIT 1",
					$email,
					$post_id
				)
			);

			if ( $duplicate ) {
				$error_code = 'duplicate_email';
			}
		}

		if ( ! empty( $error_code ) ) {
			add_filter(
				'redirect_post_location',
				function ( $location ) use ( $error_code, $existing_email, $post_id ) {
					$location = add_query_arg( 'berp_error', $error_code, $location );

					// If we removed the just-created post, send the user back to "Add New" with the error flag.
					if ( 'duplicate_email' === $error_code && empty( $existing_email ) ) {
						$location = add_query_arg(
							array(
								'post_type'  => 'berp_client',
								'berp_error' => $error_code,
							),
							admin_url( 'post-new.php' )
						);
					}

					return $location;
				}
			);
		}

		if ( $error_code === 'duplicate_email' && empty( $existing_email ) ) {
			// If this was a brand new client attempt, remove the invalid post to prevent creation.
			wp_delete_post( $post_id, true );
		}

		// Abort saving when any validation error occurs.
		if ( ! empty( $error_code ) ) {
			return;
		}

		// Save Client Information after validation passes.
		foreach ( $sanitized_fields as $field => $value ) {
			update_post_meta( $post_id, '_' . $field, $value );
		}

		// Save Documents
		if ( isset( $_POST['berp_client_documents'] ) && is_array( $_POST['berp_client_documents'] ) ) {
			$documents = array();
			foreach ( $_POST['berp_client_documents'] as $doc ) {
				$title = isset( $doc['title'] ) ? sanitize_text_field( wp_unslash( $doc['title'] ) ) : '';
				$url   = isset( $doc['url'] ) ? esc_url_raw( wp_unslash( $doc['url'] ) ) : '';

				if ( ! empty( $title ) || ! empty( $url ) ) {
					$documents[] = array(
						'title' => $title,
						'url'   => $url,
					);
				}
			}
			update_post_meta( $post_id, '_berp_client_documents', $documents );
		} else {
			delete_post_meta( $post_id, '_berp_client_documents' );
		}

		// Save Custom Fields
		$employee_settings = berp_get_employee_settings();
		$custom_fields     = isset( $employee_settings['custom_fields'] ) ? $employee_settings['custom_fields'] : array();

		foreach ( $custom_fields as $field ) {
			if ( empty( $field['name'] ) ) {
				continue;
			}

			$field_key = 'berp_custom_' . sanitize_key( $field['name'] );

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
		do_action( 'berp_after_client_saved', $post_id, $_POST );
	}
}

// Initialize
new BERP_Client_Metaboxes();

