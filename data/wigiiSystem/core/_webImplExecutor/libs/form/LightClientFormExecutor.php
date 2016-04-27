<?php
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

/**
 * Wigii Light Client implementation.
 * A web client which answers to direct urls of type:
 * /WigiiNamespace/Module/c/item/id			: displays element detail 
 * /WigiiNamespace/Module/c/item/id/edit 	: displays edit element form
 * /WigiiNamespace/Module/c/item/id/copy 	: displays copy element form
 * /WigiiNamespace/Module/c/folder 			: shows an expanded groupPanel
 * /WigiiNamespace/Module/c/folder/id 		: shows a listview                     
 * /WigiiNamespace/Module/c/folder/id/add 	: displays add element form
 * /WigiiNamespace/Module/c 				: displays root folders
 * /NoWigiiNamespace/Home/c					: displays accessible namespaces
 * /WigiiNamespace/Home/c					: display accessible modules in namespace
 * /WigiiNamespace/Module/c/list			: list view configuration Activity
 * /WigiiNamespace/Module/c/filter			: list filter Activity
 * /WigiiNamespace/Module/c/user			: user menu Activity
 * 
 * Created by CWE on October 26th 2015.
 */
class LightClientFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightClientFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightClientFormExecutor");
		}
		return $this->_executionSink;
	}

	// Light Client implementation
	
	public function isMinimalPrincipalAuthorized() {return true;}
	public function processAndEnds($p,$exec) {
		echo "<p>LightClient ".$p->getUsername().": ".$exec->getCrtRequest()."</p>";
	}
}



