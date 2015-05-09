/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 *  
 *  
 *  This small plugin is inpired from CKSource - Frederico Knabben. All rights reserved. Copyright (c) 2003-2012, for licensing see http://ckeditor.com/license
 */
 
 CKEDITOR.plugins.add('wordcount', {
    lang: ['de', 'en', 'fr'],
	init: function (editor) {
        var defaultFormat = '<span class="cke_path_item">' + editor.lang.wordcount.WordCount + ' %count%</span>';
        var format = defaultFormat;
        var intervalId;

		function counterId(editor) {
			return 'cke_wordcount_' + editor.name
		}
		function counterElement(editor) {
			return document.getElementById(counterId(editor))
		}
		function strip(html) {
			var tmp = document.createElement("DIV");
			tmp.innerHTML = html;
			return tmp.textContent || tmp.innerText
		}
		
		editor.on('uiSpace', function (event) {
			if (event.data.space == 'bottom') {
				event.data.html += '<div id="' + counterId(event.editor) + '" class="cke_wordcount" style="display:block;float:right;margin-top:5px;margin-right:3px;color:black;"' + ' title="' + CKEDITOR.tools.htmlEncode('Words Counter') + '"' + '>&nbsp;</div>'
			}
		}, editor, null, 100);
		
		function updateCounter(editor, event) {
			var count = 0;
			var limit = limit;
			var format = format;
			if (editor.config.wordcount_limit != undefined) {
				limit = editor.config.wordcount_limit
			}
			if (editor.config.wordcount_format != undefined) {
				format = editor.config.wordcount_format
			}
			if (editor.getData() != undefined) {
				var d = strip(editor.getData());
				if(d!=undefined && d!=null && d!=""){
					count = $.trim(d).split(/\s+/).length;
				} else {
					count = 0;
				}
			}
			
			var html = format.replace('%count%', Math.min(count, limit));
			html = html.replace('%limit%', limit);
			counterElement(editor).innerHTML = html;
			return count;
		}
		
		function limitWord(editor, event) {
			var count = updateCounter(editor, event);
			if (editor.config.wordcount_limit != undefined && count > editor.config.wordcount_limit) {
				kc = event.data.keyCode;
				if (kc != 46 && kc != 8 && kc != 9
					&& kc != 37 && kc != 38 && kc != 39 && kc != 40
					&& kc != 35 && kc != 36) { 
					event.cancel();
					//alert("canceled "+kc+" "+count+" "+limit);
				}
			}
		}
		
		editor.on('dataReady', function (event) { updateCounter(event.editor, event); }, editor, null, 100);
		//editor.on('selectionChange', function (event) { updateCounter(event.editor, event); }, editor, null, 100);
		//editor.on('focus', function (event) { updateCounter(event.editor, event); }, editor, null, 100);
		//editor.on('blur', function (event) { updateCounter(event.editor, event); }, editor, null, 100);
		editor.on('key', function (event) { limitWord(event.editor, event); }, editor, null, 0);
		
    }
});