<?php
/**
 * Taxonomy helper functions.
 *
 * @package BuildERP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function berp_ensure_taxonomy_exists( $taxonomy ) {
	return taxonomy_exists( $taxonomy );
}

/* =============================
 * Expense categories
 * ===========================*/

function berp_expense_category_exists( $term ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_expense_category' ) ) {
		return false;
	}
	return term_exists( $term, 'berp_expense_category' );
}

function berp_get_expense_categories( $args = array() ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_expense_category' ) ) {
		return array();
	}
	$defaults = array(
		'taxonomy'   => 'berp_expense_category',
		'hide_empty' => false,
	);
	return get_terms( wp_parse_args( $args, $defaults ) );
}

function berp_get_post_expense_categories( $post_id ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_expense_category' ) ) {
		return array();
	}
	$terms = get_the_terms( $post_id, 'berp_expense_category' );
	return is_array( $terms ) ? $terms : array();
}

function berp_set_post_expense_categories( $post_id, $terms, $append = false ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_expense_category' ) ) {
		return array();
	}
	return wp_set_post_terms( $post_id, $terms, 'berp_expense_category', $append );
}

/* =============================
 * Departments
 * ===========================*/

function berp_department_exists( $term ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_department' ) ) {
		return false;
	}
	return term_exists( $term, 'berp_department' );
}

function berp_get_departments( $args = array() ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_department' ) ) {
		return array();
	}
	$defaults = array(
		'taxonomy'   => 'berp_department',
		'hide_empty' => false,
	);
	return get_terms( wp_parse_args( $args, $defaults ) );
}

function berp_get_post_departments( $post_id ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_department' ) ) {
		return array();
	}
	$terms = get_the_terms( $post_id, 'berp_department' );
	return is_array( $terms ) ? $terms : array();
}

function berp_set_post_departments( $post_id, $terms, $append = false ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_department' ) ) {
		return array();
	}
	return wp_set_post_terms( $post_id, $terms, 'berp_department', $append );
}

/* =============================
 * Project statuses
 * ===========================*/

function berp_project_status_exists( $term ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_project_status' ) ) {
		return false;
	}
	return term_exists( $term, 'berp_project_status' );
}

function berp_get_project_statuses( $args = array() ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_project_status' ) ) {
		return array();
	}
	$defaults = array(
		'taxonomy'   => 'berp_project_status',
		'hide_empty' => false,
	);
	return get_terms( wp_parse_args( $args, $defaults ) );
}

function berp_get_post_project_statuses( $post_id ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_project_status' ) ) {
		return array();
	}
	$terms = get_the_terms( $post_id, 'berp_project_status' );
	return is_array( $terms ) ? $terms : array();
}

function berp_set_post_project_statuses( $post_id, $terms, $append = false ) {
	if ( ! berp_ensure_taxonomy_exists( 'berp_project_status' ) ) {
		return array();
	}
	return wp_set_post_terms( $post_id, $terms, 'berp_project_status', $append );
}
