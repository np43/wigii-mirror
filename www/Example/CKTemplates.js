// Register a templates definition set named "default".
CKEDITOR.config.templates_files = [ CLIENT_NAME+'/CKTemplates.js' ];
CKEDITOR.addTemplates( 'default',
{
	// The name of sub folder which hold the shortcut preview images of the templates.
	imagesPath : CKEDITOR.getUrl( CKEDITOR.plugins.getPath( 'templates' ) + 'templates/images/' ),

	// The templates definitions.
	templates :
		[
			{
				title: 'Memo',
				description: 'Template for typing memos.',
				html:
					'<div style="font-family:arial;">'+
					'<table><tr><td></td><td><h3>Title</h3></td></tr></table>'+
					'<div>Type your text here...' +
					'</div>' +
					'</div>'
			},
			{
				title: 'Standard',
				html:
					'<h3>Title</h3>' +
					'<p>Type the text here.</p>'
			}
		]
});