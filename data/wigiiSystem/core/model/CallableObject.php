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
 * A callable object supporting function calls, method calls on objects and closures.
 * Created by CWE on 8 décembre 2013
 * Medair (CWE) 04.07.2017: Fixed bug on invocation with first parameter null
 */
class CallableObject extends Model
{	
	private static $singleton;
	private $callable;

	/**
	 * Creates an instance of a callable object given a method and an optional object
	 * @param String|Closure $method the method name or function name or closure that should be called.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be called.
	 * @return CallableObject 
	 */
	public static function createInstance($method, $obj=null) {
		if($method instanceof CallableObject) return $method;
		else {
			$returnValue = new self();
			$returnValue->setMethod($method, $obj);
			return $returnValue;
		}
	}

	/**
	 * Sets the method that should be called.
	 * @param String|Closure $method the method name or function name or closure that should be called.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be called.	 
	 */
	public function setMethod($method, $obj=null) {
		if(!isset($method)) throw new ServiceException("method can not be null", ServiceException::INVALID_ARGUMENT);
		if(is_object($method) && $method instanceof Closure) $this->callable = $method;
		elseif(is_string($method) && $method != '') {
			if(is_null($obj)) {
				if(!function_exists($method)) throw new ServiceException("method '$method' is not a valid function", ServiceException::INVALID_ARGUMENT);
				else $this->callable = $method;
			}
			else {
				if(!method_exists($obj, $method)) throw new ServiceException("method '$method' is not a valid method of given object", ServiceException::INVALID_ARGUMENT);
				else $this->callable = array($obj, $method);
			}
		}
		else throw new ServiceException("method should be a closure or a valid method name", ServiceException::INVALID_ARGUMENT); 
	}	

	/**
	 * Invokes a method given a method name, an optional object and some arguments
	 * @param String|Closure $method the method name or function name or closure that should be invoked.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be invoked.
	 * @param mixed $args one or several arguments to pass to the method call as a comma separated list.
	 * Variable number of arguments is supported, that means that you can call CallableObject::invokeMethod($method, $obj, $arg1, $arg2, $arg3, ...)
	 * If you need to pass the arguments as an array, you should use the method CallableObject::invokeMethodWithArgArray($method, $obj, $args)
	 * @return mixed returns the return value of the invoked function
	 */
	public static function invokeMethod($method, $obj=null, $args=null) {
		if(isset($args)) $argsArr = array($args);
		else $argsArr = null;
		$nArgs = func_num_args();
		if($nArgs > 3) {
			for($i = 3; $i < $nArgs; $i++) {
				$argsArr[] = func_get_arg($i);
			}
		}
		return self::invokeMethodWithArgArray($method, $obj, $argsArr);
	}	
	
	/**
	 * Invokes a method given a method name, an optional object and some arguments
	 * @param String|Closure $method the method name or function name or closure that should be invoked.
	 * @param Object $obj if set, then should be the object instance which holds the method that should be invoked.
	 * @param mixed $args an array of arguments, for example: array($arg1, $arg2, $arg3, ...)
	 * If you need to pass several arguments as a comma separated list, use the method CallableObject::invokeMethod($method, $obj, $arg1, $arg2, $arg3, ...) instead
	 * @return mixed returns the return value of the invoked function
	 */
	public static function invokeMethodWithArgsArray($method, $obj=null, $args=null) {
		if(!isset(self::$singleton)) self::$singleton = new self();
		self::$singleton->setMethod($method, $obj);
		return self::$singleton->invokeWithArgsArray($args);
	}

	/**
	 * Invokes the method set into this callable object with a list of arguments
	 * @param mixed $args a comma separated list of arguments, for example $co->invokeMethod($arg1, $arg2, $arg3, ...);
	 * If you need to pass the arguments as an array, use the 'invokeWithArgsArray' method.
	 * @return mixed returns the return value of the invoked method
	 */
	public function invoke($args=null) {
	    if(isset($args) || func_num_args() > 1) $argsArr = func_get_args();
		else $argsArr = array();
		return $this->invokeWithArgsArray($argsArr); 
	}

	/**
	 * See PHP magic method __invoke.
	 * This allows you to use directly this callable object as a function,
	 * for example: 
	 * $co = CallableObject::createInstance($method, $obj);
	 * $returnValue = $co($arg1, $arg2, $arg3, ...);
	 */
	public function __invoke($args=null) {
	    if(isset($args) || func_num_args() > 1) $argsArr = func_get_args();
		else $argsArr = array();
		return $this->invokeWithArgsArray($argsArr);
	}
	
	/**
	 * Invokes the method set into this callable object with some arguments given as an array
	 * @param Array $args an array of arguments : array($arg1, $arg2, $arg3, ...)
	 * If you need to pass the arguments as a comma separated list, use the 'invoke' method
	 * @return mixed returns the return value of the invoked method
	 */
	public function invokeWithArgsArray($args=null) {
		if(!isset($this->callable)) throw new ServiceException("no callable method has been set", ServiceException::CONFIGURATION_ERROR);
		if(!isset($args)) $args = array();
		if(!is_array($args)) $args = array($args);
		return call_user_func_array($this->callable, $args);
	}
}


