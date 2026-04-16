<?php
/**
 * Admin Product UI
 *
 * Handles the admin interface for assigning filter values
 * to products on the product edit screen.
 *
 * @package Eternal_Product_Category_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Eternal_Filter_Admin_Product_UI
 */
class Eternal_Filter_Admin_Product_UI {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add meta box to product edit screen.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		// Save filter assignments.
		add_action( 'save_post_product', array( $this, 'save_filter_assignments' ) );

		// AJAX handler for loading filter groups.
		add_action( 'wp_ajax_eternal_get_product_filter_groups', array( $this, 'ajax_get_filter_groups' ) );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add meta box to product edit screen.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function add_meta_box( $post_type ) {
		if ( 'product' === $post_type ) {
			add_meta_box(
				'eternal_product_filter_values',
				__( 'Category Filter Values', 'eternal-product-category-filter' ),
				array( $this, 'render_meta_box' ),
				'product',
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'eternal_product_filters_save', 'eternal_product_filters_nonce' );

		// Get product's categories.
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			echo '<p>' . esc_html__( 'This is not a valid product.', 'eternal-product-category-filter' ) . '</p>';
			return;
		}

		$category_ids = $product->get_category_ids();

		if ( empty( $category_ids ) ) {
			echo '<p>' . esc_html__( 'Assign this product to a category to see available filter values.', 'eternal-product-category-filter' ) . '</p>';
			return;
		}

		// Get all filter groups from product's categories.
		$filter_groups = $this->get_filter_groups_for_categories( $category_ids );

		if ( empty( $filter_groups ) ) {
			echo '<p>' . esc_html__( 'No filter groups configured for this product\'s categories.', 'eternal-product-category-filter' ) . '</p>';
			return;
		}

		// Get currently assigned filter values.
		$assigned_terms = wp_get_object_terms( $post->ID, 'product_filter_value' );
		$assigned_slugs = wp_list_pluck( $assigned_terms, 'slug' );

		echo '<div class="eternal-filter-accordion accordion" id="eternal-filter-accordion-container">';

		foreach ( $filter_groups as $group_index => $group ) {
			$is_first = 0 === $group_index;
			$aria_expanded = $is_first ? 'true' : 'false';
			$background_style = $is_first ? '#fff' : '#f9f9f9';
			$open_class = $is_first ? ' open' : '';
			$content_hidden = $is_first ? '' : 'hidden';

			echo '<div class="accordion-section eternal-accordion-section' . esc_attr( $open_class ) . '" data-group-index="' . esc_attr( $group_index ) . '">';
			echo '<button type="button" class="accordion-trigger eternal-accordion-trigger" aria-expanded="' . esc_attr( $aria_expanded ) . '" style="padding: 12px 15px !important; cursor: pointer; display: flex !important; flex-direction: row !important; align-items: center !important; gap: 8px !important; background: ' . esc_attr( $background_style ) . ' !important; transition: background-color 0.2s ease; border: none !important; border-radius: 0 !important; box-shadow: none !important; width: 100% !important; text-align: left !important; font-size: 13px !important; font-weight: 600 !important; color: #2271b1 !important; margin: 0 !important; line-height: 1.4 !important; min-height: auto !important; appearance: none !important;">';
			echo '<span class="accordion-title">' . esc_html( $group['group_name'] ) . '</span>';
			echo '<span class="accordion-icon" aria-hidden="true" style="flex-shrink: 0; width: 16px; height: 16px; position: relative; display: inline-block;"></span>';
			echo '</button>';

			echo '<div class="accordion-content eternal-accordion-content"' . $content_hidden . ' style="padding: 15px; border-top: 1px solid #eee; background: #fff; max-height: 300px; overflow-y: auto;">';

			if ( ! empty( $group['options'] ) ) {
				foreach ( $group['options'] as $option ) {
					$checked = in_array( $option['slug'], $assigned_slugs, true ) ? 'checked' : '';

					echo '<label style="display: block; margin-bottom: 6px; padding: 4px 0;">';
					echo '<input type="checkbox" ';
					echo 'name="eternal_filter_values[]" ';
					echo 'value="' . esc_attr( $option['slug'] ) . '" ';
					echo 'data-group-id="' . esc_attr( $group['group_id'] ) . '" ';
					echo 'data-option-id="' . esc_attr( $option['option_id'] ) . '" ';
					echo $checked . ' ';
					echo '/> ';
					echo '<span>' . esc_html( $option['name'] ) . '</span>';
					echo '</label>';
				}
			}

			if ( empty( $group['options'] ) ) {
				echo '<p style="color: #666; font-style: italic;">' . esc_html__( 'No options configured for this group.', 'eternal-product-category-filter' ) . '</p>';
			}

			echo '</div>'; // End accordion-content.
			echo '</div>'; // End accordion-section.
		}

		echo '</div>'; // End accordion-container.

		// Hidden product ID.
		echo '<input type="hidden" id="eternal-product-id" value="' . esc_attr( $post->ID ) . '">';

		// Loading state (hidden by default).
		echo '<div id="eternal-filters-loading" style="display: none;">';
		echo '<p class="description">' . esc_html__( 'Loading filter groups...', 'eternal-product-category-filter' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Get filter groups for given category IDs.
	 *
	 * @param array $category_ids Category IDs.
	 * @return array Filter groups.
	 */
	private function get_filter_groups_for_categories( $category_ids ) {
		$all_filter_groups = array();
		$seen_options = array();

		foreach ( $category_ids as $category_id ) {
			$filter_groups_json = get_term_meta( $category_id, 'category_filter_groups', true );
			if ( empty( $filter_groups_json ) ) {
				continue;
			}

			$filter_groups = json_decode( $filter_groups_json, true );
			if ( empty( $filter_groups ) || ! is_array( $filter_groups ) ) {
				continue;
			}

			foreach ( $filter_groups as $group ) {
				// Skip if no options.
				if ( empty( $group['options'] ) ) {
					continue;
				}

				// Add group if not already added.
				if ( ! isset( $all_filter_groups[ $group['group_id'] ] ) ) {
					$all_filter_groups[ $group['group_id'] ] = array(
						'group_id'   => $group['group_id'],
						'group_name' => $group['group_name'],
						'slug'       => $group['slug'],
						'options'    => array(),
					);
				}

				// Add options (avoid duplicates by slug).
				foreach ( $group['options'] as $option ) {
					$option_key = $group['group_id'] . '_' . $option['slug'];

					if ( ! isset( $seen_options[ $option_key ] ) ) {
						$all_filter_groups[ $group['group_id'] ]['options'][] = $option;
						$seen_options[ $option_key ] = true;
					}
				}
			}
		}

		return array_values( $all_filter_groups );
	}

	/**
	 * Save filter assignments when product is saved.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_filter_assignments( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['eternal_product_filters_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eternal_product_filters_nonce'] ) ), 'eternal_product_filters_save' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Clear existing filter value terms.
		wp_delete_object_term_relationships( $post_id, 'product_filter_value' );

		// Get selected filter values.
		if ( isset( $_POST['eternal_filter_values'] ) && is_array( $_POST['eternal_filter_values'] ) ) {
			$filter_values = array_map( 'sanitize_text_field', wp_unslash( $_POST['eternal_filter_values'] ) );

			if ( ! empty( $filter_values ) ) {
				// Assign terms to product.
				foreach ( $filter_values as $slug ) {
					// Check if term exists, if not create it.
					$term = get_term_by( 'slug', $slug, 'product_filter_value' );

					if ( ! $term ) {
						// Create new term.
						$term = wp_insert_term(
							$this->slug_to_name( $slug ),
							'product_filter_value',
							array( 'slug' => $slug )
						);

						if ( is_wp_error( $term ) ) {
							continue;
						}

						$term_id = $term['term_id'];
					} else {
						$term_id = $term->term_id;
					}

					// Assign term to product.
					wp_set_object_terms( $post_id, $term_id, 'product_filter_value', true );
				}
			}
		}
	}

	/**
	 * Convert slug to display name.
	 *
	 * @param string $slug Slug.
	 * @return string Name.
	 */
	private function slug_to_name( $slug ) {
		return str_replace( array( '-', '_' ), ' ', ucwords( $slug ) );
	}

	/**
	 * AJAX handler for loading filter groups dynamically.
	 *
	 * @return void
	 */
	public function ajax_get_filter_groups() {
		// Verify nonce.
		check_ajax_referer( 'eternal_load_filters', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'eternal-product-category-filter' ) ) );
		}

		// Get category IDs from request.
		$category_ids = isset( $_POST['category_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['category_ids'] ) ) : array();

		if ( empty( $category_ids ) ) {
			wp_send_json_success( array( 'filter_groups' => array() ) );
		}

		// Get filter groups.
		$filter_groups = $this->get_filter_groups_for_categories( $category_ids );

		wp_send_json_success( array( 'filter_groups' => $filter_groups ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on product edit screen.
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$post_type = get_post_type( get_the_ID() );
		if ( 'product' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'eternal-product-filter-admin',
			ETERNAL_FILTER_URL . 'assets/js/admin-product-assignment.js',
			array( 'jquery' ),
			ETERNAL_FILTER_VERSION,
			true
		);

		// Enqueue admin styles for accordion.
		wp_enqueue_style(
			'eternal-filter-admin',
			ETERNAL_FILTER_URL . 'assets/css/admin-filter-config.css',
			array(),
			ETERNAL_FILTER_VERSION,
			true
		);

		wp_localize_script(
			'eternal-product-filter-admin',
			'eternalProductFilterData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'eternal_load_filters' ),
				'strings'  => array(
					'noFilters'     => esc_html__( 'No filter groups configured for this product\'s categories.', 'eternal-product-category-filter' ),
					'assignToCategory' => esc_html__( 'Assign this product to a category to see available filter values.', 'eternal-product-category-filter' ),
					'loading'       => esc_html__( 'Loading filter groups...', 'eternal-product-category-filter' ),
				),
			)
		);
	}
}
