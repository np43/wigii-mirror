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
 * Maps a user user right db record to a UserURP
 * Created by CWE on 20 sept. 09
 */
class UserURPListMapper implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $pRightPrefix;
	private $userURPList;
	private $userCounter;
	private $userAdminServiceImpl;
	private $principal;
	private $userId;

	public static function createInstance($userAdminServiceImpl, $principal, $userId, $userURPList, $pRights = null, $pRightPrefix='P')
	{
		$returnValue = new UserURPListMapper();
		$returnValue->userAdminServiceImpl = $userAdminServiceImpl;
		$returnValue->reset($principal, $userId, $userURPList, $pRights, $pRightPrefix);
		return $returnValue;
	}

	public function reset($principal, $userId, $userURPList, $pRights = null, $pRightPrefix='P')
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->userURPList = $userURPList;
		$this->userCounter = 0;
		$this->pRights = $pRights;
		$this->pRightPrefix = $pRightPrefix;
		$this->principal = $principal;
		$this->userId = $userId;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public function getListIterator()
	{
		return $this->userURPList->getListIterator();
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
			$isOwner = formatBoolean($row[$this->pRightPrefix.'isOwner']);
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$user = $this->userAdminServiceImpl->createUserInstanceFromRow($this->principal, $row, $isOwner);
			$userUR = UserUR::createInstance($user);
			$userUR->setRights(UUR::createInstance($this->userId, $user->getId(), $row));
			$userURP = UserURP::createInstance($userUR);
			if(isset($this->pRights))
			{
				$userURP->setRights($this->pRights);
			}
			elseif($isOwner)
			{
				$pRights = PrincipalRights::createInstance();
				$pRights->setOwner($isOwner);
				$userURP->setRights($pRights);
			}
			$this->userURPList->addUserURP($userURP);
			$this->userCounter++;
		}
	}
}