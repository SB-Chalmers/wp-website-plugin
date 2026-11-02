/**
 * Live preview of tags, status and Related content in the post / project / resource
 * editor (since 1.24.0, includes/editor-simple.php).
 *
 * WordPress's own Post Terms block (Tags: …, Status: …) only shows the terms that are
 * already saved, and the Related content gallery is chosen on the server from saved
 * tags. With "Show template" on, these blocks now follow the Add content sidebar:
 *
 * - Post Terms (core/post-terms) of the post being edited is drawn from the unsaved
 *   tags / status, with the same markup as on the site (prefix, links, separator).
 *   No terms → nothing, like on the site.
 * - The Related content Query Loop (class `sbe-related`) shows the cards the site would
 *   show for the current, unsaved tags (REST route sbe/v1/related-preview renders the
 *   template's own Query Loop on the server).
 *
 * Everywhere else (Site Editor, cards inside Query Loops) the blocks work as before.
 * Plain JS, no build step.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { useEffect, useRef, useState } = wp.element;
	const { useSelect } = wp.data;
	const { useBlockProps } = wp.blockEditor;
	const { createHigherOrderComponent } = wp.compose;
	const apiFetch = wp.apiFetch;
	const TYPES = [ 'post', 'project', 'resource' ];

	/* { id, type } of the post being edited when the block shows that post, else null. */
	function useCurrentPost( contextPostId ) {
		const key = useSelect(
			( select ) => {
				const ed = select( 'core/editor' );
				if ( ! ed || ! ed.getCurrentPostType ) {
					return '';
				}
				const type = ed.getCurrentPostType();
				const id = ed.getCurrentPostId();
				if ( TYPES.indexOf( type ) === -1 || ( contextPostId && contextPostId !== id ) ) {
					return '';
				}
				return type + ':' + id;
			},
			[ contextPostId ]
		);
		if ( ! key ) {
			return null;
		}
		const parts = key.split( ':' );
		return { type: parts[ 0 ], id: parseInt( parts[ 1 ], 10 ) };
	}

	/* Unsaved term IDs of a taxonomy on the post being edited (null if it has none). */
	function useEditedTermIds( slug ) {
		return useSelect(
			( select ) => {
				const tax = select( 'core' ).getTaxonomy( slug );
				if ( ! tax ) {
					return null;
				}
				const ids = select( 'core/editor' ).getEditedPostAttribute( tax.rest_base || slug );
				return Array.isArray( ids ) ? ids.slice().sort( ( a, b ) => a - b ).join( ',' ) : null;
			},
			[ slug ]
		);
	}

	const stop = ( e ) => {
		if ( e.target && e.target.closest && e.target.closest( 'a' ) ) {
			e.preventDefault();
		}
	};

	/* ---------- Tags: … / Status: … ---------- */

	function LiveTerms( props ) {
		const { attributes } = props;
		const slug = attributes.term;
		const idList = useEditedTermIds( slug );
		const last = useRef( null );
		const terms = useSelect(
			( select ) => {
				if ( idList === null ) {
					return null;
				}
				if ( idList === '' ) {
					return [];
				}
				const ids = idList.split( ',' ).map( Number );
				return select( 'core' ).getEntityRecords( 'taxonomy', slug, { include: ids, per_page: -1, context: 'view' } );
			},
			[ slug, idList ]
		);
		if ( terms ) {
			last.current = terms
				.slice()
				.sort( ( a, b ) => String( a.name ).localeCompare( String( b.name ) ) ); // Site order: by name.
		}
		const shown = last.current || [];
		const classes = [ 'taxonomy-' + slug ];
		if ( attributes.textAlign ) {
			classes.push( 'has-text-align-' + attributes.textAlign );
		}
		const blockProps = useBlockProps( { className: classes.join( ' ' ), onClick: stop } );
		if ( ! shown.length ) {
			return el( 'div', Object.assign( {}, blockProps, { hidden: true } ) ); // Nothing on the site either.
		}
		const sep = typeof attributes.separator === 'string' ? attributes.separator : ', ';
		const children = [];
		if ( attributes.prefix ) {
			children.push( el( 'span', { key: 'p', className: 'wp-block-post-terms__prefix' }, attributes.prefix ) );
		}
		shown.forEach( ( term, i ) => {
			if ( i ) {
				children.push( el( 'span', { key: 's' + i, className: 'wp-block-post-terms__separator' }, sep ) );
			}
			children.push( el( 'a', { key: 't' + term.id, href: term.link, rel: 'tag', dangerouslySetInnerHTML: { __html: term.name } } ) );
		} );
		if ( attributes.suffix ) {
			children.push( el( 'span', { key: 'x', className: 'wp-block-post-terms__suffix' }, attributes.suffix ) );
		}
		return el( 'div', blockProps, children );
	}

	/* ---------- Related content ---------- */

	function LiveRelated( props ) {
		const post = props.sbePost;
		const tagIds = useEditedTermIds( 'post_tag' );
		const [ html, setHtml ] = useState( null );
		useEffect( () => {
			if ( tagIds === null ) {
				return undefined;
			}
			if ( tagIds === '' ) {
				setHtml( '' );
				return undefined;
			}
			let live = true;
			const timer = setTimeout( () => {
				apiFetch( {
					path: '/sbe/v1/related-preview',
					method: 'POST',
					data: { post_id: post.id, tags: tagIds.split( ',' ).map( Number ) },
				} )
					.then( ( res ) => live && setHtml( ( res && res.html ) || '' ) )
					.catch( () => live && setHtml( '' ) );
			}, 400 );
			return () => {
				live = false;
				clearTimeout( timer );
			};
		}, [ post.id, tagIds ] );

		// The canvas CSS shows a note instead when there is nothing related (the site hides the whole section).
		return el( 'div', {
			className: 'sbe-related-live' + ( html ? ' has-items' : '' ),
			onClick: stop,
			dangerouslySetInnerHTML: { __html: html || '' },
		} );
	}

	function hasClass( attributes, cls ) {
		return ( ' ' + ( ( attributes && attributes.className ) || '' ) + ' ' ).indexOf( ' ' + cls + ' ' ) !== -1;
	}

	function Switch( props ) {
		const { BlockEdit, ...rest } = props;
		const post = useCurrentPost( rest.context && rest.context.postId );
		if ( post ) {
			if ( rest.name === 'core/post-terms' && rest.attributes.term ) {
				return el( LiveTerms, rest );
			}
			if ( rest.name === 'core/query' && hasClass( rest.attributes, 'sbe-related' ) ) {
				return el( LiveRelated, Object.assign( {}, rest, { sbePost: post } ) );
			}
		}
		return el( BlockEdit, rest );
	}

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'sbe/live-preview',
		createHigherOrderComponent(
			( BlockEdit ) => ( props ) =>
				props.name === 'core/post-terms' || props.name === 'core/query'
					? el( Switch, Object.assign( {}, props, { BlockEdit } ) )
					: el( BlockEdit, props ),
			'sbeLivePreview'
		)
	);
} )( window.wp );
