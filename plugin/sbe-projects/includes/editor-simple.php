<?php
/**
 * Simple editor for posts, projects and resources (since 1.23.0).
 *
 * - One sidebar "Add content" (assets/editor-simple.js) with only the fields an
 *   author needs: main picture, type / status, tags, people, links, sections.
 *   WordPress's own Settings sidebar (Post / Block tabs, author, slug, template …)
 *   is hidden. Administrators can switch back with ⋮ → "Show all WordPress settings".
 * - The editor shows the page as on the site by default ("Show template" on): title
 *   in the 760px column, People involved, Read more, "Go to resource" button, tags.
 * - Those server-rendered blocks preview the unsaved values from the sidebar
 *   (sbe_editor_preview_value()), so the preview changes as soon as you tag someone.
 * - Since 1.24.0 the Tags / Status lines and the Related content cards follow the
 *   sidebar too (assets/live-preview.js, sbe_related_preview_rest() in related.php).
 * - Since 1.25.0 the empty title, second title and text on the page say what to do
 *   (assets/editor-hints.js, texts in sbe_simple_hints()).
 *
 * To undo: remove the require_once line for this file in sbe-projects.php.
 */

defined( 'ABSPATH' ) || exit;

/** Post types that get the simple editor. */
function sbe_simple_post_types() {
	return array( 'post', 'project', 'resource' );
}

/**
 * Texts shown on the page in the editor while a field is empty (assets/editor-hints.js).
 */
function sbe_simple_hints() {
	return array(
		'title'    => __( 'Write the title here', 'sbe-projects' ),
		'subtitle' => __( 'Second title (optional): fill it in under “Second title” in Add content on the right →', 'sbe-projects' ),
		'text'     => __( 'Write your text here. Enter starts a new paragraph; the + button adds a picture, heading or list.', 'sbe-projects' ),
	);
}

/**
 * Text in an empty post, project or resource (instead of "Type / to choose a block").
 *
 * @param string  $text Default text.
 * @param WP_Post $post Post being edited.
 * @return string
 */
function sbe_simple_body_placeholder( $text, $post ) {
	if ( $post && in_array( $post->post_type, sbe_simple_post_types(), true ) ) {
		$hints = sbe_simple_hints();
		return $hints['text'];
	}
	return $text;
}
add_filter( 'write_your_story', 'sbe_simple_body_placeholder', 10, 2 );

/**
 * Scripts and styles for the simple editor.
 */
function sbe_simple_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->post_type, sbe_simple_post_types(), true ) ) {
		return;
	}
	$js  = SBE_PROJECTS_DIR . '/assets/editor-simple.js';
	$css = SBE_PROJECTS_DIR . '/assets/editor-simple.css';
	wp_enqueue_style( 'sbe-editor-simple', plugins_url( 'assets/editor-simple.css', SBE_PROJECTS_FILE ), array(), SBE_PROJECTS_VERSION . '.' . filemtime( $css ) );
	wp_enqueue_script(
		'sbe-editor-simple',
		plugins_url( 'assets/editor-simple.js', SBE_PROJECTS_FILE ),
		array( 'sbe-projects-editor', 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-data', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-preferences', 'wp-core-data' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( $js ),
		true
	);
	// Tags, status and Related content follow the sidebar before saving (since 1.24.0).
	$live = SBE_PROJECTS_DIR . '/assets/live-preview.js';
	wp_enqueue_script(
		'sbe-live-preview',
		plugins_url( 'assets/live-preview.js', SBE_PROJECTS_FILE ),
		array( 'wp-hooks', 'wp-compose', 'wp-data', 'wp-element', 'wp-block-editor', 'wp-editor', 'wp-core-data', 'wp-api-fetch' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( $live ),
		true
	);
	// Hints on the page: title, second title and text say what to do (since 1.25.0).
	$hints = SBE_PROJECTS_DIR . '/assets/editor-hints.js';
	wp_enqueue_script(
		'sbe-editor-hints',
		plugins_url( 'assets/editor-hints.js', SBE_PROJECTS_FILE ),
		array( 'wp-hooks', 'wp-compose', 'wp-data', 'wp-element', 'wp-blocks', 'wp-block-editor', 'wp-editor', 'wp-edit-post' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( $hints ),
		true
	);
	wp_add_inline_script( 'sbe-editor-hints', 'window.sbeHints = ' . wp_json_encode( sbe_simple_hints() ) . ';', 'before' );
	// Read by editor.js too (its panels move into the Add content sidebar), so it goes before editor.js.
	wp_add_inline_script(
		'sbe-projects-editor',
		'window.sbeSimple = ' . wp_json_encode(
			array(
				'enabled'   => true,
				// Administrators may switch to the full WordPress sidebar; everyone else always gets the simple one.
				'canSwitch' => current_user_can( 'manage_options' ),
				'helpUrl'   => function_exists( 'sbe_help_url' ) ? sbe_help_url( 'tutorial' ) : '',
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'sbe_simple_editor_assets', 20 );

/**
 * Show the template ("Show template" on) by default in the post, project and resource
 * editor, so the editor looks like the published page. Someone who switches it off
 * in ⋮ → Show template keeps their choice (WordPress stores it per user).
 *
 * @param array                   $settings Editor settings.
 * @param WP_Block_Editor_Context $context  Editor context.
 * @return array
 */
function sbe_simple_editor_settings( $settings, $context ) {
	if ( ! empty( $context->post ) && in_array( $context->post->post_type, sbe_simple_post_types(), true ) ) {
		$settings['defaultRenderingMode'] = 'template-locked';
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'sbe_simple_editor_settings', 10, 2 );

/**
 * Editor canvas only: styles for the template preview (e.g. the Related content
 * note). Loaded inside the editor iframe, never on the site.
 */
function sbe_simple_canvas_assets() {
	$screen = is_admin() && function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, sbe_simple_post_types(), true ) ) {
		return; // Not on the site, not in the Site Editor.
	}
	// The site's own styles for the tag line and the Related content cards, so the preview looks the same.
	foreach ( array( 'tags', 'carousel', 'categories-tags', 'placeholders' ) as $name ) {
		wp_enqueue_style( 'sbe-' . $name, plugins_url( 'assets/' . $name . '.css', SBE_PROJECTS_FILE ), array(), (string) filemtime( SBE_PROJECTS_DIR . '/assets/' . $name . '.css' ) );
	}
	$css = SBE_PROJECTS_DIR . '/assets/editor-canvas.css';
	wp_enqueue_style( 'sbe-editor-canvas', plugins_url( 'assets/editor-canvas.css', SBE_PROJECTS_FILE ), array(), SBE_PROJECTS_VERSION . '.' . filemtime( $css ) );
}
add_action( 'enqueue_block_assets', 'sbe_simple_canvas_assets' );

/**
 * Unsaved value from the editor for a server-rendered preview.
 *
 * The People, Read more and Repository link blocks are rendered on the server, which
 * only knows the saved post. The editor passes the current sidebar values in the
 * query argument sbe_preview (JSON) of the block-renderer request, so the preview
 * shows them before the post is saved. Only for REST requests by someone who may
 * edit the post; the site itself never reads it.
 *
 * @param string $key     people | links | repo.
 * @param int    $post_id Post being previewed.
 * @return mixed|null Null when there is no preview value.
 */
function sbe_editor_preview_value( $key, $post_id ) {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || empty( $_GET['sbe_preview'] ) || ! $post_id ) { // phpcs:ignore WordPress.Security.NonceVerification
		return null;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return null;
	}
	$data = json_decode( wp_unslash( (string) $_GET['sbe_preview'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $data ) || ! array_key_exists( $key, $data ) ) {
		return null;
	}
	return $data[ $key ];
}
