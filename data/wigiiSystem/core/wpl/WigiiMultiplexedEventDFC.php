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
 * A wigii events subscriber which pushes multiplexed events into a dataflow
 * Created by CWE on 14 février 2014
 */
class WigiiMultiplexedEventDFC implements MultiplexedEvent
{
	private $_debugLogger;
	private $lockedForUse = true;	
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;				
	}	
	public function freeMemory() {
		unset($this->principal);
		unset($this->dfasl);	
		unset($this->dataFlowContext);	
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	/**
	 * Creates an instance of this wigii events subscriber.
	 * It results into an exclusive access object. By default memory is freed automatically by the endStream method, 
	 * but you can also free it explicitely by calling the freeMemory method.
	 * In any case, the freeMemory method must be called once to enable the ServiceProvider to recycle the object.
	 *
	 * @param Principal $principal Sets the principal that will execute the data flow processing the wigii events
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList Sets the DataFlowActivitySelectorList describing 
	 * the data flow to be executed with the wigii events.
	 * The data flow will be a flow of PHP StdClass with the attributes equal to the arguments of the 'event' method 
	 * of MultiplexedEvent interface :
	 * - eventName: String: the event name
	 * - entityName: String: the entity name, for instance 'element','group', etc. See class WigiiEventsDispatcher for more details.
	 * - module: Module: the Wigii module concerned by this event
	 * - object: Object: an object given addition details about the event. See interface WigiiEvents and classes in package model/eventModel for more details.
	 */
	public static function createInstance($principal, $dataFlowActivitySelectorList) {
		$returnValue = ServiceProvider::getExclusiveAccessObject('WigiiMultiplexedEventDFC');
		$returnValue->setPrincipal($principal);
		$returnValue->setDataFlowActivitySelectorList($dataFlowActivitySelectorList);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiMultiplexedEventDFC");
		}
		return $this->_debugLogger;
	}	
	
	private $dflowS;
	public function setDataFlowService($dataFlowService)
	{
		$this->dflowS = $dataFlowService;
	}
	protected function getDataFlowService()
	{
		// autowired
		if(!isset($this->dflowS))
		{
			$this->dflowS = ServiceProvider::getDataFlowService();
		}
		return $this->dflowS;
	}
	
	// Configuration
	
	private $principal;
	/**
	 * Sets the principal that will execute the data flow processing the wigii events
	 */
	public function setPrincipal($principal) {
		$this->principal = $principal;
	}
	
	private $dfasl;
	/**
	 * Sets the DataFlowActivitySelectorList describing the data flow to be executed with the wigii events.
	 * The data flow will be a flow of PHP StdClass with the attributes equal to the arguments of the 'event' method 
	 * of MultiplexedEvent interface :
	 * - eventName: String: the event name
	 * - entityName: String: the entity name, for instance 'element','group', etc. See class WigiiEventsDispatcher for more details.
	 * - module: Module: the Wigii module concerned by this event
	 * - object: Object: an object given addition details about the event. See interface WigiiEvents and classes in package model/eventModel for more details. 
	 */
	public function setDataFlowActivitySelectorList($dfasl) {
		$this->dfasl = $dfasl;
	}		

	// Wigii events handling
	
	/**
	 * Indicates to this subscriber that no more wigii events will be generated.
	 * The connected data flow can be ended.
	 * @param boolean $freeMemory if freeMemory is true, then the method freeMemory is automatically call after ending the stream.
	 * Else the programmer should call once later the freeMemory method to enable the ServiceProvider to recycle the object.
	 */
	public function endStream($freeMemory=true) {
		if(isset($this->dataFlowContext)) {
			$this->getDataFlowService()->endStream($this->dataFlowContext);
		}
		if($freeMemory) $this->freeMemory();
	}
	
	public function event($eventName, $entityName, $module, $object) {
		// builds std class instance
		$obj = (object)array('eventName' => $eventName, 'entityName' => $entityName, 'module' => $module, 'object' => $object);
		// pushes object into the data flow
		$this->getDataFlowService()->processDataChunk($obj, $this->getDataFlowContext());
	}
	
	// Implementation
	
	private $dataFlowContext;
	/**
	 * Gets the data flow context of the data flow into which to push the wigii events
	 * @throws DataFlowServiceException in case of error.
	 */
	protected function getDataFlowContext() {
		if(!isset($this->dataFlowContext)) {
			if(!isset($this->principal) || !isset($this->dfasl)) throw new DataFlowServiceException("principal or DataFlowActivitySelectorList have not been set. The two are mandatory.", DataFlowServiceException::CONFIGURATION_ERROR);
			$this->dataFlowContext = $this->getDataFlowService()->startStream($this->principal, $this->dfasl);
		}
		return $this->dataFlowContext;
	}
}