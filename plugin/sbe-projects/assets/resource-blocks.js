/**
 * Editor side of the "Resource icon" and "Repository link" blocks (rendered on the server).
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl } = wp.components;
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

	const placeholder = ( title, text ) => () =>
		el(
			'div',
			{ style: { padding: 16, border: '1px dashed currentColor', opacity: 0.7 } },
			el( 'strong', null, title ),
			el( 'p', { style: { margin: '8px 0 0' } }, text )
		);

	registerBlockType( 'sbe/resource-icon', {
		usesContext: [ 'postId', 'postType' ],
		edit: function ( props ) {
			const { attributes, setAttributes, context } = props;
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Settings' },
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Link to the resource',
							checked: !! attributes.isLink,
							onChange: ( v ) => setAttributes( { isLink: v } ),
						} )
					)
				),
				el( ServerSideRender, {
					block: 'sbe/resource-icon',
					attributes: { isLink: attributes.isLink },
					urlQueryArgs: context && context.postId ? { post_id: context.postId } : {},
					EmptyResponsePlaceholder: placeholder( 'Resource icon', 'Each resource’s gallery icon appears here.' ),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );

	registerBlockType( 'sbe/repository-link', {
		usesContext: [ 'postId', 'postType' ],
		edit: function ( props ) {
			const { attributes, setAttributes, context } = props;
			const preview = usePreviewArgs( 'sbe_repo_url', 'repo', context && context.postId );
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
							label: 'Link text',
							value: attributes.text,
							onChange: ( v ) => setAttributes( { text: v } ),
						} )
					)
				),
				el( ServerSideRender, {
					block: 'sbe/repository-link',
					attributes: { text: attributes.text },
					urlQueryArgs: preview || ( context && context.postId ? { post_id: context.postId } : {} ),
					EmptyResponsePlaceholder: placeholder(
						attributes.text || 'Go to resource',
						'A “Go to resource →” button linking to the resource’s repository appears here. Hidden when no link is set.'
					),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
