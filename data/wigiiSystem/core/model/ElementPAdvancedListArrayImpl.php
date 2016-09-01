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
 * ElementPList advanced impl, with additionnal getter
 * created by LWR, on 19 march 2010
 */

class ElementPAdvancedListArrayImpl extends ObjectListArrayImpl implements ElementPAdvancedList {

	private $filterOnWritable;
	protected function setFilterOnWritable($var){
		$this->filterOnWritable = $var;
	}
	public function isFilterOnWritable(){
		return $this->filterOnWritable;
	}
	private $allHaveReadRights;
	protected function setAllHaveReadRights($var){
		$this->allHaveReadRights = $var;
	}
	public function allHaveReadRights(){
		return $this->allHaveReadRights;
	}
	private $allHaveWriteRights;
	protected function setAllHaveWriteRights($var){
		$this->allHaveWriteRights = $var;
	}
	private $allHaveAdminRights;
	protected function setAllHaveAdminRights($var){
		$this->allHaveAdminRights = $var;
	}
	public function allHaveWriteRights(){
		return $this->allHaveWriteRights;
	}
	public function allHaveAdminRights(){
		return $this->allHaveAdminRights;
	}
	private $specificAttributFS;
	private $allHaveSpecificAttribut;
	protected function setAllHaveSpecificAttribut($var){
		$this->allHaveSpecificAttribut = $var;
	}
	public function allHaveSpecificAttribut(){
		return $this->allHaveSpecificAttribut;
	}
	private $atLeastOneHasSpecificAttribut;
	protected function setAtLeastOneHasSpecificAttribut($var){
		$this->atLeastOneHasSpecificAttribut = $var;
	}
	public function atLeastOneHasSpecificAttribut() {
		return $this->atLeastOneHasSpecificAttribut;
	}
	public function getSpecificAttributFieldSelector(){
		return $this->specificAttributFS;
	}

	private $fieldSelectorList;
	protected function setFieldSelectorList($fieldSeleclectorList){
		$this->fieldSelectorList = $fieldSeleclectorList;
	}
	public function getFieldSelectorList(){
		return $this->fieldSelectorList;
	}

	private $elementIds;
	protected function addElementId($id){
		$this->elementIds[$id] = $id;
	}
	public function getElementIds(){
		return $this->elementIds;
	}
	public function getIds()
	{
		return $this->elementIds;
	}
	private $sysUserElementPList;
	protected function addElementPToSysUser($elementP, $userId){
		if(!isset($this->sysUserElementPList)){
			$this->sysUserElementPList = array();
		}
		if(!isset($this->sysUserElementPList[$userId])){
			$this->sysUserElementPList[$userId] = ElementPListArrayImpl::createInstance();
		}
		$this->sysUserElementPList[$userId]->addElementP($elementP);
	}
	public function getSysUserElementPList($userId){
		if(!isset($this->sysUserElementPList)){
			return null;
		}
		return $this->sysUserElementPList[$userId];
	}
	private $getNoneSysUserElementPListCache;
	public function getNoneSysUserElementPList($userId){
		if(!isset($this->getNoneSysUserElementPList)) $this->getNoneSysUserElementPList = array();
		if($this->getNoneSysUserElementPList[$userId]) return $this->getNoneSysUserElementPList[$userId];
		 
		if(!isset($this->sysUserElementPList) || $this->sysUserElementPList[$userId]==null){
			$returnValue = $this;
		} else {
			//build the diff between the current list and the one created by the user
			$returnValue = ElementPListArrayImpl::createInstance(array_diff_key($this->getListIterator(), $this->sysUserElementPList[$userId]->getListIterator()));
		}
		$this->getNoneSysUserElementPList[$userId] = $returnValue;
		return $returnValue;
	}
	private $sysCreationUserElementPList;
	protected function addElementPToSysCreationUser($elementP, $userId){
		if(!isset($this->sysCreationUserElementPList)){
			$this->sysCreationUserElementPList = array();
		}
		if(!isset($this->sysCreationUserElementPList[$userId])){
			$this->sysCreationUserElementPList[$userId] = ElementPListArrayImpl::createInstance();
		}
		$this->sysCreationUserElementPList[$userId]->addElementP($elementP);
	}
	public function getSysCreationUserElementPList($userId){
		if(!isset($this->sysCreationUserElementPList)){
			return null;
		}
		return $this->sysCreationUserElementPList[$userId];
	}
	private $getNoneSysCreationUserElementPListCache;
	public function getNoneSysCreationUserElementPList($userId){
		if(!isset($this->getNoneSysCreationUserElementPList)) $this->getNoneSysCreationUserElementPList = array();
		if($this->getNoneSysCreationUserElementPList[$userId]) return $this->getNoneSysCreationUserElementPList[$userId];
		 
		if(!isset($this->sysCreationUserElementPList) || $this->sysCreationUserElementPList[$userId]==null){
			$returnValue = $this;
		} else {
			//build the diff between the current list and the one created by the user
			$returnValue = ElementPListArrayImpl::createInstance(array_diff_key($this->getListIterator(), $this->sysCreationUserElementPList[$userId]->getListIterator()));
		}
		$this->getNoneSysCreationUserElementPList[$userId] = $returnValue;
		return $returnValue;
	}

	public function reset($specificAttributName=null, $fieldSeleclectorList=null, $filterOnWritable=false)
	{
		parent::reset();
		$this->elementIds = null;
		$this->sysUserElementIds = null;
		$this->sysCreationUserElementIds = null;
		$this->sysCreationUserElementPList = null;
		$this->sysUserElementPList = null;
		$this->getNoneSysCreationUserElementPList = null;
		$this->getNoneSysUserElementPList = null;
		
		$this->setAllHaveReadRights(true);
		$this->setAllHaveWriteRights(true);
		$this->setAllHaveAdminRights(true);
		$this->setFilterOnWritable($filterOnWritable);
		if($specificAttributName!=null){
			$this->specificAttributFS = FieldSelector::createElementAttributeSelector($specificAttributName);
		} else {
			$this->specificAttributFS = null;
		}
		$this->setFieldSelectorList($fieldSeleclectorList);
		$this->setAllHaveSpecificAttribut(true);
		$this->setAtLeastOneHasSpecificAttribut(false);
	}

	/**
	 * @param specificAttributeName allows to know directly if all elements in the list has this specific attribute setted to true
	 * @param fieldSelectorList, allows to store in this ElementPList a fieldSelector to know what are the original information filled
	 */
	public static function createInstance($specificAttributName=null, $fieldSeleclectorList=null, $filterOnWritable=false)
	{
		$returnValue = new self();
		$returnValue->reset($specificAttributName, $fieldSeleclectorList, $filterOnWritable);
		return $returnValue;
	}
	public function addElementP($elementP){
		if(!isset($elementP)) throw new ServiceException("elementP cannot be null", ServiceException::INVALID_ARGUMENT);
		if($elementP->getElement()==null) throw new ServiceException("elementP->element cannot be null", ServiceException::INVALID_ARGUMENT);
		
		if($this->isFilterOnWritable()){
			if($elementP->getRights()==null || !$elementP->getRights()->canWriteElement()){
				return;
			}
		}
		
		$id = $elementP->getElement()->getId();
		$this->objArray[$id] = $elementP;
		
		$this->addElementId($id);
		$this->addElementPToSysUser($elementP, $elementP->getElement()->getSys_user());
		$this->addElementPToSysCreationUser($elementP, $elementP->getElement()->getSys_creationUser());

		if($this->getSpecificAttributFieldSelector()!=null) {
			$val = $elementP->getElement()->getAttribute($this->getSpecificAttributFieldSelector());
			if($val == false) $this->setAllHaveSpecificAttribut(false);
			else $this->setAtLeastOneHasSpecificAttribut(true);
		}			
		if($elementP->getRights()==null){
			$this->setAllHaveReadRights(false);
			$this->setAllHaveWriteRights(false);
			$this->setAllHaveAdminRights(false);
		} elseif(!$elementP->getRights()->canWriteElement()){
			$this->setAllHaveWriteRights(false);
		}
		if($elementP->getRights()!=null && !$elementP->getRights()->canModify()){
			$this->setAllHaveAdminRights(false);
		}
	}

	public function getElementP($elementId){
		if(!isset($this->objArray)) return null;
		return $this->objArray[$elementId];
	}

	public function createFieldList(){
//		return FormFieldList::createInstance($this->createWigiiBag());
		return FieldListArrayImpl::createInstance();
	}
	public function createWigiiBag(){
//		return FormBag::createInstance();
		return WigiiBagBaseImpl::createInstance();
	}
}


