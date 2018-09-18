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

/*
 * Created on 19 September 2018
 * by LWR
 */

class EventExpSubscriber implements MultiplexedEvent {

	private $_debugLogger;
	private $_executionSink;
	private $wigiiExecutor;
	private $enabled;
	public function setEnabled($var){ $this->enabled = $var; }
	public function getEnabled(){ return $this->enabled; }

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
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	/*
	 * dependy injection
	 */
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
// 		eput($eventName);
		$eventExp = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $module, "eventExp");
		if($eventExp==null) return null;
// 		eput($eventExp);
		$eventExp=str2fx($eventExp);
		$rec = null;
		if(method_exists($object,"getElement")){
			$rec = $object->getElement();
		}
		$funcExpVM = $this->getWigiiExecutor()->getFuncExpEvaluator($p, $exec, $rec);
		$ctx = $funcExpVM->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
		// stores result into variable 'value'
		$ctx->setVariable(fs('wigiiEventObject'), $object);
		$ctx->setVariable(fs('wigiiEventName'), $eventName);
		$ctx->setVariable(fs('wigiiEventEntity'), $entityName);
		try {
// 			eput("eval funcExp");
			$returnValue = $funcExpVM->evaluateFuncExp($eventExp, $this);
			$funcExpVM->freeMemory();
		} catch(Exception $e) {
			$funcExpVM->freeMemory();
			throw $e;
		}
// 		eput($returnValue);
		return $returnValue;
	}

}



