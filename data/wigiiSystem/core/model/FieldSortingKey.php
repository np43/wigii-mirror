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
 * A field sorting key for a list
 * Created by CWE on 19 août 09
 */
class FieldSortingKey extends Model
{
	private $fieldName;
	private $subFieldName;
	private $ascending;

	public static function createInstance($fieldName, $subFieldName = null, $ascending=true)
	{
		$returnValue = new FieldSortingKey();
		$returnValue->setFieldName($fieldName);
		$returnValue->setSubFieldName($subFieldName);
		$returnValue->setAscending($ascending);
		return $returnValue;
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
	public function isAscending()
	{
		return $this->ascending;
	}
	public function setAscending($ascending)
	{
		$this->ascending = $ascending;
	}
}