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
 * A field selector
 * Created by CWE on 19 août 09
 */
class FieldSelector extends Model
{
	private $fieldName;
	private $subFieldName;

	public static function createInstance($fieldName, $subFieldName = null)
	{
		$returnValue = new FieldSelector();
		$returnValue->setFieldName($fieldName);
		$returnValue->setSubFieldName($subFieldName);
		return $returnValue;
	}

	/**
	 * Creates a FieldSelector pointing on an Element attribute
	 * attribute name is a string as defined in Element->fillFromArray()
	 */
	public static function createElementAttributeSelector($attributeName)
	{
		return FieldSelector::createInstance("__element", $attributeName);
	}
	/**
	 * Returns true if FieldSelector is in fact an Element attribute selector
	 */
	public function isElementAttributeSelector()
	{
		return ($this->getFieldName() === "__element");
	}

	public function getFieldName()
	{
		return $this->fieldName;
	}
	public function setFieldName($fieldName)
	{
		$this->fieldName = $fieldName;
	}
	public function getSubFieldName()
	{
		return $this->subFieldName;
	}
	public function setSubFieldName($subFieldName)
	{
		$this->subFieldName = $subFieldName;
	}

	public function toString()
	{
		$fieldName = $this->getFieldName();
		$subFieldName = $this->getSubFieldName();
		if(isset($subFieldName)) $subFieldName = '.'.$subFieldName;
		return $fieldName.$subFieldName;
	}
}