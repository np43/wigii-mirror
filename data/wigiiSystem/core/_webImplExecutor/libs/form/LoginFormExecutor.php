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
 * Created on 15 sept. 09
 * by LWR
 */
class LoginFormExecutor extends FormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		//on n'essaie même pas de se loguer s'il y a déjà des erreurs dans le form.

		if($this->hasError()) return;

		$transS = ServiceProvider::getTranslationService();
		$authS = ServiceProvider::getAuthenticationService();

		$username = $this->getRecord()->getFieldValue("username");
		$pwd = $this->getRecord()->getFieldValue("password");
		$loginAsPublic = $this->getRecord()->getFieldValue("loginAsPublic");
		try {
			// logs out any automatic login (public user) before login in as the defined user. 
			if(!$authS->isMainPrincipalMinimal()) $authS->logout();
			
			if($loginAsPublic) {
				$authS->loginAsPublic($p->getWigiiNamespace()->getClient()->getClientName());
			}
			else {
				$authS->login($username, $pwd, $p->getWigiiNamespace()->getClient()->getClientName());
			}			
		} catch (AuthenticationServiceException $authE){
			switch($authE->getCode()){
				case AuthenticationServiceException::FORBIDDEN:
					$this->addErrorToField($transS->h($p, "forbiddenUser"), "username");
					break;
				case AuthenticationServiceException::INVALID_PASSWORD_MORE_THAN_2_TIMES:
					$this->setProtectWithCaptcha(true);
					$this->addErrorToField($transS->h($p, "invalidPassword"), "password");
					break;
				case AuthenticationServiceException::WAIT:
					$this->setProtectWithCaptcha(true);
					$this->addErrorToField($transS->h($p, "toManyLoginFailure"), "username");
					break;
				case AuthenticationServiceException::POP3_AUTHENTICATION_SERVER_CONNECTION_PROBLEM:
					$this->addErrorToField($transS->h($p, "pop3ServerConnectionProblem"), "username");
					break;
				case AuthenticationServiceException::POP3_AUTHENTICATION_UNKNOWN_USERNAME:
					$this->addErrorToField($transS->h($p, "invalidUsernameOnPop3Server"), "username");
					break;
				case AuthenticationServiceException::INVALID_USERNAME:
					$this->addErrorToField($transS->h($p, "invalidUsername"), "username");
					break;
				case AuthenticationServiceException::INVALID_PASSWORD:
					$this->addErrorToField($transS->h($p, "invalidPassword"), "password");
					break;
				default:
					throw $authE;
			}
		}

		$p = $authS->getMainPrincipal();
		if($p->passwordExpired() && !$p->canModifyRealUserPassword()){
			$this->addErrorToField($transS->h($p, "userExpired"), "username");
			$authS->logout();
			$p = $authS->getMainPrincipal();
		}
	}

	protected function actOnCheckedRecord($p, $exec) {
		//if the login passed, we need to change the value of the $p object
		$authS = ServiceProvider::getAuthenticationService();
		$userAS = ServiceProvider::getUserAdminService();
		$p = $authS->getMainPrincipal();
		$transS = ServiceProvider::getTranslationService();

		$userAS->calculateAllMergedRoles($p);
		$roleList = $this->getWigiiExecutor()->storeAdminAndCalculatedRoleIdsInSession($p);

		if($roleList->isEmpty() && $p->getModuleAccess()==null) {
			//if no role, then login failed
			echo '<div class="fieldError" style="width:'.((int)$this->getFormRenderer()->getTotalWidth()).'px;">'.$transS->t($p, "noModuleAccessDefined").'</div><div class="clear"></div>';
			$this->addStateError();
			$authS->logout();
			$p = $authS->getMainPrincipal();
			return;
		}

		$this->getWigiiExecutor()->throwEvent()->login(PWithUser::createInstance($p, $p->getAttachedUser()));

		if(!$p->isRealUserPublic()) {
			if($this->getRecord()->getFieldValue("stayConnected")){
				$exec->addJsCode($authS->getJsCodeToSetWigiiCredentialCookie($p));
			} else {
				$exec->addJsCode($authS->getJsCodeToUnsetWigiiCredentialCookie($p));
			}
		}

		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$exec->addJsCode("$.unblockUI({ });");

		//select last language
		$lastLanguage = $p->getValueInGeneralContext("language");
		if($lastLanguage != null) ServiceProvider::getTranslationService()->setLanguage($lastLanguage);

		if (!$this->isDialog() && $_SESSION["RemainingUpdates"] != null) {
			$request = $_SESSION["RemainingUpdates"];
			foreach ($request as $req) {
				$exec->addRequests($req);
			}
			$_SESSION["RemainingUpdates"] = null;
		} else {
			if($this->isDialog()) {
				$exec->cleanRemainingRequest();
				$exec->addJsCode('self.location.reload();');
			}
			else {				
				$exec->addRequestOnFragment();
				if($exec->getRemainingRequests() == null){
					$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/":'').WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
				}
			}
				//$exec->addJsCode("self.location.reload();");
	//			//all the logic and checks are done in the navigate process
	//			$exec->addRequests("mainDiv/".WigiiNamespace::EMPTY_NAMESPACE_URL."/".Module::HOME_MODULE."/start");
		}
	}

	protected function doRenderForm($p, $exec){

		$configS = $this->getWigiiExecutor()->getConfigurationContext();

		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		//echo '<div class="field" style="color:#888;font-weight:bold;font-size:14px;padding-bottom:15px;">'.$this->getTrm()->t("connectToWigii").':</div>';

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		//if in the dialog
		if($exec->getIdAnswer() =="elementDialog"){
			$transS = ServiceProvider::getTranslationService();
			$this->getWigiiExecutor()->openAsDialogForm(
				$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
				'$("form", this).submit();', $transS->t($p, "login"),
				$transS->t($p, "ok"), $transS->t($p, "cancel"));
		} else {
			//format login form to fit login speciality

			$companyColor = $configS->getParameter($p, null, "companyColor");
			$rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");
			if(!$companyColor) $rCompanyColor = "#3E4552";
			if(!$rCompanyColor) $rCompanyColor = "#fff";

			$authS = ServiceProvider::getAuthenticationService();
			$globalContactEmail = $configS->getParameter($p, null, "globalContactEmail");

			//add listener on ENTER in login form and on TAB on username
			$exec->addJsCode("" .
				"$('#login_form_username_value_text').keydown(function(e){ if (e.keyCode == '9') { $('#login_form_password_value_password').focus().select(); e.preventDefault(); }});" .
				"$('#login_form_password_value_password').keydown(function(e){ if (e.keyCode == '9' && e.shiftKey) { $('#login_form_username_value_text').focus().select(); e.preventDefault(); }});" .
				"");
			//rearrange
			if($_POST["action"]==null && !$authS->isPublicAccessEnabledForClient($p->getWigiiNamespace()->getClient()->getClientName())){ //don't rearrange on error
				$exec->addJsCode("" .
					"$('#loginForm #login_form__username').after($('#loginForm #login_form__stayConnected'));" .
					"$('#login_form__stayConnected .value').width(30).after($('#login_form__stayConnected .label'));" .
					"$('#loginForm #login_form__stayConnected').width('auto').find('.label').width('auto');" .
					"$('#loginForm #login_form__password').css('clear','left').after($('#loginForm button'));" .
					"$('#loginForm button').css('float','left');" .
					"$('#loginForm div.publicFormBorder').remove();" .
					"");
			} else {
				$exec->addJsCode("" .
					"$('#loginForm').width('100%');" .
					"$('#login_form').width(".$this->getTotalWidth().");" .
					//CWE 03.02.2016: keep errors in red instead of rCompanyColor "$('#loginForm .label, #loginForm a, #loginForm .fieldError').css('color', '$rCompanyColor');" .
					"$('#loginForm .label, #loginForm a').css('color', '$rCompanyColor');" .
					"$('#loginForm .fieldError').css('font-weight','bold').css('color','$rCompanyColor').css('border-width','2px').css('border-bottom-style','solid').css('border-color', 'red');" .
					"");
			}

			//allow both way to write hideIntroductionTextAndGlobalEmailonLoginPage and hideIntroductionTextAndGlobalEmailOnLoginPage
			if($configS->getParameter($p, null, "hideIntroductionTextAndGlobalEmailonLoginPage")!="1" && $configS->getParameter($p, null, "hideIntroductionTextAndGlobalEmailOnLoginPage")!="1"){
				$exec->addJsCode("$('#login_form').css('float','left').after('<div style=\"float:left;margin:5px 10px 0px 50px;\">".$this->getTrm()->h('wigiiLoginIntroductionText')."<a href=\"mailto:".$globalContactEmail."\">".$globalContactEmail."</a></div>');");
			}
			$this->getTrm()->put('<div class="clear"></div>');
		}

		$exec->addJsCode(" if($('#".$this->getFormId()." :input:first').val()!=''){ $('#".$this->getFormId()." #login_form_password_value_password').focus(); } else $('#".$this->getFormId()." :input:first').focus(); ");
		$exec->addJsCode("openItems = null;"); //if the login form is displayed then the openItems make no cense. so we reset them (this prevent the question force to quit if changing language on first page two times consequently)

		$exec->addJsCode("$('#login_form').find('.ok').unbind('click').bind('click', function(e){ $('#login_form').submit(); e.stopPropagation(); e.preventDefault(); });");
	}

}



