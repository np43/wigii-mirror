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
 * A list of indicators
 * Created by LWR on 28 February 2011
 */
interface IndicatorList extends ObjectList
{
	/**
	 * Adds an indicator to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field selector in the list
	 */
	public function addIndicator($fieldSelector, $dataType, $func, $id=null);
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
}