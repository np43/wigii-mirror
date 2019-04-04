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
 * Principal contextual rights and authorizations,
 * user interface helper to enable/disable the right buttons
 * Created by CWE on 26 août 09
 */
class PrincipalRights extends Model
{
	private $owner;
	private $role;
	private $bCanModify;
	private $bCanWriteElement;
	private $bCanShareElement;
	
	/**
	 * array : db row
	 * colPrefix : column prefix used in the DB request
	 */
	public static function createInstance($array=null, $colPrefix='')
	{
		$returnValue = new PrincipalRights();
		$returnValue->setRole(false);
		$returnValue->setOwner(false);
		$returnValue->setCanModify(false);
		$returnValue->setCanWriteElement(false);
		$returnValue->setCanShareElement(false);
		//eput($array);
		if(is_array($array))
		{
			if(isset($array[$colPrefix.'isOwner'])) $returnValue->setOwner($array[$colPrefix.'isOwner']);
			if(isset($array[$colPrefix.'hasRole'])) $returnValue->setRole($array[$colPrefix.'hasRole']);
			if(isset($array[$colPrefix.'canModify'])) $returnValue->setCanModify($array[$colPrefix.'canModify']);
			if(isset($array[$colPrefix.'canWriteElement'])) $returnValue->setCanWriteElement($array[$colPrefix.'canWriteElement']);
			if(isset($array[$colPrefix.'canShareElement'])) $returnValue->setCanShareElement($array[$colPrefix.'canShareElement']);
		}
		return $returnValue;
	}

	/**
	 * Adds other principal rights to this one
	 */
	public function addPrincipalRights($principalRights)
	{
		if(is_null($principalRights)) return;
		$this->setOwner($this->isOwner() || $principalRights->isOwner());
		$this->setRole($this->hasRole() || $principalRights->hasRole());
		$this->setCanModify($this->canModify() || $principalRights->canModify());
		$this->setCanWriteElement($this->canWriteElement() || $principalRights->canWriteElement());
		$this->setCanShareElement($this->canShareElement() || $principalRights->canShareElement());
	}

	/**
	 * Adds principalRights2 to principalRights1 and returns modified principalRights1
	 */
	public static function addPrincipalRights2to1($principalRights1, $principalRights2)
	{
		if(is_null($principalRights1)) return $principalRights2;
		else
		{
			$principalRights1->addPrincipalRights($principalRights2);
			return $principalRights1;
		}
	}

	/**
	 * Principal owns the attached user
	 */
	public function isOwner()
	{
		return $this->owner;
	}
	public function setOwner($owner)
	{
		$this->owner = $this->formatBoolean($owner);
	}
	/**
	 * Principal has the attached user as a role
	 */
	public function hasRole()
	{
		return $this->role;
	}
	public function setRole($hasRole)
	{
		$this->role = $this->formatBoolean($hasRole);
	}

	public function canModify()
	{
		return $this->bCanModify;
	}
	public function setCanModify($canModify)
	{
		$this->bCanModify = $this->formatBoolean($canModify);
	}
	public function canWriteElement()
	{
		return $this->bCanWriteElement;
	}
	public function setCanWriteElement($canWriteElement)
	{
		$this->bCanWriteElement = $this->formatBoolean($canWriteElement);
	}
	public function canShareElement()
	{
		return $this->bCanShareElement;
	}
	public function setCanShareElement($canShareElement)
	{
		$this->bCanShareElement = $this->formatBoolean($canShareElement);
	}
	
	/**
	 * Returns the PrincipalRights as a letter
	 * @return string x,w,s or r.
	 */
	public function getLetter() {
		if($this->canModify()) return "x";
		if($this->canWriteElement()) return "w";
		if($this->canShareElement()) return "s";
		return "r";
	}
}