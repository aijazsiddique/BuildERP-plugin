<?php
/**
 * Advance Request Notifications
 *
 * Handles email notifications for advance requests.
 *
 * @package    BuildERP
 * @subpackage BuildERP/includes/admin
 * @since      1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Advance_Notifications Class
 */
class BERP_Advance_Notifications {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		// Hook into advance status changes.
		add_action( 'berp_advance_request_created', array( $this, 'notify_admin_new_request' ), 10, 3 );
		add_action( 'berp_advance_approved', array( $this, 'notify_employee_approved' ), 10, 3 );
		add_action( 'berp_advance_rejected', array( $this, 'notify_employee_rejected' ), 10, 2 );
	}

	/**
	 * Get notification settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		$settings = get_option( 'berp_settings', array() );
		return isset( $settings['notification'] ) ? $settings['notification'] : array();
	}

	/**
	 * Check if admin notifications are enabled.
	 *
	 * @return bool
	 */
	private function admin_notifications_enabled() {
		$settings = $this->get_settings();
		return isset( $settings['admin_notifications'] ) ? (bool) $settings['admin_notifications'] : true;
	}

	/**
	 * Check if employee notifications are enabled.
	 *
	 * @return bool
	 */
	private function employee_notifications_enabled() {
		$settings = $this->get_settings();
		return isset( $settings['employee_notifications'] ) ? (bool) $settings['employee_notifications'] : true;
	}

	/**
	 * Get admin email.
	 *
	 * @return string
	 */
	private function get_admin_email() {
		$settings = get_option( 'berp_settings', array() );
		if ( isset( $settings['general']['company_email'] ) && ! empty( $settings['general']['company_email'] ) ) {
			return $settings['general']['company_email'];
		}
		return get_option( 'admin_email' );
	}

	/**
	 * Get employee email from employee post.
	 *
	 * @param int $employee_id Employee post ID.
	 * @return string|false Email or false.
	 */
	private function get_employee_email( $employee_id ) {
		$email = get_post_meta( $employee_id, '_berp_email', true );
		if ( ! $email ) {
			// Try to get from linked user.
			$user_id = get_post_meta( $employee_id, '_berp_user_id', true );
			if ( $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$email = $user->user_email;
				}
			}
		}
		return $email;
	}

	/**
	 * Notify admin of new advance request.
	 *
	 * @param int   $advance_id  Advance post ID.
	 * @param int   $employee_id Employee post ID.
	 * @param array $data        Request data.
	 */
	public function notify_admin_new_request( $advance_id, $employee_id, $data ) {
		if ( ! $this->admin_notifications_enabled() ) {
			return;
		}

		// Only send if approval is required.
		$requires_approval = berp_get_employee_advance_requires_approval_setting();
		if ( ! $requires_approval ) {
			return;
		}

		$admin_email = $this->get_admin_email();
		if ( ! $admin_email ) {
			return;
		}

		$employee      = get_post( $employee_id );
		$employee_name = $employee ? $employee->post_title : __( 'Unknown Employee', 'BuildERP' );
		$amount        = isset( $data['amount'] ) ? berp_format_currency( $data['amount'] ) : '—';
		$reason        = isset( $data['reason'] ) ? $data['reason'] : __( 'No reason provided', 'BuildERP' );
		$edit_link     = admin_url( 'post.php?post=' . $advance_id . '&action=edit' );

		$subject = sprintf(
			/* translators: 1: employee name */
			__( '[BuildERP] New Advance Request from %s', 'BuildERP' ),
			$employee_name
		);

		$message = sprintf(
			/* translators: 1: employee name, 2: amount, 3: reason, 4: edit link */
			__( "A new salary advance request has been submitted.\n\nEmployee: %1\$s\nAmount: %2\$s\nReason: %3\$s\n\nReview and approve/reject this request:\n%4\$s", 'BuildERP' ),
			$employee_name,
			$amount,
			$reason,
			$edit_link
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Notify employee when advance is approved.
	 *
	 * @param int   $advance_id  Advance post ID.
	 * @param int   $employee_id Employee post ID.
	 * @param float $amount      Approved amount.
	 */
	public function notify_employee_approved( $advance_id, $employee_id, $amount ) {
		if ( ! $this->employee_notifications_enabled() ) {
			return;
		}

		$email = $this->get_employee_email( $employee_id );
		if ( ! $email ) {
			return;
		}

		$employee      = get_post( $employee_id );
		$employee_name = $employee ? $employee->post_title : '';
		$repayment     = get_post_meta( $advance_id, '_berp_repayment_type', true );
		$installments  = get_post_meta( $advance_id, '_berp_installments', true );

		$repayment_info = __( 'Full deduction in next payroll', 'BuildERP' );
		if ( 'installments' === $repayment && $installments > 1 ) {
			$repayment_info = sprintf(
				/* translators: %d: number of installments */
				__( '%d equal installments from your upcoming payrolls', 'BuildERP' ),
				$installments
			);
		}

		$subject = __( '[BuildERP] Your Advance Request Has Been Approved', 'BuildERP' );

		$message = sprintf(
			/* translators: 1: employee name, 2: amount, 3: repayment info */
			__( "Dear %1\$s,\n\nYour salary advance request has been approved!\n\nAmount: %2\$s\nRepayment: %3\$s\n\nThe amount has been added to your account. It will be deducted from your upcoming payroll(s).\n\nBest regards,\nBuildERP", 'BuildERP' ),
			$employee_name,
			berp_format_currency( $amount ),
			$repayment_info
		);

		wp_mail( $email, $subject, $message );
	}

	/**
	 * Notify employee when advance is rejected.
	 *
	 * @param int    $advance_id     Advance post ID.
	 * @param string $rejection_note Rejection reason.
	 */
	public function notify_employee_rejected( $advance_id, $rejection_note ) {
		if ( ! $this->employee_notifications_enabled() ) {
			return;
		}

		$employee_id = get_post_meta( $advance_id, '_berp_employee_id', true );
		if ( ! $employee_id ) {
			return;
		}

		$email = $this->get_employee_email( $employee_id );
		if ( ! $email ) {
			return;
		}

		$employee      = get_post( $employee_id );
		$employee_name = $employee ? $employee->post_title : '';
		$amount        = get_post_meta( $advance_id, '_berp_advance_amount', true );

		$subject = __( '[BuildERP] Your Advance Request Has Been Declined', 'BuildERP' );

		$rejection_text = ! empty( $rejection_note )
			? $rejection_note
			: __( 'No specific reason provided.', 'BuildERP' );

		$message = sprintf(
			/* translators: 1: employee name, 2: amount, 3: rejection reason */
			__( "Dear %1\$s,\n\nUnfortunately, your salary advance request has been declined.\n\nRequested Amount: %2\$s\nReason: %3\$s\n\nIf you have questions, please contact HR or your manager.\n\nBest regards,\nBuildERP", 'BuildERP' ),
			$employee_name,
			berp_format_currency( $amount ),
			$rejection_text
		);

		wp_mail( $email, $subject, $message );
	}
}

