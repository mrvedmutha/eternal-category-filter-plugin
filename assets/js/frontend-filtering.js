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
		const apiEndpoint = eternalFiltersData.apiEndpoint;

		$.ajax( {
			url: apiEndpoint,
			method: 'GET',
			beforeSend: function() {
				$( '#eternal-filters-container' ).html( '<p class="eternal-filters-loading">' + eternalFiltersData.strings.loading + '</p>' );
			},
			success: function( response ) {
				if ( response.filter_groups && response.filter_groups.length > 0 ) {
					renderFilters( response.filter_groups );
				} else {
					showNoFilters();
				}
			},
			error: function() {
				showError();
			},
		} );
	}

	/**
	 * Render filter groups.
	 */
	function renderFilters( filterGroups ) {
		const $container = $( '#eternal-filters-container' );
		$container.empty();

		const selectedFilters = eternalFiltersData.currentFilters || [];

		filterGroups.forEach( function( group ) {
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
	}

	/**
	 * Update URL with selected filters.
	 */
	function updateURLWithFilters() {
		const selectedFilters = [];
		$( '.eternal-filter-option input:checked' ).each( function() {
			selectedFilters.push( $( this ).val() );
		} );

		if ( selectedFilters.length > 0 ) {
			const newURL = updateQueryStringParameter( window.location.href, eternalFiltersData.filterParam, selectedFilters.join( ',' ) );
			window.location.href = newURL;
		} else {
			// Remove filter parameter if no filters selected.
			const newURL = removeQueryStringParameter( window.location.href, eternalFiltersData.filterParam );
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
		$( '#eternal-filters-container' ).html( '<p class="eternal-filters-none">' + eternalFiltersData.strings.noFilters + '</p>' );
	}

	/**
	 * Show error message.
	 */
	function showError() {
		$( '#eternal-filters-container' ).html( '<p class="eternal-filters-error">' + eternalFiltersData.strings.errorLoading + '</p>' );
	}

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		// Check if we're on a category page with filter container.
		if ( $( '#eternal-filters-container' ).length > 0 ) {
			loadFilters();
		}

		// Handle filter option changes.
		$( document ).on( 'change', '.eternal-filter-option input', function() {
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
