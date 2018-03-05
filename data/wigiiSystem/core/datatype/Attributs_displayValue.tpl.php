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
 * Created on 3 déc. 09 by LWR
 * Updated by Wigii.org (Lionel Weber) on 30.11.2017 to manage colors
 */
if(!isset($exec)) $exec = ServiceProvider::getExecutionService();

$value = $this->formatValueFromRecord($fieldName, null, $this->getRecord());
$xml = $field->getXml();
$color = $xml->xpath('attribute[@color and (text()="'.$this->getRecord()->getFieldValue($fieldName).'")]');
if($color){ $color = (string)$color[0]["color"]; }

if((string)$xml["isTimeline"]=="1"){

    $options = $this->evalfx(str2fx((string)$xml["timelineOptions"]));

    $history = json_decode($options['global_history']);

    //Get nbElement to have in timeline
    $elementNot=$options['exclude'];
    $nbColumn=0;
    foreach($xml->attribute as $attribute_key => $attribute){
        if(!in_array($attribute,$elementNot)){
            $nbColumn++;
        }
    }

    //define the options:
    $useMultipleColumn = $nbColumn-1;
    $inputNode = "input";
    $inputType ="checkbox";

    $inputName = $fieldName.'_'.$subFieldName.'';
    $val = $this->formatValueToPreventInjection($this->getRecord()->getFieldValue($fieldName, $subFieldName));

    //if none attribute exist then add code to uncheck existing values if click on it
    $allowUnchek = false;
    if($xml->xpath("attribute[text()='none']")){
        $allowUnchek = true;
        $nbColumn -= 1;
    }
    $firstLoop = true;
    $this->put("<div style='width: 100%; height: 30px; background-color: #f9ebb2; text-align: right; margin-bottom:10px; font-weight: bold;' class='timelineBackground'>");

    $percentage = (100/($nbColumn));

    $historyFields = array();
    foreach($xml->attribute as $attribute_key => $attribute) {
        if($firstLoop){
            $firstLoop = false;
            continue;
        }
        if (!in_array($attribute, $elementNot)) {
            $historyField = $options['transitionHistory'][(string)$attribute];
            if($historyField){
                if($historyField instanceof FieldSelector) {
                    $timestamp = strtotime($this->getRecord()->getFieldValue($historyField->getFieldName()));
                    if($timestamp){
                        $date = date("d.m.Y",$timestamp);
                    } else{
                        $date = "";
                    }

                    $historyFields[(string)$attribute] = $historyField->getFieldName();
                } else {
                    $date = date("d.m.Y",strtotime($historyField));
                }
                $this->put('<div id="'. str_replace(' ','_',(string)$attribute). '_date" style="line-height: 30px; height: 30px; width: '. $percentage. '%; float: left; text-align:center;"><span style="display:none;">'. $date. '</span></div>');
            }else{
                $this->put('<div style="height: 30px; width: '. $percentage. '%; float: left;">&nbsp;</div>');
            }
        }
    }
    $this->put("<span class='glyphicon glyphicon-arrow-right' style='margin-top: -56px; margin-right: -35px; color: #f9ebb2; font-size: 57pt; z-index:-1;' aria-hidden='true'></span>");
    $this->put("</div>");

    $firstLoop = true;
    $fields_array = array();
    $tabId = array();
    foreach($xml->attribute as $attribute_key => $attribute){


        if(!in_array($attribute,$elementNot)){
            if($attribute == "none") continue;

            // filters dropdown using prefix filter
            if($filterDropDown && strpos((string)$attribute, $prefixFilter)!==0) continue;
            // CWE 09.02.2016: in public: filters disabled options
            if($isPublicPrincipal && $attribute["disabled"]=="1") continue;

            $fields_array[] = $attribute;

            //the radioButton is before the text of the option
            //the width of the checkbox is valueWidth / useMultipleColumn if defined
            if($useMultipleColumn>0){
                $this->put('<div class="child" style="writing-mode: vertical-rl; display: flex; float:left; width: 100%; max-width:'.((($parentWidth)/$useMultipleColumn)).'px;" >');
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
            $this->put('<'.$inputNode.' id="'.$inputId.'" style="margin:0; margin-bottom:3px; margin-right:'. ((($parentWidth)/$useMultipleColumn)/2). 'px;" name="'.$inputName.'" '.($attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' ');
            if($inputType != null) $this->put(' type="'.$inputType.'" ');
            $this->put(' value="'.(string)$attribute.'" ');
            $this->put(' disabled ');
            if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');

            if(($val != null && (string)$attribute==$val)) $this->put(' checked="on" ');
            $this->put(' style="');
            if($xml["displayAsTag"]=="1") $this->put(' float:left; '); //the label will be float left, so the input should be as well
            if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
            $this->put('" /> ');

            //add the label
            if($attribute["noLabel"]!="1"){
                if($xml["displayAsTag"]=="1"){
                    $label = $this->doFormatForTag($label, $xml, $labelDBValue);
                } else if ((string)$attribute["color"]){
                    $color = (string)$attribute["color"];
                    $label = '<span style="padding:2px 10px 2px 10px;line-height:21px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'; margin-right:'. ((($parentWidth)/$useMultipleColumn)/2). 'px;">'.$label.'</span>';
                }
                if($useMultipleColumn>0) $labelWidth = (($parentWidth-5)/$useMultipleColumn)-30;
                else $labelWidth = ($parentWidth-30);
                $this->put('<label style="padding-left:5px;" for="'.$inputId.'" ><div style="display: inline-table;width: 100%; max-width:'.$labelWidth.'px;margin-right:'. ((($parentWidth)/$useMultipleColumn)/2). 'px;">'.$label.'</div></label>');
            }

            if($useMultipleColumn>0){
                $this->put('</div>');
            }

            //Add JS on the field
            //$idform = $formId.'__'.$fieldName;
            //$historyField = $options['transitionHistory'][(string)$attribute];
            //$historyId = $formId.'_' .$historyField.'_value_text';
            //$date = date('Y-m-d');
            //$exec->addJsCode("$('#$historyId').hide()");

            //$jsCode .= "$('#$idform input').prop('checked', false);";

            /*
                if(!empty($tabId)){
                    foreach ($tabId as $id){
                        $jsCode .= "$('#$id').prop('checked', true);";
                        $jsCode .= "$('#$historyId').val('$date');";
                    }
                }
            */
            $idField = $formId.'__'.$fieldName;
            $jsCode .= "$('#$inputId').attr('class', 'inTimeline');";


            $tabId[str_replace(" ", "_", (string)$attribute)] = $inputId;

            if($firstLoop){
                $firstLoop = false;
            }
        }else{
            $inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.str_replace(" ", "_", (string)$attribute).'_'.($inputType==null?$inputNode:$inputType);

            $label = $this->getRecord()->getRedirectedFieldLabel($this->getP(), $fieldName, $attribute);
            $labelDBValue = (string)$attribute;
            $tempDisabled = $disabled;
            if(!$label && $label!=="0"){
                $label = $transS->t($p, (string)$attribute, $attribute);
                $tempDisabled = true;
            }

            $this->put('<div style="clear: both;">&nbsp;</div>');
            //add the checkbox
            $this->put('<'.$inputNode.' id="'.$inputId.'" style="margin:0;" name="'.$inputName.'" '.($attribute["disabled"]=="1" ? 'disabled="on"' : "").' '.($attribute["class"]!="" ? 'class="'.(string)$attribute["class"].'"' : "").' ');
            if($inputType != null) $this->put(' type="'.$inputType.'" ');
            $this->put(' value="'.(string)$attribute.'" ');
            $this->put(' disabled ');
            if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');

            if(($val != null && (string)$attribute==$val)) $this->put(' checked="on" ');
            $this->put(' style="');
            if($xml["displayAsTag"]=="1") $this->put(' float:left; '); //the label will be float left, so the input should be as well
            if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
            $this->put('" /> ');

            //add the label
            if($attribute["noLabel"]!="1"){
                if($xml["displayAsTag"]=="1"){
                    $label = $this->doFormatForTag($label, $xml, $labelDBValue);
                } else if ((string)$attribute["color"]){
                    $color = (string)$attribute["color"];
                    $label = '<span style="padding:2px 10px 2px 10px;line-height:21px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'">'.$label.'</span>';
                }
                if($useMultipleColumn>0) $labelWidth = (($parentWidth-5)/$useMultipleColumn)-30;
                else $labelWidth = ($parentWidth-30);
                $this->put('<label style="padding-left:5px;" for="'.$inputId.'" ><div style="display: inline-table;width: 100%; max-width:'.$labelWidth.'px;">'.$label.'</div></label>');
            }

            //Add JS on the field
            $idform = $formId.'__'.$fieldName;
            $jsCode .= "$('#$inputId').click(function(){";
            $jsCode .= "$('#$idform input').prop('checked', false);";
            $jsCode .= "$('#$inputId').prop('checked', true);";
            $jsCode .= "});";
        }
    }

    $temp_tab = array();
    $checkedAttribute = str_replace(" ", "_", $val);
    foreach($tabId as $k => $v){
        $temp_tab[$k] = $v;
        if($checkedAttribute==$k) {
            foreach ($temp_tab as $k2 => $id) {
                $jsCode .= "$('#$id').prop('checked', true);";
                $jsCode .= "$('#" . $k2 . "_date span').show();";
            }
            break;
        }
    }

    $fieldId = $formId.'__'.$fieldName;
    /*
        $jsCode = "var options = {
            historyFields : '".json_encode($historyFields). "'
            }";
*/
    $jsCode .= "setListenerForTimeline('$formId', '$fieldId', $nbColumn, {historyFields:'".json_encode($historyFields)."'})";


    $exec->addJsCode($jsCode);
    /*
            $exec->addJsCode("
    $('div#".$formId."__".$fieldName." .value input:checkbox').click(function() {
        $('div#".$formId."__".$fieldName." .value input:checkbox').attr('checked', false);" .
                ($allowUnchek
                    ? "if($(this).val()==radioButtonClick_".$formId.'_'.$fieldName.'_'.$subFieldName."){ $(this).attr('checked', false); radioButtonClick_".$formId.'_'.$fieldName.'_'.$subFieldName."= ''; } else { $(this).attr('checked', true); radioButtonClick_".$formId.'_'.$fieldName.'_'.$subFieldName."= $(this).val(); }"
                    : "$(this).attr('checked', true);"
                )."
    });
    ");
        if($allowUnchek) {
            $exec->addJsCode("
    radioButtonClick_" . $formId . '_' . $fieldName . '_' . $subFieldName . "= '';
    $('div#" . $formId . "__" . $fieldName . " .value').mouseover(function(e) { radioButtonClick_" . $formId . '_' . $fieldName . '_' . $subFieldName . "= $('input[name=$inputName]:checked').attr('value'); });
                    ");
        }
    */
}
elseif($xml["displayAsTag"]=="1"){
	if($value != null){
		$this->put('<span class="tag ui-corner-all" style="padding:0px 10px 2px 10px;'.$xml["tagStyle"].($color ? "background-color:#".$color.";color:#".getBlackOrWhiteFromBackgroundColor($color).";" : "").'">'.$value.'</span>');
	}
} else {
	if($color){
		$this->put('<span style="padding:2px 10px 2px 10px;background-color:#'.$color.';color:#'.getBlackOrWhiteFromBackgroundColor($color).'">'.$value.'</span>');
	} else {
		$this->put($value);
	}
}


