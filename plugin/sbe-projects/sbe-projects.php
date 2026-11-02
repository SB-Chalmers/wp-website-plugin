<?php
/**
 * Plugin Name: SBE Projects
 * Description: Projects (with Ongoing / Completed status), People (staff profiles that can be tagged on posts, projects and resources), Resources (tools and datasets with a repository link and gallery icon), Publications on posts, projects and resources (typed in or pasted as BibTeX), post categories, tags shared by posts, projects and resources with tag filters on the News, Projects and Resources pages and tag galleries, a Related content gallery, site search for news and projects, and a Help page for editors (rendered from docs/).
 * Version:     1.26.1
 * Author:      Sustainable Built Environments
 * Text Domain: sbe-projects
 */

defined( 'ABSPATH' ) || exit;

const SBE_PROJECTS_VERSION = '1.26.1';
const SBE_PROJECTS_FILE    = __FILE__;
const SBE_PROJECTS_DIR     = __DIR__;

require_once __DIR__ . '/includes/people.php';
require_once __DIR__ . '/includes/links.php';
require_once __DIR__ . '/includes/publications.php';
require_once __DIR__ . '/includes/resources.php';
require_once __DIR__ . '/includes/pagination.php';
require_once __DIR__ . '/includes/carousel.php';
require_once __DIR__ . '/includes/categories-tags.php';
require_once __DIR__ . '/includes/tag-filter.php';
require_once __DIR__ . '/includes/related.php';
require_once __DIR__ . '/includes/sections.php';
require_once __DIR__ . '/includes/editor-blocks.php';
require_once __DIR__ . '/includes/editor-simple.php';
require_once __DIR__ . '/includes/placeholders.php';
require_once __DIR__ . '/includes/search.php';
require_once __DIR__ . '/includes/help.php';
require_once __DIR__ . '/includes/redirects.php';

/**
 * Site-wide: focus rings only for keyboard focus (see assets/focus.css).
 */
function sbe_focus_enqueue() {
	wp_enqueue_style( 'sbe-focus', plugins_url( 'assets/focus.css', SBE_PROJECTS_FILE ), array(), (string) filemtime( SBE_PROJECTS_DIR . '/assets/focus.css' ) );
}
add_action( 'wp_enqueue_scripts', 'sbe_focus_enqueue' );

/**
 * Register the Project post type and the Project status taxonomy.
 */
function sbe_projects_register() {
	register_post_type(
		'project',
		array(
			'labels'        => array(
				'name'               => __( 'Projects', 'sbe-projects' ),
				'singular_name'      => __( 'Project', 'sbe-projects' ),
				'add_new'            => __( 'Add New Project', 'sbe-projects' ),
				'add_new_item'       => __( 'Add New Project', 'sbe-projects' ),
				'edit_item'          => __( 'Edit Project', 'sbe-projects' ),
				'new_item'           => __( 'New Project', 'sbe-projects' ),
				'view_item'          => __( 'View Project', 'sbe-projects' ),
				'view_items'         => __( 'View Projects', 'sbe-projects' ),
				'search_items'       => __( 'Search Projects', 'sbe-projects' ),
				'not_found'          => __( 'No projects found.', 'sbe-projects' ),
				'not_found_in_trash' => __( 'No projects found in Trash.', 'sbe-projects' ),
				'all_items'          => __( 'All Projects', 'sbe-projects' ),
				'archives'           => __( 'Project Archives', 'sbe-projects' ),
				'menu_name'          => __( 'Projects', 'sbe-projects' ),
			),
			'public'        => true,
			'show_in_rest'  => true, // Required for the block editor and Query Loop.
			'has_archive'   => false, // /projects/ is a regular page instead.
			'rewrite'       => array(
				'slug'       => 'projects',
				'with_front' => false,
			),
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-portfolio',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'author' ),
		)
	);

	register_taxonomy(
		'project_status',
		array( 'project' ),
		array(
			'labels'            => array(
				'name'          => __( 'Project status', 'sbe-projects' ),
				'singular_name' => __( 'Project status', 'sbe-projects' ),
				'all_items'     => __( 'All statuses', 'sbe-projects' ),
				'edit_item'     => __( 'Edit status', 'sbe-projects' ),
				'add_new_item'  => __( 'Add New Status', 'sbe-projects' ),
				'menu_name'     => __( 'Project status', 'sbe-projects' ),
			),
			'hierarchical'      => true, // Checkbox UI in the editor sidebar.
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'project-status',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'sbe_projects_register' );

/**
 * Optional second title (subtitle) for projects, stored as post meta.
 */
function sbe_projects_register_meta() {
	register_post_meta(
		'project',
		'sbe_subtitle',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
			'label'             => __( 'Second title', 'sbe-projects' ),
		)
	);
}
add_action( 'init', 'sbe_projects_register_meta' );

/**
 * Hide the "Second title" heading when a project has no second title.
 */
function sbe_projects_hide_empty_subtitle( $content, $block, $instance ) {
	$key = $block['attrs']['metadata']['bindings']['content']['args']['key'] ?? '';
	if ( 'sbe_subtitle' !== $key ) {
		return $content;
	}
	$post_id = $instance->context['postId'] ?? get_the_ID();
	if ( '' === trim( (string) get_post_meta( $post_id, 'sbe_subtitle', true ) ) ) {
		return '';
	}
	return $content;
}
add_filter( 'render_block_core/heading', 'sbe_projects_hide_empty_subtitle', 10, 3 );

/**
 * Show the project status ("Status: Ongoing") as plain text, not as a link to its archive
 * page. (Tags are links since 1.18.0, see includes/tag-filter.php.)
 */
function sbe_projects_unlink_status( $content, $block ) {
	if ( 'project_status' !== ( $block['attrs']['term'] ?? '' ) ) {
		return $content;
	}
	return preg_replace( '#<a\b[^>]*>(.*?)</a>#s', '$1', $content );
}
add_filter( 'render_block_core/post-terms', 'sbe_projects_unlink_status', 10, 2 );

/**
 * [sbe_project_jump_links]: "Ongoing (n) · Completed (n)" links to the two sections of the
 * Projects page (headings with the anchors #ongoing-projects / #completed-projects).
 * Counts are live (published projects per status; with a tag chosen in the tag filter,
 * only projects with that tag).
 */
function sbe_projects_jump_links() {
	$items  = array();
	$tagged = function_exists( 'sbe_tag_filter_current' ) ? sbe_tag_filter_current() : null;
	foreach ( array( 'ongoing' => 'ongoing-projects', 'completed' => 'completed-projects' ) as $slug => $anchor ) {
		$term = get_term_by( 'slug', $slug, 'project_status' );
		if ( ! $term ) {
			continue;
		}
		$count = (int) $term->count;
		if ( $tagged ) {
			$q     = new WP_Query(
				array(
					'post_type'      => 'project',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array( 'taxonomy' => 'project_status', 'terms' => $term->term_id ),
						array( 'taxonomy' => 'post_tag', 'terms' => $tagged->term_id ),
					),
				)
			);
			$count = (int) $q->found_posts;
		}
		$items[] = sprintf(
			'<a href="#%1$s">%2$s <span class="sbe-jump-links__count">(%3$d)</span></a>',
			esc_attr( $anchor ),
			esc_html( $term->name ),
			$count
		);
	}
	if ( ! $items ) {
		return '';
	}
	return '<p class="sbe-jump-links">' . implode( '<span class="sbe-jump-links__sep" aria-hidden="true"> · </span>', $items ) . '</p>';
}
add_shortcode( 'sbe_project_jump_links', 'sbe_projects_jump_links' );

/**
 * [sbe_year] – current year (site timezone), e.g. for the footer copyright line.
 */
function sbe_projects_year_shortcode() {
	return esc_html( wp_date( 'Y' ) );
}
add_shortcode( 'sbe_year', 'sbe_projects_year_shortcode' );

/**
 * Menu: when reading a single post, project or resource, mark its listing page
 * (News /posts/, Projects /projects/, Resources /resources/) in the page-list menu with
 * the class `sbe-current-section`, styled like the current page (Additional CSS).
 */
function sbe_projects_menu_current_section( $content ) {
	$map = array(
		'post'     => '/posts/',
		'project'  => '/projects/',
		'resource' => '/resources/',
	);
	if ( ! is_singular( array_keys( $map ) ) ) {
		return $content;
	}
	$target = untrailingslashit( home_url( $map[ get_post_type() ] ) );
	$tags   = new WP_HTML_Tag_Processor( $content );
	$li     = null;
	while ( $tags->next_tag() ) {
		$name = $tags->get_tag();
		if ( 'LI' === $name ) {
			$tags->set_bookmark( 'li' );
			$li = true;
			continue;
		}
		if ( 'A' === $name && $li ) {
			$href = untrailingslashit( (string) $tags->get_attribute( 'href' ) );
			if ( $href === $target ) {
				$tags->seek( 'li' );
				$tags->add_class( 'sbe-current-section' );
				break;
			}
		}
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/page-list', 'sbe_projects_menu_current_section' );

/**
 * "Single item: Project" template, editable in the Site Editor.
 */
function sbe_projects_register_template() {
	register_block_template(
		'sbe-projects//single-project',
		array(
			'title'       => __( 'Single Project', 'sbe-projects' ),
			'description' => __( 'Displays a single project: title, optional second title, featured image and description.', 'sbe-projects' ),
			'post_types'  => array( 'project' ),
			'content'     => file_get_contents( __DIR__ . '/templates/single-project.html' ),
		)
	);
}
add_action( 'init', 'sbe_projects_register_template' );

/**
 * Sidebar field for the second title in the project editor.
 */
function sbe_projects_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->post_type, array( 'project', 'post', 'resource' ), true ) ) {
		return;
	}
	wp_enqueue_script(
		'sbe-projects-editor',
		plugins_url( 'editor.js', __FILE__ ),
		array( 'wp-plugins', 'wp-blocks', 'wp-dom-ready', 'wp-editor', 'wp-edit-post', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-element', 'wp-preferences', 'sbe-bibtex' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( __DIR__ . '/editor.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'sbe_projects_editor_assets' );

/**
 * One-time setup: create the default status terms and refresh permalinks.
 * Runs on first load after activation, and again whenever the version changes.
 */
function sbe_projects_maybe_setup() {
	if ( get_option( 'sbe_projects_version' ) === SBE_PROJECTS_VERSION ) {
		return;
	}

	foreach ( array( 'ongoing' => 'Ongoing', 'completed' => 'Completed' ) as $slug => $name ) {
		if ( ! term_exists( $slug, 'project_status' ) ) {
			wp_insert_term( $name, 'project_status', array( 'slug' => $slug ) );
		}
	}

	flush_rewrite_rules( false );
	update_option( 'sbe_projects_version', SBE_PROJECTS_VERSION );
}
add_action( 'init', 'sbe_projects_maybe_setup', 20 );

/**
 * Refresh permalinks on deactivation so /projects/ URLs stop resolving.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		delete_option( 'sbe_projects_version' );
		flush_rewrite_rules( false );
	}
);
