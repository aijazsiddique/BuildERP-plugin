<?php
/**
 * Timekeeper Employee List View.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged  = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

$args = array(
	'post_type'      => 'berp_employee',
	'posts_per_page' => 20,
	'paged'          => $paged,
	'orderby'        => 'title',
	'order'          => 'ASC',
	's'              => $search,
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
);

$query = new WP_Query( $args );
?>

<div class="berp-portal-card">
	<div class="berp-card-header">
		<h3><?php esc_html_e( 'Active Employees', 'BuildERP' ); ?></h3>
	</div>
	<div class="berp-card-body">
		<form method="get" class="berp-filter-form">
			<input type="hidden" name="view" value="employees">
			<div class="berp-form-row">
				<div class="berp-form-group berp-col-8">
					<input type="text" name="search" class="berp-form-control" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name...', 'BuildERP' ); ?>">
				</div>
				<div class="berp-form-group berp-col-4">
					<button type="submit" class="berp-btn berp-btn-primary"><?php esc_html_e( 'Search', 'BuildERP' ); ?></button>
				</div>
			</div>
		</form>

		<?php if ( $query->have_posts() ) : ?>
			<table class="berp-portal-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Employee ID', 'BuildERP' ); ?></th>
						<th><?php esc_html_e( 'Name', 'BuildERP' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'BuildERP' ); ?></th>
						<th><?php esc_html_e( 'Status', 'BuildERP' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$emp_id = get_post_meta( get_the_ID(), '_berp_employee_id', true );
						$phone  = get_post_meta( get_the_ID(), '_berp_employee_phone', true );
						?>
						<tr>
							<td><?php echo esc_html( $emp_id ); ?></td>
							<td><?php the_title(); ?></td>
							<td><?php echo esc_html( $phone ); ?></td>
							<td><span class="berp-badge berp-badge-success"><?php esc_html_e( 'Active', 'BuildERP' ); ?></span></td>
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
			<p><?php esc_html_e( 'No employees found.', 'BuildERP' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</div>

