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
 * Created on 12 October 2011 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class FiltersSelectSearchBarFieldList implements FieldList {

	private $wigiiNamespace;
	private $module;
	private $listContext;
	private $filtersRec;

	private $wigiiExecutor;
	protected function setWigiiExecutor($var){$this->wigiiExecutor = $var; }
	protected function getWigiiExecutor(){return $this->wigiiExecutor; }

	public static function createInstance($wigiiExecutor, $wigiiNamespace, $module, $filtersRec, $listContext=MANDATORY_ARG){
		$ssFl = new self();
		$ssFl->wigiiNamespace = $wigiiNamespace;
		$ssFl->module = $module;
		$ssFl->listContext = $listContext;
		$ssFl->filtersRec = $filtersRec;
		$ssFl->setWigiiExecutor($wigiiExecutor);
		return $ssFl;
	}
	protected function getWigiiNamespace(){ return $this->wigiiNamespace; }
	protected function getModule(){ return $this->module; }
	protected function getListContext(){ return $this->listContext; }
	protected function getFiltersRec(){ return $this->filtersRec; }

	/**
	 * Adds a field to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field in the list
	 */
	protected $fields;
	protected $nbFields = 0;

	public function addField($field){
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //Serviceprovider::getConfigService();

		$fieldName = $field->getFieldName();
		$fieldXml = $field->getXml();

		if($this->fields[$fieldName] != null) throw new ListException($fieldName." already exist", ListException::ALREADY_EXISTS);
		$this->field[$fieldName] = 1;

		$filtersRec = $this->getFiltersRec();
		try{
			$filterAttrI = $filtersRec->getFieldList()->getField("filters_Attributs".$this->nbFields);
		} catch (ElementServiceException $ese){
			if($ese->getCode() == ElementServiceException::NO_CORRESPONDANT_FIELD){
				return; //no more select field available, ignore the others
			}
			throw $ese;
		}

		$isDbValueSet = null;
		$attribute = null;
		$attrNb = 0;
		if($fieldXml["type"] == "groupFilter"){
			$fieldName = $fieldXml->getName();
			$label = $transS->t($p, $fieldXml->getName(), $fieldXml);
			$attribute = '';
			$groupLogExpList = array();
			foreach($fieldXml->attribute as $option){
				if((string)$option == "none") continue;
				$attrNb++;
				$groupLogExpList[(string)$option] = (string)$option["groupLogExp"];
				if($option["checked"] == '1') {
					$checked = ' checked="1"';
				}
				else $checked = '';
				$attribute .= '<attribute'.$checked.'>'.(string)$option.'<label>'.$transS->t($p, (string)$option, $option).'</label></attribute>';
			}
			$this->getListContext()->addSelectGroupFilterField($fieldName, $groupLogExpList);
		} else if($fieldXml["type"] == "groupByFieldListAndElementInfo"){
			return; //Deprecated
		} else {
			$elAttributName = (string)$fieldXml["elementAttribut"];
			$elFieldName = (string)$fieldXml["field"];
			$elSubFieldName = (string)$fieldXml["subField"];
			$isDbValueSet = ($fieldXml["displayDBValue"] == '1');			
			$fieldName = $fieldXml->getName();
			if($elFieldName){
				$elField = $configS->mf($p, $this->getModule())->xpath("$elFieldName");
				if($elField === false) throw new ServiceException("Field: $elFieldName for filter select search in: $fieldName, does not exists in elements field ".$this->getModule()->getModuleUrl(), ServiceException::INVALID_ARGUMENT);
				$elField = $elField[0];
				if(	$elField["type"]!="Attributs" &&
					$elField["type"]!="Booleans" &&
					$elField["type"]!="MultipleAttributs" &&
					!($elField["type"]=="Files" && $elSubFieldName=="date") &&
					!($elField["type"]=="Emails" && $elSubFieldName=="externalAccessEndDate") &&
					$elField["type"]!="TimeRanges" &&
					$elField["type"]!="Dates"
					){
						throw new ServiceException("Wrong field: $elAttributName $elFieldName $subFieldName in SelectSearch activity (field: $fieldName). Only Attributs, MultipleAttributs, Dates, TimeRanges, Booleans, Files->date, Emails->externalAccessEndDate, Elements->sys_date, Elements->sys_creationDate are allowed.", ServiceException::INVALID_ARGUMENT);
					}
			} else if($elAttributName=="sys_date" || $elAttributName=="sys_creationDate"){
				//element attribute

			} else {
				throw new ServiceException("Wrong field: $elAttributName $elFieldName $subFieldName in SelectSearch activity (field: $fieldName). Only Attributs, MultipleAttributs, Dates, TimeRanges, Booleans, Files->date, Emails->externalAccessEndDate, Elements->sys_date, Elements->sys_creationDate are allowed.", ServiceException::INVALID_ARGUMENT);
			}

			if(count($fieldXml->children()) > 0){
				$label = $transS->t($p, $fieldXml->getName(), $fieldXml);
			} else {
				if($elAttributName){
					$label = $transS->t($p, $elAttributName, $fieldXml);
				} else {
					$label = $transS->t($p, $elFieldName, $elField);
					if($elSubFieldName) $label .= " ".$transS->t($p, $elField["type"]."_".$elSubFieldName);
				}
			}
			
			if($elField["type"]=="Attributs" || $elField["type"]=="MultipleAttributs"){
				$attribute = '';
				//add the element fields
				//$html2text = new Html2text();
				foreach($elField->attribute as $option){
					if((string)$option == "none") continue;
					$attrNb++;
					if(!$isDbValueSet){
						$attrLabel = $transS->t($p, (string)$option, $option);
					}else{
						$attrLabel = $option;
					}
					/* Medair (CWE) 13.11.2017, keep HTML in labels, but disables help icons js code 
					$html2text->setHtml($attrLabel);
					$attrLabel = $html2text->getText();					
					$attribute .= '<attribute>'.(string)$option.'<label>'.str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $attrLabel).'</label></attribute>';					 
					*/
					$attribute .= '<attribute>'.(string)$option.'<label>'.$attrLabel.'</label></attribute>';
				}
				//unset($html2text);
				$this->reverseFields[$fieldName] = $label;
				$this->getListContext()->addSelectSearchField($fieldName, $elFieldName);
			} else if($elField["type"]=="Booleans"){
				$this->getListContext()->addSelectSearchField($fieldName, "!BO!.".$elField["type"].".".$elFieldName.".".$elSubFieldName.".".$elAttributName);
			} else {
				$this->getListContext()->addSelectSearchField($fieldName, "!TR!.".$elField["type"].".".$elFieldName.".".$elSubFieldName.".".$elAttributName);
			}
		}

		$this->nbFields++;
		$filtersRec->getFieldList()->renameField($filterAttrI->getFieldName(), $fieldName);

		if($fieldXml["type"] == "groupFilter" || $elField["type"]=="Attributs" || $elField["type"]=="MultipleAttributs"){
			// activate chosen drop downs in advanced search
			if((string)$elField["useCheckboxes"]=="1" || (string)$elField["useRadioButtons"]=="1"){
				// handle checkboxes as is 
				$size = ($elField["useMultipleColumn"] < 3 ? $elField["useMultipleColumn"] : '3');
				$filterAttrI->setXml(simplexml_load_string('<'.$filterAttrI->getFieldName().' type="MultipleAttributs" expand="1" useCheckboxes="1" isInLine="1" chosen="1" useMultipleColumn="'.$size.'"><label>'.$label.'</label>' . $attribute . '</'.$filterAttrI->getFieldName().'>'));
			} else {
				// activate chosen drop downs in advanced search
				$size='chosen="1"';
				$filterAttrI->setXml(simplexml_load_string('<'.$filterAttrI->getFieldName().' type="MultipleAttributs" expand="1" '.$size.'><label>'.$label.'</label>' . $attribute . '</'.$filterAttrI->getFieldName().'>'));	
			}
			// in the case of checkboxes we want them also in the search bar
		} else if($elField["type"]=="Booleans"){
			$filterAttrI->setDataType($configS->getDataType("Attributs"));
			$filterAttrI->setXml(simplexml_load_string('<'.$filterAttrI->getFieldName().' type="Attributs" useRadioButtons="1" useMultipleColumn="2" expand="1" ><label>'.$label.'</label><attribute>none</attribute><attribute>true<label>'.$transS->t($p, "checked").'</label></attribute><attribute>false<label>'.$transS->t($p, "unchecked").'</label></attribute></'.$filterAttrI->getFieldName().'>'));
		} else {
			//case of date filters
			//change the dataType to Strings
			$filterAttrI->setDataType($configS->getDataType("TimeRanges"));
			$filterAttrI->setXml(simplexml_load_string('<'.$filterAttrI->getFieldName().' type="TimeRanges" onlyDate="1" expand="1" ><label>'.$label.'</label></'.$filterAttrI->getFieldName().'>'));
		}
		$filtersRec->getWigiiBag()->setHidden(false, $filterAttrI->getFieldName());

	}

	private $reverseFields = array();
	public function addReverseAttribute(){
		if($this->reverseFields == null) return;
		$filtersRec = $this->getFiltersRec();
		$filterRevAttr = $filtersRec->getFieldList()->getField(ListContext::ReverseSelectSearchField);
		$attribute = null;
		foreach($this->reverseFields as $fieldName=>$label){
			$attribute .= '<attribute>'.$fieldName.'<label>'.$label.'</label></attribute>';
		}
		$filterRevAttr->setXml(simplexml_load_string('<'.$filterRevAttr->getFieldName().' type="MultipleAttributs" expand="1" useCheckboxes="1" isInLine="1" useMultipleColumn="2">' . $attribute . '</'.$filterRevAttr->getFieldName().'>'));
		$filtersRec->getWigiiBag()->setHidden(false, ListContext::ReverseSelectSearchField);
	}

	public function getField($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function doesFieldExist($fieldName){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function getListIterator(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
}


