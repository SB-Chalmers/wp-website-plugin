/**
 * Search panel: "Search" in the menu (class sbe-search-toggle) opens a panel that covers
 * the page below the sticky header, with one large field. Esc, "Search" again, the "Close"
 * button (phones) or opening the phone menu closes it.
 * See includes/search.php.
 */
( function () {
	const panel = document.getElementById( 'sbe-search-overlay' );
	if ( ! panel ) {
		return;
	}
	const input = panel.querySelector( 'input' );
	const header = document.querySelector( '.wp-site-blocks > header' );
	let returnFocus = null;

	const toggles = () => document.querySelectorAll( '.sbe-search-toggle > a' );

	function place() {
		const bottom = header ? header.getBoundingClientRect().bottom : 0;
		panel.style.top = Math.max( 0, bottom ) + 'px';
	}

	function setState( open ) {
		toggles().forEach( ( a ) => {
			a.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			a.parentElement.classList.toggle( 'sbe-search-toggle--open', open );
		} );
		document.documentElement.classList.toggle( 'sbe-search-open', open );
	}

	function open( from ) {
		// On phones "Search" sits in the menu overlay: close that first.
		const menuClose = document.querySelector( '.wp-block-navigation__responsive-container.is-menu-open .wp-block-navigation__responsive-container-close' );
		if ( menuClose ) {
			menuClose.click();
		}
		returnFocus = from || document.activeElement;
		place();
		panel.hidden = false;
		setState( true );
		setTimeout( () => input.focus(), 60 );
	}

	function close() {
		panel.hidden = true;
		setState( false );
		const target = returnFocus && document.contains( returnFocus ) && returnFocus.offsetParent ? returnFocus : toggles()[ 0 ];
		if ( target ) {
			target.focus();
		}
	}

	toggles().forEach( ( a ) => {
		a.setAttribute( 'aria-expanded', 'false' );
		a.setAttribute( 'aria-controls', 'sbe-search-overlay' );
	} );

	panel.querySelector( '.sbe-search-overlay__close' ).addEventListener( 'click', close );

	document.addEventListener( 'click', ( e ) => {
		// Opening the phone menu closes the panel.
		if ( ! panel.hidden && e.target.closest( '.wp-block-navigation__responsive-container-open' ) ) {
			panel.hidden = true;
			setState( false );
			return;
		}
		const a = e.target.closest( '.sbe-search-toggle > a' );
		if ( a ) {
			e.preventDefault();
			if ( panel.hidden ) {
				open( a );
			} else {
				close();
			}
		}
	} );
	document.addEventListener( 'keydown', ( e ) => {
		if ( 'Escape' === e.key && ! panel.hidden ) {
			close();
		}
	} );
	window.addEventListener( 'resize', () => {
		if ( ! panel.hidden ) {
			place();
		}
	} );
	// Empty search: stay on the panel instead of listing everything.
	panel.querySelector( 'form' ).addEventListener( 'submit', ( e ) => {
		if ( ! input.value.trim() ) {
			e.preventDefault();
			input.focus();
		}
	} );
} )();
