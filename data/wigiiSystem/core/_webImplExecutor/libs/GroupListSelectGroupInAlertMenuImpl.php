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
 * Created on 12 Jan 2010
 * by LWR
 */

class GroupListSelectGroupInAlertMenuImpl implements GroupList {

	private $jsCode;

	public static function createInstance(){
		$r = new self();
		$r->reset();
		return $r;
	}

	public function getJsCode(){
		return $this->jsCode;
	}
	public function reset(){
		$this->jsCode = "";
	}

	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }

	/**
	 * Adds a group to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same group in the list
	 */
	public function addGroup($group){
		$this->jsCode .= " $('#userManageAlertEmailsMenu li[id^=group_".$group->getId()."_]').addClass('selected').find('li').addClass('selectedHerited'); ";
		$this->jsCode .= " $('#userManageAlertEmailsMenu li[id$=group_".$group->getId()."]').addClass('selected').find('li').addClass('selectedHerited'); ";
	}
	public function addGroupP($groupP){
		//if rights are null then that mean it is herited parents. --> don't take them
		//you cannot subscribe for a group without any rights on. you need to have at least read right
		if($groupP->getRights()){
			$this->addGroup($groupP->getDbEntity());
		}
	}
}



