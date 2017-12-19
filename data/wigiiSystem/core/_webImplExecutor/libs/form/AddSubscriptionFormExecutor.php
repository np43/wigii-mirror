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
class AddSubscriptionFormExecutor extends AddElementFormExecutor {

	private $groupP;
	protected function getGroupP(){
		return $this->groupP;
	}
	protected function setGroupP($var){
		$this->groupP = $var;
	}

	private $subscriptionR;
	protected function getSubR(){
		return $this->subscriptionR;
	}
	protected function setSubR($var){
		$this->subscriptionR = $var;
	}

//	private $language;
//	protected function setLanguage($var){ $this->language = $var; }
//	protected function getLanguage(){ return $this->language; }

	public static function createInstance($wigiiExecutor, $groupP, $subscriptionR, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setSubR($subscriptionR);
		$fe->setGroupP($groupP);
		//$fe->setLanguage($language);
		return $fe;
	}

	protected function setState($state){
		switch($state){
			case "closed":
				$this->addStateError(); //this state cannot allow to end the loop
				break;
			case "start":
				break;
//			case "confirm": //this will provide a again check as after a start
//				break;
			case "check":
				break;
			case "persistAndNotify":
				break;
			case "end":
				break;
			default: throw FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function goToNextState(){
		switch($this->getState()){
			case "closed": break; //stay on closed
			case "start": $this->setState("check"); break;
			case "check": $this->setState("check"); break;
//23.3.12 disable confirm step
//			case "check": $this->setState("check"); break;
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
		if($this->hasError() && $this->getState()!="closed"){
			$this->setState("check");
		}
//23.3.12 disable confirm step
//		if(!$this->hasError() && $this->getState()=="check"){
//			$this->setState("confirm");
//			$this->addStateError();
//		}
		if(!$this->hasError() && $this->getState()=="check"){
			$this->setState("persistAndNotify");
		}
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();

		$subR = $this->getSubR();

		$emailValue = null;
		$emailFieldName = $this->getEmailField($p, $exec);
		if($emailFieldName) $emailValue = $this->getRecord()->getFieldValue($emailFieldName);

		$emailCustomBody = $subR->getFieldValue("subscriptionConfEmailText");
		$emailCustomBody = $emailCustomBody[$transS->getLanguage()];

		//if access is provided in the email message, then open access limited to subscriptionPeriodEndDate
		if(	strpos($emailCustomBody, '$access$')!==false){
			if($subR->getFieldValue("subscriptionPeriod", "endDate")){
				$this->getRecord()->setFieldValue(strtotime($subR->getFieldValue("subscriptionPeriod", "endDate")." 23:59:00"), $emailFieldName, "externalAccessEndDate");
			}
			$this->getRecord()->setFieldValue(Emails::EXTERNAL_ACCESS_EDIT, $emailFieldName, "externalAccessLevel");
			$this->getRecord()->setFieldValue($this->getGroupP()->getId(), $emailFieldName, "externalConfigGroup");
		}

		parent::actOnCheckedRecord($p, $exec);

		//send a confirmation email to the subscription
		if($emailValue && $emailCustomBody){
			$emailS = $this->getWigiiExecutor()->getEmailService();
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
				if((string)$emailFieldXml["label1"]) $labelConstruction[] = $this->formatValueForMailMerge((string)$emailFieldXml["label1"]);
				if((string)$emailFieldXml["label2"]) $labelConstruction[] = $this->formatValueForMailMerge((string)$emailFieldXml["label2"]);
				if((string)$emailFieldXml["label3"]) $labelConstruction[] = $this->formatValueForMailMerge((string)$emailFieldXml["label3"]);
				$crtLabel = implode(((string)$emailFieldXml["labelSep"] ? (string)$emailFieldXml["labelSep"] : " "), $labelConstruction);
			}
			if($emailFieldXml["other1"]!=""){
				$crtOther1 = $this->formatValueForMailMerge((string)$emailFieldXml["other1"]);
			}
			if($emailFieldXml["other2"]!=""){
				$crtOther2 = $this->formatValueForMailMerge((string)$emailFieldXml["other2"]);
			}
			if($emailFieldXml["other3"]!=""){
				$crtOther3 = $this->formatValueForMailMerge((string)$emailFieldXml["other3"]);
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
		}

		if($_POST['$$specialMacro$$']=="macro"){
			echo "Ok";
			exit();
		}
		$this->goToNextState(); //go to end state, and recall the doRenderForm for ending message
		$this->resetAdditionalText();
	}

    /**
     * This function format the value used in mailmerge
     *
     * @param $fieldName
     * @return mixed
     */
	private function formatValueForMailMerge($fieldName) {
	    // Get Field object from Field name
        $field = $this->getRecord()->getFieldList()->getField($fieldName);
        $typeName = $field->getDataType()->getDataTypeName();

        // Check data type attached to Field then return either the formatted value or the value if it is another datatype
        switch ($typeName){
            case "Attributs":
                return Attributs::formatDisplay($this->getRecord()->getFieldValue($fieldName), $field);
                break;
            case "MultipleAttributs":
                return MultipleAttributs::formatDisplay($this->getRecord()->getFieldValue($fieldName), $field);
                break;
            default:
                return $this->getRecord()->getFieldValue($fieldName);
                break;
        }
    }

	protected function getGroupIdInWhichToAdd($p, $exec){
		return$this->getGroupP()->getId();
	}

	protected function endActOnCheckedRecord($p, $exec){
		//nothing to do here
	}

	private $emailFieldName;
	protected function getEmailField($p, $exec){
		if(!isset($this->emailFieldName)){
			$this->emailFieldName = $this->getWigiiExecutor()->doesGroupHasEmailField($p, $exec, $this->getGroupP()->getGroup());
		}
		return $this->emailFieldName;
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$element = $this->getRecord();
		$state = $this->getState();
		$subR = $this->getSubR();

		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $exec->getCrtModule());
		if($isKey) $this->getRecord()->getWigiiBag()->setDisabled(false, $isKey->getName());

		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

		if($state == "start" && $exec->getCrtParameters(1)){
			$params = $exec->getCrtParameters();
			$params = array_slice($params, 2); //first option is for language definition
			//prefill values based on extra parameters defined in the URL
			//as for example: http://localhost/.../.../Contacts/newSubscription/602852/l01/first_name=Lionel/last_name=Weber
			foreach($params as $fieldDefault){
				list($fieldname, $value) = explode("=", $fieldDefault);
				list($fieldname, $subfieldname) = explode(".", $fieldname);
				$this->getRecord()->setFieldValue($value, $fieldname, $subfieldname);
			}
//			foreach($_GET as $key=>$value){
//				eput($key." ".$value);
//			}
		}
		//23.3.12 disable confirm step
//		if($state=="confirm"){
//			echo "keep"; //we keep the cureent html. Answers the parseupdateresult js function.
//
//			$html = '<p class="" style="margin:0px;">';
//			$html .= $this->getTrm()->t("newSubscriptionConfirmData");
//			$html .= "</p>";
//			//if a field is set with enableForEmailing, it will be used to send information
//			//and if a email is setup
//			if($this->getEmailField($p, $exec)){
//				//$subR->getFieldValue("subscriptionConfEmailFrom")!=null if there is an email field we send automatically an email confirmation
//				$emailValue = $this->getRecord()->getFieldValue($this->getEmailField($p, $exec));
//				if($emailValue){
//					$html .= '<p class="" style="font-weight:bold;margin:10px 0px 10px 0px;">';
//					$html .= $this->getTrm()->t("newSubscriptionConfirmDataWithEmail");
//					$html .= $emailValue;
//					$html .= "</p>";
//				} else {
//					//nothing is added to the confirmation dialog
//					//$html .= ": ".$this->getTrm()->t("emptyEmail"); //this should be mandatory, this case happens only if the email is optional
//				}
//			}
//			$exec->addJsCode("$('#confirmationDialog').html('".str_replace("'", "\'", $html)."');");
//			$this->getWigiiExecutor()->openAsConfirmationDialog2B("confirmationDialog", 350, '$("#mainDiv form input[name=\'action\']").val("persistAndNotify"); $("#mainDiv form").submit();', null, $transS->t($p, "Ok"), $transS->t($p, "Cancel"), true);
//			return;
//		}

		echo '<div class="public public'.str_replace(" ", "_", $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()).'" style="';
		if($this->getTotalWidth()) echo ' width: 100%; max-width:'.($this->getTotalWidth()+40).'px; ';
		if($this->isDialog()) echo ' float:left; ';
		echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
		echo ' " >';

		$backgroundColor = $subR->getFieldValue("subscriptionBackgroundColorCode");
		if($backgroundColor){
			if($backgroundColor[0]!="#") $backgroundColor = "#".$backgroundColor;
			$exec->addJsCode(" $('html').css('background-color','$backgroundColor'); ");
		}

		$title = $subR->getFieldValue("title");
		//take out empty titles
		foreach($title as $lang=>$val){
			if($val == null) unset($title[$lang]);
		}
		//if title is multi language:
		if(count($title)>1){
			foreach($title as $lang=>$val){
				echo '<span onclick="';
				echo 'self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/'.$lang.'/'.($exec->getCrtParameters(2) ? $exec->getCrtParameters(2).'/' : '').($exec->getCrtParameters(3) ? $exec->getCrtParameters(3).'/' : '').($exec->getCrtParameters(4) ? $exec->getCrtParameters(4).'/' : '').($exec->getCrtParameters(5) ? $exec->getCrtParameters(5).'/' : '').'\';';
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
		if($title){
			echo '<h3 style="margin-bottom:10px; margin-top:10px;" >'.$title."</h3>";
		}

		switch($state){
			case "closed":
				echo '<div class="clear"></div>';
				echo '<h4>'.$this->getTrm()->t("subscriptionClosed").'</h4>';
				//add the closing reason
				if($this->getAdditionalText()){
					echo '<p style="margin:5px 0 10px 0;">'.$this->getAdditionalText()."</p>";
				}
				//add additional info
				$subscriptionClosingMessageAddin = $subR->getFieldValue("subscriptionClosingMessageAddin");
				$subscriptionClosingMessageAddin = $subscriptionClosingMessageAddin[$transS->getLanguage()];
				if($subscriptionClosingMessageAddin){
					echo "<p>".$subscriptionClosingMessageAddin."</p>";
				}
				break;
			case "end":
				echo '<div class="clear"></div>';
				$subscriptionEndMessage = $subR->getFieldValue("subscriptionEndMessageAddin");
				$subscriptionEndMessage = $subscriptionEndMessage[$transS->getLanguage()];
				if($subscriptionEndMessage){
					echo $subscriptionEndMessage;
				} else {
					echo '<h4 style="margin-bottom:10px; margin-top:10px;">'.$this->getTrm()->t("subscriptionDone").'</h4>';
					//confirmation email explanation:
					$emailValue = null;
					$emailFieldName = $this->getEmailField($p, $exec);
					if($emailFieldName) $emailValue = $this->getRecord()->getFieldValue($emailFieldName);

					$emailCustomBody = $subR->getFieldValue("subscriptionConfEmailText");
					$emailCustomBody = $emailCustomBody[$transS->getLanguage()];
					if($emailValue && $emailCustomBody){
						echo '<p style="margin:5px 0 10px 0;">'.$this->getTrm()->t("confirmationEmailSentTextAfterSubscriptionDone")."</p>";
					}
				}
				$subscriptionProposeNewSubscriptionWhenDone = $subR->getFieldValue("subscriptionProposeNewSubscriptionWhenDone");
				if($subscriptionProposeNewSubscriptionWhenDone){
					//new subscription
					echo '<span onclick="';
					echo 'self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/'.$lang.'\';';
					echo '" class="ui-corner-all ui-widget-header ui-tabs-selected ui-state-active " style="padding:2px 7px 2px 7px; cursor:pointer; margin-right:10px; font-weight:normal; " >';
					echo $this->getTrm()->t("newSubscription");
					echo "</span>";
				}
				$backUrl = $subR->getFieldValue("subscriptionReturnUrl", "url");
				if($backUrl){
					echo '<span onclick="';
					echo 'self.location=\''.$backUrl.'\';';
					echo '" class="ui-corner-all ui-widget-header ui-tabs-selected ui-state-active " style="padding:2px 7px 2px 7px; cursor:pointer; margin-right:10px; font-weight:normal; " >';
					echo $this->getTrm()->t("back");
					echo "</span>";
				}

				if($this->getAdditionalText()){
					echo '<p style="margin:5px 0 10px 0;">'.$this->getAdditionalText()."</p>";
				}
				break;
			default:
				$introductionText = $subR->getFieldValue("introductionText");
				$introductionText = $introductionText[$transS->getLanguage()];
				if($introductionText){
					echo "<p>".$introductionText."</p>";
				}
				echo '<div class="clear"></div>';
				if($this->getAdditionalText()){
					echo '<p style="margin:5px 0 10px 0;">'.$this->getAdditionalText()."</p>";
				}

//				if($state=="confirm"){
//					echo '<p class="redFont" style="font-weight:bold;margin:5px 0 10px 0;">';
//					echo $this->getTrm()->t("newSubscriptionConfirmData");
//					//if a field is set with enableForEmailing, it will be used to send information
//					//and if a email is setup
//					if($this->getEmailField($p, $exec)){
//						//$subR->getFieldValue("subscriptionConfEmailFrom")!=null if there is an email field we send automatically an email confirmation
//						$emailValue = $this->getRecord()->getFieldValue($this->getEmailField($p, $exec));
//						echo '<br />';
//						echo $this->getTrm()->t("newSubscriptionConfirmDataWithEmail");
//						if($emailValue){
//							echo $emailValue;
//						} else {
//							echo ": ".$this->getTrm()->t("emptyEmail"); //this should be mandatory, this case happens only if the email is optional
//						}
//					}
//					echo "</p>";
//				}

				$this->getTrm()->setFormRenderer($this->getFormRenderer()->setP($p));

				$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

				$this->getTrm()->displayRemainingForms();

				$this->getTrm()->getCaptchaHTML($this);

				$backUrl = $subR->getFieldValue("subscriptionReturnUrl", "url");
				if($backUrl){
					$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $this->getTrm()->t("back"), $backUrl);
				} else {
					$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());
				}
				$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
				$this->getTrm()->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());
		}

		echo '</div>';
	}

}



