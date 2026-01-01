<?php
/**
 * Site Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Site_CPT Class
 *
 * Registers and manages the Site/Project custom post type
 */
class BERP_Site_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Sites', 'Post type general name', 'aic_builderp' ),
			'singular_name'      => _x( 'Site', 'Post type singular name', 'aic_builderp' ),
			'menu_name'          => _x( 'Sites/Projects', 'Admin Menu text', 'aic_builderp' ),
			'name_admin_bar'     => _x( 'Site', 'Add New on Toolbar', 'aic_builderp' ),
			'add_new'            => __( 'Add New', 'aic_builderp' ),
			'add_new_item'       => __( 'Add New Site', 'aic_builderp' ),
			'new_item'           => __( 'New Site', 'aic_builderp' ),
			'edit_item'          => __( 'Edit Site', 'aic_builderp' ),
			'view_item'          => __( 'View Site', 'aic_builderp' ),
			'all_items'          => __( 'All Sites', 'aic_builderp' ),
			'search_items'       => __( 'Search Sites', 'aic_builderp' ),
			'not_found'          => __( 'No sites found.', 'aic_builderp' ),
			'not_found_in_trash' => __( 'No sites found in Trash.', 'aic_builderp' ),
			'featured_image'     => _x( 'Site Image', 'Overrides the "Featured Image" phrase', 'aic_builderp' ),
			'set_featured_image' => _x( 'Set site image', 'Overrides the "Set featured image" phrase', 'aic_builderp' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'site' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_sites',
				'read_post'          => 'berp_view_sites',
				'delete_post'        => 'berp_delete_sites',
				'edit_posts'         => 'berp_edit_sites',
				'edit_others_posts'  => 'berp_manage_sites',
				'delete_posts'       => 'berp_delete_sites',
				'publish_posts'      => 'berp_manage_sites',
				'read_private_posts' => 'berp_manage_sites',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-building',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_site', $args );
	}
}
