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
 * A FuncExp parameter
 * Created by CWE on 28 novembre 2013
 */
class FuncExpParameter extends Model {	
	public static function createInstance($name) {
		$returnValue = new self();
		$returnValue->setName($name);
		return $returnValue;
	}
	
	// Dependency injection
	
	private $fxpHolder;
	/**
	 * Attaches a FuncExpParameterHolder to this func exp parameter
	 * @param FuncExpParameterHolder $fxpHolder
	 */
	public function setFuncExpParameterHolder($fxpHolder) {
		$this->fxpHolder = $fxpHolder;
	}
	
	// Attributes
	
	private $name;
	public function setName($name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	
	// Parameter registration
	
	/**
	 * Registers a setter method for this parameter.
	 * The setter method is stored into the attached FuncExpParameterHolder.
	 * The setter method can be either given by an object instance and a method name (string),
	 * or it can be a global function name, 
	 * or it can be a closure.
	 * @param String|Closure $method the method name or a closure.
	 * @param Object $obj the object instance which holds the method.
	 */
	public function registerSetterMethod($method, $obj=null) {
		if(!isset($this->fxpHolder)) throw new FuncExpEvalException("no attached FuncExpParameterHolder instance, please set one.", FuncExpEvalException::CONFIGURATION_ERROR);
		$this->fxpHolder->registerParameter($this, $method, $obj);
	}
	
	/**
	 * Registers an indexed setter method for this parameter.
	 * The setter method is stored into the attached FuncExpParameterHolder.
	 * The setter method can be either given by an object instance and a method name (string),
	 * or it can be a global function name, 
	 * or it can be a closure.
	 * @param String|Closure $method the method name or a closure.
	 * @param Object $obj the object instance which holds the method.
	 * @param String|Number $key the key used as first argument in the indexed setter method
	 * example: setValue($key, $funcExpParameter)
	 */
	public function registerIndexedSetterMethod($method, $obj=null, $key) {
		if(!isset($this->fxpHolder)) throw new FuncExpEvalException("no attached FuncExpParameterHolder instance, please set one.", FuncExpEvalException::CONFIGURATION_ERROR);
		$this->fxpHolder->registerParameter($this, $method, $obj, $key);
	}
}