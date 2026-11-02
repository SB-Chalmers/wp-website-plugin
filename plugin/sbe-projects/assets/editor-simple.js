/**
 * Simple editor for posts, projects and resources (since 1.23.0, includes/editor-simple.php).
 *
 * One sidebar "Add content" (button with text in the top bar, open when the editor
 * loads) holds everything an author fills in: the selected picture's description,
 * main picture, second title / status / type, tags, people, links, gallery icon,
 * repository and the SBE sections. WordPress's own Settings sidebar (Post and Block
 * tabs with author, slug, template, block styles …) is never shown: whenever it would
 * open, the Add content sidebar opens instead.
 *
 * Administrators: ⋮ → "Show all WordPress settings" switches back to the normal
 * sidebar (stored per user); the same menu item switches the simple editor on again.
 * Plain JS, no build step.
 */
( function ( wp ) {
	const S = window.sbeSimple;
	const E = window.sbeEditor;
	if ( ! S || ! S.enabled || ! E ) {
		return;
	}
	const el = wp.element.createElement;
	const { Fragment } = wp.element;
	const { registerPlugin } = wp.plugins;
	const { PanelBody, TextareaControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const ed = wp.editor;
	const PluginSidebar = ( ed && ed.PluginSidebar ) || wp.editPost.PluginSidebar;
	const MoreMenuItem = ( ed && ed.PluginMoreMenuItem ) || wp.editPost.PluginMoreMenuItem;

	const PLUGIN = 'sbe-add-content';
	const NAME = 'add-content';
	const AREA = PLUGIN + '/' + NAME;
	const SCOPE = 'core';
	const CORE_AREAS = [ 'edit-post/document', 'edit-post/block' ];
	const TYPES = [ 'post', 'project', 'resource' ];

	const help = ( text ) => el( 'p', { className: 'sbe-simple-help' }, text );
	const usePostType = () => useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );

	/* Selected picture in the text: description (alt text). Replaces the Block tab's only must-have field. */
	function ImagePanel() {
		const id = useSelect( ( select ) => {
			const be = select( 'core/block-editor' );
			const selected = be.getSelectedBlockClientId();
			return selected && be.getBlockName( selected ) === 'core/image' ? selected : null;
		}, [] );
		const alt = useSelect( ( select ) => ( id ? ( select( 'core/block-editor' ).getBlockAttributes( id ) || {} ).alt || '' : '' ), [ id ] );
		const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
		if ( ! id ) {
			return null;
		}
		return el(
			PanelBody,
			{ title: 'Selected picture', initialOpen: true, className: 'sbe-simple-image' },
			el( TextareaControl, {
				__nextHasNoMarginBottom: true,
				label: 'Describe the picture',
				help: 'Read aloud to people who can’t see it, e.g. “Participants of the LCA PhD forum in Tallinn”. A caption under the picture is added with the picture’s own toolbar.',
				value: alt,
				onChange: ( v ) => updateBlockAttributes( id, { alt: v } ),
			} )
		);
	}

	/* Featured image. */
	function PicturePanel() {
		const PostFeaturedImage = ed.PostFeaturedImage;
		return el(
			PanelBody,
			{ title: 'Main picture', initialOpen: true },
			help( 'Shown at the top of the page and in the galleries. A landscape picture works best.' ),
			el( PostFeaturedImage )
		);
	}

	/* One taxonomy (only when it belongs to this post type). */
	function TaxPanel( { slug, title, text } ) {
		const postType = usePostType();
		const tax = useSelect( ( select ) => select( 'core' ).getTaxonomy( slug ), [ slug ] );
		if ( ! tax || ( tax.types || [] ).indexOf( postType ) === -1 ) {
			return null;
		}
		const Selector = tax.hierarchical ? ed.PostTaxonomiesHierarchicalTermSelector : ed.PostTaxonomiesFlatTermSelector;
		return el(
			PanelBody,
			{ title, initialOpen: true, className: 'sbe-simple-tax sbe-simple-tax--' + slug },
			text && help( text ),
			el( Selector, { slug } )
		);
	}

	function Intro() {
		const published = useSelect( ( select ) => select( 'core/editor' ).isCurrentPostPublished(), [] );
		return el(
			'div',
			{ className: 'sbe-simple-intro' },
			el(
				'p',
				null,
				'Write the title and text on the page to the left – it looks the way it will on the site (empty fields there say what to write). Fill in the boxes below, then press ',
				el( 'strong', null, published ? 'Save' : 'Publish' ),
				' (top right).'
			),
			S.helpUrl && el( 'p', null, el( 'a', { href: S.helpUrl, target: '_blank', rel: 'noopener' }, 'Help with adding content ↗' ) )
		);
	}

	function AddContentSidebar() {
		const simple = useSelect( E.isSimple, [] );
		const postType = usePostType();
		if ( ! simple || TYPES.indexOf( postType ) === -1 ) {
			return null;
		}
		return el(
			PluginSidebar,
			{ name: NAME, title: 'Add content', icon: 'edit-page', className: 'sbe-simple-sidebar' },
			el(
				E.InSidebar.Provider,
				{ value: true },
				el( Intro ),
				el( ImagePanel ),
				el( PicturePanel ),
				el( E.SubtitlePanel ),
				el( TaxPanel, { slug: 'project_status', title: 'Status', text: 'Ongoing or completed: decides where the project is listed on the Projects page.' } ),
				el( TaxPanel, { slug: 'category', title: 'Type of post', text: 'Post, Interview or Article. Shown on the cards in Related content and search.' } ),
				el( TaxPanel, { slug: 'post_tag', title: 'Tags', text: 'Topics. They link related news, projects and resources. Pick existing tags where you can.' } ),
				el( E.PeoplePanel ),
				el( E.LinksPanel ),
				el( E.PublicationsPanel ),
				el( E.ResourcePanel ),
				el( E.SectionsPanel )
			)
		);
	}

	/* Administrators: switch between the simple and the full sidebar. */
	function SwitchItem() {
		const full = useSelect( ( select ) => !! select( 'core/preferences' ).get( 'sbe', 'fullEditor' ), [] );
		const postType = usePostType();
		if ( ! S.canSwitch || TYPES.indexOf( postType ) === -1 ) {
			return null;
		}
		return el(
			MoreMenuItem,
			{
				onClick: () => {
					wp.data.dispatch( 'core/preferences' ).set( 'sbe', 'fullEditor', ! full );
					wp.data.dispatch( 'core/interface' ).enableComplementaryArea( SCOPE, full ? AREA : 'edit-post/document' );
				},
			},
			full ? 'Use the simple editor' : 'Show all WordPress settings'
		);
	}

	registerPlugin( PLUGIN, {
		render: () => el( Fragment, null, el( AddContentSidebar ), el( SwitchItem ) ),
	} );

	/*
	 * Keep the Add content sidebar in place of WordPress's Settings sidebar, open it once
	 * when the editor is ready (on screens wide enough for a sidebar), and mark <body>
	 * so editor-simple.css can hide the Settings button and label ours.
	 */
	let opened = false;
	wp.data.subscribe( () => {
		const select = wp.data.select;
		const editor = select( 'core/editor' );
		const type = editor && editor.getCurrentPostType();
		const simple = TYPES.indexOf( type ) !== -1 && E.isSimple( select );
		document.body.classList.toggle( 'sbe-simple-editor', simple );
		if ( ! simple ) {
			return;
		}
		const area = select( 'core/interface' ).getActiveComplementaryArea( SCOPE );
		if ( CORE_AREAS.indexOf( area ) !== -1 ) {
			wp.data.dispatch( 'core/interface' ).enableComplementaryArea( SCOPE, AREA );
			opened = true;
		} else if ( ! opened && editor.__unstableIsEditorReady && editor.__unstableIsEditorReady() ) {
			opened = true;
			if ( area !== AREA && window.innerWidth >= 782 ) {
				wp.data.dispatch( 'core/interface' ).enableComplementaryArea( SCOPE, AREA );
			}
		}
	} );
} )( window.wp );
