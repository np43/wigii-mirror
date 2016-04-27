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
$dtXml = $field->getDataType()->getXml();

//defining width if existant
if($parentWidth != null && $labelWidth != null){
	$valueWidth = " width:".($parentWidth-$labelWidth-5)."px; ";
	$labelWidth = " width:".($labelWidth)."px; ";
}

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$isFilled = $this->getRecord()->getWigiiBag()->isFilled($fieldName);
$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($this->getP());
$isRequire = 	$fieldXml["require"]=="1" && !$isPublicPrincipal ||
				$isPublicPrincipal && $fieldXml["requireInPublic"]=="1" ||
				$isPublicPrincipal && $fieldXml["require"]=="1" && $fieldXml["requireInPublic"]!="0";
$isNotExpanded = !$isFilled && $fieldXml["expand"]!="1" && (!$isRequire || $fieldXml["expand"]=="0");
$inputId = $formId.'_'.$fieldName;

//street
$subFieldName = "street";
$inputNode = "textarea";
$inputType = null;
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
$this->displayForm_1_TillClassDefinition();
//if($fieldXml["htmlArea"]=="1") $this->put('htmlArea ');
//if($fieldXml["activateHtmlArea"]=="1") $this->put('activateHtmlArea ');
$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
$this->displayForm_3b_CloseStyleBeginValueAsNode();
$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
$this->displayForm_4b_CloseValueAsNode($inputNode);

//zip_code
$subFieldName = "zip_code";
$inputNode = "input";
$inputType = "text";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
$this->displayForm_1_TillClassDefinition();
$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
$this->displayForm_4a_CloseValueAsAttribute();

//city
$subFieldName = "city";
$inputNode = "input";
$inputType = "text";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;
$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
$this->displayForm_1_TillClassDefinition();
$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
$this->displayForm_4a_CloseValueAsAttribute();

//state
if ($fieldXml["noState"] !== null && $fieldXml["noState"] != "1"){
	$subFieldName = "state";
	$inputNode = "input";
	$inputType = "text";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;
	$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

	$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
	$this->displayForm_1_TillClassDefinition();
	$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
	$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->displayForm_4a_CloseValueAsAttribute();
}

//country
if ($fieldXml["noCountry"] !== null && $fieldXml["noCountry"] != "1"){
	$subFieldName = "country";
	$inputNode = "input";
	$inputType = "text";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;
	$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

	$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, $valueWidth, $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
	$this->displayForm_1_TillClassDefinition();
	$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
	$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->displayForm_4a_CloseValueAsAttribute();
}








