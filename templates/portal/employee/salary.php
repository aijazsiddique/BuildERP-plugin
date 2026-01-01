<?php
/**
 * Employee Salary View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$employee_id = BERP_Employee_Dashboard::get_current_employee_id();
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$args = array(
	'post_type'      => 'berp_payroll',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_key'       => '_berp_month',
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
		<h3><?php esc_html_e( 'My Salary Slips', 'aic_builderp' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<?php if ( $query->have_posts() ) : ?>
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Month', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Gross Salary', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Deductions', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Net Salary', 'aic_builderp' ); ?></th>
						<th><?php esc_html_e( 'Action', 'aic_builderp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $query->have_posts() ) : $query->the_post(); 
						$month = get_post_meta( get_the_ID(), '_berp_month', true );
						$gross = get_post_meta( get_the_ID(), '_berp_gross_salary', true );
						$deductions = get_post_meta( get_the_ID(), '_berp_total_deductions', true );
						$net = get_post_meta( get_the_ID(), '_berp_net_salary', true );
					?>
						<tr>
							<td><?php echo esc_html( date_i18n( 'F Y', strtotime( $month . '-01' ) ) ); ?></td>
							<td><?php echo esc_html( berp_format_currency( $gross ) ); ?></td>
							<td><?php echo esc_html( berp_format_currency( $deductions ) ); ?></td>
							<td><strong><?php echo esc_html( berp_format_currency( $net ) ); ?></strong></td>
							<td>
								<button class="berp-btn berp-btn-sm berp-btn-outline"><?php esc_html_e( 'Download PDF', 'aic_builderp' ); ?></button>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<div class="berp-pagination">
				<?php
				echo paginate_links( array(
					'total' => $query->max_num_pages,
					'current' => $paged,
					'format' => '?paged=%#%',
				) );
				?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No salary slips found.', 'aic_builderp' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>
