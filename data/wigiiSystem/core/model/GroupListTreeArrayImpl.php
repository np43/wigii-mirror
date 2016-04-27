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
 * GroupList array based implementation which organizes the groups as a tree
 * Created by CWE on 20 mars 10
 */
class GroupListTreeArrayImpl extends ObjectListArrayImpl implements GroupList, GroupPList
{
	protected $childrenGroups;
	protected $rootGroups;

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	public function reset()
	{
		parent::reset();
		$this->childrenGroups = array();
		$this->rootGroups = array();
	}
	public function freeMemory()
	{
		parent::freeMemory();
		unset($this->childrenGroups);
		unset($this->rootGroups);
	}

	// Tree implementation

	/**
	 * Visits the Group Tree in depth, starting from roots (level 0)
	 */
	public function visitInDepth($treeInDepthVisitor)
	{
		if(is_null($treeInDepthVisitor)) throw new ListException("treeInDepthVisitor cannot be null", ListException::INVALID_ARGUMENT);
		if(isset($this->rootGroups))
		{
			foreach($this->rootGroups as $rg)
			{
				if(!$this->recursiveVisitInDepth($rg, $treeInDepthVisitor, 0)) break;
			}
		}
	}
	protected function recursiveVisitInDepth($currentGroup, $treeInDepthVisitor, $depth)
	{
		if(isset($currentGroup))
		{
			$gId = $currentGroup->getId();
			if(isset($this->childrenGroups[$gId])) $nChildren = count($this->childrenGroups[$gId]);
			else $nChildren = 0;
			// visits current node
			$visitedAllChildren = true;
			if($treeInDepthVisitor->actOnTreeNode($currentGroup, $depth, $nChildren))
			{
				// ok to visit children
				if($nChildren > 0)
				{
					foreach($this->childrenGroups[$gId] as $cg)
					{
						if(!$this->recursiveVisitInDepth($cg, $treeInDepthVisitor, $depth+1))
						{
							$visitedAllChildren = false;
							break;
						}
					}
				}
			}
			else $visitedAllChildren = false;
			// acts on current node after children visit
			return $treeInDepthVisitor->actAfterTreeNode($currentGroup, $depth, $visitedAllChildren);
		}
		return true;
	}

	// GroupList implementation
	public function addGroupP($groupP){
		return $this->addGroup($groupP->getGroup());
	}
	public function addGroup($group)
	{
		if(!isset($group)) throw new ListException("group cannot be null", ListException::INVALID_ARGUMENT);
		$key = $group->getId();
		if(isset($this->objArray[$key])) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $group;
		$parentGroupId = $group->getGroupParentId();
		// adds root group
		if(is_null($parentGroupId))
		{
			$this->rootGroups[$key] = $group;
		}
		// adds child group
		else
		{
			$this->childrenGroups[$parentGroupId][$key] = $group;
		}
		return $this;
	}
}