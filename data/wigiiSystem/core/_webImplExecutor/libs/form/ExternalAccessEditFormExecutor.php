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
 * Created on 15 sept. 09
 * by LWR
 */
class ExternalAccessEditFormExecutor extends EditElementFormExecutor {

	private $externalAccessFieldname;
	public function getExternalAccessFieldname(){ return $this->externalAccessFieldname; }
	protected function setExternalAccessFieldname($var){ $this->externalAccessFieldname = $var; }

	private $externalAccessFieldXml;
	public function getExternalAccessFieldXml($param=null){
		if($param!=null) return $this->externalAccessFieldXml[$param];
		else return $this->externalAccessFieldXml;
	}
	protected function setExternalAccessFieldXml($var){ $this->externalAccessFieldXml = $var; }

	private $externalAccessLevel;
	public function getExternalAccessLevel(){ return $this->externalAccessLevel; }
	protected function setExternalAccessLevel($var){ $this->externalAccessLevel = $var; }

	private $externalAccessEndDate;
	public function getExternalAccessEndDate(){ return $this->externalAccessEndDate; }
	protected function setExternalAccessEndDate($var){ $this->externalAccessEndDate = $var; }

	private $elementP;
	public function getElementP(){ return $this->elementP; }
	protected function setElementP($var){ $this->elementP = $var; }

	private $groupSubscriptionRecord;
	public function getGroupSubscriptionRecord(){ return $this->groupSubscriptionRecord; }
	protected function setGroupSubscriptionRecord($var){ $this->groupSubscriptionRecord = $var; }

	public static function createInstance($wigiiExecutor, $elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($elementP->getDbEntity());
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setElementP($elementP);
		$fe->setExternalAccessFieldname($fieldName);
		$fe->setExternalAccessFieldXml($fieldXml);
		$fe->setExternalAccessLevel($externalAccessLevel);
		$fe->setExternalAccessEndDate($externalAccessEndDate);
		$fe->setGroupSubscriptionRecord($groupSubscriptionRecord);

		//if Email field is multiple --> field is readonly
		if($fieldXml["isMultiple"]=="1"){
			$elementP->getDbEntity()->getWigiiBag()->setReadonly(true, $fieldName);
			$elementP->getDbEntity()->getWigiiBag()->setHidden(true, $fieldName);
		}
		return $fe;
	}

	protected function setState($state){
		switch($state){
			case "start":
				break;
//06/07/2012 disable confirm stage, that is not usefull
//			case "confirm": //this will provide a again check as after a start
//				break;
			case "check":
				break;
			case "persistAndNotify":
				break;
			case "end":
				break;
			default: throw new FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}

	protected function goToNextState(){
		switch($this->getState()){
			case "start": $this->setState("check"); break;
			case "check": $this->setState("check"); break;
//06/07/2012 disable confirm stage, that is not usefull
//			case "confirm": $this->setState("persistAndNotify"); break;
			case "persistAndNotify": $this->setState("end"); $this->addStateError(); break;
			case "end":
				throw new FormExecutorException("no further state after: ".$this->getState(), FormExecutorException::INVALID_STATE);
				break;
			default: throw FormExecutorException("invalid state: ".$this->getState(), FormExecutorException::INVALID_STATE);
		}
		return $this->getState();
	}

	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		//the email field cannot be emptied
		if($this->getRecord()->getFieldValue($this->getExternalAccessFieldname())==null){
			$this->addErrorToField($transS->h($p, "compulsory_field"), $this->getExternalAccessFieldname());
		}
		if($this->getRecord()->getWigiiBag()->isChanged($this->getExternalAccessFieldname()) && $this->getExternalAccessFieldXml("isMultiple")=="1"){
			$this->addErrorToField($transS->h($p, "readonly"), $this->getExternalAccessFieldname());
		}
//06/07/2012 disable confirm stage, that is not usefull
//		if(!$this->hasError() && $this->getState()=="check"){
//			$this->setState("confirm");
//			$this->addStateError();
//		}
		if(!$this->hasError() && $this->getState()=="check"){
			$this->setState("persistAndNotify");
		}
		//bypass addMessageToNotification
	}

	protected function endActOnCheckedRecord($p, $exec){
		//nothing to do here, as it will continue in ActOnCheckedRecord
	}

	protected function getExternalAccessCode($p, $exec){
		//if the field has change then there is a new code calculated.
		if($this->getRecord()->getWigiiBag()->isChanged($this->getExternalAccessFieldname())){
			return $this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "externalCode");
		} else {
			return $exec->getCrtParameters(0);
		}
		//other wise we take the code in paramater

	}
	protected function realoadAfterCheckedRecord($p, $exec){
		//no reload. The operation done is not disaparing automaticaly, and on the click there is the link to view the new details.
	}

	protected function getBackUrl($p, $exec){
		return ''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$this->getExternalAccessCode($p, $exec).'/view/'.$exec->getCrtParameters(2).'';
	}
	protected function getUnlockUrl($p, $exec){
		return ''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$this->getExternalAccessCode($p, $exec).'/unlock/';
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
		$emailS = $this->getWigiiExecutor()->getEmailService();

		$subR = $this->getGroupSubscriptionRecord();

		$emailFieldName = $this->getExternalAccessFieldname();
		$emailValue = $this->getRecord()->getFieldValue($emailFieldName);
		$oldRecord = $this->fetchOldRecord($p, $exec, $this->getRecord()->getId());

		//if the email field has changed, then reset an edit access to this email.
		if($this->getRecord()->getWigiiBag()->isChanged($emailFieldName)){
			//reset old external params
			$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalAccessLevel"), $emailFieldName, "externalAccessLevel");
			$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalAccessEndDate"), $emailFieldName, "externalAccessEndDate");
			$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalConfigGroup"), $emailFieldName, "externalConfigGroup");
		}

		$emailCustomBody = null;
		if($subR){
			$emailCustomBody = $subR->getFieldValue("subscriptionConfEmailText");
			$emailCustomBody = $emailCustomBody[$transS->getLanguage()];
			if($emailCustomBody){
				//if access is provided in the email message, then open access limited to subscriptionPeriodEndDate
				if(	strpos($emailCustomBody, '$access$')!==false){
					if($subR->getFieldValue("subscriptionPeriod", "endDate")){
						$this->getRecord()->setFieldValue(strtotime($subR->getFieldValue("subscriptionPeriod", "endDate")." 23:59:00"), $emailFieldName, "externalAccessEndDate");
					} else {
						$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalAccessEndDate"), $emailFieldName, "externalAccessEndDate");
					}
					$this->getRecord()->setFieldValue(Emails::EXTERNAL_ACCESS_EDIT, $emailFieldName, "externalAccessLevel");
				} else {
					//if no access is provided in the email message, then close external access
					$this->getRecord()->setFieldValue(Emails::EXTERNAL_ACCESS_STOP, $emailFieldName, "externalAccessLevel");
				}
				//hidden fields are not available in the record if the email has not changed, so reload from previous email.
				if(!$this->getRecord()->getWigiiBag()->isChanged($emailFieldName)){
					$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalConfigGroup"), $emailFieldName, "externalConfigGroup");
					$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "externalCode"), $emailFieldName, "externalCode");
					$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "proofKey"), $emailFieldName, "proofKey");
					$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "proof"), $emailFieldName, "proof");
					$this->getRecord()->setFieldValue($oldRecord->getFieldValue($emailFieldName, "proofStatus"), $emailFieldName, "proofStatus");
				}
				//force the update of the hidden fields
				$this->getRecord()->getWigiiBag()->setChanged($emailFieldName);
			}
		}

		parent::actOnCheckedRecord($p, $exec);

		//send a confirmation email to the subscription
		if($subR && $emailValue && $emailCustomBody){
			$mail = $emailS->getEmailInstance();
			$mail->setFrom($subR->getFieldValue("subscriptionConfEmailFrom"));
			$subject = $subR->getFieldValue("subscriptionConfEmailSubject");
			$mail->setSubject($subject[$transS->getLanguage()]);
			$mail->addTo($emailValue);

			//do mail merge
			$emailFieldXml = $this->getRecord()->getFieldList()->getField($emailFieldName)->getXml();
			$crtLabel = $crtOther1 = $crtOther2 = $crtOther3 = null;
			if($emailFieldXml["label1"]!="" || $emailFieldXml["label2"]!="" || $emailFieldXml["label3"]!=""){
				$labelConstruction = array();
				if((string)$emailFieldXml["label1"]) $labelConstruction[] = $this->getRecord()->getFieldValue((string)$emailFieldXml["label1"]);
				if((string)$emailFieldXml["label2"]) $labelConstruction[] = $this->getRecord()->getFieldValue((string)$emailFieldXml["label2"]);
				if((string)$emailFieldXml["label3"]) $labelConstruction[] = $this->getRecord()->getFieldValue((string)$emailFieldXml["label3"]);
				$crtLabel = implode(((string)$emailFieldXml["labelSep"] ? (string)$emailFieldXml["labelSep"] : " "), $labelConstruction);
			}
			if($emailFieldXml["other1"]!=""){
				$crtOther1 = $this->getRecord()->getFieldValue((string)$emailFieldXml["other1"]);
			}
			if($emailFieldXml["other2"]!=""){
				$crtOther2 = $this->getRecord()->getFieldValue((string)$emailFieldXml["other2"]);
			}
			if($emailFieldXml["other3"]!=""){
				$crtOther3 = $this->getRecord()->getFieldValue((string)$emailFieldXml["other3"]);
			}
			$mergeData[$emailValue]['$label$'] = $crtLabel;
			$mergeData[$emailValue]['$other1$']=$crtOther1;
			$mergeData[$emailValue]['$other2$']=$crtOther2;
			$mergeData[$emailValue]['$other3$']=$crtOther3;
			$mergeData[$emailValue]['$email$']=$emailValue;
			$mergeData[$emailValue]['$confirmation$']= $elS->getEmailValidationLinkFromCode($p, $this->getRecord()->getFieldValue($emailFieldName, "proofKey"))."/".$transS->getLanguage();
			$mergeData[$emailValue]['$unsubscribe$']= $elS->getEmailUnsubscribeLinkFromCode($p, $this->getRecord()->getFieldValue($emailFieldName, "proofKey"))."/".$transS->getLanguage();
			$mergeData[$emailValue]['$access$']= $elS->getExternalAccessLinkFromCode($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $this->getRecord()->getFieldValue($emailFieldName, "externalCode")).($this->getRecord()->getFieldValue($emailFieldName, "externalAccessLevel")==Emails::EXTERNAL_ACCESS_EDIT ? "/edit" : "/view")."/".$transS->getLanguage();
			$templatePath = $this->getWigiiExecutor()->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			$body = $emailCustomBody;
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$mail->setBodyHtml($body);
			$attachFieldnameFields = array("subscriptionConfEmailAttachement1", "subscriptionConfEmailAttachement2");
			foreach($attachFieldnameFields as $attachFieldname){
				$path = $subR->getFieldValue($attachFieldname, "path");
				if($path != null){
					//in Group activity files are always stored on disk
					$mail->createAttachment(
						FILES_PATH.$path, false,
						WigiiEmailMime::TYPE_OCTETSTREAM,
						WigiiEmailMime::DISPOSITION_ATTACHMENT,
						WigiiEmailMime::ENCODING_BASE64,
						stripslashes($subR->getFieldValue($attachFieldname, "name")).$subR->getFieldValue($attachFieldname, "type")
						);
				}
			}
			$emailS->send($p, $mail, $mergeData);
		} else if($this->getRecord()->getWigiiBag()->isChanged($this->getExternalAccessFieldname())){
			//send an email to the new email value with the new edit link:
			$trm = $this->getWigiiExecutor()->createTRM();
			$trm->setForNotification(true);
			$trm->setForExternalAccess(true);
			$trm->setP($p);
			$email = $emailS->getEmailInstance();
			$email->setSubject($trm->getsubscriptionConfirmationEmailSubject());

			$body = $trm->getsubscriptionConfirmationEmailHtml(
					"<h3>".$trm->getBaseEmailSubject()."</h3><p>".$transS->t($p, "externalAccessChangeEmailIntroductionTextInValidationEmail")."</p>",
					$elS->getEmailValidationLinkFromCode($p, $this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "proofKey")),
					$this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "proofStatus"),
					$elS->getExternalAccessLinkFromCode($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $this->getExternalAccessCode($p, $exec)),
					$this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "externalAccessLevel"),
					$this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "externalAccessEndDate"));

			$templatePath = $this->getWigiiExecutor()->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$email->setBodyHtml($body);

			$this->getWigiiExecutor()->doSendEmail($p, $exec, $this->getElementP(), $this->getExternalAccessFieldname(), $email);

		}

		//remove the unload function
		$exec->addJsCode("window.onbeforeunload = null;");
		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

		if($subR){
			$this->goToNextState(); //go to end state, and recall the doRenderForm for ending message
			$this->resetAdditionalText();
		} else {
			$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
			$this->getWigiiExecutor()->openAsMessage(
				$exec->getIdAnswer(), 350,
				$transS->t($p, "operationDoneSuccessfully"), "",
				//'', $transS->t($p, "ok"), null, null, "done");
				'window.close()', $transS->t($p, "ok"), null, null, "done");

			$this->realoadAfterCheckedRecord($p, $exec);
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

		$element = $this->getRecord();
		$state = $this->getState();
		$backUrl = $this->getBackUrl($p, $exec);
		$subR = $this->getGroupSubscriptionRecord();

//20/11/2014 put back the unlock on unload and add a code to prevent the unload if clicking on a link or the download current file button
		//if the browser close without saving the element is unlocked
		//the asynch false is needed to work in this particular case
		$exec->addJsCode("" .
			"window.onbeforeunload = function() {" .
				"jQuery.ajax({ url:'".str_replace('//', '\/\/', $this->getUnlockUrl($p, $exec))."', async:false });" .
			"};" .
			//store onbeforeunload for later use
			"$(window).data('beforeunload',window.onbeforeunload);".
			//remove||re-assign onbeforeunload on hover
			"$('a, div.downloadCurrentFile').hover(function(){window.onbeforeunload=null;},function(){window.onbeforeunload=$(window).data('beforeunload');});".
			"");

//17/09/2013 remove the unlock on unload as it is found that if you download the current uploaded file or a previous version from a Files field, the unload function is trigged. This will unsecure the possible changes from other user
//		//if the browser close without saving the element is unlocked
//		//the asynch false is needed to work in this particular case
//		$exec->addJsCode("" .
//			"window.onbeforeunload = function() {" .
//				"jQuery.ajax({ url:'".str_replace('//', '\/\/', $this->getUnlockUrl($p, $exec))."', async:false });" .
//			"};" .
//			"");

//06/07/2012 disable confirm stage, that is not usefull
//		if($state=="confirm"){
//			echo "keep"; //we keep the cureent html. Answers the parseupdateresult js function.
//
//			$html = "";
//			//$html .= $this->getWigiiExecutor()->getIconForDialog("warning"); //cannot manage to pur the src image witht the http:// making the JS either comment either url \/\/ which is incorect
//			$html .= '<p class="" style="margin:0px;">';
//			$html .= $this->getTrm()->t("externalAccessConfirmData");
//			$html .= "</p>";
//
//			if($element->getWigiiBag()->isChanged($this->getExternalAccessFieldname())){
//				$addExternalAccessChangeEmailText = true;
//				//if custom email, then check that $access$ is in the body, other wise there is no sense to say the followings.
//				if($subR){
//					$emailCustomBody = $subR->getFieldValue("subscriptionConfEmailText");
//					$emailCustomBody = $emailCustomBody[$transS->getLanguage()];
//					//if access is provided in the email message, then open access limited to subscriptionPeriodEndDate
//					if(	strpos($emailCustomBody, '$access$')!==false){
//						$addExternalAccessChangeEmailText = true;
//					} else if($emailCustomBody){
//						$addExternalAccessChangeEmailText = false;
//					}
//				}
//				if($addExternalAccessChangeEmailText){
//					$html .= '<p class="" style="margin:10px 0 0 0;">';
//					$html .= $this->getTrm()->t("externalAccessChangeEmail").': <font style="font-weight:bold;color:#f00;">'.$element->getFieldValue($this->getExternalAccessFieldname(), "value").'</font>';
//					$html .= "</p>";
//				}
//			}
//
//			$exec->addJsCode("$('#confirmationDialog').html('".str_replace("'", "\'", $html)."');");
//			$this->getWigiiExecutor()->openAsConfirmationDialog2B("confirmationDialog", 350, '$("#mainDiv form input[name=\'action\']").val("persistAndNotify"); $("#mainDiv form").submit();', null, $transS->t($p, "Ok"), $transS->t($p, "Cancel"), true);
//			return;
//		}

		$this->getFormRenderer()->resetJsCodeAfterShow();

		echo '<div class="public public'.str_replace(" ", "_", $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()).'" style="';
		if($this->getTotalWidth()) echo ' width:'.$this->getTotalWidth().'px; ';
		if($this->isDialog()) echo ' float:left; ';
		echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
		echo ' " >';

		if($subR){
			$backgroundColor = $subR->getFieldValue("subscriptionBackgroundColorCode");
			if($backgroundColor){
				if($backgroundColor[0]!="#") $backgroundColor = "#".$backgroundColor;
				$exec->addJsCode(" $('html').css('background-color','$backgroundColor'); ");
			}

			$title = $subR->getFieldValue("title");
			if($title){
				//take out empty titles
				foreach($title as $lang=>$val){
					if($val == null) unset($title[$lang]);
				}
				//if title is multi language:
				if(count($title)>1){
					foreach($title as $lang=>$val){
						echo '<span onclick="';
						echo 'self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/'.$exec->getCrtParameters(1).'/'.$lang.'\';';
						echo '" class="ui-corner-all ui-widget-header ';
						if($lang==$transS->getLanguage()) echo 'ui-tabs-selected ui-state-active';
						echo ' " style="padding:2px 7px 2px 7px; cursor:pointer; margin-right:10px; ';
						if($lang==$transS->getLanguage()) echo ' font-weight:bold; ';
						else echo ' font-weight:normal; ';
						echo '" >';
						echo $transS->getVisibleLanguage($lang);
						echo "</span>";
					}
				}
				$title = $title[$transS->getLanguage()];
			}
			if($title){
				echo '<h3 style="margin-bottom:10px; margin-top:10px;" >'.$title."</h3>";
			}
		}

		if($this->getExternalAccessEndDate()){
			echo '<div style="margin-bottom:10px; margin-top:10px;" >'.$transS->t($p, "externalAccessEndDateTitle").": <font style=\"font-weight:bold;color:#f00;\">".date("d.m.Y H:i", $this->getExternalAccessEndDate())."</font></div>";
		}

		switch($state){
			case "end":
				echo '<div class="clear"></div>';
				$subscriptionEndMessage = $subR->getFieldValue("subscriptionEndMessageAddin");
				$subscriptionEndMessage = $subscriptionEndMessage[$transS->getLanguage()];
				if($subscriptionEndMessage){
					echo $subscriptionEndMessage;
				} else {
					echo '<h4 style="margin-bottom:10px; margin-top:10px;">'.$this->getTrm()->t("subscriptionDone").'</h4>';
					//confirmation email explanation:
					$emailValue = $this->getRecord()->getFieldValue($this->getExternalAccessFieldname());

					$emailCustomBody = $subR->getFieldValue("subscriptionConfEmailText");
					$emailCustomBody = $emailCustomBody[$transS->getLanguage()];
					if($emailValue && $emailCustomBody){
						echo '<p style="margin:5px 0 10px 0;">'.$this->getTrm()->t("confirmationEmailSentTextAfterSubscriptionDone")."</p>";
					}
				}
				if($this->getAdditionalText()){
					echo '<p style="margin:5px 0 10px 0;">'.$this->getAdditionalText()."</p>";
				}
				break;
			default:
				if($subR){
					$introductionText = $subR->getFieldValue("introductionText");
					$introductionText = $introductionText[$transS->getLanguage()];
					if($introductionText){
						echo "<p>".$introductionText."</p>";
					}
				}
				if($this->getAdditionalText()){
					echo '<div class="clear"></div>';
					echo '<p style="margin:5px 0 10px 0;">'.$this->getAdditionalText()."</p>";
				}
				$this->getTrm()->setFormRenderer($this->getFormRenderer()->setP($p));

				$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

				$this->getTrm()->displayRemainingForms();

				$this->getTrm()->getCaptchaHTML($this);

				$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $this->getTrm()->t("cancel"), $backUrl);

				$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

				$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

				$this->addAutoSaveJSCode($p, $exec);

		}

		echo '</div>';

	}

}



