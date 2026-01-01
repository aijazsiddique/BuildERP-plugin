<?php
/**
 * Timekeeper Log Attendance View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get active sites.
$sites = get_posts(
	array(
		'post_type'      => 'berp_site',
		'posts_per_page' => -1,
		'meta_key'       => '_berp_site_status',
		'meta_value'     => 'in_progress',
	)
);

// Get active employees (include those with 'active' status or no status set).
$employees = get_posts(
	array(
		'post_type'      => 'berp_employee',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_berp_employee_status',
				'value'   => 'active',
				'compare' => '=',
			),
			array(
				'key'     => '_berp_employee_status',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_berp_employee_status',
				'value'   => '',
				'compare' => '=',
			),
		),
	)
);
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Log Daily Attendance', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<form id="berp-portal-attendance-form" class="berp-portal-form">
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-4">
					<label for="attendance_date"><?php esc_html_e( 'Date', 'aic_builderp' ); ?></label>
					<input type="date" id="attendance_date" name="date" class="berp-form-control" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required>
				</div>
				<div class="berp-form-group berp-col-4">
					<label for="site_id"><?php esc_html_e( 'Site / Project', 'aic_builderp' ); ?></label>
					<select id="site_id" name="site_id" class="berp-form-control" required>
						<option value=""><?php esc_html_e( 'Select Site', 'aic_builderp' ); ?></option>
						<?php foreach ( $sites as $site ) : ?>
							<option value="<?php echo esc_attr( $site->ID ); ?>"><?php echo esc_html( $site->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="berp-form-group berp-col-4">
					<label for="default_overtime"><?php esc_html_e( 'Default Overtime (Hrs)', 'aic_builderp' ); ?></label>
					<input type="number" id="default_overtime" name="default_overtime" class="berp-form-control" value="0" min="0" step="0.5">
				</div>
			</div>

			<div class="berp-attendance-list-header">
				<div class="berp-search-box">
					<input type="text" id="employee_search" class="berp-form-control" placeholder="<?php esc_attr_e( 'Search employees...', 'aic_builderp' ); ?>">
				</div>
				<div class="berp-actions">
					<button type="button" id="select_all" class="berp-btn berp-btn-sm berp-btn-outline"><?php esc_html_e( 'Select All', 'aic_builderp' ); ?></button>
					<button type="button" id="deselect_all" class="berp-btn berp-btn-sm berp-btn-outline"><?php esc_html_e( 'Deselect All', 'aic_builderp' ); ?></button>
				</div>
			</div>

			<div class="berp-attendance-list-container">
				<table class="berp-portal-table berp-attendance-table">
					<thead>
						<tr>
							<th width="50"><input type="checkbox" id="check_all_toggle"></th>
							<th><?php esc_html_e( 'Employee', 'aic_builderp' ); ?></th>
							<th width="150"><?php esc_html_e( 'Overtime', 'aic_builderp' ); ?></th>
							<th width="100"><?php esc_html_e( 'Status', 'aic_builderp' ); ?></th>
						</tr>
					</thead>
					<tbody id="employee_list_body">
						<?php foreach ( $employees as $employee ) : ?>
							<tr class="employee-row" data-employee-id="<?php echo esc_attr( $employee->ID ); ?>" data-name="<?php echo esc_attr( strtolower( $employee->post_title ) ); ?>">
								<td>
									<input type="checkbox" name="employees[<?php echo esc_attr( $employee->ID ); ?>][present]" value="1" class="employee-check" checked>
								</td>
								<td>
									<span class="employee-name"><?php echo esc_html( $employee->post_title ); ?></span>
									<span class="employee-id text-muted">#<?php echo esc_html( get_post_meta( $employee->ID, '_berp_employee_id', true ) ); ?></span>
								</td>
								<td>
									<input type="number" name="employees[<?php echo esc_attr( $employee->ID ); ?>][overtime]" class="berp-form-control berp-form-control-sm overtime-input" value="" placeholder="0" min="0" step="0.5">
								</td>
								<td>
									<span class="status-indicator" data-employee-id="<?php echo esc_attr( $employee->ID ); ?>"></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="berp-form-footer sticky-footer">
				<div class="berp-counter">
					<span id="selected_count"><?php echo count( $employees ); ?></span> <?php esc_html_e( 'employees selected', 'aic_builderp' ); ?>
				</div>
				<button type="submit" class="berp-btn berp-btn-primary" id="submit_attendance">
					<?php esc_html_e( 'Submit Attendance', 'aic_builderp' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
