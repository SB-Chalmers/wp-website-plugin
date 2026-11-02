<?php
/**
 * "Read more" links on projects: a list of external links (label + URL) stored in the
 * `sbe_links` meta field, edited in the editor sidebar and shown by the `sbe/read-more` block.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the links field.
 */
function sbe_links_register_meta() {
	register_post_meta(
		'project',
		'sbe_links',
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'label'             => 'Read more links',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'label' => array( 'type' => 'string' ),
							'url'   => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
			),
			'sanitize_callback' => 'sbe_links_sanitize',
			'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		)
	);
}
add_action( 'init', 'sbe_links_register_meta' );

/**
 * Keep only rows with a URL; clean label and URL.
 */
function sbe_links_sanitize( $value ) {
	$clean = array();
	foreach ( (array) $value as $row ) {
		$row   = (array) $row;
		$url   = esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) );
		$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
		if ( '' !== $url ) {
			$clean[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}
	return $clean;
}

/**
 * Register the Read more block.
 */
function sbe_links_register_block() {
	wp_register_script(
		'sbe-read-more-block',
		plugins_url( 'assets/read-more-block.js', SBE_PROJECTS_FILE ),
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-data', 'wp-server-side-render' ),
		SBE_PROJECTS_VERSION,
		true
	);

	register_block_type(
		'sbe/read-more',
		array(
			'api_version'           => 3,
			'title'                 => __( 'Read more links', 'sbe-projects' ),
			'category'              => 'theme',
			'icon'                  => 'admin-links',
			'description'           => __( 'Shows the external "Read more" links added to this project. Hidden when there are none.', 'sbe-projects' ),
			'attributes'            => array(
				'heading' => array(
					'type'    => 'string',
					'default' => 'Read more',
				),
				'align'   => array( 'type' => 'string' ),
			),
			'supports'              => array(
				'align' => array( 'wide', 'full' ),
				'html'  => false,
			),
			'uses_context'          => array( 'postId', 'postType' ),
			'style_handles'         => array( 'sbe-people' ),
			'editor_script_handles' => array( 'sbe-read-more-block' ),
			'render_callback'       => 'sbe_links_render_block',
		)
	);
}
add_action( 'init', 'sbe_links_register_block' );

/**
 * Render the Read more block.
 */
function sbe_links_render_block( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	$links   = $post_id ? sbe_links_sanitize( get_post_meta( $post_id, 'sbe_links', true ) ) : array();
	// Editor preview: the unsaved Read more links panel (includes/editor-simple.php).
	$preview = function_exists( 'sbe_editor_preview_value' ) ? sbe_editor_preview_value( 'links', $post_id ) : null;
	if ( is_array( $preview ) ) {
		$links = sbe_links_sanitize( $preview );
	}
	if ( ! $links ) {
		return '';
	}

	$items = '';
	foreach ( $links as $link ) {
		$label = $link['label'];
		if ( '' === $label ) {
			// No label: show the address without "https://www.".
			$label = preg_replace( '#^https?://(www\.)?#i', '', untrailingslashit( $link['url'] ) );
		}
		$items .= '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $label ) . '</a></li>';
	}

	$heading = trim( (string) ( $attributes['heading'] ?? '' ) );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'sbe-read-more' ) );

	return '<div ' . $wrapper . '>'
		. ( $heading ? '<h3 class="wp-block-heading sbe-read-more__heading">' . esc_html( $heading ) . '</h3>' : '' )
		. '<ul class="sbe-read-more__list">' . $items . '</ul></div>';
}
