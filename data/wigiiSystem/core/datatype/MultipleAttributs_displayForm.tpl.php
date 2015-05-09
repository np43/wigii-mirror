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

$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width:".($parentWidth-1)."px; "; //select we don't need to make it smaller
}

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);

// getting prefixFilter if defined
$prefixFilter = (string)$fieldXml['prefixFilter'];
$filterDropDown = !empty($prefixFilter);

$subFieldName = "value";

if((string)$fieldXml["useCheckboxes"]=="1"){

	//define the options:
	$useMultipleColumn = (int)(string)$fieldXml["useMultipleColumn"];
	$inputNode = "input";
	$inputType = "checkbox";
	$inputName = $fieldName.'_'.$subFieldName.'[]';
	$val = $this->getRecord()->getFieldValue($fieldName, $subFieldName);
//	eput($val);
	foreach($fieldXml->attribute as $attribute_key => $attribute){

		// filters dropdown using prefix filter
		if($filterDropDown && $attribute != "none" && strpos((string)$attribute, $prefixFilter)!==0) continue;
		
		//the checkbox is before the text of the option
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
		if(($val != null && array_search((string)$attribute, $val) !== false)) $this->put(' checked="on" ');
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
			$this->put('<br>'); //next line for the next label and checkbox
		}
	}

} else {
	$inputNode = "select";
	$inputType = null;
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName.'[]';

	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	if($inputType != null) $this->put(' type="'.$inputType.'" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled ');
	$this->put('class="');
	if($readonly) $this->put('removeDisableOnSubmit ');
	if((string)$fieldXml["chosen"]=="1") $this->put('chosen ');
	if((string)$fieldXml["allowNewValues"]=="1") $this->put('allowNewValues ');
	$this->put('"');
	if((string)$fieldXml["size"]!="") $this->put(' size="'.(string)$fieldXml["size"].'" ');
	elseif((string)$fieldXml["searchable"]!="1") $this->put(' size="5" ');
	$this->put(' multiple="multiple" ');
	$this->put(' style="'.$valueWidth);
	if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
	$this->put('" >');

	$val = $this->getRecord()->getFieldValue($fieldName, $subFieldName);

	//define the options:
	foreach($fieldXml->attribute as $attribute_key => $attribute){
		// filters dropdown using prefix filter
		if($filterDropDown && $attribute != "none" && strpos((string)$attribute, $prefixFilter)!==0) continue;
		
		if(($val != null && array_search((string)$attribute, $val) !== false)) $selected = ' selected="selected" ';
		else $selected = "";
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
			//limit attribute options to 120 chars
			$this->put('<option '.($tempDisabled || $attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' value="'.(string)$attribute.'" '.$selected.' title="'.$label.'" >'.(strlen($label)>64 ? str_replace(" ", "&nbsp;", substr($label, 0, 61))."..." : str_replace(" ", "&nbsp;", $label)).'</option>');
		}
	}

	$this->put('</'.$inputNode.'>');

	//ici il ne faut pas metre la condition if disable car s'il est disable il peut très bien se faire
	//enable par un autre checkbox. Donc il faut que le code js soit présent...
	if(!$readonly) {
		//$this->getExecutionService()->addJsCode("multipleSelectVals['".$inputId."'] = $('#".$inputId."').val(); $('#".$inputId."').click(function(){ multipleSelectOnClick(this.id); });");
	}
}

