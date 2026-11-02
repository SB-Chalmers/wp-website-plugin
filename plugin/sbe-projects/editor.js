/**
 * Editor sidebar panels (plain JS, no build step):
 * - Project: "Second title" and "Read more" links.
 * - Post / Project / Resource: publications (typed in, or pasted as BibTeX; assets/bibtex.js).
 * - Post / Project / Resource: tag people (add via search box, reorder by drag or ↑ ↓).
 * - Resource: gallery icon and repository link.
 * - Post / Project / Resource: "SBE sections" panel listing the SBE section patterns
 *   (includes/sections.php) with one button each, so they are easy to find and add.
 *   On projects the "Read more links" button opens the project's Read more links panel.
 *
 * Simple editor (since 1.23.0, assets/editor-simple.js): these panels then appear in the
 * "Add content" sidebar instead of WordPress's Settings sidebar. `Panel` below renders a
 * plain PanelBody there (InSidebar context) and nothing in the Settings sidebar.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { useMemo, useState } = wp.element;
	const { registerPlugin } = wp.plugins;
	const { TextControl, TextareaControl, ComboboxControl, Button } = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const DocPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

	/* True while the simple editor is on (window.sbeSimple from includes/editor-simple.php;
	 * administrators can switch to the full sidebar, stored in the 'sbe' preferences). */
	const InSidebar = wp.element.createContext( false );
	function isSimple( select ) {
		const s = window.sbeSimple;
		if ( ! s || ! s.enabled ) {
			return false;
		}
		return ! ( s.canSwitch && select( 'core/preferences' ).get( 'sbe', 'fullEditor' ) );
	}
	function Panel( props ) {
		const inSidebar = wp.element.useContext( InSidebar );
		const simple = useSelect( isSimple, [] );
		if ( inSidebar ) {
			return el( wp.components.PanelBody, { title: props.title, initialOpen: props.initialOpen, className: props.className }, props.children );
		}
		return simple ? null : el( DocPanel, props );
	}

	const common = { __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };

	function usePostMeta() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		const update = ( key, value ) => setMeta( { ...meta, [ key ]: value } );
		return { postType, meta, update };
	}

	/* Project: second title */
	function SubtitlePanel() {
		const { postType, meta, update } = usePostMeta();
		if ( postType !== 'project' || ! meta ) {
			return null;
		}
		return el(
			Panel,
			{ name: 'sbe-project-subtitle', title: 'Second title', initialOpen: true },
			el( TextControl, {
				...common,
				label: 'Second title (optional)',
				help: 'Shown under the project title. Leave empty to hide it.',
				value: meta.sbe_subtitle || '',
				onChange: ( v ) => update( 'sbe_subtitle', v ),
			} )
		);
	}

	/* Project: read more links */
	function LinksPanel() {
		const { postType, meta, update } = usePostMeta();
		if ( postType !== 'project' || ! meta ) {
			return null;
		}
		const links = Array.isArray( meta.sbe_links ) ? meta.sbe_links : [];
		const setLinks = ( next ) => update( 'sbe_links', next );
		const setRow = ( i, key, value ) =>
			setLinks( links.map( ( row, j ) => ( j === i ? { ...row, [ key ]: value } : row ) ) );
		const move = ( i, d ) => {
			const next = links.slice();
			const [ row ] = next.splice( i, 1 );
			next.splice( i + d, 0, row );
			setLinks( next );
		};

		return el(
			Panel,
			{ name: 'sbe-project-links', title: 'Read more links', initialOpen: true, className: 'sbe-project-links-panel' },
			links.length === 0 &&
				el( 'p', { style: { color: '#757575', marginTop: 0 } }, 'No links yet. The section is hidden until you add one.' ),
			links.map( ( row, i ) =>
				el(
					'div',
					{ key: i, style: { borderBottom: '1px solid #e0e0e0', paddingBottom: 12, marginBottom: 12 } },
					el( TextControl, {
						...common,
						label: 'Link text',
						placeholder: 'e.g. Project page at Chalmers',
						value: row.label || '',
						onChange: ( v ) => setRow( i, 'label', v ),
					} ),
					el( 'div', { style: { height: 8 } } ),
					el( TextControl, {
						...common,
						type: 'url',
						label: 'Address (URL)',
						placeholder: 'https://',
						value: row.url || '',
						onChange: ( v ) => setRow( i, 'url', v ),
					} ),
					el(
						'div',
						{ style: { display: 'flex', gap: 4, marginTop: 8 } },
						el( Button, { variant: 'tertiary', size: 'small', disabled: i === 0, onClick: () => move( i, -1 ) }, 'Up' ),
						el( Button, { variant: 'tertiary', size: 'small', disabled: i === links.length - 1, onClick: () => move( i, 1 ) }, 'Down' ),
						el( Button, { variant: 'tertiary', size: 'small', isDestructive: true, onClick: () => setLinks( links.filter( ( _, j ) => j !== i ) ) }, 'Remove' )
					)
				)
			),
			el( Button, { variant: 'secondary', onClick: () => setLinks( [ ...links, { label: '', url: '' } ] ) }, 'Add link' )
		);
	}

	/* Post / Project / Resource: publications.
	 * Typed in field by field, or added by pasting BibTeX (assets/bibtex.js), which fills
	 * in the same fields. Shown by the sbe/publications block; hidden when the list is empty. */
	function PublicationsPanel() {
		const { postType, meta, update } = usePostMeta();
		const [ open, setOpen ] = useState( null );
		const [ bib, setBib ] = useState( '' );
		const [ notice, setNotice ] = useState( null );
		if ( [ 'post', 'project', 'resource' ].indexOf( postType ) === -1 || ! meta ) {
			return null;
		}
		const pubs = Array.isArray( meta.sbe_publications ) ? meta.sbe_publications : [];
		const setPubs = ( next ) => update( 'sbe_publications', next );
		const setField = ( i, key, value ) =>
			setPubs( pubs.map( ( row, j ) => ( j === i ? { ...row, [ key ]: value } : row ) ) );
		const move = ( i, d ) => {
			const next = pubs.slice();
			const [ row ] = next.splice( i, 1 );
			next.splice( i + d, 0, row );
			setPubs( next );
			if ( open === i ) {
				setOpen( i + d );
			}
		};
		const remove = ( i ) => {
			setPubs( pubs.filter( ( _, j ) => j !== i ) );
			setOpen( null );
		};
		const empty = { title: '', url: '', authors: '', year: '', venue: '' };

		const addBibtex = () => {
			const found = window.sbeBibtex ? window.sbeBibtex.parse( bib ) : [];
			if ( ! found.length ) {
				setNotice( { error: true, text: 'No publication found. The BibTeX should start with something like @article{ and include a title.' } );
				return;
			}
			const have = pubs.map( ( p ) => ( p.title || '' ).toLowerCase().trim() );
			const fresh = found.filter( ( p ) => have.indexOf( p.title.toLowerCase().trim() ) === -1 );
			setPubs( [ ...pubs, ...fresh ] );
			setBib( '' );
			setOpen( null );
			const skipped = found.length - fresh.length;
			let text = fresh.length === 1 ? 'Added 1 publication.' : 'Added ' + fresh.length + ' publications.';
			if ( skipped ) {
				text += ' ' + skipped + ' already in the list ' + ( skipped === 1 ? 'was' : 'were' ) + ' skipped.';
			}
			if ( fresh.length ) {
				text += ' Check ' + ( fresh.length === 1 ? 'it' : 'them' ) + ' in the list below (Edit to change).';
			}
			setNotice( { error: false, text } );
		};

		const small = { color: '#757575', fontSize: 12 };
		const summary = ( row ) => {
			const line = [ row.authors, row.year ? '(' + row.year + ')' : '' ].filter( Boolean ).join( ' ' );
			return el(
				'div',
				{ style: { minWidth: 0, overflowWrap: 'anywhere' } },
				el( 'div', { style: { fontWeight: 600 } }, row.title || el( 'em', { style: { fontWeight: 400, color: '#cc1818' } }, 'No title yet (not shown until it has one)' ) ),
				line && el( 'div', { style: small }, line ),
				row.venue && el( 'div', { style: { ...small, fontStyle: 'italic' } }, row.venue )
			);
		};
		const fields = ( row, i ) =>
			el(
				'div',
				{ style: { display: 'grid', gap: 8, marginTop: 8 } },
				el( TextControl, { ...common, label: 'Title', value: row.title || '', onChange: ( v ) => setField( i, 'title', v ) } ),
				el( TextControl, { ...common, type: 'url', label: 'Link (URL, optional)', placeholder: 'https://research.chalmers.se/… or https://doi.org/…', value: row.url || '', onChange: ( v ) => setField( i, 'url', v ) } ),
				el( TextControl, { ...common, label: 'Authors', placeholder: 'e.g. Anna Andersson, Erik Berg', value: row.authors || '', onChange: ( v ) => setField( i, 'authors', v ) } ),
				el( TextControl, { ...common, label: 'Year', value: row.year || '', onChange: ( v ) => setField( i, 'year', v ) } ),
				el( TextControl, { ...common, label: 'Journal or conference', value: row.venue || '', onChange: ( v ) => setField( i, 'venue', v ) } )
			);

		return el(
			Panel,
			{ name: 'sbe-publications', title: 'Publications', initialOpen: pubs.length > 0, className: 'sbe-publications-panel' },
			el(
				'p',
				{ style: { ...small, marginTop: 0 } },
				'Papers, reports or theses from this work, listed under “Publications” on the page. Paste BibTeX or add them one by one; they appear in the list below. Leave empty to hide the section.'
			),
			el(
				'div',
				{ style: { marginBottom: 12 } },
				el( TextareaControl, {
					__nextHasNoMarginBottom: true,
					label: 'Paste BibTeX',
					help: 'From research.chalmers.se (Export → BibTeX), Google Scholar, Zotero etc. Several entries at once are fine.',
					placeholder: '@article{key,\n  title = {…},\n  author = {…},\n  …\n}',
					rows: 5,
					value: bib,
					onChange: ( v ) => {
						setBib( v );
						setNotice( null );
					},
					style: { fontFamily: 'monospace', fontSize: 12 },
				} ),
				el( Button, { variant: 'secondary', disabled: ! bib.trim(), onClick: addBibtex, style: { marginTop: 8 } }, 'Add from BibTeX' ),
				notice && el( 'p', { role: 'status', style: { margin: '8px 0 0', fontSize: 12, color: notice.error ? '#cc1818' : '#007017' } }, notice.text )
			),
			el(
				Button,
				{
					variant: 'secondary',
					style: { marginBottom: 16 },
					onClick: () => {
						setPubs( [ ...pubs, { ...empty } ] );
						setOpen( pubs.length );
						setNotice( null );
					},
				},
				'Add publication'
			),
			pubs.map( ( row, i ) =>
				el(
					'div',
					{ key: i, style: { borderBottom: '1px solid #e0e0e0', paddingBottom: 12, marginBottom: 12, ...( i === 0 ? { borderTop: '1px solid #e0e0e0', paddingTop: 12 } : {} ) } },
					summary( row ),
					open === i && fields( row, i ),
					el(
						'div',
						{ style: { display: 'flex', flexWrap: 'wrap', gap: 4, marginTop: 8 } },
						el( Button, { variant: 'tertiary', size: 'small', onClick: () => setOpen( open === i ? null : i ) }, open === i ? 'Done' : 'Edit' ),
						el( Button, { variant: 'tertiary', size: 'small', disabled: i === 0, onClick: () => move( i, -1 ) }, 'Up' ),
						el( Button, { variant: 'tertiary', size: 'small', disabled: i === pubs.length - 1, onClick: () => move( i, 1 ) }, 'Down' ),
						el( Button, { variant: 'tertiary', size: 'small', isDestructive: true, onClick: () => remove( i ) }, 'Remove' )
					)
				)
			)
		);
	}

	/* Post / Project / Resource: tag people.
	 * "Add a person" search box, then a list of the tagged people in display order.
	 * Reorder by dragging a row (handle ⠿) or with the ↑ ↓ buttons; × removes. */
	function PeoplePanel() {
		const { postType, meta, update } = usePostMeta();
		const [ drag, setDrag ] = useState( null );
		const [ over, setOver ] = useState( null );
		const people = useSelect(
			( select ) =>
				select( 'core' ).getEntityRecords( 'postType', 'person', {
					per_page: -1,
					status: 'publish',
					orderby: 'title',
					order: 'asc',
					_fields: 'id,title',
				} ),
			[]
		);

		const byId = useMemo( () => {
			const idMap = {};
			( people || [] ).forEach( ( p ) => {
				idMap[ p.id ] = ( p.title && ( p.title.raw || p.title.rendered ) ) || '#' + p.id;
			} );
			return idMap;
		}, [ people ] );

		if ( [ 'post', 'project', 'resource' ].indexOf( postType ) === -1 || ! meta ) {
			return null;
		}

		const selected = ( meta.sbe_people || [] ).filter( ( id ) => byId[ id ] );
		const setPeople = ( ids ) => update( 'sbe_people', ids );
		const move = ( from, to ) => {
			if ( from === null || to < 0 || to >= selected.length || from === to ) {
				return;
			}
			const next = selected.slice();
			const [ id ] = next.splice( from, 1 );
			next.splice( to, 0, id );
			setPeople( next );
		};
		const endDrag = () => {
			setDrag( null );
			setOver( null );
		};
		const options = ( people || [] )
			.filter( ( p ) => selected.indexOf( p.id ) === -1 )
			.map( ( p ) => ( { value: String( p.id ), label: byId[ p.id ] } ) );

		const iconBtn = ( label, text, onClick, disabled, extra ) =>
			el( Button, { size: 'small', label, showTooltip: true, disabled, onClick, style: { minWidth: 24, padding: '0 4px' }, ...extra }, text );

		const row = ( id, i ) => {
			let line = 'none';
			if ( drag !== null && over === i && drag !== i ) {
				line = '2px solid var(--wp-admin-theme-color, #3858e9)';
			}
			return el(
				'li',
				{
					key: id,
					draggable: true,
					onDragStart: ( e ) => {
						setDrag( i );
						e.dataTransfer.effectAllowed = 'move';
						e.dataTransfer.setData( 'text/plain', String( id ) );
					},
					onDragOver: ( e ) => {
						e.preventDefault();
						e.dataTransfer.dropEffect = 'move';
						if ( over !== i ) {
							setOver( i );
						}
					},
					onDrop: ( e ) => {
						e.preventDefault();
						move( drag, i );
						endDrag();
					},
					onDragEnd: endDrag,
					style: {
						display: 'flex',
						alignItems: 'center',
						gap: 6,
						margin: 0,
						padding: '4px 4px 4px 6px',
						background: drag === i ? '#f0f0f0' : '#fff',
						opacity: drag === i ? 0.5 : 1,
						border: '1px solid #e0e0e0',
						borderRadius: 2,
						marginTop: i === 0 ? 0 : -1,
						borderTop: drag !== null && drag > i && over === i ? line : '1px solid #e0e0e0',
						borderBottom: drag !== null && drag < i && over === i ? line : '1px solid #e0e0e0',
						cursor: 'grab',
					},
				},
				el( 'span', { 'aria-hidden': true, style: { color: '#949494', fontSize: 14, lineHeight: 1 } }, '⠿' ),
				el( 'span', { style: { flex: 1, minWidth: 0, overflowWrap: 'anywhere' } }, byId[ id ] ),
				iconBtn( 'Move up', '↑', () => move( i, i - 1 ), i === 0 ),
				iconBtn( 'Move down', '↓', () => move( i, i + 1 ), i === selected.length - 1 ),
				iconBtn( 'Remove ' + byId[ id ], '×', () => setPeople( selected.filter( ( x ) => x !== id ) ), false, { isDestructive: true } )
			);
		};

		return el(
			Panel,
			{ name: 'sbe-people', title: 'People', initialOpen: true },
			people === null
				? el( 'p', null, 'Loading people…' )
				: el(
						wp.element.Fragment,
						null,
						el( ComboboxControl, {
							...common,
							label: 'Add a person',
							placeholder: 'Type a name',
							value: null,
							allowReset: false,
							options,
							onChange: ( v ) => {
								if ( v ) {
									setPeople( [ ...selected, parseInt( v, 10 ) ] );
								}
							},
						} ),
						selected.length === 0
							? el( 'p', { style: { color: '#757575', margin: '12px 0 0' } }, 'Nobody tagged yet. The People involved section is hidden.' )
							: el(
									'ol',
									{ style: { listStyle: 'none', margin: '12px 0 0', padding: 0 }, onDragLeave: ( e ) => {
										if ( ! e.currentTarget.contains( e.relatedTarget ) ) {
											setOver( null );
										}
									} },
									selected.map( row )
							  )
				  ),
			el(
				'p',
				{ style: { marginTop: 8, color: '#757575', fontSize: 12 } },
				'Shown on the page in this order. Drag a name (or use ↑ ↓) to change it. Add or edit people under People in the admin menu.'
			)
		);
	}

	/* Resource: gallery icon and repository link */
	function ResourcePanel() {
		const { postType, meta, update } = usePostMeta();
		const iconId = meta && meta.sbe_icon ? meta.sbe_icon : 0;
		const icon = useSelect( ( select ) => ( iconId ? select( 'core' ).getMedia( iconId ) : null ), [ iconId ] );
		if ( postType !== 'resource' || ! meta ) {
			return null;
		}
		const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
		const iconUrl =
			icon && ( ( icon.media_details && icon.media_details.sizes && icon.media_details.sizes.medium && icon.media_details.sizes.medium.source_url ) || icon.source_url );

		return el(
			wp.element.Fragment,
			null,
			el(
				Panel,
				{ name: 'sbe-resource-icon', title: 'Gallery icon', initialOpen: true },
				el(
					'p',
					{ style: { marginTop: 0, color: '#757575', fontSize: 12 } },
					'Shown on the Resources page instead of the feature image. Use a square image, ideally a simple logo or symbol on a transparent background (PNG).'
				),
				iconUrl &&
					el( 'img', {
						src: iconUrl,
						alt: '',
						style: { display: 'block', width: 96, height: 96, objectFit: 'contain', padding: 12, borderRadius: 12, background: '#f0f0f0', marginBottom: 8 },
					} ),
				el(
					MediaUploadCheck,
					null,
					el( MediaUpload, {
						allowedTypes: [ 'image' ],
						value: iconId,
						onSelect: ( media ) => update( 'sbe_icon', media.id ),
						render: ( { open } ) =>
							el(
								'div',
								{ style: { display: 'flex', gap: 8 } },
								el( Button, { variant: 'secondary', onClick: open }, iconId ? 'Replace icon' : 'Choose icon' ),
								iconId ? el( Button, { variant: 'tertiary', isDestructive: true, onClick: () => update( 'sbe_icon', 0 ) }, 'Remove' ) : null
							),
					} )
				),
				! iconId &&
					el( 'p', { style: { marginBottom: 0, color: '#757575', fontSize: 12 } }, 'No icon yet: the gallery shows the resource’s initials.' )
			),
			el(
				Panel,
				{ name: 'sbe-resource-repo', title: 'Repository', initialOpen: true },
				el( TextControl, {
					...common,
					type: 'url',
					label: 'Repository link (URL)',
					placeholder: 'https://github.com/…',
					help: 'Shown as a “Go to resource →” button. Leave empty to hide it.',
					value: meta.sbe_repo_url || '',
					onChange: ( v ) => update( 'sbe_repo_url', v ),
				} )
			)
		);
	}


	/* Post / Project / Resource: SBE sections, one button per pattern. */
	function SectionsPanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const patterns = useSelect( ( select ) => {
			const core = select( 'core' );
			let list = core.getBlockPatterns ? core.getBlockPatterns() : null;
			if ( ! list || ! list.length ) {
				const settings = select( 'core/block-editor' ).getSettings();
				list = settings.__experimentalAdditionalBlockPatterns || settings.__experimentalBlockPatterns || [];
			}
			return list;
		}, [] );
		const { insertBlocks } = wp.data.useDispatch( 'core/block-editor' );

		if ( [ 'post', 'project', 'resource' ].indexOf( postType ) === -1 ) {
			return null;
		}

		// Every SBE pattern allowed for this post type. On projects the Read more links pattern
		// is not allowed (they have their own field); it keeps its place in the list as a
		// button that opens the project's Read more links panel instead.
		const ours = [];
		( patterns || [] ).forEach( ( p ) => {
			if ( ( p.categories || [] ).indexOf( 'sbe-sections' ) === -1 ) {
				return;
			}
			if ( p.name === 'sbe/publications' ) {
				// Publications have their own panel (since 1.26.0): the button opens it.
				ours.push( {
					name: p.name,
					title: p.title,
					description: 'Publications have their own panel: click to open it, then paste BibTeX or add them one by one.',
					panelClass: 'sbe-publications-panel',
				} );
				return;
			}
			const types = p.post_types || p.postTypes;
			if ( ! types || types.indexOf( postType ) !== -1 ) {
				ours.push( p );
			} else if ( p.name === 'sbe/read-more' && postType === 'project' ) {
				ours.push( {
					name: p.name,
					title: p.title,
					description: 'Projects have their own Read more links panel: click to open it.',
					panelClass: 'sbe-project-links-panel',
				} );
			}
		} );

		// Open a sidebar panel (if closed) and scroll to it. Works in both sidebars.
		const openPanel = ( p ) => {
			const node = document.querySelector( '.' + p.panelClass );
			if ( ! node ) {
				return;
			}
			const toggle = node.querySelector( '.components-panel__body-toggle' );
			if ( toggle && ! node.classList.contains( 'is-opened' ) ) {
				toggle.click();
			}
			setTimeout( () => node.scrollIntoView( { behavior: 'smooth', block: 'start' } ), 50 );
		};

		// Insert after the selected top-level block (never inside a list, group etc.),
		// or at the end of the post when nothing is selected.
		const insert = ( pattern ) => {
			const be = wp.data.select( 'core/block-editor' );
			const blocks = wp.blocks.parse( pattern.content );
			const selected = be.getSelectedBlockClientId();
			if ( ! selected ) {
				insertBlocks( blocks, undefined, undefined, true );
				return;
			}
			// Ancestors from the outside in, then the block itself. When the template is shown,
			// the post's own blocks sit inside a Post Content block: stay inside it.
			const chain = be.getBlockParents( selected ).concat( [ selected ] );
			let start = 0;
			chain.forEach( ( id, i ) => {
				if ( be.getBlockName( id ) === 'core/post-content' ) {
					start = i + 1;
				}
			} );
			const target = chain[ Math.min( start, chain.length - 1 ) ];
			const root = be.getBlockRootClientId( target ) || undefined;
			insertBlocks( blocks, be.getBlockIndex( target ) + 1, root, true );
		};

		return el(
			Panel,
			{ name: 'sbe-sections', title: 'SBE sections', initialOpen: true },
			el(
				'p',
				{ style: { marginTop: 0, color: '#757575', fontSize: 12 } },
				'Ready-made sections. Click one to add it below the block you have selected (or at the end). Then replace the example text.'
			),
			ours.length === 0
				? el( 'p', null, 'Loading…' )
				: ours.map( ( p ) =>
						el(
							'div',
							{ key: p.name, style: { marginBottom: 10 } },
							el( Button, { variant: 'secondary', onClick: () => ( p.panelClass ? openPanel( p ) : insert( p ) ), style: { width: '100%', justifyContent: 'flex-start', height: 'auto', minHeight: 32, whiteSpace: 'normal', textAlign: 'left' } }, ( p.panelClass ? '→ ' : '+ ' ) + p.title ),
							p.description && el( 'div', { style: { color: '#757575', fontSize: 12, marginTop: 4 } }, p.description )
						)
				  )
		);
	}

	registerPlugin( 'sbe-project-subtitle', { render: SubtitlePanel } );
	registerPlugin( 'sbe-project-links', { render: LinksPanel } );
	registerPlugin( 'sbe-publications', { render: PublicationsPanel } );
	registerPlugin( 'sbe-people-tags', { render: PeoplePanel } );
	registerPlugin( 'sbe-resource-fields', { render: ResourcePanel } );
	registerPlugin( 'sbe-sections-panel', { render: SectionsPanel } );

	// Used by assets/editor-simple.js to show the same panels in the Add content sidebar.
	window.sbeEditor = { InSidebar, isSimple, SubtitlePanel, LinksPanel, PublicationsPanel, PeoplePanel, ResourcePanel, SectionsPanel };

	/*
	 * Simpler inserter (since 1.22.0, see includes/editor-blocks.php): Group only as the
	 * plain Group (no Row / Stack / Grid, the pages are one column), Embed only as
	 * YouTube and Vimeo, Heading only as H2 and H3 (sections and sub-sections; the
	 * plain Heading block and its level menu still offer the rest). Existing blocks keep
	 * working; pasting another link into an Embed block still embeds it.
	 */
	wp.domReady( function () {
		const keep = { 'core/group': [ 'group' ], 'core/embed': [ 'youtube', 'vimeo' ], 'core/heading': [ 'h2', 'h3' ] };
		Object.keys( keep ).forEach( function ( block ) {
			( wp.blocks.getBlockVariations( block ) || [] ).forEach( function ( v ) {
				if ( keep[ block ].indexOf( v.name ) === -1 ) {
					wp.blocks.unregisterBlockVariation( block, v.name );
				}
			} );
		} );
	} );
} )( window.wp );
