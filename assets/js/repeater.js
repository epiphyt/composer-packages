/**
 * Add repeater field support via wp.template().
 */
/* global jQuery, composerPackages */
jQuery( document ).ready( function ( $ ) {
	let html;
	let template;

	$( '.composer-packages__add-button' ).on( 'click', addTemplateItem );

	// remove template item
	$( document ).on( 'click', '.composer-packages__delete-button', function ( event ) {
		event.preventDefault();

		const item = $( this ).parent().parent();
		let itemId = item.attr( 'data-item-id' );
		const parent = item.parent();

		// assign new item IDs
		item.nextAll().each( () => {
			replaceItemId( $( this ), itemId, $( this ).attr( 'data-item-id' ) );
			// increment item ID for the next item
			itemId++;
		} );
		// remove this item
		item.remove();

		if ( ! parent.children().length ) {
			parent.html(
				'<tr class="composer-packages__products--not-available"><td colspan="5">' +
					composerPackages.i18n.noDataFound +
					'</td></tr>'
			);
		}
	} );

	/**
	 * Add a template item.
	 *
	 * @param {Event} event The event
	 */
	function addTemplateItem( event ) {
		event.preventDefault();

		template = wp.template( 'product' );
		html = template();
		const set = $( '#composer-packages__products' );
		const noData = $( '#composer-packages__products .composer-packages__products--not-available' );

		if ( noData ) {
			noData.remove();
		}

		// add template to HTML
		set.append( html );

		// adjust attributes
		const newItem = set.children( 'tr:last-child' );
		const prev = newItem.prev( 'tr' );
		let setItemId = 0;

		if ( prev.length ) {
			setItemId = parseInt( prev.attr( 'data-item-id' ) );
		}

		replaceItemId( newItem, setItemId + 1 );
	}

	/**
	 * Replace the item ID in an item.
	 *
	 * @param {jQuery} item      The item
	 * @param {string} newItemId The new item ID
	 * @param {string} oldItemId The old item ID
	 */
	function replaceItemId( item, newItemId, oldItemId ) {
		let replace = '[__key]';

		if ( oldItemId ) {
			replace = '[' + oldItemId + ']';
		}

		item.attr( 'data-item-id', newItemId );
		item.find( 'input' ).attr( 'name', function ( index, attribute ) {
			return attribute.replace( replace, '[' + newItemId + ']' );
		} );
	}
} );
