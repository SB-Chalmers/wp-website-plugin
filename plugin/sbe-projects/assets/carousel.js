/**
 * Side-scrolling galleries: adds ← → buttons under each `.sbe-carousel` Query Loop. Scrolling itself is plain CSS (see carousel.css),
 * so swiping / trackpad scrolling works even without this script.
 */
( function () {
	document.querySelectorAll( '.wp-block-query.sbe-carousel' ).forEach( function ( query, i ) {
		var track = query.querySelector( '.wp-block-post-template' );
		if ( ! track ) {
			return;
		}
		if ( ! track.id ) {
			track.id = 'sbe-carousel-' + ( i + 1 );
		}

		var arrows = document.createElement( 'div' );
		arrows.className = 'sbe-carousel-arrows';
		// Name for screen readers, taken from the heading (or heading row) right above the gallery.
		var row = query.previousElementSibling;
		var heading = row && ( row.matches( '.wp-block-heading' ) ? row : row.querySelector( '.wp-block-heading' ) );
		var label = heading ? heading.textContent.trim() : 'gallery';

		function makeButton( dir ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'sbe-carousel-arrow sbe-carousel-arrow--' + dir;
			b.setAttribute( 'aria-controls', track.id );
			b.setAttribute( 'aria-label', ( dir === 'prev' ? 'Scroll back: ' : 'Scroll forward: ' ) + label );
			b.innerHTML = '<span aria-hidden="true">' + ( dir === 'prev' ? '←' : '→' ) + '</span>';
			b.addEventListener( 'click', function () {
				var card = track.querySelector( 'li' );
				var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;
				var step = card ? card.getBoundingClientRect().width + gap : track.clientWidth;
				track.scrollBy( { left: dir === 'prev' ? -step : step } );
			} );
			return b;
		}
		var prev = makeButton( 'prev' );
		var next = makeButton( 'next' );
		arrows.appendChild( prev );
		arrows.appendChild( next );
		// Arrows go under the gallery: ← on the left, → on the right (like the page-number arrows).
		track.insertAdjacentElement( 'afterend', arrows );

		function update() {
			var max = track.scrollWidth - track.clientWidth;
			arrows.hidden = max <= 1; // everything fits: no arrows
			prev.disabled = track.scrollLeft <= 1;
			next.disabled = track.scrollLeft >= max - 1;
		}
		track.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
	} );
} )();
