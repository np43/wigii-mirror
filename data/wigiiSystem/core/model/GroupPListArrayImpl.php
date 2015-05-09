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
 * GroupPList array implementation
 * Created by CWE on 1 nov. 09
 */
class GroupPListArrayImpl extends ObjectListArrayImpl implements GroupPList
{
	protected $groupIds;
	protected function setGroupIds($var){
		$this->groupIds = $var;
	}
	protected function addGroupId($group){
		$this->groupIds[$group->getId()] = $group->getId();
	}
	public function getGroupIds(){
		return $this->groupIds;
	}
	public function getIds(){
		return $this->getGroupIds();
	}
	
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function reset()
	{
		parent::reset();
		$this->groupIds = null;
		$this->groupRights = array();
	}
	
	public function getGroupP($groupId){
		return $this->objArray[$groupId];
	}
	public function addGroupP($groupP)
	{
		if(!isset($groupP)) throw new ListException("groupP cannot be null", ListException::INVALID_ARGUMENT);
		$group = $groupP->getGroup();
		$key = $group->getId(); //$this->getKeyWithNameModuleWigiiNamespace($group->getId(), $group->getModule(), $group->getWigiiNamespace());
		if(isset($this->objArray[$key])) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $groupP;
		$this->addGroupId($groupP);
		$this->addGroupRightsAsLetter($groupP);
		return $this;
	}
	
	public function getItemInList($groupId){
		return $this->objArray[$groupId];
	}
	
	// Group rights
	
	private $groupRights;
	/**
	 * @return Array returns an array(groupId=>principal right as a letter). 
	 */
	public function getGroupRightsAsLetter() {
		return $this->groupRights;
	}
	protected function addGroupRightsAsLetter($groupP) {
		$rights = $groupP->getRights();
		if(isset($rights)) $this->groupRights[$groupP->getId()] = $rights->getLetter();
	}
}