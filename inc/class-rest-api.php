<?php
/**
 * REST API
 *
 * Registers REST API endpoints for retrieving filter data.
 *
 * @package Eternal_Product_Category_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Eternal_Filter_REST_API
 */
class Eternal_Filter_REST_API {

	/**
	 * REST API namespace.
	 */
	const REST_NAMESPACE = 'eternal-filters/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/category/(?P<category_id>\d+)/filters',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_category_filters' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'category_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'Product category ID', 'eternal-product-category-filter' ),
					),
				),
			)
		);
	}

	/**
	 * Get filter groups for a category.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error REST response or error.
	 */
	public function get_category_filters( $request ) {
		$category_id = $request->get_param( 'category_id' );

		// Verify category exists.
		$category = get_term( $category_id, 'product_cat' );
		if ( ! $category || is_wp_error( $category ) ) {
			return new WP_Error(
				'category_not_found',
				__( 'Product category not found.', 'eternal-product-category-filter' ),
				array( 'status' => 404 )
			);
		}

		// Get filter groups from category meta.
		$filter_groups_json = get_term_meta( $category_id, 'category_filter_groups', true );
		$filter_groups = array();

		if ( ! empty( $filter_groups_json ) ) {
			$decoded = json_decode( $filter_groups_json, true );
			if ( is_array( $decoded ) ) {
				$filter_groups = $decoded;
			}
		}

		// Enhance with product counts for each option.
		foreach ( $filter_groups as &$group ) {
			if ( empty( $group['options'] ) ) {
				continue;
			}

			foreach ( $group['options'] as &$option ) {
				$option['count'] = $this->get_product_count_for_option( $category_id, $option['slug'] );
			}
		}

		// Prepare response.
		$response = array(
			'category_id'   => intval( $category_id ),
			'category_name' => $category->name,
			'category_slug' => $category->slug,
			'filter_groups' => $filter_groups,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get product count for a specific filter option.
	 *
	 * @param int    $category_id Category ID.
	 * @param string $option_slug Filter option slug.
	 * @return int Product count.
	 */
	private function get_product_count_for_option( $category_id, $option_slug ) {
		// Get term by slug.
		$term = get_term_by( 'slug', $option_slug, 'product_filter_value' );
		if ( ! $term ) {
			return 0;
		}

		// Query products in this category with this filter value.
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $category_id,
					'field'    => 'term_id',
				),
				array(
					'taxonomy' => 'product_filter_value',
					'terms'    => $term->term_id,
					'field'    => 'term_id',
				),
			),
		);

		$query = new WP_Query( $args );
		return intval( $query->found_posts );
	}
}
