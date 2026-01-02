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
			'name'               => _x( 'Sites', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Site', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Sites/Projects', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Site', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Add New', 'BuildERP' ),
			'add_new_item'       => __( 'Add New Site', 'BuildERP' ),
			'new_item'           => __( 'New Site', 'BuildERP' ),
			'edit_item'          => __( 'Edit Site', 'BuildERP' ),
			'view_item'          => __( 'View Site', 'BuildERP' ),
			'all_items'          => __( 'All Sites', 'BuildERP' ),
			'search_items'       => __( 'Search Sites', 'BuildERP' ),
			'not_found'          => __( 'No sites found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No sites found in Trash.', 'BuildERP' ),
			'featured_image'     => _x( 'Site Image', 'Overrides the "Featured Image" phrase', 'BuildERP' ),
			'set_featured_image' => _x( 'Set site image', 'Overrides the "Set featured image" phrase', 'BuildERP' ),
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

