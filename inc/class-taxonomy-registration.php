<?php
/**
 * Taxonomy Registration
 *
 * Registers the product_filter_value custom taxonomy for storing
 * filter values assigned to products.
 *
 * @package Eternal_Product_Category_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Eternal_Filter_Taxonomy_Registration
 */
class Eternal_Filter_Taxonomy_Registration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 10 );
	}

	/**
	 * Register the product_filter_value taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		$labels = array(
			'name'                       => _x( 'Filter Values', 'taxonomy general name', 'eternal-product-category-filter' ),
			'singular_name'              => _x( 'Filter Value', 'taxonomy singular name', 'eternal-product-category-filter' ),
			'search_items'               => __( 'Search Filter Values', 'eternal-product-category-filter' ),
			'popular_items'              => __( 'Popular Filter Values', 'eternal-product-category-filter' ),
			'all_items'                  => __( 'All Filter Values', 'eternal-product-category-filter' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Filter Value', 'eternal-product-category-filter' ),
			'view_item'                  => __( 'View Filter Value', 'eternal-product-category-filter' ),
			'update_item_item'           => __( 'Update Filter Value', 'eternal-product-category-filter' ),
			'add_new_item'               => __( 'Add New Filter Value', 'eternal-product-category-filter' ),
			'new_item_name'              => __( 'New Filter Value Name', 'eternal-product-category-filter' ),
			'separate_items_with_commas' => __( 'Separate filter values with commas', 'eternal-product-category-filter' ),
			'add_or_remove_items'        => __( 'Add or remove filter values', 'eternal-product-category-filter' ),
			'choose_from_most_used'      => __( 'Choose from the most used filter values', 'eternal-product-category-filter' ),
			'not_found'                  => __( 'No filter values found.', 'eternal-product-category-filter' ),
			'no_terms'                   => __( 'No filter values', 'eternal-product-category-filter' ),
			'menu_name'                  => __( 'Filter Values', 'eternal-product-category-filter' ),
			'items_list_navigation'      => __( 'Filter Values list navigation', 'eternal-product-category-filter' ),
			'items_list'                 => __( 'Filter Values list', 'eternal-product-category-filter' ),
			'most_used'                  => _x( 'Most Used', 'filter values', 'eternal-product-category-filter' ),
			'back_to_items'              => __( '&larr; Go to Filter Values', 'eternal-product-category-filter' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => false, // Hide admin UI - managed through category configuration
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'query_var'         => true,
			'rewrite'           => false,
			'capabilities'      => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_products',
			),
			'meta_box_cb'       => false, // We'll provide our own meta box.
		);

		register_taxonomy( 'product_filter_value', 'product', $args );
	}
}
