/**
 * Hints on the page itself in the post / project / resource editor (since 1.25.0,
 * includes/editor-simple.php).
 *
 * With "Show template" on, WordPress's own empty-field texts are "(no title)" for the
 * title, "Add Second title" for the project's second title (which can't be typed on the
 * page, only in the sidebar) and "Type / to choose a block" for the text. New editors
 * didn't know what to do with them, so they now say what to do:
 *
 * - Title: "Write the title here" (own copy of core's editable title, same markup).
 *   Enter at the end of the title moves to the start of the text.
 * - Second title (heading bound to sbe_subtitle), while empty: a grey line pointing to
 *   the Add content sidebar (the block itself can't be clicked). Hidden on the site as before.
 * - Empty paragraphs: SBE_TEXT_HINT (the empty-post text is the same, see
 *   sbe_simple_body_placeholder() in includes/editor-simple.php).
 *
 * Only for the post being edited; Site Editor and Query Loops are untouched.
 * Plain JS, no build step.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { useSelect, useDispatch } = wp.data;
	const { useBlockProps, PlainText } = wp.blockEditor;
	const { createHigherOrderComponent } = wp.compose;
	const { createBlock, getDefaultBlockName } = wp.blocks;
	const TYPES = [ 'post', 'project', 'resource' ];
	const HINTS = window.sbeHints || {};
	const TITLE_HINT = HINTS.title || 'Write the title here';
	const SUBTITLE_HINT = HINTS.subtitle || 'Second title (optional): fill it in under “Second title” in Add content on the right →';
	const TEXT_HINT = HINTS.text || 'Write your text here. Enter starts a new paragraph; the + button adds a picture, heading or list.';

	/* Type of the post being edited when the block belongs to it (not inside a Query Loop), else ''. */
	function useOwnPostType( context ) {
		return useSelect(
			( select ) => {
				const ed = select( 'core/editor' );
				if ( ! ed || ! ed.getCurrentPostType ) {
					return '';
				}
				const type = ed.getCurrentPostType();
				if ( TYPES.indexOf( type ) === -1 ) {
					return '';
				}
				if ( context && ( Number.isFinite( context.queryId ) || ( context.postId && context.postId !== ed.getCurrentPostId() ) ) ) {
					return '';
				}
				return type;
			},
			[ context && context.postId, context && context.queryId ]
		);
	}

	/* ---------- Title ---------- */

	/*
	 * Enter at the end of the title: go to the start of the text. (Core inserts a block
	 * after the title, which the locked template doesn't allow, so nothing happened.)
	 */
	function goToText() {
		const be = wp.data.select( 'core/block-editor' );
		const act = wp.data.dispatch( 'core/block-editor' );
		const content = be.getBlocksByName ? be.getBlocksByName( 'core/post-content' )[ 0 ] : null;
		if ( ! content ) {
			return;
		}
		const first = be.getBlockOrder( content )[ 0 ];
		const block = first && be.getBlock( first );
		if ( block && block.name === getDefaultBlockName() && ! String( block.attributes.content || '' ).length ) {
			act.selectBlock( first );
		} else {
			act.insertBlock( createBlock( getDefaultBlockName() ), 0, content );
		}
	}

	function Title( props ) {
		const { attributes } = props;
		const level = typeof attributes.level === 'number' ? attributes.level : 2;
		const TagName = level === 0 ? 'p' : 'h' + level;
		const title = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '', [] );
		const { editPost } = useDispatch( 'core/editor' );
		const blockProps = useBlockProps();
		return el( PlainText, {
			tagName: TagName,
			placeholder: TITLE_HINT,
			value: title,
			onChange: ( v ) => editPost( { title: v } ),
			__experimentalVersion: 2,
			__unstableOnSplitAtEnd: goToText,
			...blockProps,
		} );
	}

	/* ---------- Second title (projects) ---------- */

	function isSubtitle( attributes ) {
		const b = attributes && attributes.metadata && attributes.metadata.bindings && attributes.metadata.bindings.content;
		return !! ( b && b.source === 'core/post-meta' && b.args && b.args.key === 'sbe_subtitle' );
	}

	function SubtitleHint( props ) {
		const level = props.attributes.level || 2;
		const blockProps = useBlockProps( { className: 'sbe-editor-hint' } );
		return el( 'h' + level, blockProps, SUBTITLE_HINT );
	}

	function Subtitle( props ) {
		const { BlockEdit, ...rest } = props;
		const empty = useSelect( ( select ) => {
			const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
			return ! String( meta.sbe_subtitle || '' ).trim();
		}, [] );
		return empty ? el( SubtitleHint, rest ) : el( BlockEdit, rest );
	}

	/* ---------- Switch ---------- */

	function Switch( props ) {
		const { BlockEdit, ...rest } = props;
		const type = useOwnPostType( rest.context );
		if ( type ) {
			if ( rest.name === 'core/post-title' && ! rest.attributes.isLink ) {
				return el( Title, rest );
			}
			if ( rest.name === 'core/heading' && type === 'project' && isSubtitle( rest.attributes ) ) {
				return el( Subtitle, props );
			}
			if ( rest.name === 'core/paragraph' && ! rest.attributes.placeholder ) {
				return el( BlockEdit, Object.assign( {}, rest, { attributes: Object.assign( {}, rest.attributes, { placeholder: TEXT_HINT } ) } ) );
			}
		}
		return el( BlockEdit, rest );
	}

	const NAMES = [ 'core/post-title', 'core/heading', 'core/paragraph' ];
	wp.hooks.addFilter(
		'editor.BlockEdit',
		'sbe/editor-hints',
		createHigherOrderComponent(
			( BlockEdit ) => ( props ) =>
				NAMES.indexOf( props.name ) !== -1 ? el( Switch, Object.assign( {}, props, { BlockEdit } ) ) : el( BlockEdit, props ),
			'sbeEditorHints'
		)
	);
} )( window.wp );
