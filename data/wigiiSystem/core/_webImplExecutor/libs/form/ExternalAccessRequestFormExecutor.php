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
 * Created on 27 May 2011
 * by LWR
 */
class ExternalAccessRequestFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($var){ $this->groupP = $var; }
	protected function getGroupP(){ return $this->groupP; }

	private $subscriptionGroupRec;
	protected function setSubscriptionGroupRec($var){ $this->subscriptionGroupRec = $var; }
	protected function getSubscriptionGroupRec(){ return $this->subscriptionGroupRec; }

	public static function createInstance($wigiiExecutor, $groupP, $record, $subscriptionGroupRec, $formId=MANDATORY_ARG, $submitUrl=MANDATORY_ARG){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setGroupP($groupP);
		$fe->setSubscriptionGroupRec($subscriptionGroupRec);
		return $fe;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();
		$emailS = $this->getWigiiExecutor()->getEmailService(); //TechnicalServiceProvider::getEmailService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$rec = $this->getRecord();
		$trm = $this->getWigiiExecutor()->createTRM();
		$trm->setForNotification(true);
		$trm->setForExternalAccess(true);
		$trm->setP($p);
		$email = $emailS->getEmailInstance();
		$email->setFrom((string)$configS->getParameter($p, null, "emailNotificationFrom"));
		$email->addTo($rec->getFieldValue("externalAccessRequestEmail", "value"));
		$email->setSubject($transS->t($p, "externalAccessRequestEmailSubject"));

		$body = "";
		$count = 1;
		$max = $this->getElementPList()->count();
		foreach($this->getElementPList()->getListIterator() as $elementId=>$elementP){
			$title = "";
			if($count == 1){
				$title .= ($this->getTitle() ? "<h3>".$this->getTitle()."</h3>" : '').$transS->t($p, "externalAccessRequestEmailIntroductionText");
			}
			if($max > 1){
				$title .= "<h4>".$count.$transS->t($p, min(4, $count)."thSubscriptionfound")."</h4>";
			}
			$elS->setExternalAccessForEdit($p, $elementId, $this->getEmailField());
			$body .= $trm->getSubscriptionConfirmationEmailHtml(
				$title,
				$elS->getEmailValidationLinkFromCode($p, $elementP->getDbEntity()->getFieldValue($this->getEmailField(), "proofKey")),
				$elementP->getDbEntity()->getFieldValue($this->getEmailField(), "proofStatus"),
				$elS->getExternalAccessLinkFromCode($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $elementP->getDbEntity()->getFieldValue($this->getEmailField(), "externalCode")),
				Emails::EXTERNAL_ACCESS_EDIT,
				$elementP->getDbEntity()->getFieldValue($this->getEmailField(), "externalAccessEndDate"));
			$count++;
		}

		$templatePath = $this->getWigiiExecutor()->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
		ob_start();
		include($templatePath);
		$body = ob_get_clean();
		$email->setBodyHtml($body);

		//send the message with the email service
		$emailS->send($p, $email);

		echo '<div class="public public'.str_replace(" ", "_", $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()).'" style="';
		if($this->getTotalWidth()) echo ' width: 100%; max-width:'.$this->getTotalWidth().'px; ';
		echo ' display:table; ';
		echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
		echo ' " >';

		//$exec->addJsCode(" $('html').css('background-color','#e6e6e6'); ");

		echo '<h3 style="margin-bottom:10px; margin-top:10px;" >'.$transS->t($p, "operationDoneSuccessfully")."</h3>";
		echo '<div class="clear"></div>';
		echo '<p style="margin:5px 0 10px 0;">'.$transS->t($p, "externalAccessRequestEmailIsSent")."</p>";

		echo '</div>';

		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

	}

	private $elementPList;
	protected function setElementPList($var){ $this->elementPList = $var; }
	protected function getElementPList(){ return $this->elementPList; }

	private $emailField;
	protected function setEmailField($var){ $this->emailField = $var; }
	protected function getEmailField(){ return $this->emailField; }

	protected function doSpecificCheck($p, $exec){
		//try to find the email in one field of one element linked to the GroupP

		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$emailField = $this->getWigiiExecutor()->doesGroupHasEmailField($p, $exec, $this->getGroupP()->getGroup());
		//find Emails field
		if(!$emailField) throw new ServiceException("ExternalAccessRequest must have at least one Email field in configuration for group:". $this->getGroupP()->getId(), ServiceException::INVALID_ARGUMENT);
		$this->setEmailField($emailField);

		//limit the element search for this email
		$elementPList = ElementPAdvancedListArrayImpl::createInstance();
		$lf = ListFilter::createInstance();
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector($emailField);
		$lf->setFieldSelectorList($fsl);
		//create log exp to match only the good value
		$exp = "$emailField.value = ".$this->getRecord()->getFieldValue("externalAccessRequestEmail", "value")."";
		$exp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString($exp);
		$lf->setFieldSelectorLogExp($exp);
		//get element list
		$gExp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString("INGR(id = ".$this->getGroupP()->getId().")");
		$elS->getSelectedElementsInGroups($p, $gExp, $elementPList, $lf);

		if($elementPList->isEmpty()){
			$this->addErrorToField($transS->h($p, "unknownEmailForExternalAccessRequest"), "externalAccessRequestEmail");
		}

		$this->setElementPList($elementPList);

	}

	private $title = null;
	protected function getTitle(){
		if(!isset($this->title)){
			$transS = ServiceProvider::getTranslationService();
			$title = $this->getSubscriptionGroupRec()->getFieldValue("title");
			$returnValue = "";
			if(is_array($title)){
				$returnValue = $title[$transS->getLanguage()];
				if($returnValue){
					//ok
				} else {
					foreach($title as $langTitle){
						if($langTitle){
							$returnValue = $langTitle;
							break;
						}
					}
				}
			}
			$this->title = $returnValue;
		}
		return $this->title;
	}
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");

		$subR = $this->getSubscriptionGroupRec();

		$this->getTrm()->setState($this->getState());

		echo '<div style="';
		if($this->getTotalWidth()) echo ' width: 100%; max-width:'.$this->getTotalWidth().'px; ';
		if($this->isDialog()) echo ' float:left; ';
		echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
		echo ' " >';

		//for the email request no need to have the language menu or title. this helps in website integration
		$title = $subR->getFieldValue("title");
		//take out empty titles
		foreach($title as $lang=>$val){
			if($val == null) unset($title[$lang]);
		}
		//if title is multi language:
		if(count($title)>1){
			foreach($title as $lang=>$val){
				echo '<span onclick="';
				echo 'self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/'.$lang.'\';';
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

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		echo '</div>';
	}
}



