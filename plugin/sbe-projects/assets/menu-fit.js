/**
 * Header menu: show the hamburger whenever the menu doesn't fit on one row.
 *
 * Core WordPress only switches to the hamburger below 600px. This checks the real
 * layout instead: with the menu expanded, if its items wrap onto a second line, or the
 * site title has to wrap because of it, <html> gets the class `sbe-menu-collapsed`
 * (assets/menu-fit.css shows the hamburger). Re-checked on resize and when fonts load.
 * Loaded in the footer without defer, so it runs before the first paint in most cases.
 */
( function () {
	const root = document.documentElement;
	const header = document.querySelector( '.wp-site-blocks > header' );
	const nav = header && header.querySelector( '.wp-block-navigation' );
	if ( ! nav ) {
		return;
	}
	const title = header.querySelector( '.wp-block-site-title' );

	function oneLine( el ) {
		const lh = parseFloat( getComputedStyle( el ).lineHeight ) || el.getBoundingClientRect().height;
		return el.getBoundingClientRect().height < lh * 1.5;
	}

	function menuFits() {
		const container = nav.querySelector( '.wp-block-navigation__responsive-container' );
		if ( ! container || container.classList.contains( 'is-menu-open' ) ) {
			return null; // Menu overlay open: leave as is.
		}
		const items = container.querySelectorAll( '.wp-block-navigation-item' );
		if ( ! items.length ) {
			return true;
		}
		const tops = Array.from( items, ( li ) => Math.round( li.getBoundingClientRect().top ) );
		const wraps = Math.max( ...tops ) - Math.min( ...tops ) > 2;
		return ! wraps && ( ! title || oneLine( title ) );
	}

	function check() {
		if ( window.innerWidth < 600 ) {
			root.classList.remove( 'sbe-menu-collapsed' ); // Core's own hamburger.
			return;
		}
		const was = root.classList.contains( 'sbe-menu-collapsed' );
		root.classList.remove( 'sbe-menu-collapsed' ); // Measure the expanded menu (no paint in between).
		const fits = menuFits();
		const collapse = null === fits ? was : ! fits;
		root.classList.toggle( 'sbe-menu-collapsed', collapse );
	}

	let frame = 0;
	function schedule() {
		cancelAnimationFrame( frame );
		frame = requestAnimationFrame( check );
	}

	check();
	window.addEventListener( 'resize', schedule );
	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( check );
	}
	window.addEventListener( 'load', check );
} )();
