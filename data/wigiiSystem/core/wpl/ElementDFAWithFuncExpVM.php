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
 * A data flow activity base class which works on flows of Elements/ElementP and
 * makes available a configured FuncExp evaluator.
 * By default, this class does nothing, only passes the elements through,
 * subclasses should implement the processElement method. 
 * Created by CWE on 12 décembre 2013
 */
class ElementDFAWithFuncExpVM implements DataFlowActivity
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
		unset($this->variables);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementDFAWithFuncExpVM");
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
	
	private $variables;
	/**
	 * Sets some variables into the VM context
	 * @param Array $array an array of key/values
	 */
	public function setVariables($array) {
		$this->variables = $array;
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
			// extracts the element
			$element = $data->getDbEntity();
			
			// gets element evaluator
			$eltEval = $this->getElementEvaluator($p, $this->getElementEvaluatorClassName(), $element);
			// injects DataFlowContext
			$eltEval->setDataFlowContext($dataFlowContext);
			// gets vm
			$this->vm = $this->getFuncExpVM($p, $eltEval, $this->getFuncExpVMClassName());
			// configures the vm
			if(isset($this->vmModules)) $this->vm->useModules($this->vmModules);
			// injects current DataFlowContxt
			$this->vm->getFuncExpVMServiceProvider()->setDataFlowContext($dataFlowContext);
			// injects any pre-defined variables
			if(!empty($this->variables) && is_array($this->variables)) {
				$vmCtx = $this->vm->getFuncExpVMServiceProvider()->getFuncExpVMContext();
				foreach($this->variables as $k => $v) {
					$vmCtx->setVariable(fs($k), $v);
				}
			}
			
			// evaluates the element according to subclass code
			$this->processElement($data, $dataFlowContext);
			
			// frees vm and element evaluator
			$this->vm->freeMemory(); $eltEval->freeMemory();
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
	
	// element event handling
			
	/**
	 * Processes the current element or elementP in the flow
	 * and writes some output if needed to the next stage in the flow using the DataFlowContext
	 * Use the method $this->evaluateFuncExp($funcExp) if you need to evaluate any 
	 * functional expressions based on the element.
	 * @param Element|ElementP $elementP the current element in the flow
	 * @param DataFlowContext $dataFlowContext the current dataflow
	 */
	protected function processElement($elementP, $dataFlowContext) {
		if(!$dataFlowContext->isCurrentStepTheLastStep()) $dataFlowContext->writeResultToOutput($elementP, $this);
	}
	
	/**
	 * Evaluates a FuncExp using the current element as context.
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
	 * ElementEvaluator instantiator. Defaults to ServiceProvider::getElementEvaluator
	 * Redefine this method only if you need to change the implementation.
	 */
	protected function getElementEvaluator($principal, $className, $element) {
		$returnValue = ServiceProvider::getElementEvaluator($principal, $className);
		$returnValue->setContext($principal, $element);
		if($this->translateAllValues) $returnValue->setTranslateAllValues(true);
		return $returnValue;
	}
	/**
	 * FuncExpVM instantiator. Defaults to ServiceProvider::getFuncExpVM
	 * Redefine this method only if you need to change the implementation.
	 */
	protected function getFuncExpVM($principal, $parentEvaluator, $className) {
		return ServiceProvider::getFuncExpVM($principal, $parentEvaluator, $className);
	}
}