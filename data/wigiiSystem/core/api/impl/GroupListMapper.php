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
 * Maps a group db record to a Group
 * Created by CWE on 11 oct. 09
 */
class GroupListMapper implements RowList
{
	private $lockedForUse = true;
	private $groupList;
	private $groupCounter;
	private $groupAdminServiceImpl;
	private $principal;
	private $withDetail;

	public static function createInstance($groupAdminServiceImpl, $principal, $groupList, $withDetail=true)
	{
		$returnValue = new GroupListMapper();
		$returnValue->groupAdminServiceImpl = $groupAdminServiceImpl;
		$returnValue->reset($principal, $groupList, $withDetail);
		return $returnValue;
	}

	public function reset($principal, $groupList, $withDetail=true)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->groupList = $groupList;
		$this->principal = $principal;
		$this->withDetail = $withDetail;
		$this->groupCounter = 0;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public function getListIterator()
	{
		return $this->groupList->getListIterator();
	}

	public function isEmpty()
	{
		return ($this->groupCounter == 0);
	}

	public function count()
	{
		return $this->groupCounter;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$group = $this->groupAdminServiceImpl->createGroupInstanceFromRow($this->principal, $row, $this->withDetail);
			// checks module access, no need to do this check
//			$module = $group->getModule();
//			if(is_null($module)) throw new GroupAdminServiceException('group can not have a null module in database', GroupAdminServiceException::DATA_INTEGRITY_ERROR);
//			$autoS = $this->groupAdminServiceImpl->getAuthorizationService();
//			if(!$autoS->isRootPrincipal($this->principal) && is_null($this->principal->getModuleAccess($module)))
//			{
//				$autoS->fail($this->principal, 'has not access to module '.$module->getModuleName());
//			}
			// ok, adds group to list
			$this->groupList->addGroup($group);
			$this->groupCounter++;
		}
	}
}