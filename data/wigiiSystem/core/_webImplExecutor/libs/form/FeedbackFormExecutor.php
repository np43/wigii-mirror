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
class FeedbackFormExecutor extends FormExecutor {

	private $rootPrincipal;
	public function setRootPrincipal($rootPrincipal){ $this->rootPrincipal = $rootPrincipal; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }

	public static function createInstance($rootPrincipal, $wigiiExecutor, $record, $formId, $submitUrl=MANDATORY_ARG){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		return $fe;
	}

	protected function getButtonViewElement($p, $exec, $rec){
		$wigiiNamespace = $exec->getCrtWigiiNamespace();
		$module = $exec->getCrtModule(); //$rec->getModule();
		$label = $this->getTrm()->t("viewElement");
		$result = "";
		$result .= '<a href="'.ServiceProvider::getElementService()->getUrlForElement($wigiiNamespace, $module, $rec).'" target="_blank" style="color:#000;text-decoration:none;">';
		$result .= $label;
		$result .= '</a>';
		return $result;
	}
	protected function displayButtonViewElement($p, $exec){
		$rec = $this->getRecord()->getAttachedRecord();
		$result = "";
		$result .= '<div style="text-align:center;border-top:1px dotted #ccc;padding-top:10px;">';
			$result .= '<span style="color:#000;background-color:#ecf3fe;border:1px solid #aac9ff;text-decoration:none;"><font style="font-size:16px;">&nbsp;</font>'.$this->getButtonViewElement($p, $exec, $rec).'<font style="font-size:16px;">&nbsp;</font></span>';
		$reuslt .= '</div>';
		echo $result;
	}
	protected function getButtonAccess($p, $exec){
		$wigiiNamespace = $exec->getCrtWigiiNamespace();
		$module = $exec->getCrtModule();
		$result = "";
		$result .= '<a href="'.SITE_ROOT."#".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl().'" target="_blank" style="color:#000;text-decoration:none;">';
		$result .= $this->getTrm()->t("accessSystem")." ".($wigiiNamespace->getWigiiNamespaceName() ? $wigiiNamespace->getWigiiNamespaceName().' - ' : "").$this->getTrm()->t($module->getModuleName());
		$result .= '</a>';
		return $result;
	}
	protected function displayButtonAccess($p, $exec){
		$result = "";
		$result .= '<div style="text-align:center;border-top:1px dotted #ccc;padding-top:10px;">';
			$result .= '<span style="color:#000;background-color:#ecf3fe;border:1px solid #aac9ff;text-decoration:none;"><font style="font-size:16px;">&nbsp;</font>'.$this->getButtonAccess($p, $exec).'<font style="font-size:16px;">&nbsp;</font></span>';
		$reuslt .= '</div>';
		echo $result;
	}

	protected function displayFeedbackTitle($p, $exec){
		$rec = $this->getRecord();
		echo $this->getFeedbackTitle($p, $exec, $rec);
	}

	protected function displayFeedbackText($p, $exec){
		$trm = $this->getTrm();
		echo $trm->formatValueFromRecord("feedback_text", "value", $this->getRecord(), false).'<p></p>';

	}
	protected function displayFeedbackRecord($p, $exec){
		$rec = $this->getRecord();

		$result = "";

		$trm = $this->getTrm();
		$trm->reset($rec);
		$trm->setOutputEnabled(false);
		if($trm->getDetailRenderer() == null){
			$trm->setDetailRenderer($this->getDetailRenderer($trm));
		}

		foreach($rec->getFieldList()->getListIterator() as $field){
			//ignore feedback_text as it is displayed in displayFeedbackText. The other info looks to the standard ignoreNotification="1"
			if($field->getFieldName()=="feedback_text")	continue;
			if(!$field->getDataType()) continue; //ignores free texts
			
			$fieldXml = $field->getXml();
			
			if($fieldXml["ignoreNotification"]=="1") continue;
			if(!$rec->getWigiiBag()->isFilled($field->getFieldName())) continue;

			$trm->displayLabel($field->getFieldName());
			$label = $trm->getHtmlAndClean();

			$result .= '<p style="padding:0px;margin:3px 0px;color:'.$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "companyColor").';">';

			if(strstr($label, "<img")!==false){
				$result .= $this->getTranslationService()->t($p, $field->getFieldName(), $field).": ";
			} else {
				$result .= $label.": ";
			}
			if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Blobs" || $field->getDataType()->getDataTypeName()=="Texts"){
				$trm->displayValue($field->getFieldName());
				$result .= "".$trm->getHtmlAndClean()."";
			} else if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Booleans"){
				$result .= $trm->formatValueFromRecord($field->getFieldName(), "value", $rec, false);
			} else {
				$trm->displayValue($field->getFieldName());
				$result .= "<b>".$trm->getHtmlAndClean()."</b>";
			}
			$result .= '</p>';
		}
		if($result) $result = '<p style="border-top:1px dotted #ccc;"></p>'.$result.'<p></p>';
		echo $result;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$emailS = $this->getWigiiExecutor()->getEmailService(); //TechnicalServiceProvider::getEmailService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$feedbackRec = $this->getRecord();

		if($feedbackRec->getAttachedRecord() != null && $configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnElement_enable") != "1")
		throw new ServiceException("Module ".$exec->getCrtModule()->getModuleName()." is not configured to send feedbacks on element.", ServiceException::FORBIDDEN);
		if($feedbackRec->getAttachedRecord() == null && $configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable") != "1")
		throw new ServiceException("Module ".$exec->getCrtModule()->getModuleName()." is not configured to send feedbacks on system.", ServiceException::FORBIDDEN);

		$mail = $emailS->getEmailInstance();

		$mail->setFrom($feedbackRec->getFieldValue("feedback_email"));

		//set the Email Account of the P
		if(((string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription")!="1" || !$p->getValueInGeneralContext("email")) && $mail->getFrom()!=(string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, null, "emailNotificationFrom")){
			$p->setValueInGeneralContext("email", $mail->getFrom());
			$this->getWigiiExecutor()->persistMainPrincipalSessionContext($p, $exec);
		}
		$exec->addJsCode(" userManageAlertEmailsInputValue = '".$mail->getFrom()."'; ");

		$this->addSubject($p, $exec, $feedbackRec, $mail);
		$this->addBody($p, $exec, $feedbackRec, $mail);
		$this->addAttachements($p, $feedbackRec, $mail);
		$this->processRecipients($p, $feedbackRec, $mail);

		if($this->getRecord()->getAttachedRecord() != null){
			$this->getWigiiExecutor()->throwEvent()->sendFeedbackOnElement(PWithElementWithRecordWithEmails::createInstance($p, $this->getRecord()->getAttachedRecord(), $this->getRecord(), $mail->getRecipients()));
		} else {
			$this->getWigiiExecutor()->throwEvent()->sendFeedback(PWithModuleWithRecordWithEmails::createInstance($p, $exec->getCrtModule(), $this->getRecord(), $mail->getRecipients()));
		}

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
	}

	private $feedbackForTranslation;
	protected function getFeedbackForTranslation($p, $rec){
		if(!isset($this->feedbackForTranslation)){
			$this->feedbackForTranslation = array();
			$transS = ServiceProvider::getTranslationService();
			$feedbackForXml = $rec->getFieldList()->getField("feedback_for")->getXml();
			$feedbackFor = $rec->getFieldValue("feedback_for");
			foreach($feedbackForXml->attribute as $attr){
				if(is_array($feedbackFor) && array_search((string)$attr, $feedbackFor) !== false
					|| (string)$attr==$feedbackFor){
					$this->feedbackForTranslation[] = $transS->t($p, (string)$attr, $attr);
				}
			}
		}
		return $this->feedbackForTranslation;
	}
	protected function addSubject($p, $exec, $rec, $mail){
		$transS = ServiceProvider::getTranslationService();

		$subject = $this->getFeedbackContext($p, $exec, $rec);

//		$subject = $transS->t($p, "feedback");
//
//		$feedbackForXml = $rec->getFieldList()->getField("feedback_for")->getXml();
//		$feedbackFor = $rec->getFieldValue("feedback_for");
//		foreach($feedbackForXml->attribute as $attr){
//			if(is_array($feedbackFor) && array_search((string)$attr, $feedbackFor) !== false
//				|| (string)$attr==$feedbackFor){
//				$subject .= " - ".$transS->t($p, (string)$attr, $attr);
//			}
//		}

		$mail->setSubject($subject);
	}

	protected function addBody($p, $exec, $rec, $mail){
		//find feedback template
		$templatePath = $this->getWigiiExecutor()->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), $rec->getActivity());

		//$this->setRecord($rec);
		$this->getTrm()->reset($rec, true, true);

		//in Feedback activity, the form is defined as empty, so everything will be changed of course
		//we don't want
		$rec->getWigiiBag()->resetChanges();

		ob_start();
		include($templatePath);
		$body = ob_get_clean();

		$mail->setBodyHtml($body);
	}

	protected function addAttachements($p, $rec, $mail){
		//look to possible attachements:
		foreach($rec->getFieldList()->getListIterator() as $field){
			if($field->getDataType() != null && $field->getDataType()->getDataTypeName() =="Files"){
				$path = $rec->getFieldValue($field->getFieldName(), "path");
				if($path != null){
					//move the file in the uploaded files (because the email are send in asynch)
					if(!rename(TEMPORARYUPLOADEDFILE_path.$path, FILES_PATH."feedbackAttachement_".$path)) throw new ServiceException("Error on storing the temporaryUploadedFile ".$filePath." in the Client folder:".FILES_PATH.$fileName, ServiceException::FORBIDDEN);
					//the attachement file are marked to be deleted after the email is successfully sent
					$mail->createAttachment(
						FILES_PATH."feedbackAttachement_".$path, true,
						WigiiEmailMime::TYPE_OCTETSTREAM,
						WigiiEmailMime::DISPOSITION_ATTACHMENT,
						WigiiEmailMime::ENCODING_BASE64,
						stripslashes($rec->getFieldValue($field->getFieldName(), "name")).$rec->getFieldValue($field->getFieldName(), "type")
						);
				}
			}
		}
	}

	protected function processRecipients($p, $rec, $mail){
		$emailS = $this->getWigiiExecutor()->getEmailService(); //TechnicalServiceProvider::getEmailService();

		//send the feedback to the email of each Attribute or MultipleAttribut field
		//for the selected options
		$emails = TechnicalServiceProvider::getValueListArrayMapper(true, ValueListArrayMapper::Natural_Separators);
		$hasGroupEmailNotification = $rec->getLinkedEmailInRecord($p, $emails);

		if($hasGroupEmailNotification){
			if($rec->getAttachedRecord() == null) throw new RecordException("", RecordException::NO_ATTACHED_RECORD);

			$elS = ServiceProvider::getElementService();
			$elS->getFieldFromGroupsContainingElement(
				$this->getRootPrincipal(),
				FieldSelector::createInstance('emailNotification'),
				$rec->getAttachedRecord(), 1, //add parents
				$emails);
		}

		$mail->clearRecipients();
		$mail->addCc($rec->getFieldValue("feedback_email"));

		foreach($emails->getListIterator() as $email){
			$mail->addTo($email);
		}

		$emailS->send($p, $mail);
	}

	protected function getTechnicalInfo($p, $exec){
		ob_start();
		?><div class="field" style="font-size:9px;color:#666; margin-top:15px; border-bottom:none; border-top:dotted 1px;"><?
		$exec->displayTechnicalInfo($p);
		?></div><?
		return ob_get_clean();
	}

	protected function getFeedbackContext($p, $exec, $rec){
		$transS = ServiceProvider::getTranslationService();
		$wigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceName();
		if($rec->getAttachedRecord() != null){
			return $transS->t($p, "feedbackInTab")." ".($wigiiNamespace ? $wigiiNamespace." / " : '').$transS->t($p, $exec->getCrtModule()->getModuleName())." ".$transS->t($p, "feedbackAboutElement").$this->getRecord()->getAttachedRecord()->getId();
		} else {
			return $transS->t($p, "feedbackInTab")." ".($wigiiNamespace ? $wigiiNamespace." / " : '').$transS->t($p, $exec->getCrtModule()->getModuleName());
		}
	}
	protected function getFeedbackTitle($p, $exec, $rec){
		$text = '<h3>'.$this->getFeedBackContext($p, $exec, $rec).'</h3>';
		return $text;
	}

	//forFeedbackForm = false, if false then add a div with margin and clear
	protected function getElementLink($p, $exec, $rec, $forFeedbackForm=false){
		if($rec->getAttachedRecord() != null){
			$elS = ServiceProvider::getElementService();
			$transS = ServiceProvider::getTranslationService();
			ob_start();
			if(!$forFeedbackForm){
				?><div style="margin-top:20px;margin-bottom:10px;float:left;clear:both;"><?
			}
			$elS->displayElementId($p, $this->getRecord()->getAttachedRecord(), $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $transS->t($p, "feedbackAccessToElement").": ");
			if(!$forFeedbackForm){
				?></div><?
			}
			return ob_get_clean();
		}
	}

	protected function doSpecificCheck($p, $exec){
	    // Medair (CWE) 04.04.2017 checks for authorized direct sender
	    if(defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) {
	        try {
	            $this->getWigiiExecutor()->getEmailService()->isEmailAuthorizedDirectSender($p,$this->getRecord()->getFieldValue("feedback_email"),$p->getRealUsername());
	        }
	        catch(AuthorizationServiceException $ase) {
	            if($ase->getCode() == AuthorizationServiceException::NOT_ALLOWED) {
	                $this->addErrorToField(ServiceProvider::getTranslationService()->t($p,'unauthorizedSender'), "feedback_email");
	            }
	            else throw $ase;
	        }
	    }	    
	}

	protected function doRenderForm($p, $exec){
		//add lookup
		$feedbackRec = $this->getRecord();

		if($this->getState() == "start"){
			if($this->getRecord()->getAttachedRecord()==null){
				$exec->cacheCrtAnswer($p, "giveGeneralFeedback");
			} else {
				$exec->cacheCrtAnswer($p, "giveFeedback");
			}
			$feedbackRec->setFieldValue($p->getValueInGeneralContext("email"), "feedback_email");
		}

		$transS = ServiceProvider::getTranslationService();

		$this->getTrm()->setState($this->getState());

		echo $this->getElementLink($p, $exec, $this->getRecord(), true);

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());


		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, ($this->getRecord()->getAttachedRecord() != null ? "giveFeedback" : "giveGeneralFeedback")),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), null, '{ my : "left top", at: "left+30 top+70" }');

		//ajout du code feedback_special
		//ce code permet de disabled ou d'enabled des champs en fonction d'une
		//option définie dans le feedback_for. ceci n'est pas du tout générique
		//et ne fonctionne qu'avec les champs dont le nom est préfixé par
		//feedback_special et qu'une liste d'option soit défini comme feedback_from
		//si rien n'est défini tel quel ce bout de code est inutil, mais ne pose aucun problème
		$exec->addJsCode('' .
			'$("#giveFeedback_form [id^=giveFeedback_form_feedback_special]").attr("disabled",!$("#giveFeedback_form_feedback_for_value_feedback_special_option_checkbox").attr("checked"));' .
			'$("#giveFeedback_form :disabled").addClass("lightGray").parent().prev().children().addClass("darkGrayFont");' .
			'$("#giveFeedback_form_feedback_for_value_feedback_special_option_checkbox").click(function(){' .
				'$("#giveFeedback_form [id^=giveFeedback_form_feedback_special]").attr("disabled",!this.checked);' .
				'$("#giveFeedback_form *").removeClass("lightGray  darkGrayFont");' .
				'$("#giveFeedback_form :disabled").addClass("lightGray").parent().prev().children().addClass("darkGrayFont");' .
			'});' .
			'');
		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



