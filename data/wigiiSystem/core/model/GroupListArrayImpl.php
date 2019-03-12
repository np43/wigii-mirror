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
 * GroupList array implementation
 * Created by CWE on 10 oct. 09
 */
class GroupListArrayImpl extends ObjectListArrayImpl implements GroupList
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
		return $this->groupIds;
	}
	
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	private $mergeDuplicates=false;
	public function setMergeDuplicates($bool) {
	    $this->mergeDuplicates=$bool;
	    return $this;
	}
	
	public function reset()
	{
		parent::reset();
		$this->groupIds = array();
	}
	
	public function addGroup($group)
	{
		if(!isset($group)) throw new ListException("group cannot be null", ListException::INVALID_ARGUMENT);
		$key = $group->getId(); //$this->getKeyWithNameModuleWigiiNamespace($group->getId(), $group->getModule(), $group->getWigiiNamespace());
		if(isset($this->objArray[$key]) && !$this->mergeDuplicates) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $group;
		$this->addGroupId($group);
		return $this;
	}	
	
	public function getItemInList($groupId){
		return $this->objArray[$groupId];
	}
}