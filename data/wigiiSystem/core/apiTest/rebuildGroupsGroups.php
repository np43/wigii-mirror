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
 * Rebuils the table GroupsGroups.
 * Created by CWE on 20 mars 2010
 */
class RebuildGroupsGroups extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("RebuildGroupsGroups");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('RebuildGroupsGroups','Rebuilds the whole table Groups_Groups');
	}


	public function run()
	{
		$rootPrincipal = $this->getRootPrincipal();
		$gAS = ServiceProvider::getGroupAdminService();
		$gAS->rebuildGroupsGroups($rootPrincipal);
	}
}
TestRunner::test(new RebuildGroupsGroups());