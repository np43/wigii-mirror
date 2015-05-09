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
 * Maps a group db record with rights to a GroupRP
 * Created by LWR on 6 October 2011
 */
class GroupRPListMapper extends Model implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $groupRPList;
	private $groupCounter;
	private $groupAdminServiceImpl;
	private $principal;
	private $userId;
	private $colPrefixP;
	private $colPrefixR;
	
	public static function createInstance($groupAdminServiceImpl, $principal, $userId, $groupRPList, $colPrefixR='GR', $colPrefixP='GP', $pRights = null)
	{
		$returnValue = new GroupRPListMapper();
		$returnValue->groupAdminServiceImpl = $groupAdminServiceImpl;
		$returnValue->reset($principal, $userId, $groupRPList, $colPrefixR, $colPrefixP, $pRights);
		return $returnValue;
	}

	public function reset($principal, $userId, $groupRPList, $colPrefixR, $colPrefixP, $pRights = null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->groupRPList = $groupRPList;
		$this->groupCounter = 0;
		$this->pRights = $pRights;
		$this->principal = $principal;
		$this->userId = $userId;
		$this->colPrefixR = $colPrefixR;
		$this->colPrefixP = $colPrefixP;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public function getListIterator()
	{
		return $this->groupRPList->getListIterator();
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
			
			$group = $this->groupAdminServiceImpl->createGroupInstanceFromRow($this->principal, $row, $row[$this->colPrefixP."canModify"]);
			$groupR = GroupR::createInstance($group);
			$groupR->setRights(UGR::createInstance($this->userId, $group->getId(), $row, $this->colPrefixR));
			$groupRP = GroupRP::createInstance($groupR);
			if(isset($this->pRights))
			{
				$groupRP->setRights($this->pRights);
			}
			else
			{
				$groupRP->setRights(PrincipalRights::createInstance($row, $this->colPrefixP));
			}
			$this->groupRPList->addGroupRP($groupRP);
			$this->groupCounter++;
		}
	}
}