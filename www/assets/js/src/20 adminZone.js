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

//User filter
userFilterDefaultValue = null;
user2FilterDefaultValue = null;
groupFilterDefaultValue = null;


userFilterLastValue = null;
function adminFilterUser_input_focus(){
	v = $('#adminFilterUser input');
	if(v.hasClass('empty')){
		v.removeClass('empty').removeClass('grayFont').val('');
	} else {
		v.select();
	}
	userFilterLastValue = v.val();
}
adminFilterTextOnBlurTimeout = null;
function adminFilterUser_input_blur(){
	v = $('#adminFilterUser input');
	changed = userFilterLastValue != v.val();
	if(v.val()==''){
		v.addClass('empty').addClass('grayFont').val(userFilterDefaultValue);
	}
	if(changed) {
		//reset the paging
		$('#adminFilterUser .select:eq(1) :input').val("1");
		adminFilterTextOnBlurTimeout = setTimeout(function(){$('#adminFilterUser div.goButton').click(); }, 100);
	}
}

function adminFilterUser_select_change(e){
	s = $('#adminFilterUser select[name=__userFilterType]');
	p = $('#adminFilterUser select[name=__horizontalPagingText]');
	if(s.val()!='none'){
		s.removeClass('empty').removeClass('grayFont');
	} else {
		s.addClass('empty').addClass('grayFont');
	}
	if(e.target.name =="__userFilterType"){
		p.val("1");
	}
	$('#adminFilterUser div.goButton').click();
}

function adminFilterUser_goButton_click(crtWigiiNamespace, crtModule){
	clearTimeout(adminFilterTextOnBlurTimeout);

	setVis("busyDiv", true);

	url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/setFilterUser/';

	inputTextFilter = $('#adminFilterUser input');
	if(inputTextFilter.hasClass('empty')){
		tempText = inputTextFilter.val();
		inputTextFilter.val('');
	}

	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			data: $('#adminFilterUser :input').serialize(),
			type: 'POST',
			success : parseUpdateResult,
			cache:false,
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;

	if(inputTextFilter.hasClass('empty')){
		inputTextFilter.val(tempText);
	}
}

function adminFilterUser_reset(crtWigiiNamespace, crtModule){
	t = $('#adminFilterUser input');
	s = $('#adminFilterUser select[name=__userFilterType]');
	p = $('#adminFilterUser select[name=__horizontalPagingText]');
	t.val(userFilterDefaultValue).addClass('empty').addClass('grayFont');
	s.val('none').addClass('empty').addClass('grayFont');
	p.val(1);
	adminFilterTextOnBlurTimeout = setTimeout(function(){ $('#adminFilterUser div.goButton').click(); }, 100);
}

function adminFilterUserType_disable(isDisabled){
	if(isDisabled){
		$('#adminFilterUser :input[name="__userFilterType"]').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
	} else {
		$('#adminFilterUser :input[name="__userFilterType"]').attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
	}
}
function adminFilterUserText_disable(isDisabled){
	if(isDisabled){
		$('#adminFilterUser :input[name="__userFilterText"]').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
	} else {
		$('#adminFilterUser :input[name="__userFilterText"]').attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
	}
}
function adminUserPaging_enable(isEnabled){
	p = $('#adminFilterUser select[name=__horizontalPagingText]');
	if(isEnabled){
		p.attr('disabled', false).removeClass('disabled').css('background-color', '#fff').show();
	} else {
		p.val('1').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6').hide();
	}
}
function adminFilterUser_hide(isHidden){
	if(isHidden){
		$('#adminFilterUser').hide();
	} else {
		$('#adminFilterUser').show();
	}
}

function adminUserPaging_matchFor(crt, total, prefix){
	select = $('#adminFilterUser select[name=__horizontalPagingText]');
	select.find('option').remove();
	for(var i = 1; i<total+1; i++){
		if(crt == i){
			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" selected="selected" >'+prefix+' '+i+'</option>');
		} else {
			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" >'+prefix+' '+i+'</option>');
		}
	}
	if(total > 1){
		select.removeClass("grayFont").addClass("blackFont");
	} else {
		select.addClass("grayFont").removeClass("blackFont");
	}
}

//User2 filter
user2FilterLastValue = null;
function adminFilterUser2_input_focus(){
	v = $('#adminFilterUser2 input');
	if(v.hasClass('empty')){
		v.removeClass('empty').removeClass('grayFont').val('');
	} else {
		v.select();
	}
	user2FilterLastValue = v.val();
}
adminFilterTextOnBlurTimeout = null;
function adminFilterUser2_input_blur(){
	v = $('#adminFilterUser2 input');
	changed = user2FilterLastValue != v.val();
	if(v.val()==''){
		v.addClass('empty').addClass('grayFont').val(user2FilterDefaultValue);
	}
	if(changed) {
		//reset the paging
		$('#adminFilterUser2 .select:eq(1) :input').val("1");
		adminFilterTextOnBlurTimeout = setTimeout(function(){$('#adminFilterUser2 div.goButton').click(); }, 100);
	}
}

function adminFilterUser2_select_change(e){
	s = $('#adminFilterUser2 select[name=__user2FilterType]');
	p = $('#adminFilterUser2 select[name=__horizontalPagingText]');
	if(s.val()!='none'){
		s.removeClass('empty').removeClass('grayFont');
	} else {
		s.addClass('empty').addClass('grayFont');
	}
	if(e.target.name =="__user2FilterType"){
		p.val("1");
	}
	$('#adminFilterUser2 div.goButton').click();
}

function adminFilterUser2_goButton_click(crtWigiiNamespace, crtModule){
	clearTimeout(adminFilterTextOnBlurTimeout);

	setVis("busyDiv", true);

	url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/setFilterUser2/';

	inputTextFilter = $('#adminFilterUser2 input');
	if(inputTextFilter.hasClass('empty')){
		tempText = inputTextFilter.val();
		inputTextFilter.val('');
	}

	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			data: $('#adminFilterUser2 :input').serialize(),
			type: 'POST',
			success : parseUpdateResult,
			cache:false,
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;

	if(inputTextFilter.hasClass('empty')){
		inputTextFilter.val(tempText);
	}
}

function adminFilterUser2_reset(crtWigiiNamespace, crtModule){
	t = $('#adminFilterUser2 input');
	s = $('#adminFilterUser2 select[name=__user2FilterType]');
	p = $('#adminFilterUser2 select[name=__horizontalPagingText]');
	t.val(user2FilterDefaultValue).addClass('empty').addClass('grayFont');
	s.val('none').addClass('empty').addClass('grayFont');
	p.val(1);
	adminFilterTextOnBlurTimeout = setTimeout(function(){ $('#adminFilterUser2 div.goButton').click(); }, 100);
}

function adminFilterUser2Type_disable(isDisabled){
	if(isDisabled){
		$('#adminFilterUser2 :input[name="__user2FilterType"]').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
	} else {
		$('#adminFilterUser2 :input[name="__user2FilterType"]').attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
	}
}
function adminFilterUser2Text_disable(isDisabled){
	if(isDisabled){
		$('#adminFilterUser2 :input[name="__user2FilterText"]').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
	} else {
		$('#adminFilterUser2 :input[name="__user2FilterText"]').attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
	}
}
function adminUser2Paging_enable(isEnabled){
	p = $('#adminFilterUser2 select[name=__horizontalPagingText]');
	if(isEnabled){
		p.attr('disabled', false).removeClass('disabled').css('background-color', '#fff').show();
	} else {
		p.val('1').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6').hide();
	}
}
function adminFilterUser2_hide(isHidden){
	if(isHidden){
		$('#adminFilterUser2').hide();
	} else {
		$('#adminFilterUser2').show();
	}
}

function adminUser2Paging_matchFor(crt, total, prefix){
	select = $('#adminFilterUser2 select[name=__horizontalPagingText]');
	select.find('option').remove();
	for(var i = 1; i<total+1; i++){
		if(crt == i){
			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" selected="selected" >'+prefix+' '+i+'</option>');
		} else {
			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" >'+prefix+' '+i+'</option>');
		}
	}
	if(total > 1){
		select.removeClass("grayFont").addClass("blackFont");
	} else {
		select.addClass("grayFont").removeClass("blackFont");
	}
}

//GROUPS
groupFilterLastValue = null;
function adminFilterGroup_input_focus(){
	v = $('#adminFilterGroup input');
	if(v.hasClass('empty')){
		v.removeClass('empty').removeClass('grayFont').val('');
	} else {
		v.select();
	}
	groupFilterLastValue = v.val();
}
adminFilterTextOnBlurTimeout = null;
function adminFilterGroup_input_blur(){
	v = $('#adminFilterGroup input');
	changed = groupFilterLastValue != v.val();
	if(v.val()==''){
		v.addClass('empty').addClass('grayFont').val(groupFilterDefaultValue);
	}
	if(changed){
		//reset the paging
//		$('#adminFilterGroup .select:eq(1) :input').val("1");
		adminFilterTextOnBlurTimeout = setTimeout(function(){$('#adminFilterGroup div.goButton').click(); }, 100);
	}
}

//function adminFilterGroup_select_change(e){
//	p = $('#adminFilterGroup select[name=__horizontalPagingText]');
//	if(e.target.name =="__userFilterType"){
//		p.val("1");
//	}
//	$('#adminFilterGroup div.goButton').click();
//}

function adminFilterGroup_goButton_click(crtWigiiNamespace, crtModule){
	clearTimeout(adminFilterTextOnBlurTimeout);

	setVis("busyDiv", true);

	url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/setFilterGroup/';

	inputTextFilter = $('#adminFilterGroup input');
	if(inputTextFilter.hasClass('empty')){
		tempText = inputTextFilter.val();
		inputTextFilter.val('');
	}

	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			data: $('#adminFilterGroup :input').serialize(),
			type: 'POST',
			success : parseUpdateResult,
			cache:false,
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;

	if(inputTextFilter.hasClass('empty')){
		inputTextFilter.val(tempText);
	}
}

function adminFilterGroup_reset(crtWigiiNamespace, crtModule){
	t = $('#adminFilterGroup input');
//	p = $('#adminFilterGroup select[name=__horizontalPagingText]');
	t.val(groupFilterDefaultValue).addClass('empty').addClass('grayFont');
//	p.val(1);
	adminFilterTextOnBlurTimeout = setTimeout(function(){ $('#adminFilterGroup div.goButton').click(); }, 100);
}

function adminFilterGroupText_disable(isDisabled){
	if(isDisabled){
		$('#adminFilterGroup :input[name="__groupFilterText"]').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
	} else {
		$('#adminFilterGroup :input[name="__groupFilterText"]').attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
	}
}
//function adminGroupPaging_enable(isEnabled){
//	p = $('#adminFilterGroup select[name=__horizontalPagingText]');
//	if(isEnabled){
//		p.attr('disabled', false).removeClass('disabled').css('background-color', '#fff');
//	} else {
//		p.val('1').attr('disabled', true).addClass('disabled').css('background-color', '#e6e6e6');
//	}
//}
function adminFilterGroup_hide(isHidden){
	if(isHidden){
		$('#adminFilterGroup').hide();
	} else {
		$('#adminFilterGroup').show();
	}
}

//function adminGroupPaging_matchFor(crt, total, prefix){
//	select = $('#adminFilterGroup select[name=__horizontalPagingText]');
//	select.find('option').remove();
//	for(var i = 1; i<total+1; i++){
//		if(crt == i){
//			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" selected="selected" >'+prefix+' '+i+'</option>');
//		} else {
//			select.append('<option class="blackFont" value="'+i+'" title="'+prefix+' '+i+'" >'+prefix+' '+i+'</option>');
//		}
//	}
//	if(total > 1){
//		select.removeClass("grayFont").addClass("blackFont");
//	} else {
//		select.addClass("grayFont").removeClass("blackFont");
//	}
//}

function adminButton_click(doAction, crtWigiiNamespace, crtModule, idButton, groupFilterLabel, userFilterLabel, roleFilterLabel, okLabel, cancelLabel, displayAllLabel, guGroupFilterLabel, guGroupFilterExplanation, guUserFilterLabel, guUserFilterExplanation, guScreenshot, urUserFilterLabel, urUserFilterExplanation, urRoleFilterLabel, urRoleFilterExplanation, urScreenshot, uuUserFilterLabel, uuUserFilterExplanation, uuUser2FilterLabel, uuUser2FilterExplanation, uuScreenshot, dialogTitle, title, message, workingModule){
	//define empty filter value
	if (typeof workingModule === 'undefined') { workingModule = null; }
	var idButtonPlus = idButton;
	if(idButton.indexOf('_')) {
		temp = idButton.split('_');
		idButton = temp[0];
	}
	if(idButton =="adminGroup"){
		userFilterDefaultValue = null;
		user2FilterDefaultValue = null;
		groupFilterDefaultValue = groupFilterLabel;
	} else if(idButton == "adminUser"){
		userFilterDefaultValue = userFilterLabel;
		user2FilterDefaultValue = null;
		groupFilterDefaultValue = null;
	} else if(idButton == "adminRole"){
		userFilterDefaultValue = roleFilterLabel;
		user2FilterDefaultValue = null;
		groupFilterDefaultValue = null;
	} else if(idButton == "adminGroupUser"){
		userFilterDefaultValue = guUserFilterLabel;
		user2FilterDefaultValue = null;
		groupFilterDefaultValue = guGroupFilterLabel;
	} else if(idButton == "adminUserRole"){
		userFilterDefaultValue = urUserFilterLabel;
		user2FilterDefaultValue = urRoleFilterLabel;
		groupFilterDefaultValue = null;
	} else if(idButton == "adminUserUser"){
		userFilterDefaultValue = uuUserFilterLabel;
		user2FilterDefaultValue = uuUser2FilterLabel;
		groupFilterDefaultValue = null;
	} else if(idButton == "adminUserAdmin"){
		userFilterDefaultValue = userFilterLabel;
		user2FilterDefaultValue = null;
		groupFilterDefaultValue = null;
	}
	
	var parentMenu = $('#'+idButtonPlus).parent().parent();
	if(doAction && parentMenu.prop('id')=='adminGroupMenu') {
		tmpText = $('#adminGroup').children().first().children().first().contents()[0].data.split('(');
		$('#adminGroup').children().first().children().first().contents()[0].data = tmpText[0]+' ('+$('#'+idButtonPlus).text().trim()+') ';
	}
	
	if(!(doAction && (idButton =="adminGroupUser" || idButton =="adminUserRole" || idButton =="adminUserUser"))){
		i1 = $('#adminFilterGroup input:first');
		i2 = $('#adminFilterUser input:first');
		i3 = $('#adminFilterUser2 input:first');
		if(i1.hasClass('empty')) i1.val(groupFilterDefaultValue);
		if(i2.hasClass('empty')) i2.val(userFilterDefaultValue);
		if(i3.hasClass('empty')) i3.val(user2FilterDefaultValue);
	}
	//if group/role/user matrix, display filters as dialog before.
	if(doAction && (idButton =="adminGroupUser" || idButton =="adminUserRole" || idButton =="adminUserUser")){
		$('#elementDialog').html('');
		$('#elementDialog')
		.append('<div class="introduction" style="width:400px;margin-bottom:10px; margin-top:8px;"><img src="'+SITE_ROOT+'images/icones/26px/magnifier.png" /><div style="margin-left:5px;padding-top:7px;font-size:14px;font-weight:bold;">'+title+'</div><div class="clear"></div><div style="margin-top:5px;">'+message+'</div></div>')
		.append('<div class="clear"></div><hr class="SBB" style="margin:20px;" />');
		$('#elementDialog')
		.append('<div class="filters"></div>')
		;
		//prepare dialog content
		if(idButton =="adminGroupUser"){
			$('#elementDialog div.filters')
			.append('<div class="label">'+guGroupFilterLabel+'</div>').append($('#adminFilterGroup input').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+guGroupFilterExplanation+'</div><div class="clear"></div>')
			.append('<div class="label">'+guUserFilterLabel+'</div>').append($('#adminFilterUser input').clone()).append('<div class="clear"></div>')
			.append('<div class="label"></div>').append($('#adminFilterUser select:first').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+guUserFilterExplanation+'</div><div class="clear"></div>')
			;
			$('#elementDialog div.filters input:first').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(guGroupFilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.filters input:last').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(guUserFilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.introduction').before('<div class="screenshot"><img src="'+SITE_ROOT+guScreenshot+'" /></div>');
			$('#elementDialog div.filters select').removeAttr('disabled').removeClass('disabled').removeClass('grayFont').css('background-color', 'transparent');
		} else if (idButton =="adminUserRole"){
			$('#elementDialog div.filters')
			.append('<div class="label">'+urUserFilterLabel+'</div>').append($('#adminFilterUser input').clone()).append('<div class="clear"></div>')
			.append('<div class="label"></div>').append($('#adminFilterUser select:first').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+urUserFilterExplanation+'</div><div class="clear"></div>')
			.append('<div class="label">'+urRoleFilterLabel+'</div>').append($('#adminFilterUser2 input').clone()).append('<div class="clear"></div>')
			.append('<div class="label"></div>').append($('#adminFilterUser2 select:first').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+urRoleFilterExplanation+'</div><div class="clear"></div>')
			;
			$('#elementDialog div.filters input:first').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(urUserFilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.filters input:last').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(urRoleFilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.introduction').before('<div class="screenshot"><img src="'+SITE_ROOT+urScreenshot+'" /></div>');
			$('#elementDialog div.filters select').hide();
		} else if (idButton =="adminUserUser"){
			$('#elementDialog div.filters')
			.append('<div class="label">'+uuUserFilterLabel+'</div>').append($('#adminFilterUser input').clone()).append('<div class="clear"></div>')
			.append('<div class="label"></div>').append($('#adminFilterUser select:first').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+uuUserFilterExplanation+'</div><div class="clear"></div>')
			.append('<div class="label">'+uuUser2FilterLabel+'</div>').append($('#adminFilterUser2 input').clone()).append('<div class="clear"></div>')
			.append('<div class="label"></div>').append($('#adminFilterUser2 select:first').clone()).append('<div class="clear"></div>')
//			.append('<div class="explanation">'+uuUser2FilterExplanation+'</div><div class="clear"></div>')
			;
			$('#elementDialog div.filters input:first').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(uuUserFilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.filters input:last').blur(function(){ if($(this).val()=='' || $(this).hasClass('empty')){ $(this).addClass('empty').addClass('grayFont').val(uuUser2FilterExplanation); }}).focus(function(){ if($(this).hasClass('empty')){ $(this).val('').removeClass('empty').removeClass('grayFont'); }});
			$('#elementDialog div.introduction').before('<div class="screenshot"><img src="'+SITE_ROOT+uuScreenshot+'" /></div>');
			$('#elementDialog div.filters select').removeAttr('disabled').removeClass('disabled').removeClass('grayFont').css('background-color', 'transparent');
		}
		//$('#elementDialog .empty').val('').removeClass('empty').removeClass('grayFont');
		$('#elementDialog div:not(.clear, .filters), #elementDialog img').css('float', 'left');
		$('#elementDialog div.label').css('white-space', 'nowrap').css("margin", "5px 5px 2px 5px");
		$('#elementDialog div.screenshot').css("margin", "5px 15px 0px 5px");
		$('#elementDialog input').css('float', 'left').width(280).css("padding", "1px").css("margin", "5px 5px 2px 5px");
		$('#elementDialog select').css('float', 'left').width(160).css("margin", "5px 5px 2px 5px");
		$('#elementDialog :input').keydown(function(e){
			if(e.keyCode == 13){ $('#elementDialog').closest('.ui-dialog').children('.ui-dialog-buttonpane').find('button.ok').click(); }
			else if (e.keyCode == 27){ $('#elementDialog').closest('.ui-dialog').children('.ui-dialog-buttonpane').find('button.cancel').click(); }
			e.stopPropagation();
		});
		var buttons = {};
		buttons[okLabel] =
			{
			click: function(){
				setVis("busyDiv", true);

				url = SITE_ROOT +'Update/'+crtContextId+EXEC_requestSeparator+ 'NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/setFilterFor'+idButton.replace('admin', '');
				if(workingModule!=null) {
					url+= '/' + workingModule;
				}
				$('#elementDialog input.empty').val('');

				var myAjax = new jQuery.ajax({
						url: encodeURI(url),
						data: $('#elementDialog :input').serialize(),
						type: 'POST',
						success : parseUpdateResult,
						cache:false,
						error: errorOnUpdate
					});
				onUpdateErrorCounter = 0;

				$('#elementDialog').html('').dialog("destroy");
			},
			"class": 'ok',
			text : okLabel
			};
		buttons[displayAllLabel] =
			{
			click: function(e){
				$('#elementDialog input').val('');
				$('#elementDialog select').val('');
				$(e.target).prev().click();
			},
			"class": 'reset',
			text : displayAllLabel
			};
		buttons[cancelLabel] =
			{
			click: function(e){ $(this).dialog("destroy"); },
			"class": 'cancel',
			text : cancelLabel
			};

		if($('#elementDialog').is(':ui-dialog')){ $('#elementDialog').dialog("destroy"); } $('#elementDialog').dialog({
			title: dialogTitle+$('#'+idButton).text(),
			width:560,
			position: { my: "center", at: "center" },
			buttons: buttons,
			modal:true,
			dragStop: function(event, ui){ },
			beforeClose: function(){ actOnCloseDialog("elementDialog"); $(this).dialog("destroy"); },
			closeOnEscape: false, resizable:false
		});
		$('#elementDialog').next().find('button.ok').focus();
		$('#elementDialog input').each(function(){ $(this).blur();});
		tempMax = 117;
		$('#elementDialog div.label').each(function(){ tempMax = Math.max(tempMax, $(this).width());});
		$('#elementDialog div.label').width(tempMax);
		$('#elementDialog input').width(550-50-tempMax);
		return;
	}

	//subButton get the submenu action name
	var subButton = '';
	var button = $('#'+idButton);
	var parentButton = button.parent().parent();
	$('#adminSearchBar').find('.S').removeClass('S');
	switch(parentButton.attr('id')) {
		case 'adminAccessMenu': 
			$('#adminAccess').addClass('S');
			subButton = workingModule;
			break;
		case 'adminGroupMenu':
			$('#adminGroup').addClass('S');
			subButton = idButton;
			idButton = 'adminGroup';
			break;
	} 
	button.addClass('S');

	adminFilterUser_hide(idButton=="adminGroup" || idButton=="adminModuleEditor" );
	adminFilterUser2_hide(idButton=="adminGroup" || idButton=="adminModuleEditor" || idButton=="adminUser" || idButton=="adminRole" || idButton=="adminGroupUser" || idButton=="adminUserAdmin" );
	adminFilterGroup_hide(idButton=="adminUser" || idButton=="adminRole" || idButton=="adminModuleEditor" || idButton=="adminUserRole" || idButton=="adminUserUser" || idButton=="adminUserAdmin");

	adminFilterUserType_disable(idButton=="adminUser" || idButton=="adminRole");
	adminFilterUser2Type_disable(idButton=="adminUserRole");

	adminUserPaging_enable(idButton=="adminGroupUser");
	adminUser2Paging_enable(idButton=="adminUserRole" || idButton=="adminUserUser");
//	adminGroupPaging_enable(idButton=="adminGroupUser");

	$('#adminFilterUser select[name=__horizontalPagingText]').val("1");
	$('#adminFilterUser2 select[name=__horizontalPagingText]').val("1");
//	$('#adminFilterGroup select[name=__horizontalPagingText]').val("1");

	if(doAction && subButton=='') {
		update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/switchAdminTo/'+idButton);
	} else if(doAction && subButton!='') {
		update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/switchAdminTo/'+idButton+'/'+subButton);
	}
}

//*************************************
//Matrix
//*************************************

function matrixResize(id){
	m = $('#'+id);
	if(m.length == 0) return;

	totalW = m.width();
	totalH = $(window).height()-m.offset().top-$('#footerBar').outerHeight()-2;

	//alert(totalW+" "+totalH);

	corn = $('.MatrixCorner', m);
	mR = $('.MatrixRowHeaders', m);
	mC = $('.MatrixColHeaders', m);
	mI = $('.MatrixItems', m);
	sL = $('.ScrollLeftZone', m);
	sR = $('.ScrollRightZone', m);
	sU = $('.ScrollUpZone', m);
	sD = $('.ScrollDownZone', m);

	//the rowHeaders should not be more than the half of the screen
	mR.css('max-width', m.width()*0.5);

	//resize the corner
	if(jQuery.browser.msie && version <= 7.0){
		corn.width(mR.width()+10);
		corn.height(mC.height()+5);
	} else {
		corn.width(mR.width()+5);
		corn.height(mC.height()+5);
	}

	//resize the colHeaders container
	mC.width(totalW-corn.outerWidth()-10);

	//resize the rowHeaders container
	//use only the standard scrolling in Admin (reason commented)
	//mR.height(totalH-corn.outerHeight()-5);
	//mR.height(mR.height()+30);
	mR.css('margin-bottom', '30px');

	//resize the matrixItem
	if(jQuery.browser.msie && version <= 7.0){
		mI.width(mC.width());
		mI.height(mR.height()+5);
	} else {
		mI.width(mC.width()-1);
		mI.height(mR.height());
	}

	//place the scrollZones
	if(jQuery.browser.msie && version <= 7.0){
		sR.height(mC.height()+5+mI.height()+10);
	} else {
		sR.height(mC.height()+mI.height()+5);
	}
	sR.css('top', mC.offset().top+5);
	sR.css('left', mC.offset().left+mC.outerWidth()-sR.outerWidth()-5);
	if(jQuery.browser.msie && version <= 7.0){
		sL.height(mC.height()+5+mI.height()+10);
	} else {
		sL.height(mC.height()+mI.height()+5);
	}
	sL.css('top', sR.css('top'));
	sL.css('left', mC.offset().left+5);

	if(jQuery.browser.msie && version <= 7.0){
		sU.width(mR.width()+5+mI.width()+10);
	} else {
		sU.width(mR.width()+mI.width()+5);
	}
	sU.css('top', mR.offset().top);
	sU.css('left', 5);
	if(jQuery.browser.msie && version <= 7.0){
		sD.width(mR.width()+5+mI.width()+10);
	} else {
		sD.width(mR.width()+mI.width()+5);
	}
	sD.css('top', mR.offset().top+mR.outerHeight() - sD.outerHeight()-5);
	sD.css('left', sU.css('left'));

	//hide display the scrollZones
	showScrollZones(id)

	//alert(mC.children('table').width());

}


function scrollLeft(id){
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixItems table').animate( { marginLeft: 0}, -parseInt($('#'+id+' .MatrixColHeaders table').css('marginLeft').replace('px', ''))*5, "swing", function(){ stopScroll(id); });
}
function goLeftScroll(id){
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixItems table').stop().css('marginLeft',0);
	showScrollZones(id);
}
function scrollRight(id){
	max = $('#'+id+' .MatrixColHeaders table').width()-$('#'+id+' .MatrixColHeaders').width()+100;
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixItems table').animate( { marginLeft: -max}, max*5, "swing", function(){ stopScroll(id); });
}
function goRightScroll(id){
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixItems table').stop();
	max = $('#'+id+' .MatrixColHeaders table').width()-$('#'+id+' .MatrixColHeaders').width()+100;
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixItems table').css('marginLeft', -max);
	showScrollZones(id);
}
function scrollUp(id){
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').animate( { marginTop: 0}, -parseInt($('#'+id+' .MatrixRowHeaders div:first').css('marginTop').replace('px', ''))*10, "swing", function(){ stopScroll(id); });
}
function goUpScroll(id){
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').stop();
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').css('marginTop', 0);
	showScrollZones(id);
}
function scrollDown(id){
	max = -$('#'+id+' .MatrixRowHeaders div:first').offset().top+$('#'+id+' .MatrixRowHeaders div:last').offset().top+$('#'+id+' .MatrixRowHeaders div:last').outerHeight()-$('#'+id+' .MatrixRowHeaders').height()+20;
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').animate( { marginTop: -max}, max*10, "swing", function(){ stopScroll(id); });
}
function goDownScroll(id){
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').stop();
	max = -$('#'+id+' .MatrixRowHeaders div:first').offset().top+$('#'+id+' .MatrixRowHeaders div:last').offset().top+$('#'+id+' .MatrixRowHeaders div:last').outerHeight()-$('#'+id+' .MatrixRowHeaders').height()+20;
	$('#'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').css('marginTop', -max);
	showScrollZones(id);
}
function stopScroll(id){
	$('#'+id+' .MatrixColHeaders table, #'+id+' .MatrixRowHeaders div:first, #'+id+' .MatrixItems table').stop();
	showScrollZones(id);
}

function showScrollZones(id){
	m = $('#'+id);
	if(m.length == 0) return;

	mR = $('.MatrixRowHeaders div:first', m);
	mC = $('.MatrixColHeaders table', m);
	mI = $('.MatrixItems table', m);
	sL = $('.ScrollLeftZone', m);
	sR = $('.ScrollRightZone', m);
	sU = $('.ScrollUpZone', m);
	sD = $('.ScrollDownZone', m);

	//hide display the scrollZones
	//use only the standard scrolling in Admin
	sU.hide();
//	if(mR.css('marginTop')=='0px'){
//		sU.hide();
//		mI.css('marginTop', 0);
//	} else sU.show();
	if(mC.css('marginLeft')=='0px') {
		sL.hide();
		mI.css('marginLeft', 0);
	} else sL.show();

	//use only the standard scrolling in Admin
	sD.hide();
//	if(-$('#'+id+' .MatrixRowHeaders').offset().top+$('#'+id+' .MatrixRowHeaders div:last').offset().top+$('#'+id+' .MatrixRowHeaders div:last').outerHeight()+10 > $('#'+id+' .MatrixRowHeaders').height()) sD.show();
//	else sD.hide();

	if($('#'+id+' .MatrixColHeaders').width() - $('#'+id+' .MatrixColHeaders table').width()-50 < parseInt(mC.css('marginLeft').replace('px',''))) sR.show();
	else sR.hide();

}

//higlights
function unHighlight(id){
	if(typeof(id) == 'string'){
		myObj = $('#'+id);
	} else myObj = $(this);

	if(!myObj.hasClass('Matrix')){
		myObj = myObj.parents('.Matrix');
	}
	myObj.find('.highlight, .active').removeClass('active').removeClass('highlight').removeClass('highlightV');
}
function highlightFromRowHeader(id){
	if(typeof(id) == 'string'){
		obj = $('#'+id);
	} else obj = $(this);
	unHighlight(id);
	idRow = obj.attr('id');
	idM = obj.parents('.Matrix').attr('id');
	$('#'+idRow+', #'+idM+' .'+idRow+' td>div').addClass('highlight');
}
function highlightFromColHeader(){
	idCol = $(this).attr('id');
	unHighlight(idCol);
	idM = $(this).parents('.Matrix').attr('id');
	$('#'+idCol+', #'+idM+' .'+idCol+'>div').addClass('highlight').addClass('highlightV');
	$('#'+idCol).parents('td').addClass('highlight').addClass('highlightV');
}
function highlightFromMatrixItem(){
	idCol = $(this).parent().attr('class');
	unHighlight(idCol); // unhiglight from the col, just to have an id
	idRow = $(this).parent().parent().attr('class');
	idM = $(this).parents('.Matrix').attr('id');
	$(this).addClass('active');
	$('#'+idRow+', #'+idM+' .'+idRow+' td>div').addClass('highlight');
	$('#'+idCol+', #'+idCol+', #'+idM+' .'+idCol+'>div').addClass('highlight').addClass('highlightV');
	$('#'+idCol).parents('td').addClass('highlight').addClass('highlightV');
}


//context menu
valueContextMenu_crtRow = null;
valueContextMenu_crtCol = null;
function showValueContextMenu(e, obj, idContextMenu){
	valueContextMenu_crtCol = $(obj).parent().attr('class');
	valueContextMenu_crtRow = $(obj).parent().parent().attr('class');

	if($('#'+valueContextMenu_crtCol+'').hasClass('noRights')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		return;
	}

	if($('#'+valueContextMenu_crtRow+'').hasClass('RR')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		$('#'+idContextMenu+' .ugr_').show();
		$('#'+idContextMenu+' .ugr_r').show();
		$('#'+idContextMenu+' .ugr_s').hide();
		$('#'+idContextMenu+' .ugr_w').hide();
		$('#'+idContextMenu+' .ugr_x').hide();
	}
	if($('#'+valueContextMenu_crtRow+'').hasClass('RS')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		$('#'+idContextMenu+' .ugr_').show();
		$('#'+idContextMenu+' .ugr_r').show();
		$('#'+idContextMenu+' .ugr_s').show();
		$('#'+idContextMenu+' .ugr_w').hide();
		$('#'+idContextMenu+' .ugr_x').hide();
	}
	if($('#'+valueContextMenu_crtRow+'').hasClass('RW')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		$('#'+idContextMenu+' .ugr_').show();
		$('#'+idContextMenu+' .ugr_r').show();
		$('#'+idContextMenu+' .ugr_s').show();
		$('#'+idContextMenu+' .ugr_w').show();
		$('#'+idContextMenu+' .ugr_x').hide();
	}
	if($('#'+valueContextMenu_crtRow+'').hasClass('RX')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		$('#'+idContextMenu+' .ugr_').show();
		$('#'+idContextMenu+' .ugr_r').show();
		$('#'+idContextMenu+' .ugr_s').show();
		$('#'+idContextMenu+' .ugr_w').show();
		$('#'+idContextMenu+' .ugr_x').show();
	}

	obj = $(obj);
	cm = $('#'+idContextMenu);
	positionElementOnMouse(cm, e, 'center', obj, -50);
	cm.show();


	obj.unbind('mouseleave');
}
function hideValueContextMenu(e, obj, idContextMenu){
	//unHighlight(idContextMenu);
	$('#'+idContextMenu).hide();
}

function clickOnRightInGroupUserMatrixValueContextMenu(contextButton, crtWigiiNamespace, crtModule, workingModule){
	contextButton = $(contextButton);

	right = 'none'; //no  rights
	if(contextButton.hasClass('ugr_r')) right = 'r';
	if(contextButton.hasClass('ugr_s')) right = 's';
	if(contextButton.hasClass('ugr_w')) right = 'w';
	if(contextButton.hasClass('ugr_x')) right = 'x';

	//check rights on doing action
	if($('#'+valueContextMenu_crtCol+'').hasClass('noRights')){ // || !$('#'+valueContextMenu_crtRow+'').hasClass('RX')){
		return;
	}

	//change content of the value:
	obj = $('tr.'+valueContextMenu_crtRow+' td.'+valueContextMenu_crtCol+'>div>div');
	tempRight = right;
	if(tempRight == "none") tempRight = "";
	obj.removeClass().addClass('ugr_'+tempRight);
	if(right=="none"){
		obj.text('');
	} else {
		obj.text(contextButton.text());
	}

	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/GroupUserMatrix/setUgr/'+workingModule+'/'+right+'/'+valueContextMenu_crtRow+'/'+valueContextMenu_crtCol)

}

standardContextMenu_crtHeader = null;
function showStandardContextMenu(e, obj, idContextMenu){
	standardContextMenu_crtHeader = $(obj).attr('id')

	obj = $(obj);
	cm = $('#'+idContextMenu);
	positionElementOnMouse(cm, e, 'right', obj);
	cm.show();

	obj.unbind('mouseleave');
}
function hideStandardContextMenu(e, obj, idContextMenu){
	//unHighlight(idContextMenu);
	$('#'+idContextMenu).hide();
}
function clickOnAcitivityInGroupUserMatrixGroupContextMenu(contextButton, crtWigiiNamespace, crtModule, workingModule){
	contextButton = $(contextButton);

	activities = ["groupEdit", "groupDelete", "groupEmailNotification", "groupPortal", "groupHtmlContent", "groupXmlPublish", "groupSubscription"];
	for (i in activities){
		if(contextButton.hasClass(activities[i])){
			act = activities[i];
			break;
		}
	}

	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/GroupUserMatrix/doGroupActivity/'+act+'/'+workingModule+'/'+standardContextMenu_crtHeader)

}
function clickOnAcitivityInGroupUserMatrixUserContextMenu(contextButton, crtWigiiNamespace, crtModule, workingModule){
	contextButton = $(contextButton);

	activities = ["userEdit", "userDelete"];
	for (i in activities){
		if(contextButton.hasClass(activities[i])){
			act = activities[i];
			break;
		}
	}

	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/GroupUserMatrix/doUserActivity/'+act+'/'+workingModule+'/'+standardContextMenu_crtHeader)

}
function clickOnAcitivityInUserAdminMatrixUserContextMenu(contextButton, crtWigiiNamespace, crtModule, workingModule){
	contextButton = $(contextButton);

	activities = ["userEdit", "userDelete"];
	for (i in activities){
		if(contextButton.hasClass(activities[i])){
			act = activities[i];
			break;
		}
	}

	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/UserAdminMatrix/doUserActivity/'+act+'/'+workingModule+'/'+standardContextMenu_crtHeader)

}
function clickOnAcitivityInUserAdminMatrixItems(items, crtWigiiNamespace, crtModule, workingModule){
	crtCol = $(items).parent().parent().attr('class');
	crtRow = $(items).parent().parent().parent().attr('class');
	checked = $(items).find('input').get(0).checked;
	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/UserAdminMatrix/setAdminLevel/'+workingModule+'/'+crtRow+'/'+crtCol+'/'+checked)

}

function clickOnAcitivityInUserUserMatrixUserContextMenu(contextButton, crtWigiiNamespace, crtModule, workingModule){
	contextButton = $(contextButton);

	activities = ["userEdit", "userDelete"];
	for (i in activities){
		if(contextButton.hasClass(activities[i])){
			act = activities[i];
			break;
		}
	}

	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/UserUserMatrix/doUserActivity/'+act+'/'+workingModule+'/'+standardContextMenu_crtHeader)

}
function clickOnAcitivityInUserUserMatrixItems(items, crtWigiiNamespace, crtModule, workingModule){
	crtCol = $(items).parent().attr('class');
	crtRow = $(items).parent().parent().attr('class');
	checked = $(items).find('input').get(0).checked;
	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/UserUserMatrix/setUserUser/'+workingModule+'/'+crtRow+'/'+crtCol+'/'+checked)

}

function clickOnAcitivityInUserRoleMatrixItems(items, crtWigiiNamespace, crtModule, workingModule){
	crtCol = $(items).parent().attr('class');
	crtRow = $(items).parent().parent().attr('class');
	checked = $(items).find('input').get(0).checked;
	update('NoAnswer/'+crtWigiiNamespace+'/'+crtModule+'/UserRoleMatrix/setUserRole/'+workingModule+'/'+crtRow+'/'+crtCol+'/'+checked)
}

adminGroup_crtSelectedGroup = null;
adminUser_crtSelectedUser = null;
adminModuleEditor_crtSelectedModuleConfig = null;

function adminUserDetailOnResize(){
	if($('#adminUser_list').length>0){
		if(browserName == "msie" && version =="8") corr = 25;
		else corr = 5;
		$('#adminUser_detail').width($(window).width()-$('#adminUser_list').outerWidth()-corr);
		$('#adminUser_list').css('height', $(window).height()-$('#adminUser_list').position().top -$('#footerBar').outerHeight()-5);
		$('#elementDetail').width($('#adminUser_detail').width()-60-5); //60 is internal padding
		$('#elementDetail').css('height', $(window).height()-$('#elementDetail').position().top -$('#footerBar').outerHeight()-10);
		//position the scroll on selected element
		if($('#adminUser_list div.userHeader.S').length > 0){
			$('#adminUser_list').scrollTop($('#adminUser_list div.userHeader.S').position().top+2+$('#adminUser_list').position().top+$('#adminUser_list').scrollTop()-$('#adminUser_list').height());
		}
		//$('#adminUser_list').css('height', $(window).height()-$('#userBar').outerHeight()-$('#navigationBar').outerHeight()-$('#tabsBar').outerHeight()-$('#adminSearchBar').outerHeight()-$('#footerBar').outerHeight()-5);
	}
}
function adminGroupDetailOnClickGroupTree(){
	if(browserName == "msie" && version =="8") corr = 25;
	else corr = 5;
	$('#adminGroup_detail').width($(window).width()-$('#adminGroup_list').outerWidth()-corr);
	$('#elementDetail').width($('#adminGroup_detail').width()-60-5); //60 is internal padding
	$('#elementDetail').css('height', $(window).height()-$('#elementDetail').position().top -$('#footerBar').outerHeight()-5);
}
function adminGroupOnResize(){
	if($('#adminGroup_list').length>0){
		if(browserName == "msie" && version =="8") corr = 25;
		else corr = 5;
		adminGroupDetailOnClickGroupTree();
		$('#adminGroup_list>ul').css('height', $(window).height()-$('#adminGroup_list').position().top -$('#footerBar').outerHeight()-20); //20 is the padding of the groupPanel>ul
	}
}
//function adminGroupDetailOnToggleTreeNode(){
//	if(browserName == "msie" && version =="8") corr = 25;
//	else corr = 5;
//	$('#adminGroup_detail').width($(window).width()-$('#adminGroup_list').outerWidth()-corr);
//	$('#elementDetail').width($('#adminGroup_detail').width()-5);
//}
function adminModuleEditorDetailOnResize(){
	//$('#adminModuleEditor_detail').width($(window).width()-$('#adminModuleEditor_list').outerWidth());
}

function adminModuleEditorListOnResize(){
	if($("#adminModuleEditor_list").offset()) { //test if the adminModuleEditor_list was created
		$("#adminModuleEditor_list").height($(window).height()-$("#adminModuleEditor_list").offset().top-$("#footerBar").outerHeight()-10); //10 = padding-top + padding-bottom
		$("#adminModuleEditor_list").width($(window).width()-$("#adminModuleEditor_detail").width()-15); //16 = padding-left + padding-right + border-left-width +1 for IE
	}
}

function setListenersToAdminGroup(){
	//add click on keep notified
	if($('#keepNotifyButton').length){
		$('#keepNotifyButton').click(openKeepNotifiedDialog);
	}
	setListenersToGroupTree('#adminGroup_list');

	//add resize on fold/unfold folder
	$('#adminGroup_list li span.folder').click(function(){
		adminGroupDetailOnClickGroupTree();
	});
	//add click on li: select folder
	$('#adminGroup_list li').click(function(e){
		adminGroupDetailOnClickGroupTree();
		adminGroup_crtSelectedGroup = $(this).attr('id').split('_')[1];

		//update the commands depending on the rights
		$('#adminGroup_detail .commands div').removeClass('disabled');
		$('#adminGroup_detail .commands div.write, #adminGroup_detail .commands div.admin').addClass('disabled');
		if($(this).hasClass("write")){
			$('#adminGroup_detail .commands div.write').removeClass('disabled');
		}
		if($(this).hasClass("admin")){
			$('#adminGroup_detail .commands div.admin').removeClass('disabled');
		}
		if($(this).parent().attr('id')=="group_0"){
			$('#adminGroup_detail .commands div.level1').addClass('disabled');
		}

		self.location = '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/'+crtWorkingModuleName+'/folder/'+adminGroup_crtSelectedGroup;
		update('elementDetail/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/groupDetail/'+crtWorkingModuleName+'/'+adminGroup_crtSelectedGroup);
		e.stopPropagation();
	});
}

function setListenerToUserRoleAllocationFilter(){
	$('#UserRoleAllocationFilterInput')
		.keydown(function(e){
			//$(this).next().click();
			if(e.keyCode == 13){ $(this).next().click(); }
		})
		.focus(function(){ $(this).select(); })
	;
	$('#UserRoleAllocationFilterInput').next().click(function(){
		var searchVal = $(this).prev().val();
		if(searchVal == '' || searchVal == null){
			$('#adminRole_list div.userHeader').show();
			if($('#adminRole_list .S:first').length > 0){
				$('#adminRole_list').scrollTop($('#adminRole_list .S:first').get()[0].offsetTop-$('#adminRole_list').get()[0].offsetTop - 50);
			}
		} else {
			$('#adminRole_list div.userHeader').hide();
			//if there are some separators then use them as separators else use the space
			searchVal = searchVal.replace(/\"/g, '');
			if(searchVal.search(/[,;|]/i)!=-1){
				searchVal = searchVal.replace(/\|\|/g, '|');
				searchVal = searchVal.replace(/,/g, '|');
				searchVal = searchVal.replace(/;/g, '|');
			} else {
				searchVal = searchVal.replace(/ /g, '|');
			}
			searchVal = searchVal.split('|');
			var x;
			for(x in searchVal){
				$('#adminRole_list div.userHeader:icontains(\"'+searchVal[x].trim()+'\")').show();
			}
		}

	});
	
	$('#adminRole_list_check_all')
		.click(function(e){
			$('#adminRole_list > div').not('[style*="display: none"]').addClass('S');
			$('#adminRole_list_check_all').parent().children().first().text($('#adminRole_list > div.S').length);
		});
	$('#adminRole_list_uncheck_all')
		.click(function(e){
			$('#adminRole_list > div').not('[style*="display: none"]').removeClass('S');
			$('#adminRole_list_uncheck_all').parent().children().first().text($('#adminRole_list > div.S').length);
		});
}

$(window).resize(adminModuleEditorDetailOnResize);
$(window).resize(adminModuleEditorListOnResize);
$(window).resize(adminGroupOnResize);
$(window).resize(adminUserDetailOnResize);





