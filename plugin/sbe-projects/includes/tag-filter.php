<?php
/**
 * Tag filter, clickable tags and the tag gallery (since 1.18.0).
 *
 * - Tag filter: a Query Loop with the extra CSS class `sbe-tag-filter` (News, Projects
 *   Ongoing + Completed, Resources) is limited to the tag in `?tagged=<slug>`. The first
 *   such Query Loop on a page gets a row of tag links above its gallery:
 *   "Tags  All · <most used tags> · More tags (n)"; the rest of the tags (alphabetical) are
 *   folded away behind "More tags" (assets/tag-filter.js). Only tags used by published
 *   items of that gallery's post type are listed. (`?tag=` can't be used: it is
 *   WordPress's own tag-archive query variable.)
 * - With "Reload full page" off and only one filtered gallery on the page (News), choosing a
 *   tag swaps the gallery in place like the page numbers. Projects has two galleries and
 *   live counts in the jump links, so it reloads the page.
 * - Tags under gallery titles (`sbe-card-tags`) are links:
 *     in a filtered gallery          → the same page filtered by that tag (again = All);
 *     on Home (side-scrolling rows)  → the News / Projects / Resources page filtered by it;
 *     Related content, search, tag gallery → the tag gallery /tag/<slug>/.
 * - Tags on single posts, projects and resources (Post Terms block `sbe-single-tags`, just
 *   before Related content) link to the tag gallery.
 * - Tag gallery: plugin template `templates/tag.html` (Query Loop class `sbe-tag-results`,
 *   inheriting the main query): everything with that tag, with the "All · News · Projects
 *   · Resources" row from the search (includes/search.php) and a type line on each card.
 */

defined( 'ABSPATH' ) || exit;

const SBE_TAG_FILTER_PARAM   = 'tagged';
const SBE_TAG_FILTER_VISIBLE = 8; // Tags shown before "More tags".

/**
 * The tag chosen in the filter (?tagged=<slug>), or null for "All".
 *
 * @return WP_Term|null
 */
function sbe_tag_filter_current() {
	$raw = $_GET[ SBE_TAG_FILTER_PARAM ] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! is_string( $raw ) || '' === $raw ) {
		return null;
	}
	$term = get_term_by( 'slug', sanitize_title( wp_unslash( $raw ) ), 'post_tag' );
	return $term instanceof WP_Term ? $term : null;
}

/**
 * URL of the page being shown, without query arguments (filter links start on page 1).
 *
 * @return string
 */
function sbe_tag_filter_base_url() {
	$id = get_queried_object_id();
	if ( $id && is_singular() ) {
		return get_permalink( $id );
	}
	return strtok( add_query_arg( array() ), '?' );
}

/**
 * Find all core/query blocks (recursively) in parsed blocks.
 *
 * @param array $blocks Parsed blocks.
 * @return array[]
 */
function sbe_tag_filter_find_queries( $blocks ) {
	$found = array();
	foreach ( $blocks as $block ) {
		if ( 'core/query' === ( $block['blockName'] ?? '' ) ) {
			$found[] = $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = array_merge( $found, sbe_tag_filter_find_queries( $block['innerBlocks'] ) );
		}
	}
	return $found;
}

/**
 * Number of `sbe-tag-filter` Query Loops in the content of the page being shown.
 *
 * @return int
 */
function sbe_tag_filter_count_on_page() {
	static $count = null;
	if ( null === $count ) {
		$count = 0;
		$post  = is_singular() ? get_post( get_queried_object_id() ) : null;
		if ( $post && false !== strpos( $post->post_content, 'sbe-tag-filter' ) ) {
			foreach ( sbe_tag_filter_find_queries( parse_blocks( $post->post_content ) ) as $q ) {
				$count += sbe_block_has_class( $q, 'sbe-tag-filter' ) ? 1 : 0;
			}
		}
	}
	return $count;
}

/**
 * The published page with a `sbe-tag-filter` gallery for this post type (News for posts,
 * Projects for projects, Resources for resources), or '' when there is none.
 *
 * @param string $post_type Post type.
 * @return string URL.
 */
function sbe_tag_filter_page_url( $post_type ) {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array();
		global $wpdb;
		$ids   = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_content LIKE '%sbe-tag-filter%' ORDER BY menu_order, ID LIMIT 20" );
		$pages = array_filter( array_map( 'get_post', $ids ) );
		foreach ( $pages as $page ) {
			foreach ( sbe_tag_filter_find_queries( parse_blocks( $page->post_content ) ) as $q ) {
				$type = $q['attrs']['query']['postType'] ?? 'post';
				if ( sbe_block_has_class( $q, 'sbe-tag-filter' ) && ! isset( $cache[ $type ] ) ) {
					$cache[ $type ] = get_permalink( $page );
				}
			}
		}
	}
	return $cache[ $post_type ] ?? '';
}

/**
 * Stack of the Query Loops being rendered, so blocks inside a card know which gallery
 * they are in. Pushed in render_block_data, popped when the Query Loop has rendered.
 *
 * @param string     $action 'push', 'pop' or 'top'.
 * @param array|null $item   Item to push.
 * @return array|null Top item.
 */
function sbe_tag_query_stack( $action = 'top', $item = null ) {
	static $stack = array();
	if ( 'push' === $action ) {
		$stack[] = $item;
	} elseif ( 'pop' === $action ) {
		array_pop( $stack );
	}
	return $stack ? end( $stack ) : null;
}

/**
 * Before a Query Loop renders: remember it, and limit a `sbe-tag-filter` one to the
 * chosen tag (added to its own taxonomy filter, e.g. project status).
 *
 * @param array $block Parsed block.
 * @return array
 */
function sbe_tag_filter_query( $block ) {
	if ( 'core/query' !== ( $block['blockName'] ?? '' ) ) {
		return $block;
	}
	$filter = sbe_block_has_class( $block, 'sbe-tag-filter' );
	$term   = $filter ? sbe_tag_filter_current() : null;
	$tax    = $block['attrs']['query']['taxQuery'] ?? array();
	$tax    = is_array( $tax ) ? $tax : array();

	// Project status of the gallery (for the "No ongoing projects tagged …" text).
	$status_ids = $tax['include']['project_status'] ?? ( $tax['project_status'] ?? array() );
	$status     = $status_ids ? get_term( (int) reset( $status_ids ), 'project_status' ) : null;

	if ( $term ) {
		if ( ! $tax || isset( $tax['include'] ) || isset( $tax['exclude'] ) ) {
			$tax['include']             = (array) ( $tax['include'] ?? array() );
			$tax['include']['post_tag'] = array( $term->term_id );
		} else {
			$tax['post_tag'] = array( $term->term_id ); // Older taxQuery format.
		}
		$block['attrs']['query']['taxQuery'] = $tax;
	}

	sbe_tag_query_stack(
		'push',
		array(
			'filter'    => $filter,
			'in_place'  => $filter && ! empty( $block['attrs']['enhancedPagination'] ) && 1 === sbe_tag_filter_count_on_page(),
			'related'   => sbe_block_has_class( $block, 'sbe-related' ),
			'post_type' => (string) ( $block['attrs']['query']['postType'] ?? 'post' ),
			'status'    => $status instanceof WP_Term ? $status->name : '',
		)
	);
	return $block;
}
add_filter( 'render_block_data', 'sbe_tag_filter_query' );

/**
 * After a Query Loop has rendered: forget it.
 *
 * @param string $content Rendered block.
 * @return string
 */
function sbe_tag_query_stack_pop( $content ) {
	sbe_tag_query_stack( 'pop' );
	return $content;
}
add_filter( 'render_block_core/query', 'sbe_tag_query_stack_pop', 1 );

/**
 * Interactivity directives that make a link swap the gallery in place.
 *
 * @param WP_HTML_Tag_Processor $tags Processor positioned on the link.
 * @param string                $key  Unique key.
 */
function sbe_tag_filter_add_navigate( $tags, $key ) {
	$tags->set_attribute( 'data-wp-key', $key );
	$tags->set_attribute( 'data-wp-on--click', 'core/query::actions.navigate' );
	$tags->set_attribute( 'data-wp-on-async--mouseenter', 'core/query::actions.prefetch' );
}

/**
 * Where a tag on a card or single page links to.
 *
 * @param WP_Term    $term      Tag.
 * @param array|null $ctx       Gallery the card is in (null = not in a gallery).
 * @param string     $post_type Post type of the card.
 * @return string
 */
function sbe_tag_link_target( $term, $ctx, $post_type ) {
	if ( $ctx && $ctx['filter'] ) {
		$current = sbe_tag_filter_current();
		if ( $current && $current->term_id === $term->term_id ) {
			return sbe_tag_filter_base_url(); // Clicking the active tag again shows all.
		}
		return add_query_arg( SBE_TAG_FILTER_PARAM, $term->slug, sbe_tag_filter_base_url() );
	}
	if ( $ctx && ! $ctx['related'] && ! is_search() && ! is_tag() ) {
		$page = sbe_tag_filter_page_url( $post_type );
		if ( $page ) {
			return add_query_arg( SBE_TAG_FILTER_PARAM, $term->slug, $page );
		}
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '' : $link;
}

/**
 * Tags (Post Terms block for post_tag) link to the filter or the tag gallery, see above.
 *
 * @param string   $content  Rendered Post Terms.
 * @param array    $block    Parsed block.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function sbe_tag_links( $content, $block, $instance ) {
	if ( 'post_tag' !== ( $block['attrs']['term'] ?? '' ) || '' === trim( $content ) ) {
		return $content;
	}
	$post_id = (int) ( $instance->context['postId'] ?? 0 );
	$terms   = $post_id ? get_the_terms( $post_id, 'post_tag' ) : array();
	if ( ! $terms || is_wp_error( $terms ) ) {
		return $content;
	}
	$by_link = array();
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$by_link[ untrailingslashit( $link ) ] = $term;
		}
	}

	$ctx       = sbe_tag_query_stack();
	$post_type = get_post_type( $post_id );
	$current   = $ctx && $ctx['filter'] ? sbe_tag_filter_current() : ( is_tag() ? get_queried_object() : null );
	$tags      = new WP_HTML_Tag_Processor( $content );
	while ( $tags->next_tag( 'a' ) ) {
		$term = $by_link[ untrailingslashit( (string) $tags->get_attribute( 'href' ) ) ] ?? null;
		if ( ! $term ) {
			continue;
		}
		$url = sbe_tag_link_target( $term, $ctx, $post_type );
		if ( '' === $url ) {
			continue;
		}
		$tags->set_attribute( 'href', $url );
		$tags->remove_attribute( 'rel' );
		if ( $current instanceof WP_Term && $current->term_id === $term->term_id ) {
			$tags->add_class( 'is-current' );
		}
		if ( $ctx && $ctx['in_place'] ) {
			sbe_tag_filter_add_navigate( $tags, 'sbe-cardtag-' . $post_id . '-' . $term->slug );
		}
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/post-terms', 'sbe_tag_links', 10, 3 );

/**
 * Tags used by published items of a post type, most used first.
 *
 * @param string $post_type Post type.
 * @return WP_Term[]
 */
function sbe_tag_filter_terms( $post_type ) {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT tt.term_id, COUNT(DISTINCT p.ID) AS n
			FROM {$wpdb->term_taxonomy} tt
			INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			WHERE tt.taxonomy = 'post_tag' AND p.post_type = %s AND p.post_status = 'publish'
			GROUP BY tt.term_id",
			$post_type
		)
	);
	$terms  = array();
	$counts = array();
	foreach ( $rows as $row ) {
		$term = get_term( (int) $row->term_id, 'post_tag' );
		if ( $term instanceof WP_Term ) {
			$terms[]                   = $term;
			$counts[ $term->term_id ] = (int) $row->n;
		}
	}
	usort(
		$terms,
		function ( $a, $b ) use ( $counts ) {
			return ( $counts[ $b->term_id ] <=> $counts[ $a->term_id ] ) ?: strcasecmp( $a->name, $b->name );
		}
	);
	return $terms;
}

/**
 * The tag row above the first `sbe-tag-filter` gallery on the page.
 *
 * @param string $content Rendered Query Loop.
 * @param array  $block   Parsed block.
 * @return string
 */
function sbe_tag_filter_nav( $content, $block ) {
	static $done = false;
	if ( $done || ! sbe_block_has_class( $block, 'sbe-tag-filter' ) || sbe_tag_filter_has_shortcode() ) {
		return $content;
	}
	$done = true;
	$html = sbe_tag_filter_nav_html(
		(string) ( $block['attrs']['query']['postType'] ?? 'post' ),
		! empty( $block['attrs']['enhancedPagination'] ) && 1 === sbe_tag_filter_count_on_page()
	);
	if ( '' === $html ) {
		return $content;
	}
	// Insert right after the Query Loop's opening tag, so it's inside the part that is swapped.
	return preg_replace( '/^(\s*<[^>]+>)/', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $html ), $content, 1 );
}
add_filter( 'render_block_core/query', 'sbe_tag_filter_nav', 20, 2 );

/**
 * Does the page being shown place the tag row itself with [sbe_tag_filter]?
 *
 * @return bool
 */
function sbe_tag_filter_has_shortcode() {
	$post = is_singular() ? get_post( get_queried_object_id() ) : null;
	return $post && has_shortcode( $post->post_content, 'sbe_tag_filter' );
}

/**
 * [sbe_tag_filter type="project"]: the tag row at this spot instead of inside the first
 * gallery. Used on the Projects page, where it filters both galleries (Ongoing and
 * Completed), so it sits above both headings. Choosing a tag reloads the page.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function sbe_tag_filter_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'post' ), $atts, 'sbe_tag_filter' );
	return sbe_tag_filter_nav_html( sanitize_key( $atts['type'] ), false );
}
add_shortcode( 'sbe_tag_filter', 'sbe_tag_filter_shortcode' );

/**
 * The tag row.
 *
 * @param string $post_type Post type whose tags are listed.
 * @param bool   $in_place  Add the directives that swap the gallery in place.
 * @return string HTML, '' when there are no tags.
 */
function sbe_tag_filter_nav_html( $post_type, $in_place ) {
	$terms   = sbe_tag_filter_terms( $post_type );
	$current = sbe_tag_filter_current();
	if ( ! $terms && ! $current ) {
		return '';
	}

	// Most used tags first; the rest alphabetically behind "More tags". The chosen tag is
	// always visible.
	$visible = array_slice( $terms, 0, SBE_TAG_FILTER_VISIBLE );
	$extra   = array_slice( $terms, SBE_TAG_FILTER_VISIBLE );
	if ( $current && ! in_array( $current->term_id, wp_list_pluck( $visible, 'term_id' ), true ) ) {
		$extra     = array_values( array_filter( $extra, fn( $t ) => $t->term_id !== $current->term_id ) );
		$visible[] = $current;
	}
	usort( $extra, fn( $a, $b ) => strcasecmp( $a->name, $b->name ) );

	sbe_tag_filter_enqueue();

	$base = sbe_tag_filter_base_url();
	$link = function ( $label, $url, $active, $key, $class = '' ) use ( $in_place ) {
		$tags = new WP_HTML_Tag_Processor( '<li><a>' . esc_html( $label ) . '</a></li>' );
		if ( $class ) {
			$tags->next_tag( 'li' );
			$tags->add_class( $class );
		}
		$tags->next_tag( 'a' );
		$tags->set_attribute( 'href', $url );
		if ( $active ) {
			$tags->set_attribute( 'aria-current', 'page' );
			$tags->add_class( 'is-active' );
		}
		if ( $in_place ) {
			sbe_tag_filter_add_navigate( $tags, 'sbe-tag-' . $key );
		}
		return $tags->get_updated_html();
	};

	$html  = '<nav class="sbe-category-filter__nav sbe-tag-filter__nav" aria-label="' . esc_attr__( 'Filter by tag', 'sbe-projects' ) . '">';
	$html .= '<ul class="sbe-category-filter__list sbe-tag-filter__list">';
	$html .= '<li class="sbe-tag-filter__label" aria-hidden="true">' . esc_html__( 'Tags', 'sbe-projects' ) . '</li>';
	$html .= $link( __( 'All', 'sbe-projects' ), $base, null === $current, 'all' );
	foreach ( $visible as $term ) {
		$html .= $link( $term->name, add_query_arg( SBE_TAG_FILTER_PARAM, $term->slug, $base ), $current && $current->term_id === $term->term_id, $term->slug );
	}
	foreach ( $extra as $term ) {
		$html .= $link( $term->name, add_query_arg( SBE_TAG_FILTER_PARAM, $term->slug, $base ), false, $term->slug, 'is-extra' );
	}
	if ( $extra ) {
		/* translators: %d: number of hidden tags. */
		$more  = sprintf( __( 'More tags (%d)', 'sbe-projects' ), count( $extra ) );
		$html .= sprintf(
			'<li class="sbe-tag-filter__toggle-item"><button type="button" class="sbe-tag-filter__toggle" aria-expanded="false" data-more="%1$s" data-less="%2$s">%1$s</button></li>',
			esc_attr( $more ),
			esc_attr__( 'Fewer tags', 'sbe-projects' )
		);
	}
	$html .= '</ul>';
	// Without JavaScript: show every tag, no toggle.
	$html .= '<noscript><style>.sbe-tag-filter__list .is-extra{display:block!important}.sbe-tag-filter__toggle-item{display:none!important}</style></noscript>';
	$html .= '</nav>';
	return $html;
}

/**
 * A filtered gallery with nothing in it: "No ongoing projects tagged “LCA”. Show all".
 *
 * @param string $content Rendered No Results block.
 * @return string
 */
function sbe_tag_filter_no_results( $content ) {
	$ctx  = sbe_tag_query_stack();
	$term = $ctx && $ctx['filter'] ? sbe_tag_filter_current() : null;
	if ( ! $term || '' === trim( $content ) ) {
		return $content;
	}
	if ( 'project' === $ctx['post_type'] ) {
		$what = $ctx['status']
			/* translators: %s: project status, e.g. "ongoing". */
			? sprintf( __( '%s projects', 'sbe-projects' ), strtolower( $ctx['status'] ) )
			: __( 'projects', 'sbe-projects' );
	} elseif ( 'resource' === $ctx['post_type'] ) {
		$what = __( 'resources', 'sbe-projects' );
	} else {
		$what = __( 'news', 'sbe-projects' );
	}
	$text = sprintf(
		/* translators: 1: "ongoing projects", "news" …, 2: tag name. */
		__( 'No %1$s tagged “%2$s”.', 'sbe-projects' ),
		$what,
		$term->name
	);
	$link = get_term_link( $term );
	$html = '<p>' . esc_html( $text );
	if ( ! is_wp_error( $link ) ) {
		$html .= ' <a href="' . esc_url( $link ) . '">' . esc_html__( 'Everything tagged with it', 'sbe-projects' ) . ' →</a>';
	}
	$html .= '</p>';
	return preg_replace( '#^(\s*<div\b[^>]*>).*(</div>\s*)$#s', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $html ) . '$2', $content, 1 );
}
add_filter( 'render_block_core/query-no-results', 'sbe_tag_filter_no_results' );

/**
 * Styles and the "More tags" script for the tag row.
 */
function sbe_tag_filter_enqueue() {
	wp_enqueue_style(
		'sbe-categories-tags',
		plugins_url( 'assets/categories-tags.css', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/categories-tags.css' )
	);
	wp_enqueue_script(
		'sbe-tag-filter',
		plugins_url( 'assets/tag-filter.js', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/tag-filter.js' ),
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);
}

/**
 * Tag link styles (cards, single pages, tag gallery) on every page.
 */
function sbe_tags_enqueue() {
	wp_enqueue_style(
		'sbe-tags',
		plugins_url( 'assets/tags.css', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/tags.css' )
	);
	if ( is_tag() ) {
		wp_enqueue_style(
			'sbe-categories-tags',
			plugins_url( 'assets/categories-tags.css', SBE_PROJECTS_FILE ),
			array(),
			(string) filemtime( SBE_PROJECTS_DIR . '/assets/categories-tags.css' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sbe_tags_enqueue' );

/**
 * "Tag" template (tag galleries /tag/<slug>/), editable in the Site Editor.
 */
function sbe_tags_register_template() {
	register_block_template(
		'sbe-projects//tag',
		array(
			'title'       => __( 'Tag', 'sbe-projects' ),
			'description' => __( 'Everything with a tag: news, projects and resources.', 'sbe-projects' ),
			'content'     => file_get_contents( SBE_PROJECTS_DIR . '/templates/tag.html' ),
		)
	);
}
add_action( 'init', 'sbe_tags_register_template' );

/**
 * Tag gallery title: "Tagged: LCA" instead of the theme's "Tag: LCA".
 *
 * @param string $prefix Prefix.
 * @return string
 */
function sbe_tags_archive_prefix( $prefix ) {
	return is_tag() ? _x( 'Tagged:', 'tag gallery title prefix', 'sbe-projects' ) : $prefix;
}
add_filter( 'get_the_archive_title_prefix', 'sbe_tags_archive_prefix' );
