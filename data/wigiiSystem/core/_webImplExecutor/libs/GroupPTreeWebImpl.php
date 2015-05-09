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

/*
 * Created on 25 sept. 09
 * by LWR
 */

class GroupPTreeWebImpl extends Model implements GroupPTree {
	
	protected $nb;
	protected $groups;
	protected function setGroups($groups){ $this->groups = $groups; }
	protected function getGroups(){ 
		if(!isset($this->groups)){
			$this->groups = array();
		}
		return $this->groups; }
	
	public function createInstance(){
		$gt = new self();
		return $gt;
	}
	
	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator(){
		return $this->getGroups();
	}

	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty(){
		return $this->nb == null;
	}

	/**
	 * Returns the number of items in the list
	 */
	public function count(){
		return $this->nb;
	}
	
	/**
	 * Adds a group to the tree
	 * parentGroup: the parent group to which attach the current group added
	 * can be null if no parent.
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same group in the tree
	 */
	public function addGroupP($groupP, $parentGroup){
		$this->nb ++;
		if($this->groups[$groupP->getGroup()->getId()] != null) throw new ListException("the group ".$groupP->getGroup()->getId().":".$groupP->getGroup()->getGroupName()." already exists in GroupPTree.", ListException::ALREADY_EXISTS);
		$this->groups[$groupP->getGroup()->getId()]=$groupP;
		return $this;
	}
	
}


