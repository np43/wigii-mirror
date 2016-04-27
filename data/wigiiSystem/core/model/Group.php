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

/* A wigii Group
 * Created by CWE on 31 mai 09
 */
class Group extends WigiiNamespaceEntity implements SysInformation
{
	private $groupname;
	private $module;
	private $id_group_parent; // group id
	private $groupDetail;
	
	private $sys_creationDate;
	private $sys_creationUser; // real user id
	private $sys_creationUsername; // real username
	private $sys_date;
	private $sys_user; // real user id
	private $sys_username; // real username

	public static function createInstance($array=null, $withDetail=true)
	{
		$g = new Group();
		if(is_array($array))
		{
			$g->fillFromArray($array);
		}
		if($withDetail)
		{
			$g->setDetail(GroupDetail::createInstance($array));
		}
		return $g;
	}
	
	public function getAttribute($fieldSelector){
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName){
			case "id": return $this->getId(); break;
			case "groupname": return $this->getGroupName(); break;
			case "wigiiNamespace": return $this->getWigiiNamespace(); break;
			case "module": return $this->getModule(); break;
			case "id_group_parent": return $this->getGroupParentId(); break;
			case "sys_date": return $this->getSys_date(); break;
			case "sys_user": return $this->getSys_user(); break;
			case "sys_username": return $this->getSys_username(); break;
			case "sys_creationDate": return $this->getSys_creationDate(); break;
			case "sys_creationUser": return $this->getSys_creationUser(); break;
			case "sys_creationUsername": return $this->getSys_creationUsername(); break;
			default:
				if(isset($this->groupDetail)) return $this->groupDetail->getAttribute($fieldSelector);
				throw new GroupAdminServiceException("invalid group attribute $fName in field selector", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}
	public function setAttribute($value, $fieldSelector){
		if(is_null($fieldSelector)) throw new GroupAdminServiceException("fieldSelector cannot be null", GroupAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		switch($fName){
			case "id": return $this->setId($value); break;
			case "groupname": return $this->setGroupname($value); break;
			case "wigiiNamespace": return $this->setWigiiNamespace($value); break;
			case "module": return $this->setModule($value); break;
			case "id_group_parent": return $this->setGroupParentId($value); break;
			case "sys_date": return $this->setSys_date($value); break;
			case "sys_user": return $this->setSys_user($value); break;
			case "sys_username": return $this->setSys_username($value); break;
			case "sys_creationDate": return $this->setSys_creationDate($value); break;
			case "sys_creationUser": return $this->setSys_creationUser($value); break;
			case "sys_creationUsername": return $this->setSys_creationUsername($value); break;
			default:
				if(isset($this->groupDetail)) return $this->groupDetail->setAttribute($value, $fieldSelector);
				throw new GroupAdminServiceException("invalid group attribute $fName in field selector", GroupAdminServiceException::INVALID_ARGUMENT);
		}
	}

	public function fillFromArray($array, $colPrefix='')
	{
		if(!is_array($array)) throw new ServiceException("array is not an array", ServiceException::INVALID_ARGUMENT);
		if(isset($array[$colPrefix."id"])) $this->setId($array[$colPrefix."id"]);
		if(isset($array[$colPrefix."groupname"])) $this->setGroupname($array[$colPrefix."groupname"]);
		if(isset($array[$colPrefix."wigiiNamespace"])) $this->setWigiiNamespace($array[$colPrefix."wigiiNamespace"]);
		if(isset($array[$colPrefix."module"])) $this->setModule($array[$colPrefix."module"]);
		if(isset($array[$colPrefix."id_group_parent"])) $this->setGroupParentId($array[$colPrefix."id_group_parent"]);
		if(isset($array[$colPrefix."sys_creationDate"])) $this->setSys_creationDate($array[$colPrefix."sys_creationDate"]);
		if(isset($array[$colPrefix."sys_creationUser"])) $this->setSys_creationUser($array[$colPrefix."sys_creationUser"]);
		if(isset($array[$colPrefix."sys_creationUsername"])) $this->setSys_creationUsername($array[$colPrefix."sys_creationUsername"]);
		if(isset($array[$colPrefix."sys_date"])) $this->setSys_date($array[$colPrefix."sys_date"]);
		if(isset($array[$colPrefix."sys_user"])) $this->setSys_user($array[$colPrefix."sys_user"]);
		if(isset($array[$colPrefix."sys_username"])) $this->setSys_username($array[$colPrefix."sys_username"]);
	}

	// Attributes

	public function getGroupName()
	{
		return $this->groupname;
	}
	public function setGroupName($groupName)
	{
		$this->groupname = $this->formatValue($groupName);
	}
	public function getModule()
	{
		return $this->module;
	}
	public function setModule($module)
	{
		$this->module = $module;
	}
	public function getGroupParentId()
	{
		return $this->id_group_parent;
	}
	public function setGroupParentId($groupParentId)
	{
		if($groupParentId == 0)
		{
			$this->id_group_parent = null;
		}
		else $this->id_group_parent = $this->formatValue($groupParentId);
	}
	public function getDetail()
	{
		return $this->groupDetail;
	}
	public function setDetail($groupDetail)
	{
		$this->groupDetail = $groupDetail;
	}
	
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



