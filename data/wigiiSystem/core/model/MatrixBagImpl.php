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
 * Matrix implementation using an Array as a Bag
 * Created on 19 avril 2016 by CWE
 */
class MatrixBagImpl extends Model implements Matrix {
	protected $bag=array();
	protected $columnIds=array();
	protected $colHeaders=array();
	protected $rowIds=array();
	protected $rowHeaders=array();
	protected $nRows=0;
	protected $nCols=0;
	
	// Object lifecycle
	
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}
	
	// Matrix implementation
		
	public function getValue($rowId, $colId) {
		$ri = $this->rowIds[$rowId];
		$ci = $this->columnIds[$colId];
		if($ri && $ci) return $this->bag["($ri($ci))"];
		else throw new ListException("invalid index row '$rowId' and column '$colId'", ListException::OUT_OF_BOUND);
	}

	public function setValue($rowId, $colId, $val) {
		$ri = $this->rowIds[$rowId];
		$ci = $this->columnIds[$colId];
		if($ri && $ci) $this->bag["($ri($ci))"] = $val;
		else throw new ListException("invalid index row '$rowId' and column '$colId'", ListException::OUT_OF_BOUND);
	}
	
	public function getRowIterator($rowId) {
		$returnValue = MatrixBagImplIterator::createInstance();
		$returnValue->setMatrixBagImpl($this);
		$returnValue->setIds($this->columnIds);
		$returnValue->setListId($rowId);
		$returnValue->setIsColumnIterator(false);
		return $returnValue;
	}
	
	public function getColIterator($colId) {
		$returnValue = MatrixBagImplIterator::createInstance();
		$returnValue->setMatrixBagImpl($this);
		$returnValue->setIds($this->rowIds);
		$returnValue->setListId($colId);
		$returnValue->setIsColumnIterator(true);
		return $returnValue;
	}
	
	public function getRowIdsIterator() {
		return array_keys($this->rowIds);
	}
	
	public function getColIdsIterator() {
		return array_keys($this->columnIds);
	}
	
	public function countRows() {
		return $this->nRows;
	}
	
	public function countCols() {
		return $this->nCols;
	}
	
	public function isEmpty() {
		return empty($this->bag);
	}
	
	public function getRowHeader($rowId) {
		return $this->rowHeaders[$rowId];
	}
	
	public function getColHeader($colId) {
		return $this->colHeaders[$colId];
	}
	
	public function rowExists($rowId) {
		return isset($this->rowIds[$rowId]);
	}
	
	public function colExists($colId) {
		return isset($this->columnIds[$colId]);
	}
	
	public function addRow($rowId, $header) {
		if($this->rowExists($rowId)) throw new ListException("row with ID '$rowId' is already defined in the matrix", ListException::ALREADY_EXISTS);
		$this->rowIds[$rowId]=$this->nRows++;
		$this->rowHeaders[$rowId]=$header;
	}

	public function addCol($colId, $header) {
		if($this->colExists($colId)) throw new ListException("row with ID '$rowId' is already defined in the matrix", ListException::ALREADY_EXISTS);
		$this->columnIds[$colId]=$this->nCols++;
		$this->colHeaders[$colId]=$header;
	}	
	
	// implementation
		
	/**
	 * @param MatrixBagImplIterator $iterator
	 */
	public function askForCurrent ($iterator) {
		if(!isset($iterator)) throw new ListException('iterator cannot be null', ListException::INVALID_ARGUMENT);
		if($iterator->isColumnIterator()) {
			$rowId = $iterator->getId();
			$colId = $iterator->getListId();
		}
		else {
			$rowId = $iterator->getListId();
			$colId = $iterator->getId();
		}
		return $this->getValue($rowId, $colId);
	}	
}

/**
 * Matrix Bag Impl Iterator on rows or columns
 * Created on 20 avril 2016 by CWE
 */
class MatrixBagImplIterator implements Iterator {
	
	private $matrixBagImpl;
	private $ids;
	private $listId;
	private $isColIterator;
	
	// Object lifecycle
	
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}
	
	// Configuration
	
	/**
	 * @param MatrixBagImpl $m sets the MatrixBagImpl on which to run the Iterator
	 */
	public function setMatrixBagImpl($m) {
		$this->matrixBagImpl = $m;
	}
	
	/**
	 * @param Array $array injects a copy of the array of Ids on which to iterate
	 */
	public function setIds($array) {
		$this->ids = $array;
	}
	/**
	 * @return String Id of current step
	 */
	public function getId() {
		if(isset($this->ids)) {
			return key($this->ids);
		}
		else return null;
	}
	/**
	 * @param String $id sets the List ID (if iterates on a row, then row ID, if iterates on a column then column ID)
	 */
	public function setListId($id) {
		$this->listId = $id;
	}	
	/**
	 * @return String returns the List ID on which iteration is done 
	 * (if iterates on a row, then row ID, if iterates on a column then column ID)
	 */
	public function getListId() {
		return $this->listId;
	}
	/**
	 * @param Boolean $boolean if true indicates that iteration is on a column, 
	 * else iterates on a row
	 */
	public function setIsColumnIterator($boolean) {
		$this->isColIterator = $boolean;
	}
	/**
	 * @return Boolean if true indicates that iteration is on a column,
	 * else iterates on a row
	 */
	public function isColumnIterator() {
		return $this->isColIterator;
	}
	
	// Iterator implementation
	
	public function current () {
		if(isset($this->matrixBagImpl)) {			
			if($this->isColumnIterator()) {
				$rowId = $this->getId();
				$colId = $this->getListId();
			}
			else {
				$rowId = $this->getListId();
				$colId = $this->getId();
			}
			return $this->matrixBagImpl->getValue($rowId, $colId);
		}
		else return null;
	}
	
	public function next () {
		if(isset($this->ids)) next($this->ids);
	}
	
	public function key () {
		return $this->getId();
	}
	
	public function valid () {
		if(isset($this->matrixBagImpl)) {
			$id = $this->getId();
			$listId = $this->getListId();
			if($id && $listId) return true;
			else return false;
		}
		else return false;
	}
	
	public function rewind () {
		if(isset($this->ids)) {
			reset($this->ids);
			// checks validity of listId
			$listId = $this->getListId();
			if($listId && $this->matrixBagImpl) {
				if($this->isColumnIterator() && !$this->matrixBagImpl->colExists($listId)) throw new ListException("Column '$listId' does not exist", ListException::OUT_OF_BOUND);
				elseif(!$this->isColumnIterator() && !$this->matrixBagImpl->rowExists($listId)) throw new ListException("Row '$listId' does not exist", ListException::OUT_OF_BOUND);
			}
		}
	}
}