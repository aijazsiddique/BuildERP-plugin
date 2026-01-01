<?php
/**
 * Main Portal Controller.
 *
 * Handles portal initialization, shortcodes, and routing.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Portal Class.
 */
class BERP_Portal {

	/**
	 * Portal settings cache.
	 *
	 * @var array
	 */
	private $portal_settings = null;

	/**
	 * Initialize the portal.
	 */
	public function init() {
		// Register shortcodes (for backward compatibility).
		add_shortcode( 'berp_employee_login', array( $this, 'render_login_shortcode' ) );
		add_shortcode( 'berp_employee_dashboard', array( $this, 'render_dashboard_shortcode' ) );

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Handle redirects.
		add_action( 'template_redirect', array( $this, 'handle_redirects' ) );

		// Hook into template_include to use standalone template.
		add_filter( 'template_include', array( $this, 'maybe_use_standalone_template' ), 99 );

		// Handle login form submission.
		add_action( 'init', array( $this, 'handle_login_submission' ) );

		// AJAX handlers for portal attendance.
		add_action( 'wp_ajax_berp_portal_bulk_attendance', array( $this, 'ajax_bulk_attendance' ) );
		add_action( 'wp_ajax_berp_portal_check_attendance_status', array( $this, 'ajax_check_attendance_status' ) );
	}

	/**
	 * Get portal settings.
	 *
	 * @return array
	 */
	private function get_portal_settings() {
		// Always fetch fresh to avoid cache issues.
		$settings = get_option( 'berp_settings', array() );
		return isset( $settings['portal'] ) ? $settings['portal'] : array();
	}

	/**
	 * Check if standalone template should be used.
	 *
	 * @param string $template Current template path.
	 * @return string Template path.
	 */
	public function maybe_use_standalone_template( $template ) {
		global $post;

		// Multiple ways to check if we're on the right page.
		$current_page_id = 0;

		// Try queried object first.
		$queried_object = get_queried_object();
		if ( $queried_object && isset( $queried_object->ID ) && $queried_object instanceof WP_Post ) {
			$current_page_id = $queried_object->ID;
		}

		// Fallback to global $post.
		if ( ! $current_page_id && $post && isset( $post->ID ) ) {
			$current_page_id = $post->ID;
		}

		// Fallback to get_the_ID().
		if ( ! $current_page_id ) {
			$current_page_id = get_the_ID();
		}

		// If we still don't have a page ID, return original template.
		if ( ! $current_page_id ) {
			return $template;
		}

		// Get settings.
		$settings          = $this->get_portal_settings();
		$use_standalone    = isset( $settings['use_standalone_template'] ) ? (bool) $settings['use_standalone_template'] : true;
		$login_page_id     = isset( $settings['login_page_id'] ) ? absint( $settings['login_page_id'] ) : 0;
		$dashboard_page_id = isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;

		// If standalone is disabled, return original.
		if ( ! $use_standalone ) {
			return $template;
		}

		// Check if current page is a portal page.
		$is_portal_page = ( $login_page_id && $current_page_id === $login_page_id ) ||
							( $dashboard_page_id && $current_page_id === $dashboard_page_id );

		// Fallback: check for shortcodes in page content.
		if ( ! $is_portal_page ) {
			$page_post = get_post( $current_page_id );
			if ( $page_post && 'page' === $page_post->post_type ) {
				if ( has_shortcode( $page_post->post_content, 'berp_employee_login' ) ||
					has_shortcode( $page_post->post_content, 'berp_employee_dashboard' ) ) {
					$is_portal_page = true;
				}
			}
		}

		if ( $is_portal_page ) {
			$standalone_template = BERP_PLUGIN_DIR . 'templates/portal/standalone-template.php';
			if ( file_exists( $standalone_template ) ) {
				return $standalone_template;
			}
		}

		return $template;
	}

	/**
	 * Enqueue portal assets.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_portal_page() ) {
			return;
		}

		$user_id       = get_current_user_id();
		$last_site     = $user_id ? (int) get_user_meta( $user_id, '_berp_last_site', true ) : 0;
		$site_selected = $user_id ? $this->get_user_site_employee_selections( $user_id ) : array();

		wp_enqueue_style(
			'berp-portal-styles',
			plugins_url( 'assets/css/portal.css', BERP_PLUGIN_FILE ),
			array(),
			BERP_VERSION
		);

		wp_enqueue_script(
			'berp-portal-scripts',
			plugins_url( 'assets/js/portal.js', BERP_PLUGIN_FILE ),
			array( 'jquery' ),
			BERP_VERSION,
			true
		);

		wp_localize_script(
			'berp-portal-scripts',
			'berpPortal',
			array(
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'berp_portal_nonce' ),
				'lastSite'       => $last_site,
				'siteSelections' => $site_selected,
			)
		);
	}

	/**
	 * Get the last-selected employees per site for a user (used by portal UI).
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_user_site_employee_selections( $user_id ) {
		$raw        = get_user_meta( $user_id, '_berp_attendance_site_employee_selections', true );
		$selections = array();

		if ( ! is_array( $raw ) ) {
			return $selections;
		}

		foreach ( $raw as $site_id => $employee_ids ) {
			$site_id = absint( $site_id );
			if ( ! $site_id ) {
				continue;
			}

			if ( ! is_array( $employee_ids ) ) {
				continue;
			}

			$employee_ids = array_values(
				array_unique(
					array_filter(
						array_map(
							'absint',
							$employee_ids
						)
					)
				)
			);

			if ( empty( $employee_ids ) ) {
				continue;
			}

			$selections[ (string) $site_id ] = $employee_ids;
		}

		return $selections;
	}

	/**
	 * Check if current page is a portal page.
	 *
	 * @return bool
	 */
	private function is_portal_page() {
		// Get current page ID using queried object for reliability.
		$queried_object  = get_queried_object();
		$current_page_id = 0;

		if ( $queried_object && isset( $queried_object->ID ) ) {
			$current_page_id = $queried_object->ID;
		} elseif ( get_the_ID() ) {
			$current_page_id = get_the_ID();
		}

		if ( ! $current_page_id ) {
			return false;
		}

		$settings          = $this->get_portal_settings();
		$login_page_id     = isset( $settings['login_page_id'] ) ? absint( $settings['login_page_id'] ) : 0;
		$dashboard_page_id = isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;

		// Check by page ID from settings.
		if ( $current_page_id === $login_page_id || $current_page_id === $dashboard_page_id ) {
			return true;
		}

		// Fallback: Check by shortcode for backward compatibility.
		$post = get_post( $current_page_id );
		if ( $post && ( has_shortcode( $post->post_content, 'berp_employee_login' ) || has_shortcode( $post->post_content, 'berp_employee_dashboard' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render login shortcode.
	 *
	 * @return string HTML content.
	 */
	public function render_login_shortcode() {
		if ( is_user_logged_in() ) {
			// If already logged in, redirect to dashboard.
			$dashboard_page_id = $this->get_dashboard_page_id();
			if ( $dashboard_page_id && get_the_ID() !== $dashboard_page_id ) {
				wp_redirect( get_permalink( $dashboard_page_id ) );
				exit;
			}
			return '<p>' . esc_html__( 'You are already logged in.', 'aic_builderp' ) . '</p>';
		}

		ob_start();
		include BERP_PLUGIN_DIR . 'templates/portal/login.php';
		return ob_get_clean();
	}

	/**
	 * Render dashboard shortcode.
	 *
	 * @return string HTML content.
	 */
	public function render_dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			$login_page_url = $this->get_login_page_url();
			if ( $login_page_url && get_permalink() !== $login_page_url ) {
				wp_redirect( $login_page_url );
				exit;
			}
			return '<p>' . esc_html__( 'Please log in to view the dashboard.', 'aic_builderp' ) . '</p>';
		}

		$user = wp_get_current_user();

		// Check if user has portal access.
		if ( ! $this->has_portal_access( $user ) ) {
			return '<p>' . esc_html__( 'You do not have permission to access the employee portal.', 'aic_builderp' ) . '</p>';
		}

		ob_start();

		// Load header.
		include BERP_PLUGIN_DIR . 'templates/portal/header.php';

		// Route based on role and query var.
		$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'dashboard';

		if ( in_array( 'berp_timekeeper', (array) $user->roles, true ) || current_user_can( 'manage_options' ) ) {
			// Timekeeper Dashboard.
			$this->render_timekeeper_view( $view );
		} else {
			// Employee Dashboard.
			$this->render_employee_view( $view );
		}

		// Load footer.
		include BERP_PLUGIN_DIR . 'templates/portal/footer.php';

		return ob_get_clean();
	}

	/**
	 * Render timekeeper views.
	 *
	 * @param string $view View name.
	 */
	private function render_timekeeper_view( $view ) {
		switch ( $view ) {
			case 'log-attendance':
				include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/log-attendance.php';
				break;
			case 'view-attendance':
				include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/view-attendance.php';
				break;
			case 'employees':
				include BERP_PLUGIN_DIR . 'templates/portal/timekeeper/employees.php';
				break;
			// Employee self-service views for timekeeper (my-* views).
			case 'my-dashboard':
				include BERP_PLUGIN_DIR . 'templates/portal/dashboard-employee.php';
				break;
			case 'my-attendance':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/attendance.php';
				break;
			case 'my-salary':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/salary.php';
				break;
			case 'my-advances':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/advances.php';
				break;
			case 'my-profile':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/profile.php';
				break;
			case 'dashboard':
			default:
				include BERP_PLUGIN_DIR . 'templates/portal/dashboard-timekeeper.php';
				break;
		}
	}

	/**
	 * Render employee views.
	 *
	 * @param string $view View name.
	 */
	private function render_employee_view( $view ) {
		switch ( $view ) {
			case 'attendance':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/attendance.php';
				break;
			case 'salary':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/salary.php';
				break;
			case 'statement':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/statement.php';
				break;
			case 'advances':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/advances.php';
				break;
			case 'profile':
				include BERP_PLUGIN_DIR . 'templates/portal/employee/profile.php';
				break;
			case 'dashboard':
			default:
				include BERP_PLUGIN_DIR . 'templates/portal/dashboard-employee.php';
				break;
		}
	}

	/**
	 * Handle redirects.
	 */
	public function handle_redirects() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$settings          = $this->get_portal_settings();
		$login_page_id     = isset( $settings['login_page_id'] ) ? absint( $settings['login_page_id'] ) : 0;
		$dashboard_page_id = isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;

		// Check if on login page (by ID or shortcode).
		$is_login_page = ( $login_page_id && $post->ID === $login_page_id ) || has_shortcode( $post->post_content, 'berp_employee_login' );

		// Check if on dashboard page (by ID or shortcode).
		$is_dashboard_page = ( $dashboard_page_id && $post->ID === $dashboard_page_id ) || has_shortcode( $post->post_content, 'berp_employee_dashboard' );

		// Redirect logged-in users away from login page.
		if ( is_user_logged_in() && $is_login_page ) {
			$redirect_page_id = $this->get_dashboard_page_id();
			// Only redirect if dashboard page exists AND it's not the current page.
			if ( $redirect_page_id && $redirect_page_id !== $post->ID ) {
				wp_redirect( get_permalink( $redirect_page_id ) );
				exit;
			}
		}

		// Redirect non-logged-in users away from dashboard page.
		if ( ! is_user_logged_in() && $is_dashboard_page ) {
			$login_page_url = $this->get_login_page_url();
			// Only redirect if login page exists AND it's not the current page.
			if ( $login_page_url && get_permalink( $post->ID ) !== $login_page_url ) {
				wp_redirect( $login_page_url );
				exit;
			}
		}
	}

	/**
	 * Handle login form submission.
	 */
	public function handle_login_submission() {
		if ( ! isset( $_POST['berp_login_submit'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['berp_login_nonce'] ) || ! wp_verify_nonce( $_POST['berp_login_nonce'], 'berp_login_action' ) ) {
			return;
		}

		$username = isset( $_POST['berp_username'] ) ? sanitize_text_field( $_POST['berp_username'] ) : '';
		$password = isset( $_POST['berp_password'] ) ? $_POST['berp_password'] : '';
		$remember = isset( $_POST['berp_remember'] ) ? true : false;

		if ( empty( $username ) || empty( $password ) ) {
			return;
		}

		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			// Redirect back with error.
			$error_code     = $user->get_error_code();
			$login_page_url = $this->get_login_page_url();
			if ( $login_page_url ) {
				wp_redirect( add_query_arg( 'login_error', $error_code, $login_page_url ) );
				exit;
			}
		} else {
			// Successful login - redirect to dashboard.
			$dashboard_page_id = $this->get_dashboard_page_id();
			if ( $dashboard_page_id ) {
				wp_redirect( get_permalink( $dashboard_page_id ) );
				exit;
			}
			wp_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Check if user has portal access.
	 *
	 * @param WP_User $user User object.
	 * @return boolean
	 */
	private function has_portal_access( $user ) {
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		}
		if ( in_array( 'berp_employee', (array) $user->roles, true ) ) {
			return true;
		}
		if ( in_array( 'berp_timekeeper', (array) $user->roles, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get dashboard page ID.
	 *
	 * @return int|false Page ID or false.
	 */
	private function get_dashboard_page_id() {
		$settings          = $this->get_portal_settings();
		$dashboard_page_id = isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;

		if ( $dashboard_page_id && get_post_status( $dashboard_page_id ) === 'publish' ) {
			return $dashboard_page_id;
		}

		// Fallback: Try to find page with dashboard shortcode.
		global $wpdb;
		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_content LIKE %s LIMIT 1",
				'page',
				'publish',
				'%[berp_employee_dashboard]%'
			)
		);

		return $page_id ? absint( $page_id ) : false;
	}

	/**
	 * Get login page URL.
	 *
	 * @return string|false URL or false.
	 */
	private function get_login_page_url() {
		$settings      = $this->get_portal_settings();
		$login_page_id = isset( $settings['login_page_id'] ) ? absint( $settings['login_page_id'] ) : 0;

		if ( $login_page_id && get_post_status( $login_page_id ) === 'publish' ) {
			return get_permalink( $login_page_id );
		}

		// Fallback: Try to find page with login shortcode.
		global $wpdb;
		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_content LIKE %s LIMIT 1",
				'page',
				'publish',
				'%[berp_employee_login]%'
			)
		);

		if ( $page_id ) {
			return get_permalink( $page_id );
		}

		return wp_login_url();
	}

	/**
	 * Get login page ID.
	 *
	 * @return int|false Page ID or false.
	 */
	private function get_login_page_id() {
		$settings      = $this->get_portal_settings();
		$login_page_id = isset( $settings['login_page_id'] ) ? absint( $settings['login_page_id'] ) : 0;

		if ( $login_page_id && get_post_status( $login_page_id ) === 'publish' ) {
			return $login_page_id;
		}

		// Fallback: Try to find page with login shortcode.
		global $wpdb;
		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_content LIKE %s LIMIT 1",
				'page',
				'publish',
				'%[berp_employee_login]%'
			)
		);

		return $page_id ? absint( $page_id ) : false;
	}

	/**
	 * AJAX handler for bulk attendance submission from portal.
	 */
	public function ajax_bulk_attendance() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'berp_portal_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aic_builderp' ) ) );
		}

		// Check permission - allow admins and those with specific capability.
		if ( ! current_user_can( 'berp_log_attendance' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to log attendance.', 'aic_builderp' ) ) );
		}

		// Get and validate parameters.
		$date      = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
		$site_id   = isset( $_POST['site_id'] ) ? absint( $_POST['site_id'] ) : 0;
		$employees = isset( $_POST['employees'] ) ? json_decode( stripslashes( $_POST['employees'] ), true ) : array();

		if ( empty( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Date is required.', 'aic_builderp' ) ) );
		}

		if ( empty( $site_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Site is required.', 'aic_builderp' ) ) );
		}

		if ( empty( $employees ) || ! is_array( $employees ) ) {
			wp_send_json_error( array( 'message' => __( 'No employees selected.', 'aic_builderp' ) ) );
		}

		// Validate date format.
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $date_obj || $date_obj->format( 'Y-m-d' ) !== $date ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'aic_builderp' ) ) );
		}

		// Get attendance settings.
		$settings            = get_option( 'berp_settings', array() );
		$attendance_settings = isset( $settings['attendance'] ) ? $settings['attendance'] : array();
		$block_future        = isset( $attendance_settings['block_future'] ) ? (bool) $attendance_settings['block_future'] : true;

		// Block future dates if setting enabled.
		if ( $block_future && $date > current_time( 'Y-m-d' ) ) {
			wp_send_json_error( array( 'message' => __( 'Cannot log attendance for future dates.', 'aic_builderp' ) ) );
		}

		$user_id = get_current_user_id();
		$created = 0;
		$updated = 0;
		$skipped = 0;

		$selected_employee_ids = array();

		foreach ( $employees as $emp_data ) {
			$employee_id = isset( $emp_data['id'] ) ? absint( $emp_data['id'] ) : 0;
			$overtime    = isset( $emp_data['overtime'] ) ? floatval( $emp_data['overtime'] ) : 0;

			if ( ! $employee_id ) {
				++$skipped;
				continue;
			}

			$selected_employee_ids[] = $employee_id;

			// Check if attendance exists.
			$existing_id = $this->find_attendance( $employee_id, $date );

			if ( $existing_id ) {
				// Update existing.
				$this->update_attendance_meta( $existing_id, $employee_id, $date, $site_id, $overtime, '', $user_id );
				++$updated;
			} else {
				// Create new.
				$post_id = $this->create_attendance_post( $employee_id, $date, $site_id, $overtime, '', $user_id );
				if ( ! is_wp_error( $post_id ) ) {
					++$created;
				} else {
					++$skipped;
				}
			}
		}

		// Save last used site.
		update_user_meta( $user_id, '_berp_last_site', $site_id );
		$this->update_user_site_employee_selections( $user_id, $site_id, $selected_employee_ids );

		$total = $created + $updated;
		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: total processed, 2: created, 3: updated */
					__( '%1$d attendance records processed (%2$d new, %3$d updated).', 'aic_builderp' ),
					$total,
					$created,
					$updated
				),
				'created' => $created,
				'updated' => $updated,
				'skipped' => $skipped,
			)
		);
	}

	/**
	 * Store last-selected employees for a site (per user).
	 *
	 * @since 1.0.0
	 * @param int   $user_id User ID.
	 * @param int   $site_id Site ID.
	 * @param array $employee_ids Employee IDs.
	 * @return void
	 */
	private function update_user_site_employee_selections( $user_id, $site_id, $employee_ids ) {
		$user_id = absint( $user_id );
		$site_id = absint( $site_id );
		if ( ! $user_id || ! $site_id ) {
			return;
		}

		$employee_ids = is_array( $employee_ids ) ? $employee_ids : array();
		$employee_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						'absint',
						$employee_ids
					)
				)
			)
		);

		if ( empty( $employee_ids ) ) {
			return;
		}

		$selections                      = $this->get_user_site_employee_selections( $user_id );
		$selections[ (string) $site_id ] = $employee_ids;

		update_user_meta( $user_id, '_berp_attendance_site_employee_selections', $selections );
	}

	/**
	 * AJAX handler for checking attendance status for a given date.
	 */
	public function ajax_check_attendance_status() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'berp_portal_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'aic_builderp' ) ) );
		}

		// Check permission.
		if ( ! current_user_can( 'berp_log_attendance' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'aic_builderp' ) ) );
		}

		$date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';

		if ( empty( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Date is required.', 'aic_builderp' ) ) );
		}

		// Get all attendance records for this date.
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm1.meta_value as employee_id, pm2.meta_value as overtime
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_berp_employee_id'
				INNER JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = '_berp_date'
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_berp_overtime_hours'
				WHERE p.post_type = 'berp_attendance'
				AND p.post_status = 'publish'
				AND pm_date.meta_value = %s",
				$date
			)
		);

		$logged_employees = array();
		foreach ( $results as $row ) {
			$logged_employees[ $row->employee_id ] = array(
				'logged'   => true,
				'overtime' => floatval( $row->overtime ),
			);
		}

		wp_send_json_success(
			array(
				'date'   => $date,
				'logged' => $logged_employees,
				'count'  => count( $logged_employees ),
			)
		);
	}

	/**
	 * Find attendance by employee and date.
	 *
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date string (Y-m-d).
	 * @return int|null Attendance post ID or null.
	 */
	private function find_attendance( $employee_id, $date ) {
		global $wpdb;

		$attendance_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_berp_employee_id'
				INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_berp_date'
				WHERE p.post_type = 'berp_attendance'
				AND p.post_status = 'publish'
				AND pm1.meta_value = %d
				AND pm2.meta_value = %s
				LIMIT 1",
				$employee_id,
				$date
			)
		);

		return $attendance_id ? intval( $attendance_id ) : null;
	}

	/**
	 * Create attendance post.
	 *
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date string.
	 * @param int    $site_id Site ID.
	 * @param float  $overtime Overtime hours.
	 * @param string $notes Notes.
	 * @param int    $user_id User ID.
	 * @return int|WP_Error Post ID or error.
	 */
	private function create_attendance_post( $employee_id, $date, $site_id, $overtime, $notes, $user_id ) {
		$title = sprintf(
			/* translators: 1: employee id 2: date */
			__( 'Attendance - %1$s - %2$s', 'aic_builderp' ),
			$employee_id,
			$date
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_attendance',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->update_attendance_meta( $post_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id );

		return $post_id;
	}

	/**
	 * Update attendance meta.
	 *
	 * @param int    $attendance_id Attendance post ID.
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date string.
	 * @param int    $site_id Site ID.
	 * @param float  $overtime Overtime hours.
	 * @param string $notes Notes.
	 * @param int    $user_id User ID.
	 */
	private function update_attendance_meta( $attendance_id, $employee_id, $date, $site_id, $overtime, $notes, $user_id ) {
		update_post_meta( $attendance_id, '_berp_employee_id', absint( $employee_id ) );
		update_post_meta( $attendance_id, '_berp_date', sanitize_text_field( $date ) );
		update_post_meta( $attendance_id, '_berp_site_id', absint( $site_id ) );
		update_post_meta( $attendance_id, '_berp_overtime_hours', floatval( $overtime ) );
		update_post_meta( $attendance_id, '_berp_notes', sanitize_textarea_field( $notes ) );
		update_post_meta( $attendance_id, '_berp_logged_by', absint( $user_id ) );
		update_post_meta( $attendance_id, '_berp_logged_at', current_datetime()->getTimestamp() );

		// Weekend/holiday flags.
		$flags = $this->get_day_flags( $employee_id, $date );
		update_post_meta( $attendance_id, '_berp_is_weekend', $flags['is_weekend'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_is_holiday', $flags['is_holiday'] ? 1 : 0 );
		update_post_meta( $attendance_id, '_berp_weekend_payable', $flags['weekend_payable'] ? 1 : 0 );
	}

	/**
	 * Get day flags for weekend/holiday detection.
	 *
	 * @param int    $employee_id Employee ID.
	 * @param string $date Date string.
	 * @return array Flags array.
	 */
	private function get_day_flags( $employee_id, $date ) {
		$day_of_week = gmdate( 'w', strtotime( $date ) );

		// Get settings for weekend days.
		$settings         = get_option( 'berp_settings', array() );
		$payroll_settings = isset( $settings['payroll'] ) ? $settings['payroll'] : array();
		$weekend_days     = isset( $payroll_settings['weekend_days'] ) ? $payroll_settings['weekend_days'] : array( 5, 6 ); // Default: Fri, Sat.

		$is_weekend = in_array( (int) $day_of_week, (array) $weekend_days, true );

		// Check for holidays (basic implementation).
		$is_holiday = false;

		// Check weekend payability for employee.
		$weekend_payable = false;
		if ( $is_weekend ) {
			$emp_weekend_payable = get_post_meta( $employee_id, '_berp_weekend_payable', true );
			$weekend_payable     = ( '1' === $emp_weekend_payable || 'yes' === $emp_weekend_payable );
		}

		return array(
			'is_weekend'      => $is_weekend,
			'is_holiday'      => $is_holiday,
			'weekend_payable' => $weekend_payable,
		);
	}
}
