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
 * Created on 24/06/2013 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */

$fieldXml = $field->getXml();
$fieldId = $this->getFormRenderer()->getFormId()."__".$fieldName;
$linkType = Links::linkTypeFromString((string)$fieldXml['linkType']);

//defining width if existant
if($parentWidth != null){
	$valueWidth = " width: 100%; max-width:".($parentWidth-5)."px; ";
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


$strValue = $this->formatValueFromRecord($fieldName, null, $this->getRecord());
$exec = ServiceProvider::getExecutionService();
if($linkType == Links::LINKS_TYPE_QUERY) {
	if($this->getRecord()->isNew()){
		$strValue = '<div '.($fieldXml["fsl"]!="" ? 'class="H linkTypeQuery" ' : 'class="linkTypeQuery" ').'style="float: left">(0)</div>';
	}
	else {
		$strValue = '<div '.($fieldXml["fsl"]!="" ? 'class="H linkTypeQuery" ' : 'class="linkTypeQuery" ').'style="float: left"></div>';
	}
}
else {
	//wrap link
	// if(!$this->getRecord()->isNew()) {
	// 	$jsUpdate = "update('elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/subelement/list/".$this->getRecord()->getId()."/".$fieldName."');";
	// 	$strValue = '<a href="#" onclick="'.$jsUpdate.'">'.$strValue.'</a>';
	// }
	$strValue = '<div '.($fieldXml["fsl"]!="" ? 'class="H" ' : '').'style="float: left">('.$strValue.')</div>';
}
$this->put($strValue);

// add button (only if not readonly and not disabled)
$subEltModule = ServiceProvider::getModuleAdminService()->getModule($this->getP(), (string)$fieldXml['module'])->getModuleUrl();
$trashBinPrefix = (string)$this->getConfigService()->getParameter($this->getP(), null, "deletedSubElementsLinkNamePrefix");
if(!empty($trashBinPrefix) && strpos($field->getFieldName(), $trashBinPrefix)!==0 && $linkType == Links::LINKS_TYPE_SUBITEM &&
	!$readonly && !$disabled){
	if($this->getRecord()->isNew()){
		$this->put('<div class="grayFont" style="float: right; margin: 0 4px; padding: 2px 4px">'.$this->t("finishElementBeforeAddingSubItems_prefix").$this->t("#elements".$subEltModule."#").'.</div>');
	} else {		
		$this->put('<div class="addNewSubElement ui-corner-all Green" style="font-weight: bold; float: right; margin: 0 4px; padding: 2px 4px">+ <font class="H">'.$this->t("addElementButton").'</font></div>');
	}
	$this->addJsCode("setListenerToAddSubItem('".$fieldId."', '".$this->getRecord()->getId()."', '".$fieldName."');");
}

//display preview if fsl!=null
if($fieldXml["fsl"]!=""){
	if($fieldXml["fskl"] != "") $fskl = str2fskl((string)$fieldXml["fskl"]);
	elseif($fieldXml["fsk"] != "") $fskl = str2fskl((string)$fieldXml["fsk"]);
	else $fskl = null;

	$fsl = str2fsl((string)$fieldXml["fsl"]);

	$this->put('</div>'); //close current value div and open a new div full width for preview
	$this->displayElementPListPreview($this->getP(), $this->getFormRenderer()->getIsInLineWidth(), $this->getRecord(), $fieldName, $fsl, $fskl, (string)$fieldXml["limit"]);
	$this->put('<div>'); //open new div to prevent div open close missmatch
}



