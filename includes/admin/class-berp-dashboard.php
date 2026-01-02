<?php
/**
 * Dashboard Admin Class
 *
 * Handles the dashboard page and widgets.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Dashboard Class
 */
class BERP_Dashboard {

	/**
	 * Cache key prefix for dashboard transients.
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'berp_dashboard_';

	/**
	 * Cache duration in seconds (5 minutes by default).
	 *
	 * @var int
	 */
	const CACHE_DURATION = 300;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

		// Register cache clearing hooks for relevant post types.
		add_action( 'save_post_berp_employee', array( __CLASS__, 'clear_cache' ) );
		add_action( 'save_post_berp_site', array( __CLASS__, 'clear_cache' ) );
		add_action( 'save_post_berp_attendance', array( __CLASS__, 'clear_cache' ) );
		add_action( 'save_post_berp_expense', array( __CLASS__, 'clear_cache' ) );
		add_action( 'save_post_berp_invoice', array( __CLASS__, 'clear_cache' ) );
		add_action( 'save_post_berp_payroll', array( __CLASS__, 'clear_cache' ) );
		add_action( 'delete_post', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on dashboard page.
		if ( 'toplevel_page_builderp' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'chart-js',
			plugins_url( 'assets/vendor/chart.js/chart.umd.min.js', BERP_PLUGIN_FILE ),
			array(),
			'4.4.0',
			true
		);

		wp_enqueue_style(
			'berp-dashboard-css',
			plugins_url( 'assets/css/dashboard.css', BERP_PLUGIN_FILE ),
			array(),
			BERP_VERSION
		);

		wp_enqueue_script(
			'berp-dashboard-js',
			plugins_url( 'assets/js/dashboard.js', BERP_PLUGIN_FILE ),
			array( 'jquery', 'chart-js' ),
			BERP_VERSION,
			true
		);

		wp_localize_script(
			'berp-dashboard-js',
			'berpDashboard',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'berp_dashboard_nonce' ),
				'data'    => $this->get_chart_data(),
			)
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_page() {
		$stats    = $this->get_quick_stats();
		$activity = $this->get_recent_activity();
		$alerts   = $this->get_alerts();

		?>
		<div class="wrap berp-dashboard-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'BuildErp Dashboard', 'builderp' ); ?></h1>
			<hr class="wp-header-end">

			<!-- Quick Stats Cards -->
			<div class="berp-stats-grid">
				<div class="berp-stat-card">
					<div class="berp-stat-icon employees"><span class="dashicons dashicons-groups"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( $stats['employees'] ); ?></h3>
						<p><?php esc_html_e( 'Active Employees', 'builderp' ); ?></p>
					</div>
				</div>
				<div class="berp-stat-card">
					<div class="berp-stat-icon sites"><span class="dashicons dashicons-building"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( $stats['sites'] ); ?></h3>
						<p><?php esc_html_e( 'Active Sites', 'builderp' ); ?></p>
					</div>
				</div>
				<div class="berp-stat-card">
					<div class="berp-stat-icon attendance"><span class="dashicons dashicons-calendar-alt"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( $stats['attendance'] ); ?>%</h3>
						<p><?php esc_html_e( 'Attendance Today', 'builderp' ); ?></p>
					</div>
				</div>
				<div class="berp-stat-card">
					<div class="berp-stat-icon revenue"><span class="dashicons dashicons-money-alt"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( berp_format_currency( $stats['revenue'] ) ); ?></h3>
						<p><?php esc_html_e( 'Revenue (This Month)', 'builderp' ); ?></p>
					</div>
				</div>
				<div class="berp-stat-card">
					<div class="berp-stat-icon expenses"><span class="dashicons dashicons-cart"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( berp_format_currency( $stats['expenses'] ) ); ?></h3>
						<p><?php esc_html_e( 'Expenses (This Month)', 'builderp' ); ?></p>
					</div>
				</div>
				<div class="berp-stat-card">
					<div class="berp-stat-icon pending"><span class="dashicons dashicons-clock"></span></div>
					<div class="berp-stat-content">
						<h3><?php echo esc_html( berp_format_currency( $stats['pending_invoices'] ) ); ?></h3>
						<p><?php esc_html_e( 'Pending Invoices', 'builderp' ); ?></p>
					</div>
				</div>
			</div>

			<div class="berp-dashboard-columns">
				<!-- Main Column -->
				<div class="berp-dashboard-main">
					<!-- Charts -->
					<div class="berp-dashboard-widget">
						<h2 class="berp-widget-title"><?php esc_html_e( 'Revenue vs Expenses (Last 6 Months)', 'builderp' ); ?></h2>
						<div class="berp-chart-container">
							<canvas id="berp-revenue-chart"></canvas>
						</div>
					</div>

					<div class="berp-dashboard-widget">
						<h2 class="berp-widget-title"><?php esc_html_e( 'Site Profitability', 'builderp' ); ?></h2>
						<div class="berp-chart-container">
							<canvas id="berp-profitability-chart"></canvas>
						</div>
					</div>
				</div>

				<!-- Sidebar Column -->
				<div class="berp-dashboard-sidebar">
					<!-- Alerts -->
					<?php if ( ! empty( $alerts ) ) : ?>
					<div class="berp-dashboard-widget alerts-widget">
						<h2 class="berp-widget-title"><?php esc_html_e( 'Alerts & Notifications', 'builderp' ); ?></h2>
						<ul class="berp-alerts-list">
							<?php foreach ( $alerts as $alert ) : ?>
								<li class="alert-<?php echo esc_attr( $alert['type'] ); ?>">
									<span class="dashicons <?php echo esc_attr( $alert['icon'] ); ?>"></span>
									<?php echo esc_html( $alert['message'] ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>

					<!-- Recent Activity -->
					<div class="berp-dashboard-widget">
						<h2 class="berp-widget-title"><?php esc_html_e( 'Recent Activity', 'builderp' ); ?></h2>
						<ul class="berp-activity-list">
							<?php if ( empty( $activity ) ) : ?>
								<li><?php esc_html_e( 'No recent activity.', 'builderp' ); ?></li>
							<?php else : ?>
								<?php foreach ( $activity as $item ) : ?>
									<li>
										<span class="activity-time"><?php echo esc_html( human_time_diff( strtotime( $item['date'] ) ) . ' ' . __( 'ago', 'builderp' ) ); ?></span>
										<span class="activity-text"><?php echo wp_kses_post( $item['text'] ); ?></span>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add dashboard widgets to WordPress dashboard.
	 */
	public function add_dashboard_widgets() {
		if ( current_user_can( 'berp_view_dashboard' ) ) {
			wp_add_dashboard_widget(
				'berp_dashboard_widget',
				__( 'BuildErp Overview', 'builderp' ),
				array( $this, 'render_dashboard_widget' )
			);
		}
	}

	/**
	 * Render WordPress dashboard widget.
	 */
	public function render_dashboard_widget() {
		$stats = $this->get_quick_stats();
		?>
		<div class="berp-wp-widget">
			<div class="berp-wp-widget-row">
				<div class="berp-wp-widget-item">
					<span class="dashicons dashicons-groups"></span>
					<strong><?php echo esc_html( $stats['employees'] ); ?></strong>
					<span><?php esc_html_e( 'Employees', 'builderp' ); ?></span>
				</div>
				<div class="berp-wp-widget-item">
					<span class="dashicons dashicons-building"></span>
					<strong><?php echo esc_html( $stats['sites'] ); ?></strong>
					<span><?php esc_html_e( 'Sites', 'builderp' ); ?></span>
				</div>
				<div class="berp-wp-widget-item">
					<span class="dashicons dashicons-clock"></span>
					<strong><?php echo esc_html( berp_format_currency( $stats['pending_invoices'] ) ); ?></strong>
					<span><?php esc_html_e( 'Pending', 'builderp' ); ?></span>
				</div>
			</div>
			<div class="berp-wp-widget-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=builderp' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Dashboard', 'builderp' ); ?></a>
			</div>
		</div>
		<style>
			.berp-wp-widget-row { display: flex; justify-content: space-between; margin-bottom: 15px; text-align: center; }
			.berp-wp-widget-item { display: flex; flex-direction: column; align-items: center; }
			.berp-wp-widget-item .dashicons { font-size: 24px; height: 24px; width: 24px; margin-bottom: 5px; color: #0073aa; }
			.berp-wp-widget-item strong { font-size: 18px; display: block; }
			.berp-wp-widget-item span { font-size: 12px; color: #666; }
			.berp-wp-widget-footer { text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
		</style>
		<?php
	}

	/**
	 * Get quick stats with caching.
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array
	 */
	private function get_quick_stats( $force_refresh = false ) {
		$cache_key = self::CACHE_PREFIX . 'quick_stats';

		// Check settings for caching enabled.
		$settings        = get_option( 'berp_settings', array() );
		$caching_enabled = isset( $settings['advanced']['report_caching'] ) ? (bool) $settings['advanced']['report_caching'] : true;

		// Try to get cached data.
		if ( $caching_enabled && ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$employees = wp_count_posts( 'berp_employee' );
		$sites     = wp_count_posts( 'berp_site' );

		// Calculate attendance percentage for today.
		$today            = current_time( 'Y-m-d' );
		$attendance_query = new WP_Query(
			array(
				'post_type'      => 'berp_attendance',
				'post_status'    => 'publish',
				'meta_key'       => '_berp_date',
				'meta_value'     => $today,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
		$present_count    = count( $attendance_query->posts );
		$total_employees  = isset( $employees->publish ) ? (int) $employees->publish : 0;
		$attendance_rate  = $total_employees > 0 ? round( ( $present_count / $total_employees ) * 100 ) : 0;

		// Calculate revenue this month.
		$start_date = current_time( 'Y-m-01' );
		$end_date   = current_time( 'Y-m-t' );
		$revenue    = $this->get_total_revenue( $start_date, $end_date );

		// Calculate expenses this month.
		$expenses = $this->get_total_expenses( $start_date, $end_date );

		// Calculate pending invoices.
		$pending = $this->get_pending_invoices_amount();

		$stats = array(
			'employees'        => $total_employees,
			'sites'            => isset( $sites->publish ) ? (int) $sites->publish : 0,
			'attendance'       => $attendance_rate,
			'revenue'          => $revenue,
			'expenses'         => $expenses,
			'pending_invoices' => $pending,
			'generated_at'     => current_time( 'mysql' ),
		);

		// Cache the results.
		if ( $caching_enabled ) {
			set_transient( $cache_key, $stats, self::CACHE_DURATION );
		}

		return $stats;
	}

	/**
	 * Clear dashboard cache.
	 *
	 * Call this when data changes that affects dashboard stats.
	 *
	 * @since 1.0.0
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_PREFIX . 'quick_stats' );
		delete_transient( self::CACHE_PREFIX . 'chart_data' );
		delete_transient( self::CACHE_PREFIX . 'alerts' );
		delete_transient( self::CACHE_PREFIX . 'activity' );
	}

	/**
	 * Get total revenue for period.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return float
	 */
	private function get_total_revenue( $start_date, $end_date ) {
		global $wpdb;

		// Sum of paid invoices in period.
		// Simplified: Sum of invoices created in this month that have payments recorded.
		// A better approach would be to sum payments by payment date.
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_berp_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$invoices = get_posts( $args );
		$total    = 0;
		foreach ( $invoices as $invoice ) {
			$paid   = get_post_meta( $invoice->ID, '_berp_amount_paid', true );
			$total += floatval( $paid );
		}

		return $total;
	}

	/**
	 * Get total expenses for period.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return float
	 */
	private function get_total_expenses( $start_date, $end_date ) {
		global $wpdb;

		$args = array(
			'post_type'      => 'berp_expense',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_berp_date',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);

		$expenses = get_posts( $args );
		$total    = 0;
		foreach ( $expenses as $expense ) {
			$amount = get_post_meta( $expense->ID, '_berp_amount', true );
			$total += floatval( $amount );
		}

		return $total;
	}

	/**
	 * Get pending invoices amount.
	 *
	 * @return float
	 */
	private function get_pending_invoices_amount() {
		$args = array(
			'post_type'      => 'berp_invoice',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_berp_status',
					'value'   => array( 'sent', 'partial', 'overdue' ),
					'compare' => 'IN',
				),
			),
		);

		$invoices = get_posts( $args );
		$total    = 0;
		foreach ( $invoices as $invoice ) {
			$due    = get_post_meta( $invoice->ID, '_berp_amount_due', true );
			$total += floatval( $due );
		}

		return $total;
	}

	/**
	 * Get recent activity.
	 *
	 * @return array
	 */
	private function get_recent_activity() {
		// Combine recent invoices, expenses, and attendance.
		$activity = array();

		// Recent Invoices.
		$invoices = get_posts(
			array(
				'post_type'      => 'berp_invoice',
				'posts_per_page' => 3,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $invoices as $post ) {
			$client_id   = get_post_meta( $post->ID, '_berp_client_id', true );
			$client_name = get_the_title( $client_id );
			$amount      = get_post_meta( $post->ID, '_berp_grand_total', true );

			$activity[] = array(
				'date' => $post->post_date,
				/* translators: %1$s is invoice number, %2$s is client name, %3$s is formatted amount. */
				'text' => sprintf(
					__( 'Invoice <strong>%1$s</strong> created for %2$s (%3$s)', 'builderp' ),
					get_post_meta( $post->ID, '_berp_invoice_number', true ),
					$client_name,
					berp_format_currency( $amount )
				),
			);
		}

		// Recent Expenses.
		$expenses = get_posts(
			array(
				'post_type'      => 'berp_expense',
				'posts_per_page' => 3,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $expenses as $post ) {
			$amount     = get_post_meta( $post->ID, '_berp_amount', true );
			$activity[] = array(
				'date' => $post->post_date,
				/* translators: %1$s is expense amount, %2$s is expense title. */
				'text' => sprintf(
					__( 'Expense <strong>%1$s</strong> recorded: %2$s', 'builderp' ),
					berp_format_currency( $amount ),
					$post->post_title
				),
			);
		}

		// Sort by date descending.
		usort(
			$activity,
			function ( $a, $b ) {
				return strtotime( $b['date'] ) - strtotime( $a['date'] );
			}
		);

		return array_slice( $activity, 0, 5 );
	}

	/**
	 * Get alerts.
	 *
	 * @return array
	 */
	private function get_alerts() {
		$alerts = array();

		// Check overdue invoices.
		$overdue_count = 0;
		$invoices      = get_posts(
			array(
				'post_type'      => 'berp_invoice',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_berp_status',
						'value' => 'overdue',
					),
				),
			)
		);

		if ( count( $invoices ) > 0 ) {
			$alerts[] = array(
				'type'    => 'danger',
				'icon'    => 'dashicons-warning',
				// translators: %d is number of overdue invoices.
				'message' => sprintf( __( '%d Overdue Invoices', 'builderp' ), count( $invoices ) ),
			);
		}

		// Check budget alerts.
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_berp_budget_alert',
						'value' => 'exceeded',
					),
				),
			)
		);

		if ( count( $sites ) > 0 ) {
			$alerts[] = array(
				'type'    => 'danger',
				'icon'    => 'dashicons-money',
				// translators: %d is number of sites that exceeded budget.
				'message' => sprintf( __( '%d Sites Exceeded Budget', 'builderp' ), count( $sites ) ),
			);
		}

		return $alerts;
	}

	/**
	 * Get chart data for JS with caching.
	 *
	 * @param bool $force_refresh Force refresh cache.
	 * @return array
	 */
	private function get_chart_data( $force_refresh = false ) {
		$cache_key = self::CACHE_PREFIX . 'chart_data';

		// Check settings for caching enabled.
		$settings        = get_option( 'berp_settings', array() );
		$caching_enabled = isset( $settings['advanced']['report_caching'] ) ? (bool) $settings['advanced']['report_caching'] : true;

		// Try to get cached data.
		if ( $caching_enabled && ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Revenue vs Expenses (Last 6 Months).
		$months       = array();
		$revenue_data = array();
		$expense_data = array();

		for ( $i = 5; $i >= 0; $i-- ) {
			$month_start = gmdate( 'Y-m-01', strtotime( "-$i months" ) );
			$month_end   = gmdate( 'Y-m-t', strtotime( "-$i months" ) );
			$month_label = gmdate( 'M', strtotime( "-$i months" ) );

			$months[]       = $month_label;
			$revenue_data[] = $this->get_total_revenue( $month_start, $month_end );
			$expense_data[] = $this->get_total_expenses( $month_start, $month_end );
		}

		// Site Profitability (Top 5).
		$sites = get_posts(
			array(
				'post_type'      => 'berp_site',
				'posts_per_page' => 5,
				'meta_key'       => '_berp_budget_used_percentage',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);

		$site_labels   = array();
		$site_budgets  = array();
		$site_expenses = array();

		foreach ( $sites as $site ) {
			$site_labels[]   = $site->post_title;
			$site_budgets[]  = floatval( get_post_meta( $site->ID, '_berp_budget', true ) );
			$site_expenses[] = floatval( get_post_meta( $site->ID, '_berp_budget_spent', true ) );
		}

		$data = array(
			'revenue'       => array(
				'labels'   => $months,
				'datasets' => array(
					array(
						'label'           => __( 'Revenue', 'builderp' ),
						'data'            => $revenue_data,
						'borderColor'     => '#2271b1',
						'backgroundColor' => 'rgba(34, 113, 177, 0.1)',
						'fill'            => true,
					),
					array(
						'label'           => __( 'Expenses', 'builderp' ),
						'data'            => $expense_data,
						'borderColor'     => '#d63638',
						'backgroundColor' => 'rgba(214, 54, 56, 0.1)',
						'fill'            => true,
					),
				),
			),
			'profitability' => array(
				'labels'   => $site_labels,
				'datasets' => array(
					array(
						'label'           => __( 'Budget', 'builderp' ),
						'data'            => $site_budgets,
						'backgroundColor' => '#2271b1',
					),
					array(
						'label'           => __( 'Spent', 'builderp' ),
						'data'            => $site_expenses,
						'backgroundColor' => '#d63638',
					),
				),
			),
			'generated_at'  => current_time( 'mysql' ),
		);

		// Cache for 15 minutes (chart data changes less frequently).
		if ( $caching_enabled ) {
			set_transient( $cache_key, $data, 900 );
		}

		return $data;
	}
}

