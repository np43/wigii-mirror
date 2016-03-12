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
 * Created on 29 Jun 2012 by LWR
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
if($fieldXml["isJournal"]=="1" && !$this->isForNotification() && !$this->isForPrint()){
	$fieldId = $this->getDetailRenderer()->getDetailId()."__".$fieldName;
	// CWE 10.02.2016: if element is blocked enables to add comments if allowOnReadOnly
	if(!($this->getRecord() instanceof Element && $this->getRecord()->isState_blocked()) || $fieldXml["allowOnReadOnly"]=="1"){
		if($this->isForExternalAccess()){
			$code = $exec->getCrtParameters(0);
			$actionUrl = "confirmationDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/externalAccess/".$code."/addJournalItem/".$this->getRecord()->getId()."";
		} else {
			$actionUrl = "confirmationDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/addJournalItem/".$this->getRecord()->getId()."";
		}
		$this->getExecutionService()->addJsCode("setListenerToAddJournalItem('".$exec->getIdAnswer()."', '".$this->getRecord()->getId()."', '$fieldName', '$fieldId', '".$this->getTranslationService()->getLanguage()."', ".($fieldXml["htmlArea"]==1 ? "true" : "false" ).", '".$this->getNewJournalItemString($this->getP(), $fieldXml["htmlArea"]==1, true)."', '".$this->t("ok")."', '".$this->t("cancel")."', '".$actionUrl."', '".$this->getNewJournalContentStringCode()."');");
		$this->put('&nbsp;&nbsp;&nbsp; (<span class="H addJournalItem">'.$this->t("addJournalItem").'</span>)');
	}
}


