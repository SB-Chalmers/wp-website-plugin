/**
 * Block editor sidebar "How to add content" (? icon in the top bar, also in ⋮ → Plugins).
 * Shows the quick tutorial, rendered on the server from docs/POSTS-AND-PROJECTS-TUTORIAL.md
 * (includes/help.php passes it in window.sbeHelp). Plain JS, no build step.
 */
( function ( wp ) {
	const data = window.sbeHelp;
	if ( ! data || ! data.html ) {
		return;
	}
	const el = wp.element.createElement;
	const { RawHTML } = wp.element;
	const { registerPlugin } = wp.plugins;
	const PluginSidebar = ( wp.editor && wp.editor.PluginSidebar ) || wp.editPost.PluginSidebar;
	const MoreMenuItem = ( wp.editor && wp.editor.PluginSidebarMoreMenuItem ) || wp.editPost.PluginSidebarMoreMenuItem;

	function HelpSidebar() {
		return el(
			wp.element.Fragment,
			null,
			el( MoreMenuItem, { target: 'sbe-help', icon: 'editor-help' }, 'Help' ),
			el(
				PluginSidebar,
				{ name: 'sbe-help', title: 'Help', icon: 'editor-help' },
				el(
					'div',
					{ className: 'sbe-help sbe-help--sidebar' },
					el(
						'p',
						{ className: 'sbe-help-links' },
						el( 'a', { href: data.helpUrl, target: '_blank', rel: 'noopener' }, 'Open in a full page' ),
						' · ',
						el( 'a', { href: data.guideUrl, target: '_blank', rel: 'noopener' }, 'Full editor guide' )
					),
					el( RawHTML, null, data.html )
				)
			)
		);
	}

	registerPlugin( 'sbe-help', { render: HelpSidebar } );
} )( window.wp );
