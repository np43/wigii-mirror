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
 * Evaluates an element policy
 * Created by CWE on 03 July 2014
 */
interface ElementPolicyEvaluator extends WigiiExclusiveAccessObject
{
	// Dependency injection
	
	/**
	 * Injects a reference to the FormExecutor that launched the ElementPolicyEvaluator.
	 * This setter is only called in the scope of the GUI. In the API scope, this setter is ignored.
	 * @param FormExecutor $formExecutor
	 */
	public function setFormExecutor($formExecutor);
	
	/**
	 * Injects a reference to the WigiiExecutor that launched the ElementPolicyEvaluator.
	 * This setter is only called in the scope of the GUI. In the API scope, this setter is ignored.
	 * @param WigiiExecutor $wigiiExecutor
	 */
	public function setWigiiExecutor($wigiiExecutor);
	
	/**
	 * Injects a reference to the current ExecutionService instance.
	 * @param ExecutionService $exec
	 */
	public function setExecutionService($exec);
	
	/**
	 * Informs the ElementPolicyEvaluator that it will be acting into the scope of a multiple selection of elements.
	 * This is useful to know if it is important to always return the same FieldSelectorList of updated fields during one lifecycle.
	 * @param Boolean $bool
	 */
	public function setIsMultiple($bool);
	
	// Methods 
	
	/**
	 * Computes or updates the "enableElementState" properties from the given ElementP
	 * @param Principal $principal the principal asking for the computation
	 * @param ElementP $elementP the elementP instance on which to compute the enableElementState properties.
	 * @param String $state optionally specifies which state we want to compute the availability.
	 * State is one of : 'locked', 'blocked', 'important1', 'important2', 'finalized',
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 * @throws RecordException in case of error
	 */
	public function computeEnableElementState($principal, $elementP, $state=null);
	
	/**
	 * Updates the element state before saving it into the database.
	 * This method can be used to change some element statuses or even update some field values if needed.
	 * @param Principal $principal the principal performing the operation
	 * @param Element $element the element beeing saved.
	 * @param FieldSelectorList an optional FieldSelectorList indicating which fields of the element have been modified,
	 * if null, then assume that all the Fields present in the FieldList have been modified.
	 * @throws RecordException in case of error.
	 * @return FieldSelectorList can return a FieldSelectorList specifying which fields or element attributes have been updated.
	 */
	public function updateElementStateOnSave($principal, $element, $fieldSelectorList=null);
	
	/**
	 * Called by the WigiiExecutor when initiating a copy of an element.
	 * This method can be used to initialize element state before displaying the form,
	 * for instance to unset 'finalized', 'approved', 'dismissed' or 'blocked' states.
	 * @param Principal $principal the principal performing the copy operation
	 * @param Element $element the original element beeing copied.
	 */
	public function initializeElementStateOnCopy($principal, $element);
	
	/**
	 * Updates the element content when a state attribute changes.
	 * @param Principal $principal the principal performing the operation
	 * @param Element $element the element for which a state attribute changes
	 * @param String $state the state attribute name. One of: 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 * @param Boolean $checked true if state is checked, false if state is not checked.
	 * @throws RecordException in case of error.
	 * @return FieldSelectorList should return a FieldSelectorList specifying which fields or element attributes have been updated.
	 */
	public function updateElementOnSetState($principal, $element, $state, $checked);
	
	/**
	 * Returns an optional FieldSelectorList which specifies which fields need to be loaded and up to date
	 * into the element before executing the method 'updateElementOnSetState'.
	 * This method is called by the WigiiExecutor to fill the element before calling 'updateElementOnSetState'.
	 * If a null value is returned, then the WigiiExecutor loads all the fields of the module.
	 * @param Principal $principal the principal performing the operation
	 * @param String $state the name of the state attribute that will be modified. One of : 'locked', 'blocked', 'important1', 'important2', 'finalized', 
	 * 'approved', 'dismissed', 'archived', 'deprecated', 'hidden'.
	 * @return FieldSelectorList a FieldSelectorList or null.
	 */
	public function getFieldSelectorListForUpdateElementOnSetState($principal, $state);
}