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
 * Created on 28 February 2011
 * by LWR
 */
class AddIndicatorFormExecutor extends FormExecutor {
	
	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AddIndicatorFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AddIndicatorFormExecutor");
		}
		return $this->_executionSink;
	}
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		
		$rec = $this->getRecord();
		
		$p->setValueInRoleContext("indicators_areShown_".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '_' . $exec->getCrtModule()->getModuleUrl(), true);
		
		$indicatorList = $this->getWigiiExecutor()->getIndicatorList($p, $exec);
		
		$fs = explode("/", $rec->getFieldValue("indicator_field"));
		$fs = FieldSelector::createInstance($fs[0], $fs[1]);
		
		$availableFunction = Indicator::getAvailableFunctions();
		$label = $rec->getFieldValue("indicator_label");
		
		if($fs->isElementAttributeSelector()){
			if($label == null){
				$label = $transS->t($p, $fs->getSubFieldName());
				$label .= " (";
				$label .= $transS->t($p, $availableFunction[$rec->getFieldValue("indicator_function")]);
				if($rec->getFieldValue("indicator_isRecursive")) $label .= " R";
				$label .= ")";
				$rec->setFieldValue($label, "indicator_label");
			}
		} else {
			$fl = FieldListArrayImpl::createInstance(false, true);
			$configS->getFields($p, $exec->getCrtModule(), null, $fl);
			$field = $fl->getField($fs->getFieldName());
			if($label == null){
				$label = $transS->t($p, $field->getFieldName(), $field->getXml());
				$label .= " (";
				$label .= $transS->t($p, $availableFunction[$rec->getFieldValue("indicator_function")]);
				if($rec->getFieldValue("indicator_isRecursive")) $label .= " R";
				$label .= ")";
				$rec->setFieldValue($label, "indicator_label");
			}
		}
		$indicatorList->addIndicator($fs, ($fs->isElementAttributeSelector() ? null : $field->getDataType()), $rec->getFieldValue("indicator_function"), $rec->getFieldValue("indicator_label"), null, $rec->getFieldValue("indicator_isRecursive"));
		
		$this->getWigiiExecutor()->serializeIndicatorsInContext($p, $exec, $indicatorList);
		//persist context in DB;
		$this->getWigiiExecutor()->persistMainPrincipalSessionContext($p, $exec);
		
		//display the indicators to add the new one
		$exec->addRequests('indicators/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl().'/'.$exec->getCrtModule()->getModuleUrl().'/display/indicators');
		$exec->addJsCode("invalidCache('moduleView'); resize_elementList();");
		
		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
	}
	
	protected function doSpecificCheck($p, $exec){
		try {
			$rec = $this->getRecord();
			$fs = explode("/", $rec->getFieldValue("indicator_field"));
			$fs = FieldSelector::createInstance($fs[0], $fs[1]);
			if($fs->isElementAttributeSelector()){
				$ind = Indicator::createInstance($fs, null, $rec->getFieldValue("indicator_function"), $rec->getFieldValue("indicator_label"), null, $rec->getFieldValue("indicator_isRecursive"));
			} else {
				$configS = $this->getWigiiExecutor()->getConfigurationContext();
				$fl = FieldListArrayImpl::createInstance(false, true);
				$configS->getFields($p, $exec->getCrtModule(), null, $fl);
				$field = $fl->getField($fs->getFieldName());
				$ind = Indicator::createInstance($fs, $field->getDataType(), $rec->getFieldValue("indicator_function"), $rec->getFieldValue("indicator_label"), null, $rec->getFieldValue("indicator_isRecursive"));
			}
		} catch (ServiceException $se){
			if($se->getCode() == ServiceException::INVALID_ARGUMENT){
				$transS = ServiceProvider::getTranslationService();
				$this->addErrorToField($transS->h($p, "invalidFunctionForSelectedField"), "indicator_function"); 
			} else throw $se;
		}
		
	}
	
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		
		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());
		
		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());
		
		$this->getTrm()->displayRemainingForms();
		
		$this->getTrm()->getCaptchaHTML($this);
		
		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());
		
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "addIndicator"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"));
		
		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



