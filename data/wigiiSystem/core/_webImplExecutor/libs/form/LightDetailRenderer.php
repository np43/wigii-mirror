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
 * DetailRenderer implementation used by LightClientFormExecutor
 * Created by Medair (CWE,LMA) on 16.12.2016
 */
class LightDetailRenderer extends DetailRenderer {
	
	// Dependency injection
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightDetailRenderer");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightDetailRenderer");
		}
		return $this->_executionSink;
	}
	
	
	// Object lifecycle
	
	
	/**
	 * Creates and initializes a new instance of LightDetailRenderer
	 * @param String $detailId
	 * @param LightClientTRM $templateRecordManager
	 * @return LightDetailRenderer
	 */
	public static function createInstance($detailId, $templateRecordManager) {
		$returnValue = new self();
		$returnValue->setTemplateRecordManager($templateRecordManager);
		$returnValue->setDetailId($detailId);
		return $returnValue;
	}

	// FieldListVisitor implementation	

	public function actOnField($field, $dataType){
		// CONTEXT
		$fieldXml = $field->getXml();
		$fieldName = $field->getFieldName();
		$dataTypeName = ($dataType!=null?$dataType->getDataTypeName():null);
		$rm = $this->getTemplateRecordManager();
		$p = $this->getP();
		if($p!=null){
			$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);
		} else {
			$isPublicPrincipal = false;
		}

		//if field is hidden, or onlyInForm, or onlyInWrite with a none writable record, or not in Public and principal is public -> skip it
		if(($isPublicPrincipal && $fieldXml["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldXml["onlyInPublic"]=="1")) return;
		if($rm->getRecord()->getWigiiBag()->isHidden($fieldName) || $fieldXml["onlyInForm"]=="1") return;
		if($fieldXml["onlyInWrite"]=="1" && $this->getRecordIsWritable()===false) return;

		//if no value then do not display, except:
		//if proof status is Deleted -> show the email field
		if($dataTypeName=="Emails" && $rm->getRecord()->getFieldValue($field->getFieldName(), "proofStatus")==Emails::PROOF_STATUS_DELETED){
			//display the delete sign with unsubscribe info
		//do an exception for Blobs with isJournal to make the +add button available
		} else if($dataTypeName=="Blobs" && $fieldXml["isJournal"]=="1"){
			//display the label
		//always display Links if record is writable
		} else if($dataTypeName=="Links"){
			//display the label
		//always display field if displayEvenIfEmpty
		} else if($fieldXml["displayEvenIfEmpty"]=="1"){
			//display the label
		//prevent empty booleans to be displayed
		} else if($dataTypeName=="Booleans" && $rm->getRecord()->getFieldValue($field->getFieldName(), "value")==false){
			return;
		} else if($dataType!= null && !$rm->getRecord()->getWigiiBag()->isFilled($field->getFieldName())){
			return;
		}

		//BASE FIELD ATTRIBUTES
		$idField = $this->getDetailId()."__".$fieldName;
		$fieldClass = (string)$fieldXml["class"];
		if($fieldXml["allowOnReadOnly"]=="1") $fieldClass .=" allowOnReadOnly ";
		if($rm->getRecord() instanceof Element && $rm->getRecord()->isState_blocked()) {
			$fieldClass .= " readOnly ";
		}
		$isTitle = ($fieldXml["displayAsTitle1"]=="1" || $fieldXml["displayAsTitle2"]=="1" || $fieldXml["displayAsTitle3"]=="1");
		if($isTitle && $dataType!= null){
			if($dataTypeName == "Files" || $dataTypeName == "Urls" || $dataTypeName == "Addresses" || $dataTypeName == "Blobs" || $dataTypeName == "Texts"){
				$isTitle = false;
			}
		}

		
		
		//FIELD GROUP MANAGEMENT
		
		// enters new group
		if($fieldXml["groupStart"]=="1") {
			$this->enterFieldGroup($rm, $fieldXml, $fieldName, $idField);
			return;
		// leaves new group
		} else if($fieldXml["groupEnd"]=="1") {
			$crtFieldGroupId = $this->getDetailId()."__".$this->getCrtFieldGroup();
			$this->leaveCrtFieldGroup($rm, $fieldXml);
			return;
		}
		
		
		
		// FIELD DIV
		

		// computes inline style for field
		$style = "";
		
		
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" '.($dataType!=null?'data-wigii-datatype="'.$dataTypeName.'"':'').' >');
		
		// FIELD INFO PART
		if($dataType!=null){
			$additionalInformations = $rm->getAdditionalInformation($fieldName);
			if($additionalInformations) $rm->put('<div class="addinfo">'.$additionalInformations.'</div>');
		}

		
		// LABEL PART
		
		
		//display label
		if($dataType!=null && $fieldXml["noLabel"]!="1"){			
			$rm->put('<div class="label" style="'.$style.'" >');
			// TO DO : refactor the rendering of the label to be Responsive Design
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());			
			$rm->put('</div>');
		}

		
		
		// VALUE PART
		
		
		// computes inline style for value
		$style = "";
		
		// computes class for value
		$class = "";
		if($dataType!=null){
			if($dataTypeName == "Blobs" ||
				$dataTypeName == "Texts"){
				$class .= " text ";
			}
			if($dataTypeName == "Files"){
				$class .= " file ";
			}
			$class = "value ".$class;
		} else {
			//for freetext, use the class value only if isLabel!=1
			if($fieldXml["displayAsLabel"]=="1"){
				$class = "label";
			} else {
				$class = "value";
			}
		}

		// display field value
		$rm->put('<div class="'.$class.'" style="'.$style.'" >');		
		// TO DO : refactor the rendering of the label to be Responsive Design
		$rm->displayValue($fieldName, $valueWidth, $this->getVisibleLanguage());		
		$rm->put('</div>');
		

		// DYNAMIC JS PART
		
		
		// adds any dynamically generated hidden divs
		if((string)$fieldXml["divExp"]!=null) $this->resolveDivExp((string)$fieldXml["divExp"]);
		if((string)$fieldXml["divInDetailExp"]!=null) $this->resolveDivExp((string)$fieldXml["divInDetailExp"]);
		
		//add any JsCode if defined:
		if((string)$fieldXml["jsCode"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCode"]));
		}
		if((string)$fieldXml["jsCodeInDetail"]!=null){
			$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetail"]);
			if($dataType && ($dataTypeName=="Attributs")) {
				$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
			}
			$this->addJsCodeAfterShow($jsCode);
		}
		//add any dynamically generated JsCode if defined:
		if((string)$fieldXml["jsCodeExp"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeExp"]));
		}
		if((string)$fieldXml["jsCodeInDetailExp"]!=null){
			$jsCode = str_replace('$$idForm$$', $this->getDetailId(), (string)$fieldXml["jsCodeInDetailExp"]);
			if($dataType && ($dataTypeName=="Attributs")) {
				$jsCode = str_replace('$$attrValue$$', $rm->getRecord()->getFieldValue($field->getFieldName()), $jsCode);
			}
			$this->addJsCodeAfterShow($jsCode);
		}
		
		//END FIELD DIV
		$rm->put('</div>');
	}
	
	public function finish(){
		$this->setRecordIsWritable(null);
		$this->freeFuncExpEval();
	}
	
	
	// GROUP RENDERING
	
	protected function enterFieldGroup($rm, $fieldXml, $fieldName, $idField){
		// computes class for group
		$fieldClass = (string)$fieldXml["class"];
		
		// computes inline style for group
		$style = '';

		//open fieldGroup div			
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" >');
	
		//display label if necessary
		if($fieldXml["noLabel"]!="1"){
			// computes inline style for label
			$style = '';
			
			$rm->put('<div class="label" style="'.$style.'" >');
			if($fieldXml["displayAsTitle"]=="1") $rm->put('<h4>');
			// TO DO : refactor the rendering of the label to be Responsive Design
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage());
			if($fieldXml["displayAsTitle"]=="1") $rm->put('</h4>');
			$rm->put('</div>');
		}
	
		//create the group container
		$rm->put('<div id="'.$idField.'_group" ');
		$rm->put('class="value fieldGroup'.($fieldXml["noFieldset"] =="1" ? ' noFieldset ' : '').'" ');
		// computes inline style for group container
		$style = '';
		$rm->put(' style="'.$style.'">');
	}
	
	protected function leaveCrtFieldGroup($rm, $fieldXml){
		$rm->put('</div></div>'); //close div.value, div.field
	}
}


