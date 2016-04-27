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
 * Maps a list of PerfLog database record to HTML
 * Created by CWE on 10.2.2013
 */
class PerfLog2HTMLMapper implements RowList
{
	private $lockedForUse = true;
	private $html;
	private $n;
	private $stack;
	private $stackIndex;
	
	// Instance creation
	
	public static function createInstance() {
		$returnValue = new PerfLog2HTMLMapper();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->html = '';
		$this->n = 0;
		$this->stack = array();
		$this->stackIndex = 0;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Implementation
	
	public function getHTML() {
		if($this->n > 0) {
			return '<ul>'.$this->html;
		}
		else return '';
	}
	
	protected function push($classname, $operation, $timestamp) {
		$stackContext = PerfLogStackContext::createInstance($classname.'.'.$operation, $timestamp);		
		$this->stackIndex++;
		$this->stack[$this->stackIndex] = $stackContext;		
		if($this->n == 0) $this->html = '</ul>';		
		$this->html = '</ul></li>'.$this->html;
	}
	protected function pop($classname, $operation, $timestamp, $id_perflog) {
		$op = $classname.'.'.$operation;
		while($this->stackIndex > 0 && $op != $this->stack[$this->stackIndex]->getOperationName()) {
			$this->stackIndex--;
		}
		if($this->stackIndex > 0) {
			$stackCtx = $this->stack[$this->stackIndex];
			$opDuration = $stackCtx->getEndTime() - $timestamp;
			$subcallDuration = $stackCtx->getSubcallDuration();			
			if($subcallDuration > 0) $this->html = " (subcalls duration =  $subcallDuration ms)<br/><ul>".$this->html;
			else $this->html = "<br/><ul>".$this->html;
			$this->html = "<li>$id_perflog# $op execution time = $opDuration ms".$this->html;
			$this->stackIndex--;
			if($this->stackIndex > 0) {
				$this->stack[$this->stackIndex]->updateSubcallDuration($opDuration);
			}
		}
		else {
			$this->html = "<li>Operation $op has a BEGIN without END".$this->html;
		}
	}
	
	// RowList implementation
	
	/**
	 * Adds a row to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same row in the list
	 */
	public function addRow($row) {
		if($row['operation'] != 'SQL') {
			// order is from the end, so BEGIN --> pop and END --> push
			switch($row['action']) {
				case 'BEGIN':
					$this->pop($row['classname'], $row['operation'], $row['timestamp'], $row['id_perflog']);
					$this->n++;
					break;
				case 'END':
					$this->push($row['classname'], $row['operation'], $row['timestamp']);
					$this->n++;
					break;
			}
		}
	}
	
	/**
	 * Returns an iterator on this list
	 * Compatible with the foreach control structure
	 */
	public function getListIterator() {
		throw new ServiceException(ServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * Returns true if the list is empty
	 */
	public function isEmpty() {
		return $this->n == 0;
	}

	/**
	 * Returns the number of items in the list
	 */
	public function count() {
		return $this->n;
	}
}
class PerfLogStackContext {
	private $operationName;
	private $endTime;
	private $subCallDuration;
	
	public static function createInstance($operationName, $endTime) {
		$returnValue = new PerfLogStackContext();
		$returnValue->operationName = $operationName;
		$returnValue->endTime = $endTime;
		$returnValue->subCallDuration = 0;
		return $returnValue;
	}
	
	public function getOperationName() {
		return $this->operationName;
	}
	public function getEndTime() {
		return $this->endTime;
	}
	public function getSubcallDuration() {
		return $this->subCallDuration;
	}
	public function updateSubcallDuration($callDuration) {
		$this->subCallDuration += $callDuration;
	}
}