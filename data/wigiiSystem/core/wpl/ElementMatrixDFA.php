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
 * A data flow activity which extracts rows of an Element Matrix
 * Created by CWE on 12.02.2016
 */
class ElementMatrixDFA extends ElementDFAWithFuncExpVM implements ValueList
{			
	private $dataFlowContext;
	
	// Object lifecycle
		
	public function reset() {
		$this->startIndex=null;
		$this->stopIndex=null;		
	}	
	public function freeMemory() {
		unset($this->startIndex);
		unset($this->stopIndex);
		unset($this->columns);
		unset($this->calculatedColumns);
		unset($this->dataFlowContext);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementMatrixDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $startIndex;
	/**
	 * Sets the start index from which to extract the matrix rows.
	 * @param int $i If not specified, then defaults to 1
	 */
	public function setFromRow($i) {
		$this->startIndex=$i;
	}
	private $stopIndex;
	/**
	 * Sets the stop index to which to extract the matrix rows.
	 * @param int $i If not specified, then defaults to all findable rows
	 */
	public function setToRow($i) {
		$this->stopIndex=$i;
	}
	private $columns;
	/**
	 * Sets the names of the columns to be extracted
	 * @param Array $cols an of strings.
	 * The extractor will search for fields having these specified names and suffixed with a row number.
	 * @example setColumns(array('A_','B_','C_'))
	 * will extracts fields A_1, B_1, C_1, A_2, B_2, C_2, ...
	 */
	public function setColumns($cols) {
		$this->columns=$cols;
	}
	
	private $calculatedColumns;
	/**
	 * Defines an optional set of calculated columns to be added to extracted rows
	 * @param Array $calCols an array of the form array(colName=>fx) specifying the name of the calculated column and the expression to calculate the value.
	 * The expression can combine field selectors pointing to real fields in the underlying Element or field selectors pointing to columns in the current row.
	 */
	public function setCalculatedColumns($calCols) {
		$this->calculatedColumns=$calCols;
	}
				
	// element event handling
	
	protected function processElement($elementP, $dataFlowContext) {
		$this->dataFlowContext = $dataFlowContext;
		// extracts the Record
		$rec = $elementP->getDbEntity();
		// extracts the Record matrix
		$rec->exportMatrix($this->columns, $this->startIndex, $this->stopIndex, $this);		
	}
	
	// ValueList implementation
	
	public function addValue($val) {
		// adds the calculated columns
		if(!empty($this->calculatedColumns)) {
			if(!$val) $val=(object)array();
			// loads current columns as local variables into new context
			if(!empty($val)) {
				$vmContext=$this->getCurrentFuncExpVM()->getFuncExpVMServiceProvider()->getFuncExpVMContext(true);
				foreach($val as $col=>$v) {
					$vmContext->setVariable(fs($col), $v);
				}
			}
			// computes the result of each calculated columns			
			foreach($this->calculatedColumns as $col=>$fx) {
				$val->{$col} = $this->evaluateFuncExp($fx);
			}
		}
		// pushes matrix row down in the data flow
		//$this->debugLogger()->write(json_encode($val));
		$this->dataFlowContext->writeResultToOutput($val, $this);
	}
	public function getListIterator() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
	public function isEmpty() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
	public function count() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
}