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
 * A list of indicators
 * Created by LWR on 28 February 2011
 * Updated by Wigii.org(Lionel Weber) on October 2017 to support system indicators and recursive indicators
 */
interface IndicatorList extends ObjectList
{
	/**
	 * Adds an indicator to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field selector in the list
	 */
	public function addIndicator($fieldSelector, $dataType, $func, $label=null, $id=null, $isRecursive=false);
	public function addIndicatorInstance($indicator);

	/**
	 * Returns true if IndicatorList contains an Indicator with given id
	 */
	public function containsIndicator($id);
	
	/**
	 * Returns the given indicator with this id
	 */
	public function getIndicator($id);
	
	/**
	 * remove the given indicator
	 */
	public function removeIndicator($id);
	
	/**
	 * merge the given indicatorList to the current
	 */
	public function mergeIndicatorList($indicatorList);
}