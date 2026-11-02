/**
 * Editor side of the "Read more links" block (rendered on the server).
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	/*
	 * Editor preview of unsaved sidebar values (see includes/editor-simple.php): returns
	 * { post_id, sbe_preview } for the block-renderer request when this block shows the
	 * post being edited, or null (e.g. in the Site Editor or inside a Query Loop).
	 */
	function usePreviewArgs( metaKey, key, contextPostId ) {
		const json = wp.data.useSelect(
			function ( select ) {
				const ed = select( 'core/editor' );
				if ( ! ed || ! ed.getCurrentPostType ) {
					return '';
				}
				if ( [ 'post', 'project', 'resource' ].indexOf( ed.getCurrentPostType() ) === -1 ) {
					return '';
				}
				const id = ed.getCurrentPostId();
				if ( contextPostId && contextPostId !== id ) {
					return '';
				}
				const meta = ed.getEditedPostAttribute( 'meta' ) || {};
				if ( meta[ metaKey ] === undefined ) {
					return '';
				}
				const data = {};
				data[ key ] = meta[ metaKey ];
				return JSON.stringify( { post_id: id, sbe_preview: JSON.stringify( data ) } );
			},
			[ metaKey, key, contextPostId ]
		);
		return json ? JSON.parse( json ) : null;
	}

	registerBlockType( 'sbe/read-more', {
		usesContext: [ 'postId', 'postType' ],
		edit: function ( props ) {
			const { attributes, setAttributes, context } = props;
			const preview = usePreviewArgs( 'sbe_links', 'links', context && context.postId );
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Settings' },
						el( TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: 'Heading',
							value: attributes.heading,
							onChange: ( v ) => setAttributes( { heading: v } ),
						} )
					)
				),
				el( ServerSideRender, {
					block: 'sbe/read-more',
					attributes: { heading: attributes.heading },
					urlQueryArgs: preview || {},
					EmptyResponsePlaceholder: () =>
						el(
							'div',
							{ style: { padding: 24, border: '1px dashed currentColor', opacity: 0.7 } },
							el( 'strong', null, attributes.heading || 'Read more' ),
							el( 'p', { style: { margin: '8px 0 0' } }, 'The project’s "Read more" links will appear here. Hidden when a project has none.' )
						),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
