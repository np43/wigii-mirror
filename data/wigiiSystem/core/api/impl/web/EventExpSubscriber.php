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
 * Wigii Events expression handler
 * Created on 19 September 2018 by LWR
 * Modified by CWE on 22.07.2019 to allow delegated FuncExp event handlers to check event validity before acting on event
 */
class EventExpSubscriber implements MultiplexedEvent {

	private $_debugLogger;
	private $_executionSink;
	private $wigiiExecutor;
	private $vmContext;
	
	// Object lifecycle
	
	public function __construct() {
		$this->debugLogger()->write("creating instance");
	}
	
	/*
	 * dependency injection
	 */
		
	private function debugLogger()
	{
	    if(!isset($this->_debugLogger))
	    {
	        $this->_debugLogger = DebugLogger::getInstance("EventExpSubscriber");
	    }
	    return $this->_debugLogger;
	}
	private function executionSink()
	{
	    if(!isset($this->_executionSink))
	    {
	        $this->_executionSink = ExecutionSink::getInstance("EventExpSubscriber");
	    }
	    return $this->_executionSink;
	}
	
	
	public function setWigiiExecutor($wigiiExecutor)
	{
		$this->wigiiExecutor = $wigiiExecutor;
	}
	protected function getWigiiExecutor()
	{
		return $this->wigiiExecutor;
	}

	
	/*
	 * service implementation
	 */

	public function event($eventName, $entityName, $module, $object){
		$exec = ServiceProvider::getExecutionService();
		$returnValue = null;
		$p = $object->getP();
		// gets eventExp
		$eventExp = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $module, "eventExp");
		if($eventExp==null) return null;
		$eventExp=str2fx($eventExp);
		// gets attached element
		$rec = null;
		if(method_exists($object,"getElement")){
			$rec = $object->getElement();
		}
		// loads FuncExp vm and injects event details in scope
		$funcExpVM = $this->getWigiiExecutor()->getFuncExpEvaluator($p, $exec, $rec);
		$this->vmContext = $funcExpVM->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		$this->vmContext->setVariable(fs('wigiiEventObject'), $object);
		$this->vmContext->setVariable(fs('wigiiEventName'), $eventName);
		$this->vmContext->setVariable(fs('wigiiEventEntity'), $entityName);
		$this->vmContext->setVariable(fs('wigiiEventSubscriber'), $this);
		// executes event exp
		try {
			$returnValue = $funcExpVM->evaluateFuncExp($eventExp, $this);
			$this->vmContext->clearAllVariables();
			$this->vmContext = null;
			$funcExpVM->freeMemory();
		} catch(Exception $e) {
		    $this->vmContext->clearAllVariables();
		    $this->vmContext = null;
		    $funcExpVM->freeMemory();			
			throw $e;
		}
		return $returnValue;
	}

	/**
	 * Asserts that given event is currently beeing handled. 
	 * This method is called by delegated FuncExps to check event validity.
	 * @param String $eventName event name as found in variable wigiiEventName.
	 * @param String $entityName event entity as found in variable wigiiEventEntity.
	 * @param Object $object event object as found in variable wigiiEventObject
	 * @return boolean returns true if described event is currently beeing handled.
	 * @throws ServiceException::INVALID_STATE if described event is not currently beeing handled by this EventExpSubscriber.
	 */
	public function assertOnEvent($eventName, $entityName, $object) {
	    $validator = ArgValidator::getInstance();
	    $errorMsg = 'not currently handling event '.$entityName.' '.$eventName;
	    $validator->assert($this->vmContext!=null,$errorMsg,ServiceException::INVALID_STATE);
	    $validator->assert($this->vmContext->getVariable(fs('wigiiEventName'))==$eventName,$errorMsg,ServiceException::INVALID_STATE);
	    $validator->assert($this->vmContext->getVariable(fs('wigiiEventEntity'))==$entityName,$errorMsg,ServiceException::INVALID_STATE);
	    $validator->assert($this->vmContext->getVariable(fs('wigiiEventObject'))===$object,$errorMsg,ServiceException::INVALID_STATE);
	    return true;
	}
}



