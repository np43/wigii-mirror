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

/**
 * Created on 15 sept. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class EditMultipleElementFormExecutor extends EditElementFormExecutor implements ElementDataTypeSubfieldVisitor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FormExecutor");
		}
		return $this->_executionSink;
	}

	private $elementPAList;
	protected function setElementPAList($var){$this->elementPAList = $var; }
	public function getElementPAList(){return $this->elementPAList; }

	private $listContextWithMultipleSelection;
	/**
	 * Sets the list context which contains the actual multiple selection
	 * @param ListContext $listContext
	 */
	public function setListContextWithMultipleSelection($listContext) {
		$this->listContextWithMultipleSelection = $listContext;
	}
	protected function getListContextWithMultipleSelection() {
		return $this->listContextWithMultipleSelection;
	}

	//dependency injection
	private $elementService;
	public function setElementService($var){
		if(!is_a($var, "ElementServiceImpl")) throw ElementServiceException("EditMultipleElementFormExecutor needs a ElementServiceImpl type to be able to call visitDataTypeSubfields", ElementServiceException::INVALID_ARGUMENT);
		$this->elementService = $var;
	}
	protected function getElementService(){
		if(!isset($this->elementService)){
			$this->elementService = ServiceProvider::getElementService();
			if(!is_a($this->elementService, "ElementServiceImpl")) throw ElementServiceException("EditMultipleElementFormExecutor needs a ElementServiceImpl type to be able to call visitDataTypeSubfields", ElementServiceException::INVALID_ARGUMENT);
		}
		return $this->elementService;
	}
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal=MANDATORY_ARG, $elementPAList=MANDATORY_ARG){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setElementPAList($elementPAList);
		return $fe;
	}

	private $fslForUpdate;
	/**
	 * @return FieldSelectorList returns current FieldSelectorList that will be used to update the record in the DB. 
	 * Can be modified by a FuncExp to add dependencies to be saved.
	 */
	public function getFieldSelectorListForUpdate() {
		return $this->fslForUpdate;
	}
	
	protected function doSpecificCheck($p, $exec){

		if($this->getState() == "persistAndNotify"){
			//validate the email
			//if the email is empty (because of no notification needed, validation works)
			if(!validateEmail($this->getWigiiExecutor()->getNotificationService()->getNotificationEmailInputValue()) ||
				!validateEmails($this->getWigiiExecutor()->getNotificationService()->getNotificationToInputValue())
				){
				$this->setState("addMessageToNotification");
			}
		}

		if(!$this->hasError() && $this->getState()=="check"){
			try{
				$this->getWigiiExecutor()->getNotificationService()->isNotificationNeededForMultipleElement($p, $exec->getCrtModule(), "update", $this->getRecord());
				if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessage")!="0"){
					$this->setState("addMessageToNotification");
				}
			} catch (NotificationServiceException $e){
				if ($e->getCode() == NotificationServiceException::NO_NOTIFICATION_NEEDED){
					$this->executionSink()->log("No notification needed: ".$e->getMessage());
					//don't change the state
					return;
				}
				throw $e;
			}
		}
	}

	//implements ElementDataTypeSubfieldVisitor
	private $elementPAListWigiiBag;
	public function getElementPAListWigiiBag(){
		if(!isset($this->elementPAListWigiiBag)){
			$this->elementPAListWigiiBag = reset($this->getElementPAList()->getListIterator())->getDbEntity()->getWigiiBag();
		}
		return $this->elementPAListWigiiBag;
	}
	private $recordWigiiBag;
	public function getRecordWigiiBag(){
		if(!isset($this->recordWigiiBag)){
			$this->recordWigiiBag = $this->getRecord()->getWigiiBag();
		}
		return $this->recordWigiiBag;
	}
	public function actOnSubfield($field, $dataType, $subFieldName, $subFieldType, $sqlType, $userSelectedSubField, $lang=null){
		$fieldName = $field->getFieldName();
		if($this->getRecordWigiiBag()->isMultipleAddOnlyChecked($fieldName)) $op = SUPDOP_ADD;
		else $op = SUPDOP_SET;
		$value = $this->getRecord()->getFieldValue($fieldName, $subFieldName);
		if($lang!=null) $value = $value[$lang];
		$this->getElementPAListWigiiBag()->applyOperator($op, $value, $dataType->getDataTypeName(), $fieldName, $subFieldName, $lang, $field);
	}

	protected function reloadAfterCheckedRecord($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		//invalid the cache of each element
		//$exec->invalidMultipleCache($p, 'elementDialog', "selectElementDetail", "element/detail/", $this->getListContext()->getMultipleSelection());
		$exec->invalidCache($p, 'elementDialog');
		$exec->invalidCache($p, 'moduleView');

		//reload each item
		$n = $this->getElementPAList()->count();
		if($n <= (int)$configS->getParameter($p, $exec->getCrtModule(), "listPageSize")){
			$exec->addJsCode("updateElementsInList('" . implode("-", $this->getElementPAList()->getIds()) . "');");
			//$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
		} else {
			// updates the list context multiple element state if we have an element policy evaluator injected
			if(!is_null($this->getElementPolicyEvaluator())) {
				$lc = $this->getListContextWithMultipleSelection();
				if(isset($lc)) {
					foreach($this->getElementPAList()->getListIterator() as $elementP) {
						$lc->updateElementStateInMultipleSelection($elementP->getId(), null, $elementP->getElement()->getStateAsInt(), false);
					}
					$lc->computeMultipleElementStateInt();
				}
			}

			//$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDonePleaseRefresh"), null, null, "Ok", null, null, "done");
			$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
		}
		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

//		$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
	}
	protected function endActOnCheckedRecord($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->reloadAfterCheckedRecord($p, $exec);
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = $this->getElementService();
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();

		if($this->getElementPAList() != null && !$this->getElementPAList()->isEmpty()) {

			//recalculate calculated fields
			//do the operation on each checked multiple field
			$fsl = FieldSelectorListArrayImpl::createInstance(false);
			foreach($this->getRecord()->getFieldList()->getListIterator() as $field){
				//remove any doNotPersist fields
				$fieldXml = $field->getXml();
				if($fieldXml["doNotPersist"]=="1") continue;

				$fieldName = $field->getFieldName();
				if($field->getDataType() == null) continue;
				if($this->getRecordWigiiBag()->isMultipleChecked($fieldName)){
					// Update Emails subfields only if email changes
					if($field->getDataType()->getDataTypeName() == "Emails") {
						$newValue = $this->getRecord()->getFieldValue($fieldName);
						$appendNewValue = $this->getRecordWigiiBag()->isMultipleAddOnlyChecked($fieldName);
						$rec = $this->getRecord();
						foreach($this->getElementPAList()->getListIterator() as $elementP) {
							$element = $elementP->getElement();
							$currentValue = $element->getFieldValue($fieldName);							
							if($appendNewValue) {
								if($newValue == null) $updatedValue = $currentValue;
								elseif($currentValue == null) $updatedValue = $newValue;
								else $updatedValue = str_update($currentValue, $newValue, SUPDOP_ADD, '/'.ValueListArrayMapper::Natural_Separators.'/', ", ");
							}
							else {
								if($newValue == null) $updatedValue = null;
								elseif($currentValue == null) $updatedValue = $newValue;
								else $updatedValue = str_update($currentValue, $newValue, SUPDOP_SET, '/'.ValueListArrayMapper::Natural_Separators.'/', ", ");
							}
							if($updatedValue != $currentValue) {
								// copies all the subfields from the mutiple edit form, except external access code which is recalculated
								$element->setFieldValue($updatedValue, $fieldName, "value");								
								$element->setFieldValue($rec->getFieldValue($fieldName, "proofKey"), $fieldName, "proofKey");
								// updates proof only if email is not emptied (keep possible delete status)
								if($updatedValue) {
									$element->setFieldValue($rec->getFieldValue($fieldName, "proofStatus"), $fieldName, "proofStatus");
									$element->setFieldValue($rec->getFieldValue($fieldName, "proof"), $fieldName, "proof");
								}
								$element->setFieldValue($rec->getFieldValue($fieldName, "externalConfigGroup"), $fieldName, "externalConfigGroup");
								$element->setFieldValue($rec->getFieldValue($fieldName, "externalAccessLevel"), $fieldName, "externalAccessLevel");
								$element->setFieldValue($rec->getFieldValue($fieldName, "externalAccessEndDate"), $fieldName, "externalAccessEndDate");
								if(!empty($updatedValue)) $element->setFieldValue($elS->getEmailExternalCode($p, $element->getId(), $fieldName, $updatedValue), $fieldName, "externalCode");
								else $element->setFieldValue(null, $fieldName, "externalCode");
							}
						}
					}
					// else perform operation on each subField:
					else $elS->visitDataTypeSubfields($field, $field->getDataType(), $this);
					$fsl->addFieldSelector($fieldName);
				}
				//autoCalc field needs to be updated too:
				if($field->isCalculated()){
					$fsl->addFieldSelector($fieldName);
				}
			}
			$this->fslForUpdate = $fsl;

			//do the autocalc on the list
			$evaluatorClassName = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Element_evaluator");
			$evaluator = ServiceProvider::getElementEvaluator($p, $evaluatorClassName);
			$evaluator->setFormExecutor($this);
			$elS->evaluateElementList($p, $this->getElementPAList(), $evaluator);
			// if any business logic error during execution, then shows them to end user
			if($this->hasError()) {
				$this->setState("check");
				return;
			}
			
			// evaluates element policy on each item of the list
			$policyEval = $this->getElementPolicyEvaluator();
			if(isset($policyEval)) {
				$policyEval->setExecutionService($exec);
				$policyFsl = null;
				foreach($this->getElementPAList()->getListIterator() as $elementP) {
					$fsl = $policyEval->updateElementStateOnSave($p, $elementP->getElement(), $this->fslForUpdate);
					// checks fsl integrity to prevent saving an empty field and loosing some info in the db.
					if(!isset($policyFsl)) {
						$policyFsl = FieldSelectorListArrayImpl::createInstance(false);
						if(isset($fsl)) $policyFsl->mergeFieldSelectorList($fsl);
					}
					else {
						$n = $policyFsl->count(); $mismatchError = false;
						if($n > 0 && (!isset($fsl) || $fsl->isEmpty())) $mismatchError = true;
						elseif($n == 0 && isset($fsl) && !$fsl->isEmpty()) $mismatchError = true;
						elseif(isset($fsl)) {
							$policyFsl->mergeFieldSelectorList($fsl);
							if($policyFsl->count() > $n) $mismatchError = true;
						}
						if($mismatchError) throw new ServiceException("Multiple edit: the element policy evaluator '".get_class($policyEval)."' does not update the same fields in all elements. Please correct the 'updateElementStateOnSave' method to be consistent for all elements.", ServiceException::DATA_INTEGRITY_ERROR);
					}
				}
				if(isset($policyFsl)) $this->fslForUpdate->mergeFieldSelectorList($policyFsl);
			}

			//eput($this->getElementPAList()->getListIterator());
			//eput($this->getRecordWigiiBag());
			//persist the updated ElementPList
			if(!$this->fslForUpdate->isEmpty()){
				try{
					$elS->updateMultipleElement($this->getRootPrincipal(), $p, $this->getElementPAList(), $this->fslForUpdate);
					if($this->getState()=="persistAndSkipNotify"){
						$this->getWigiiExecutor()->getNotificationService()->skipNextNotification();
					}
					$this->getWigiiExecutor()->throwEvent()->updateMultipleElement(PWithRecordWithElementPListWithGroupPList::createInstance($p, $this->getRecord(), $this->getElementPAList(), ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null)));
				} catch (AuthorizationServiceException $ase){
					if($ase->getCode() == AuthorizationServiceException::OBJECT_IS_LOCKED){
						$this->getWigiiExecutor()->displayListOfLockedItems($p, $this->getElementPAList()->getIds(), $exec->getIdAnswer());
						$this->setState("check");
						$this->addStateError();
						return;
					} else throw $ase;
				}

				//autosharing in new record to other group coming from attribut idGroup
				$gids = ValueListArrayMapper::createInstance(true, ValueListArrayMapper::Natural_Separators, true);
				$this->getRecord()->getLinkedIdGroupInRecord($p, $gids, true);
				$newGids = $gids->getListIterator();
				if($newGids == null) $newGids = array();

				$gids->reset(true, ValueListArrayMapper::Natural_Separators, true);
				$this->getRecord()->getNoneLinkedIdGroupInRecord($p, $gids, true);
				$removeGids = $gids->getListIterator();
				if($removeGids == null) $removeGids = array();

				if($newGids){
					$gplAllElements = GroupListArrayImpl::createInstance();
					$elS->getGroupsWithoutDetailContainingAllElements($p, $this->getElementPAList(), $gplAllElements);
				}
				if($removeGids){
					$gplAllGroups = GroupListArrayImpl::createInstance();
					$elS->getAllGroupsWithoutDetailContainingElements($p, $this->getElementPAList(), $gplAllGroups);
				}

				if($newGids && $removeGids){
					//only remove the ones that we are not adding
					$removeGids = array_diff_key($removeGids, $newGids);
				}

				if($newGids){ //only add ones that are not existing for all
					$newGids = array_diff_key($newGids, $gplAllElements->getIds());
				}
				if($removeGids){ //only remove ones that exists
					$removeGids = array_intersect_key($removeGids, $gplAllGroups->getIds());
				}

				$this->getWigiiExecutor()->getNotificationService()->blockNotificationPostingValue();
				if($newGids){
					$groupPList = $elS->addMultipleElementSharing($this->getRootPrincipal(), $this->getRootPrincipal(), $this->getElementPAList(), $newGids);
					$this->getWigiiExecutor()->throwEvent()->addMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $groupPList));
				}

				//do remove sharing
				if($removeGids){
					$groupPList = $elS->removeMultipleElementSharing($this->getRootPrincipal(), $this->getRootPrincipal(), $this->getElementPAList(), $removeGids);
					$this->getWigiiExecutor()->throwEvent()->removeMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $groupPList));
					$currentGroups = $configS->getGroupPList($p, $exec->getCrtModule())->getIds();
					foreach($removeGids as $gid){
						unset($currentGroups[$gid]);
					}
					//if no more currentGroups and the number of items are less than the page
					//then remove them from the page
					if(!$currentGroups && $this->getElementPAList()->count() <= (int)$configS->getParameter($p, $exec->getCrtModule(), "listPageSize")){
						$exec->addJsCode("$('#moduleView #row_".implode(", #moduleView #row_", $this->getElementPAList()->getIds())."').remove();");
					}
				}
				$this->getWigiiExecutor()->getNotificationService()->unblockNotificationPostingValue();

			} else {
				echo $transS->t($p, "noChangeAreMade");
				echo "<br />";
				echo $transS->t($p, "noMessageWasSent");
			}
		}

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->endActOnCheckedRecord($p, $exec);
	}

	protected function getCancelJsCode($p, $exec, $state){
		$cancelJsCode = null;
		return $cancelJsCode;
	}
	//the state is important, because mybe the current state is already changed when displaying the dialog
	protected function openDialog($p, $exec, $state){
		$transS = ServiceProvider::getTranslationService();

		//we need to add a correction for ie7
		if($exec->getBrowserName()=="msie" && $exec->getBrowserVersion()<=7){
			$corr = 2;
		} else $corr = 0;

		if($state=="addMessageToNotification"){
			$this->getWigiiExecutor()->openAsDialogForm3B(
				$exec->getIdAnswer(), 520,
				'$("form", this).submit();', $transS->t($p, "addMessageToNotification"),
				$transS->t($p, "sendNotification"), $transS->t($p, "skipNotification"), $transS->t($p, "back"), ' $("form input[name=\'action\']", this).val("newCheck"); $("form", this).submit(); ', ' $("form input[name=\'action\']", this).val("persistAndSkipNotify"); $("form", this).submit(); ');
			$exec->addJsCode(' $(".ui-dialog-buttonpane .cancel", $("#' . $exec->getIdAnswer() . '").closest(".ui-dialog")).hide(); ');
		} else {
			$cancelJsCode = $this->getCancelJsCode($p, $exec, $state);
			$this->getWigiiExecutor()->openAsDialogForm(
				$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth()+$corr,
				'$("form", this).submit();', $transS->t($p, "editElements"),
				$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);
		}
	}
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$element = $this->getRecord();
		$state = $this->getState();

		//$this->renderBeforeForm($p, $exec, $state);
		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		//$this->renderInForm($p, $exec, $state);
		if($state=="addMessageToNotification"){
			//display the notification preview with the message input box
			$notificationS = $this->getWigiiExecutor()->getNotificationService();
			$notificationS->displayNotificationForm($p, "update", "MultipleElement", $exec->getCrtModule(), $element, ($config->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $config->getGroupPList($p, $exec->getCrtModule()) : null), $this->getElementPAList());
			//hide all the record form
			$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
		}

		//disable any file fields. there is no sense doing a multiple operation on a file
		//disable isKey field as it is in readonly for edition
		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $exec->getCrtModule());
		foreach($element->getFieldList()->getListIterator() as $field){
			if($field->getDataType() == null) continue;
			if($field->getDataType()->getDataTypeName() == "Files"){
				$element->getWigiiBag()->setHidden(true, $field->getFieldName());
			}
			if($isKey && $field->getFieldName() == $isKey->getName()){
				$element->getWigiiBag()->setHidden(true, $field->getFieldName());
			}
			// Medair (CWE) 04.12.2017: disables multiple edition of fields having notInMultipleEdit=1
			$fieldXml = $field->getXml();
			if($fieldXml['notInMultipleEdit'] == '1') {
			    $element->getWigiiBag()->setHidden(true, $field->getFieldName());
			}
		}

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), ($this->isWorkzoneViewDocked())?false:$this->isDialog(), ($this->isWorkzoneViewDocked())?'Cancel':null);

		$this->openDialog($p, $exec, $state);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		//check the check box if keydown in field, or clic on checkboxes
		$exec->addJsCode("" .
			"$('#".$exec->getIdAnswer()." .field .value :input').keydown(function(e){" .
				"if(e.keyCode != 13 && e.keyCode != 27 && e.keyCode != 9){" .
					"$(this).closest('div.field').find('.label').find(':input:first').attr('checked',true);" .
				"}" .
			"});" .
			"$('#".$exec->getIdAnswer()." .field .value textarea.htmlArea').parent().prev().find('span').click(function(e){" .
				"$(this).closest('div.field').find('.label').find(':input:first').attr('checked',true);" .
			"});" .
			"$('#".$exec->getIdAnswer()." .field .value :checkbox, #".$exec->getIdAnswer()." .field .value :radio, #".$exec->getIdAnswer()." .field .value select, #".$exec->getIdAnswer()." .field .value button').click(function(e){" .
				"$(this).closest('div.field').find('.label').find(':input:first').attr('checked',true);" .
			"});" .
			"");

	}
}



