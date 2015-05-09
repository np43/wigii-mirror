<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * ElementPolicyEvaluator base implementation
 * Created by CWE on 03 July 2014
 */
class ElementPolicyEvaluatorBaseImpl implements ElementPolicyEvaluator
{
	private $_debugLogger;
	private $lockedForUse = true;

	// Object lifecycle

	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	public function freeMemory() {
		unset($this->formExecutor);
		unset($this->executionService);
		unset($this->multipleSelect);
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPolicyEvaluatorBaseImpl");
		}
		return $this->_debugLogger;
	}

	private $formExecutor;
	public function setFormExecutor($formExecutor) {
		$this->formExecutor = $formExecutor;
		if(isset($formExecutor) && !isset($this->isMultiple)) $this->isMultiple = $formExecutor->isMultiple();
	}
	protected function getFormExecutor() {
		return $this->formExecutor;
	}

	private $executionService;
	public function setExecutionService($exec) {
		$this->executionService = $exec;
	}
	protected function getExecutionService() {
		return $this->executionService;
	}

	private $multipleSelect;
	public function setIsMultiple($bool) {
		$this->multipleSelect = $bool;
	}
	protected function isMultiple() {
		return $this->multipleSelect;
	}

	// Configuration

	private $enableApprovalForNonAdminUser = false;
	/**
	 * Enables approval system for non admin users. Defaults to false.
	 * That means that by default only users having admin rights on folders
	 * can change element state to blocked, approved, finalized or dismissed.
	 * @param boolean $bool
	 */
	public function enableApprovalForNonAdminUser($bool) {
		$this->enableApprovalForNonAdminUser = $bool;
	}
	/**
	 * Returns true if approval system is enabled for non admin users.
	 */
	protected function isApprovalEnabledForNonAdminUser() {
		return $this->enableApprovalForNonAdminUser;
	}

	// Policy helpers

	/**
	 * Disables all element states
	 * @param ElementP $elementP
	 */
	protected function disableAllElementStates($elementP) {
		$elementP->enableElementState_locked(false);
		$elementP->enableElementState_blocked(false);
		$elementP->enableElementState_important1(false);
		$elementP->enableElementState_important2(false);
		$elementP->enableElementState_finalized(false);
		$elementP->enableElementState_approved(false);
		$elementP->enableElementState_dismissed(false);
		$elementP->enableElementState_archived(false);
		$elementP->enableElementState_deprecated(false);
		$elementP->enableElementState_hidden(false);
	}

	/**
	 * Disables element states which are part of the approval system
	 * @param ElementP $elementP
	 */
	protected function disableApprovalSystem($elementP) {
		if($this->isStatePartOfApprovalSystem('locked')) $elementP->enableElementState_locked(false);
		if($this->isStatePartOfApprovalSystem('blocked')) $elementP->enableElementState_blocked(false);
		if($this->isStatePartOfApprovalSystem('important1')) $elementP->enableElementState_important1(false);
		if($this->isStatePartOfApprovalSystem('important2')) $elementP->enableElementState_important2(false);
		if($this->isStatePartOfApprovalSystem('finalized')) $elementP->enableElementState_finalized(false);
		if($this->isStatePartOfApprovalSystem('approved')) $elementP->enableElementState_approved(false);
		if($this->isStatePartOfApprovalSystem('dismissed')) $elementP->enableElementState_dismissed(false);
		if($this->isStatePartOfApprovalSystem('archived')) $elementP->enableElementState_archived(false);
		if($this->isStatePartOfApprovalSystem('deprecated')) $elementP->enableElementState_deprecated(false);
		if($this->isStatePartOfApprovalSystem('hidden')) $elementP->enableElementState_hidden(false);
	}

	/**
	 * Sets element state
	 * @param Principal $principal principal performing the operation
	 * @param Element $element element on which to set the state
	 * @param String $state element state attribute name. One of: One of: 'locked', 'blocked', 'important1', 'important2', 'finalized',
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 * @param Boolean $checked checked or not
	 * @param FieldSelectorList $fslForUpdate an optional FieldSelectorList that will be updated with the state attributes.
	 */
	protected function setElementState($principal, $element, $state, $checked, $fslForUpdate=null) {
		$stateInfo = array();
		if($principal->getRealUser()){
			$stateInfo["realUsername"] = $principal->getRealUser()->getUsername();
			$stateInfo["realUserWigiiNamespace"] = $principal->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
			$stateInfo["realUserId"] = $principal->getRealUser()->getId();
		}
		if($principal->getAttachedUser()){
			$stateInfo["username"] = $principal->getAttachedUser()->getUsername();
			$stateInfo["userWigiiNamespace"] = $principal->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
			$stateInfo["userId"] = $principal->getAttachedUser()->getId();
		}
		$stateInfo["timestamp"] = time();
		$stateInfo["message"] = null;
		$element->setState($state, $checked, $stateInfo);
		$this->updateFslForSetElementState($state, $fslForUpdate);
	}

	/**
	 * Updates the FieldSelectorList with the fields updated in the method setElementState.
	 * @param String $state the state name passed to the method setElementState.
	 * @param FieldSelectorList $fsl
	 */
	protected function updateFslForSetElementState($state, $fsl) {
		if(isset($fsl)) {
			$fs = fs_e('state_'.$state);
			if(!$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) {
				$fsl->addFieldSelectorInstance($fs);
			}
			$fs = fs_e('state_'.$state.'Info');
			if(!$fsl->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) {
				$fsl->addFieldSelectorInstance($fs);
			}
		}
	}

	/**
	 * Returns true if state is part of the approval system else false.
	 * By default approval system is composed of the states 'blocked', 'approved', 'finalized' and 'dismissed'.
	 * @param String $state the state name, one of: 'locked', 'blocked', 'important1', 'important2', 'finalized',
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 */
	protected function isStatePartOfApprovalSystem($state) {
		switch($state) {
			case 'blocked':
			case 'finalized':
			case 'approved':
			case 'dismissed':
				return true;
			default: return false;
		}
	}

	// ElementPolicyEvaluator implementation

	public function computeEnableElementState($principal, $elementP, $state=null) {
		$element = $elementP->getElement();
		// keeps configuration policy, except if :
		// - subelement and parent blocked then all states are disabled.
		if($elementP->getElement()->isSubElement() && $elementP->isParentElementState_blocked()) {
			$this->disableAllElementStates($elementP);
			return;
		}
		// - blocked then all other states are disabled
		if($element->isState_blocked()) {
			$configEnablesDismiss = $elementP->isEnabledElementState_dismissed();
			$configEnableLock = $elementP->isEnabledElementState_locked();
			$configEnableBlock = $elementP->isEnabledElementState_blocked();

			$this->disableAllElementStates($elementP);
			// and finalized or approved and not dismissed then enable dismiss (if defined in config)
			if(($element->isState_finalized() || $element->isState_approved()) && !$element->isState_dismissed()) {
				$elementP->enableElementState_dismissed($configEnablesDismiss);
			}
			// and not finalized, approved or dismissed, then enables block (to unblock, if defined in config)
			elseif(!$element->isState_finalized() && !$element->isState_approved() && !$element->isState_dismissed()) {
				$elementP->enableElementState_blocked($configEnableBlock);
				// enables lock if not locked
				if(!$element->isState_locked()) $elementP->enableElementState_locked($configEnableLock && $configEnableBlock);
			}
		}
		// - not finalized and not approved, disables dismiss
		elseif(!$element->isState_finalized() && !$element->isState_approved()) {
			$elementP->enableElementState_dismissed(false);
		}
		// - locked and principal owns lock, then enables to release lock
		if($element->isState_locked() && $element->getSys_user() == $principal->getRealUserId()) {
			$elementP->enableElementState_locked(true);
		}
		// if non admin user and approval system not enabled for non admin user, then disables approval system
		if((is_null($elementP->getRights()) || !$elementP->getRights()->canModify()) &&
			!$this->isApprovalEnabledForNonAdminUser()) {
			$this->disableApprovalSystem($elementP);
			// prevents unblocking element by locking it
			if($element->isState_blocked() && !$elementP->isEnabledElementState_blocked() && !$element->isState_locked()) {
				$elementP->enableElementState_locked(false);
			}
		}
	}

	public function updateElementStateOnSave($principal, $element, $fieldSelectorList=null) {
		/* nothing to update */
		return null;
	}

	public function initializeElementStateOnCopy($principal, $element) {
		/* unsets finalized, approved, dismissed, blocked and deprecated states */
		$element->setState_blocked(false);
		$element->setState_blockedInfo(null);
		$element->setState_finalized(false);
		$element->setState_finalizedInfo(null);
		$element->setState_approved(false);
		$element->setState_approvedInfo(null);
		$element->setState_dismissed(false);
		$element->setState_dismissedInfo(null);
		$element->setState_deprecated(false);
		$element->setState_deprecatedInfo(null);
	}

	public function updateElementOnSetState($principal, $element, $state, $checked) {
		// nothing to update, except if :
		$fslForUpdate = FieldSelectorListArrayImpl::createInstance(false);
		// sets approved or finalized, then sets blocked
		if(($state == 'approved' || $state == 'finalized') && $checked) {
			$this->setElementState($principal, $element, 'blocked', true, $fslForUpdate);
		}
		// sets locked, then releases blocked if not finalized, approved or dismissed.
		elseif($state == 'locked' && $checked) {
			if(!$element->isState_finalized() && !$element->isState_approved() && !$element->isState_dismissed()
				&& $element->isState_blocked()) {
				$this->setElementState($principal, $element, 'blocked', false, $fslForUpdate);
			}
			elseif($this->isMultiple()) $this->updateFslForSetElementState('blocked', $fslForUpdate);
		}
		// sets finalized or approved or blocked, then releases lock if principal owns lock
		if(($state == 'approved' || $state == 'finalized' || $state == 'blocked') && $checked) {
			if($element->isState_locked()
				&& $element->getSys_user() == $principal->getRealUserId()) {
				$this->setElementState($principal, $element, 'locked', false, $fslForUpdate);
			}
			elseif($this->isMultiple()) $this->updateFslForSetElementState('locked', $fslForUpdate);
		}
		return $fslForUpdate;
	}

	public function getFieldSelectorListForUpdateElementOnSetState($principal, $state) {
		//potentially all fields is needed for notification:
		return null;

		/* no fields needed */
		return FieldSelectorListArrayImpl::createInstance();
	}
}