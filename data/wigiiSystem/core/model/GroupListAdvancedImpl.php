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

/**
 * GroupList advanced implementation
 * Created by LWR on 19 march 10
 *
 */
class GroupListAdvancedImpl extends ObjectListArrayImpl implements GroupList, GroupPList, DbEntityList
{
	protected $groupIds;
	protected function setGroupIds($var){
		$this->groupIds = $var;
	}
	protected function addGroupId($group){
		$this->groupIds[$group->getId()] = $group->getId();
	}
	public function getGroupIds(){
		return $this->groupIds;
	}
	public function getIds()
	{
		return $this->groupIds;
	}

	protected $moduleGroup;
	protected $rootGroups;
	protected $allRootGroupsIsReadable = true;
	protected $allRootGroupsIsWritable = true;
	protected $allRootGroupsIsAdministrable = true;
	protected function setRootGroups($var){
		$this->rootGroups = $var;
	}
	protected function addRootGroup($group){
		$entity = $group->getDbEntity();
		if($entity->getGroupParentId()!=null) throw new ListException("a root group must not have a parent group id", ListException::INVALID_ARGUMENT);
		if($group instanceof GroupP){
			if(!$group->getRights()){
				$this->allRootGroupsIsReadable = false;
			} else if(!$group->getRights()->canWriteElement()){
				$this->allRootGroupsIsWritable = false;
			} else if(!$group->getRights()->canModify()){
				$this->allRootGroupsIsAdministrable = false;
			}
		}
		if($this->isStoreDbEntity()){
			$this->rootGroups->addGroup($entity);
		} else {
			$this->rootGroups->addGroupP($group);
		}
	}
	public function isAllRootGroupsReadable(){ return $this->allRootGroupsIsReadable; }
	public function isAllRootGroupsWritable(){ return $this->allRootGroupsIsWritable; }
	public function isAllRootGroupsAdministrable(){ return $this->allRootGroupsIsAdministrable; }
	public function isAllGroupsInSameModule(){ return count($this->moduleGroup)==1; }
	public function getModule(){ return $this->moduleGroup; }

	public function getRootGroups(){
		return $this->rootGroups;
	}
	protected $noRightGroups;
	protected function setNoRightGroups($var){
		$this->noRightGroups = $var;
	}
	protected function addNoRightGroup($group){
		if($this->isStoreDbEntity()){
			$this->noRightGroups->addGroup($group->getDbEntity());
		} else {
			$this->noRightGroups->addGroupP($group);
		}
	}
	public function getNoRightGroups(){
		return $this->noRightGroups;
	}
	protected $readGroups;
	protected function setReadGroups($var){
		$this->readGroups = $var;
	}
	protected function addReadGroup($group){
		if($this->isStoreDbEntity()){
			$this->readGroups->addGroup($group->getDbEntity());
		} else {
			$this->readGroups->addGroupP($group);
		}
	}
	public function getReadGroups(){
		return $this->readGroups;
	}
	protected $sGroups;
	protected function setSGroups($var){
		$this->sGroups = $var;
	}
	protected function addSGroup($group){
		if($this->isStoreDbEntity()){
			$this->sGroups->addGroup($group->getDbEntity());
		} else {
			$this->sGroups->addGroupP($group);
		}
	}
	public function getSGroups(){
		return $this->sGroups;
	}
	protected $writeGroups;
	protected function setWriteGroups($var){
		$this->writeGroups = $var;
	}
	protected function addWriteGroup($group){
		if($this->isStoreDbEntity()){
			$this->writeGroups->addGroup($group->getDbEntity());
		} else {
			$this->writeGroups->addGroupP($group);
		}
	}
	public function getWriteGroups(){
		return $this->writeGroups;
	}
	protected $modifyGroups;
	protected function setModifyGroups($var){
		$this->modifyGroups = $var;
	}
	protected function addModifyGroup($group){
		if($this->isStoreDbEntity()){
			$this->modifyGroups->addGroup($group->getDbEntity());
		} else {
			$this->modifyGroups->addGroupP($group);
		}
	}
	public function getModifyGroups(){
		return $this->modifyGroups;
	}

	private $storeDbEntity;
	protected function isStoreDbEntity(){
		return $this->storeDbEntity;
	}
	protected function setStoreDbEntity($var){
		$this->storeDbEntity = $var;
	}
	public static function createInstance($storeDbEntity = true)
	{
		$returnValue = new self();
		$returnValue->reset($storeDbEntity);
		return $returnValue;
	}
	public function reset($storeDbEntity = true)
	{
		parent::reset();
		if($storeDbEntity){
			$this->rootGroups = GroupListArrayImpl::createInstance();
			$this->noRightGroups = GroupListArrayImpl::createInstance();
			$this->readGroups = GroupListArrayImpl::createInstance();
			$this->sGroups = GroupListArrayImpl::createInstance();
			$this->writeGroups = GroupListArrayImpl::createInstance();
			$this->modifyGroups = GroupListArrayImpl::createInstance();
		} else {
			$this->rootGroups = GroupPListArrayImpl::createInstance();
			$this->noRightGroups = GroupPListArrayImpl::createInstance();
			$this->readGroups = GroupPListArrayImpl::createInstance();
			$this->sGroups = GroupPListArrayImpl::createInstance();
			$this->writeGroups = GroupPListArrayImpl::createInstance();
			$this->modifyGroups = GroupPListArrayImpl::createInstance();
		}
		$this->setStoreDbEntity($storeDbEntity);
	}

	public function addGroup($group)
	{
		$entity = $group->getDbEntity();
		if(!isset($entity)) throw new ListException("group cannot be null", ListException::INVALID_ARGUMENT);
		$key = $entity->getId(); //$this->getKeyWithNameModuleWigiiNamespace($entity->getId(), $entity->getModule(), $entity->getWigiiNamespace());
		if(isset($this->objArray[$key])) throw new ListException("a group with same key $key has already been added", ListException::ALREADY_EXISTS);
		if($this->isStoreDbEntity()){
			$this->objArray[$key] = $entity;
		} else {
			$this->objArray[$key] = $group;
		}
		$this->addGroupId($group);
		if(!isset($this->moduleGroup)) $this->moduleGroup = $entity->getModule();

		if($entity->getGroupParentId()==null){
			$this->addRootGroup($group);
		}
		return $this;
	}
	public function addGroupP($groupP){
		if($groupP->getRights() == null){
			$this->addNoRightGroup($groupP);
		} else {
			if ($groupP->getRights()->canModify()) $this->addModifyGroup($groupP);
			if ($groupP->getRights()->canWriteElement()) $this->addWriteGroup($groupP);
			if ($groupP->getRights()->canShareElement()) $this->addSGroup($groupP);
			$this->addReadGroup($groupP);
		}
		$this->addGroup($groupP);
		return $this;
	}
	public function getItemInList($groupId){
		return $this->objArray[$groupId];
	}

}