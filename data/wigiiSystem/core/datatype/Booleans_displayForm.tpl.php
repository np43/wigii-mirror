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

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$fieldXml = $field->getXml();

//value
$subFieldName = "value";
$inputNode = "input";
$inputType = "checkbox";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;

$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
if($disabled || $readonly) $this->put(' disabled ');
if($readonly) $this->put(' class="removeDisableOnSubmit" ');

if($this->getRecord()->getFieldValue($fieldName, $subFieldName)){
	$this->put(' checked="on" ');
}

$this->put(' style="width:17px;height:17px;vertical-align:bottom;');
if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome

$this->put('" />');

if($fieldXml["persistCookie"]=="1" && $_POST["action"]==null && $this->getRecord()->getId()==0){
	//on ajoute le code pour persister la dernière valeur tapée et pour la préremplir si
	//le champ est vide.
	$this->getExecutionService()->addJsCode("
myVal = $.cookie('cookie_form_value_".$inputId."');
if(myVal != ''){ if(myVal == 'true' || myVal=='on'){ myVal = true;}	else { myVal = false; } $('#".$inputId."').get(0).checked = myVal; }
$('#".$inputId."').click(function(){ $.cookie('cookie_form_value_".$inputId."', this.checked, { path: '/', expires: 365, secure: ".strtolower(put(HTTPS_ON))." }); }); ");
}


