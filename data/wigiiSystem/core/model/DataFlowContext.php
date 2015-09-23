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
 * A data flow execution context
 * Created by CWE on 28 mai 2013
 * Updated by CWE on 5 décembre 2013: added data flow context attributes
 * Updated by CWE on 14 février 2013: added wigii events dispatcher
 */
class DataFlowContext
{
	private $dataFlowService;

	// Object lifecycle

	public static function createInstance($dataFlowService) {
		if(is_null($dataFlowService)) throw new DataFlowServiceException("dataFlowService instance cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue = new self();
		$returnValue->dataFlowService = $dataFlowService;
		return $returnValue;
	}

	// dependency injection

	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("DataFlowContext");
		}
		return $this->_debugLogger;
	}


	// DataFlow interaction

	/**
	 * Writes some data to the output data flow
	 * The underlying DataFlowService will process the data chunk and
	 * call if needed the next steps in the data flow chain.
	 * This method can be called as many times a needed.
	 * Each call results in one data chunk to be passed to the DataFlowService for further processing.
	 * @param $resultData some result data, can be any kind of object
	 * @param $dataFlowActivity the reference to the current executing dataflow activity.
	 * @throws DataFlowServiceException in case of error
	 */
	public function writeResultToOutput($resultData, $dataFlowActivity) {
		$this->getDataFlowService()->processResultFromActivity($this, $resultData, $dataFlowActivity);
	}

	/**
	 * Returns a reference to the underlying DataFlow Service
	 */
	public function getDataFlowService() {
		return $this->dataFlowService;
	}
	/**
	 * Returns true if the current step is the last step of the dataflow
	 * Or equivalently if no more DataFlowActivity is coming after the one
	 * which is currently executing
	 */
	public function isCurrentStepTheLastStep() {
		return $this->getDataFlowService()->isStepTheLastStep($this->getStepId(), $this);
	}

	// Attributes

	/**
	 * Resets all the attributes
	 */
	public function reset() {
		$this->freeMemory();
		unset($this->contextId);
		unset($this->stepId);
	}
	/**
	 * Frees the memory used by this DataFlowContext, called before recycling
	 */
	public function freeMemory() {
		unset($this->dataFlowActivitySelectorList);
		unset($this->principal);
		unset($this->attributes);
		if(isset($this->recyclableAttributes)) {
			foreach($this->recyclableAttributes as $obj) {
				$this->recycleObject($obj);
			}
			unset($this->recyclableAttributes);
		}
		unset($this->checkedIfWigiiEventsAreEnabled);
		unset($this->wigiiEventsEnabled);
	}


	private $contextId;
	/**
	 * Returns the data flow execution instance id
	 */
	public function getContextId() {
		return $this->contextId;
	}
	/**
	 * Sets an ID to identify this data flow execution instance
	 */
	public function setContextId($contextId) {
		$this->contextId = $contextId;
	}

	private $stepId;
	/**
	 * Returns the current executing step ID in this DataFlow
	 */
	public function getStepId() {
		return $this->stepId;
	}
	/**
	 * Sets the current executing step ID of this DataFlow
	 */
	public function setStepId($stepId) {
		$this->stepId = $stepId;
	}

	private $dataFlowActivitySelectorList;
	/**
	 * Returns the DataFlowActivity list of this DataFlow context
	 */
	public function getDataFlowActivitySelectorList() {
		return $this->dataFlowActivitySelectorList;
	}
	/**
	 * Sets the DataFlowActivity list of this DataFlow context
	 */
	public function setDataFlowActivitySelectorList($dfaSL) {
		$this->dataFlowActivitySelectorList = $dfaSL;
	}

	private $principal;
	/**
	 * Returns the principal attached to this DataFlowContext
	 */
	public function getPrincipal() {
		return $this->principal;
	}
	/**
	 * Sets the principal attached to this DataFlowContext
	 * @param unknown_type $principal
	 */
	public function setPrincipal($principal) {
		$this->principal = $principal;
	}



	// User defined attributes
	private $attributes;
	private $recyclableAttributes;

	/**
	 * Characterizes this dataflow context with an attribute
	 * @param $key the attribute key
	 * @param $val the attribute value. Accepts null. Any existing value under the given key is replaced.
	 * @param $recyclable optional boolean. If true, then the method freeMemory (if exists) will be called on value, when the data flow is recycled,
	 * else no clean up code is executed, relies only on the garbage collection. Defaults to false.
	 */
	public function setAttribute($key, $val, $recyclable=false) {
		if(is_null($key)) throw new DataFlowServiceException('dataflow context attribute key cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) $this->attributes = array();
		$this->attributes[$key] = $val;
		if($this->debugLogger()->isEnabled()) $this->debugLogger()->write("sets attribute '$key' with value '".(is_object($val) ? "instance of ".get_class($val) : $val)."'");
		if(!isset($this->recyclableAttributes)) $this->recyclableAttributes = array();
		$oldVal = $this->recyclableAttributes[$key];
		if($oldVal !== $val) $this->recycleObject($oldVal);
		if($recyclable) $this->recyclableAttributes[$key] = $val;
		else unset($this->recyclableAttributes[$key]);
	}

	/**
	 * Sets the attributes as defined the given array.
	 * Uses the array key as attribute keys and the array values as attribute values.
	 * The values are shallow copied from the given array.
	 */
	public function setAttributesFromArray($attributes){
		if(empty($attributes)) return;
		if(!is_array($attributes)) throw new DataFlowServiceException('attributes should be an array', DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) $this->attributes = $attributes;
		else $this->attributes = array_merge($this->attributes, $attributes);
	}

	/**
	 * Returns the value of an attribute characterizing this context or null if not defined
	 */
	public function getAttribute($key) {
		if(is_null($key)) throw new DataFlowServiceException('dataflow context attribute key cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) return null;
		else return $this->attributes[$key];
	}

	/**
	 * Returns an iterator on the list of attributes attached to this context
	 * The iterator is compatible with the foreach control structure : foreach(DataFlowContext->getAttributeIterator() as $key=>$val){...}
	 */
	public function getAttributesIterator() {
		return $this->attributes;
	}

	/**
	 * Returns true if this context has some attributes defined (only counts the existence of keys, not the values)
	 */
	public function hasAttributes() {
		return !empty($this->attributes);
	}

	/**
	 * Returns the number of attributes attached to this context. Only counts the existence of keys, not the values.
	 */
	public function countAttributes() {
		return count($this->attributes);
	}

	/**
	 * Removes the given context attributes if defined
	 * Ignores invalid keys.
	 * @param $keys an array of keys or one single key
	 */
	public function removeAttributes($keys) {
		if(empty($this->attributes)) return;
		if(empty($keys)) return;
		if(is_array($keys)) $this->attributes = array_diff_key($this->attributes, array_combine($keys, $keys));
		else unset($this->attributes[$keys]);
	}

	/**
	 * Removes all context attributes, except the given array of keys if defined
	 */
	public function removeAllAttributes($exceptTheseKeys=null) {
		if(empty($this->attributes)) return;
		if(isset($exceptTheseKeys)) {
			if(is_array($exceptTheseKeys)) $this->attributes = array_intersect_key($this->attributes, array_combine($exceptTheseKeys, $exceptTheseKeys));
			else $this->attributes = array($exceptTheseKeys=>$this->attributes[$exceptTheseKeys]);
		}
		else unset($this->attributes);
	}

	// Wigii events

	private $checkedIfWigiiEventsAreEnabled;
	private $wigiiEventsEnabled;

	/**
	 * Returns true if WigiiEvents are enabled in this DataFlowContext
	 * If true, then it is worth calling getWigiiEventsDispatcher and trigger the right event.
	 * Else it does nothing.
	 */
	public function areWigiiEventsEnabled() {
		if(!$this->checkedIfWigiiEventsAreEnabled) {
			$this->wigiiEventsEnabled = $this->getDataFlowService()->areWigiiEventsEnabled($this);
		}
		return $this->wigiiEventsEnabled;
	}

	private $wigiiEventsDispatcher;
	/**
	 * Returns an object implementing WigiiEvents interface that can be used
	 * to throw wigii events to some listeners.
	 * @return WigiiEvents
	 */
	public function getWigiiEventsDispatcher() {
		if(!isset($this->wigiiEventsDispatcher)) {
			$this->wigiiEventsDispatcher = DataFlowContextWigiiEvents::createInstance($this);
		}
		return $this->wigiiEventsDispatcher;
	}

	// Implementation

	private function recycleObject($obj) {
		if(isset($obj) && method_exists($obj, "freeMemory")) $obj->freeMemory();
	}
}

class DataFlowContextWigiiEvents implements WigiiEvents {
	private $dataFlowContext;

	public static function createInstance($dataFlowContext) {
		$returnValue = new self();
		$returnValue->dataFlowContext = $dataFlowContext;
		return $returnValue;
	}

	/**
	 * readElement
	 * this event is thrown each time an element is displayed in a detail window
	 */
	public function readElement($pWithElement){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "read", "Element", $pWithElement->getElement()->getModule(), $pWithElement);
	}

	/**
	 * download file
	 * this event is throw each time a file is downloaded from an element
	 * this does not inclued the download of the thumbnail.
	 */
	public function downloadFileFromElement($pWithElementWithField){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "download", "Element", $pWithElementWithField->getElement()->getModule(), $pWithElementWithField);
	}

	/**
	 * insertElement
	 */
	public function insertElement($pWithElementWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "insert", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
	}

	/**
	 * change Element status
	 */
	public function updateElementState($pWithElementWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setState", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
	}
	/**
	 * updateElement
	 */
	public function updateElement($pWithElementWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "update", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
	}

	/**
	 * deleteElement
	 */
	public function deleteElement($pWithElementWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "delete", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
	}

	/**
	 * restore element
	 */
	public function restoreElement($pWithElementWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "restore", "Element", $pWithElementWithGroupPList->getElement()->getModule(), $pWithElementWithGroupPList);
	}

	/**
	 * insertMultipleElement
	 */
	public function insertMultipleElement($pWithModuleWithElementPListWithGroupList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "insert", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
	}

	/**
	 * updateMultipleElement
	 */
	public function updateMultipleElement($pWithRecordWithElementPListWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "update", "MultipleElement", $pWithRecordWithElementPListWithGroupPList->getRecord()->getModule(), $pWithRecordWithElementPListWithGroupPList);
	}

	/**
	 * change multiple Element status
	 */
	public function updateMultipleElementState($pWithModuleWithElementPListWithState){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setState", "MultipleElement", $pWithModuleWithElementPListWithState->getModule(), $pWithModuleWithElementPListWithState);
	}

	/**
	 * deleteMultipleElement
	 */
	public function deleteMultipleElements($pWithModuleWithElementPListWithGroupListWithArray){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "delete", "MultipleElement", $pWithModuleWithElementPListWithGroupListWithArray->getModule(), $pWithModuleWithElementPListWithGroupListWithArray);
	}

	/**
	 * sendFeedbackOnElement
	 */
	public function sendFeedbackOnElement($pWithElementWithRecord){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "sendFeedback", "Element", $pWithElementWithRecord->getElement()->getModule(), $pWithElementWithRecord);
	}

	/**
	 * sendFeedback
	 */
	public function sendFeedback($pWithModuleWithRecord){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "sendFeedback", "Module", $pWithModuleWithRecord->getModule(), $pWithModuleWithRecord);
	}

	/**
	 * Emailing
	 */
	public function Emailing($pWithModuleWithElementIdsWithRecord){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "Emailing", "MultipleElement", $pWithModuleWithElementIdsWithRecord->getModule(), $pWithModuleWithElementIdsWithRecord);
	}


	/**
	 * sendEmail
	 */
	public function sendEmail($pWithElementWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "sendEmail", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
	}

	/**
	 * sendExternalValidationLink
	 */
	public function sendExternalValidationLink($pWithElementWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "sendExternalValidationLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
	}

	/**
	 * setExternalAccessViewLink
	 */
	public function setExternalAccessViewLink($pWithElementWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setExternalAccessViewLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
	}

	/**
	 * setExternalAccessEditLink
	 */
	public function setExternalAccessEditLink($pWithElementWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setExternalAccessEditLink", "Element", $pWithElementWithEmailWithFieldname->getElement()->getModule(), $pWithElementWithEmailWithFieldname);
	}

	/**
	 * stopExternalAccessLink
	 */
	public function stopExternalAccessLink($pWithElementWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "stopExternalAccessLink", "Element", $pWithElementWithFieldname->getElement()->getModule(), $pWithElementWithFieldname);
	}

	/**
	 * sendMultipleExternalValidationLink
	 */
	public function sendMultipleExternalValidationLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "sendMultipleExternalValidationLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
	}

	/**
	 * setMultipleExternalAccessViewLink
	 */
	public function setMultipleExternalAccessViewLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setMultipleExternalAccessViewLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
	}

	/**
	 * setMultipleExternalAccessEditLink
	 */
	public function setMultipleExternalAccessEditLink($pWithModuleWithElementPlistWithEmailWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setMultipleExternalAccessEditLink", "MultipleElement", $pWithModuleWithElementPlistWithEmailWithFieldname->getModule(), $pWithModuleWithElementPlistWithEmailWithFieldname);
	}

	/**
	 * stopMultipleExternalAccessLink
	 */
	public function stopMultipleExternalAccessLink($pWithModuleWithElementPlistWithFieldname){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "stopMultipleExternalAccessLink", "MultipleElement", $pWithModuleWithElementPlistWithFieldname->getModule(), $pWithModuleWithElementPlistWithFieldname);
	}

	/**
	 * exportElements
	 */
	public function exportElements($pWithModuleWithElementIds){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "exportElements", "MultipleElement", $pWithModuleWithElementIds->getModule(), $pWithModuleWithElementIds);
	}

	/**
	 * shareElement
	 */
	public function shareElement($pWithElementWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "share", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
	}

	/**
	 * unshareElement
	 */
	public function unshareElement($pWithElementWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "unshare", "Element", $pWithElementWithGroup->getElement()->getModule(), $pWithElementWithGroup);
	}

	/**
	 * setMultipleElementSharing
	 */
	public function setMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "setShare", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
	}
	/**
	 * addMultipleElementSharing
	 */
	public function addMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "share", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
	}
	/**
	 * removeMultipleElementSharing
	 */
	public function removeMultipleElementSharing($pWithModuleWithElementPListWithGroupList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "unshare", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
	}
	/**
	 * moveMultipleElementToModule
	 * module is original module, destination module is group module
	 */
	public function moveMultipleElementToModule($pWithModuleWithElementPListWithGroupList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "moveToModule", "MultipleElement", $pWithModuleWithElementPListWithGroupList->getModule(), $pWithModuleWithElementPListWithGroupList);
	}

	/**
	 * navigate
	 */
	public function navigate($pWithUserIdWithWigiiNamespaceWithModule){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "navigate", "User", null, $pWithUserIdWithWigiiNamespaceWithModule);
	}

	/**
	 * selectGroup
	 */
	public function selectGroup($pWithGroupPList){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "selectGroup", "Group", null, $pWithGroupPList);
	}

	/**
	 * switchLanguage
	 */
	public function switchLanguage($pWithUserWithLanguage){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "switchLanguage", "User", null, $pWithUserWithLanguage);
	}

	/**
	 * changePassword
	 */
	public function changePassword($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "changePassword", "User", null, $pWithUser);
	}

	/**
	 * login
	 */
	public function login($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "login", "User", null, $pWithUser);
	}

	/**
	 * logout
	 */
	public function logout($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "logout", "User", null, $pWithUser);
	}

	/**
	 * insertGroup
	 */
	public function insertGroup($pWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "insert", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
	}

	/**
	 * updateGroup
	 */
	public function updateGroup($pWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "update", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
	}

	/**
	 * deleteGroup
	 */
	public function deleteGroup($pWithGroup){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "delete", "Group", $pWithGroup->getGroup()->getModule(), $pWithGroup);
	}

	/**
	 * insertUser
	 */
	public function insertUser($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "insert", "User", null, $pWithUser);
	}

	/**
	 * updateUser
	 */
	public function updateUser($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "update", "User", null, $pWithUser);
	}

	/**
	 * deleteUser
	 */
	public function deleteUser($pWithUser){
		$this->dataFlowContext->getDataFlowService()->dispatchWigiiEvent($this->dataFlowContext, "delete", "User", null, $pWithUser);
	}
}