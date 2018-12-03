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
 * Created on 28 nov 2018
 * by LWR
 */
class ActivityFormExecutor extends FormExecutor {
	
	private $fromKeepModifyingExp = false; //flag to indicate no error but keep displaying
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl=MANDATORY_ARG){
		$ae = new self();
		$ae->setWigiiExecutor($wigiiExecutor);
		$ae->setRecord($record);
		$ae->setFormId($formId);
		$ae->setSubmitUrl($submitUrl);
		return $ae;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$activityXml = $configS->ma($p, $this->getRecord()->getModule(), $this->getRecord()->getActivity());
		
		$keepModifyingExp = (string)$activityXml["keepModifyingExp"];
		if($keepModifyingExp && $this->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $keepModifyingExp, $this->getRecord()) == '1') {
			$this->addStateError();
			$this->fromKeepModifyingExp = true;
			return;
		}
		
		$resultExp= (string)$activityXml["resultExp"];
		if($resultExp){
			$this->evaluateFuncExp($p, $exec, str2fx($resultExp), $this->getRecord());
			if($this->hasError()) return; //if resultExp add some error, then return to renderForm
		}
		
		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
	}

	protected function doSpecificCheck($p, $exec){
		$this->fromKeepModifyingExp = false;
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$activityXml = $configS->ma($p, $this->getRecord()->getModule(), $this->getRecord()->getActivity());
		
		$originalState = $this->getState();
		$this->getTrm()->setState($this->getState());

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);
		
		$submitLabel = (string)$activityXml["submitLabel"];
		if($submitLabel){
			$submitLabel = $this->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $submitLabel, $this->getRecord());
		} else {
			$submitLabel = $this->getSubmitLabel();
		}
		
		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $submitLabel, $this->isDialog());
		
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
				'$("form", this).submit();', $transS->t($p, $this->getRecord()->getActivity()->getActivityName(),$activityXml),
				$submitLabel, $transS->t($p, "cancel"), null, '{ my : "left top", at: "left+30 top+70" }');

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());
		
		$resultExp= (string)$activityXml["resultExp"];
		if((((string)$activityXml["executeResultExpOnStart"]=="1" && $originalState=="start") || $this->fromKeepModifyingExp) && $resultExp){
			$this->evaluateFuncExp($p, $exec, str2fx($resultExp), $this->getRecord());
		}
	}
}



