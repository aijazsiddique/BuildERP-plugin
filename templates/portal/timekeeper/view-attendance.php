<?php
/**
 * Timekeeper View Attendance.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged       = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$date_filter = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
$site_filter = isset( $_GET['site'] ) ? absint( $_GET['site'] ) : '';

$args = array(
	'post_type'      => 'berp_attendance',
	'post_status'    => array( 'publish', 'private', 'draft' ),
	'posts_per_page' => 20,
	'paged'          => $paged,
	'orderby'        => 'meta_value',
	'meta_key'       => '_berp_date',
	'order'          => 'DESC',
	'meta_query'     => array(),
);

if ( $date_filter ) {
	$args['meta_query'][] = array(
		'key'   => '_berp_date',
		'value' => $date_filter,
	);
}

if ( $site_filter ) {
	$args['meta_query'][] = array(
		'key'   => '_berp_site_id',
		'value' => $site_filter,
	);
}

$query = new WP_Query( $args );

// Get sites for filter.
$sites = get_posts(
	array(
		'post_type'      => 'berp_site',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Attendance Records', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<form method="get" class="berp-filter-form">
			<!-- Preserve view parameter -->
			<input type="hidden" name="view" value="view-attendance">
			
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-4">
					<input type="date" name="date" class="berp-form-control" value="<?php echo esc_attr( $date_filter ); ?>" placeholder="<?php esc_attr_e( 'Filter by Date', 'aic_builderp' ); ?>">
				</div>
				<div class="berp-form-group berp-col-4">
					<select name="site" class="berp-form-control">
						<option value=""><?php esc_html_e( 'All Sites', 'aic_builderp' ); ?></option>
						<?php foreach ( $sites as $site_id ) : ?>
							<option value="<?php echo esc_attr( $site_id ); ?>" <?php selected( $site_filter, $site_id ); ?>><?php echo esc_html( get_the_title( $site_id ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="berp-form-group berp-col-4">
					<button type="submit" class="berp-btn berp-btn-primary"><?php esc_html_e( 'Filter', 'aic_builderp' ); ?></button>
					<a href="<?php echo esc_url( remove_query_arg( array( 'date', 'site' ) ) ); ?>" class="berp-btn berp-btn-outline"><?php esc_html_e( 'Reset', 'aic_builderp' ); ?></a>
				</div>
			</div>
		</form>

		<?php if ( $query->have_posts() ) : ?>
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Employee', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Site', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Overtime', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Logged By', 'aic_builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$date        = get_post_meta( get_the_ID(), '_berp_date', true );
						$employee_id = get_post_meta( get_the_ID(), '_berp_employee_id', true );
						$site_id     = get_post_meta( get_the_ID(), '_berp_site_id', true );
						$overtime    = get_post_meta( get_the_ID(), '_berp_overtime_hours', true );
						$logged_by   = get_post_meta( get_the_ID(), '_berp_logged_by', true );

						$employee_name    = $employee_id ? get_the_title( $employee_id ) : '-';
						$site_name        = $site_id ? get_the_title( $site_id ) : '-';
						$logger           = $logged_by ? get_userdata( $logged_by ) : null;
						$logger_name      = $logger ? $logger->display_name : '-';
						$overtime_display = is_numeric( $overtime ) ? floatval( $overtime ) : 0;
						?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></td>
							<td><?php echo esc_html( $employee_name ); ?></td>
							<td><?php echo esc_html( $site_name ); ?></td>
							<td><?php echo esc_html( $overtime_display ); ?></td>
							<td><?php echo esc_html( $logger_name ); ?></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<div class="berp-pagination">
				<?php
				echo paginate_links(
					array(
						'total'   => $query->max_num_pages,
						'current' => $paged,
						'format'  => '?paged=%#%',
					)
				);
				?>
			</div>
		<?php else : ?>
			<p class="berp-no-data"><?php esc_html_e( 'No attendance records found.', 'aic_builderp' ); ?></p>
			<p class="berp-help-text"><?php esc_html_e( 'Submit attendance from the "Log Attendance" page to see records here.', 'aic_builderp' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>
