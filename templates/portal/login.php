<?php
/**
 * Portal Login Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error_code = isset( $_GET['login_error'] ) ? sanitize_text_field( $_GET['login_error'] ) : '';
$error_msg  = '';

if ( $error_code ) {
	switch ( $error_code ) {
		case 'invalid_username':
		case 'invalid_email':
			$error_msg = __( 'Invalid username or email.', 'aic_builderp' );
			break;
		case 'incorrect_password':
			$error_msg = __( 'The password you entered is incorrect.', 'aic_builderp' );
			break;
		default:
			$error_msg = __( 'An error occurred. Please try again.', 'aic_builderp' );
			break;
	}
}

// Get company branding.
$settings     = get_option( 'berp_settings', array() );
$company_logo = isset( $settings['general']['company_logo'] ) ? $settings['general']['company_logo'] : '';
$company_name = isset( $settings['general']['company_name'] ) ? $settings['general']['company_name'] : get_bloginfo( 'name' );
?>

<div class="berp-portal-login-container">
	<div class="berp-portal-login-card">
		<div class="berp-portal-login-header">
			<?php if ( ! empty( $company_logo ) ) : ?>
				<div class="berp-portal-login-logo">
					<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company_name ); ?>">
				</div>
			<?php endif; ?>
			<h2><?php esc_html_e( 'Employee Portal', 'aic_builderp' ); ?></h2>
			<p><?php esc_html_e( 'Please sign in to continue', 'aic_builderp' ); ?></p>
		</div>

		<?php if ( $error_msg ) : ?>
			<div class="berp-portal-alert berp-portal-alert-danger">
				<?php echo esc_html( $error_msg ); ?>
			</div>
		<?php endif; ?>

		<form method="post" action="" class="berp-portal-login-form">
			<div class="berp-form-group">
				<label for="berp_username"><?php esc_html_e( 'Username or Email', 'aic_builderp' ); ?></label>
				<input type="text" name="berp_username" id="berp_username" class="berp-form-control" required>
			</div>

			<div class="berp-form-group">
				<label for="berp_password"><?php esc_html_e( 'Password', 'aic_builderp' ); ?></label>
				<input type="password" name="berp_password" id="berp_password" class="berp-form-control" required>
			</div>

			<div class="berp-form-group berp-form-check">
				<input type="checkbox" name="berp_remember" id="berp_remember">
				<label for="berp_remember"><?php esc_html_e( 'Remember Me', 'aic_builderp' ); ?></label>
			</div>

			<div class="berp-form-actions">
				<?php wp_nonce_field( 'berp_login_action', 'berp_login_nonce' ); ?>
				<button type="submit" name="berp_login_submit" class="berp-btn berp-btn-primary berp-btn-block">
					<?php esc_html_e( 'Sign In', 'aic_builderp' ); ?>
				</button>
			</div>
		</form>

		<div class="berp-portal-login-footer">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot Password?', 'aic_builderp' ); ?></a>
		</div>
	</div>
</div>
