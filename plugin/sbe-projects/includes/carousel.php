<?php
/**
 * Side-scrolling galleries ("carousels").
 *
 * Any Query Loop block with the extra CSS class `sbe-carousel` (Block → Advanced →
 * Additional CSS class) shows its items in one row that scrolls sideways, 1-4 cards
 * (plus a slice of the next) visible depending on screen width, like the grids, with ← → buttons added next to the heading row above it.
 * Used on Home for "News" and "Ongoing projects" (9 items each).
 *
 * Class `sbe-carousel-mobile`: same side-scrolling row, but only on phones (<=600px), and
 * without arrows (swipe). On larger screens it stays a normal grid. Currently unused:
 * the Projects page galleries show big cards, one per row, on phones.
 * Pure CSS scrolling (scroll-snap); the small script only adds the arrow buttons.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the carousel CSS/JS only on pages that contain a carousel.
 */
function sbe_carousel_enqueue( $content, $block ) {
	$class = $block['attrs']['className'] ?? '';
	if ( ! preg_match( '/(^|\s)sbe-carousel(-mobile)?(\s|$)/', $class ) ) {
		return $content;
	}
	$css = SBE_PROJECTS_DIR . '/assets/carousel.css';
	$js  = SBE_PROJECTS_DIR . '/assets/carousel.js';
	wp_enqueue_style( 'sbe-carousel', plugins_url( 'assets/carousel.css', SBE_PROJECTS_FILE ), array(), (string) filemtime( $css ) );
	wp_enqueue_script(
		'sbe-carousel',
		plugins_url( 'assets/carousel.js', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( $js ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	return $content;
}
add_filter( 'render_block_core/query', 'sbe_carousel_enqueue', 10, 2 );
