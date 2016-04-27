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
 * created by LWR, on 07 nov 2012
 */

class ElementPListArrayImpl extends ObjectListArrayImpl implements ElementPList {

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

	public function reset()
	{
		parent::reset();
		$this->elementIds = null;
	}

	/**
	 * @param $objArray if set prefill the content of this list with the current objectArray. WARNING this object array MUST be in the same format as if adding standard ElementP
	 */
	public static function createInstance($objArray=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->objArray = $objArray;
		if($objArray) $returnValue->elementIds = array_combine(array_keys($objArray),array_keys($objArray));
		else $returnValue->elementIds = null;
		return $returnValue;
	}
	public function addElementP($elementP){
		if(!isset($elementP)) throw new ServiceException("elementP cannot be null", ServiceException::INVALID_ARGUMENT);
		if($elementP->getElement()==null) throw new ServiceException("elementP->element cannot be null", ServiceException::INVALID_ARGUMENT);
		
		$id = $elementP->getElement()->getId();
		$this->objArray[$id] = $elementP;
		$this->addElementId($id);
	}

	public function getElementP($elementId){
		if(!isset($this->objArray)) return null;
		return $this->objArray[$elementId];
	}
	
	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
	}
}





