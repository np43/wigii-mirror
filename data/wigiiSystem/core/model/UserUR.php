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
 * A User associated to a UUR
 * Created by CWE on 23 août 09
 */
class UserUR extends Model implements DbEntity
{
	private $user;
	private $uur;

	public static function createInstance($user)
	{
		$returnValue = new UserUR();
		$returnValue->setUser($user);
		return $returnValue;
	}
	
	/**
	 * DbEntity implementation
	 */
	public function getId(){
		if($this->getDbEntity()!=null){
			return $this->getDbEntity()->getId();
		}
		return null;
	}
	public function getDbEntity(){
		if(isset($this->user)){
			return $this->user;
		}
		return null;
	}

	public function getUser()
	{
		return $this->user;
	}
	protected function setUser($user)
	{
		$this->user = $user;
	}
	/**
	 * Returns the UserUserRight object attached to this user
	 * returns null if no UUR exists
	 */
	public function getRights()
	{
		return $this->uur;
	}
	public function setRights($uur)
	{
		$this->uur  =$uur;
	}
}