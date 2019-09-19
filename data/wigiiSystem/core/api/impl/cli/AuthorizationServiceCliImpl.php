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
 * AuthorizationService specialization for CLI environment.
 * Supports injection of WebSocketServer system principal.
 * Created by CWE on 18.09.2019
 */
class AuthorizationServiceCliImpl extends AuthorizationServiceImpl {

	private $_debugLogger;
	private $_executionSink;

	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AuthorizationServiceCliImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AuthorizationServiceCliImpl");
		}
		return $this->_executionSink;
	}
	
	// Object lifecycle
	
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}
	
	// Implementation
	
	public function createAllSystemPrincipals($principalList, $wigiiNamespace=null, $moduleAccess=null)
	{
	    // creates standard system principals
	    parent::createAllSystemPrincipals($principalList, $wigiiNamespace, $moduleAccess);

	    // creates web socket server principal
	    $wssrvP = $this->createWebSockSrvPrincipal($wigiiNamespace, $moduleAccess);
	    $principalList->addPrincipal($wssrvP);
	    $this->setWebSockSrvPrincipal($wssrvP);
	}
}