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
 * A list of FieldWithSelectedSubfields
 * Created by CWE on 14 juin 2013
 */
interface FieldWithSelectedSubfieldsList extends ObjectList
{
	/**
	 * Adds a FieldWithSelectedSubfields to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field in the list
	 */
	public function addFieldWithSelectedSubfields($field);

	/**
	 * Fills the list by copying the FieldList, but using the FieldSelectorList if defined 
	 * for a given field, if field selector list defines some subfields, then only those will be selected,
	 * else if the field selector list selects the field without sufields defined, then all the subfields will be selected,
	 * else if the field selector list does not select the field or any subfield, then the field is not put in the list
	 */
	public function fillFromFieldList($fieldList, $fieldSelectorList=null);
	
	/**
	 * Returns a FieldWithSelectedSubfields stored in the list given its field name or null if not exist
	 */
	public function getFieldWithSelectedSubfields($fieldName);	
}