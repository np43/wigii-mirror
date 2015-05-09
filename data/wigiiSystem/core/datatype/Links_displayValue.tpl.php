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
 * Created on 24/06/2013
 * by LWR
 */
$exec = $this->getExecutionService();

$fieldXml = $field->getXml();
if(!$this->isForNotification()){
	$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
}

$strValue = $this->formatValueFromRecord($fieldName, null, $this->getRecord());
//wrap link
// $jsUpdate = "update('elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/subelement/list/".$this->getRecord()->getId()."/".$fieldName."');";
// $strValue = '<a href="#" onclick="'.$jsUpdate.'">'.$strValue.'</a>';
$strValue = '<div '.($fieldXml["fsl"]!="" ? 'class="H" ' : '').'style="float: left">('.$strValue.')</div>';

$this->put($strValue);

// add button
$subEltModule = $this->getModuleAdminService()->getModule($this->getP(), (string)$fieldXml['module'])->getModuleUrl();
//the onclick on addNewSubElement is done in setListenerToPreviewList()
$subEltModule = ServiceProvider::getModuleAdminService()->getModule($this->getP(), (string)$fieldXml['module'])->getModuleUrl();
$trashBinPrefix = (string)$this->getConfigService()->getParameter($this->getP(), null, "deletedSubElementsLinkNamePrefix");
if(!empty($trashBinPrefix) && strpos($field->getFieldName(), $trashBinPrefix)!==0 && !$this->isForPrint() && !$this->isForExternalAccess() && !$this->isForNotification()){
	$this->put('<div class="addNewSubElement ui-corner-all Green" style="font-weight: bold; float: right; margin: 0 4px; padding: 2px 4px" >+ <font class="H">'.$this->t("addElementButton").'</font></div>');
	$this->addJsCode("setListenerToAddSubItem('".$fieldId."', '".$this->getRecord()->getId()."', '".$fieldName."');");
}

//display preview if fsl!=null
if(!$this->isForNotification() && $fieldXml["fsl"]!=""){
	if($fieldXml["fskl"] != "") $fskl = str2fskl((string)$fieldXml["fskl"]);
	elseif($fieldXml["fsk"] != "") $fskl = str2fskl((string)$fieldXml["fsk"]);
	else $fskl = null;

	$fsl = str2fsl((string)$fieldXml["fsl"]);

	$this->put('</div>'); //close current value div and open a new div full width for preview
	$this->displayElementPListPreview($this->getP(), $this->getDetailRenderer()->getIsInLineWidth(), $this->getRecord(), $fieldName, $fsl, $fskl, (string)$fieldXml["limit"]);
	$this->put('<div>'); //open new div to prevent div open close missmatch
}

