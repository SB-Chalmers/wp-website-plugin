<?php
/**
 * "Related content" gallery at the bottom of posts, projects and resources.
 *
 * Shows other posts, projects AND resources that share at least one tag with the one
 * being read, most shared tags first, then newest first (max 12).
 *
 * Set up in the Single Posts / Single Project / Single Resource templates as:
 *   Group (extra CSS class `sbe-related-content`)
 *     Heading "Related content"
 *     Query Loop (extra CSS classes `sbe-carousel sbe-related`)
 *
 * - The Query Loop's own settings (post type, order, …) are ignored: this file replaces
 *   its query (the Post Template gets a `sbeRelated` flag in its block context). It's a side-scrolling gallery because of `sbe-carousel` (includes/carousel.php).
 * - The whole group is left out when there's nothing related (no tags, or no other
 *   post/project/resource with those tags).
 * - Resources have no picture in most cases: their card shows the gallery icon instead
 *   (includes/placeholders.php).
 * - Each card says what it is in a grey line under the title ("Project · Ongoing",
 *   "Interview · 3 March 2025", "Resource"), the same line as in search results
 *   (sbe_search_result_type() in includes/search.php, enabled by sbe_related_rendering()).
 */

defined( 'ABSPATH' ) || exit;

const SBE_RELATED_MAX   = 12;
const SBE_RELATED_TYPES = array( 'post', 'project', 'resource' );

/**
 * IDs of published posts/projects/resources sharing tags with an item, best matches first.
 *
 * @param int $post_id Post, project or resource ID.
 * @return int[]
 */
function sbe_related_ids( $post_id ) {
	static $cache = array();
	$post_id      = (int) $post_id;
	$preview      = sbe_related_preview();
	$preview      = $preview && $preview['id'] === $post_id ? $preview : null;
	if ( ! $preview && isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	if ( ! $preview ) {
		$cache[ $post_id ] = array();
	}

	if ( ! $post_id || ! in_array( get_post_type( $post_id ), SBE_RELATED_TYPES, true ) ) {
		return array();
	}
	// Editor preview: the unsaved tags from the Add content sidebar instead of the saved ones.
	$tags = $preview ? $preview['tags'] : wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $tags ) || ! $tags ) {
		return array();
	}

	$candidates = get_posts(
		array(
			'post_type'           => SBE_RELATED_TYPES,
			'post_status'         => 'publish',
			'tag__in'             => $tags,
			'post__not_in'        => array( $post_id ),
			'posts_per_page'      => 100,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		)
	);

	$scored = array();
	foreach ( $candidates as $i => $candidate ) {
		$shared   = array_intersect( $tags, wp_list_pluck( get_the_tags( $candidate->ID ) ?: array(), 'term_id' ) );
		$scored[] = array( $candidate->ID, count( $shared ), $i );
	}
	// Most shared tags first; ties keep the newest-first order.
	usort(
		$scored,
		function ( $a, $b ) {
			return array( $b[1], $a[2] ) <=> array( $a[1], $b[2] );
		}
	);

	$ids = array_slice( array_map( 'intval', array_column( $scored, 0 ) ), 0, SBE_RELATED_MAX );
	if ( ! $preview ) {
		$cache[ $post_id ] = $ids;
	}
	return $ids;
}

/**
 * Editor preview state: array( 'id' => post ID, 'tags' => unsaved tag IDs ) while
 * sbe_related_preview_rest() renders the gallery, else null. Pass a value to set it.
 *
 * @param array|null|false $set New state (null clears), or false to read.
 * @return array|null
 */
function sbe_related_preview( $set = false ) {
	static $state = null;
	if ( false !== $set ) {
		$state = $set;
	}
	return $state;
}

/**
 * The post/project/resource being read, or 0.
 *
 * @return int
 */
function sbe_related_current_id() {
	$preview = sbe_related_preview();
	if ( $preview ) {
		return $preview['id'];
	}
	return is_singular( SBE_RELATED_TYPES ) ? (int) get_queried_object_id() : 0;
}

/**
 * Leave out the whole "Related content" group when there's nothing to show.
 *
 * @param string|null $pre   Short-circuit output.
 * @param array       $block Parsed block.
 * @return string|null
 */
function sbe_related_maybe_hide( $pre, $block ) {
	if ( null !== $pre || 'core/group' !== ( $block['blockName'] ?? '' ) || ! sbe_block_has_class( $block, 'sbe-related-content' ) ) {
		return $pre;
	}
	return sbe_related_ids( sbe_related_current_id() ) ? null : '';
}
add_filter( 'pre_render_block', 'sbe_related_maybe_hide', 10, 2 );

/**
 * Is a `sbe-related` Query Loop being rendered right now? Pass true/false to set it
 * (start of the Query Loop in sbe_related_enqueue(), end in sbe_related_rendering_end()).
 *
 * @param bool|null $set New state, or null to read.
 * @return bool
 */
function sbe_related_rendering( $set = null ) {
	static $on = false;
	if ( null !== $set ) {
		$on = (bool) $set;
	}
	return $on;
}

/**
 * End of a `sbe-related` Query Loop: stop adding type lines.
 *
 * @param string $content Rendered Query Loop.
 * @param array  $block   Parsed block.
 * @return string
 */
function sbe_related_rendering_end( $content, $block ) {
	if ( sbe_block_has_class( $block, 'sbe-related' ) ) {
		sbe_related_rendering( false );
	}
	return $content;
}
add_filter( 'render_block_core/query', 'sbe_related_rendering_end', 5, 2 );

/**
 * Load the small stylesheet (spacing under the heading) when a related gallery is shown,
 * and mark the start of its rendering (type lines under the card titles).
 *
 * @param array $block Parsed block.
 * @return array
 */
function sbe_related_enqueue( $block ) {
	if ( 'core/query' === ( $block['blockName'] ?? '' ) && sbe_block_has_class( $block, 'sbe-related' ) ) {
		sbe_related_rendering( true );
		wp_enqueue_style(
			'sbe-categories-tags',
			plugins_url( 'assets/categories-tags.css', SBE_PROJECTS_FILE ),
			array(),
			(string) filemtime( SBE_PROJECTS_DIR . '/assets/categories-tags.css' )
		);
	}
	return $block;
}
add_filter( 'render_block_data', 'sbe_related_enqueue' );

/**
 * Flag the Post Template inside a `sbe-related` Query Loop, so its query can be replaced
 * in sbe_related_query_vars().
 *
 * @param array         $context      Block context.
 * @param array         $parsed_block Block being rendered.
 * @param WP_Block|null $parent_block Parent block.
 * @return array
 */
function sbe_related_mark_context( $context, $parsed_block, $parent_block ) {
	if (
		'core/post-template' === ( $parsed_block['blockName'] ?? '' )
		&& $parent_block instanceof WP_Block
		&& 'core/query' === $parent_block->name
		&& sbe_block_has_class( $parent_block->parsed_block, 'sbe-related' )
	) {
		$context['query']               = (array) ( $context['query'] ?? array() );
		$context['query']['sbeRelated'] = true;
	}
	return $context;
}
add_filter( 'render_block_context', 'sbe_related_mark_context', 10, 3 );

/**
 * Replace the query of a `sbe-related` Query Loop with the related posts/projects/resources.
 *
 * @param array    $query Query vars built from the block settings.
 * @param WP_Block $block Post Template block.
 * @return array
 */
function sbe_related_query_vars( $query, $block ) {
	if ( empty( $block->context['query']['sbeRelated'] ) ) {
		return $query;
	}
	$ids = sbe_related_ids( sbe_related_current_id() );
	return array(
		'post_type'           => SBE_RELATED_TYPES,
		'post_status'         => 'publish',
		'post__in'            => $ids ? $ids : array( 0 ),
		'orderby'             => 'post__in',
		'posts_per_page'      => SBE_RELATED_MAX,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
}
add_filter( 'query_loop_block_query_vars', 'sbe_related_query_vars', 10, 2 );

/**
 * Editor preview of Related content (since 1.24.0, assets/live-preview.js).
 *
 * POST sbe/v1/related-preview { post_id, tags[] } → { html }: the Related content
 * Query Loop of the template the post uses, rendered for the unsaved tags, exactly as
 * the site would show it. Empty html = nothing related (the site hides the section).
 * Only for people who may edit that post.
 */
function sbe_related_preview_route() {
	register_rest_route(
		'sbe/v1',
		'/related-preview',
		array(
			'methods'             => 'POST',
			'callback'            => 'sbe_related_preview_rest',
			'permission_callback' => function ( $request ) {
				return current_user_can( 'edit_post', (int) $request['post_id'] );
			},
			'args'                => array(
				'post_id' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'tags'    => array(
					'type'    => 'array',
					'items'   => array( 'type' => 'integer' ),
					'default' => array(),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'sbe_related_preview_route' );

/**
 * First Query Loop with the class `sbe-related` in a list of parsed blocks.
 *
 * @param array $blocks Parsed blocks.
 * @return array|null
 */
function sbe_related_find_query( $blocks ) {
	foreach ( $blocks as $block ) {
		if ( 'core/query' === ( $block['blockName'] ?? '' ) && sbe_block_has_class( $block, 'sbe-related' ) ) {
			return $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = sbe_related_find_query( $block['innerBlocks'] );
			if ( $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * REST callback of sbe/v1/related-preview.
 *
 * @param WP_REST_Request $request Request.
 * @return array
 */
function sbe_related_preview_rest( $request ) {
	$post_id = (int) $request['post_id'];
	$type    = get_post_type( $post_id );
	if ( ! in_array( $type, SBE_RELATED_TYPES, true ) ) {
		return array( 'html' => '' );
	}
	$tags = array_values( array_filter( array_map( 'absint', (array) $request['tags'] ) ) );
	$tags = $tags ? get_terms(
		array(
			'taxonomy'   => 'post_tag',
			'include'    => $tags,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	) : array();
	if ( is_wp_error( $tags ) || ! $tags ) {
		return array( 'html' => '' );
	}

	// The template the site uses for this post (database copy, plugin or theme file).
	$template = function_exists( 'resolve_block_template' )
		? resolve_block_template( 'single', array( 'single-' . $type, 'single' ), '' )
		: null;
	$query = $template && ! empty( $template->content ) ? sbe_related_find_query( parse_blocks( $template->content ) ) : null;
	if ( ! $query ) {
		return array( 'html' => '' );
	}

	sbe_related_preview( array( 'id' => $post_id, 'tags' => array_map( 'intval', $tags ) ) );
	$html = sbe_related_ids( $post_id ) ? render_block( $query ) : '';
	sbe_related_preview( null );
	sbe_related_rendering( false );

	return array( 'html' => $html );
}
