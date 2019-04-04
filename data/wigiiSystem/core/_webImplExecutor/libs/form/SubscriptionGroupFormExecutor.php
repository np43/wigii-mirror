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
 * Created on 11 february 10
 * by LWR
 */
class SubscriptionGroupFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl=MANDATORY_ARG, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){

		$transS = ServiceProvider::getTranslationService();
		$rec = $this->getRecord();

		$group = $this->getGroupP()->getGroup();
		$emailField = $this->getWigiiExecutor()->doesGroupHasEmailField($p, $exec, $group);
		//if no emailField the external access is automatically disabled --> cannot send to the subscriber an access
		//and no confirmation email can be set
		if(!$emailField){
			//deprecated on 26.03.2012, this is unclear. Instead we will use the customize email message with default value containing the merge field
//			$rec->setFieldValue(null, "addinConfirmationEmail");
			$rec->getWigiiBag()->emptyFieldValue("subscriptionConfEmailFrom");
			$rec->getWigiiBag()->setFilled(false, "subscriptionConfEmailFrom");
			$rec->getWigiiBag()->emptyFieldValue("subscriptionConfEmailSubject");
			$rec->getWigiiBag()->setFilled(false, "subscriptionConfEmailSubject");
			$rec->getWigiiBag()->emptyFieldValue("subscriptionConfEmailText");
			$rec->getWigiiBag()->setFilled(false, "subscriptionConfEmailText");
			$rec->getWigiiBag()->emptyFieldValue("subscriptionConfEmailAttachement1");
			$rec->getWigiiBag()->setFilled(false, "subscriptionConfEmailAttachement1");
			$rec->getWigiiBag()->emptyFieldValue("subscriptionConfEmailAttachement2");
			$rec->getWigiiBag()->setFilled(false, "subscriptionConfEmailAttachement2");
		}

		//if no protocol seted in subscriptionReturnUrl
		$subscriptionReturnUrl = $rec->getFieldValue("subscriptionReturnUrl", "url");
		if($subscriptionReturnUrl!=null && !preg_match('#^(((ht|f)tp(s?))\://)#i', $subscriptionReturnUrl)) $rec->setFieldValue("http://".$subscriptionReturnUrl, "subscriptionReturnUrl", "url");

		//maxSubscriptionNb should be integer
		$maxSubscriptionNb = $rec->getFieldValue("maxSubscriptionNb");
		if($maxSubscriptionNb!=null && !is_numeric($maxSubscriptionNb)){
			$this->addErrorToField($transS->h($p, "maxSubscriptionNbInvalid"), "maxSubscriptionNb");
		} elseif($maxSubscriptionNb!=null) {
			$rec->setFieldValue((int)$maxSubscriptionNb, "maxSubscriptionNb");
		}

		//deprecated on 26.03.2012, this is unclear. Instead we will use the customize email message with default value containing the merge field
//		//allowExternalAccess can only be 0, 1 or 2. 1 can be only if end date
//		$addinConfirmationEmail = $rec->getFieldValue("addinConfirmationEmail");
//
//		if($addinConfirmationEmail && array_search("confEmailLimitedExternalAccess", $addinConfirmationEmail)!==false && array_search("confEmailExternalAccess", $addinConfirmationEmail)===false){
//			$addinConfirmationEmail[]="confEmailExternalAccess";
//		}
//		if($addinConfirmationEmail && array_search("confEmailLimitedExternalAccess", $addinConfirmationEmail)!==false && !$rec->getFieldValue("subscriptionPeriod", "endDate")){
//			$addinConfirmationEmail[array_search("confEmailLimitedExternalAccess", $addinConfirmationEmail)]=null;
//		}
//
//		$rec->setFieldValue($addinConfirmationEmail, "addinConfirmationEmail");

		//if one of the emailConf field is filled, then --> email from & email text is compulsory
		if(	$rec->getWigiiBag()->isFilled("subscriptionConfEmailFrom") ||
			$rec->getWigiiBag()->isFilled("subscriptionConfEmailSubject") ||
			$rec->getWigiiBag()->isFilled("subscriptionConfEmailText") ||
			$rec->getWigiiBag()->isFilled("subscriptionConfEmailAttachement1") ||
			$rec->getWigiiBag()->isFilled("subscriptionConfEmailAttachement2")
			){
			if(!$rec->getWigiiBag()->isFilled("subscriptionConfEmailFrom")){
				$this->addErrorToField($transS->h($p, "compulsoryFieldOtherwiseEmptyOtherEmailFields"), "subscriptionConfEmailFrom");
			}
			if(!$rec->getWigiiBag()->isFilled("subscriptionConfEmailText")){
				$this->addErrorToField($transS->h($p, "compulsoryFieldOtherwiseEmptyOtherEmailFields"), "subscriptionConfEmailText");
			}
		}

		$size = 0;
		$size += (int)$rec->getFieldValue("subscriptionConfEmailAttachement1", "size");
		$size += (int)$rec->getFieldValue("subscriptionConfEmailAttachement2", "size");

		$totalMaxAttachementSize = (int)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "totalMaxAttachementFilesize");

		if($size > $totalMaxAttachementSize){
			$this->addErrorToField($transS->t($p, "totalAttachementFilesize").formatFileSize($size).$transS->t($p, "totalAttachementFilesizeExceed").formatFileSize($totalMaxAttachementSize).'<br />'.$transS->t($p, "toAttacheBigerFilesLinkItInTheEmail"), "subscriptionConfEmailAttachement1");
		}

	}

	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();
		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		try{
			//add any file fields in the wigiiBag
			$oldRecord = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance($rec->getActivity()->getActivityName()), $exec->getCrtModule());
			$oldRecord->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $oldRecord->getActivity());

			$this->updateHiddenFields($p, $exec, false, $oldRecord);
			$group->getDetail()->setSubscription($rec->getWigiiBag()->exportAsSerializedArray($rec->getActivity()));
			$this->updateFilesOnDisk($p, $exec, false, $oldRecord, false);

			$groupAS->setSubscription($p, $group->getId(), $group->getDetail()->getSubscription());
			//if subscriptionConfEmailFrom is setted, add the emails to groupNotification
			$newEmails = $rec->getFieldValue("subscriptionConfEmailFrom");
			if($newEmails!=null){
				$groupAS->addToEmailNotification($p, $group->getId(), $newEmails);

				if($exec->getIdAnswer() != "confirmationDialog"){
					echo ExecutionServiceImpl::answerRequestSeparator;
					echo "confirmationDialog";
					echo ExecutionServiceImpl::answerParamSeparator;
				}
				$this->getWigiiExecutor()->openAsMessage(
					"confirmationDialog", ($this->getTotalWidth()+$this->getCorrectionWidth()),
					$this->getTrm()->t("subscriptionConfEmailFromIsAddedTitle"),
					$this->getTrm()->t("subscriptionConfEmailFromIsAddedExpl").":<br /><br /><b>".$newEmails."</b>",
					null, $this->getTrm()->t("ok"));

			}
			$exec->addJsCode(" adminGroup_crtSelectedGroup = '".$group->getId()."'; ");
		} catch(GroupAdminServiceException $e){
			throw $e;
		}

		$groupAS->unLock($p, $group);

		$this->getWigiiExecutor()->throwEvent()->updateGroup(PWithGroup::createInstance($p, $group));

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");


		if($newEmails==null){
			if($exec->getIdAnswer() != "confirmationDialog"){
				echo ExecutionServiceImpl::answerRequestSeparator;
				echo "confirmationDialog";
				echo ExecutionServiceImpl::answerParamSeparator;
			}			
			$this->getWigiiExecutor()->operationSuccessfullMessage("confirmationDialog", 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
		}

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}


	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();

		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$state = $this->getState();

		$emailField = $this->getWigiiExecutor()->doesGroupHasEmailField($p, $exec, $group);
		if(!$emailField){
			//disable the subscriptionConfEmail group
			//deprecated on 26.03.2012, this is unclear. Instead we will use the customize email message with default value containing the merge field
			//$rec->getWigiiBag()->setHidden(true, "addinConfirmationEmail");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmail");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmailFrom");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmailSubject");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmailText");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmailAttachement1");
			$rec->getWigiiBag()->setHidden(true, "subscriptionConfEmailAttachement2");
		}

		if($emailField && $state=="start" && !array_key_exists("subscriptionConfEmailText", str2array($group->getDetail()->getSubscription()))){
			if($rec->getFieldValue("subscriptionConfEmailFrom")==null){
				$rec->setFieldValue((string)$configS->getParameter($p, null, "emailNotificationFrom"), "subscriptionConfEmailFrom");
				$title = $rec->getFieldValue("title");
				$title = $title[$transS->getLanguage()];
				$rec->setFieldValue(array($transS->getLanguage()=>$this->getTrm()->getSubscriptionConfirmationEmailSubject($group->getGroupName())),"subscriptionConfEmailSubject");
				$rec->setFieldValue(array($transS->getLanguage()=>$this->getTrm()->getsubscriptionConfirmationEmailHtml($transS->t($p, "thankYouForYourSubscription"), '$confirmation$', Emails::PROOF_STATUS_NONE, null, null, null)),"subscriptionConfEmailText");
			}
		}

//		$rec->getWigiiBag()->setHelp($transS->h($p, "authenticationServerHelp"), "authenticationServer");
		$this->getTrm()->setState($state);
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "
update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');
";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

		if($emailField){
			//add mail merge options
			$fieldList = FieldListArrayImpl::createInstance(true, true);
			$configS->getGroupFields($p, $group, null, $fieldList);

			$emailFieldXml = $fieldList->getField($emailField)->getXml();
			$label = $label1 = $label2 = $label3 = $other1 = $other2 = $other3 = null;

			if((string)$emailFieldXml["label1"]) $label1 = $transS->h($p, (string)$emailFieldXml["label1"], $fieldList->getField((string)$emailFieldXml["label1"])->getXml());
			if((string)$emailFieldXml["label2"]) $label2 = $transS->h($p, (string)$emailFieldXml["label2"], $fieldList->getField((string)$emailFieldXml["label2"])->getXml());
			if((string)$emailFieldXml["label3"]) $label3 = $transS->h($p, (string)$emailFieldXml["label3"], $fieldList->getField((string)$emailFieldXml["label3"])->getXml());
			$label = implode(((string)$emailFieldXml["labelSep"] ? (string)$emailFieldXml["labelSep"] : " "), array($label1, $label2, $label3));
			if((string)$emailFieldXml["other1"]) $other1 = $transS->h($p, (string)$emailFieldXml["other1"], $fieldList->getField((string)$emailFieldXml["other1"])->getXml());
			if((string)$emailFieldXml["other2"]) $other2 = $transS->h($p, (string)$emailFieldXml["other2"], $fieldList->getField((string)$emailFieldXml["other2"])->getXml());
			if((string)$emailFieldXml["other3"]) $other3 = $transS->h($p, (string)$emailFieldXml["other3"], $fieldList->getField((string)$emailFieldXml["other3"])->getXml());
			$confirmation = $transS->h($p, "Emails_proofKeyLink");
			$unsubscribe = $transS->h($p, "Emails_proofKeyUnsubscribeLink");
			$access = $transS->h($p, "Emails_externalCodeLink");
			$code = '$("#groupSubscription_form__subscriptionConfEmailText>.label").append(\'';
			$code .= '<div class="R">'.$transS->h($p, "confirmationLinkIsMandatoryForLegalReason")."</div>";
			$code .= '<div style="margin:3px 0px 3px 0px;">'.$transS->h($p, "availableMailMergeField").":&nbsp;";
			if($label) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$label$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $label)."&nbsp;</span> ";
			if($other1) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$other1$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $other1)."&nbsp;</span> ";
			if($other2) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$other2$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $other2)."&nbsp;</span> ";
			if($other3) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$other3$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $other3)."&nbsp;</span> ";
			if($confirmation) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$confirmation$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $confirmation)."&nbsp;</span> ";
			if($unsubscribe) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$unsubscribe$'.'">&nbsp;'.str_replace(" ", "&nbsp;", $unsubscribe)."&nbsp;</span> ";
			if($access) $code .= '<span class="H G SBIB ui-corner-all" title="'.'$access$'.'" onmouseover="showHelp(this, \\\''.str_replace("'", "\\\'", $transS->h($p, "externalAccessMailMerge_help")).'\\\',20, \\\'fromCenter\\\', 200, 200, 0);" onmouseout="hideHelp();">&nbsp;'.str_replace(" ", "&nbsp;", $access)."&nbsp;</span> ";
			$code .= '</div>\');';
			$exec->addJsCode($code);
			$exec->addJsCode('setListenerForConfirmationEmailMerge("groupSubscription_form");');
		}
	}
}



