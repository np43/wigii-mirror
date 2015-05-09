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
 * Created on 4 déc. 09
 * by LWR
 */

if(!isset($transS)) $transS = ServiceProvider::getTranslationService();
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();

$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	if($fieldXml["flex"]=="1"){
		$valueWidth = " width:".($parentWidth-6)."px; ";  //select we don't need to make it smaller
	} else {
		$valueWidth = " width:".($parentWidth-1)."px; ";  //select we don't need to make it smaller
	}
}

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);

// getting prefixFilter if defined
$prefixFilter = (string)$fieldXml['prefixFilter'];
$filterDropDown = !empty($prefixFilter);

$subFieldName = "value";

if((string)$fieldXml["useRadioButtons"]=="1" || (string)$fieldXml["useCheckboxes"]=="1"){

	//define the options:
	$useMultipleColumn = (int)(string)$fieldXml["useMultipleColumn"];
	$inputNode = "input";
	if((string)$fieldXml["useRadioButtons"]=="1") $inputType = "radio";
	else $inputType ="checkbox";

	$inputName = $fieldName.'_'.$subFieldName.'';
	$val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName));

	//if none attribute exist then add code to uncheck existing values if click on it
	$allowUnchek = false;
	if($fieldXml->xpath("attribute[text()='none']")){
		$allowUnchek = true;
	}
	foreach($fieldXml->attribute as $attribute_key => $attribute){
		if($attribute == "none") continue;

		// filters dropdown using prefix filter
		if($filterDropDown && strpos((string)$attribute, $prefixFilter)!==0) continue;
		
		//the radioButton is before the text of the option
		//the width of the checkbox is valueWidth / useMultipleColumn if defined
		if($useMultipleColumn>0){
			$this->put('<div style="float:left; width:'.(($parentWidth-5)/$useMultipleColumn).'px;" >');
		}
		$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.str_replace(" ", "_", (string)$attribute).'_'.($inputType==null?$inputNode:$inputType);

		$label = $this->getRecord()->getRedirectedFieldLabel($this->getP(), $fieldName, $attribute);

		$tempDisabled = $disabled;
		if(!$label && $label!=="0"){
			$label = $transS->t($p, (string)$attribute, $attribute);
			$tempDisabled = true;
		}

		//add the checkbox
		$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" '.($attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' ');
		if($inputType != null) $this->put(' type="'.$inputType.'" ');
		$this->put(' value="'.(string)$attribute.'" ');
		if($tempDisabled) $this->put(' disabled ');
		if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');

		if(($val != null && (string)$attribute==$val)) $this->put(' checked="on" ');
		$this->put(' style="');
		if($fieldXml["displayAsTag"]=="1") $this->put(' float:left; '); //the label will be float left, so the input should be as well
		if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
		$this->put('" />');
		//add the label
		if($attribute["noLabel"]!="1"){
			if($fieldXml["displayAsTag"]=="1"){
				$label = $this->doFormatForTag($label, $fieldXml);
			}
			$this->put('<label style="padding-left:5px;" for="'.$inputId.'" >'.$label.'</label>');
		}
		if($useMultipleColumn>0){
			$this->put('</div>');
		} else {
			$this->put('<br>'); //next line for the next label and radiobutton
		}
	}

	if((string)$fieldXml["useCheckboxes"]=="1"){
		$exec->addJsCode("
$('div#".$formId."__".$fieldName." .value input:checkbox').click(function() {
    $('div#".$formId."__".$fieldName." .value input:checkbox').attr('checked', false);" .
    ($allowUnchek
    		? "if($(this).val()==radioButtonClick_$inputId){ $(this).attr('checked', false); radioButtonClick_$inputId = ''; } else { $(this).attr('checked', true); radioButtonClick_$inputId = $(this).val(); }"
    		: "$(this).attr('checked', true);"
    		)."
});
");
	}
	if($allowUnchek){
		$exec->addJsCode("
radioButtonClick_$inputId = '';
$('div#".$formId."__".$fieldName." .value').mouseover(function(e) { radioButtonClick_$inputId = $('input[name=$inputName]:checked').attr('value'); });
".((string)$fieldXml["useCheckboxes"]=="1" ? "" : "$('input[name=$inputName]').click(function(e) { if($(this).val()==radioButtonClick_$inputId){ $(this).attr('checked', false); radioButtonClick_$inputId = ''; } else { radioButtonClick_$inputId = $(this).val(); } }); ")."
");
	}

} else {

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
if((string)$fieldXml["chosen"]=="1") $this->put('chosen ');
if((string)$fieldXml["flex"]=="1") $this->put('flex ');
if((string)$fieldXml["allowNewValues"]=="1") $this->put('allowNewValues ');
$this->put('"');
$this->put(' style="'.$valueWidth);
if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
$this->put('" >');

$val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName));
$valExistsInOption = false;
//define the options:
foreach($fieldXml->attribute as $attribute_key => $attribute){
	if($attribute == "none" && (string)$fieldXml["flex"]=="1"){
		$this->put('<option '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' value="" title="" ></option>');
		continue;
	}
	
	// filters dropdown using prefix filter
	if($filterDropDown && $attribute != "none" && strpos((string)$attribute, $prefixFilter)!==0) continue;
	
	if(!$valExistsInOption) $valExistsInOption = $val == (string)$attribute;
	if(($val == (string)$attribute)){
		$selected = ' selected="selected" ';
	} else {
		$selected = "";
	}

	$label = $this->getRecord()->getRedirectedFieldLabel($this->getP(), $fieldName, $attribute);
	$tempDisabled = false;
	if(!$label && $label!=="0"){
		$label = $transS->t($p, (string)$attribute, $attribute);
		$tempDisabled = true;
	}

	if($attribute["optGroupStart"]=="1"){
		$this->put('<optgroup '.($tempDisabled || $attribute["disabled"]=="1" ? 'disabled="on"' : "").' label="'.$label.'" >');
	} else if($attribute["optGroupEnd"]=="1"){
		$this->put('</optgroup>');
	} else {
		//a disable option cannot be selected
		//limit attribute options to 64 chars
		$this->put('<option '.($tempDisabled || $attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.$selected.' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' value="'.(string)$attribute.'" title="'.$label.'" >'.(strlen($label)>64 ? str_replace(" ", "&nbsp;", substr($label, 0, 61))."..." : str_replace(" ", "&nbsp;", $label)).'</option>');
	}
}
if($fieldXml["allowNewValues"]=="1" && !$valExistsInOption){
	$this->put('<option selected="selected" value="'.$val.'" title="'.$transS->t($p, $val).'" >'.(strlen($transS->t($p, $val))>64 ? str_replace(" ", "&nbsp;", substr($transS->t($p, $val), 0, 61))."..." : str_replace(" ", "&nbsp;", $transS->t($p, $val))).'</option>');
}
$this->put('</'.$inputNode.'>');

}
