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

interface Matrix {
	
	/**
	 * get value object from a cell in the matrix
	 * @param rowId : string
	 * @param colId : string
	 */
	public function getValue($rowId, $colId);
	/**
	 * set the value from a cell in the matrix
	 * @param rowId : string
	 * @param colId : string
	 * @param $val : object, contains the value
	 */
	public function setValue($rowId, $colId, $val);
	
	/**
	 * iterate through one row
	 * @param rowId : string
	 */
	public function getRowIterator($rowId);
	/**
	 * iterate through one column
	 * @param colId : string
	 */
	public function getColIterator($colId);
	
	/**
	 * iterate through headers (ids) in one dimension
	 */
	public function getRowIdsIterator();
	public function getColIdsIterator();
	
	public function countRows();
	public function countCols();
	public function isEmpty();
	
	/**
	 * get header object
	 * @param rowId : string
	 * @param colId : string
	 */
	public function getRowHeader($rowId);
	public function getColHeader($colId);
	public function rowExists($rowId);
	public function colExists($colId);
	
	/**
	 * add a row
	 * @param rowId : string
	 * @param $header : object
	 */
	public function addRow($rowId, $header);
	/**
	 * add a column
	 * @param colId : string
	 * @param $header : object
	 */
	public function addCol($colId, $header);
	
	
}
 
 
 
 