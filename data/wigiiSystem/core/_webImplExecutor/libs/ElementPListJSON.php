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

/*
 * Created on 6 oct. 09
 * by LWR
 */

class ElementPListJSON implements ElementPList {
	private $elementIds; //array which stores the elements ids already added
	
	public static function createInstance(){
		$elPl = new self();
		return $elPl;
	}
	
	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator(){
		throw new ServiceException("".ServiceException::NOT_IMPLEMENTED);
	}
	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty(){
		throw new ServiceException("".ServiceException::NOT_IMPLEMENTED);
	}
	/**
	 * Returns the number of items in the list
	 */
	public function count(){
		throw new ServiceException("".ServiceException::NOT_IMPLEMENTED);
	}
	/**
	 * Creates a new empty FieldList
	 * the fieldList depends on a wigiiBag, so we create already the wigiiBag
	 * if necessary
	 */
	public function createFieldList(){
		return FormFieldList::createInstance($this->createWigiiBag());
		//throw new ServiceException("".ServiceException::NOT_IMPLEMENTED);
	}
	/**
	 * Creates a the wigii bag if not already created for this list
	 */
	private $wigiiBag;
	public function createWigiiBag(){
		if(!isset($this->wigiiBag)){
			$this->wigiiBag = FormBag::createInstance(); 
		}
		return $this->wigiiBag;
		//throw new ServiceException("".ServiceException::NOT_IMPLEMENTED);		
	}
	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
	private $first = true;
	public function addElementP($elementP){
		
		$element = $elementP->getElement();
		$elId = $element->getId();
		if($this->elementIds[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementIds[$elId] = $elId;
		
		if($this->first){
			$this->first = false;
		} else {
			echo ",
";
		}
		
		echo '{';
		//display an element in the list:
		echo "id:'".$elId."',cell:[";
		$firstCell = true;
		foreach($element->getFieldList()->getListIterator() as $field){
			if($field->getDataType() == null) continue;
			
			$value = $element->getFieldValue($field->getFieldName());
			if(is_array($value)) echo implode(", ", $value);
			
			if($firstCell){
				$firstCell = false;
			} else {
				echo ",";
			}
			echo "'";
			echo $value;
			echo "'";
		}
		//new line
		echo ']}';
		flush();
	}
}


