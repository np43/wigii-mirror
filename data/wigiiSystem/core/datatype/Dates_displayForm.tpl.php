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
 *
 * Changed on 2.10.2017 by Medair (LMA) - Add min date and max date attribute
 */

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
$inputId = $formId.'_'.$fieldName;

//Define max and min date
$minDate = (string)$fieldXml["minDate"];
if(!empty($minDate)) $minDate = $this->evaluateConfigParameter($minDate);
$maxDate = (string)$fieldXml["maxDate"];
if(!empty($maxDate)) $maxDate = $this->evaluateConfigParameter($maxDate);

//value
$subFieldName = "value";
$inputNode = "input";
$inputType = "text";
$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$inputName = $fieldName.'_'.$subFieldName;

$minDateId = $formId.'_'.$minDate.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
$maxDateId = $formId.'_'.$maxDate.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);

$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
if($inputType != null) $this->put(' type="'.$inputType.'" ');
if($disabled) $this->put(' disabled ');
if($readonly) $this->put(' disabled class="removeDisableOnSubmit" ');
$this->put(' style="'.$valueWidth);
if($readonly) $this->put('background-color:#E3E3E3;'); //disabled make color as white in Google Chrome
$this->put('" value="');
$value = $this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord());
if($fieldXml["isBirthDate"]=="1"){
	//remove the (age)
	$value = preg_replace("/ \(.*\)/", "", $value);
}
$this->put($value);
$this->put('" />');

//jquery small calendar for date
if(!($readonly || $disabled) && $fieldXml["isBirthDate"]!="1") {
	$this->getExecutionService()->addJsCode("
$('#".$inputId."')
.datepicker({ dateFormat: 'dd.mm.yy', changeYear: true, firstDay:1,	constrainInput:false, showOn:'button' })
.click(function(){ $(this).datepicker('hide'); }).css('max-width',".($parentWidth-30).")
".($isNotExpanded ? ".hide()" : "").";");
}
if($minDate) {
    if (preg_match('^[0-9]{1,2}\.[0-12]{2}\.\d{4}^', $minDate)) {
        $date = explode('.', $minDate);
        Dates::fromString($minDate, $d, $m, $y);

        $minDate = $d . '.' . $m . '.' . $y;

        $this->getExecutionService()->addJsCode("
        var dateFormat = 'd.m.yy';
        date = $.datepicker.parseDate( dateFormat, '" . $minDate . "' );
        $('#" . $inputId . "').datepicker('option', 'minDate', date);
    ");

    } else {
            $this->getExecutionService()->addJsCode("
        $('#" . $minDateId . "').on('change', function(){
            $('#" . $inputId . "').datepicker('option', 'minDate', getDate(this));
        });
    ");
    }

}
if($maxDate) {
    if (preg_match('^[0-9]{1,2}\.[0-12]{2}\.\d{4}^', $maxDate)) {
        $date = explode('.', $maxDate);
        Dates::fromString($maxDate, $d, $m, $y);

        $maxDate = $d . '.' . $m . '.' . $y;

        $this->getExecutionService()->addJsCode("
        var dateFormat = 'd.m.yy';
        date = $.datepicker.parseDate( dateFormat, '" . $maxDate . "' );
        $('#" . $inputId . "').datepicker('option', 'maxDate', date);
    ");
    } else {
            $this->getExecutionService()->addJsCode("
    $('#" . $maxDateId . "').on('change', function(){
        $('#" . $inputId . "').datepicker('option', 'maxDate', getDate(this));
    });
    ");
    }
}


if($minDate || $maxDate){
    $this->getExecutionService()->addJsCode("
        var dateFormat = 'dd.mm.yy';
        function getDate( element ) {
          var date;
          try {
            date = $.datepicker.parseDate( dateFormat, element.value );
          } catch( error ) {
            date = null;
          }
 
          return date;
        }
    ");
}



