/**
 * Small BibTeX reader for the Publications panel (editor.js). No library, no build step.
 *
 * window.sbeBibtex.parse( text ) → array of { title, url, authors, year, venue }, one per
 * entry (@article, @inproceedings, @book …; @string/@comment/@preamble are skipped).
 * - authors: "First Last, First Last" (BibTeX "Last, First and …"; "others" → "et al.")
 * - venue:   journal / booktitle / school / institution / publisher / howpublished
 * - url:     url, else https://doi.org/<doi>
 * - LaTeX accents and commands are turned into plain text ({\"o} → ö, {\aa} → å, -- → –).
 */
( function () {
	// Accent commands → Unicode combining marks (normalised to NFC afterwards).
	const ACCENTS = {
		'"': '̈', "'": '́', '`': '̀', '^': '̂', '~': '̃',
		'=': '̄', '.': '̇', u: '̆', v: '̌', H: '̋',
		c: '̧', k: '̨', r: '̊', d: '̣', b: '̱',
	};
	// Letter commands.
	const LETTERS = {
		aa: 'å', AA: 'Å', o: 'ø', O: 'Ø', ae: 'æ', AE: 'Æ', oe: 'œ', OE: 'Œ',
		ss: 'ß', l: 'ł', L: 'Ł', i: 'ı', j: 'ȷ', dh: 'ð', DH: 'Ð', th: 'þ', TH: 'Þ',
		textendash: '–', textemdash: '—', textquoteright: '’', textquoteleft: '‘',
		textregistered: '®', texttrademark: '™', copyright: '©', textdegree: '°',
		ldots: '…', dots: '…', textellipsis: '…', S: '§', P: '¶', textasciitilde: '~',
		textunderscore: '_', textbackslash: '\\', ',': ' ', ';': ' ', ' ': ' ',
		'&': '&', '%': '%', $: '$', '#': '#', _: '_', '{': '{', '}': '}',
	};

	function delatex( s ) {
		s = String( s || '' );
		// Accents: \"{o}  {\"o}  \"o  \'{\i}  \c{c}  \v s
		s = s.replace( /\\([\"'`^~=.])\s*(?:\{\s*(\\?[A-Za-z])\s*\}|(\\?[A-Za-z]))/g, ( m, acc, a, b ) => {
			let ch = a || b;
			ch = ch === '\\i' ? 'i' : ch === '\\j' ? 'j' : ch.replace( '\\', '' );
			return ch + ACCENTS[ acc ];
		} );
		s = s.replace( /\\([uvHckrdb])(?:\s*\{\s*(\\?[A-Za-z])\s*\}|\s+([A-Za-z]))/g, ( m, acc, a, b ) => {
			let ch = a || b;
			ch = ch === '\\i' ? 'i' : ch === '\\j' ? 'j' : ch.replace( '\\', '' );
			return ch + ACCENTS[ acc ];
		} );
		// Formatting commands: keep their argument (\emph{x}, \textit{x}, \url{x} …).
		for ( let n = 0; n < 5; n++ ) {
			s = s.replace( /\\(?:emph|textit|textbf|textsc|textrm|textsf|texttt|textup|textnormal|mathrm|mathit|mbox|url|href\{[^{}]*\}|text)\s*\{([^{}]*)\}/g, '$1' );
		}
		// Letter commands and escaped characters.
		s = s.replace( /\\([A-Za-z]+)(?![A-Za-z])\s?(?:\{\})?|\\([&%$#_{}, ;])/g, ( m, word, sym ) => {
			const k = word || sym;
			return Object.prototype.hasOwnProperty.call( LETTERS, k ) ? LETTERS[ k ] : word ? '' : sym;
		} );
		s = s
			.replace( /---/g, '—' )
			.replace( /--/g, '–' )
			.replace( /``|''/g, '"' )
			.replace( /~/g, ' ' )
			.replace( /[{}]/g, '' )
			.replace( /\$/g, '' )
			.replace( /\s+/g, ' ' )
			.trim();
		return s.normalize ? s.normalize( 'NFC' ) : s;
	}

	/* Split text at a separator regex, only at brace depth 0. */
	function splitTop( s, re ) {
		const parts = [];
		let depth = 0;
		let last = 0;
		for ( let i = 0; i < s.length; i++ ) {
			const c = s[ i ];
			if ( c === '{' ) {
				depth++;
			} else if ( c === '}' ) {
				depth--;
			} else if ( depth === 0 ) {
				re.lastIndex = i;
				const m = re.exec( s );
				if ( m && m.index === i ) {
					parts.push( s.slice( last, i ) );
					last = i + m[ 0 ].length;
					i = last - 1;
				}
			}
		}
		parts.push( s.slice( last ) );
		return parts;
	}

	/* One BibTeX name → "First Last". */
	function formatName( raw ) {
		const name = raw.trim();
		if ( ! name ) {
			return '';
		}
		if ( /^\{.*\}$/.test( name ) && splitTop( name, /,/y ).length === 1 && name.indexOf( '}' ) === name.length - 1 ) {
			return delatex( name ); // {Organisation name}
		}
		const parts = splitTop( name, /\s*,\s*/y ).map( ( p ) => p.trim() );
		let out;
		if ( parts.length === 1 ) {
			out = parts[ 0 ];
		} else if ( parts.length === 2 ) {
			out = parts[ 1 ] + ' ' + parts[ 0 ]; // Last, First
		} else {
			out = parts[ 2 ] + ' ' + parts[ 0 ] + ', ' + parts[ 1 ]; // Last, Jr, First
		}
		return delatex( out );
	}

	function formatAuthors( raw ) {
		if ( ! raw ) {
			return '';
		}
		const names = splitTop( raw.replace( /\s+/g, ' ' ), /\s+and\s+/iy );
		let etal = false;
		const list = [];
		names.forEach( ( n ) => {
			if ( /^\s*others\s*$/i.test( n ) ) {
				etal = true;
			} else {
				const f = formatName( n );
				if ( f ) {
					list.push( f );
				}
			}
		} );
		return list.join( ', ' ) + ( etal ? ' et al.' : '' );
	}

	/* Read one field value starting at s[i]; returns [ value, nextIndex ]. */
	function readValue( s, i, strings ) {
		let out = '';
		for ( ;; ) {
			while ( /\s/.test( s[ i ] || '' ) ) {
				i++;
			}
			const c = s[ i ];
			if ( c === '{' ) {
				let depth = 0;
				const start = i;
				for ( ; i < s.length; i++ ) {
					if ( s[ i ] === '\\' ) {
						i++;
						continue;
					}
					if ( s[ i ] === '{' ) {
						depth++;
					} else if ( s[ i ] === '}' && --depth === 0 ) {
						break;
					}
				}
				out += s.slice( start + 1, i );
				i++;
			} else if ( c === '"' ) {
				let depth = 0;
				const start = ++i;
				for ( ; i < s.length; i++ ) {
					if ( s[ i ] === '\\' ) {
						i++;
						continue;
					}
					if ( s[ i ] === '{' ) {
						depth++;
					} else if ( s[ i ] === '}' ) {
						depth--;
					} else if ( s[ i ] === '"' && depth <= 0 ) {
						break;
					}
				}
				out += s.slice( start, i );
				i++;
			} else {
				const m = /^[^\s,#})]+/.exec( s.slice( i ) );
				if ( ! m ) {
					break;
				}
				const word = m[ 0 ];
				out += Object.prototype.hasOwnProperty.call( strings, word.toLowerCase() ) ? strings[ word.toLowerCase() ] : word;
				i += word.length;
			}
			while ( /\s/.test( s[ i ] || '' ) ) {
				i++;
			}
			if ( s[ i ] === '#' ) {
				i++;
				continue;
			}
			return [ out, i ];
		}
		return [ out, i ];
	}

	/* All entries in the text: [ { type, key, fields } ]. */
	function entries( text ) {
		const s = String( text || '' );
		const strings = {};
		const found = [];
		const at = /@\s*([A-Za-z]+)\s*([{(])/g;
		let m;
		while ( ( m = at.exec( s ) ) ) {
			const type = m[ 1 ].toLowerCase();
			let i = at.lastIndex;
			const close = m[ 2 ] === '{' ? '}' : ')';
			if ( type === 'comment' || type === 'preamble' ) {
				continue;
			}
			const fields = {};
			let key = '';
			if ( type !== 'string' ) {
				const km = /^\s*([^,\s}]*)\s*,?/.exec( s.slice( i ) );
				key = km ? km[ 1 ] : '';
				i += km ? km[ 0 ].length : 0;
			}
			for ( ;; ) {
				const fm = /^\s*([A-Za-z][\w:.+-]*)\s*=\s*/.exec( s.slice( i ) );
				if ( ! fm ) {
					break;
				}
				i += fm[ 0 ].length;
				const r = readValue( s, i, strings );
				fields[ fm[ 1 ].toLowerCase() ] = r[ 0 ];
				i = r[ 1 ];
				while ( /[\s,]/.test( s[ i ] || '' ) ) {
					i++;
				}
				if ( s[ i ] === close ) {
					break;
				}
			}
			at.lastIndex = i;
			if ( type === 'string' ) {
				Object.keys( fields ).forEach( ( k ) => ( strings[ k ] = fields[ k ] ) );
				continue;
			}
			found.push( { type, key, fields } );
		}
		return found;
	}

	function toPublication( e ) {
		const f = e.fields;
		const title = delatex( f.title || f.booktitle || '' ).replace( /\.$/, '' );
		let year = delatex( f.year || '' );
		if ( ! year && f.date ) {
			year = ( /\d{4}/.exec( f.date ) || [ '' ] )[ 0 ];
		}
		let venue = f.journal || f.journaltitle || ( f.title && f.booktitle ) || '';
		if ( ! venue ) {
			venue = f.school || f.institution || f.organization || f.publisher || f.howpublished || '';
		}
		venue = delatex( venue );
		if ( ! venue && /thesis/.test( e.type ) ) {
			venue = e.type === 'phdthesis' ? 'PhD thesis' : 'Master’s thesis';
		}
		let url = ( f.url || '' ).trim().replace( /^\\url\{|\}$/g, '' );
		const doi = ( f.doi || '' ).trim().replace( /^https?:\/\/(dx\.)?doi\.org\//i, '' ).replace( /^doi:\s*/i, '' );
		if ( ! url && doi ) {
			url = 'https://doi.org/' + doi;
		}
		return {
			title,
			url,
			authors: formatAuthors( f.author || f.editor || '' ),
			year,
			venue,
		};
	}

	window.sbeBibtex = {
		delatex,
		formatAuthors,
		entries,
		parse: ( text ) => entries( text ).map( toPublication ).filter( ( p ) => p.title ),
	};
} )();
