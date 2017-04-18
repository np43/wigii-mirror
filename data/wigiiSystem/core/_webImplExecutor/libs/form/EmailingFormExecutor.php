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
class EmailingFormExecutor extends FormExecutor {

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EmailingFormExecutor");
		}
		return $this->_debugLogger;
	}
	
	private $elementIds;
	protected function setElementIds($var){$this->elementIds = $var; }
	protected function getElementIds(){return $this->elementIds; }

	private $emailLabels;
	public function setEmailLabels($var){$this->emailLabels = $var; }
	protected function getEmailLabels($key=null, $emailFieldname=null){
		if($key==null) return $this->emailLabels;
		if($emailFieldname==null) return $this->emailLabels[$key];
		else{
			if($this->emailLabels[$key]==null) return null;
			return $this->emailLabels[$key][$emailFieldname];
		}
	}

	private $emails;
	public function setEmails($var){$this->emails = $var; }
	protected function getEmails($key=null, $email=null){
		if($key==null) return $this->emails;
		if($email==null) return $this->emails[$key];
		else{
			if($this->emails[$key]==null) return null;
			return $this->emails[$key][$email];
		}
	}

	private $fieldList;
	public function setFieldList($var){$this->fieldList = $var; }
	protected function getFieldList(){return $this->fieldList; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $elementIds){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setElementIds($elementIds);
		return $fe;
	}

	protected function displayEmailingMessage($p, $exec){
		$trm = $this->getTrm();
		echo $trm->formatValueFromRecord("message", "value", $this->getRecord(), false);
	}

	protected function addBody($p, $exec, $rec, $mail){

		$templatePath = $this->getWigiiExecutor()->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), $rec->getActivity());

		$this->getTrm()->reset($rec, true, true);

		//in Emailing activity, the form is defined as empty, so everything will be changed of course
		//we don't want
		$rec->getWigiiBag()->resetChanges();

		ob_start();
		include($templatePath);
		$body = ob_get_clean();

		$mail->setBodyHtml($body);
	}

	protected function setState($state){
		switch($state){
			case "start":
			case "check":
			case "sendTestEmail":
				break;
			default: throw new FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function getState(){ return $this->state; }
	protected function goToNextState(){
		switch($this->state){
			case "sendTestEmail":
			case "start":
			default:
				$this->setState("check");
		}
		return $this->state;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$emailS = $this->getWigiiExecutor()->getEmailService(); //TechnicalServiceProvider::getEmailService();

		$EmailingRec = $this->getRecord();


		$mail = $emailS->getEmailInstance();

		$mail->setFrom($EmailingRec->getFieldValue("from_email"));

		//set the Email Account of the P
		if(((string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription")!="1" || !$p->getValueInGeneralContext("email")) && $mail->getFrom()!=(string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "emailNotificationFrom")){
			$p->setValueInGeneralContext("email", $mail->getFrom());
			$this->getWigiiExecutor()->persistMainPrincipalSessionContext($p, $exec);
		}
		$exec->addJsCode(" userManageAlertEmailsInputValue = '".$mail->getFrom()."'; ");

		if($this->getState()=="sendTestEmail"){
			$mail->setSubject($transS->t($p, "sendTestEmailSubjectPrefix").": ".stripslashes($EmailingRec->getFieldValue("object")));
		} else {
			$mail->setSubject(stripslashes($EmailingRec->getFieldValue("object")));
		}

		//construct the corp message:
		$this->addBody($p, $exec, $EmailingRec, $mail);

		$this->addAttachements($p, $EmailingRec, $mail);

		//recipients is force to from_email if state is sendTestEmail in this method
		$this->processRecipients($p, $exec, $EmailingRec, $mail);

		if($this->getState()=="sendTestEmail"){
			//redisplay the form
			$this->addStateError();
		} else {
			$this->getWigiiExecutor()->throwEvent()->Emailing(PWithModuleWithElementIdsWithRecord::createInstance($p, $exec->getCrtModule(), $this->getElementIds(), $this->getRecord()));

			//important to clear the cancel stack
			$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

			$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
		}
	}

	protected function addAttachements($p, $rec, $mail){
		//look to possible attachements:
		foreach($rec->getFieldList()->getListIterator() as $field){
			if($field->getDataType() != null && $field->getDataType()->getDataTypeName() =="Files"){
				$path = $rec->getFieldValue($field->getFieldName(), "path");
				if($path != null){
					if($this->getState()=="sendTestEmail"){
						//copy the file in the uploaded files (do not move it because we need to still send it after)
						//the copy needs to have a unique name to prevent inserting double entry in the delete
						$postfix = "_".substr(time(),-6);
						if(!copy(TEMPORARYUPLOADEDFILE_path.$path, FILES_PATH."emailAttachement_".$path.$postfix)) throw new ServiceException("Error on storing the temporaryUploadedFile ".$filePath." in the Client folder:".FILES_PATH.$fileName, ServiceException::FORBIDDEN);
						//the attachement file are marked to be deleted after the email is successfully sent
						$mail->createAttachment(
							FILES_PATH."emailAttachement_".$path.$postfix, true,
							WigiiEmailMime::TYPE_OCTETSTREAM,
							WigiiEmailMime::DISPOSITION_ATTACHMENT,
							WigiiEmailMime::ENCODING_BASE64,
							stripslashes($rec->getFieldValue($field->getFieldName(), "name")).$rec->getFieldValue($field->getFieldName(), "type")
							);
					} else {
						//move the file in the uploaded files (because the email are send in asynch)
						if(!rename(TEMPORARYUPLOADEDFILE_path.$path, FILES_PATH."emailAttachement_".$path)) throw new ServiceException("Error on storing the temporaryUploadedFile ".$filePath." in the Client folder:".FILES_PATH.$fileName, ServiceException::FORBIDDEN);
						//the attachement file are marked to be deleted after the email is successfully sent
						$mail->createAttachment(
							FILES_PATH."emailAttachement_".$path, true,
							WigiiEmailMime::TYPE_OCTETSTREAM,
							WigiiEmailMime::DISPOSITION_ATTACHMENT,
							WigiiEmailMime::ENCODING_BASE64,
							stripslashes($rec->getFieldValue($field->getFieldName(), "name")).$rec->getFieldValue($field->getFieldName(), "type")
							);
					}
				}
			}
		}
	}

	protected function addRecipients($p, $rec, $mail){
		$mail->clearRecipients();
		$mail->addTo($rec->getFieldValue("from_email"));
		$emails = $rec->getFieldValue("to_email");
		//the emails are already refined in Emails::checkValues
		$emails = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $emails);

		foreach($emails as $email){
			$mail->addBcc($email);
		}
	}

	protected function processRecipients($p, $exec, $rec, $mail){
		$emailS = $this->getWigiiExecutor()->getEmailService(); //TechnicalServiceProvider::getEmailService();
		$elS = ServiceProvider::getElementService();
		if($this->getState()=="sendTestEmail"){
			//in the case sendTestEmail the to is equal to the from and no others
			$mail->clearRecipients();
			$mail->addTo($rec->getFieldValue("from_email"));
		} else {
			//add all recipients and do the send
			$this->addRecipients($p, $rec, $mail);
		}

		//$mergeData["lionel.weber@medair.org"]=array('$firstname$'=>"Lionel");
		$mergeData = null;
		$body = $mail->getBodyHtmlForDb();
		$subject = $mail->getSubjectForDb();

		if(
			strpos($subject, '$label$')!==false ||
			strpos($subject, '$other1$')!==false ||
			strpos($subject, '$other2$')!==false ||
			strpos($subject, '$other3$')!==false ||
			strpos($subject, '$email$')!==false ||
			strpos($subject, '$confirmation$')!==false ||
			strpos($subject, '$unsubscribe$')!==false ||
			strpos($subject, '$access$')!==false ||
			strpos($body, '$label$')!==false ||
			strpos($body, '$other1$')!==false ||
			strpos($body, '$other2$')!==false ||
			strpos($body, '$other3$')!==false ||
			strpos($body, '$email$')!==false ||
			strpos($body, '$confirmation$')!==false ||
			strpos($body, '$unsubscribe$')!==false ||
			strpos($body, '$access$')!==false
			){
			$mergeData = array();
			foreach($this->getEmailLabels("email") as $emailFieldName){
				foreach($this->getElementIds() as $elementId=>$val){
					$labSep = ($this->getEmailLabels("labelSep", $emailFieldName) ? $this->getEmailLabels("labelSep", $emailFieldName) : " ");
					$tempLab = "";
					$tempVal = $val[$this->getEmailLabels("label1", $emailFieldName)];
					if($tempVal){ if($tempLab){ $tempLab .= $labSep; } $tempLab .= $tempVal; }
					$tempVal = $val[$this->getEmailLabels("label2", $emailFieldName)];
					if($tempVal){ if($tempLab){ $tempLab .= $labSep; } $tempLab .= $tempVal; }
					$tempVal = $val[$this->getEmailLabels("label3", $emailFieldName)];
					if($tempVal){ if($tempLab){ $tempLab .= $labSep; } $tempLab .= $tempVal; }
					$crtEmails = $val[$emailFieldName]["value"];
					$crtEmailsArr = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $crtEmails);
					foreach($crtEmailsArr as $crtEmail) {
						$mergeData[$crtEmail]['$label$'] = $tempLab;
						$mergeData[$crtEmail]['$other1$']=$val[$this->getEmailLabels("other1", $emailFieldName)];
						$mergeData[$crtEmail]['$other2$']=$val[$this->getEmailLabels("other2", $emailFieldName)];
						$mergeData[$crtEmail]['$other3$']=$val[$this->getEmailLabels("other3", $emailFieldName)];
						$mergeData[$crtEmail]['$email$']=$crtEmails;
						$mergeData[$crtEmail]['$confirmation$']= $elS->getEmailValidationLinkFromCode($p, $val[$emailFieldName]["proofKey"]);
						$mergeData[$crtEmail]['$unsubscribe$']= $elS->getEmailUnsubscribeLinkFromCode($p, $val[$emailFieldName]["proofKey"]);
						$mergeData[$crtEmail]['$access$']= $elS->getExternalAccessLinkFromCode($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $val[$emailFieldName]["externalCode"]);
						//$this->debugLogger()->write("Access code for $crtEmail is '".$mergeData[$crtEmail]['$access$']."'");
					}
				}
			}
		}

		//if sendTestEmail we send the result to the first email found in the email list
		if($this->getState()=="sendTestEmail" && $mergeData){
			$emails = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $rec->getFieldValue("to_email"));
			$mergeData = array($mail->getFrom()=>$mergeData[reset($emails)]);
		}
		$emailS->send($p, $mail, $mergeData);
	}

	protected function doSpecificCheck($p, $exec){
	    $configS = $this->getWigiiExecutor()->getConfigurationContext();
	    $transS = ServiceProvider::getTranslationService();	    
	    $rec = $this->getRecord();
	    
	    // Medair (CWE) 04.04.2017 checks for authorized direct sender
	    if(defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) {
	        try {
	            $this->getWigiiExecutor()->getEmailService()->isEmailAuthorizedDirectSender($p,$rec->getFieldValue("from_email"),$p->getRealUsername());
	        }
	        catch(AuthorizationServiceException $ase) {
	            if($ase->getCode() == AuthorizationServiceException::NOT_ALLOWED) {
	                $this->addErrorToField($transS->t($p,'unauthorizedSender'), "from_email");
	            }
	            else throw $ase;
	        }
	    }
		
		//check that total file size are not above a certain amount
	    $size = 0;
		foreach($rec->getFieldList()->getListIterator() as $field){
			if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Files"){
				$size += (int)$rec->getFieldValue($field->getFieldName(), "size");
			}
		}

		$totalMaxAttachementSize = (int)$configS->getParameter($p, null, "totalMaxAttachementFilesize");

		if($size > $totalMaxAttachementSize){
			$this->addErrorToField($transS->t($p, "totalAttachementFilesize").formatFileSize($size).$transS->t($p, "totalAttachementFilesizeExceed").formatFileSize($totalMaxAttachementSize).'<br />'.$transS->t($p, "toAttacheBigerFilesLinkItInTheEmail"), "attachedFile0");
		}

		$body = $rec->getFieldValue("message");
		$subject = $rec->getFieldValue("object");
		//check if all emails has externalAccess if externalAccess code is defined in mail body
		if(
			strpos($subject, '$access$')!==false ||
			strpos($body, '$access$')!==false
			){
			foreach($this->getElementIds() as $elementId=>$elementEmails){
				//loop from the end to fetch the email fields
				foreach(array_reverse($elementEmails) as $element) {				
					if(!is_array($element)) continue;
					if($element["value"] && !($element["externalCode"] && $element["externalAccessLevel"])){
						$this->addErrorToField($transS->t($p, "missExternalAccessEmailPre")." ".$element["value"]." ".$transS->t($p, "missExternalAccessEmailPost"),"message");
					}
				}								
			}
		}

		if($this->getState()=="sendTestEmail" && $this->hasError()){
			//change status to check as the sendTestEmail has not been able to succeed
			$this->setState($this->goToNextState());
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$afterSendingTestEmail = false;
		if($this->getState()=="sendTestEmail"){
			$this->getTrm()->resetFieldCache(); //in the case of sendTestEmail the message field is already used in displaying in the email.
			$afterSendingTestEmail = true;
		}
		$emailLabels = $this->getEmailLabels();
		$fieldList = $this->getFieldList();

		//add a menu to update the To list if multiple fields
		if(count($this->getEmails("perFields"))>1){
			$options = "";
			$table = "emails = { all: '".implode(", ", array_keys($this->getEmails("allFields")))."'";
			$isDefaultForEmailling = null;
			foreach($this->getEmails("perFields") as $emailFieldname=>$emails){
				$options .= '<option value="'.$emailFieldname.'">';
				$fEmailXml = $fieldList->getField($emailFieldname)->getXml();
				if($fEmailXml["isDefaultForEmailing"]=="1"){
					$isDefaultForEmailling = $emailFieldname;
				}
				$options .= str_replace(array("'",'&'),array("&apos;","&amp;"),$transS->t($p, $emailFieldname, $fEmailXml));				
				$options .= '</option>';
				$table .= ", $emailFieldname : '".implode(", ", array_keys($emails))."'";
			}
			$table .= " };";
//			eput($options);
//			eput($table);
			$exec->addJsCode("" .
				"$('#Emailing_form__to_email div.label')" .
				".append('&nbsp;&nbsp;<select name=\"__emailingEmailFields\"><option value=\"all\">".$transS->h($p, "allEmailingEmailFields")."</option>".$options."</select>')." .
				"find('select').change(function(){" .
					"$table" .
					"v = $(this).val();" .
					"$('#Emailing_form_to_email_value_textarea').val(emails[v]);" .
				"}).click(function(e){ return false; e.stopPropagation();})" .
				"".($_POST["__emailingEmailFields"] ? ".val('".$_POST["__emailingEmailFields"]."')" : ($isDefaultForEmailling ? ".val('".$isDefaultForEmailling."').change()" : ".change()"))."" .
				";");
		} else if($this->getState()=="start") {
			$emails = $this->getEmails("allFields");
			if(is_array($emails)) $emails = implode(", ", array_keys($emails));
			else $emails = '';
			$this->getRecord()->setFieldValue($emails, "to_email");
		}

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, "Emailing"),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null, '{ my : "left center", at: "left+20 center" }');

		//ajout du code spécifique pour les emailings. Ce code permet de cacher les champs Files tant que ceux-ci ne sont pas rempli
		//le double __ vient du fait qu'il n'y a pas de groupe

		/**
		 * on cache tous les attached file sauf:
		 *  - le premier
		 * 	- ceux qui ont une valeur
		 * 	- et leur suivant
		 *  - on ajoute aussi le code pour découvrir les champs suivants dans le cas d'un changement
		 */
		$exec->addJsCode('' .
			"$('#".$this->getFormId()." [id^=Emailing_form__attachedFile]:not(:has(:input[value!=\"\"]))').hide();" .
			"$('#".$this->getFormId()." [id^=Emailing_form__attachedFile]:not(:has(:input[value!=\"\"])):first').show();" .
			'$("#'.$this->getFormId().' [id^=Emailing_form__attachedFile] input").change(function(){ $(this).parent().parent().next().fadeIn("normal"); }); ' .
			'');

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		//add emailLabels merging fields:
		$label = $label1 = $label2 = $label3 = $other1 = $other2 = $other3 = $confirmation = $unsubscribe = $access = null;
		$trm = $this->getWigiiExecutor()->createTrm();
		if($emailLabels["label1"]){
			foreach($emailLabels["label1"] as $emailFieldname=>$val) $label[$emailFieldname][0] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
		}
		if($emailLabels["label2"]){
			foreach($emailLabels["label2"] as $emailFieldname=>$val) $label[$emailFieldname][1] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
		}
		if($emailLabels["label3"]){
			foreach($emailLabels["label3"] as $emailFieldname=>$val) $label[$emailFieldname][2] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
		}
		$tempLabel = array();
		if($label){
			foreach($label as $emailFieldname=>$lab){
				ksort($lab);
				$tempLabel[] = implode(($emailLabels["labelSep"][$emailFieldname] ? $emailLabels["labelSep"][$emailFieldname] : " "), $lab);
			}
		}
		//remove duplicates and implode
		if($tempLabel){
			$label = implode(" / ", array_combine($tempLabel, $tempLabel));
		}

		if($emailLabels["other1"]){
			//fill other1 with val as key, to prevent duplicates in the field label if several email use the same one
			foreach($emailLabels["other1"] as $emailFieldname=>$val) $other1[$val] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
			$other1 = implode(" / ", $other1);
		}
		if($emailLabels["other2"]){
			//fill other1 with val as key, to prevent duplicates in the field label if several email use the same one
			foreach($emailLabels["other2"] as $emailFieldname=>$val) $other2[$val] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
			$other2 = implode(" / ", $other2);
		}
		if($emailLabels["other3"]){
			//fill other1 with val as key, to prevent duplicates in the field label if several email use the same one
			foreach($emailLabels["other3"] as $emailFieldname=>$val) $other3[$val] = $transS->h($p, $val, $fieldList->getField($val)->getXml());
			$other3 = implode(" / ", $other3);
		}
		//hide the confirmation mail merge and the access mail merge has there are not userfriendly
		//$confirmation = $transS->h($p, "Emails_proofKeyLink");
		//$access = $transS->h($p, "Emails_externalCodeLink");
		if($label || $other1 || $other2 || $other3 || $confirmation || $unsubscribe || $access){
			$code = '$("#'.$this->getFormId().' #Emailing_form__message>.label").append(\'';
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
			$exec->addJsCode('setListenerForEmailMerge("'.$this->getFormId().'");');
		}


		//add a button SendTestEmail:
		//the send button submit the form with as an action: sendTestEmail
		$exec->addJsCode("" .
			"$('#Emailing_form__message>div.label')" .
			".prepend('<div class=\"ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only\" style=\"float:right;\" ><span class=\"ui-button-text\">".$transS->t($p, "sendTestEmail")."</span></div>')" .
			".find('>div.ui-button')" .
			".mouseenter(function(){ $(this).addClass('ui-state-hover'); showHelp(this, '".$transS->h($p, "sendTestEmailHelp")."', 30, 'fromCenter', 200, 200, 0); })" .
			".mouseleave(function(){ $(this).removeClass('ui-state-hover'); hideHelp(); })" .
			".click(function(){" .
				"$('#".$this->getFormId()." input[name=\"action\"]').attr('value', 'sendTestEmail');" .
				"$('#".$this->getFormId()."').submit();" .
				"$('#".$this->getFormId()." input[name=\"action\"]').attr('value', 'check');" .
			"})" .
			";");

		if($afterSendingTestEmail){
			$exec->addJsCode("$('#Emailing_form__message>div.label').append('".str_replace("//", '\/\/', $this->getWigiiExecutor()->getIconForDialog("done"))."&nbsp;".$transS->h($p, "testEmailSentSuccessfullyTo").": ".$this->getTrm()->getRecord()->getFieldValue("from_email")."');");
		}
	}
}



