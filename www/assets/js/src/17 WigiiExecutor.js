/*!
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

function printObj(obj) {
	var arr = [];
	$.each(obj, function(key, val) {
		var next = key + ": ";
		next += $.isPlainObject(val) ? printObj(val) : val;
		arr.push( next );
	});
	return "{ " + arr.join(", ") + " }";
};


/* global JS variables */

crtElementId = null; //id of the current selectedElement
crtElementType = 'element'; //type name of the current selectedElement
crtRoleId = null;
crtContextId = 0; //id of the current JS context, this is used to manage context from multiple browser window
crtWigiiNamespaceUrl = null;
crtModuleName = null;
crtWorkingModuleName = null;

function object2Array(obj){
	r = new Array;
	i = 0;
	for(x in obj){
		r[i++]=obj[x];
	}
	return r;
}

// prepends #ctrWigiiNamespace/crtModule/ to url. 
function prependCrtWigiiNamespaceAndModule2Url(url) {
	return '#'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/'+url;
}

//add the function icontains which use the contains insensitive.
$.expr[':'].icontains = function(obj, index, meta, stack){ return (obj.textContent || obj.innerText || jQuery(obj).text() || '').toLowerCase().indexOf(meta[3].toLowerCase()) >= 0; };

////the first occurence needs to have the full dots
//function dotdotdot(dotdotdotElement, maxDots) {
//	dotdotdotText = dotdotdotElement.text();
//	lastDotPos = dotdotdotText.lastIndexOf('.');
//	if(lastDotPos==-1) lastDotPos = dotdotdotText.length;
//	//current nb of dots
//	nbDots = maxDots-(dotdotdotText.length-lastDotPos+1);
//	//add one
//	nbDots = (nbDots+1)%maxDots;
//	spaces = new Array(maxDots-nbDots).join(" ");
//	dots = new Array(nbDots).join(".");
//	$('#companyBanner>div>font:first').text(nbDots+" "+dotdotdotText+":"+dots+":"+spaces);
//
//	dotdotdotElement.text(dotdotdotText.substr(0,dotdotdotText.length-maxDots)+dots+spaces);
//
//};
dialogPos = new Array();
busyDivStack = new Array();
busyDivInterval = null;
function setVis(element, vis){
	if(typeof element != 'object'){
		element = $('#'+element);
	}
	id = element.attr('id');

	if(vis == true){
		element.show();
		if(id=='busyDiv'){
			if(busyDivStack.length==0){
//				busyDivInterval = setInterval(function(){ dotdotdot(element, 3); }, 300);
			}
			busyDivStack.push('x');
		}
		//element.css("visibility", 'visible'); //.fadeIn("normal");
		//element.show("slow");
	}
	else{
		if(id=='busyDiv'){
			//alert(busyDivStack.length);
			busyDivStack.pop();
			if(busyDivStack.length==0){
//				clearInterval(busyDivInterval);
				element.hide();
				//element.css("visibility", 'hidden'); //.fadeOut("normal", function(){ if($(this).css('opacity') == 0) $(this).css("visibility", 'hidden');});
			}
		} else {
			//element.hide("slow");
			element.hide();
			//element.css("visibility", 'hidden'); //.fadeOut("normal", function(){ if($(this).css('opacity') == 0) $(this).css("visibility", 'hidden');});
		}
	}
}


//called each time a dialog is closed
function actOnCloseDialog(id){
	//nothing special to do here...
}
//called each time a dialog is canceled
function actOnCancelDialog(id){
	$('#'+id).stopTime(); //very important to stop all the timers on elements that we will destroy.
	$('#'+id+" *").stopTime(); //very important to stop all the timers on elements that we will destroy.
	$('#'+id).html('');
}
function emptyDialog(id){
	$('#'+id).stopTime(); //very important to stop all the timers on elements that we will destroy.
	$('#'+id+" *").stopTime(); //very important to stop all the timers on elements that we will destroy.
	$('#'+id).html('');
}

checkOpenItemTemp_id = null;
checkOpenItemTemp_lookupPath = null;
checkOpenItemTemp_url = null;
checkOpenItemTemp_f = null;
checkOpenItemTemp_informIfFoundInCache = null;
function checkOpenItem(id, lookupPath, url, checkAnyOpenItem, informIfFoundInCache){
	if (arguments.length<2) lookupPath = null;
	if (arguments.length<3) url = null;
	if (arguments.length<4) checkAnyOpenItem = false;
	if (arguments.length<5) informIfFoundInCache = false;

	if(checkAnyOpenItem){
		f = $('form');
	} else {
		f = $('#'+id+' form');
	}
	if(f.length>0){
		if(f.length==1){
			//those needs to be updated only there is a checkOpenItems thing, this is to prevent the call on cancel click to change them
			checkOpenItemTemp_id = id;
			checkOpenItemTemp_lookupPath = lookupPath;
			checkOpenItemTemp_url = url;
			checkOpenItemTemp_f = f;
			checkOpenItemTemp_informIfFoundInCache = informIfFoundInCache;
			customConfirmTextFromDialogId = DIALOG_doYouWantToSaveChage;
			if(typeof( window["DIALOG_doYouWantToSave_"+id] ) != "undefined") eval("customConfirmTextFromDialogId = DIALOG_doYouWantToSave_"+id);
			// if cancel button exists and is visible, then asks user to confirm what to do
			cButton = $('.ui-dialog-buttonpane .cancel', checkOpenItemTemp_f.closest('.ui-dialog'));
			if(cButton.filter(':visible').length){
				jConfirm(customConfirmTextFromDialogId, null, function(check){
					if(check===true){
						//click on Ok button
						//alert("ok");
						$('.ui-dialog-buttonpane .ok', checkOpenItemTemp_f.closest('.ui-dialog')).click();
						//cancel current action
					} else if(check===null){
						//do nothing and cancel current action
						checkOpenItemTemp_url = null;
					} else if(check===false){
						//do cancel or no button
						$('.ui-dialog-buttonpane .cancel', checkOpenItemTemp_f.closest('.ui-dialog')).click();
						//continue current action
						if(checkOpenItemTemp_url!=null){
							updateThroughCache(checkOpenItemTemp_id, checkOpenItemTemp_lookupPath, checkOpenItemTemp_url, null, checkOpenItemTemp_informIfFoundInCache, true);
							checkOpenItemTemp_url = null;
						}
					}
				});
			// else if cancel button but not visible then clicks on it and  closes the dialog
			} else if(cButton.length) {
				//alert("cancel");
				//do cancel or no button
				cButton.click();
				//continue current action
				if(checkOpenItemTemp_url!=null){
					updateThroughCache(checkOpenItemTemp_id, checkOpenItemTemp_lookupPath, checkOpenItemTemp_url, null, checkOpenItemTemp_informIfFoundInCache, true);
					checkOpenItemTemp_url = null;
				}
			// else no cancel button, only ok button to click onto
			} else {
				$('.ui-dialog-buttonpane .ok', checkOpenItemTemp_f.closest('.ui-dialog')).click();
			}
			return true;
		} else {
			jAlert(DIALOG_finishCurrentAction);
			return true;
		}
	}
	return false;
}

currentElementDialogViewCacheKey = null;
function updateCurrentElementDialogViewCache(){
	if(__cache && __cache["elementDialog"] && __cache["elementDialog"][currentElementDialogViewCacheKey]){
		setCacheFromDom("elementDialog", currentElementDialogViewCacheKey);
	}
}
function invalidCurrentElementDialogViewCache(){
	invalidCache("elementDialog", currentElementDialogViewCacheKey);
}
currentModuleViewCacheKey = null;
function updateCurrentModuleViewCache(){
	if(__cache && __cache["moduleView"] && __cache["moduleView"][currentModuleViewCacheKey]){
		setCacheFromDom("moduleView", currentModuleViewCacheKey);
	}
}
function invalidCurrentModuleViewCache(){
	invalidCache("moduleView", currentModuleViewCacheKey);
}
function invalidModuleViewCache(){
	invalidCache("moduleView");
}

__cache = new Object();
__cacheJS = new Object();
__cacheKeep = new Object();
//checkAnyOpenItem checks that in the whole dom there is no form.
//other wise the check is done only within the id
function updateThroughCache(id, lookupPath, url, checkAnyOpenItem, informIfFoundInCache, noOpenItemCheck){
	if (arguments.length<4) checkAnyOpenItem = false;
	if (arguments.length<5) informIfFoundInCache = false;
	if (arguments.length<6) noOpenItemCheck = false;

	if(!noOpenItemCheck && checkOpenItem(id, lookupPath, url, checkAnyOpenItem, informIfFoundInCache)){
		return;
	}

	//special caching for moduleView
	if(id=="moduleView"){
		currentModuleViewCacheKey = lookupPath;
	} else if(id=="elementDialog"){
		currentElementDialogViewCacheKey = lookupPath;
	}
	r = null; c= null; foundInCache = false;
	if(lookupPath){
		r = getCache(id, lookupPath);
		if(r) foundInCache = true;
		c = getJSCache(id, lookupPath);
		if(c) foundInCache = true;
	}
	if(foundInCache){
		if(r) {
			$("#"+id+" *").stopTime(); //very important to stop all the timers on elements that we will destroy.
			$("#"+id).html(r);
		}
		//inform the server that an action has been done from the user, but content is found in cache
		if(informIfFoundInCache) {
			foundInCacheUpdate(url);
		}
		if(c){
			eval(c);
		}
	} else {
		update(url, true);
	}
}
function getCache(id, lookupPath){
	if(!__cache[id]){
		__cache[id] = new Object();
	}
	r = __cache[id][lookupPath];
	return r;
}
function getJSCache(id, lookupPath){
	if(!__cacheJS[id]){
		__cacheJS[id] = new Object();
	}
	c = __cacheJS[id][lookupPath];
	return c;
}
function setCache(id, lookupPath, html){
	if(!__cache[id]){
		__cache[id] = new Object();
	}
	__cache[id][lookupPath] = html;
}
function setJSCache(id, lookupPath, code){
	if(!__cacheJS[id]){
		__cacheJS[id] = new Object();
	}
	__cacheJS[id][lookupPath] = code;
}
function setCacheFromDom(id, lookupPath){
	if(id==null){
		alert("try to setCacheFromDom with no id. WigiiExecutor.js");
		return;
	}
	if(!__cache[id]){
		__cache[id] = new Object();
	}
	if(!__cacheJS[id]){
		__cacheJS[id] = new Object();
	}
	__cache[id][lookupPath] = $('#'+id).html();
	if($('#JSCode'+id).length>0){
		__cacheJS[id][lookupPath] = $('#JSCode'+id).text();
	}
}
//isRecursif should be ignored, as it is used only in window opener recursif code
function invalidCompleteCache(isRecursif){
	if (arguments.length<1) isRecursif = false;
	__cache = new Object();
	__cacheJS = new Object();

	if(!isRecursif){
		var crtWin = window;
		while(crtWin.opener){
			try {
				if(crtWin.opener.location.hostname == crtWin.location.hostname) {
					crtWin.opener.invalidCompleteCache(true);
					crtWin = crtWin.opener;
				}
				else crtWin = false;
			}
			catch(err) {
				crtWin = false;
			}
		}
	}
}
//isRecursif should be ignored, as it is used only in window opener recursif code
function invalidCache(id, lookupPath, isRecursif){
	if (arguments.length<2) lookupPath = null;
	if (arguments.length<3) isRecursif = false;

	//invalid cache of any parent window

	if(!__cache[id]){
		__cache[id] = new Object();
	}
	if(!__cacheJS[id]){
		__cacheJS[id] = new Object();
	}
	if(lookupPath){
		if(!__cacheKeep[lookupPath]) {
			__cache[id][lookupPath] = null;
			__cacheJS[id][lookupPath] = null;
		}
	} else {
		var c = __cache[id];
		var newC = new Object();
		for(var cId in c) {
			if(__cacheKeep[cId]) newC[cId] = c[cId];			
		}
		__cache[id] = newC;
		
		c = __cacheJS[id];
		newC = new Object();
		for(var cId in c) {
			if(__cacheKeep[cId]) newC[cId] = c[cId];			
		}
		__cacheJS[id] = newC;
	}

	if(!isRecursif){
		var crtWin = window;
		while(crtWin.opener){
			try {
				if(crtWin.opener.location.hostname == crtWin.location.hostname) {
					crtWin.opener.invalidCache(id, lookupPath, true);
					crtWin = crtWin.opener;
				}
				else crtWin = false;
			}
			catch(err) {
				crtWin = false;
			}
		}
	}
}
function keepInCache(id) {
	__cacheKeep[id] = id;
}
function clearKeepInCache() {
	__cacheKeep = new Object();
}
var crtNavigateCacheKey = null;
function setModuleViewKeyCacheForNavigate(key) {
	setJSCache('navigationCache', crtNavigateCacheKey, key);
}
function getModuleViewKeyCacheForNavigate() {
	return getJSCache('navigationCache', crtNavigateCacheKey);
}
function setCurrentNavigateCacheKey(key) {
	crtNavigateCacheKey = key;
}

function foundInCacheUpdate(url){
	if(url == null) return;

	//find the idAnswer:
	s = url.split("/");
	s[0] = EXEC_foundInJSCache;
	url = s.join("/");

	url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ url;

	var myAjax = new jQuery.ajax({
			url: encodeURI(url),
			success : parseUpdateResult,
			cache:false,
			error: errorOnUpdate
		});
	onUpdateErrorCounter = 0;
}
function update(url, noOpenItemCheck, postdata, successCallback){
	if (arguments.length<2) noOpenItemCheck = false;
	if (arguments.length<3) postdata = null;
	if (arguments.length<4) successCallback = null;

	if(url == null) return;

	//find the idAnswer:
	idAnswer = url.split("/")[0];

	if(!noOpenItemCheck){
		if(checkOpenItem(idAnswer, null, url)){
			return;
		}
	}

	setVis("busyDiv", true);

	url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ url;

	if(postdata != null){
		var myAjax = new jQuery.ajax({
				type: "POST",
				url: encodeURI(url),
				success : (successCallback != null ? successCallback : parseUpdateResult),
				data: postdata,
				error: errorOnUpdate
			});
	} else {
		var myAjax = new jQuery.ajax({
				url: encodeURI(url),
				success : (successCallback != null ? successCallback : parseUpdateResult),
				cache:false,
				error: errorOnUpdate
			});
	}
	onUpdateErrorCounter = 0;
}

//use this fonction to make your navigator download a file
function download(url){
	url = SITE_ROOT +"useContext/"+crtContextId+EXEC_requestSeparator+ url.replace(SITE_ROOT, '');
	window.location.href = url;
}

/**
 * cette méthode réeffectue l'update trois fois s'il y a eu une erreur de request.
 * Ceci arrive par exemple lorsque je fais l'event de la touche ESC qui clique
 * le bouton annuler...
 */
onUpdateErrorCounter = 0; //compteur du nombre d'erreur consécutif d'un update ajax
function errorOnUpdate(XMLHttpRequest, textStatus, errorThrown) {
	onUpdateErrorCounter ++;
	if(onUpdateErrorCounter <= 3){
  		var myAjax = new jQuery.ajax( {
			url: this.url,
			data: this.data,
			method: this.method,
			type: this.type,
			success : this.success,
			cache:false,
			error: this.error
			}
			);
  	}
}

encHTML = function(text) {
	return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
};

decHTML = function(text) {
	//very important to replace the \n and \r to prevent js evaluation error
	return text.replace(/\n/g, ' ').replace(/\r/g, ' ').replace(/&quot;/g,'"').replace(/&#39;/g,"'").replace(/&mySQ;/g,"'").replace(/&oelig;/g,'œ').replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>'); //.replace(/<br\/>/g,'\n').replace(/<br>/g,'\n').replace(/<br \/>/g,'\n');
};

/**
 * décode le résultat d'une requête asynchrone et effectue les opérations désirée en fonction
 * du premier paramêtre de la réponse
 * @param {} tabReq
 * @param {} textStatus
 */
function parseUpdateResult(tabReq, textStatus){

	//setVis("busyDiv", false);
	$("#formProgressBar").hide();
	// Wigii 4.324 R1790: transfers parseUpdateResult algorithm to wigii() JS client.
	wigii().parseUpdateResult(tabReq,textStatus);
	/*
	tabReq = tabReq.split(EXEC_answerRequestSeparator);

	var tabLength = tabReq.length;
	var code = '';

	lookupPaths = new Object();
	for (var i=0; i<tabLength; i = i+1){
		request = tabReq[i].split(EXEC_answerParamSeparator);
		if(request != ""){
			//d'abord on traite les idRequest de type mot clé:
			if (request[0] == "JSCode"){
				if(request.length > 2){
					tempCode = decHTML(request[2]);
					if(tempCode.substr(0, 6)=="<br />"){ //this is when there is a fatal arror back
						tempCode = null;
					}
					if(request[1] == "foundInCache") {
						if(lookupPaths["foundInCache"]) {
							setJSCache(lookupPaths["foundInCache"][1], lookupPaths["foundInCache"][0], tempCode);
							keepInCache(lookupPaths["foundInCache"][0]);
						}
					}
					else {
						setJSCache(request[1], lookupPaths[request[1]], tempCode);
						keepInCache(lookupPaths[request[1]]);
					}
				} else {
					tempCode = decHTML(request[1]);
					if(tempCode.substr(0, 6)=="<br />"){ //this is when there is a fatal arror back
						tempCode = null;
					}
				}
//				tempCode1 = prompt("", tempCode);
//				if(tempCode1 != null) tempCode = tempCode1;
				eval(tempCode);
			} else if (request[0] == "Alert"){
				alert(request[1]);
			} else if (request[0] == "Reload"){
				self.location = request[1];
			} else if (request[0] == "NoAnswer"){
				// detects a cache key
				if(request[1].indexOf('cacheKey_') == 0) {
					lookupPaths[request[0]] = request[1];
				}
				//should never occurs, but if there is an answer
				//then we alert it.
				else if(request[1] != ""){
					if(request.length>2){
						alert(request[1]+'\n'+request[2]);
					} else {
						alert(request[1]);
					}
				}
			} else if (request[0] == "foundInCache"){
				// detects a cache key
				if(request[1].indexOf('cacheKey_') == 0) {
					lookupPaths[request[0]] = [request[1],request[2]];
				}
			} else {
				//si la réponse est = keep --> do nothing
				if(request[1]=='keep'){

				} else {
					//on remplace le contenu de l'id par l'html qui suit
					$("#"+request[0]).stopTime(); //very important to stop all the timers on elements that we will destroy.
					$("#"+request[0]+" *").stopTime(); //very important to stop all the timers on elements that we will destroy.
					if(request.length > 2){
						$("#"+request[0]).html(request[2]);
						//dans le cas ou la réponse est vide, alors on ferme le dialog
						if(request[2]==''){
							if($("#"+request[0]).is(':ui-dialog')){
								$("#"+request[0]).dialog("destroy");
							}
						} else {
							setCache(request[0], request[1], request[2]);
							keepInCache(request[1]);
							lookupPaths[request[0]] = request[1];
						}
					} else {
						$("#"+request[0]).html(request[1]);
						//dans le cas ou la réponse est vide, alors on ferme le dialog
						if(request[1]==''){
							if($("#"+request[0]).is(':ui-dialog')){
								$("#"+request[0]).dialog("destroy");
							}
						}
					}
				}
			}
		}
	}
	clearKeepInCache();
	*/
	setVis("busyDiv", false);
}

function positionElementOnMouse(obj, event, align, linkedObj, additionalLowOffset){
	if(arguments.length < 4) linkedObj = null;
	if(arguments.length < 5) additionalLowOffset = null;
	//we need to add the scrolls to get the real position
	//the width of 5 px allows a better way to manage position beside the mouse without being to easy under it
	//if we want to be under, then use the offsetLow and a fromLeft
	//calculate the offsetLow to be not under the mouse, but not either out of the current linkedObj
	//to prevent block scrolling when mouving mouse
	if(linkedObj != null){
		offsetLow = 5;
		//alert(offsetLow+" "+(linkedObj.offset().top+linkedObj.height())+" "+(event.clientY+$(document).scrollTop())+" "+((linkedObj.offset().top+linkedObj.height()) - (event.clientY+$(document).scrollTop())));
		//WARNING if you forget adding the parseint calculation is messy, because some is concatenating and others are additionning
		scrollTop = parseInt($(document).scrollTop());
		if(scrollTop != 0) scrollTop = scrollTop - 5;
		if (parseInt(linkedObj.offset().top)+parseInt(linkedObj.height()) - parseInt(event.clientY)+scrollTop <= offsetLow){
			offsetLow = parseInt(linkedObj.offset().top) + parseInt(linkedObj.height()) - parseInt(event.clientY)+scrollTop-2;
		}
	} else {
		offsetLow = 0;
		scrollTop = parseInt($(document).scrollTop());
	}
	positionElement(obj, event.clientY+scrollTop, event.clientX+parseInt($(document).scrollLeft()), 30, 5, align, offsetLow+additionalLowOffset, true);
}
//position the object depending on the align parameters
//using linkedObj as reference.
//the object will be positioned to not go out of the client window size
function positionElementOnDom(obj, linkedObj, align, offsetLow, preventRecovering, usePosition){
	if(arguments.length < 4) offsetLow = null;
	if(arguments.length < 5) preventRecovering = false;
	if(arguments.length < 6) usePosition = false;
	if(usePosition){
		var pos = linkedObj.position();
	} else {
		var pos = linkedObj.offset();
	}
	var myLeft = pos.left;
	var myTop = pos.top;
	positionElement(obj, myTop, myLeft, linkedObj.outerHeight(), linkedObj.outerWidth(), align, offsetLow, preventRecovering);
}

function positionElement(obj, refTop, refLeft, refHeight, refWidth, align, offsetLow, preventRecovering){

	var myLeft, myTop;

	//alignement centré
	if(align=="center"){
		myLeft = refLeft+ refWidth/2 - obj.outerWidth()/2;
	} else if (align=="right"){
		myLeft = refLeft + refWidth;
	} else if (align=="left"){
		myLeft = refLeft - obj.outerWidth();
	} else if (align=="fromLeft"){
		myLeft = refLeft;
	} else if (align=="fromRight"){
		myLeft = refLeft + refWidth - obj.outerWidth();
	} else if (align=="fromCenter"){
		myLeft = refLeft+ (refWidth/2);
	} else if (align=="toCenter"){
		myLeft = refLeft+ (refWidth/2) - obj.outerWidth();
	}
	if(offsetLow === null){
		myTop = refTop + refHeight;
	} else {
		myTop = refTop + offsetLow;
	}


	//var winW = $('#mainDiv').outerWidth();
	//var winH = $('#mainDiv').outerHeight();
	// get window size ( code extracted from pre 1.1.0 getPageSize )
	var winW, winH;

	if (self.innerHeight) {	// all except Explorer
		if(document.documentElement.clientWidth){
			winW = document.documentElement.clientWidth;
		} else {
			winW = self.innerWidth;
		}
		winH = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		winW = document.documentElement.clientWidth;
		winH = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		winW = document.body.clientWidth;
		winH = document.body.clientHeight;
	}

	var width = obj.outerWidth();
	var height = obj.outerHeight();

//	if(obj.attr('id') == "editElementSharingMenu"){
//		alert("top:"+myTop+" myLeft:"+myLeft+" winW:"+winW+" winH:"+winH+" width:"+width+" height:"+height);
//	}

	myTop = Math.min(winH-height, myTop-$(document).scrollTop());
	myTop = Math.max(0, myTop);

	//control that the obj is not covering the linkedObj
	if(preventRecovering){
		//vertical control
		//alert(myTop+' '+(linkedObj.offset().top+linkedObj.outerHeight())+' '+(myTop+height)+' '+linkedObj.offset().top);
		if((myTop+$(document).scrollTop() < (refTop+refHeight) && (myTop+$(document).scrollTop()+height)>refTop)){
			//move it on the left if possible
			if((winW-width < myLeft+$(document).scrollLeft()) && (refLeft - width > 0)){
				myLeft = refLeft - width;
			} else {
				//myLeft = refLeft + refWidth;
			}
		}
	}
	if(!preventRecovering){
		myLeft = Math.min(winW-width, myLeft-$(document).scrollLeft());
	}
	//limit on the right
	myLeft = Math.min(winW-width, myLeft-$(document).scrollLeft());
	//limit on the left
	myLeft = Math.max(0, myLeft);

//	if(obj.attr('id') == "editElementSharingMenu"){
//		alert(myTop+$(document).scrollTop() + " " + myLeft+$(document).scrollLeft());
//	}

	//those values will be added to the offsetParent of the obj.
	//if we want that those value will really be from the document
	//we need to soustract the offset of the parent.
//	if(obj.attr('id') == "editElementSharingMenu"){
//		alert(obj.get()[0].parentNode.offsetParent.offsetLeft);
//	}
//

	//if the obj origin is not the document, then substract the origin
//	obj.css("left", 0);
//	obj.css("top", 0);
//	origin = obj.offset();
//	alert(origin.left+" "+origin.top);
//	obj.css("left", myLeft-origin.left+$(document).scrollLeft());
//	obj.css("top", myTop-origin.top+$(document).scrollTop()); //il faut ajouter la position actuelle du scroll top pour que ce soit toujours à la bonne place

	obj.css("left", myLeft+$(document).scrollLeft());
	obj.css("top", myTop+$(document).scrollTop()); //il faut ajouter la position actuelle du scroll top pour que ce soit toujours à la bonne place

	//obj.offset({ left: myLeft+$(document).scrollLeft(), top: myTop+$(document).scrollTop() });
}


showHelpTimer = null;
hideHelpTimer = null;
doHideHelpTimer = null;
var showHelpRef, showHelpText, showHelpOffsetLow, showHelpAlign, showHelpDelay, showHelpMaxWidth, showHelpDefinedWidth, showHelpHideAfter, showHelpEvent;

//cette méthode construit vraiment le hep. On fait cela uniquement dans le
//cas où l'on va réellement l'afficher
function showHelp2(){

	var event = showHelpEvent;
	var ref = showHelpRef;
	var text = showHelpText;
	var offsetLow = showHelpOffsetLow;
	var align = showHelpAlign;
	var delay = showHelpDelay;
	var maxWidth = showHelpMaxWidth;
	var definedWidth = showHelpDefinedWidth;
	var hideAfter = showHelpHideAfter;

	//pour que la méthode outerWidth fonctionne correctement
	//il faut définir le width...
	helpEl = $('#help');
	helpEl.width(5);
	helpEl.html(text);
	if(definedWidth){
		helpEl.width(maxWidth);
	} else {
		helpEl.width("auto");
		if(helpEl.width() > maxWidth){
			helpEl.width(maxWidth);
		}
	}
	$('#help').stop(true, true).show();
	$('#help').hide();

	//put("width:"+helpEl.width()+" outerWidth:"+helpEl.outerWidth());

	if(typeof ref != 'object'){
		ref = $('#'+ref);
	} else {
		ref = $(ref);
	}
	if(event != null && typeof event == 'object' && event.clientY){
		positionElementOnMouse(helpEl, event, align, ref, offsetLow);
	} else {
		positionElementOnDom(helpEl, ref, align, offsetLow, false);
	}

	//if the mouse is over the help but the mouse do not move then keep the help showing
	$('#help').unbind().mouseenter(function(){ clearTimeout(doHideHelpTimer); }).mousemove(function(){ hideHelp(true); });
	$('#help').stop(true, true).show();
}

function showHelp(ref,text,offsetLow,align,delay,maxWidth,hideAfter, event){
	clearTimeout(doHideHelpTimer);
	clearTimeout(hideHelpTimer); //on annule les hide en cours...
	//s'il n'y a pas de texte, on montre pas le help
	//ceci peut arriver dans les infosbulles de l'ihmList
	if(text == "") return;
	definedWidth = false; //it is better to always leave that width to use the max if necessary
	if (arguments.length<3) offsetLow = null;
	if (arguments.length<4) align = "fromCenter";
	if (arguments.length<5) delay = 200;
	if (arguments.length<6 || maxWidth == null){
		maxWidth = 200; //largeur maximum de l'aide
		definedWidth = false;
	}
	if (arguments.length<7) hideAfter = 3500; //cache l'aide après ce temps
	if (arguments.length<8) event = null; //mouseover event

	//on rends publique ces données, pour permettre de les transmettres dans une fonction appelée en timout
	showHelpEvent = event;
	showHelpRef = ref;
	showHelpText = text;
	showHelpOffsetLow = offsetLow;
	showHelpAlign = align;
	showHelpDelay = delay;
	showHelpMaxWidth = maxWidth;
	showHelpDefinedWidth = definedWidth;
	showHelpHideAfter = hideAfter;

	if(delay != 0){
		//showHelpTimer = setTimeout("showHelp.showHelp3();", delay);
		showHelpTimer = setTimeout("showHelp2();", delay);
	} else {
		showHelp2();
	}

	if(hideAfter != 0){
		hideHelpTimer = setTimeout("hideHelp();", hideAfter);
	} else {
		//in any case hide after 10sec
		hideHelpTimer = setTimeout("hideHelp();", 10000);
	}
}

function hideHelp(fromHideHelp){
	if(arguments.length < 1) fromHideHelp = false;
	//detect if mouse is in help in this case keep help open
	clearTimeout(doHideHelpTimer);
	if(fromHideHelp){
		clearTimeout(showHelpTimer);
		clearTimeout(hideHelpTimer);
		$('#help').hide();
	} else {
		doHideHelpTimer = setTimeout("hideHelp(true);", "50");
	}
}

//cette méthode permet de faire que le multipleSelect dont l'id est passé en paramêtre
//devient un multipleSelect, ou chaque clique toggle la valeur de l'option
var multipleSelectVals = new Array; //les clés contiennes les valeurs de chaque id
function multipleSelectOnClick(id){
	var scrollTop = $('#'+id).scrollTop();
	var newMultipleTab = new Array;
	var alreadyIn = false;
	var select = $('#'+id);
	var temp = multipleSelectVals[id];
	$(temp).each(function(i){
		if(this.toString() != select.val().toString()){ newMultipleTab[newMultipleTab.length] = this.toString();}
		else { alreadyIn = true; }
	});
	if(!alreadyIn && select.val()) newMultipleTab[newMultipleTab.length] = select.val().toString();
	multipleSelectVals[id] = newMultipleTab;
	select.val(newMultipleTab);
	//select.val(new Array('French', 'German'));
	select.scrollTop(scrollTop);
}

function colorize(parent){
	if(arguments.length < 1) parent = $(window);
	else parent = $('#'+parent);
	//text = "begin to colorize "+parent;
	$('#moduleView *[class*="color_"]').each(function(){
		color = "#"+$(this).attr("class").match(/color_(.{6})/i)[1].toUpperCase();
		$(this).css('background-color', color).css('border-color', color);
		var
		    r = HexToR(color),
		    g = HexToG(color),
		    b = HexToB(color);
		var brightness = (r*299 + g*587 + b*114) / 1000;
		if (brightness > 140) {
		    // use black
			$("*", this).css('color', "#000000");
		} else {
		    // use white
			$("*", this).css('color', "#FFFFFF");
		}
	});
	//alert(text);
}

function HexToR(h) {return parseInt((cutHex(h)).substring(0,2),16)}
function HexToG(h) {return parseInt((cutHex(h)).substring(2,4),16)}
function HexToB(h) {return parseInt((cutHex(h)).substring(4,6),16)}
function cutHex(h) {return (h.charAt(0)=="#") ? h.substring(1,7):h}

/**
 * CRON JOBS function
 */
var globalCronJobsStopper = false;
var doingWakeup = false;
var cronJobsWorkingFunction = function(params){
	if(arguments.length < 1) params = "";
	if(doingWakeup){
		return;
	}
	doingWakeup = true;
	$('#cronJobsCursor').text(' > ');
	update('JSCode/NoWigiiNamespace/NoModule/wakeup/'+params);
}
var cronJobsFinishWorkingFunction = function(){
	doingWakeup = false;
}
function setListenersToCronJob(wakeupJsCode, postponeTimer){
	$('#cronJobsStart').click(function(){
		$('#cronJobsNb').stopTime('cronJobsWorking');
		$('#cronJobsStop span').stopTime('cronJobsPostpone');
		$('#cronJobsStop span').text('');
		cronJobsFinishWorkingFunction();
		globalCronJobsStopper = false;
		wakeupJsCode();
	});
	$('#cronJobsStop').click(function(){
		$('#cronJobsNb').stopTime('cronJobsWorking');
		$('#cronJobsNb').stopTime('cronJobsWakeup');
		globalCronJobsStopper = true;
		s = (postponeTimer)/1000;
		m = Math.floor(s/60);
		s = s % 60;
		if(s<10){
			s = '0'+s;
		}
		$('#cronJobsStop span').text(' '+m+':'+s);
		$('#cronJobsStop span').stopTime('cronJobsPostpone');
		$('#cronJobsStop span').everyTime(1000, 'cronJobsPostpone', function(i){
			s = (postponeTimer-(i*1000))/1000;
			m = Math.floor(s/60);
			s = s % 60;
			if(s<10){
				s = '0'+s;
			}
			$(this).text(' '+m+':'+s);
		});
		$('#cronJobsNb').oneTime(postponeTimer, 'cronJobsWorking', function(){
			$('#cronJobsStop span').stopTime('cronJobsPostpone');
			$('#cronJobsStart').click();
		});
	});
}


function logout(){
	update('NoAnswer/NoWigiiNamespace/NoModule/logout');
}
