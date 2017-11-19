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
 * Created on 11 february 10
 * by LWR
 */
class DetailGroupFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRecord($record);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){}

	protected function actOnCheckedRecord($p, $exec){}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$groupP = $this->getGroupP();
		$group = $groupP->getGroup();
		$record = $this->getRecord();

		//Group Identity
		$wigiiNamespaceName = $group->getWigiiNamespace()->getWigiiNamespaceName();
		if(!$wigiiNamespaceName) $wigiiNamespaceName = '<font class="grayFont">'.$transS->t($p, "noWigiiNamespace").'</font>';
		$record->setFieldValue($wigiiNamespaceName, "wigiiNamespace");
		$record->setFieldValue($groupAS->getHtmlToDisplayGroupAsFolder($p, $exec, $group), "groupname");

		$record->setFieldValue($group->getSys_creationUsername(), "sys_creationUsername");
		$record->setFieldValue($group->getSys_username(), "sys_username");
		$record->setFieldValue($group->getSys_date(), "sys_date");
		$record->setFieldValue($group->getSys_creationDate(), "sys_creationDate");

		if($group->getDetail() != null){

			$record->setFieldValue($group->getDetail()->getDescription(), "description");
			$actTrm = $this->createTrmInstance();

			//ConfigEdit
			if($config->allowGroupDynamicConfig($p, $group->getModule())){
				if($config->doesGroupHasConfigFile($p, $group)){
					$record->setFieldValue(true, "groupConfigEdit");
				}
			}

			if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "Notification_enable")=="1"){
				//Email Alerts
				$record->setFieldValue($group->getDetail()->getEmailNotification(), "groupEmailNotification");
			}

			if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "Group_enablePortal")=="1"){
				//Portal
				$actRec1 = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
				$actRec1->attachRecord($group);
				$actRec1->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $actRec1->getActivity());
				//$record->setFieldValue($actRec1->getFieldValue("url", "url")!=null, "groupPortal");
				if($actRec1->getFieldValue("url", "url")!=null){ //$record->getFieldValue("groupPortal")){
					$record->setFieldValue("_blank", "groupPortalPreview", "target");
					$record->setFieldValue($actRec1->getFieldValue("url", "url"), "groupPortalPreview", "url");
					$record->setFieldValue($actRec1->getFieldValue("groupPortalCookieName")!=null, "groupPortalPreviewExternalSystemConnection");
				}

				//HTML Content
				$actRec2 = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
				$actRec2->attachRecord($group);
				$actRec2->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $actRec2->getActivity());
				//$record->setFieldValue($actRec2->getFieldValue("text")!=null, "groupHtmlContent");
				if($actRec2->getFieldValue("text")!=null){
					$actTrm->reset($actRec2);
					$actTrm->setDetailRenderer(DetailRenderer::createInstance($this->getFormId()."_groupHtmlContent", $actTrm, $this->getTotalWidth()-20, $this->getLabelWidth()-10, $this->getLanguageVisible()));
					$actTrm->setOutputEnabled(false);
					$actTrm->displayRemainingDetails();
					$record->setFieldValue($actTrm->getHtmlAndClean(), "groupHtmlContentPreview");
				}
			}

			if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "Group_enableXmlPublish")=="1"){
				//XML publish
				$actRec4 = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance("groupXmlPublish"), $exec->getCrtModule());
				$actRec4->attachRecord($group);
				$actRec4->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $actRec4->getActivity());
				//$record->setFieldValue($actRec4->getFieldValue("enableGroupXmlPublish")==true, "groupXmlPublish");
				if($actRec4->getFieldValue("enableGroupXmlPublish")==true){
					$record->setFieldValue("_blank", "groupXmlPublishPreview", "target");
					$record->setFieldValue(SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/getXmlFeed/".$group->getId()."/".$actRec4->getFieldValue("xmlPublishCode"), "groupXmlPublishPreview", "url");
					$record->setFieldValue($transS->t($p, "groupCsvPublishParametersExplanation"), "groupCsvPublishParametersExplanation");
					$record->setFieldValue(SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/getXmlFeed/".$group->getId()."/".$actRec4->getFieldValue("xmlPublishCode")."/csv", "groupCsvPublishPreview", "url");
					$record->setFieldValue(SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/getXmlFeed/".$group->getId()."/".$actRec4->getFieldValue("xmlPublishCode")."/csvraw", "groupCsvRawPublishPreview", "url");
					$record->setFieldValue(SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/getXmlFeed/".$group->getId()."/".$actRec4->getFieldValue("xmlPublishCode")."/csvbackup", "groupCsvBackupPublishPreview", "url");
				}
			}

			if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "Group_enableSubscription")=="1"){
				//Subscription
				$actRec3 = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity::createInstance("groupSubscription"), $exec->getCrtModule());
				$actRec3->attachRecord($group);
				$actRec3->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $actRec3->getActivity());
				//$record->setFieldValue($actRec3->getFieldValue("enableGroupSubscription")==true, "groupSubscription"); //we need to do == true to prevent no display in case of null
				if($actRec3->getFieldValue("enableGroupSubscription")==true){
					$link = SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/newSubscription/".$group->getId();


					$record->setFieldValue("_blank", "groupSubscriptionLink", "target");
					$record->setFieldValue($link, "groupSubscriptionLink", "url");

					if($actRec3->getFieldValue("enableExternalAccessRequest") && $this->getWigiiExecutor()->doesGroupHasEmailField($p, $exec, $this->getGroupP()->getDbEntity())){
						$externalAccessRequestLink = SITE_ROOT.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/externalAccessRequest/".$group->getId();
						$record->setFieldValue("_blank", "groupSubscriptionExternalAccessRequestLink", "target");
						$record->setFieldValue($externalAccessRequestLink, "groupSubscriptionExternalAccessRequestLink", "url");
					}

					$actRec3->getWigiiBag()->setHidden(true, "enableGroupSubscription");
					$actTrm->reset($actRec3);
					$actTrm->setDetailRenderer(DetailRenderer::createInstance($this->getFormId()."_groupSubscription", $actTrm, $this->getTotalWidth()-20, $this->getLabelWidth()-10, $this->getLanguageVisible()));
					$actTrm->setOutputEnabled(false);
					$actTrm->displayRemainingDetails();
					$record->setFieldValue($actTrm->getHtmlAndClean(), "groupSubscriptionPreview");
				}
			}

//			//add onclick on field to match the activities button
//			$exec->addJsCode("
//$('#detailGroup_form_groupConfigEdit').click(function(){
//	$('#adminGroup_detail .commands .groupConfigEdit').click();
//});
//$('#detailGroup_form_groupPortal').click(function(){
//	$('#adminGroup_detail .commands .groupPortal').click();
//});
//$('#detailGroup_form_groupHtmlContent').click(function(){
//	$('#adminGroup_detail .commands .groupHtmlContent').click();
//});
//$('#detailGroup_form_groupXmlPublish').click(function(){
//	$('#adminGroup_detail .commands .groupXmlPublish').click();
//});
//$('#detailGroup_form_groupSubscription').click(function(){
//	$('#adminGroup_detail .commands .groupSubscription').click();
//});
//
//");
		}
		$this->getTrm()->setDetailRenderer($this->getDetailRenderer()->setP($p));
		$this->getTrm()->displayRemainingDetails("groupHtmlContentPreview");
		$this->getTrm()->disableHTMLPurifier(); //allow display of preview containing tabs
		$this->getTrm()->displayRemainingDetails("groupXmlPublish");
		$this->getTrm()->enableHTMLPurifier();
		$this->getTrm()->displayRemainingDetails("groupSubscriptionPreview");
		$this->getTrm()->disableHTMLPurifier(); //allow display of preview containing tabs
		$this->getTrm()->displayRemainingDetails("historicOfObject");
		$this->getTrm()->enableHTMLPurifier();
		$this->getTrm()->displayRemainingDetails();
	}
}



