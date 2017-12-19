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
	$inputWithDatePickerWidth = $parentWidth-$labelWidth-5-35;
	$halfInputWidth = ((($parentWidth)/2)-5);
	$valueWidth = " width: 100%; max-width:".($parentWidth-$labelWidth-6)."px; text-align: left;";
	$labelWidth = " width: 100%; max-width:".($labelWidth-20)."px; ";
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

if($fieldXml["onlyDate"]!="1"){

	//isAllDay
	$subFieldName = "isAllDay";
	$inputNode = "input";
	$inputType = "checkbox";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$inputName = $fieldName.'_'.$subFieldName;
	$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

	$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, 'max-width: 14px;', $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
	$val = $this->getRecord()->getFieldValue($fieldName, $subFieldName);
	//if($fieldXml["onlyDate"]=="1"){
	//	//si l'on veut le onlyDate, on modifie le onClick en l'obligeant d'être toujours valide..
	//	//c'est mieux que de le rendre disable, car ainsi la valeur checked=on est tout de même transmise
	//	//on force cette case à cocher à true
	//	$val = true;
	//	$this->put(" onclick=\" $(this).attr('checked','on'); showHelp(this, '".$this->h("TimeRanges_onlyDate")."', 20, 'fromCenter', 200, 200, 5000);\" ");
	//} else {
		//contient le code pour afficher/supprimer les champs heures
		$this->put(' onclick=" if($(this).attr(\'checked\')){ $(this).parent().next().next().hide(); } else { $(this).parent().next().next().show(); } "');
	//}
	//ajoute une valeur checked par défaut s'il n'y a pas de valeur
	if ($val === null || $val === "1" || $val === "on" || $val === true){ //dans le cas ou la valeur viendrait directement de la base de donnée...
		$this->put(' checked="on" ');
	}
	$this->displayForm_1_TillClassDefinition();
	$this->displayForm_2_TillStyleDefinition($labelWidth, null, $subFieldName, $readonly, $disabled);
	$this->displayForm_3c_CloseStyleAndFinish();

	$this->put('<div class="timeZone" ');
		if ($val === null || $val === "1" || $val === "on" || $val === true){
			$this->put(' style="display:none;" ');
		}
		$this->put(' >');

		//begTime
		$subFieldName = "begTime";
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

		//endTime
		$subFieldName = "endTime";
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

	$this->put('</div>'); //end of timeZone

	//begDate
	$subFieldName = "begDate";
	$inputNode = "input";
	$inputType = "text";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$begDateInputId = $inputId;
	$inputName = $fieldName.'_'.$subFieldName;
	$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

	$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, ($valueWidth), $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
	$this->displayForm_1_TillClassDefinition();
	$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
	$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->displayForm_4a_CloseValueAsAttribute();

	//endDate
	$subFieldName = "endDate";
	$inputNode = "input";
	$inputType = "text";
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$endDateInputId = $inputId;
	$inputName = $fieldName.'_'.$subFieldName;
	$isRequire = $fieldXml["require"]=="1" && $dtXml->{$subFieldName}["require"]="1";

	$this->displayForm_0_TillPossibleAdditionalAttribute($labelWidth, ($valueWidth), $subFieldName, $field->getDataType()->getDataTypeName(), $inputNode, $inputType, $inputId, $inputName, $isRequire);
	$this->displayForm_1_TillClassDefinition();
	$this->displayForm_2_TillStyleDefinition($labelWidth, $valueWidth, $subFieldName, $readonly, $disabled);
	$this->displayForm_3a_CloseStyleBeginValueAsAttribute();
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->displayForm_4a_CloseValueAsAttribute();

} else {
	//in line only Date option
	$subFieldName = "begDate";
	$inputNode = "input";
	$inputType = "text";
	$inputName = $fieldName.'_'.$subFieldName;
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$begDateInputId = $inputId;
	$this->put($this->t("timeRangesOnlyDateFrom")." ");
	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	$this->put(' type="'.$inputType.'" ');
	$this->put(' class="');
	if($readonly) $this->put(' removeDisableOnSubmit ');
	$this->put('" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled ');
	$this->put(' style="');
	if($readonly) $this->put('background-color:#E3E3E3;');
	$this->put('" value="');
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->put('" />');

	$this->put(" ".$this->t("timeRangesOnlyDateTo")." ");

	$subFieldName = "endDate";
	$inputNode = "input";
	$inputType = "text";
	$inputName = $fieldName.'_'.$subFieldName;
	$inputId = $formId.'_'.$fieldName.'_'.$subFieldName.'_'.($inputType==null?$inputNode:$inputType);
	$endDateInputId = $inputId;
	$this->put('<'.$inputNode.' id="'.$inputId.'" name="'.$inputName.'" ');
	$this->put(' type="'.$inputType.'" ');
	$this->put(' class=" ');
	if($readonly) $this->put(' removeDisableOnSubmit ');
	$this->put('" ');
	if($disabled) $this->put(' disabled ');
	if($readonly) $this->put(' disabled ');
	$this->put(' style="');
	if($readonly) $this->put('background-color:#E3E3E3;');
	$this->put('" value="');
	$this->put($this->formatValueFromRecord($fieldName, $subFieldName, $this->getRecord()));
	$this->put('" />');
}
//jquery small calendar for date
if(!($readonly || $disabled)) {
	if($fieldXml["onlyDate"]!="1"){
		$this->getExecutionService()->addJsCode("
$('#".$begDateInputId.", #".$endDateInputId."')
	.datepicker({
		dateFormat: 'dd.mm.yy',
		changeYear: true,
		firstDay:1,
		constrainInput:false,
		showOn:'button'
	})
	.click(function(){
		$(this).datepicker('hide');
	})
	.width(".$inputWithDatePickerWidth.")
	.next().css('margin',0).width(34);
;");
	} else {
		$this->getExecutionService()->addJsCode("
$('#".$begDateInputId.", #".$endDateInputId."')
	.datepicker({
		dateFormat: 'dd.mm.yy',
		changeYear: true,
		firstDay:1,
		constrainInput:false,
		showOn:'focus'
	})
	.width(".($halfInputWidth-27).");
	");
	}
	//if begDate is more than end date, then clear endate
	$this->getExecutionService()->addJsCode("
$('#".$begDateInputId."').bind('blur change', function(){
	if($(this).datepicker('getDate')>$('#".$endDateInputId."').datepicker('getDate')){
		$('#".$endDateInputId."').val('');
	}
});
");
}




