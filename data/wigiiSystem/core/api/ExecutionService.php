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
 * This service manages a queue of http requests following the Wigii communication protocol.
 * It allows to iterate through the requests and load the request context in some public accessors.
 * Created on 24 juil. 09 by LWR
 * Modified by CWE on 28.08.2019 to enable storage of execution variables and to expose if current request is in public scope or not
 */
interface ExecutionService {

	/**
	 * start the execution service by fetching and parsing the requests
	 */
	public function start();
	/**
	 * will do all the job we want to do at the end of an execution
	 */
	public function end();
	/**
	 * set the crt's attributes to the state of the next request.
	 * if there was a request then everything is loaded and it returns true.
	 * if there was no more request then false is returned and the crt's are empty
	 * if current context is empty, then load new context
	 */
	public function loadNextRequest($wigiiExecutor);

	/**
	 * process the url to make it array of requests, and add it to the remaining request.
	 * @return array return the array of request which was defined in this url.
	 */
	public function addRequests($url);


	/**
	 * access to the current request state
	 */
	public function getIsUpdating();
	public function getCrtWigiiNamespace();
	public function getCrtModule();
	public function getCrtAction();
	public function getCrtParameters($key=null);
	public function getRemainingRequests();

	/**
	 * the execution service has one main principal which is stored
	 * in session
	 */
	public function getExecPrincipal();

	/**
	 * Returns true if current execution is in public scope (typically running with the Public Principal)
	 * Defaults to false.
	 */
	public function getIsInPublic();
	
	/**
	 * get current system version
	 */
	public function getVersion();
	/**
	 * get current system version type as void/beta/rc/ etc..
	 */
	public function getVersionType();
	
	// Execution variables
	
	/**
	 * Returns the value of an execution variable
	 * @param String $name variable name
	 * @return mixed any valid PHP value or object, stored in memory during the ExecutionService lifecycle or null if not defined
	 */
	public function getVar($name);
	
	/**
	 * Stores a variable into the ExecutionService
	 * @param String $name variable name. If already exists, value is replaced.
	 * @param mixed $value any valid PHP value or object, stored in memory during the ExecutionService lifecycle
	 */
	public function setVar($name, $value);
}


