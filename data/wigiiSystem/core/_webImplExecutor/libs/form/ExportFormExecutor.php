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
 * Created on 10 january 2011
 * by LWR
 */
abstract class ExportFormExecutor extends FormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	abstract protected function doOnStart($p, $exec);
	abstract protected function export($p, $exec, $state, $exportLC, $elementListLC, $tempExportLC);

	protected function actOnCheckedRecord($p, $exec) {
		//if parameters are define then add them to the end
		$params = $exec->getCrtParameters();
		if($params != null){
			$params = implode("/",$params);
		} else $params = "";
		$sessAS = ServiceProvider::getSessionAdminService();
		$sessAS->storeData($this->getRecord(), "exportRecordWigiiBag", $this->getRecord()->getWigiiBag());
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."'); download('".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/exportAndDownload/".$params."');");
	}

	protected function getHeader($contentType, $fileName, $isIntegrated=false){
		header('Pragma: public');
		header('Cache-Control: max-age=0');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		if(!$isIntegrated){
			header('Content-type: '.$contentType);
			header('Content-Disposition: attachment; filename="'.$fileName.'"');
		} else {
			header('Content-type: text');
			//header('Content-Disposition: inline; filename="'.$fileName.'"');
		}
		//CWE 15.01.2019 prevent cross origin iframes
		header("X-Frame-Options: SAMEORIGIN");
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

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "exportElementTo"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

	}
}



