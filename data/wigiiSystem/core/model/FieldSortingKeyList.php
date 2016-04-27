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
 * a list of FieldSortingKey
 * Created by CWE on 19 août 09
 */
interface FieldSortingKeyList extends ObjectList
{
	/**
	 * Adds a field sorting key to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field sorting key in the list
	 */
	public function addFieldSortingKey($fieldName, $subFieldName = null, $ascending=true);

	/**
	 * Adds a field sorting key on a specific attribute of the element as:
	 * sys_date, id, version... (as defined in Element->fillFromArray())
	 */
	public function addElementSortingKey($subFieldName, $ascending=true);

	/**
	 * Returns true if fieldSortingKey instance is an Element sorting key else false
	 */
	public function isElementSortingKey($fieldSortingKey);
}