<?php
/**
 * SBE Theme – child theme of Twenty Twenty-Five.
 *
 * - Loads the site-wide CSS (style.css) on the front end and in the editor.
 * - [sbe_site_name] shortcode (used in the footer copyright line).
 * - One-click setup: creates the standard pages (Home, News, Projects, People, Contact),
 *   sets Home as the front page and orders the menu. Needs the SBE Projects plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide CSS: front end and block editor.
 */
function sbe_theme_styles() {
	wp_enqueue_style( 'sbe-theme', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'sbe_theme_styles' );

function sbe_theme_editor_styles() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'sbe_theme_editor_styles' );

/**
 * [sbe_site_name] – the site title (Settings → General).
 */
function sbe_theme_site_name_shortcode() {
	return esc_html( get_bloginfo( 'name' ) );
}
add_shortcode( 'sbe_site_name', 'sbe_theme_site_name_shortcode' );

/**
 * Standard pages created by the setup: slug => [ title, file in starter-pages/, menu order ].
 * The Resources page (order 4) is created by the SBE Projects plugin itself.
 */
function sbe_theme_starter_pages() {
	return array(
		'home'     => array( 'Home', 'home.html', 1 ),
		'posts'    => array( 'News', 'news.html', 2 ),
		'projects' => array( 'Projects', 'projects.html', 3 ),
		'staff'    => array( 'People', 'people.html', 5 ),
		'contact'  => array( 'Contact', 'contact.html', 6 ),
	);
}

/**
 * Admin notice with the setup button (administrators only, until setup has run).
 */
function sbe_theme_setup_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['sbe_setup_done'] ) ) {
		$created = sanitize_text_field( wp_unslash( $_GET['sbe_setup_done'] ) );
		echo '<div class="notice notice-success is-dismissible"><p><strong>SBE setup done.</strong> ';
		echo $created ? esc_html( sprintf( 'Created: %s.', str_replace( ',', ', ', $created ) ) ) : esc_html( 'All pages already existed, nothing was overwritten.' );
		echo ' Next: add your logo and site title, and edit the footer (see SETUP.md).</p></div>';
		return;
	}
	if ( get_option( 'sbe_theme_setup_done' ) ) {
		return;
	}
	if ( ! defined( 'SBE_PROJECTS_VERSION' ) ) {
		echo '<div class="notice notice-warning"><p><strong>SBE Theme:</strong> activate the <em>SBE Projects</em> plugin first. The theme\'s pages and templates use its projects, people and resources.</p></div>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=sbe_theme_setup' ), 'sbe_theme_setup' );
	echo '<div class="notice notice-info"><p><strong>SBE Theme:</strong> create the standard pages (Home, News, Projects, People, Contact), set Home as the front page and order the menu? Existing pages with the same address are left as they are.</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Create the pages</a> ';
	echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sbe_theme_setup&skip=1' ), 'sbe_theme_setup' ) ) . '">Not now, hide this</a></p></div>';
}
add_action( 'admin_notices', 'sbe_theme_setup_notice' );

/**
 * Run the setup.
 */
function sbe_theme_run_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sbe_theme_setup' );
	update_option( 'sbe_theme_setup_done', 1 );

	if ( ! empty( $_GET['skip'] ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}

	// Project status terms (the plugin creates them on init; make sure they exist).
	$ids = array();
	foreach ( array( 'ongoing' => 'Ongoing', 'completed' => 'Completed' ) as $slug => $name ) {
		$term = get_term_by( 'slug', $slug, 'project_status' );
		if ( ! $term ) {
			$new  = wp_insert_term( $name, 'project_status', array( 'slug' => $slug ) );
			$term = is_wp_error( $new ) ? null : get_term( $new['term_id'], 'project_status' );
		}
		$ids[ $slug ] = $term ? (int) $term->term_id : 0;
	}

	$created = array();
	$home_id = 0;
	foreach ( sbe_theme_starter_pages() as $slug => $page ) {
		list( $title, $file, $order ) = $page;
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			if ( 'home' === $slug ) {
				$home_id = $existing->ID;
			}
			continue;
		}
		$content = file_get_contents( get_stylesheet_directory() . '/starter-pages/' . $file );
		$content = str_replace(
			array( '__SBE_ONGOING__', '__SBE_COMPLETED__' ),
			array( $ids['ongoing'], $ids['completed'] ),
			$content
		);
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'menu_order'   => $order,
				'post_content' => wp_slash( $content ),
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			$created[] = $title;
			if ( 'home' === $slug ) {
				$home_id = $id;
			}
		}
	}

	// Resources page (created by the plugin) goes between Projects and People.
	$resources = get_page_by_path( 'resources' );
	if ( $resources ) {
		wp_update_post( array( 'ID' => $resources->ID, 'menu_order' => 4 ) );
	}

	// Home as the front page.
	if ( $home_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );
	}

	// Pretty permalinks (same as sb.chalmers.se) if the site still uses plain ?p=123 links.
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
	}
	flush_rewrite_rules();

	wp_safe_redirect( add_query_arg( 'sbe_setup_done', rawurlencode( implode( ',', $created ) ), admin_url() ) );
	exit;
}
add_action( 'admin_post_sbe_theme_setup', 'sbe_theme_run_setup' );
