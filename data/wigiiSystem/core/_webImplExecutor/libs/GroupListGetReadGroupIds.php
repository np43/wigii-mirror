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
 * Created on 14 Jan 2011
 * by LWR
 */

class GroupListGetReadGroupIds implements GroupList {

	private $result;

	public static function createInstance(){
		$r = new self();
		$r->reset();
		return $r;
	}

	public function getResult(){
		return $this->result;
	}
	public function getIds(){
		return $this->result;
	}
	public function reset(){
		$this->result = array();
	}

	public function getListIterator(){ return $this->result; }
	public function isEmpty(){ return $this->result == null; }
	public function count(){ return count($this->result); }

	/**
	 * Adds a group to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same group in the list
	 */
	public function addGroup($group){
		$this->result[$group->getId()] = $group->getId();
	}
	public function addGroupP($groupP){
		//if rights are null then that mean it is herited parents. --> don't take them
		//you cannot subscribe for a group without any rights on. you need to have at least read right
		if($groupP->getRights()){
			$this->addGroup($groupP->getDbEntity());
		}
	}
}



