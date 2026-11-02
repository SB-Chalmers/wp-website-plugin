<?php
/**
 * Post categories and tags shared by posts, projects and resources.
 *
 * - Categories (core `category` taxonomy, posts only) say what KIND of post it is:
 *   Post, Interview, Article to start with. Editors add/rename/delete them under
 *   Posts → Categories. The default category ("Post") is used when none is ticked.
 * - Tags (core `post_tag` taxonomy) are shared by posts, projects AND resources, so one
 *   tag list covers all three. Tags drive the "Related content" gallery
 *   (includes/related.php) and are matched by the site search.
 * - Categories are no longer a filter on the News page (removed in 1.18.0); they are only
 *   shown in the grey type line on Related content / search / tag cards. Visitors filter
 *   by tag instead (includes/tag-filter.php).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Share the post tags with projects and resources.
 */
function sbe_tags_for_projects() {
	register_taxonomy_for_object_type( 'post_tag', 'project' );
	register_taxonomy_for_object_type( 'post_tag', 'resource' );
}
add_action( 'init', 'sbe_tags_for_projects', 11 ); // After the project/resource post types (init 10).

/**
 * Block editor: a Post Terms block for tags on an item without tags shows the label
 * "No tags" in the editor preview (the live site shows nothing). Blank that label in the
 * REST data the editor reads, so gallery previews look like the live site.
 *
 * @param WP_REST_Response $response Taxonomy response.
 * @param WP_Taxonomy      $taxonomy Taxonomy.
 * @return WP_REST_Response
 */
function sbe_tags_blank_no_terms_label( $response, $taxonomy ) {
	$data = $response->get_data();
	if ( 'post_tag' !== $taxonomy->name || empty( $data['labels'] ) ) {
		return $response;
	}
	$labels             = (array) $data['labels']; // An object in the response.
	$labels['no_terms'] = ' '; // A space: an empty string would fall back to "Term items not found.".
	$data['labels']     = (object) $labels;
	$response->set_data( $data );
	return $response;
}
add_filter( 'rest_prepare_taxonomy', 'sbe_tags_blank_no_terms_label', 10, 2 );

/**
 * Tag archives (/tag/<slug>/) list published posts, projects and resources, 24 per page;
 * `?type=news|projects|resources` narrows them to one kind (same as the search).
 *
 * @param WP_Query $query Main query.
 */
function sbe_tag_archive_includes_projects( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_tag() ) {
		$query->set( 'post_type', function_exists( 'sbe_search_post_types' ) ? sbe_search_post_types() : array( 'post', 'project', 'resource' ) );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'sbe_tag_archive_includes_projects' );

/**
 * One-time setup of the starting categories: rename the default "Uncategorized"
 * to "Post" and add "Interview" and "Article". Runs once (option
 * `sbe_categories_created`), so categories deleted or renamed later are not recreated.
 */
function sbe_categories_setup() {
	if ( get_option( 'sbe_categories_created' ) ) {
		return;
	}

	$default = (int) get_option( 'default_category' );
	$term    = $default ? get_term( $default, 'category' ) : null;
	if ( $term && ! is_wp_error( $term ) && 'uncategorized' === $term->slug ) {
		wp_update_term( $default, 'category', array( 'name' => 'Post', 'slug' => 'post' ) );
	} elseif ( ! term_exists( 'post', 'category' ) ) {
		$new = wp_insert_term( 'Post', 'category', array( 'slug' => 'post' ) );
		if ( ! is_wp_error( $new ) && ! $default ) {
			update_option( 'default_category', $new['term_id'] );
		}
	}

	foreach ( array( 'interview' => 'Interview', 'article' => 'Article' ) as $slug => $name ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	update_option( 'sbe_categories_created', 1 );
}
add_action( 'init', 'sbe_categories_setup', 21 );

/**
 * Does a parsed block have the given extra CSS class?
 *
 * @param array  $block Parsed block.
 * @param string $class Class name.
 * @return bool
 */
function sbe_block_has_class( $block, $class ) {
	$classes = preg_split( '/\s+/', (string) ( $block['attrs']['className'] ?? '' ) );
	return in_array( $class, $classes, true );
}
