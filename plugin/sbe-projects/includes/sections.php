<?php
/**
 * Ready-made content sections ("SBE sections" block patterns) for posts, projects and
 * resources, plus their styles. Everything stacks vertically because these pages are a
 * narrow 760px column: nothing is placed side by side.
 *
 * Editors insert them from the "SBE sections" panel in the editor sidebar (editor.js),
 * or from the block inserter → Patterns → "SBE sections" (search "sbe" lists them all).
 * Every section is offered for all three types. The one exception is the Read more links
 * pattern on projects: projects have their own Read more links field (sidebar panel +
 * sbe/read-more block in the template), so the pattern is registered for posts and
 * resources only and the SBE sections panel opens that field instead on projects.
 * Publications (since 1.26.0) work the same way for all three types: their own field
 * (includes/publications.php), so the Publications pattern is hidden from the inserter
 * and its SBE sections button opens the Publications panel.
 * After inserting, every part is ordinary blocks and can be edited or removed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Styles for the sections (key facts box, publications list). Loaded on the site
 * and in the editor so the preview matches.
 */
function sbe_sections_enqueue() {
	$css = SBE_PROJECTS_DIR . '/assets/sections.css';
	wp_enqueue_style( 'sbe-sections', plugins_url( 'assets/sections.css', SBE_PROJECTS_FILE ), array(), (string) filemtime( $css ) );
}
add_action( 'enqueue_block_assets', 'sbe_sections_enqueue' );

/**
 * Register the pattern category and the patterns.
 */
function sbe_sections_register_patterns() {
	register_block_pattern_category(
		'sbe-sections',
		array(
			'label'       => __( 'SBE sections', 'sbe-projects' ),
			'description' => __( 'Ready-made sections for news posts, projects and resources.', 'sbe-projects' ),
		)
	);

	$types = array( 'post', 'project', 'resource' );

	$patterns = array(
		'sbe/video'             => array(
			'title'       => __( 'Video', 'sbe-projects' ),
			'description' => __( 'A YouTube or Vimeo video. Paste the video link into the box, then add a caption from the toolbar.', 'sbe-projects' ),
			'content'     => '<!-- wp:embed {"providerNameSlug":"youtube","responsive":true} /-->',
		),
		'sbe/video-section'     => array(
			'title'       => __( 'Video section (heading, video, text)', 'sbe-projects' ),
			'description' => __( 'A heading, a video and a paragraph about it.', 'sbe-projects' ),
			'content'     => '<!-- wp:heading -->
<h2 class="wp-block-heading">Videos</h2>
<!-- /wp:heading -->

<!-- wp:embed {"providerNameSlug":"youtube","responsive":true} /-->

<!-- wp:paragraph -->
<p>A few sentences about what the video shows.</p>
<!-- /wp:paragraph -->',
		),
		'sbe/image-caption'     => array(
			'title'       => __( 'Image with caption', 'sbe-projects' ),
			'description' => __( 'One image with a caption underneath.', 'sbe-projects' ),
			'content'     => '<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption">Caption: what the image shows (and photo credit if needed).</figcaption></figure>
<!-- /wp:image -->',
		),
		'sbe/images-stacked'    => array(
			'title'       => __( 'Images, one after another', 'sbe-projects' ),
			'description' => __( 'A heading and two images with captions, stacked vertically. Duplicate an image to add more.', 'sbe-projects' ),
			'content'     => '<!-- wp:heading -->
<h2 class="wp-block-heading">Images</h2>
<!-- /wp:heading -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption">First caption.</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img alt=""/><figcaption class="wp-element-caption">Second caption.</figcaption></figure>
<!-- /wp:image -->',
		),
		'sbe/publications'      => array(
			'title'       => __( 'Publications', 'sbe-projects' ),
			'description' => __( 'A list of papers: linked title, then authors, year and journal on the next line (Shift+Enter).', 'sbe-projects' ),
			// Since 1.26.0 publications have their own sidebar panel and template block
			// (includes/publications.php). The pattern stays registered (hidden from the
			// inserter) only so the SBE sections panel keeps a "→ Publications" button
			// in this place, which opens that panel.
			'inserter'    => false,
			'content'     => '<!-- wp:heading -->
<h2 class="wp-block-heading">Publications</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"sbe-publications"} -->
<ul class="wp-block-list sbe-publications"><!-- wp:list-item -->
<li><a href="https://research.chalmers.se/">Title of the first paper</a><br>Author A, Author B (2025). Journal or conference name.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="https://research.chalmers.se/">Title of the second paper</a><br>Author A, Author C (2024). Journal or conference name.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->',
		),
		'sbe/key-facts'         => array(
			'title'       => __( 'Key facts (funder, duration, partners)', 'sbe-projects' ),
			'description' => __( 'A light grey box with short facts. Delete the lines you do not need.', 'sbe-projects' ),
			'content'     => '<!-- wp:group {"metadata":{"name":"Key facts"},"className":"sbe-facts","layout":{"type":"default"}} -->
<div class="wp-block-group sbe-facts"><!-- wp:paragraph -->
<p><strong>Funder:</strong> Name of the funder</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Duration:</strong> 2025–2028</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Partners:</strong> Partner A, Partner B</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
		),
		'sbe/partners'          => array(
			'title'       => __( 'Project partners', 'sbe-projects' ),
			'description' => __( 'A heading and a list of partners.', 'sbe-projects' ),
			'content'     => '<!-- wp:heading -->
<h2 class="wp-block-heading">Project partners</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Partner organisation (Country)</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Partner organisation (Country)</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->',
		),
		'sbe/interview-question' => array(
			'title'       => __( 'Interview question and answer', 'sbe-projects' ),
			'description' => __( 'A bold question followed by the answer. Insert once per question.', 'sbe-projects' ),
			'content'     => '<!-- wp:paragraph -->
<p><strong>What is your research about?</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The answer goes here.</p>
<!-- /wp:paragraph -->',
		),
		'sbe/read-more'         => array(
			'title'       => __( 'Read more links', 'sbe-projects' ),
			'description' => __( 'A "Read more" heading with a list of links, same look as on project pages. Put it at the end of the text.', 'sbe-projects' ),
			'postTypes'   => array( 'post', 'resource' ),
			'content'     => '<!-- wp:group {"metadata":{"name":"Read more"},"className":"sbe-read-more","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group sbe-read-more" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:heading {"level":3,"className":"sbe-read-more__heading"} -->
<h3 class="wp-block-heading sbe-read-more__heading">Read more</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"sbe-read-more__list"} -->
<ul class="wp-block-list sbe-read-more__list"><!-- wp:list-item -->
<li><a href="https://research.chalmers.se/">Link text, e.g. Profile on Chalmers Research</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->',
		),
		'sbe/event-details'     => array(
			'title'       => __( 'Event details (date, place, registration)', 'sbe-projects' ),
			'description' => __( 'A light grey box with the practical details of an event. Delete the lines you do not need.', 'sbe-projects' ),
			'content'     => '<!-- wp:group {"metadata":{"name":"Event details"},"className":"sbe-facts","layout":{"type":"default"}} -->
<div class="wp-block-group sbe-facts"><!-- wp:paragraph -->
<p><strong>Date:</strong> 12 March 2026, 13:00–16:00</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Place:</strong> Room name, Chalmers campus Johanneberg</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Registration:</strong> <a href="https://">Register here</a> by 1 March</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
		),
		'sbe/quote'             => array(
			'title'       => __( 'Quote', 'sbe-projects' ),
			'description' => __( 'A highlighted quote with the name of the person or source.', 'sbe-projects' ),
			'content'     => '<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>The quote goes here.</p>
<!-- /wp:paragraph --><cite>Name, organisation</cite></blockquote>
<!-- /wp:quote -->',
		),
	);

	foreach ( $patterns as $name => $pattern ) {
		register_block_pattern(
			$name,
			$pattern + array(
				'categories' => array( 'sbe-sections' ),
				'keywords'   => array( 'sbe', 'section' ),
				'postTypes'  => $types,
			)
		);
	}
}
add_action( 'init', 'sbe_sections_register_patterns' );
