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
 * FormRenderer implementation used by LightClientFormExecutor
 * Created by Medair (CWE,LMA) on 16.12.2016
 */
class LightFormRenderer extends FormRenderer {
	
	// Dependency injection
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightFormRenderer");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightFormRenderer");
		}
		return $this->_executionSink;
	}
	

	// Object lifecycle
	
	
	/**
	 * Creates and initializes a new instance of LightFormRenderer
	 * @param LightClientFormExecutor $formExecutor
	 * @param String $formId
	 * @param LightClientTRM $templateRecordManager
	 * @return LightFormRenderer
	 */
	public static function createInstance($formExecutor, $formId, $templateRecordManager){
		$returnValue = new self();
		$returnValue->setFormExecutor($formExecutor);
		$returnValue->setFormId($formId);
		$returnValue->setTemplateRecordManager($templateRecordManager);
		return $returnValue;
	}

		
	// FieldListVisitor implementation
	
	
	public function actOnField($field, $dataType){

		// CONTEXT
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = $this->getP();
		$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);

		$fieldXml = $field->getXml();
		$fieldName = $field->getFieldName();
		$dataTypeName = ($dataType!=null?$dataType->getDataTypeName():null);
		$rm = $this->getTemplateRecordManager();

		//if field is hidden, or onlyInDetail, or not in Public and principal is public -> skip it
		if(($isPublicPrincipal && $fieldXml["notInPublic"]=="1")) return;
		if((!$isPublicPrincipal && $fieldXml["onlyInPublic"]=="1")) return;
		if($rm->getRecord()->getWigiiBag()->isHidden($fieldName) || $fieldXml["onlyInDetail"]=="1") return;

		//BASE FIELD ATTRIBUTES
		//	- $idField
		//	- $error
		//	- $help
		//	- $fieldClass //if a custom class has been defined for this field
		//	- $isRequire
		//	- $isFilled
		//	- $isCollapse
		$idField = $this->getFormId()."__".$fieldName;
		$fieldClass = (string)$fieldXml["class"];
		$error = $rm->getRecord()->getWigiiBag()->getError($fieldName);
		$help = str_replace('"', '&quot;', $transS->t($p, $rm->getRecord()->getWigiiBag()->getHelp($fieldName)));
		$isRequire = (
			$fieldXml["require"]=="1" && !$isPublicPrincipal ||
			$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
			$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0"
		);

		if($fieldXml["readonlyInPublic"]=="1" && $isPublicPrincipal){
			$rm->getRecord()->getWigiiBag()->setReadonly(true, $fieldName);
		}
		$isFilled = null;
		$isCollapse = null;
		if($dataType!=null){
			$isFilled = $rm->getRecord()->getWigiiBag()->isFilled($fieldName);
			$isCollapse =
				$dataTypeName!="Files" &&
				$dataTypeName!="Links" &&
				$dataTypeName!="Booleans" &&
				!$isFilled &&
				$fieldXml["isJournal"]!="1" &&
				$fieldXml["displayOnRightSide"]!="1" &&
				$fieldXml["expand"]!="1" &&
				(!$isRequire || $fieldXml["expand"]=="0");
		}

		
		//FIELD GROUP MANAGEMENT
		
		// enters new group
		if($fieldXml["groupStart"]=="1") {
			$this->enterFieldGroup($rm, $fieldXml, $fieldName, $idField);
			if(!empty($error)){
				$rm->put('<div class="fieldError">'.$error.'</div>');
			}
			return;
			// leaves new group
		} else if($fieldXml["groupEnd"]=="1") {
			$crtFieldGroupId = $this->getFormId()."__".$this->getCrtFieldGroup();
			$this->leaveCrtFieldGroup($rm, $fieldXml);
			return;
		}
		
				
		// FIELD DIV

		// computes help tag
		if(!empty($help)){
			// filters html tags in help text
			$html2text = new Html2text();
			$html2text->setHtml($help);
			$help = $html2text->getText();
			$help = str_replace("\\'", "'", str_replace('"', "'", $help));
			unset($html2text);
			$help = ' title="'.$help.'" ';
		}
		// computes inline style for field	
		$style = "";
		
		$rm->put('<div id="'.$idField.'" class="field '.$fieldClass.'" style="'.$style.'" '.$help.($dataType!=null?' data-wigii-datatype="'.$dataTypeName.'"':'').' >');

		// FIELD INFO PART
		if($dataType!=null){
			$additionalInformations = $rm->getAdditionalinInformation($fieldName);
			if($additionalInformations) $rm->put('<div class="addinfo">'.$additionalInformations.'</div>');
		}
		
		// FIELD ERROR PART		
		if(!empty($error)){
			$rm->put('<div class="fieldError">'.$error.'</div>');
		}

		
		// LABEL PART
		
		
		//display label
		if($dataType!=null && $fieldXml["noLabel"]!="1"){			
			// computes inline style for label
			$style = "";
			
			$rm->put('<div class="label" style="'.$style.'" >');
			//add red cross on error
			if(!empty($error)){
				$rm->put('<img class="icon" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" /> ');
			}
			//add * on require
			if($isRequire){
				$rm->put('* ');
			}
			// TO DO : refactor the rendering of the label to be Responsive Design
			$rm->displayLabel($fieldName, $labelWidth, $this->getVisibleLanguage(), false);			
			$rm->put('</div>');			
		}

		
		// VALUE PART
		
		// computes inline style for value
		$style = "";
		
		// computes class for value
		if($dataType != null) {
			$class = "value";
		} else {
			//for freetext, use the class value only if isLabel!=1
			if($fieldXml["displayAsLabel"]=="1"){
				$class = "label";
			} else {
				$class = "value";
			}
		}
		
		// displays field form
		$rm->put('<div class="'.$class.'" style="'.$style.'" >');		
		// TO DO : refactor the rendering of the form to be Responsive Design
		if(!($dataTypeName=='Files' || $dataTypeName=='Links')) $rm->displayForm($this->getFormId(), $fieldName, $valueWidth, $this->getLabelWidth(), $this->getVisibleLanguage());
		$rm->put('</div>');

		
		
		// DYNAMIC JS PART
		
		// adds any dynamically generated hidden divs
		if((string)$fieldXml["divExp"]!=null) $this->resolveDivExp((string)$fieldXml["divExp"]);
		if((string)$fieldXml["divInFormExp"]!=null) $this->resolveDivExp((string)$fieldXml["divInFormExp"]);
		
		//add any JsCode if defined:
		if((string)$fieldXml["jsCode"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCode"]));
		}
		if((string)$fieldXml["jsCodeInForm"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCodeInForm"]));
		}
		//add any dynamically generated JsCode if defined:
		if((string)$fieldXml["jsCodeExp"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCodeExp"]));
		}
		if((string)$fieldXml["jsCodeInFormExp"]!=null){
			$this->addJsCodeAfterShow(str_replace('$$idForm$$', $this->getFormId(), (string)$fieldXml["jsCodeInFormExp"]));
		}

		//END FIELD DIV
		$rm->put('</div>');
	}
	
	
	public function displayFormErrors() {
		// CONTEXT
		$transS = ServiceProvider::getTranslationService();
		$exec = ServiceProvider::getExecutionService();
		$p = $this->getP();
		$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($p);
		
		$rm = $this->getTemplateRecordManager();
		
		if(!$rm->getRecord()->getWigiiBag()->hasErrors()) return;
		
		// Render FORM ERRORS DIV
		$rm->put('<div class="fieldError" style="border-top-style:none;border-bottom-style:dotted;font-weight:bold;padding-bottom:10px;margin-bottom:5px;">');
		if($rm->isForExternalAccess()){
			$rm->put($transS->t($p, "errorSummary").":<br />");
			foreach($rm->getRecord()->getWigiiBag()->getErrors() as $tempFieldName=>$tempError){
				if($tempFieldName=="captcha_code"){
					$rm->put($transS->t($p, "captchaInvalidCode")."<br />");
				} else {
					$tempField = $rm->getRecord()->getFieldList()->getField($tempFieldName);
					$rm->put($transS->t($p, $tempFieldName, $tempField->getXml()).": ".$tempError."<br />");
				}
			}
		} else {
			$rm->put('<br />'.$transS->t($p, "formHasErrors").' <img style="float:none;" src="'.SITE_ROOT_forFileUrl.'images/icones/tango/22x22/emblems/emblem-unreadable.png" class="icon" />');
		}
		$rm->put('</div><div class="clear"></div>');		
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


