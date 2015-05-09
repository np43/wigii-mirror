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
 * UserPList array implementation
 * Created by LWR on 26 february 10
 */
class UserPListArrayImpl extends ObjectListArrayImpl implements UserPList
{
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function getIds(){
		if(isset($this->objArray)){
			return array_keys($this->objArray);
		}
	}
	public function addUserP($userP)
	{
		if(!isset($userP)) throw new ListException("userP cannot be null", ListException::INVALID_ARGUMENT);
		$user = $userP->getUser();
		$key = $userP->getId(); //$this->getKeyWithNameModuleWigiiNamespace($user->getUsername(), null, $user->getWigiiNamespace());
		if(isset($this->objArray[$key])) throw new ListException("a user with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $userP;
	}
}