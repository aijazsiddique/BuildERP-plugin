<?php
/**
 * Standalone Portal Template.
 *
 * This template renders portal pages without theme header/footer.
 * It provides a clean, theme-independent interface for the employee portal.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get settings for branding.
$settings        = get_option( 'berp_settings', array() );
$portal_settings = isset( $settings['portal'] ) ? $settings['portal'] : array();
$branding_color  = isset( $portal_settings['branding_color'] ) ? $portal_settings['branding_color'] : '#2271b1';
$company_name    = isset( $settings['general']['company_name'] ) ? $settings['general']['company_name'] : get_bloginfo( 'name' );
$company_logo    = isset( $settings['general']['company_logo'] ) ? $settings['general']['company_logo'] : '';

// Determine the page type.
$login_page_id     = isset( $portal_settings['login_page_id'] ) ? absint( $portal_settings['login_page_id'] ) : 0;
$dashboard_page_id = isset( $portal_settings['dashboard_page_id'] ) ? absint( $portal_settings['dashboard_page_id'] ) : 0;
$current_page_id   = get_the_ID();

$is_login_page     = ( $login_page_id && $current_page_id === $login_page_id );
$is_dashboard_page = ( $dashboard_page_id && $current_page_id === $dashboard_page_id );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php wp_title( '|', true, 'right' ); ?><?php echo esc_html( $company_name ); ?></title>
	<style>
		:root {
			--berp-primary-color: <?php echo esc_attr( $branding_color ); ?>;
			--berp-primary-hover: <?php echo esc_attr( berp_adjust_color_brightness( $branding_color, -20 ) ); ?>;
			--berp-primary-light: <?php echo esc_attr( berp_adjust_color_brightness( $branding_color, 40 ) ); ?>;
		}
		
		/* Reset and base styles */
		*, *::before, *::after {
			box-sizing: border-box;
		}
		
		html, body {
			margin: 0;
			padding: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			font-size: 14px;
			line-height: 1.5;
			background-color: #f0f0f1;
			color: #1d2327;
			min-height: 100vh;
			width: 100%;
		}
		
		a {
			color: var(--berp-primary-color);
			text-decoration: none;
		}
		
		a:hover {
			color: var(--berp-primary-hover);
		}
		
		/* Standalone template wrapper */
		.berp-standalone-wrapper {
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			width: 100%;
			max-width: 100%;
		}
		
		/* Login page specific styles */
		.berp-standalone-wrapper.berp-login-page {
			justify-content: center;
			align-items: center;
			padding: 20px;
			background: linear-gradient(135deg, var(--berp-primary-color) 0%, var(--berp-primary-hover) 100%);
		}
		
		.berp-standalone-wrapper.berp-login-page .berp-standalone-content {
			width: 100%;
			max-width: 400px;
		}
		
		/* Dashboard page styles */
		.berp-standalone-wrapper.berp-dashboard-page {
			background-color: #f0f0f1;
		}
		
		.berp-standalone-wrapper.berp-dashboard-page .berp-standalone-content {
			flex: 1;
			display: flex;
			flex-direction: column;
			width: 100%;
			max-width: 100%;
		}
		
		/* Footer */
		.berp-standalone-footer {
			text-align: center;
			padding: 15px 20px;
			font-size: 12px;
			color: #646970;
			background: #fff;
			border-top: 1px solid #dcdcde;
		}
		
		.berp-login-page .berp-standalone-footer {
			background: transparent;
			border: none;
			color: rgba(255,255,255,0.8);
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
		}
		
		.berp-standalone-footer a {
			color: inherit;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'berp-standalone-body' ); ?>>

<div class="berp-standalone-wrapper <?php echo $is_login_page ? 'berp-login-page' : 'berp-dashboard-page'; ?>">
	
	<div class="berp-standalone-content">
		<?php
		// Render appropriate content based on page type.
		if ( $is_login_page ) {
			berp_render_standalone_login();
		} elseif ( $is_dashboard_page ) {
			berp_render_standalone_dashboard();
		} else {
			// Fallback to regular content.
			while ( have_posts() ) {
				the_post();
				the_content();
			}
		}
		?>
	</div>

	<?php if ( $is_login_page ) : ?>
		<footer class="berp-standalone-footer">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $company_name ); ?>. <?php esc_html_e( 'All rights reserved.', 'BuildERP' ); ?></p>
		</footer>
	<?php endif; ?>
	
</div>

<?php wp_footer(); ?>
</body>
</html>


