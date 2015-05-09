<?php
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
 */

/*
 * Created on 24 juil. 09
 * by LWR
 */

/**
 * the System console will collecte all the information of the debugLogger, the executionSink and the addJsCode...
 * this service is only available in the webImpl
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
	public function flushJsCode();
	
}


