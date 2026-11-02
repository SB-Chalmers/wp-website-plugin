<?php
/**
 * Simpler block inserter for posts, projects and resources (since 1.22.0).
 *
 * When someone edits a post, project or resource, the inserter (+) only offers the
 * blocks the SBE sections are made of, and the Patterns tab only shows SBE sections.
 * Pages, the Site Editor (templates, header, footer) and every other screen are not
 * affected and keep all blocks and patterns.
 *
 * Existing content is untouched: blocks already on a page keep working, they just
 * can't be added from the inserter. The template blocks (title, featured image,
 * People involved, Read more …) still show and work when "Show template" is on.
 *
 * To undo: remove the require_once line for this file in sbe-projects.php (and the
 * variation lines at the end of editor.js).
 */

defined( 'ABSPATH' ) || exit;

/** Post types whose editor gets the short block list. */
function sbe_editor_blocks_post_types() {
	return array( 'post', 'project', 'resource' );
}

/**
 * Blocks offered in the inserter for those post types: exactly the blocks used by
 * the SBE section patterns (includes/sections.php). If a new pattern needs another
 * block, add it here.
 */
function sbe_editor_blocks_allowed() {
	return array(
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/image',
		'core/embed',
		'core/group',
		'core/quote',
	);
}

/**
 * Limit the inserter in the post/project/resource editor.
 *
 * @param bool|string[]           $allowed Allowed block names, or true for all.
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|string[]
 */
function sbe_editor_blocks_filter( $allowed, $context ) {
	if ( empty( $context->post ) || ! in_array( $context->post->post_type, sbe_editor_blocks_post_types(), true ) ) {
		return $allowed;
	}
	return sbe_editor_blocks_allowed();
}
add_filter( 'allowed_block_types_all', 'sbe_editor_blocks_filter', 100, 2 );

/**
 * Hide the Openverse (free stock images) category in the inserter's Media tab for
 * the same post types; the site's own Media Library stays.
 *
 * @param array                   $settings Editor settings.
 * @param WP_Block_Editor_Context $context  Editor context.
 * @return array
 */
function sbe_editor_blocks_settings( $settings, $context ) {
	if ( ! empty( $context->post ) && in_array( $context->post->post_type, sbe_editor_blocks_post_types(), true ) ) {
		$settings['enableOpenverseMediaCategory'] = false;
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'sbe_editor_blocks_settings', 10, 2 );

/**
 * Patterns tab: only SBE sections for posts, projects and resources.
 *
 * The editor shows a pattern on a post type when its post_types list is empty or
 * contains that type. Every pattern outside the "SBE sections" category (core,
 * Twenty Twenty-Five, remote and plugin patterns) gets a post_types list without
 * post/project/resource, so it still shows on pages and in the Site Editor.
 * Done on the REST response (/wp/v2/block-patterns/patterns) because remote
 * patterns are only registered while that request runs.
 *
 * @param WP_REST_Response $response Response.
 * @param WP_REST_Server   $server   Server.
 * @param WP_REST_Request  $request  Request.
 * @return WP_REST_Response
 */
function sbe_editor_blocks_patterns( $response, $server, $request ) {
	if ( '/wp/v2/block-patterns/patterns' !== $request->get_route() || ! ( $response instanceof WP_REST_Response ) || $response->is_error() ) {
		return $response;
	}
	$ours  = sbe_editor_blocks_post_types();
	$other = array_values( array_diff( get_post_types( array( 'show_in_rest' => true ) ), $ours ) );
	$data  = $response->get_data();
	if ( ! is_array( $data ) ) {
		return $response;
	}
	foreach ( $data as $i => $pattern ) {
		if ( ! is_array( $pattern ) || in_array( 'sbe-sections', (array) ( $pattern['categories'] ?? array() ), true ) ) {
			continue;
		}
		$types                     = (array) ( $pattern['post_types'] ?? array() );
		$data[ $i ]['post_types'] = $types ? array_values( array_diff( $types, $ours ) ) : $other;
		if ( $types && ! $data[ $i ]['post_types'] ) {
			// Was only meant for our types: hide it everywhere instead of everywhere-visible.
			$data[ $i ]['post_types'] = array( 'sbe-none' );
		}
	}
	$response->set_data( $data );
	return $response;
}
add_filter( 'rest_post_dispatch', 'sbe_editor_blocks_patterns', 10, 3 );
