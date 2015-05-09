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
 * Maps a group db record to a GroupP as a tree
 * Created by CWE on 22 sept. 09
 */
class GroupPTreeMapper extends Model implements RowList
{
	private $lockedForUse = true;
	private $pRights;
	private $groupAdminServiceImpl;
	private $principal;
	private $groupPBuffer;
	private $parentGroupBuffer;
	private $independantGroups;
	private $children;

	public static function createInstance($groupAdminServiceImpl, $principal, $pRights = null)
	{
		$returnValue = new GroupPTreeMapper();
		$returnValue->groupAdminServiceImpl = $groupAdminServiceImpl;
		$returnValue->reset($principal, $pRights);
		return $returnValue;
	}

	public function reset($principal, $pRights = null)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->pRights = $pRights;
		$this->principal = $principal;
		$this->groupPBuffer = array();
		$this->independantGroups = array();
		$this->children = array();
	}
	public function freeMemory()
	{
		$this->lockedForUse = false;
		unset($this->parentGroupBuffer);
		unset($this->independantGroups);
		unset($this->groupPBuffer);
		unset($this->children);
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public function getListIterator()
	{
		return $this->groupPBuffer;
	}

	public function isEmpty()
	{
		return (count($this->groupPBuffer) == 0);
	}

	public function count()
	{
		return count($this->groupPBuffer);
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			$row['client'] = $this->principal->getWigiiNamespace()->getClient();
			$groupP = GroupP::createInstance($this->groupAdminServiceImpl->createGroupInstanceFromRow($this->principal, $row));
			if(isset($this->pRights))
			{
				$groupP->setRights($this->pRights);
			}
			elseif(isset($row['canModify']))
			{
				$groupP->setRights(PrincipalRights::createInstance($row));
			}
			$g = $groupP->getGroup();
			$gId = $g->getId();
			$pgId = $g->getGroupParentId();

			$this->groupPBuffer[$gId] = $groupP;
			if(is_null($pgId))
			{
				$this->independantGroups[$gId] = $groupP;
			}
			else
			{
				$this->children[$pgId][$gId] = $groupP;
			}
		}
	}

	/**
	 * Flushes the internal buffer into a GroupPTree
	 * fieldSelectorList: field selector list used when fetching parent groups
	 */
	public function flush($groupPTree, $fieldSelectorList=null)
	{
		if(is_null($groupPTree)) throw new GroupAdminServiceException('groupPTree can not be null', GroupAdminServiceException::INVALID_ARGUMENT);
		$this->parentGroupBuffer = array();
		// flushes each group in buffer
		foreach($this->independantGroups as $pgId => $groupP)
		{
			$this->flushGroupP($groupP, $groupPTree, $fieldSelectorList);
			$this->visitChildren($pgId, $groupPTree, $fieldSelectorList);
		}
		$this->freeMemory();
	}

	private function visitChildren($parentGroupId, $groupPTree, $fieldSelectorList)
	{
		if(isset($this->children[$parentGroupId]))
		{
			foreach($this->children[$parentGroupId] as $gId => $groupP)
			{
				$this->flushGroupP($groupP, $groupPTree, $fieldSelectorList);
				$this->visitChildren($gId, $groupPTree, $fieldSelectorList);
			}
		}
	}
	protected function flushGroupP($groupP, $groupPTree, $fieldSelectorList)
	{
		// looks for parent group
		$parentGroupId = $groupP->getGroup()->getGroupParentId();
		if(isset($parentGroupId))
		{
			$parentGroup = $this->parentGroupBuffer[$parentGroupId];
			// if does not exist, then first gets parent group
			if(!isset($parentGroup))
			{
				// do we have a group which is the parent group ?
				$parentGroup = $this->groupPBuffer[$parentGroupId];
				if(isset($parentGroup))
				{
					$parentGroup = $parentGroup->getGroup();
				}
				// else fetches the database
				else
				{
					$parentGroup = $this->groupAdminServiceImpl->getGroup($this->principal, $parentGroupId, $fieldSelectorList);
					if(isset($parentGroup))
					{
						$parentGroup = $parentGroup->getGroup();
					}
					else throw new GroupAdminServiceException("parent group with id $parentGroupId does not exist in database", GroupAdminServiceException::DATA_INTEGRITY_ERROR);
				}
				$this->parentGroupBuffer[$parentGroupId] = $parentGroup;
			}
		}
		else $parentGroup = null;

		// adds groupP and parent to result tree
		$groupPTree->addGroupP($groupP, $parentGroup);
	}
}