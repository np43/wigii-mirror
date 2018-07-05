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
 * A data flow activity base class which works on hybrid flows and
 * makes available for each item a configured FuncExp evaluator.
 * Supports flows of PHP stdClasses, scalars and Element or ElementP.
 * By default, this class does nothing, only passes the objects through,
 * subclasses should implement the processObject method. 
 * Created by CWE on 02 Mars 2014;
 * Modified by CWE on 28 August 2014 to work with hybrid flows.
 */
class ObjectDFAWithFuncExpVM implements DataFlowActivity
{			
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->funcExpVMClassName);
		unset($this->vmModules);
		unset($this->elementEvaluatorClassName);
		unset($this->translateAllValues);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ObjectDFAWithFuncExpVM");
		}
		return $this->_debugLogger;
	}	
	
	// configuration	
	
	private $funcExpVMClassName;
	/**
	 * Sets the FuncExp VM class to use.
	 */
	public function setFuncExpVMClassName($className) {
		$this->funcExpVMClassName = $className;
	}
	/**
	 * Defaults to FuncExpVM
	 */
	protected function getFuncExpVMClassName() {
		if(!isset($this->funcExpVMClassName) || $this->funcExpVMClassName == '') {
			$this->funcExpVMClassName = 'FuncExpVM';
		}
		return $this->funcExpVMClassName;
	}
	
	private $vmModules;
	/**
	 * Loads a list of classes in the current context of the VM
	 * @param Array|String $modules an array of class names or one string
	 */
	public function useVMModules($modules) {
		$this->vmModules = $modules;
	}
	
	private $elementEvaluatorClassName;
	/**
	 * Sets the Element Evaluator class to use.
	 */
	public function setElementEvaluatorClassName($className) {
		$this->elementEvaluatorClassName = $className;
	}
	/**
	 * Defaults to ElementEvaluator
	 */
	protected function getElementEvaluatorClassName() {
		if(!isset($this->elementEvaluatorClassName) || $this->elementEvaluatorClassName == '') {
			$this->elementEvaluatorClassName = 'ElementEvaluator';
		}
		return $this->elementEvaluatorClassName;
	}
	
	private $translateAllValues;
	/**
	 * If true, then all field selectors values are translated using the TemplateRecordManager
	 * @param Boolean $bool
	 */
	public function setTranslateAllValues($bool) {
		$this->translateAllValues = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		/* nothing to do */		
	}
	private $vm;
	public function processDataChunk($data, $dataFlowContext) {
		if(is_null($data)) return;
		$returnValue = null;
		$eltEval = null; $this->vm = null;
		$p = $dataFlowContext->getPrincipal();
		try {
			// configures the VM context according to data type
			// if element
			if($data instanceof ElementP || $data instanceof Element) {
				// extracts the element
				$element = $data->getDbEntity();
					
				// gets element evaluator
				$eltEval = $this->getElementEvaluator($p, $this->getElementEvaluatorClassName(), $element);
			}
			
			// gets vm
			$this->vm = $this->getFuncExpVM($p, $this->getFuncExpVMClassName(), $eltEval);
			// configures the vm
			if(isset($this->vmModules)) $this->vm->useModules($this->vmModules);
			// injects current DataFlowContxt
			$this->vm->getFuncExpVMServiceProvider()->setDataFlowContext($dataFlowContext);
			
			// if stdClass
			if($data instanceof stdClass) {
				// loads the object properties as local variables
				$vmCtx = $this->vm->getFuncExpVMServiceProvider()->getFuncExpVMContext();
				foreach($data as $property => $value) {
					$vmCtx->setVariable(fs($property), $value);
				}
			}
			// else scalars or object manipulated as a whole.
			elseif(!($data instanceof ElementP || $data instanceof Element)) {
				// loads the object under the local variable 'value'
				$vmCtx = $this->vm->getFuncExpVMServiceProvider()->getFuncExpVMContext();
				$vmCtx->setVariable(fs('value'), $data);
			}
			
			// evaluates the object according to subclass code
			$this->processObject($data, $dataFlowContext);
			
			// frees vm
			$this->vm->freeMemory();
			if(isset($eltEval)) $eltEval->freeMemory();
		}
		catch(Exception $e) {
			if(isset($this->vm)) $this->vm->freeMemory();
			if(isset($eltEval)) $eltEval->freeMemory();
			throw $e;
		}			
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}	
	
	// object event handling
			
	/**
	 * Processes the current object in the flow
	 * and writes some output if needed to the next stage in the flow using the DataFlowContext
	 * Use the method $this->evaluateFuncExp($funcExp) if you need to evaluate any 
	 * functional expressions based on the object.
	 * @param StdClass $obj the current object in the flow
	 * @param DataFlowContext $dataFlowContext the current dataflow
	 */
	protected function processObject($obj, $dataFlowContext) {
		if(!$dataFlowContext->isCurrentStepTheLastStep()) $dataFlowContext->writeResultToOutput($obj);
	}
	
	/**
	 * Evaluates a FuncExp using the current object as context.
	 * @return Any returns the value of the evaluated FuncExp.
	 */
	protected function evaluateFuncExp($funcExp) {
		return $this->vm->evaluateFuncExp($funcExp, $this);
	}
	
	/**
	 * Returns a reference on the current instantiated FuncExp VM
	 * that can be used to lookup information inside the executing environment.
	 * @return FuncExpVM a FuncExpVM instance
	 */
	protected function getCurrentFuncExpVM() {
		return $this->vm;
	}
	
	// implementation
	
	/**
	 * FuncExpVM instantiator. Defaults to ServiceProvider::getFuncExpVM
	 * Redefine this method only if you need to change the implementation.
	 * @param ElementEvaluator $elementEvaluator a optional ElementEvaluator 
	 * that should be set as a parent evaluator for this VM in the context of evaluating func exp on elements.
	 */
	protected function getFuncExpVM($principal, $className, $elementEvaluator=null) {
		return ServiceProvider::getFuncExpVM($principal, $elementEvaluator, $className);
	}
	
	/**
	 * ElementEvaluator instantiator. Defaults to ServiceProvider::getElementEvaluator
	 * Redefine this method only if you need to change the implementation.
	 */
	protected function getElementEvaluator($principal, $className, $element) {
		$returnValue = ServiceProvider::getElementEvaluator($principal, $className);
		$returnValue->setContext($principal, $element);
		if($this->translateAllValues) $returnValue->setTranslateAllValues(true);
		return $returnValue;
	}
}