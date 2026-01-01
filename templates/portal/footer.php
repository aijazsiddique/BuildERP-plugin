<?php
/**
 * Portal Footer Template.
 *
 * @package BuildErp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		</main>
	</div>
	<footer class="berp-portal-footer">
		<p>&copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'aic_builderp' ); ?></p>
	</footer>
</div>
