<?php
/**
 * Query Pagination: when the block uses arrows, always show both arrows.
 * Core leaves out the "previous" arrow on the first page and the "next" arrow on the
 * last page; this adds a greyed-out, non-clickable arrow in their place
 * (class `is-disabled`, styled in Additional CSS).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the missing arrow(s) to a rendered Query Pagination block.
 *
 * @param string $content Rendered block HTML.
 * @param array  $block   Parsed block.
 * @return string
 */
function sbe_pagination_disabled_arrows( $content, $block ) {
	$arrow_type = $block['attrs']['paginationArrow'] ?? 'none';
	$arrows     = array(
		'arrow'   => array( '←', '→' ),
		'chevron' => array( '«', '»' ),
	);
	if ( ! isset( $arrows[ $arrow_type ] ) ) {
		return $content;
	}

	$has_prev = false !== strpos( $content, 'wp-block-query-pagination-previous' );
	$has_next = false !== strpos( $content, 'wp-block-query-pagination-next' );
	// Only one page (no arrows at all) or both already there: nothing to do.
	if ( $has_prev === $has_next ) {
		return $content;
	}

	$show_label = $block['attrs']['showLabel'] ?? true;
	$make       = function ( $dir ) use ( $arrows, $arrow_type, $show_label ) {
		$is_prev = 'previous' === $dir;
		$arrow   = sprintf(
			'<span class="wp-block-query-pagination-%1$s-arrow is-arrow-%2$s">%3$s</span>',
			$dir,
			esc_attr( $arrow_type ),
			$arrows[ $arrow_type ][ $is_prev ? 0 : 1 ]
		);
		$label   = $show_label ? esc_html( $is_prev ? __( 'Previous Page' ) : __( 'Next Page' ) ) : '';
		return sprintf(
			'<span class="wp-block-query-pagination-%1$s is-disabled" aria-hidden="true">%2$s</span>',
			$dir,
			$is_prev ? $arrow . $label : $label . $arrow
		);
	};

	if ( ! $has_prev ) {
		// Right after the wrapper's opening tag.
		$content = preg_replace( '/^(\s*<[^>]+>)/', '$1' . $make( 'previous' ), $content, 1 );
	}
	if ( ! $has_next ) {
		// Right before the wrapper's closing tag.
		$pos = strrpos( $content, '</' );
		if ( false !== $pos ) {
			$content = substr_replace( $content, $make( 'next' ), $pos, 0 );
		}
	}
	return $content;
}
add_filter( 'render_block_core/query-pagination', 'sbe_pagination_disabled_arrows', 10, 2 );
