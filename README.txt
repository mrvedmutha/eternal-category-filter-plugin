=== Eternal Product Category Filter ===
Contributors: eternal
Tags: woocommerce, filters, product categories, product filtering
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Dynamic, category-specific product filters for WooCommerce. Configure filter groups per product category and assign filter values to products with union (OR) filtering logic.

== Description ==

This plugin enables you to create dynamic, category-specific product filters for your WooCommerce store. Each product category can have its own set of filter groups (e.g., "Product Types", "Skin Type", "Benefits") with multiple options each.

Key Features:

* **Category-Specific Filters**: Configure different filter groups for each product category
* **Easy Administration**: Add filter groups and options through the product category edit screen
* **Product Assignment**: Assign filter values to products through the product edit screen
* **Union (OR) Logic**: Products matching ANY selected filter value are shown
* **REST API**: Built-in REST API for custom frontend integrations
* **Standalone Widget**: Display filters using the included widget or shortcode
* **URL-Based Filtering**: Filter state persists via URL parameters
* **Mobile Responsive**: Works with all WordPress themes

== Installation ==

1. Upload the `eternal-product-category-filter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the "Plugins" screen in WordPress
3. Configure filter groups on your product category edit screens
4. Assign filter values to your products

== Usage ==

### Configuring Filter Groups

1. Go to Products > Categories
2. Edit or create a product category
3. Scroll down to the "Product Filter Groups" section
4. Add filter groups (e.g., "Product Types", "Skin Type")
5. Add options to each filter group (e.g., "Face Creme", "Body Oil")
6. Save the category

### Assigning Filter Values to Products

1. Edit a product
2. In the right sidebar, find the "Category Filter Values" meta box
3. Select the filter values that apply to this product
4. Update/save the product

### Displaying Filters

**Using the Widget:**
1. Go to Appearance > Widgets
2. Add the "Product Category Filters" widget to a sidebar
3. The widget will automatically display filters for the current category

**Using the Shortcode:**
```
[eternal_product_filters]
```

**Theme Integration:**
For custom theme integration, use the REST API endpoint:
`GET /wp-json/eternal-filters/v1/category/{category_id}/filters`

== Filter Logic ==

This plugin uses union (OR) logic by default:
- When a user selects "Face Creme" AND "Dry Skin"
- Products matching EITHER "Face Creme" OR "Dry Skin" (or both) will be shown
- This maximizes product discoverability and is standard for e-commerce

== URL Parameters ==

Filters are stored in the URL as:
`?eternal_filter=value1,value2,value3`

This allows users to bookmark, share, and revisit filtered results.

== REST API ==

The plugin provides a REST API endpoint for custom integrations:

**Endpoint:** `GET /wp-json/eternal-filters/v1/category/{category_id}/filters`

**Response Format:**
```json
{
  "category_id": 123,
  "category_name": "Skincare",
  "category_slug": "skincare",
  "filter_groups": [
    {
      "group_id": "group_0",
      "group_name": "Product Types",
      "slug": "product-types",
      "options": [
        {
          "option_id": "opt_0_0",
          "name": "Face Creme",
          "slug": "face-creme",
          "order": 1,
          "count": 15
        }
      ]
    }
  ]
}
```

== Frequently Asked Questions ==

= Can I use the same filter values across different categories? =

Yes! Filter values are stored in a global taxonomy and can be reused across multiple categories.

= Does this work with page builders? =

Yes, you can use the shortcode `[eternal_product_filters]` in most page builders. For advanced integration, use the REST API endpoint.

= Can I customize the filter display? =

Yes! The plugin provides basic styling. For custom styling, you can use CSS classes provided or build a custom integration using the REST API.

= What happens if a product is in multiple categories? =

The product will show filter values from all its assigned categories. The plugin correctly handles this scenario.

= Does this work with WooCommerce block themes? =

Yes! The plugin is compatible with both classic and block-based WooCommerce themes.

== Changelog ==

= 1.0.0 =
* Initial release
* Category-specific filter group configuration
* Product filter value assignment
* REST API for frontend integration
* Union (OR) filter logic
* Standalone widget and shortcode
* URL-based filtering

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade notices required.
