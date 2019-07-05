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
 * Created on 11 february 10 by LWR
 * Refactored by CWE on 02.07.2019 as a WebService and integrate standard getXmlFeed action 
 */
class XmlPublishGroupFormExecutor extends WebServiceFormExecutor {
    private $_debugLogger;
    private $_executionSink;
    
    // Dependency injection
    
    private function debugLogger()
    {
        if(!isset($this->_debugLogger))
        {
            $this->_debugLogger = DebugLogger::getInstance("XmlPublishGroupFormExecutor");
        }
        return $this->_debugLogger;
    }
    private function executionSink()
    {
        if(!isset($this->_executionSink))
        {
            $this->_executionSink = ExecutionSink::getInstance("XmlPublishGroupFormExecutor");
        }
        return $this->_executionSink;
    }
    
    // Configuration
    
    private $publicPrincipal;
    /**
     * Injects Public Principal to be used to access content
     * @param Principal $p
     */
    public function setPublicPrincipal($p) {
        $this->publicPrincipal = $p;
    }
    
    // WebExecutor implementation
    
    public function isMinimalPrincipalAuthorized() {return false;}
    public function isPublicPrincipalAuthorized()  {return true;}
    
    public function processAndEnds($p,$exec) {
        $action = $exec->getCrtAction();
        if($action=='getXmlFeed') {
            if(isset($this->publicPrincipal)) {
                $p = $this->publicPrincipal;
                $p->changeWigiiNamespace($exec->getCrtWigiiNamespace());
            }
            return $this->processGetXmlFeed($p, $exec);
        }
        elseif($action=='groupXmlPublish') return $this->processGroupXmlPublish($p, $exec);
        else throw new FormExecutorException('Unsupported request '.$exec->getCrtRequest(), FormExecutorException::UNSUPPORTED_OPERATION);
    }
    
    protected function processGetXmlFeed($p,$exec) {
        $this->executionSink()->publishStartOperation('processGetXmlFeed',$p);
        try {
            
            // reads url parameters            
            $i = 0;
            $groupId = $exec->getCrtParameters($i++);
            $xmlCode = $exec->getCrtParameters($i++);
            if($groupId==null || $xmlCode==null) throw new FormExecutorException('getXmlFeed takes at least two arguments: groupId and xmlCode', FormExecutorException::INVALID_ARGUMENT);
            $isXml = $exec->getCrtParameters($i) == null || $exec->getCrtParameters($i) == "xml"; //if nothing then isXml is by default
            $isDownload = $exec->getCrtParameters($i) == "download"; //this case is when we want to download a file referenced in the xml list. --> the path of files in xml feed is construct with the xml code to check if the document is really available through an xml public feed.
            $isCSVRaw = $exec->getCrtParameters($i) == "csvraw";
            $isCSVBackup = $exec->getCrtParameters($i) == "csvbackup";
            $isCSV = $exec->getCrtParameters($i) == "csv";
            $isICal = $exec->getCrtParameters($i) == "ics";
            $isExcel = $exec->getCrtParameters($i) == "excel";
            $isExcelCal = $exec->getCrtParameters($i) == "excelcal";
            $i++;
            if($isDownload){
                $elementId = $exec->getCrtParameters($i++);
                $fieldName = $exec->getCrtParameters($i++);
            } else if($isCSVBackup || $isCSVRaw || $isCSV){
                $isIntegrated = false; //detect if next option is integrated
                if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
                if($exec->getCrtParameters($i)=="tab") { $csvSeparator = "	"; $i++; }
                else $csvSeparator = $exec->getCrtParameters($i++);
                if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
                $csvEncoding = $exec->getCrtParameters($i++);
                if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
            } else if($isICal){
                $fromDate = $exec->getCrtParameters($i++);
            }
            else if($isExcelCal) {
                $calYear = $exec->getCrtParameters($i++);
            }
            $nextArg = $exec->getCrtParameters($i);
            
            // reads group
            $record = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance("groupXmlPublish"), $exec->getCrtModule());
            $groupAS = ServiceProvider :: getGroupAdminService();            
                    
            $groupP = $groupAS->getGroup($p, $groupId);
            if ($groupP == null || $groupP->getRights() == null) {
                throw new ServiceException('GetXmlFeed on unreachable group: ' . $groupId, ServiceException :: FORBIDDEN);
            }
            
            //setup the configuration context on the groupId to get configuration
            //We need to get the config for includeCHildrenGroups at a top level. because there is no sense to get the last groupConfig for children when setting a new groupList
            $groupPList = GroupListAdvancedImpl :: createInstance()->addGroupP($groupP);
            $this->getWigiiExecutor()->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), $groupPList, (ServiceProvider::getConfigService()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
            
            $record->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getXmlPublish(), $record->getActivity());
            //verify XmlPublish is enabled for this group
            if ($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_enableXmlPublish") == "0") {
                throw new ServiceException('Group_enableXmlPublish is 0', ServiceException :: FORBIDDEN);
            }
            if (!$record->getFieldValue("enableGroupXmlPublish")) {
                throw new ServiceException('GetXmlFeed needs enableGroupXmlPublish in group detail: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
            }
            if ($record->getFieldValue("xmlPublishCode") !== $xmlCode) {
                throw new ServiceException('GetXmlFeed needs correct code for group: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
            }
            
            $lc = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "getXmlFeed");
            $lc->resetFetchCriteria($p, $this->getWigiiExecutor());
            
            // processes each feed type
            if($isXml) $this->getXmlFeed($p, $exec, $groupP, $lc);
            elseif($isDownload) $this->getXmlFeedDownload($p, $exec,$groupId,$elementId,$fieldName,$nextArg);
            elseif($isICal) $this->getXmlFeedICal($p, $exec, $groupPList, $fromDate);
            elseif($isCSVBackup || $isCSVRaw || $isCSV) $this->getXmlFeedCSV($p, $exec, $groupPList,$csvSeparator,$csvEncoding,$isIntegrated,$isCSV,$isCSVRaw,$isCSVBackup);
            elseif($isExcel || $isExcelCal) $this->getXmlFeedExcel($p, $exec, $groupPList, $isExcelCal, $calYear);
            else throw new FormExecutorException('Unsupported request '.$exec->getCrtRequest(), FormExecutorException::UNSUPPORTED_OPERATION);
        }
        catch(Exception $e) {
            $this->executionSink()->publishEndOperationOnError('processGetXmlFeed',$e,$p);
            // Logs exception on server side
            ExceptionSink :: publish($e);
            // Sends 404 Page not found to browser
            $this->getWigiiExecutor()->displayNotFound();
            exit();
        }
        $this->executionSink()->publishEndOperation('processGetXmlFeed',$p);        
    }
    /**
     * Generates an xml list file
     */
    private function getXmlFeed($p, $exec, $groupP, $lc) {
        $elS = ServiceProvider :: getElementService();
        
        //add headers
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
        header("Pragma: no-cache");
        header('Pragma: public');
        header('Content-type: ' . typeMime(".xml"));
        
        //generates the xml
        $elementPListExportXml = $this->getWigiiExecutor()->createElementPListExportXmlInstance($this->getWigiiExecutor(), $lc);
        $elementPListExportXml->setModule($exec->getCrtModule());
        $elementPListExportXml->setCrtRequest($exec->getCrtRequest());
        $elementPListExportXml->actOnBeforeAddElementP();
        
        $tempLF = ListFilter :: createInstance();
        $listViewXml = $this->getWigiiExecutor()->getConfigurationContext()->mf($p, $exec->getCrtModule(), Activity :: createInstance("listView"));
        if ($listViewXml && $listViewXml->xpath("*[@isDefaultSortingKey=1]")) {
            $field = reset($listViewXml->xpath("*[@isDefaultSortingKey=1]"));
            $tempLF->setFieldSortingKeyList(FieldSortingKeyListArrayImpl :: createInstance());
            if ((string) $field["elementAttribut"]) {
                $tempLF->getFieldSortingKeyList()->addElementSortingKey((string) $field["elementAttribut"], !stripos($field["defaultSorted"], "desc")!==false);
            } else {
                $tempLF->getFieldSortingKeyList()->addFieldSortingKey((string) $field["field"], ((string) $field["subField"] ? (string) $field["subField"] : null), !stripos($field["defaultSorted"], "desc")!==false);
            }
        }
        
        $elS->getAllElementsInGroup($p, $groupP->getGroup(), $elementPListExportXml, ($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"), $tempLF);
        $elementPListExportXml->actOnFinishAddElementP(null);
        exit();
    }
    /**
     * Sends the document to the browser
     * Last parameter is passed to download subrequest
     */
    private function getXmlFeedDownload($p, $exec,$groupId,$elementId,$fieldName,$nextArg=null) {
        $elS = ServiceProvider :: getElementService();
        $tempElementPList = ElementPAdvancedListArrayImpl :: createInstance();
        $elS->getSelectedElementsInGroups($p, lxInGR(lxEq(fs('id'), $groupId)), $tempElementPList, lf(fsl(fs_e('id')),lxEq(fs_e('id'),$elementId)));
        if ($tempElementPList->isEmpty()) {
            throw new ServiceException('GetXmlFeed, download element ' . $elementId . ' but id is not in group ' . $groupId . '', ServiceException :: FORBIDDEN);
        }
        //set a flag for the download request to use the public principal
        $this->getWigiiExecutor()->setPublicPrincipalForDownloadRequest($p);
        $exec->addRequests("" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/download/" . $elementId . "/" . $fieldName . ($nextArg ? "/" . $nextArg : ""));        
    }
    private function getXmlFeedICal($p, $exec, $groupPList, $fromDate) {
        $exportRec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance("exportExcelCalendar"), $exec->getCrtModule());
        $exportICSFormExecutor = $this->getWigiiExecutor()->createExportICSFormExecutor($exportRec, null, null);
        
        //if no date define do today minus 3 month
        if($fromDate == null) $fromDate = date("Y-m-d", time()-(30*3*24*3600));
        $exportRec->setFieldValue($fromDate, "excelCalendarYear");
        
        $sessAS = ServiceProvider::getSessionAdminService();
        $sessAS->storeData($exportRec, "exportRecordWigiiBag", $exportRec->getWigiiBag());
        
        $exportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
        $elementListLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
        $tempExportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");
        
        //set group context to current group
        $exportLC->setGroupPList($groupPList, true);
        $elementListLC->setGroupPList($groupPList, true);
        $tempExportLC->setGroupPList($groupPList, true);
        //set config group to current group
        $elementListLC->setConfigGroupList($groupPList);
        $exportLC->setConfigGroupList($groupPList);
        $tempExportLC->setConfigGroupList($groupPList);
        
        $exportICSFormExecutor->export($p, $exec, null, $exportLC, $elementListLC, $tempExportLC);
    }
    private function getXmlFeedCSV($p, $exec, $groupPList,$csvSeparator,$csvEncoding,$isIntegrated,$isCSV,$isCSVRaw,$isCSVBackup) {
        $exportRec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance("exportCSV"), $exec->getCrtModule());
        $exportCSVFormExecutor = $this->getWigiiExecutor()->createExportFormExecutor($exportRec, null, null);
        if($isCSV){
            $exportRec->setFieldValue("csvFormated", "csvFormat");
        }
        if($isCSVRaw){
            $exportRec->setFieldValue("csvRaw", "csvFormat");
        }
        if($isCSVBackup){
            $exportRec->setFieldValue("csvBackup", "csvFormat");
        }
        if(!$csvSeparator)	$exportRec->setFieldValue(",", "csvSeparator");
        else $exportRec->setFieldValue($csvSeparator, "csvSeparator");
        if(!$csvEncoding)	$exportRec->setFieldValue("ISO-8859-1", "csvEncoding");
        else $exportRec->setFieldValue($csvEncoding, "csvEncoding");
        
        $sessAS = ServiceProvider::getSessionAdminService();
        $sessAS->storeData($exportRec, "exportRecordWigiiBag", $exportRec->getWigiiBag());
        
        $exportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
        $elementListLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
        $tempExportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");
        
        //set group context to current group
        $exportLC->setGroupPList($groupPList, true);
        $elementListLC->setGroupPList($groupPList, true);
        $tempExportLC->setGroupPList($groupPList, true);
        //set config group to current group
        $elementListLC->setConfigGroupList($groupPList);
        $exportLC->setConfigGroupList($groupPList);
        $tempExportLC->setConfigGroupList($groupPList);
        
        $exportCSVFormExecutor->export($p, $exec, null, $exportLC, $elementListLC, $tempExportLC, $isIntegrated);
    }
    private function getXmlFeedExcel($p, $exec, $groupPList, $isExcelCal, $calYear) {
        $exportRec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance(($isExcelCal?"exportExcelCalendar":"exportExcel")), $exec->getCrtModule());
        $exportExcelFormExecutor = $this->getWigiiExecutor()->createExportFormExecutor($exportRec, null, null);
        if($isExcelCal) {
            $exportRec->setFieldValue("excelCalendarAnnual","excelCalendarTemplate");
            if(empty($calYear)) $calYear = date("Y");
            $exportRec->setFieldValue($calYear,"excelCalendarYear");
        }
        else {
            $exportRec->setFieldValue("excelList","excelGrouping");
        }
        
        $sessAS = ServiceProvider::getSessionAdminService();
        $sessAS->storeData($exportRec, "exportRecordWigiiBag", $exportRec->getWigiiBag());
        
        $exportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
        $elementListLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
        $tempExportLC = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");
        
        //set group context to current group
        $exportLC->setGroupPList($groupPList, true);
        $elementListLC->setGroupPList($groupPList, true);
        $tempExportLC->setGroupPList($groupPList, true);
        //set config group to current group
        $elementListLC->setConfigGroupList($groupPList);
        $exportLC->setConfigGroupList($groupPList);
        $tempExportLC->setConfigGroupList($groupPList);
        if(is_null($elementListLC->getFieldSortingKeyList())) $elementListLC->setFieldSortingKeyList(FieldSortingKeyListArrayImpl :: createInstance());
        
        $exportExcelFormExecutor->export($p, $exec, null, $exportLC, $elementListLC, $tempExportLC);
    }
    
    protected function processGroupXmlPublish($p, $exec) {
        if (!$exec->getCrtModule()->isAdminModule()) throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
        $groupAS = ServiceProvider :: getGroupAdminService();
        
        $totalWidth = 450;
        $labelWidth = 150;
        $action = $exec->getCrtRequest();
        
        $i = 0;
        $workingModuleName = $exec->getCrtParameters($i++);
        $groupId = $exec->getCrtParameters($i++);
        $isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
        $groupP = $groupAS->getGroup($p, $groupId);
        $group = $groupP->getGroup();
        
        if (false === $this->getWigiiExecutor()->lockEntity($p, $exec, null, "group", $group)) return;
            
        $rec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance("groupXmlPublish"), $exec->getCrtModule());
            
        //set url to refresh on done, depending on context
        if($isFromGroupPanel){
            $request = "elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId."/groupPanel";
        } else {
            $request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
        }
        
        $this->setGroupP($groupP);
        $this->setRecord($rec);
        $this->setFormId("groupXmlPublish_form");
        $this->setSubmitUrl($action);
        $this->setActOnCheckedRecordRequest($request);
        $this->setCorrectionWidth(43);
        $this->setLabelWidth($labelWidth);
        $this->setTotalWidth($totalWidth);
        
        $state = "start";
        if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
            
        //fill the original datas: important to do it here to make any files content available after checking if no change
        $rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $rec->getActivity());
        
        $this->ResolveForm($p, $exec, $state);
    }
    
    // FormExecutor implementation
    
	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	/* @deprecated by CWE on 03.07.2019 as FormExecutor is now loaded as a Web service (see WigiiExecutor::findWebExecutorForAction method) 
	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl=MANDATORY_ARG, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}
    */
	protected function doSpecificCheck($p, $exec){
		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		//if enable --> and the link is not yet created, then create the link
		if($rec->getFieldValue("enableGroupXmlPublish") && $rec->getFieldValue("xmlPublishCode")==null
			|| $rec->getFieldValue("recalcXmlPublishCode")){
			$rec->setFieldValue(false, "recalcXmlPublishCode");
			$rec->setFieldValue($group->getDetail()->getNewXmlPublishCode($p, $group), "xmlPublishCode");
		}
	}

	protected function actOnCheckedRecord($p, $exec) {
		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();
		
		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		try{
			//eput($rec->getFieldList()->getField("url")->displayDebug());
			$oldRecord = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance($rec->getActivity()->getActivityName()), $exec->getCrtModule());
			$oldRecord->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $oldRecord->getActivity());

			//add any file fields in the wigiiBag
			$this->updateHiddenFields($p, $exec, false, $oldRecord);
			$group->getDetail()->setXmlPublish($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
			$this->updateFilesOnDisk($p, $exec, false, $oldRecord, false);

			$groupAS->setXmlPublish($p, $group->getId(), $group->getDetail()->getXmlPublish());
			$exec->addJsCode(" adminGroup_crtSelectedGroup = '".$group->getId()."'; ");
		} catch(GroupAdminServiceException $e){
			throw $e;
		}

		$groupAS->unLock($p, $group);

		$this->getWigiiExecutor()->throwEvent()->updateGroup(PWithGroup::createInstance($p, $group));

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		if($exec->getIdAnswer() != "confirmationDialog"){
			echo ExecutionServiceImpl::answerRequestSeparator;
			echo "confirmationDialog";
			echo ExecutionServiceImpl::answerParamSeparator;
		}		
		$this->getWigiiExecutor()->operationSuccessfullMessage("confirmationDialog", 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}


	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "
update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');
";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}