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
 * Created on 24 may 2011
 * by LWR
 */
class ExternalAccessDeleteFormExecutor extends EditElementFormExecutor {
	
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
	
	public static function createInstance($wigiiExecutor, $elementP, $fieldName, $fieldXml, $externalAccessLevel=MANDATORY_ARG, $externalAccessEndDate=MANDATORY_ARG, $groupSubscriptionRecord=MANDATORY_ARG, $formId=MANDATORY_ARG, $submitUrl=MANDATORY_ARG){
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
		
		//if Email field is multiple --> throw exception
		if($fieldXml["isMultiple"]=="1"){
			throw new ServiceException("externalAccess delete cannot be done on a multiple email field", ServiceException::FORBIDDEN);
		} 
		return $fe;
	}
	
	protected function setState($state){
		switch($state){
			case "start":
				break;
			case "check":
				break;
			case "persistAndNotify":
				break;
			default: throw new FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function goToNextState(){
		switch($this->getState()){
			case "start": $this->setState("check"); break;
			case "check": $this->setState("check"); break;
			case "persistAndNotify":
			default: throw new FormExecutorException("invalid state: ".$this->getState(), FormExecutorException::INVALID_STATE);
		}
		return $this->getState();
	}
	
	public function CheckForm($p, $exec){
		//do no check, because in this case we have nothing to do and like this we keep the content of the DB
	}
	
	protected function doSpecificCheck($p, $exec){
		//bypass addMessageToNotification
	}
	
	protected function endActOnCheckedRecord($p, $exec){
		$transS = ServiceProvider::getTranslationService(); 
		
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$this->getWigiiExecutor()->openAsMessage(
				$exec->getIdAnswer(), 350,
				$transS->t($p, "operationDoneSuccessfully"), "",
				//'', $transS->t($p, "ok"), null, null, "done");
				'window.close()', $transS->t($p, "ok"), null, null, "done");
			
		$this->reloadAfterCheckedRecord($p, $exec);
	}
	
	protected function getExternalAccessCode($p, $exec){
		return $exec->getCrtParameters(0);
		
	}
	protected function reloadAfterCheckedRecord($p, $exec){ 
		//no reload. The operation done is not disaparing automaticaly, and on the click there is the link to view the new details.
	}
	
	protected function getBackUrl($p, $exec){
		return ''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$this->getExternalAccessCode($p, $exec).'/view/'.$exec->getCrtParameters(2).''; 
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService(); 
		
		//reset the email value + change the status of the element
		$this->getRecord()->setFieldValue(Emails::PROOF_STATUS_DELETED, $this->getExternalAccessFieldname(), "proofStatus");
		$this->getRecord()->setFieldValue(null, $this->getExternalAccessFieldname(), "proofKey");
		$delProof = $elS->getDeletedProofValue();
		$this->getRecord()->setFieldValue($this->getRecord()->getFieldValue($this->getExternalAccessFieldname(), "value")."<br />".$delProof, $this->getExternalAccessFieldname(), "proof");
		$this->getRecord()->setFieldValue(null, $this->getExternalAccessFieldname(), "value");
		$this->getRecord()->setFieldValue(null, $this->getExternalAccessFieldname(), "externalConfigGroup");
		$this->getRecord()->setFieldValue(null, $this->getExternalAccessFieldname(), "externalAccessLevel");
		$this->getRecord()->setFieldValue(null, $this->getExternalAccessFieldname(), "externalCode");
		$this->getRecord()->getWigiiBag()->setChanged($this->getExternalAccessFieldname());
		
		//this is necessary for the notifcation
		$this->getRecord()->setState_deprecated(true);
		$message = $elS->setState_deprecated($p, $this->getRecord()->getId(), true, $delProof);
		$this->getRecord()->setArrayState_deprecatedInfo($message);
		
		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");
		
		parent::actOnCheckedRecord($p, $exec);
	}
	
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		
		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");
		
		$element = $this->getRecord();
		$state = $this->getState();
		$backUrl = $this->getBackUrl($p, $exec);
		$subR = $this->getGroupSubscriptionRecord();
		
		echo '<div class="public public'.str_replace(" ", "_", $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()).'" style="';
		if($this->getTotalWidth()) echo ' width: 100%; max-width:'.$this->getTotalWidth().'px; ';
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
			default:
				
				$this->getTrm()->setFormRenderer($this->getFormRenderer()->setP($p));
				
				$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());
				
					
				$html = '<p class="R" style="margin:0px;font-weight:bold;">';
				$html .= $this->getTrm()->t("externalAccessConfirmDelete");
				$html .= "</p>";
				
				$html .= '<p class="" style="margin:10px 0 10px 0;">';
				$html .= $this->getTrm()->t("externalAccessDeleteExplanation");
				$html .= "</p>";
				
				echo $html;
					
				//$this->getTrm()->displayRemainingForms();
				
				$this->getTrm()->getCaptchaHTML($this);
				
				$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $this->getTrm()->t("cancel"), $backUrl);
				
				$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		}
		
		echo '</div>';
	}

}



