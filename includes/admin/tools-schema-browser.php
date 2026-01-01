<?php
/**
 * Schema Browser Tool
 *
 * Browse meta keys, see usage statistics, and find where each key is used
 *
 * @package    BuildErp
 * @subpackage BuildErp/includes/admin
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BERP_Tools_Schema_Browser {

	/**
	 * Render the schema browser tool
	 */
	public function render() {
		$meta_keys = $this->get_all_meta_keys_with_stats();
		?>
		<div class="berp-schema-browser-tool">
			<div class="berp-tool-header">
				<h2><?php esc_html_e( 'Schema Browser', 'aic_builderp' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Browse all meta keys, view usage statistics, and find where each key is used in your codebase.', 'aic_builderp' ); ?>
				</p>
			</div>

			<div class="berp-browser-search">
				<input type="text" id="berp-meta-search" class="regular-text" placeholder="<?php esc_attr_e( 'Search meta keys...', 'aic_builderp' ); ?>">
				<button type="button" id="berp-clear-search" class="button">
					<?php esc_html_e( 'Clear', 'aic_builderp' ); ?>
				</button>
			</div>

			<div class="berp-meta-grid">
				<?php foreach ( $meta_keys as $meta_key => $stats ) : ?>
					<div class="berp-meta-card" data-meta-key="<?php echo esc_attr( $meta_key ); ?>">
						<div class="berp-meta-header">
							<h4><code><?php echo esc_html( $meta_key ); ?></code></h4>
							<span class="berp-usage-badge"><?php echo esc_html( number_format( $stats['count'] ) ); ?></span>
						</div>

						<div class="berp-meta-details">
							<div class="berp-detail-row">
								<span class="berp-detail-label"><?php esc_html_e( 'Usage Count:', 'aic_builderp' ); ?></span>
								<span class="berp-detail-value"><?php echo esc_html( number_format( $stats['count'] ) ); ?></span>
							</div>

							<?php if ( ! empty( $stats['post_types'] ) ) : ?>
								<div class="berp-detail-row">
									<span class="berp-detail-label"><?php esc_html_e( 'Post Types:', 'aic_builderp' ); ?></span>
									<span class="berp-detail-value">
										<?php foreach ( $stats['post_types'] as $post_type => $count ) : ?>
											<span class="berp-post-type-tag">
												<?php echo esc_html( $post_type ); ?> (<?php echo esc_html( $count ); ?>)
											</span>
										<?php endforeach; ?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $stats['data_type'] ) ) : ?>
								<div class="berp-detail-row">
									<span class="berp-detail-label"><?php esc_html_e( 'Data Type:', 'aic_builderp' ); ?></span>
									<span class="berp-detail-value">
										<span class="berp-data-type"><?php echo esc_html( $stats['data_type'] ); ?></span>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $stats['description'] ) ) : ?>
								<div class="berp-detail-row">
									<span class="berp-detail-label"><?php esc_html_e( 'Description:', 'aic_builderp' ); ?></span>
									<span class="berp-detail-value"><?php echo esc_html( $stats['description'] ); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<div class="berp-meta-actions">
							<button type="button" class="button button-small berp-find-usage" data-meta-key="<?php echo esc_attr( $meta_key ); ?>">
								<span class="dashicons dashicons-search"></span>
								<?php esc_html_e( 'Find Usage', 'aic_builderp' ); ?>
							</button>
							<button type="button" class="button button-small berp-view-samples" data-meta-key="<?php echo esc_attr( $meta_key ); ?>">
								<span class="dashicons dashicons-visibility"></span>
								<?php esc_html_e( 'View Samples', 'aic_builderp' ); ?>
							</button>
						</div>

						<div class="berp-usage-details" id="usage-<?php echo esc_attr( md5( $meta_key ) ); ?>" style="display: none;">
							<div class="berp-loading">
								<span class="spinner is-active"></span>
								<?php esc_html_e( 'Loading...', 'aic_builderp' ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( empty( $meta_keys ) ) : ?>
				<div class="berp-no-results">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No meta keys found. Start adding data to your BuildErp plugin.', 'aic_builderp' ); ?></p>
				</div>
			<?php endif; ?>

		</div>

		<style>
			.berp-schema-browser-tool {
				max-width: 1400px;
				margin: 20px 0;
			}

			.berp-browser-search {
				margin: 20px 0;
				display: flex;
				gap: 10px;
			}

			.berp-browser-search input {
				flex: 1;
				max-width: 400px;
			}

			.berp-meta-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}

			.berp-meta-card {
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				padding: 15px;
				transition: box-shadow 0.2s;
			}

			.berp-meta-card:hover {
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}

			.berp-meta-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 15px;
				padding-bottom: 10px;
				border-bottom: 1px solid #eee;
			}

			.berp-meta-header h4 {
				margin: 0;
				font-size: 14px;
			}

			.berp-meta-header code {
				background: #f0f0f1;
				padding: 4px 8px;
				border-radius: 3px;
				font-size: 13px;
				font-weight: 600;
			}

			.berp-usage-badge {
				background: #0073aa;
				color: #fff;
				padding: 4px 10px;
				border-radius: 12px;
				font-size: 11px;
				font-weight: 600;
			}

			.berp-meta-details {
				margin-bottom: 15px;
			}

			.berp-detail-row {
				display: flex;
				padding: 8px 0;
				border-bottom: 1px solid #f0f0f1;
				font-size: 13px;
			}

			.berp-detail-row:last-child {
				border-bottom: none;
			}

			.berp-detail-label {
				font-weight: 600;
				color: #555;
				min-width: 120px;
			}

			.berp-detail-value {
				color: #23282d;
				flex: 1;
			}

			.berp-post-type-tag {
				display: inline-block;
				background: #e8f4f8;
				color: #0073aa;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 11px;
				margin-right: 4px;
				margin-bottom: 4px;
			}

			.berp-data-type {
				display: inline-block;
				background: #f0f0f1;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-family: monospace;
			}

			.berp-meta-actions {
				display: flex;
				gap: 8px;
			}

			.berp-meta-actions button {
				flex: 1;
				font-size: 12px;
			}

			.berp-meta-actions .dashicons {
				font-size: 14px;
				vertical-align: middle;
				margin-right: 3px;
			}

			.berp-usage-details {
				margin-top: 15px;
				padding-top: 15px;
				border-top: 1px solid #eee;
			}

			.berp-loading {
				text-align: center;
				padding: 20px;
				color: #666;
			}

			.berp-loading .spinner {
				float: none;
				margin: 0 auto 10px;
			}

			.berp-no-results {
				text-align: center;
				padding: 60px 20px;
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
				color: #666;
			}

			.berp-no-results .dashicons {
				font-size: 48px;
				width: 48px;
				height: 48px;
				color: #ccc;
			}

			.berp-usage-list {
				list-style: none;
				padding: 0;
				margin: 0;
			}

			.berp-usage-item {
				padding: 10px;
				background: #f9f9f9;
				border-radius: 3px;
				margin-bottom: 8px;
				font-size: 12px;
			}

			.berp-usage-item strong {
				color: #0073aa;
			}

			.berp-sample-value {
				background: #f0f0f1;
				padding: 8px;
				border-radius: 3px;
				margin-top: 5px;
				font-family: monospace;
				font-size: 11px;
				word-break: break-word;
			}
		</style>
		<?php
	}

	/**
	 * Get all meta keys with statistics
	 *
	 * @return array Meta keys with stats
	 */
	private function get_all_meta_keys_with_stats() {
		global $wpdb;

		// Get meta keys from database with counts
		$results = $wpdb->get_results(
			"SELECT pm.meta_key, COUNT(*) as count, p.post_type
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key LIKE '_berp_%' OR pm.meta_key LIKE 'berp_%'
             GROUP BY pm.meta_key, p.post_type
             ORDER BY pm.meta_key"
		);

		$meta_keys = array();

		foreach ( $results as $row ) {
			if ( ! isset( $meta_keys[ $row->meta_key ] ) ) {
				$meta_keys[ $row->meta_key ] = array(
					'count'       => 0,
					'post_types'  => array(),
					'data_type'   => $this->detect_data_type( $row->meta_key ),
					'description' => $this->get_meta_description( $row->meta_key ),
				);
			}

			$meta_keys[ $row->meta_key ]['count']                        += (int) $row->count;
			$meta_keys[ $row->meta_key ]['post_types'][ $row->post_type ] = (int) $row->count;
		}

		return $meta_keys;
	}

	/**
	 * Detect data type from meta key
	 *
	 * @param string $meta_key Meta key
	 * @return string Data type
	 */
	private function detect_data_type( $meta_key ) {
		if ( strpos( $meta_key, '_id' ) !== false ) {
			return 'integer';
		}
		if ( strpos( $meta_key, '_date' ) !== false ) {
			return 'date';
		}
		if ( strpos( $meta_key, '_amount' ) !== false || strpos( $meta_key, '_salary' ) !== false ) {
			return 'decimal';
		}
		if ( strpos( $meta_key, '_hours' ) !== false ) {
			return 'float';
		}
		if ( strpos( $meta_key, '_email' ) !== false ) {
			return 'email';
		}
		if ( strpos( $meta_key, '_phone' ) !== false ) {
			return 'phone';
		}
		if ( strpos( $meta_key, '_status' ) !== false ) {
			return 'string';
		}
		if ( strpos( $meta_key, 'allowances' ) !== false || strpos( $meta_key, 'deductions' ) !== false ) {
			return 'array';
		}

		return 'string';
	}

	/**
	 * Get meta key description
	 *
	 * @param string $meta_key Meta key
	 * @return string Description
	 */
	private function get_meta_description( $meta_key ) {
		$descriptions = array(
			'_berp_employee_id'           => __( 'Employee post ID reference', 'aic_builderp' ),
			'_berp_email'                 => __( 'Email address', 'aic_builderp' ),
			'_berp_phone'                 => __( 'Phone number', 'aic_builderp' ),
			'_berp_basic_salary'          => __( 'Base salary amount', 'aic_builderp' ),
			'_berp_allowances'            => __( 'Serialized array of allowances', 'aic_builderp' ),
			'_berp_deductions'            => __( 'Serialized array of deductions', 'aic_builderp' ),
			'_berp_status'                => __( 'Current status', 'aic_builderp' ),
			'_berp_date'                  => __( 'Date (YYYY-MM-DD)', 'aic_builderp' ),
			'_berp_site_id'               => __( 'Site post ID reference', 'aic_builderp' ),
			'_berp_overtime_hours'        => __( 'Overtime hours worked', 'aic_builderp' ),
			'_berp_client_id'             => __( 'Client post ID reference', 'aic_builderp' ),
			'_berp_budget'                => __( 'Budget amount', 'aic_builderp' ),
			'_berp_linked_user_id'        => __( 'WordPress user ID (for portal access)', 'aic_builderp' ),
			'_berp_portal_access_enabled' => __( 'Portal access enabled flag', 'aic_builderp' ),
		);

		return isset( $descriptions[ $meta_key ] ) ? $descriptions[ $meta_key ] : '';
	}

	/**
	 * Find meta key usage in code
	 *
	 * @param string $meta_key Meta key
	 * @return array Usage information
	 */
	public function find_meta_usage( $meta_key ) {
		global $wpdb;

		$usage = array(
			'database' => array(),
			'files'    => array(),
			'samples'  => array(),
		);

		// Get database usage
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_type, pm.meta_value
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = %s
                 LIMIT 10",
				$meta_key
			)
		);

		foreach ( $posts as $post ) {
			$usage['database'][] = array(
				'post_id'    => $post->ID,
				'post_title' => $post->post_title,
				'post_type'  => $post->post_type,
				'edit_link'  => get_edit_post_link( $post->ID ),
			);

			// Get sample values
			if ( count( $usage['samples'] ) < 5 ) {
				$usage['samples'][] = $post->meta_value;
			}
		}

		// Search for usage in PHP files
		$plugin_dir = defined( 'BERP_PLUGIN_DIR' ) ? BERP_PLUGIN_DIR : '';
		if ( ! empty( $plugin_dir ) ) {
			$usage['files'] = $this->search_files_for_meta_key( $plugin_dir, $meta_key );
		}

		return $usage;
	}

	/**
	 * Search files for meta key
	 *
	 * @param string $dir Directory to search
	 * @param string $meta_key Meta key to find
	 * @return array Files containing meta key
	 */
	private function search_files_for_meta_key( $dir, $meta_key ) {
		$files = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				$content = file_get_contents( $file->getPathname() );
				if ( strpos( $content, $meta_key ) !== false ) {
					$files[] = array(
						'file' => str_replace( $dir, '', $file->getPathname() ),
						'path' => $file->getPathname(),
					);

					// Limit results
					if ( count( $files ) >= 10 ) {
						break;
					}
				}
			}
		}

		return $files;
	}
}
