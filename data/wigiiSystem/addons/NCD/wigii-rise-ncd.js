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
 * 
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
		
		riseNcd.context = {};		
		
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
		 * Calls Rise NCD Web service and returns the result as a String
		 *@param String fx the FuncExp to call as a web service expression
		 *@param Object|Array data optional data to post with the Fx call. (if null, then calls using GET, else POSTS)
		 */
		riseNcd.call = function(fx,data) {		
			var url = wigii().SITE_ROOT+'NCD/Espace/fx/'+$.base64EncodeUrl(fx);			
			var returnValue;
			if(data) {
				$.ajax({type:"POST",
					url:wigii().SITE_ROOT+'NCD/Espace/fx/'+$.base64EncodeUrl(fx),
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
					url:wigii().SITE_ROOT+'NCD/Espace/fx/'+$.base64EncodeUrl(fx),
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
	};
	// registers the RiseNcd plugin into the Wigii JS client
	if(!wigiiApi['getRiseNcd']) wigiiApi.getRiseNcd = function() {
		if(!wigiiApi['riseNcdInstance']) {
			wigiiApi.riseNcdInstance = new RiseNcd();
		}
		return wigiiApi.riseNcdInstance;
	};	
})(wigii,jQuery,"https://rise.wigii.org/")