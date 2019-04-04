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
 * A class which evaluates a Field Selector LogExp against the values of a record.
 * Returns true, if the log exp returns true.
 * Created by LWR on 06/06/2013
 * Modified by CWE on 11.09.2014 to support multi-valued objects (e.g. MultipleAttributes)
 */
class FieldSelectorLogExpRecordEvaluator extends FieldSelectorLogExpAbstractEvaluator
{
	private $_debugLogger;
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FieldSelectorLogExpRecordEvaluator");
		}
		return $this->_debugLogger;
	}
	
	protected $record;

	// Object lifecycle

	public function reset($record=null)
	{
		$this->freeMemory();
		$this->record = $record;
	}
	public function freeMemory() {
		unset($this->record);
	}

	// Service implementation

	/**
	 * @param Record $record a record
	 * @param LogExp $fsLogExp a field selector log exp
	 * @return true if record is matching fsLogExp
	 */
	public function evaluate($record, $fsLogExp)
	{
		if(isset($fsLogExp))
		{
			$this->reset($record);
			$returnValue = $fsLogExp->acceptLogExpVisitor($this);
			$this->freeMemory();
			return $returnValue;
		}
	}

	protected function getValue($obj){
		return $this->record->getFieldValue($obj->getFieldName(), $obj->getSubFieldName());
	}	
}