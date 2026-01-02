<?php
/**
 * Employee Attendance View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();
$paged       = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$args = array(
	'post_type'      => 'berp_attendance',
	'posts_per_page' => 20,
	'paged'          => $paged,
	'meta_key'       => '_berp_date',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'meta_query'     => array(
		array(
			'key'   => '_berp_employee_id',
			'value' => $employee_id,
		),
	),
);

$query = new WP_Query( $args );
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'My Attendance History', 'builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<?php if ( $query->have_posts() ) : ?>
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Site', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Overtime (Hrs)', 'builderp' ); ?></th>
						<th><?php esc_html_e( 'Status', 'builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$date      = get_post_meta( get_the_ID(), '_berp_date', true );
						$site_id   = get_post_meta( get_the_ID(), '_berp_site_id', true );
						$overtime  = get_post_meta( get_the_ID(), '_berp_overtime_hours', true );
						$site_name = $site_id ? get_the_title( $site_id ) : '-';
						?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); ?></td>
							<td><?php echo esc_html( $site_name ); ?></td>
							<td><?php echo esc_html( $overtime ); ?></td>
							<td><span class="berp-badge berp-badge-success"><?php esc_html_e( 'Present', 'builderp' ); ?></span></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<div class="berp-pagination">
				<?php
				echo wp_kses_post(
					paginate_links(
					array(
						'total'   => $query->max_num_pages,
						'current' => $paged,
						'format'  => '?paged=%#%',
					)
					)
				);
				?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No attendance records found.', 'builderp' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>

