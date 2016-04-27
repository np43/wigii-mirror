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
 * Created on 6 january 2011
 * by LWR
 */
class ExportCSVFormExecutor extends ExportFormExecutor {

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

		$contentType = 'application/vnd.ms-excel';
		$ext = "csv";
		$fileName = ($exec->getCrtWigiiNamespace()->getWigiiNamespaceName() ? $exec->getCrtWigiiNamespace()->getWigiiNamespaceName()."_" : "").$transS->t($p, $exec->getCrtModule()->getModuleUrl())."_".date('Y.m.d').".".$ext;

		$this->getHeader($contentType, $fileName);

		$configGroupPList = $exportLC->getConfigGroupList();
		if($elementListLC->getMultipleSelection()!=null){
			$exportLC->resetFetchCriteria($p, $this->getWigiiExecutor());
			$exportLC->matchSortingCriteria($elementListLC);
			//add groupList on all
			$groupList = GroupListAdvancedImpl::createInstance();
			$lf = ListFilter::createInstance();
			$lf->setFieldSelectorList(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
			$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
			$exportLC->setGroupPList($groupList->getRootGroups());
			//add the multipleSelection criterias in the LogExp
			$exportLC->addLogExpOnMultipleSelection($elementListLC->getMultipleSelection());
		} else {
			$exportLC->matchFetchCriteria($elementListLC);
		}
		//force export context to take sub folders
		if(!$exportLC->doesGroupListIncludeChildren()) $exportLC->setGroupPList($exportLC->getGroupPList(), true);
		$exportLC->setConfigGroupList($configGroupPList);

		if($this->getRecord()->getFieldValue("csvFormat")=="csvFormated"){
			$elementPList = ElementPListExportCSVPretty::createInstance($this->getWigiiExecutor(), $exportLC);
			//$exportLC->setFieldSelectorList(null);
		} else {
			//prepare fieldSelector with all fields + content for integrated files:
			$originalFieldList = FormFieldList::createInstance(FormBag::createInstance());
			$importFieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false);
			$configS->getFields($p, $exec->getCrtModule(), null, $originalFieldList);
			foreach($originalFieldList->getListIterator() as $field){
				if($field->getDataType()==null) continue;
				if($field->getDataType()->getDataTypeName()=="Files"){
					$fieldXml = $field->getXml();
					if($fieldXml["htmlArea"]=="1"){
						foreach($field->getDataType()->getXml()->children() as $subField){
							$importFieldSelectorList->addFieldSelector($field->getFieldName(), $subField->getName());
						}
					} else {
						$importFieldSelectorList->addFieldSelector($field->getFieldName());
					}
				} else {
					$importFieldSelectorList->addFieldSelector($field->getFieldName());
				}
			}
			$exportLC->setFieldSelectorList($importFieldSelectorList);
			$elementPList = ElementPListExportCSVRaw::createInstance($this->getWigiiExecutor(), $exportLC);
			if($this->getRecord()->getFieldValue("csvFormat")=="csvRaw"){
				$elementPList->ignoreSysFields(true);
			}
		}
		$elementPList->setSeparator($this->getRecord()->getFieldValue("csvSeparator"));
		$elementPList->setEncoding($this->getRecord()->getFieldValue("csvEncoding"));

		$elementPList->actOnBeforeAddElementP();
		$nbRow = $elS->getSelectedElementsInGroups($p,
			$exportLC->getGroupLogExp(),
			$elementPList,
			$exportLC);

		$elementPList->actOnFinishAddElementP($nbRow);

		$this->getWigiiExecutor()->throwEvent()->exportElements(PWithModuleWithElementPList::createInstance($p, $exec->getCrtModule(), $elementPList));

		$sessAS->clearData($this->getRecord(), "exportRecordWigiiBag");

		exit();
	}

	protected function doOnStart($p, $exec){
		$defaultSeparator = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "csvDefaultSeparator");
		if(isset($defaultSeparator)){
			$defaultSeparator = str_replace('"', '\"', $defaultSeparator);
			if($_COOKIE["cookie_form_value_".$this->getFormId()."_csvSeparator_value_text"]==null){
				$exec->addJsCode('$.cookie("cookie_form_value_'.$this->getFormId().'_csvSeparator_value_text", "'.$defaultSeparator.'", { path: "/", expires: 365, secure: '.strtolower(put(HTTPS_ON)).' });');
				$this->getRecord()->setFieldValue($defaultSeparator, "csvSeparator");
			}
		}
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
		$this->getTrm()->displayRemainingForms("csvFormat");

		//display the export format icons
		?><div class="field" style="width:<?=$this->getTotalWidth();?>px;" ><?
		$first = true; //we select by default the first option
				$nbOptions = count($this->getRecord()->getFieldList()->getField("csvFormat")->getXml()->attribute);
		foreach($this->getRecord()->getFieldList()->getField("csvFormat")->getXml()->attribute as $format){
				$margin = ($this->getTotalWidth()-(20*$nbOptions)-($nbOptions*100))/($nbOptions+1);
				?><div class="label <?=($first ? "S" : "");?>" style="cursor:pointer;width:100px;margin-left:<?=$margin;?>px;text-align:center;"<?
					?>onmouseover="showHelp(this, '<?=$transS->h($p, $format."IconHelp");?>');" <?
					?>onmouseout="hideHelp();" <?
					?>onclick="$('#<?=$this->getFormId();?>_csvFormat_value_select').val('<?=$format;?>'); $(this).parent().find('.S').removeClass('S');$(this).addClass('S');" <?
						if($format=="csvBackup"){
							?>><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/emblems/database.png";?>" /><?
						} else {
							?>><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/mime-types/text-".$format."-text.png";?>" /><?
						}
						?><div style="font-weight:bold;"><?
							echo $transS->t($p, $format."IconTitle");
						?></div><?
				?></div><?
				if($first) $first = false;
		}
		?></div><?

		$exec->addJsCode("$('#".$this->getFormId()."__csvFormat').hide();");

		//display the remaining field
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "exportElementToCSV"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

	}

}



