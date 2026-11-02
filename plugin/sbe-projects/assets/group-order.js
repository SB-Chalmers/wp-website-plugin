/**
 * People → Staff groups: drag rows, or click ↑ ↓, to set the order on the People page.
 * Saves straight away (admin-ajax action sbe_group_order).
 */
( function ( $ ) {
	var cfg = window.sbeGroupOrder || {};
	var $list = $( '#the-list' );
	var $notice;

	function rows() {
		return $list.children( 'tr' ).filter( function () {
			return /^tag-\d+$/.test( this.id );
		} );
	}

	function message( text, isError ) {
		if ( ! $notice ) {
			$notice = $( '<div class="notice is-dismissible" style="margin:5px 0 10px"><p></p></div>' ).insertBefore( '#posts-filter' );
		}
		$notice.removeClass( 'notice-success notice-error' ).addClass( isError ? 'notice-error' : 'notice-success' ).show();
		$notice.find( 'p' ).text( text );
		if ( ! isError ) {
			clearTimeout( message.t );
			message.t = setTimeout( function () { $notice.fadeOut(); }, 2000 );
		}
	}

	function refreshButtons() {
		var $r = rows();
		$r.find( '.sbe-go__up, .sbe-go__down' ).prop( 'disabled', false );
		$r.first().find( '.sbe-go__up' ).prop( 'disabled', true );
		$r.last().find( '.sbe-go__down' ).prop( 'disabled', true );
	}

	function save() {
		refreshButtons();
		var ids = rows().map( function () { return this.id.replace( 'tag-', '' ); } ).get();
		$.post( window.ajaxurl, { action: 'sbe_group_order', _ajax_nonce: cfg.nonce, ids: ids } )
			.done( function ( res ) {
				if ( res && res.success ) {
					$.each( res.data, function ( i, id ) {
						$( '#tag-' + id + ' .sbe-go' ).attr( 'data-order', i + 1 );
					} );
					message( cfg.saved );
				} else {
					message( cfg.error, true );
				}
			} )
			.fail( function () { message( cfg.error, true ); } );
	}

	// Put rows in saved order (used after a new group is added, which WordPress puts at the top).
	function sortRows() {
		var $r = rows().get().sort( function ( a, b ) {
			return ( +$( a ).find( '.sbe-go' ).attr( 'data-order' ) || 0 ) - ( +$( b ).find( '.sbe-go' ).attr( 'data-order' ) || 0 );
		} );
		$list.append( $r );
		refreshButtons();
	}

	if ( ! cfg.enabled ) {
		$( 'body' ).addClass( 'sbe-go-off' );
		$( '.column-sbe_order' ).filter( 'th' ).attr( 'title', cfg.disabled );
		return;
	}

	$list.sortable( {
		items: '> tr',
		handle: '.sbe-go__handle',
		axis: 'y',
		cursor: 'grabbing',
		placeholder: 'sbe-go-placeholder',
		helper: function ( e, $tr ) {
			// Keep cell widths while dragging.
			var $orig = $tr.children();
			var $h = $tr.clone();
			$h.children().each( function ( i ) { $( this ).width( $orig.eq( i ).width() ); } );
			return $h;
		},
		start: function ( e, ui ) {
			ui.placeholder.html( '<td colspan="' + ui.item.children().length + '">&nbsp;</td>' );
		},
		update: save
	} );

	$list.on( 'click', '.sbe-go__up, .sbe-go__down', function () {
		var $tr = $( this ).closest( 'tr' );
		if ( $( this ).hasClass( 'sbe-go__up' ) ) {
			$tr.prev( 'tr' ).before( $tr );
		} else {
			$tr.next( 'tr' ).after( $tr );
		}
		$( this ).trigger( 'focus' );
		save();
	} );

	$( document ).ajaxComplete( function ( e, xhr, settings ) {
		if ( settings && typeof settings.data === 'string' && settings.data.indexOf( 'action=add-tag' ) !== -1 ) {
			setTimeout( sortRows, 0 );
		}
	} );

	refreshButtons();
}( jQuery ) );
