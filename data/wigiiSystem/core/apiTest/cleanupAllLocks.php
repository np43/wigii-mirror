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
 * Unlocks all Elements, Groups and Users
 * Created by CWE on 20 mars 2010
 */
class CleanupAllLocks extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CleanupAllLocks");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('CleanupAllLocks','Unlocks all Elements, Groups and Users');
	}


	public function run()
	{
		$rootPrincipal = $this->getRootPrincipal();
		$gAS = ServiceProvider::getDbAdminService();
		$gAS->clearAllLocks($rootPrincipal, "Elements");
		$gAS->clearAllLocks($rootPrincipal, "Users");
		$gAS->clearAllLocks($rootPrincipal, "Groups");
	}
}
TestRunner::test(new CleanupAllLocks());