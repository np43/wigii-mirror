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
 * A list of FuncExpParameter Mappings
 * Created by CWE on 28 novembre 2013
 */
interface FuncExpParameterMappingList extends ObjectList
{
	/**
	 * Addds a FuncExpParameterMapping instance to the list.
	 * @param String|Number $key an optional key under which the mapping should be stored,
	 * if null, then automatic integer keys will be generated, starting from 0.
	 */
	public function addFxpMapping($fxpMapping, $key=null);
	
	/**
	 * Adds an array of FuncExpParameterMapping to the list
	 * @param Array $arr an array of FuncExpParameterMapping instances.
	 * If the keys are not integers, then they are conserved. (similar to php array_merge function)
	 */
	public function addFxpMappingArray($arr);
}