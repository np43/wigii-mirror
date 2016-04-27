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
 * Created on 18 sept. 13
 * by LWR
 */
class DeleteUserFormExecutor extends FormExecutor {

	private $userP;
	protected function setUserP($userP){ $this->userP = $userP; }
	protected function getUserP(){ return $this->userP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	public static function createInstance($wigiiExecutor, $userP, $record, $formId, $submitUrl, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setUserP($userP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		//no special check to do
	}

	protected function actOnCheckedRecord($p, $exec) {
		$userAS = ServiceProvider::getUserAdminService();
		$transS = ServiceProvider::getTranslationService();

		$user = $this->getUserP()->getUser();

		//delete the user and all the content associated to him
		$userAS->deleteUser($p, $user->getId());
		$this->getWigiiExecutor()->throwEvent()->deleteUser(PWithUser :: createInstance($p, $user));
		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getWigiiExecutor()->getAdminContext($p)->getSubScreen());
	}

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		if($this->getUserP()->getUser()->isRole()) return $transS->t($p, "deleteRole");
		return $transS->t($p, "deleteUser");
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$workingModule = $this->getWigiiExecutor()->getAdminContext($p)->getWorkingModule();

		$state = $this->getState();
		$this->getTrm()->setState($this->getState());

		$userEditRec = $this->getRecord();
		$user = $this->getUserP()->getUser();


		//fill values on start
		if($state=="start"){

		}

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$wigiiNamespace = $user->getWigiiNamespace()->getWigiiNamespaceName();
		?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/status/not-known.png";?>" style="float:left;margin:0px 5px 0px 0px;"/><?

		echo '<span style="line-height:1.7;">';
		if($user->isRole()) echo $transS->t($p, "areYouSureDeleteRole");
		else echo $transS->t($p, "areYouSureDeleteUser");
		echo "<br>";
		echo "<b>" . ($wigiiNamespace ? $wigiiNamespace . " : " : "") . $user->getUsername() . "</b>";
		echo "</span";

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/user/".$user->getId()."');";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $this->getDialogTitle($p, $exec),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



