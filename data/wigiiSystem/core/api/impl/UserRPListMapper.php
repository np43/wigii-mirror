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
 * Maps a user db record with rights to a UserRP
 * Created by CWE on 20 sept. 09
 */
class UserRPListMapper implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $userRPList;
	private $userCounter;
	private $userAdminServiceImpl;
	private $principal;
	private $groupId;

	public static function createInstance($userAdminServiceImpl, $principal, $groupId, $userRPList, $pRights = null)
	{
		$returnValue = new UserRPListMapper();
		$returnValue->userAdminServiceImpl = $userAdminServiceImpl;
		$returnValue->reset($principal, $groupId, $userRPList, $pRights);
		return $returnValue;
	}

	public function reset($principal, $groupId, $userRPList, $pRights = null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->userRPList = $userRPList;
		$this->userCounter = 0;
		$this->pRights = $pRights;
		$this->principal = $principal;
		$this->groupId = $groupId;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	public function getListIterator()
	{
		return $this->userRPList->getListIterator();
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
			$user = $this->userAdminServiceImpl->createUserInstanceFromRow($this->principal, $row, $isOwner);
			$userR = UserR::createInstance($user);
			$userR->setRights(UGR::createInstance($this->groupId, $user->getId(), $row));
			$userRP = UserRP::createInstance($userR);
			if(isset($this->pRights))
			{
				$userRP->setRights($this->pRights);
			}
			elseif($isOwner)
			{
				$pRights = PrincipalRights::createInstance();
				$pRights->setOwner($isOwner);
				$userRP->setRights($pRights);
			}
			$this->userRPList->addUserRP($userRP);
			$this->userCounter++;
		}
	}
}