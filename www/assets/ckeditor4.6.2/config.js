/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */
if(!CKEDITOR.wigiiCustom) {
	CKEDITOR.editorConfig = function( config ) {
		// Define changes to default configuration here. For example:
		// config.language = 'fr';
		// config.uiColor = '#AADC6E';
		
		config.disableNativeSpellChecker = false;
		config.scayt_autoStartup = false;
		config.removePlugins = 'scayt';
		
		config.autoGrow_onStartup = false; //after living with it, it is better when autogrow is not done automatically
		
		config.colorButton_colorsPerRow = 8;
		
		//config.extraPlugins = 'embed';
		
		config.colorButton_colors =
		    '000,800000,8B4513,2F4F4F,008080,000080,4B0082,696969,' +
		    'B22222,A52A2A,DAA520,006400,40E0D0,0000CD,800080,808080,' +
		    'F00,FF8C00,FFD700,008000,0FF,00F,EE82EE,A9A9A9,' +
		    'FFA07A,FFA500,FFFF00,00FF00,AFEEEE,ADD8E6,DDA0DD,D3D3D3,' +
		    'FFF0F5,FAEBD7,FFFFE0,F0FFF0,F0FFFF,F0F8FF,E6E6FA,FFF';
		
	};
}
 