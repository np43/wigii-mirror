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
 * Maps a user db record to a UserP
 * Created by CWE on 20 sept. 09
 */
class UserPListMapper implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $userPList;
	private $userCounter;
	private $userAdminServiceImpl;
	private $principal;

	public static function createInstance($userAdminServiceImpl, $principal, $userPList, $pRights = null)
	{
		$returnValue = new UserPListMapper();
		$returnValue->userAdminServiceImpl = $userAdminServiceImpl;
		$returnValue->reset($principal, $userPList, $pRights);
		return $returnValue;
	}

	public function reset($principal, $userPList, $pRights = null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->userPList = $userPList;
		$this->userCounter = 0;
		$this->pRights = $pRights;
		$this->principal = $principal;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	public function getListIterator()
	{
		return $this->userPList->getListIterator();
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
			$isOwner = formatBoolean($row['isOwner']) || (isset($this->pRights) && $this->pRights->isOwner());
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$userP = UserP::createInstance($this->userAdminServiceImpl->createUserInstanceFromRow($this->principal, $row, $isOwner));
			if(isset($this->pRights))
			{
				$userP->setRights($this->pRights);
			}
			elseif($isOwner)
			{
				$userP->setRights(PrincipalRights::createInstance($row));
			}
			$this->userPList->addUserP($userP);
			$this->userCounter++;
		}
	}
}