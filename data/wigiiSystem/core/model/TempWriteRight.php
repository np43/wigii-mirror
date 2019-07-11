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
 * A wigii Temporary Write right on a group
 * Created by CWE on 10.07.2019
 */
class TempWriteRight extends UGR
{
    private $sys_creationDate;
    private $initialized = false;
    
    public static function createInstance($principal=MANDATORY_ARG, $groupId=MANDATORY_ARG, $array=UNUSED_ARG, $colPrefix=UNUSED_ARG)
	{
		$ugr = new self();
		$ugr->setPrincipal($principal);
		$ugr->setGroupId($groupId);
		$ugr->setRightsFromLetter('w');
		return $ugr;
	}	
	protected function setPrincipal($principal) {
	    // Temporary Rights are only allowed for authenticated users or roles, not for system principals
	    ServiceProvider::getAuthorizationService()->assertPrincipalHasAttachedUser($principal);
	    // sets user id
	    $this->setUserId($principal->getUserId());
	}	
	public function setCanModify($canModify) { 
	    if($this->initialized) throw new GroupAdminServiceException('TempWriteRight is immutable', GroupAdminServiceException::UNAUTHORIZED);
	    return parent::setCanModify($canModify);
	}
	public function setCanWriteElement($canWriteElement) { 
	    if($this->initialized) throw new GroupAdminServiceException('TempWriteRight is immutable', GroupAdminServiceException::UNAUTHORIZED);
	    return parent::setCanWriteElement($canWriteElement);
	}
	public function setCanShareElement($canShareElement) { 
	    if($this->initialized) throw new GroupAdminServiceException('TempWriteRight is immutable', GroupAdminServiceException::UNAUTHORIZED);
	    return parent::setCanShareElement($canShareElement);
	}
	public function setRightsFromLetter($letter) { 
	    if($this->initialized) throw new GroupAdminServiceException('TempWriteRight is immutable', GroupAdminServiceException::UNAUTHORIZED);
	    $returnValue = parent::setRightsFromLetter($letter);
	    $this->initialized = true;
	    return $returnValue;
	}
	public function getSys_creationDate(){return $this->sys_creationDate; }
	public function setSys_creationDate($var) {
	    if(isset($this->sys_creationDate)) throw new GroupAdminServiceException('TempWriteRight is immutable', GroupAdminServiceException::UNAUTHORIZED);
	    $this->sys_creationDate = $this->formatValue($var); 
	}
}


