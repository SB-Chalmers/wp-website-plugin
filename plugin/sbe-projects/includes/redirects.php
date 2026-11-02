<?php
/**
 * Permanent redirects from old sb.chalmers.se URLs that no longer exist.
 * Add a line to sbe_redirect_map() to redirect another old address.
 */

defined( 'ABSPATH' ) || exit;

/** Old path (no slashes) => new path. */
function sbe_redirect_map() {
	return array(
		'lcaphdforum' => '/projects/nordic-built-environment-lca-phd-forum/',
		'mrstudio'    => '/projects/ace-mixed-reality-studio/',
	);
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	$map  = sbe_redirect_map();
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}
}, 1 ); // before redirect_canonical(), which would guess a similar slug
