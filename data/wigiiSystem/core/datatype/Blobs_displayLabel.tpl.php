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

/**
 * Created on 29 Jun 2012 by LWR
 * Modified by Medair in 2016-2017 for maintenance purposes (see SVN logs for details)
 */

/**
 * the context (this) is TemplareRecordManager->displayLable($fieldName, $parentWidth = null, $visibleLanguage = null, $allowSpecialLabel = true)
 * here is the list of parameter available
 * $fieldName
 * $parentWidth
 * $visibleLanguage
 * $allowSpecialLabel
 * $field
 */
if(!isset($exec)) $exec = $this->getExecutionService();
$fieldXml = $field->getXml();
//display the label
$this->put($this->t($fieldName, $fieldXml));

//add a "+add" button
// Medair(CWE) 09.01.2017: if externalAccess allow add comments only if access level is Edit
if($fieldXml["isJournal"]=="1" && !$this->isForNotification() && !$this->isForPrint() && 
	(!$this->isForExternalAccess() || $this->isForExternalAccess() && $this->getExternalAccessLevel()==Emails::EXTERNAL_ACCESS_EDIT)){
	$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
	// CWE 09.05.2018: if field is readonly and allowOnReadOnly=0, then add button is disabled
	if($fieldXml["readonly"]=="1"||$fieldXml["disabled"]=="1") $allowJournal = ($fieldXml["allowOnReadOnly"]!="0");	
	else $allowJournal = true;
	if($this->getDetailRenderer()->getRecordIsWritable() && $allowJournal || $fieldXml["allowOnReadOnly"]=="1"){
		if($this->isForExternalAccess()){
			$code = $exec->getCrtParameters(0);
			$actionUrl = "confirmationDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/externalAccess/".$code."/addJournalItem/".$this->getRecord()->getId()."";
		} else {
			$actionUrl = "confirmationDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/addJournalItem/".$this->getRecord()->getId()."";
		}
		$this->getExecutionService()->addJsCode("setListenerToAddJournalItem('".$exec->getIdAnswer()."', '".$this->getRecord()->getId()."', '$fieldName', '$fieldId', '".($this->getTranslationService()->getLanguage()=="l02" ? "fr" : "en")."', ".($fieldXml["htmlArea"]==1 ? "true" : "false" ).", '".$this->getNewJournalItemString($this->getP(), $fieldXml["htmlArea"]==1, true)."', '".$this->t("ok")."', '".$this->t("cancel")."', '".$actionUrl."', '".$this->getNewJournalContentStringCode()."');");
		$this->put('&nbsp;&nbsp;&nbsp; (<span class="H addJournalItem">'.$this->t("addJournalItem").'</span>)');
	}
}


