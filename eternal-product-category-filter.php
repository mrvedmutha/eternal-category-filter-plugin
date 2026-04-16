<?php
/**
 * Plugin Name:       Eternal Product Category Filter
 * Plugin URI:        https://eternal.com/
 * Description:       Dynamic, category-specific product filters for WooCommerce. Configure filter groups per product category and assign filter values to products with union (OR) filtering logic.
 * Version:           1.0.0
 * Author:            Eternal
 * Author URI:        https://eternal.com/
 * Text Domain:       eternal-product-category-filter
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 *
 * @package Eternal_Product_Category_Filter
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Plugin constants.
define( 'ETERNAL_FILTER_VERSION', '1.0.0' );
define( 'ETERNAL_FILTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ETERNAL_FILTER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Eternal_Product_Category_Filter {

	/**
	 * Single instance of the class.
	 *
	 * @var Eternal_Product_Category_Filter|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return Eternal_Product_Category_Filter
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Load plugin after WooCommerce is loaded.
		add_action( 'woocommerce_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialise plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Load required files.
		$this->load_classes();

		// Initialize classes.
		new Eternal_Filter_Taxonomy_Registration();
		new Eternal_Filter_Admin_Category_UI();
		new Eternal_Filter_Admin_Product_UI();
		new Eternal_Filter_REST_API();
		new Eternal_Filter_Frontend();
	}

	/**
	 * Load required class files.
	 *
	 * @return void
	 */
	private function load_classes(): void {
		$classes = array(
			'class-taxonomy-registration',
			'class-admin-category-ui',
			'class-admin-product-ui',
			'class-rest-api',
			'class-frontend-filtering',
		);

		foreach ( $classes as $class_file ) {
			$file_path = ETERNAL_FILTER_PATH . 'inc/' . $class_file . '.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Eternal Product Category Filter requires WooCommerce to be installed and active.', 'eternal-product-category-filter' ); ?></p>
		</div>
		<?php
	}
}

// Initialize the plugin.
function eternal_product_category_filter() {
	return Eternal_Product_Category_Filter::get_instance();
}

// Start the plugin.
eternal_product_category_filter();
