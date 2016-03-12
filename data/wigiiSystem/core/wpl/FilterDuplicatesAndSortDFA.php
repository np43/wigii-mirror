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
 * A data flow activity which filters duplicates using the value or a specified objectSelector method.
 * The resulting flow can be sorted by the logical keys used to filter duplicates. 
 * This implementation uses the php in memory array sort method.
 * In order to do statistics, it can also provide the distribution of the objects in the flow.
 * Created by CWE on 24 March 2014
 */
class FilterDuplicatesAndSortDFA implements DataFlowActivity
{
	private $objectDistribution;
	private $dataBuffer;
	private $sortBuffer;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->sortOrder = 0;	
	}	
	public function freeMemory() {
		unset($this->objectSelectorMethod);
		unset($this->objectSortByMethod);
		unset($this->objectClass);
		unset($this->objectDistribution);
		unset($this->objectDistributionCallback);
		unset($this->dataBuffer);
		unset($this->sortBuffer);	
	}
	
	// Dependency injection
	
	private $translationService;
	/**
	 * Injects a TranslationService to be used by this library
	 * @param TranslationService $translationService
	 */
	public function setTranslationService($translationService){
		$this->translationService = $translationService;
	}
	/**
	 * Gets the injected TranslationService
	 * @return TranslationService
	 */
	protected function getTranslationService(){
		//autowired
		if(!isset($this->translationService)){
			$this->translationService = ServiceProvider::getTranslationService();
		}
		return $this->translationService;
	}
	
	// configuration
	
	private $objectSelectorMethod;
	/**
	 * Sets the selector method that should be executed on each object in the flow in order
	 * to build a logical key used to distinguish duplicates.
	 * The method signature takes one argument which is the current object in the data flow and
	 * should return a scalar that will be used as a key for the current object.
	 * It can also return an object of the form {key: the object key, weight: the object weight in the distribution (default is 1)}
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setObjectSelectorMethod($method, $obj=null) {
		$this->objectSelectorMethod = CallableObject::createInstance($method, $obj);
	}

	private $objectSortByMethod;
	/**
	 * Sets the method that should be executed on each object in the flow in order
	 * to build a logical key used to sort the objects in the flow.
	 * The method signature takes one argument which is the current object in the data flow and
	 * should return a scalar that will be used as a sorting value for the current object.
	 * If this method is not set, then uses by default the logical key returned by the object selector method to do sorting.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setObjectSortByMethod($method, $obj=null) {
		$this->objectSortByMethod = CallableObject::createInstance($method, $obj);
	}
	
	private $objectClass; 
	/**
	 * Informs the DataFlowActivity of the class of the Objects that are going to be filtered and sorted.
	 * This is used to preconfigure duplicate filtering and sorting methods depending of the nature of the StdClass instance.
	 * @param String $className the nature of the StdClass instance
	 */
	public function setObjectClass($className) {
		$this->objectClass=$className;
	}
	/**
	 * Returns a default object selector callback given the object class or null if no standard configuration exists.
	 * @param String $className the nature of the StdClass instance
	 * @return CallableObject the callback instance to be used to build a logical key on the object to filter duplicates
	 */
	protected function getDefaultObjectSelectorMethod($className) {
		switch($className) {
			case 'cfgAttribut': return CallableObject::createInstance('cfgAttributObjectSelectorMethod',$this);			
		}
	}
	/**
	 * Returns a default object sortby callback given the object class or null if no standard configuration exists.
	 * @param String $className the nature of the StdClass instance
	 * @return CallableObject the callback instance to be used to build a logical key on the object to sort the objects in the flow.
	 */
	protected function getDefaultObjectSortyByMethod($className) {
		switch($className) {
			case 'cfgAttribut': return CallableObject::createInstance('cfgAttributObjectSortyByMethod',$this);
		}
	}
	
	private $objectDistributionCallback;
	/**
	 * Configures a callback to get the object distribution in the flow.
	 * The method signature takes one argument which is an array of the form [object logical key => number of occurences in the flow]
	 * representing the objects statistics.
	 */
	public function setObjectDistributionCallback($method, $obj=null) {
		$this->objectDistributionCallback = CallableObject::createInstance($method, $obj);
	}
	
	private $sortOrder;
	/**
	 * Sets the sort order.
	 * @param int $order 0=no sorting, keep original order; 1=ascending; 2=descending.
	 */
	public function setSortOrder($order=0) {		
		$this->sortOrder = $order;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(isset($this->objectClass)) {
			if(!isset($this->objectSelectorMethod)) $this->objectSelectorMethod=$this->getDefaultObjectSelectorMethod($this->objectClass);
			if(!isset($this->objectSortByMethod)) $this->objectSortByMethod=$this->getDefaultObjectSortyByMethod($this->objectClass);	
		}				
		$this->objectDistribution = array();		
		if($this->sortOrder < 0 || $this->sortOrder > 2) throw new DataFlowServiceException('sortOrder can be only one of 0=no sorting, 1=ascending, 2=descending', DataFlowServiceException::CONFIGURATION_ERROR);
		if($this->sortOrder > 0) {
			$this->dataBuffer = array();
			if(isset($this->objectSortByMethod)) $this->sortBuffer = array();
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		// builds key
		if(isset($this->objectSelectorMethod)) {
			$key = $this->objectSelectorMethod->invoke($data);
			if($key instanceof stdClass || is_array($key)) {
				$key = $key['key'];
				$weight = $key['weight'];
				if(empty($weight)) $weight = 1;
			}
			else $weight = 1;
		}
		else {
			$key = $this->getObjectSelector($data);
			$weight = 1;
		}
		// filter object
		$c = $this->objectDistribution[$key];
		if($c == 0) {
			// if sorting is enabled, then stores data into sort buffer
			if($this->sortOrder > 0) {
				$this->dataBuffer[$key] = $data;
				// gets sorting value
				if(isset($this->objectSortByMethod)) {
					$this->sortBuffer[$key] = $this->objectSortByMethod->invoke($data);
				}
			}
			// else pushes it to next stage.
			else $dataFlowContext->writeResultToOutput($data, $this);
		}
		$this->objectDistribution[$key] = $c+$weight;
	}
	public function endOfStream($dataFlowContext) {
		// if sorting is enabled, then sorts the buffer and flushes it to output
		if($this->sortOrder > 0) {
			// if special sorting values, then sorts the sortBuffer
			if(isset($this->sortBuffer)) {
				if($this->sortOrder == 1) asort($this->sortBuffer);
				else arsort($this->sortBuffer);
				// flushes buffer
				foreach($this->sortBuffer as $k => $v) {
					$dataFlowContext->writeResultToOutput($this->dataBuffer[$k], $this);
				}
				unset($this->sortBuffer);
			}
			// else sorts the dataBuffer by key
			else {
				if($this->sortOrder == 1) ksort($this->dataBuffer);
				else krsort($this->dataBuffer);
				// flushes buffer
				foreach($this->dataBuffer as $data) {
					$dataFlowContext->writeResultToOutput($data, $this);
				}
			}
			unset($this->dataBuffer);			
		}
		// publishes the object statistics if asked for.
		if(isset($this->objectDistributionCallback)) $this->objectDistributionCallback->invoke($this->objectDistribution);
	}
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {		
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}

	// filtering
	
	/**
	 * Builds a logical key on the current object that will be used to filter duplicates
	 * @param Any $data the current object in the flow
	 * @return scalar the object logical key
	 * This implementation only supports filtering a flow of values.
	 * To filter objects, configure the setObjectSelectorMethod with a callback,
	 * or redefine this function.
	 */
	protected function getObjectSelector($data) {
		if(is_scalar($data)) return $data;
		else throw new DataFlowServiceException('can only filter duplicated values, does not support objects. To filter objects, configure the setObjectSelectorMethod', DataFlowServiceException::UNSUPPORTED_OPERATION);
	}
	
	// class specific filter and sort methods
	
	/**
	 * FilterDuplicatesAndSortDFA objectSelectorMethod for cfgAttribut flow
	 * @param stdClass $data an stdClass of the form {value, attributes, label}
	 */
	public function cfgAttributObjectSelectorMethod($data) {
		return $data->value;
	}
	/**
	 * FilterDuplicatesAndSortDFA objectSortByMethod for cfgAttribut flow
	 * @param stdClass $data an stdClass of the form {value, attributes, label}
	 */
	public function cfgAttributObjectSortyByMethod($data) {
		if(!empty($data->label)) {
			if(is_array($data->label)) {
				$l = $data->label[$this->getTranslationService()->getLanguage()];
				if(empty($l)) $returnValue = $data->value;
				else $returnValue = $l;
			}
			else $returnValue = $data->label;
		}
		else $returnValue = $data->value;
	
		// puts disabled options at the end
		if(!empty($data->attributes) && $data->attributes['disabled']=='1') {
			return 'Z'.$returnValue;
		}
		else {
			return 'A'.$returnValue;
		}
	}
	
}