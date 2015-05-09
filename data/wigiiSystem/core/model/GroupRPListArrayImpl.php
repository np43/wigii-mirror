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
 * GroupRPList array implementation
 * Created by LWR on 6 October 2011
 */
class GroupRPListArrayImpl extends ObjectListArrayImpl implements GroupRPList
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
		$this->groupRights = array();
	}
	
	public function addGroupRP($groupRP)
	{
		if(!isset($groupRP)) throw new ListException("groupRP cannot be null", ListException::INVALID_ARGUMENT);
		$group = $groupRP->getGroupR()->getGroup();
		$key = $this->getKeyWithNameModuleWigiiNamespace($group->getId(), $group->getModule(), $group->getWigiiNamespace());
		if(isset($this->objArray[$key])) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $groupRP;
		$this->addGroupRightsAsLetter($groupRP);
	}
	
	// Group rights
	
	private $groupRights;
	/**
	 * @return Array returns an array(groupId=>right as a letter).
	 */
	public function getGroupRightsAsLetter() {
		return $this->groupRights;
	}
	protected function addGroupRightsAsLetter($groupRP) {
		$rights = $groupRP->getGroupR()->getRights();
		if(isset($rights)) $this->groupRights[$groupRP->getGroupR()->getGroup()->getId()] = $rights->getLetter();
	}
}