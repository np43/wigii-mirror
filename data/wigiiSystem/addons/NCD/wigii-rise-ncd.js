/**
 *  Rise.wigii.org NCD Web Service JS client
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
 
/**
 * Rise.wigii.org NCD Web Service JS client
 * Created by Camille Weber on 08.11.2016
 * Modified by Camille Weber on 18.04.2018 to include Move Forward project
 * @param wigiiFacade wigii the Wigii JS client facade function (defined in /assets/js/wigii_XXXX.js)
 * @param JQuery $ depends on JQuery 1.8.x
 */
(function(wigii,$,SITE_ROOT) {
	var wigiiApi = wigii();
	if(!wigiiApi.SITE_ROOT) wigiiApi.SITE_ROOT = SITE_ROOT;
	
	/**
	 * Rise NCD client class
	 */
	var RiseNcd = function() {
		var riseNcd = this;
		riseNcd.className = 'RiseNcd';
		riseNcd.instantiationTime = (new Date()).getTime();
		riseNcd.ctxKey = wigiiApi.ctxKey+'_'+riseNcd.className+'_'+riseNcd.instantiationTime;		
		
		riseNcd.context = {
			mfCodeCache: {}
		};		
		
		riseNcd.login = function(username, password) {				
			return riseNcd.call('sysLogin("'+username+'","'+password+'")');			
		};
		riseNcd.ping = function() {
			return riseNcd.call('riseNcd_ping()');
		};		
		riseNcd.version = function() {
			return riseNcd.call('riseNcd_version()');
		};
		riseNcd.sysUsername = function() {
			if(!riseNcd.context.username) {
				riseNcd.context.username = riseNcd.call('sysUsername()');
			}
			return riseNcd.context.username;
		};
		riseNcd.storeData = function(elementId, keyField, data) {
			riseNcd.call('riseNcd_storeData("'+elementId+'"'+(keyField?',"'+keyField+'"':'')+')', data);
		};
		riseNcd.getData = function(elementId) {
			var data = riseNcd.call('riseNcd_getData("'+elementId+'")');
			if(data) {
				//data = data.replace(/\n|\r/g,' ');
				//console.debug(data);
				data = JSON.parse(data);
			}
			return data;
		};		
		riseNcd.createDataStorage = function(groupId, description) {
			return riseNcd.call('riseNcd_createDataStorage("'+groupId+'"'+(description?',"'+description+'"':'')+')');
		};
		/**
		 * Calls synchronously Rise NCD Web service and returns the result as a String
		 *@param String fx the FuncExp to call as a web service expression
		 *@param Object|Array data optional data to post with the Fx call. (if null, then calls using GET, else POSTS)
		 */
		riseNcd.call = function(fx,data) {		
			var url = wigiiApi.SITE_ROOT+'NCD/Espace/fx/'+$.base64EncodeUrl(fx);
			var returnValue;
			if(data) {
				$.ajax({type:"POST",
					url:url,
					dataType:'json',
					contentType: 'text/plain',
					data: JSON.stringify(data),
					crossDomain: true,
					xhrFields: {withCredentials: true},
					async:false,
					success: function(data) {returnValue=data;}					
				});
			}
			else {
				$.ajax({type:"GET",
					url:url,
					crossDomain: true,
					xhrFields: {withCredentials: true},
					async:false,
					success: function(data) {returnValue=data;}					
				});
			}
			return returnValue;
		};
		
		// Projet ATELIER ENCODE / PARTAGE
		
		riseNcd.partage_sauverArticle = function(repositoryId,withContent,articles) {
			riseNcd.call('partage_sauverArticle("'+repositoryId+'"'+(withContent?',logTrue()':',logFalse()')+')', articles);
		};
		riseNcd.partage_rateArticle = function(repositoryId,rating,articles) {
			riseNcd.call('partage_rateArticle("'+repositoryId+'","'+rating+'")', articles);
		};
		riseNcd.partage_sauverContenu = function(repositoryId,contenus) {
			riseNcd.call('partage_sauverContenu("'+repositoryId+'")', contenus);
		};
		
		// Projet ATELIER ENCODE / MOVE Forward
		
		/**
		 * Fetches asynchronously an object into Rise.wigii.org Move Forward catalog and executes some action on the fetched code
		 *@param String codeId ID of the object stored into the catalog
		 *@param Function callback action to do on the fetched code. Callback is a function which takes one parameter of type object of the form
		 *{ id: object ID in catalog,
		 *  type: svg|ncd type of code fetched,
		 *  svg: String. SVG code fetched if defined,
		 *  ncd: String. WNCD code fetched if defined
		 *  objectName: String. Name of object in catalog
	     *  objectType: String. Type of object in catalog
		 *}
		 * If type is ncd both svg and ncd code can be defined. In that case, ncd holds an expression which uses in some way the svg code.
		 *@param Function exceptionHandler an optional exception handler in case of error. Function signature is exceptionHandler(exception,context) where exception is Wigii API exception object of the form {name:string,code:int,message:string}
		 * and context is an object with some server context information of the form {request:string, wigiiNamespace:string, module:string, action:string, realUsername:string, username:string, principalNamespace:string, version:string}
		 */
		riseNcd.mf_actOnCode = function(codeId,callback,exceptionHandler) {
			// if code is already in cache, then executes directly action on it
			if(riseNcd.context.mfCodeCache[codeId]) {
				if($.isFunction(callback)) callback(riseNcd.context.mfCodeCache[codeId]);
			}
			// else loads it first and puts it in cache.
			wigiiApi.callFx('mf_jsonEncode(mf_getCode("'+codeId+'"))',{
				fxEndPoint:wigiiApi.SITE_ROOT+'NCD/Catalog/fx/',
				resultHandler:function(code) {
					try {
						code = JSON.parse(code);
						// cleans up svg code from non needed elements and keeps defs separated
						if(code.svg) {
							// parses SVG as XML and loads jQuery on it
							var svgCode = $.parseXML(code.svg);
							svgCode = $(svgCode).find('svg');
							// extracts SVG defs and saves it into code.svgDefs
							var svgElt = svgCode.children('defs');
							if(svgElt.length>0) code.svgDefs = wigiiApi.xml2string(svgElt[0]);
							// extracts first valuable group of objects
							svgElt = svgCode.children('g');
							if(svgElt.length>1) {
								svgElt.wrapAll('<g/>');
								svgElt = svgCode.children('g');
							}
							if(svgElt.length==1) {
								svgCode = svgElt;
								svgElt = svgCode.children();
								if(svgElt.length==1) svgElt = svgCode.children('g');
								if(svgElt.length!=1) svgElt = svgCode;
								code.svg = wigiiApi.xml2string(svgElt[0]);
							}
						}
						riseNcd.context.mfCodeCache[codeId] = code;
						if($.isFunction(callback)) callback(code);
					}
					catch(exc) {						
						if($.isFunction(exceptionHandler)) exceptionHandler(exc);
						else wigiiApi.publishException(exc);
					}
				},
				exceptionHandler:exceptionHandler
			});
		};
		/**
		 * Clears current Move Forward cache 
		 */
		riseNcd.mf_clearCache = function() {
			riseNcd.context.mfCodeCache = {};
		};		
	};
	// registers the RiseNcd plugin into the Wigii JS client
	if(!wigiiApi['getRiseNcd']) wigiiApi.getRiseNcd = function() {
		if(!wigiiApi['riseNcdInstance']) {
			wigiiApi.riseNcdInstance = new RiseNcd();
		}
		return wigiiApi.riseNcdInstance;
	};	
})(wigii,jQuery,"https://rise.wigii.org/")