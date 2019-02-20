/**
 *  This file is part of Wigii.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/*
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details) 
 */

//timeout for external access
externalAccessTimeoutTimer = null;
previewCrtHeight = 10;

//DataType Files

function getBasicCKEditorToolbar(){
	return [
		[ 'Bold','Italic','Underline','TextColor'],['NumberedList','BulletedList','Outdent','Indent','Link', 'Image','Table','Scayt','Templates']];
}
function getMediumCKEditorToolbar(){
	return [
		['Templates','Maximize','-','PasteText','RemoveFormat'],['NumberedList','BulletedList','Outdent','Indent' ],['Link', 'Image','Table','HorizontalRule','-','Source','Scayt' ],'/',
		[ 'Format','FontSize','Bold','Italic','Underline','TextColor','BGColor','Strike','SpecialChar'],['JustifyLeft','JustifyCenter','JustifyRight']
		];
}
function getFullCKEditorToolbar(){
	//version 4
	return [
		[ 'Source','-','Templates','Preview','Maximize' ],
		[ 'Cut','Copy','Paste','PasteText','RemoveFormat','-','Undo','Redo' ],
		[ 'Find','Replace','-','SelectAll','-','Scayt','About' ],
		'/',
		[ 'NumberedList','BulletedList','-','Outdent','Indent','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ],
		[ 'Link','Unlink','Anchor', 'Image','Table','HorizontalRule','SpecialChar','CreateDiv','ShowBlocks' ],
		'/',
		[ 'Format','FontSize' ],
		[ 'TextColor','BGColor' ],
		[ 'Bold','Italic','Underline','Strike','Subscript','Superscript' ]
		];
}

function getScayt_sLangFromLanguage(lang){
	switch(lang){
		case "en": return "en_GB";
		case "fr": return "fr_FR";
		default : return "en_GB";
	}
}

function boxChooseFile(inputBoxFileId, inputFileId, inputNameId, inputPathId, SITE_ROOT_forFileUrl){
	$(inputBoxFileId).click(function(){
		// Clears all previous callbacks
		boxSelect.unregister(boxSelect.SUCCESS_EVENT_TYPE);
		boxSelect.unregister(boxSelect.CANCEL_EVENT_TYPE);
		//Register a success callback handler
		boxSelect.success(function(response) {
			var v = $(inputNameId).parent();
			$(inputBoxFileId).hide();
			$(inputFileId).hide();
			boxSuccess(inputNameId, inputPathId, response, SITE_ROOT_forFileUrl, v);
		}); 			
		//Register a cancel callback handler
		boxSelect.cancel(function() {/*nothing to do*/});		
		//Launch the Box's File Picker
		boxSelect.launchPopup();
	});
}
function boxSuccess(inputNameId, inputPathId, response, SITE_ROOT_forFileUrl, v){
	// Shows file management buttons
	$('div.updateCurrentFile', v).show();
	$('div.removeCurrentFile', v).show();
	$('div.downloadCurrentFile', v).hide();
	// Extracts file info 
	var name = response[0].name;
	var fileId = response[0].id;
	var dotSep=name.lastIndexOf(".");
	var type='';
	var ext='';
	if(dotSep>-1) {
		ext = name.substring(dotSep+1).toLowerCase();
		type = '.'+ext;
		name = name.substring(0,dotSep);
	}	
	// Puts the ID of the file in the path as box://xxxxx
	$(inputPathId).val("box://".concat(fileId));
	// Puts the type of the file
	$(inputPathId.replace("path", "type")).val(type);
	// Hides Box file picker button
	$(this).hide();	
	// puts file name in text box and shows mime type
	$(inputNameId).val(name).show().focus().change().select();
	$('div.filePreview', v).css('background-image', 'url("'+SITE_ROOT_forFileUrl+'images/preview/prev.26.'+ext+'.png")');
	$('div.filePreview', v).show();	
}
/**
 * Callback from BoxService which updates File info for the given field of type Files
 * @param String inputFileId HTML id of field of type Files for which to update File info
 * @param String fileName file name got from Box
 * @param Numeric fileSize actual file size in bytes got from Box
 * @param String fileSizeFormatted file size expressed as a string for end user
 * @param String fileMime file MIME type got from Box
 * @param String file date got from Box in format (YYYY-MM-DD HH:MM:SS)
 * @param String fileDateFormatted file date expressed as a string for end user
 */
function boxUpdateFileInfo(inputFileId, fileName, fileType, fileMime, fileSize, fileSizeFormatted, fileDate, fileDateFormatted) {
	// Extracts formId and field name from inputFileId
	var formId = inputFileId.split('__');
	var fieldName = formId[1];
	formId = formId[0];
	// Detects if we are in edit mode
	if($('#'+inputFileId+' div.value input#'+formId+'_'+fieldName+'_path_hidden').length>0) {		
		// Updates file name
		$('#'+formId+'_'+fieldName+'_name_text').val(fileName);
		// And all hidden fields
		$('#'+formId+'_'+fieldName+'_size_hidden').val(fileSize);
		$('#'+formId+'_'+fieldName+'_type_hidden').val(fileType);
		$('#'+formId+'_'+fieldName+'_mime_hidden').val(fileMime);
		$('#'+formId+'_'+fieldName+'_date_hidden').val(fileDate);
	}
	// Or in details mode
	else {
		var fileDet = $('#'+inputFileId+' div.value.file');
		if(fileDet.length>0) {
			// if small view
			if(fileDet.find('div.fdet.small').length>0){
				// updates file name
				fileDet.find('div.fdet a.H.fileDownload').html(fileName);
				// updates file date
				fileDet.find('div.fdet font').html('('+fileSizeFormatted+', '+fileDateFormatted+')');
				// updates file size
				var e = fileDet.find('div.fdet a.fdet.fileDownload img');
				if(e.attr('title')) e.attr('title',e.attr('title').replace(/\(.*\)/g,'('+fileSizeFormatted+')'));
				e = fileDet.find('div.fdet img.checkOutIn');
				if(e.attr('title')) e.attr('title',e.attr('title').replace(/\(.*\)/g,'('+fileSizeFormatted+')'));
				
			}
			// else if normal view
			else {
				// updates file name
				fileDet.find('div.fdet a.fileDownload').html(fileName);
				// updates file date
				fileDet.find('div.fdet font').html('('+fileDateFormatted+')');
				// updates file size
				fileDet.find('a.fdet.fileDownload div span font').html('('+fileSizeFormatted+')');
			}
		}
	} 		
}
function addJsCodeOnFileInput(inputFileId, inputNameId, inputPathId, clickToBrowseAndFindANewFile, SITE_ROOT_forFileUrl){
	var v = $(inputNameId).parent();
	var inputBoxFileId = inputPathId.replace('_path_hidden','_box_hidden');
	$('div.removeCurrentFile', v).click(function(){
		var v = $(inputNameId).parent();
		$(this).hide();
		
		var w = "#".concat($(inputNameId).parent().parent().attr('id'));

		// Removes Box service tag on file removal
		if(domElementHasWigiiService(w, "box")){
			domElementRemoveWigiiService(w, "box");
		}
		// Hides file operations
		$('div.filePreview', v).hide();
		$('div.updateCurrentFile', v).hide();
		$('div.downloadCurrentFile', v).hide();
		$('div.backToFilename', v).hide();
		// Shows Box file picker button
		$(inputBoxFileId).show();
		// Shows Local file chooser
		$(inputFileId).val('').show();
		$(inputNameId).val('').hide().change().blur(); //this is important to generate autoSave feature if activated
		$(inputPathId).val('');
	});
	$('div.backToFilename', v).click(function(){
		var v = $(inputNameId).parent();
		$(this).hide();
		$('div.filePreview', v).show();
		$('div.updateCurrentFile', v).show();
		$('div.removeCurrentFile', v).show();
		$(inputFileId).hide();
		$(inputNameId).show();
	});
	$('div.updateCurrentFile', v).click(function(e){
		var v = $(inputNameId).parent();	
		if($(inputPathId).val().indexOf("box://")>=0 && window.boxSelect){
			// Clears all previous callbacks
			boxSelect.unregister(boxSelect.SUCCESS_EVENT_TYPE);
			boxSelect.unregister(boxSelect.CANCEL_EVENT_TYPE);
			//Register a success callback handler
			boxSelect.success(function(response) {
				$('div.downloadCurrentFile', v).hide();
				$('div.removeCurrentFile', v).hide();
				boxSuccess(inputNameId, inputPathId, response, SITE_ROOT_forFileUrl, v);
			}); 			
			//Register a cancel callback handler
			boxSelect.cancel(function() {/*nothing to do*/});		
			//Launch the Box's File Picker
			boxSelect.launchPopup();
			
		} else {
			$(this).hide();
			$('div.removeCurrentFile', v).show();
			$('div.filePreview', v).hide();
			$('div.backToFilename', v).show();
			$(inputNameId).hide();
			$(inputFileId).show();
			showHelp($(inputFileId), clickToBrowseAndFindANewFile, 0, 'left');
		}
	});
	$(inputFileId).click(function(){ hideHelp(); }).change(function(){
		var v = $(inputFileId).parent();
		$('div.backToFilename', v).hide();
		$('div.updateCurrentFile', v).show();
		$('div.removeCurrentFile', v).show();
		var name = $(this).val();
		name = name.substring(name.lastIndexOf('"."\\"."\\"."')+1);
		var ext = name.substring(name.lastIndexOf('.')+1);
		name = name.substring(0, name.lastIndexOf('.'));
		//remove any local path in the name
		var re = new RegExp('.*/', "g");
		name = name.replace(re, '');
		re = new RegExp('.*\\\\', "g");
		name = name.replace(re, '');
		// hides local File chooser
		$(this).hide();	
		// hides Box file picker
		$(inputBoxFileId).hide();
		// puts file name in text box and shows mime type
		$(inputNameId).val(name).show().focus().select();
		$('div.filePreview', v).css('background-image', 'url("'+SITE_ROOT_forFileUrl+'images/preview/prev.26.'+ext+'.png")');
		$('div.filePreview', v).show();
	});
	
	//make the whole field area dropable from files and not only the input
	if(!!navigator.userAgent.match(/Trident/g) || !!navigator.userAgent.match(/MSIE/g)){
		//IE do not support this code to manage file drag and drop
	} else {
		var dropContainer = document.getElementById(inputFileId.replace('#','')).parentNode.parentNode;
		dropContainer.ondragover = dropContainer.ondragenter = function(evt) {
			$(inputFileId).parent().parent().css('background-color','#ddd');
			evt.preventDefault();
		};
		dropContainer.ondragleave = dropContainer.ondragout = function(evt) {
			$(inputFileId).parent().parent().css('background-color','transparent');
			evt.preventDefault();
		};
		dropContainer.ondrop = function(evt) {
			// pretty simple -- but not for IE :(
			document.getElementById(inputFileId.replace('#','')).files = evt.dataTransfer.files;
			$(inputFileId).change();
			$(inputFileId).parent().parent().css('background-color','transparent');
			evt.preventDefault();
		};
	}
}

//DataType on-line Files
// if cancel is false, then dialog without a cancel button (only ok button).
function addJsCodeOnOnLineFileInput(textContentId, inputNameId, template, cancel, ok, SITE_ROOT_forFileUrl, newDocument, lang, height, templateFile){
	v = $(inputNameId).parent();
	$('div.removeExistingOnLineFile', v).click(function(){
		v = $(inputNameId).parent();
		$(this).hide();
		$('div.filePreview', v).hide();
		$('div.removeExistingOnLineFile', v).hide();
		$('div.updateExistingOnLineFile', v).hide();
		$('div.newOnLineFile', v).show();
		$(inputNameId).val('').hide();
		$(textContentId).val('').change().blur();  //this is important to generate autoSave feature if activated
	});

	$('div.newOnLineFile, div.updateExistingOnLineFile', v).click(function(e){
		myPosition = { my: 'center top', at: 'center top+50' };
		v = $(inputNameId).parent();
		if($(textContentId).val()==''){
			//load a default template if defined in the config
			if(template!=null){
				t = CKEDITOR.getTemplates('default').templates;
				for(i in t){
					if(t[i].title == template){
						$(textContentId).val(t[i].html);
					}
				}
			}
		}
		$('#elementPreview').html('<textarea style="width:'+($(window).width()-30)+'px;" id="elementPreview_textContent">'+$(textContentId).val().replace(/&/g,'&amp;')+'</textarea>');

		//activates link for scheduled autosave
		crtActiveOnlineFileTextContentId = textContentId;

		// displays dialog
		if( $('#elementPreview').is(':ui-dialog')) { $('#elementPreview').dialog("destroy"); }
		var btns = {ok: function(){
				// clears link for scheduled autosave
				crtActiveOnlineFileTextContentId=null;
				//$(textContentId).val($('#elementPreview_textContent').val());
				$(textContentId).val($('#elementPreview').find('textarea:first').ckeditorGet().getData()).change().blur();  //this is important to generate autoSave feature if activated
				v = $(inputNameId).parent();
				$('div.filePreview', v).css('background-image', 'url("'+SITE_ROOT_forFileUrl+'images/preview/prev.26.html.png")');
				$('div.filePreview', v).show();
				$('div.removeExistingOnLineFile', v).show();
				$('div.updateExistingOnLineFile', v).show();
				$('div.newOnLineFile', v).hide();
				if($(inputNameId).val()==null || $(inputNameId).val()==''){
					$(inputNameId).val(newDocument);
				}
				$(inputNameId).show().focus().select();
				CKEDITOR.remove($('#elementPreview').find('textarea:first').ckeditorGet());
				$(this).val('').dialog('destroy');
			}
		};
		if(cancel !== false) btns[cancel] = function(){
			//clears link for scheduled autosave
			crtActiveOnlineFileTextContentId=null;
			$(this).dialog('destroy');
		};
		$('#elementPreview').dialog({
			buttons: btns,
			width: $(window).width()-110, position: myPosition,
			closeOnEscape: false, draggable:false, resizable:false, modal:false,
			beforeClose: function(){ }
		});
		$('#elementPreview').prev().css('display', 'none');

		var editorID = 'elementPreview_textContent';
		var instance = CKEDITOR.instances[editorID];
		if (instance) { CKEDITOR.remove(instance); }
		tempZIndex = $('#elementPreview').parent().css('zIndex')+1;
		$('#elementPreview').find('textarea:first').ckeditor({
			language : lang,
			scayt_sLang : getScayt_sLangFromLanguage(lang),
			templates : crtWigiiNamespaceUrl+",default",
			templates_files : [templateFile],
			baseFloatZIndex : tempZIndex,
			toolbar : getFullCKEditorToolbar(),
			toolbarCanCollapse : true,
			toolbarStartupExpanded : true,
			resize_minWidth: $(window).width()-130,
			height:height-100,
			allowedContent: true,
		    filebrowserUploadUrl : crtWigiiNamespaceUrl+'/'+crtModuleName+'/CKEditor/upload'
		});
	});
}

var progressBarTimeout = null;
var savingBarTimeout = null;
var jsFileContentInForm = null;
function getAjaxformOption(formId){
	return {
		success: parseUpdateResult,
		beforeSerialize: function(){
			//remove disable for readonly field when submitting the form
			if($(formId+' .removeDisableOnSubmit').length>0){
				$(formId+' .removeDisableOnSubmit').removeAttr('disabled');
			}
		},
		beforeSubmit:  function(formData, jqForm, options){
			hideHelp(); setVis('busyDiv', true);
			// show long run loading bar if server side response takes more than 2 seconds
			if(savingBarTimeout==null){
				savingBarTimeout = setTimeout(function(){ 
					$("#savingBar").html(wigii().context.serverSavingWaitingMsg).show();
					// if Form has Fields linked to box-upload service, then shows Box specific waiting message
					if($(formId).wigii('FormHelper').fields("[data-wigii-service~='box-upload']").$.length) {
						var boxCtx = wigii().context.box;
						boxCtx.showBoxWaitingMsg = true;
						if(!boxCtx.waitForBoxUpload) boxCtx.waitForBoxUpload = function() {
							if(wigii().context.box.showBoxWaitingMsg) $("#savingBar").html(wigii().context.box.serverSavingWaitingMsg).show();
							else $("#savingBar").html(wigii().context.serverSavingWaitingMsg).show();
							wigii().context.box.showBoxWaitingMsg = !wigii().context.box.showBoxWaitingMsg;
							savingBarTimeout = setTimeout(wigii().context.box.waitForBoxUpload, 5000);
						};
						savingBarTimeout = setTimeout(boxCtx.waitForBoxUpload, 3500);						
					}
				}, 2000);
		    }
			//add any jsFile content in form data
			if(jsFileContentInForm){
				for(var jsFileContent in jsFileContentInForm){
					formData[formData.length] = { name: jsFileContent, value: jsFileContentInForm[jsFileContent] };
				}
				jsFileContentInForm = null;
			}
			closeAnyJsOnGoingStreams();
		},
		uploadProgress: function(event, position, total, percentComplete) {
	        $("#formProgressBar").progressbar({
		      value: percentComplete
		    });
		    //show the progress bar only if the upload operation takes more than 500 miliseconds
		    if(progressBarTimeout==null){
			    progressBarTimeout = setTimeout(function(){ $("#formProgressBar").show(); }, 500);
		    }
		    if(percentComplete>=100){
		    	setTimeout(function(){ if(progressBarTimeout){ clearTimeout(progressBarTimeout); } progressBarTimeout = null; $("#formProgressBar").hide(); },500);
		    }
	    },
		error: errorOnUpdate, cache:false };
}

//stop any on going streams (currently only audio)
//called in WigiiExecutor.js actOnCancelDialog and in WigiiForm.js getAjaxformOption
function closeAnyJsOnGoingStreams(){
	if(jsOnGoingStreams){
		for(var jsStreams in jsOnGoingStreams){
			if(typeof jsOnGoingStreams[jsStreams] == "object"){
				var audioTracks = jsOnGoingStreams[jsStreams].getAudioTracks();
				if(audioTracks){
					for(var audioTrack in audioTracks){
						audioTracks[audioTrack].stop();
					}
				}
			}
		}
		jsOnGoingStreams = null;
	}
}
var jsOnGoingStreams = null; //each audio streams are stoped if still opened will be stopped in getAjaxformOption->beforeSubmit
function addJsCodeOnFileAudioRecording(formIdFielname, audioFieldname, audioRecordingButtonId, audioRecordingSaveButtonId, continueRecordingLabel, pauseRecordingLabel, stopedRecordingLabel){
	window.AudioContext = window.AudioContext || window.webkitAudioContext;
	/*check if navigator allows MediaDevices*/
	if(navigator.mediaDevices.getUserMedia){
		var chunks = [];
		var stream = null;
		
		var onError = function(error) {
		  console.error('Error: ', error);
		};
		
		var onSuccess = function(stream){
			if(!jsOnGoingStreams){ jsOnGoingStreams = {}; }
			jsOnGoingStreams[audioFieldname] = stream;
			var audioRecorder = new MediaRecorder(stream);
			
			$('#'+audioRecordingButtonId).click(function(){ 
				if(audioRecorder.state=='inactive'){
					audioRecorder.start();
				} else if(audioRecorder.state=='paused'){
					audioRecorder.resume();
				} else if(audioRecorder.state=='recording'){
					audioRecorder.pause();
				}
				$('#'+audioRecordingSaveButtonId).show();
				if(audioRecorder.state=='recording'){
					$(this).addClass('isRecording');
					$(this).html('<img src="images/icones/tango/16x16/actions/media-playback-pause.png" style="float:left;margin-right:5px;"/>'+pauseRecordingLabel);
				} else {
					$(this).removeClass('isRecording');
					$(this).html('<img src="images/icones/tango/16x16/devices/audio-input-microphone.png" style="float:left;margin-right:5px;"/>'+continueRecordingLabel);
				}
			});
			
			$('#'+audioRecordingSaveButtonId).click(function(){ 
				if(audioRecorder.state!='inactive'){
					audioRecorder.stop();
					stream.getAudioTracks()[0].stop();
					$('#'+audioRecordingButtonId).hide();
					$(this).html('<img src="images/icones/18px/accept.png" style="float:left;margin-right:5px;"/>'+stopedRecordingLabel);
				}
			});
			
			audioRecorder.onstop = function(e) {
				/*store the audio data in a file */
				var filename = String(moment().format('YYYY-MM-DD HH:mm'))+'.ogg';
				var file = new File(chunks, filename, { 'type' : 'audio/ogg; codecs=opus'});
				/* add file information in the wigii Files subfields */
				$('#'+formIdFielname+'_name_text').val(filename.replace('.ogg',''));
				$('#'+formIdFielname+'_type_hidden').val('.ogg');
				$('#'+formIdFielname+'_size_hidden').val(file.size);
				$('#'+formIdFielname+'_path_hidden').val('');
				chunks = [];
				jsFileContentInForm[audioFieldname] = file;
			};
			
			audioRecorder.ondataavailable = function(e) {
				chunks.push(e.data);
			};
		};
		
		/*gain access to the mic*/
		try {
			navigator.mediaDevices.getUserMedia({audio: true}).then(onSuccess,onError);
		} catch (error){
			console.error('Error: ', error);
		}
	}
}

function addJsCodeAfterFormIsShown(formId, lang, templateFilter, templateFile){
	//reset any jsFileContent
	jsFileContentInForm = {};
	$(formId+' textarea.htmlArea').each(function(){
		var editorID = $(this).attr('id');
		var instance = CKEDITOR.instances[editorID];
		if (instance) { CKEDITOR.remove(instance); }
		if($(formId +' textarea.htmlArea').closest('.ui-dialog').length > 0){
			tempZIndex = $(formId+ ' textarea.htmlArea').closest('.ui-dialog').css('zIndex')+1;
		} else {
			tempZIndex = 100;
		}
		if($(this).hasClass('fullToolbarHtmlArea')){
			toolbar = getFullCKEditorToolbar();
		} else if($(this).hasClass('mediumToolbarHtmlArea')){
			toolbar = getMediumCKEditorToolbar();
		} else {
			toolbar = getBasicCKEditorToolbar();
		}
		height = 100;
		maxHeight = 450;
		options = {};
		if($(this).hasClass('wordlimit') && !$(this).hasClass('isJournal')){
			wordlimit = $(this).attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1');
			extraPlugins = ""
			if(options.extraPlugins) extraPlugins = options.extraPlugins+',';
			extraPlugins += 'wordcount,undo';
			options = $.extend(options, {
				extraPlugins : extraPlugins,
				wordcount_limit : wordlimit,
				wordcount_format : '<span class="cke_path_item">Word count: %count% / %limit%</span>'
			});
		}
		if($(this).hasClass('difH')){
			height = $(this).height()-40;
		} else {
			extraPlugins = ""
			if(options.extraPlugins) extraPlugins = options.extraPlugins+',';
			extraPlugins += 'autogrow';
			options = $.extend(options, {
				extraPlugins : extraPlugins,
				autoGrow_minHeight : height,
				autoGrow_maxHeight : maxHeight
			});
		}

		options = $.extend({
				language : lang,
				scayt_sLang : getScayt_sLangFromLanguage(lang),
				templates : templateFilter,
				templates_files : [templateFile],
				baseFloatZIndex : tempZIndex,
				toolbar : toolbar,
				toolbarCanCollapse : true,
				toolbarStartupExpanded : $(this).hasClass('activateHtmlArea'),
				height : height,
				resize_minWidth : 50,
				allowedContent: true,
				filebrowserUploadUrl : crtWigiiNamespaceUrl+'/'+crtModuleName+'/CKEditor/upload'
			}, options);

		$(this).ckeditor(
			function(){
				t = $('#'+this.element.getId());
				if(t.hasClass('collapsed')){
					t.next().hide();
				}
				this.on('focus', function() {
					//$(this.element.$).focus();
					//crtActiveCKEditor = this.element.$;
					$(this.element.$).triggerHandler('focus');
				});
				//this event is then fired on the textarea it self. This allow autoSave to be trigged on blur
				this.on('blur', function() {
					$(this.element.$).blur();
				});
			}, options);
		
		$(window).resize();
		
		//instance = $(this).ckeditorGet();
//		instance.on('keydown', function(){ l = $(this).closest('div.field').find('.label'); if(l.find(':input:first').length){ l.find(':input:first').attr('checked',true); } });
	});

	autosize($(formId+' textarea:not(.noWrap):not(.noElastic)').css('max-height',450).css('min-height',30));

	$(formId+' textarea.noWrap').css('overflow','auto').tabby();
	$(formId+' :input:enabled:not([readonly]):first').focus();
	
	/*
	 * CWE 2015.06.18: switches chosen and flex plugin to select2 plugin to improve rendering performances and functionalities
	 */	
	$('select.flex, select.chosen',formId).each(function(i) {
		var current = $(this);
		var attrPlaceholder = $.trim(current.attr('data-placeholder')) || false;
		var maxSelection = current.attr("data-max-selection") || false;
		//create options for the creation of select2 list
		var options = {};
		if(attrPlaceholder != false && attrPlaceholder.length>0) {
			if(current.hasClass('flex')) options.allowClear = true;
			options.placeholder = attrPlaceholder;
		}		
		if(maxSelection != false && current.hasClass('chosen')) options.maximumSelectionLength = maxSelection;
		if(current.hasClass('allowNewValues')) options.tags = [];
		// CWE 11.10.2018 if displayDBValue then shows db value instead of label, but keeps label in search/drop-down
		if(current.hasClass('displayDBValue')) options.templateSelection = function(data){return data.id;};
		
		// Medair (CWE) 08.02.2018: add support of ajax data sources
		var attributeMatchExp = current.attr("data-attributematchexp") || false;
		if(attributeMatchExp) {
			// configures the ajax object for select2
			options.ajax = {
				url:function(params) {
					return encodeURI(SITE_ROOT+"useContext/"+crtContextId+EXEC_requestSeparator+crtWigiiNamespaceUrl+"/"+crtModuleName+"/fx/"+attributeMatchExp+"/"+params.term);
				},
				data:function(params){},
				dataType:"json",			
				delay: Number(current.attr("data-querydelay")) || 250,
				error: wigii().defaultFxErrorHandler
			};
			options.minimumInputLength= Number(current.attr("data-queryminlength")) || 1;
			options.selectId = current.attr('id');
			options.dataAdapter = $.fn.select2.amd.require('select2/data/wigiiAjaxAdapter');
		}
		current.select2(options);
	});
	
	$(formId+' textarea:not(.htmlArea,.isJournal).wordlimit').each(function(){ $(this).wordlimit({ allowed: $(this).attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1') }); });

	/* deprecated code working with colorpicker plugin, depracted on the 22.03.2018 and replace by spectrum
	$(formId+' .colorPickerInput:input:enabled:not([readonly])').ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
			$(el).val(hex);
			$(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$(this).ColorPickerSetColor(this.value);
		}
	})
	.bind('keyup', function(){
		$(this).ColorPickerSetColor(this.value);
	});
	*/
	$(formId+' .colorPickerInput:input:enabled:not([readonly])').spectrum({
		preferredFormat: "hex",
		chooseText: DIALOG_okLabel,
		cancelText: DIALOG_cancelLabel,
		allowEmpty:true,
	    showInput: true,
	    showPalette: true,
	    maxPaletteSize: 10,
	    maxSelectionSize: 10,
	    localStorageKey: "spectrum."+crtWigiiNamespaceUrl,
	    palette: [
	        ["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(153, 153, 153)","rgb(183, 183, 183)",
	        "rgb(204, 204, 204)", "rgb(217, 217, 217)", "rgb(239, 239, 239)", "rgb(243, 243, 243)", "rgb(255, 255, 255)"],
	        ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)",
	        "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"],
	        ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)",
	        "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)"],
	        ["rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)",
	        "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)"],
	        ["rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)",
	        "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)"],
	        ["rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)",
	        "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)"],
	        ["rgb(133, 32, 12)", "rgb(153, 0, 0)", "rgb(180, 95, 6)", "rgb(191, 144, 0)", "rgb(56, 118, 29)",
	        "rgb(19, 79, 92)", "rgb(17, 85, 204)", "rgb(11, 83, 148)", "rgb(53, 28, 117)", "rgb(116, 27, 71)"],
	        ["rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)",
	        "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]
	    ]
    });

	$(formId).ajaxForm(getAjaxformOption(formId));

	$(formId+' div.label').click(function(){ $(':input:visible:first', $(this).next()).select(); } );
	$(formId+' .addC').nextAll().hide();
	$(formId+' .addC').click(function(){
		$(this).hide();
		//show the field
		$(this).next().show();
		//add Date management
		$(this).next().children('.ui-datepicker-trigger').show();
		//tabs management
		$(this).next().children('.ui-tabs').show();
		//htmlArea management
		$(this).next().children('.cke').show();
		//add a width of 100% to label
        $(this).prev().css('width', '100%');
		//.find('>:not(.timeZone)').show();
		$(this).next().find(':input:first').focus();
	});
	//resize the date picker
	if($(formId+' .ui-datepicker-trigger').length){ $(formId+' .ui-datepicker-trigger').css("width", "30px"); }
	//add code for multiple select
	$(formId+' select[multiple]:not([readonly]):not([disabled])').click(function(){ multipleSelectOnClick(this.id); }).each(function(){
		multipleSelectVals[this.id] = $(this).val();
	});
	
	//handle the data-max-selection on checkboxes
	$( '*:input:checkbox[data-max-selection]' ).click(function( event ) {
		var maxSelectionValue = event.target.dataset.maxSelection;
		if(maxSelectionValue > 0) {
			var valueDiv = $(event.target).parent().parent();
			$(valueDiv).find('span[class="max-selection-alert"]').remove();
			if($(valueDiv).find(':checkbox:checked').length >= maxSelectionValue) {
				if ($(valueDiv).find(':checkbox:checked').length > maxSelectionValue) $(event.target).attr('checked', false);
				$(valueDiv).find(':checkbox:not(:checked)').attr("disabled", true);
				$(valueDiv).append('<span class="max-selection-alert" style="color:DarkGray">You can only select '+maxSelectionValue+' items</span>');
			} else {
				$(valueDiv).find(':checkbox:not(:checked)').attr("disabled", false);
			}
		}
	});

	//show Sysinfo on mouse over + CTRL
	$(formId+' div.field .label').bind('contextmenu', function(e){
		$(this).parent().find(">.addinfo").show();
		return false;
	});
	$(formId+' div.field').mouseleave(function(e){
		$(this).find(".addinfo").hide();
	});
	if(isWorkzoneViewDocked()) addScrollWithShadow($(formId).parent().prop("id"));	
}

function convertTimestamps(obj){
	//advanced search: match any TIMESTAMP() and convert them into a real timestamp
	orignalText = $(obj).val();
	res = orignalText.match(/TIMESTAMP\(([0-9]{4}\-[0-9]{2}\-[0-9]{2}[ \:0-9]*)\)/gi);
	for(r in res){
		d = new Date(res[r].replace("TIMESTAMP", "").replace("(", "").replace(")", "").replace(/-/g, "/"));
		orignalText = orignalText.replace(res[r], Math.round(d.getTime()/1000));
	}
	$(obj).val(orignalText);
}

emailingFormCrtMergeField = "";
function setListenerForEmailMerge(formId){
	$("#"+formId+" #Emailing_form__message>.label span").click(function(e){
		clone = $(this).clone();
		clone.css("position", "absolute");
		clone.css("z-index", "999999999");
		clone.offset({left: $(this).position().left+10, top: $(this).position().top+10});
		emailingFormCrtMergeField = clone.attr("title");
		crtOverSubject = false;
		$("#"+formId+"").mousemove(function(e) {
			clone.offset({left: e.pageX+10, top: e.pageY+10});
		});
		$("#"+formId+" #Emailing_form_object_value_text").mouseenter(function(){ crtOverSubject = true; });
		$("#"+formId+" #Emailing_form_object_value_text").mouseleave(function(){ crtOverSubject = false; });
		e.stopPropagation();
		$("#"+formId+"").one("click", function(){
			clone.remove();
			if($("#"+formId+" #Emailing_form_object_value_text:focus").length){
				$("#"+formId+" #Emailing_form_object_value_text").val($("#"+formId+" #Emailing_form_object_value_text").val()+emailingFormCrtMergeField);
			}
			emailingFormCrtMergeField = null;
			$("#cke_Emailing_form_message_value_textarea iframe").contents().find("body").unbind("click");
		});
		$("#cke_Emailing_form_message_value_textarea iframe").contents().find("body").one("click", function(){
			clone.remove();
			ckInst = CKEDITOR.instances["Emailing_form_message_value_textarea"];
			ckInst.insertHtml(emailingFormCrtMergeField);
			$("#"+formId+"").unbind("click");
			emailingFormCrtMergeField = null;
		});
		$("#"+formId+" #Emailing_form__message>.label").append(clone);
	});
}

confirmationEmailFormCrtMergeField = "";
function setListenerForConfirmationEmailMerge(formId){
	$("#groupSubscription_form__subscriptionConfEmailText>.label span").click(function(e){
		clone = $(this).clone();
		clone.css("position", "absolute");
		clone.css("z-index", "999999999");
		clone.offset({left: $(this).position().left+10, top: $(this).position().top+10});
		confirmationEmailFormCrtMergeField = clone.attr("title");
		crtOverSubject = false;
		$("#"+formId+"").mousemove(function(e) {
			clone.offset({left: e.pageX+10, top: e.pageY+10});
		});
		$("#groupSubscription_form_subscriptionConfEmailSubject_tabs input").mouseenter(function(){ crtOverSubject = true; });
		$("#groupSubscription_form_subscriptionConfEmailSubject_tabs input").mouseleave(function(){ crtOverSubject = false; });
		e.stopPropagation();
		$("#"+formId+"").one("click", function(){
			clone.remove();
			if($("#groupSubscription_form_subscriptionConfEmailSubject_tabs input:focus").length){
				$("#groupSubscription_form_subscriptionConfEmailSubject_tabs input:focus").val($("#groupSubscription_form_subscriptionConfEmailSubject_tabs input:focus").val()+confirmationEmailFormCrtMergeField);
			}
			confirmationEmailFormCrtMergeField = null;
			$("#cke_groupSubscription_form_subscriptionConfEmailText_value_l01_textarea iframe").contents().find("body").unbind("click");
			$("#cke_groupSubscription_form_subscriptionConfEmailText_value_l02_textarea iframe").contents().find("body").unbind("click");
		});
		$("#cke_groupSubscription_form_subscriptionConfEmailText_value_l01_textarea iframe").contents().find("body").one("click", function(){
			clone.remove();
			ckInst = CKEDITOR.instances["groupSubscription_form_subscriptionConfEmailText_value_l01_textarea"];
			ckInst.insertHtml(confirmationEmailFormCrtMergeField);
			$("#"+formId+"").unbind("click");
			confirmationEmailFormCrtMergeField = null;
		});
		$("#cke_groupSubscription_form_subscriptionConfEmailText_value_l02_textarea iframe").contents().find("body").one("click", function(){
			clone.remove();
			ckInst = CKEDITOR.instances["groupSubscription_form_subscriptionConfEmailText_value_l02_textarea"];
			ckInst.insertHtml(confirmationEmailFormCrtMergeField);
			$("#"+formId+"").unbind("click");
			confirmationEmailFormCrtMergeField = null;
		});
		$("#groupSubscription_form__subscriptionConfEmailText>.label").append(clone);
	});
}

function actOnDisplayOnRightSide(elementDialogId, fieldId, journalItemWidth, totalWidth){
	if($('#elementRightSide').length==0){
		//create elementRightSide
		if($('#'+elementDialogId+' .elementDetail').length){
			//if in detail mode
			$('#'+elementDialogId+' .elementDetail').wrapInner('<div class="center" />');
			$('#'+elementDialogId+' .elementDetail').prepend('<table style="border:none;border-collapse:collapse;margin:0px;padding:0px;"><tr><td valign="top" class="center"></td><td valign="top" class="right"></td></tr></table>');
			$('#'+elementDialogId+' .elementDetail td.center').append($('#'+elementDialogId+' .elementDetail div.center'));
			$('#'+elementDialogId+' .elementDetail td.right').append('<div style="float:left;border-width:1px;padding:5px;width:'+(journalItemWidth)+'px;text-align:left;" id="elementRightSide" class="Tcolor"></div>');
			$('#'+elementDialogId+' .elementDetail>table td.center').width(totalWidth+22);
			$('#'+elementDialogId+'').closest('.ui-dialog').width(totalWidth+journalItemWidth+32);
			$('#'+elementDialogId+'>div.T').width(totalWidth+journalItemWidth+31);
			if($('#'+elementDialogId+'').closest('.ui-dialog').position()) $('#'+elementDialogId+'').closest('.ui-dialog').css('left', Math.min($('#'+elementDialogId+'').closest('.ui-dialog').position().left, $(window).width()-$('#'+elementDialogId+'').closest('.ui-dialog').outerWidth()-5));
//			$('#'+elementDialogId+' .elementDetail').before('<div style="position:fixed;margin-top:-50px;margin-left:'+(totalWidth+19)+'px;border-width:2px;padding:5px;width:'+(journalItemWidth+20)+'px;background-color:#fff;" id="elementRightSide" class="SBIB ui-corner-all"></div>');
		} else if($('#mainDiv .elementDetail').length){
			//in print mode
			$('#mainDiv .elementDetail').wrapInner('<div class="center" />');
			$('#mainDiv .elementDetail').prepend('<table style="border:none;border-collapse:collapse;margin:0px;padding:0px;"><tr><td valign="top" class="center"></td><td valign="top" class="right"></td></tr></table>');
			$('#mainDiv .elementDetail td.center').append($('#mainDiv .elementDetail div.center'));
			$('#mainDiv .elementDetail td.right').append('<div style="float:left;border-width:1px;padding:5px;width:'+(journalItemWidth)+'px;text-align:left;" id="elementRightSide" class="Tcolor"></div>');
			$('#mainDiv .elementDetail>table td.center').width(totalWidth+22);
//			$('#mainDiv .elementDetail').before('<div style="position:absolute;margin-left:'+(totalWidth+19)+'px;border-width:1px;padding:5px;width:'+(journalItemWidth+20)+'px;background-color:#fff;" id="elementRightSide" class="BSB ui-corner-all"></div>');
		} else if($('#mainDiv .public form').length){
			//external edit
			$('#mainDiv .public form').wrapInner('<div class="center" />');
			$('#mainDiv .public form').prepend('<table style="border:none;border-collapse:collapse;margin:0px;padding:0px;"><tr><td valign="top" class="center"></td><td valign="top" class="right"></td></tr></table>');
			$('#mainDiv .public form td.center').append($('#mainDiv .public form div.center'));
			$('#mainDiv .public form td.right').append('<div style="float:left;border-width:1px;padding:5px;width:'+(journalItemWidth)+'px;text-align:left;" id="elementRightSide" class="Tcolor"></div>');
			$('#mainDiv .public form>table td.center').width(totalWidth+22);
			$('#mainDiv .public form').width(totalWidth+journalItemWidth+32);
			$('#mainDiv .public').width(totalWidth+journalItemWidth+32);
			$('#mainDiv .public form>table').after($('#mainDiv .public form>table td.center>div.center>div.publicFormBorder'));
			$('#mainDiv .public form>div.publicFormBorder').width(totalWidth+journalItemWidth+32);
		} else if($('#mainDiv .public').length){
			//external view
			$('#mainDiv .public').wrapInner('<div class="center" />');
			$('#mainDiv .public').prepend('<table style="border:none;border-collapse:collapse;margin:0px;padding:0px;"><tr><td valign="top" class="center"></td><td valign="top" class="right"></td></tr></table>');
			$('#mainDiv .public td.center').append($('#mainDiv .public div.center'));
			$('#mainDiv .public td.right').append('<div style="float:left;border-width:1px;padding:5px;width:'+(journalItemWidth)+'px;text-align:left;" id="elementRightSide" class="Tcolor"></div>');
			$('#mainDiv .public>table td.center').width(totalWidth+22);
			$('#mainDiv .public').width(totalWidth+journalItemWidth+32);
			$('#mainDiv .public>table').after($('#mainDiv .public>table td.center>div.center>div.publicFormBorder'));
			$('#mainDiv .public>div.publicFormBorder').width(totalWidth+journalItemWidth+32);
		} else if($('#'+elementDialogId+' form').length){
			//in form mode
			$('#'+elementDialogId+' form').wrapInner('<div class="center" />');
			$('#'+elementDialogId+' form').prepend('<table style="border:none;border-collapse:collapse;margin:0px;padding:0px;"><tr><td valign="top" class="center"></td><td valign="top" class="right"></td></tr></table>');
			$('#'+elementDialogId+' form td.center').append($('#'+elementDialogId+' form div.center'));
			$('#'+elementDialogId+' form td.right').append('<div style="float:left;border-width:1px;padding:5px;width:'+(journalItemWidth)+'px;text-align:left;" id="elementRightSide" class="Tcolor"></div>');
			$('#'+elementDialogId+' form>table td.center').width(totalWidth+12);
			$('#'+elementDialogId+' form').width(totalWidth+journalItemWidth+32);
			$('#'+elementDialogId+'').closest('.ui-dialog').width(totalWidth+journalItemWidth+37);
			if($('#'+elementDialogId+'').closest('.ui-dialog').position()) $('#'+elementDialogId+'').closest('.ui-dialog').css('left', Math.min($('#'+elementDialogId+'').closest('.ui-dialog').position().left, $(window).width()-$('#'+elementDialogId+'').closest('.ui-dialog').outerWidth()-5));
//			$('#'+elementDialogId+' form').wrapInner('<div class="center" />');
//			$('#'+elementDialogId+' form>.center').before('<div style="position:fixed;margin-left:'+(totalWidth+12)+'px;border-width:2px;padding:5px;width:'+(journalItemWidth+20)+'px;background-color:#fff;" id="elementRightSide" class="SBIB ui-corner-all"></div>');
            $('#'+elementDialogId).ready(function(){
                var width = $('.ui-dialog').width()+20;
                $('.ui-dialog').css('width', width);
            });
		} else {
			return;
		}
	}

	$('#'+fieldId)
		.width(journalItemWidth)
		.find('div.label').width(journalItemWidth)
		.next().width(journalItemWidth)
		;
	if($('#'+fieldId+' div.value>div').length){
		$('#'+fieldId+' div.value>div').width(journalItemWidth);
	}

	$('#elementRightSide').append($('#'+fieldId));

	$('#elementRightSide .label').css("font-weight", "bold");
	$('#elementRightSide .field:gt(0)').css('border-top','solid 2px #fff').css('margin-top','5px');
	//add max height only in
	if($('#'+elementDialogId+' .elementDetail').length){
		//if in detail view or in external view then add max-height
		$('#elementRightSide div.value').css('max-height', ((($('.elementDetail table td.center>div.center').height()) / $('#elementRightSide div.value').length)-30)+'px').css('overflow-y','scroll');
	}
	if($('#mainDiv .public').length && $('#mainDiv .public form').length==0){
		$('#elementRightSide div.value').css('max-height', ((($('#mainDiv .public table td.center>div.center').height()) / $('#elementRightSide div.value').length)-30)+'px').css('overflow-y','scroll');
	}

	//Remove max-width
	$('.ui-dialog').css('max-width', '');

//	$('#elementRightSide').unbind('load').bind('load', function(){
//		alert($(this).find('.value').length);
//		$(this).find('.value').css('max-height', ($(window).height()-$(this).position().top) / $(this).find('.value').length).css('overflow-y','scroll');
//	});

//	if(dialogPos['elementDialog']==null){
//		dialogPos['elementDialog'] = new Array();
//		dialogPos['elementDialog'][0] = Math.max(0, $(window).width()-totalWidth-journalItemWidth-50);
//	}
//	dialogPos['elementDialog'][0] = Math.min(0, journalItemWidth+50, dialogPos['elementDialog'][0]);
//	dialogPos['elementDialog'][1] = dialogPos['elementDialog'][1];

}

// Displays a waiting message on file download
function messageDownload(fieldId){
	if(domElementHasWigiiService('#'+fieldId, 'box')) {
		$('#fileDownloadingBar').html(wigii().context.box.fileDownloadingWaitingMsg);
		setVis('fileDownloadingBar', true);	
	}
	else {
		$('#fileDownloadingBar').html(wigii().context.fileDownloadingWaitingMsg);
		/* doesn't show fileDownloadingBar by default, but still restores default message inside */
	}
}

function setListenerToDownloadFile(fieldId, fieldName, src){
	$('#'+fieldId+' .fileDownload').click(function(e){
		messageDownload(fieldId);
		if(domElementHasWigiiService('#'+fieldId, 'box')) setTimeout(function() {setVis('fileDownloadingBar', false);}, 5000); // time out to hide fileDownloadingBar
		e.stopPropagation();
		download(src);
		return false; //if the click is on a link, prevent the link adress to be executed
	});
}
function setListenerToPreviewFile(fieldId, fieldName, src, time){
	$('#'+fieldId+' .value .imgPreview').click(function(e){
		previewImage(src, time);
		messageDownload(fieldId);
		e.stopPropagation();
	});
	$('#'+fieldId+' .value .htmlPreview').click(function(e){
		previewHtml(src, time);
		messageDownload(fieldId);
		e.stopPropagation();
	});
}
function setListenerToUnzipForViewing(fieldId, fieldName, src, time){
	$('#'+fieldId+' .value .htmlPreview').click(function(e){
		previewUnzipForViewing(src, time);
		e.stopPropagation();
	});
}

function setListenerToAddJournalItem(elementDialogId, recordId, fieldName, fieldId, crtLanguage, isHtmlArea, newJournalItemString, okLabel, cancelLabel, urlAction, newJournalContentStringCode){
	//if no elementDialogId that means we are looking at the item directly in the mainDiv
	if(elementDialogId==""){
		elementDialogId="mainDiv";
	}
	//remove the add comment button in case the dialog is readOnly
	//except if allowReadOnly is setup for this field
	if(!$('#'+fieldId+'').hasClass("allowOnReadOnly") && (($('#'+elementDialogId+'>.elementDetail').length==1 && $('#'+elementDialogId+' div.T div.el_edit').length==0) || $('#'+fieldId+'.readOnly').length>0)){
		$('#'+fieldId+' div.label span').remove();
		$('#'+fieldId+' div.label').text($('#'+fieldId+' div.label').text().replace("(", "").replace(")", ""));
		return;
	}
	$('#'+fieldId+' div.label span.addJournalItem').click(function(e){
		emb = $(this);//$('#'+fieldId+' div.label span');
		$(emb).parent().find('.addJournalItemMenu').remove();
		$(emb).after('<div class="addJournalItemMenu SBB ui-corner-all" style="z-index:999999;position:fixed;font-weight:normal;background-color:#fff;top:'+(Math.min(e.pageY-$(window).scrollTop(), $(window).height()-350))+'px; left:'+(Math.min(e.pageX-$(window).scrollLeft(), $(window).width()-420))+'px; padding:5px; " >'+
				'<span class="label" style="float:left;margin-top:5px; margin-bottom:5px;width:400px;" >'+$(emb).parent().text().replace(/\(.*\)/, '')+'</span><div class="clear"></div>'+
				'<textarea id="'+fieldId+'_addJournalItem" style="margin-top:5px; margin-bottom:5px;width:400px;" class="elastic" >'+'</textarea>'+
				'<br />'+
				'<input type="button" name="ok" value="'+okLabel+'" />'+
				'<input type="button" name="cancel" value="'+cancelLabel+'" />'+
			'</div>');
		if(isHtmlArea){
			options = {};
			//in case of a form dialog
			if($('#'+fieldId+' div.value textarea').hasClass('wordlimit')){
				wordlimit = $('#'+fieldId+' div.value textarea').attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1');
				options = $.extend({
					extraPlugins : 'wordcount,undo',
					wordcount_limit : wordlimit,
					wordcount_format : '<span class="cke_path_item">Word count: %count% / %limit%</span>'
				}, options);
			} else if($('#'+fieldId+'').hasClass('wordlimit')){ //if from a detail dialog
				wordlimit = $('#'+fieldId+'').attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1');
				options = $.extend({
					extraPlugins : 'wordcount,undo',
					wordcount_limit : wordlimit,
					wordcount_format : '<span class="cke_path_item">Word count: %count% / %limit%</span>'
				}, options);
			}
			options = $.extend({
				language : crtLanguage,
				scayt_sLang : getScayt_sLangFromLanguage(crtLanguage),
				baseFloatZIndex : $(this).closest('.ui-dialog').css('zIndex')+1,
				toolbar : getBasicCKEditorToolbar(),
				toolbarCanCollapse : true,
				toolbarStartupExpanded : $(emb).parent().parent().find('.activateHtmlArea').length>0,
				width : 400,
				height : 200,
				resize_minWidth : 50,
				startupFocus: true,
				allowedContent: true,
				filebrowserUploadUrl : crtWigiiNamespaceUrl+'/'+crtModuleName+'/CKEditor/upload'
			}, options);

			$(emb).parent().find('.elastic').ckeditor(function(){ }, options);

		} else {
			autosize($(emb).parent().find('.elastic').css('max-height',450).css('min-height',30));
			if($(emb).parent().parent().find('.value textarea').hasClass('wordlimit')){ //in case of a form dialog
				$(emb).parent().find('.elastic').wordlimit({ allowed: $(emb).parent().parent().find('.value textarea').attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1') });
			} else if($(emb).parent().parent().hasClass('wordlimit')){ //if from a detail dialog
				$(emb).parent().find('.elastic').wordlimit({ allowed: $(emb).parent().parent().attr('class').match(/ wordlimit_([0-9]*) /g)[0].replace(/ wordlimit_([0-9]*) /g, '$1') });
			}
			$(emb).parent().find('.elastic').focus();
		}
		$(emb).next().click(function(e){ e.stopPropagation(); return false; });
		$('.addJournalItemMenu input[type="button"]:input[name="cancel"]', $(emb).parent()).click(function(){
			$(this).parent().hide();
		});
		$('.addJournalItemMenu input[type="button"]:input[name="ok"]', $(emb).parent()).click(function(){
			$(this).parent().hide();

			if(urlAction){
				//external access: 'confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/externalAccess/'+crtExternalAccessCode+'/addJournalItem/'+recordId+'/';
				//standard access: 'confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/addJournalItem/'+recordId+'/';
				url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ urlAction;
						var myAjax = new jQuery.ajax({
							type: 'POST',
							url: encodeURI(url),
							success : parseUpdateResult,
							cache:false,
							data: {
								addJournalItemMessage: newJournalItemString.replace(newJournalContentStringCode, $(this).parent().find('textarea').val()),
								elementId: recordId,
								journalFieldName: fieldName,
								elementDialogId: elementDialogId
							},
							error: errorOnUpdate
						});
			} else { //in modify mode, update the textarea
				$('#'+fieldId+' div.value textarea').val(newJournalItemString.replace(newJournalContentStringCode, $(this).parent().find('textarea').val()).replace(/\\n/g, "\n").replace("style=&quot;",'style="').replace("&quot;>",'">')+$('#'+fieldId+' div.value textarea').val());
				if(!isHtmlArea){
					$('#'+fieldId+' div.value textarea').change().blur();
				} else {
//					$('#'+fieldId+' div.value textarea').ckeditorGet().setData(newJournalItemString.replace(newJournalContentStringCode, $(this).parent().find('textarea').val()).replace(/\\n/g, "\n")+$('#'+fieldId+' div.value textarea').val());
//					$('#'+fieldId+' div.value textarea').ckeditorGet().updateElement();
					$('#'+fieldId+' div.value textarea').blur();
				}
			}
		});
		$(emb).next().draggable({ handle:".label" });
		//prevent selecting the textarea content as normally when clicking on label
		e.stopPropagation();
		return false;
	});
}

function setListenerToPreviousVersions(inputPathId, areYouSure){
	$('#'+inputPathId).parent().find('a.previousVersion').mouseenter(function(){
		$(this).parent().find('.deletePreviousVersion').stop().remove();
		$(this).append('<span class=\"deletePreviousVersion\" style=\"font-size:11px;padding:5px;margin-top:-8px;margin-right:-5px;margin-left:0px;cursor:pointer;display:none;\">x</span>');
		$('.deletePreviousVersion', this).show().click(function(e){
			var vToDel = $('#'+inputPathId).parent().find('a.previousVersion:has(span.deletePreviousVersion)');
			jYescancel(areYouSure+':<br /><br />'+$(this).parent().find('span.H').text(), null, function(check){
				if(check===true){
					//click on Ok button
					eval(vToDel.attr('href').replace('javascript:download(\''+SITE_ROOT.replace('//', '\/\/'),'update(\'NoAnswer/').replace('previousVersion', 'deletePreviousVersion'));
					vToDel.fadeOut(800, function(){ vToDel.remove(); });
				} else if(check===null){
					//do nothing and cancel current action
				}
			});
			e.stopPropagation();
			return false;
		});
	}).mouseleave(function(){
		$('.deletePreviousVersion', this).stop().fadeOut(500, function(){ $(this).remove(); });
	});
}
function setListenerToEmailExternalCode(
	elementDialogId,
	emailManageButtonId,
	proofKey,
	proofStatus,
	externalCodeExternalAccess,
	externalAccessEndDate,
	options,
	crtLanguage,
	recordId,
	fieldName,
	email
	){

	//if no elementDialogId that means we are looking at the item directly in the mainDiv
	if(elementDialogId==""){
		elementDialogId="mainDiv";
	}
	$('#'+emailManageButtonId+'').click(function(){
		setVis("busyDiv", true);
		//call server to load html code:
		url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "emailExternalCodeMenu/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/getExternalAccessMenuContent";
		var myAjax = new jQuery.ajax({
				type: 'POST',
				url: encodeURI(url),
				success : function(returnText, textStatus){

	otherRequest = returnText.split(EXEC_answerRequestSeparator);
	returnText = otherRequest[0].split(EXEC_answerParamSeparator);
	otherRequest.shift();
	i=1

	externalAccessMenusendEmailTitle = returnText[i++];
	externalAccessMenuEndDate = returnText[i++];
	okLabel = returnText[i++];
	cancelLabel = returnText[i++];
	subjectLabel = returnText[i++];
	helloBaseText = returnText[i++];
	helloBaseSubject = returnText[i++];
	validationEmailHtml = returnText[i++];
	validationEmailSubject = returnText[i++];
	externalAccessViewEmailHtml = returnText[i++];
	externalAccessViewEmailSubject = returnText[i++];
	externalAccessEditEmailHtml = returnText[i++];
	externalAccessEditEmailSubject = returnText[i++];
	areYouSureToStopExternalAccess = returnText[i++];

	emb = $('#'+emailManageButtonId+'');
	$(emb).parent().find('.externalAccessMenu').remove();
	$(emb).after('<div class="externalAccessMenu SBB ui-corner-all" style="font-weight:normal;background-color:#fff;top:'+(parseInt($(emb).position().top)+parseInt($(emb).outerHeight()))+'px; left:'+($(emb).position().left-170)+'px; padding:5px; position:absolute;" >'+
		'<div style="width:380px;font-weight:bold;">'+externalAccessMenusendEmailTitle+'</div>'+
			options +
			'<div class="endDate" style="cursor:pointer;margin-bottom:5px;margin-top:5px;"><span class="endDate" style="cursor:pointer;margin-right:5px;" onclick="$(this).next().focus();">'+externalAccessMenuEndDate+'</span><input class="endDate" type="text" name="externalAccessEndDate" value="'+externalAccessEndDate+'" /></div>'+
			'<hr class="SBB">'+
			'<span class="grayFont subject" style="display:none;">'+subjectLabel+'&nbsp;</span><input class="subject" type="text" name="subject" value="" style="display:none;margin-top:0px; margin-bottom:5px;" />'+
			'<textarea style="display:none;margin-top:5px; margin-bottom:5px;" class="elastic"></textarea>'+
			'<br />'+
			'<input type="button" name="ok" disabled="on" value="'+okLabel+'" />'+
			'<input type="button" name="cancel" value="'+cancelLabel+'" />'+
		'</div>');
	externalTempCode = null;
	//resize subject input
	$(emb).parent().find('input.subject').width(395-$(emb).parent().find('span.subject').width());

	//hide externalaccess view and edit if element is readOnly	
	if((!isWorkzoneViewDocked() && $('#'+elementDialogId+' div.T div.el_edit').length==0) || (isWorkzoneViewDocked() && $('#searchBar .middleBox div.T div.el_edit').length==0)){
		$('.externalAccessMenu input[value="externalAccessMenuViewLink"]').hide().next().hide().next().hide();
		$('.externalAccessMenu input[value="externalAccessMenuEditLink"]').hide().next().hide().next().hide();
		$('.externalAccessMenu input[value="externalAccessMenuStop"]').hide().next().hide().next().hide();
		$('.externalAccessMenu input[value="externalAccessMenuValidationLink"]').hide().next().hide().next().hide();
	}


	$(emb).parent().find('.endDate').hide();
	$(emb).next().find('input[type="radio"]').click(function(){
		$(this).attr('checked', true);
		$(this).parent().find('input[type="button"]:input[name="ok"]').removeAttr('disabled');
		showCkEditor = false;
		showEndDate = false;
		switch($(this).val()){
			case 'externalAccessMenusendEmail':
				$(this).parent().find('span.stopAccess').remove();
				$(this).parent().find('.elastic').val(helloBaseText);
				$(this).parent().find('input.subject').val(helloBaseSubject);
				externalTempCode = null;
				showEndDate = false;
				showCkEditor = true;
				break;
			case 'externalAccessMenuValidationLink':
				$(this).parent().find('span.stopAccess').remove();
				$(this).parent().find('.elastic').val(validationEmailHtml);
				$(this).parent().find('input.subject').val(validationEmailSubject);
				externalTempCode = proofKey;
				showEndDate = false;
				showCkEditor = true;
				break;
			case 'externalAccessMenuViewLink':
				$(this).parent().find('span.stopAccess').remove();
				$(this).parent().find('.elastic').val(externalAccessViewEmailHtml);
				$(this).parent().find('input.subject').val(externalAccessViewEmailSubject);
				externalTempCode = externalCodeExternalAccess;
				showEndDate = true;
				showCkEditor = true;
				break;
			case 'externalAccessMenuEditLink':
				$(this).parent().find('span.stopAccess').remove();
				$(this).parent().find('.elastic').val(externalAccessEditEmailHtml);
				$(this).parent().find('input.subject').val(externalAccessEditEmailSubject);
				externalTempCode = externalCodeExternalAccess;
				showEndDate = true;
				showCkEditor = true;
				break;
			case 'externalAccessMenuStop':
				if($(this).parent().find('.cke').length>0){
					$(this).parent().find('.elastic').ckeditorGet().destroy();
				}
				$(this).parent().find('.elastic').hide();
				$(this).parent().find('.subject').hide();
				$(this).parent().find('input[type="button"]:input[name="ok"]').before('<span class="stopAccess">'+areYouSureToStopExternalAccess+'<br /><br /></span>');
				externalTempCode = externalCodeExternalAccess;
				showEndDate = false;
				showCkEditor = false;
				break;
		}
		if(showEndDate) {
			$(this).parent().find('input.endDate').datepicker({
					dateFormat: 'dd.mm.yy',
					changeYear: true,
					firstDay:1,
					constrainInput:true,
					showOn:'button'
				})
				.width('75')
				.click(function(){
					$(this).datepicker('hide');
				});
			$(this).parent().find('.endDate').show();
		} else $(this).parent().find('.endDate').hide();

		if(showCkEditor){
			$(this).parent().find('.subject').show();
			$(this).parent().find('.elastic').ckeditor({
				language : crtLanguage,
				scayt_sLang : getScayt_sLangFromLanguage(crtLanguage),
				baseFloatZIndex : $(this).closest('.ui-dialog').css('zIndex')+1,
				toolbar : getBasicCKEditorToolbar(),
				toolbarCanCollapse : true,
				toolbarStartupExpanded : false,
				extraPlugins : 'autogrow',
				autoGrow_minHeight: Math.max(80, $(this).height()),
				width : 400,
				allowedContent: true,
				resize_minWidth : 50,
				startupFocus: true
			});
		}
	});
	$(emb).next().find('input[type="button"]:input[name="cancel"]').click(function(){
		$(this).parent().hide().remove();
	});
	$(emb).next().find('input[type="button"]:input[name="ok"]').click(function(){
		$(this).parent().hide();
		url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ elementDialogId+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/manageEmail/'+recordId+'/';
				var myAjax = new jQuery.ajax({
					type: 'POST',
					url: encodeURI(url),
					success : parseUpdateResult,
					cache:false,
					data: {
						externalMessage: $(this).parent().find('textarea').val(),
						externalSubject: $(this).parent().find('input.subject').val(),
						externalAction: $(this).parent().find('input:radio:checked').val(),
						externalCode: externalTempCode,
						externalEndDate: $(this).parent().find('input.endDate').val(),
						externalElementId: recordId,
						externalFieldName: fieldName,
						externalEmail: email
					},
					error: errorOnUpdate
				});
		});

	if(otherRequest){
		parseUpdateResult(otherRequest.join(EXEC_answerRequestSeparator), textStatus);
	}

				},
				cache:false,
				data: {
					proofKey: proofKey,
					externalCode: externalCodeExternalAccess,
					proofStatus: proofStatus
				},
				error: errorOnUpdate
			});
		onUpdateErrorCounter = 0;

	});
}

/**
 * AutoSave
 */
var crtAutoSaveFieldChanged = {};
var changeAutoSaveFieldForSubmit = {}; //IE do not support submit data in a form { 'changedAutoSaveField' : Array }, we need so to build the object ourself
var tempAutoSaveFormIndex = 0;
function saveField(formId, submitUrlForAutoSave, autoSaveFieldId, labelAutoSaveTrigged, additionalUrlArgument){
	if(arguments.length < 5) additionalUrlArgument = null;
	if($('#'+autoSaveFieldId+' input[name=captcha_code]').length){
		return false;
	}
	setVis('busyDiv', true);
	if($('#'+autoSaveFieldId).hasClass('field')){
		var autoSaveFieldName = autoSaveFieldId.replace(formId+'__', '');
		var autoSaveMesssageTargetId = autoSaveFieldId;
	} else {
		var parentField = $('#'+autoSaveFieldId).parents('.field');
		if(parentField.length > 0) {
			var autoSaveFieldName = parentField.attr('id').replace(formId+'__', '');
			var autoSaveMesssageTargetId = parentField.attr('id');
		}
		else {
			// this path is used when ckeditor is full screen
			parentField = $('#'+autoSaveFieldId).parent().parent();
			if(parentField.attr('id').indexOf(formId+'__') === 0) {
				var autoSaveFieldName = parentField.attr('id').replace(formId+'__', '');
				var autoSaveMesssageTargetId = $('#'+autoSaveFieldId).parent().find('div.cke_contents').attr('id');
			}
			else throw 'cannot find wrapping field name for '+autoSaveFieldId+' in autosave.';
		}
	}

	//store the list of change fields
	crtAutoSaveFieldChanged[autoSaveFieldName] = true;
	changeAutoSaveFieldForSubmit['changedAutoSaveField['+autoSaveFieldName+']']=true;

	var url = SITE_ROOT +submitUrlForAutoSave+'/'+autoSaveFieldName+(additionalUrlArgument ? '/'+additionalUrlArgument:'');

	$('#'+autoSaveMesssageTargetId).prepend('<div class="autoSaveConfirmation ui-widget ui-corner-all" style="position:absolute;margin-left:0px;margin-top:20px;padding:2px;">'+labelAutoSaveTrigged+'<span class="percent"></span></div>');
	asFid = 'tempAutoSaveFileForm'+ (tempAutoSaveFormIndex++);
	//wrap a new form to submit only the specified fields
	$('#'+autoSaveFieldId).wrap('<form id="'+asFid+'" action="'+url+'" enctype="multipart/form-data" method="post" ></form>');
	var hasRemovedDisabledOnSubmit = false;
	if($('#'+autoSaveFieldId).find('.removeDisableOnSubmit[disabled]').andSelf().filter('.removeDisableOnSubmit[disabled]').length){
		$('#'+autoSaveFieldId).find('.removeDisableOnSubmit[disabled]').andSelf().filter('.removeDisableOnSubmit[disabled]').attr('disabled', false);
		hasRemovedDisabledOnSubmit = true;
	}
	$('#'+asFid).ajaxForm({
		success: function(tabReq, textStatus){
			parseUpdateResult(tabReq, textStatus);
		},
		data: { 'autoSaveFieldId':autoSaveFieldId, 'autoSaveMesssageTargetId':autoSaveMesssageTargetId },
		uploadProgress: function(event, position, total, percentComplete) {
	        $('#'+autoSaveMesssageTargetId+' .autoSaveConfirmation .percent').html(' ('+percentComplete+'%)');
	    }, cache:false
	});
	$('#'+asFid).submit();
	//unwrap just after submitting to prevent leaving in form a sub form
	//the timeout is necessary to prevent IE 7-8-9 to not submit the form
	setTimeout(function(){
		if(hasRemovedDisabledOnSubmit){
			$('#'+autoSaveFieldId).find('.removeDisableOnSubmit').andSelf().filter('.removeDisableOnSubmit').attr('disabled', true);
		}
		$('#'+autoSaveFieldId).unwrap();
	}, 10);
}

function autoSaveCKEditor(obj, formId, submitUrlForAutoSave, labelAutoSaveTrigged){
	$(obj).ckeditorGet().updateElement();
	saveField(formId, submitUrlForAutoSave, $(obj).attr('id'), labelAutoSaveTrigged);
	return false;
}
var focusAutoSaveFieldId = 'initial';
var crtAutoSaveFieldDirty = {};
var crtActiveCKEditor = null;
var crtActiveOnlineFileTextContentId = null;
var blockHistoryForOnlineFileTextFieldName = null;
function setListenerForAutoSave(formId, submitUrlForAutoSave, labelAutoSaveTrigged){
	//initialise variables
	focusAutoSaveFieldId = 'initial';
	crtAutoSaveFieldDirty = {};
	crtAutoSaveFieldChanged = {};
	changeAutoSaveFieldForSubmit = {};
	tempAutoSaveFormIndex = 0;

	//mark dirty fields on change
	$('#'+formId+' :input').change(function(){
//		alert('change:'+$(this).parents('.field').attr('id'));
		crtAutoSaveFieldDirty[$(this).parents('.field').attr('id')] = true;
	});

	//on radio button or checkbox save on click
	$('#'+formId+' :radio, #'+formId+' :checkbox').click(function(){
		crtAutoSaveFieldDirty[$(this).parents('.field').attr('id')] = true;
		if(crtAutoSaveFieldDirty[$(this).parents('.field').attr('id')]){
			saveField(formId, submitUrlForAutoSave, $(this).parents('.field').attr('id'), labelAutoSaveTrigged);
			crtAutoSaveFieldDirty[$(this).parents('.field').attr('id')]=false;
		}
	});

	//mark if a field receive the new focus
	onFocus = function(e){
		if($(this).parents('.field').length){
			focusAutoSaveFieldId = $(this).parents('.field').attr('id');
		}
	};
	
	$('#'+formId+' :input').focus(onFocus);

	//on standard fields save on blur if dirty, when changing field
	onBlur = function(){
		var autoSaveFieldId = $(this).parents('.field').attr('id');
		//reinitialise focusAutosaveFieldId to empty
		focusAutoSaveFieldId = '';
		//wait to mark focusAutoSaveFieldId with the new focus
		setTimeout(function(){
			if((focusAutoSaveFieldId != autoSaveFieldId) && crtAutoSaveFieldDirty[autoSaveFieldId]){
				var blockHistory = null;
				if(autoSaveFieldId == formId+'__'+blockHistoryForOnlineFileTextFieldName) {
					blockHistory = 'blockHistory';
					blockHistoryForOnlineFileTextFieldName = null;
				}
				saveField(formId, submitUrlForAutoSave, autoSaveFieldId, labelAutoSaveTrigged, blockHistory);
				crtAutoSaveFieldDirty[autoSaveFieldId]=false;
			}
	    },10);
	};
	$('#'+formId+' :input:not(:radio,:checkbox)').blur(onBlur);

	// select2 dropdowns
	$('#'+formId+' select.flex, '+'#'+formId+' select.chosen')
		.on('select2:open', onFocus)
		.on('select2:close', onBlur);
	
	//CKEditors
	$('#'+formId+' div.value textarea.htmlArea').blur(function(){
		crtActiveCKEditor = null;
		autoSaveCKEditor(this, formId, submitUrlForAutoSave, labelAutoSaveTrigged);
	});
	$('#'+formId+' div.value textarea.htmlArea').focus(function(){
		crtActiveCKEditor = this;
	});

//	CKEDITOR.on('instanceReady', function(evt) {
//	    var editor = evt.editor;
//	    editor.on('focus', function(e) {
//	        //crtActiveCKEditor = $('#'+formId+' #' + e.editor.name);
//	    	crtActiveCKEditor = this;
//	    });
//	});
//	
	// select2 dropdowns event for autosave : http://code.runnable.com/UmuP-67-dQlIAAFU/events-in-select2-for-jquery
	
	//launch auto saving evey 30 seconds
	$('#'+formId).stopTime(formId+'autoSaveFormCKEditors');
	$('#'+formId).everyTime(1000*30, formId+'autoSaveFormCKEditors', function(i){
		// autosaves CKEditor content for html areas
		if(crtActiveCKEditor != null) autoSaveCKEditor(crtActiveCKEditor, formId, submitUrlForAutoSave, labelAutoSaveTrigged);
		// autosaves inline file content from preview dialog
		if(crtActiveOnlineFileTextContentId != null) {
			// gets data from element preview ck editor
			$(crtActiveOnlineFileTextContentId).val($('#elementPreview').find('textarea:first').ckeditorGet().getData());
			// autosaves file content
			var autoSaveFieldId = $(crtActiveOnlineFileTextContentId).parents('.field').attr('id');
			saveField(formId, submitUrlForAutoSave, autoSaveFieldId, labelAutoSaveTrigged,
					(crtActiveOnlineFileTextContentId == '#editElement_form_'+blockHistoryForOnlineFileTextFieldName+'_textContent_textarea' ? 'blockHistory' : null));
			crtAutoSaveFieldDirty[autoSaveFieldId]=false;
		}
	});

	//submit what was the changes made when submit current form
	$('#'+formId).ajaxForm($.extend({}, getAjaxformOption('#'+formId), {
		data: changeAutoSaveFieldForSubmit,
		beforeSerialize:  function(){
			//remove disable for readonly field when submitting the form (part of standard option)
			if($('#'+formId+' .removeDisableOnSubmit').length>0){
				$('#'+formId+' .removeDisableOnSubmit').removeAttr('disabled');
			}
			$('#'+formId).find('form').each(function(){ $(this).children().unwrap(); });
		}
		}));

	//hide cancel button in form
	if($('#'+formId+' .cancel').length){
		$('#'+formId+' .cancel').hide();
	}
	if($('#'+formId).parents('.ui-dialog').find('button.cancel').length){
		$('#'+formId).parents('.ui-dialog').find('button.cancel').hide();
	}
	// Re-initializes middle box with toolbar from dialog box.
	if(isWorkzoneViewDocked()){
		var middleBox = initMiddleBox();
		middleBox.append($('#elementDialog .T').addClass('docked').css({'width':''}));
		$('#searchBar .toolbarBox').hide();
	}
	
}

//
function setListenerForTimeline(formId, fieldId, nbColumn, options){
    var historyFields = options.historyFields;
    historyFields = $.parseJSON(historyFields);
    var d = new Date();
    var date = d.getDate() + '.' + (d.getMonth()+1) + '.' + d.getFullYear();

    $("#"+fieldId+' input').click(function(){
    	if($(this).attr('class') == 'inTimeline'){
            if($(this).prop('checked') == false){
                $(this).prop('checked', false);
            }else{
                $(this).prop('checked', true);
            }

            var index = $(this).parent().index()-1;
            var revIndex = nbColumn - index;
            $(this).parent().parent().children('div .child:lt('+index+')').each(function(){
                var fieldName = $(this).children('input').attr('value');
                var fieldId = formId+'_'+historyFields[fieldName]+'_value_text';

                $(this).children('input').prop('checked', true);

                if(!$('#'+fieldId).attr('value')){
                    $('#'+fieldId).attr('value', date);
                }
            });
            $(this).parent().parent().children('div .child:gt(-'+revIndex+')').each(function(){
                $(this).children('input').prop('checked', false);
            });
            $("#"+fieldId+' .value').children('input').each(function(){
                $(this).prop('checked', false);
            });

            if($(this).prop('checked') == true){
                $(this).prop('checked', true);

                var fieldName = $(this).attr('value');
                var fieldDate = $('#'+fieldName.replace(/ /g , "_")+'_date span');
                var field = formId+'_'+historyFields[fieldName]+'_value_text';

                $(this).attr('id');

                if(!$('#'+field).attr('value')){
                    $('#'+field).attr('value', date);
                    fieldDate.html(date).show();
                }

            }else{
                $("#"+fieldId).prop('checked', false);
            }
		}
    });
}

// called each time the server historizes an online html file.
function actOnHistorizedHtmlFile(fieldName) {
	// blocks further history if CKEditor is still open on the same file during autosave flow
	if(fieldName && crtActiveOnlineFileTextContentId == ('#editElement_form_'+fieldName+'_textContent_textarea')) {
		blockHistoryForOnlineFileTextFieldName = fieldName;
	}
}

/**
*@param String selector jQuery selector to fetch some DOM elements
*@param String serviceNames a space separated string of service names to add to data-wigii-service attribute
*/
function domElementAddWigiiService(selector, serviceNames) {
	
	var existingServices = $(selector).attr("data-wigii-service");
	
	if(existingServices == null){
		existingServices = serviceNames;
	}else{
		var servicesArray = serviceNames.split(" ");
		var servicesList = existingServices.split(" ");
		
		//loop on all element of the both arrays and return true if there are already this element
		//else add the new element in the second array
		for(key1 in servicesArray){
			var exist = false;
			for (key2 in servicesList) {
				
				if(servicesArray[key1] === servicesList[key2]){
					exist = true;
				}
			}
		
			if(!exist){
				existingServices = existingServices.concat(" ".concat(servicesArray[key1]));
			}
		}
	}
	$(selector).attr("data-wigii-service", existingServices);
}
/**
*@param String selector jQuery selector to fetch some DOM elements
*@param String serviceNames a space separated string of service names to remove from data-wigii-service attribute
*/
function domElementRemoveWigiiService(selector, serviceNames){
	var existingServices = $(selector).attr("data-wigii-service");
	var newServices = "";
	
	if(existingServices != null){
		var servicesArray = serviceNames.split(" ");
		var servicesList = existingServices.split(" ");

		for (key in servicesArray) {
			servicesList.splice(servicesList.indexOf(servicesArray[key]),1)
		}
		
		for (key in servicesList) {
			newServices = newServices.concat(" ".concat(servicesList[key]));
		}
		$(selector).attr("data-wigii-service", newServices);
	}
}
/**
*@param String selector jQuery selector to fetch some DOM elements
*@param String serviceName a service name to check if exist in the data-wigii-service attribute
*/
function domElementHasWigiiService(selector, serviceName){
	var existingServices = $(selector).attr("data-wigii-service");
	if(existingServices != null){
		var servicesList = existingServices.split(" ");
			for (key in servicesList) {
				if(servicesList[key] === serviceName){
					return true;
				}
			}
	}
	return false;
}

//$(document).ready(function(){
//	$(".chosen-select").chosen({width: '350px'});
//});


