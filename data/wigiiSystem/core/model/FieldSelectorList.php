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
 * A list of field selectors
 * Created by CWE on 19 août 09
 */
interface FieldSelectorList extends ObjectList
{
	/**
	 * Adds a field selector to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field selector in the list
	 */
	public function addFieldSelector($fieldName, $subFieldName=null);
	public function addFieldSelectorInstance($fieldSelector);

	/**
	 * Returns true if FieldSelectorList contains a FieldSelector with given fieldName and subFieldName
	 * if subFieldName is null then returns true only if exists a FieldSelector with only FieldName defined.
	 */
	public function containsFieldSelector($fieldName, $subFieldName=null);

	/**
	 * Sets an array of selected languages for multi language fields
	 */
	public function setSelectedLanguages($selectedLanguages);

	/**
	 * Returns an array of selected languages for multi language fields
	 */
	public function getSelectedLanguages();
}