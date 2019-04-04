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
 * A FuncExpParameter holder
 * Created by CWE on 28 novembre 2013
 */
class FuncExpParameterHolder {	
	private $lockedForUse = true;	
	private $parameters;
	private $declaredParameters;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	
	public function freeMemory() {
		$this->lockedForUse = false;
		unset($this->parameters);
		unset($this->declaredParameters);
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}	

	/**
	 * Returns an instance of a FuncExpParameterHolder
	 * Call the method freeMemory on the provided instance once finished using it,
	 * this allows the TechnicalServiceProvider to recycle the object for further use.
	 */
	public static function getInstance() {
		return TechnicalServiceProvider::getFuncExpParameterHolder();
	}
	
	// Implementation
	
	/**
	 * Constructs a FuncExpParameter instance
	 * @param String $name the name of the parameter
	 * @return FuncExpParameter
	 */
	public function fxp($name) {
		if(!isset($this->declaredParameters)) $this->declaredParameters = array();
		$returnValue = $this->declaredParameters[$name];
		if(!isset($returnValue)) {
			$returnValue = FuncExpParameter::createInstance($name);
			$returnValue->setFuncExpParameterHolder($this);
			$this->declaredParameters[$name] = $returnValue;
		}
		return $returnValue;
	}

	/**
	 * Constructs a FuncExpParameterMapping instance given an array (parameterName => parameterValue).
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the mapping, you can pass the mapping
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fxpMap(p1Name, p1Value, p2Name, p2Value, ...) is equivalent
	 * to $funcExpBuilder->fxpMap(array(p1Name => p1Value, p2Name => p2Value))
	 * @param Array $arr an array (parameterName => parameterValue).
	 * @return FuncExpParameterMapping
	 */
	public function fxpMap($arr) {
		$nArgs = func_num_args();
		$returnValue = FuncExpParameterMapping::createInstance();	
		$returnValue->setFuncExpParameterHolder($this);		
		if($nArgs > 1) {
			$i = 0;
			while($i < $nArgs) {
				// get parameter name
				$paramName = func_get_arg($i);
				$i++;				
				// get parameter value
				if($i < $nArgs) $paramValue = func_get_arg($i);
				else $paramValue = null;
				$returnValue->setFxpValue($paramName, $paramValue);
				$i++;
			}			
		}
		else if(isset($arr)) {
			if(!is_array($arr)) throw new ServiceException("arr should be an array of (parameterName => parameterValue)", ServiceException::INVALID_ARGUMENT);
			foreach($arr as $paramName => $paramValue) {
				$returnValue->setFxpValue($paramName, $paramValue);
			}
		}
		return $returnValue; 
	}
	
	/**
	 * Constructs a FuncExpParameterMappingList instance given an array of FuncExpParameterMapping
	 * This function supports variable number of arguments, that means that
	 * instead of providing an array with the mapping, you can pass the mapping
	 * in a comma separated list as a normal function call.
	 * example: $funcExpBuilder->fxpMapList(fxpMap1,fxpMap2, ...) is equivalent
	 * to $funcExpBuilder->fxpMapList(array(fxpMap1, fxpMap2, ...))
	 * @param Array $arr an array of FuncExpParameterMappings.
	 * @return FuncExpParameterMappingList
	 */
	public function fxpMapList($fxpMapArr) {
		$nArgs = func_num_args();		
		if($nArgs > 1) {
			$returnValue = FuncExpParameterMappingListArrayImpl::createInstance();
			for($i = 0; $i < $nArgs; $i++) {
				$returnValue->addFxpMapping(func_get_arg($i));
			}
		}
		else if(isset($fxpMapArr)) {
			$returnValue = FuncExpParameterMappingListArrayImpl::createInstance();
			if(!is_array($fxpMapArr)) $returnValue->addFxpMapping($fxpMapArr);
			else $returnValue->addFxpMappingArray($fxpMapArr);
		}
		else $returnValue = null;
		return $returnValue; 
	}
	
	/**
	 * Instiantiates the parameters with the values found in the given mapping.
	 * @param FuncExpParameterMapping $fxpMapping a FuncExpParameterMapping instance used to instanciate the declared parameters in the holder.
	 */
	public function instantiateParameters($fxpMapping) {
		if(isset($this->parameters)) {
			if(!isset($fxpMapping)) throw new FuncExpEvalException("fxpMapping cannot be null", FuncExpEvalException::INVALID_ARGUMENT);
			foreach($this->parameters as $paramName => $regParams) {
				// gets param value in mapping
				$paramValue = $fxpMapping->getFxpValue($paramName);
				// executes the setters
				foreach($regParams as $paramSetter) {									
					if($paramSetter instanceof Closure) $paramSetter($paramValue);
					else /* we have an array */ {
						$obj = $paramSetter['obj'];
						$key = $paramSetter['key'];
						$method = $paramSetter['method'];
						if(isset($obj)) {
							if(isset($key)) $obj->{$method}($key, $paramValue);	
							else $obj->{$method}($paramValue);
						}
						else {
							if(isset($key)) $method($key, $paramValue);	
							else $method($paramValue);
						}
					}
				}
			}
		}
	}		
	
	/**
	 * Registers a FuncExpParameter into this holder,
	 * associated to its setter method.
	 * The setter method can be either given by an object instance and a method name (string),
	 * or it can be a global function name, 
	 * or it can be a closure.
	 * @param FuncExpParameter $fxParameter the FuncExpParameter instance to register
	 * @param String|Closure $method the method name or a closure.
	 * @param Object $obj the object instance which holds the method.
	 * @param String|Number $key an optional additional key that should be passed as first argument to the setter method.
	 */
	public function registerParameter($fxParameter, $method, $obj=null, $key=null) {
		if(!isset($fxParameter)) throw new FuncExpEvalException("fxParameter cannot be null", FuncExpEvalException::INVALID_ARGUMENT);
		if(!isset($method)) throw new FuncExpEvalException("method cannot be null. It should be either a function name or a closure", FuncExpEvalException::INVALID_ARGUMENT);
		// validates method
		if(!($method instanceof Closure)) {
			if(isset($obj)) {
				if(!method_exists($obj, $method)) throw new FuncExpEvalException("method '$method' is not a valid instance method of the given object", FuncExpEvalException::INVALID_ARGUMENT);				
			}
			elseif(!function_exists($method)) throw new FuncExpEvalException("function '$method' is not a valid function", FuncExpEvalException::INVALID_ARGUMENT);
		}		
		if(!isset($this->parameters)) $this->parameters = array();
		$pName = $fxParameter->getName();	
		$regParams = $this->parameters[$pName];
		if(!isset($regParams)) $regParams = array();
		$regParams[] = array('method'=>$method, 'obj'=>$obj, 'key'=>$key);
		$this->parameters[$pName] = $regParams;
	}
	
	
}