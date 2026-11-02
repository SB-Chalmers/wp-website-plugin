<?php
/**
 * Site search for news posts, projects and resources.
 *
 * - The menu (Navigation block) ends with a "Search" link (class `sbe-search-toggle`).
 *   Clicking it opens a search panel that covers the page below the header, with one
 *   large centred field (assets/search.js; markup from sbe_search_overlay()). Esc or
 *   clicking "Search" again closes it (on phones also a "Close" button, or opening the
 *   menu). Without JavaScript the link goes to /?s=.
 *   Searching goes to /?s=<words>.
 * - The main search query only returns published posts, projects and resources (not
 *   pages or people). `?type=news` / `?type=projects` / `?type=resources` narrows it
 *   to one of them.
 * - Besides title, text and excerpt, a search also matches tag and category names,
 *   a project's second title (sbe_subtitle) and the names of tagged people (sbe_people; posts, projects and resources).
 * - Results page: DB template `search` (created once from templates/search.html,
 *   option `sbe_search_template_created`; edit it in the Site Editor afterwards).
 *   Its Query Loop has class `sbe-search-results`: a filter row "All (n) · News (n)
 *   · Projects (n) · Resources (n)" is added above the gallery. Each card shows what it
 *   is in a grey line under the title ("Project · Ongoing", "Interview · 3 March 2025",
 *   "Resource").
 */

defined( 'ABSPATH' ) || exit;

const SBE_SEARCH_PER_PAGE = 24; // Fills whole rows at 1, 2, 3 and 4 columns.

/**
 * Post types for a search, from ?type= (news | projects | resources), default all three.
 *
 * @return string[]
 */
function sbe_search_post_types() {
	$type = isset( $_GET['type'] ) && is_string( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'news' === $type ) {
		return array( 'post' );
	}
	if ( 'projects' === $type ) {
		return array( 'project' );
	}
	if ( 'resources' === $type ) {
		return array( 'resource' );
	}
	return array( 'post', 'project', 'resource' );
}

/**
 * Main search query: posts, projects and resources only, 24 per page.
 *
 * @param WP_Query $query Query.
 */
function sbe_search_limit_types( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$query->set( 'post_type', sbe_search_post_types() );
	$query->set( 'post_status', 'publish' );
	$query->set( 'posts_per_page', SBE_SEARCH_PER_PAGE );
	$query->set( 'sbe_search', 1 ); // Marks queries that also match tags / second titles.
}
add_action( 'pre_get_posts', 'sbe_search_limit_types' );

/**
 * Also match tag and category names, the project's second title and the names of
 * people tagged on the post or project (resources have no tagged people).
 *
 * @param string   $search SQL " AND (...)" from core.
 * @param WP_Query $query  Query.
 * @return string
 */
function sbe_search_extra_fields( $search, $query ) {
	global $wpdb;
	if ( '' === trim( $search ) || ! $query->get( 'sbe_search' ) ) {
		return $search;
	}
	$words = trim( (string) $query->get( 's' ) );
	if ( '' === $words ) {
		return $search;
	}
	$like  = '%' . $wpdb->esc_like( $words ) . '%';
	$extra = $wpdb->prepare(
		"{$wpdb->posts}.ID IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			WHERE tt.taxonomy IN ( 'post_tag', 'category' ) AND t.name LIKE %s )
		OR {$wpdb->posts}.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm
			WHERE pm.meta_key = 'sbe_subtitle' AND pm.meta_value LIKE %s )",
		$like,
		$like
	);
	// People: posts, projects and resources where a person whose name matches is tagged (sbe_people).
	$people = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'person' AND post_status = 'publish' AND post_title LIKE %s LIMIT 50",
			$like
		)
	);
	foreach ( array_map( 'absint', $people ) as $person_id ) {
		// sbe_people is a serialized list of IDs, e.g. a:2:{i:0;i:71;i:1;i:67;} → a tagged ID appears as ";i:<ID>;".
		$extra .= $wpdb->prepare(
			" OR {$wpdb->posts}.ID IN ( SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = 'sbe_people' AND pm.meta_value LIKE %s )",
			'%;i:' . $person_id . ';%'
		);
	}

	$inner = preg_replace( '/^\s*AND\s*/i', '', $search );
	return " AND ( {$inner} OR {$extra} ) ";
}
add_filter( 'posts_search', 'sbe_search_extra_fields', 10, 2 );

/**
 * Under each result title: what it is. Projects: "Project · Ongoing"; posts:
 * "<category> · <date>"; resources: "Resource".  Added to every Post Title block on the search
 * results page, the tag gallery (/tag/<slug>/) and in Related content galleries (includes/related.php) (a shortcode would
 * not work: template shortcodes run after the loop).
 *
 * @param string   $content  Rendered Post Title.
 * @param array    $block    Parsed block.
 * @param WP_Block $instance Block instance.
 * @return string
 */
function sbe_search_result_type( $content, $block, $instance ) {
	$in_related = function_exists( 'sbe_related_rendering' ) && sbe_related_rendering();
	if ( ( ! is_search() && ! is_tag() && ! $in_related ) || empty( $instance->context['postId'] ) ) {
		return $content;
	}
	$post = get_post( (int) $instance->context['postId'] );
	if ( ! $post || ! in_array( $post->post_type, array( 'post', 'project', 'resource' ), true ) ) {
		return $content;
	}
	if ( 'resource' === $post->post_type ) {
		$parts = array( __( 'Resource', 'sbe-projects' ) );
	} elseif ( 'project' === $post->post_type ) {
		$parts  = array( __( 'Project', 'sbe-projects' ) );
		$status = get_the_terms( $post, 'project_status' );
		if ( $status && ! is_wp_error( $status ) ) {
			$parts[] = $status[0]->name;
		}
	} else {
		$cats  = get_the_category( $post->ID );
		$parts = array( $cats ? $cats[0]->name : __( 'News', 'sbe-projects' ), get_the_date( '', $post ) );
	}
	return $content . '<div class="sbe-result-type">' . esc_html( implode( ' · ', $parts ) ) . '</div>';
}
add_filter( 'render_block_core/post-title', 'sbe_search_result_type', 10, 3 );

/**
 * Filter row "All (n) · News (n) · Projects (n) · Resources (n)" above a
 * `sbe-search-results` gallery, and above the `sbe-tag-results` gallery on tag pages.
 *
 * @param string $content Rendered Query Loop.
 * @param array  $block   Parsed block.
 * @return string
 */
function sbe_search_filter_links( $content, $block ) {
	if ( ! function_exists( 'sbe_block_has_class' ) ) {
		return $content;
	}
	$on_search = is_search() && sbe_block_has_class( $block, 'sbe-search-results' );
	$on_tag    = is_tag() && sbe_block_has_class( $block, 'sbe-tag-results' );
	if ( ! $on_search && ! $on_tag ) {
		return $content;
	}
	$words = get_search_query( false );
	$tag   = $on_tag ? get_queried_object() : null;
	$count = function ( $types ) use ( $words, $tag ) {
		$args = array(
			'post_type'      => $types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		if ( $tag instanceof WP_Term ) {
			$args['tag_id'] = $tag->term_id;
		} else {
			$args['s']          = $words;
			$args['sbe_search'] = 1;
		}
		$q = new WP_Query( $args );
		return (int) $q->found_posts;
	};
	$news      = $count( array( 'post' ) );
	$projects  = $count( array( 'project' ) );
	$resources = $count( array( 'resource' ) );
	$current   = sbe_search_post_types();
	$keys      = array(
		'post'     => 'news',
		'project'  => 'projects',
		'resource' => 'resources',
	);
	$current   = 1 === count( $current ) ? $keys[ $current[0] ] : 'all';
	$base      = $tag instanceof WP_Term ? get_term_link( $tag ) : add_query_arg( 's', rawurlencode( $words ), home_url( '/' ) );
	$items     = array(
		'all'       => array( __( 'All', 'sbe-projects' ), $news + $projects + $resources, $base ),
		'news'      => array( __( 'News', 'sbe-projects' ), $news, add_query_arg( 'type', 'news', $base ) ),
		'projects'  => array( __( 'Projects', 'sbe-projects' ), $projects, add_query_arg( 'type', 'projects', $base ) ),
		'resources' => array( __( 'Resources', 'sbe-projects' ), $resources, add_query_arg( 'type', 'resources', $base ) ),
	);

	$html = '<nav class="sbe-category-filter__nav sbe-search-filter" aria-label="' . ( $on_tag ? esc_attr__( 'Filter by type', 'sbe-projects' ) : esc_attr__( 'Filter search results', 'sbe-projects' ) ) . '"><ul class="sbe-category-filter__list">';
	foreach ( $items as $key => $item ) {
		list( $label, $n, $url ) = $item;
		$attrs = sprintf( ' href="%s"', esc_url( $url ) );
		if ( $key === $current ) {
			$attrs .= ' aria-current="page" class="is-active"';
		}
		$html .= '<li><a' . $attrs . '>' . esc_html( $label ) . ' <span class="sbe-search-filter__count">(' . $n . ')</span></a></li>';
	}
	$html .= '</ul></nav>';

	wp_enqueue_style(
		'sbe-categories-tags',
		plugins_url( 'assets/categories-tags.css', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/categories-tags.css' )
	);

	return preg_replace( '/^(\s*<[^>]+>)/', '$1' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $html ), $content, 1 );
}
add_filter( 'render_block_core/query', 'sbe_search_filter_links', 20, 2 );

/**
 * Search styles and the panel script, on every page (the menu has "Search" everywhere).
 */
function sbe_search_enqueue() {
	// Header menu → hamburger whenever it doesn't fit on one row (not search-specific, but
	// the "Search" item is what made the menu long). Footer, not deferred: runs before paint.
	wp_enqueue_style(
		'sbe-menu-fit',
		plugins_url( 'assets/menu-fit.css', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/menu-fit.css' )
	);
	wp_enqueue_script(
		'sbe-menu-fit',
		plugins_url( 'assets/menu-fit.js', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/menu-fit.js' ),
		array( 'in_footer' => true )
	);
	wp_enqueue_script(
		'sbe-search',
		plugins_url( 'assets/search.js', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/search.js' ),
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);
	wp_enqueue_style(
		'sbe-search',
		plugins_url( 'assets/search.css', SBE_PROJECTS_FILE ),
		array(),
		(string) filemtime( SBE_PROJECTS_DIR . '/assets/search.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'sbe_search_enqueue' );

/**
 * The search panel (hidden until "Search" in the menu is clicked). It sits fixed below
 * the sticky header (search.js sets its top to the header's bottom edge).
 */
function sbe_search_overlay() {
	?>
	<div id="sbe-search-overlay" class="sbe-search-overlay" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'sbe-projects' ); ?>">
		<button type="button" class="sbe-search-overlay__close"><?php esc_html_e( 'Close', 'sbe-projects' ); ?></button>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="sbe-search-overlay__form">
			<label class="screen-reader-text" for="sbe-search-overlay-input"><?php esc_html_e( 'Search news, projects and resources', 'sbe-projects' ); ?></label>
			<div class="sbe-search-overlay__field">
				<input id="sbe-search-overlay-input" class="sbe-search-overlay__input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search', 'sbe-projects' ); ?>" autocomplete="off">
				<button type="submit" class="sbe-search-overlay__submit" aria-label="<?php esc_attr_e( 'Search', 'sbe-projects' ); ?>"><span aria-hidden="true">→</span></button>
			</div>
			<p class="sbe-search-overlay__hint"><?php esc_html_e( 'Searches news, projects and resources, including tags and the people involved.', 'sbe-projects' ); ?></p>
		</form>
	</div>
	<?php
}
add_action( 'wp_footer', 'sbe_search_overlay' );

/**
 * "Search" in the menu is underlined (like the current page) on the results page.
 *
 * @param string $content Rendered Navigation Link.
 * @param array  $block   Parsed block.
 * @return string
 */
function sbe_search_menu_current( $content, $block ) {
	if ( ! is_search() || ! function_exists( 'sbe_block_has_class' ) || ! sbe_block_has_class( $block, 'sbe-search-toggle' ) ) {
		return $content;
	}
	$tags = new WP_HTML_Tag_Processor( $content );
	if ( $tags->next_tag( 'li' ) ) {
		$tags->add_class( 'sbe-current-section' );
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/navigation-link', 'sbe_search_menu_current', 10, 2 );

/**
 * Create the Search results template in the database once, from templates/search.html,
 * so it replaces the theme's search.html and can be edited in the Site Editor.
 */
function sbe_search_template_setup() {
	if ( get_option( 'sbe_search_template_created' ) ) {
		return;
	}
	$theme    = get_stylesheet();
	$existing = get_block_template( $theme . '//search', 'wp_template' );
	if ( $existing && 'custom' === $existing->source ) {
		update_option( 'sbe_search_template_created', 1 ); // Already customized: leave it.
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'wp_template',
			'post_name'    => 'search',
			'post_title'   => 'Search Results',
			'post_excerpt' => 'Displays search results for news, projects and resources.',
			'post_status'  => 'publish',
			'post_content' => file_get_contents( SBE_PROJECTS_DIR . '/templates/search.html' ),
			'tax_input'    => array( 'wp_theme' => array( $theme ) ),
		),
		true
	);
	if ( ! is_wp_error( $id ) ) {
		wp_set_object_terms( $id, $theme, 'wp_theme' ); // tax_input needs a capable user.
		update_option( 'sbe_search_template_created', 1 );
	}
}
add_action( 'init', 'sbe_search_template_setup', 30 );
