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
 * An ElementPolicyEvaluator implementation which automatically approves an element based on a log exp evaluated against element content.
 * Created by CWE on August 28th 2015
 */
class ElementLogExpApprover extends ElementPolicyEvaluatorBaseImpl
{
	private $_debugLogger;
	
	// Dependency injection

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementLogExpApprover");
		}
		return $this->_debugLogger;
	}
	
	protected function getFieldSelectorLogExpRecordEvaluator() {
		return TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator();
	}
	
	// Configuration
	
	private $approvalLogExp;
	/**
	 * Defines the approval log exp which should be evaluated when Element is saved to decide if element state is set to Approved.
	 * @param LogExp $logExp a valid log exp which will be evaluated against element content.
	 */
	public function setApprovalLogExp($logExp) {
		$this->approvalLogExp = $logExp;
	}
	protected function getApprovalLogExp() {
		return $this->approvalLogExp;
	}
	
	// ElementPolicyEvaluator implementation	

	public function updateElementStateOnSave($principal, $element, $fieldSelectorList=null) {		
		$fsl = null;
		// if Element is not Blocked and approval log exp evaluates positively, then changes element state to Approved.
		if(!$element->isState_blocked() &&
			$this->shouldElementBeApproved($principal, $element, $fieldSelectorList)) {
			$this->debugLogger()->logBeginOperation('approve Element');
			// updates Element on state changes according to standard policy
			$fsl = $this->updateElementOnSetState($principal, $element, 'approved', true);
			// changes state to Approved						
			$this->setElementState($principal, $element, 'approved', true, $fsl);
			$this->debugLogger()->logEndOperation('approve Element');
		}  
		return $fsl;
	}
	
	/**
	 * Decides if element state should be set to approved.
	 * @param Principal $principal the principal performing the operation
	 * @param Element $element the element beeing saved.
	 * @param FieldSelectorList an optional FieldSelectorList indicating which fields of the element have been modified,
	 * if null, then assume that all the Fields present in the FieldList have been modified.
	 * @throws RecordException in case of error.
	 * @return boolean true if element state should be set to approved, else false.
	 */
	protected function shouldElementBeApproved($principal, $element, $fieldSelectorList=null) {
		$approvalLx = $this->getApprovalLogExp();
		if(isset($approvalLx)) {
			$returnValue = $this->getFieldSelectorLogExpRecordEvaluator()->evaluate($element, $approvalLx);
		}
		else $returnValue = false;
		return $returnValue;
	}
}