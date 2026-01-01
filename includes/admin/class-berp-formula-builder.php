<?php
/**
 * Salary Formula Builder admin page.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Salary Formula Builder admin experience.
 */
class BERP_Formula_Builder {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'berp_salary_formula';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_berp_save_formula', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_berp_preview_formula', array( $this, 'ajax_preview_formula' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_script' ) );
	}

	/**
	 * Add submenu item.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'builderp',
			__( 'Salary Formula Builder', 'aic_builderp' ),
			__( 'Formula Builder', 'aic_builderp' ),
			// Allow users with payroll management capability or manage_options fallback.
			'berp_view_dashboard',
			'builderp-formula',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		// Check capability - this method is called from BERP_Admin_Menu which already checks manage_options.
		// Keep this as a safety check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'aic_builderp' ) );
		}

		$config = $this->get_config();
		$active = $config['active'];
		?>
		<div class="wrap berp-formula-builder">
			<h1><?php esc_html_e( 'Salary Formula Builder', 'aic_builderp' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Define variables, build your salary formula, test with sample data, and activate the version for payroll.', 'aic_builderp' ); ?></p>
			<?php settings_errors( 'berp_formula_builder' ); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="berp-formula-builder-form">
				<?php wp_nonce_field( 'berp_save_formula', 'berp_formula_nonce' ); ?>
				<input type="hidden" name="action" value="berp_save_formula" />

				<div class="berp-card">
					<h2><?php esc_html_e( 'Step 1: Define Variables', 'aic_builderp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Create the variables that will be available inside the formula. Use snake_case keys.', 'aic_builderp' ); ?></p>
					<div class="notice notice-info inline" style="margin: 15px 0; padding: 10px 15px;">
						<p style="margin: 5px 0;">
							<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
							<strong><?php esc_html_e( 'Settings Integration:', 'aic_builderp' ); ?></strong>
							<?php
							printf(
								/* translators: %s: link to settings page */
								esc_html__( 'Default values are automatically synced from your %s. Your custom Sample values are saved and will persist across page reloads.', 'aic_builderp' ),
								'<a href="' . esc_url( admin_url( 'admin.php?page=builderp-settings&tab=payroll' ) ) . '">' . esc_html__( 'Payroll Settings', 'aic_builderp' ) . '</a>'
							);
							?>
						</p>
					</div>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Key', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Label', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Type', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Default', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Sample', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Description', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'aic_builderp' ); ?></th>
							</tr>
						</thead>
						<tbody id="berp-formula-variables">
							<?php foreach ( $active['variables'] as $index => $variable ) : ?>
								<?php $this->render_variable_row( $index, $variable ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button button-secondary berp-add-variable"><?php esc_html_e( 'Add Variable', 'aic_builderp' ); ?></button></p>
				</div>

				<div class="berp-card">
					<h2><?php esc_html_e( 'Step 2: Build Formula', 'aic_builderp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Use the visual formula builder to create your salary calculation. Drag and drop nodes to build your formula.', 'aic_builderp' ); ?></p>

					<!-- Toggle between visual and text editor -->
					<div class="berp-formula-editor-toggle" style="margin-bottom: 15px;">
						<button type="button" class="button button-primary berp-use-visual-builder" id="berp-use-visual-builder"><?php esc_html_e( 'Visual Builder', 'aic_builderp' ); ?></button>
						<button type="button" class="button button-secondary berp-use-text-editor" id="berp-use-text-editor"><?php esc_html_e( 'Text Editor', 'aic_builderp' ); ?></button>
					</div>

					<!-- Visual Formula Builder Container -->
					<div id="berp-visual-formula-builder" class="berp-visual-builder-container" style="display: none;">
						<div class="berp-visual-builder-wrapper">
							<div class="berp-visual-builder-sidebar">
								<div class="berp-visual-builder-header">🛠 <?php esc_html_e( 'Salary Builder', 'aic_builderp' ); ?></div>

								<div class="berp-visual-builder-category"><?php esc_html_e( 'Variables', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-var" draggable="true" data-type="VARIABLE"><?php esc_html_e( 'Variable', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-var" draggable="true" data-type="NUMBER"><?php esc_html_e( 'Number', 'aic_builderp' ); ?></div>

								<div class="berp-visual-builder-category"><?php esc_html_e( 'Math (Dynamic)', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-math" draggable="true" data-type="ADD"><?php esc_html_e( 'Addition (+)', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-math" draggable="true" data-type="MUL"><?php esc_html_e( 'Multiplication (*)', 'aic_builderp' ); ?></div>

								<div class="berp-visual-builder-category"><?php esc_html_e( 'Math (Simple)', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-math" draggable="true" data-type="SUB"><?php esc_html_e( 'Subtraction (-)', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-math" draggable="true" data-type="DIV"><?php esc_html_e( 'Division (/)', 'aic_builderp' ); ?></div>

								<div class="berp-visual-builder-category"><?php esc_html_e( 'Functions', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-func" draggable="true" data-type="IF"><?php esc_html_e( 'IF Condition', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-func" draggable="true" data-type="MIN"><?php esc_html_e( 'MIN', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-func" draggable="true" data-type="MAX"><?php esc_html_e( 'MAX', 'aic_builderp' ); ?></div>

								<div class="berp-visual-builder-category"><?php esc_html_e( 'Logic', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-logic" draggable="true" data-type="GT"><?php esc_html_e( 'Greater (>)', 'aic_builderp' ); ?></div>
								<div class="berp-node-btn berp-node-logic" draggable="true" data-type="LT"><?php esc_html_e( 'Less (<)', 'aic_builderp' ); ?></div>
							</div>

							<div class="berp-visual-builder-workspace" id="berp-visual-builder-workspace">
								<svg id="berp-visual-builder-connections"></svg>
							</div>
						</div>

						<div class="berp-visual-builder-bottom-bar">
							<div class="berp-visual-builder-formula-label"><?php esc_html_e( 'GENERATED FORMULA', 'aic_builderp' ); ?></div>
							<div id="berp-visual-builder-final-formula" class="berp-visual-builder-formula-output">waiting for result...</div>
						</div>
					</div>

					<!-- Text Editor (Original) -->
					<div id="berp-text-formula-editor" class="berp-text-editor-container">
						<textarea name="berp_formula[formula]" rows="5" class="large-text code" required><?php echo esc_textarea( $active['formula'] ); ?></textarea>

					<div class="berp-formula-explanation" style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
						<strong><?php esc_html_e( 'Plain Language:', 'aic_builderp' ); ?></strong>
						<p id="berp-formula-explanation-text" style="margin: 5px 0 0 0; font-style: italic;">
							<?php echo esc_html( berp_explain_formula( $active['formula'], $active['variables'] ) ); ?>
						</p>
					</div>

					<p class="berp-formula-helper" style="margin-top: 15px;">
						<strong><?php esc_html_e( 'Example:', 'aic_builderp' ); ?></strong>
						<code>(basic_salary / working_days) * present_days + (overtime_hours * overtime_rate) + total_allowances - total_deductions</code>
					</p>
					<div class="berp-template-buttons">
						<?php foreach ( $config['templates'] as $template ) : ?>
							<button type="button" class="button button-secondary berp-formula-template" data-formula="<?php echo esc_attr( $template['formula'] ); ?>"><?php echo esc_html( $template['label'] ); ?></button>
						<?php endforeach; ?>
					</div>
					<p style="margin-top: 15px;">
						<button type="button" class="button button-link" id="berp-toggle-formula-docs"><?php esc_html_e( 'Show Formula Documentation', 'aic_builderp' ); ?></button>
					</p>
					<div id="berp-formula-docs-container" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ccd0d4;">
						<?php echo berp_get_formula_documentation(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<div class="berp-card">
					<h2><?php esc_html_e( 'Step 3: Test Formula with Sample Data', 'aic_builderp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Use the Sample column above to set values, then click Test to preview the calculation.', 'aic_builderp' ); ?></p>
					<button type="button" class="button button-primary berp-test-formula"><?php esc_html_e( 'Test Formula', 'aic_builderp' ); ?></button>
					<div class="berp-formula-preview" id="berp-formula-preview" aria-live="polite"></div>
				</div>

				<div class="berp-card">
					<h2><?php esc_html_e( 'Step 4: Save & Activate', 'aic_builderp' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Saving will create a version snapshot. The newest saved formula becomes active for payroll calculations.', 'aic_builderp' ); ?></p>
					<label>
						<input type="checkbox" name="berp_formula[is_active]" value="1" <?php checked( isset( $active['is_active'] ) ? $active['is_active'] : false, true ); ?> />
						<?php esc_html_e( 'Set this formula as Active', 'aic_builderp' ); ?>
					</label>
					<p>
						<label for="berp_formula_notes"><?php esc_html_e( 'Notes (optional)', 'aic_builderp' ); ?></label>
						<textarea id="berp_formula_notes" name="berp_formula[notes]" rows="3" class="large-text"><?php echo esc_textarea( isset( $active['notes'] ) ? $active['notes'] : '' ); ?></textarea>
					</p>
					<?php submit_button( __( 'Save Formula', 'aic_builderp' ), 'primary', 'submit', false ); ?>
				</div>
			</form>

			<div class="berp-card">
				<h2><?php esc_html_e( 'Version History', 'aic_builderp' ); ?></h2>
				<?php if ( empty( $config['history'] ) ) : ?>
					<p><?php esc_html_e( 'No previous versions yet. Save to create the first snapshot.', 'aic_builderp' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Label', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Saved At', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Saved By', 'aic_builderp' ); ?></th>
								<th><?php esc_html_e( 'Preview', 'aic_builderp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $config['history'] as $entry ) : ?>
								<tr>
									<td><?php echo esc_html( $entry['label'] ); ?></td>
									<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['saved_at'] ) ); ?></td>
									<td><?php echo esc_html( $this->get_user_name( $entry['saved_by'] ) ); ?></td>
									<td><code><?php echo esc_html( $entry['formula'] ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
	</div>

	<!-- Variable dropdown template for the visual builder -->
	<select id="berp-variable-dropdown-template" style="display: none;">
		<option value=""><?php esc_html_e( 'Select a variable...', 'aic_builderp' ); ?></option>
		<?php foreach ( $active['variables'] as $variable ) : ?>
			<option value="<?php echo esc_attr( $variable['key'] ); ?>"><?php echo esc_html( $variable['label'] ); ?></option>
		<?php endforeach; ?>
	</select>

	<script type="text/template" id="berp-variable-row-template">
			<tr class="berp-variable-row">
				<td><input type="text" name="berp_formula[variables][{{index}}][key]" value="" /></td>
				<td><input type="text" name="berp_formula[variables][{{index}}][label]" value="" /></td>
				<td>
					<select name="berp_formula[variables][{{index}}][type]">
						<option value="number"><?php echo esc_html( __( 'Number', 'aic_builderp' ) ); ?></option>
						<option value="currency"><?php echo esc_html( __( 'Currency', 'aic_builderp' ) ); ?></option>
						<option value="percent"><?php echo esc_html( __( 'Percent', 'aic_builderp' ) ); ?></option>
					</select>
				</td>
				<td><input type="number" step="0.01" name="berp_formula[variables][{{index}}][default]" value="" /></td>
				<td><input type="number" step="0.01" name="berp_formula[variables][{{index}}][sample]" value="" /></td>
				<td><input type="text" name="berp_formula[variables][{{index}}][description]" value="" /></td>
				<td><button type="button" class="button link-delete berp-remove-variable"><?php echo esc_html( __( 'Remove', 'aic_builderp' ) ); ?></button></td>
			</tr>
		</script>
		<?php
	}

	/**
	 * Render a variable row.
	 *
	 * @param int   $index    Row index.
	 * @param array $variable Variable data.
	 * @return void
	 */
	private function render_variable_row( $index, $variable ) {
		$defaults = array(
			'key'         => '',
			'label'       => '',
			'type'        => 'number',
			'default'     => '',
			'sample'      => '',
			'description' => '',
		);
		$variable = wp_parse_args( $variable, $defaults );
		?>
		<tr class="berp-variable-row">
			<td><input type="text" name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $variable['key'] ); ?>" /></td>
			<td><input type="text" name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $variable['label'] ); ?>" /></td>
			<td>
				<select name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][type]">
					<option value="number" <?php selected( $variable['type'], 'number' ); ?>><?php esc_html_e( 'Number', 'aic_builderp' ); ?></option>
					<option value="currency" <?php selected( $variable['type'], 'currency' ); ?>><?php esc_html_e( 'Currency', 'aic_builderp' ); ?></option>
					<option value="percent" <?php selected( $variable['type'], 'percent' ); ?>><?php esc_html_e( 'Percent', 'aic_builderp' ); ?></option>
				</select>
			</td>
			<td><input type="number" step="0.01" name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][default]" value="<?php echo esc_attr( $variable['default'] ); ?>" /></td>
			<td><input type="number" step="0.01" name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][sample]" value="<?php echo esc_attr( $variable['sample'] ); ?>" /></td>
			<td><input type="text" name="berp_formula[variables][<?php echo esc_attr( $index ); ?>][description]" value="<?php echo esc_attr( $variable['description'] ); ?>" /></td>
			<td><button type="button" class="button link-delete berp-remove-variable"><?php esc_html_e( 'Remove', 'aic_builderp' ); ?></button></td>
		</tr>
		<?php
	}

	/**
	 * Handle save request.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! isset( $_POST['berp_formula_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['berp_formula_nonce'] ), 'berp_save_formula' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aic_builderp' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save this formula.', 'aic_builderp' ) );
		}

		$input   = isset( $_POST['berp_formula'] ) ? wp_unslash( $_POST['berp_formula'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$clean   = $this->sanitize_input( $input );
		$config  = $this->get_config();
		$history = isset( $config['history'] ) && is_array( $config['history'] ) ? $config['history'] : array();

		// Push current active into history.
		if ( ! empty( $config['active']['formula'] ) ) {
			array_unshift(
				$history,
				array(
					'label'     => isset( $config['active']['label'] ) ? $config['active']['label'] : __( 'Previous version', 'aic_builderp' ),
					'formula'   => $config['active']['formula'],
					'variables' => $config['active']['variables'],
					'saved_at'  => isset( $config['active']['saved_at'] ) ? $config['active']['saved_at'] : time(),
					'saved_by'  => isset( $config['active']['saved_by'] ) ? $config['active']['saved_by'] : get_current_user_id(),
				)
			);
			$history = array_slice( $history, 0, 5 );
		}

		$config['active']              = $clean;
		$config['active']['is_active'] = ! empty( $clean['is_active'] );
		$config['history']             = $history;

		berp_save_salary_formula_config( $config );

		add_settings_error(
			'berp_formula_builder',
			'berp_formula_saved',
			esc_html__( 'Formula saved successfully.', 'aic_builderp' ),
			'updated'
		);

		wp_safe_redirect( admin_url( 'admin.php?page=builderp-formula' ) );
		exit;
	}

	/**
	 * AJAX: preview formula evaluation.
	 *
	 * @return void
	 */
	public function ajax_preview_formula() {
		check_ajax_referer( 'berp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'aic_builderp' ) ), 403 );
		}

		$formula   = isset( $_POST['formula'] ) ? wp_unslash( $_POST['formula'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$variables = isset( $_POST['variables'] ) ? wp_unslash( $_POST['variables'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( is_string( $variables ) ) {
			$variables = json_decode( $variables, true );
		}

		if ( ! is_array( $variables ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid variables payload.', 'aic_builderp' ) ), 400 );
		}

		$variables = $this->sanitize_preview_variables( $variables );

		$evaluator = new BERP_Formula_Evaluator();
		$result    = $evaluator->evaluate( $formula, $variables );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'result'    => $result,
				'used_vars' => array_keys( $variables ),
			)
		);
	}

	/**
	 * Sanitize input from form submission.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	private function sanitize_input( $input ) {
		$variables = array();
		if ( isset( $input['variables'] ) && is_array( $input['variables'] ) ) {
			foreach ( $input['variables'] as $row ) {
				$key = isset( $row['key'] ) ? sanitize_key( $row['key'] ) : '';
				if ( '' === $key ) {
					continue;
				}
				$variables[] = array(
					'key'         => $key,
					'label'       => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : $key,
					'type'        => isset( $row['type'] ) && in_array( $row['type'], array( 'number', 'currency', 'percent' ), true ) ? $row['type'] : 'number',
					'default'     => isset( $row['default'] ) ? sanitize_text_field( $row['default'] ) : '0',
					'sample'      => isset( $row['sample'] ) ? sanitize_text_field( $row['sample'] ) : '',
					'description' => isset( $row['description'] ) ? sanitize_text_field( $row['description'] ) : '',
				);
				if ( count( $variables ) >= 30 ) {
					break;
				}
			}
		}
		if ( empty( $variables ) ) {
			$defaults  = berp_get_default_salary_formula();
			$variables = isset( $defaults['active']['variables'] ) ? $defaults['active']['variables'] : array();
		}

		$formula = isset( $input['formula'] ) ? sanitize_textarea_field( $input['formula'] ) : '';
		if ( '' === $formula ) {
			$formula = '(basic_salary / working_days) * present_days + total_allowances - total_deductions';
		}

		return array(
			'label'       => __( 'Active Formula', 'aic_builderp' ),
			'formula'     => $formula,
			'variables'   => $variables,
			'notes'       => isset( $input['notes'] ) ? sanitize_textarea_field( $input['notes'] ) : '',
			'is_active'   => ! empty( $input['is_active'] ),
			'version'     => ( isset( $input['version'] ) ? absint( $input['version'] ) : 0 ) + 1,
			'saved_at'    => time(),
			'saved_by'    => get_current_user_id(),
			'description' => __( 'User-saved formula', 'aic_builderp' ),
		);
	}

	/**
	 * Sanitize preview variables from AJAX.
	 *
	 * @param array $variables Variables.
	 * @return array
	 */
	private function sanitize_preview_variables( $variables ) {
		$clean = array();
		foreach ( $variables as $key => $value ) {
			$clean_key           = sanitize_key( $key );
			$clean[ $clean_key ] = is_numeric( $value ) ? (float) $value : 0;
		}

		return $clean;
	}

	/**
	 * Get configuration with defaults.
	 *
	 * @return array
	 */
	private function get_config() {
		// Use settings-synced config to reflect current payroll settings.
		return berp_get_formula_config_with_settings();
	}

	/**
	 * Localize data for JS on the builder page.
	 *
	 * @param string $hook Current hook.
	 * @return void
	 */
	public function localize_script( $hook ) {
		if ( false === strpos( $hook, 'builderp-formula' ) ) {
			return;
		}

		// Enqueue formula enhancements script.
		wp_enqueue_script(
			'berp-formula-enhancements',
			plugins_url( 'assets/js/formula-enhancements.js', dirname( __DIR__, 1 ) ),
			array( 'jquery', 'berp-admin-scripts' ),
			defined( 'BERP_VERSION' ) ? BERP_VERSION : '1.0.0',
			true
		);

		// Enqueue visual formula builder script.
		wp_enqueue_script(
			'berp-formula-visual-builder',
			plugins_url( 'assets/js/formula-visual-builder.js', dirname( __DIR__, 1 ) ),
			array( 'jquery', 'berp-admin-scripts' ),
			defined( 'BERP_VERSION' ) ? BERP_VERSION : '1.0.0',
			true
		);

		$config = $this->get_config();
		wp_localize_script(
			'berp-admin-scripts',
			'berpFormulaBuilder',
			array(
				'nonce'     => wp_create_nonce( 'berp_admin_nonce' ),
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'templates' => $config['templates'],
				'strings'   => array(
					'preview_success' => __( 'Formula evaluated successfully.', 'aic_builderp' ),
					'preview_failed'  => __( 'Formula test failed.', 'aic_builderp' ),
				),
			)
		);
	}

	/**
	 * Get user display name.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private function get_user_name( $user_id ) {
		if ( empty( $user_id ) ) {
			return __( 'System', 'aic_builderp' );
		}
		$user = get_user_by( 'id', $user_id );
		return $user ? $user->display_name : __( 'Unknown', 'aic_builderp' );
	}
}
