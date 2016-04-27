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
 * A FuncExp library which enables to callback PHP code from within a func exp context 
 * Created by CWE on 20 octobre 2014
 */
class CallbackFL extends FuncExpVMAbstractFL
{
	// Runtime context
	
	/**
	 * @see FuncExpVMAbstractFL::getFuncExpVM()
	 */
	public function getFuncExpVM() {return parent::getFuncExpVM();}
	/**
	 * @see FuncExpVMAbstractFL::getFuncExpVMServiceProvider()
	 */
	public function getFuncExpVMServiceProvider() {return parent::getFuncExpVMServiceProvider();}
	/**
	 * @see FuncExpVMAbstractFL::getPrincipal()
	 */
	public function getPrincipal() {return parent::getPrincipal();}
	/**
	 * @see FuncExpVMAbstractFL::getNumberOfArgs()
	 */
	public function getNumberOfArgs($args) {return parent::getNumberOfArgs($args);}
	/**
	 * @see FuncExpVMAbstractFL::evaluateArg()
	 */
	public function evaluateArg($arg) {return parent::evaluateArg($arg);}
	/**
	 * @see FuncExpVMAbstractFL::evaluateFieldSelector()
	 */
	public function evaluateFieldSelector($fieldSelector, $defaultVal=null) {return parent::evaluateFieldSelector($fieldSelector, $defaultVal);}
	/**
	 * @see FuncExpVMAbstractFL::setValueInContext()
	 */
	public function setValueInContext($key, $value) {return parent::setValueInContext($key, $value);}
	/**
	 * @see FuncExpVMAbstractFL::getValueInContext()
	 */
	public function getValueInContext($key, $goThroughParents=true) {return parent::getValueInContext($key, $goThroughParents);}
	
	// Callback methods
	
	/**
	 * Executes a PHP callback from a Func Exp context.<br/>
	 * FuncExp signature : <code>callbackFx(callback, fxStyle, arg1, arg2, ...)</code><br/>
	 * Where arguments are :
	 * - Arg(0) callback : an instance of a CallableObject referencing the callback to be executed.
	 * - Arg(1) fxStyle : a boolean. If true, then the callback is called into a "func exp style", i.e. that the arguments 
	 * are not evaluated and passed as an array to the callback. 
	 * Else the callback is called in a "PHP style", i.e. that the arguments are evaluated before calling the callback and 
	 * passed as a comma separated list.
	 * - Arg(2..n) argI: the callback arguments. 
	 * 
	 * If fxStyle is true then the callback signature is : callback(callbackFL, args),
	 * where arguments are :
	 * - callbackFL: CallbackFL, a reference to the func exp executing environment that can be used to evaluate arguments, get the principal or other information.
	 * - args: Array, an array containing the function arguments. Each argument should be evaluated using the callbackFL->evaluateArg() method.
	 * 
	 * Else the callback signature is : callback(principal, valarg1, valarg2, ...),
	 * where arguments are :
	 * - principal: Principal, the principal executing the function
	 * - valarg1, ..., valargn: Any, the function arguments (already evaluated).
	 * 
	 * @return Any the return value of the callback is returned as the return value of this func exp.
	 */
	public function callbackFx($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException('callbackFx takes at least two arguments: the callback and the calling style.', FuncExpEvalException::INVALID_ARGUMENT);
		$callback = $args[0];
		if(!($callback instanceof CallableObject)) {
			$callback = $this->evaluateArg($callback);
			if(!($callback instanceof CallableObject)) throw new FuncExpEvalException('the callback argument should be a non null instance of CallableObject', FuncExpEvalException::INVALID_ARGUMENT);
		}
		// if funcExp calling style
		if($this->evaluateArg($args[1])) {
			// prepares arguments (without evaluation)
			$callbackArgs = array();
			for($i=2;$i<$nArgs;$i++) {
				$callbackArgs[] = $args[$i];
			}
			// invokes the callback and returns
			return $callback->invoke($this, $callbackArgs);
		}
		// else
		else {
			$callbackArgs = array();
			// adds current principal
			$callbackArgs[] = $this->getPrincipal();
			// prepares arguments (with evaluation)
			for($i=2;$i<$nArgs;$i++) {
				$callbackArgs[] = $this->evaluateArg($args[$i]);
			}
			// invokes the callback and returns
			return $callback->invokeWithArgsArray($callbackArgs);
		}
	}
}