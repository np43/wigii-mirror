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
 * An iterator over a sequence of FuncExp 
 * Created by CWE on 3 octobre 2013
 */
interface FuncExpIterator
{		
	/**
	 * Returns the current evaluated func exp value
	 */
	public function getCurrentFuncExpValue();
	
	/**
	 * Evaluates the next func exp in the sequence and returns a FuncExpIterator instance
	 * which contains the value of the evaluated func exp and the next func exp to be executed.
	 * Returns null if there is no more FuncExp to be evaluated.
	 * @param Boolean $stepInto in case the evaluated func exp returns an iterator : if true then returns that iterator, 
	 * else returns an iterator which has as a value the iterator returned by the func exp and some next instruction to be executed.
	 */
	public function evaluateNextFuncExp($stepInto=true);
}