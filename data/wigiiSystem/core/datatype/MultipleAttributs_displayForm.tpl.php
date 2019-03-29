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
 * Created on 4 déc. 09 by LWR
 * Modified by CWE on 25.02.2016 to display a deprecated message to user if an old value is present in field and does not exist in the drop down anymore.
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 * Modified by Medair (CWE) on 09.02.2018 to support ajax drop-downs
 */
if(!isset($transS)) $transS = ServiceProvider::getTranslationService();

$fieldXml = $field->getXml();

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width: 100%; max-width:".($parentWidth)."px; "; //select we don't need to make it smaller
}

//defining readOnly or disabled
$readonly = $this->getRecord()->getWigiiBag()->isReadonly($fieldName);
$disabled = $this->getRecord()->getWigiiBag()->isDisabled($fieldName);
$isPublicPrincipal = ServiceProvider::getAuthorizationService()->isPublicPrincipal($this->getP());

// getting prefixFilter if defined
$prefixFilter = (string)$fieldXml['prefixFilter'];
$regexFilter = (string)$fieldXml['regexFilter'];
$filterDropDown = !empty($prefixFilter) || !empty($regexFilter);


$subFieldName = "value";

if((string)$fieldXml["useCheckboxes"]=="1"){

	//define the options:
	$useMultipleColumn = (int)(string)$fieldXml["useMultipleColumn"];
	$maxSelection = (int)(string)$fieldXml["maxSelection"];
	$inputNode = "input";
	$inputType = "checkbox";
	$inputName = $fieldName.'_'.$subFieldName.'[]';
	$val = $this->getRecord()->getFieldValue($fieldName, $subFieldName);
//	eput($val);
	$firstLoop = true;
	foreach($fieldXml->attribute as $attribute_key => $attribute){

		// filters dropdown using prefix filter
		if($filterDropDown && $attribute != "none" && (($prefixFilter ? strpos((string)$attribute, $prefixFilter)!==0 : false) || ($regexFilter ? preg_match($regexFilter,(string)$attribute)===0 : false))) continue;
		
		// CWE 09.02.2016: in public: filters disabled options
		if($isPublicPrincipal && $attribute["disabled"]=="1") continue;
		
		if(!$firstLoop && !$useMultipleColumn) {
		    $this->put('<br>'); //next line for the next label and checkbox
		}
		
		//the checkbox is before the text of the option
		//the width of the checkbox is valueWidth / useMultipleColumn if defined / maxSelection if defined

		if($useMultipleColumn>0){
			$this->put('<div style="float:left; width: 100%; max-width:'.($parentWidth/$useMultipleColumn).'px;" >');
		}
		$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.str_replace(" ", "_", (string)$attribute).'_'.($inputType==null?$inputNode:$inputType);

		$label = $this->getRecord()->getRedirectedFieldLabel($this->getP(), $fieldName, $attribute);
		$labelDBValue = (string)$attribute;
		$tempDisabled = $disabled;
		if(!$label && $label!=="0"){
			$label = $transS->t($p, (string)$attribute, $attribute);
			$tempDisabled = true;
		}

		//add the checkbox
		$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" '.($attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").($maxSelection>0 ? 'data-max-selection="'.(string)$maxSelection.'"' : "").' ');
		if($inputType != null) $this->put(' type="'.$inputType.'" ');
		$this->put(' value="'.(string)$attribute.'" ');
		if($tempDisabled) $this->put(' disabled ');
		if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
		if(($val != null && array_search((string)$attribute, $val) !== false)) $this->put(' checked="on" ');
		$this->put(' style="width:17px;height:17px;vertical-align:top;margin-top:4px;');
		if($fieldXml["displayAsTag"]=="1") $this->put(' float:left; '); //the label will be float left, so the input should be as well
		if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
		$this->put('" />');
		//add the label
		if($attribute["noLabel"]!="1"){
			if($fieldXml["displayAsTag"]=="1"){
				$label = $this->doFormatForTag($label, $fieldXml, $labelDBValue);
			} else if ((string)$attribute["color"]){
				$color = (string)$attribute["color"];
				$label = '<span style="padding:2px 10px 2px 10px;line-height:21px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'">'.$label.'</span>';
			}
			if($useMultipleColumn>0) $labelWidth = ($parentWidth/$useMultipleColumn)-17; //17 is the width of the checkbox
			else $labelWidth = ($parentWidth-17);//17 is the width of the checkbox
			//as the label has a padding-left of 5, remove 5 in the div containing the text. The div for the label is important to prevent multiple lines to align bellow the input box
			$this->put('<label style="padding-left:5px;" for="'.$inputId.'" ><div style="display: inline-table;width:100%;max-width:'.($labelWidth-5).'px;margin-top:5px;">'.$label.'</div></label>');
		}
		if($useMultipleColumn>0){
			$this->put('</div>');
		}
		
		if($firstLoop){
		    $firstLoop = false;
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
	$chosen = (string)$fieldXml["chosen"]=="1";
	if($chosen) $this->put('chosen ');
	
	if((string)$fieldXml["allowNewValues"]=="1") $this->put('allowNewValues ');
	if((string)$fieldXml["displayDBValue"]=="1") $this->put('displayDBValue ');
	$this->put('"');
	if((string)$fieldXml["size"]!="") $this->put(' size="'.(string)$fieldXml["size"].'" ');
	elseif((string)$fieldXml["searchable"]!="1") $this->put(' size="5" ');
	$this->put(' multiple="multiple" ');
	if((string)$fieldXml["maxSelection"]!="") $this->put(' data-max-selection="'.(string)$fieldXml["maxSelection"].'" ');
	$this->put(' style="'.$valueWidth);
	if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
	$this->put('"');
	
	// Medair (CWE) 08.02.2018: adds support of ajax drop-downs
	$attributeMatchExp = (string)$fieldXml['attributeMatchExp'];
	if(!empty($attributeMatchExp)) {
	    // parses attribute match exp to a valid FuncExp
	    $attributeMatchExp = str2fx($attributeMatchExp);
	    // retrieves the pattern from the second parameter of the URL
	    // calls the matching FuncExp, converts the result to be compatible with select2 by calling select2Ajax
	    // and then serializes everything as JSON by calling newJsonString
	    $attributeMatchExp = fx('newJsonString',fx('select2Ajax',$attributeMatchExp,fx('sysExecParameter',"1")));
	    // serializes the FuncExp as a callable url
	    $attributeMatchExp = fx2str($attributeMatchExp);
	    //fput($attributeMatchExp);
	    $attributeMatchExp = base64url_encode($attributeMatchExp);
	    // pushes it to browser into a data-attributematchexp attribute
	    $this->put(' data-attributematchexp="'.$attributeMatchExp.'"');
	    $queryDelay = (string)$fieldXml['queryDelay'];
	    if($queryDelay) $this->put(' data-querydelay="'.$queryDelay.'"');
	    $queryMinLength = (string)$fieldXml['queryMinLength'];
	    if($queryMinLength) $this->put(' data-queryminlength="'.$queryMinLength.'"');
	}
	$this->put('>');
	

	$val = $this->getRecord()->getFieldValue($fieldName, $subFieldName);

	$sameAsField = (string)$fieldXml["sameAsField"];
	if(!empty($sameAsField)) {
		$sameAsFieldId = $formId.'_'.$sameAsField.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
		$this->addJsCode('$("#'.$inputId.'").attr("data-placeholder", $("#'.$sameAsFieldId.'").attr("data-placeholder")).html($("#'.$sameAsFieldId.' option").clone()).find("option[selected]").prop("selected", false);');
		
		$existingKeys = array();
		foreach($fieldXml->attribute as $attribute_key => $attribute){
			$existingKeys[(string)$attribute] = (string)$attribute;
		}
		
		$missingValues=array();
		$s = ''; $first=true;		
		foreach($val as $k) {
			if($first) $first = false;
			else $s .= ', ';
			$s .= 'option[value='."'$k'".']';
			if($existingKeys[$k] == null && $k) $missingValues[$k]=$k;
		}
		$this->addJsCode('$("#'.$inputId.'").'.'find("'.$s.'").prop("selected", "selected");');

		if(!empty($missingValues)) {
			$currentFlow = $this->evalfx(fx('ctlCurrentFlow'));
			// Adds non matching value as a new value if allowed
			if($fieldXml["allowNewValues"]=="1"){
				foreach($missingValues as $k) {
					$labelForTitle = $transS->t($p, $k);
					$label = $labelForTitle;
					if(!$chosen && strlen($label)>64) {
						$label = substr($label, 0, 61)."...";
					}
					if(!$chosen){
						$label = str_replace(" ", "&nbsp;", $label);
					}
					$htmlOption = "'".'<option selected="selected" value="'.$k.'" title="'.$labelForTitle.'" >'.$label.'</option>'."'";
					$this->addJsCode('$("#'.$inputId.'").append('.$htmlOption.')');
				}
			}
			// CWE 25.02.2016: displays a deprecated message to user if an old value is present in field and does not exist in the drop down anymore.
			elseif(!$isPublicPrincipal && ($currentFlow == ElementEvaluator::ELEMENT_FLOW_COPY || $currentFlow == ElementEvaluator::ELEMENT_FLOW_EDIT)) {
				$deprecatedMessage='';
				foreach($missingValues as $k) {
					if($deprecatedMessage) $deprecatedMessage.=', ';
					$deprecatedMessage.="&apos;".$k."&apos;";
				}
				$deprecatedMessage=str_replace('$value$',$deprecatedMessage,$transS->t($p,'removeDeprecatedValues'));
				$this->addJsCode(
						"$('#".$formId.'__'.$fieldName." div.value').wigii('bindHelpService',{
							width:250,height:80,
							type:'warning',
							localContent:true,
							content:'".$deprecatedMessage."'
						});");
			}
		}		
	}
	else {
	    
	    // Medair (CWE) 08.02.2018: adds support of ajax drop-downs
	    $attributeMatchExp = (string)$fieldXml['attributeMatchExp'];
	    if(!empty($attributeMatchExp) && !empty($val)) {
	        // parses attribute match exp to a valid FuncExp
	        $attributeMatchExp = str2fx($attributeMatchExp);
	        // takes current value as exact search pattern
	        $attributeMatchExp->addArgument($val);
	        // executes pattern matching
	        $attributeMatchExp = $this->evalfx($attributeMatchExp);
	        // prefills set of attributes with existing xml
	        $attributes = array();
	        foreach($fieldXml->attribute as $attribute) {
	            $attributes[(string)$attribute] = $attribute;
	        }
	        // extends set of attributes with the matching ones
	        if(isset($attributeMatchExp)) {
	            foreach($attributeMatchExp->children() as $attribute) {
	                $attributes[(string)$attribute] = $attribute;
	            }
	        }
	    }
	    else $attributes = $fieldXml->attribute;
	    
		//define the options:
		$html2text = new Html2text();
		$existingKeys = array();
		foreach($attributes as $attribute_key => $attribute){			
			// filters dropdown using prefix filter
			if($filterDropDown && $attribute != "none" && (($prefixFilter ? strpos((string)$attribute, $prefixFilter)!==0 : false) || ($regexFilter ? preg_match($regexFilter,(string)$attribute)===0 : false))) continue;
			// CWE 09.02.2016: in public: filters disabled options
			if($isPublicPrincipal && $attribute["disabled"]=="1") continue;
			
			$existingKeys[(string)$attribute] = (string)$attribute;
			if(($val != null && array_search((string)$attribute, $val) !== false)) $selected = ' selected="selected" ';
			else $selected = "";
			$label = $this->getRecord()->getRedirectedFieldLabel($this->getP(), $fieldName, $attribute);
			$tempDisabled = false;
			if(!$label && $label!=="0"){
				$label = $transS->t($p, (string)$attribute, $attribute);
				$tempDisabled = true;
			}
			
			// cleans up the html
			$html2text->setHtml($label);
			$label = $html2text->getText();
// 			$html2text->clear();
			$label = trim($label);
			
			if($attribute == "none" && $chosen){				
				if($label != '' && $label !='&nbsp;') {
					$this->addJsCode("$('#".$inputId."').attr('data-placeholder','".$label."')");					
				}
				continue;
			}
			
			if($attribute["optGroupStart"]=="1"){
				$this->put('<optgroup '.($tempDisabled || $attribute["disabled"]=="1" ? 'disabled="on"' : "").' label="'.$label.'" >');
			} else if($attribute["optGroupEnd"]=="1"){
				$this->put('</optgroup>');
			} else {
				$labelForTitle = $label;
				if(!$chosen && strlen($label)>64) {
					$label = substr($label, 0, 61)."...";
				}
				if(!$chosen){
					$label = str_replace(" ", "&nbsp;", $label);
				}
				$this->put('<option  '.($tempDisabled || $attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' value="'.(string)$attribute.'" '.$selected.' title="'.$labelForTitle.'" >'.$label.'</option>');
			}
		}
		
		// computes the missing values
		$missingValues=array();
		if(!empty($val)) {
			foreach($val as $k) {
				if($existingKeys[$k] == null && $k) $missingValues[$k]=$k;
			}
		}
		
		if(!empty($missingValues)) {
			$currentFlow = $this->evalfx(fx('ctlCurrentFlow'));
			// Adds non matching value as a new value if allowed
			if($fieldXml["allowNewValues"]=="1"){
				foreach($missingValues as $k) {
					$labelForTitle = $transS->t($p, $k);
					$label = $labelForTitle;
					if(!$chosen && strlen($label)>64) {
						$label = substr($label, 0, 61)."...";
					}
					if(!$chosen){
						$label = str_replace(" ", "&nbsp;", $label);
					}
					$this->put('<option selected="selected" value="'.$k.'" title="'.$labelForTitle.'" >'.$label.'</option>');
				}
			}
			// CWE 25.02.2016: displays a deprecated message to user if an old value is present in field and does not exist in the drop down anymore.
			elseif(!$isPublicPrincipal && ($currentFlow == ElementEvaluator::ELEMENT_FLOW_COPY || $currentFlow == ElementEvaluator::ELEMENT_FLOW_EDIT)) {
				$deprecatedMessage='';
				foreach($missingValues as $k) {
					if($deprecatedMessage) $deprecatedMessage.=', ';
					$deprecatedMessage.="&apos;".$k."&apos;";
				}
				$deprecatedMessage=str_replace('$value$',$deprecatedMessage,$transS->t($p,'removeDeprecatedValues'));
				$this->addJsCode(
						"$('#".$formId.'__'.$fieldName." div.value').wigii('bindHelpService',{
							width:250,height:80,
							type:'warning',
							localContent:true,
							content:'".$deprecatedMessage."'
						});");
			}
		}
	}
	unset($html2text);
	$this->put('</'.$inputNode.'>');
		
	//ici il ne faut pas metre la condition if disable car s'il est disable il peut très bien se faire
	//enable par un autre checkbox. Donc il faut que le code js soit présent...
	if(!$readonly) {
		//$this->getExecutionService()->addJsCode("multipleSelectVals['".$inputId."'] = $('#".$inputId."').val(); $('#".$inputId."').click(function(){ multipleSelectOnClick(this.id); });");
	}
}

