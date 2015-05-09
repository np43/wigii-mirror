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

/*
 * Created on 6 oct. 09
 * by LWR
 * modified on 8 April 2012 by Benjamin Girardet
 * - add the sx = true and make the xml generation through a simpleXml object
 */


class SimpleXMLExtended extends SimpleXMLElement // http://coffeerings.posterous.com/php-simplexml-and-cdata
{
  public function addCData($cdata_text)
  {
    $node= dom_import_simplexml($this); 
    $no = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}

class ElementPListExportXml extends ElementPListWebImplWithWigiiExecutor implements ElementDataTypeSubfieldVisitor {

	private $sx = true;
	
	private $module;
	public function setModule($var){ $this->module = $var; }
	protected function getModule(){ return $this->module; }
	
	private $crtRequest;
	public function setCrtRequest($url){ $this->crtRequest = $url; }
	protected function getCrtRequest(){ return $this->crtRequest; }
	
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
		$noFormat = false; //if define at true in this switch, then the value will not be formated with the TRM
		switch($dataType->getDataTypeName()){
			case "Files":
				switch($subFieldName){
					case "mime":
					case "path":
					case "user":
					case "username":  $noFormat = true;
					break;
					case "content":
					case "thumbnail": return; //no content in xmlFeed
				}
				break;
			case "TimeRanges":
				switch($subFieldName){
					case "isAllDay": $noFormat = true;
				}
				break;
			case "Urls":
				switch($subFieldName){
					case "url": $noFormat = true;
				}
				break;
			case "Numerics":
			case "Floats":
			case "Booleans":
				$noFormat = true;
				break;
			case "Emails":
				switch($subFieldName){
					case "proofKey":
					case "proof": return;
					case "proofStatus":
					case "externalAccessLevel":
					case "externalAccessEndDate":
					case "externalCode":
						$noFormat = true;
						break;
				}
				break;
		}
		//export the SysInfo subfields
		switch($subFieldName){
			case "sys_user":
			case "sys_username":
			case "sys_date":
			case "sys_creationUser":
			case "sys_creationUsername":
			case "sys_creationDate":
				$noFormat = true;
		}
		$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
		//for file path, make it a complete url to download:
		if($dataType->getDataTypeName() == "Files" && $subFieldName=="path" && $value){
			$value = SITE_ROOT.$this->getCrtRequest()."/download/".$this->crtElement->getId()."/".$field->getFieldName();
		}
		
		if($lang!=null) $value = $value[$lang];
		if(!$noFormat){
			$value = $this->getTrm()->formatValue($field->getFieldName(), $subFieldName, $value, $field);
		}
		$label = $field->getFieldName();
		$label = $this->getTrm()->t($field->getFieldName(), $field->getXml());
		if($subFieldName != "value") $label .= " ".$this->getTrm()->t($dataType->getDataTypeName()."_".$subFieldName);
		if($lang != null) $label .= " (".(ServiceProvider::getTranslationService()->getInstalledLanguage($lang)).")";
		$this->addCell($subFieldName.($lang? "_".$lang:""), $label, $value);
		
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
	private $trm_ = null;
	private function getTrm(){
		if(!isset($this->trm_)){
			$this->trm_ = TemplateRecordManager::createInstance();
		}
		return $this->trm_;
	}
	private function setElementToTrm($element){
		$trm_ = $this->getTrm();
		$trm_->reset($element);
	}
	public function addElementP($elementP){
		
		$elS = ServiceProvider::getElementService();
		
		$element = $elementP->getElement();
		$this->crtElement = $element;
		$this->setElementToTrm($element);
		$elId = $element->getId();
		if($this->elementPs[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementPs[$elId] = $elementP;
		
		$this->beginElement($elId);
		
		$this->firstCell = true;
		foreach($element->getFieldList()->getListIterator() as $field){
			//we take only dataTypes in export. There is no sense exporting the freetext
			if($field->getDataType()==null) continue; 
			//perform operation on each subField:
			$this->beginField($field->getFieldName(), $field->getDataType()->getDataTypeName(), $this->getTrm()->t($field->getFieldName(), $field->getXml()));
			$this->xml_recursiveLevel += 1;
			$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			$this->xml_recursiveLevel -= 1;
			$this->endField();
		}
		
		
		$this->endElement();
	}
	
	protected function beginField($name, $type, $label){
		if($this->sx){
			
			$this->xml_currentField = $this->xml_currentFields->addChild('field');
			$this->xml_currentField->addAttribute('name', $name);
			$this->xml_currentField->addAttribute('type', $type);
			$this->xml_currentField->addAttribute('label', $this->prepareLabel($label));
			return;
		}
		echo '<field name="'.$name.'" type="'.$type.'" label="'.$this->prepareLabel($label).'">';
	}
	protected function endField(){
		if($this->sx){
		
			return;
		}
		echo '</field>';
	}
	
	protected function beginElement($elId){
		if($this->sx){
			$this->xml_currentItem = $this->xml->addChild('item');
			foreach($this->getElementAttributeFsl()->getListIterator() as $fs){
				$this->addCell($fs->getSubFieldName(), $this->getTrm()->t($fs->getSubFieldName()), $this->crtElement->getAttribute($fs));
			}
			$this->xml_currentFields = $this->xml_currentItem->addChild('fields');
			return;
		}
		echo '<item>';
		foreach($this->getElementAttributeFsl()->getListIterator() as $fs){
			$this->addCell($fs->getSubFieldName(), $this->getTrm()->t($fs->getSubFieldName()), $this->crtElement->getAttribute($fs));
		}
		echo '<fields>';
		flush();
	}
	
	protected function endElement(){
		if($this->sx){
		
			return;
		}
		echo '</fields>';
		echo '</item>';
	}
	
	protected function addCell($subFieldName, $label, $value){
		if($this->sx){
			if($this->xml_recursiveLevel == 2){
				$currentElement = &$this->xml_currentField;
			} else {
				$currentElement = &$this->xml_currentItem;
			}
			$this->xml_currentSubField = $currentElement->addChild($subFieldName);
			if($label != null) {
				$this->xml_currentSubField->addAttribute('label', $this->prepareLabel($label));
			}
			if($value)
				$this->xml_currentSubField->addCData($value);
			return;
		}
		//$value = formatToString($value);
		echo '<'.$subFieldName;
		if($label != null){
			echo ' label="'.$this->prepareLabel($label).'"';
		}
		echo '>';
		
		echo '<![CDATA['.$value.']]>';
		echo '</'.$subFieldName.'>';
	}
	
	public function actOnBeforeAddElementP(){
		if($this->sx){
			$root = '<'.$this->getModule()->getModuleName().'></'.$this->getModule()->getModuleName().'>';
			$this->xml = new SimpleXMLExtended($root);
			$this->xml_recursiveLevel = 1;
			return;
		}
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo "\n";
		echo '<'.$this->getModule()->getModuleName().'>';
	}
	
	public function actOnFinishAddElementP($numberOfObjects){
		if($this->sx){
			echo $this->xml->asXML();
			return;
		}
		echo '</'.$this->getModule()->getModuleName().'>';
	}
	
	protected function prepareLabel($label){
		$label = str_replace('"', "'", preg_replace("(<.*>)", "", preg_replace("(<br\s*/>)", " ", $label)));
		return $label;
	}
	
}


