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

/*
 * Created on 21 November 2012
 * by LWR
 */
class ExportICSCalendarFormExecutor extends ExportFormExecutor {
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}
	
	protected function doSpecificCheck($p, $exec){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	public function export($p, $exec, $state, $exportLC, $elementListLC, $tempExportLC){
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$transS = ServiceProvider::getTranslationService();
		$sessAS = ServiceProvider::getSessionAdminService();
		
		$recordWigiiBag = $sessAS->getData($this->getRecord(), "exportRecordWigiiBag");
		$recordWigiiBag->setFormFieldList($this->getRecord()->getFieldList());
		$this->getRecord()->setWigiiBag($recordWigiiBag);
		
		$contentType = 'text/calendar';
		$ext = 'ics';
		$fileName = ($exec->getCrtWigiiNamespace()->getWigiiNamespaceName() ? $exec->getCrtWigiiNamespace()->getWigiiNamespaceName()."_" : "").$transS->t($p, $exec->getCrtModule()->getModuleUrl()).".".$ext;
		
		$this->getHeader($contentType, $fileName);
		
		if($elementListLC->getMultipleSelection()!=null){
			$exportLC->resetFetchCriteria($p, $this->getWigiiExecutor());
//			//add groupList on all
//			$groupList = GroupListAdvancedImpl::createInstance();
//			$lf = ListFilter::createInstance();
//			$lf->setFieldSelectorList(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
//			$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
//			$lc->setGroupPList($groupList);
			//changed on 24 sept, when multiple select export in excel it is more natural to keep current group filter
			//the only limitation, is that the user needs to clic on all group if he has some selection in multiple groups
			$exportLC->matchFetchCriteria($elementListLC);
			//add the multipleSelection criterias in the LogExp
			$exportLC->addLogExpOnMultipleSelection($elementListLC->getMultipleSelection());
		} else {
			$exportLC->matchFetchCriteria($elementListLC);
			$exportLC->setFieldSelectorLogExp(null);
			$selectExp = $elementListLC->getLogExpOnSelectSearch();
			$textExp = $elementListLC->getLogExpOnTextSearch();
			$andExp = LogExp::createAndExp();
			$one = false; //check if there is at least one existing logExp
			if($selectExp) { $one = true; $andExp->addOperand($selectExp); }
			if($textExp) { $one = true; $andExp->addOperand($textExp); }
			if($one) $exportLC->setFieldSelectorLogExp($andExp);
		}
		
		$Axml = $configS->ma($p, $exec->getCrtModule(), $this->getRecord()->getActivity());
		
		//reset sorting on period
		if(!$exportLC->getFieldSortingKeyList()){
			$exportLC->setFieldSortingKeyList(FieldSortingKeyListArrayImpl :: createInstance());
		}
		$exportLC->getFieldSortingKeyList()->reset();
		if((string)$Axml->parameters["periodFieldname"]){
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["periodFieldname"], "begDate", true);
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["periodFieldname"], "begTime", true);
		//Dates case
		} else {
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["dateFieldname"], "value", true);
		}
		
		$elementPList = ElementPListExportICSCalendar::createInstance($this->getWigiiExecutor(), $exportLC);
		$elementPList->setP($p);
		$elementPList->setExecutionService($exec);
		if((string)$Axml->parameters["periodFieldname"]){
			$elementPList->setDate($this->getRecord()->getFieldValue("excelCalendarYear"), (string)$Axml->parameters["periodFieldname"], true);
		//Dates case
		} else {
			$elementPList->setDate($this->getRecord()->getFieldValue("excelCalendarYear"), (string)$Axml->parameters["dateFieldname"], false, (string)$Axml->parameters["endDateFieldname"]);
		}
		$elementPList->setSubjectField((string)$Axml->parameters["subjectFieldname"]);
		$elementPList->setDescriptionField((string)$Axml->parameters["descriptionFieldname"]);
		$elementPList->setLocationField((string)$Axml->parameters["locationFieldname"]);
		$elementPList->setPostLocationField((string)$Axml->parameters["postLocationFieldname"]);
		$elementPList->setLabelField((string)$Axml->parameters["labelFieldname"]);
		$elementPList->setOrganizerField((string)$Axml->parameters["organizerFieldname"]);
		
		$elementPList->actOnBeforeAddElementP();
//		eput($exportLC->getGroupLogExp());
//		eput($exportLC->getFieldSelectorLogExp());
		$nbRow = $elS->getSelectedElementsInGroups($p, $exportLC->getGroupLogExp(), $elementPList, $exportLC);
		$elementPList->actOnFinishAddElementP($nbRow);
		
		echo $elementPList->output();
		
//		$this->getWigiiExecutor()->throwEvent()->exportElements(PWithModuleWithElementPList::createInstance($p, $exec->getCrtModule(), $elementPList));
		
		$sessAS->clearData($this->getRecord(), "exportRecordWigiiBag");
		
		exit();
	}
	
	protected function doOnStart($p, $exec){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION); }
	
	protected function doRenderForm($p, $exec){ new ServiceException('unsupported by this implementation', ServiceException::UNSUPPORTED_OPERATION);}
}



