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
class ExternalAccessViewFormExecutor extends DetailElementFormExecutor {
	
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
		return $fe;
	}
	
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$elS = ServiceProvider::getElementService();
		$subR = $this->getGroupSubscriptionRecord();
		
		$exec->addJsCode("clearTimeout(externalAccessTimeoutTimer); $.unblockUI();");
		
		$elementP = $this->getElementP();
		
		$this->getDetailRenderer()->resetJsCodeAfterShow();
		
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
			//take out empty titles
			if($title){
				foreach($title as $lang=>$val){
					if($val == null) unset($title[$lang]);
				}
				$title = $title[$transS->getLanguage()];
			}
			if($title){
				echo '<h3 style="margin-bottom:10px; margin-top:10px;" >'.$title."</h3>";
			}
		}
		
		$title = $this->getTrm()->getBaseEmailSubject();
		
		if($this->getExternalAccessEndDate()){
			echo '<div style="margin-bottom:10px; margin-top:10px;" >'.$transS->t($p, "externalAccessEndDateTitle").": <font style=\"font-weight:bold;color:#f00;\">".date("d.m.Y H:i", $this->getExternalAccessEndDate())."</font></div>";
		}
		
		$this->getDetailRenderer()->setP($p)->setRecordIsWritable($elementP->getRights()->canWriteElement() && !$elementP->getElement()->isState_blocked());
		$this->getTrm()->setDetailRenderer($this->getDetailRenderer());
		
		$this->getTrm()->displayRemainingDetails();
		
		//add buttons
		if($elementP->getRights() && $elementP->getRights()->canWriteElement()){
			echo '<div class="clear"></div><div class="publicFormBorder" style="border-width:2px 0px 0px 0px;background-color:transparent;padding-top:10px;margin-bottom:30px;">';
		}
		
		//add edit button if can Write Element and email field is not multiple
		if($elementP->getRights() && $elementP->getRights()->canWriteElement() && $this->getExternalAccessFieldXml("isMultiple")!="1"){
			echo '<button aria-disabled="false" role="button" class="H ui-button publicFormBorder ui-state-default ui-corner-all ui-button-text-only" type="button" style="margin-left:10px;float:right;"';
			echo 'onclick="self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/delete/'.$transS->getLanguage().'\';" ';
			echo '><span class="ui-button-text">'.$transS->t($p, "deleteElement").'</span></button>';
		}
		
		//add edit button if can Write Element
		if($elementP->getRights() && $elementP->getRights()->canWriteElement()){
			echo '<button aria-disabled="false" role="button" class="H ui-button publicFormBorder ui-state-default ui-corner-all ui-button-text-only" type="button" style="margin-left:10px;float:right;" ';
			echo 'onclick="self.location=\''.SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/".$exec->getCrtAction()."/".$exec->getCrtParameters(0).'/edit/'.$transS->getLanguage().'\';" ';
			echo '><span class="ui-button-text">'.$transS->t($p, "editElement").'</span></button>';
		}
		
		if($elementP->getRights() && $elementP->getRights()->canWriteElement()){
			echo '</div>';
		}
		
		echo '</div>';
		
		$exec->addJsCode($this->getDetailRenderer()->getJsCodeAfterShow());
	}

}



