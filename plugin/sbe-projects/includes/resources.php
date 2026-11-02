<?php
/**
 * Resources: tools, datasets, scripts etc. shared by the group.
 *
 * - "Resources" post type, URLs /resources/<slug>. Written in the block editor like a post:
 *   title, featured image, description (content).
 * - `sbe_icon` (attachment ID): a separate icon used in the Resources gallery, so the gallery
 *   looks consistent even when feature images differ.
 * - `sbe_repo_url`: link to the repository, shown as a button by the `sbe/repository-link` block.
 * - `sbe/resource-icon` block: shows the icon (initials placeholder when none is set).
 * - Placeholder colours: a resource without an icon gets one colour from a palette sampled
 *   from the Home page picture (SBE_RESOURCE_COLORS), picked at random among the colours
 *   used least so far and saved in `sbe_placeholder_color`, so it stays the same and
 *   resources differ from each other. Used for the icon placeholder and for the resource's
 *   picture placeholder in Related content / search results (includes/placeholders.php).
 *   Posts and projects without a featured image use the same palette (since 1.19.2),
 *   counted per post type.
 * - Tags: the shared post/project tag list (includes/categories-tags.php); they drive the
 *   "Related content" gallery at the bottom of the resource page (includes/related.php).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder colours for resources without an icon: background => letter colour.
 * Sampled from the Home page picture (green-to-blue background, blue line art, orange
 * accents). Letter colour = whichever of white / navy has the higher contrast (all ≥ 4.5:1
 * except royal blue + white, 5.0:1).
 */
const SBE_RESOURCE_COLORS = array(
	'#8dd899' => '#15224d', // light green
	'#79c2a5' => '#15224d', // mint
	'#5ba6b3' => '#15224d', // teal
	'#4e93bc' => '#15224d', // sky blue
	'#3c6ebd' => '#ffffff', // royal blue
	'#3443c4' => '#ffffff', // line-art blue
	'#e07028' => '#15224d', // orange accent
);

/**
 * Placeholder colours of a resource, post or project: array( background, letters ).
 * Assigned once; "least used" is counted among items of the same post type, so each
 * gallery (Resources, News, Projects) gets an even mix.
 *
 * @param int $post_id Resource, post or project ID.
 * @return string[]
 */
function sbe_resources_placeholder_colors( $post_id ) {
	$colors = array_keys( SBE_RESOURCE_COLORS );
	$bg     = (string) get_post_meta( $post_id, 'sbe_placeholder_color', true );
	if ( ! isset( SBE_RESOURCE_COLORS[ $bg ] ) ) {
		// Count the colours other resources already have, pick at random among the least used.
		$used = array_fill_keys( $colors, 0 );
		$ids  = get_posts(
			array(
				'post_type'      => get_post_type( $post_id ),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__not_in'   => array( (int) $post_id ),
				'meta_key'       => 'sbe_placeholder_color',
			)
		);
		foreach ( $ids as $id ) {
			$c = (string) get_post_meta( $id, 'sbe_placeholder_color', true );
			if ( isset( $used[ $c ] ) ) {
				++$used[ $c ];
			}
		}
		$least = array_keys( $used, min( $used ), true );
		$bg    = $least[ array_rand( $least ) ];
		update_post_meta( $post_id, 'sbe_placeholder_color', $bg );
	}
	return array( $bg, SBE_RESOURCE_COLORS[ $bg ] );
}

/**
 * Initials of a resource: first letters of its first and last word ("Python for AEC
 * Researchers" → PR). Used everywhere a resource has a placeholder (Resources page, admin
 * list, Related content, search results), so the letters always match.
 *
 * @param int $post_id Resource ID.
 * @return string
 */
function sbe_resources_initials( $post_id ) {
	return sbe_people_initials( html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Inline style for a placeholder: background and letter colour.
 *
 * @param int $post_id Resource, post or project ID.
 * @return string
 */
function sbe_resources_placeholder_style( $post_id ) {
	list( $bg, $fg ) = sbe_resources_placeholder_colors( $post_id );
	return 'background-color:' . $bg . ';color:' . $fg . ';';
}

/**
 * Register the Resource post type and its fields.
 */
function sbe_resources_register() {
	register_post_type(
		'resource',
		array(
			'labels'        => array(
				'name'                  => __( 'Resources', 'sbe-projects' ),
				'singular_name'         => __( 'Resource', 'sbe-projects' ),
				'add_new'               => __( 'Add New Resource', 'sbe-projects' ),
				'add_new_item'          => __( 'Add New Resource', 'sbe-projects' ),
				'edit_item'             => __( 'Edit Resource', 'sbe-projects' ),
				'new_item'              => __( 'New Resource', 'sbe-projects' ),
				'view_item'             => __( 'View Resource', 'sbe-projects' ),
				'view_items'            => __( 'View Resources', 'sbe-projects' ),
				'search_items'          => __( 'Search Resources', 'sbe-projects' ),
				'not_found'             => __( 'No resources found.', 'sbe-projects' ),
				'not_found_in_trash'    => __( 'No resources found in Trash.', 'sbe-projects' ),
				'all_items'             => __( 'All Resources', 'sbe-projects' ),
				'menu_name'             => __( 'Resources', 'sbe-projects' ),
				'featured_image'        => __( 'Feature image', 'sbe-projects' ),
				'set_featured_image'    => __( 'Set feature image', 'sbe-projects' ),
				'remove_featured_image' => __( 'Remove feature image', 'sbe-projects' ),
				'use_featured_image'    => __( 'Use as feature image', 'sbe-projects' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false, // /resources/ is a regular page instead.
			'rewrite'       => array(
				'slug'       => 'resources',
				'with_front' => false,
			),
			'menu_position' => 7,
			'menu_icon'     => 'dashicons-archive',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'author' ),
		)
	);

	$auth = function ( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};

	register_post_meta(
		'resource',
		'sbe_repo_url',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'label'             => 'Repository link',
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => $auth,
		)
	);

	register_post_meta(
		'resource',
		'sbe_icon',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'label'             => 'Gallery icon',
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => $auth,
		)
	);
}
add_action( 'init', 'sbe_resources_register' );

/**
 * "Single Resource" template, editable in the Site Editor.
 */
function sbe_resources_register_template() {
	register_block_template(
		'sbe-projects//single-resource',
		array(
			'title'       => __( 'Single Resource', 'sbe-projects' ),
			'description' => __( 'Displays a single resource: title, feature image, description, repository link and related content.', 'sbe-projects' ),
			'post_types'  => array( 'resource' ),
			'content'     => file_get_contents( SBE_PROJECTS_DIR . '/templates/single-resource.html' ),
		)
	);
}
add_action( 'init', 'sbe_resources_register_template' );

/**
 * Blocks: Resource icon and Repository link (both rendered in PHP).
 */
function sbe_resources_register_blocks() {
	wp_register_script(
		'sbe-resource-blocks',
		plugins_url( 'assets/resource-blocks.js', SBE_PROJECTS_FILE ),
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-data', 'wp-server-side-render' ),
		SBE_PROJECTS_VERSION,
		true
	);

	wp_register_style(
		'sbe-resources',
		plugins_url( 'assets/resources.css', SBE_PROJECTS_FILE ),
		array(),
		SBE_PROJECTS_VERSION . '.' . filemtime( dirname( SBE_PROJECTS_FILE ) . '/assets/resources.css' )
	);

	register_block_type(
		'sbe/resource-icon',
		array(
			'api_version'           => 3,
			'title'                 => __( 'Resource icon', 'sbe-projects' ),
			'category'              => 'theme',
			'icon'                  => 'format-image',
			'description'           => __( 'Shows the gallery icon of a resource. Use inside a Query Loop on the Resources page.', 'sbe-projects' ),
			'attributes'            => array(
				'isLink' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
			'supports'              => array( 'html' => false ),
			'uses_context'          => array( 'postId', 'postType' ),
			'style_handles'         => array( 'sbe-resources' ),
			'editor_script_handles' => array( 'sbe-resource-blocks' ),
			'render_callback'       => 'sbe_resources_render_icon',
		)
	);

	register_block_type(
		'sbe/repository-link',
		array(
			'api_version'           => 3,
			'title'                 => __( 'Repository link', 'sbe-projects' ),
			'category'              => 'theme',
			'icon'                  => 'external',
			'description'           => __( 'Button linking to the resource’s repository. Hidden when no link is set.', 'sbe-projects' ),
			'attributes'            => array(
				'text' => array(
					'type'    => 'string',
					'default' => 'Go to resource',
				),
			),
			'supports'              => array( 'html' => false ),
			'uses_context'          => array( 'postId', 'postType' ),
			'style_handles'         => array( 'sbe-resources' ),
			'editor_script_handles' => array( 'sbe-resource-blocks' ),
			'render_callback'       => 'sbe_resources_render_repo_link',
		)
	);
}
add_action( 'init', 'sbe_resources_register_blocks' );

/**
 * Icon markup for a resource: the chosen icon, or a placeholder with initials.
 */
function sbe_resources_icon_html( $post_id, $size = 'medium' ) {
	$icon_id = (int) get_post_meta( $post_id, 'sbe_icon', true );
	if ( $icon_id && wp_attachment_is_image( $icon_id ) ) {
		$inner = wp_get_attachment_image(
			$icon_id,
			$size,
			false,
			array(
				'class'   => 'sbe-resource-icon__img',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
		return '<span class="sbe-resource-icon__frame">' . $inner . '</span>';
	}
	return '<span class="sbe-resource-icon__frame sbe-resource-icon__placeholder" style="' . esc_attr( sbe_resources_placeholder_style( $post_id ) ) . '" aria-hidden="true">'
		. '<span class="sbe-resource-icon__letters">' . esc_html( sbe_resources_initials( $post_id ) ) . '</span></span>';
}

/**
 * Render the Resource icon block.
 */
function sbe_resources_render_icon( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	$inner   = sbe_resources_icon_html( $post_id );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'sbe-resource-icon' ) );

	if ( ! empty( $attributes['isLink'] ) ) {
		$inner = '<a href="' . esc_url( get_permalink( $post_id ) ) . '" tabindex="-1" aria-hidden="true">' . $inner . '</a>';
	}
	return '<div ' . $wrapper . '>' . $inner . '</div>';
}

/**
 * Render the Repository link block.
 */
function sbe_resources_render_repo_link( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	$url     = $post_id ? (string) get_post_meta( $post_id, 'sbe_repo_url', true ) : '';
	// Editor preview: the unsaved Repository field (includes/editor-simple.php).
	$preview = function_exists( 'sbe_editor_preview_value' ) ? sbe_editor_preview_value( 'repo', $post_id ) : null;
	if ( is_string( $preview ) ) {
		$url = esc_url_raw( $preview );
	}
	if ( '' === trim( $url ) ) {
		return '';
	}
	$text    = trim( (string) ( $attributes['text'] ?? '' ) );
	$text    = '' === $text ? __( 'Go to resource', 'sbe-projects' ) : $text;
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'sbe-repo-link' ) );

	// Coloured rounded button with an arrow, styled in assets/resources.css.
	return '<p ' . $wrapper . '>'
		. '<a href="' . esc_url( $url ) . '"><span class="sbe-repo-link__text">' . esc_html( $text ) . '</span>'
		. '<span class="sbe-repo-link__arrow" aria-hidden="true">→</span></a>'
		. '</p>';
}

/**
 * Admin list: show the icon next to the name.
 */
add_filter(
	'manage_resource_posts_columns',
	function ( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['sbe_icon'] = __( 'Icon', 'sbe-projects' );
			}
			$new[ $key ] = $label;
		}
		return $new;
	}
);
add_action(
	'manage_resource_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'sbe_icon' === $column ) {
			echo '<span class="sbe-resource-icon sbe-resource-icon--admin">' . sbe_resources_icon_html( $post_id, 'thumbnail' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
		}
	},
	10,
	2
);
add_action(
	'admin_head',
	function () {
		echo '<style>.post-type-resource .column-sbe_icon{width:64px}'
			. '.sbe-resource-icon--admin .sbe-resource-icon__frame{display:flex;align-items:center;justify-content:center;box-sizing:border-box;width:48px;height:48px;padding:6px;border-radius:8px;background:#f0f0f0;font-weight:600;color:#555}.sbe-resource-icon--admin img{width:100%;height:100%;object-fit:contain}</style>';
	}
);

/**
 * One-time setup: create the Resources page (placed before Contact in the menu).
 */
function sbe_resources_maybe_create_page() {
	if ( get_option( 'sbe_resources_page_created' ) ) {
		return;
	}
	update_option( 'sbe_resources_page_created', 1 );

	if ( get_page_by_path( 'resources' ) ) {
		return;
	}

	$contact = get_page_by_path( 'contact' );
	$order   = $contact ? (int) $contact->menu_order : 5;
	if ( $contact ) {
		wp_update_post(
			array(
				'ID'         => $contact->ID,
				'menu_order' => $order + 1,
			)
		);
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Resources',
			'post_name'    => 'resources',
			'menu_order'   => $order,
			'post_content' => file_get_contents( SBE_PROJECTS_DIR . '/templates/resources-page.html' ),
		)
	);
}
add_action( 'init', 'sbe_resources_maybe_create_page', 25 );
