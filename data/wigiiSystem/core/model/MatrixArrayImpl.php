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

/*
 * Created on 19 janv. 10
 * by LWR
 */
class MatrixColIteratorArrayArrayImpl implements Iterator {
	private $matrix;
	private $crtRowId;
	private $crtColId;
	public function __construct($matrix, $colId=null){
		if(!is_array($matrix)) throw new ServiceException("matrix is not implemented with 2 dimensions array", ServiceException::INVALID_ARGUMENT);
		if(!is_array(reset($matrix))) throw new ServiceException("matrix is not implemented with 2 dimensions array", ServiceException::INVALID_ARGUMENT);
		$this->matrix = $matrix;
		$this->crtRowId = key($matrix);
		reset($matrix[$this->crtRowId]);
		if(!is_array($matrix[$this->crtRowId])) throw new ServiceException("matrix is not implemented with 2 dimensions array", ServiceException::INVALID_ARGUMENT);
		
		if(isset($colId)){
			$this->setCrtCol($colId);
		} else {
			$this->crtColId = key($matrix[$this->crtRowId]);
		}
	}
	
	public function setCrtCol($colId){
		$this->rewind();
		if(!array_key_exists($colId, $this->matrix[$this->crtRowId])) throw new ServiceException("col $colId does not exist", ServiceException::INVALID_ARGUMENT);
		$this->crtColId = $colId;
	}
	public function current(){
		if($this->crtRowId === false) return false;
		return $this->matrix[$this->crtRowId][$this->crtColId];
	}
	public function key(){
		return $this->crtRowId;
	}
	public function next(){
		next($this->matrix);
		$this->crtRowId = key($this->matrix);
	}
	public function rewind(){
		reset($this->matrix);
		$this->crtRowId = key($this->matrix);
	}
	public function valid(){
		return array_key_exists($this->crtRowId, $this->matrix);
	}
	//implements ArrayAccess
//	public function offsetExists($offset){
//		return array_key_exists($this->crtRowId, $this->matrix);
//	}
//	public function offsetGet($offset){
//		return (array_key_exists($this->crtRowId, $this->matrix) ? $this->matrix[$offset][$this->crtColId] : null);
//	}
//	public function offsetSet($offset, $value){
//		$this->matrix[$offset][$this->crtColId] = $value;
//	}
//	public function offsetUnset($offset){
//		unset($this->matrix[$offset]);
//	}
}

class MatrixArrayImpl extends Model implements Matrix {
	
	private $matrixColIteratorArrayArrayImpl;
	private $matrix;
	private $rowHeaders;
	private $colHeaders;
	private $initialValue = null;
	
	public static function createInstance(){
		$r = new self();
		return $r;
	}
	
	public function setInitialValue($val){
		$this->initialValue = $val;
	}
	public function getInitialValue(){
		return $this->initialValue;
	}
	/**
	 * get value object from a cell in the matrix
	 * @param rowId : string
	 * @param colId : string
	 */
	public function getValue($rowId, $colId){
		if(!isset($this->matrix)) throw new ListException("empty matrix", ListException::OUT_OF_BOUND);
		if(!array_key_exists($rowId, $this->matrix)) throw new ListException("row $rowId does not exist", ListException::OUT_OF_BOUND);
		if(!array_key_exists($colId, $this->matrix[$rowId])) throw new ListException("col $colId does not exist", ListException::OUT_OF_BOUND);
		
		return $this->matrix[$rowId][$colId];
	}
	/**
	 * set the value from a cell in the matrix
	 * @param rowId : string
	 * @param colId : string
	 * @param $val : object, contains the value
	 */
	public function setValue($rowId, $colId, $val){
		if(!isset($this->matrix)) throw new ListException("empty matrix", ListException::OUT_OF_BOUND);
		if(!array_key_exists($rowId, $this->matrix)) throw new ListException("row $rowId does not exist", ListException::OUT_OF_BOUND);
		if(!array_key_exists($colId, $this->matrix[$rowId])) throw new ListException("col $colId does not exist", ListException::OUT_OF_BOUND);
		
		$this->matrix[$rowId][$colId] = $val;
	}
	
	/**
	 * iterate through one row
	 * @param rowId : string
	 */
	public function getRowIterator($rowId){
		if(!isset($this->matrix)) throw new ListException("empty matrix", ListException::OUT_OF_BOUND);
		if(!array_key_exists($rowId, $this->matrix)) throw new ListException("row $rowId does not exist", ListException::OUT_OF_BOUND);
		//if(!isset($this->matrix[$rowId][$colId])) throw new ListException("col $colId does not exist", ListException::OUT_OF_BOUND);
		
		return $this->matrix[$rowId];
	}
	/**
	 * iterate through one column
	 * @param colId : string
	 */
	public function getColIterator($colId){
		if(!isset($this->matrix)) throw new ListException("empty matrix", ListException::OUT_OF_BOUND);
		reset($this->matrix);
		$rowId = key($this->matrix);
		if(!array_key_exists($rowId, $this->matrix)) throw new ListException("no rows found", ListException::OUT_OF_BOUND);
		if(!array_key_exists($colId, $this->matrix[$rowId])) throw new ListException("col $colId does not exist", ListException::OUT_OF_BOUND);
		
		return $this->getMatrixColIteratorArrayArrayImpl($colId);
	}
	protected function getMatrixColIteratorArrayArrayImpl($colId=null){
		if(!isset($this->matrixColIteratorArrayArrayImpl)){
			$this->matrixColIteratorArrayArrayImpl = $this->createMatrixColIteratorArrayArrayImplInstance();
		}
		if(isset($colId)) $this->matrixColIteratorArrayArrayImpl->setCrtCol($colId);
		return $this->matrixColIteratorArrayArrayImpl;
	}
	protected function createMatrixColIteratorArrayArrayImplInstance($colId=null){
		return new MatrixColIteratorArrayArrayImpl($this->matrix, $colId);
	}
	
	/**
	 * iterate through row ids
	 */
	public function getRowIdsIterator(){
		if(!isset($this->rowHeaders)) throw new ListException("empty row headers", ListException::OUT_OF_BOUND);
		
		return array_keys($this->rowHeaders);
	}
	/**
	 * iterate through col ids
	 */
	public function getColIdsIterator(){
		if(!isset($this->colHeaders)) throw new ListException("empty column headers", ListException::OUT_OF_BOUND);
		
		return array_keys($this->colHeaders);
//		if(!isset($this->matrix)) throw new ListException("empty matrix", ListException::OUT_OF_BOUND);
//		reset($this->matrix);
//		$cols = current($this->matrix);
//		if(!is_array($cols)) throw new ListException("no columns found", ListException::OUT_OF_BOUND);
//		
//		return array_keys($cols);
	}
	
	public function countRows(){
		if(!isset($this->matrix)) return 0;
		if(!is_array($this->matrix)) return 0;
		
		return count($this->matrix);
	}
	public function countRowHeaders(){
		if(!isset($this->rowHeaders)) return 0;
		return count($this->rowHeaders);
	}
	/**
	 * this will reset the rows internal pointer
	 */
	public function countCols(){
		if(!isset($this->matrix)) return 0;
		reset($this->matrix);
		$cols = current($this->matrix);
		if(!is_array($cols)) return 0;
		
		return count($cols);
	}
	public function countColHeaders(){
		if(!isset($this->colHeaders)) return 0;
		return count($this->colHeaders);
	}
	
	public function isEmpty(){
		return !isset($this->matrix);
	}
	
	/**
	 * get header object
	 * @param rowId : string
	 * @param colId : string
	 */
	public function getRowHeader($rowId){
		if(!isset($this->rowHeaders)) throw new ListException("no row headers set", ListException::OUT_OF_BOUND);
		if(!array_key_exists($rowId, $this->rowHeaders)) throw new ListException("row header $rowId not found", ListException::OUT_OF_BOUND);
		return $this->rowHeaders[$rowId];
	}
	public function getColHeader($colId){
		if(!isset($this->colHeaders)) throw new ListException("no column headers set", ListException::OUT_OF_BOUND);
		if(!array_key_exists($colId, $this->colHeaders)) throw new ListException("column header $colId not found", ListException::OUT_OF_BOUND);
		return $this->colHeaders[$colId];
	}
	public function rowExists($rowId){
		if(!isset($this->rowHeaders)) return false;
		if(!array_key_exists($rowId, $this->rowHeaders)) return false;
		return true;
	}
	public function colExists($colId){
		if(!isset($this->colHeaders)) return false;
		if(!array_key_exists($colId, $this->colHeaders)) return false;
		return true;
	}
	
	/**
	 * add a row
	 * @param rowId : string
	 * @param $header : object
	 */
	public function addRow($rowId, $header){
		//create the header
		if(!isset($this->rowHeaders)) $this->rowHeaders = array();
		if(array_key_exists($rowId, $this->rowHeaders)) throw new ListException("row $rowId already exists", ListException::ALREADY_EXISTS);
		$this->rowHeaders[$rowId] = $header;
		
		//if cols are not seted --> do noting else
		//else create an empty line based on colHeaders
		if(isset($this->colHeaders)){
			if(!isset($this->matrix)) $this->matrix = array();
			$this->matrix[$rowId] = array_combine(array_keys($this->colHeaders), array_fill(0, count($this->colHeaders), $this->getInitialValue()));
		}
	}
	
	/**
	 * add a column
	 * @param colId : string
	 * @param $header : object
	 */
	public function addCol($colId, $header){
		//create the header
		if(!isset($this->colHeaders)) $this->colHeaders = array();
		if(array_key_exists($colId, $this->colHeaders)) throw new ListException("col $colId already exists", ListException::ALREADY_EXISTS);
		$this->colHeaders[$colId] = $header;
		
		//if rows are not seted --> do noting else
		//else create an empty col based on rowHeaders
		if(isset($this->rowHeaders)){
			if(!isset($this->matrix)) $this->matrix = array();
			foreach($this->rowHeaders as $id=>$header){
				if(!isset($this->matrix[$id])) $this->matrix[$id]=array();
				$this->matrix[$id][$colId] = $this->getInitialValue();
			}
		}
	}
	
	
}
 
 
 
 