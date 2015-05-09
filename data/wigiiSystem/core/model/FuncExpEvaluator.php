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
 * Evaluates a FuncExp and returns its value
 * Created by CWE on 13 avr. 10
 */
interface FuncExpEvaluator
{
	/**
	 * Evaluates a FuncExp and returns its value
	 * @param $funcExp an instance of a FuncExp,
	 * or an instance of a FieldSelector for which to find the value, 
	 * or any object for which to retrieve a value (by default returns the object)
	 * @param $caller optionaly specifies which object is currently asking the evaluation of the FuncExp
	 * (this is used to detect loops when delegating FuncExp evaluation to chains of evaluators)
	 * Throws an exception in case of error.
	 */
	public function evaluateFuncExp($funcExp, $caller=null);
}