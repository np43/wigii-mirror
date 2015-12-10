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

/**
 * This service manages a queue of http requests following the Wigii communication protocol.
 * It allows to iterate through the requests and load the request context in some public accessors.
 * Created on 24 juil. 09 by LWR
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
	 * process the url to make it array of requests, and add it to the remaingin request.
	 * @return return the array of request which was defined in this url.
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
	 * get current system version
	 */
	public function getVersion();
	/**
	 * get current system version type as void/beta/rc/ etc..
	 */
	public function getVersionType();
}


