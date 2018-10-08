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

/**
 * Created on 18 sept 13 by LWR
 * Modified by Medair (CWE) on 18.05.2017 to prevent deletion of group is not empty and an Element_beforeDeleteExp is defined.
 */
class DeleteGroupFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	private $rootPincipal; //this is needed for moving the folder in the trashbin in case user has no access to the trashbin
	public function setRootPrincipal($rootP){ $this->rootPrincipal = $rootP; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }
	
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

	protected $beforeDeleteExpResult = null;
	
	protected function doSpecificCheck($p, $exec){
	    if(!$this->hasError() && $this->getState()=="check"){
	        $this->beforeDeleteExpResult = null;
	        $configS = $this->getWigiiExecutor()->getConfigurationContext();
	        // if delete folder and content, then checks if deletion of elements is conditioned by an Element_beforeDeleteExp
	        if($this->getRecord()->getFieldValue("deleteGroupOption")=="deleteChildrenAndContent") {
    	        $beforeDeleteExp = (string)$configS->getParameter($p, $this->getGroupP()->getGroup()->getModule(), "Element_beforeDeleteExp");	        
    	        $beforeDeleteExp = $this->evaluateBeforeDeleteExp($p, $exec, $beforeDeleteExp);	        
    	        if(!$beforeDeleteExp->okToDelete) {
    	            $this->beforeDeleteExpResult = $beforeDeleteExp;
    	            $this->addStateError();
    	        }
	        }
	    }
	}

	/**
	 * Evaluates the Element_beforeDeleteExp and returns an object authorizing or not the deletion and an optional error message.
	 * @param Principal $p current principal running the deletion process
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param String $beforeDeleteExp beforeDeleteExp FuncExp or 0 or 1.
	 * @throws Exception in case of error
	 * @return StdClass of the form {okToDelete: Boolean, message: String}
	 */
	protected function evaluateBeforeDeleteExp($p,$exec,$beforeDeleteExp) {
	    $transS = ServiceProvider::getTranslationService();
	    $returnValue = null;
	    
	    // null expression always resolves to true
	    if($beforeDeleteExp == null) $returnValue = true;
	    // if 0, then deletion of folder is allowed only if empty.
	    // elseif($beforeDeleteExp === '0') $returnValue = false;
	    // and 1 to true
	    elseif($beforeDeleteExp === '1') $returnValue = true;
	    // else should be a FuncExp.
	    else {
	        // for group deletion, don't evaluate beforeDeleteExp on each elements, but authorizes group deletion only if empty
	        $returnValue = (ServiceProvider::getElementService()->countSelectedElementsInGroups($p,
	            lxInGR(lxEq(fs('id'), $this->getGroupP()->getGroup()->getId())),
	            lf(fsl(fs_e('id')))) == 0);
	    }
	    // returns the resolved expression
	    if(!($returnValue instanceof stdClass)) {
	        // if evaluates to true, then OK to delete.
	        if($returnValue) $returnValue = (object)array('okToDelete'=>true,'message'=>null);
	        // else KO to delete and adds a default message
	        else $returnValue = (object)array('okToDelete'=>false,'message'=>$transS->t($p, "groupCannotBeDeletedExplanation"));
	    }
	    // adds default message if explanation is missing
	    elseif(!$returnValue->okToDelete && !$returnValue->message) $returnValue->message = $transS->t($p, "groupCannotBeDeletedExplanation");
	    return $returnValue;
	}
	
	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();

		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$parentId = $group->getGroupParentId();
		$workingModule = $group->getModule();
		$workingModuleName = $workingModule->getModuleName();
		
		$moveContentToParent = ($groupEditRec->getFieldValue("deleteGroupOption")=="moveChildrenAndContentToParent");
		
		// if trashbin exists and user doesn't want to keep content, then moves group to trashbin
		$trashBinGroupId = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "trashBinGroup");
		if(!empty($trashBinGroupId) && !$moveContentToParent && $parentId != $trashBinGroupId){
			// checks for delete authorization
			$this->assertPrincipalAuthorizedForDeleteGroup($p, $this->getGroupP());
			
			$rootP = $this->getRootPrincipal();
			// transfers lock to root principal
			ServiceProvider::getDbAdminService()->lock($rootP, 'Groups', $group, true);
			try {
				// moves group to trashbin
				$groupAS->moveGroup($rootP, $group->getId(), $trashBinGroupId);
			}
			catch(Exception $e) {
				if($e instanceof GroupAdminServiceException && $e->getCode() == GroupAdminServiceException::INVALID_ARGUMENT) {
					$e = new GroupAdminServiceException("trashBinGroup $trashBinGroupId is not a valid group. Correct the configuration. Root message is : ".$e->getMessage(), GroupAdminServiceException::CONFIGURATION_ERROR);
				}
				// unlocks
				$this->getWigiiExecutor()->unLockEntity($rootP, $exec, null, "group", $group);
				throw $e;
			}
			// unlocks
			$this->getWigiiExecutor()->unLockEntity($rootP, $exec, null, "group", $group);
		}
		// else deletes group
		else {
			//delete possible files in activities
			$groupActivityWithConfig = array (
				"groupPortal",
				"groupHtmlContent",
				"groupXmlPublish",
				"groupSubscription"
			);
			foreach ($groupActivityWithConfig as $activityName) {
				$rec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance($activityName), $exec->getCrtModule());
				switch ($activityName) {
					case "groupPortal" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $rec->getActivity());
						break;
					case "groupHtmlContent" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $rec->getActivity());
						break;
					case "groupXmlPublish" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $rec->getActivity());
						break;
					case "groupSubscription" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $rec->getActivity());
						break;
				}
				foreach ($rec->getFieldList()->getListIterator() as $field) {
					if ($field->getDataType() != null && $field->getDataType()->getDataTypeName() == "Files") {
						if (isImage($rec->getFieldValue($field->getFieldName(), "mime")))
							@ unlink(FILES_PATH . "tn_" . $rec->getFieldValue($field->getFieldName(), "path"));
						@ unlink(FILES_PATH . $rec->getFieldValue($field->getFieldName(), "path"));
					}
				}
			}
	
			//delete group config if defined
			if ($this->getWigiiExecutor()->getConfigurationContext()->doesGroupHasConfigFile($p, $group)) {
				$configfile = $this->getWigiiExecutor()->getConfigurationContext()->getGroupConfigFilename($p, $group);
				@ unlink($configfile);
			}
			//delete the group and all the content associated to him
			$groupAS->deleteGroup($p, $group->getId(), $moveContentToParent);
		}
		
		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$this->getWigiiExecutor()->throwEvent()->deleteGroup(PWithGroup :: createInstance($p, $group));

		if(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel"){
			//the timeout is important to let the JS of the request to be executed first, as the setListenersToGroupPanel
			$exec->addJsCode(" setTimeout(function(){ clickOnGroupInGroupPanel(".$parentId.");}, 100); ");
		}

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}
	protected function assertPrincipalAuthorizedForDeleteGroup($p, $groupP)
	{
		$autoS = ServiceProvider::getAuthorizationService();
		// checks general authorization
		$autoS->assertPrincipalAuthorized($p, "GroupAdminService", "deleteGroup");
		// check specific rights
		if(is_null($groupP->getRights()) ||
				!$groupP->getRights()->canModify() ||
				!$p->getGroupCreator($groupP->getGroup()->getModule()) ||
				(!$p->getRootGroupCreator($groupP->getGroup()->getModule()) && $groupP->getGroup()->getGroupParentId()==null)
		)
		{
			$autoS->fail($p, "has no right to delete the group ".$groupP->getId());
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$wigiiNamespace = $group->getWigiiNamespace()->getWigiiNamespaceName();
		$state = $this->getState();

		//fill values on start
		if ($group->getGroupParentId() == null) {
			$exec->addJsCode("" .
				"$('#deleteGroup_form_deleteGroupOption_value_moveChildrenAndContentToParent_radio').attr('disabled', true);" .
				"$('#deleteGroup_form_deleteGroupOption_value_deleteChildrenAndContent_radio').attr('checked', true);");
			$explanation = $transS->t($p, "rootGroupThenDeleteChildrenAndContentExplanation");
		} else {
		    if($state=="start") $exec->addJsCode("$('#deleteGroup_form_deleteGroupOption_value_moveChildrenAndContentToParent_radio').attr('checked', true);");
			$explanation = $transS->t($p, "moveChildrenAndContentToParentExplanation");
		}		

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		// Medair (CWE) 18.05.2017: displays error message coming from Element_beforeDeleteExp if defined
		if($this->hasError() && $this->beforeDeleteExpResult && !$this->beforeDeleteExpResult->okToDelete) {
		    echo '<div style="margin-top:15px;padding-left:45px;position:relative;">';
		    ?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/emblems/emblem-unreadable.png";?>" style="left:0;position:absolute;margin-right:15px;margin-bottom:15px;" /><?
		  echo '<div><b>'.$transS->t($p, "groupCannotBeDeleted").':</b><br/><br />';
		  echo $this->beforeDeleteExpResult->message."</div></div><br /><br />";	
		}
		else {		   
    		?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/status/not-known.png";?>" style="float:left;margin:0px 5px 0px 0px;"/><?
    
    		echo '<span style="line-height:1.7;">';
    		echo $transS->t($p, "areYouSureDeleteGroup");
    		echo "<br>";
    		echo "<b>" . ($wigiiNamespace ? $wigiiNamespace . " : " : "") . $group->getGroupname() . "</b>";
    		echo '</span>';
    		echo "<br>";
		}
    	
		$this->getTrm()->displayRemainingForms();
    
   		?><div class="explanation" style="float:left;"><?=$explanation;?></div><?
		
		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');";

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $this->getDialogBoxtitle($p),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);


		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
	public function getDialogBoxTitle($p){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "deleteGroup");
	}
}



