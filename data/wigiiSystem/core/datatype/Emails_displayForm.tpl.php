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

$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width:".($parentWidth-5)."px; ";
}
//
//$subFieldName = "value";
$value = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName));

//$fieldXml["autoFillWithPrincipalEmail"]=="1" is treated in FormRenderer ActOnField

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$isFilled = $isFilled || $this->getRecord()->getWigiiBag()->isFilled($fieldName);
$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($this->getP());
$isRequire = 	$fieldXml["require"]=="1" && !$isPublicPrincipal ||
				$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
				$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0";
$isNotExpanded = !$isFilled && $fieldXml["expand"]!="1" && (!$isRequire || $fieldXml["expand"]=="0");
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
$isNoAutofill = $fieldXml["noAutofill"]=="1" || ($this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "noAutofill")=='1') && $fieldXml["noAutofill"]!="0";


$inputId = $formId.'_'.$fieldName;
$subFieldName = "value";
//value
if($fieldXml["isMultiple"]=="1"){
	$inputNode = "textarea";
	$inputType = null;
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;

	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	if($inputType != null) $this->put(' type="'.$inputType.'" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled ');
	$this->put(' class="noElastic');
	if($readonly) $this->put(' removeDisableOnSubmit ');
	if($isNoAutofill) $this->put('autocomplete="off"');
	$this->put(' " ');
	$this->put(' style="'.$valueWidth." height:80px;");
	if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
	$this->put('" >');
	$this->put($value);
	$this->put('</'.$inputNode.'>');
} else {
	$inputNode = "input";
	$inputType = "text";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;

	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	if($inputType != null) $this->put(' type="'.$inputType.'" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
	if($isNoAutofill) $this->put('autocomplete="off"');
	$this->put(' style="'.$valueWidth);
	if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
	$this->put('" value="');
	$this->put($value);
	$this->put('" />');
}



