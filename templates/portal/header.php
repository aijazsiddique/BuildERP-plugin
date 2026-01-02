<?php
/**
 * Portal Header Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();

// Get dashboard URL - try multiple methods for reliability.
$dashboard_url = '';
if ( function_exists( 'get_queried_object_id' ) && get_queried_object_id() ) {
	$dashboard_url = get_permalink( get_queried_object_id() );
}
if ( empty( $dashboard_url ) ) {
	$dashboard_url = get_permalink();
}
if ( empty( $dashboard_url ) ) {
	// Fallback: use current URL without query string.
	$dashboard_url = strtok( $_SERVER['REQUEST_URI'], '?' );
	$dashboard_url = home_url( $dashboard_url );
}

// Get current view.
$current_view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'dashboard';
?>
<div class="berp-portal-wrapper">
	<header class="berp-portal-header">
		<div class="berp-portal-brand">
			<button class="berp-mobile-menu-toggle" aria-label="<?php esc_attr_e( 'Toggle Menu', 'builderp' ); ?>" aria-expanded="false">
				<span class="berp-hamburger"></span>
			</button>
			<h1><?php esc_html_e( 'BuildErp Portal', 'builderp' ); ?></h1>
		</div>
		<div class="berp-portal-user-menu">
			<span class="berp-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="berp-logout-link"><?php esc_html_e( 'Logout', 'builderp' ); ?></a>
		</div>
	</header>

	<div class="berp-portal-main">
		<aside class="berp-portal-sidebar">
			<nav class="berp-portal-nav">
				<ul>
					<?php if ( in_array( 'berp_timekeeper', (array) $current_user->roles ) || current_user_can( 'manage_options' ) ) : ?>
						<!-- Timekeeper Menu -->
						<li class="berp-nav-section-title"><?php esc_html_e( 'Timekeeper', 'builderp' ); ?></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'dashboard', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'dashboard' ? 'active' : ''; ?>"><?php esc_html_e( 'Dashboard', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'log-attendance', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'log-attendance' ? 'active' : ''; ?>"><?php esc_html_e( 'Log Attendance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'view-attendance', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'view-attendance' ? 'active' : ''; ?>"><?php esc_html_e( 'View Attendance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'employees', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'employees' ? 'active' : ''; ?>"><?php esc_html_e( 'Employees', 'builderp' ); ?></a></li>
						
						<!-- Employee Self-Service for Timekeeper -->
						<li class="berp-nav-section-title"><?php esc_html_e( 'My Account', 'builderp' ); ?></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'my-dashboard', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'my-dashboard' ? 'active' : ''; ?>"><?php esc_html_e( 'My Dashboard', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'my-attendance', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'my-attendance' ? 'active' : ''; ?>"><?php esc_html_e( 'My Attendance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'my-salary', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'my-salary' ? 'active' : ''; ?>"><?php esc_html_e( 'My Salary', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'my-advances', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'my-advances' ? 'active' : ''; ?>"><?php esc_html_e( 'Request Advance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'my-profile', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'my-profile' ? 'active' : ''; ?>"><?php esc_html_e( 'My Profile', 'builderp' ); ?></a></li>
					<?php else : ?>
						<!-- Employee Menu -->
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'dashboard', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'dashboard' ? 'active' : ''; ?>"><?php esc_html_e( 'Dashboard', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'attendance', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'attendance' ? 'active' : ''; ?>"><?php esc_html_e( 'My Attendance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'salary', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'salary' ? 'active' : ''; ?>"><?php esc_html_e( 'My Salary', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'advances', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'advances' ? 'active' : ''; ?>"><?php esc_html_e( 'Request Advance', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'statement', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'statement' ? 'active' : ''; ?>"><?php esc_html_e( 'Account Statement', 'builderp' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'view', 'profile', $dashboard_url ) ); ?>" class="<?php echo $current_view === 'profile' ? 'active' : ''; ?>"><?php esc_html_e( 'My Profile', 'builderp' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</aside>
		<main class="berp-portal-content">

