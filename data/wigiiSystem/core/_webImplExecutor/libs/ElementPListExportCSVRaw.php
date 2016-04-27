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

/*
 * Created on 6 oct. 09
 * by LWR
 */

class ElementPListExportCSVRaw extends ElementPListWebImplWithWigiiExecutor implements ElementDataTypeSubfieldVisitor {

	private $separator;
	public function setSeparator($separator){ $this->separator=$separator; }
	protected function getSeparator(){
		if(!isset($this->separator)){
			throw new ServiceException("No separator defined in ElementPListCSV", ServiceException::INVALID_ARGUMENT);
		}
		return $this->separator;
	}
	private $encoding;
	public function setEncoding($encoding) {$this->encoding = $encoding; }
	protected function getEncoding(){
		if(!isset($this->encoding)){
			$this->encoding = "UTF-8";
		}
		return $this->encoding;
	}

	private $ignoreSysFields = false;
	public function ignoreSysFields($var){
		$this->ignoreSysFields = $var;
	}
	protected function isSysFieldsIgnored(){
		return $this->ignoreSysFields;
	}

	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$elPl->setListContext($listContext);
		$elPl->setWigiiExecutor($wigiiExecutor);
		return $elPl;
	}

	/**
	 * Adds an element to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same element in the list
	 */
//	private $elementIds; //array which stores the elements ids already added
//	public function getElementIds(){ return $this->elementIds; }
	private $elementPs;
	public function getListIterator(){
		return $this->elementPs;
	}
	public function isEmpty(){
		return $this->elementPs==null;
	}
	public function count(){
		if($this->elementPs == null) return null;
		return count($this->elementPs);
	}


	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang=null){
		$fieldSelectorList = $this->getListContext()->getFieldSelectorList();
		//if a field selector is defined, then check that this field and subfield is in
		if($fieldSelectorList != null){
			if(	!$fieldSelectorList->containsFieldSelector($field->getFieldName(), $subFieldName) &&
				!$fieldSelectorList->containsField($field->getFieldName())) return;
		}
		//do not export the SysInfo subfields
		if($this->isSysFieldsIgnored()){
			switch($subFieldName){
				case "sys_user":
				case "sys_username":
				case "sys_date":
				case "sys_creationUser":
				case "sys_creationUsername":
				case "sys_creationDate":
					return;
			}
		}
		//are we constructing the headers?
		if($this->first){
			$label = $field->getFieldName();
			if($subFieldName != "value") $label .= " ".$subFieldName;
			if($lang != null) $label .= " ".$lang;
			$this->addCell($label, $this->firstCell);
		} else {
			//if not, then add the value
			$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
			if($lang!=null) $value = $value[$lang];
			$this->addCell($value, $this->firstCell);
		}
		if($this->firstCell) $this->firstCell = false;
	}
	private $first = true;
	private $firstCell = true;
	private $crtElement = null;
	private $elementAttributeFsl = null;
	private function getElementAttributeFsl(){
		if(!isset($this->elementAttributeFsl)){
			$this->elementAttributeFsl = ServiceProvider::getElementService()->getFieldSelectorListForElementAttributForImport();
			ServiceProvider::getElementService()->fillFieldSelectorListForElementSignatureAttribute($this->elementAttributeFsl);
		}
		return $this->elementAttributeFsl;
	}
	public function addElementP($elementP){

		$elS = ServiceProvider::getElementService();

		$element = $elementP->getElement();
		$this->crtElement = $element;
		$elId = $element->getId();
		if($this->elementPs[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementPs[$elId] = $elementP;

		//le premier élément que l'on ajoute, on créé les headers
		if($this->first){
			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
			$transS = ServiceProvider::getTranslationService();

			$this->firstCell = true;
			foreach($element->getFieldList()->getListIterator() as $field){
				//we take only dataTypes in export. There is no sense exporting the freetext
				if($field->getDataType()==null) continue;
				//perform operation on each subField:
				$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			}
			foreach($this->getElementAttributeFsl()->getListIterator() as $fs){
				$this->addCell($fs->getFieldName()." ".$fs->getSubFieldName(), $this->firstCell);
			}
			$this->first = false;
		}


		$this->beginElement($elId);

		$this->firstCell = true;
		foreach($element->getFieldList()->getListIterator() as $field){
			//we take only dataTypes in export. There is no sense exporting the freetext
			if($field->getDataType()==null) continue;
			//perform operation on each subField:
			$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
		}
		foreach($this->getElementAttributeFsl()->getListIterator() as $fs){
			$this->addCell($this->crtElement->getAttribute($fs), $this->firstCell);
		}

		$this->endElement();
	}

	protected function beginElement($elId){
		echo "\n";
		flush();
	}

	protected function endElement(){

	}

	protected function addCell($value, $firstCell){
		if(!$firstCell) echo $this->getSeparator();

		$value = formatToString($value);
		$value = str_replace('"', '""', str_replace('&nbsp;', ' ', str_replace("\n", '\\\\n', str_replace("\r", "", $value))));
		if($this->getEncoding()=="UTF-8"){
			echo '"'.$value.'"';
		} else {
			echo '"'.utf8_decode($value).'"';
		}
	}

	public function actOnBeforeAddElementP(){
		//nothing to do, because we need an element to have the fieldList
	}

	public function actOnFinishAddElementP($numberOfObjects){
		//nothing to do
	}

}


