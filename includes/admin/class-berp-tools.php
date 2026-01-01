<?php
/**
 * BuildERP Tools Admin Page
 *
 * Provides admin interface for demo data generation and other tools.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BERP_Tools
 *
 * Handles the Tools admin page functionality.
 *
 * @since 1.0.0
 */
class BERP_Tools {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_tools_assets' ) );
		add_action( 'wp_ajax_berp_run_validator', array( $this, 'ajax_run_validator' ) );
		add_action( 'wp_ajax_berp_quick_fix', array( $this, 'ajax_quick_fix' ) );
		add_action( 'wp_ajax_berp_export_system_info', array( $this, 'ajax_export_system_info' ) );
		add_action( 'wp_ajax_berp_search_meta_usage', array( $this, 'ajax_search_meta_usage' ) );
		add_action( 'wp_ajax_berp_generate_helper', array( $this, 'ajax_generate_helper' ) );
	}

	/**
	 * Handle form actions.
	 *
	 * @since 1.0.0
	 */
	public function handle_actions() {
		// Generate demo data action.
		if ( isset( $_POST['berp_generate_demo_data'] ) ) {
			$this->handle_generate_demo_data();
		}

		// Clear demo data action.
		if ( isset( $_POST['berp_clear_demo_data'] ) ) {
			$this->handle_clear_demo_data();
		}
	}

	/**
	 * Handle generate demo data action.
	 *
	 * @since 1.0.0
	 */
	private function handle_generate_demo_data() {
		// Verify nonce.
		if ( ! isset( $_POST['berp_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_tools_nonce'] ) ), 'berp_tools_action' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed.', 'aic_builderp' ) . '</p></div>';
				}
			);
			return;
		}

		// Verify capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'You do not have permission to perform this action.', 'aic_builderp' ) . '</p></div>';
				}
			);
			return;
		}

		// Include demo data file.
		$demo_file = BERP_PLUGIN_DIR . 'demo-data.php';
		if ( file_exists( $demo_file ) ) {
			require_once $demo_file;
		}

		if ( function_exists( 'berp_generate_demo_data' ) ) {
			$summary = berp_generate_demo_data( true );

			// Log activity.
			if ( function_exists( 'berp_log_activity' ) ) {
				berp_log_activity(
					'demo_data_generated',
					__( 'Demo data generated via admin tools.', 'aic_builderp' ),
					$summary
				);
			}

			// Store success message in transient.
			set_transient(
				'berp_tools_notice',
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: %1$d: clients count, %2$d: sites count, %3$d: employees count, %4$d: attendance count, %5$d: expenses count, %6$d: quotations count, %7$d: invoices count */
						__( 'Demo data generated successfully! Created: %1$d clients, %2$d sites, %3$d employees, %4$d attendance records, %5$d expenses, %6$d quotations, %7$d invoices.', 'aic_builderp' ),
						$summary['clients'],
						$summary['sites'],
						$summary['employees'],
						$summary['attendance'],
						$summary['expenses'],
						$summary['quotations'],
						$summary['invoices']
					),
				),
				30
			);
		} else {
			set_transient(
				'berp_tools_notice',
				array(
					'type'    => 'error',
					'message' => __( 'Failed to generate demo data. Function not found.', 'aic_builderp' ),
				),
				30
			);
		}

		// Redirect to prevent form resubmission.
		wp_safe_redirect( admin_url( 'admin.php?page=builderp-tools' ) );
		exit;
	}

	/**
	 * Handle clear demo data action.
	 *
	 * @since 1.0.0
	 */
	private function handle_clear_demo_data() {
		// Verify nonce.
		if ( ! isset( $_POST['berp_tools_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['berp_tools_nonce'] ) ), 'berp_tools_action' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed.', 'aic_builderp' ) . '</p></div>';
				}
			);
			return;
		}

		// Verify capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'You do not have permission to perform this action.', 'aic_builderp' ) . '</p></div>';
				}
			);
			return;
		}

		// Include demo data file.
		$demo_file = BERP_PLUGIN_DIR . 'demo-data.php';
		if ( file_exists( $demo_file ) ) {
			require_once $demo_file;
		}

		if ( function_exists( 'berp_clear_demo_data' ) ) {
			$summary = berp_clear_demo_data( true );

			// Log activity.
			if ( function_exists( 'berp_log_activity' ) ) {
				berp_log_activity(
					'demo_data_cleared',
					__( 'All BuildERP data cleared via admin tools.', 'aic_builderp' ),
					$summary
				);
			}

			// Calculate total deleted.
			$total = array_sum( $summary );

			set_transient(
				'berp_tools_notice',
				array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: %d: total items deleted */
						__( 'All BuildERP data cleared successfully! %d items deleted.', 'aic_builderp' ),
						$total
					),
				),
				30
			);
		} else {
			set_transient(
				'berp_tools_notice',
				array(
					'type'    => 'error',
					'message' => __( 'Failed to clear data. Function not found.', 'aic_builderp' ),
				),
				30
			);
		}

		// Redirect to prevent form resubmission.
		wp_safe_redirect( admin_url( 'admin.php?page=builderp-tools' ) );
		exit;
	}

	/**
	 * Enqueue tools assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_tools_assets( $hook ) {
		if ( 'builderp_page_builderp-tools' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'berp-tools-styles',
			plugins_url( 'assets/css/tools.css', BERP_PLUGIN_FILE ),
			array(),
			BERP_VERSION
		);

		wp_enqueue_script(
			'berp-tools-scripts',
			plugins_url( 'assets/js/tools.js', BERP_PLUGIN_FILE ),
			array( 'jquery' ),
			BERP_VERSION,
			true
		);

		wp_localize_script(
			'berp-tools-scripts',
			'berpTools',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'berp_tools_action' ),
				'strings' => array(
					'validating' => __( 'Validating schema...', 'aic_builderp' ),
					'fixing'     => __( 'Applying fixes...', 'aic_builderp' ),
					'exporting'  => __( 'Generating export...', 'aic_builderp' ),
					'searching'  => __( 'Searching...', 'aic_builderp' ),
					'generating' => __( 'Generating helper...', 'aic_builderp' ),
					'error'      => __( 'An error occurred. Please try again.', 'aic_builderp' ),
					'success'    => __( 'Operation completed successfully.', 'aic_builderp' ),
				),
			)
		);
	}

	/**
	 * Render the Tools page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		// Verify capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'aic_builderp' ) );
		}

		// Display notices from transient.
		$notice = get_transient( 'berp_tools_notice' );
		if ( $notice ) {
			delete_transient( 'berp_tools_notice' );
		}

		// Get active tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'demo-data';
		?>
		<div class="wrap berp-tools-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=builderp-tools&tab=demo-data"
					class="nav-tab <?php echo 'demo-data' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-database-add"></span>
					<?php esc_html_e( 'Demo Data', 'aic_builderp' ); ?>
				</a>
				<a href="?page=builderp-tools&tab=system-info"
					class="nav-tab <?php echo 'system-info' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'System Info', 'aic_builderp' ); ?>
				</a>
				<a href="?page=builderp-tools&tab=schema-browser"
					class="nav-tab <?php echo 'schema-browser' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Schema Browser', 'aic_builderp' ); ?>
				</a>
			</h2>

			<div class="berp-tools-content">
				<?php
				switch ( $active_tab ) {
					case 'demo-data':
						$this->render_demo_data_tab();
						break;
					case 'system-info':
						$this->render_system_info_tab();
						break;
					case 'schema-browser':
						$this->render_schema_browser_tab();
						break;
					default:
						$this->render_demo_data_tab();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Demo Data tab.
	 *
	 * @since 1.0.0
	 */
	private function render_demo_data_tab() {
		// Get current data counts.
		$post_types = array(
			'berp_client'     => __( 'Clients', 'aic_builderp' ),
			'berp_site'       => __( 'Sites', 'aic_builderp' ),
			'berp_employee'   => __( 'Employees', 'aic_builderp' ),
			'berp_attendance' => __( 'Attendance Records', 'aic_builderp' ),
			'berp_expense'    => __( 'Expenses', 'aic_builderp' ),
			'berp_quotation'  => __( 'Quotations', 'aic_builderp' ),
			'berp_invoice'    => __( 'Invoices', 'aic_builderp' ),
			'berp_payroll'    => __( 'Payroll Records', 'aic_builderp' ),
		);

		$total_records = 0;
		$data_counts   = array();
		foreach ( $post_types as $post_type => $label ) {
			$count                     = wp_count_posts( $post_type );
			$total                     = isset( $count->publish ) ? $count->publish : 0;
			$data_counts[ $post_type ] = $total;
			$total_records            += $total;
		}

		$has_data = $total_records > 0;
		?>

		<div class="berp-demo-data-wrapper">

			<!-- Header Stats -->
			<div class="berp-demo-stats-grid">
				<div class="berp-stat-card berp-stat-primary">
					<div class="berp-stat-icon">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( number_format( $total_records ) ); ?></h3>
						<p><?php esc_html_e( 'Total Records', 'aic_builderp' ); ?></p>
					</div>
				</div>

				<div class="berp-stat-card berp-stat-success">
					<div class="berp-stat-icon">
						<span class="dashicons dashicons-groups"></span>
					</div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( number_format( $data_counts['berp_employee'] ) ); ?></h3>
						<p><?php esc_html_e( 'Employees', 'aic_builderp' ); ?></p>
					</div>
				</div>

				<div class="berp-stat-card berp-stat-info">
					<div class="berp-stat-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( number_format( $data_counts['berp_attendance'] ) ); ?></h3>
						<p><?php esc_html_e( 'Attendance Records', 'aic_builderp' ); ?></p>
					</div>
				</div>

				<div class="berp-stat-card berp-stat-warning">
					<div class="berp-stat-icon">
						<span class="dashicons dashicons-building"></span>
					</div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( number_format( $data_counts['berp_site'] ) ); ?></h3>
						<p><?php esc_html_e( 'Active Sites', 'aic_builderp' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Main Content -->
			<div class="berp-demo-main-content">

				<!-- Demo Data Generator -->
				<div class="berp-demo-card berp-generator-card">
					<div class="berp-card-header">
						<h2><?php esc_html_e( 'Generate Demo Data', 'aic_builderp' ); ?></h2>
						<p><?php esc_html_e( 'Populate your BuildErp plugin with realistic sample data for testing and demonstration', 'aic_builderp' ); ?></p>
					</div>

					<div class="berp-card-body">
						<div class="berp-data-preview">
							<h4><?php esc_html_e( 'What will be created:', 'aic_builderp' ); ?></h4>
							<div class="berp-preview-grid">
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-businessperson" style="color: #2271b1;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>5</strong>
										<span><?php esc_html_e( 'Clients', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-building" style="color: #dba617;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>5</strong>
										<span><?php esc_html_e( 'Sites/Projects', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-groups" style="color: #00a32a;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>10</strong>
										<span><?php esc_html_e( 'Employees', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-calendar-alt" style="color: #8c191f;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>~150</strong>
										<span><?php esc_html_e( 'Attendance Records', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-money-alt" style="color: #d63638;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>10</strong>
										<span><?php esc_html_e( 'Expenses', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-text-page" style="color: #2271b1;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>3</strong>
										<span><?php esc_html_e( 'Quotations', 'aic_builderp' ); ?></span>
									</div>
								</div>
								<div class="berp-preview-item">
									<span class="berp-preview-icon">
										<span class="dashicons dashicons-media-spreadsheet" style="color: #dba617;"></span>
									</span>
									<div class="berp-preview-text">
										<strong>3</strong>
										<span><?php esc_html_e( 'Invoices', 'aic_builderp' ); ?></span>
									</div>
								</div>
							</div>
						</div>

						<form method="post" action="" class="berp-demo-form">
							<?php wp_nonce_field( 'berp_tools_action', 'berp_tools_nonce' ); ?>
							<button type="submit" name="berp_generate_demo_data" class="button button-primary button-large">
								<span class="dashicons dashicons-database-add" style="vertical-align: middle; margin-right: 5px;"></span>
								<?php esc_html_e( 'Generate Demo Data', 'aic_builderp' ); ?>
							</button>
							<p class="berp-form-note">
								<span class="dashicons dashicons-info"></span>
								<?php esc_html_e( 'This process may take a few moments. Please do not close this page.', 'aic_builderp' ); ?>
							</p>
						</form>
					</div>
				</div>

				<!-- Data Overview -->
				<div class="berp-demo-card berp-overview-card">
					<div class="berp-card-header">
						<h2><?php esc_html_e( 'Current Data Overview', 'aic_builderp' ); ?></h2>
						<p><?php esc_html_e( 'Your current BuildErp database statistics', 'aic_builderp' ); ?></p>
					</div>

					<div class="berp-card-body">
						<?php if ( $has_data ) : ?>
							<div class="berp-data-table">
								<?php foreach ( $post_types as $post_type => $label ) : ?>
									<div class="berp-data-row">
										<span class="berp-data-label"><?php echo esc_html( $label ); ?></span>
										<span class="berp-data-value">
											<?php echo esc_html( number_format( $data_counts[ $post_type ] ) ); ?>
											<?php if ( $data_counts[ $post_type ] > 0 ) : ?>
												<span class="berp-data-indicator berp-has-data"></span>
											<?php endif; ?>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="berp-empty-state">
								<span class="dashicons dashicons-database"></span>
								<h3><?php esc_html_e( 'No Data Found', 'aic_builderp' ); ?></h3>
								<p><?php esc_html_e( 'Your BuildErp database is empty. Generate demo data to get started!', 'aic_builderp' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>

			</div>

			<!-- Danger Zone -->
			<?php if ( $has_data ) : ?>
				<div class="berp-demo-card berp-danger-card">
					<div class="berp-card-header">
						<h2><?php esc_html_e( 'Danger Zone', 'aic_builderp' ); ?></h2>
						<p><?php esc_html_e( 'Permanently delete all BuildErp data from your database', 'aic_builderp' ); ?></p>
					</div>

					<div class="berp-card-body">
						<div class="berp-danger-warning">
							<h4><?php esc_html_e( 'Warning: This action is irreversible!', 'aic_builderp' ); ?></h4>
							<ul>
								<li><?php esc_html_e( 'All clients, sites, and employees will be deleted', 'aic_builderp' ); ?></li>
								<li><?php esc_html_e( 'All attendance, payroll, and expense records will be removed', 'aic_builderp' ); ?></li>
								<li><?php esc_html_e( 'All quotations and invoices will be permanently erased', 'aic_builderp' ); ?></li>
								<li><?php esc_html_e( 'This action cannot be undone - please backup your data first', 'aic_builderp' ); ?></li>
							</ul>
						</div>

						<form method="post" action="" class="berp-demo-form" onsubmit="return confirm('<?php echo esc_js( __( 'Are you ABSOLUTELY SURE you want to delete ALL BuildERP data?\n\nThis will permanently delete:\n• All employees and attendance records\n• All clients and sites\n• All invoices and quotations\n• All expenses and payroll records\n\nThis action CANNOT be undone!', 'aic_builderp' ) ); ?>');">
							<?php wp_nonce_field( 'berp_tools_action', 'berp_tools_nonce' ); ?>
							<button type="submit" name="berp_clear_demo_data" class="button button-secondary">
								<span class="dashicons dashicons-trash" style="vertical-align: middle; margin-right: 5px;"></span>
								<?php esc_html_e( 'Clear All Data', 'aic_builderp' ); ?>
							</button>
							<p class="berp-form-note berp-danger-note">
								<span class="dashicons dashicons-warning"></span>
								<?php echo esc_html( sprintf( __( 'This will delete %s records permanently', 'aic_builderp' ), number_format( $total_records ) ) ); ?>
							</p>
						</form>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<style>
			/* Demo Data Tab Styles - Matching BuildErp Admin Style */
			.berp-demo-data-wrapper {
				margin-top: 20px;
			}

			/* Stats Grid */
			.berp-demo-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 20px;
				margin-bottom: 30px;
			}

			.berp-demo-stats-grid .berp-stat-card {
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 20px;
				border-radius: 4px;
				box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
				display: flex;
				align-items: center;
				gap: 15px;
			}

			.berp-demo-stats-grid .berp-stat-card:hover {
				border-color: #8c8f94;
			}

			.berp-stat-icon {
				width: 48px;
				height: 48px;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-shrink: 0;
			}

			.berp-stat-icon .dashicons {
				font-size: 32px;
				width: 32px;
				height: 32px;
			}

			.berp-stat-primary .berp-stat-icon { color: #2271b1; }
			.berp-stat-success .berp-stat-icon { color: #00a32a; }
			.berp-stat-info .berp-stat-icon { color: #8c191f; }
			.berp-stat-warning .berp-stat-icon { color: #dba617; }

			.berp-stat-content h3 {
				margin: 0;
				font-size: 32px;
				font-weight: 600;
				color: #1d2327;
				line-height: 1;
			}

			.berp-stat-content p {
				margin: 5px 0 0;
				font-size: 14px;
				color: #646970;
				font-weight: 400;
			}

			/* Main Content */
			.berp-demo-main-content {
				display: grid;
				grid-template-columns: 2fr 1fr;
				gap: 20px;
				margin-bottom: 30px;
			}

			/* Cards */
			.berp-demo-card {
				background: #fff;
				border: 1px solid #c3c4c7;
				box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
			}

			.berp-card-header {
				padding: 20px;
				border-bottom: 1px solid #f0f0f1;
			}

			.berp-card-header h2 {
				margin: 0 0 5px;
				font-size: 16px;
				font-weight: 600;
				color: #1d2327;
			}

			.berp-card-header p {
				margin: 0;
				font-size: 13px;
				color: #646970;
			}

			.berp-card-body {
				padding: 20px;
			}

			/* Data Preview */
			.berp-data-preview h4 {
				margin: 0 0 15px;
				font-size: 13px;
				color: #646970;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.berp-preview-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
				gap: 10px;
				margin-bottom: 20px;
			}

			.berp-preview-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				text-align: center;
				padding: 15px 10px;
				border: 1px solid #dcdcde;
				background: #f6f7f7;
			}

			.berp-preview-item:hover {
				border-color: #8c8f94;
				background: #fff;
			}

			.berp-preview-icon {
				width: 36px;
				height: 36px;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-bottom: 8px;
			}

			.berp-preview-icon .dashicons {
				font-size: 36px;
				width: 36px;
				height: 36px;
			}

			.berp-preview-text {
				display: flex;
				flex-direction: column;
				gap: 3px;
			}

			.berp-preview-text strong {
				font-size: 20px;
				font-weight: 600;
				color: #1d2327;
				line-height: 1;
			}

			.berp-preview-text span {
				font-size: 12px;
				color: #646970;
			}

			/* Form and Buttons */
			.berp-demo-form {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #f0f0f1;
			}

			.berp-form-note {
				margin: 10px 0 0;
				font-size: 12px;
				color: #646970;
				font-style: italic;
			}

			.berp-form-note .dashicons {
				font-size: 14px;
				width: 14px;
				height: 14px;
				vertical-align: middle;
				margin-right: 4px;
			}

			.berp-danger-note {
				color: #d63638;
				font-weight: 600;
				font-style: normal;
			}

			/* Data Table */
			.berp-data-table {
				display: flex;
				flex-direction: column;
				gap: 1px;
			}

			.berp-data-row {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 10px 15px;
				background: #f6f7f7;
				border: 1px solid #dcdcde;
			}

			.berp-data-row + .berp-data-row {
				border-top: none;
			}

			.berp-data-label {
				font-size: 13px;
				color: #1d2327;
			}

			.berp-data-value {
				display: flex;
				align-items: center;
				gap: 8px;
				font-size: 14px;
				font-weight: 600;
				color: #1d2327;
			}

			.berp-data-indicator {
				width: 8px;
				height: 8px;
				border-radius: 50%;
			}

			.berp-has-data {
				background: #00a32a;
			}

			/* Empty State */
			.berp-empty-state {
				text-align: center;
				padding: 40px 20px;
			}

			.berp-empty-state .dashicons {
				font-size: 64px;
				width: 64px;
				height: 64px;
				color: #c3c4c7;
				margin-bottom: 15px;
			}

			.berp-empty-state h3 {
				margin: 0 0 8px;
				font-size: 16px;
				color: #1d2327;
			}

			.berp-empty-state p {
				margin: 0;
				font-size: 13px;
				color: #646970;
			}

			/* Danger Card */
			.berp-danger-card {
				border-color: #d63638;
			}

			.berp-danger-card .berp-card-header {
				background: #fcf0f1;
				border-bottom-color: #d63638;
			}

			.berp-danger-card .berp-card-header h2 {
				color: #d63638;
			}

			.berp-danger-warning {
				padding: 15px;
				background: #fcf0f1;
				border: 1px solid #f0b9bb;
				margin-bottom: 20px;
			}

			.berp-danger-warning h4 {
				margin: 0 0 10px;
				font-size: 14px;
				color: #d63638;
				font-weight: 600;
			}

			.berp-danger-warning ul {
				margin: 0;
				padding-left: 20px;
			}

			.berp-danger-warning li {
				font-size: 13px;
				color: #50575e;
				margin-bottom: 5px;
			}

			/* Responsive */
			@media (max-width: 1200px) {
				.berp-demo-main-content {
					grid-template-columns: 1fr;
				}
			}

			@media (max-width: 782px) {
				.berp-demo-stats-grid {
					grid-template-columns: 1fr;
				}

				.berp-preview-grid {
					grid-template-columns: repeat(2, 1fr);
				}
			}
		</style>
		<?php
	}

	/**
	 * Render System Info tab.
	 *
	 * @since 1.0.0
	 */
	private function render_system_info_tab() {
		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-system-info.php';
		$system_info = new BERP_Tools_System_Info();
		$system_info->render();
	}

	/**
	 * Render Schema Browser tab.
	 *
	 * @since 1.0.0
	 */
	private function render_schema_browser_tab() {
		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-schema-browser.php';
		$schema_browser = new BERP_Tools_Schema_Browser();
		$schema_browser->render();
	}

	/**
	 * AJAX handler for running validator.
	 *
	 * @since 1.0.0
	 */
	public function ajax_run_validator() {
		check_ajax_referer( 'berp_tools_action', 'nonce' );

		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'aic_builderp' ) ) );
		}

		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-validator.php';
		$validator = new BERP_Tools_Validator();
		$results   = $validator->run_validation();

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for quick fix.
	 *
	 * @since 1.0.0
	 */
	public function ajax_quick_fix() {
		check_ajax_referer( 'berp_tools_action', 'nonce' );

		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'aic_builderp' ) ) );
		}

		$issue_type = isset( $_POST['issue_type'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_type'] ) ) : '';
		$issue_data = isset( $_POST['issue_data'] ) ? map_deep( wp_unslash( $_POST['issue_data'] ), 'sanitize_text_field' ) : array();

		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-validator.php';
		$validator = new BERP_Tools_Validator();
		$result    = $validator->apply_quick_fix( $issue_type, $issue_data );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Fix applied successfully', 'aic_builderp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to apply fix', 'aic_builderp' ) ) );
		}
	}

	/**
	 * AJAX handler for exporting system info.
	 *
	 * @since 1.0.0
	 */
	public function ajax_export_system_info() {
		check_ajax_referer( 'berp_tools_action', 'nonce' );

		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'aic_builderp' ) ) );
		}

		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-system-info.php';
		$system_info = new BERP_Tools_System_Info();
		$report      = $system_info->generate_report();

		wp_send_json_success( array( 'report' => $report ) );
	}

	/**
	 * AJAX handler for searching meta usage.
	 *
	 * @since 1.0.0
	 */
	public function ajax_search_meta_usage() {
		check_ajax_referer( 'berp_tools_action', 'nonce' );

		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'aic_builderp' ) ) );
		}

		$meta_key = isset( $_POST['meta_key'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_key'] ) ) : '';

		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-schema-browser.php';
		$schema_browser = new BERP_Tools_Schema_Browser();
		$usage          = $schema_browser->find_meta_usage( $meta_key );

		wp_send_json_success( array( 'usage' => $usage ) );
	}

	/**
	 * AJAX handler for generating helper function.
	 *
	 * @since 1.0.0
	 */
	public function ajax_generate_helper() {
		check_ajax_referer( 'berp_tools_action', 'nonce' );

		if ( ! current_user_can( 'berp_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'aic_builderp' ) ) );
		}

		$meta_key  = isset( $_POST['meta_key'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_key'] ) ) : '';
		$data_type = isset( $_POST['data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['data_type'] ) ) : '';
		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';

		require_once plugin_dir_path( BERP_PLUGIN_FILE ) . 'includes/admin/tools-helper-generator.php';
		$helper_generator = new BERP_Tools_Helper_Generator();
		$code             = $helper_generator->generate_helper( $meta_key, $data_type, $post_type );

		wp_send_json_success( array( 'code' => $code ) );
	}
}
