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

/* interface Indicator Evaluator
 * an indicator evaluator evaluate datas according to indicator function
 * Created by LWR on 2 March 2011
 */
interface IndicatorEvaluator 
{
	/**
	 * check the integrity between the indicator function and 
	 * the supported implementation function
	 */
	public function isIndicatorFunctionSupported($indicator);
	
	/**
	 * return an array of Indicator supported function
	 */
	public function getSupportedIndicatorFunctions();
	
	/**
	 * evaluate all the data with the indicator function and return the value.
	 * update the indicator value
	 */
	public function evaluateIndicator($principal, $indicator);

}





