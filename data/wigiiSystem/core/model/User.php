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

/* A wigii User
 * Created by CWE on 31 mai 09
 */
if(!defined('USERNAME_minLength')) define('USERNAME_minLength', 3);
if(!defined('USERNAME_maxLength')) define('USERNAME_maxLength', 64);
class User extends WigiiNamespaceEntity implements SysInformation
{
	private $username;
	private $userDetail;
	private $isRole;
	private $isCalculatedRole;
	private $isPublic;
	
	private $sys_creationDate;
	private $sys_creationUser; // real user id
	private $sys_creationUsername; // real username
	private $sys_date;
	private $sys_user; // real user id
	private $sys_username; // real username
	
	/** create an instance of a User
	 * @param $array array = null, if define we will fill a maximum of attributs based on
	 * the key and value of this array
	 */
	public static function createInstance($array = null, $withDetail=true)
	{
		$u = new User();
		if(is_array($array))
		{
			$u->fillFromArray($array);
			
		}
		if($withDetail)
		{
			$u->setDetail(UserDetail::createInstance($array));
		}
		return $u;
	}
	
	public function getAttribute($fieldSelector){
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName){
			case "username": return $this->getUsername(); break;
			case "id": return $this->getId(); break;
			case "wigiiNamespace": return $this->getWigiiNamespace(); break;
			case "isRole": return $this->isRole(); break;
			case "isCalculatedRole": return $this->isCalculatedRole(); break;
			case "sys_date": return $this->getSys_date(); break;
			case "sys_user": return $this->getSys_user(); break;
			case "sys_username": return $this->getSys_username(); break;
			case "sys_creationDate": return $this->getSys_creationDate(); break;
			case "sys_creationUser": return $this->getSys_creationUser(); break;
			case "sys_creationUsername": return $this->getSys_creationUsername(); break;
			default:
				if(isset($this->userDetail)) return $this->userDetail->getAttribute($fieldSelector);
				throw new UserAdminServiceException("invalid user attribute $fName in field selector", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}
	public function setAttribute($value, $fieldSelector){
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName){
			case "username": return $this->setUsername($value); break;
			case "id": return $this->setId($value); break;
			case "wigiiNamespace": return $this->setWigiiNamespace($value); break;
			case "isRole": return $this->setRole($value); break;
			case "isCalculatedRole": return $this->setCalculatedRole($value); break;
			case "sys_date": return $this->setSys_date($value); break;
			case "sys_user": return $this->setSys_user($value); break;
			case "sys_username": return $this->setSys_username($value); break;
			case "sys_creationDate": return $this->setSys_creationDate($value); break;
			case "sys_creationUser": return $this->setSys_creationUser($value); break;
			case "sys_creationUsername": return $this->setSys_creationUsername($value); break;
			default:
				if(isset($this->userDetail)) return $this->userDetail->setAttribute($value, $fieldSelector);
				throw new UserAdminServiceException("invalid user attribute $fName in field selector", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}

	public function fillFromArray($array, $colPrefix='')
	{
		if(!is_array($array)) throw new ServiceException("array is not an array", ServiceException::INVALID_ARGUMENT);
		if(isset($array[$colPrefix."id"])) $this->setId($array[$colPrefix."id"]);
		if(isset($array[$colPrefix."username"])) $this->setUsername($array[$colPrefix."username"]);
		if(isset($array[$colPrefix."wigiiNamespace"])) $this->setWigiiNamespace($array[$colPrefix."wigiiNamespace"]);
		if(isset($array[$colPrefix."isRole"])) $this->setRole($array[$colPrefix."isRole"]);
		if(isset($array[$colPrefix."isCalculatedRole"])) $this->setCalculatedRole($array[$colPrefix."isCalculatedRole"]);
		if(isset($array[$colPrefix."sys_creationDate"])) $this->setSys_creationDate($array[$colPrefix."sys_creationDate"]);
		if(isset($array[$colPrefix."sys_creationUser"])) $this->setSys_creationUser($array[$colPrefix."sys_creationUser"]);
		if(isset($array[$colPrefix."sys_creationUsername"])) $this->setSys_creationUsername($array[$colPrefix."sys_creationUsername"]);
		if(isset($array[$colPrefix."sys_date"])) $this->setSys_date($array[$colPrefix."sys_date"]);
		if(isset($array[$colPrefix."sys_user"])) $this->setSys_user($array[$colPrefix."sys_user"]);
		if(isset($array[$colPrefix."sys_username"])) $this->setSys_username($array[$colPrefix."sys_username"]);
	}
	
	// Attributes

	public function getUsername()
	{
		return $this->username;
	}
	public function getDetail()
	{
		return $this->userDetail;
	}

	public function setUsername($var)
	{
		$tempUsername = array();
		$allow = '[_a-z0-9-]';
		//var2 is var without the @domain.xx if exist)
		if(preg_match('/@'.$allow.'+(\.'.$allow.'+)*(\.'.$allow.'{2,})+$/i', $var, $tempUsername)==1){
			$var2 = str_replace($tempUsername[0], "", $var);
		} else {
			$var2 = $var;
		}
		unset($tempUsername);
		ArgValidator::assertNoSpecialCharsString("username $var is invalid. Only alphanumeric characters and _.- are allowed. Min length:".USERNAME_minLength." Max lenght:".USERNAME_maxLength,$var2,
			USERNAME_minLength, USERNAME_maxLength, false, UserAdminServiceException::INVALID_USERNAME);
		$this->username = $this->formatValue($var);
	}

	public function setDetail($userDetail)
	{
		$this->userDetail = $userDetail;
	}

	public function isRole(){ return $this->isRole; }
	public function setRole($var){ $this->isRole = $this->formatBoolean($var); }
	public function isCalculatedRole(){ return $this->isCalculatedRole; }
	public function setCalculatedRole($var){ $this->isCalculatedRole = $this->formatBoolean($var); }
	public function isPublic(){ return $this->isPublic;}
	public function setPublic($var) { $this->isPublic = $var;}
	
	public function getSys_creationDate(){return $this->sys_creationDate; }
	public function setSys_creationDate($var){$this->sys_creationDate = $this->formatValue($var); }
	public function getSys_creationUser(){ return $this->sys_creationUser; }
	public function setSys_creationUser($var){$this->sys_creationUser = $var; }
	public function getSys_creationUsername(){ return $this->sys_creationUsername; }
	public function setSys_creationUsername($var){$this->sys_creationUsername = $var; }
	public function getSys_date(){return $this->sys_date; }
	public function setSys_date($var){$this->sys_date = $this->formatValue($var); }
	public function getSys_user(){ return $this->sys_user; }
	public function setSys_user($var){$this->sys_user = $var; }
	public function getSys_username(){ return $this->sys_username; }
	public function setSys_username($var){$this->sys_username = $var; }
	
}

