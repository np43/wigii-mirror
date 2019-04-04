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
 * A data flow activity which updates the element sharing
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 19.07.2018
 */
class ElementUpdateSharingDFA implements DataFlowActivity
{			
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
	}	
	public function freeMemory() {
		unset($this->fieldName);
		unset($this->newGroupIds);
		unset($this->oldGroupIds);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementUpdateSharingDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $newGroupIds;
	/**
	 * Gives a list of new group ids in which to share the element
	 * @param Array $newGroupIds Array of group IDs in which to share the element. 
	 * If not given, calls the method getLinkedIdGroupInRecord on the element to determine in which group to share the element.
	 * If sharing groups are determined based on element values, then movePriority is also considered and 
	 * if appropriate, the element will be moved in corresponding folder instead of shared.
	 */
	public function setNewGroupIds($newGroupIds) {
		$this->newGroupIds= $newGroupIds;
	}
	
	private $oldGroupIds;
	/**
	 * Gives a list of old deprecated group ids from which to remove the sharing
	 * @param Array|FieldSelector $oldGroupIds An array with the existing/deprecated groups from which to remove the sharing.
	 * Can also be an element attribute field selector in which an array has been stored using the getLinkedGroupIds() FuncExp 
	 * If a group ID is present in newGroupIds and oldGroupIds then sharing will remain active.
	 */
	public function setOldGroupIds($oldGroupIds) {
		$this->oldGroupIds= $oldGroupIds;
	}
	
	private $addSharingOnly;
	/**
	 * If true, then sharing is only added not removed.
	 * Defaults to update (remove and add)
	 */
	public function setAddSharingOnly($bool) {
		$this->addSharingOnly = $bool;		
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
	}
	public function processDataChunk($data, $dataFlowContext) {
		// extracts element
		$element = $data->getDbEntity();
		// calculates old group ids
		if($this->addSharingOnly) $oldGids = null;
		else {
			$oldGids = $this->oldGroupIds;
			if($oldGids instanceof FieldSelector && $oldGids->isElementAttributeSelector()) {
				$oldGids = $element->getAttribute($oldGids);
			}
		}
		// recalculates the values
		ServiceProvider::getWigiiBPL()->elementUpdateSharing($dataFlowContext->getPrincipal(), $this, wigiiBPLParam(
		    	"element",$element,
				"newGroupIds",$this->newGroupIds,
				"oldGroupIds",$oldGids,
				"refreshGUI", $this->refreshGUI,
				"wigiiEventsSubscriber",($dataFlowContext->areWigiiEventsEnabled() && $dataFlowContext->getWigiiEventsDispatcher()?$dataFlowContext->getWigiiEventsDispatcher():false)
			)
		);
		// pushes data further down in the flow
		$dataFlowContext->writeResultToOutput($data,$this);
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}