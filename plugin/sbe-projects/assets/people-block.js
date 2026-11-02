/**
 * Editor side of the "People" block (rendered on the server).
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl } = wp.components;
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

	registerBlockType( 'sbe/people', {
		usesContext: [ 'postId', 'postType' ],
		edit: function ( props ) {
			const { attributes, setAttributes, context } = props;
			const preview = usePreviewArgs( 'sbe_people', 'people', context && context.postId );
			const blockProps = useBlockProps();
			const emptyText =
				attributes.show === 'all'
					? 'No people added yet. Add them under People in the admin menu.'
					: attributes.show === 'contact'
					? 'No contact persons yet. Tick "Contact person" on a person under People in the admin menu.'
					: 'People tagged on the post or project will appear here.';

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Settings' },
						el( SelectControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: 'Show',
							value: attributes.show,
							options: [
								{ label: 'People tagged on this post / project (photo, name, role)', value: 'tagged' },
								{ label: 'All staff (photo, name, role, research interests)', value: 'all' },
								{ label: 'Contact persons (small photo, name, role)', value: 'contact' },
							],
							onChange: ( v ) => setAttributes( { show: v } ),
						} ),
						el( 'div', { style: { height: 16 } } ),
						el( TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: 'Heading (optional)',
							value: attributes.heading,
							onChange: ( v ) => setAttributes( { heading: v } ),
						} )
					)
				),
				el( ServerSideRender, {
					block: 'sbe/people',
					attributes: { show: attributes.show, heading: attributes.heading },
					urlQueryArgs: attributes.show !== 'all' && attributes.show !== 'contact' && preview ? preview : {},
					EmptyResponsePlaceholder: () =>
						el(
							'div',
							{ style: { padding: 24, border: '1px dashed currentColor', opacity: 0.7 } },
							el( 'strong', null, attributes.heading || 'People' ),
							el( 'p', { style: { margin: '8px 0 0' } }, emptyText )
						),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
