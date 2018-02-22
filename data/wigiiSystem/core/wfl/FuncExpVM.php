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
 * A virtual machine which evaluates functional expressions using pluggable modules.
 * Created by CWE on 23 juillet 2013
 * Modified by Medair (CWE) on 07.04.2017 to propagate change of principal to parent evaluator (if possible)
 */
class FuncExpVM implements FuncExpEvaluator {	
	private $_debugLogger;
	private $lockedForUse = true;	
	private $stack;
	private $stackPointer;	
	private $bootstrapModules;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->stack = array();
		$this->stackPointer = 0;
		// loads standard func exp libraries		
		$this->useModules(array('WigiiFL','CallbackFL', 'FuncExpVMStdFL','PhpStdFL'));
		// loads bootstrap modules
		$this->useModules($this->bootstrapModules);
	}
	
	public function freeMemory() {
		if($this->freeParentEvaluatorOnFreeMemory 
			&& isset($this->parentFuncExpEvaluator)
			&& method_exists($this->parentFuncExpEvaluator, 'freeMemory')) {
			$this->parentFuncExpEvaluator->freeMemory();
		}
		unset($this->parentFuncExpEvaluator);
		$this->freeParentEvaluatorOnFreeMemory = false;
		unset($this->principal);			
		unset($this->stack);
		unset($this->wigiiExecutor);
		$this->stackPointer = 0;
		$this->lockedForUse = false;
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	// System principal management
	
	/**
	 * Adds a system principal or a list of system principals to the FuncExpVM
	 */
	public function addSystemPrincipal($systemPrincipal)
	{
		if(is_null($systemPrincipal)) return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received ".$systemPrincipal->count()." system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the FuncExpVM
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
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FuncExpVM");
		}
		return $this->_debugLogger;
	}
	
	private $parentFuncExpEvaluator;
	/**
	 * Sets a parent FuncExp evaluator to which this FuncExp evaluator
	 * can delegate evaluation of functions it does not understand (no symbol mapping)
	 * @param FuncExpEvaluator $funcExpEvaluator a FuncExpEvaluator instance
	 * The evaluator should implement the 'evaluateFuncExp' function which evaluates
	 * a FuncExp and returns its value, or evaluates a FieldSelector and returns its value,
	 * or evaluates any objects and returns its value (by default returns back the object)
	 */
	public function setParentFuncExpEvaluator($funcExpEvaluator) {
		$this->parentFuncExpEvaluator = $funcExpEvaluator;
	}
	/**
	 * Returns a reference to the parent FuncExpEvaluator if set
	 * @return FuncExp
	 */
	public function getParentFuncExpEvaluator() {
		return $this->parentFuncExpEvaluator;
	}
	
	private $freeParentEvaluatorOnFreeMemory;
	/**
	 * If true, then configures the VM so that it also frees the memory of the parent evaluator when freeing its memory.
	 * Defaults to false: only unsets the ref without calling freeMemory on parent.
	 */
	public function setFreeParentEvaluatorOnFreeMemory($bool) {
		$this->freeParentEvaluatorOnFreeMemory = $bool;
	}
	
	private $principal;
	/**
	 * Sets the principal that should be used with this FuncExp VM
	 * @param Principal $principal a valid Principal object
	 */
	public function setPrincipal($principal) {
		$this->principal = $principal;
		// Medair (CWE) 07.04.2017: propagates change of principal to parent evaluator 
		if($this->parentFuncExpEvaluator instanceof RecordEvaluator && $this->parentFuncExpEvaluator->hasPrincipal()) {
		    $this->parentFuncExpEvaluator->changePrincipal($this->getRootPrincipal(), $principal);
		}		
	}
	/**
	 * Returns the Principal that has been attached to this FuncExp VM
	 */
	public function getPrincipal() {
		return $this->principal;
	}
	
	private $funcExpVMServiceProviderClassName;
	/**
	 * Sets the FuncExp VM Service provider class to use
	 */
	public function setFuncExpVMServiceProviderClassName($className) {
		$this->funcExpVMServiceProviderClassName = $className;
	}
	/**
	 * Defaults to FuncExpVMServiceProvider
	 */
	protected function getFuncExpVMServiceProviderClassName() {
		if(!isset($this->funcExpVMServiceProviderClassName) || $this->funcExpVMServiceProviderClassName == '') {
			$this->funcExpVMServiceProviderClassName = 'FuncExpVMServiceProvider';
		}
		return $this->funcExpVMServiceProviderClassName;
	}
	
	private $funcExpVMServiceProvider;
	/**
	 * Returns the instance of the FuncExpVMServiceProvider attached to this VM
	 * @return FuncExpVMServiceProvider
	 */
	public function getFuncExpVMServiceProvider() {
		if(!isset($this->funcExpVMServiceProvider)) {
			$this->funcExpVMServiceProvider = ServiceProvider::createWigiiObject($this->getFuncExpVMServiceProviderClassName());
			$this->funcExpVMServiceProvider->setFuncExpVM($this);
			$this->funcExpVMServiceProvider->setRootPrincipal($this->getRootPrincipal());
		}
		return $this->funcExpVMServiceProvider;
	}
	
	/**
	 * Sets a list of class names the VM must load when started
	 * @param Array|String $classNames an array of class names or one string
	 */
	public function setBootstrapModules($classNames) {
		if(is_null($classNames)) unset($this->bootstrapModules);
		elseif(!is_array($classNames) && !is_string($classNames)) throw new FuncExpEvalException('bootstrap modules should be an array of class names or one single string', FuncExpEvalException::INVALID_ARGUMENT);
		else $this->bootstrapModules = $classNames; 
	}
	
	private $wigiiExecutor;
	/**
	 * Injects the WigiiExecutor if known and valid in the context of use
	 * @param WigiiExecutor $wigiiExecutor
	 */
	public function setWigiiExecutor($wigiiExecutor) {
	    $this->wigiiExecutor = $wigiiExecutor;
	}
	
	/**
	 *@return ConfigService Returns a contexutal ConfigService instance ready to be used to query the configuration
	 */
	public function getConfigService() {
	    if(isset($this->wigiiExecutor)) return $this->wigiiExecutor->getConfigurationContext();
	    else return ServiceProvider::getConfigService();
	}
	
	// FuncExp Evaluator implementation
	
	public function evaluateFuncExp($funcExp, $caller=null) {		
		if(is_null($funcExp)) return null;
		$returnValue = null;
		$this->stackPointer++;		
		try {
			$parentFuncExpEval = $this->getParentFuncExpEvaluator();
			$this->setObjectInScope($this->getScopeKeyForVMValue('currentFuncExp'), $funcExp);
			// instance of FuncExp: tries to evaluate symbol, if not found delegates to parent
			if($funcExp instanceof FuncExp) {
				$funcName = $funcExp->getName();				
				// gets module
				$module = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext()->getModuleForFuncName($funcName);				
				// if found, then calls method
				if(isset($module)) {
					// if module is a FuncExp then invokes it
					if($module instanceof FuncExp) {
						// creates a new scope
						$vmScope = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
						// adds the func exp arguments as local variables
						$i = 0;
						foreach($module->getArguments() as $arg) {
							$vmScope->setVariable(FieldSelector::createInstance($funcName, 'arg'.$i), $arg);
							$i++;
						}
						// invokes the func exp
						$returnValue = $this->evaluateFuncExp($module, $this);
					}
					// else module is some php code
					else {
						$returnValue = $module->$funcName($funcExp->getArguments());
					}
					if($this->debugLogger()->isEnabled()) $this->debugLogger()->write($funcName." returns ".(is_object($returnValue) ? get_class($returnValue) : $returnValue));
				}
				// else delegates to parent
				else {
					$returnValue = $this->delegateEvaluationToParent($parentFuncExpEval, $funcExp);
				}
			}
			// instance of FieldSelector: looks for a variable in scope, else delegates to parent
			elseif($funcExp instanceof FieldSelector) {
				try{$returnValue = $this->getFuncExpVMServiceProvider()->getFuncExpVMContext()->getVariable($funcExp);}
				catch(FuncExpEvalException $fe) {
					if($fe->getCode() == FuncExpEvalException::VARIABLE_NOT_DECLARED && isset($parentFuncExpEval)) {
						$returnValue = $this->delegateEvaluationToParent($parentFuncExpEval, $funcExp);
					}
					else throw $fe;
				}			
			}
			// other object : if parent is set, delegates to parent, else returns object
			elseif(isset($parentFuncExpEval)) {
				$returnValue = $this->delegateEvaluationToParent($parentFuncExpEval, $funcExp);
			}							
			else $returnValue = $funcExp;
		}
		catch(Exception $e) {
			if(isset($this->stack[$this->stackPointer])) unset($this->stack[$this->stackPointer]);
			$this->stackPointer--;
			throw $e;
		}
		if(isset($this->stack[$this->stackPointer])) unset($this->stack[$this->stackPointer]);
		$this->stackPointer--;
		return $returnValue;
	}
	private function delegateEvaluationToParent($parentFuncExpEval, $funcExp) {		 
		if(is_null($parentFuncExpEval)) throw new FuncExpEvalException("Parent FuncExp evaluator has not been set. Cannot evaluate ".($funcExp instanceof FuncExp ? 'FuncExp : '.$funcExp->getName() : ($funcExp instanceof FieldSelector ? 'FieldSelector : '.$funcExp->toString() : 'expression.')), FuncExpEvalException::CONFIGURATION_ERROR);
		else {
			$this->debugLogger()->write("could not evaluate funcExp, delegates to parent evaluator ".get_class($parentFuncExpEval));
			return $parentFuncExpEval->evaluateFuncExp($funcExp, $this);
		}
	}
	
	/**
	 * Forks current FuncExpVM including configured parent evaluator.
	 * @return FuncExpVM
	 */
	public function forkVM() {
	    $parentEvaluator = $this->getParentFuncExpEvaluator();
	    $p = $this->getPrincipal();
	    // clones parent evaluator
	    if(isset($parentEvaluator)) {
	        $parentEvaluator = get_class($parentEvaluator);
	        $parentEvaluator = ServiceProvider::getRecordEvaluator($p, $parentEvaluator);
	        $parentEvaluator->setContext($p, null);
	    }
	    // gets vm
	    $returnValue = ServiceProvider::getFuncExpVM($p, $parentEvaluator);
	    $returnValue->setFreeParentEvaluatorOnFreeMemory(true);
	    $returnValue->setWigiiExecutor($this->wigiiExecutor);
	    return $returnValue;
	}
	
	// Scope
	
	/**
	 * Loads a list of classes in the current context of the VM
	 * @param Array|String $modules an array of class names or one string
	 */
	public function useModules($modules) {
		$this->getFuncExpVMServiceProvider()->useModules($modules);
	}
	
	/**
	 * Stores a value in the current scope
	 * Any value stored under the same key at this level of the stack is replaced,
	 * any value stored under this key on a lower level of the stack are kept but occulted by this new value
	 * @param Scalar $key the key of the value to be stored
	 * @param Any $value the value to be stored (can be any object)
	 */
	public function setValueInScope($key, $value) {
		$this->setObjectInScope($this->getScopeKeyForValue($key), $value);
	}
	
	/**
	 * Returns the value stored under the given key if defined, else returns null
	 * By default, it walks through the whole stack searching for the key, from top to down,
	 * and returns the first value found.
	 * Except if the argument goThroughParents is false, then it searches only in the context on the top of the stack
	 * @param Scalar $key the key for which to retrieve a value
	 * @param Boolean $goThroughParents if true (default), then searches for the key through the whole stack else only on top of the stack
	 */
	public function getValueInScope($key, $goThroughParents=true) {
		return $this->getObjectInScope($this->getScopeKeyForValue($key), $goThroughParents);
	}	
	
	/**
	 * Stores a singleton in the current scope under a given class key
	 * @param String $classKey the key under which the singleton is stored
	 * for instance: the interface name or the class name if no interface.
	 * @param Object $obj the singleton instance
	 */
	public function setSingletonInScope($classKey, $obj) {
		$this->setObjectInScope($this->getScopeKeyForSingleton($classKey), $obj);
	}
	/**
	 * Returns a singleton instance given its class key
	 * @param String $classKey the key under which the singleton was stored
	 * for instance: the interface name or the class name if no interface.
	 * @param Boolean $goThroughParents if true (default), then searches for the key through the whole stack else only on top of the stack
	 * @return the singleton instance or null if not defined.
	 */
	public function getSingletonInScope($classKey, $goThroughParents=true) {
		return $this->getObjectInScope($this->getScopeKeyForSingleton($classKey), $goThroughParents);
	}
	
	/**
	 * Returns a reference on the current evaluating FuncExp
	 * @return FuncExp|FieldSelector|Any the return type depends of the object beeing evaluated, can be either a FuncExp or a FieldSelector or any object.
	 */
	public function getCurrentEvaluatingFuncExp() {
		return $this->getObjectInScope($this->getScopeKeyForVMValue('currentFuncExp'), false);
	}
	
	/**
	 * Asserts that current FuncExp beeing evaluated does not originate from public space.
	 * @throws FuncExpEvalException with code FORBIDDEN (403) if current FuncExp is marked as public.
	 */
	public function assertFxOriginIsNotPublic() {
		$currentFx = $this->getCurrentEvaluatingFuncExp();		
		if($currentFx instanceof FuncExp && $currentFx->isOriginPublic()) throw new FuncExpEvalException("FuncExp '".$currentFx->getName()."' originates from public space and is not authorized to be executed.", FuncExpEvalException::FORBIDDEN);
	}
	
	/**
	 * @return Boolean returns true if current FuncExp beeing evaluated originates from public space, null if unknown, false if not.
	 */
	public function isFxOriginPublic() {
		$currentFx = $this->getCurrentEvaluatingFuncExp();
		if($currentFx instanceof FuncExp) return $currentFx->isOriginPublic();
	}
	
	// Introspection
	
	/**
	 * Dumps the VM stack into a list
	 * @param FuncExpVMStackDump $funcExpVMStackDump an optional Stack dump to be filled. 
	 * If null, then a FuncExpVMStackDumpArrayImpl is created and filled.
	 * @return FuncExpVMStackDump returns the filled stack dump.
	 */
	public function dumpStack($funcExpVMStackDump=null) {
		if(!isset($funcExpVMStackDump)) $funcExpVMStackDump = FuncExpVMStackDumpArrayImpl::createInstance();
		for($i = $this->stackPointer; $i > 0; $i--) {
			$funcExpVMStackDump->addStackEntry($this->stack[$i]);
		}
		return $funcExpVMStackDump;
	}
	
	// Implementation
	
	protected function getScopeKeyForValue($key) {
		return "(value(".$key."))";
	}	
	protected function getScopeKeyForSingleton($key) {
		return "(singleton(".$key."))";
	}	
	protected function getScopeKeyForVMValue($key) {
		return "(vm(".$key."))";
	}
	protected function setObjectInScope($key, $obj) {
		if(!isset($this->stack[$this->stackPointer])) $this->stack[$this->stackPointer] = array();
		$this->stack[$this->stackPointer][$key] = $obj;
	}
	protected function getObjectInScope($key, $goThroughParents=true, $exceptionIfNotFound=false) {
		$i = $this->stackPointer; $found = false;
		if($goThroughParents) $endLevel = 0;
		else $endLevel = $i;
		$returnValue = null;
		while($i >= $endLevel && !$found) {
			if(isset($this->stack[$i])) {
				if(array_key_exists($key, $this->stack[$i])) {
					$found = true;
					$returnValue = $this->stack[$i][$key];
				}
			}
			$i--;
		} 
		if(!found && $exceptionIfNotFound) throw new FuncExpEvalException("$key was not found", FuncExpEvalException::SYMBOL_NOT_FOUND);
		return $returnValue;
	}
}