/**
 * Admin Filter Configuration JavaScript
 *
 * Handles the repeater functionality for filter groups and options
 * on the product category edit page.
 *
 * @package Eternal_Product_Category_Filter
 */

( function( $ ) {
	'use strict';

	// Track group and option indices.
	let groupIndex = 0;
	let optionIndices = {};

	/**
	 * Generate a unique ID for a new group or option.
	 */
	function generateId() {
		return Date.now().toString( 36 ) + Math.random().toString( 36 ).substr( 2 );
	}

	/**
	 * Create a new filter option row.
	 */
	function createOptionRow( groupIndex, optionIndex ) {
		const optionId = generateId();
		const html = `
			<div class="filter-option-row" style="margin-bottom: 10px; padding: 10px; border: 1px solid #e0e0e0; background: #fff;" data-option-id="${optionId}">
				<div style="display: flex; gap: 10px;">
					<div style="flex: 1;">
						<label style="font-size: 12px; color: #666;">${eternalFilterData.optionNamePlaceholder}</label>
						<input type="text" name="filter_groups[${groupIndex}][options][${optionIndex}][name]" class="filter-option-name" style="width: 100%;" placeholder="${eternalFilterData.optionNamePlaceholder}">
					</div>
					<div style="width: 80px;">
						<label style="font-size: 12px; color: #666;">Order</label>
						<input type="number" name="filter_groups[${groupIndex}][options][${optionIndex}][order]" class="filter-option-order" style="width: 100%;" value="${optionIndex + 1}" min="1">
					</div>
					<div>
						<button type="button" class="button remove-filter-option-button" data-group-index="${groupIndex}" data-option-index="${optionIndex}" style="margin-top: 15px; padding: 5px 10px; font-size: 12px; color: #a00;">
							${eternalFilterData.removeOptionText}
						</button>
					</div>
				</div>
			</div>
		`;

		return html;
	}

	/**
	 * Create a new filter group row.
	 */
	function createGroupRow( groupIndex ) {
		const groupId = generateId();
		const html = `
			<div class="filter-group-row" style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;" data-group-id="${groupId}">
				<div class="filter-group-header" style="margin-bottom: 10px;">
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<div style="flex: 1;">
							<label style="font-weight: bold; color: #0073aa;">Filter Group Name</label>
							<input type="text" name="filter_groups[${groupIndex}][group_name]" class="filter-group-name" style="width: 100%;" placeholder="${eternalFilterData.groupNamePlaceholder}" data-group-index="${groupIndex}">
						</div>
						<button type="button" class="button remove-filter-group-button" data-group-index="${groupIndex}" style="margin-left: 10px; color: #a00;">
							${eternalFilterData.removeGroupText}
						</button>
					</div>
				</div>
				<div class="filter-group-options" style="margin-top: 15px;">
					<label style="font-weight: bold; color: #0073aa; margin-bottom: 5px; display: block;">Filter Options</label>
					<div class="filter-options-list" data-group-index="${groupIndex}">
						<!-- Options will be added here -->
					</div>
					<button type="button" class="button add-filter-option-button" data-group-index="${groupIndex}" style="margin-top: 10px;">
						${eternalFilterData.addOptionText}
					</button>
				</div>
			</div>
		`;

		return html;
	}

	/**
	 * Initialize on document ready.
	 */
	$( document ).ready( function() {
		const $container = $( '#filter-groups-container' );

		// Initialize option indices for existing groups.
		$( '.filter-options-list' ).each( function() {
			const groupIndex = $( this ).data( 'group-index' );
			const optionCount = $( this ).find( '.filter-option-row' ).length;
			optionIndices[groupIndex] = optionCount;
			groupIndex = Math.max( groupIndex, parseInt( groupIndex ) + 1 );
		} );

		// Add filter group button.
		$( '#add-filter-group-button' ).on( 'click', function() {
			const newGroupIndex = groupIndex++;
			const $groupRow = $( createGroupRow( newGroupIndex ) );
			$container.append( $groupRow );
			optionIndices[newGroupIndex] = 0;
		} );

		// Remove filter group button (delegated).
		$container.on( 'click', '.remove-filter-group-button', function() {
			if ( confirm( 'Are you sure you want to remove this filter group?' ) ) {
				const $groupRow = $( this ).closest( '.filter-group-row' );
				$groupRow.remove();
			}
		} );

		// Add filter option button (delegated).
		$container.on( 'click', '.add-filter-option-button', function() {
			const groupIndex = $( this ).data( 'group-index' );
			const newOptionIndex = optionIndices[groupIndex]++;
			const $optionsList = $( this ).siblings( '.filter-options-list' );
			const $optionRow = $( createOptionRow( groupIndex, newOptionIndex ) );
			$optionsList.append( $optionRow );
		} );

		// Remove filter option button (delegated).
		$container.on( 'click', '.remove-filter-option-button', function() {
			const $optionRow = $( this ).closest( '.filter-option-row' );
			$optionRow.remove();
		} );

		// Auto-generate slugs when typing names.
		$container.on( 'input', '.filter-group-name', function() {
			const $input = $( this );
			const name = $input.val();

			// Only auto-generate slug if it's the first edit
			if ( ! $input.data( 'slug-edited' ) && name.length > 0 ) {
				// Simple slug generation (client-side)
				const slug = name.toLowerCase()
					.replace( /[^a-z0-9]+/g, '-' )
					.replace( /^-+|-+$/g, '-' );

				// Store slug as data attribute (will be saved server-side)
				$input.data( 'generated-slug', slug );
			}
		} );

		// Mark slug as manually edited if user modifies it directly
		// (This would be used if we added a visible slug field)
	} );

} )( jQuery );
