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
 * Created on 15 sept. 09
 * by LWR
 */
class ChangePasswordFormExecutor extends FormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setProtectWithCaptcha(true);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$oldPass = $this->getRecord()->getFieldValue("oldPassword");
		$newPass = $this->getRecord()->getFieldValue("newPassword");
		$confNewPass = $this->getRecord()->getFieldValue("confNewPassword");

		if($oldPass == $newPass) $this->addErrorToField($transS->h($p, "samePassword"), "newPassword");
		if($newPass != $confNewPass) $this->addErrorToField($transS->h($p, "passwordDifferent"), "confNewPassword");

		//on n'essaie même pas de modifier le password s'il y a des erreurs dans le formulaire.
		if($this->hasError()) return;

	}

	protected function actOnCheckedRecord($p, $exec) {

		$oldPass = $this->getRecord()->getFieldValue("oldPassword");
		$newPass = $this->getRecord()->getFieldValue("newPassword");

		$passwordWasExpired = $p->passwordExpired();

		try{
			$userAS = ServiceProvider::getUserAdminService();
			$userAS->changeOwnPassword($p, $oldPass, $newPass);
		} catch(UserAdminServiceException $e){
			if($e->getCode() == UserAdminServiceException::INVALID_PASSWORD){
				$transS = ServiceProvider::getTranslationService();
				$this->addErrorToField($transS->h($p, "invalidPassword"), "oldPassword");
				return;
			} else if($e->getCode() == UserAdminServiceException::INVALID_ARGUMENT){
				$transS = ServiceProvider::getTranslationService();
				$this->addErrorToField($transS->h($p, "invalidPassword").": ".$e->getMessage(), "newPassword");
				return;
			}
			throw $e;
		}
		$this->getWigiiExecutor()->throwEvent()->changePassword(PWithUser::createInstance($p, $p->getAttachedUser()));

		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$transS = ServiceProvider::getTranslationService();

		if($passwordWasExpired){
			//load the original request
			//mainDiv is hidden because of the close of the dialog
			//before adding the request, we need to show back the mainDiv.
			$exec->addJsCode("$('#mainDiv').show();");
			if($_SESSION["RemainingUpdates"] != null){
				$request = $_SESSION["RemainingUpdates"];
				foreach($request as $req){
					$exec->addRequests($req);
				}
				$_SESSION["RemainingUpdates"] = null;
			} else {
				$exec->addRequests("mainDiv/".WigiiNamespace::EMPTY_NAMESPACE_URL."/".Module::HOME_MODULE."/start");
			}
		} else {
			$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
		}
		$authS = ServiceProvider::getAuthenticationService();
		if($_COOKIE[$authS->getCredentialCookieName()]){
			$exec->addJsCode($authS->getJsCodeToSetWigiiCredentialCookie($p));
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		if(!$p->canModifyRealUserPassword()) throw new ServiceException("cannot modify password", ServiceException::FORBIDDEN);

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		//if password expired, then the cancel button logs you out
		if($p->passwordExpired()){
			$modal = true;
			$cancelCode = $this->getWigiiExecutor()->getJsCodeBeforeLogout($p)." update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/logout/'); $('#mainDiv').show();";
		} else {
			$modal = false;
			$cancelCode = null;
		}

		if($p->getRealUserAuthenticationMethod()!="usual"){
			$this->changePasswordOnNoneUsualAuthenticationMethod($p, $exec, $cancelCode, $modal);
		} else {
			$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

			$this->getTrm()->displayRemainingForms();

			$this->getTrm()->getCaptchaHTML($this);

			$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

			$this->getWigiiExecutor()->openAsDialogForm(
				($exec->getIsUpdating() ? $exec->getIdAnswer() : "mainDiv"), $this->getTotalWidth()+$this->getCorrectionWidth(),
				'$("form", this).submit();', ($p->passwordExpired() ? $transS->t($p, "passwordExpired") : $transS->t($p, "changeOwnPassword")),
				$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelCode, '{ my : "center", at: "center" }', null, $modal);

			$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
			
			$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());
		}
	}

	protected function changePasswordOnNoneUsualAuthenticationMethod($p, $exec, $cancelCode, $modal){
		/**
		 * WARNING, this method is probably redefine in Client implementation!!
		 * check it!
		 */
		$transS = ServiceProvider::getTranslationService();
		$changePasswordUrl = "".$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "changePasswordUrl");
		if($changePasswordUrl!="" && ($p->getRealUserAuthenticationServer() == null || $p->getRealUserAuthenticationServer() == EMAIL_defaultPop3Server)){
			$message = '<a href="'.$changePasswordUrl.'" target="_blank" >'.$transS->t($p, "clickHereToChangePassword").'</a>';
		} else {
			$message = $transS->t($p, "pleaseChangePasswordExternally").$p->getRealUserAuthenticationServer();
		}

		$this->getWigiiExecutor()->openAsMessage(
				($exec->getIsUpdating() ? $exec->getIdAnswer() : "mainDiv"), 450,
				($p->passwordExpired() ? $transS->t($p, "passwordExpired") : $transS->t($p, "changeOwnPassword")), $message,
				$cancelCode, $transS->t($p, "ok"));
	}

}



