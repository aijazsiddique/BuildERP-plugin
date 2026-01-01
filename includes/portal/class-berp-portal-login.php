<?php
/**
 * Portal Login Handler.
 *
 * Handles login form submission and authentication.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Portal_Login Class.
 */
class BERP_Portal_Login {

	/**
	 * Initialize the login handler.
	 */
	public function init() {
		add_action( 'init', array( $this, 'process_login' ) );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
	}

	/**
	 * Process login form submission.
	 */
	public function process_login() {
		if ( isset( $_POST['berp_login_submit'] ) && isset( $_POST['berp_login_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['berp_login_nonce'], 'berp_login_action' ) ) {
				return;
			}

			$creds = array(
				'user_login'    => sanitize_text_field( $_POST['berp_username'] ),
				'user_password' => $_POST['berp_password'], // wp_signon sanitizes this.
				'remember'      => isset( $_POST['berp_remember'] ),
			);

			$user = wp_signon( $creds, is_ssl() );

			if ( is_wp_error( $user ) ) {
				// Add error to query arg to display on login page.
				$login_url = $this->get_login_page_url();
				if ( $login_url ) {
					wp_redirect( add_query_arg( 'login_error', $user->get_error_code(), $login_url ) );
					exit;
				}
			} else {
				// Redirect to dashboard.
				$dashboard_url = $this->get_dashboard_page_url();
				if ( $dashboard_url ) {
					wp_redirect( $dashboard_url );
					exit;
				} else {
					wp_redirect( home_url() );
					exit;
				}
			}
		}
	}

	/**
	 * Redirect after logout.
	 */
	public function redirect_after_logout() {
		$login_url = $this->get_login_page_url();
		if ( $login_url ) {
			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Get login page URL.
	 *
	 * @return string|false URL or false.
	 */
	private function get_login_page_url() {
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
	 * Get dashboard page URL.
	 *
	 * @return string|false URL or false.
	 */
	private function get_dashboard_page_url() {
		global $wpdb;
		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_content LIKE %s LIMIT 1",
				'page',
				'publish',
				'%[berp_employee_dashboard]%'
			)
		);

		if ( $page_id ) {
			return get_permalink( $page_id );
		}
		return home_url();
	}
}
