/**
 * Tag filter (includes/tag-filter.php): "More tags (n)" shows the rest of the tags,
 * "Fewer tags" folds them away again. Delegated, so it keeps working after the gallery
 * (and the tag row inside it) is swapped in place.
 */
( function () {
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest && event.target.closest( '.sbe-tag-filter__toggle' );
		if ( ! button ) {
			return;
		}
		var nav = button.closest( '.sbe-tag-filter__nav' );
		if ( ! nav ) {
			return;
		}
		var open = nav.classList.toggle( 'is-expanded' );
		button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		button.textContent = open ? button.getAttribute( 'data-less' ) : button.getAttribute( 'data-more' );
	} );
} )();
