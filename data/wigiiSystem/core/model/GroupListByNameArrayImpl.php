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
 * GroupList implementation which stores groups by their name
 * Created by Medair(CWE) on 03.07.2017
 */
class GroupListByNameArrayImpl extends ObjectListArrayImpl implements GroupList
{			
    public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function reset()
	{
		parent::reset();
		$this->groupIds = array();
	}
	
	private $groupIds;
	/**	 
	 * @return array Returns a map between a Group name and its ID 
	 */
	public function getGroupIds() {
	    return $this->groupIds;
	}
	public function getIds(){
	    return $this->groupIds;
	}
	
	public function addGroup($group)
	{
		if(isset($group)) {
		    $this->objArray[$group->getGroupName()] = $group;
		    $this->groupIds[$group->getGroupName()] = $group->getId();
		}
		return $this;
	}	
	
	public function getItemInList($groupName){
	    return $this->objArray[$groupName];
	}
}