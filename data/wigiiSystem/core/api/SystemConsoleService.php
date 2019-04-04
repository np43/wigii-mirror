<?php
/**
 *  This file is part of Wigii (R) software.
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
 * The System Console gathers information of the DebugLogger and ExecutionSink and pushes them to the browser.
 * It also buffers JS code and notifications added during one request lifecycle and flushes them back to browser on demand.
 * This service is only available in the Web implementation.
 * Created on 24 juil. 09 by LWR
 * Modified by CWE on 01.12.2015 to add JS notifications buffering
 */
interface SystemConsoleService {
	
	/**
	 * this will store a message in the SystemConsole 
	 */
	public function storeMessage($type, $attachedClass, $message);
	
	/**
	 * this will publish all the message
	 */
	public function flushMessages();
	
	/**
	 * this will store a JSCode in the SystemConsole
	 */
	public function addJsCode($code, $currentRequestId=null);
	
	/**
	 * this will publish all JSCode
	 */
	public function flushJSCode();
	
	/**
	 * Stores a Js notification in the SystemConsole waiting to be sent to the client
	 * @param String $target notification target. One of searchBar,elementDialog.
	 * @param String $type notification type. One of help,info,warning,error,notif.
	 * @param String $url notification callback url to get notification content.
	 * @param WigiiBPLParameter $options an optional map of options to be passed to the WigiiApi JS client.
	 * example: addJsNotif("searchBar","help","User Guide/Filemanager/help/item/12345/integratedFile")
	 * will add a (?) icon in the searchBar toolbox, displaying a help popup with the html content of the 12345 element of the User Guide.
	 * Some options could be "width","height","top","left","title",... see WigiiApi.js Popup class for more detail.
	 */
	public function addJsNotif($target,$type,$url,$options=null);
	
	/**
	 * Flushes the pending Js notifications to the WigiiApi Js client.
	 */
	public function flushJSNotif();
}


