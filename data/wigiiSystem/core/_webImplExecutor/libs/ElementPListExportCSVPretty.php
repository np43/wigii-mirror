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
 * Created on 6 oct. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

class ElementPListExportCSVPretty extends ElementPListWebImplWithWigiiExecutor implements ElementDataTypeSubfieldVisitor {
	
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
	
	public static function createInstance($wigiiExecutor, $listContext){
		$elPl = new self();
		$transS = ServiceProvider::getTranslationService();
		$configS = $wigiiExecutor->getConfigurationContext();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		$exec = ServiceProvider::getExecutionService();
		$fsl = FieldSelectorListForActivity::createInstance();
		$fsl->setSelectedLanguages(array($transS->getLanguage()=>$transS->getLanguage()));
//		$configS->getFields($p, $exec->getCrtModule(), Activity::createInstance("excelExport"), $fsl);
		$fieldList = FormFieldList::createInstance(null);
		$configS->getGroupsFields($p, $listContext->getConfigGroupList(), null, $fieldList);
		foreach($fieldList->getListIterator() as $field){
			if($field->getDataType()==null) continue;
			$fieldXml = $field->getXml();
			if((string)$fieldXml["excelExport"]=="none") continue;
			if(!$fsl->containsField($field->getFieldName())) $fsl->addFieldSelector($field->getFieldName());
			if($field->isCalculated() && $field->shouldCalculateOnFetch()){
				$field->getFuncExpDependencies($fsl);
			}
		}
		$listContext->setFieldSelectorList($fsl);
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
		$transS = ServiceProvider::getTranslationService();
		$fieldSelectorList = $this->getListContext()->getFieldSelectorList();
		//if a field selector is defined, then check that this field and subfield is in
		if($fieldSelectorList != null){
			if(	!$fieldSelectorList->containsFieldSelector($field->getFieldName(), $subFieldName) &&
				!$fieldSelectorList->containsField($field->getFieldName())) return;
		}
		//bypassing none usefull information:
		$noFormat = false; //if define at true in this switch, then the value will not be formated with the TRM
		switch($dataType->getDataTypeName()){
			case "Files":
				switch($subFieldName){
					case "mime":
					case "path":
					case "user":
					case "username":
					case "content":
					case "textContent":
					case "thumbnail": return;
				}
				break;
			case "TimeRanges":
				switch($subFieldName){
					case "isAllDay": $noFormat = true;
				}
				break;
			case "Urls":
				switch($subFieldName){
					case "target": return;
					case "url": $noFormat = true;
				}
				break;
			case "Floats":
			case "Numerics":
			case "Booleans":
				$noFormat = true;
				break;
			case "Emails":
				switch($subFieldName){
					case "proofKey":
					case "proof":
					case "externalConfigGroup":
					case "externalAccessLevel": //since 19 march 2012 we are no more exporting in csv the external access. this is too complex for normal user. To find this info use the export csv raw
					case "proofStatus":
					case "externalAccessEndDate":
					case "externalCode": return;
					case "value": $noFormat = true; break;
				}
				break;
		}
		//do not export the SysInfo subfields
		switch($subFieldName){
			case "sys_user":
			case "sys_username":
			case "sys_date":
			case "sys_creationUser":
			case "sys_creationUsername":
			case "sys_creationDate":
				return;
		}
		//are we constructing the headers?
		if($this->first){
			$label = $field->getFieldName();
			$label = $this->getTrm()->t($field->getFieldName(), $field->getXml());
			if($subFieldName != "value") $label .= " ".$this->getTrm()->t($dataType->getDataTypeName()."_".$subFieldName);
			if($lang != null) $label .= " (".(ServiceProvider::getTranslationService()->getVisibleLanguage($lang)).")";
			$this->addCell($label, $this->firstCell);
		} else {
			//if not, then add the value
			$value = $this->crtElement->getFieldValue($field->getFieldName(), $subFieldName);
			if($lang!=null) $value = $value[$lang];
			if($noFormat){
				$this->addCell($value, $this->firstCell);
			} else {
				$this->addCell($this->getTrm()->formatValue($field->getFieldName(), $subFieldName, $value, $field), $this->firstCell);
			}
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
		}
		return $this->elementAttributeFsl;
	}
	private $trm_ = null;
	private function getTrm(){
		if(!isset($this->trm_)){
			$this->trm_ = TemplateRecordManager::createInstance();
			$this->trm_->setWorkzoneViewDocked($this->isWorkzoneViewDocked());
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
		
		//le premier élément que l'on ajoute, on créé les headers
		if($this->first){
			$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
			$transS = ServiceProvider::getTranslationService();
			
			$this->firstCell = true;
			$this->addCell("ID", $this->firstCell);
			if($this->firstCell) $this->firstCell = false;
			$this->addCell($transS->t($p, "sys_date"), $this->firstCell);
			foreach($element->getFieldList()->getListIterator() as $field){
				//we take only dataTypes in export. There is no sense exporting the freetext
				if($field->getDataType()==null) continue; 
				$xml = $field->getXml();
				if((string)$xml["excelExport"]=="none") continue;
				
				//perform operation on each subField:
				$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
			}
			$this->first = false;
		}
		
		
		$this->beginElement($elId);
		
		$this->firstCell = true;
		$this->addCell($this->crtElement->getId(), $this->firstCell);
		if($this->firstCell) $this->firstCell = false;
		$this->addCell($this->getTrm()->doFormatForDate($this->crtElement->getSys_date(), false, false, true), $this->firstCell);
		foreach($element->getFieldList()->getListIterator() as $field){
			//we take only dataTypes in export. There is no sense exporting the freetext
			if($field->getDataType()==null) continue;
			$xml = $field->getXml();
			if((string)$xml["excelExport"]=="none") continue;
				
			//perform operation on each subField:
			$elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
		}
		
		$this->endElement();
	}
	
	protected function beginElement($elId){
		echo "\n";
		flush();
	}
	
	protected function endElement(){
		
	}
	
	private $html2text;
	protected function addCell($value, $firstCell){
		if(!$firstCell) echo $this->getSeparator();
		
		if(!isset($this->html2text)) $this->html2text = new Html2text();
		$this->html2text->setHtml($value);
		$value = htmlspecialchars_decode($this->html2text->get_text(), ENT_QUOTES);
// 		$this->html2text->clear();
		
//		$value = formatToString($value);
		$value = str_replace('"', '', str_replace("\n", " ", str_replace("\r", "", $value)));
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


