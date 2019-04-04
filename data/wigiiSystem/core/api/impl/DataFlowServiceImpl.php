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
 * The data flow service implementation
 * Created by CWE on 28 mai 2013
 * Modified by Medair (CWE) 15.12.2017 to add public method clearDataSourceCache
 */
class DataFlowServiceImpl implements DataFlowService
{
	private $_executionSink;
	private $_debugLogger;

	
	// System principal management
	
	/**
	 * Adds a system principal or a list of system principals to the DataFlowServiceImpl
	 */
	public function addSystemPrincipal($systemPrincipal)
	{
		if(is_null($systemPrincipal)) return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received ".$systemPrincipal->count()." system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the DataFlowServiceImpl
	 */
	protected function getSystemPrincipals()
	{
		//autowired
		if(!isset($this->systemPrincipals))
		{
			$this->systemPrincipals = PrincipalListArrayImpl::createInstance();
		}
		return $this->systemPrincipals;
	}
	/**
	 * Gets the root principal
	 */
	protected function getRootPrincipal()
	{
		$returnValue = ServiceProvider::getAuthorizationService()->findRootPrincipal($this->getSystemPrincipals());
		if(is_null($returnValue)) throw new AuthorizationServiceException("root principal has not been initialized by Service Provider", AuthorizationServiceException::FORBIDDEN);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DataFlowServiceImpl");
		}
		return $this->_executionSink;
	}
	
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("DataFlowServiceImpl");
		}
		return $this->_debugLogger;
	}	
	
	// Data flow sources and processing

	public function processString($principal, $str, $dataFlowActivitySelectorList, $wigiiEventsSubscriber = null) {
		return $this->processWholeData($principal, $str, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
	}

	public function processFieldList($principal, $fieldList, $dataFlowActivitySelectorList, $fieldSelectorList=null, $wigiiEventsSubscriber = null) {
		if(is_null($fieldList)) return;
		$returnValue = null;		
		// merges FieldList and FieldSelectorList
		$fieldWithSelectedSubfieldsList = FieldWithSelectedSubfieldsListArrayImpl::createInstance($fieldList, $fieldSelectorList);
		if(!$fieldWithSelectedSubfieldsList->isEmpty()) {
			// starts the stream			
			$dataFlowContext = $this->startStream($principal, $dataFlowActivitySelectorList);
			$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			try {
				// processes each field
				foreach($fieldWithSelectedSubfieldsList->getListIterator() as $field) {				
					$this->processDataChunk($field, $dataFlowContext);
				}
				// ends the stream
				$returnValue = $this->endStream($dataFlowContext);
			}
			catch(Exception $e) {
				$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
				throw $e;
			}
			$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
		}
		return $returnValue;		
	}
	
	public function processObjectList($principal, $objectList, $dataFlowActivitySelectorList, $wigiiEventsSubscriber = null) {
		if(is_null($objectList)) return;
		$returnValue = null;				
		if(!$objectList->isEmpty()) {
			// starts the stream			
			$dataFlowContext = $this->startStream($principal, $dataFlowActivitySelectorList);
			$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			try {
				// processes each object
				foreach($objectList->getListIterator() as $o) {				
					$this->processDataChunk($o, $dataFlowContext);
				}
				// ends the stream
				$returnValue = $this->endStream($dataFlowContext);
			}
			catch(Exception $e) {
				$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
				throw $e;
			}
			$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
		}
		return $returnValue;
	}
	
	public function processInputDataFlow($principal, $inputDataFlow, $dataFlowActivitySelectorList, 
		$freeMemoryOfSource=true, $wigiiEventsSubscriber = null, $cacheKey=null) {		
		if(is_null($inputDataFlow)) {
			$this->debugLogger()->write("inputDataFlow is null");
			return;
		}
		// if caching is active
		if(isset($cacheKey)) {
			// 1. caches the flow content if not already in cache
			$cacheKey = "(processInputDataFlow($cacheKey))";
			if(!$this->isDataSourceInCache($cacheKey)) {
				$this->cacheDataSource($principal, $cacheKey, $inputDataFlow, $freeMemoryOfSource, $wigiiEventsSubscriber);
			}
			// 2. dumps the cache into the flow
			return $this->processDataSourceFromCache($principal, $cacheKey, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
		}
		// else processes the flow
		else {
			$returnValue = null;
			// starts the input flow
			$inputDataFlow->startStream();			
			if($inputDataFlow->hasData()) {
				// starts the stream			
				$dataFlowContext = $this->startStream($principal, $dataFlowActivitySelectorList);
				$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
				try {
					// processes each data chunk
					while($inputDataFlow->hasData()) {
						$this->processDataChunk($inputDataFlow->readDataChunk(), $dataFlowContext);
					}
					// ends the input flow
					$inputDataFlow->endStream();	
					// ends the stream
					$returnValue = $this->endStream($dataFlowContext);
				}
				catch(Exception $e) {
					$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
					throw $e;
				}
				$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			}
			else $inputDataFlow->endStream();	
			if($freeMemoryOfSource && method_exists($inputDataFlow, 'freeMemory')) $inputDataFlow->freeMemory();
			return $returnValue;
		}
	}

	public function processDumpableObject($principal, $dataFlowDumpable, $dataFlowActivitySelectorList, 
		$freeMemoryOfSource=true, $wigiiEventsSubscriber=null, $cacheKey=null) {
		if(is_null($dataFlowDumpable)) {
			$this->debugLogger()->write("dataFlowDumpable is null");
			return;
		}
		// if caching is active
		if(isset($cacheKey)) {
			// 1. caches the flow content if not already in cache
			$cacheKey = "(processDumpableObject($cacheKey))";
			if(!$this->isDataSourceInCache($cacheKey)) {
				$this->cacheDataSource($principal, $cacheKey, $dataFlowDumpable, $freeMemoryOfSource, $wigiiEventsSubscriber);
			}
			// 2. dumps the cache into the flow
			return $this->processDataSourceFromCache($principal, $cacheKey, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
		}
		// else processes the flow
		else {
			$dataFlowContext = $this->startStream($principal, $dataFlowActivitySelectorList);
			$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			try {
				$dataFlowDumpable->dumpIntoDataFlow($this, $dataFlowContext);
				$returnValue = $this->endStream($dataFlowContext);			
			}
			catch(Exception $e) {
				$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
				throw $e;
			}
			$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);		
			if($freeMemoryOfSource && method_exists($dataFlowDumpable, 'freeMemory')) $dataFlowDumpable->freeMemory();
			return $returnValue;
		}
	}
	
	public function processDataFlowSelector($principal, $dataFlowSelector, 
		$freeMemoryOfSource=true, $wigiiEventsSubscriber=null, $cacheKey=null) {
		if(is_null($dataFlowSelector)) throw new DataFlowServiceException("dataFlowSelector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$src = $dataFlowSelector->getSource();
		$dfasl = $dataFlowSelector->getDataFlowActivitySelectorList();
		if(is_null($src)) {
			$this->debugLogger()->write("dataflow source is null");
			return;
		}
		else return $this->processDataSource($principal, $src, $dfasl, $freeMemoryOfSource, $wigiiEventsSubscriber, $cacheKey);	
	}
	
	public function processDataFlowSelectorList($principal, $dataFlowSelectorList, $dataFlowResultList=null,
			$freeMemoryOfSource=true, $wigiiEventsSubscriber=null) {
		if(isset($dataFlowSelectorList) && !$dataFlowSelectorList->isEmpty()) {			
			foreach($dataFlowSelectorList as $key => $dfs) {
				$returnValue = $this->processDataFlowSelector($principal, $dfs, $freeMemoryOfSource, $wigiiEventsSubscriber);
				if(isset($returnValue) && isset($dataFlowResultList)) $dataFlowResultList->addDataFlowResult($returnValue, $key);
			}
			return $dataFlowResultList;
		}
		else return null;
	}
		
	public function processDataSource($principal, $source, $dataFlowActivitySelectorList,
			$freeMemoryOfSource=true, $wigiiEventsSubscriber=null, $cacheKey=null) {
		if(is_null($source)) {
			$this->debugLogger()->write("dataflow source is null");
			return;
		}
		elseif($source instanceof DataFlowSelector) {
			// if caching is active
			if(isset($cacheKey)) {
				// 1. caches the flow content if not already in cache
				$cacheKey = "(processDataFlowSelector($cacheKey))";
				if(!$this->isDataSourceInCache($cacheKey)) {
					$this->cacheDataSource($principal, $cacheKey, $source, $freeMemoryOfSource, $wigiiEventsSubscriber);
				}
				// 2. dumps the cache into the flow
				return $this->processDataSourceFromCache($principal, $cacheKey, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
			}
			// else processes the flow
			else {
				// appends data flow activities to source DFASL
				$sourceDfasl = $source->getDataFlowActivitySelectorList();
				if(isset($dataFlowActivitySelectorList)) {
					foreach($dataFlowActivitySelectorList->getListIterator() as $dfas) {
						$sourceDfasl->addDataFlowActivitySelectorInstance($dfas);
					}
				}
				// processes DataFlowSelector
				return $this->processDataFlowSelector($principal, $source, $freeMemoryOfSource, $wigiiEventsSubscriber, null/*no cache*/);
			}
		}
		elseif($source instanceof DataFlowDumpable) {
			return $this->processDumpableObject($principal, $source, $dataFlowActivitySelectorList, $freeMemoryOfSource, $wigiiEventsSubscriber, $cacheKey);
		}
		elseif($source instanceof ObjectList) {
			return $this->processObjectList($principal, $source, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
		}
		elseif($source instanceof InputDataFlow) {
			return $this->processInputDataFlow($principal, $source, $dataFlowActivitySelectorList, $freeMemoryOfSource, $wigiiEventsSubscriber, $cacheKey);
		}
		elseif(is_array($source)) {			
			return $this->processDumpableObject($principal, array2df($source), $dataFlowActivitySelectorList, $freeMemoryOfSource, $wigiiEventsSubscriber, null/*array can already be cached by caller, therefore no internal caching*/);
		}
		else {
			return $this->processWholeData($principal, $source, $dataFlowActivitySelectorList, $wigiiEventsSubscriber);
		}
	}
	
	// Data flow connector

	public function startStream($principal, $dataFlowActivitySelectorList) {
		$returnValue = $this->getDataFlowContext($principal, $dataFlowActivitySelectorList);
		$this->executionSink()->publishStartOperation("dataflow_".$returnValue->getContextId(), $principal);
		return $returnValue;
	}

	public function processDataChunk($data, $dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("dataFlowContext cannot be null. The method startStream should be called before calling processDataChunk", DataFlowServiceException::INVALID_ARGUMENT);
		$contextId = $dataFlowContext->getContextId();		
		$stepId = $this->getFirstStepId($dataFlowContext);
				
		// updates the execution context
		$dataFlowContext->setStepId($stepId);
		// gets or creates first activity
		$dfa = $this->getOpenedDataFlowActivityForStep($stepId, $contextId);
		if(!isset($dfa)) {
			$dfa = $this->getDataFlowActivity($this->getDataFlowActivitySelectorForStep($stepId, $dataFlowContext));
			// stores the DataFlowActivity
			$this->storeOpenedDataFlowActivityForStep($stepId, $contextId, $dfa);
			// starts the stream
			$dfa->startOfStream($dataFlowContext);
		}
		// pushes the data chunk to the DataFlowActivity
		$dfa->processDataChunk($data, $dataFlowContext);
	}

	public function endStream($dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("dataFlowContext cannot be null. The method startStream should be called before calling endStream", DataFlowServiceException::INVALID_ARGUMENT);
		$contextId = $dataFlowContext->getContextId();
		try
		{			
			$stepId = $this->getFirstStepId($dataFlowContext);
			$continue = true; $returnValue = null; $singleDfa = null;
			while($continue) {
				// if current step is the last step then
				// closes the stream if open and returns the value stored in the buffer
				if($this->isStepTheLastStep($stepId, $dataFlowContext)) {
					if(isset($singleDfa)) {
						$returnValue = $this->consumeSingleValueFromStepBuffer($contextId, $stepId);
						$this->recycleDataFlowActivity($singleDfa);
						$singleDfa = null;
					}
					else {
						$dfa = $this->getOpenedDataFlowActivityForStep($stepId, $contextId);
						if(isset($dfa)) {
							$dfa->endOfStream($dataFlowContext);
							$returnValue = $this->consumeSingleValueFromStepBuffer($contextId, $stepId);
							$this->recycleDataFlowActivityForStep($stepId, $contextId);
						}
					}
					$continue = false;
				}
				// else:
				else {
					if(isset($singleDfa)) {
						$this->recycleDataFlowActivity($singleDfa);
						$singleDfa = null;
					}
					else {
						$dfa = $this->getOpenedDataFlowActivityForStep($stepId, $contextId);
						if(isset($dfa)) {
							// closes current step stream
							$dfa->endOfStream($dataFlowContext);
							$this->recycleDataFlowActivityForStep($stepId, $contextId);
						}
					}
					$bcount = $this->stepBufferCount($contextId, $stepId);
					$newStepId = $this->getNextStepId($stepId, $dataFlowContext);
					$dfa = $this->getOpenedDataFlowActivityForStep($newStepId, $contextId);
						
					// if buffer has no elements and next step not instanciated then stops
					if($bcount == 0 && !isset($dfa)) $continue = false;
					// if buffer has only one element and next step not instanciated then process single data
					elseif($bcount == 1 && !isset($dfa)) {
						$singleDfa = $this->getDataFlowActivity($this->getDataFlowActivitySelectorForStep($newStepId, $dataFlowContext));
						$dataFlowContext->setStepId($newStepId);
						$singleDfa->processWholeData($this->consumeSingleValueFromStepBuffer($contextId, $stepId), $dataFlowContext);
					}
					// else if buffer has some elements then flushes into next step
					elseif($bcount > 0) {
						// updates the execution context
						$dataFlowContext->setStepId($newStepId);
						// if next step is not already instanciated, instanciates it and opens the stream
						if(!isset($dfa)) {
							$dfa = $this->getDataFlowActivity($this->getDataFlowActivitySelectorForStep($newStepId, $dataFlowContext));
							// stores the DataFlowActivity
							$this->storeOpenedDataFlowActivityForStep($newStepId, $contextId, $dfa);
							// starts the stream
							$dfa->startOfStream($dataFlowContext);
						}
						// flushes the step buffer
						$this->flushStepBufferIntoActivity($contextId, $stepId, $dfa, $dataFlowContext);
					}
					else $dataFlowContext->setStepId($newStepId);
					$stepId = $newStepId;
				}
			}			
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("dataflow_".$contextId, $e, $dataFlowContext->getPrincipal());
			$this->recycleDataFlowContext($dataFlowContext);
			throw $e;
		}	
		$this->executionSink()->publishEndOperation("dataflow_".$contextId, $dataFlowContext->getPrincipal());
		$this->recycleDataFlowContext($dataFlowContext);
		return $returnValue;
	}

	public function processWholeData($principal, $data, $dataFlowActivitySelectorList, $wigiiEventsSubscriber=null) {
		// creates a context
		$dataFlowContext = $this->getDataFlowContext($principal, $dataFlowActivitySelectorList);
		$contextId = $dataFlowContext->getContextId();
		$this->executionSink()->publishStartOperation("dataflow_".$contextId, $principal);
		$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
		try {
			$stepId = $this->getFirstStepId($dataFlowContext);		
			$dataFlowContext->setStepId($stepId);
			// calls processWholeData on first step
			$dfa = $this->getDataFlowActivity($this->getDataFlowActivitySelectorForStep($stepId, $dataFlowContext));
			$dfa->processWholeData($data, $dataFlowContext);
			$this->recycleDataFlowActivity($dfa);
			// if first step is also last step, then returns buffer value
			$returnValue = null;
			if($this->isStepTheLastStep($stepId, $dataFlowContext)) {
				$returnValue = $this->consumeSingleValueFromStepBuffer($contextId, $stepId);
				$this->executionSink()->publishEndOperation("dataflow_".$contextId, $principal);
			}
			// else closes all streams:
			else $returnValue = $this->endStream($dataFlowContext);
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("dataflow_".$contextId, $e, $principal);
			$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			throw $e;
		}	
		$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
		return $returnValue;		
	}


	// Data Flow activity result callback

	public function processResultFromActivity($dataFlowContext, $resultData, $dataFlowActivity) {
		$contextId = $dataFlowContext->getContextId();
		$stepId = $dataFlowContext->getStepId();		
		// if last step --> stores result into buffer except if a result already exists
		if($this->isStepTheLastStep($stepId, $dataFlowContext)) {
			if($this->stepBufferCount($contextId, $stepId) == 0) {
				$this->storeResultDataIntoStepBuffer($contextId, $stepId, $resultData);
			}
			else throw new DataFlowServiceException("On the DataFlow last step, only one result can be generated. You should add a new step to merge the multiple results into one.", DataFlowServiceException::INVALID_STATE);
		}
		// else stores result into buffer and flushes it into next step if full
		else {
			//$this->debugLogger()->write("processing output for data flow $contextId and step $stepId");
			$this->storeResultDataIntoStepBuffer($contextId, $stepId, $resultData);
			if($this->isStepBufferFull($contextId, $stepId)) {
				$newStepId = $this->getNextStepId($stepId, $dataFlowContext);
				// updates the execution context
				$dataFlowContext->setStepId($newStepId);
				// gets next step stream
				$dfa = $this->getOpenedDataFlowActivityForStep($newStepId, $contextId);
				// if next step is not already instanciated, instanciates it and opens the stream
				if(!isset($dfa)) {
					$dfa = $this->getDataFlowActivity($this->getDataFlowActivitySelectorForStep($newStepId, $dataFlowContext));
					// stores the DataFlowActivity
					$this->storeOpenedDataFlowActivityForStep($newStepId, $contextId, $dfa);
					// starts the stream
					$dfa->startOfStream($dataFlowContext);
				}
				// flushes the step buffer
				$this->flushStepBufferIntoActivity($contextId, $stepId, $dfa, $dataFlowContext);
				$dataFlowContext->setStepId($stepId);
			}
		}
	}
	
	public function dispatchWigiiEvent($dataFlowContext, $eventName, $entityName, $module, $object) {
		if($this->areWigiiEventsEnabled($dataFlowContext)) {
			$i = $this->dfContextWigiiEventsSubscriber[$dataFlowContext->getContextId()] - 1;
			while($i >= 0) {
				$s = $this->wigiiEventsSubscriberStack[$i];
				if($s === false) break;
				if($s instanceof MultiplexedEvent) {				
					$s->event($eventName, $entityName, $module, $object);
				}
				elseif($s instanceof WigiiEvents) {
					$m = $eventName;
					if(method_exists($s, $m)) {
						$s->{$m}($object);
					}
					else {
						$m = $eventName.$entityName;
						if(method_exists($s, $m)) {
							$s->{$m}($object);
						}
					}					
				}
				$i--;
			}
		}
	}
	
	public function areWigiiEventsEnabled($dataFlowContext) {
		$returnValue = false;
		if($this->wigiiEventsSubscriberStackPointer >= 0) { 		
			$i = $this->dfContextWigiiEventsSubscriber[$dataFlowContext->getContextId()];
			if($i > 0 && $i-1 <= $this->wigiiEventsSubscriberStackPointer) {
				$returnValue = ($this->wigiiEventsSubscriberStack[$i-1] !== false);
			}
		}
		return $returnValue;
	}	

	// DataFlow activities service provider
		
	private $wigiiXmlSaxParserDFASelector = null;
	
	public function getWigiiXmlSaxParserDFA($dataFlowContext) {
		if(is_null($this->wigiiXmlSaxParserDFASelector)) $this->wigiiXmlSaxParserDFASelector = DataFlowActivitySelector::createInstance('WigiiXmlSaxParserDFA');
		return $this->getDataFlowActivity($this->wigiiXmlSaxParserDFASelector);
	}	
	
	public function recycleWigiiXmlSaxParserDFA($saxParser, $dataFlowContext) {
		$this->recycleDataFlowActivity($saxParser);
	}	
	
	// DataFlow activities output filter service provider
	
	private $wigiiXmlWriterDFAOFSelector = null;
	
	public function getWigiiXmlWriterDFAOF($dataFlowContext, $dataFlowActivity) {
		if(is_null($this->wigiiXmlWriterDFAOFSelector)) $this->wigiiXmlWriterDFAOFSelector = DataFlowActivitySelector::createInstance('WigiiXmlWriterDFAOF');
		$returnValue = $this->getDataFlowActivity($this->wigiiXmlWriterDFAOFSelector);
		$returnValue->setDataFlowContext($dataFlowContext, $dataFlowActivity);
		return $returnValue;
	}
		
	public function recycleWigiiXmlWriterDFAOF($xmlWriter, $dataFlowContext) {
		$this->recycleDataFlowActivity($xmlWriter);
	}
	
	// Configuration
	
	private $stepBufferLimit = 2;
	/**
	 * Sets the execution strategy of the DataFlow
	 * If true : executes the DataFlow in Depth First mode (each data chunk goes through the whole data flow, one after the other)
	 * If false: executes the DataFlow in Breadth First mode (processes all the data chunks at each step, one step after the other)
	 */
	public function setDepthFirstExecution($bool) {
		if($bool) $this->stepBufferLimit = 2; // buffer limit is 2 to be able to choose between stream or single data
		else $this->stepBufferLimit = -1; // buffer limit is -1 <=> unlimited.
	}
	public function isDepthFirstExecution() {
		return $this->stepBufferLimit == 2;
	}
	
	// Implementation

	private $dataFlowContextIDGenerator = 0;
	const DATAFLOWCONTEXTIDGENERATOR_MAXKEY = 65535; 
	private $freeDataFlowContexts = array();
	private $usedDataFlowContexts = array();
	private $freeDataFlowActivities = array();
	private $usedDataFlowActivities = array();
	private $runningDataFlows = array();
	private $stepBuffer = array();	
	
	private function getDataFlowContextID() {
		$k = $this->dataFlowContextIDGenerator;
		$continue = true; $returnValue = $k;
		while($continue) {
			$continue = isset($this->usedDataFlowContexts[$k]) || isset($this->freeDataFlowContexts[$k]);
			if(!$continue) $returnValue = $k;
			$k++;
			if($k > self::DATAFLOWCONTEXTIDGENERATOR_MAXKEY) $k = 0;
			if($continue && $k == $this->dataFlowContextIDGenerator) throw new DataFlowServiceException("no id available for a new DataFlowContext", DataFlowServiceException::UNEXPECTED_ERROR);			
		}
		$this->dataFlowContextIDGenerator = $k;
		return $returnValue;
	}
	
	/**
	 * Returns a recycled or new DataFlowContext
	 * @param Principal $principal authenticated user performing the operation
	 * @param DataFlowActivitySelectorList $dataFlowActivitySelectorList cannot be null or empty
	 */
	protected function getDataFlowContext($principal, $dataFlowActivitySelectorList) {
		if(is_null($dataFlowActivitySelectorList) || $dataFlowActivitySelectorList->isEmpty())
			throw new DataFlowServiceException("dataFlowActivitySelectorList cannot be null or empty", DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue = array_pop($this->freeDataFlowContexts);
		// if no free context, then creates a new one
		if(is_null($returnValue)) $returnValue = $this->createDataFlowContextInstance();
		// if context ID is not set or is used, then generates a new one
		$contextId = $returnValue->getContextId();		
		if(is_null($contextId) || isset($this->usedDataFlowContexts[$contextId])) $contextId = $this->getDataFlowContextID();
		// initializes the DataFlowContext instance
		$returnValue->reset();
		$returnValue->setContextId($contextId);
		$returnValue->setPrincipal($principal);
		$returnValue->setDataFlowActivitySelectorList($dataFlowActivitySelectorList);
		// stores DataFlowContext in used list
		$this->usedDataFlowContexts[$contextId] = $returnValue;
		return $returnValue;
	}
	/**
	 * Defaults to DataFlowContext
	 */
	protected function createDataFlowContextInstance() {
		return DataFlowContext::createInstance($this);
	}
	
	/**
	 * Recycles a DataFlowContext
	 * Precondition: assumes that endOfStream has already been called and
	 * that it is valid to prepare the object to receive a new call of startOfStream
	 */
	protected function recycleDataFlowContext($dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("dataFlowContext cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$contextId = $dataFlowContext->getContextId();
		// retrieves used dataflowcontext
		$dataFlowContext = $this->usedDataFlowContexts[$contextId];
		$this->usedDataFlowContexts[$contextId] = null;
		// stores it into the list of free context
		if(isset($dataFlowContext)) {
			$dataFlowContext->freeMemory();
			$this->freeDataFlowContexts[] = $dataFlowContext;
		}
		// frees DataFlow from running dataflows
		$this->runningDataFlows[$contextId] = null;
		// frees Step buffer
		$this->stepBuffer[$contextId] = null;
	}
	
	/**
	 * Returns a new free and reseted DataFlowActivity instance given a selector
	 * Postcondition: the parameter values defined into the data flow activity selector
	 * are passed to the data flow activity instance.
	 */
	protected function getDataFlowActivity($dataFlowActivitySelector) {
		if(is_null($dataFlowActivitySelector)) throw new DataFlowServiceException("dataFlowActivitySelector cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$className = $dataFlowActivitySelector->getClassName();
		if(is_null($className) || $className == '') throw new DataFlowServiceException("dataFlowActivitySelector has not class name defined", DataFlowServiceException::INVALID_ARGUMENT);		
		// gets pool of free DataFlowActivities of this class
		$freeDFAs = $this->freeDataFlowActivities[$className];
		if(is_null($freeDFAs) || !is_array($freeDFAs)) $freeDFAs = array();
		// gets free DataFlowActivity
		$returnValue = array_pop($freeDFAs);
		$this->freeDataFlowActivities[$className] = $freeDFAs;
		// if none exists, then creates a new DataFlowActivity
		if(is_null($returnValue)) $returnValue = $this->createDataFlowActivityInstance($className);		
		// stores DataFlowActivitiy in used list		
		$this->usedDataFlowActivities[$this->getDataFlowActivityInstanceId($returnValue)] = $returnValue;
		// resets the DataFlowActivity
		$returnValue->reset();
		// initializes DataFlowActivity parameters using the selector		
		$params = $dataFlowActivitySelector->getDataFlowActivityParametersIterator();
		if(isset($params)) {
			foreach($params as $methodName => $arg) {
				if(method_exists($returnValue, $methodName)) {
					$returnValue->$methodName($arg);
				}
				else throw new DataFlowServiceException("method $methodName is not a valid method on class $className", DataFlowServiceException::INVALID_ARGUMENT);
			}
		}
		return $returnValue;
	}

	/**
	 * Creates a new instance of a DataFlowActivity given its class name
	 * Defaults to new $className
	 */
	protected function createDataFlowActivityInstance($className) {
		if(is_null($className) || $className == '') throw new DataFlowServiceException("DataFlowActivity class name cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$returnValue = ServiceProvider::createWigiiObject($className);
		// injects root principal if needed
		if($returnValue instanceof RootPrincipalDFA) {
			$returnValue->setRootPrincipal($this->getRootPrincipal());
		}
		return $returnValue;
	}
	/**
	 * Returns a unique ID identifying this DataFlowActivityInstance
	 * defaults to calling spl_object_hash 
	 */
	protected function getDataFlowActivityInstanceId($dataFlowActivityInstance) {
		return spl_object_hash($dataFlowActivityInstance);
	}
	
	/**
	 * Recycles a DataFlowActivity
	 * Precondition: assumes that endOfStream has already been called and
	 * that it is valid to prepare the object to receive a new call of startOfStream
	 */
	protected function recycleDataFlowActivity($dataFlowActivity) {
		if(is_null($dataFlowActivity)) throw new DataFlowServiceException("DataFlowActivity cannot be null", DataFlowServiceException::INVALID_ARGUMENT);		
		$objectId = $this->getDataFlowActivityInstanceId($dataFlowActivity);		
		// retrieves used DataFlowActivity 
		$dataFlowActivity = $this->usedDataFlowActivities[$objectId];
		$this->usedDataFlowActivities[$objectId] = null;				
		if(isset($dataFlowActivity)) {						
			// frees the memory
			$dataFlowActivity->freeMemory();
			// stores it into the list of free DataFlowActivities
			$className = get_class($dataFlowActivity);			
			$freeDFAs = $this->freeDataFlowActivities[$className];
			if(is_null($freeDFAs) || !is_array($freeDFAs)) $freeDFAs = array();
			$freeDFAs[] = $dataFlowActivity;
			$this->freeDataFlowActivities[$className] = $freeDFAs;					
		}		
	}

	/**
	 * Returns the DataFlowActivitySelector linked to this step in this context
	 */
	protected function getDataFlowActivitySelectorForStep($stepId, $dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("DataFlowContext cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		return $dataFlowContext->getDataFlowActivitySelectorList()->getDataFlowActivitySelector($stepId);
	}

	/**
	 * Stores the result into the step buffer.
	 */
	protected function storeResultDataIntoStepBuffer($contextId, $stepId, $resultData) {
		// retrieves dataflow
		$dataFlow = $this->stepBuffer[$contextId];
		//$this->debugLogger()->write("dataflow $contextId (previous): ".json_encode($dataFlow));
		if(is_null($dataFlow) || !is_array($dataFlow)) $dataFlow = array();
		// retrieves step buffer
		$stepBuffer = $dataFlow[$stepId];
		//$this->debugLogger()->write("dataflow $contextId, step $stepId previous buffer ".json_encode($stepBuffer));
		if(is_null($stepBuffer) || !is_array($stepBuffer)) $stepBuffer = array();		
		// stores result in step buffer
		if(isset($resultData)) $stepBuffer[] = $resultData;
		$dataFlow[$stepId] = $stepBuffer;
		//$this->debugLogger()->write("dataflow $contextId, step $stepId buffer ".json_encode($stepBuffer));
		$this->stepBuffer[$contextId] = $dataFlow;
		//$this->debugLogger()->write("dataflow $contextId (next): ".json_encode($dataFlow));
	}

	/**
	 * Returns the number of elements in the step buffer
	 */
	protected function stepBufferCount($contextId, $stepId) {
		// retrieves dataflow
		$dataFlow = $this->stepBuffer[$contextId];
		if(is_null($dataFlow) || !is_array($dataFlow)) return 0;
		// retrieves step buffer
		$stepBuffer = $dataFlow[$stepId];
		if(is_null($stepBuffer) || !is_array($stepBuffer)) return 0;		
		// returns count of step buffer
		return count($stepBuffer);
	}

	/**
	 * Returns true if the step buffer is full
	 */
	protected function isStepBufferFull($contextId, $stepId) {
		if($this->stepBufferLimit == -1) return false;
		else return ($this->stepBufferCount($contextId, $stepId) >= $this->stepBufferLimit);
	}

	/**
	 * Calls DataFlowActivity->processDataChunk on each chunk of data stored in the step buffer.
	 * Empties the step buffer.
	 */
	protected function flushStepBufferIntoActivity($contextId, $stepId, $dataFlowActivity, $dataFlowContext) {
		if(is_null($dataFlowActivity)) throw new DataFlowServiceException("DataFlowActivity cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("DataFlowContext cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		// retrieves dataflow
		$dataFlow = $this->stepBuffer[$contextId];
		//$this->debugLogger()->write("dataflow $contextId (before flush): ".json_encode($dataFlow));
		if(is_null($dataFlow) || !is_array($dataFlow)) return;
		// retrieves step buffer
		$stepBuffer = $dataFlow[$stepId];
		if(is_null($stepBuffer) || !is_array($stepBuffer)) return;		
		// flushes step buffer
		//$this->debugLogger()->write("dataflow $contextId flushes step $stepId buffer into step ".$dataFlowContext->getStepId()." : ".json_encode($stepBuffer));
		foreach($stepBuffer as $data) {
			$dataFlowActivity->processDataChunk($data, $dataFlowContext);
		}
		// retrieves again dataflow
		$dataFlow = $this->stepBuffer[$contextId];
		$dataFlow[$stepId] = null;
		$this->stepBuffer[$contextId] = $dataFlow;
		//$this->debugLogger()->write("dataflow $contextId (after flush): ".json_encode($dataFlow));
	}

	/**
	 * Returns the single value stored into the step buffer or null if buffer is empty.
	 * The buffer is emptied.
	 * @throws DataFlowServiceException INVALID_STATE if the buffer has more than one element
	 */
	protected function consumeSingleValueFromStepBuffer($contextId, $stepId) {
		// retrieves dataflow
		$dataFlow = $this->stepBuffer[$contextId];
		if(is_null($dataFlow) || !is_array($dataFlow)) return null;
		// retrieves step buffer
		$stepBuffer = $dataFlow[$stepId];
		if(is_null($stepBuffer) || !is_array($stepBuffer)) return null;		
		// checks that step buffer has only one element
		$n = count($stepBuffer);
		if($n > 1) throw new DataFlowServiceException("step buffer has more than one element", DataFlowServiceException::INVALID_STATE);
		elseif ($n == 0) return null;
		else {
			$dataFlow[$stepId] = null;
			$this->stepBuffer[$contextId] = $dataFlow;
			return array_pop($stepBuffer);
		}
	}

	/**
	 * Returns true if this step represents the last step of this DataFlow context
	 */
	public function isStepTheLastStep($stepId, $dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("DataFlowContext cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$lastStepId = $dataFlowContext->getDataFlowActivitySelectorList()->count()-1;		
		if($stepId > $lastStepId) throw new DataFlowServiceException("Invalid step ID $stepId.", DataFlowServiceException::INVALID_ARGUMENT);
		return ($stepId == $lastStepId);
	}

	/**
	 * Returns the next step ID in the chain of DataFlowActivities in this context
	 * @param int $stepId the current step ID
	 * @param DataFlowContext $dataFlowContext the current DataFlowContext
	 */
	protected function getNextStepId($stepId, $dataFlowContext) {
		if(is_null($dataFlowContext)) throw new DataFlowServiceException("DataFlowContext cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$lastStepId = $dataFlowContext->getDataFlowActivitySelectorList()->count()-1;
		$returnValue = $stepId + 1;
		if($returnValue > $lastStepId) throw new DataFlowServiceException("StepID $stepId is last step. There is no next step.", DataFlowServiceException::INVALID_STATE);
		return $returnValue;
	}

	/**
	 * Returns the first step ID in the chain of DataFlowActivities in this context
	 * @param DataFlowContext $dataFlowContext the current DataFlowContext
	 */
	protected function getFirstStepId($dataFlowContext) {
		return 0;
	}

	/**
	 * Returns the cached DataFlowActivity used for this current step
	 * @param int $stepId the current step ID
	 * @param int $contextId the current context ID
	 * @return DataFlowActivity a DataFlowActivity instance or null if not defined
	 */
	protected function getOpenedDataFlowActivityForStep($stepId, $contextId) {
		// retrieves the data flow
		$dataFlow = $this->runningDataFlows[$contextId];
		if(isset($dataFlow) && is_array($dataFlow)) {
			// retrieves the data flow activity
			$returnValue = $dataFlow[$stepId];
		}
		else $returnValue = null;
		return $returnValue;
	}

	/**
	 * Caches the DataFlowActivity used for this current step
	 * @param int $stepId the current step ID
	 * @param int $contextId the current context ID
	 * @param DataFlowActivity $dataFlowActivity the DataFlowActivity instance linked to this current step
	 */
	protected function storeOpenedDataFlowActivityForStep($stepId, $contextId, $dataFlowActivity) {
		// retrieves the data flow
		$dataFlow = $this->runningDataFlows[$contextId];
		if(is_null($dataFlow) || !is_array($dataFlow)) $dataFlow = array();
		// caches the dataflow activity instance
		$dataFlow[$stepId] = $dataFlowActivity;
		$this->runningDataFlows[$contextId] = $dataFlow;
	}

	/**
	 * Recycles the DataFlowActivity used for this step
	 * Precondition: assumes that endOfStream has already been called and
	 * that it is valid to prepare the object to receive a new call of startOfStream
	 */
	protected function recycleDataFlowActivityForStep($stepId, $contextId) {
		// retrieves the data flow
		$dataFlow = $this->runningDataFlows[$contextId];
		if(is_null($dataFlow) || !is_array($dataFlow)) throw new DataFlowServiceException("no running dataflow with context id '$contextId'", DataFlowServiceException::INVALID_ARGUMENT);
		// retrieves data flow activity 
		$dataFlowActivity = $dataFlow[$stepId];
		// recycles dataflow activity
		if(isset($dataFlowActivity)) {
			$dataFlow[$stepId] = null;
			$this->runningDataFlows[$contextId] = $dataFlow;
			$this->recycleDataFlowActivity($dataFlowActivity);
		}
	}
	
	// wigii events subscriber stack
	
	private $wigiiEventsSubscriberStack;
	private $wigiiEventsSubscriberStackPointer = -1;	
	private $dfContextWigiiEventsSubscriber;
	
	/**
	 * Adds a WigiiEvents subscriber in the scope of this data flow context
	 * @param WigiiEvents|MultiplexedEvent|Boolean $wigiiEventsSubscriber	 
	 */
	protected function addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext) {
		if(!isset($wigiiEventsSubscriber)) $wigiiEventsSubscriber = true;
		elseif(!($wigiiEventsSubscriber instanceof MultiplexedEvent) && !($wigiiEventsSubscriber instanceof WigiiEvents) && $wigiiEventsSubscriber !== false) throw new DataFlowServiceException("wigiiEventSubscriber should implement WigiiEvents or MultiplexedEvent interface, or be explicitely equal to false to stop event propagation.", DataFlowServiceException::INVALID_ARGUMENT);
		if($this->wigiiEventsSubscriberStackPointer < 0) {
			$this->wigiiEventsSubscriberStack = array();			
			$this->dfContextWigiiEventsSubscriber = array();
		}
		$this->wigiiEventsSubscriberStackPointer++;
		// checks if previous call has disabled events --> in that case, 
		// keeps disabled except if explicitely enabled for this stage and lowers.
		if($this->wigiiEventsSubscriberStackPointer > 0) {
			if($this->wigiiEventsSubscriberStack[$this->wigiiEventsSubscriberStackPointer-1] === false 
				&& $wigiiEventsSubscriber === true) {
				$wigiiEventsSubscriber = false;
			}
			elseif(!isset($this->wigiiEventsSubscriberStack[$this->wigiiEventsSubscriberStackPointer-1])
				&& $wigiiEventsSubscriber === true) {
				$wigiiEventsSubscriber = null;
			}
		}
		$this->wigiiEventsSubscriberStack[$this->wigiiEventsSubscriberStackPointer] = $wigiiEventsSubscriber;
		$this->dfContextWigiiEventsSubscriber[$dataFlowContext->getContextId()] = $this->wigiiEventsSubscriberStackPointer+1;		
	}
	/**
	 * Removes the WigiiEvents subscriber from listening to wigii events
	 */
	protected function removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext) {
		if($this->wigiiEventsSubscriberStackPointer >= 0) {	
			$ctxId = $dataFlowContext->getContextId();
			$i = $this->dfContextWigiiEventsSubscriber[$ctxId];
			if($i > 0) {
				if($i > $this->wigiiEventsSubscriberStackPointer) $this->wigiiEventsSubscriberStack[$i] = null;
				else {
					while($this->wigiiEventsSubscriberStackPointer >= $i - 1) {
						$this->wigiiEventsSubscriberStack[$this->wigiiEventsSubscriberStackPointer] = null;					
						$this->wigiiEventsSubscriberStackPointer--;
					}
				}
				$this->dfContextWigiiEventsSubscriber[$ctxId] = null;
			}			
		}
	}
	
	// data flow source caching
	
	private $dataSourceCache = array();
	
	/**
	 * Resets the internal data source cache
	 */
	public function clearDataSourceCache() {
	    $this->executionSink()->publishStartOperation('clearDataSourceCache');
	    $this->dataSourceCache = array();
	    $this->executionSink()->publishEndOperation('clearDataSourceCache');
	}
	
	protected function cacheDataSource($principal, $cacheKey, $source, $freeMemoryOfSource=true, $wigiiEventsSubscriber=null) {
		$this->executionSink()->publishStartOperation('cacheDataSource', $principal);
		try {
			$this->dataSourceCache[$cacheKey] = array();
			$this->processDataSource($principal, $source, dfasl(
				dfas('CallbackDFA', 'setProcessDataChunkCallback', CallableObject::createInstance('cacheDataSourceProcessDataChunkCallback', $this),
					'initializeContext', array('cacheKey' => $cacheKey))
			), $freeMemoryOfSource, $wigiiEventsSubscriber, null);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError('cacheDataSource', $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation('cacheDataSource', $principal);
	}
	public function cacheDataSourceProcessDataChunkCallback($data, $callbackDFA) {
		$cacheKey = $callbackDFA->getValueInContext('cacheKey');
		$this->dataSourceCache[$cacheKey][] = $data;
	}
	
	protected function processDataSourceFromCache($principal, $cacheKey, $dataFlowActivitySelectorList, $wigiiEventsSubscriber=null) {
		$this->executionSink()->publishStartOperation('processDataSourceFromCache', $principal);
		try {
			$dataSource =& $this->dataSourceCache[$cacheKey];
			$dataFlowContext = $this->startStream($principal, $dataFlowActivitySelectorList);
			$this->addWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
			try {
				foreach($dataSource as $data) {
					$this->processDataChunk($data, $dataFlowContext);
				}
				unset($dataSource);
				$returnValue = $this->endStream($dataFlowContext);
			}
			catch(Exception $e) {
				$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
				unset($dataSource);
				throw $e;
			}
			$this->removeWigiiEventsSubscriber($wigiiEventsSubscriber, $dataFlowContext);
		}
		catch(Exception $e) {
			$this->executionSink()->publishEndOperationOnError('processDataSourceFromCache', $e, $principal);
			throw $e;
		}
		$this->executionSink()->publishEndOperation('processDataSourceFromCache', $principal);
		return $returnValue;
	}
	
	protected function isDataSourceInCache($cacheKey) {
		$this->debugLogger()->write("checks if data source '$cacheKey' is in cache");
		return array_key_exists($cacheKey, $this->dataSourceCache);
	}
}