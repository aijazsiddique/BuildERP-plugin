<?php
/**
 * Helper Function Generator Tool
 *
 * Auto-generate helper functions for meta keys and validate naming
 *
 * @package    BuildErp
 * @subpackage BuildErp/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BERP_Tools_Helper_Generator {

	/**
	 * Render the helper generator tool
	 */
	public function render() {
		?>
		<div class="berp-helper-generator-tool">
			<div class="berp-tool-header">
				<h2><?php esc_html_e( 'Helper Function Generator', 'aic_builderp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Auto-generate helper functions for meta keys. Follow BuildErp naming conventions and best practices.', 'aic_builderp' ); ?>
				</p>
			</div>

			<div class="berp-generator-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="berp-meta-key"><?php esc_html_e( 'Meta Key', 'aic_builderp' ); ?></label>
						</th>
						<td>
							<input type="text" id="berp-meta-key" class="regular-text" placeholder="_berp_example_field">
							<p class="description"><?php esc_html_e( 'Enter the meta key (e.g., _berp_employee_id)', 'aic_builderp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="berp-data-type"><?php esc_html_e( 'Data Type', 'aic_builderp' ); ?></label>
						</th>
						<td>
							<select id="berp-data-type" class="regular-text">
								<option value="string"><?php esc_html_e( 'String', 'aic_builderp' ); ?></option>
								<option value="integer"><?php esc_html_e( 'Integer', 'aic_builderp' ); ?></option>
								<option value="float"><?php esc_html_e( 'Float/Decimal', 'aic_builderp' ); ?></option>
								<option value="boolean"><?php esc_html_e( 'Boolean', 'aic_builderp' ); ?></option>
								<option value="array"><?php esc_html_e( 'Array (serialized)', 'aic_builderp' ); ?></option>
								<option value="date"><?php esc_html_e( 'Date', 'aic_builderp' ); ?></option>
								<option value="email"><?php esc_html_e( 'Email', 'aic_builderp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Select the data type for proper sanitization', 'aic_builderp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="berp-post-type"><?php esc_html_e( 'Post Type', 'aic_builderp' ); ?></label>
						</th>
						<td>
							<select id="berp-post-type" class="regular-text">
								<option value=""><?php esc_html_e( 'Any', 'aic_builderp' ); ?></option>
								<option value="berp_employee"><?php esc_html_e( 'Employee', 'aic_builderp' ); ?></option>
								<option value="berp_attendance"><?php esc_html_e( 'Attendance', 'aic_builderp' ); ?></option>
								<option value="berp_payroll"><?php esc_html_e( 'Payroll', 'aic_builderp' ); ?></option>
								<option value="berp_expense"><?php esc_html_e( 'Expense', 'aic_builderp' ); ?></option>
								<option value="berp_client"><?php esc_html_e( 'Client', 'aic_builderp' ); ?></option>
								<option value="berp_site"><?php esc_html_e( 'Site', 'aic_builderp' ); ?></option>
								<option value="berp_quotation"><?php esc_html_e( 'Quotation', 'aic_builderp' ); ?></option>
								<option value="berp_invoice"><?php esc_html_e( 'Invoice', 'aic_builderp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Specify post type for better documentation (optional)', 'aic_builderp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="berp-description"><?php esc_html_e( 'Description', 'aic_builderp' ); ?></label>
						</th>
						<td>
							<textarea id="berp-description" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Brief description of what this meta field stores', 'aic_builderp' ); ?>"></textarea>
							<p class="description"><?php esc_html_e( 'Add description for better documentation', 'aic_builderp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Generate Functions', 'aic_builderp' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="berp-gen-getter" checked>
								<?php esc_html_e( 'Getter Function', 'aic_builderp' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" id="berp-gen-setter" checked>
								<?php esc_html_e( 'Setter Function', 'aic_builderp' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" id="berp-gen-delete">
								<?php esc_html_e( 'Delete Function', 'aic_builderp' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="button" id="berp-generate-helper" class="button button-primary">
						<span class="dashicons dashicons-editor-code"></span>
						<?php esc_html_e( 'Generate Helper Functions', 'aic_builderp' ); ?>
					</button>
				</p>
			</div>

			<div id="berp-generated-code" class="berp-generated-code" style="display: none;">
				<h3><?php esc_html_e( 'Generated Code', 'aic_builderp' ); ?></h3>
				<div class="berp-code-actions">
					<button type="button" id="berp-copy-code" class="button button-secondary">
						<span class="dashicons dashicons-clipboard"></span>
						<?php esc_html_e( 'Copy to Clipboard', 'aic_builderp' ); ?>
					</button>
					<button type="button" id="berp-download-code" class="button button-secondary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Download as PHP File', 'aic_builderp' ); ?>
					</button>
				</div>
				<pre id="berp-code-output" class="berp-code-block"><code></code></pre>

				<div class="berp-code-info">
					<h4><?php esc_html_e( 'Implementation Notes', 'aic_builderp' ); ?></h4>
					<ul>
						<li><?php esc_html_e( 'Add these functions to your includes/functions.php file', 'aic_builderp' ); ?></li>
						<li><?php esc_html_e( 'All functions follow BuildErp naming conventions', 'aic_builderp' ); ?></li>
						<li><?php esc_html_e( 'Input is sanitized and output is escaped', 'aic_builderp' ); ?></li>
						<li><?php esc_html_e( 'Functions include proper documentation', 'aic_builderp' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<style>
			.berp-helper-generator-tool {
				max-width: 900px;
				margin: 20px 0;
			}

			.berp-generator-form {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-top: 20px;
			}

			.berp-generator-form .form-table {
				margin-top: 0;
			}

			.berp-generator-form .form-table th {
				padding-left: 0;
			}

			.berp-generator-form .submit {
				margin-top: 20px;
				margin-bottom: 0;
				padding-top: 20px;
				border-top: 1px solid #eee;
			}

			.berp-generator-form button .dashicons {
				vertical-align: middle;
				margin-right: 5px;
			}

			.berp-generated-code {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 20px;
				margin-top: 20px;
			}

			.berp-generated-code h3 {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 2px solid #0073aa;
				color: #0073aa;
			}

			.berp-code-actions {
				margin-bottom: 15px;
			}

			.berp-code-actions button {
				margin-right: 10px;
			}

			.berp-code-actions button .dashicons {
				vertical-align: middle;
				margin-right: 5px;
			}

			.berp-code-block {
				background: #282c34;
				color: #abb2bf;
				padding: 20px;
				border-radius: 4px;
				overflow-x: auto;
				font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
				font-size: 13px;
				line-height: 1.6;
				max-height: 600px;
				overflow-y: auto;
			}

			.berp-code-block code {
				color: inherit;
				background: none;
				padding: 0;
			}

			.berp-code-info {
				background: #e8f4f8;
				border: 1px solid #b8dce8;
				border-radius: 4px;
				padding: 15px;
				margin-top: 15px;
			}

			.berp-code-info h4 {
				margin-top: 0;
				color: #0073aa;
			}

			.berp-code-info ul {
				margin: 10px 0 0 20px;
			}

			.berp-code-info li {
				margin-bottom: 8px;
				color: #555;
			}
		</style>
		<?php
	}

	/**
	 * Generate helper function
	 *
	 * @param string $meta_key Meta key
	 * @param string $data_type Data type
	 * @param string $post_type Post type
	 * @return string Generated code
	 */
	public function generate_helper( $meta_key, $data_type, $post_type = '' ) {
		// Validate meta key
		if ( empty( $meta_key ) || strpos( $meta_key, '_berp_' ) !== 0 ) {
			return '// Error: Meta key must start with _berp_';
		}

		// Generate function name from meta key
		$function_base = str_replace( '_berp_', '', $meta_key );
		$function_base = str_replace( '_', '_', $function_base );

		$code  = "<?php\n";
		$code .= "/**\n";
		$code .= " * Helper functions for {$meta_key}\n";
		$code .= " *\n";
		$code .= " * Auto-generated by BuildErp Helper Generator\n";
		$code .= ' * Generated: ' . current_time( 'Y-m-d H:i:s' ) . "\n";
		$code .= " *\n";
		$code .= " * @package BuildErp\n";
		$code .= " */\n\n";
		$code .= "// Prevent direct access\n";
		$code .= "if (!defined('ABSPATH')) {\n";
		$code .= "    exit;\n";
		$code .= "}\n\n";

		// Generate getter
		$code .= $this->generate_getter( $meta_key, $function_base, $data_type, $post_type );
		$code .= "\n\n";

		// Generate setter
		$code .= $this->generate_setter( $meta_key, $function_base, $data_type, $post_type );
		$code .= "\n\n";

		// Generate delete function
		$code .= $this->generate_delete( $meta_key, $function_base, $post_type );

		return $code;
	}

	/**
	 * Generate getter function
	 *
	 * @param string $meta_key Meta key
	 * @param string $function_base Function name base
	 * @param string $data_type Data type
	 * @param string $post_type Post type
	 * @return string Generated code
	 */
	private function generate_getter( $meta_key, $function_base, $data_type, $post_type ) {
		$function_name   = "berp_get_{$function_base}";
		$post_type_label = ! empty( $post_type ) ? str_replace( 'berp_', '', $post_type ) : 'post';

		$code  = "/**\n";
		$code .= " * Get {$function_base}\n";
		$code .= " *\n";
		$code .= ' * @param int $post_id Post ID';
		if ( ! empty( $post_type ) ) {
			$code .= " ({$post_type})";
		}
		$code .= "\n";
		$code .= " * @param mixed \$default Default value if not found\n";
		$code .= ' * @return ' . $this->get_php_type_hint( $data_type ) . " Value\n";
		$code .= " */\n";
		$code .= "function {$function_name}(\$post_id, \$default = ";
		$code .= $this->get_default_value( $data_type );
		$code .= ") {\n";
		$code .= "    if (empty(\$post_id)) {\n";
		$code .= "        return \$default;\n";
		$code .= "    }\n\n";
		$code .= "    \$value = get_post_meta(\$post_id, '{$meta_key}', true);\n\n";
		$code .= "    if (empty(\$value) && \$value !== '0' && \$value !== 0) {\n";
		$code .= "        return \$default;\n";
		$code .= "    }\n\n";
		$code .= "    // Type casting\n";
		$code .= '    ' . $this->get_type_casting( $data_type ) . "\n\n";
		$code .= "    return \$value;\n";
		$code .= '}';

		return $code;
	}

	/**
	 * Generate setter function
	 *
	 * @param string $meta_key Meta key
	 * @param string $function_base Function name base
	 * @param string $data_type Data type
	 * @param string $post_type Post type
	 * @return string Generated code
	 */
	private function generate_setter( $meta_key, $function_base, $data_type, $post_type ) {
		$function_name   = "berp_set_{$function_base}";
		$post_type_label = ! empty( $post_type ) ? str_replace( 'berp_', '', $post_type ) : 'post';

		$code  = "/**\n";
		$code .= " * Set {$function_base}\n";
		$code .= " *\n";
		$code .= ' * @param int $post_id Post ID';
		if ( ! empty( $post_type ) ) {
			$code .= " ({$post_type})";
		}
		$code .= "\n";
		$code .= ' * @param ' . $this->get_php_type_hint( $data_type ) . " \$value Value to set\n";
		$code .= " * @return bool Success status\n";
		$code .= " */\n";
		$code .= "function {$function_name}(\$post_id, \$value) {\n";
		$code .= "    if (empty(\$post_id)) {\n";
		$code .= "        return false;\n";
		$code .= "    }\n\n";
		$code .= "    // Sanitize value\n";
		$code .= '    ' . $this->get_sanitization( $data_type ) . "\n\n";
		$code .= "    return update_post_meta(\$post_id, '{$meta_key}', \$value);\n";
		$code .= '}';

		return $code;
	}

	/**
	 * Generate delete function
	 *
	 * @param string $meta_key Meta key
	 * @param string $function_base Function name base
	 * @param string $post_type Post type
	 * @return string Generated code
	 */
	private function generate_delete( $meta_key, $function_base, $post_type ) {
		$function_name   = "berp_delete_{$function_base}";
		$post_type_label = ! empty( $post_type ) ? str_replace( 'berp_', '', $post_type ) : 'post';

		$code  = "/**\n";
		$code .= " * Delete {$function_base}\n";
		$code .= " *\n";
		$code .= ' * @param int $post_id Post ID';
		if ( ! empty( $post_type ) ) {
			$code .= " ({$post_type})";
		}
		$code .= "\n";
		$code .= " * @return bool Success status\n";
		$code .= " */\n";
		$code .= "function {$function_name}(\$post_id) {\n";
		$code .= "    if (empty(\$post_id)) {\n";
		$code .= "        return false;\n";
		$code .= "    }\n\n";
		$code .= "    return delete_post_meta(\$post_id, '{$meta_key}');\n";
		$code .= '}';

		return $code;
	}

	/**
	 * Get PHP type hint for data type
	 *
	 * @param string $data_type Data type
	 * @return string PHP type hint
	 */
	private function get_php_type_hint( $data_type ) {
		$type_map = array(
			'string'  => 'string',
			'integer' => 'int',
			'float'   => 'float',
			'boolean' => 'bool',
			'array'   => 'array',
			'date'    => 'string',
			'email'   => 'string',
		);

		return isset( $type_map[ $data_type ] ) ? $type_map[ $data_type ] : 'mixed';
	}

	/**
	 * Get default value for data type
	 *
	 * @param string $data_type Data type
	 * @return string Default value
	 */
	private function get_default_value( $data_type ) {
		$defaults = array(
			'string'  => "''",
			'integer' => '0',
			'float'   => '0.0',
			'boolean' => 'false',
			'array'   => 'array()',
			'date'    => "''",
			'email'   => "''",
		);

		return isset( $defaults[ $data_type ] ) ? $defaults[ $data_type ] : 'null';
	}

	/**
	 * Get type casting code
	 *
	 * @param string $data_type Data type
	 * @return string Type casting code
	 */
	private function get_type_casting( $data_type ) {
		$casting = array(
			'string'  => '$value = (string) $value;',
			'integer' => '$value = (int) $value;',
			'float'   => '$value = (float) $value;',
			'boolean' => '$value = (bool) $value;',
			'array'   => '$value = is_array($value) ? $value : array();',
			'date'    => '$value = (string) $value;',
			'email'   => '$value = (string) $value;',
		);

		return isset( $casting[ $data_type ] ) ? $casting[ $data_type ] : '';
	}

	/**
	 * Get sanitization code
	 *
	 * @param string $data_type Data type
	 * @return string Sanitization code
	 */
	private function get_sanitization( $data_type ) {
		$sanitization = array(
			'string'  => '$value = sanitize_text_field($value);',
			'integer' => '$value = absint($value);',
			'float'   => '$value = floatval($value);',
			'boolean' => '$value = (bool) $value;',
			'array'   => "\$value = is_array(\$value) ? array_map('sanitize_text_field', \$value) : array();",
			'date'    => '$value = sanitize_text_field($value);',
			'email'   => '$value = sanitize_email($value);',
		);

		return isset( $sanitization[ $data_type ] ) ? $sanitization[ $data_type ] : '$value = sanitize_text_field($value);';
	}
}
