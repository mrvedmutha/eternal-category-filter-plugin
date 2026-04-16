/**
 * Admin Product Assignment JavaScript
 *
 * Handles dynamic loading of filter groups based on
 * product's assigned categories.
 *
 * @package Eternal_Product_Category_Filter
 */

( function( $ ) {
	'use strict';

	/**
	 * Load filter groups for given category IDs.
	 */
	function loadFilterGroups( categoryIds ) {
		if ( ! categoryIds || categoryIds.length === 0 ) {
			showNoFiltersMessage();
			return;
		}

		// Show loading state.
		$( '#eternal-filters-loading' ).show();
		$( '#eternal-filter-accordion-container' ).hide();

		$.ajax( {
			url: eternalProductFilterData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'eternal_get_product_filter_groups',
				nonce: eternalProductFilterData.nonce,
				category_ids: categoryIds,
			},
			success: function( response ) {
				$( '#eternal-filters-loading' ).hide();

				if ( response.success && response.data.filter_groups ) {
					renderFilterGroups( response.data.filter_groups );
				} else {
					showNoFiltersMessage();
				}
			},
			error: function() {
				$( '#eternal-filters-loading' ).hide();
				showNoFiltersMessage();
			},
		} );
	}

	/**
	 * Render filter groups in the meta box as accordion.
	 */
	function renderFilterGroups( filterGroups ) {
		const $container = $( '#eternal-filter-accordion-container' );
		$container.empty();

		if ( ! filterGroups || filterGroups.length === 0 ) {
			showNoFiltersMessage();
			return;
		}

		// Get product ID.
		const productId = $( '#eternal-product-id' ).val();

		// Get currently assigned filter values.
		let assignedSlugs = [];
		if ( window.eternalAssignedFilters && window.eternalAssignedFilters[productId] ) {
			assignedSlugs = window.eternalAssignedFilters[productId];
		}

		filterGroups.forEach( function( group, groupIndex ) {
			// Create accordion section using WordPress core classes.
			const $section = $( '<div class="accordion-section eternal-accordion-section" data-group-index="' + groupIndex + '"></div>' );

			// Create accordion trigger button with inline styles to override WordPress admin CSS.
			const $trigger = $( '<button type="button" class="accordion-trigger eternal-accordion-trigger" aria-expanded="false" style="padding: 12px 15px !important; cursor: pointer; display: flex !important; flex-direction: row !important; align-items: center !important; gap: 8px !important; background: #f9f9f9 !important; transition: background-color 0.2s ease; border: none !important; border-radius: 0 !important; box-shadow: none !important; width: 100% !important; text-align: left !important; font-size: 13px !important; font-weight: 600 !important; color: #2271b1 !important; margin: 0 !important; line-height: 1.4 !important; min-height: auto !important; appearance: none !important;"></button>' );
			$trigger.append( '<span class="accordion-title">' + group.group_name + '</span>' );
			$trigger.append( '<span class="accordion-icon" aria-hidden="true" style="flex-shrink: 0; width: 16px; height: 16px; position: relative; display: inline-block;"></span>' );
			$section.append( $trigger );

			// Create accordion content.
			const $content = $( '<div class="accordion-content eternal-accordion-content" hidden style="padding: 15px; border-top: 1px solid #eee; background: #fff; max-height: 300px; overflow-y: auto;"></div>' );

			if ( group.options && group.options.length > 0 ) {
				group.options.forEach( function( option ) {
					const checked = assignedSlugs.indexOf( option.slug ) !== -1 ? 'checked' : '';
					const $label = $( '<label style="display: block; margin-bottom: 6px; padding: 4px 0;"></label>' );

					$label.append( '<input type="checkbox" name="eternal_filter_values[]" value="' + option.slug + '" data-group-id="' + group.group_id + '" data-option-id="' + option.option_id + '" ' + checked + '/> ' );
					$label.append( '<span>' + option.name + '</span>' );

					$content.append( $label );
				} );
			}

			$section.append( $content );
			$container.append( $section );
		} );

		// Initialize first accordion as open.
		$container.find( '.accordion-section:first' ).addClass( 'open' );
		$container.find( '.accordion-section:first .accordion-trigger' ).attr( 'aria-expanded', 'true' );
		$container.find( '.accordion-section:first .accordion-trigger' ).css( 'background', '#fff' );
		$container.find( '.accordion-content:first' ).prop( 'hidden', false );

		$container.show();
	}

	/**
	 * Show "no filters" message.
	 */
	function showNoFiltersMessage() {
		const $container = $( '#eternal-filter-accordion-container' );
		$container.html( '<p class="description">' + eternalProductFilterData.strings.noFilters + '</p>' );
		$container.show();
	}

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		// Only run on product edit screen.
		if ( $( '#eternal-filter-accordion-container' ).length === 0 ) {
			return;
		}

		// Accordion toggle functionality using WordPress core classes.
		$( document ).on( 'click', '.accordion-trigger', function( e ) {
			e.preventDefault();

			const $trigger = $( this );
			const $section = $trigger.closest( '.accordion-section' );
			const $content = $section.find( '.accordion-content' );

			// Toggle current accordion.
			const isExpanded = $trigger.attr( 'aria-expanded' ) === 'true';

			if ( isExpanded ) {
				// Close accordion.
				$section.removeClass( 'open' );
				$trigger.attr( 'aria-expanded', 'false' );
				$trigger.css( 'background', '#f9f9f9' );
				$content.prop( 'hidden', true );
			} else {
				// Open accordion.
				$section.addClass( 'open' );
				$trigger.attr( 'aria-expanded', 'true' );
				$trigger.css( 'background', '#fff' );
				$content.prop( 'hidden', false );
			}
		} );

		// Watch for category checkbox changes in the product categories meta box.
		// This uses WooCommerce's category checkbox selector.
		$( document ).on( 'change', '#product_cat-all input[type="checkbox"], #product_catchecklist input[type="checkbox"]', function() {
			// Get all checked category IDs.
			const categoryIds = [];
			$( '#product_catchecklist input[type="checkbox"]:checked' ).each( function() {
				const value = $( this ).val();
				if ( value && value !== '0' ) {
					categoryIds.push( parseInt( value, 10 ) );
				}
			} );

			// Debounce the AJAX call.
			clearTimeout( window.eternalFilterLoadTimeout );
			window.eternalFilterLoadTimeout = setTimeout( function() {
				loadFilterGroups( categoryIds );
			}, 500 );
		} );

		// Store initial assigned filters for this product.
		const productId = $( '#eternal-product-id' ).val();
		if ( productId ) {
			window.eternalAssignedFilters = window.eternalAssignedFilters || {};
			window.eternalAssignedFilters[productId] = [];

			$( '#eternal-filter-accordion-container input[name="eternal_filter_values[]"]:checked' ).each( function() {
				window.eternalAssignedFilters[productId].push( $( this ).val() );
			} );
		}

		// Track checkbox changes to update assigned filters.
		$( document ).on( 'change', '#eternal-filter-accordion-container input[name="eternal_filter_values[]"]', function() {
			if ( ! productId ) {
				return;
			}

			window.eternalAssignedFilters[productId] = [];
			$( '#eternal-filter-accordion-container input[name="eternal_filter_values[]"]:checked' ).each( function() {
				window.eternalAssignedFilters[productId].push( $( this ).val() );
			} );
		} );
	} );

} )( jQuery );
