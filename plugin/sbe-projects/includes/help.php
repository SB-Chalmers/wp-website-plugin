<?php
/**
 * Help for editors, rendered live from the Markdown files in docs/.
 *
 * - Dashboard: a "Help" item in the left admin menu with tabs for the quick tutorial and the
 *   editor guide (and the build notes, for administrators only).
 * - Block editor: a "How to add content" sidebar (? icon in the top bar) showing the quick tutorial.
 *
 * The files are read and converted on every page load, so whatever is in docs/ is what editors
 * see. Markdown → HTML with Parsedown 1.7.4 (MIT, includes/vendor/).
 */

defined( 'ABSPATH' ) || exit;

/**
 * The documents shown, keyed by the ?doc= value. 'cap' = capability needed to see it.
 */
function sbe_help_docs() {
	return array(
		'tutorial' => array(
			'file'  => 'POSTS-AND-PROJECTS-TUTORIAL.md',
			'label' => __( 'Quick tutorial', 'sbe-projects' ),
			'cap'   => 'edit_posts',
		),
		'guide'    => array(
			'file'  => 'EDITOR-GUIDE.md',
			'label' => __( 'Editor guide', 'sbe-projects' ),
			'cap'   => 'edit_posts',
		),
		'build'    => array(
			'file'  => 'BUILD-NOTES.md',
			'label' => __( 'Build notes (admins)', 'sbe-projects' ),
			'cap'   => 'manage_options',
		),
	);
}

/**
 * URL of a document on the Help page.
 */
function sbe_help_url( $doc = 'tutorial' ) {
	return add_query_arg(
		array(
			'page' => 'sbe-help',
			'doc'  => $doc,
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Render one document to HTML. Returns '' when the file is missing.
 *
 * Links between the docs (e.g. "EDITOR-GUIDE.md") are pointed at the Help page tabs,
 * headings get ids so #anchors work, and external links open in a new tab.
 */
function sbe_help_render( $key ) {
	$docs = sbe_help_docs();
	if ( empty( $docs[ $key ] ) ) {
		return '';
	}
	$path = SBE_PROJECTS_DIR . '/docs/' . $docs[ $key ]['file'];
	if ( ! is_readable( $path ) ) {
		return '';
	}

	if ( ! class_exists( 'Parsedown' ) ) {
		require_once __DIR__ . '/vendor/Parsedown.php';
	}
	$parser = new Parsedown();
	$html   = $parser->text( (string) file_get_contents( $path ) );

	// Links to the other docs → the matching Help tab (keeps any #anchor).
	foreach ( $docs as $doc_key => $doc ) {
		$html = preg_replace(
			'/href="' . preg_quote( $doc['file'], '/' ) . '(#[^"]*)?"/',
			'href="' . esc_url( sbe_help_url( $doc_key ) ) . '$1"',
			$html
		);
	}

	// External links open in a new tab.
	$html = preg_replace( '/<a href="(https?:\/\/(?![^"]*page=sbe-help)[^"]*)"/', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html );

	// Ids on headings (same slugs as GitHub/VS Code: "Add a post" → #add-a-post).
	$html = preg_replace_callback(
		'/<h([2-4])>(.*?)<\/h\1>/s',
		function ( $m ) {
			$slug = sanitize_title( wp_strip_all_tags( $m[2] ) );
			return '<h' . $m[1] . ' id="' . esc_attr( $slug ) . '">' . $m[2] . '</h' . $m[1] . '>';
		},
		$html
	);

	// Tables scroll sideways in narrow places (the editor sidebar) instead of overflowing.
	$html = str_replace( array( '<table>', '</table>' ), array( '<div class="sbe-help-table"><table>', '</table></div>' ), $html );

	return wp_kses_post( $html );
}

/**
 * Admin menu: "Help", near the bottom of the content items.
 */
function sbe_help_menu() {
	add_menu_page(
		__( 'Help: adding content', 'sbe-projects' ),
		__( 'Help', 'sbe-projects' ),
		'edit_posts',
		'sbe-help',
		'sbe_help_page',
		'dashicons-editor-help',
		26 // Below Comments (25), above Appearance (60).
	);
}
add_action( 'admin_menu', 'sbe_help_menu' );

/**
 * The Help page: tabs + the rendered document.
 */
function sbe_help_page() {
	$docs = array_filter(
		sbe_help_docs(),
		function ( $doc ) {
			return current_user_can( $doc['cap'] );
		}
	);
	$current = isset( $_GET['doc'] ) ? sanitize_key( wp_unslash( $_GET['doc'] ) ) : 'tutorial'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( empty( $docs[ $current ] ) ) {
		$current = 'tutorial';
	}
	$path = SBE_PROJECTS_DIR . '/docs/' . $docs[ $current ]['file'];
	?>
	<div class="wrap sbe-help-wrap">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Help', 'sbe-projects' ); ?></h1>
		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Help documents', 'sbe-projects' ); ?>">
			<?php foreach ( $docs as $key => $doc ) : ?>
				<a href="<?php echo esc_url( sbe_help_url( $key ) ); ?>" class="nav-tab<?php echo $key === $current ? ' nav-tab-active' : ''; ?>"<?php echo $key === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $doc['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="sbe-help">
			<?php
			$html = sbe_help_render( $current );
			if ( '' === $html ) {
				echo '<p>' . esc_html__( 'This document could not be found.', 'sbe-projects' ) . '</p>';
			} else {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- passed through wp_kses_post().
				if ( is_readable( $path ) ) {
					echo '<p class="sbe-help-source">' . esc_html(
						sprintf(
							/* translators: 1: file name, 2: date */
							__( 'Shown from %1$s, last changed %2$s.', 'sbe-projects' ),
							'docs/' . $docs[ $current ]['file'],
							wp_date( get_option( 'date_format' ), filemtime( $path ) )
						)
					) . '</p>';
				}
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Styles for the Help page.
 */
function sbe_help_admin_assets( $hook ) {
	if ( 'toplevel_page_sbe-help' !== $hook ) {
		return;
	}
	$file = SBE_PROJECTS_DIR . '/assets/help.css';
	wp_enqueue_style( 'sbe-help', plugins_url( 'assets/help.css', SBE_PROJECTS_FILE ), array(), SBE_PROJECTS_VERSION . '.' . filemtime( $file ) );
}
add_action( 'admin_enqueue_scripts', 'sbe_help_admin_assets' );

/**
 * Block editor: "How to add content" sidebar with the quick tutorial.
 * Posts, projects, resources and pages (not the Site Editor).
 */
function sbe_help_editor_assets() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'project', 'resource', 'page' ), true ) ) {
		return;
	}
	$css = SBE_PROJECTS_DIR . '/assets/help.css';
	$js  = SBE_PROJECTS_DIR . '/assets/help-sidebar.js';
	wp_enqueue_style( 'sbe-help', plugins_url( 'assets/help.css', SBE_PROJECTS_FILE ), array(), SBE_PROJECTS_VERSION . '.' . filemtime( $css ) );
	wp_enqueue_script(
		'sbe-help-sidebar',
		plugins_url( 'assets/help-sidebar.js', SBE_PROJECTS_FILE ),
		array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components' ),
		SBE_PROJECTS_VERSION . '.' . filemtime( $js ),
		true
	);
	wp_add_inline_script(
		'sbe-help-sidebar',
		'window.sbeHelp = ' . wp_json_encode(
			array(
				'html'     => sbe_help_render( 'tutorial' ),
				'guideUrl' => sbe_help_url( 'guide' ),
				'helpUrl'  => sbe_help_url( 'tutorial' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'sbe_help_editor_assets' );
