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
 * Created on 11 january 2011
 * by LWR
 */
class ExportExcelCalendarFormExecutor extends ExportFormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$year = $this->getRecord()->getFieldValue("excelCalendarYear");
		if($year < 1900 || $year > 3000){
			$this->addErrorToField($transS->t($p, "invalidYear"), "excelCalendarYear");
		}
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
		$exportLC->getFieldSortingKeyList()->reset();
		//TimeRanges case
		if((string)$Axml->parameters["periodFieldname"]){
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["periodFieldname"], "begDate", true);
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["periodFieldname"], "begTime", true);
		//Dates case
		} else {
			$exportLC->getFieldSortingKeyList()->addFieldSortingKey((string)$Axml->parameters["dateFieldname"], "value", true);
		}

		//loop through the groupTree to make export per group
		switch($this->getRecord()->getFieldValue("excelCalendarTemplate")){
			case "excelCalendarAnnual":
				$elementPList = ElementPListExportExcelYearCalendar::createInstance($this->getWigiiExecutor(), $exportLC);
				$elementPList->setP($p);
				//TimeRanges case
				if((string)$Axml->parameters["periodFieldname"]){
					$elementPList->setYear($this->getRecord()->getFieldValue("excelCalendarYear"), (string)$Axml->parameters["periodFieldname"], true);
				//Dates case
				} else {
					$elementPList->setYear($this->getRecord()->getFieldValue("excelCalendarYear"), (string)$Axml->parameters["dateFieldname"], false);
				}

				$elementPList->setTitle(CLIENT_NAME." ".$this->getRecord()->getFieldValue("excelCalendarYear"));
				$elementPList->setSubjectField((string)$Axml->parameters["subjectFieldname"]);
				$elementPList->setLocationField((string)$Axml->parameters["locationFieldname"]);
				$elementPList->setPostLocationField((string)$Axml->parameters["postLocationFieldname"]);
				$elementPList->setLabelField((string)$Axml->parameters["labelFieldname"]);
				break;
			default:
				throw new ServiceException("unknown excelGrouping option: ".$this->getRecord()->getFieldValue("excelGrouping"), ServiceException::INVALID_ARGUMENT);
		}

		$elementPList->actOnBeforeAddElementP();
		$elementPList->setCrtWigiiNamespace($exec->getCrtWigiiNamespace());
		$nbRow = $elS->getSelectedElementsInGroups($p, $exportLC->getGroupLogExp(), $elementPList, $exportLC);
		$elementPList->actOnFinishAddElementP($nbRow);

		$elementPList->saveFile();

		$this->getWigiiExecutor()->throwEvent()->exportElements(PWithModuleWithElementPList::createInstance($p, $exec->getCrtModule(), $elementPList));

		$sessAS->clearData($this->getRecord(), "exportRecordWigiiBag");

		exit();
	}

	protected function doOnStart($p, $exec){
		$this->getRecord()->setFieldValue((int)date("Y"), "excelCalendarYear");
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
		$this->getTrm()->displayRemainingForms("excelCalendarTemplate");

		//display the export format icons
		?><div class="field" style="width:<?=$this->getTotalWidth();?>px;" ><?
		$first = true; //we select by default the first option
		$nbOptions = count($this->getRecord()->getFieldList()->getField("excelCalendarTemplate")->getXml()->attribute);
		foreach($this->getRecord()->getFieldList()->getField("excelCalendarTemplate")->getXml()->attribute as $format){
				$margin = ($this->getTotalWidth()-5-($nbOptions*100))/($nbOptions+1);
				?><div class="label <?=($first ? "S" : "");?>" style="cursor:pointer;width:100px;margin-left:<?=$margin;?>px;text-align:center;"<?
					?>onmouseover="showHelp(this, '<?=$transS->h($p, $format."IconHelp");?>');" <?
					?>onmouseout="hideHelp();" <?
					?>onclick="$('#<?=$this->getFormId();?>_excelCalendarTemplate_value_select').val('<?=$format;?>'); $(this).parent().find('.S').removeClass('S');$(this).addClass('S');" <?
						?>><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/mimetypes/x-office-calendar.png";?>" /><?
						?><div style="font-weight:bold;"><?
							echo $transS->t($p, $format."IconTitle");
						?></div><?
				?></div><?
				if($first) $first = false;
		}
		?></div><?

		$exec->addJsCode("$('#".$this->getFormId()."__excelCalendarTemplate').hide();");

		//display the remaining field
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "exportElementToExcelCalendar"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		$exec->addJsCode("$('#".$this->getFormId()."').parents('.ui-dialog').find('button:last').focus();");
	}
}



