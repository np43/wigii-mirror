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
 * Maps a group db record to a GroupP as a list
 * Created by CWE on 11 oct. 09
 */
class GroupPListMapper implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $groupPList;
	private $groupPCounter;
	private $groupAdminServiceImpl;
	private $principal;

	public static function createInstance($groupAdminServiceImpl, $principal, $groupPList, $pRights = null)
	{
		$returnValue = new GroupPListMapper();
		$returnValue->groupAdminServiceImpl = $groupAdminServiceImpl;
		$returnValue->reset($principal, $groupPList, $pRights);
		return $returnValue;
	}

	public function reset($principal, $groupPList, $pRights = null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->groupPList = $groupPList;
		$this->pRights = $pRights;
		$this->groupPCounter = 0;
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
		return $this->groupPList->getListIterator();
	}

	public function isEmpty()
	{
		return ($this->groupPCounter == 0);
	}

	public function count()
	{
		return $this->groupPCounter;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			$canRead = formatBoolean($row['canRead']);
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$groupP = GroupP::createInstance($this->groupAdminServiceImpl->createGroupInstanceFromRow($this->principal, $row, $canRead));
			if(isset($this->pRights))
			{
				$groupP->setRights($this->pRights);
			}
			elseif($canRead)
			{
				$groupP->setRights(PrincipalRights::createInstance($row));
			}
			$this->groupPList->addGroupP($groupP);
			$this->groupPCounter++;
		}
	}
}