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
 * A userR associated to a PrincipalRights
 * Created by CWE on 26 août 09
 */
class UserRP extends Model implements DbEntity
{
	private $userR;
	private $principalRights;

	public static function createInstance($userR)
	{
		$returnValue = new UserRP();
		$returnValue->setUserR($userR);
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
		if(isset($this->userR)){
			return $this->userR->getDbEntity();
		}
		return null;
	}
	
	public function getUserR()
	{
		return $this->userR;
	}
	public function setUserR($userR)
	{
		$this->userR = $userR;
	}
	public function getRights()
	{
		return $this->principalRights;
	}
	public function setRights($principalRights)
	{
		$this->principalRights = $principalRights;
	}
}