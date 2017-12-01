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
 * Created on 4 déc. 09
 * by LWR
 */

$exec = $this->getExecutionService();
$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width: 100%; max-width:".($parentWidth)."px; ";
}

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$isFilled = $this->getRecord()->getWigiiBag()->isFilled($fieldName);
$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($this->getP());
$isRequire = 	$fieldXml["require"]=="1" && !$isPublicPrincipal ||
				$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
				$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0";
$isNotExpanded = !$isFilled && $fieldXml["isJournal"]!="1" && $fieldXml["expand"]!="1" && $fieldXml["displayOnRightSide"]!="1"  && (!$isRequire || $fieldXml["expand"]=="0");

$subFieldName = "value";
$inputNode = "textarea";
$inputType = null;
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;

if($fieldXml["isJournal"]=="1"){
	$readonly = true; //if isJournal then we force people to click on add to add a comment
	//readonly and not disabled as we need to removeDisableOnSubmit
}

//value

$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
$this->put(' class="');
if($isNotExpanded) $this->put('collapsed ');
if($readonly) $this->put(' removeDisableOnSubmit ');
if($fieldXml["noWrap"]=="1") $this->put('noWrap ');
if($fieldXml["htmlArea"]=="1") $this->put('htmlArea ');
if($fieldXml["fullToolbarHtmlArea"]=="1") $this->put('fullToolbarHtmlArea ');
if($fieldXml["mediumToolbarHtmlArea"]=="1") $this->put('mediumToolbarHtmlArea ');
if($fieldXml["activateHtmlArea"]=="1") $this->put('activateHtmlArea ');
if((string)$fieldXml["wordlimit"]>0) $this->put('wordlimit wordlimit_'.(string)$fieldXml["wordlimit"].' ');
if((string)$fieldXml["isJournal"]=="1") $this->put('isJournal ');
if((string)$fieldXml["height"]!=null && (string)$fieldXml["height"] != "0") $this->put('difH ');
$this->put('" ');
if($disabled) $this->put(' disabled="disabled" ');
if($readonly) $this->put(' disabled="disabled" ');
if($fieldXml["noWrap"]=="1") $this->put(' wrap="off" ');
$this->put(' style="'.$valueWidth. '');
if((string)$fieldXml["height"]!=null && (string)$fieldXml["height"] != "0") $this->put(' height:'.$fieldXml["height"].'px; ');
if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
$this->put('" >');
//$this->put(str_replace('<', '&lt;', str_replace('>', '&gt;', $this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()))));
//$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
//do not purify when displaying in form
$this->put($this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName)));
$this->put('</'.$inputNode.'>');

if($fieldXml["template"]!=null && $_POST["action"]==null && ($this->getRecord()->getId()==0 || $fieldXml["loadIfNoValue"]=="1" && !$this->getRecord()->getWigiiBag()->isFilled($fieldName))){
//	list($templateNamespace, $templateName) = explode(":", $fieldXml["template"]);
//	if($templateName==null){
//		$templateName = $templateNamespace;
//		$templateNamespace = "default";
//	} else {
//		$ckTemplateFile = CLIENT_WEB_PATH.$templateNamespace."_CKTemplates.js";
//		$exec->addJsCode("CKEDITOR.loadTemplates(['$ckTemplateFile'], function(){ });");
//	}
	$templateNamespace = "default";
	$exec->addJsCode("t = CKEDITOR.getTemplates('$templateNamespace').templates; for(i in t){ if(t[i].title == '".$fieldXml["template"]."'){ $('#".$inputId."').val(t[i].html); } } ");
}

/******************
 * on the right view
 ******************/
if($fieldXml["displayOnRightSide"]=="1" && !$this->isForNotification() && $this->getState()!="addMessageToNotification"){
	$fieldId = $this->getFormRenderer()->getFormId()."__".$fieldName;
	$this->getFormRenderer()->addJsCodeAfterShow("actOnDisplayOnRightSide('".$exec->getIdAnswer()."', '$fieldId', ".max(($fieldXml["htmlArea"]=="1" ? 300 : 200), min(400, $this->getFormRenderer()->getValueWidth())).", ".($this->getFormRenderer()->getValueWidth()+$this->getFormRenderer()->getLabelWidth()+50).");");
}

