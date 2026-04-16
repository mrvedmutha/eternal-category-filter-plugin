# Eternal Product Category Filter - WPRig Theme Integration Plan

## Overview

This document outlines the implementation plan for integrating the **Eternal Product Category Filter** plugin with the **WPRig** theme. The plugin is now fully functional and standalone; this integration will replace the static filter UI with dynamic data from the plugin.

**Status**: Plugin Development Complete ✅  
**Next Phase**: Theme Integration (This Document)

---

## Prerequisites

### Plugin Requirements
- [x] Plugin installed and activated
- [x] REST API endpoint accessible: `/wp-json/eternal-filters/v1/category/{id}/filters`
- [x] Filter groups configured in at least one product category
- [x] Products assigned filter values
- [x] Widget and shortcode available: `[eternal_product_filters]`

### Theme Files to Modify
1. `/Users/wingsdino/Studio/eternal/wp-content/themes/wprig/template-parts/product-listing/filters-sidebar.php`
2. `/Users/wingsdino/Studio/eternal/wp-content/themes/wprig/assets/js/src/product-listing.ts`
3. `/Users/wingsdino/Studio/eternal/wp-content/themes/wprig/inc/Product_Listing/Component.php`

---

## Integration Strategy

### Phase 1: Template Integration (PHP)

#### File: `template-parts/product-listing/filters-sidebar.php`

**Current State**: Static HTML with hardcoded filter groups

**Target State**: Dynamic data loading from plugin REST API

```php
<?php
/**
 * Product Filters Sidebar - Dynamic Version
 * 
 * Loads filter data from Eternal Product Category Filter plugin
 * Falls back to static filters if plugin is inactive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if current category has filters configured
$current_category = get_queried_object();
$has_plugin_filters = false;

if ( $current_category && function_exists( 'eternal_filters_get_data' ) ) {
	$filter_data = eternal_filters_get_data( $current_category->term_id );
	$has_plugin_filters = ! empty( $filter_data['filter_groups'] );
}

if ( $has_plugin_filters ) : 
	// Use plugin's filter widget for consistent styling
	?>
	<div class="product-filters-wrapper">
		<?php 
		// Use plugin's widget function or shortcode
		echo do_shortcode( '[eternal_product_filters]' );
		?>
	</div>
<?php else : 
	// Fallback to static filters (existing code)
	?>
	<!-- Static filter HTML goes here -->
<?php endif; ?>
```

**Key Changes**:
1. Check for plugin availability
2. Call plugin's data retrieval function
3. Use plugin's widget/shortcode for rendering
4. Maintain existing static HTML as fallback

---

### Phase 2: JavaScript Integration (TypeScript)

#### File: `assets/js/src/product-listing.ts`

**Current State**: Custom filter handling with static data

**Target State**: Integration with plugin's REST API and events

```typescript
/**
 * Product Listing Component - Enhanced with Plugin Integration
 */

interface FilterGroup {
  group_id: string;
  group_name: string;
  slug: string;
  options: FilterOption[];
}

interface FilterOption {
  option_id: string;
  name: string;
  slug: string;
  count: number;
}

class ProductListing {
  private pluginActive: boolean = false;
  private filterEndpoint: string = '';
  private currentCategoryId: number = 0;

  constructor() {
    this.detectPlugin();
    if ( this.pluginActive ) {
      this.initializePluginFilters();
    } else {
      this.initializeStaticFilters();
    }
  }

  private detectPlugin(): void {
    // Check if plugin's data is available in global scope
    this.pluginActive = typeof window.eternalFiltersData !== 'undefined';
    
    if ( this.pluginActive ) {
      this.filterEndpoint = window.eternalFiltersData.endpoint;
      this.currentCategoryId = window.eternalFiltersData.categoryId;
    }
  }

  private async initializePluginFilters(): Promise<void> {
    try {
      // Fetch filter data from REST API
      const response = await fetch( `${this.filterEndpoint}${this.currentCategoryId}/filters` );
      const data = await response.json();
      
      if ( data.filter_groups && data.filter_groups.length > 0 ) {
        this.renderPluginFilters( data.filter_groups );
        this.setupPluginFilterListeners();
      }
    } catch ( error ) {
      console.error( 'Failed to load filters:', error );
      this.initializeStaticFilters(); // Fallback
    }
  }

  private renderPluginFilters( filterGroups: FilterGroup[] ): void {
    const container = document.querySelector( '.product-filters-container' );
    if ( ! container ) return;

    // Clear existing content
    container.innerHTML = '';

    // Render filter groups
    filterGroups.forEach( ( group, index ) => {
      const groupElement = this.createFilterGroupElement( group, index );
      container.appendChild( groupElement );
    } );

    // Initialize first accordion as open
    const firstGroup = container.querySelector( '.filter-group' );
    if ( firstGroup ) {
      firstGroup.classList.add( 'open' );
    }
  }

  private createFilterGroupElement( group: FilterGroup, index: number ): HTMLElement {
    const groupDiv = document.createElement( 'div' );
    groupDiv.className = 'filter-group';
    groupDiv.setAttribute( 'data-group-id', group.group_id );

    // Create group header
    const header = document.createElement( 'div' );
    header.className = 'filter-group-header';
    header.innerHTML = `
      <h3>${group.group_name}</h3>
      <span class="accordion-icon"></span>
    `;
    
    // Toggle functionality
    header.addEventListener( 'click', () => {
      groupDiv.classList.toggle( 'open' );
    });

    // Create options list
    const optionsList = document.createElement( 'div' );
    optionsList.className = 'filter-group-options';

    group.options.forEach( ( option ) => {
      const optionLabel = document.createElement( 'label' );
      optionLabel.className = 'filter-option';
      optionLabel.innerHTML = `
        <input type="checkbox" 
               name="eternal_filter[]" 
               value="${option.slug}" 
               data-group-id="${group.group_id}">
        <span class="option-name">${option.name}</span>
        <span class="option-count">(${option.count})</span>
      `;
      
      // Change handler
      optionLabel.querySelector( 'input' ).addEventListener( 'change', ( e ) => {
        this.handleFilterChange( e.target as HTMLInputElement );
      });

      optionsList.appendChild( optionLabel );
    } );

    groupDiv.appendChild( header );
    groupDiv.appendChild( optionsList );

    return groupDiv;
  }

  private handleFilterChange( checkbox: HTMLInputElement ): void {
    // Get all selected filters
    const selectedFilters = Array.from( 
      document.querySelectorAll( 'input[name="eternal_filter[]"]:checked' )
    ).map( input => input.value );

    // Update URL parameters
    this.updateURLParams( selectedFilters );

    // Trigger product grid refresh
    this.refreshProductGrid( selectedFilters );
  }

  private updateURLParams( filters: string[] ): void {
    const url = new URL( window.location.href );
    
    if ( filters.length > 0 ) {
      url.searchParams.set( 'eternal_filter', filters.join( ',' ) );
    } else {
      url.searchParams.delete( 'eternal_filter' );
    }

    window.history.pushState( {}, '', url.toString() );
  }

  private async refreshProductGrid( filters: string[] ): Promise<void> {
    // Trigger WooCommerce product query update
    // The plugin handles the actual filtering via woocommerce_product_query hook
    // We just need to trigger a page reload or AJAX refresh
    
    window.location.reload();
  }

  private setupPluginFilterListeners(): void {
    // Listen for URL changes (back button)
    window.addEventListener( 'popstate', () => {
      this.loadFiltersFromURL();
    } );

    // Load filters from URL on page load
    this.loadFiltersFromURL();
  }

  private loadFiltersFromURL(): void {
    const urlParams = new URLSearchParams( window.location.search );
    const filterParam = urlParams.get( 'eternal_filter' );

    if ( filterParam ) {
      const filters = filterParam.split( ',' );
      filters.forEach( ( filterValue ) => {
        const checkbox = document.querySelector( `input[value="${filterValue}"]` );
        if ( checkbox ) {
          checkbox.checked = true;
        }
      } );
    }
  }

  private initializeStaticFilters(): void {
    // Existing static filter implementation
    console.log( 'Using static filters (plugin not active)' );
  }
}

// Initialize on DOM ready
document.addEventListener( 'DOMContentLoaded', () => {
  new ProductListing();
});
```

**Key Changes**:
1. Detect if plugin is active
2. Fetch filter data from REST API
3. Render dynamic filters with existing theme styling
4. Handle filter changes and URL updates
5. Maintain fallback to static filters
6. Preserve existing theme styling and UX

---

### Phase 3: Component Integration (PHP)

#### File: `inc/Product_Listing/Component.php`

**Add dependency check and data localization**:

```php
<?php
/**
 * Product Listing Component - Enhanced with Plugin Integration
 */

class Product_Listing_Component {

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( $this, 'localize_plugin_data' ) );
	}

	public function enqueue_scripts() {
		if ( ! is_product_category() ) {
			return;
		}

		// Check if plugin is active
		if ( ! class_exists( 'Eternal_Product_Category_Filter' ) ) {
			return;
		}

		// Localize plugin data for JavaScript
		wp_localize_script( 'wprig-product-listing', 'eternalFiltersData', array(
			'endpoint'  => rest_url( 'eternal-filters/v1/category/' ),
			'categoryId' => get_queried_object_id(),
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'eternal-filters' ),
		) );
	}

	public function localize_plugin_data() {
		if ( ! is_product_category() ) {
			return;
		}

		// Check if plugin is active and current category has filters
		if ( ! class_exists( 'Eternal_Product_Category_Filter' ) ) {
			return;
		}

		$current_category = get_queried_object();
		$filter_groups = get_term_meta( $current_category->term_id, 'category_filter_groups', true );

		if ( ! empty( $filter_groups ) ) {
			// Add body class for CSS targeting
			add_filter( 'body_class', function( $classes ) {
				$classes[] = 'has-eternal-filters';
				return $classes;
			} );
		}
	}
}

// Initialize component
add_action( 'init', function() {
	$component = new Product_Listing_Component();
	$component->init();
} );
```

---

## CSS Integration

### Existing Theme Styles (Preserve)

The theme's existing filter styles should work with the plugin's output. Key classes to maintain:

```css
/* From theme's existing CSS */
.eternal-product-filters-widget {
	margin-bottom: 20px;
}

.eternal-filter-group {
	margin-bottom: 15px;
}

.eternal-filter-group-title {
	font-weight: bold;
	margin-bottom: 8px;
	color: #021f1d;
}

.eternal-filter-option {
	display: block;
	margin-bottom: 5px;
	cursor: pointer;
}

.eternal-filter-option input[type="checkbox"] {
	margin-right: 5px;
}

.eternal-filter-count {
	color: #868686;
	font-size: 0.9em;
}
```

### Additional Plugin-Specific Styles

Add to theme's stylesheet or plugin CSS:

```css
/* Accordion styles for plugin filters */
.has-eternal-filters .eternal-filter-group {
	border-bottom: 1px solid #e0e0e0;
	padding: 10px 0;
}

.has-eternal-filters .eternal-filter-group.open {
	/* Active state styling */
}

.has-eternal-filters .eternal-filter-group-header {
	cursor: pointer;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.has-eternal-filters .eternal-filter-group-options {
	display: none;
	padding: 10px 0 0 20px;
}

.has-eternal-filters .eternal-filter-group.open .eternal-filter-group-options {
	display: block;
}
```

---

## Mobile Filter Drawer Integration

### Current Implementation
The theme has a mobile filter drawer that needs to work with plugin data.

### Integration Approach

```javascript
// In product-listing.ts or mobile-drawer.ts

class MobileFilterDrawer {
  private pluginActive: boolean = false;

  constructor() {
    this.detectPlugin();
    this.initializeDrawer();
  }

  private detectPlugin(): void {
    this.pluginActive = typeof window.eternalFiltersData !== 'undefined';
  }

  private initializeDrawer(): void {
    const drawerToggle = document.querySelector( '.mobile-filter-toggle' );
    const drawer = document.querySelector( '.mobile-filter-drawer' );

    if ( ! drawerToggle || ! drawer ) return;

    drawerToggle.addEventListener( 'click', () => {
      if ( this.pluginActive ) {
        this.loadPluginFiltersInDrawer();
      } else {
        this.loadStaticFiltersInDrawer();
      }
      
      drawer.classList.add( 'open' );
    } );
  }

  private async loadPluginFiltersInDrawer(): Promise<void> {
    try {
      const response = await fetch( `${window.eternalFiltersData.endpoint}${window.eternalFiltersData.categoryId}/filters` );
      const data = await response.json();
      
      const container = drawer.querySelector( '.filter-drawer-content' );
      container.innerHTML = this.renderFiltersForMobile( data.filter_groups );
    } catch ( error ) {
      console.error( 'Failed to load filters:', error );
    }
  }

  private renderFiltersForMobile( filterGroups: FilterGroup[] ): string {
    return filterGroups.map( group => `
      <div class="mobile-filter-group">
        <h3 class="mobile-filter-title">${group.group_name}</h3>
        <div class="mobile-filter-options">
          ${group.options.map( option => `
            <label class="mobile-filter-option">
              <input type="checkbox" name="eternal_filter[]" value="${option.slug}">
              ${option.name}
            </label>
          `).join( '' )}
        </div>
      </div>
    `).join( '' );
  }
}
```

---

## Implementation Checklist

### Pre-Integration
- [ ] Plugin installed and activated
- [ ] At least one product category has filter groups configured
- [ ] Sample products have filter values assigned
- [ ] REST API endpoint tested and working
- [ ] Backup theme files before modification

### Phase 1: Template Integration
- [ ] Update `filters-sidebar.php` to check for plugin
- [ ] Add plugin shortcode/widget call
- [ ] Implement fallback to static filters
- [ ] Test with plugin active
- [ ] Test with plugin inactive (fallback)

### Phase 2: JavaScript Integration  
- [ ] Update `product-listing.ts` with plugin detection
- [ ] Implement REST API data fetching
- [ ] Add dynamic filter rendering
- [ ] Implement filter change handlers
- [ ] Add URL parameter management
- [ ] Test filter selection and URL updates

### Phase 3: Component Integration
- [ ] Update `Product_Listing/Component.php`
- [ ] Add plugin data localization
- [ ] Add body class for CSS targeting
- [ ] Test component initialization

### Phase 4: Mobile Integration
- [ ] Update mobile filter drawer
- [ ] Test plugin filters on mobile
- [ ] Verify responsive behavior
- [ ] Test mobile filter application

### Phase 5: Testing
- [ ] Test on Skincare category (Product Types, Skin Type, Benefits)
- [ ] Test on Nutraceuticals category (Formulations For, Product Format)
- [ ] Test URL parameter persistence
- [ ] Test browser back button
- [ ] Test mobile responsive design
- [ ] Test fallback when plugin inactive
- [ ] Performance testing (API response times)

### Phase 6: Styling Verification
- [ ] Compare with Figma design specifications
- [ ] Verify spacing, fonts, colors match theme
- [ ] Test responsive breakpoints
- [ ] Verify accordion behavior
- [ ] Check hover states and transitions

---

## Testing Scenarios

### Scenario 1: Plugin Active with Filters
1. Navigate to Skincare category page
2. Verify "Product Types", "Skin Type", "Benefits" filters display
3. Select "Face Creme" option
4. Verify URL updates with `?eternal_filter=face-creme`
5. Verify products update to show only matching products
6. Verify arrow icons rotate on accordion expand/collapse
7. Verify blue color scheme matches admin

### Scenario 2: Plugin Inactive
1. Deactivate Eternal Product Category Filter plugin
2. Navigate to category page
3. Verify static filters display (fallback)
4. Verify page still functional
5. Reactivate plugin
6. Verify dynamic filters return

### Scenario 3: Multiple Categories
1. Navigate to Skincare category
2. Verify skincare-specific filters show
3. Navigate to Nutraceuticals category
4. Verify nutraceuticals-specific filters show
5. Verify filters are different between categories

### Scenario 4: Mobile Testing
1. Open category page on mobile viewport
2. Tap mobile filter toggle
3. Verify drawer opens with plugin filters
4. Select filter options
5. Apply filters
6. Verify products update
7. Verify drawer closes/applies correctly

### Scenario 5: URL Persistence
1. Apply filters on category page
2. Copy URL with `?eternal_filter=` parameter
3. Paste in new tab
4. Verify same filters are selected
5. Verify same products are shown

---

## Rollback Plan

If issues arise during integration:

1. **Immediate Rollback**: Restore original template files
   ```bash
   git checkout template-parts/product-listing/filters-sidebar.php
   git checkout assets/js/src/product-listing.ts
   git checkout inc/Product_Listing/Component.php
   ```

2. **Partial Rollback**: Disable integration but keep changes
   ```php
   // In filters-sidebar.php, temporarily force fallback
   $force_static_fallback = true;
   ```

3. **Bug Isolation**: Use plugin's widget directly
   ```php
   // In sidebar.php or functions.php
   add_action( 'woocommerce_sidebar', 'eternal_filter_widget_display' );
   ```

---

## Performance Considerations

### API Caching
The plugin should implement caching for REST API responses:
```php
// Transient caching for 15 minutes
$cache_key = 'eternal_filters_' . $category_id;
$filter_data = get_transient( $cache_key );

if ( false === $filter_data ) {
    $filter_data = $this->get_filter_data( $category_id );
    set_transient( $cache_key, $filter_data, 15 * MINUTE_IN_SECONDS );
}
```

### Frontend Optimization
- Debounce filter change events (300ms)
- Use browser localStorage for recently used filters
- Lazy load filter counts
- Optimize accordion animations

---

## Figma Design Reference

**Design Specifications**:
- Font: Primary theme font (same as existing)
- Colors: Blue (#2271b1) for text and arrows
- Spacing: Match existing filter sidebar
- Accordion: Smooth 200ms transitions
- Mobile: Full-width drawer with slide animation
- Active States: White background for open accordion

**Files**:
- Figma design: [Link to design file]
- Component library: Match theme's existing components

---

## Success Criteria

### Functional Requirements
- ✅ Plugin filters load dynamically from REST API
- ✅ Filters are category-specific (different per category)
- ✅ Filter selection updates URL parameters
- ✅ Product grid updates with filtered results
- ✅ Browser back button works with filter state
- ✅ Mobile filter drawer works with plugin data
- ✅ Fallback to static filters when plugin inactive

### Design Requirements
- ✅ Visual match with Figma designs
- ✅ Consistent with existing theme styling
- ✅ Blue color scheme for filters
- ✅ Smooth accordion animations
- ✅ Responsive on all breakpoints
- ✅ Accessible (keyboard navigation, ARIA labels)

### Performance Requirements
- ✅ API responses under 200ms (with caching)
- ✅ No console errors
- ✅ Smooth animations (60fps)
- ✅ Mobile performance acceptable

---

## Timeline Estimate

- **Phase 1 (Template)**: 2-3 hours
- **Phase 2 (JavaScript)**: 4-6 hours  
- **Phase 3 (Component)**: 1-2 hours
- **Phase 4 (Mobile)**: 2-3 hours
- **Phase 5 (Testing)**: 2-3 hours
- **Phase 6 (Styling)**: 2-3 hours

**Total Estimated Time**: 13-20 hours

---

## Notes

### Plugin Version Compatibility
- Requires Eternal Product Category Filter v1.0.0+
- Compatible with WooCommerce 7.0+
- Tested with WordPress 6.0+
- Compatible with PHP 8.0+

### Theme Compatibility
- Developed for WPRig theme
- May work with other WooCommerce themes with minor adjustments
- CSS classes are theme-agnostic where possible

### Future Enhancements
- AJAX product grid updates (no page reload)
- Advanced filter combinations (AND logic option)
- Filter range sliders (for price, weight, etc.)
- Recently used filters
- Filter search functionality
- Analytics tracking for filter usage

---

## Support & Troubleshooting

### Common Issues

**Issue**: Filters not displaying
- **Solution**: Check if category has filter groups configured
- **Solution**: Verify plugin is activated
- **Solution**: Check REST API endpoint accessibility

**Issue**: Wrong products showing after filtering
- **Solution**: Clear product cache
- **Solution**: Verify filter assignments are correct
- **Solution**: Check taxonomy term relationships

**Issue**: Mobile drawer not opening
- **Solution**: Check for JavaScript conflicts
- **Solution**: Verify mobile breakpoint CSS
- **Solution**: Test for z-index issues

**Issue**: Styles not applying
- **Solution**: Clear browser cache
- **Solution**: Check for CSS specificity conflicts
- **Solution**: Verify plugin CSS is enqueued

---

**Document Version**: 1.0  
**Last Updated**: 2026-04-16  
**Plugin Version**: 1.0.0  
**Theme Version**: Current WPRig
