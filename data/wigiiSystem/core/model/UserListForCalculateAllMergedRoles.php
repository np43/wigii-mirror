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
 * UserList implementation for the method calculateAllMergedRoles in UserAdminService
 * particularity:
 * 	group by wigiiNamespace
 *  separate calculatedRoles
 * Created by LWR on 6 July 2011
 */
class UserListForCalculateAllMergedRoles extends ObjectListArrayImpl implements UserList
{
	//herited attributs:
	//protected $objArray = array()
	
	protected $wigiiNamespaceList;
	protected $calculatedRoleStack; //is a stack of calculatedRole
	protected $calculatedRoleList; //contains the complete list of calculatedRole
	protected $calculatedRoleIds;
	public function getCalculatedRoleIds(){ return $this->calculatedRoleIds; }
	protected $calculatedRoleStackIds;
	public function getCalculatedRoleStackIds(){ return $this->calculatedRoleStackIds; }
	protected $roleIds;
	public function getRoleIds($wigiiNamespaceUrl){ if(!isset($this->roleIds)) return null; return $this->roleIds[$wigiiNamespaceUrl]; }
	protected $readAllGroupsInWigiiNamespace;
	public function getReadAllGroupsInWigiiNamespace($wigiiNamespaceUrl){ if(!isset($this->readAllGroupsInWigiiNamespace)) return null; return $this->readAllGroupsInWigiiNamespace[$wigiiNamespaceUrl]; }
	
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function getWigiiNamespaceIterator(){
		return $this->wigiiNamespaceList;
	}
	public function getCalculatedRoleIterator(){
		return $this->calculatedRoleList;
	}
	protected function addCalculatedRole($role){
		if(!isset($this->calculatedRoleList)) $this->calculatedRoleList = array();
		$this->calculatedRoleList[$this->getFirstKey($role)] = $role;
		if(!isset($this->calculatedRoleIds)) $this->calculatedRoleIds = array();
		$this->calculatedRoleIds[$role->getId()] = $role->getId();
		
	}
	public function resetCalculatedRoleStack(){
		$this->calculatedRoleStack = $this->getCalculatedRoleIterator();
		$this->calculatedRoleStackIds = $this->getCalculatedRoleIds();
	}
	public function popCalculatedRoleFromStack($wigiiNamespaceUrl){
		if(!isset($this->calculatedRoleStack)) return null;
		if(!isset($this->calculatedRoleStack[$wigiiNamespaceUrl])) return null;
		$returnValue = $this->calculatedRoleStack[$wigiiNamespaceUrl];
		//first setup null to value, otherwise the unset delete the referenced object itself
		$this->calculatedRoleStack[$wigiiNamespaceUrl] = null;
		unset($this->calculatedRoleStack[$wigiiNamespaceUrl]);
		unset($this->calculatedRoleStackIds[$returnValue->getId()]);
		return $returnValue;
	}
	public function getCalculatedRoleStackIterator(){
		return $this->calculatedRoleStack;
	}
	public function countCalculatedRoleStack(){
		if(!isset($this->calculatedRoleStack)) return null;
		return count($this->calculatedRoleStack);
	}
	
	public function getRoleIteratorForWigiiNamespace($wigiiNamespaceUrl){
		if(!isset($this->objArray)) return null;
		if(!isset($this->objArray[$wigiiNamespaceUrl])) return null;
		return $this->objArray[$wigiiNamespaceUrl];
	}
	
	public function addUser($user)
	{
		if(!isset($user)) throw new ListException("user cannot be null", ListException::INVALID_ARGUMENT);
		
		//expired user or role should not be added in the calculation
		if($user->getDetail()->passwordExpired()) return;
		
		if($user->isCalculatedRole()){
			$this->addCalculatedRole($user);
		} else {
			$firstKey = $this->getFirstKey($user);
			$secondKey = $this->getSecondKey($user);
			if(!isset($this->wigiiNamespaceList)) $this->wigiiNamespaceList = array();
			$this->wigiiNamespaceList[$firstKey] = $user->getWigiiNamespace();
			
			if(!isset($this->objArray[$firstKey])) $this->objArray[$firstKey] = array();
			if(isset($this->objArray[$firstKey][$secondKey])) throw new ListException("a user with same key $firstKey, $secondKey, has already been added", ListException::ALREADY_EXISTS);
			
			$this->objArray[$firstKey][$secondKey] = $user;
			if(!isset($this->roleIds)) $this->roleIds = array();
			if(!isset($this->roleIds[$firstKey])) $this->roleIds[$firstKey] = array();
			$this->roleIds[$firstKey][$secondKey] = $user->getId();
			
			if($user->getDetail()->getReadAllGroupsInWigiiNamespace()){
				if(!isset($this->readAllGroupsInWigiiNamespace)) $this->readAllGroupsInWigiiNamespace = array();
				if(!isset($this->readAllGroupsInWigiiNamespace[$firstKey])) $this->readAllGroupsInWigiiNamespace[$firstKey] = array();
				$this->readAllGroupsInWigiiNamespace[$firstKey] = array_merge($this->readAllGroupsInWigiiNamespace[$firstKey], $user->getDetail()->getReadAllGroupsInWigiiNamespace());
			}
		}
	}
	
	protected function getFirstKey($user){
		return $user->getWigiiNamespace()->getWigiiNamespaceUrl();
	}
	protected function getSecondKey($user){
		return $user->getId();
	}
}