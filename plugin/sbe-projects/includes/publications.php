<?php
/**
 * Publications on posts, projects and resources (since 1.26.0).
 *
 * - Meta `sbe_publications`: list of { title, url, authors, year, venue }, edited in the
 *   "Publications" panel of the editor sidebar (editor.js, PublicationsPanel). Each one
 *   can be typed in field by field or added by pasting BibTeX (parsed in the browser,
 *   assets/bibtex.js), which fills in the same fields.
 * - Block `sbe/publications` renders the list with the heading "Publications"; it renders
 *   nothing when the list is empty, so the section only appears when filled in.
 * - The block is placed automatically, right before "People involved" (`sbe/people`), in
 *   the Single Posts, Single Project and Single Resource templates with a block hook
 *   (`hooked_block_types`), so the database templates don't need to be edited. Moving or
 *   removing it in the Site Editor is remembered by WordPress (ignoredHookedBlocks).
 */

defined( 'ABSPATH' ) || exit;

/** Post types that have publications. */
function sbe_publications_post_types() {
	return array( 'post', 'project', 'resource' );
}

/** Templates the block is hooked into. */
function sbe_publications_templates() {
	return array( 'single', 'single-post', 'single-project', 'single-resource' );
}

/**
 * Register the field.
 */
function sbe_publications_register_meta() {
	foreach ( sbe_publications_post_types() as $type ) {
		register_post_meta(
			$type,
			'sbe_publications',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'label'             => 'Publications',
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'                 => 'object',
							'properties'           => array(
								'title'   => array( 'type' => 'string' ),
								'url'     => array( 'type' => 'string' ),
								'authors' => array( 'type' => 'string' ),
								'year'    => array( 'type' => 'string' ),
								'venue'   => array( 'type' => 'string' ),
							),
							'additionalProperties' => false,
						),
					),
				),
				'sanitize_callback' => 'sbe_publications_sanitize',
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'sbe_publications_register_meta' );

/**
 * Keep only rows with a title; clean every field.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function sbe_publications_sanitize( $value ) {
	$clean = array();
	foreach ( (array) $value as $row ) {
		$row   = (array) $row;
		$title = sanitize_text_field( (string) ( $row['title'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}
		$clean[] = array(
			'title'   => $title,
			'url'     => esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) ),
			'authors' => sanitize_text_field( (string) ( $row['authors'] ?? '' ) ),
			'year'    => sanitize_text_field( (string) ( $row['year'] ?? '' ) ),
			'venue'   => sanitize_text_field( (string) ( $row['venue'] ?? '' ) ),
		);
	}
	return $clean;
}

/**
 * Register the block.
 */
function sbe_publications_register_block() {
	wp_register_script(
		'sbe-publications-block',
		plugins_url( 'assets/publications-block.js', SBE_PROJECTS_FILE ),
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-data', 'wp-server-side-render' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( SBE_PROJECTS_DIR . '/assets/publications-block.js' ),
		true
	);

	register_block_type(
		'sbe/publications',
		array(
			'api_version'           => 3,
			'title'                 => __( 'Publications', 'sbe-projects' ),
			'category'              => 'theme',
			'icon'                  => 'book-alt',
			'description'           => __( 'Shows the publications added in the Publications panel. Hidden when there are none.', 'sbe-projects' ),
			'attributes'            => array(
				'heading' => array(
					'type'    => 'string',
					'default' => 'Publications',
				),
			),
			'supports'              => array(
				'html'     => false,
				'inserter' => false, // Placed by the template (block hook), never by hand in a post.
			),
			'uses_context'          => array( 'postId', 'postType' ),
			'editor_script_handles' => array( 'sbe-publications-block' ),
			'render_callback'       => 'sbe_publications_render_block',
		)
	);
}
add_action( 'init', 'sbe_publications_register_block' );

/**
 * Place the block before "People involved" in the single templates.
 *
 * @param string[]                        $hooked   Block types hooked at this position.
 * @param string                          $position before | after | first_child | last_child.
 * @param string                          $anchor   Anchor block type.
 * @param WP_Block_Template|WP_Post|array $context  Where the blocks come from.
 * @return string[]
 */
function sbe_publications_hook_block( $hooked, $position, $anchor, $context ) {
	if ( 'before' !== $position || 'sbe/people' !== $anchor ) {
		return $hooked;
	}
	if ( ! ( $context instanceof WP_Block_Template ) || ! in_array( $context->slug, sbe_publications_templates(), true ) ) {
		return $hooked;
	}
	$hooked[] = 'sbe/publications';
	return $hooked;
}
add_filter( 'hooked_block_types', 'sbe_publications_hook_block', 10, 4 );

/**
 * One publication as an <li>: linked title, then "Authors (Year). Venue."
 *
 * @param array $pub Sanitized publication.
 * @return string
 */
function sbe_publications_item_html( $pub ) {
	$title = esc_html( $pub['title'] );
	if ( '' !== $pub['url'] ) {
		$title = '<a href="' . esc_url( $pub['url'] ) . '">' . $title . '</a>';
	} else {
		$title = '<span class="sbe-publications__title">' . $title . '</span>';
	}

	$line = '';
	if ( '' !== $pub['authors'] ) {
		$line .= esc_html( $pub['authors'] );
	}
	if ( '' !== $pub['year'] ) {
		$line .= ( '' === $line ? '' : ' ' ) . '(' . esc_html( $pub['year'] ) . ')';
	}
	if ( '' !== $line ) {
		$line .= '.';
	}
	if ( '' !== $pub['venue'] ) {
		$line .= ( '' === $line ? '' : ' ' ) . '<em>' . esc_html( rtrim( $pub['venue'], '.' ) ) . '</em>.';
	}

	return '<li>' . $title . ( '' !== $line ? '<br>' . $line : '' ) . '</li>';
}

/**
 * Render the Publications block.
 */
function sbe_publications_render_block( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	$pubs    = $post_id ? sbe_publications_sanitize( get_post_meta( $post_id, 'sbe_publications', true ) ) : array();
	// Editor preview: the unsaved Publications panel (includes/editor-simple.php).
	$preview = function_exists( 'sbe_editor_preview_value' ) ? sbe_editor_preview_value( 'publications', $post_id ) : null;
	if ( is_array( $preview ) ) {
		$pubs = sbe_publications_sanitize( $preview );
	}
	if ( ! $pubs ) {
		return '';
	}

	$items   = implode( '', array_map( 'sbe_publications_item_html', $pubs ) );
	$heading = trim( (string) ( $attributes['heading'] ?? '' ) );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'sbe-publications-section' ) );

	return '<div ' . $wrapper . '>'
		. ( $heading ? '<h3 class="wp-block-heading sbe-publications__heading">' . esc_html( $heading ) . '</h3>' : '' )
		. '<ul class="sbe-publications">' . $items . '</ul></div>';
}

/**
 * The BibTeX reader for the sidebar panel, loaded before editor.js.
 */
function sbe_publications_editor_assets() {
	$js = SBE_PROJECTS_DIR . '/assets/bibtex.js';
	wp_register_script( 'sbe-bibtex', plugins_url( 'assets/bibtex.js', SBE_PROJECTS_FILE ), array(), SBE_PROJECTS_VERSION . '.' . filemtime( $js ), true );
}
add_action( 'init', 'sbe_publications_editor_assets', 5 );
