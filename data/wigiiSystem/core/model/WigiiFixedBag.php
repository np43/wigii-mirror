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
 * A wigii fixed bag.
 * Created by CWE on 27 mai 2014
 */
interface WigiiFixedBag
{
	/**
	 * Returns the value stored into the wigii fixed bag.
	 * @param Int $elementId the element id for which to retrieve the value
	 * @param String $fieldName the field name for which to retrieve the value
	 * @param String $subFieldName an optional subfield name. If null, defaults to the "value" subfield.
	 * @return Any the value stored into the Wigii bag or null if no value defined for the given elementId, fieldname and subfieldname.
	 */
	public function getValue($elementId, $fieldName, $subFieldName=null);

	/**
	 * Copies the content of this wigii fixed bag into the given wigii bag.
	 * @param WigiiBag $wigiiBag the wigii bag into which to copy the values.
	 * This method calls iteratively $wigiiBag->setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName)
	 * with all the values stored into the wigii fixed bag.
	 */
	public function copyIntoWigiiBag($wigiiBag);
}