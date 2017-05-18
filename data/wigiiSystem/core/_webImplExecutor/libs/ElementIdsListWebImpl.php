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
 * WigiiBag for Extraction of Ids and rights implementation
 * Modified by CWE on 15.07.2014 to add calculation of Element enable status if ElementPolicyEvaluator is injected.
 * Modified by Medair (ACA,CWE) on 16.05.2017 to hide delete button in multiple selection if enableDeleteOnlyForAdmin and not admin or if Element_beforeDeleteExp=0
 */

class ElementIdsListWebImpl extends ObjectListArrayImpl implements ElementPList {
	private $computeElementPolicy = false;
	private $elementPolicyEvaluator;
	private $elementState;
	private $elementEnableState;
	private $elementEnableStateConfig;
	private $principal;
	private $configS;
	
	public function reset() {
		parent::reset();
		unset($this->elementPolicyEvaluator);
		unset($this->elementState);
		unset($this->elementEnableState);
		unset($this->elementEnableStateConfig);
		unset($this->principal);
		unset($this->configS);
		$this->computeElementPolicy = false;
	}
	
	public static function createInstance() {
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	/**
	 * @param Principal $principal current principal filling the list
	 * @param Module $module module of the selected elements
	 * @param ConfigService $configS ConfigService instance used to get the default element enable status policy.
	 * @param ElementPolicyEvaluator $elementPolicyEvaluator optional ElementPolicyEvaluator used to compute each policy individually.
	 *
	 */
	public static function createInstanceWithPolicyCalculation($principal, $module, $configS, $elementPolicyEvaluator=null) {
		$returnValue = self::createInstance();
		$returnValue->initializePolicyCalculation($principal, $module, $configS);
		$returnValue->setElementPolicyEvaluator($elementPolicyEvaluator);
		return $returnValue;
	}
	
	/**
	 * Injects the ElementPolicyEvaluator instance to be used by the ElementPList
	 * @param ElementPolicyEvaluator $elementPolicyEvaluator
	 */
	public function setElementPolicyEvaluator($elementPolicyEvaluator) {
		$this->elementPolicyEvaluator = $elementPolicyEvaluator;
		if(isset($this->elementPolicyEvaluator)) {
			$this->elementPolicyEvaluator->setIsMultiple(true);
			
		}
	}
	/**
	 * @param Principal $principal current principal filling the list
	 * @param Module $module module of the selected elements
	 * @param ConfigService $configS ConfigService instance used to get the default element enable status policy.
	 */
	public function initializePolicyCalculation($principal, $module, $configS) {
		$this->computeElementPolicy = true;
		$this->elementEnableState = array();
		$this->elementState = array();
		$this->elementEnableStateConfig = array(
				'Element_enableLockedStatus' => $configS->getParameter($principal, $module, 'Element_enableLockedStatus')=="1",
				'Element_enableBlockedStatus' => $configS->getParameter($principal, $module, 'Element_enableBlockedStatus')=="1",
				'Element_enableImportant1Status' => $configS->getParameter($principal, $module, 'Element_enableImportant1Status')=="1",
				'Element_enableImportant2Status' => $configS->getParameter($principal, $module, 'Element_enableImportant2Status')=="1",
				'Element_enableFinalizedStatus' => $configS->getParameter($principal, $module, 'Element_enableFinalizedStatus')=="1",
				'Element_enableApprovedStatus' => $configS->getParameter($principal, $module, 'Element_enableApprovedStatus')=="1",
				'Element_enableDismissedStatus' => $configS->getParameter($principal, $module, 'Element_enableDismissedStatus')=="1",
				'Element_enableArchivedStatus' => $configS->getParameter($principal, $module, 'Element_enableArchivedStatus')=="1",
				'Element_enableDeprecatedStatus' => $configS->getParameter($principal, $module, 'Element_enableDeprecatedStatus')=="1",
				'Element_enableHiddenStatus' => $configS->getParameter($principal, $module, 'Element_enableHiddenStatus')=="1",
		        'Element_enableHiddenDelete' => $configS->getParameter($principal, $module, 'enableDeleteOnlyForAdmin')=="1" || ((string)$configS->getParameter($principal, $module, 'Element_beforeDeleteExp')==="0")
		);
		$this->principal = $principal;
		$this->configS = $configS;
	}
	
	public function addElementP($elementP){
		if(!isset($elementP)) throw new ServiceException("elementP cannot be null", ServiceException::INVALID_ARGUMENT);
		if($elementP->getElement()==null) throw new ServiceException("elementP->element cannot be null", ServiceException::INVALID_ARGUMENT);
		if($elementP->getRights()==null) throw new ServiceException("elementP->rights cannot be null", ServiceException::INVALID_ARGUMENT);
		$element = $elementP->getElement();
		$id = $element->getId();
		$this->objArray[$id] = $elementP->getRights()->canWriteElement();
		
		if($this->computeElementPolicy) {
			$this->elementState[$id] = $element->getStateAsInt();
			// initializes elementP with default policy
			$elementP->enableElementState_locked($this->elementEnableStateConfig['Element_enableLockedStatus']);
			$elementP->enableElementState_blocked($this->elementEnableStateConfig['Element_enableBlockedStatus']);
			$elementP->enableElementState_important1($this->elementEnableStateConfig['Element_enableImportant1Status']);
			$elementP->enableElementState_important2($this->elementEnableStateConfig['Element_enableImportant2Status']);
			$elementP->enableElementState_finalized($this->elementEnableStateConfig['Element_enableFinalizedStatus']);
			$elementP->enableElementState_approved($this->elementEnableStateConfig['Element_enableApprovedStatus']);
			$elementP->enableElementState_dismissed($this->elementEnableStateConfig['Element_enableDismissedStatus']);
			$elementP->enableElementState_archived($this->elementEnableStateConfig['Element_enableArchivedStatus']);
			$elementP->enableElementState_deprecated($this->elementEnableStateConfig['Element_enableDeprecatedStatus']);
			$elementP->enableElementState_hidden($this->elementEnableStateConfig['Element_enableHiddenStatus']);
			$elementP->enableElementState_delete($this->elementEnableStateConfig['Element_enableHiddenDelete'] && (!$elementP->getRights()->canModify() || ((string)$this->configS->getParameter($this->principal, $elementP->getElement()->getModule(), 'Element_beforeDeleteExp')==="0")));
			
			// updates policy using the ElementPolicyEvaluator
			if(isset($this->elementPolicyEvaluator)) $this->elementPolicyEvaluator->computeEnableElementState($this->principal, $elementP);
			
			// converts the policy to a binary representation
			$this->elementEnableState[$id] = $elementP->getEnableElementStateAsInt();
		}
	}

	public function createFieldList(){
		return FieldListArrayImpl::createInstance();
	}
	public function createWigiiBag(){
		return WigiiBagBaseImpl::createInstance();
	}
	
	/**
	 * @return Array returns an array[element id => element state as int]
	 * See Element->getStateAsInt for more details.
	 */
	public function getElementStates() {
		return $this->elementState;
	}
	/**
	 * @return Array returns an array[element id => element enable state as int]
	 * See ElementP->getEnableElementStateAsInt for more details.
	 */
	public function getElementEnableStates() {
		return $this->elementEnableState;
	}
}



