<?php
/**
 * Frontend Filtering
 *
 * Handles frontend product filtering logic, URL parameter parsing,
 * and shortcode/widget for displaying filters.
 *
 * @package Eternal_Product_Category_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Eternal_Filter_Frontend
 */
class Eternal_Filter_Frontend {

	/**
	 * REST API namespace.
	 */
	const REST_NAMESPACE = 'eternal-filters/v1';

	/**
	 * URL parameter name for filters.
	 */
	const FILTER_PARAM = 'eternal_filter';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Modify product query based on URL parameters.
		add_action( 'woocommerce_product_query', array( $this, 'filter_product_query' ), 10, 2 );

		// Enqueue frontend scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Register shortcode for standalone filter display.
		add_shortcode( 'eternal_product_filters', array( $this, 'render_filter_shortcode' ) );

		// Register widget.
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Filter product query based on selected filter values.
	 *
	 * @param WP_Query $query WP Query object.
	 * @param WC_Query $wc_query WooCommerce Query object.
	 * @return void
	 */
	public function filter_product_query( $query, $wc_query ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Only filter on product category archives.
		if ( ! is_product_category() ) {
			return;
		}

		// Get selected filters from URL.
		$selected_filters = $this->get_selected_filters_from_url();

		if ( empty( $selected_filters ) ) {
			return;
		}

		// Build taxonomy query for union (OR) logic.
		$tax_query = array(
			'taxonomy' => 'product_filter_value',
			'field'    => 'slug',
			'terms'    => $selected_filters,
			'operator' => 'IN', // Union (OR) - match ANY of the selected values
		);

		// Get existing tax query.
		$existing_tax_query = $query->get( 'tax_query' );
		if ( empty( $existing_tax_query ) ) {
			$existing_tax_query = array();
		}

		// Add our filter query while preserving the category query.
		$existing_tax_query[] = $tax_query;
		$query->set( 'tax_query', $existing_tax_query );
	}

	/**
	 * Get selected filter values from URL.
	 *
	 * @return array Array of filter slugs.
	 */
	private function get_selected_filters_from_url() {
		$filters = isset( $_GET[ self::FILTER_PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::FILTER_PARAM ] ) ) : '';

		if ( empty( $filters ) ) {
			return array();
		}

		// Split by comma to get multiple filter values.
		$filter_array = explode( ',', $filters );
		return array_map( 'sanitize_title', $filter_array );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Only load on product category pages.
		if ( ! is_product_category() ) {
			return;
		}

		// Get current category.
		$category = get_queried_object();
		if ( ! $category || ! isset( $category->term_id ) ) {
			return;
		}

		// Check if category has filter groups configured.
		$filter_groups = get_term_meta( $category->term_id, 'category_filter_groups', true );
		if ( empty( $filter_groups ) ) {
			return;
		}

		// Enqueue frontend script.
		wp_enqueue_script(
			'eternal-filters-frontend',
			ETERNAL_FILTER_URL . 'assets/js/frontend-filtering.js',
			array(),
			ETERNAL_FILTER_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'eternal-filters-frontend',
			'eternalFiltersData',
			array(
				'categoryId'    => $category->term_id,
				'apiEndpoint'   => rest_url( self::REST_NAMESPACE . '/category/' . $category->term_id . '/filters' ),
				'filterParam'   => self::FILTER_PARAM,
				'currentFilters' => $this->get_selected_filters_from_url(),
				'strings'        => array(
					'clearAll'      => __( 'Clear All', 'eternal-product-category-filter' ),
					'loadFilters'   => __( 'Load Filters', 'eternal-product-category-filter' ),
					'loading'       => __( 'Loading filters...', 'eternal-product-category-filter' ),
					'noFilters'     => __( 'No filters available for this category.', 'eternal-product-category-filter' ),
					'errorLoading'  => __( 'Error loading filters. Please try again.', 'eternal-product-category-filter' ),
				),
			)
		);
	}

	/**
	 * Render filter shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_filter_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'category_id' => 0,
				'title'       => __( 'Filter Products', 'eternal-product-category-filter' ),
			),
			$atts
		);

		// Determine category ID.
		$category_id = intval( $atts['category_id'] );

		if ( empty( $category_id ) && is_product_category() ) {
			$category = get_queried_object();
			$category_id = $category->term_id;
		}

		if ( empty( $category_id ) ) {
			return '';
		}

		// Start output buffering.
		ob_start();

		// Enqueue scripts.
		$this->enqueue_frontend_assets();

		// Render filter container.
		echo '<div class="eternal-product-filters-widget" data-category-id="' . esc_attr( $category_id ) . '">';

		if ( ! empty( $atts['title'] ) ) {
			echo '<h3>' . esc_html( $atts['title'] ) . '</h3>';
		}

		// Filters will be loaded dynamically by JavaScript.
		echo '<div class="eternal-filters-container" id="eternal-filters-container">';
		echo '<p class="eternal-filters-loading">' . esc_html( eternalFiltersData.strings.loading ) . '</p>';
		echo '</div>';

		// Clear all button.
		$clear_url = remove_query_arg( self::FILTER_PARAM );
		echo '<p><a href="' . esc_url( $clear_url ) . '" class="eternal-filters-clear-all">' . esc_html( eternalFiltersData.strings.clearAll ) . '</a></p>';

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Register widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'Eternal_Filter_Widget' );
	}
}

/**
 * Filter Widget Class
 */
class Eternal_Filter_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'eternal_product_filters_widget',
			'description' => __( 'Display product filters for the current category.', 'eternal-product-category-filter' ),
		);

		parent::__construct(
			'eternal_product_filters',
			__( 'Product Category Filters', 'eternal-product-category-filter' ),
			$widget_ops
		);
	}

	/**
	 * Widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $args['after_title'];
		}

		// Render shortcode output.
		echo do_shortcode( '[eternal_product_filters]' );

		echo $args['after_widget'];
	}

	/**
	 * Widget form.
	 *
	 * @param array $instance Widget instance.
	 * @return string|void
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Filter Products', 'eternal-product-category-filter' );
		$field_id = $this->get_field_id( 'title' );
		$field_name = $this->get_field_name( 'title' );
		?>
		<p>
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Title:', 'eternal-product-category-filter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	/**
	 * Update widget instance.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array Updated instance.
	 */
	public function update( $new_instance, $old_instance ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$instance = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		return $instance;
	}
}
