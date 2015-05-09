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
 * Maps a user db record to a User
 * Created by LWR, on 4 march 2010
 */
class UserListMapper implements RowList
{
	private $lockedForUse = true;
	private $userList;
	private $userCounter;
	private $userAdminServiceImpl;
	private $principal;
	private $withDetails;

	public static function createInstance($userAdminServiceImpl, $principal, $userList, $withDetails = true)
	{
		$returnValue = new UserListMapper();
		$returnValue->userAdminServiceImpl = $userAdminServiceImpl;
		$returnValue->reset($principal, $userList, $withDetails);
		return $returnValue;
	}

	public function reset($principal, $userList, $withDetails = true)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->userList = $userList;
		$this->userCounter = 0;
		$this->principal = $principal;
		$this->withDetails = $withDetails;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public function getListIterator()
	{
		return $this->userList->getListIterator();
	}

	public function isEmpty()
	{
		return ($this->userCounter == 0);
	}

	public function count()
	{
		return $this->userCounter;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$user = $this->userAdminServiceImpl->createUserInstanceFromRow($this->principal, $row, $this->withDetails);
			$this->userList->addUser($user);
			$this->userCounter++;
		}
	}
}