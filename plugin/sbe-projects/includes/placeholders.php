<?php
/**
 * Placeholder pictures in galleries.
 *
 * A post or project without a featured image would leave a gap in a gallery
 * card (Home, News, Projects, Related content, search results). Inside Query
 * Loops, an empty Featured Image block is replaced by a light box with the
 * title's initials on a colour from the resource palette (saved per item, see
 * includes/resources.php). A resource without a featured image shows its gallery
 * icon (sbe_icon) in a light box instead, when it has one.
 * Single pages are not affected.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initials of a title, e.g. "Circular Business Models" → "CB", "INDIMASS" → "I".
 * First letters of the first two words that start with a letter or digit.
 *
 * @param string $title Title.
 * @return string
 */
function sbe_placeholder_initials( $title ) {
	$words = preg_split( '/[\s\-–—:]+/u', trim( wp_strip_all_tags( html_entity_decode( $title ) ) ) );
	$out   = '';
	foreach ( $words as $word ) {
		if ( preg_match( '/^[\p{L}\p{N}]/u', $word ) ) {
			$out .= mb_substr( $word, 0, 1 );
		}
		if ( mb_strlen( $out ) >= 2 ) {
			break;
		}
	}
	return mb_strtoupper( $out );
}

/**
 * Replace an empty Featured Image block inside a Query Loop with a placeholder.
 *
 * @param string   $content Rendered block.
 * @param array    $block   Parsed block.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function sbe_featured_image_placeholder( $content, $block, $instance ) {
	if ( '' !== trim( $content ) ) {
		return $content;
	}
	// Only in galleries: the block context has no queryId here, so a card is
	// recognised as a featured image of a post other than the page being shown
	// (the page's own featured image, e.g. on a single post, stays empty).
	$post_id = (int) ( $instance->context['postId'] ?? 0 );
	if ( ! $post_id || $post_id === (int) get_queried_object_id() || has_post_thumbnail( $post_id ) ) {
		return $content;
	}
	$attrs = $block['attrs'] ?? array();
	$ratio = ! empty( $attrs['aspectRatio'] ) && 'auto' !== $attrs['aspectRatio'] ? $attrs['aspectRatio'] : '16/9';
	$title = get_the_title( $post_id );
	$icon  = 'resource' === get_post_type( $post_id ) ? (int) get_post_meta( $post_id, 'sbe_icon', true ) : 0;
	$class = 'sbe-featured-placeholder';
	if ( $icon && wp_attachment_is_image( $icon ) ) {
		// Resource: its gallery icon, centred in the box.
		$inner  = wp_get_attachment_image( $icon, 'medium', false, array( 'class' => 'sbe-featured-placeholder__icon', 'alt' => '', 'loading' => 'lazy' ) );
		$class .= ' sbe-featured-placeholder--icon';
	} else {
		// Resources use the same letters as on the Resources page (first + last word).
		$letters = 'resource' === get_post_type( $post_id ) && function_exists( 'sbe_resources_initials' )
			? sbe_resources_initials( $post_id )
			: sbe_placeholder_initials( $title );
		$inner   = '<span>' . esc_html( $letters ) . '</span>';
	}
	$style = 'aspect-ratio:' . $ratio . ';';
	if ( ! $icon && function_exists( 'sbe_resources_placeholder_style' ) ) {
		// No picture or icon: a colour from the resource palette (a resource gets the same
		// colour as on the Resources page; posts/projects get their own, saved once).
		$style .= sbe_resources_placeholder_style( $post_id );
		$class .= ' sbe-featured-placeholder--colored';
	}
	$box = '<span class="sbe-featured-placeholder__box" style="' . esc_attr( $style ) . '">' . $inner . '</span>';
	if ( ! empty( $attrs['isLink'] ) ) {
		$box = '<a href="' . esc_url( get_permalink( $post_id ) ) . '" tabindex="-1" aria-hidden="true">' . $box . '</a>';
	}
	sbe_placeholder_enqueue();
	return '<figure class="wp-block-post-featured-image ' . $class . '">' . $box . '</figure>';
}
add_filter( 'render_block_core/post-featured-image', 'sbe_featured_image_placeholder', 10, 3 );

/**
 * Placeholder styles (only on pages that use a placeholder).
 */
function sbe_placeholder_enqueue() {
	$css = SBE_PROJECTS_DIR . '/assets/placeholders.css';
	wp_enqueue_style( 'sbe-placeholders', plugins_url( 'assets/placeholders.css', SBE_PROJECTS_FILE ), array(), (string) filemtime( $css ) );
}
