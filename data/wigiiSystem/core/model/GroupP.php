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
 * A group associated to a PrincipalRights
 * Created by CWE on 26 août 09
 */
class GroupP extends Model implements DbEntity
{
	private $group;
	private $principalRights;

	public static function createInstance($group)
	{
		$returnValue = new GroupP();
		$returnValue->setGroup($group);
		return $returnValue;
	}
	
	public static function createReadInstance($group) {
		$returnValue = self::createInstance($group);
		$returnValue->setRights(PrincipalRights::createInstance());
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
		if(isset($this->group)){
			return $this->group;
		}
		return null;
	}
	
	public function getGroup()
	{
		return $this->group;
	}
	protected function setGroup($group)
	{
		$this->group = $group;
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