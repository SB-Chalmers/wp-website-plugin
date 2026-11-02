<?php
/**
 * People: staff profiles, tagging people on posts/projects/resources, and the People block.
 *
 * - "People" post type (admin only, no public pages): name (title), photo (featured image),
 *   role, research interests and an external profile URL.
 * - Posts, projects and resources store tagged people as a list of IDs in the `sbe_people` meta field.
 * - The `sbe/people` block shows either the people tagged on the current post/project/resource
 *   (photo, name, role) or all staff (photo, name, role, research interests).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that can have people tagged.
 */
function sbe_people_taggable_types() {
	return array( 'post', 'project', 'resource' );
}

/**
 * Square image size for profile photos. The People block crops photos to a square, so a
 * width-limited size like 'medium' (300px wide, i.e. only ~200px tall for a landscape photo)
 * was upscaled and looked soft.
 */
function sbe_people_image_size() {
	add_image_size( 'sbe-person', 600, 600, true );
}
add_action( 'after_setup_theme', 'sbe_people_image_size' );

/**
 * Make sure a photo has the 'sbe-person' size. Photos uploaded before the size existed
 * (or imported from elsewhere) get it generated once, the first time they're shown.
 */
function sbe_people_ensure_photo_size( $attachment_id ) {
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! is_array( $meta ) || ! empty( $meta['sizes']['sbe-person'] ) ) {
		return;
	}
	$file = get_attached_file( $attachment_id ); // the "-scaled" copy when there is one: faster than the full original
	if ( ! $file || ! file_exists( $file ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return;
	}
	$result = $editor->multi_resize( array( 'sbe-person' => array( 'width' => 600, 'height' => 600, 'crop' => true ) ) );
	if ( empty( $result['sbe-person'] ) ) {
		return;
	}
	// The resized file is written next to the original; store it like any other size.
	$meta['sizes']['sbe-person'] = $result['sbe-person'];
	wp_update_attachment_metadata( $attachment_id, $meta );
}

/**
 * Register the Person post type and its fields.
 */
function sbe_people_register() {
	register_post_type(
		'person',
		array(
			'labels'              => array(
				'name'               => __( 'People', 'sbe-projects' ),
				'singular_name'      => __( 'Person', 'sbe-projects' ),
				'add_new'            => __( 'Add New Person', 'sbe-projects' ),
				'add_new_item'       => __( 'Add New Person', 'sbe-projects' ),
				'edit_item'          => __( 'Edit Person', 'sbe-projects' ),
				'new_item'           => __( 'New Person', 'sbe-projects' ),
				'search_items'       => __( 'Search People', 'sbe-projects' ),
				'not_found'          => __( 'No people found.', 'sbe-projects' ),
				'not_found_in_trash' => __( 'No people found in Trash.', 'sbe-projects' ),
				'all_items'          => __( 'All People', 'sbe-projects' ),
				'menu_name'          => __( 'People', 'sbe-projects' ),
				'featured_image'     => __( 'Profile photo', 'sbe-projects' ),
				'set_featured_image' => __( 'Set profile photo', 'sbe-projects' ),
				'remove_featured_image' => __( 'Remove profile photo', 'sbe-projects' ),
				'use_featured_image' => __( 'Use as profile photo', 'sbe-projects' ),
			),
			'public'              => false, // No pages of their own; people link to their external profile.
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-groups',
			'supports'            => array( 'title', 'thumbnail', 'custom-fields' ),
			'capability_type'     => 'post',
		)
	);

	$fields = array(
		'sbe_role'        => array( 'Role', 'sanitize_text_field' ),
		'sbe_profile_url' => array( 'Profile page (external link)', 'esc_url_raw' ),
		'sbe_interests'   => array( 'Research interests', 'sanitize_textarea_field' ),
		'sbe_order'       => array( 'Order on People page', 'sbe_people_sanitize_order' ),
		'sbe_contact'     => array( 'Contact person', 'sbe_people_sanitize_flag' ),
	);
	foreach ( $fields as $key => $field ) {
		register_post_meta(
			'person',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'label'             => $field[0],
				'show_in_rest'      => true,
				'sanitize_callback' => $field[1],
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}

	foreach ( sbe_people_taggable_types() as $type ) {
		register_post_meta(
			$type,
			'sbe_people',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'label'             => 'People',
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'sanitize_callback' => function ( $value ) {
					return array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) );
				},
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'sbe_people_register' );

/**
 * Staff groups (Researchers, Student assistants, Alumni, ...).
 * Internal only: used to split the Staff page into sections, never shown as tags.
 * Manage under People → Staff groups.
 */
function sbe_people_register_groups() {
	register_taxonomy(
		'person_group',
		array( 'person' ),
		array(
			'labels'             => array(
				'name'          => __( 'Staff groups', 'sbe-projects' ),
				'singular_name' => __( 'Staff group', 'sbe-projects' ),
				'all_items'     => __( 'All staff groups', 'sbe-projects' ),
				'edit_item'     => __( 'Edit staff group', 'sbe-projects' ),
				'update_item'   => __( 'Update staff group', 'sbe-projects' ),
				'add_new_item'  => __( 'Add New Staff Group', 'sbe-projects' ),
				'new_item_name' => __( 'New staff group name', 'sbe-projects' ),
				'menu_name'     => __( 'Staff groups', 'sbe-projects' ),
				'not_found'     => __( 'No staff groups found.', 'sbe-projects' ),
			),
			'hierarchical'       => true, // Checkbox list in the editor.
			'public'             => false, // No public tag pages.
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rewrite'            => false,
		)
	);

	register_term_meta(
		'person_group',
		'sbe_order',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => true,
			'sanitize_callback' => function ( $value ) {
				return (int) $value;
			},
		)
	);

	// Role shown on posts and projects for people in this group who have no Role of their own.
	register_term_meta(
		'person_group',
		'sbe_default_role',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// What the People page shows for each person in this group. Stored as "hide" flags
	// (sbe_hide_photo, sbe_hide_role, sbe_hide_interests = '1'), so everything is shown by default.
	foreach ( array_keys( sbe_people_group_fields() ) as $field ) {
		register_term_meta(
			'person_group',
			'sbe_hide_' . $field,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) {
					return $value ? '1' : '';
				},
			)
		);
	}

	// '1' = group is not listed on the People page (e.g. external collaborators, who
	// can still be tagged on posts and projects).
	register_term_meta(
		'person_group',
		'sbe_hidden',
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => function ( $value ) {
				return $value ? '1' : '';
			},
		)
	);
}
add_action( 'init', 'sbe_people_register_groups' );

/**
 * Optional parts of a person shown on the People page, switchable per staff group.
 * The name is always shown.
 */
function sbe_people_group_fields() {
	return array(
		'photo'     => __( 'Photo', 'sbe-projects' ),
		'role'      => __( 'Role', 'sbe-projects' ),
		'interests' => __( 'Research interests', 'sbe-projects' ),
	);
}

/**
 * Which parts the People page shows for a staff group: array( 'photo' => bool, ... ).
 */
function sbe_people_group_show( $term_id ) {
	$show = array();
	foreach ( array_keys( sbe_people_group_fields() ) as $field ) {
		$show[ $field ] = ! get_term_meta( $term_id, 'sbe_hide_' . $field, true );
	}
	return $show;
}

/**
 * Tick boxes "Show on the People page" for the group add/edit forms.
 */
function sbe_people_group_show_boxes( $term_id = 0 ) {
	$show = $term_id ? sbe_people_group_show( $term_id ) : array();
	$out  = '';
	foreach ( sbe_people_group_fields() as $field => $label ) {
		$on   = $term_id ? $show[ $field ] : true;
		$out .= '<label style="display:inline-block;margin-right:1.2em"><input type="checkbox" name="sbe_show[]" value="' . esc_attr( $field ) . '"' . checked( $on, true, false ) . ' /> ' . esc_html( $label ) . '</label>';
	}
	return $out;
}

/**
 * Staff group order: set by dragging rows (or the ↑ ↓ buttons) in the list under
 * People → Staff groups. Stored as term meta `sbe_order` = 1, 2, 3, ... (no gaps).
 * New groups are added at the bottom.
 */
add_action(
	'person_group_add_form_fields',
	function () {
		echo '<div class="form-field"><label for="sbe_default_role">' . esc_html__( 'Default role', 'sbe-projects' ) . '</label>'
			. '<input type="text" name="sbe_default_role" id="sbe_default_role" value="" />'
			. '<p>' . esc_html__( 'Optional. Shown under the name on posts and projects for people in this group who have no Role of their own, for example "Student assistant". Not shown on the People page.', 'sbe-projects' ) . '</p></div>'
			. '<div class="form-field"><label>' . esc_html__( 'Show on the People page', 'sbe-projects' ) . '</label>' . sbe_people_group_show_boxes()
			. '<p>' . esc_html__( 'What is shown for each person in this group on the People page, besides the name. Without photos the group becomes a simple list of names.', 'sbe-projects' ) . '</p></div>'
			. '<div class="form-field"><label><input type="checkbox" name="sbe_hidden" value="1" /> ' . esc_html__( 'Hide on the People page', 'sbe-projects' ) . '</label>'
			. '<p>' . esc_html__( 'People in this group can still be tagged on posts and projects (for example external collaborators).', 'sbe-projects' ) . '</p></div>'
			. '<p class="description">' . esc_html__( 'New groups are added at the bottom of the list. Drag them, or use the arrows, to change the order on the People page.', 'sbe-projects' ) . '</p>';
	}
);
add_action(
	'person_group_edit_form_fields',
	function ( $term ) {
		echo '<tr class="form-field"><th scope="row"><label for="sbe_default_role">' . esc_html__( 'Default role', 'sbe-projects' ) . '</label></th>'
			. '<td><input type="text" name="sbe_default_role" id="sbe_default_role" value="' . esc_attr( get_term_meta( $term->term_id, 'sbe_default_role', true ) ) . '" />'
			. '<p class="description">' . esc_html__( 'Optional. Shown under the name on posts and projects for people in this group who have no Role of their own, for example "Student assistant". Not shown on the People page.', 'sbe-projects' ) . '</p></td></tr>'
			. '<tr class="form-field"><th scope="row">' . esc_html__( 'Show on the People page', 'sbe-projects' ) . '</th>'
			. '<td>' . sbe_people_group_show_boxes( $term->term_id )
			. '<p class="description">' . esc_html__( 'What is shown for each person in this group on the People page, besides the name. Without photos the group becomes a simple list of names.', 'sbe-projects' ) . '</p></td></tr>'
			. '<tr class="form-field"><th scope="row">' . esc_html__( 'People page', 'sbe-projects' ) . '</th>'
			. '<td><label><input type="checkbox" name="sbe_hidden" value="1"' . checked( '1', get_term_meta( $term->term_id, 'sbe_hidden', true ), false ) . ' /> ' . esc_html__( 'Hide on the People page', 'sbe-projects' ) . '</label>'
			. '<p class="description">' . esc_html__( 'People in this group can still be tagged on posts and projects (for example external collaborators).', 'sbe-projects' ) . '</p>'
			. '<input type="hidden" name="sbe_group_form" value="1" /></td></tr>';
	}
);
add_action(
	'person_group_add_form_fields',
	function () {
		echo '<input type="hidden" name="sbe_group_form" value="1" />';
	},
	20
);
$sbe_save_group_settings = function ( $term_id ) {
	if ( isset( $_POST['sbe_group_form'] ) && current_user_can( 'manage_categories' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- core verifies the term form nonce.
		$default_role = isset( $_POST['sbe_default_role'] ) ? sanitize_text_field( wp_unslash( $_POST['sbe_default_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' !== $default_role ) {
			update_term_meta( $term_id, 'sbe_default_role', $default_role );
		} else {
			delete_term_meta( $term_id, 'sbe_default_role' );
		}
		$shown = isset( $_POST['sbe_show'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sbe_show'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		foreach ( array_keys( sbe_people_group_fields() ) as $field ) {
			if ( in_array( $field, $shown, true ) ) {
				delete_term_meta( $term_id, 'sbe_hide_' . $field );
			} else {
				update_term_meta( $term_id, 'sbe_hide_' . $field, '1' );
			}
		}
		if ( ! empty( $_POST['sbe_hidden'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $term_id, 'sbe_hidden', '1' );
		} else {
			delete_term_meta( $term_id, 'sbe_hidden' );
		}
	}
};
add_action( 'created_person_group', $sbe_save_group_settings );
add_action( 'edited_person_group', $sbe_save_group_settings );

// New groups go to the bottom (however they are created).
add_action(
	'created_person_group',
	function ( $term_id ) {
		$max = 0;
		foreach ( sbe_people_get_groups() as $group ) {
			if ( (int) $group->term_id !== (int) $term_id ) {
				$max = max( $max, (int) get_term_meta( $group->term_id, 'sbe_order', true ) );
			}
		}
		update_term_meta( $term_id, 'sbe_order', $max + 1 );
	},
	5
);

/**
 * Save a new group order. $ids = the groups whose order changed, in their new order.
 * They take the positions those groups already had in the full list, so the rest
 * stays put. Everything is then renumbered 1, 2, 3, ...
 */
function sbe_people_save_group_order( $ids ) {
	$all = wp_list_pluck( sbe_people_get_groups(), 'term_id' );
	$all = array_map( 'intval', $all );
	$ids = array_values( array_intersect( array_map( 'intval', $ids ), $all ) );
	$pos = array_keys( array_intersect( $all, $ids ) );
	foreach ( $pos as $i => $p ) {
		$all[ $p ] = $ids[ $i ];
	}
	foreach ( $all as $i => $term_id ) {
		update_term_meta( $term_id, 'sbe_order', $i + 1 );
	}
	return $all;
}

add_action(
	'wp_ajax_sbe_group_order',
	function () {
		check_ajax_referer( 'sbe_group_order' );
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( null, 403 );
		}
		$ids = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cast to int below.
		wp_send_json_success( sbe_people_save_group_order( array_map( 'intval', $ids ) ) );
	}
);

// One-off: renumber existing groups 1, 2, 3, ... (removes duplicate numbers).
add_action(
	'admin_init',
	function () {
		if ( get_option( 'sbe_people_groups_renumbered' ) ) {
			return;
		}
		sbe_people_save_group_order( array() );
		update_option( 'sbe_people_groups_renumbered', 1 );
	}
);

// Admin lists and checkboxes show staff groups in People page order.
add_filter(
	'get_terms',
	function ( $terms, $taxonomies, $args ) {
		if ( ! is_admin() || array( 'person_group' ) !== array_values( (array) $taxonomies ) || ! empty( $_GET['orderby'] ) || ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $terms;
		}
		if ( ! is_array( $terms ) || ! $terms || ! is_object( reset( $terms ) ) ) {
			return $terms;
		}
		usort( $terms, 'sbe_people_compare_groups' );
		return $terms;
	},
	10,
	3
);

add_filter(
	'manage_edit-person_group_columns',
	function ( $columns ) {
		unset( $columns['description'] );
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'cb' === $key ) {
				$new['sbe_order'] = __( 'Order', 'sbe-projects' );
			}
		}
		$new['sbe_default_role'] = __( 'Default role', 'sbe-projects' );
		$new['sbe_show']         = __( 'Shown on People page', 'sbe-projects' );
		$new['sbe_hidden']       = __( 'Hidden on People page', 'sbe-projects' );
		return $new;
	}
);
add_filter(
	'manage_person_group_custom_column',
	function ( $out, $column, $term_id ) {
		if ( 'sbe_hidden' === $column ) {
			return get_term_meta( $term_id, 'sbe_hidden', true ) ? '✓' : '';
		}
		if ( 'sbe_show' === $column ) {
			if ( get_term_meta( $term_id, 'sbe_hidden', true ) ) {
				return '—';
			}
			$parts = array( __( 'Name', 'sbe-projects' ) );
			$show  = sbe_people_group_show( $term_id );
			foreach ( sbe_people_group_fields() as $field => $label ) {
				if ( $show[ $field ] ) {
					$parts[] = $label;
				}
			}
			return esc_html( implode( ', ', $parts ) );
		}
		if ( 'sbe_default_role' === $column ) {
			return esc_html( get_term_meta( $term_id, 'sbe_default_role', true ) );
		}
		if ( 'sbe_order' === $column ) {
			return '<span class="sbe-go" data-order="' . esc_attr( (int) get_term_meta( $term_id, 'sbe_order', true ) ) . '">'
				. '<span class="sbe-go__handle" title="' . esc_attr__( 'Drag to reorder', 'sbe-projects' ) . '" aria-hidden="true">⠿</span>'
				. '<button type="button" class="button-link sbe-go__up" aria-label="' . esc_attr__( 'Move up', 'sbe-projects' ) . '">↑</button>'
				. '<button type="button" class="button-link sbe-go__down" aria-label="' . esc_attr__( 'Move down', 'sbe-projects' ) . '">↓</button>'
				. '</span>';
		}
		return $out;
	},
	10,
	3
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'edit-tags.php' !== $hook || empty( $_GET['taxonomy'] ) || 'person_group' !== $_GET['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$file = SBE_PROJECTS_DIR . '/assets/group-order.js';
		wp_enqueue_script( 'sbe-group-order', plugins_url( 'assets/group-order.js', SBE_PROJECTS_FILE ), array( 'jquery', 'jquery-ui-sortable' ), SBE_PROJECTS_VERSION . '.' . filemtime( $file ), true );
		wp_localize_script(
			'sbe-group-order',
			'sbeGroupOrder',
			array(
				'nonce'    => wp_create_nonce( 'sbe_group_order' ),
				'enabled'  => empty( $_GET['orderby'] ) && empty( $_GET['s'] ), // phpcs:ignore WordPress.Security.NonceVerification
				'saved'    => __( 'Order saved.', 'sbe-projects' ),
				'error'    => __( 'Could not save the order. Reload the page and try again.', 'sbe-projects' ),
				'disabled' => __( 'Clear the search or sorting to change the order.', 'sbe-projects' ),
			)
		);
		wp_add_inline_style(
			'common',
			'.column-sbe_order{width:5.5em}.sbe-go{display:inline-flex;align-items:center;gap:2px;white-space:nowrap}'
			. '.sbe-go__handle{cursor:grab;color:#787c82;font-size:16px;padding:0 4px 0 0}'
			. '.sbe-go button{font-size:15px;line-height:1;padding:2px 4px;text-decoration:none}'
			. '.sbe-go button[disabled]{color:#c3c4c7;cursor:default}'
			. '#the-list tr.ui-sortable-helper{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.2);display:table}'
			. '#the-list tr.sbe-go-placeholder{background:#f0f6fc;outline:1px dashed #2271b1}'
			. '.sbe-go-off .sbe-go__handle,.sbe-go-off .sbe-go button{display:none}'
		);
	}
);

/**
 * Create the default staff groups once.
 */
function sbe_people_default_groups() {
	if ( get_option( 'sbe_people_groups_seeded' ) ) {
		return;
	}
	$defaults = array(
		'researchers'        => array( 'Researchers', 1 ),
		'student-assistants' => array( 'Student assistants', 2 ),
		'alumni'             => array( 'Alumni', 3 ),
	);
	foreach ( $defaults as $slug => $group ) {
		$term = term_exists( $slug, 'person_group' );
		if ( ! $term ) {
			$term = wp_insert_term( $group[0], 'person_group', array( 'slug' => $slug ) );
		}
		if ( ! is_wp_error( $term ) ) {
			update_term_meta( (int) $term['term_id'], 'sbe_order', $group[1] );
		}
	}
	update_option( 'sbe_people_groups_seeded', 1 );
}
add_action( 'init', 'sbe_people_default_groups', 20 );

/**
 * "Profile details" box on the person edit screen.
 *
 * People have no content area, so WordPress edits them on the classic screen
 * (not the block editor). The fields therefore live in a regular meta box.
 */
add_action(
	'add_meta_boxes_person',
	function () {
		add_meta_box( 'sbe_person_details', __( 'Profile details', 'sbe-projects' ), 'sbe_people_details_box', 'person', 'normal', 'high' );
		remove_meta_box( 'postcustom', 'person', 'normal' ); // Hide the raw "Custom Fields" box.
		remove_meta_box( 'slugdiv', 'person', 'normal' );
		// Staff groups box with a short hint above the tick boxes (since 1.23.1).
		remove_meta_box( 'person_groupdiv', 'person', 'side' );
		add_meta_box( 'person_groupdiv', __( 'Staff groups', 'sbe-projects' ), 'sbe_people_groups_box', 'person', 'side', 'default', array( 'taxonomy' => 'person_group' ) );
	}
);

/*
 * Person screen hints (since 1.23.1): like the Add content sidebar in the block editor,
 * every box says in one line what it is for, so editors don't need the tutorial.
 */

/** Staff groups box: hint, then WordPress's normal tick boxes. */
function sbe_people_groups_box( $post, $box ) {
	echo '<p class="description">' . wp_kses(
		__( 'The section of the People page they are listed in. <strong>External collaborators</strong> are not listed but can be tagged. Someone left? Tick <strong>Alumni</strong> (and untick the old group) instead of deleting them.', 'sbe-projects' ),
		array( 'strong' => array() )
	) . '</p>';
	post_categories_meta_box( $post, $box );
}

/** Profile photo box: hint above the button. */
add_filter(
	'admin_post_thumbnail_html',
	function ( $html, $post_id ) {
		if ( 'person' !== get_post_type( $post_id ) ) {
			return $html;
		}
		return '<p class="description">' . esc_html__( 'Square, head and shoulders. Without a photo their initials are shown.', 'sbe-projects' ) . '</p>' . $html;
	},
	10,
	2
);

/** One line under the name field. */
add_action(
	'edit_form_after_title',
	function ( $post ) {
		if ( 'person' !== $post->post_type ) {
			return;
		}
		echo '<p class="description" style="margin:12px 0 0">' . esc_html__( 'Type the full name above, fill in the boxes, then press Publish. The person appears on the People page in the ticked staff group and can be tagged on posts, projects and resources.', 'sbe-projects' ) . '</p>';
	}
);

/**
 * Hide what editors shouldn't change on the person screen: status / visibility / date
 * in the Publish box, and adding staff groups or the "Most Used" tab (groups are set up
 * under People → Staff groups). Administrators see everything.
 */
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'person' !== $screen->post_type || current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<style>#misc-publishing-actions,#person_group-adder,#person_group-tabs{display:none}</style>';
	}
);

/**
 * Optional order number: whole number, or empty string when not set.
 */
function sbe_people_sanitize_order( $value ) {
	$value = trim( (string) $value );
	return ( '' === $value || ! is_numeric( $value ) ) ? '' : (string) intval( $value );
}

/**
 * Tick box value: '1' when ticked, empty string otherwise.
 */
function sbe_people_sanitize_flag( $value ) {
	return ( $value && '0' !== (string) $value ) ? '1' : '';
}

/**
 * Sort people: those with an order number first (lowest first), then everyone else by name.
 */
function sbe_people_sort( $people ) {
	usort(
		$people,
		function ( $a, $b ) {
			$oa = sbe_people_sanitize_order( get_post_meta( $a->ID, 'sbe_order', true ) );
			$ob = sbe_people_sanitize_order( get_post_meta( $b->ID, 'sbe_order', true ) );
			if ( '' !== $oa && '' !== $ob && (int) $oa !== (int) $ob ) {
				return (int) $oa <=> (int) $ob;
			}
			if ( ( '' === $oa ) !== ( '' === $ob ) ) {
				return '' === $oa ? 1 : -1;
			}
			return strcasecmp( $a->post_title, $b->post_title );
		}
	);
	return $people;
}

function sbe_people_details_box( $post ) {
	wp_nonce_field( 'sbe_person_details', 'sbe_person_details_nonce' );
	$role      = get_post_meta( $post->ID, 'sbe_role', true );
	$url       = get_post_meta( $post->ID, 'sbe_profile_url', true );
	$interests = get_post_meta( $post->ID, 'sbe_interests', true );
	$order     = get_post_meta( $post->ID, 'sbe_order', true );
	$contact   = get_post_meta( $post->ID, 'sbe_contact', true );
	?>
	<p><?php esc_html_e( 'All fields are optional. Add the profile photo in the "Profile photo" box.', 'sbe-projects' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="sbe_role"><?php esc_html_e( 'Role', 'sbe-projects' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="sbe_role" name="sbe_role" value="<?php echo esc_attr( $role ); ?>" />
				<p class="description"><?php esc_html_e( 'For example "Professor" or "PhD student".', 'sbe-projects' ); ?><?php $sbe_def = sbe_people_default_role( $post->ID ); if ( $sbe_def ) { echo ' ' . esc_html( sprintf( /* translators: %s: default role */ __( 'If empty, posts and projects show "%s" (from the staff group).', 'sbe-projects' ), $sbe_def ) ); } ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="sbe_profile_url"><?php esc_html_e( 'Profile page (external link)', 'sbe-projects' ); ?></label></th>
			<td>
				<input type="url" class="large-text" id="sbe_profile_url" name="sbe_profile_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://" />
				<p class="description"><?php esc_html_e( 'The full address of their staff page. Their name and photo link here.', 'sbe-projects' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="sbe_interests"><?php esc_html_e( 'Research interests', 'sbe-projects' ); ?></label></th>
			<td>
				<textarea class="large-text" rows="4" id="sbe_interests" name="sbe_interests"><?php echo esc_textarea( $interests ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Shown on the People page only.', 'sbe-projects' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="sbe_order"><?php esc_html_e( 'Order on People page', 'sbe-projects' ); ?></label></th>
			<td>
				<input type="number" step="1" style="width:6em" id="sbe_order" name="sbe_order" value="<?php echo esc_attr( $order ); ?>" />
				<p class="description"><?php esc_html_e( 'Optional. People with a number come first in their section, lowest number first. Everyone else follows in alphabetical order.', 'sbe-projects' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Contact person', 'sbe-projects' ); ?></th>
			<td>
				<label for="sbe_contact"><input type="checkbox" id="sbe_contact" name="sbe_contact" value="1" <?php checked( '1', $contact ); ?> /> <?php esc_html_e( 'Show on the Contact page', 'sbe-projects' ); ?></label>
				<p class="description"><?php esc_html_e( 'Shown with photo, name and role. Uses the same order as the People page.', 'sbe-projects' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

add_action(
	'save_post_person',
	function ( $post_id ) {
		if ( ! isset( $_POST['sbe_person_details_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sbe_person_details_nonce'] ) ), 'sbe_person_details' )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = array(
			'sbe_role'        => 'sanitize_text_field',
			'sbe_profile_url' => 'esc_url_raw',
			'sbe_interests'   => 'sanitize_textarea_field',
			'sbe_order'       => 'sbe_people_sanitize_order',
			'sbe_contact'     => 'sbe_people_sanitize_flag',
		);
		foreach ( $fields as $key => $sanitize ) {
			$value = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( '' === trim( (string) $value ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
);

/**
 * "Full name" placeholder instead of "Add title" for people.
 */
add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return ( $post && 'person' === $post->post_type ) ? __( 'Full name', 'sbe-projects' ) : $text;
	},
	10,
	2
);

/**
 * Admin list: sort people by name and show photo + role columns.
 */
add_filter(
	'manage_person_posts_columns',
	function ( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['sbe_photo'] = __( 'Photo', 'sbe-projects' );
				$new[ $key ]      = __( 'Name', 'sbe-projects' );
				$new['sbe_role']  = __( 'Role', 'sbe-projects' );
				$new['sbe_order'] = __( 'Order', 'sbe-projects' );
				$new['sbe_contact'] = __( 'Contact', 'sbe-projects' );
				continue;
			}
			$new[ $key ] = $label;
		}
		return $new;
	}
);
add_action(
	'manage_person_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'sbe_photo' === $column ) {
			echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'width:48px;height:48px;object-fit:cover;border-radius:50%;' ) );
		} elseif ( 'sbe_order' === $column ) {
			echo esc_html( get_post_meta( $post_id, 'sbe_order', true ) );
		} elseif ( 'sbe_contact' === $column ) {
			echo '1' === get_post_meta( $post_id, 'sbe_contact', true ) ? '<span aria-label="' . esc_attr__( 'Contact person', 'sbe-projects' ) . '">&#10003;</span>' : '';
		} elseif ( 'sbe_role' === $column ) {
			$role = get_post_meta( $post_id, 'sbe_role', true );
			if ( '' !== (string) $role ) {
				echo esc_html( $role );
			} elseif ( sbe_people_default_role( $post_id ) ) {
				echo '<span style="color:#787c82" title="' . esc_attr__( 'Default role from the staff group, shown on posts and projects only', 'sbe-projects' ) . '">' . esc_html( sbe_people_default_role( $post_id ) ) . ' ' . esc_html__( '(default)', 'sbe-projects' ) . '</span>';
			}
		}
	},
	10,
	2
);
add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() && $query->is_main_query() && 'person' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
		}
	}
);
add_action(
	'admin_head',
	function () {
		echo '<style>.post-type-person .column-sbe_photo{width:64px}.post-type-person .column-sbe_contact{width:70px}</style>';
	}
);

/**
 * Register the People block (rendered in PHP).
 */
function sbe_people_register_block() {
	wp_register_script(
		'sbe-people-block',
		plugins_url( 'assets/people-block.js', SBE_PROJECTS_FILE ),
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-data', 'wp-server-side-render' ),
		SBE_PROJECTS_VERSION,
		true
	);

	wp_register_style(
		'sbe-people',
		plugins_url( 'assets/people.css', SBE_PROJECTS_FILE ),
		array(),
		SBE_PROJECTS_VERSION
	);

	register_block_type(
		'sbe/people',
		array(
			'api_version'     => 3,
			'title'           => __( 'People', 'sbe-projects' ),
			'category'        => 'theme',
			'icon'            => 'groups',
			'description'     => __( 'Shows the people tagged on this post, project or resource, all staff, or the contact persons.', 'sbe-projects' ),
			'attributes'      => array(
				'show'    => array(
					'type'    => 'string',
					'enum'    => array( 'tagged', 'all', 'contact' ),
					'default' => 'tagged',
				),
				'heading' => array(
					'type'    => 'string',
					'default' => '',
				),
				'align'   => array( 'type' => 'string' ),
			),
			'supports'        => array(
				'align' => array( 'wide', 'full' ),
				'html'  => false,
			),
			'uses_context'    => array( 'postId', 'postType' ),
			'style_handles'   => array( 'sbe-people' ),
			'editor_script_handles' => array( 'sbe-people-block' ),
			'render_callback' => 'sbe_people_render_block',
		)
	);
}
add_action( 'init', 'sbe_people_register_block' );

/**
 * Get the people IDs tagged on a post, in the order they were tagged, published only.
 */
function sbe_people_get_tagged( $post_id ) {
	$ids = array_filter( array_map( 'absint', (array) get_post_meta( $post_id, 'sbe_people', true ) ) );
	if ( ! $ids ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'person',
			'post_status'    => 'publish',
			'post__in'       => $ids,
			'orderby'        => 'post__in',
			'posts_per_page' => -1,
		)
	);
}

/**
 * Initials for the photo placeholder, e.g. "Anna Svensson" → "AS".
 */
function sbe_people_initials( $name ) {
	$parts = preg_split( '/\s+/u', trim( wp_strip_all_tags( $name ) ) );
	$parts = array_values( array_filter( $parts ) );
	if ( ! $parts ) {
		return '';
	}
	$first = mb_substr( $parts[0], 0, 1 );
	$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}

/**
 * Render one person as a list item. Every field except the name is optional.
 *
 * @param WP_Post $person         Person.
 * @param bool    $with_interests Include research interests.
 * @param bool    $with_photo     Include a photo (or an initials placeholder when the person has none).
 * @param bool    $use_default_role Fall back to the staff group's default role (null = when no interests).
 * @param bool    $with_role      Include the role.
 */
function sbe_people_render_person( $person, $with_interests, $with_photo = true, $use_default_role = null, $with_role = true ) {
	$name      = get_the_title( $person );
	$role      = $with_role ? trim( (string) get_post_meta( $person->ID, 'sbe_role', true ) ) : '';
	$url       = trim( (string) get_post_meta( $person->ID, 'sbe_profile_url', true ) );
	$interests = $with_interests ? trim( (string) get_post_meta( $person->ID, 'sbe_interests', true ) ) : '';
	if ( null === $use_default_role ) {
		$use_default_role = ! $with_interests;
	}
	if ( '' === $role && $use_default_role && $with_role ) {
		// Posts, projects and the Contact page (not the People page, where the section heading says it).
		$role = sbe_people_default_role( $person->ID );
	}

	$photo = '';
	if ( $with_photo ) {
		$thumb_id = get_post_thumbnail_id( $person );
		if ( $thumb_id ) {
			sbe_people_ensure_photo_size( $thumb_id );
		}
		$photo = get_the_post_thumbnail(
			$person,
			'sbe-person',
			array(
				'class'   => 'sbe-person__photo',
				'sizes'   => '(max-width: 600px) 100vw, 320px',
				'alt'     => $name,
				'loading' => 'lazy',
			)
		);
		if ( ! $photo ) {
			$photo = '<span class="sbe-person__photo sbe-person__photo--empty" aria-hidden="true"><span>'
				. esc_html( sbe_people_initials( $name ) ) . '</span></span>';
		}
	}

	$name_html = esc_html( $name );
	if ( $url ) {
		if ( $photo ) {
			$photo = '<a href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $photo . '</a>';
		}
		$name_html = '<a href="' . esc_url( $url ) . '">' . $name_html . '</a>';
	}

	return '<li class="sbe-person' . ( $url ? '' : ' sbe-person--no-link' ) . '">' . $photo
		. '<p class="sbe-person__name">' . $name_html . '</p>'
		. ( $role ? '<p class="sbe-person__role">' . esc_html( $role ) . '</p>' : '' )
		. ( $interests ? '<div class="sbe-person__interests"><p class="sbe-person__interests-title">' . esc_html__( 'Research interests', 'sbe-projects' ) . '</p><p>' . nl2br( esc_html( $interests ) ) . '</p></div>' : '' )
		. '</li>';
}

/**
 * Default role from the person's staff group(s), e.g. "Student assistant".
 * First group (in People page order) that has one wins. Empty when none.
 */
function sbe_people_default_role( $person_id ) {
	$groups = get_the_terms( $person_id, 'person_group' );
	if ( ! $groups || is_wp_error( $groups ) ) {
		return '';
	}
	usort( $groups, 'sbe_people_compare_groups' );
	foreach ( $groups as $group ) {
		$role = trim( (string) get_term_meta( $group->term_id, 'sbe_default_role', true ) );
		if ( '' !== $role ) {
			return $role;
		}
	}
	return '';
}

/**
 * Render a list of people.
 * If nobody in the list has a photo (e.g. an Alumni section), it becomes a compact text list
 * without photo boxes; otherwise people without a photo get an initials placeholder.
 * $show (People page, from the staff group's "Show on the People page" boxes):
 * array( 'photo' => bool, 'role' => bool, 'interests' => bool ). No photo → compact list.
 */
function sbe_people_render_list( $people, $with_interests, $show = null ) {
	$show      = wp_parse_args( (array) $show, array( 'photo' => true, 'role' => true, 'interests' => true ) );
	$any_photo = false;
	foreach ( $show['photo'] ? $people : array() as $person ) {
		if ( has_post_thumbnail( $person ) ) {
			$any_photo = true;
			break;
		}
	}

	$items = '';
	foreach ( $people as $person ) {
		$items .= sbe_people_render_person( $person, $with_interests && $show['interests'], $any_photo, $with_interests ? false : null, $show['role'] );
	}
	return '<ul class="sbe-people__list' . ( $any_photo ? '' : ' sbe-people__list--compact' ) . '">' . $items . '</ul>';
}

/**
 * Staff groups in display order (by "Order" field, then name).
 */
function sbe_people_get_groups() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'person_group',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort( $terms, 'sbe_people_compare_groups' );
	return $terms;
}

/**
 * Sort callback for staff groups: "Order" number, then name.
 */
function sbe_people_compare_groups( $a, $b ) {
	$oa = (int) get_term_meta( $a->term_id, 'sbe_order', true );
	$ob = (int) get_term_meta( $b->term_id, 'sbe_order', true );
	return $oa === $ob ? strcasecmp( $a->name, $b->name ) : $oa <=> $ob;
}

/**
 * Render callback for the People block.
 */
function sbe_people_render_block( $attributes, $content, $block ) {
	$show    = in_array( $attributes['show'] ?? '', array( 'all', 'contact' ), true ) ? $attributes['show'] : 'tagged';
	$heading = trim( (string) ( $attributes['heading'] ?? '' ) );
	$body    = '';

	if ( 'all' === $show ) {
		$base = array(
			'post_type'      => 'person',
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		);

		// One section per staff group, in the chosen order. Empty groups are skipped.
		foreach ( sbe_people_get_groups() as $group ) {
			if ( get_term_meta( $group->term_id, 'sbe_hidden', true ) ) {
				continue; // e.g. external collaborators: taggable, but not listed here.
			}
			$people = get_posts(
				$base + array(
					'tax_query' => array(
						array(
							'taxonomy' => 'person_group',
							'terms'    => $group->term_id,
						),
					),
				)
			);
			if ( $people ) {
				$people = sbe_people_sort( $people );
				$body  .= '<section class="sbe-people__group sbe-people__group--' . esc_attr( $group->slug ) . '">'
					. '<h2 class="wp-block-heading sbe-people__group-heading">' . esc_html( $group->name ) . '</h2>'
					. sbe_people_render_list( $people, true, sbe_people_group_show( $group->term_id ) )
					. '</section>';
			}
		}

		// People without a group are listed last, without a heading.
		$ungrouped = get_posts(
			$base + array(
				'tax_query' => array(
					array(
						'taxonomy' => 'person_group',
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);
		if ( $ungrouped ) {
			$ungrouped = sbe_people_sort( $ungrouped );
			$body     .= '<section class="sbe-people__group sbe-people__group--none">' . sbe_people_render_list( $ungrouped, true ) . '</section>';
		}
	} elseif ( 'contact' === $show ) {
		// People with the "Contact person" box ticked, in Staff page order.
		$people = get_posts(
			array(
				'post_type'      => 'person',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => 'sbe_contact', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		if ( $people ) {
			$body = sbe_people_render_list( sbe_people_sort( $people ), false );
		}
	} else {
		$post_id = $block->context['postId'] ?? get_the_ID();
		$people  = $post_id ? sbe_people_get_tagged( $post_id ) : array();
		// Editor preview: the unsaved People panel (includes/editor-simple.php).
		$preview = function_exists( 'sbe_editor_preview_value' ) ? sbe_editor_preview_value( 'people', $post_id ) : null;
		if ( is_array( $preview ) ) {
			$ids    = array_values( array_filter( array_map( 'absint', $preview ) ) );
			$people = $ids ? get_posts(
				array(
					'post_type'      => 'person',
					'post_status'    => 'publish',
					'post__in'       => $ids,
					'orderby'        => 'post__in',
					'posts_per_page' => -1,
				)
			) : array();
		}
		if ( $people ) {
			$body = sbe_people_render_list( $people, false );
		}
	}

	if ( '' === $body ) {
		return '';
	}

	$wrapper = get_block_wrapper_attributes( array( 'class' => 'sbe-people sbe-people--' . $show ) );

	return '<div ' . $wrapper . '>'
		. ( $heading ? '<h3 class="wp-block-heading sbe-people__heading">' . esc_html( $heading ) . '</h3>' : '' )
		. $body . '</div>';
}
