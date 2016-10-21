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

//Get the height of the top of screen (companyBanner + navigationBar + searchBar (in option))
function getTopHeight(includeSearchBar){
	if(arguments.length<1){
		includeSearchBar = false;
	}
	var height = 0;
	$('#companyBanner').children().each(function(){height += $(this).outerHeight();});
	height += $('#NMContainer').outerHeight();	
	if(includeSearchBar) height += $('#searchBar').outerHeight();
	
	return height;
}

function resize_groupPanel(){
	resize_groupPanel_i = $('#groupPanel>ul');
	fb = $('#footerBar');
	gpT = $('#groupPanel>.keepNotify');
	if(resize_groupPanel_i.length>0){
		resize_groupPanel_i.height($(window).height() - getTopHeight(true) - fb.outerHeight() - gpT.outerHeight()-20);
//		if($('#groupPanel>ul:hidden').length){
//			var tempH = $('#moduleView').height();
//			var tempPad = tempH / 2 -30;
//		} else {
//			var tempH = $(window).height()-resize_groupPanel_i.position().top - fb.outerHeight();
//			var tempPad = tempH / 2 -30;
//		}
//		var tempH = $(window).height()-$('#groupPanel').position().top - fb.outerHeight();
		var tempH = $(window).height() - getTopHeight(true) - fb.outerHeight();
		var tempPad = tempH / 2 -30;

		$('#groupPanel>.collapse').height(tempH-tempPad).css('padding-top',tempPad);
	}
}
$(window).resize(resize_groupPanel);

ElementPListRows_makeHeaders_getTotalWidth = 0;
ElementPListRows_makeHeaders_getNbNoWidth = 0;
ElementPListRows_makeHeaders_getNb = 0;
ElementPListRows_makeHeaders_totalPaddingInCol = 0;
ElementPListRows_scrollWidth = 17;
//those variables are filled in ElementPListRowsForElementList.php at the end of the method makeHeader
function ElementPListRows_makeHeaders(){
	//17 is the scroll bar size
	if(jQuery.browser.msie && version <= 7.0) cor = 2;
	else cor = 0;
	$('#moduleView .list .headerList>div.noWidth').width(Math.max(75, parseInt(($('#moduleView .list').width()-ElementPListRows_scrollWidth-ElementPListRows_makeHeaders_getTotalWidth-(cor*ElementPListRows_makeHeaders_getNb))/ElementPListRows_makeHeaders_getNbNoWidth)-ElementPListRows_makeHeaders_totalPaddingInCol));
	$('#moduleView .list .headerList>div:last').css('padding-right', ElementPListRows_scrollWidth+'px'); //to cover the space of the vertical scroll
}
function resize_elementList(){
	if(isWorkzoneViewMode() && crtModuleName!='Admin' && !$('#elementDialog').hasClass('ui-dialog-content')) resize_workzoneViewDocked();

	resize_elementList_i = $('#moduleView>div.list>div.dataList');
	fb = $('#footerBar');
	if(resize_elementList_i.length>0){
//		ElementPListRows_actOnFinishAddElementP();
//		//this is needed because of a googleChrome bug
		ElementPListRows_makeHeaders();
		tempTableWidth = 0;
		$('#moduleView .list .headerList>div').each(function(){ tempTableWidth += parseInt($(this).outerWidth()); });
//		$('#moduleView .list .dataList table').width($('#moduleView .list').width()-ElementPListRows_scrollWidth); //css('width', '100%'); dosen't work in IE7
		$('#moduleView .list .dataList table').width(tempTableWidth-ElementPListRows_scrollWidth); //css('width', '100%'); dosen't work in IE7
		$('#moduleView .list .dataList table').css('table-layout', 'fixed');
		//alert($(window).height()+" "+ul.position().top+" "+fb.height());
		$('#moduleView>div.list .headerList').width(tempTableWidth);
		if(tempTableWidth > $('#moduleView .list').width()){
			//there is an horizontal scrool bar in the table
			$('#moduleView div.nbItemsInList').css('margin-top', '-42px');
		} else {
			$('#moduleView div.nbItemsInList').css('margin-top', '-25px');
		}

		resize_elementList_i.height($(window).height() - getTopHeight(true) - $('#moduleView > .dataZone.list > .headerList').outerHeight() - fb.outerHeight());

		$('#moduleView .list .dataList').unbind('scroll').scroll(function(){
			$('#moduleView>div.list .headerList').css('margin-left', -$(this).scrollLeft());
		});
	}
	resize_portal();
	resize_coverPage();
	resize_calendar();
	resize_blog();
	if(!isWorkzoneViewMode()||crtModuleName=='Admin'){
		resize_scrollArea(true);
	}
}
$(window).resize(resize_elementList);

function resize_workzoneViewDocked(){
	var collapseSymbole = '&laquo;',
		explodeSymbole = '&raquo;',
		collapseRef = $('#groupPanel>.collapse'),
		elementDialog = $('#elementDialog'),
		moduleView = $('#moduleView'),
		groupPanel = $('#groupPanel'),
		dockingContainer = $('#dockingContainer');
		dockingCollapse = dockingContainer.find('.collapse');
		height = $(window).innerHeight() - getTopHeight(true) - $('#footerBar').outerHeight(),
		availableWidthSpace = $(window).innerWidth();
	
	var dockingCardVisible = $('#elementDialog:visible').length==1,
		windowSize = $(window).innerWidth(),
		scrollSize = $('#scrollElement').width(),
		cardSize = scrollSize + 15,
		groupSize = groupPanel.outerWidth(),
		moduleSize = windowSize - groupSize - cardSize,
		GroupListCollapsed = groupPanel.data('GroupListCollapsed') || 0 ;
		ListViewCollapsed = moduleView.data('ListViewCollapsed') || 0 ;
		collapseMinWidth = moduleView.data('minWidth') || 100;
	//get the same parameters of existing collapse bar
	dockingCollapse.height(collapseRef.height()).css('padding-top',collapseRef.css('padding-top'));
	
	//set width of docked card
	elementDialog.width(cardSize);
	
	//Auto-Collapse
	var userHasClick = groupPanel.data('userClickCollapse') || dockingCollapse.find('span').data('userClickCollapse');
	if(dockingCardVisible) {
		if(GroupListCollapsed == 1 && !userHasClick || moduleSize < collapseMinWidth && !userHasClick) {
			groupPanel.find('ul#group_0').hide();
			groupPanel.find('.keepNotify').hide();
		    positionSelectedGroup("#groupPanel");
		    groupPanel.find('.collapse').html(explodeSymbole);
		    
		    groupSize = groupPanel.outerWidth();
		    moduleSize = windowSize - groupSize - cardSize;
		    
		    if(ListViewCollapsed == 1 && !userHasClick || moduleSize < collapseMinWidth && !userHasClick){
				moduleView.hide();
				dockingCollapse.find('span').html(explodeSymbole);
			}
		}
		
		if(ListViewCollapsed == 1 && !userHasClick) {
			moduleView.hide();
			dockingCollapse.find('span').html(explodeSymbole);
		}
	} else {
		if(!userHasClick) {
			groupPanel.find('ul#group_0').show();
			groupPanel.find('.keepNotify').show();
		    positionSelectedGroup("#groupPanel");
		    groupPanel.find('.collapse').html(collapseSymbole);
		    groupSize = groupPanel.outerWidth();
		}
	}
	groupPanel.data('userClickCollapse',false);
	dockingCollapse.find('span').data('userClickCollapse',false);

	var marginsWidth = elementDialog.outerWidth() - cardSize;//scrollSize;	
	availableWidthSpace -= groupSize;
	
	//When element dialog is not visible or is there empty moduleView is display with the totality of space available
	if(elementDialog.children().length==0 || elementDialog.css('display')=='none') {
		//remove margin size
		availableWidthSpace -= moduleView.outerWidth()-moduleView.width();
		moduleView.width(availableWidthSpace).show();
		return;
	}
	
	//if docking collapse bar is not hidden, we subtract is availableWidthSpace
	if($('#dockingContainer>.collapse:hidden').length==0) availableWidthSpace-= dockingCollapse.outerWidth();
	
	//if moduleView is visible then calculate available space and asign then to it
	if(dockingContainer.find('#moduleView:hidden').length==0) {		
		var remainingWidthSpace = availableWidthSpace - elementDialog.outerWidth(); // + marginsWidth;	
		//if moduleView is too small, we put the minimum and reduce elementDialog
		var moduleViewMinWidth = collapseMinWidth;
		if (remainingWidthSpace <= moduleViewMinWidth) {		
			moduleView.width(moduleViewMinWidth);
			elementDialog.width(availableWidthSpace - marginsWidth - moduleView.outerWidth());
		} else {
			moduleView.width(remainingWidthSpace -15);
			elementDialog.width(cardSize+(marginsWidth/2))
		}
	} else { //if moduleView is hidden calculate the availableWidthSpace
		availableWidthSpace -= marginsWidth;		
		elementDialog.width(availableWidthSpace);
	}	
	elementDialog.height(height + (elementDialog.height()-elementDialog.outerHeight())).css('overflow','auto');
	if(dockingCardVisible){
		$('.firstBox, .toolbarBox','#searchBar').hide();
	}
}

function resize_homePage(){
	resize_homePage_i = $('#workZone #ContainerHome');
	fb = $('#footerBar');
	if(resize_homePage_i.length>0){
		//resize_homePage_i = resize_homePage_i.parent();
		resize_homePage_i.height($(window).height()-resize_homePage_i.position().top - fb.outerHeight()-40); //-40 is because of internal padding
		if($('#workZone #quickStartTabs').length){
			$('#workZone #quickStartTabs').css("min-width", $(window).width()-50-$('#workZone #ContainerHome').outerWidth());
			//$('#workZone #quickStartTabs').height(resize_homePage_i.height());
		}
	}
}
$(window).resize(resize_homePage);

function resize_portal(){
	if($('#moduleView .portal:visible').length > 0 && $('#moduleView .portal>.media').length > 0){
		//$('#searchBar .firstBox, #searchBar .toolbarBox').hide();
		$('#moduleView .portal').css('float', 'left');
		fb = $('#footerBar');
		tempResizePortalHeight = $(window).height()-$('#moduleView').position().top-$('#moduleView .toolBar').height()-$('#moduleView #indicators').height()-fb.outerHeight()-1;
		$('#moduleView .portal').height(tempResizePortalHeight);
		$('#moduleView .portal').width($(window).width()-$('#groupPanel').outerWidth()-4);
		$('#moduleView .portal').css('overflow','hidden');
		$('#moduleView .portal .media>iframe').attr('height', $('#moduleView .portal').height()).attr('width', $('#moduleView .portal').width());
	//} else {
		//$('#searchBar .firstBox, #searchBar .toolbarBox').show();
	}
	resize_groupPanel();
}
function resize_coverPage(){
	if($('#moduleView .portal:visible').length > 0 && $('#moduleView .portal>.media').length == 0){
		$('#moduleView .portal').css('float', 'left');
		fb = $('#footerBar');
		tempResizePortalHeight = $(window).height()-$('#moduleView').position().top-$('#moduleView .toolBar').height()-$('#moduleView #indicators').height()-fb.outerHeight()-1;
		$('#moduleView .portal').height(tempResizePortalHeight);
		$('#moduleView .portal').width($(window).width()-$('#groupPanel').outerWidth()-24); //padding-left and right 10px;
		$('#moduleView .portal').css('overflow','auto');
	}
}

coverPage_toggleList_titleList = null;
coverPage_toggleList_titleWebsite = null;
function coverPage_toggleList(){
	if($('#searchBar .toolbarBox .toggleCoverPage').length==0){
		$('#searchBar .toolbarBox div.addNewElement').after('<div class="toggleCoverPage" style="display:none;" onclick="coverPage_toggleList();"><span class="L H"></span></div>');
	}
	if($('#moduleView .dataZone:visible').length > 0){
		showCoverPage();
	} else {
		hideCoverPage();
	}
}
//when displaying a folder without cover page reset the searchBar as normal
function removeCoverPageItems(){
	hideCoverPage();
	$('#searchBar .toolbarBox .toggleCoverPage').hide();
}
function showCoverPage(){
	$('#moduleView .dataZone').hide();
	$('#searchBar .toolbarBox div:not(.toggleCoverPage,.addNewElement)').hide();
	$('#searchBar .toolbarBox .toggleCoverPage span').text(coverPage_toggleList_titleList).parent().show();
	$('#moduleView .portal').show();
	resize_elementList();
}
function hideCoverPage(){
	$('#moduleView .portal').hide();
	$('#searchBar .toolbarBox .toggleCoverPage span').text(coverPage_toggleList_titleWebsite).parent().show();
	$('#searchBar .toolbarBox div:not(.toggleCoverPage,.addNewElement,.cm,.disabledR,.ui-dialog)').show();
	$('#moduleView .dataZone').show();
	resize_elementList();
}

function isWorkzoneViewMode(){
	return wigii().context.isWorkzoneViewDocked;
}

function fold(id){
	$(id+' ul ul').hide();
	$(id+' span.with-ul-displayed').removeClass('with-ul-displayed').addClass('with-ul-hidden');

}
function unfoldToSelectedGroup(id, selectedClass){
	if(arguments.length < 2){
		selectedClass = "selected";
	}
	//check if groupPanel is collapsed, if yes keep hidding
	var hide = false;
	if(id=="#groupPanel" && $(id+'>ul:hidden').length){
		hide = true;
	}

	//always unfold noRights group
	$(id+' ul:has(li.disabled)').show();
	$(id+' li.disabled>ul').show();
	$(id+' li.disabled>div>span.with-ul-hidden').removeClass('with-ul-hidden').addClass('with-ul-displayed');
	//unfold selected li
	$(id+' ul:has(div.'+selectedClass+')').show();
	$(id+' li.'+selectedClass+'>ul').show();
	$(id+' li:has(li div.'+selectedClass+')>div>span.with-ul-hidden').removeClass('with-ul-hidden').addClass('with-ul-displayed');
	$(id+' li:has(>div.'+selectedClass+')>div>span.with-ul-hidden').removeClass('with-ul-hidden').addClass('with-ul-displayed');
	if(hide){
		$(id+'>ul').hide();
	}
}
function unfoldToSelectedGroupButNotChildren(id, selectedClass){
	if(arguments.length < 2){
		selectedClass = "selected";
	}
	//always unfold noRights group
	$(id+' ul:has(li.disabled)').show();
	$(id+' li.disabled>ul').show();
	$(id+' li.disabled>div>span.with-ul-hidden').removeClass('with-ul-hidden').addClass('with-ul-displayed');
	//unfold selected li
	$(id+' ul:has(div.'+selectedClass+')').show();
	$(id+' li:has(li div.'+selectedClass+')>div>span.with-ul-hidden').removeClass('with-ul-hidden').addClass('with-ul-displayed');
	//$(id+' li:not(:has(li div.'+selectedClass+')).'+selectedClass+'>div>span.with-ul-displayed').addClass('with-ul-hidden').removeClass('with-ul-displayed');
}
function positionSelectedGroup(id, selectedClass){
	if(arguments.length < 2){
		selectedClass = "selected";
	}
	group = $(id+' li.'+selectedClass);
	if(group.length>0){
		p = group.offset().top-$(id+'>ul').offset().top+$(id+'>ul').scrollTop();
		h = $(id+'>ul').innerHeight();
		s = $(id+'>ul').scrollTop();
		d = group.outerHeight();
		//alert(p+' '+h+' '+s+' '+d);
		if(p < s){
			$(id+'>ul').scrollTop(p-10);
		} else if((p+d) > (h + s)){
			$(id+'>ul').scrollTop(Math.min(p-10, p-h+d+10));
		}
	}
}

function prepareOrganizeDialog(callbackOnClick, callbackOnOk){
	$('#organizeDialog').html('<div class="groupPanel"></div><div class="clear"></div>');
	$('#organizeDialog>.groupPanel').html($('#groupPanel>ul').clone(true));
	$('#organizeDialog>.groupPanel span.menu').remove();
	$('#organizeDialog>.groupPanel span.description').remove();
	$('#organizeDialog>.groupPanel div, #organizeDialog>.groupPanel li, #organizeDialog>.groupPanel ul, #organizeDialog>.groupPanel a.H').unbind();
	$('#organizeDialog .disabled').removeClass('disabled');
	$('#organizeDialog .selected').removeClass('selected');
	$('#organizeDialog .found').removeClass('found');
	$('#organizeDialog .hidden').show().removeClass('hidden');
	$('#organizeDialog .empty').removeClass('empty');

	//define heigth
	$('#organizeDialog>.groupPanel>ul').height($('#moduleView').height()-80).show();


	//add the input box before each link
	$('#organizeDialog>.groupPanel li>div>a').before('<span class="checkbox">&nbsp;</span>');

	//add callback on click
	$('#organizeDialog>.groupPanel li>div').click(callbackOnClick);

	fold('#organizeDialog>.groupPanel');

	tempWidth = 450;
	$('#organizeDialog>.groupPanel #group_0').width(tempWidth).css('overflow-x','auto');
	$('#organizeDialog>.groupPanel a').css('white-space', 'nowrap');
	$('#organizeDialog>.groupPanel').css('margin-bottom', 0);
	$('#organizeDialog>.groupPanel>ul').css('height', $('#organizeDialog>.groupPanel>ul').height()-20);

	$('#organizeDialog').dialog({
		width: tempWidth+20,
		buttons: [
			{
				text: DIALOG_okLabel,
				click: callbackOnOk,
				"class": "ok"
			},
			{
				text: DIALOG_cancelLabel,
				click: function(){ $(this).dialog("destroy"); },
				"class": "cancel"
			}
		],
		closeOnEscape: true,
		stack:false,
		resizable:false,
		zIndex:0,
		modal:true
	});
}

function parseKeepNotifiedResult(returnText, textStatus){
	setVis("busyDiv", false);
	otherRequest = returnText.split(EXEC_answerRequestSeparator);
	returnText = otherRequest[0].split(EXEC_answerParamSeparator);
	otherRequest.shift();
	i=1;
	$('#ui-dialog-title-organizeDialog').text(returnText[i++]);

	if(returnText[i++]=="emailAdressIsDefined"){
		$('#organizeDialog>.notifiedIntroduction').remove();
		$('#organizeDialog').prepend('<div class="notifiedIntroduction">'+returnText[i++]+'</div>');

		$('#organizeDialog>.groupPanel').show();
		fold('#organizeDialog>.groupPanel');
		$('#organizeDialog>.groupPanel .selected').removeClass('selected');
		$('#organizeDialog>.groupPanel .originalSelected').removeClass('originalSelected');

		checkedFolders = new Array();
		if(returnText[i]){
			checkedFolders = returnText[i++].split(',');
		}
		for(i=0; i<checkedFolders.length; i++){
			id = checkedFolders[i];
			$('#organizeDialog>.groupPanel #group_'+id+', #organizeDialog>.groupPanel #group_'+id+'>div').addClass('selected originalSelected');
			$('#organizeDialog>.groupPanel #group_'+id+' li').addClass('selected');
			$('#organizeDialog>.groupPanel #group_'+id+' li>div').addClass('selected');
//			.find('li, li>div').addClass('selected');
		}
		//disables no rights groups
		//disables read only groups
		$('#organizeDialog>.groupPanel li.noRights').addClass("disabled noRights");
		$('#organizeDialog>.groupPanel li.noRights>div').addClass("disabled noRights");

//		$('#organizeDialog>.groupPanel li.noRights, #organizeDialog>.groupPanel li.noRights>div.selected').addClass('disabled');
//		alert('add disabled');

		unfoldToSelectedGroupButNotChildren('#organizeDialog>.groupPanel', 'originalSelected');
		positionSelectedGroup('#organizeDialog>.groupPanel');
	} else {
		$('#organizeDialog>.groupPanel').hide();
		$('#organizeDialog>.notifiedIntroduction').remove();
		//display an input box
		$('#organizeDialog').prepend('<div class="notifiedIntroduction">'+returnText[i++]+'</div>');
	}
	if(otherRequest){
		parseUpdateResult(otherRequest.join(EXEC_answerRequestSeparator), textStatus);
	}
}
function actionForKeepNotified(action){
	setVis("busyDiv", true);
	original = new Array();
	actual = new Array();
	if(action=="setKeepNotifiedGroupsForEmail"){
		//the original selection are taged witht the originalSelected class
		$('#organizeDialog li.originalSelected').each(function(){ original[original.length] = $(this).attr('id').split('_')[1]; });
		//actual are all selected with no selected parents
		$('#organizeDialog li.selected').each(function(){ if(!$(this).parent().parent().hasClass('selected')){ actual[actual.length] = $(this).attr('id').split('_')[1]; } });
	}
	url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "organizeDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/"+action;
	var myAjax = new jQuery.ajax({
			type: 'POST',
			url: encodeURI(url),
			success : parseKeepNotifiedResult,
			cache:false,
			data: {
				emailAccountForP: $('#KeepNotifiedEmailInput').val(),
				original: original, //contain the first matching groups
				actual: actual //contain the current most hign li.selected
			},
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;
}
function openKeepNotifiedDialog(){
	//for the keep notified dialog the highest li's are the references
	prepareOrganizeDialog(
		//on click
		function(e){
			if(!$(this).hasClass('disabled')){
				$(this).toggleClass('selected');
				if($(this).hasClass('selected')){
					//select all the sub folders
					$(this).parent().addClass('selected').find('li, li>div').addClass('selected');
				} else {
					//deselect all the folders above
					$(this).parents('.selected').removeClass('selected');
					//deselect all the sub folders
					$(this).parent().find('li').removeClass('selected');
					//clean selected div when li are not selected
					$('#organizeDialog li:not(.selected)>div.selected').removeClass('selected');
				}
			}
			return false;
		},
		//on Ok
		function(){
			if($('#KeepNotifiedEmailInput').length){
				actionForKeepNotified("setKeepNotifiedEmail");
			} else {
				actionForKeepNotified("setKeepNotifiedGroupsForEmail");
				if( $('#organizeDialog').is(':ui-dialog')) { $('#organizeDialog').dialog("destroy"); }
			}
		});

	//fetch current email + subscribed group
	url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "organizeDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/getKeepNotifiedDialogContent/";
	setVis("busyDiv", true);
	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			cache:false,
			success : parseKeepNotifiedResult,
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;
}

function openOrganizeDialog(elementId){
	newCheckedFolders = new Object();
	checkedFolders = new Array();

	prepareOrganizeDialog(
		//on click
		function(e){
			if(!$(this).hasClass('disabled')){
				$(this).toggleClass('selected');
				$('#organizeDialog>div>span.nb').removeClass('zero');
				if($(this).hasClass('selected')){
					newCheckedFolders[$(this).parent().attr('id').split('_')[1]] = $(this).parent().attr('id').split('_')[1];
					$('#organizeDialog>div>span.nb').text(parseInt($('#organizeDialog>div>span.nb').text())+1);
				} else {
					newCheckedFolders[$(this).parent().attr('id').split('_')[1]] = null;
					$('#organizeDialog>div>span.nb').text(parseInt($('#organizeDialog>div>span.nb').text())-1);
					if(parseInt($('#organizeDialog>div>span.nb').text())<1){
						$('#organizeDialog>div>span.nb').addClass('zero');
					}
				}
			}
			return false;
		},
		//on ok, call setGroupsContainingElement
		function(){
			//force at least one selected group
			if(parseInt($('#organizeDialog>div>span.nb').text())==0){
				jAlert(DIALOG_selectAtLeastOneGroup);
				return false;
			}
			url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "confirmationDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/setGroupsContainingElement/"+elementId;
			var myAjax = new jQuery.ajax({
					type: 'POST',
					url: encodeURI(url),
					success : parseUpdateResult,
					cache:false,
					data: {
						original: checkedFolders.join(','),
						actual: object2Array(newCheckedFolders).join(',')
					},
					error: errorOnUpdate
				});
			onUpdateErrorCounter = 0;
			$(this).dialog("destroy");
			//openOrganize dialog currently work only on main item, not on subitems as the groupPanel is not the good one
			$('#elementDialog').prev().find('.ui-dialog-titlebar-close').click();
		}
		);

	//fetch the selected groups
	url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "organizeDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/getGroupsContainingElement/"+elementId;
	setVis("busyDiv", true);
	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			cache:false,
			success : function(returnText, textStatus){
				setVis("busyDiv", false);
				returnText = returnText.split(EXEC_answerRequestSeparator)[0].split(EXEC_answerParamSeparator);
				dialogTitle = returnText[1];
				$('#organizeDialog').prev().find('.ui-dialog-title').text(dialogTitle);
				checkedFolders = returnText[4];
				if(checkedFolders=="") return;
				checkedFolders = checkedFolders.split(",");
				for(i=0; i<checkedFolders.length; i++){
					id = checkedFolders[i];
					newCheckedFolders[id] = id;
					$('#organizeDialog>.groupPanel #group_'+id+', #organizeDialog>.groupPanel #group_'+id+'>div').addClass('selected');
				}
				nbSelected = returnText[2];
				//nbSelected reflects the complete list of folders the item is into.
				//however this number should reflect only the list of visible folders, to prevent the user "loosing" the item
				//by unselecting the last folder visible by this user
				nbSelected = $('#organizeDialog>.groupPanel li.selected').length;
				textSelected = returnText[3];
				$('#organizeDialog').prepend('<div class="introduction"><span class="nb">'+nbSelected+'</span>&nbsp;<span>'+textSelected+'</span></div>');

				//disables read only groups
				$('#organizeDialog>.groupPanel li:not(.write)').addClass("disabled noRights");
				$('#organizeDialog>.groupPanel li:not(.write)>div').addClass("disabled noRights");

				unfoldToSelectedGroupButNotChildren('#organizeDialog>.groupPanel');

				positionSelectedGroup('#organizeDialog>.groupPanel');
			},
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;
}

function openOrganizeMultipleDialog(){
	prepareOrganizeDialog(
		//on click
		function(e){
			if(!$(this).hasClass('disabled')){
				if($(this).hasClass('wasMixed')){
					if($(this).hasClass('mixed')){
						$(this).add($(this).parent()).removeClass('selected').removeClass('mixed');
					} else if($(this).hasClass('selected')){
						$(this).add($(this).parent()).removeClass('selected').addClass('mixed selected');
					} else {
						$(this).add($(this).parent()).addClass('selected');
					}
				} else if($(this).hasClass('wasSelected')){
					$(this).add($(this).parent()).toggleClass('selected');
				} else if($(this).hasClass('wasUnselected')){
					$(this).add($(this).parent()).toggleClass('selected');
				}
			}
			return false;
		},
		//on ok, call setGroupsContainingElement
		function(){
			setVis('busyDiv', true);
			changeToSelected = new Array();
			$('#organizeDialog li.wasMixed.selected:not(.mixed), #organizeDialog li.wasUnselected.selected').each(function(){
				changeToSelected[changeToSelected.length] = $(this).attr('id').replace('group_', '');
			});
			changeToUnselected = new Array();
			$('#organizeDialog li.wasMixed:not(.selected), #organizeDialog li.wasSelected:not(.selected)').each(function(){
				changeToUnselected[changeToUnselected.length] = $(this).attr('id').replace('group_', '');
			});

			url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/setGroupsContainingElements/multiple';
			var myAjax = new jQuery.ajax({
					type: 'POST',
					url: encodeURI(url),
					success : parseUpdateResult,
					cache:false,
					data: {
						changeToUnselected: changeToUnselected.join(','),
						changeToSelected: changeToSelected.join(',')
					},
					error: errorOnUpdate
				});
			onUpdateErrorCounter = 0;
		}
		);

	//fetch the selected groups
	url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'organizeDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/getGroupsContainingElements/multiple';
	setVis('busyDiv', true);
	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			cache:false,
			success : function(returnText, textStatus){
				setVis('busyDiv', false);
				returnText = returnText.split(EXEC_answerRequestSeparator)[0].split(EXEC_answerParamSeparator);
				dialogTitle = returnText[1];
				$('#organizeDialog').prev().find('.ui-dialog-title').text(dialogTitle);
				eval(returnText[2]); //fill var originalFolders
				totalElementsChecked = parseInt($('#multipleDialog .summary .multipleSelectionNb').text());
				//result = '';
				for(i in originalFolders){
					groupId = i;
					isSelected = originalFolders[i]==totalElementsChecked;
					isMixed = originalFolders[i] && !isSelected;
					//result += 'group '+groupId+' is mixed '+isMixed+' and is selected '+isSelected+' | ';
					//always add selected even for mixed to make the li blue
					$('#organizeDialog>.groupPanel #group_'+groupId+', #organizeDialog>.groupPanel #group_'+groupId+'>div').addClass('selected');
					if(isMixed){
						$('#organizeDialog>.groupPanel #group_'+groupId+', #organizeDialog>.groupPanel #group_'+groupId+'>div').addClass('mixed wasMixed');
					} else if(isSelected){
						$('#organizeDialog>.groupPanel #group_'+groupId+', #organizeDialog>.groupPanel #group_'+groupId+'>div').addClass('wasSelected');
					}
				}
				$('#organizeDialog>.groupPanel li:not(.selected), #organizeDialog>.groupPanel li>div:not(.selected)').addClass('wasUnselected');
				//disables read only groups
				$('#organizeDialog>.groupPanel li:not(.write)').addClass('disabled noRights');
				$('#organizeDialog>.groupPanel li:not(.write)>div').addClass('disabled noRights');
				unfoldToSelectedGroupButNotChildren('#organizeDialog>.groupPanel');
				positionSelectedGroup('#organizeDialog>.groupPanel');
			},
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;
}

function removeElementInList(elementId){
	//if the item was selected, then remove the higliths in the group panel
	if($("#moduleView #row_"+elementId).hasClass('S')){
		$('#groupPanel div.highlight').removeClass('highlight');
	}
	$("#moduleView #row_"+elementId).remove();
}

function addElementInList(elementId){
	//check if element is in List (if for example adding with isKey feature
	if($("#moduleView #row_"+elementId).length){
		updateElementInList(elementId);
	} else {
		url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "row_"+elementId+"/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/addElementInList/"+elementId;
		setVis("busyDiv", true);
		var myAjax = new jQuery.ajax({
				url: encodeURI(url),
				cache:false,
				success : function(returnText, textStatus){

					setVis("busyDiv", false);
					otherRequest = returnText.split(EXEC_answerRequestSeparator);
					returnText = otherRequest[0].split(EXEC_answerParamSeparator);
					otherRequest.shift();
					i=1;

					//for blogView or any view implementing the folder list in the groupList
					if($('#moduleView .groupList').length>0){
						$('#moduleView .groupList').after(returnText[i++]);
					} else {
						$('#moduleView tr.folder:last').after(returnText[i++]);
					}

					if(otherRequest){
						parseUpdateResult(otherRequest.join(EXEC_answerRequestSeparator), textStatus);
					}

				},
				error: errorOnUpdate
			});
		onUpdateErrorCounter = 0;
	}
}
function updateElementInList(elementId){
	update("NoResponse/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/updateElementInList/"+elementId);
}
//elementIds must be ids separated with -
function updateElementsInList(elementIds){
	update("NoResponse/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/updateElementsInList/"+elementIds);
}

function unselectGroups(id, selectedClass){
	if(arguments.length < 2){
		selectedClass = "selected";
	}
	$(id+' li.'+selectedClass+', '+id+' li.'+selectedClass+'>div.'+selectedClass+'').removeClass(selectedClass);
	//don't need to change the location in this case as this function is always followed by an other one setting the location to the relevant value
	//self.location = '#'+crtWigiiNamespaceUrl+"/"+crtModuleName+"/folder/0";
}
function clickOnGroupInGroupPanel(id){
	$('#groupPanel #group_'+id+'').click();
}
function selectGroupInGroupPanel(){
	unselectGroups('#groupPanel');
	for(i=0; i<arguments.length; i++){
		id = arguments[i];
		$('#group_'+id+', #group_'+id+'>div').addClass('selected');
		//hide subfolders management in list if no subfolders
		if($('#group_'+id+' ul').length==0){
			$('#moduleView div.subFoldersContent, #moduleView div.subFoldersList').hide();
		} else {
			$('#moduleView div.subFoldersContent, #moduleView div.subFoldersList').show();
		}
		//hide parentGroup folder if parent group has no rights
		if($('#group_'+id+'').parent().parent().hasClass('noRights')){
			$('#moduleView div.folderUp').addClass('disabled');
		} else {
			$('#moduleView div.folderUp').removeClass('disabled');
		}
	}
	unfoldToSelectedGroup('#groupPanel');
	positionSelectedGroup('#groupPanel');
	resize_elementList();
	self.location = '#'+crtWigiiNamespaceUrl+"/"+crtModuleName+"/folder/"+id;
}
function selectGroup(groupId, id, selectedClass){
	if(arguments.length < 3){
		selectedClass = "selected";
	}
	unselectGroups(id, selectedClass);
	$('#group_'+groupId+', #group_'+groupId+'>div').addClass(selectedClass);
	unfoldToSelectedGroup(id, selectedClass);
	positionSelectedGroup(id, selectedClass);
}

function setListenersToGroupTree(id, selectedClass){
	if(arguments.length < 2){
		selectedClass = "selected";
	}
	//add click on context menu:
	$(id+' li:not(.disabled) .menu').click(function(e){
		$(id+' .over').removeClass('over');
		$(this).closest('div').addClass('over');
		positionElementOnDom($(id+'>.cm'), $(this), 'fromLeft', 5, false, true);
		//show hide some buttons:
		$(id+'>.cm div.write, '+id+'>.cm div.admin').hide();
		if($(this).parent().parent().hasClass('readOnly')){
			$(id+'>.cm div.readOnly').show();
		}
		if($(this).parent().parent().hasClass('share')){
			$(id+'>.cm div.share').show();
		}
		if($(this).parent().parent().hasClass('write')){
			$(id+'>.cm div.write').show();
		}
		if($(this).parent().parent().hasClass('admin')){
			$(id+'>.cm div.admin').show();
		}
		if($(this).parent().parent().hasClass('level1')){
			$(id+'>.cm div.level1').hide();
		}
		// hides empty group on trashbin until proper recursive deletion of files is coded (ticket 12796)
		if($(this).parent().parent().hasClass('trashbin')){
			$('#cm_emptyGroup').hide();
		}
		$(id+'>.cm').show();
		e.stopPropagation();
	});
	$(id+'>.cm').mouseleave(function(){
		$(id+' li>div').removeClass('over');
		$(this).hide();
	});
	$(id+'>.cm').click(function(e){
		$(id+' li>div').removeClass('over');
		$(this).hide();
		e.stopPropagation();
	});
	$(id+'>.cm>div').bind('contextmenu', function(e){ //disable the right click on context menu to prevent displaying context menu on right click
		e.stopPropagation();
		return false;
	});
	//add click on li: select folder
	$(id+' li>div>a.H').click(function(e){
		$(this).parent().click();
		e.stopPropagation();
		return false;
	});
	$(id+' li:not(.disabled)').click(function(e){
		selectGroup($(this).attr('id').split('_')[1], id, selectedClass);
		var span = $('>div>span.with-ul-hidden', this);
		if(span.length>0){
			span.click();
		}
		e.stopPropagation(); //very important to not click on the parent on the same time
	});
	$(id+' li:not(.disabled)').bind('contextmenu', function(e){
		$('>div>.menu', this).click();
//		positionElementOnDom($(id+'>.cm'), $(this), 'fromLeft', 5);
//		$(id+'>.cm').show();
		e.stopPropagation();
		return false;
	});
	//add click on folder: folde, unfold or select
	$(id+' li:not(.disabled) span.folder').click(function(e){
		var ul = $(this).parent().nextAll('ul');
		if(ul.length>0){
			$(this).toggleClass('with-ul-hidden').toggleClass('with-ul-displayed');
			ul.toggle();
		} else {
			//if no children then click on the parent
			$(this).parent().click();
		}
		e.stopPropagation();
	});
	$(id+' li>div>a.H').hover(function(){
		hideHelp();
		showHelp($(this), $(this).nextAll('.description').html(), 20, "fromCenter", 500);
//		positionElementOnDom($(this).nextAll('.description'), $(this), 'fromCenter', 20, false, true);
//		$(this).nextAll('.description').show();
	}, function(){
		hideHelp();
//		$(id+' .description').hide();
	});
	//add folders class
	$(id+' li>div>span.folder')
		.addClass('without-ul');
	$(id+' li:has(ul)>div>span.folder')
		.removeClass('without-ul')
		.addClass('with-ul-displayed');
	//the hidden selector could not work in the case of displaying in a dialog box which is not showned yet
//	$(id+' li:has(>ul:hidden)>div>span.folder')
	$(id+' li:has(>ul.n)>div>span.folder')
		.removeClass('without-ul')
		.removeClass('with-ul-displayed')
		.addClass('with-ul-hidden');

	setListnerForGroupDragAndDrop(id, id+' li.admin:not(.level1)', id+' li.write>div', selectedClass);
}


//this should be called as well on setListenerOnListRows...
function setListnerForGroupDragAndDrop(id, draggableSelector, dropableSelector, selectedClass){
	if(arguments.length < 4){
		selectedClass = "selected";
	}

	//add dragable for groups
	$(draggableSelector).draggable(
		{
			scroll: true,
			cursorAt: { top: 5, left: -10 },
			helper: function( event ) {
				var helpText = '';
				if($(this).hasClass('folder')){
					helpText = $(this).text();
				} else {
					helpText = $(this).find('>div>a').text();
				}
				return $( '<div class="ui-widget ui-corner-all SBIB" style="padding:5px;">'+helpText+"</div>" );
			}
		}
	);
	//add dropable for folder
	$(dropableSelector).droppable({
		accept: "#moduleView .dataList tr:not(.folder), " +
				"#moduleView .dataList tr.folder.admin:not(.level1), " +
				"#moduleView .dataBlog div.el, " +
				"#moduleView .dataBlog div.folder.admin:not(level1), " +
				""+id+" li.admin",
		hoverClass: "SBIB",
		tolerance: "pointer",
		greedy: true,
		drop: function( event, ui ) {
			if(ui.draggable.hasClass('admin')){
				//droping a folder
				var myUiDraggable = ui.draggable; //folder
				var helpText = '';
				var myThisDroppable = null;
				if($(this).hasClass('folder')){
					myThisDroppable = $(this); //folder
					helpText = myThisDroppable.text();
				} else {
					myThisDroppable = $(this).parent(); //folder
					helpText = myThisDroppable.find('>div>a').text();
				}

				jYescancel(DIALOG_doYouWantToMoveThisFolderUnderParent+helpText, null, function(check){
					if(check===true){
						//click on Ok button
						//simulate a edit group post
						setVis("busyDiv", true);
						$.post(
							"UPDATE/"+crtContextId+"/__/elementDialog/"+crtWigiiNamespaceUrl+"/Admin/groupEdit/"+crtModuleName+"/"+myUiDraggable.attr('id').split('_')[1]+"/"+id.replace('#',''),
							{
								//only post the groupParent to not change the other values....
								'isInitiatedFromDragDrop': true,
								'groupParent_value': myThisDroppable.attr('id').split('_')[1],
								'idForm': 'editGroup_form',
								'action': 'check'
							},
							function(tabReq, textStatus){
								parseUpdateResult(tabReq, textStatus);
								if(id.replace("#","")=="groupPanel"){
									clickOnGroupInGroupPanel(myUiDraggable.attr('id').split('_')[1]);
								} else {
									selectGroup(myUiDraggable.attr('id').split('_')[1], id, selectedClass);
								}
							}
						);
						//alert("folder "+myUiDraggable.attr('id').split('_')[1]+" is moved in folder "+myThisDraggable.parent().attr('id').split('_')[1]);
						//cancel current action
					} else if(check===null){
						//do nothing and cancel current action
					}
				});
			} else {
				//droping an element
				var myUiDraggable = ui.draggable; //element
				var helpText = '';
				var myThisDroppable = null;
				if($(this).hasClass('folder')){
					myThisDroppable = $(this); //folder
					helpText = myThisDroppable.text();
				} else {
					myThisDroppable = $(this).parent(); //folder
					helpText = myThisDroppable.find('>div>a').text();
				}

				$.alerts.noButton = DIALOG_keepInBoth;
				$.alerts.okButton = DIALOG_move;
				jConfirm('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+DIALOG_doYouWantToMoveOrKeepInBoth+helpText+'?&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', null, function(check){
					if(check===true){
						//click on Move button
						if(myUiDraggable.hasClass('M')){
							//droping elements
							setVis("busyDiv", true);
							$.post(
								"UPDATE/"+crtContextId+"/__/confirmationDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/setGroupsContainingElements/multiple/drag",
								{
									'changeToSelected': myThisDroppable.attr('id').split('_')[1],
									'changeToUnselected' : ($(id+' li.selected').attr('id')?$(id+' li.selected').attr('id').split('_')[1]:'all')
								},
								parseUpdateResult
							);
							//alert("move multiple selection in folder "+myThisDraggable.parent().attr('id').split('_')[1]);
						} else {
							//droping an element
							setVis("busyDiv", true);
							$.post(
								"UPDATE/"+crtContextId+"/__/confirmationDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/setGroupsContainingElement/"+myUiDraggable.attr('id').split('_')[1]+"/drag",
								{
									'actual': myThisDroppable.attr('id').split('_')[1],
									'original' : ($(id+' li.selected').attr('id')?$(id+' li.selected').attr('id').split('_')[1]:'all')
								},
								parseUpdateResult
							);
							//alert("move element "+myUiDraggable.attr('id').split('_')[1]+" in folder "+myThisDraggable.parent().attr('id').split('_')[1]);
						}
						//cancel current action
					} else if(check===null){
						//do nothing and cancel current action
					} else if(check===false){
						//do keep both button
						if(myUiDraggable.hasClass('M')){
							//droping elements
							setVis("busyDiv", true);
							$.post(
								"UPDATE/"+crtContextId+"/__/confirmationDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/setGroupsContainingElements/multiple/drag",
								{
									'changeToSelected': myThisDroppable.attr('id').split('_')[1],
									'changeToUnselected' : ''
								},
								parseUpdateResult
							);
//							alert("add sharing multiple selection in folder "+myThisDraggable.parent().attr('id').split('_')[1]);
						} else {
							//droping an element
							setVis("busyDiv", true);
							$.post(
								"UPDATE/"+crtContextId+"/__/confirmationDialog/"+crtWigiiNamespaceUrl+"/"+crtModuleName+"/element/setGroupsContainingElement/"+myUiDraggable.attr('id').split('_')[1]+"/drag",
								{
									'actual': myThisDroppable.attr('id').split('_')[1],
									'original' : ''
								},
								parseUpdateResult
							);
							//alert("add sharing of element "+myUiDraggable.attr('id').split('_')[1]+" in folder "+myThisDraggable.parent().attr('id').split('_')[1]);
						}
					}
				});
				$.alerts.noButton = '&nbsp;No&nbsp;';
				$.alerts.okButton = '&nbsp;Ok&nbsp;';
			}
			event.stopPropagation();
		}
	});
}
crtGroupSelectorId = null;
crtMultipleSelectionDialogKeep = false;
function setListenersToGroupPanel(doYouWantToRemoveYouMultipleSelectionText){
	//add click on keep notified
	if($('#keepNotifyButton').length){
		$('#keepNotifyButton').click(openKeepNotifiedDialog);
	}

	$('#groupPanel li.noRights, #groupPanel li.noRights>div').addClass('disabled');

	setListenersToGroupTree('#groupPanel');

	//add click on collapse div
	if((jQuery.browser.msie && parseFloat(jQuery.browser.version.split(".").slice(0,2).join(".")) == 9.0)){
		$('#groupPanel>.collapse').remove();
	} else {
		$('#groupPanel>.collapse').click(function(){
			$("#groupPanel").data('userClickCollapse',true);
			if($('#groupPanel>ul#group_0:hidden').length){
				$('#groupPanel>ul#group_0').show();
				$('#groupPanel>.keepNotify').show();
				positionSelectedGroup("#groupPanel");
				$(this).html("&laquo;");
			} else {
				$('#groupPanel>ul#group_0').hide();
				$('#groupPanel>.keepNotify').hide();
				$(this).html("&raquo;");
			}

			resize_elementList();
			resize_coverPage();
			resize_portal();
		});
	}
	
	$('#dockingContainer>.collapse').click(function(){
//	$('#dockingContainer>.collapse span').click(function(){
		var docking = $('#dockingContainer'),
			moduleView = $('#moduleView');
//		$(this).data('userClickCollapse',true);
		$(this).find('span').data('userClickCollapse',true);
		if(docking.find('#moduleView:hidden').length){
			moduleView.show();
//			$(this).html("&laquo;");
			$(this).find('span').html("&laquo;");
		} else {
			moduleView.hide();
//			$(this).html("&raquo;");
			$(this).find('span').html("&raquo;");
		}

		resize_elementList();
		resize_coverPage();
		resize_portal();
	});

	//add resize on fold/unfold folder
	$('#groupPanel li span.folder').click(function(){
		resize_elementList();
	});
	//add click on li: select folder
	$('#groupPanel li').click(function(e){
		resize_elementList();
		var idGroup = $(this).attr('id').split('_')[1];
		self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/folder/'+idGroup;
		//if there is a multiple selection dialog box open, ask if we want to keep the current multiple selection
//		alert($('#multipleDialog .multipleSelectionNb').text());
		if(!crtMultipleSelectionDialogKeep && crtGroupSelectorId != idGroup && $('#multipleDialog .multipleSelectionNb').length && $('#multipleDialog .multipleSelectionNb').text()!="0"){
			$.alerts.noButton = '&nbsp;Keep&nbsp;';
			$.alerts.okButton = '&nbsp;Discard&nbsp;';
			var crtGroupHref = $('>div>a.H', this).attr('href').replace('#','');
			jConfirm(doYouWantToRemoveYouMultipleSelectionText, null, function(check){
				if(check===true){
					//click on Ok button
					$('.ui-dialog-titlebar-close', $('#multipleDialog').closest('.ui-dialog')).click();
					updateThroughCache('moduleView', crtGroupHref, 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+idGroup+'', 1, 1);
				} else if(check===null){
					//do nothing and cancel current action
					idGroup = crtGroupSelectorId;
					$('#groupPanel #group_'+idGroup).click();
				} else if(check===false){
					//keep multiple selection
					crtMultipleSelectionDialogKeep = true;
					updateThroughCache('moduleView', crtGroupHref, 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+idGroup+'', 1, 1);
				}
				crtGroupSelectorId = idGroup;
			});
			$.alerts.noButton = '&nbsp;No&nbsp;';
			$.alerts.okButton = '&nbsp;Ok&nbsp;';
		} else {
			updateThroughCache('moduleView', $('>div>a.H', this).attr('href').replace('#',''), 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+idGroup+'', 1, 1);
			crtGroupSelectorId = idGroup;
		}
		e.stopPropagation();
	});
	//add click on root ul if some root groups are available
	$('#groupPanel ul#group_0').click(function(){
		if($(this).children('li:not(.noRights)').length>0){
			unselectGroups('#groupPanel');
			var idGroup = +$(this).attr('id').split('_')[1];
			self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/folder/'+idGroup;
			updateThroughCache('moduleView', $(this).attr('href').replace('#',''), 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+idGroup+'', 1, 1);
		} else {
			//if no groups selected then select the first group available
			$('li:not(.noRights):first').click();
		}
	});

	//add click on context menu:
	$('#groupPanel>.cm>div').click(function(){
		var groupItem = $('#groupPanel li>div.over').parent();
		var idGroup = groupItem.attr('id').split('_')[1];

		if($(this).attr('id')=="cm_exit") return true;
		if($(this).attr('id')=="cm_select"){
			groupItem.click();
			return true;
		}
		
		var responseDiv = 'elementDialog';
		if(isWorkzoneViewMode()) responseDiv = 'confirmationDialog';
		
		if($(this).attr('id')=="cm_findDuplicatesIn"){
			//this action is special as it take the current context to find duplicates.
			//so it is good to first load the current folder
			groupItem.click();
			update('organizeDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/findDuplicatesIn/');
			return true;
		}
		if($(this).attr('id')=="cm_add"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/add/'+idGroup);
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_renameGroup"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupEdit/'+crtModuleName+'/'+idGroup+'/groupPanel');
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_deleteGroup"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupDelete/'+crtModuleName+'/'+idGroup+'/groupPanel');
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_emptyGroup"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupEmpty/'+crtModuleName+'/'+idGroup+'/groupPanel');
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_createSubGroup"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupNew/'+crtModuleName+'/'+idGroup+'/groupPanel');
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_copyGroup"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/Admin/groupNewCopy/'+crtModuleName+'/'+idGroup+'/groupPanel');
			groupItem.click();
			return true;
		}
		if($(this).attr('id')=="cm_groupDetail"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/Admin/groupDetail/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
		if($(this).attr('id')=="cm_portal"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/Admin/groupPortal/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
		if($(this).attr('id')=="cm_htmlContent"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/Admin/groupHtmlContent/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
		if($(this).attr('id')=="cm_subscription"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupSubscription/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
		if($(this).attr('id')=="cm_emailNotification"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupEmailNotification/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
		if($(this).attr('id')=="cm_xmlPublish"){
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/Admin/groupXmlPublish/'+crtModuleName+'/'+idGroup+'/groupPanel');
			return true;
		}
	});
}

function setListenersToIndicator(){
	$('#indicators .closeIndicators').click(function(){
		$('#indicators div.indicator').hide();
		$('#indicators div.closeIndicators').hide();
		$('#indicators div.showIndicators').show();
		update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/closeIndicators');
	});
	$('#indicators .showIndicators').click(function(){
		update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/showIndicators');
	});
	$('#indicators div.indicator').mouseenter(function(){
		$('#indicators div.indicator .closeIndicator').stop().remove();
		$(this).append('<div class=\"closeIndicator\" style=\"font-size:11px;float:right;padding:3px;margin-top:-8px;margin-right:-5px;margin-left:2px;cursor:pointer;display:none;\">x</div>');
		$('.closeIndicator', this).show().click(function(){
			$(this).parent().fadeOut(800, function(){ $(this).remove(); });
			update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/closeIndicator/'+$(this).parent().attr('id'));
		});
	});
	$('#indicators div.indicator').mouseleave(function(){
		$('.closeIndicator', this).stop().fadeOut(500, function(){ $(this).remove(); });
	});
}

menuTimeout = new Object();
function setListenersToMenu(buttonId, menuId, dialogTarget, action, actionParameter, clickToOpen){
	if(arguments.length<5 || actionParameter==null){
		actionParameter = "";
	}
	if(arguments.length<6){
		clickToOpen = false;
	}
	if(!clickToOpen){
		$('#'+buttonId).mouseenter(function(e){
			clearTimeout(menuTimeout[menuId]);
			var handler = $(this);
			menuTimeout[menuId] = setTimeout(function(){
				positionElementOnDom($('#'+menuId), handler, 'fromLeft', 26, true);
				var winH = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
				$('#'+menuId).css('overflow-y','auto').css('max-height',winH-20);
				$('#'+menuId).show().scrollTop(0);
			},150);
			e.stopPropagation();
		}).click(function(e){
			e.stopPropagation();
			e.preventDefault();
			return false;
		});
	} else {
		$('#'+buttonId).click(function(e){
			clearTimeout(menuTimeout[menuId]);
			positionElementOnDom($('#'+menuId), $(this), 'fromLeft', 26);
			var winH = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
			$('#'+menuId).css('overflow-y','auto').css('max-height',winH-20);
			$('#'+menuId).show().scrollTop(0);
			e.stopPropagation();
			e.preventDefault();
			return false;
		});
	}
	$('#'+buttonId).mouseleave(function(e){ clearTimeout(menuTimeout[menuId]); menuTimeout[menuId] = setTimeout(function(){ $('#'+menuId).hide();}, 300); });
	$('#'+menuId)
		.mouseleave(function(){
			clearTimeout(menuTimeout[menuId]); menuTimeout[menuId] = setTimeout(function(){ $('#'+menuId).hide();}, 300);
		}).mouseenter(function(){ clearTimeout(menuTimeout[menuId]); });
	$('#'+menuId+'>div').click(function(e){
			if(!$(this).hasClass('exit')){
				if(dialogTarget!=null && action!=null){
					update(dialogTarget+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/'+action+'/'+$('a:first', this).attr('href').replace('#', '')+'/'+actionParameter);
				}
			}
			clearTimeout(menuTimeout[menuId]);
			$('#'+menuId).hide();
			e.stopPropagation();
			return false;
		});
}

//listSelector: #moduleView .list
//elSelector: #moduleView .dataList tr.el
//folderSelector : #moduleView .dataList tr.folder
//cmSelector :  #moduleView .list>.cm
function setListenersToRows(listSelector, elSelector, folderSelector, cmSelector){

	if(arguments.length<4){	var cmSelector = '#moduleView .list>.cm'; }
	if(arguments.length<3){	var folderSelector = '#moduleView .dataList tr.folder'; }
	if(arguments.length<2){	var elSelector = '#moduleView .dataList tr.el'; }
	if(arguments.length<1){	var listSelector = '#moduleView .list'; }

	//make element draggable in foders if write on element and if write on folder
	var dragHandler = '.dragHandler';
	if($(elSelector+' .dragHandler').length==0){
		dragHandler = null;
	}
	$(elSelector+':not(.readOnly)').draggable(
			{
				scroll: true,
				cursorAt: { top: 5, left: -10 },
				handle: dragHandler,
				helper: function( event ) {
					if($(this).hasClass('M')){
						return $( '<div class="ui-widget ui-corner-all SBIB" style="padding:5px;">'+$('#multipleDialog .summary').html()+"</div>");
					}
					return $( '<div class="ui-widget ui-corner-all SBIB" style="padding:5px;">'+$(this).children().map(function(){ if($(this).text() && $(this).is(':visible')) { return $(this).text(); } else { return null; } }).get().join(" ").substring(0,30)+"...</div>" );
				}
			}
		);

	setListnerForGroupDragAndDrop('#groupPanel', folderSelector+'.admin:not(.level1)', folderSelector+'.write');


	$(elSelector).unbind('click');
	$(elSelector).click(function(e){
		$(elSelector).removeClass('S');
		$(this).addClass('S');
		idItem = $(this).attr('id').split('_')[1];
		self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/item/'+idItem;
		updateThroughCache('elementDialog', $(this).attr('href').replace('#', ''), 'elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/detail/'+idItem+'');
	});
	$(elSelector).unbind('contextmenu');
	$(elSelector).bind("contextmenu", function(e) {
		hideHelp();
		$('.menu', this).click(); //.trigger('click', e);
		positionElementOnMouse($(cmSelector), e, 'fromRight', null, 5);
		//$(cmSelector).css('left', e.clientX-5+parseInt($(document).scrollLeft()));
		//$(cmSelector).css('top', e.clientY-5+parseInt($(document).scrollTop()));
	    return false;
	});

	//add info bull preview of content of cell
	ModuleView_dataListIsMouseDrag = false;
	$('#moduleView .dataList tr.el>td>div').unbind();
	$('#moduleView .dataList tr.el>td>div')
		.mouseenter(function(e){
			if($(this).hasClass('helpWidth')){
				w = $(this).attr('class').match(/ help_(.*) /g)[0].replace(/ help_(.*) /g, '$1');
			} else {
				w = Math.min(400, Math.max(200, $(this).width())); //take the width of the column min 200 max 400
			}
			showHelp(this, $(this).html(), 30, 'fromLeft', 500, w, 60000, e);
		})
		.mouseleave(function(e){ hideHelp(); })
		.click(function(e){
			if(e.ctrlKey){
				ModuleView_dataListIsMouseDrag = true;
				if($('#summaryDialog:visible').length==0){
					if(!$('#summaryDialog').hasClass('ui-draggable')){
						$('#summaryDialog').draggable({ handle: '.handler', cursor: 'crosshair'});
//						$('#summaryDialog>textarea').autosize();
					}
					$('#summaryDialog').show();
				}
				if($('#summaryDialog>textarea.content').val()!=""){
					$('#summaryDialog>textarea.content').val($('#summaryDialog>textarea.content').val()+", ");
				}
				$('#summaryDialog>textarea.content').val($('#summaryDialog>textarea.content').val()+$(this).text()).focus();
				$('#summaryDialog>textarea.content').scrollTop('9999999');
				e.stopPropagation();
				var sel ;
				if(document.selection && document.selection.empty){
					document.selection.empty() ;
				} else if(window.getSelection) {
					sel=window.getSelection();
				if(sel && sel.removeAllRanges)
					sel.removeAllRanges() ;
				}
				return false;
			} else {
				ModuleView_dataListIsMouseDrag = false;
			}
		});


	//!!the display or not of the subFolders button is managed in the selectGroupInGroupPanel() JS function!!!

	//prevent click propagation on file downloads
	$(elSelector+' div.value div.file a').unbind();
	$(elSelector+' div.value div.file a').click(function(e){ e.stopPropagation(); });

	//add click on context menu:
	$(elSelector+' div.max').unbind();
	$(elSelector+' div.max').click(function(e){
		var el = $(this).closest(elSelector);
		if(el.hasClass('max')){
			el.removeClass('max');
			el.css('min-width',0);
			resize_elementList();
		} else {
			el.addClass('max');
			el.css('min-width',el.width());
			el.width('auto');
		}
		e.stopPropagation();
	});

	//add click on context menu:
	$(elSelector+' div.menu').unbind();
	$(elSelector+' div.menu').click(function(e){
		$(this).closest(elSelector).addClass('over');
		positionElementOnDom($(cmSelector), $(this), 'fromRight', 5);
		//show hide some buttons:
		elEnableState = $('.elEnableState', this).html();
		elState = $('.elState', this).html();

		if($(this).parent().parent().hasClass('readOnly')){
			$(cmSelector+' div.write').hide();
		} else {
			$(cmSelector+' div.write').show();
		}
		if($('#moduleView .toolBar .addNewElement.disabledBg').length>0){
			$(cmSelector+' #cm_addElementInList').hide();
		} else {
			$(cmSelector+' #cm_addElementInList').show();
		}
		// if blocked, then hides modify, delete and organize
		if(elState & 2) {
			$(cmSelector+' #cm_edit').hide();
			$(cmSelector+' #cm_delete').hide();
			$(cmSelector+' #cm_organize').hide();
		}
		// enables lock
		if(elEnableState & 1) {
			s = $(cmSelector+' #cm_lock');
			// checks or unchecks menu entry
			if(elState & 1) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_lock').hide();
		// enables block
		if(elEnableState & 2) {
			s = $(cmSelector+' #cm_block');
			// checks or unchecks menu entry
			if(elState & 2) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_block').hide();
		// enables important1
		if(elEnableState & 4) {
			s = $(cmSelector+' #cm_state_important1');
			// checks or unchecks menu entry
			if(elState & 4) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_state_important1').hide();
		// enables important2
		if(elEnableState & 8) {
			s = $(cmSelector+' #cm_state_important2');
			// checks or unchecks menu entry
			if(elState & 8) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_state_important2').hide();
		// enables finalize
		if(elEnableState & 16) {
			s = $(cmSelector+' #cm_finalize');
			// checks or unchecks menu entry
			if(elState & 16) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_finalize').hide();
		// enables approve
		if(elEnableState & 32) {
			s = $(cmSelector+' #cm_approve');
			// checks or unchecks menu entry
			if(elState & 32) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_approve').hide();
		// enables dismiss
		if(elEnableState & 64) {
			s = $(cmSelector+' #cm_dismiss');
			// checks or unchecks menu entry
			if(elState & 64) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_dismiss').hide();
		// enables archived
		if(elEnableState & 128) {
			s = $(cmSelector+' #cm_state_archived');
			// checks or unchecks menu entry
			if(elState & 128) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_state_archived').hide();
		// enables deprecated
		if(elEnableState & 256) {
			s = $(cmSelector+' #cm_state_deprecated');
			// checks or unchecks menu entry
			if(elState & 256) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_state_deprecated').hide();
		// enables hide
		if(elEnableState & 512) {
			s = $(cmSelector+' #cm_hide');
			// checks or unchecks menu entry
			if(elState & 512) s.addClass('checked').removeClass('unchecked');
			else s.addClass('unchecked').removeClass('checked');
		}
		else $(cmSelector+' #cm_hide').hide();
		
		// hides delete if non admin and enableDeleteOnlyForAdmin=1
		if(elEnableState & 1024) {
			$(cmSelector+' #cm_delete').hide();
		}

		$(cmSelector).show();
		e.stopPropagation();
	});

	//add click on multiple select
	//this needs to include as well the folders, not only the elements
	$(elSelector+' div.mul').unbind();
	$(elSelector+' div.mul, '+folderSelector+' div.mul').click(function(e){
		if($(this).parent().parent().hasClass('folder')){
			clickFolderLineInMultipleSelectionMode($(this).closest(folderSelector));
		} else {
			clickLineInMultipleSelectionMode($(this).closest(elSelector));
		}
		e.stopPropagation();
	});

	//make new lines, no more new
	$(listSelector+' .new').removeClass("new");

}
function setListenersToElementList(){

	//add click on multiple select All
	$('#moduleView .list .headerList div.mul').click(function(e){
		$(this).addClass('M');
		selectAllMultipleSelect();
		setTimeout(function(){ $('#moduleView .list .headerList div.mul').removeClass('M'); }, 500);
		e.stopPropagation();
	});

	//add click on folder up
	$('#moduleView .dataList tr.folder div.folderUp').click(function(e){
		$('#groupPanel #group_'+$(this).parent().parent().attr('id').split('_')[1]).click();
		e.stopPropagation();
	});

	//add click on folder line
	$('#moduleView .dataList tr.folder').click(function(e){
		$('#groupPanel #group_'+$(this).attr('id').split('_')[1]).click();
		e.stopPropagation();
	});
	//add click on see subFolders content
	$('#moduleView .dataList tr.folder div.subFoldersContent').click(function(e){
		tempGroupId = $('#groupPanel li.selected');
		if(tempGroupId.length>0){
			tempGroupId = tempGroupId.attr('id').split('_')[1];
		} else {
			tempGroupId = 0;
		}
		updateThroughCache('moduleView', $(this).attr('href').replace('#', ''), 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroupAndChildren/'+tempGroupId+'', 0, 1);
		e.stopPropagation();
	});
	//add click on reset to subFolder list
	$('#moduleView .dataList tr.folder div.subFoldersList').click(function(e){
		if($('#groupPanel li.selected').length>0){
			$('#groupPanel li.selected').click();
		} else {
			$('#groupPanel ul#group_0').click();
		}
		e.stopPropagation();
	});

	//!!the display or not of the subFolders button is managed in the selectGroupInGroupPanel() JS function!!!

	//set listener to list header
	$('#moduleView .headerList>div:not(.noSorting)').click(function(){
		//define the fieldName
		fieldName = $(this).attr('class');
		fieldName = fieldName.match('key_([^\ ]*)');
		if(!fieldName) return;
		fieldName = fieldName[1];
		//take off for all other headers the sorting class
		$('#moduleView .headerList>div').not($(this)).removeClass('DESC').removeClass('ASC');
		if(!($(this).hasClass('ASC')) && !($(this).hasClass('DESC'))){
			if($(this).hasClass('defaultSorted')){
				sortedDir = $(this).attr('class').match('defaultSorted_([^\ ]*)')[1];
				$(this).addClass(sortedDir);
			} else {
				$(this).addClass('ASC');
			}
		} else {
			$(this).toggleClass('DESC').toggleClass('ASC');
		}
		update('moduleView/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/changeSortByKey/'+fieldName+'/'+$(this).hasClass('ASC'));
	});
	
	//make a global variable to store the rezise column
	window.customHeaderSize = [];
	//Add a rezise event on each column with classname begin by 'key_'
	$('#moduleView .headerList>div[class^="key_"]').resizable({
		handles: 'e',
		create: function( event, ui ) {
			// Initialize the resize
            // Prefers an another cursor with two arrows
            $(".ui-resizable-e").css("cursor","col-resize").click(function(event){
            	//Stop the propagation of mouse click in sort column
            	event.stopImmediatePropagation();
            });
        },
		start: function(event, ui) {
			// Loop into each header div to remove the noWidth class attribut
			$('#moduleView .headerList>div').each(function( index ) {
				  $(this).removeClass('noWidth');
			});
	    },
		resize: function (event, ui){
			var unitOfMeasure = 'px';
			//retrieve the position of an element relative to its parent
			var pos = $('#moduleView .headerList>div').index(this);
			this.style.width = ui.size.width+'px';
			//change the size of cols in colgroup
			var colgroup = $('.dataList colgroup').children();
			colgroup[pos].style.width = ui.size.width + 17 + unitOfMeasure; //add 17px beacause it's the différance of size
	
			//restore the other custom size, this function is to bypass bizarre behavior of jquery
			var currentField = getFieldName(this);
			if(window.customHeaderSize) {
				for (var key in window.customHeaderSize) {
					if(key != currentField) window.customHeaderSize[key].obj.style.width = window.customHeaderSize[key].width + unitOfMeasure;
				}
			}
		},
		stop: function(event, ui) {
			//Store the custom size of column in variable
			var fieldName = getFieldName(this);
			window.customHeaderSize[fieldName] = {"obj": this, "width": ui.size.width};
			this.style.width = ui.size.width;
			update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/saveListViewUIPref/'+fieldName+'/width/'+ui.size.width);
			//Invoke the methode of resizing window
			resize_elementList();
	    }
	});
	
	//function to extract the field name
	function getFieldName(header) {
		var fieldName = header.className;
		fieldName = fieldName.match('key_([^\ ]*)');
		fieldName = fieldName[1].replace(/(value)|__element/i, '');
		fieldName = fieldName.replace('(','').replace('(','');
		fieldName = fieldName.replace(')','').replace(')','');
		return fieldName;
	}
	
	//set listeners to contextMenu
	$('#moduleView .list>.cm').mouseleave(function(){
		$('#moduleView .dataList tr').removeClass('over');
		$(this).hide();
	});
	$('#moduleView .list>.cm').click(function(){
		$('#moduleView .dataList tr').removeClass('over');
		$(this).hide();
	});
	$('#moduleView .list>.cm>div').click(function(){
		if($(this).attr('id')=="cm_exit") return true;
		if($(this).attr('id')=="cm_open"){
			$('#moduleView .dataList tr.el.over').click();
			return true;
		}
		idItem = $('#moduleView .dataList tr.el.over').attr('id').split('_')[1];
		if($(this).attr('id')=="cm_addElementInList"){
			$('#searchBar .toolbarBox .addNewElement').click();
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/add/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			return true;
		}
		//select the current line
		$('#moduleView .dataList tr.S').removeClass('S');
		$('#row_'+idItem).addClass('S');
		self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/item/'+idItem;
		// toggles checkbox value
		if($(this).hasClass('unchecked')) {
			checked = 1;
			$(this).removeClass('unchecked').addClass('checked');
		}
		else if($(this).hasClass('checked')) {
			checked = 0;
			$(this).removeClass('checked').addClass('unchecked');
		}
		else checked = -1;

		if($(this).attr('id')=="cm_edit"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+idItem);
			return true;
		}
		if($(this).attr('id')=="cm_delete"){
			var responseDiv = 'elementDialog';
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+idItem+'/elementDialog');
			if(isWorkzoneViewMode()) responseDiv = 'confirmationDialog';
			update(responseDiv+'/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+idItem+'/elementDialog');
			return true;
		}
		if($(this).attr('id')=="cm_copy"){
			copyWithOrganize('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName, idItem);
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/copy/'+idItem+'/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			return true;
		}
		if($(this).attr('id')=="cm_organize"){
			openOrganizeDialog(idItem);
			return true;
		}
		if($(this).attr('id')=="cm_lock"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/locked/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_block"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/blocked/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_important1"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/important1/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_important2"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/important2/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_finalize"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/finalized/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_approve"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/approved/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_dismiss"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/dismissed/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_archived"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/archived/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_deprecated"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/deprecated/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_hide"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/hidden/'+checked+'/');
			return true;
		}
	});

	//set listeners to loadMoreLines
	setListenersToLoadMoreLines("getNextElementInList", "#moduleView .list", "#moduleView .list .dataList table tbody");

	setListenersToRows();
}

function setListenersToElementBlog(){

	//add click on folder line
	$('#moduleView .dataBlog div.groupList div.folder:not(.subFoldersContent, .subFoldersList)').click(function(e){
		$('#groupPanel #group_'+$(this).attr('id').split('_')[1]).click();
		e.stopPropagation();
	});
	//add click on see subFolders content
	$('#moduleView .dataBlog div.groupList div.subFoldersContent').click(function(e){
		tempGroupId = $('#groupPanel li.selected');
		if(tempGroupId.length>0){
			tempGroupId = tempGroupId.attr('id').split('_')[1];
		} else {
			tempGroupId = 0;
		}
		updateThroughCache('moduleView', $(this).attr('href').replace('#', ''), 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroupAndChildren/'+tempGroupId+'', 0, 1);
		e.stopPropagation();
	});
	//add click on reset to subFolder list
	$('#moduleView .dataBlog div.groupList div.subFoldersList').click(function(e){
		if($('#groupPanel li.selected').length>0){
			$('#groupPanel li.selected').click();
		} else {
			$('#groupPanel ul#group_0').click();
		}
		e.stopPropagation();
	});

	//!!the display or not of the subFolders button is managed in the selectGroupInGroupPanel() JS function!!!

	//set listeners to contextMenu
	$('#moduleView .blog>.cm').mouseleave(function(){
		$('#moduleView .dataBlog div.el').removeClass('over');
		$(this).hide();
	});
	$('#moduleView .blog>.cm').click(function(){
		$('#moduleView .dataBlog div.el').removeClass('over');
		$(this).hide();
	});
	$('#moduleView .blog>.cm>div').click(function(){
		if($(this).attr('id')=="cm_exit") return true;
		if($(this).attr('id')=="cm_open"){
			$('#moduleView .dataBlog div.el.over').click();
			return true;
		}
		idItem = $('#moduleView .dataBlog div.el.over').attr('id').split('_')[1];
		if($(this).attr('id')=="cm_addElementInList"){
			$('#searchBar .toolbarBox .addNewElement').click();
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/add/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			return true;
		}
		//select the current line
		$('#moduleView .dataBlog div.S').removeClass('S');
		$('#row_'+idItem).addClass('S');
		self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/item/'+idItem;
		// toggles checkbox value
		if($(this).hasClass('unchecked')) {
			checked = 1;
			$(this).removeClass('unchecked').addClass('checked');
		}
		else if($(this).hasClass('checked')) {
			checked = 0;
			$(this).removeClass('checked').addClass('unchecked');
		}
		else checked = -1;

		if($(this).attr('id')=="cm_edit"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+idItem);
			return true;
		}
		if($(this).attr('id')=="cm_delete"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+idItem+'/elementDialog');
			return true;
		}
		if($(this).attr('id')=="cm_copy"){
			copyWithOrganize('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName, idItem);
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/copy/'+idItem+'/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			return true;
		}
		if($(this).attr('id')=="cm_organize"){
			openOrganizeDialog(idItem);
			return true;
		}
		if($(this).attr('id')=="cm_lock"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/locked/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_block"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/blocked/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_important1"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/important1/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_important2"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/important2/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_finalize"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/finalized/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_approve"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/approved/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_dismiss"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/dismissed/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_archived"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/archived/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_state_deprecated"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/deprecated/'+checked+'/');
			return true;
		}
		if($(this).attr('id')=="cm_hide"){
			if(checked >= 0) update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/'+idItem+'/hidden/'+checked+'/');
			return true;
		}
	});

	setListenersToLoadMoreLines("getNextElementInBlog", "#moduleView .blog", "#moduleView .blog .dataBlog");

	setListenersToRows('#moduleView .blog', '#moduleView .dataBlog div.el', '#moduleView .dataBlog div.folder', '#moduleView .blog>.cm');
}

function setListenersToLoadMoreLines(action, selector, appendToSelector){
	//set listeners to loadMoreLines
	$(selector+'>.nbItemsInList>.loadMoreLines').click(function(){
		url = SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+ crtWigiiNamespaceUrl+"/"+crtModuleName+"/"+action+"/";
		setVis("busyDiv", true);
		page = parseInt($(selector+'>.nbItemsInList>.page').text())+1;
		$(selector+'>.nbItemsInList>.page').text(page);
		var myAjax = new jQuery.ajax({
				type: 'POST',
				url: encodeURI(url),
				cache:false,
				data:{
					page: page
					},
				success : function(returnText, textStatus){
					setVis("busyDiv", false);
					data = returnText.split('_X_NBNEWROWS_X_');
					nbLoaded = parseInt(data[1]);
					jsCode = data[2];
					data = data[0];
					$(selector+'>.nbItemsInList>.nb').text(parseInt($(selector+'>.nbItemsInList>.nb').text())+nbLoaded);
					if(data =='' || parseInt($(selector+'>.nbItemsInList>.nb').text()) == parseInt($(selector+'>.nbItemsInList>.total').text())){
						$(selector+'>.nbItemsInList').html($(selector+'>.nbItemsInList>.nb').html()+$(selector+'>.nbItemsInList>.type').html());
					}
					$(appendToSelector).append(data);

					if(jsCode){
						eval(jsCode.replace('<script id="JSCode" type="text/javascript" > $(document).ready(function(){ ', '').replace('}); </script>', ''));
					}

					//update the cache content
					updateCurrentModuleViewCache();
				},
				error: errorOnUpdate
			});
		onUpdateErrorCounter = 0;
	});
}

function matchSubFoldersInElementListToGroupPanel(){
	//hide folder if hidden in groupPanel
	$('#moduleView .dataList tr.folder').each(function(e){
		if(!$('#groupPanel #group_'+$(this).attr('id').split('_')[1]+':visible').length){
			$(this).hide();
		} else {
			tempHtml = $('#groupPanel #group_'+$(this).attr('id').split('_')[1]+'>div>span.nb').html();
			if(tempHtml){
				//alert(tempHtml);
				$('div.folder', this).next().append('<span class="R nb">'+tempHtml+'</span>');
			}
		}
	});
}

/*******************
 * Element Detail
 ******************/

function displayLink(anchor, link, marginTop, marginLeft){
	if(arguments.length < 3){
		marginTop = 30;
		marginLeft = 0;
	}
	if($(anchor).find('.sendLinkInput').length==1){
		//if sendLinkInput already exist do nothing
	} else {
		link = link.replace(" ", "%20");
		$(anchor)
		.prepend('<div class="sendLinkInput ui-corner-all SBB" style="background-color:#fff;z-index:9999999;position:absolute;float:left;margin-top:'+marginTop+'px;margin-left:'+marginLeft+'px;padding:5px;width:425px;"><input type="text" style="float:left;margin:0px;padding:2px;width:400px;" value="'+link+'" /><div class="H" style="float:left;font-size:x-small;margin-left:8px;"> X </div></div>')
		.find('input').select().next().click(function(e){ $(this).parent().remove(); e.stopPropagation(); return false; });
	}
}
function mailToFromLink(elementDialogId, link){
	if($('#'+elementDialogId+'').parent().find('.sendLinkInput').length==1){

	} else {
		link = link.replace(" ", "%20");
		if(isWorkzoneViewMode()) {
			$('#'+elementDialogId+'').prepend('<div class="sendLinkInput ui-corner-all SBB" style="background-color:#fff;z-index:9999999;position:absolute;left:'+($('#searchBar .el_sendLink').position().left+$('#searchBar .el_sendLink').outerWidth()-425)+'px;top:'+getTopHeight(true)+'px;padding:5px;width:425px;"><input type="text" style="float:left;margin:0px;padding:2px;width:400px;" value="'+link+'" /><div class="H" style="float:left;font-size:x-small;margin-left:8px;"> X </div></div>').find('input').select().next().click(function(e){ $(this).parent().remove(); e.stopPropagation(); return false; });
		} else {
			$('#'+elementDialogId+'').parent().prepend('<div class="sendLinkInput ui-corner-all SBB" style="background-color:#fff;z-index:9999999;position:absolute;float:left;margin-top:20px;margin-left:'+($('#'+elementDialogId+'').width()-425)+'px;padding:5px;width:425px;"><input type="text" style="float:left;margin:0px;padding:2px;width:400px;" value="'+link+'" /><div class="H" style="float:left;font-size:x-small;margin-left:8px;"> X </div></div>').find('input').select().next().click(function(e){ $(this).parent().remove(); e.stopPropagation(); return false; });
		}
	}
	//link = link.replace('#', '#').replace(' ', '%2520');
	//window.location = 'mailto:?body=Access document with: '+link+' .';
}

/*
 * copyWithOrganize, Copy a card with a dialog box organize to choose where you want to put the copy
 * it is in a function to share the code with the button in the card and the right click on list view item
 */
function copyWithOrganize(startUrl, itemID){
	var targetFolder = $('#groupPanel li.selected.write');
	if(targetFolder.length>0) targetFolder = targetFolder.attr('id').split('_')[1];
	else targetFolder = undefined;
	var initialFolder = targetFolder;
	//var currentFolderId = ($('#groupPanel li.selected').length>0)?targetFolder:null;
	prepareOrganizeDialog(
		//on click
		function(e){
			if(!$(this).hasClass('disabled')){
				$('#organizeDialog .selected').removeClass('selected');
				$(this).toggleClass('selected');
				targetFolder = $(this).parent().attr('id').split('_')[1];
			}
			return false;
		}, 
		//on Ok
		function(){
			if(!targetFolder) {
				jAlert(DIALOG_selectAtLeastOneGroup);
				return false;
			}
			if( $('#organizeDialog').is(':ui-dialog')) { $('#organizeDialog').dialog("destroy"); }			
			// opens copy dialog
			// if folder changed, then navigates to new folder
			invalidCache("moduleView");
			if(targetFolder != initialFolder) startUrl = 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupSelectorPanel/selectGroup/'+targetFolder+EXEC_requestSeparator+startUrl;
			update(startUrl+'/element/copy/'+itemID+'/'+targetFolder);
		});
	$('#organizeDialog').prev().find('.ui-dialog-title').text(DIALOG_copyToFolder);
	$('#organizeDialog').prepend('<div class="introduction">'+DIALOG_copyToFolder_help+'</div>');
	
	//disables read only groups
	$('#organizeDialog>.groupPanel li:not(.write)').addClass("disabled noRights");
	$('#organizeDialog>.groupPanel li:not(.write)>div').addClass("disabled noRights");
	if(targetFolder) $('#organizeDialog>.groupPanel #group_'+targetFolder+', #organizeDialog>.groupPanel #group_'+targetFolder+'>div').addClass('selected');
	unfoldToSelectedGroupButNotChildren('#organizeDialog>.groupPanel');
	positionSelectedGroup('#organizeDialog>.groupPanel');	
	if(targetFolder) {		
		// sets focus on OK button to manage pressing enter to copy directly in current folder
		$('#organizeDialog').next().find('button.ok').focus();
	}	
}

function setListenersToElementDetail(elementDialogId, useWigiiNamespaceUrl, useModuleName, isSubItem, parentModuleName, elementParentId, elementLinkName, parentCacheLookup){
	if(arguments.length < 4){
		isSubItem = false;
		parentModuleName = null;
		elementParentId = null;
		elementLinkName = null;
		cacheId = null;
	}
	if(arguments.length < 2){
		var useWigiiNamespaceUrl = crtWigiiNamespaceUrl;
		var useModuleName = crtModuleName;
	}
	//if no elementDialogId that means we are looking at the item directly in the mainDiv
	if(elementDialogId==""){
		elementDialogId="mainDiv";
	}
	//if dialog lookup in dialog title, if main div lookup in T
	$('#'+elementDialogId+' .T>div, #'+elementDialogId+' .T>a').add($('a', $('#'+elementDialogId+'').prev())).click(function(){
		if($(this).hasClass('el_edit')){
			update(''+elementDialogId+'/'+useWigiiNamespaceUrl+'/'+useModuleName+'/element/edit/'+$(this).parent().attr('href').replace('#', ''));
			if(isWorkzoneViewMode()) {
				$('#searchBar .middleBox div.T').children().remove();
			}
			return;
		}
		if($(this).hasClass('el_copy')){
			var itemID = $(this).parent().attr('href').replace('#', '');			
			if(isSubItem){
				update(''+elementDialogId+'/'+useWigiiNamespaceUrl+'/'+useModuleName+'/subelement/copy/'+elementParentId+'/'+elementLinkName+'/'+itemID);
			} else {
				copyWithOrganize(''+elementDialogId+'/'+useWigiiNamespaceUrl+'/'+useModuleName, itemID);
				//update(''+elementDialogId+'/'+useWigiiNamespaceUrl+'/'+useModuleName+'/element/copy/'+$(this).parent().attr('href').replace('#', '')+'/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			}
			return;
		}
		if($(this).hasClass('el_organize')){
			openOrganizeDialog($(this).parent().attr('href').replace('#', ''));
			return;
		}
		if($(this).hasClass('el_status')){
			//positionElementOnDom($('#elementStatusMenu'), $(this), 'fromLeft', 26);
			if(isWorkzoneViewMode()){
				$('#searchBar .elementStatusMenu').show();
			} else {
				$('#'+elementDialogId+' .elementStatusMenu').show();
			}
			return;
		}
		if($(this).hasClass('el_delete')){
			update('confirmationDialog/'+useWigiiNamespaceUrl+'/'+useModuleName+'/element/delete/'+$(this).parent().attr('href').replace('#', '')+'/'+elementDialogId);
			return;
		}
		if($(this).hasClass('el_feedback')){
			update('feedbackDialog/'+useWigiiNamespaceUrl+'/'+useModuleName+'/giveFeedback/element/'+$(this).attr('href').replace('#', ''));
			return false;
		}
		if($(this).hasClass('el_back')){
			if(parentCacheLookup){
				updateThroughCache('elementDialog', parentCacheLookup, 'elementDialog/'+useWigiiNamespaceUrl+'/'+useModuleName+'/element/detail/'+elementParentId+'');
			} else {
				update('elementDialog/'+useWigiiNamespaceUrl+'/'+useModuleName+'/element/detail/'+elementParentId+'');
			}
			return false;
		}
		if($(this).hasClass('el_printDetails')){
			//is a real link
			return;
		}
		if($(this).hasClass('el_sendLink')){
			//window.location = 'mailto:?body='+$(this).attr('href').replace('#', '').replace(' ', '%2520');
			return;
		}
		
		if(isWorkzoneViewMode() && $(this).hasClass('el_closeDetails')){
			manageWorkzoneViewDocked('hide');
			return;
		}
		
	});
	
	if(isWorkzoneViewMode()){ //move control to serchBar middleBox
		var middleBox = $('#searchBar .middleBox');
		if(middleBox.length > 0) {
			middleBox.remove();
			needToResize = false;
		};
		var height = $('#searchBar .firstBox').after('<div class="middleBox"></div>').css('display','none').height();
		middleBox = $('#searchBar .middleBox').height(height);
		middleBox.append($('#elementDialog .T').addClass('docked').css({'width':''}));
		$('#searchBar .toolbarBox').hide();
		resize_groupPanel();
		resize_elementList();
		resize_calendar();
		resize_blog();
		resize_workzoneViewDocked();			
	}

	$('#'+elementDialogId+' .elementStatusMenu')
		.mouseleave(function(){
			$(this).hide();
		});
	var elementDialogIdForStatus = elementDialogId;
	if(isWorkzoneViewMode()) elementDialogIdForStatus = 'searchBar';
		
	$('#'+elementDialogIdForStatus+' .elementStatusMenu>div').click(function(e){
			if(!$(this).hasClass('exit')){
				$(this).toggleClass('checked');
				if($(this).hasClass('checked')){
					val = "1";
				} else {
					val = "0";
				}
				update(elementDialogId+'/'+useWigiiNamespaceUrl+'/'+useModuleName+'/setElementState/'+$('#'+elementDialogIdForStatus+' .T').attr('href').replace('#', '')+'/'+$(this).attr('href').replace('#', '')+'/'+val);
			} else {
				$('#'+elementDialogIdForStatus+' .elementStatusMenu').hide();
			}
			e.stopPropagation();
		});

	$('#'+elementDialogId+' .elementHistoric>div.label').click(function(){
		$(this).toggleClass('expanded').toggleClass('collapsed');
		$(this).next().toggle();
		if($(this).hasClass("expanded")){
			$(window).scrollTop($(window).scrollTop() +$('#'+elementDialogId+' .elementHistoric').height());
			if(!isWorkzoneViewMode()) resize_scrollArea(true);
		}
	});

	hrefWithSiteroot2js(elementDialogId, elementDialogId);

	//show Sysinfo on mouse right click on label
	$('#'+elementDialogId+' div.field .label').bind('contextmenu', function(e){
		$(this).parent().find(">.addinfo").show();
		return false;
	});
	$('#'+elementDialogId+' div.field').mouseleave(function(e){
		$(this).find(".addinfo").hide();
	});
}

function hrefWithSiteroot2js(domIdToCheck, targetDomId){	
	$('#'+domIdToCheck+' a[href^="'+SITE_ROOT+'"]').each(function(){
		$(this).click(function(){
			if($(this).attr('target') != '_blank'){
				//detect if changing namespace or not
				var ref = $(this).attr('href');
				//detect if a # is in the link
				if(ref.indexOf('#')!=-1){
					ref = ref.replace(SITE_ROOT+'#','')
					if(!$(this).attr('href').match(crtWigiiNamespaceUrl) || !$(this).attr('href').match(crtModuleName)){
						if($(this).attr('href').match('item/')){
							ref = ref.replace('/item/', '/navigate/item/');
						}
						if($(this).attr('href').match('folder/')){
							ref = ref.replace('/folder/', '/navigate/folder/');
						}
						update('mainDiv/'+ref);
					} else {
						if($(this).attr('href').match('item/')){
							ref = ref.replace('/item/', '/element/detail/');
							update(''+targetDomId+'/'+ref);
						}
						if($(this).attr('href').match('folder/')){
							ref = ref.replace('/folder/', '/groupSelectorPanel/selectGroup/');
							update('NoAnswer/'+ref);
						}
					}
				}
			}
		});
	});
}

function resize_navigateMenu(){
	resize_navigateMenu_i = $('#NMContainer');
	if(resize_navigateMenu_i.length>0){
		//2*12 because navR and navL have margin -12
		resize_navigateMenu_i.width($(window).width()-$('#userMenu').outerWidth()-$('#navigationBar .navR').outerWidth()-$('#navigationBar .navL').outerWidth()-1);
	}
}
$(window).resize(resize_navigateMenu);

navigateMenuTimerComeBackToSelected = null;
doShowUserMenuUserNameTimer = null;
doShowUserSubMenuUserNameTimer = null;
myTimer = [0];

var ctrlPressed = false;
window.onmousedown = function(e){ ctrlPressed = e.ctrlKey; };

function setListenerToNavigateMenu(){
	$('#navigateMenu li').click(function(e){ //:not(.with-ul)
		if($(this).hasClass('with-ul')){
			//if click directly on the top link of the navigation bar selec the first possible option
			if(!$(this).hasClass('selected') && $('ul li:first a', this).length){
				$('ul li:first a', this).click();
			}
		} else {
			$.cookie('wigii_anchor', $('a', this).attr('href'),  { path: '/' });
			if(e.ctrlKey){
				//do nothing, just let browser open link in new tab
				//window.open($('a', this).attr('href').replace('#', '')+'/navigate', '_blank');
			} else {
				$('#navigateMenu .selected').removeClass('selected');
				$(this).addClass('selected');
				refreshNavigateMenu();
				self.location = $('a', this).attr('href');
				updateThroughCache();
				clearTimeout(navigateMenuTimerComeBackToSelected);
			}
			e.stopPropagation();
		}
	}).bind('contextmenu', function(e){$.cookie('wigii_anchor', $('a', this).attr('href'),  { path: '/' }); e.stopPropagation();});
	//in the JS cache there already is the setNavigationBarInHomeState when displaying the home page
	//$('#userMenu li.home').click(setNavigationBarInHomeState);
	$('#userMenu li.back').click(function(e){
		$('#navigateMenu a[href=\'#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'\']').click(e);
		return false;
	});
	$('#userName')
		.mouseenter(function(e){
			clearTimeout(doShowUserMenuUserNameTimer);
			doShowUserMenuUserNameTimer = setTimeout(function(){
				$('#userName').addClass('menuHover');
				$('#userName>ul, #userName>ul>li').css('visibility','visible');
			}, 250);
		})
		.mouseleave(function(e){
			clearTimeout(doShowUserMenuUserNameTimer);
			doShowUserMenuUserNameTimer = setTimeout(function(){
				$('#userName').removeClass('menuHover');
				$('#userName>ul, #userName>ul>li').css('visibility','hidden');
			}, 400);
		})
	;
	$('#userName>ul>li')
		.mouseenter(function(e){		
			var height = $(window).height()-$(this).offset().top-$('#footerBar').outerHeight();
			//$('#userMenu ul li ul').css('max-height',height+'px');
			//add a max-height for let the browser handle the scrollbar
			$(this).children().last().css('max-height',height+'px'); //relative path
			
			$('#userName>ul>li.subMenuHoverTemp').removeClass('subMenuHoverTemp');
			$(this).addClass('subMenuHoverTemp');
			clearTimeout(doShowUserSubMenuUserNameTimer);
			doShowUserSubMenuUserNameTimer = setTimeout(function(){
				$('#userName>ul>li.subMenuHover>ul, #userName>ul>li.subMenuHover>ul>li').css('visibility','hidden');
				$('#userName>ul>li.subMenuHover').removeClass('subMenuHover');
				$('#userName>ul>li.subMenuHoverTemp').addClass('subMenuHover');
				$('#userName>ul>li.subMenuHover ul, #userName>ul>li.subMenuHover>ul>li').css('visibility','visible');
			}, 400);
		})
		.mouseleave(function(e){
			clearTimeout(doShowUserSubMenuUserNameTimer);
			doShowUserSubMenuUserNameTimer = setTimeout(function(){
				$('#userName>ul>li.subMenuHover>ul, #userName>ul>li.subMenuHover>ul>li').css('visibility','hidden');
				$('#userName>ul>li.subMenuHover').removeClass('subMenuHover');
			}, 400);
		})
	;
	//Create a delai for hide submenu in navigationBar
	$("#navigateMenu li.with-ul").on("mouseenter", function() {
		if(!$(this).hasClass("selected")){
			var navMenu = $("#navigateMenu li.with-ul");
			var currentLi = navMenu.index(this);
			clearTimeout(myTimer[currentLi]);
			for(var index=0, len=myTimer.length; index<len; index++){
				if(index!=currentLi) {
					myTimer[index] = null;
					navMenu.eq(index).removeClass("delai-navbar");
				}
			}
		}
	}).on("mouseleave", function() {
		if(!$(this).hasClass("selected")){
			var navMenu = $("#navigateMenu li.with-ul");
			var currentLi = navMenu.index(this);
			$(this).addClass("delai-navbar");
			myTimer[currentLi] = setTimeout(function(){
				navMenu.removeClass("delai-navbar");
			}, 400);
		}
	});
	
	$('#navigationBar .navL').click(function(e){
		if($(this).hasClass('D')) return;
		$('#navigateMenu').stop(true, true).animate({ marginLeft: "+=200" }, 200, enableDisableNavScroll);
		clearTimeout(navigateMenuTimerComeBackToSelected);
		navigateMenuTimerComeBackToSelected = setTimeout(timeToScrollBack, 5000);
	});
	$('#navigationBar .navR').click(function(e){
		if($(this).hasClass('D')) return;
		$('#navigateMenu').stop(true, true).animate({ marginLeft: "-=200" }, 200, enableDisableNavScroll);
		clearTimeout(navigateMenuTimerComeBackToSelected);
		navigateMenuTimerComeBackToSelected = setTimeout(timeToScrollBack, 5000);
	});
}

function addNavigationToMenu(menuId, menuTimer){
	var menu = $('#'+menuId);
	var submenu = menu.children().children().last();
	//console.log(menu.is("li"));
	menu
		.mouseenter(function(e){
			clearTimeout(menuTimer);
			menuTimer = setTimeout(function(){
				menu.addClass('menuHover');
				submenu.css('visibility','visible');
			}, 150);
		})
		.mouseleave(function(e){
			clearTimeout(menuTimer);
			menuTimer = setTimeout(function(){
				menu.removeClass('menuHover');
				menu.find('ul').css('visibility','hidden');
			}, 400);
		})
		/*.click(function(){ 
			//submenu.slideUp(200);
			submenu.css('visibility','hidden');
		})*/
	;
	
	addNavigationToSubMenu(submenu.children(), menuTimer);
}

function addNavigationToSubMenu(subMenus, menuTimer){
	//subMenus is a JQuery collection of list item (<li>)
	subMenus
		.mouseenter(function(e){
			var self = $(this);
			var childMenu = self.children().last();
			var currentSpaceAvailable = $(window).height() -childMenu.offset().top -$('#footerBar').outerHeight();
			//for solve a resize problem we put the initial height in data
			if(childMenu.data('originHeight')===undefined)
				childMenu.data('originHeight',childMenu.height());
			childMenu.height(childMenu.data('originHeight'));
			
			if(childMenu.is('ul') && currentSpaceAvailable < childMenu.outerHeight()) {
				//add a max-height for let the browser handle the scrollbar
				childMenu.css({'max-height':currentSpaceAvailable+'px', 'overflow-y':'auto', 'overflow-x':'hidden'});
			} else {
				childMenu.css({'max-height':'','overflow-x':'','overflow-y':''});
			}
			
			self.parent().find('.subMenuHoverTemp').removeClass('subMenuHoverTemp');
			self.addClass('subMenuHoverTemp');
			clearTimeout(menuTimer);
			menuTimer = setTimeout(function(){
				if(childMenu.is("ul")) {
					subMenus.find('ul').css('visibility','hidden');
					childMenu.css({'top':'0','visibility':'visible'});
				} else {
					subMenus.find("ul").css('visibility','hidden');
				}
			}, 10);
		})
		.mouseleave(function(e){
			clearTimeout(menuTimer);
			menuTimer = setTimeout(function(){
				//mainMenu.find('.subMenuHoverTemp').removeClass('subMenuHoverTemp');
				subMenus.parent().find('.subMenuHoverTemp').removeClass('subMenuHoverTemp');
				
				//$('#'+menuId+'>ul>li.subMenuHover>ul, #'+menuId+'>ul>li.subMenuHover>ul>li').css('visibility','hidden');
				subMenus.find('.subMenuHover>ul').css('visibility','hidden');
				//$('#'+menuId+'>ul>li.subMenuHover').removeClass('subMenuHover');
				subMenus.find('.subMenuHover').removeClass('subMenuHover');
			}, 400);
		})
		.click(function(){ 
			//submenu.slideUp(200);
			var self = $(this);
			self.addClass('S');
			subMenus.parent().css('visibility','hidden');
		})
	;
	//Recursion for sub-submenu...
	subMenus.each(function( index ) {
		var nextSubMenu = $(this).children().last();
		if(nextSubMenu.is('ul')) {
			addNavigationToSubMenu(nextSubMenu.children(), menuTimer);
		}
	});
}

function enableDisableNavScroll(){
	NMOriginalOffset = $('#NMContainer').offset().left;
	NMFirst = $('#navigateMenu>li:first').offset().left-NMOriginalOffset;
	NMLast = $('#navigateMenu>li:last').offset().left-NMOriginalOffset+$('#navigateMenu>li:last').width();
	NMWidth = $('#NMContainer').width();
	//if the first element is pushed in the right, move it back
	if(NMFirst <= 0){
		$('#navigationBar .navL').removeClass('D');
	} else {
		$('#navigationBar .navL').addClass('D');
	}
	if(NMLast > NMWidth){
		$('#navigationBar .navR').removeClass('D');
	} else {
		$('#navigationBar .navR').addClass('D');
	}
}
function timeToScrollBack(){
	//alert('time to scroll');
	NMOriginalOffset = $('#NMContainer').offset().left;
	NMWidth = $('#NMContainer').width();
	NMOffset = $('#navigateMenu>li.selected').offset().left-NMOriginalOffset;
	NMSelectedWidth = $('#navigateMenu>li.selected').width();
	NMFirstOffset = $('#navigateMenu>li:first').offset().left-NMOriginalOffset;
	NMLastOffset = $('#navigateMenu>li:last').offset().left-NMOriginalOffset+$('#navigateMenu>li:last').width();

	//alert("offset:"+NMoffset+" length:"+NMOriginalLength+" pos of first:"+NMFirstOffset);

	NMOffsetResult = 0;
	//is the selected visible?
	if((NMOffset+NMSelectedWidth) > NMWidth){
		//hidden on the right
		NMOffsetResult =  -(NMOffset + NMSelectedWidth - NMWidth)-50;
	} else if(NMOffset < 0){
		//hidden on the left
		NMOffsetResult = - NMOffset + 50;
	}

	if((NMFirstOffset + NMOffsetResult) >= 0){
		NMOffsetResult = - NMFirstOffset;
	}

	//scroll the navigateMenu if necessary
	if(NMOffsetResult != 0){
		$('#navigateMenu').animate({ marginLeft:  "+="+(NMOffsetResult)}, 500, enableDisableNavScroll);
	} else {
		enableDisableNavScroll();
	}
}

function setNavigationBarInHomeState(displayFeedbackOnSystem){
	if(arguments.length==0){ displayFeedbackOnSystem = false; }
	$('#navigationBar .homeOnly').show();
	if(!crtWigiiNamespaceUrl || !crtModuleName){
		$('#userMenu li.back').hide();
	}
	$('#navigationBar .notHome').hide();
	if(displayFeedbackOnSystem){
		$('#userFeedbackOnSystem').show();
		$('#userFeedbackOnSystem').removeClass('disabled');
		$('#userFeedbackOnSystem a').removeClass('disabled');
	} else {
		$('#userFeedbackOnSystem').hide();
		$('#userFeedbackOnSystem a').addClass('disabled');
		$('#userFeedbackOnSystem').addClass('disabled');
	}
	closeStandardsDialogs();
	self.location = '#Home';
	resize_navigateMenu();
}
function setNavigationBarNotInHomeState(displayFeedbackOnSystem){
	$('#navigationBar .admin').hide();
	$('#navigationBar .homeOnly').hide();
	$('#navigationBar .notHome').show();
	if(displayFeedbackOnSystem){
		$('#userFeedbackOnSystem').show();
		$('#userFeedbackOnSystem').removeClass('disabled');
		$('#userFeedbackOnSystem a').removeClass('disabled');
	} else {
		$('#userFeedbackOnSystem').hide();
		$('#userFeedbackOnSystem a').addClass('disabled');
		$('#userFeedbackOnSystem').addClass('disabled');
	}
	self.location = '#'+crtWigiiNamespaceUrl+"/"+crtModuleName;
	resize_navigateMenu();
}
function setNavigationBarInAdminState(){
	$('#navigationBar .homeOnly').hide();
	$('#navigationBar .notHome').show();
	$('#userMenu .notHome').hide();
	$('#navigationBar .home').hide();
	$('#userFeedbackOnSystem').hide();
	$('#userFeedbackOnSystem a').addClass('disabled');
	$('#userFeedbackOnSystem').addClass('disabled');
	$('#navigationBar .admin').show();
}
function refreshNavigateMenu(){
	$('#navigateMenu .selected')
		.parentsUntil('#navigateMenu', 'li')
		.addClass('selected');
	if($('#navigateMenu li.selected').length>1){
		$('#navigateMenu').addClass('with-sub');
	} else {
		$('#navigateMenu').removeClass('with-sub');
	}
	resize_navigateMenu();
}
//subMenuPosition : string = positionElementOnDom option
function setListenerToHomePage(subMenuPosition){
	if(arguments.length < 1) subMenuPosition = "fromLeft";
	$('#ContainerHome #homePageModuleMenu li, #ContainerHome #homePageWigiiNamespaceMenu li.sub').click(function(e){
		$('a', this).click();
	});
	$('#ContainerHome #homePageModuleMenu :not(.link) a, #ContainerHome #homePageWigiiNamespaceMenu .sub a').click(function(e){
		homePageClickRef = $(this).attr('href');
		//in IE7 href include the site root even if not define in the html of the home page
		homePageClickRef = homePageClickRef.replace(SITE_ROOT, '');
		if($(this).parent().hasClass("adminMenu")){
			$("#userMenuAdmin a[href$='"+homePageClickRef+"']").click();
		} else {
			$("#navigateMenu a[href$='"+homePageClickRef+"']").click();
		}
		self.location = homePageClickRef;
		e.stopPropagation();
	});

	var homePageMenuTimers = [];
	$('#ContainerHome #homePageWigiiNamespaceMenu li')
		.mouseenter(function(){
			var self = this;
			//dont't display submenu if only one module
			if($('ul li', self).length>1){
				tempOffsetLow = null;
				if(subMenuPosition == "right" || subMenuPosition=="left"){
					tempOffsetLow = 0;
				}
				positionElementOnDom($('ul', self), $(self), subMenuPosition, tempOffsetLow, true);				
				
				var currentLi = $('#homePageWigiiNamespaceMenu .H.N').index(self);
				clearTimeout(homePageMenuTimers[currentLi]);
				homePageMenuTimers[currentLi] = setTimeout(function(){
					$('#homePageWigiiNamespaceMenu .H.N > ul').hide();
					$('ul', self).show();
				}, 100);
			}
		})
		.mouseleave(function(){
			var self = this;
			var currentLi = $('#homePageWigiiNamespaceMenu .H.N').index(self);
			clearTimeout(homePageMenuTimers[currentLi]);
			homePageMenuTimers[currentLi] = setTimeout(function(){
				$('ul', self).hide();
			}, 400);
		})
		.click(function(e){
			if($('ul li:first a', this).length){
				$('ul li:first a', this).click();
			}
		})
		.bind('contextmenu', function(e){$.cookie('wigii_anchor', $('a', this).attr('href'),  { path: '/' }); e.stopPropagation();})
		;
}


/*******************
 * Multiple select
 ******************/


function selectLine(id, toggleIfMultiple){
	if(arguments.length < 2) toggleIfMultiple = false;
	if(typeof(id) == 'string'){
		obj = $('#'+id);
	} else obj = $(id);

	//remove all from parents who are selected
	obj.parent().find("*").removeClass("selected");
	if(switchSelectionModeIsMultiple){
		if(obj.hasClass('readOnly')){
			$('#moduleView .title .onlyWriteRights').hide();
		} else if(allMultipleSelectionHaveWriteRights) {
			$('#moduleView .title .onlyWriteRights').show();
		}
		if(toggleIfMultiple){
			obj.toggleClass('multipleSelected');
		} else {
			obj.addClass('multipleSelected');
		}
	} else {
		obj.parent().find("*").removeClass("multipleSelected");
		obj.addClass('selected');
	}

	//after the selection is made, check if need to change the contextMenu
	matchContextMenu(obj);
}

switchSelectionModeIsMultiple = false;
allMultipleSelectionHaveWriteRights = false;
multipleSelectionNb = 0;
function setSelectionMode(isMultiple){
	if(arguments.length<1){
		isMultiple = switchSelectionModeIsMultiple;
	}
	if(switchSelectionModeIsMultiple != isMultiple){
		switchSelectionModeIsMultiple = isMultiple;
//		update('multipleDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/switchMultipleSelection/'+switchSelectionModeIsMultiple);
		if(switchSelectionModeIsMultiple){

		} else {
			crtMultipleSelectionDialogKeep = false;
			update('multipleDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/stopMultipleSelection/');
			$('#moduleView .M').removeClass('M');
		}
	}
	if(switchSelectionModeIsMultiple){
		$('#moduleView div.toolBar>div.emailing').addClass('M');
		$('#moduleView div.toolBar>div.export').addClass('M');
	} else {
		$('#moduleView div.toolBar>div.emailing').removeClass('M');
		$('#moduleView div.toolBar>div.export').removeClass('M');
	}
	//update the multiple dialog content
	if($('#multipleDialog:visible').length>0){
		$('#multipleDialog .summary span.multipleSelectionNb').text(multipleSelectionNb);
		if(allMultipleSelectionHaveWriteRights){
			$('#multipleDialog div.onlyWriteRights').show();
		} else {
			$('#multipleDialog div.onlyWriteRights').hide();
		}

		// hides organize and delete if at least one element is blocked
		if(multipleElementStatePerm & 2) {
			$('#multipleDialog div.organizeMultiple').hide();
			$('#multipleDialog div.deleteMultiple').hide();
		}
		// hides delete if non admin and enableDeleteOnlyForAdmin config parameter
		if(multipleEnableElementState & 1024) {
			$('#multipleDialog div.deleteMultiple').hide();
		}
		// hides edit if all elements are blocked
		if(multipleElementStateRest & 2) {
			$('#multipleDialog div.editMultiple').hide();
		}		
		// shows or hides element statuses
		if(multipleEnableElementState > 0) {
			// enables lock
			if(multipleEnableElementState & 1) {
				s = $('#multipleDialog div.lockedMultiple');
				if(multipleElementState & 1) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 1) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.lockedMultiple').hide();
			// enables block
			if(multipleEnableElementState & 2) {
				s = $('#multipleDialog div.blockedMultiple');
				if(multipleElementState & 2) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 2) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.blockedMultiple').hide();
			// enables important1
			if(multipleEnableElementState & 4) {
				s = $('#multipleDialog div.important1Multiple');
				if(multipleElementState & 4) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 4) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.important1Multiple').hide();
			// enables important2
			if(multipleEnableElementState & 8) {
				s = $('#multipleDialog div.important2Multiple');
				if(multipleElementState & 8) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 8) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.important2Multiple').hide();
			// enables finalized
			if(multipleEnableElementState & 16) {
				s = $('#multipleDialog div.finalizedMultiple');
				if(multipleElementState & 16) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 16) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.finalizedMultiple').hide();
			// enables approved
			if(multipleEnableElementState & 32) {
				s = $('#multipleDialog div.approvedMultiple');
				if(multipleElementState & 32) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 32) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.approvedMultiple').hide();
			// enables dismissed
			if(multipleEnableElementState & 64) {
				s = $('#multipleDialog div.dismissedMultiple');
				if(multipleElementState & 64) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 64) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.dismissedMultiple').hide();
			// enables archived
			if(multipleEnableElementState & 128) {
				s = $('#multipleDialog div.archivedMultiple');
				if(multipleElementState & 128) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 128) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.archivedMultiple').hide();
			// enables deprecated
			if(multipleEnableElementState & 256) {
				s = $('#multipleDialog div.deprecatedMultiple');
				if(multipleElementState & 256) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 256) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.deprecatedMultiple').hide();
			// enables hidden
			if(multipleEnableElementState & 512) {
				s = $('#multipleDialog div.hiddenMultiple');
				if(multipleElementState & 512) s.addClass('checked').removeClass('unchecked').removeClass('mixed');
				else if(multipleElementStateMedi & 512) s.addClass('mixed').removeClass('unchecked').removeClass('checked');
				else s.addClass('unchecked').removeClass('checked').removeClass('mixed');
			}
			else $('#multipleDialog div.hiddenMultiple').hide();
		}
		else $('#multipleDialog div.elementStates').hide();
	} else if(switchSelectionModeIsMultiple){
		//if not display and multiple selection then show the dialog
		update('multipleDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/displayMultipleDialog/');
	}
}

function clickFolderLineInMultipleSelectionMode(obj){
	obj.toggleClass('M');
	invalidModuleViewCache();
	update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/setMultipleSelectionFolder/'+obj.attr('id').split('_')[1]+'/'+obj.hasClass('M'));
}
function clickLineInMultipleSelectionMode(obj){
	obj.toggleClass('M');
	invalidModuleViewCache();
	elEnableState = $('.menu .elEnableState', obj).html();
	elState = $('.menu .elState', obj).html();
	update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/setMultipleSelection/'+obj.attr('id').split('_')[1]+'/'+obj.hasClass('M')+'/'+!obj.hasClass('readOnly')+'/'+elEnableState+'/'+elState);
}
function selectAllMultipleSelect(){
	if($('#groupPanel li.selected').length==0){
		groupId = 0;
	} else {
		groupId = $('#groupPanel li.selected').attr('id').split('_')[1];
	}
	$('#moduleView .el:has(.mul), #moduleView .folder:has(.mul)').addClass('M');
	invalidModuleViewCache();
	update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/setMultipleSelectionFolder/'+groupId+'/true');
}
function deselectAllMultipleSelect(){
	if($('#groupPanel li.selected').length==0){
		groupId = 0;
	} else {
		groupId = $('#groupPanel li.selected').attr('id').split('_')[1];
	}
	$('#moduleView .M').removeClass('M');
	invalidModuleViewCache();
	update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/setMultipleSelectionFolder/'+groupId+'/false');
}
function switchSelectionMode(){
	setSelectionMode(!switchSelectionModeIsMultiple);
}

//with flowPlayer plugin
function displayAudioPlayer(mediaId){
	flowplayer(
		mediaId,
		'flowplayer-3.2.11.swf',
		{
			plugins: {
				controls: {
					backgroundColor: '#696969',
					fullscreen: false,
					height: 26,
					autoHide: false
				}
			},
			clip: {
				type: 'audio',
				autoPlay: false
			}
		}
	);
}

function displayVideoPlayer(mediaId){
	flowplayer(
		mediaId,
		'flowplayer-3.2.11.swf',
		{
			plugins: {
				controls: {
					backgroundColor: '#696969',
					fullscreen: true,
					autoHide: false
				}
			},
			clip: {
				scaling: 'no',
				type: 'video',
				autoPlay: false
			}
		}
	);
}

function previewUnzipForViewing(fileSrc, previewTime){
	update('elementPreview/'+fileSrc+'/unzipForViewing/');
}

function previewHtml(fileSrc, previewTime){
	$.blockUI(
		{
			css: {
				top: '5%',
				left: '3%',
				border: 'none',
				width: '90%',
				height:'90%'
			},
			message: '<div class=\'ui-corner-all\' style=\'position:absolute; top:3%px; left:101%; font-style:bold;font-size:10px;padding:5px 10px 5px 10px;cursor:pointer; background-color:#fff;\' onclick=\'$.unblockUI();$("#fileDownloadingBar").hide();\' >X</div><iframe onload=\'$("#fileDownloadingBar").hide();\' style=\'height:100%;width:100%;\'src=\''+SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+ fileSrc.replace(SITE_ROOT, '')+'/integrated?'+previewTime+'\' >Your browser does not support iframes.</iframe>'
		}
	);
	$('.blockOverlay').unbind('click').click($.unblockUI);
}

function previewIframe(url){
	$.blockUI(
		{
			css: {
				top: '5%',
				left: '3%',
				border: 'none',
				width: '90%',
				height:'90%'
			},
			message: '<div class=\'ui-corner-all\' style=\'position:absolute; top:3%px; left:101%; font-style:bold;font-size:10px;padding:5px 10px 5px 10px;cursor:pointer; background-color:#fff;\' onclick=\'$.unblockUI();\' >X</div><iframe style=\'height:100%;width:100%;\'src=\''+url+'\' >Your browser does not support iframes.</iframe>'
		}
	);
	$('.blockOverlay').unbind('click').click($.unblockUI);
}

function previewImage(fileSrc, previewTime){
	$.blockUI({
		css: {
			top: '10%',
			left: '10%',
			border: 'none',
			width: '80%',
			height:'80%',
			background: 'none'
		}, message: '<img style=\'max-height:100%;max-width:100%;vertical-align:middle;\' onload=\'$("#fileDownloadingBar").hide();\' src=\''+SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+ fileSrc.replace(SITE_ROOT, '')+'?'+previewTime+'\' /><span class=\'ui-corner-all\' style=\'position:absolute; font-style:bold;font-size:10px;padding:5px 10px 5px 10px;margin-left:5px;margin-top:0px;cursor:pointer; background-color:#fff;\'  onclick=\'$.unblockUI(); $("#fileDownloadingBar").hide();\' >X</span>'
	});
	$('.blockOverlay').unbind('click').click($.unblockUI);
}

function setListenersToFilters(){
	$('#workZone #searchBar input:first').change(function(){
		hideHelp();
		$(this).next().next().click();
	});
	$('#workZone #searchBar #goForSearch').click(function(){
		setVis("busyDiv", true);
		setVis('filteringBar', true);
		url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'filtersDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/simpleFilters';
		var myAjax = new jQuery.ajax({
				type: 'POST',
				url: encodeURI(url),
				success : parseUpdateResult,
				cache:false,
				data: {action: 'check', __textSearch_value:$(this).prev().prev().val()},
				error: errorOnUpdate
			});
	});
	$('#workZone #searchBar #filtersButton').click(function(){
		update('filtersDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/filters');
	});
	$('#workZone #searchBar #removeFiltersButton').click(function(){
		$('#workZone #searchBar input:first').val('');
		update('filtersDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/removeFilters');
	});
}

function setFiltersButton(isFilterSet){
	if(isFilterSet){
		$("#filtersButton").removeClass('grayFont').addClass('R');
		$("#removeFiltersButton").removeClass('grayFont').addClass('R');
	} else {
		$("#removeFiltersButton").addClass('grayFont').removeClass('R');
		$("#filtersButton").addClass('grayFont').removeClass('R');
	}
}

function setListenersToCheckInOutFiles(elementDialogId, elementId, fieldId, fieldName, checkFunction, checkTitleText, okLabel, cancelLabel){
	//if no elementDialogId that means we are looking at the item directly in the mainDiv
	if(elementDialogId==""){
		elementDialogId="mainDiv";
	}
	$('#'+fieldId+'.field .value .checkOutIn').click(function(e){
		$(this).after('<div class="SBB ui-corner-all" style="font-size:small;background-color:#fff;zIndex:'+($(this).closest('.ui-dialog').css('zIndex')+1)+';top:'+(parseInt($(this).position().top)+parseInt($(this).outerHeight())+8)+'px; left:'+$(this).position().left+'px; padding:5px; position:absolute;" ><div style="font-size:small;">'+checkTitleText+'</div><textarea style="width:300px;margin-top:5px; margin-bottom:5px;height:100px;" class=""></textarea><br /><input type="button" name="ok" value="'+okLabel+'" /><input type="button" name="cancel" value="'+cancelLabel+'" /></div>');
		autosize($(this).next().find('.elastic'));
		$(this).next().find('input:input:last').click(function(){
			$(this).parent().hide().remove();
		});
		$(this).next().find('input:input:first').click(function(){
			$(this).parent().hide();
			if(elementDialogId!="mainDiv"){
				$(this).closest('.ui-dialog').find('.ui-icon-closethick').click();
				url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/'+checkFunction+'/'+elementId+'/'+fieldName;
			} else {
				url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'mainDiv/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/'+checkFunction+'/'+elementId+'/'+fieldName;
			}
			
			if(checkFunction == 'download/checkoutFile') {
				messageDownload(fieldId);
				setTimeout(function() {setVis('fileDownloadingBar', false);}, 5000);
			}			
			var myAjax = new jQuery.ajax({
					type: 'POST',
					url: encodeURI(url),
					success : parseUpdateResult,
					cache:false,
					data: {message:$(this).parent().find('textarea').val(), elementDialogId: elementDialogId },
					error: errorOnUpdate
				});
			});
		e.stopPropagation();
	});
	if($('#'+elementDialogId+' div.T div.el_edit').length==0 || $('#'+fieldId+'.readOnly').length>0){
		$('#'+fieldId+'.field .value .checkOutIn').hide();
	}
}

function setListenerToMultipleDialog(
	externalAccessMenusendEmailTitle,
	multipleExternalAccessMenuView,
	multipleExternalAccessMenuEdit,
	multipleExternalAccessMenuStop,
	externalAccessMenuEndDate,
	okLabel,
	cancelLabel,
	areYouSureToStopExternalAccess){

	$('#multipleDialog').prev().css('background-color', '#FFE694');
	$('#multipleDialog').css('min-height', '0px');
	$('#multipleDialog').parent().find('.ui-dialog-title')
		.append($('#multipleDialog .toolBar .selectAll'))
		.append($('#multipleDialog .toolBar .resetAll'))
		.append($('#multipleDialog .toolBar .viewAll'));
	$('#multipleDialog').next().hide();

	if(externalAccessMenusendEmailTitle){
		emb = $('#multipleDialog .multipleExternalAccess');
		$(emb).click(function(){
			$(this).parent().find('.externalAccessMenu').remove();
			externalAccessMenuHtml = ''+
				'<span class="externalAccessMenu SBB ui-corner-all" style="font-weight:normal;background-color:#fff;top:'+(parseInt($(emb).position().top)+parseInt($(emb).outerHeight())-50)+'px; left:'+($(emb).position().left-50)+'px; padding:5px; position:absolute;" >'+
					'<span style="width:380px;font-weight:bold;">'+externalAccessMenusendEmailTitle+'</span><br />';
			if(multipleExternalAccessMenuView){
				externalAccessMenuHtml += ''+
					'<input type="radio" name="MultipleExternalAccessEmailManage" value="externalAccessMenuViewLink" /><span style="cursor:pointer;margin-top:-2px;" onclick="$(this).prev().click();">'+multipleExternalAccessMenuView+'</span><br />';
			}
			if(multipleExternalAccessMenuEdit){
				externalAccessMenuHtml += ''+
					'<input type="radio" name="MultipleExternalAccessEmailManage" value="externalAccessMenuEditLink" /><span style="cursor:pointer;margin-top:-2px;" onclick="$(this).prev().click();">'+multipleExternalAccessMenuEdit+'</span><br />';
			}
			externalAccessMenuHtml += ''+
					'<input type="radio" name="MultipleExternalAccessEmailManage" value="externalAccessMenuStop" /><span style="cursor:pointer;margin-top:-2px;" onclick="$(this).prev().click();">'+multipleExternalAccessMenuStop+'</span><br />' +
					'<span class="endDate" style="display:none;cursor:pointer;"><br /><span class="endDate" style="cursor:pointer;margin-right:5px;" onclick="$(this).next().focus();">'+externalAccessMenuEndDate+'</span><input class="endDate" type="text" name="externalAccessEndDate" value="" /><br /></span>'+
					'<br />'+
					'<input type="button" name="ok" disabled="on" value="'+okLabel+'" />'+
					'<input type="button" name="cancel" value="'+cancelLabel+'" />'+
				'</span>';
			$(this).after(externalAccessMenuHtml);
			$(this).next().find('input[type="radio"]').click(function(){
				$(this).attr('checked', true);
				$(this).parent().find('input[type="button"]:input[name="ok"]').removeAttr('disabled');
				showEndDate = false;
				switch($(this).val()){
					case 'externalAccessMenuValidationLink':
						$(this).parent().find('span.stopAccess').remove();
						showEndDate = false;
						break;
					case 'externalAccessMenuViewLink':
						$(this).parent().find('span.stopAccess').remove();
						showEndDate = true;
						break;
					case 'externalAccessMenuEditLink':
						$(this).parent().find('span.stopAccess').remove();
						showEndDate = true;
						break;
					case 'externalAccessMenuStop':
						$(this).parent().find('input[type="button"]:input[name="ok"]').before('<span class="stopAccess">'+areYouSureToStopExternalAccess+'<br /><br /></span>');
						showEndDate = false;
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
			});
			$(this).next().find('input[type="button"]:input[name="cancel"]').click(function(){
				$(this).parent().hide().remove();
			});
			$(this).next().find('input[type="button"]:input[name="ok"]').click(function(){
				$(this).parent().hide();
				url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/manageEmails/multiple';
						var myAjax = new jQuery.ajax({
							type: 'POST',
							url: encodeURI(url),
							success : parseUpdateResult,
							cache:false,
							data: {
								externalAction: $(this).parent().find('input:radio:checked').val(),
								externalEndDate: $(this).parent().find('input.endDate').val()
							},
							error: errorOnUpdate
						});
				$(this).parent().remove();
			});
		});
	}

	// element statuses
	$('#multipleDialog div.elementStates div.onlyWriteRights').click(function(e){
		state = $(this).attr('href').replace('#', '');
		if($(this).hasClass('unchecked')) {
			$(this).addClass('checked').removeClass('unchecked');
			update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/multiple/'+state+'/1');
		}
		else if($(this).hasClass('checked')) {
			$(this).addClass('unchecked').removeClass('checked');
			update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/multiple/'+state+'/0');
		}
		else if($(this).hasClass('mixed')) {
			$(this).addClass('checked').removeClass('mixed');
			update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/setElementState/multiple/'+state+'/1');
		}
		e.stopPropagation();
	});
}
function popup_params(width, height) {
    var a = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft;
    var i = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop;
    var g = typeof window.outerWidth!='undefined' ? window.outerWidth : document.documentElement.clientWidth;
    var f = typeof window.outerHeight != 'undefined' ? window.outerHeight: (document.documentElement.clientHeight - 22);
    var h = (a < 0) ? window.screen.width + a : a;
    var left = parseInt(h + ((g - width) / 2), 10);
    var top = parseInt(i + ((f-height) / 2.5), 10);
    return 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',location=0,menubar=0,status=0,toolbar=0,scrollbars=1,resizable=1';
}
function setListenerToPreviewListLines(previewListId, moduleName, linkType, wListenerToPreviewList, gtIndex){
	var crtwListenerToPreviewList = wListenerToPreviewList;
	var crtPreviewListId = previewListId;
	var crtLinkType = linkType;
	var moreSelector = "";
	if(gtIndex){
		moreSelector = ":gt("+(gtIndex)+")";
	}

	if(crtLinkType == 'query') {
		$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector).click(function(e){
			var res = $(this).attr('id').split('$$');
			var wigiiNamespaceUrl = res[3];
			var moduleUrl = res[4];
			var eltId = res[5];
			window.open(SITE_ROOT+'#'+wigiiNamespaceUrl+'/'+moduleUrl+'/item/'+eltId);
			e.stopPropagation();
		});
	}
	else {
		$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector).click(function(e){
			var res = $(this).attr('id').split('$$');
			var ownerEltId = res[1];
			var linkName = res[2];
			var eltId = res[3];
			//always refer to module and namespace of parent
			self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/item/'+eltId;
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/detail/'+eltId);
			updateThroughCache('elementDialog', $(this).attr('href').replace('#', ''), 'elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/detail/'+eltId);
			e.stopPropagation();
		});
		$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector+' td.edit').click(function(e){
			var res = $(this).parent().attr('id').split('$$');
			var ownerEltId = res[1];
			var linkName = res[2];
			var eltId = res[3];
			//always refer to module and namespace of parent
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+eltId);
			e.stopPropagation();
		});
		$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector+' td.delete').click(function(e){
			var res = $(this).parent().attr('id').split('$$');
			var ownerEltId = res[1];
			var linkName = res[2];
			var eltId = res[3];
			//always refer to module and namespace of parent
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/detail/'+eltId+'/__/confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+eltId);
			e.stopPropagation();
		});
		$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector+' td.restore').click(function(e){
			var res = $(this).parent().attr('id').split('$$');
			var ownerEltId = res[1];
			var linkName = res[2];
			var eltId = res[3];
			//always refer to module and namespace of parent
			//preventing Request forgery (CSRF) with posting data
			update('confirmationDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/restore/'+eltId, false, {action:"check"});
			e.stopPropagation();
		});
	}

	$('#'+crtPreviewListId+' tr:not(.loadNextLines)'+moreSelector+' td:not(.delete, .restore, .edit)>div')
		.mouseenter(function(e){
			showHelp(this, $(this).html(), 30, 'fromLeft', 500, Math.max(200, $(this).width()), 60000);
		})
		.mouseleave(function(e){ hideHelp(); })
}
function setListenerToAddSubItem(fieldId, elementId, linkName){
	//click on add subitems
	$('#'+fieldId+' .addNewSubElement').click(function(e){
		update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/subelement/add/'+elementId+'/'+linkName);
		e.stopPropagation();
	});
}
function setListenerToPreviewList(elementId, linkName, previewListId, moduleName, wListenerToPreviewList, linkType){
	var crtwListenerToPreviewList = wListenerToPreviewList;
	var crtPreviewListId = previewListId;
	var crtElementId = elementId;
	var crtLinkName = linkName;
	var crtLinkType = linkType;


	//click on the value
	$('#'+crtPreviewListId).prev().find('.H:first').click(function(e){
		$('#'+crtPreviewListId).toggle();
		e.stopPropagation();
	});

	//click on lines
	setListenerToPreviewListLines(crtPreviewListId, moduleName, crtLinkType, crtwListenerToPreviewList, null);

	//click on load more...
	$('#'+crtPreviewListId+' tr.loadNextLines').click(function(e){
		var tot = parseInt($(".totalItems", this).text());
		var pageSize = parseInt($(".pageSize", this).text());
		var nbItem = parseInt($(".nbItem", this).text());

		//update preview table with next items
		url = crtWigiiNamespaceUrl+"/"+crtModuleName+"/getNextElementInPreviewList/"+crtElementId+"/"+crtLinkName;
		url = SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+ url;

		setVis("busyDiv", true);
		var myAjax = new jQuery.ajax({
				type: 'POST',
				url: encodeURI(url),
				cache:false,
				data:{
					page: (nbItem/pageSize)+1,
					elementId : elementId,
					previewListId : crtPreviewListId
					},
				success : function(returnText, textStatus){
					setVis("busyDiv", false);
					data = returnText.split('_X_NBNEWROWS_X_');
					nbLoaded = parseInt(data[1]);
					nbTot = parseInt(data[2]);
					data = data[0];
					$('#'+crtPreviewListId+' tr.loadNextLines').before(data);
					if(e.which || (!e.which && nbItem == 0)) $('#'+crtPreviewListId+' tr.loadNextLines .nbItem').html(nbItem + nbLoaded);
					$('#'+crtPreviewListId+' tr.loadNextLines .totalItems').html(nbTot);

					setListenerToPreviewListLines(crtPreviewListId, moduleName, crtLinkType, crtwListenerToPreviewList, nbItem);

					if((nbItem + nbLoaded) >= nbTot){
						//remove see more
						$('#'+crtPreviewListId+' tr.loadNextLines>td>font').hide();
					} else {
						$('#'+crtPreviewListId+' tr.loadNextLines>td>font').show();
					}
					//update the cache content
					// Modified by AC on 06 july 2016 to handle dynamic javascript from config. Do not store modified HTML in cache but only store click instruction
					// e.which is not undefine if the user use the mouse and click
					//updateCurrentElementDialogViewCache();
					if(__cacheJS && __cacheJS["elementDialog"] && __cacheJS["elementDialog"][currentElementDialogViewCacheKey] && e.which){
						var cacheJSaddon = "$(document).ready(function(){" +
									"$('#"+crtPreviewListId+" tr.loadNextLines').click();" +
									"$('#"+crtPreviewListId+" tr.loadNextLines .nbItem').html("+(nbItem + nbLoaded)+"); ";
						if((nbItem + nbLoaded) >= nbTot) cacheJSaddon+= "$('#"+crtPreviewListId+" tr.loadNextLines>td>font').hide();";
						cacheJSaddon+= "});"
						__cacheJS["elementDialog"][currentElementDialogViewCacheKey] = __cacheJS["elementDialog"][currentElementDialogViewCacheKey]+cacheJSaddon;
					}
				},
				error: errorOnUpdate
			});
		onUpdateErrorCounter = 0;

		//update total/nbItem

		e.stopPropagation();
	});

	//click on refresh
	$('#'+crtPreviewListId+' .refresh').click(function(e){
		// Modified by AC on 06 july 2016 to handle dynamic javascript from config. Do not store modified HTML in cache but only store click instruction
		if(__cacheJS && __cacheJS["elementDialog"] && __cacheJS["elementDialog"][currentElementDialogViewCacheKey]){
			var regexExpression = "(\\$\\(document\\)\\.ready\\([\\s]*function\\(\\)[\\s]*\\{[\\w\\s\\n\\r$()'#.;]*(?:value[\\s]*tr\\.loadNextLines\\'\\)\\.click\\(\\);){1}[\\w\\s\\n\\r$()'#.;]*\\}\\);)";
			regexExpression = regexExpression.replace('value', crtPreviewListId);
			var theRegExp = new RegExp(regexExpression, 'gmi');
			__cacheJS["elementDialog"][currentElementDialogViewCacheKey] = __cacheJS["elementDialog"][currentElementDialogViewCacheKey].replace(theRegExp, '');
		}
		//empty previewList
		$('#'+crtPreviewListId+' tr:not(.loadNextLines, .header)').remove();
		$('#'+crtPreviewListId+' tr.loadNextLines .nbItem').html(0);
		$('#'+crtPreviewListId+' tr.loadNextLines').click();
		e.stopPropagation();
		return false;
	});

	// if linkType is query then updates link value with total
	if(crtLinkType == 'query') {
		$('#'+crtPreviewListId).prev().find('.linkTypeQuery').html('('+$('#'+crtPreviewListId+' tr.loadNextLines .totalItems').text()+')');
	}
}

elementCalendar_currentEventSelected = null;
elementCalendar_contextMenuIdElement = null;
lastResizeOrDragRevertFunction = null;
lastModifiedEvent = null;
function setListenersToCalendar(groupUpId, groupUpLabel, crtView, crtYear, crtMonth, crtDay){
	
	var cmSelector = '#moduleView .calendar>.cm';
	var elSelector = '#moduleView .dataZone div.fc-event';
	
	$('#moduleView .calendar').fullCalendar({
		defaultView: crtView,
		year: crtYear,
		month: crtMonth,
		date: crtDay,
		header: {
			left: 'today',
			center: 'title',
			right: 'month,agendaWeek prev,next'
		},
		timeFormat: {
		    agenda: 'H:mm{ - H:mm}',
		    '': 'H:mm'
		},
		axisFormat: 'H:mm',
		columnFormat: {
		    month: 'ddd',
		    week: 'ddd, dd MMM',
		    day: 'dddd, dd MMM'
		},
		titleFormat: {
		    month: 'MMMM yyyy',
		    week: 'dd [ MMM][ yyyy]{ - dd MMM yyyy}',
		    day: 'dddd, d MMM, yyyy'
		},
		lazyFetching: false,
		ignoreTimezone: false,
		height: $('#moduleView').height() - parseInt($('#moduleView .calendar').css('margin-top'),10),
		firstDay: 1,
		editable: calendarIsEditable,
		eventDragStart: function(event, jsEvent, ui, view){

		},
		eventResizeStart: function(event, jsEvent, ui, view){

		},
		eventDrop: function(event,dayDelta,minuteDelta,allDay,revertFunc) {
			if($(this).hasClass('readOnly')){
				revertFunc();
			} else {
				start = event.start;
				end = event.end;
				if(end == null) end = start;
				lastResizeOrDragRevertFunction = revertFunc;
				update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+event.id+'/'+start.getFullYear()+'-'+(start.getMonth()+1)+'-'+start.getDate()+' '+start.getHours()+':'+start.getMinutes()+'/'+end.getFullYear()+'-'+(end.getMonth()+1)+'-'+end.getDate()+' '+end.getHours()+':'+end.getMinutes()+'/'+allDay);
			}
	    },
		eventResize: function(event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view){
			if((event.className+'').search('readOnly')!=-1){
				revertFunc();
			} else {
				start = event.start;
				end = event.end;
				allDay = event.allDay;
				if(end == null) end = start;
				lastResizeOrDragRevertFunction = revertFunc;
				update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+event.id+'/'+start.getFullYear()+'-'+(start.getMonth()+1)+'-'+start.getDate()+' '+start.getHours()+':'+start.getMinutes()+'/'+end.getFullYear()+'-'+(end.getMonth()+1)+'-'+end.getDate()+' '+end.getHours()+':'+end.getMinutes()+'/'+allDay);
			}
		},
		eventAfterRender: function(event, element, view){
			colorize();
		},
		selectable: calendarIsEditable,
		selectHelper: true,
		select: function(start, end, allDay) {
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/add/'+$('#groupPanel li.selected').attr('id').split('_')[1]+'/'+start.getFullYear()+'-'+(start.getMonth()+1)+'-'+start.getDate()+' '+start.getHours()+':'+start.getMinutes()+'/'+end.getFullYear()+'-'+(end.getMonth()+1)+'-'+end.getDate()+' '+end.getHours()+':'+end.getMinutes()+'/'+allDay);
		},
		events: function(start, end, callback) {
			crtView = $('#moduleView .calendar').fullCalendar('getView').name;
			crtDate = $('#moduleView .calendar').fullCalendar('getDate');
			d = new Date();
			timeZoneOffset = d.getTimezoneOffset()*60;
			crtDate = 'crtYear='+crtDate.getFullYear()+';crtMonth='+crtDate.getMonth()+';crtDay='+crtDate.getDate()+';';
	        $.ajax({
	            url: encodeURI(SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+crtWigiiNamespaceUrl+'/'+crtModuleName+'/getCalendarEvents/'+Math.round(start.getTime() / 1000)+'/'+Math.round(end.getTime() / 1000)+'/'+crtView+'/'+crtDate+'/'+timeZoneOffset),
		            dataType: 'text',
					cache:false,
					error: errorOnUpdate,
		            success: function(events){
						events = eval(events);
						if(events.length>0){
							firstEvent = events[0];
//								tempFirstDate = new Date(parseInt(firstEvent.start)*1000);
//								alert(tempFirstDate.getFullYear()+'.'+tempFirstDate.getMonth()+'.'+tempFirstDate.getDate());
//								alert(firstEvent.end*1000>=start.getTime()+' '+firstEvent.start*1000<=end.getTime());
							if(!(firstEvent.end*1000 >= start.getTime() && firstEvent.start*1000 <= end.getTime())){
								goDate = new Date(firstEvent.start*1000);
								$('#moduleView .calendar').fullCalendar('gotoDate', goDate.getFullYear(), goDate.getMonth(), goDate.getDate());
							}
						}
						callback(events);
						//colorize();
					}
	        });
	    },
		eventRender: function(event, element) {
	        element.find('.fc-event-title').html(event.title);
	        element.unbind('contextmenu');
	        element.bind('contextmenu', function (e) { //FullCalendar right click properly handled
	            if (e.which == 3) {
					hideHelp();
	            	$(this).closest(elSelector).addClass('over');
	            	$('#moduleView .calendar .S').removeClass('S');
	        		$('#moduleView .calendar div.over').addClass('S');
	        		positionElementOnMouse($(cmSelector), e, 'fromRight', null, 5);
	        		//show hide some buttons: Default values
	        		elEnableState = $('0');
	        		elState = $('0');
	        		//saving the id of element clicked
	        		if($(cmSelector + " > div.idSelect").length ){
	        			$(cmSelector + " > div.idSelect").attr("id", event.id);
	        		} else {
	        			$(cmSelector).append('<div class="idSelect" id="'+event.id+'" style="display: none;"></div>');
	        		}
	        		if(event.editable == "false"){
	        			$(cmSelector+' div.write').hide();
	        		} else {
	        			$(cmSelector+' div.write').show();
	        		}
	        		if($('#moduleView .toolBar .addNewElement.disabledBg').length>0){
	        			$(cmSelector+' #cm_addElementInList').hide();
	        		} else {
	        			$(cmSelector+' #cm_addElementInList').show();
	        		}
	        		if(elState & 2) {
	        			$(cmSelector+' #cm_edit').hide();
	        			$(cmSelector+' #cm_delete').hide();
	        			$(cmSelector+' #cm_organize').hide();
	        		}
	        		$(cmSelector).show();
	        		e.stopPropagation();
					return false;
	            }
	        });
	    },

		loading: function(isLoading, view){
			setVis('busyDiv', isLoading);
		},
		eventClick: function(calEvent, jsEvent, view) {
			clickEvent_elementCalendar(this, calEvent, jsEvent, view, crtWigiiNamespaceUrl, crtModuleName);
			},
		eventMouseover: function(calEvent, jsEvent, view){
			elementCalendar_contextMenuIdElement = calEvent.id;
			hideHelp();
			//displaying description under mouse position
			showHelp(this, calEvent.description, 30, "fromCenter", 200, 200, 5000, jsEvent);
		}
	});
	if(groupUpId!==""){
		$('#moduleView .calendar .fc-header-left').append('<span class="folderUp L H" >'+groupUpLabel+'</span>');
		//add click on folder up
		$('#moduleView .calendar span.folderUp').click(function(e){
			$('#groupPanel #group_'+groupUpId).click();
			e.stopPropagation();
		});
	}
	//Context menu
	$(cmSelector).mouseleave(function(){
		$(elSelector).removeClass('over');
		$(this).hide();
	});
	$(cmSelector).click(function(){
		$(elSelector).removeClass('over');
		$(this).hide();
	});
	$(cmSelector + '>div').unbind();
	$(cmSelector + '>div').click(function(e){
		if($(this).attr('id')=="cm_exit") return true;
		if($(this).attr('id')=="cm_open"){
			$(elSelector+'.over').click();
			return true;
		}
		//Recovering the id of element clicked
		idItem = $(cmSelector + ' > div.idSelect').attr('id');
		
		if($(this).attr('id')=="cm_addElementInList"){
			$('#searchBar .toolbarBox .addNewElement').click();
			return true;
		}
		
		if($(this).hasClass('unchecked')) {
			checked = 1;
			$(this).removeClass('unchecked').addClass('checked');
		}
		else if($(this).hasClass('checked')) {
			checked = 0;
			$(this).removeClass('checked').addClass('unchecked');
		}
		else checked = -1;

		if($(this).attr('id')=="cm_edit"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/edit/'+idItem);
			return true;
		}
		if($(this).attr('id')=="cm_delete"){
			update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/delete/'+idItem+'/elementDialog');
			return true;
		}
		if($(this).attr('id')=="cm_copy"){
			copyWithOrganize('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName, idItem);
			//update('elementDialog/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/element/copy/'+idItem+'/'+$('#groupPanel li.selected').attr('id').split('_')[1]);
			return true;
		}
		if($(this).attr('id')=="cm_organize"){
			openOrganizeDialog(idItem);
			return true;
		}
		e.stopPropagation();
	});
}
function resize_calendar(){
	if($('#moduleView .calendar').length>0){
		$('#moduleView .calendar').height($('#groupPanel').height() - parseInt($('#moduleView .calendar').css('margin-top'),10));
		$('#moduleView .calendar').fullCalendar('option', 'height', $('#moduleView .calendar').height());
	}
}
function selectEvent_elementCalendar(){
	if(switchSelectionModeIsMultiple){
		//multiple mode
		$('#moduleView .calendar div.over').addClass('M');
	} else {
		//unique mode
		$('#moduleView .calendar .S').removeClass('S');
		$('#moduleView .calendar div.over').addClass('S');
	}
	elementCalendar_currentEventSelected = elementCalendar_contextMenuIdElement;
}
function clickEvent_elementCalendar(domObj, calEvent, jsEvent, view, myWigiiNamespace, module){
	if(switchSelectionModeIsMultiple){
		//multiple mode
		$(domObj).addClass('M');
	} else {
		//unique mode
		$('#moduleView .calendar .S').removeClass('S');
		$(domObj).addClass('S');
	}
	elementCalendar_currentEventSelected = calEvent.id;
	if(!switchSelectionModeIsMultiple){
		update('elementDialog/'+myWigiiNamespace+'/'+module+'/element/detail/'+elementCalendar_currentEventSelected);
	}
}

var crtBlogViewNbOfColumns = 2;
function resize_blog(){
	if($('#moduleView .blog').length>0){
		var modViewWidth = $('#moduleView').width();
		var blogWidth = Math.floor((modViewWidth-6-10) / crtBlogViewNbOfColumns); //contains margin 1x + rounding
		blogWidth = blogWidth - 42 - 2; //padding 2x, border 2x, margin 1x + rounding
		$('#moduleView>div.blog div.el:not(.max)').width(blogWidth);
		$('#moduleView>div.blog .dataBlog').height($(window).height()-$('#moduleView>div.blog .dataBlog').position().top - fb.outerHeight()-3);
	}
}

//Normalizing mousewheel speed across browsers
//find on http://stackoverflow.com/questions/5527601/normalizing-mousewheel-speed-across-browsers
function normalizeWheelSpeed(event) {
    var normalized;
    if (event.wheelDelta) {
        normalized = (event.wheelDelta % 120 - 0) == -0 ? event.wheelDelta / 120 : event.wheelDelta / 12;
    } else {
        var rawAmmount = event.deltaY ? event.deltaY : event.detail;
        normalized = -(rawAmmount % 3 ? rawAmmount * 10 : rawAmmount / 3);
    }
    return normalized;
}

//calculate the height of dialogeElement scroll area
function getElementDialogScrollHeight(name, object){
	var height = window.innerHeight;
	if(object.prop('id')=='scrollElement') {
		height = height - object.parent().parent().offset().top;
	} else {
		height = height - object.parent().offset().top;
	}	
	switch(name){
		case 'neighbour':
			height-= object.prev().outerHeight(true);
			if(object.next().hasClass('scrollGradient'))
				height-= object.next().next().outerHeight(true);
			else
				height-= object.next().outerHeight(true);
			break;
		case 'children':
			height-= object.parent().prev().outerHeight(true);
			height-= object.parent().children().first().outerHeight(true);
			break;
	}
	return (height -30);
}

//Add gradient on a scroll area
//if no scroll bar exist, create it
function addScrollWithShadow(idScrollElement, elementPreviousTop) {
	if(isWorkzoneViewMode()) return true;
	if (arguments.length<2) elementPreviousTop = 0;
	//change the CSS of an element
	function changeElementCss(element, cssRules){
		for(var index in cssRules) 
			if(cssRules[index]!='na') element.css(index,cssRules[index]);
	}
	//add the scroll event for an element
	function addScrollEvent(element) {	
		element.unbind('scroll');
		element.scroll(function() {
			var self = $(this);
			if(self.next().hasClass('scrollGradient'))
				self.next().css("display",((self.scrollTop() + self.outerHeight() >= this.scrollHeight) ? "none" : "block"));
		});
	}
	//add gradient div, passe the height in the css rules
	function addGradient(element, cssRules){
		if(element.next().hasClass('scrollGradient')) return;
		
		var html = '<div class="scrollGradient" style="';
		for(var index in cssRules)
			html+= index+':'+cssRules[index]+';';
		html+= '"></div>';
		element.after(html);
	}
	
	var scrollElement = $('#'+idScrollElement);
	var element = idScrollElement;
	if(jQuery.inArray(idScrollElement, ['elementDialog', 'emailingDialog', 'filtersDialog']) > -1)
		element = ((scrollElement.find('div.T').length == 0) ? "neighbour" : "children");
	var cssArray = {"height":"na","overflow-x":"hidden","overflow-y":"auto","margin-right":"na"};
	switch(element){
		case 'neighbour':
			if(scrollElement.parent().outerHeight(true) > window.innerHeight || scrollElement.prop('scrollHeight') > scrollElement.prop('clientHeight')) { //if the dialog box is bigger than the window height, then resize.				
				cssArray.height = getElementDialogScrollHeight(element, scrollElement);//window.innerHeight - scrollElement.prev().outerHeight(true) - scrollElement.next().outerHeight(true)-30;
				changeElementCss(scrollElement, cssArray);
				addScrollEvent(scrollElement);
				addGradient(scrollElement, {"bottom":(scrollElement.next().outerHeight(true))+"px"});
				scrollElement.next().css({"display":"block","width":(scrollElement.innerWidth()-18)+"px"});
			} else {
				scrollElement.css('overflow','visible');
			}
			break;
		case 'children':
			if(scrollElement.parent().outerHeight(true) > window.innerHeight) { //if the dialog box is bigger than the window height, then resize.
				scrollElement = $('#scrollElement');
				cssArray.height = getElementDialogScrollHeight(element, scrollElement);//window.innerHeight - scrollElement.prev().outerHeight(true) - toolbarHeight -30;
				cssArray["margin-right"] = "-7px";
				changeElementCss(scrollElement, cssArray);
				addScrollEvent(scrollElement);
				addGradient(scrollElement, {"bottom":"0px"});
				scrollElement.next().css({"display":"block","width":(scrollElement.innerWidth()-18)+"px"});
			} else {
				$('#scrollElement').css('overflow','visible');
			}
			break;
	}//end switch
	
	scrollElement.scrollTop(elementPreviousTop);
	if(scrollElement.next().hasClass('scrollGradient')) {
		scrollElement.next().unbind('mouseenter DOMMouseScroll mousewheel');
		scrollElement.next().mouseenter(function() {
			scrollElement.animate( { scrollTop: (scrollElement.scrollTop()+40) }, 750 );
		}).bind('DOMMouseScroll mousewheel', function(e){
			scrollElement.scrollTop(scrollElement.scrollTop()+(-normalizeWheelSpeed(e.originalEvent)*10));//*10 for better amplitude and - for invert the sens
	    });
	}
}

//resize function for elementDialog for the moment
function resize_scrollArea(keepScrollPosition){
	if (arguments.length<1) keepScrollPosition = false;
	if(isWorkzoneViewMode() && !crtModuleName=='Admin') return true;
	var elements = ['elementDialog', 'emailingDialog', 'filtersDialog'];
	var element = null;
	var elementName = 'elementDialog';
	var elementPreviousTop = 0;
	// find the type of dialog box by browsing the table 'elements' and look if the object has children
	$.each(elements, function(index, value) {
		var tmpElement = $('#'+value);	
		if (tmpElement.children().length > 0) {
			element = tmpElement;
			elementName = value;
		}
	});

	if(element!=null && element.parent().prop("tagName").toLowerCase()!='body') {
		if(keepScrollPosition) elementPreviousTop = ((element.find('#scrollElement').length == 1) ? element.find('#scrollElement').scrollTop() : element.scrollTop());
		var typeOfDialog = ((element.find('div.T').length == 0) ? "neighbour" : "children");
		if(typeOfDialog == 'neighbour'){
			element.css("height","");
			if(element.next().hasClass('scrollGradient'))
				element.next().css("display", "none");
		} else {
			$('#scrollElement').css("height","")
			.next().css("display", "none");
		}
		addScrollWithShadow(elementName, elementPreviousTop);
	}
}

function manageWorkzoneViewDocked(action, cardSize){
	if (arguments.length<1) action = 'show';
	if (arguments.length<2) cardSize = 1000;
	
	var elementDialog = $('#elementDialog'),
		moduleView = $('#moduleView'),
		collapseBar = $('#dockingContainer>.collapse'),
		scrollElement = $('#scrollElement');

	if(scrollElement.length==0) {
		elementDialog.append('<div id="scrollElement"></div>');
		scrollElement = $('#scrollElement').append(elementDialog.find('form'));
	}
	scrollElement.width(cardSize);
	
	switch (action){
		case 'show':		
			cardSize+=10;
			elementDialog.css({'display':'block','float':'left','width':cardSize+'px'});
			collapseBar.css('display','block');
			moduleView.css({'float':'left'});
			if ($('#moduleView:hidden').length) {
				collapseBar.find('span').html("&raquo;");
			} else {
				collapseBar.find('span').html("&laquo;");
			}
			$('.firstBox, .toolbarBox','#searchBar').css('display','none');
			elementDialog.scrollTop(0);
			break;
		case 'clear':
			elementDialog.html('');
		case 'hide':			
			elementDialog.css('display','none');
			$('#dockingContainer>.collapse').css('display','none');
			$('#moduleView').css({'float':'none'});
			$('.firstBox, .toolbarBox','#searchBar').css('display','block');
			$('#searchBar .middleBox').css('display','none');
			break;
	};
	
	resize_elementList();
}

//Re-initializes middle box
function initMiddleBox(keepExistingBox){
	if (arguments.length<1) keepExistingBox = false;
	
	var middleBox = $('#searchBar .middleBox');
	var firstBox = $('#searchBar .firstBox');

	if(!keepExistingBox && middleBox.length > 0) {
		middleBox.remove();
	};
	
	middleBox = $('#searchBar .middleBox');
	
	if(middleBox.length == 0) {
		firstBox.after('<div class="middleBox"></div>');
		middleBox = $('#searchBar .middleBox');
	}
	
	if(middleBox.find('div.T').length==0) middleBox.append('<div class=\"T docked\"></div>');
	
	var height = $('#searchBar .firstBox').height();
	return $('#searchBar .middleBox').height(height);
}