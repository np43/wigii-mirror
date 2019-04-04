<?php
/**
 *  This file is part of Wigii (R) software.
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
 * A User associated to a UGR
 * Created by CWE on 23 août 09
 */
class UserR extends Model implements DbEntity
{
	private $user;
	private $ugr;

	public static function createInstance($user)
	{
		$returnValue = new UserR();
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
	 * Returns the UserGroupRight object attached to this user
	 * returns null if no UGR exists
	 */
	public function getRights()
	{
		return $this->ugr;
	}
	public function setRights($ugr)
	{
		$this->ugr  =$ugr;
	}
}