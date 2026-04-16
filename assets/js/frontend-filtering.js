/**
 * Frontend Filtering JavaScript
 *
 * Handles dynamic filter loading, user interactions,
 * and URL parameter management.
 *
 * @package Eternal_Product_Category_Filter
 */

( function( $ ) {
	'use strict';

	/**
	 * Load filter data from REST API.
	 */
	function loadFilters() {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: loadFilters() called');
		console.log('✓ ETERNAL FILTERS: API Endpoint:', eternalFiltersData.apiEndpoint);
		console.log('✓ ETERNAL FILTERS: Category ID:', eternalFiltersData.categoryId);

		const apiEndpoint = eternalFiltersData.apiEndpoint;

		$.ajax( {
			url: apiEndpoint,
			method: 'GET',
			beforeSend: function() {
				$( '#eternal-filters-container' ).html( '<p class="eternal-filters-loading">' + eternalFiltersData.strings.loading + '</p>' );
			},
			success: function( response ) {
				// TODO: DEBUG - Remove console.log after verifying frontend filters work
				console.log('✓ ETERNAL FILTERS: API Response received:', response);
				console.log('✓ ETERNAL FILTERS: Filter groups found:', response.filter_groups ? response.filter_groups.length : 0);

				if ( response.filter_groups && response.filter_groups.length > 0 ) {
					renderFilters( response.filter_groups );
				} else {
					showNoFilters();
				}
			},
			error: function( xhr, status, error ) {
				// TODO: DEBUG - Remove console.log after verifying frontend filters work
				console.error('✗ ETERNAL FILTERS: API Error:', status, error);
				console.error('✗ ETERNAL FILTERS: Response:', xhr.responseText);
				showError();
			},
		} );
	}

	/**
	 * Render filter groups.
	 */
	function renderFilters( filterGroups ) {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: Rendering filter groups:', filterGroups.length);

		const $container = $( '#eternal-filters-container' );
		$container.empty();

		const selectedFilters = eternalFiltersData.currentFilters || [];

		filterGroups.forEach( function( group ) {
			// TODO: DEBUG - Remove console.log after verifying frontend filters work
			console.log('✓ ETERNAL FILTERS: Rendering group:', group.name, 'with', group.options.length, 'options');

			const $groupDiv = $( '<div class="eternal-filter-group" data-group-id="' + group.group_id + '"></div>' );

			$groupDiv.append( '<div class="eternal-filter-group-title">' + group.name + '</div>' );

			if ( group.options && group.options.length > 0 ) {
				const $optionsDiv = $( '<div class="eternal-filter-options"></div>' );

				group.options.forEach( function( option ) {
					const isChecked = selectedFilters.indexOf( option.slug ) !== -1 ? 'checked' : '';
					const count = option.count ? ' (' + option.count + ')' : '';

					const $label = $( '<label class="eternal-filter-option' + ( isChecked ? ' checked' : '' ) + '">' );
					$label.append( '<input type="checkbox" value="' + option.slug + '" data-option-slug="' + option.slug + '" ' + isChecked + '> ' );
					$label.append( '<span class="eternal-filter-option-name">' + option.name + '</span>' );
					$label.append( '<span class="eternal-filter-count">' + count + '</span>' );
					$label.append( '</label>' );

					$optionsDiv.append( $label );
				} );

				$groupDiv.append( $optionsDiv );
			}

			$container.append( $groupDiv );
		} );

		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: Filter rendering complete');
	}

	/**
	 * Update URL with selected filters.
	 */
	function updateURLWithFilters() {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: Updating URL with selected filters');

		const selectedFilters = [];
		$( '.eternal-filter-option input:checked' ).each( function() {
			selectedFilters.push( $( this ).val() );
		} );

		if ( selectedFilters.length > 0 ) {
			const newURL = updateQueryStringParameter( window.location.href, eternalFiltersData.filterParam, selectedFilters.join( ',' ) );
			// TODO: DEBUG - Remove console.log after verifying frontend filters work
			console.log('✓ ETERNAL FILTERS: Redirecting to URL with filters:', newURL);
			window.location.href = newURL;
		} else {
			// Remove filter parameter if no filters selected.
			const newURL = removeQueryStringParameter( window.location.href, eternalFiltersData.filterParam );
			// TODO: DEBUG - Remove console.log after verifying frontend filters work
			console.log('✓ ETERNAL FILTERS: Redirecting to URL without filters:', newURL);
			window.location.href = newURL;
		}
	}

	/**
	 * Update query string parameter.
	 */
	function updateQueryStringParameter( uri, key, value ) {
		const re = new RegExp( '([?&])' + key + '=.*?(&|$)', 'i' );
		const separator = uri.indexOf( '?' ) !== -1 ? '&' : '?';

		if ( uri.match( re ) ) {
			return uri.replace( re, '$1' + key + '=' + value + '$2' );
		} else {
			return uri + separator + key + '=' + value;
		}
	}

	/**
	 * Remove query string parameter.
	 */
	function removeQueryStringParameter( uri, key ) {
		const re = new RegExp( '([?&])' + key + '=[^&]*(&?)', 'i' );
		return uri.replace( re, function( $0, $1, $2 ) {
			if ( $2 ) {
				return $1;
			} else {
				return '';
			}
		} );
	}

	/**
	 * Show "no filters" message.
	 */
	function showNoFilters() {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: No filter groups found for this category');

		$( '#eternal-filters-container' ).html( '<p class="eternal-filters-none">' + eternalFiltersData.strings.noFilters + '</p>' );
	}

	/**
	 * Show error message.
	 */
	function showError() {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.error('✗ ETERNAL FILTERS: Error loading filters');

		$( '#eternal-filters-container' ).html( '<p class="eternal-filters-error">' + eternalFiltersData.strings.errorLoading + '</p>' );
	}

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		// TODO: DEBUG - Remove console.log after verifying frontend filters work
		console.log('✓ ETERNAL FILTERS: Frontend JavaScript initialized');
		console.log('✓ ETERNAL FILTERS: eternalFiltersData available:', typeof eternalFiltersData !== 'undefined');
		console.log('✓ ETERNAL FILTERS: Filter container found:', $( '#eternal-filters-container' ).length > 0);

		// Check if we're on a category page with filter container.
		if ( $( '#eternal-filters-container' ).length > 0 ) {
			loadFilters();
		}

		// Handle filter option changes.
		$( document ).on( 'change', '.eternal-filter-option input', function() {
			// TODO: DEBUG - Remove console.log after verifying frontend filters work
			console.log('✓ ETERNAL FILTERS: Filter option changed:', $( this ).val(), 'checked:', this.checked);

			// Update checked state on label.
			const $label = $( this ).closest( '.eternal-filter-option' );
			if ( this.checked ) {
				$label.addClass( 'checked' );
			} else {
				$label.removeClass( 'checked' );
			}

			// Update URL with new filter selection.
			updateURLWithFilters();
		} );

		// Handle clear all button.
		$( document ).on( 'click', '.eternal-filters-clear-all', function( e ) {
			e.preventDefault();

			// Uncheck all filters.
			$( '.eternal-filter-option input:checked' ).prop( 'checked', false ).trigger( 'change' );

			// Update labels.
			$( '.eternal-filter-option' ).removeClass( 'checked' );

			// Update URL to remove filters.
			const newURL = removeQueryStringParameter( window.location.href, eternalFiltersData.filterParam );
			window.location.href = newURL;
		} );
	} );

} )( jQuery );
