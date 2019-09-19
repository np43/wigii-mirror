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
 * The VM context which holds a list of modules where to find code and a list of declared variables
 * Created by CWE on 3 octobre 2013
 */
class FuncExpVMContext 
{
	private $parentContext;
	/**
	 * a map [funcName=>code|false]
	 */
	private $functions;
	/**
	 * a map [funcName=>funcExp]
	 */
	private $funcExps;
	/**
	 * a map [className=>code]
	 * this map can only grow
	 * a change on this map will unset the functions map
	 */
	private $modules;
	/**
	 * a list of classNames	 
	 * this list can only grow	
	 * a change on this list will unset the functions map
	 */
	private $usageList;
	/**
	 * a map [fieldName=>obj]
	 */
	private $variables;
	
	// Dependency injection
	
	public function setParentContext($parentContext) {
		$this->parentContext = $parentContext;
	}
	
	// Methods
	
	/**
	 * Returns the module instance containing some code for the given function name
	 * @param String $funcName the function name
	 * @return Object an object having a public method called $funcName or null if not found
	 */
	public function getModuleForFuncName($funcName) {
		if(empty($funcName)) return null;
		// 0. looks in funcexp map
		if(!empty($this->funcExps)) {
			$module = $this->funcExps[$funcName];
			if(isset($module)) return $module;
		}
		// 1. looks in functions cache		
		if(!empty($this->functions)) {
			$module = $this->functions[$funcName];
			if($module) return $module;
			// 2. if already marked as not in current scope --> goes to parent
			elseif($module === false) {
				if(isset($this->parentContext)) return $this->parentContext->getModuleForFuncName($funcName);
				else return null;
			}
		}
		// 3. looks through all loaded modules
		if(!empty($this->usageList)) {			
			// in reverse order
			$n = count($this->usageList);
			$visitedClasses = array();
			for($i = $n-1; $i >= 0; $i--) {
				$className = $this->usageList[$i];
				if(!isset($visitedClasses[$className])) {					
					$visitedClasses[$className] = true;
					$module = $this->modules[$className];
					// if method found in module then stores association into functions array
					// CWE 18.03.2019: replaced usage of method_exists by is_callable to prevent name conflicts between existing FuncExps and protected or private methods.
					if(isset($module) && is_callable(array($module, $funcName))) {
						if(!isset($this->functions)) $this->functions = array();
						$this->functions[$funcName] = $module;
						return $module;
					}					
				}				
			}
		}
		// 4. marks funcName as not present in local loaded modules and delegates search to parent
		if(!isset($this->functions)) $this->functions = array();
		$this->functions[$funcName] = false;
		if(isset($this->parentContext)) return $this->parentContext->getModuleForFuncName($funcName);
		else return null;
	}
	
	/**
	 * Loads one module in the current context
	 * @param String $className the name of the module
	 * @param Object $moduleInstance the instance of the module
	 */
	public function useModule($className, $moduleInstance) {
		if(is_null($className)) return;		
		if(!isset($this->modules)) $this->modules = array();
		if(!isset($this->usageList)) $this->usageList = array();
		$this->modules[$className] = $moduleInstance;		
		$this->usageList[] = $className;
		unset($this->functions);
	}
	/**
	 * Loads a list of modules in the current context
	 * @param Array $modules an array of module instances [className=>moduleInstance]
	 */
	public function useModules($modules) {
		if(is_null($modules)) return;
		if(!is_array($modules)) throw new FuncExpEvalException("modules should be an array [className=>moduleInstance]");
		foreach($modules as $className=>$moduleInstance) {
			$this->useModule($className, $moduleInstance);
		}
	}
	/**
	 * Gets an instance of a module given its class name (module should already be loaded in context)
	 * @param String $className
	 */
	public function getModule($className) {
	    return $this->modules[$className];
	}
	
	/**
	 * Returns the object stored with this variable or throws an exception if not found
	 * @param FieldSelector $fieldSelector the variable selector, supports subfields
	 * @return Object the object or expression stored in the variable
	 * @throws FuncExpEvalException if variable is not declared in any scope
	 */
	public function getVariable($fieldSelector) {
		if(empty($fieldSelector)) return null;
		$fName = $fieldSelector->getFieldName();		
		// 1. looks for a local variable
		if(!empty($this->variables) && key_exists($fName, $this->variables)) {
			$returnValue = $this->variables[$fName];
			$subfieldName = $fieldSelector->getSubFieldName();
			if(isset($subfieldName)) {
				if(is_array($returnValue)) {
					$returnValue = $returnValue[$subfieldName];
				}
				elseif(is_object($returnValue)) {
					$returnValue = $returnValue->{$subfieldName};					
				}
				elseif($subfieldName != 'value') $returnValue = null;
			}
			else {
				if(is_array($returnValue) && array_key_exists('value', $returnValue)) {
					$returnValue = $returnValue['value'];
				}
				elseif(is_object($returnValue) && property_exists($returnValue, 'value')) {
					$returnValue = $returnValue->{'value'};
				}				
			}
			return $returnValue;
		}
		// 2. delegates to parent
		if(isset($this->parentContext)) return $this->parentContext->getVariable($fieldSelector);
		else throw new FuncExpEvalException("variable '".$fieldSelector->toString()."' is not declared in any scope.", FuncExpEvalException::VARIABLE_NOT_DECLARED);
	}
	
	/**
	 * Sets a variable given a field selector (supports subfield) and a value
	 * @param FieldSelector $fieldSelector
	 * @param mixed $value
	 */
	public function setVariable($fieldSelector, $value) {
		if(empty($fieldSelector)) return;
		if(!isset($this->variables)) $this->variables = array();
		$fName = $fieldSelector->getFieldName();
		$subfieldName = $fieldSelector->getSubFieldName();
		if(isset($subfieldName)) {
			$currentVal = $this->variables[$fName];
			if(!is_array($currentVal)) {
				$currentVal = array($subfieldName=>$value);
			}
			else $currentVal[$subfieldName] = $value;
			$this->variables[$fName] = $currentVal;
		}
		else $this->variables[$fName] = $value;		
	}
	
	/**
	 * Clear all variables from context.
	 * @param boolean $keepDeclaration if true, then variable value is set to null and is kept in context,
	 * else variable is destroyed (that means that a call to getVariable will throw an exception). Defaults to false.
	 */
	public function clearAllVariables($keepDeclaration=false) {
		if($keepDeclaration) {
			if(!empty($this->variables)) {
				foreach($this->variables as &$value) {
					if(is_array($value)) {
						foreach($value as &$subValue) {
							$subValue = null;
						}
						unset($subValue);
					}
					else $value = null;
				}
				unset($value);
			}
		}
		else unset($this->variables);
	}
	
	/**
	 * Declares a local function
	 * @param String $funcName the function name
	 * @param FuncExp $funcExp the function body
	 */
	public function setFunction($funcName, $funcExp) {
		if(empty($funcName)) return;
		if(!($funcExp instanceof FuncExp)) throw new FuncExpEvalException("funcExp should be a non null instance of FuncExp", FuncExpEvalException::INVALID_ARGUMENT);
		if(!isset($this->funcExps)) $this->funcExps = array();
		$this->funcExps[$funcName] = $funcExp;
	}
}