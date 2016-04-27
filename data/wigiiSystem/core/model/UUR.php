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

/* A wigii User User Right
 * Created by CWE on 31 mai 09
 */
class UUR extends DbEntityInstance
{
	private $user1Id; // id user 1
	private $user2Id; // id user 2
	private $isOwner;
	private $hasRole;

	public static function createInstance($user1Id, $user2Id, $array=null, $colPrefix='')
	{
		$uur = new UUR();
		$uur->setUser1Id($user1Id);
		$uur->setUser2Id($user2Id);
		$uur->setIsOwner(false);
		$uur->setHasRole(false);
		if(is_array($array))
		{
			if(isset($array[$colPrefix.'isOwner'])) $uur->setIsOwner($array[$colPrefix.'isOwner']);
			if(isset($array[$colPrefix.'hasRole'])) $uur->setHasRole($array[$colPrefix.'hasRole']);
		}
		return $uur;
	}

	public function getUser1Id()
	{
		return $this->user1Id;
	}
	protected function setUser1Id($user1Id)
	{
		$this->user1Id = $user1Id;
	}
	public function getUser2Id()
	{
		return $this->user2Id;
	}
	protected function setUser2Id($user2Id)
	{
		$this->user2Id = $user2Id;
	}

	/**
	 * true if User 1 owns User 2
	 */
	public function isOwner()
	{
		return $this->isOwner;
	}
	/**
	 * true if User 1 owns User 2
	 */
	public function setIsOwner($isOwner)
	{
		$this->isOwner = $isOwner;
		return $this;
	}
	/**
	 * true if User 1 has User 2 as a role
	 */
	public function hasRole()
	{
		return $this->hasRole;
	}
	/**
	 * true if User 1 has User 2 as a role
	 */
	public function setHasRole($hasRole)
	{
		$this->hasRole = $hasRole;
		return $this;
	}
}


