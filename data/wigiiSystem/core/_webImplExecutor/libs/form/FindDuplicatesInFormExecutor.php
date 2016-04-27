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
 * Created on 28 sept 2012
 * by LWR
 */
class FindDuplicatesInFormExecutor extends FormExecutor {

	private $crtGroupP; //current selected groupP
	protected function setCrtGroupP($var){ $this->crtGroupP = $var; }
	protected function getCrtGroupP(){ return $this->crtGroupP; }

	private $rootPrincipal;
	protected function setRootPrincipal($var){$this->rootPrincipal = $var; }
	protected function getRootPrincipal(){return $this->rootPrincipal; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setCrtGroupP($crtSelectedGroupP);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();

		$rec = $this->getRecord();

		//no check to do

	}

	protected function actOnCheckedRecord($p, $exec) {
		//import the List
		$elS = ServiceProvider::getElementService();
		$transS = ServiceProvider::getTranslationService();

		//group by unique data
		$rec = $this->getRecord();

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/changeGroupByToFindDuplicates/".$rec->getFieldValue("fieldKey"));
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		echo '<font style="font-style:italic;" >';
		echo $transS->t($p, "findDuplicatesInExplanation")."<br /><br />";
		echo '</font>';

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "findDuplicatesIn"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



