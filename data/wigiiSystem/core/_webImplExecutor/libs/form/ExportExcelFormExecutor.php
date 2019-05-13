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
 * Created on 7 january 2011
 * by LWR
 */
class ExportExcelFormExecutor extends ExportFormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){

	}

	public function export($p, $exec, $state, $exportLC, $elementListLC, $tempExportLC){
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$transS = ServiceProvider::getTranslationService();
		$sessAS = ServiceProvider::getSessionAdminService();

		$recordWigiiBag = $sessAS->getData($this->getRecord(), "exportRecordWigiiBag");
		$recordWigiiBag->setFormFieldList($this->getRecord()->getFieldList());
		$this->getRecord()->setWigiiBag($recordWigiiBag);

		$contentType = ElementPListExportExcel::getContentTypeHeader();
		$ext = ElementPListExportExcel::getFileExtensions();
		$fileName = ($exec->getCrtWigiiNamespace()->getWigiiNamespaceName() ? $exec->getCrtWigiiNamespace()->getWigiiNamespaceName()."_" : "").$transS->t($p, $exec->getCrtModule()->getModuleUrl())."_".date('Y.m.d').".".$ext;

		$this->getHeader($contentType, $fileName);


		$configGroupPList = $exportLC->getConfigGroupList();
		if($elementListLC->getMultipleSelection()!=null){
			//add groupList on all
			$exportLC->resetFetchCriteria($p, $this->getWigiiExecutor());
			$exportLC->matchSortingCriteria($elementListLC);
			$exportLC->setGroupPList($configS->getRootGroupsInModule($p, $exec->getCrtModule()));
			//add the multipleSelection criterias in the LogExp
			$exportLC->addLogExpOnMultipleSelection($elementListLC->getMultipleSelection());
		} else {
			$exportLC->matchFetchCriteria($elementListLC);
		}

		$elementPList = ElementPListExportExcel::createInstanceForP($this->getWigiiExecutor(), $exportLC, $p);
		$elementPList->setNoGrouping();
		$elementPList->setLCTemp($tempExportLC);
		$elementPList->setLocalLinks($this->getRecord()->getFieldValue("localLinks"));
		//loop through the groupTree to make export per group
		switch($this->getRecord()->getFieldValue("excelGrouping")){
			case "excelTitle":
				$elementPList->setTitleGrouping();
				break;
			//this option is deprecated from 22 January 2012. Reason: not used and item within first group are not inclueded. --> this is not understandable by user...
//			case "excelPage":
//				$elementPList->setSheetGrouping();
//				break;
			case "excelList":
				$elementPList->setNoGrouping();
				break;
			default:
				throw new ServiceException("unknown excelGrouping option: ".$this->getRecord()->getFieldValue("excelGrouping"), ServiceException::INVALID_ARGUMENT);
		}

		if($elementPList->isNoGrouping()){
			$elementPList->actOnBeforeAddElementP();
			$elementPList->setCrtWigiiNamespace($exec->getCrtWigiiNamespace());
			//force to take content of all subgroups
			if(!$exportLC->doesGroupListIncludeChildren()) $exportLC->setGroupPList($exportLC->getGroupPList(), true);
			$exportLC->setConfigGroupList($configGroupPList);
			$nbRow = $elS->getSelectedElementsInGroups($p, $exportLC->getGroupLogExp(), $elementPList, $exportLC);
			$elementPList->actOnFinishAddElementP($nbRow);
		} else {
			$groupList = $exportLC->getGroupPList();
			if($groupList == null || $groupList->isEmpty()) return "No group selected";
			
			//query to find all ids in the view
			//force to take content of all subgroups
			if(!$exportLC->doesGroupListIncludeChildren()) $exportLC->setGroupPList($exportLC->getGroupPList(), true);
			$exportLC->setConfigGroupList($configGroupPList);

//			$tempFSL = $exportLC->getFieldSelectorList();
//			$exportLC->setFieldSelectorList(null);

			$elementIds = $this->getWigiiExecutor()->getAllElementIdsInListView($p, $exec, $exportLC);

			$elementIds = array_keys($elementIds->getListIterator());
			$countData = $elS->countSelectedElementsDistribution($p, $elementIds);
			$filledGroupIds = array();
			foreach($countData as $filledGroupId=>$nbItemsInGroup){
				if($nbItemsInGroup){
					$filledGroupIds[$filledGroupId] = $filledGroupId;
				}
			}
			//fetch the groupPTree to be able to loop throug the structure
			$groupPTree = GroupPListTreeArrayImpl::createInstance();
			$lf = ListFilter::createInstance();
			$lf->setFieldSelectorList($groupAS->getFieldSelectorListForGroupWithoutDetail());
			$lf->setFieldSelectorLogExp(lxInG(lxIn(fs('id'),$filledGroupIds)));

			$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTree, $lf);

			$elementPList->setGroupPList($groupList);
			$groupPTree->visitInDepth($elementPList);
		}

		$elementPList->saveFile();

		$this->getWigiiExecutor()->throwEvent()->exportElements(PWithModuleWithElementPList::createInstance($p, $exec->getCrtModule(), $elementPList));

		$sessAS->clearData($this->getRecord(), "exportRecordWigiiBag");

		exit();
	}

	protected function doOnStart($p, $exec){

	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$this->getTrm()->setState($this->getState());

		$state = $this->getState();
		if($state == "start"){
			$this->doOnStart($p, $exec);
		}
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		//display the title
		$this->getTrm()->displayRemainingForms("excelGrouping");

		//display the export format icons
		?><div class="field" style="width: 100%; max-width:<?=$this->getTotalWidth();?>px;" ><?
		$first = true; //we select by default the first option
		$nbOptions = count($this->getRecord()->getFieldList()->getField("excelGrouping")->getXml()->attribute);
		foreach($this->getRecord()->getFieldList()->getField("excelGrouping")->getXml()->attribute as $format){
				$margin = ($this->getTotalWidth()-5-($nbOptions*100))/($nbOptions+1);
				?><div class="label <?=($first ? "S" : "");?>" style="cursor:pointer;width: 100%; max-width:100px;margin-left:<?=$margin;?>px;text-align:center;"<?
					?>onmouseover="showHelp(this, '<?=$transS->h($p, $format."IconHelp");?>');" <?
					?>onmouseout="hideHelp();" <?
					?>onclick="$('#<?=$this->getFormId();?>_excelGrouping_value_select').val('<?=$format;?>'); $(this).parent().find('.S').removeClass('S');$(this).addClass('S');" <?
						?>><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/mime-types/office-ms-".$format.".png";?>" /><?
						?><div style="font-weight:bold;"><?
							echo $transS->t($p, $format."IconTitle");
						?></div><?
				?></div><?
				if($first) $first = false;
		}
		?></div><?

		$exec->addJsCode("$('#".$this->getFormId()."__excelGrouping').hide();");

		//display the remaining field
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "exportElementToExcel"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

		$exec->addJsCode("$('#".$this->getFormId()."').parents('.ui-dialog').find('button:last').focus();");
	}
}



