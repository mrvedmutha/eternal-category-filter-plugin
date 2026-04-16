<?php
/**
 * Admin Category UI
 *
 * Handles the admin interface for configuring filter groups
 * on product category edit pages.
 *
 * @package Eternal_Product_Category_Filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Eternal_Filter_Admin_Category_UI
 */
class Eternal_Filter_Admin_Category_UI {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add filter fields to product category taxonomy.
		add_action( 'product_cat_add_form_fields', array( $this, 'add_filter_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'edit_filter_fields' ), 10, 2 );

		// Save filter fields.
		add_action( 'created_product_cat', array( $this, 'save_filter_fields' ) );
		add_action( 'edited_product_cat', array( $this, 'save_filter_fields' ) );

		// Enqueue scripts for repeater functionality.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// AJAX handler for generating slugs.
		add_action( 'wp_ajax_eternal_generate_slug', array( $this, 'ajax_generate_slug' ) );
	}

	/**
	 * Add filter fields to the "Add New" category form.
	 *
	 * @return void
	 */
	public function add_filter_fields() {
		wp_nonce_field( 'eternal_filter_save', 'eternal_filter_nonce' );
		?>
		<div class="form-field term-filter-groups-wrap">
			<label for="filter-groups"><?php esc_html_e( 'Product Filter Groups', 'eternal-product-category-filter' ); ?></label>
			<p class="description"><?php esc_html_e( 'Define filter groups for this product category (e.g., Product Types, Skin Type, Benefits). Each filter group can have multiple options.', 'eternal-product-category-filter' ); ?></p>
			<div id="filter-groups-container">
				<div class="filter-group-row" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
					<div class="filter-group-header" style="margin-bottom: 10px;">
						<label style="font-weight: bold; color: #0073aa;"><?php esc_html_e( 'Filter Group Name', 'eternal-product-category-filter' ); ?></label>
						<input type="text" name="filter_groups[0][group_name]" class="filter-group-name" style="width: 100%;" placeholder="<?php esc_attr_e( 'e.g., Product Types', 'eternal-product-category-filter' ); ?>" data-group-index="0">
					</div>
					<div class="filter-group-options" style="margin-top: 15px;">
						<label style="font-weight: bold; color: #0073aa; margin-bottom: 5px; display: block;"><?php esc_html_e( 'Filter Options', 'eternal-product-category-filter' ); ?></label>
						<div class="filter-options-list" data-group-index="0">
							<!-- Options will be added here -->
						</div>
						<button type="button" class="button add-filter-option-button" data-group-index="0" style="margin-top: 10px;">
							<?php esc_html_e( '+ Add Option', 'eternal-product-category-filter' ); ?>
						</button>
					</div>
				</div>
			</div>
			<button type="button" class="button" id="add-filter-group-button" style="margin-top: 10px;">
				<?php esc_html_e( '+ Add Filter Group', 'eternal-product-category-filter' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Add filter fields to the "Edit" category form.
	 *
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function edit_filter_fields( $term, $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Get existing filter groups.
		$filter_groups_json = get_term_meta( $term->term_id, 'category_filter_groups', true );
		$filter_groups = array();

		if ( ! empty( $filter_groups_json ) ) {
			$decoded = json_decode( $filter_groups_json, true );
			if ( is_array( $decoded ) ) {
				$filter_groups = $decoded;
			}
		}

		wp_nonce_field( 'eternal_filter_save', 'eternal_filter_nonce' );
		?>
		<tr class="form-field term-filter-groups-wrap">
			<th scope="row"><label for="filter-groups"><?php esc_html_e( 'Product Filter Groups', 'eternal-product-category-filter' ); ?></label></th>
			<td>
				<p class="description"><?php esc_html_e( 'Define filter groups for this product category (e.g., Product Types, Skin Type, Benefits). Each filter group can have multiple options.', 'eternal-product-category-filter' ); ?></p>
				<div id="filter-groups-container">
					<?php if ( ! empty( $filter_groups ) ) : ?>
						<?php foreach ( $filter_groups as $group_index => $group ) : ?>
							<div class="filter-group-row" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
								<div class="filter-group-header" style="margin-bottom: 10px;">
									<div style="display: flex; justify-content: space-between; align-items: center;">
										<div style="flex: 1;">
											<label style="font-weight: bold; color: #0073aa;"><?php esc_html_e( 'Filter Group Name', 'eternal-product-category-filter' ); ?></label>
											<input type="text" name="filter_groups[<?php echo esc_attr( $group_index ); ?>][group_name]" class="filter-group-name" style="width: 100%;" placeholder="<?php esc_attr_e( 'e.g., Product Types', 'eternal-product-category-filter' ); ?>" value="<?php echo esc_attr( $group['group_name'] ); ?>" data-group-index="<?php echo esc_attr( $group_index ); ?>">
										</div>
										<button type="button" class="button remove-filter-group-button" data-group-index="<?php echo esc_attr( $group_index ); ?>" style="margin-left: 10px; color: #a00;">
											<?php esc_html_e( 'Remove Group', 'eternal-product-category-filter' ); ?>
										</button>
									</div>
								</div>
								<div class="filter-group-options" style="margin-top: 15px;">
									<label style="font-weight: bold; color: #0073aa; margin-bottom: 5px; display: block;"><?php esc_html_e( 'Filter Options', 'eternal-product-category-filter' ); ?></label>
									<div class="filter-options-list" data-group-index="<?php echo esc_attr( $group_index ); ?>">
										<?php if ( ! empty( $group['options'] ) ) : ?>
											<?php foreach ( $group['options'] as $option_index => $option ) : ?>
												<div class="filter-option-row" style="margin-bottom: 10px; padding: 10px; border: 1px solid #e0e0e0; background: #fff;">
													<div style="display: flex; gap: 10px;">
														<div style="flex: 1;">
															<label style="font-size: 12px; color: #666;"><?php esc_html_e( 'Option Name', 'eternal-product-category-filter' ); ?></label>
															<input type="text" name="filter_groups[<?php echo esc_attr( $group_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][name]" class="filter-option-name" style="width: 100%;" placeholder="<?php esc_attr_e( 'e.g., Face Creme', 'eternal-product-category-filter' ); ?>" value="<?php echo esc_attr( $option['name'] ); ?>">
														</div>
														<div style="width: 80px;">
															<label style="font-size: 12px; color: #666;"><?php esc_html_e( 'Order', 'eternal-product-category-filter' ); ?></label>
															<input type="number" name="filter_groups[<?php echo esc_attr( $group_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][order]" class="filter-option-order" style="width: 100%;" value="<?php echo esc_attr( $option['order'] ?? $option_index + 1 ); ?>" min="1">
														</div>
														<div>
															<button type="button" class="button remove-filter-option-button" data-group-index="<?php echo esc_attr( $group_index ); ?>" data-option-index="<?php echo esc_attr( $option_index ); ?>" style="margin-top: 15px; padding: 5px 10px; font-size: 12px; color: #a00;">
																<?php esc_html_e( 'Remove', 'eternal-product-category-filter' ); ?>
															</button>
														</div>
													</div>
												</div>
											<?php endforeach; ?>
										<?php endif; ?>
									</div>
									<button type="button" class="button add-filter-option-button" data-group-index="<?php echo esc_attr( $group_index ); ?>" style="margin-top: 10px;">
										<?php esc_html_e( '+ Add Option', 'eternal-product-category-filter' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<!-- Empty state will be handled by JS -->
					<?php endif; ?>
				</div>
				<button type="button" class="button" id="add-filter-group-button" style="margin-top: 10px;">
					<?php esc_html_e( '+ Add Filter Group', 'eternal-product-category-filter' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save filter fields when category is saved.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_filter_fields( $term_id ) {
		// Verify nonce for security.
		if ( ! isset( $_POST['eternal_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eternal_filter_nonce'] ) ), 'eternal_filter_save' ) ) {
			return;
		}

		// Check if filter groups data was submitted.
		if ( isset( $_POST['filter_groups'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Items sanitized individually below.
			$filter_groups = array();
			$filter_data = wp_unslash( $_POST['filter_groups'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Items sanitized individually below.

			if ( is_array( $filter_data ) ) {
				foreach ( $filter_data as $group_index => $group ) {
					if ( empty( $group['group_name'] ) ) {
						continue;
					}

					$filter_group = array(
						'group_id'   => 'group_' . $group_index,
						'group_name' => sanitize_text_field( $group['group_name'] ),
						'slug'       => sanitize_title( $group['group_name'] ),
						'options'    => array(),
					);

					// Process options.
					if ( ! empty( $group['options'] ) && is_array( $group['options'] ) ) {
						foreach ( $group['options'] as $option_index => $option ) {
							if ( empty( $option['name'] ) ) {
								continue;
							}

							$filter_group['options'][] = array(
								'option_id' => 'opt_' . $group_index . '_' . $option_index,
								'name'      => sanitize_text_field( $option['name'] ),
								'slug'       => sanitize_title( $option['name'] ),
								'order'     => isset( $option['order'] ) ? max( 1, intval( $option['order'] ) ) : $option_index + 1,
							);
						}
					}

					// Sort options by order field.
					usort( $filter_group['options'], function( $a, $b ) {
						return $a['order'] - $b['order'];
					} );

					$filter_groups[] = $filter_group;
				}
			}

			// Save filter groups data.
			update_term_meta( $term_id, 'category_filter_groups', wp_json_encode( $filter_groups ) );
		} else {
			// If no filter groups submitted, delete existing data.
			delete_term_meta( $term_id, 'category_filter_groups' );
		}
	}

	/**
	 * Enqueue admin scripts for repeater functionality.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on taxonomy edit screens.
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}

		// Get current taxonomy.
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reading taxonomy name for screen check.
		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		wp_enqueue_script(
			'eternal-filter-config',
			ETERNAL_FILTER_URL . 'assets/js/admin-filter-config.js',
			array( 'jquery' ),
			ETERNAL_FILTER_VERSION,
			true
		);

		// Enqueue admin styles.
		wp_enqueue_style(
			'eternal-filter-config',
			ETERNAL_FILTER_URL . 'assets/css/admin-filter-config.css',
			array(),
			ETERNAL_FILTER_VERSION,
			true
		);

		wp_localize_script(
			'eternal-filter-config',
			'eternalFilterData',
			array(
				'addGroupText'         => esc_html__( '+ Add Filter Group', 'eternal-product-category-filter' ),
				'addOptionText'        => esc_html__( '+ Add Option', 'eternal-product-category-filter' ),
				'removeGroupText'      => esc_html__( 'Remove Group', 'eternal-product-category-filter' ),
				'removeOptionText'     => esc_html__( 'Remove', 'eternal-product-category-filter' ),
				'groupNamePlaceholder' => esc_attr__( 'e.g., Product Types', 'eternal-product-category-filter' ),
				'optionNamePlaceholder' => esc_attr__( 'e.g., Face Creme', 'eternal-product-category-filter' ),
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'eternal_generate_slug' ),
			)
		);
	}

	/**
	 * AJAX handler for generating slugs.
	 *
	 * @return void
	 */
	public function ajax_generate_slug() {
		// Verify nonce.
		check_ajax_referer( 'eternal_generate_slug', 'nonce' );

		// Check permissions.
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'eternal-product-category-filter' ) ) );
		}

		// Get and sanitize the text.
		$text = isset( $_POST['text'] ) ? sanitize_text_field( wp_unslash( $_POST['text'] ) ) : '';

		if ( empty( $text ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Text is required.', 'eternal-product-category-filter' ) ) );
		}

		// Generate slug.
		$slug = sanitize_title( $text );

		wp_send_json_success( array( 'slug' => $slug ) );
	}
}
