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
 * A service provider which provides contextual service instances to Func Exp implementors 
 * Created by CWE on 3 octobre 2013
 * Modified by Medair (CWE) on 27.06.2017 to allow instanciating custom FuncExp Libraries (modules)
 */
class FuncExpVMServiceProvider
{	
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FuncExpVMServiceProvider");
		}
		return $this->_debugLogger;
	}
	
	private $funcExpVM;
	public function setFuncExpVM($funcExpVM) {
		$this->funcExpVM = $funcExpVM;
	}
	protected function getFuncExpVM() {		
		return $this->funcExpVM;
	}
	
	private $rootPrincipal;
	public function setRootPrincipal($rootP) {
		$this->rootPrincipal = $rootP;
	}
	protected function getRootPrincipal() {
		return $this->rootPrincipal;
	}
		
	// Service providing methods
	
	/**
	 * Returns an instance of a FuncExp Queue
	 * @param Boolean $forceNewInstance if true, then a new instance is created in current context, 
	 * else returns the first found instance in context stack. 
	 * If none is found, then still creates a new instance in current context.
	 */
	public function getFuncExpQueue($forceNewInstance=false) {
		$vm = $this->getFuncExpVM();
		$returnValue = $vm->getSingletonInScope('FuncExpQueue', !$forceNewInstance);
		if(!isset($returnValue)) {
			$returnValue = $this->createFuncExpQueueInstance();
			$vm->setSingletonInScope('FuncExpQueue', $returnValue);
		}		
		return $returnValue;
	}
	
	/**
	 * Returns an instance of a FuncExp Iterator given the current value 
	 * and the next instruction to be executed 
	 * @param Any $currentValue the current FuncExp value
	 * @param FuncExp $nextFuncExp a FuncExp instance defining the next instruction
	 * @param Boolean $forceToBeNext if true, then garanties that the given next instruction will be
	 * the one effectively next evaluated by the Iterator. 
	 * If false (by default), the next instruction returned by the iterator is not necessarly 
	 * the given instruction, but it is garantied that the given instruction will be executed once in the future.  
	 */
	public function getFuncExpIterator($currentValue, $nextFuncExp, $forceToBeNext=false) {
		$ctxQueue = $this->getFuncExpQueue();
		$ctxQueue->setCurrentFuncExpValue($currentValue);
		if($forceToBeNext) $ctxQueue->prependFuncExp($nextFuncExp);
		else $ctxQueue->appendFuncExp($nextFuncExp);
		return $ctxQueue->getFuncExpIterator();
	}
	
	/**
	 * Returns an instance of a FuncExp Iterator that matches a recursive call of the current executing FuncExp	
	 * @param Any $currentValue the current FuncExp value
	 * @param Array $arguments an optional array of arguments to pass to the recursive call. 
	 * If null, then keeps the current arguments which are set in the current func exp when doing the recursive call. 
	 * To pass no arguments, pass an empty array.
	 * @param Boolean $forceToBeNext if true, then garanties that the given next instruction will be
	 * the one effectively next evaluated by the Iterator. See method getFuncExpIterator.
	 */
	public function getFuncExpIteratorForRecursiveCall($currentValue, $arguments=null, $forceToBeNext=false) {
		$nextFuncExp = $this->getFuncExpVM()->getCurrentEvaluatingFuncExp();
		if(isset($arguments) && $nextFuncExp instanceof FuncExp) {
			$nextFuncExp->setArguments($arguments);			
		}
		return $this->getFuncExpIterator($currentValue, $nextFuncExp, $forceToBeNext);
	}
	
	/**
	 * Returns an instance of a FuncExpVM Context
	 * @param Boolean $forceNewInstance if true, then a new instance is created in current context, 
	 * else returns the first found instance in context stack. 
	 * If none is found, then still creates a new instance in current context.
	 * @return FuncExpVMContext
	 */
	public function getFuncExpVMContext($forceNewInstance=false) {
		$vm = $this->getFuncExpVM();
		$returnValue = $vm->getSingletonInScope('FuncExpVMContext', !$forceNewInstance);		
		if(!isset($returnValue)) {			
			// creates vm context
			$returnValue = $this->createFuncExpVMContextInstance();			
			// sets parent context
			if($forceNewInstance) $returnValue->setParentContext($vm->getSingletonInScope('FuncExpVMContext'));
			// stores vm context in scope
			$vm->setSingletonInScope('FuncExpVMContext', $returnValue);
		}				
		return $returnValue;
	}
	
	private $modules;
	/**
	 * Loads a list of classes in the current context of the VM
	 * @param Array|String $modules an array of class names or one string
	 * @return if only one class then returns the instance of the class, else returns null
	 * @throws AuthorizationServiceException if module class is located into the Client config folder and principal is a minimal principal.
	 */
	public function useModules($modules) {
		if(empty($modules)) return null;
		$returnValue = null;
		if(is_array($modules)) {
			if(!isset($this->modules)) $this->modules = array();
			$vmCtx = $this->getFuncExpVMContext();
			$n = 0;
			foreach($modules as $className) {
				if(!isset($this->modules[$className])) {
					$this->modules[$className] = $this->createModuleInstance($className);
				}
				$returnValue = $this->modules[$className];
				$vmCtx->useModule($className, $returnValue);
				$n++;
			}
			if($n > 1) $returnValue = null;
		}
		elseif(is_string($modules)) {
			if(!isset($this->modules)) $this->modules = array();			
			if(!isset($this->modules[$modules])) {
				$this->modules[$modules] = $this->createModuleInstance($modules);
			}
			$returnValue = $this->modules[$modules];
			$this->getFuncExpVMContext()->useModule($modules, $returnValue);
		}
		else throw new FuncExpEvalException('modules should be an array of class names or only one String', FuncExpEvalException::INVALID_ARGUMENT);
		return $returnValue;
	}
	
	/**
	 * Returns the current ExecutionSink instance that can be used to output production log messages
	 * @return ExecutionSink the execution sink instance or null if none is accessible in this scope
	 */
	public function getExecutionSink() {
		return $this->getFuncExpVM()->getSingletonInScope('ExecutionSink');
	}
	/**
	 * Sets an ExecutionSink instance to be used in current scope.
	 * @param ExecutionSink $executionSink
	 */
	public function setExecutionSink($executionSink) {
		$this->getFuncExpVM()->setSingletonInScope('ExecutionSink', $executionSink);
	}
	
	// service creation
	
	/**
	 * default as FuncExpQueueArrayImpl
	 */
	protected function createFuncExpQueueInstance() {
		$returnValue = new FuncExpQueueArrayImpl();
		$returnValue->setFuncExpVM($this->getFuncExpVM());
		return $returnValue;
	}
	
	/**
	 * default as FuncExpVMContext
	 */
	protected function createFuncExpVMContextInstance() {
		$returnValue = new FuncExpVMContext();		
		return $returnValue;
	}
	
	/**
	 * Creates a module instance given its class name
	 * The class can be a custom FuncExp Library.
	 * @param String $className
	 */
	protected function createModuleInstance($className) {
	    $vm = $this->getFuncExpVM();
		$returnValue = ServiceProvider::getFuncExpLibrary($vm->getPrincipal(), $className);
		// injects FuncExpVM if needed
		$returnValue->setFuncExpVM($vm);
		// injects root principal if needed
		if($returnValue instanceof RootPrincipalFL) {
			$returnValue->setRootPrincipal($this->getRootPrincipal());
		}
		return $returnValue;
	}
	
	/**
	 * Forks current FuncExpVM and returns its configured instance, including parent evaluator.
	 * @return FuncExpVM
	 */
	public function forkFuncExpVM() {
	    $returnValue = $this->getFuncExpVM()->forkVM();
	    $obj = $this->getExecutionSink();
	    if(isset($obj)) $returnValue->getFuncExpVMServiceProvider()->setExecutionSink($obj);
	    return $returnValue;
	}
}