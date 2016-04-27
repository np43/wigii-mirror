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
 * RowList array implementation
 * Created by CWE on 13 janv. 10
 */
class RowListArrayImpl extends ObjectListArrayImpl implements RowList
{
	protected $idColumnName;
	private $colVerified;

	/**
	 * idColumnName: existing column in row which is the id, to be used as a key in the array.
	 * if null, then row are numerically indexed according to php default array behavior.
	 */
	public static function createInstance($idColumnName=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->idColumnName = $idColumnName;
		$returnValue->colVerified = false;
		return $returnValue;
	}
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		if(isset($this->idColumnName))
		{
			if(!$this->colVerified)
			{
				$this->colVerified = true;
				if(!array_key_exists($this->idColumnName, $row))
				{
					throw new ListException("id column name '".$this->idColumnName."' is invalid in record", ListException::INVALID_ARGUMENT);
				}
			}
			$this->objArray[$row[$this->idColumnName]] = $row;
		}
		else $this->objArray[] = $row;
	}
	/**
	 * if array was created with an idColumnName, then index should be a real database id
	 * else index is a numerical index.
	 */
	public function getRow($index)
	{
		return $this->objArray[$index];
	}
	
	public function getKeys(){
		if($this->objArray)	return array_combine(array_keys($this->objArray), array_keys($this->objArray));
		else return array();
	}
}
