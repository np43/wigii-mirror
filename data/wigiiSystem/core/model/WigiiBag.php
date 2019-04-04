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
 * The wigii bag
 * Created by CWE on 17 août 09
 */
interface WigiiBag
{
	/**
	 * Returns an element field value stored in the wigii bag
	 * @param Int $elementId the DB id of this element, can be null if it is a new element not stored yet in DB
	 * @param String $dataTypeName the name of the Wigii DataType of the field beeing read
	 * @param String $fieldName the name of the field beeing read
	 * @param String $subFieldName the dataType subfield name. If null, then uses the predefined "value" subfield
	 * @return mixed
	 */
	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null);

	/**
	 * Sets an element field value in the wigii bag. Replaces the actual value if already exists in the bag.
	 * @param String|Number $value the value stored into the field
	 * @param Int $elementId the DB id of this element, can be null if it is a new element not stored yet in DB
	 * @param String $dataTypeName the name of the Wigii DataType of the field beeing read
	 * @param String $fieldName the name of the field beeing read
	 * @param String $subFieldName the dataType subfield name. If null, then uses the predefined "value" subfield
	 */
	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null);

	/**
	 * Perform an operation with the value on the whole content in the wigii bag which match the dataTypeName, fieldName
	 * and subFieldName
	 */
	public function applyOperator($operator, $value, $dataTypeName, $fieldName, $subFieldName=null, $field=null);
}