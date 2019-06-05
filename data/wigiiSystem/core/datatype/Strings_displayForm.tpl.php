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
 * Created on 4 déc. 09
 * by LWR
 */

if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
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
$isNotExpanded = !$isFilled && $fieldXml["expand"]!="1" && (!$isRequire || $fieldXml["expand"]=="0");
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();
$isNoAutofill = $fieldXml["noAutofill"]=="1" || ($this->getConfigService()->getParameter($this->getP(), $exec->getCrtModule(), "noAutofill")=='1') && $fieldXml["noAutofill"]!="0";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName;
$subFieldName = "value";


// CWE 10.10.2018 displays string as a drop-down
if($fieldXml['dropDown']=="1") {
	$inputNode = "select";
	$inputType = null;
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;
	
	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	if($inputType != null) $this->put(' type="'.$inputType.'" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled ');
	$this->put('class="');
	if($readonly) $this->put('removeDisableOnSubmit ');
	if((string)$fieldXml["allowNewValues"]=="1") $this->put('allowNewValues ');
	$this->put('flex displayDBValue ');
	$this->put('"');
	$this->put(' style="'.$valueWidth);
	if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
	$this->put('"');
	
	$this->put('>');
	
	$val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName));
	$labelForTitle = $transS->t($p, $val);
	$label = $labelForTitle;	
	$label = str_replace(" ", "&nbsp;", $label);
	$this->put('<option value="" ></option>');
	if(!empty($val)) $this->put('<option selected="selected" value="'.$val.'" title="'.$labelForTitle.'" >'.$label.'</option>');
	
	$this->put('</'.$inputNode.'>');
}

// else standard value display
else {
	
$inputNode = "input";
if($fieldXml["secret"]=="1") $inputType = "password";
else $inputType = "text";
$inputName = $fieldName.'_'.$subFieldName;
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);

$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($fieldXml["colorPicker"]=="1") $this->put(' class="colorPickerInput" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
if($disabled) $this->put(' disabled ');
if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
if($isNoAutofill) $this->put('autocomplete="off"');
$this->put(' style="'.$valueWidth);
if($readonly) $this->put('background-color:#E3E3E3;color:#666'); //disabled make color as white in Google Chrome
$this->put('" value="');
//do not purify when displaying in form, but keep htmlspecialchars
$this->put($val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName)));
$this->put('" />');

// CWE 05.06.2019 adds a show password button
if($fieldXml["secret"]=="1" && $fieldXml["showPassword"]=="1") {
    $labelForTitle=$transS->t($p, 'showPasswordBtn_help');
    $this->put('<span class="showPasswordBtn" style="display:none;cursor:pointer;font-size:24px;font-weight:bold;padding:2px;" title="'.$labelForTitle.'" data-wigii-for="'.$inputId.'">&#128065;</span>');
}
// CWE 05.06.2019 adds a generate password button
if($fieldXml["secret"]=="1" && $fieldXml["genPassword"]=="1") {
    $labelForTitle=$transS->t($p, 'genPasswordBtn_help');
    $this->put('<span class="genPasswordBtn" style="cursor:pointer;font-size:18px;font-weight:bold;padding:2px;" title="'.$labelForTitle.'" data-wigii-for="'.$inputId.'">&#128272;</span>');
}

if($fieldXml["persistCookie"]=="1" && $_POST["action"]==null && $this->getRecord()->getId()==0){
	//on ajoute le code pour persister la dernière valeur tapée et pour la préremplir si
	//le champ est vide.
	$this->getExecutionService()->addJsCode("
if($('#".$inputId."').val()==''){
myVal = $.cookie('cookie_form_value_".$inputId."');
if(myVal != ''){ $('#".$inputId."').val(myVal); }
}
$('#".$inputId."').bind('keydown blur', function(){ $.cookie('cookie_form_value_".$inputId."', $(this).val(), { path: '/', expires: 365, secure: ".strtolower(put(HTTPS_ON))." }); }); ");
}

}