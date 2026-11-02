/**
 * Editor side of the "Publications" block (rendered on the server, includes/publications.php).
 * Shows the unsaved Publications panel values while editing (sbe_editor_preview_value()).
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	function usePreviewArgs( contextPostId ) {
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
				if ( meta.sbe_publications === undefined ) {
					return '';
				}
				return JSON.stringify( { post_id: id, sbe_preview: JSON.stringify( { publications: meta.sbe_publications } ) } );
			},
			[ contextPostId ]
		);
		return json ? JSON.parse( json ) : null;
	}

	registerBlockType( 'sbe/publications', {
		usesContext: [ 'postId', 'postType' ],
		edit: function ( props ) {
			const { attributes, setAttributes, context } = props;
			const preview = usePreviewArgs( context && context.postId );
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
					block: 'sbe/publications',
					attributes: { heading: attributes.heading },
					urlQueryArgs: preview || ( context && context.postId ? { post_id: context.postId } : {} ),
					EmptyResponsePlaceholder: () =>
						el(
							'div',
							{ className: 'sbe-editor-hint-box', style: { padding: 16, border: '1px dashed currentColor', opacity: 0.7 } },
							el( 'strong', null, attributes.heading || 'Publications' ),
							el(
								'p',
								{ style: { margin: '8px 0 0' } },
								context && context.postId
									? 'Optional: add papers under “Publications” in Add content on the right →. Hidden on the site when there are none.'
									: 'The publications added to each post, project or resource appear here. Hidden when there are none.'
							)
						),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
