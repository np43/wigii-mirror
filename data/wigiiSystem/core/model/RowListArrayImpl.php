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
 * RowList array implementation
 * Created by CWE on 13 janv. 10
 * Modified by CWE on 06.09.2019 to support key/value pairing for two columns rows
 */
class RowListArrayImpl extends ObjectListArrayImpl implements RowList
{
	protected $idColumnName;
	private $colVerified;
	protected $valueColumnName;

	/**
	 * @param String $idColumnName existing column in row which is the id, to be used as a key in the array.
	 * If null, then row are numerically indexed according to php default array behavior.
	 * @param String $valueColumnName existing column in row which will be used as the value to store in the array.
	 * If null, then complete rows are stored.
	 */
	public static function createInstance($idColumnName=null, $valueColumnName=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->idColumnName = $idColumnName;
		$returnValue->colVerified = false;
		$returnValue->valueColumnName = $valueColumnName;
		return $returnValue;
	}
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		// checks column definition
		if(!$this->colVerified) {
		    if(isset($this->idColumnName) && !array_key_exists($this->idColumnName, $row)) {
		        throw new ListException("id column name '".$this->idColumnName."' is invalid in record", ListException::INVALID_ARGUMENT);
		    }
		    if(isset($this->valueColumnName) && !array_key_exists($this->valueColumnName, $row)) {
		        throw new ListException("value column name '".$this->valueColumnName."' is invalid in record", ListException::INVALID_ARGUMENT);
		    }
		    $this->colVerified=true;
		}
		// extracts value to store
		if(isset($this->valueColumnName)) $value = $row[$this->valueColumnName];
		else $value = $row;
		// stores value in row list
		if(isset($this->idColumnName)) $this->objArray[$row[$this->idColumnName]] = $value;
		else $this->objArray[] = $value;
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
