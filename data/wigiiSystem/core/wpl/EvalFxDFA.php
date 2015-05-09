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
 * A data flow activity which evaluates a flow of FuncExp using a FuncExpVM and a set of modules.
 * Created by CWE on 04 September 2014
 */
class EvalFxDFA implements DataFlowActivity
{			
	private $vm;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		if(isset($this->vm)) {
			$this->vm->freeMemory();
			unset($this->vm);
		}
		unset($this->funcExpVMClassName);
		unset($this->vmModules);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("EvalFxDFA");
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
		
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->vm = ServiceProvider::getFuncExpVM($dataFlowContext->getPrincipal(), null, $this->getFuncExpVMClassName());
		// configures the vm
		if(isset($this->vmModules)) $this->vm->useModules($this->vmModules);
	}
	public function processDataChunk($data, $dataFlowContext) {
		$resultData = $this->vm->evaluateFuncExp($data, $this);
		if(isset($resultData)) $dataFlowContext->writeResultToOutput($resultData, $this);
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
}